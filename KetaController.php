<?php

namespace App\Http\Controllers;
use App\Models\KeetaBranch;
use App\Models\KeetaToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Exception;

class KetaController extends Controller
{
    private function getUrlForBrand($brandId)
    {
        Log::info('Getting URL for brand ID: ' . $brandId);
        switch ($brandId) {
            case 1:
                $hsMenuId = 15; // lcp
                break;
            case 2:
                $hsMenuId = 5; // cnd
                break;
            case 3:
                $hsMenuId = 10; //oks
                break;
            case 1004:
                $hsMenuId = 11; // psk
                break;
            default:
                $hsMenuId = null;
                break;
        }
        $url = "https://afco.althawaqh.com:97/api/services/app/hungerstation/GetRestaurantMenuByHsMenuId?HsMenuId={$hsMenuId}&brandId={$brandId}";
        Log::info('Generated URL: ' . $url);
        return $url;
    }


    private function getUrlForBrandB($aggregator_menu_id, $brandId)
    {
        $url = "https://afco.althawaqh.com:97/api/services/app/hungerstation/GetRestaurantMenuByHsMenuId?HsMenuId={$aggregator_menu_id}&brandId={$brandId}";
        Log::info('Generated Sara URL: ' . $url);
        return $url;
    }


    public function fetchMenuB($aggregator_menu_id, $brandId)
    {
        Log::info('Fetching menu for brand ID: ' . $brandId);
        Log::info('Fetching menu from Aggregator Menu ID: ' . $aggregator_menu_id);
        $url = $this->getUrlForBrandB($aggregator_menu_id, $brandId);

        try {
            Log::info('Making HTTP request to: ' . $url);
            $response = Http::timeout(600)->retry(3, 1000)->post($url);

            if ($response->successful()) {
                Log::info('Successfully fetched menu data');
                return $response->json();
            }
            else {
                Log::error('Failed to fetch menu. Status Code: ' . $response->status(), [
                    'url' => $url,
                    'response_body' => $response->body(),
                ]);
            }
        }
        catch (\Exception $e) {
            Log::error('Error fetching menu: ' . $e->getMessage(), [
                'url' => $url,
                'trace' => $e->getTraceAsString(),
            ]);
        }
        return null;
    }



    public function fetchMenu($brandId)
    {
        Log::info('Fetching menu for brand ID: ' . $brandId);
        $url = $this->getUrlForBrand($brandId);

        try {
            Log::info('Making HTTP request to: ' . $url);
            $response = Http::timeout(600)->retry(3, 1000)->post($url);

            if ($response->successful()) {
                Log::info('Successfully fetched menu data');
                return $response->json();
            }
            else {
                Log::error('Failed to fetch menu. Status Code: ' . $response->status(), [
                    'url' => $url,
                    'response_body' => $response->body(),
                ]);
            }
        }
        catch (\Exception $e) {
            Log::error('Error fetching menu: ' . $e->getMessage(), [
                'url' => $url,
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return null;
    }

    public function syncMenu(Request $request)
    {
        Log::info('Starting menu sync process');
        $brandId = $request->input('brand_id');
        Log::info('Brand ID: ' . $brandId);

        $menuData = $this->fetchMenu($brandId);

        if (!$menuData) {
            Log::error('Failed to fetch menu data for brand ID: ' . $brandId);
            return response()->json(['error' => 'Failed to fetch menu data'], 500);
        }

        $url = 'https://open.mykeeta.com/api/open/product/menu/sync';
        $timestamp = Carbon::now()->timestamp;

        Log::info('Processing menu data');
        $baseParams = $this->formatMenuData($menuData, $timestamp);

        Log::info('Fetching Keeta branches for brand ID: ' . $brandId);
        $keetaIds = KeetaBranch::where('brand_id', $brandId)->get();
        Log::info('Fetched Keeta branches: ' . $keetaIds->count());

        Log::info('Fetching latest token for brand ID: ' . $brandId);
        $latestToken = KeetaToken::where('brandId', $brandId)->latest()->first();
        Log::info('Latest token: ' . ($latestToken ? $latestToken->accessToken : 'No token found'));

        $responses = [];

        // Loop through each keeta_id and sync the menu
        foreach ($keetaIds as $keetaBranch) {
            $keetaId = $keetaBranch->keeta_id;
            Log::info("Processing Keeta ID: " . $keetaId);

            $currentParams = $baseParams;

            if ($keetaBranch->pos_system == 'sara') {
                if ($keetaBranch->aggregator_menu_id) {
                    $saraParams = $this->getKeetaParams($keetaBranch->aggregator_menu_id, $brandId, $timestamp);
                    if ($saraParams) {
                        $currentParams = $saraParams;
                    } else {
                        Log::warning("Failed to get Sara menu params for branch {$keetaId}, using base menu.");
                    }
                } else {
                    Log::warning("No aggregator_menu_id for Sara branch {$keetaId}, using base menu.");
                }
            }

            $currentParams['shopId'] = $keetaId; // Add the specific keeta_id for this loop iteration
            $currentParams['accessToken'] = $latestToken->accessToken;

            // Generate signature
            $sortedKeys = array_keys($currentParams);
            sort($sortedKeys);
            $str = [];
            foreach ($sortedKeys as $key) {
                if ($key == 'sig')
                    continue;
                $value = $currentParams[$key];
                $value = is_array($value) ? json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : $value;
                $str[] = $key . '=' . $value;
            }

            $paramsForSignature = implode('&', $str);
            $requestInfoForSignature = 'https://open.mykeeta.com/api/open/product/menu/sync?' . $paramsForSignature . env('YOUR_SECRET_KEY_HERE');

            $sig = hash('sha256', $requestInfoForSignature);
            $currentParams['sig'] = $sig;

            try {
                $response = Http::timeout(600)->retry(3, 1000)->post($url, $currentParams); // 60-second timeout, retry 3 times
                if ($response->successful()) {
                    $responses[$keetaId] = $response->json();
                }
                else {
                    Log::error('Failed to sync menu with external service', [
                        'keeta_id' => $keetaId,
                        'status' => $response->status(),
                        'response_body' => $response->body(),
                    ]);
                    $responses[$keetaId] = ['error' => 'Failed to sync menu data'];
                }
            }
            catch (Exception $e) {
                Log::error('Error syncing menu data', ['keeta_id' => $keetaId, 'exception' => $e]);
                $responses[$keetaId] = ['error' => 'Error syncing menu data'];
            }

        }
        return response()->json($responses);
    }


    // new function to push single branch menu
    public function syncMenuBranch(Request $request)
    {
        // Log::info('>>> ENTERED KetaController@syncMenu <<<');
        $brandId = $request->input('brand_id');
        Log::info("Value of Brand ID: " . $brandId);
        $pos_key = $request->input('pos_key');
        Log::info("Value of POS Key: " . $pos_key);

        if (!$brandId) {
            Log::info('Brand ID is missing in the request');
            return response()->json(['error' => 'Failed to get brand ID'], 500);
        }
        if (!$pos_key) {
            return response()->json(['error' => "Pos key not found"], 500);
        }

        $branch = KeetaBranch::where("pos_key", $pos_key)->first();
        if (!$branch) {
            Log::info('Branch not found for POS Key: ' . $pos_key);
            return response()->json(['error' => 'Failed to fetch branch details'], 500);
        }
        $timestamp = Carbon::now()->timestamp;

        if ($branch->pos_system == 'sara') {
            $aggregator_menu_id = $branch->aggregator_menu_id;
            if (!$aggregator_menu_id) {
                return response()->json(['error' => 'Failed to get Aggregator Menu ID'], 500);
            }
            Log::info("Value of Aggregator Menu ID: " . $aggregator_menu_id);
            $params = $this->getKeetaParams($aggregator_menu_id, $brandId, $timestamp);
            
            if (!$params) {
                return response()->json(['error' => 'Failed to fetch Sara menu data'], 500);
            }
        } else {
            $menuData = $this->fetchMenu($brandId);
            if (!$menuData) {
                return response()->json(['error' => 'Failed to fetch menu data'], 500);
            }
            $params = $this->formatMenuData($menuData, $timestamp);
        }

        $url = 'https://open.mykeeta.com/api/open/product/menu/sync';
        $latestToken = KeetaToken::where('brandId', $brandId)->latest()->first();

        $keetaId = $branch->keeta_id;

        Log::info("Processing Keeta ID: " . $keetaId);

        $params['shopId'] = $keetaId; // Add the specific keeta_id for this loop iteration
        $params['accessToken'] = $latestToken->accessToken;

        // Generate signature
        $sortedKeys = array_keys($params);
        sort($sortedKeys);
        $str = [];
        foreach ($sortedKeys as $key) {
            if ($key == 'sig')
                continue;
            $value = $params[$key];
            $value = is_array($value) ? json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : $value;
            $str[] = $key . '=' . $value;
        }

        $paramsForSignature = implode('&', $str);
        $requestInfoForSignature = 'https://open.mykeeta.com/api/open/product/menu/sync?' . $paramsForSignature . env('YOUR_SECRET_KEY_HERE');

        $sig = hash('sha256', $requestInfoForSignature);
        $params['sig'] = $sig;

        $responses = [];
        try {
            $response = Http::timeout(600)->retry(3, 1000)->post($url, $params); // 60-second timeout, retry 3 times
            if ($response->successful()) {
                $responses[$keetaId] = $response->json();
            } else {
                Log::error('Failed to sync menu with external service', [
                    'keeta_id' => $keetaId,
                    'status' => $response->status(),
                    'response_body' => $response->body(),
                ]);
                $responses[$keetaId] = ['error' => 'Failed to sync menu data'];
            }
        } catch (Exception $e) {
            Log::error('Error syncing menu data', ['keeta_id' => $keetaId, 'exception' => $e]);
            $responses[$keetaId] = ['error' => 'Error syncing menu data'];
        }

        return response()->json($responses);
    }



    public function store(Request $request)
    {
        $request->validate([
            'pos_key' => 'required|integer|max:99999999999|unique:keeta_branches,pos_key',
            'branch_name' => 'required|string|max:800|unique:keeta_branches,branch_name',
            'brand_id' => 'required|integer',
            'pos_system' => 'required|in:sara,sdm',
            'keeta_id' => 'required|integer|max:99999999999|unique:keeta_branches,keeta_id', // Validate the keeta_id field
        ], [
            'pos_key.unique' => 'The POS ID has already been taken. Please use a different one.',
            'keeta_id.unique' => 'The Keeta ID has already been taken. Please use a different one.',
        ]);


        KeetaBranch::create([
            'pos_key' => $request->pos_key,
            'branch_name' => $request->branch_name,
            'brand_id' => $request->brand_id,
            'keeta_id' => $request->keeta_id,
            'pos_system' => $request->pos_system,
        ]);

        return response()->json(['message' => 'Branch added successfully!']);
    }




}
