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
            } else {
                Log::error('Failed to fetch menu. Status Code: ' . $response->status(), [
                    'url' => $url,
                    'response_body' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
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
            } else {
                Log::error('Failed to fetch menu. Status Code: ' . $response->status(), [
                    'url' => $url,
                    'response_body' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
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

        $shopCategoryList = [];
        $choiceGroupList = [];
        $spuList = [];
        $usedShopCategoryCodes = [];
        $usedChoiceGroupCodes = [];
        $spuSequenceCodeMap = [];
        $usedSpuOpenItemCodes = [];

        Log::info('Processing menu data');
        foreach ($menuData['result'] as $menuCategory) {
            $shopCategoryOpenItemCode = $menuCategory['menuCategoryId'];

            if (!in_array($shopCategoryOpenItemCode, $usedShopCategoryCodes)) {
                $usedShopCategoryCodes[] = $shopCategoryOpenItemCode;

                $shopCategoryList[] = [
                    "id" => null,
                    "name" => $menuCategory['menuCategoryName'],
                    "type" => 0,
                    "description" => null,
                    "nameTranslateType" => 1,
                    "sourceLanguageType" => "en",
                    "targetLanguageType" => "ar",
                    "nameTranslation" => $menuCategory['arName'] ?? null,
                    "openItemCode" => $menuCategory['menuCategoryId'],
                    "availableTime" => [
                        "code" => 1,
                        "values" => ["00:00-23:59", "00:00-23:59", "00:00-23:59", "00:00-23:59", "00:00-23:59", "00:00-23:59", "00:00-23:59"]
                    ],
                    "descriptionTranslateType" => null,
                    "descriptionTranslation" => null,
                    "descSourceLanguageType" => null,
                    "descTargetLanguageType" => null
                ];
            }
            foreach ($menuCategory['menuItemList'] as $menuItem) {
                $choiceGroupOpenItemCodeList = [];

                foreach ($menuItem['modifierGroupsList'] as $modifierGroup) {
                    if (empty($modifierGroup['itemsList'])) {
                        continue;
                    }

                    $choiceGroupOpenItemCode = $modifierGroup['sdmModGroupId'];
                    $choiceGroupName = $modifierGroup['name'];
                    $maxNumber = $modifierGroup['maximum'] == 0 ? 1 : $modifierGroup['maximum'];

                    if (!in_array($choiceGroupOpenItemCode, $usedChoiceGroupCodes)) {
                        $usedChoiceGroupCodes[] = $choiceGroupOpenItemCode;

                        $choices = [];
                        foreach ($modifierGroup['itemsList'] as $item) {
                            if ($choiceGroupOpenItemCode == 10047 && in_array($item['sdmItemId'], [16003, 16002])) {
                                continue;
                            }
                            $choices[] = [
                                "name" => $item['itemName'],
                                "spuId" => null,
                                "price" => $item['price'],
                                "currency" => null,
                                "status" => 1,
                                "id" => $item['itemId'],
                                "openItemCode" => $item['sdmItemId'],
                                "nameTranslateType" => 1,
                                "sourceLanguageType" => "en",
                                "targetLanguageType" => "ar",
                                "nameTranslation" => $item['arName'] ?? null,
                                "pickPrice" => $item['price'],
                                "propertyList" => null
                            ];
                        }

                        $choiceGroupList[] = [
                            "id" => $modifierGroup['id'],
                            "name" => $choiceGroupName,
                            "minNumber" => $modifierGroup['minimum'],
                            "maxNumber" => $maxNumber,
                            "choiceGroupSkuList" => $choices,
                            "openItemCode" => $choiceGroupOpenItemCode,
                            "nameTranslateType" => 1,
                            "sourceLanguageType" => "en",
                            "targetLanguageType" => "ar",
                            "nameTranslation" => $modifierGroup['arName'] ?? null,
                            "repeatable" => 1
                        ];
                    }

                    $choiceGroupOpenItemCodeList[] = $choiceGroupOpenItemCode;
                }

                if (!in_array($menuItem['sdmItemId'], $usedSpuOpenItemCodes)) {
                    $usedSpuOpenItemCodes[] = $menuItem['sdmItemId'];

                    $spuList[] = [
                        "id" => $menuItem['menuItemId'],
                        "name" => $menuItem['menuItemName'],
                        "status" => 1,
                        "description" => $menuItem['menuItemDescription'],
                        "shopCategoryList" => [$menuCategory['menuCategoryId']],
                        "pictureList" => [
                            ["url" => "https://afco.althawaqh.com:89/FileStore/{$menuItem['imageURL']}"]
                        ],
                        "skuList" => [
                            [
                                "id" => $menuItem['sdmItemId'],
                                "spec" => $menuItem['spec'] ?? null,
                                "price" => $menuItem['price'],
                                "currency" => $menuItem['currency'] ?? 'SAR',
                                "choiceGroupList" => $menuItem['choiceGroupList'] ?? null,
                                "openItemCode" => $menuItem['sdmItemId'],
                                "specTranslateType" => $menuItem['specTranslateType'] ?? 1,
                                "specTranslation" => $menuItem['specTranslation'] ?? null,
                                "sourceLanguageType" => "en",
                                "targetLanguageType" => "ar",
                                "choiceGroupOpenItemCodeList" => $choiceGroupOpenItemCodeList,
                                "pickPrice" => $menuItem['pickPrice'] ?? $menuItem['price'],
                                "propertyList" => $menuItem['propertyList'] ?? null
                            ]
                        ],
                        "availableTime" => [
                            "code" => 1,
                            "values" => ["00:00-23:59", "00:00-23:59", "00:00-23:59", "00:00-23:59", "00:00-23:59", "00:00-23:59", "00:00-23:59"]
                        ],
                        "openItemCode" => $menuItem['sdmItemId'],
                        "nameTranslateType" => 1,
                        "sourceLanguageType" => "en",
                        "targetLanguageType" => "ar",
                        "nameTranslation" => $menuItem['arName'] ?? null,
                        "descriptionTranslateType" => 1,
                        "descriptionTranslation" => $menuItem['arDescription'] ?? null,
                        "descSourceLanguageType" => "en",
                        "descTargetLanguageType" => "ar",
                        "shopCategoryOpenItemCodeList" => [$shopCategoryOpenItemCode],
                        "userGetModeList" => ["delivery", "pickup"]
                    ];

                    $spuSequenceCodeMap[$shopCategoryOpenItemCode][] = $menuItem['sdmItemId'];
                }
            }
        }

        Log::info('Fetching Keeta branches for brand ID: ' . $brandId);
        $keetaIds = KeetaBranch::where('brand_id', $brandId)->get();
        Log::info('Fetched Keeta branches: ' . $keetaIds->count());

        Log::info('Fetching latest token for brand ID: ' . $brandId);
        $latestToken = KeetaToken::where('brandId', $brandId)->latest()->first();
        Log::info('Latest token: ' . ($latestToken ? $latestToken->accessToken : 'No token found'));

        $params = [
            "appId" => "3493808037",
            "timestamp" => $timestamp,
            "shopCategoryList" => $shopCategoryList,
            "choiceGroupList" => $choiceGroupList,
            "spuList" => $spuList,
            "spuSequenceCodeMap" => $spuSequenceCodeMap,
            "spuExternalDataMap" => "null",
            "skuExternalDataMap" => "null",
            "groupExternalDataMap" => "null"
        ];
        //return $params;

        // Dispatch jobs for each Keeta branch
        foreach ($keetaIds as $keetaBranch) {
            dispatch(new \App\Jobs\SyncKeetaMenuJob(
                $keetaBranch->keeta_id,
                $params,
                $latestToken->accessToken,
                $brandId
            ));
        }

        Log::info('Dispatched ' . $keetaIds->count() . ' sync jobs to the queue');
        return response()->json([
            'message' => 'Menu sync jobs have been queued for processing',
            'branches_count' => $keetaIds->count()
        ]);
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
        $aggregator_menu_id = $branch->aggregator_menu_id;
        if (!$aggregator_menu_id) {
            return response()->json(['error' => 'Failed to get Aggregator Menu ID'], 500);
        }
        Log::info("Value of Aggregator Menu ID: " . $aggregator_menu_id);
        $menuData = $this->fetchMenuB($aggregator_menu_id, $brandId);



        Log::info("Menu info: ", $menuData);

        if (!$menuData) {
            return response()->json(['error' => 'Failed to fetch menu data'], 500);
        }

        $url = 'https://open.mykeeta.com/api/open/product/menu/sync';
        $timestamp = Carbon::now()->timestamp;

        $shopCategoryList = [];
        $choiceGroupList = [];
        $spuList = [];
        $usedShopCategoryCodes = [];
        $usedChoiceGroupCodes = [];
        $spuSequenceCodeMap = [];
        $usedSpuOpenItemCodes = [];

        foreach ($menuData['result'] as $menuCategory) {
            $shopCategoryOpenItemCode = $menuCategory['menuCategoryId'];

            if (!in_array($shopCategoryOpenItemCode, $usedShopCategoryCodes)) {
                $usedShopCategoryCodes[] = $shopCategoryOpenItemCode;

                $shopCategoryList[] = [
                    "id" => null,
                    "name" => $menuCategory['menuCategoryName'],
                    "type" => 0,
                    "description" => null,
                    "nameTranslateType" => 1,
                    "sourceLanguageType" => "en",
                    "targetLanguageType" => "ar",
                    "nameTranslation" => $menuCategory['arName'] ?? null,
                    "openItemCode" => $menuCategory['menuCategoryId'],
                    "availableTime" => [
                        "code" => 1,
                        "values" => ["00:00-23:59", "00:00-23:59", "00:00-23:59", "00:00-23:59", "00:00-23:59", "00:00-23:59", "00:00-23:59"]
                    ],
                    "descriptionTranslateType" => null,
                    "descriptionTranslation" => null,
                    "descSourceLanguageType" => null,
                    "descTargetLanguageType" => null
                ];
            }
            foreach ($menuCategory['menuItemList'] as $menuItem) {
                $choiceGroupOpenItemCodeList = [];

                foreach ($menuItem['modifierGroupsList'] as $modifierGroup) {
                    if (empty($modifierGroup['itemsList'])) {
                        continue;
                    }

                    $choiceGroupOpenItemCode = $modifierGroup['sdmModGroupId'];
                    $choiceGroupName = $modifierGroup['name'];
                    // Ensure maxNumber is not 0
                    $maxNumber = $modifierGroup['maximum'] == 0 ? 1 : $modifierGroup['maximum'];
                    //$maxNumber = ($choiceGroupOpenItemCode == 10037) ? 1 : ($modifierGroup['maximum'] == 0 ? 1 : $modifierGroup['maximum']);

                    if (!in_array($choiceGroupOpenItemCode, $usedChoiceGroupCodes)) {
                        $usedChoiceGroupCodes[] = $choiceGroupOpenItemCode;

                        $choices = [];
                        foreach ($modifierGroup['itemsList'] as $item) {
                            // Skip items with openItemCode 16003 and 16002 if the modifierGroup's openItemCode is 10047
                            if ($choiceGroupOpenItemCode == 10047 && in_array($item['sdmItemId'], [16003, 16002])) {
                                continue;
                            }
                            $choices[] = [
                                "name" => $item['itemName'],
                                "spuId" => null,
                                "price" => $item['price'],
                                "currency" => null,
                                "status" => 1,
                                "id" => $item['itemId'],
                                "openItemCode" => $item['sdmItemId'],
                                "nameTranslateType" => 1,
                                "sourceLanguageType" => "en",
                                "targetLanguageType" => "ar",
                                "nameTranslation" => $item['arName'] ?? null,
                                "pickPrice" => $item['price'],
                                "propertyList" => null
                            ];
                        }

                        $choiceGroupList[] = [
                            "id" => $modifierGroup['id'],
                            "name" => $choiceGroupName,
                            "minNumber" => $modifierGroup['minimum'],
                            "maxNumber" => $maxNumber,
                            "choiceGroupSkuList" => $choices,
                            "openItemCode" => $choiceGroupOpenItemCode,
                            "nameTranslateType" => 1,
                            "sourceLanguageType" => "en",
                            "targetLanguageType" => "ar",
                            "nameTranslation" => $modifierGroup['arName'] ?? null,
                            "repeatable" => 1
                        ];
                    }

                    $choiceGroupOpenItemCodeList[] = $choiceGroupOpenItemCode;
                }

                if (!in_array($menuItem['sdmItemId'], $usedSpuOpenItemCodes)) {
                    $usedSpuOpenItemCodes[] = $menuItem['sdmItemId'];

                    $spuList[] = [
                        "id" => $menuItem['menuItemId'],
                        "name" => $menuItem['menuItemName'],
                        "status" => 1,
                        "description" => $menuItem['menuItemDescription'],
                        "shopCategoryList" => [$menuCategory['menuCategoryId']],
                        "pictureList" => [
                            ["url" => $this->resolveImageUrl($menuItem['imageURL'])]
                        ],
                        "skuList" => [
                            [
                                "id" => $menuItem['sdmItemId'],
                                "spec" => $menuItem['spec'] ?? null,
                                "price" => $menuItem['price'],
                                "currency" => $menuItem['currency'] ?? 'SAR',
                                "choiceGroupList" => $menuItem['choiceGroupList'] ?? null,
                                "openItemCode" => $menuItem['sdmItemId'],
                                "specTranslateType" => $menuItem['specTranslateType'] ?? 1,
                                "specTranslation" => $menuItem['specTranslation'] ?? null,
                                "sourceLanguageType" => "en",
                                "targetLanguageType" => "ar",
                                "choiceGroupOpenItemCodeList" => $choiceGroupOpenItemCodeList,
                                "pickPrice" => $menuItem['pickPrice'] ?? $menuItem['price'],
                                "propertyList" => $menuItem['propertyList'] ?? null
                            ]
                        ],
                        "availableTime" => [
                            "code" => 1,
                            "values" => ["00:00-23:59", "00:00-23:59", "00:00-23:59", "00:00-23:59", "00:00-23:59", "00:00-23:59", "00:00-23:59"]
                        ],
                        "openItemCode" => $menuItem['sdmItemId'],
                        "nameTranslateType" => 1,
                        "sourceLanguageType" => "en",
                        "targetLanguageType" => "ar",
                        "nameTranslation" => $menuItem['arName'] ?? null,
                        "descriptionTranslateType" => 1,
                        "descriptionTranslation" => $menuItem['arDescription'] ?? null,
                        "descSourceLanguageType" => "en",
                        "descTargetLanguageType" => "ar",
                        "shopCategoryOpenItemCodeList" => [$shopCategoryOpenItemCode],
                        "userGetModeList" => ["delivery", "pickup"]
                    ];

                    $spuSequenceCodeMap[$shopCategoryOpenItemCode][] = $menuItem['sdmItemId'];
                }
            }
        }
        Log::info("SpuList: ", $spuList);
        $latestToken = KeetaToken::where('brandId', $brandId)->latest()->first();

        $params = [
            "appId" => "3493808037",
            "timestamp" => $timestamp,
            "shopCategoryList" => $shopCategoryList,
            "choiceGroupList" => $choiceGroupList,
            "spuList" => $spuList,
            "spuSequenceCodeMap" => $spuSequenceCodeMap,
            "spuExternalDataMap" => "null",
            "skuExternalDataMap" => "null",
            "groupExternalDataMap" => "null"
        ];

        $keetaId = $branch->keeta_id;

        Log::info("shop id: " . $keetaId);

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
            // }

            $paramsForSignature = implode('&', $str);
            $requestInfoForSignature = 'https://open.mykeeta.com/api/open/product/menu/sync?' . $paramsForSignature . env('YOUR_SECRET_KEY_HERE');

            $sig = hash('sha256', $requestInfoForSignature);
            $params['sig'] = $sig;
            Log::info('Prepared payload', [
                'shop_id' => $keetaId,
                'categories' => count($shopCategoryList),
                'spu_count' => count($spuList),
            ]);
            Log::info('Sending menu to Keeta', [
                'shop_id' => $keetaId,
                'signature_generated' => isset($params['sig'])
            ]);
            Log::info('FINAL PAYLOAD TO KEETA', [
                'shop_id' => $keetaId,
                'payload' => json_encode($params, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            ]);
            // Log::info("Data sent to keeta: ", $params);
            try {
                $response = Http::timeout(600)->retry(3, 1000)->post($url, $params); // 60-second timeout, retry 3 times
                Log::info('KEETA RESPONSE', [
                    'shop_id' => $keetaId,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                if ($response->successful()) {
                    Log::info("Successfully synced menu for Keeta ID: {$keetaId}");
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
        }
        return response()->json($responses);
    }

    private function resolveImageUrl($imageUrl)
    {
        if (!$imageUrl) {
            return null;
        }

        // If it's already a valid full URL → return as-is
        if (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            return $imageUrl;
        }

        // Otherwise treat it as filename
        return "https://afco.althawaqh.com:89/FileStore/{$imageUrl}";
    }

    public function syncSaraBranches(Request $request)
    {
        $brandReferenceId = $request->input('brand_reference_id');
        $brandId = $request->input('brand_id');
        // $brandId = 2;

        Log::info('Starting SARA menu sync for Brand ID: ' . $brandId);

        if (!$brandReferenceId) {
            Log::info('Brand Reference ID is missing in the request');
            return response()->json(['error' => 'Failed to get brand ID'], 500);
        }

        if (!$brandId) {
            Log::info('Brand ID is missing in the request');
            return response()->json(['error' => 'Failed to get brand ID'], 500);
        }

        $saraBranches = KeetaBranch::where('brand_id', $brandId)
            ->where('pos_system', 'sara')
            ->get();

        Log::info('Fetched Keeta branches: ' . $saraBranches->count());
        Log::info('Fetched Keeta branches: ' . $saraBranches);

        if ($saraBranches->isEmpty()) {
            Log::info('No SARA branches found for Brand ID: ' . $brandId);
            return response()->json(['message' => 'No SARA branches found for this brand'], 404);
        }

        $latestToken = KeetaToken::where('brandId', $brandReferenceId)->latest()->first();
        Log::info('Latest token: ' . ($latestToken ? $latestToken->accessToken : 'No token found'));

        $firstSaraBranch = $saraBranches->first();
        $aggregator_menu_id = $firstSaraBranch->aggregator_menu_id;

        if (!$aggregator_menu_id) {
            Log::warning("No aggregator_menu_id found for any SARA branches under Brand {$brandId}.");
            return response()->json(['error' => 'No aggregator_menu_id found for SARA branches'], 404);
        }

        Log::info("Fetching SARA menu using aggregator ID: {$aggregator_menu_id}");
        $menuData = $this->fetchMenuB($aggregator_menu_id, $brandReferenceId);

        if (!$menuData) {
            Log::error("Failed to fetch Sara menu data for Brand {$brandReferenceId}");
            return response()->json(['error' => 'Failed to fetch Sara menu data'], 500);
        }

        $timestamp = Carbon::now()->timestamp;

        $shopCategoryList = [];
        $choiceGroupList = [];
        $spuList = [];
        $usedShopCategoryCodes = [];
        $usedChoiceGroupCodes = [];
        $spuSequenceCodeMap = [];
        $usedSpuOpenItemCodes = [];

        Log::info('Processing menu data');
        foreach ($menuData['result'] as $menuCategory) {
            $shopCategoryOpenItemCode = $menuCategory['menuCategoryId'];

            if (!in_array($shopCategoryOpenItemCode, $usedShopCategoryCodes)) {
                $usedShopCategoryCodes[] = $shopCategoryOpenItemCode;

                $shopCategoryList[] = [
                    "id" => null,
                    "name" => $menuCategory['menuCategoryName'],
                    "type" => 0,
                    "description" => null,
                    "nameTranslateType" => 1,
                    "sourceLanguageType" => "en",
                    "targetLanguageType" => "ar",
                    "nameTranslation" => $menuCategory['arName'] ?? null,
                    "openItemCode" => $menuCategory['menuCategoryId'],
                    "availableTime" => [
                        "code" => 1,
                        "values" => ["00:00-23:59", "00:00-23:59", "00:00-23:59", "00:00-23:59", "00:00-23:59", "00:00-23:59", "00:00-23:59"]
                    ],
                    "descriptionTranslateType" => null,
                    "descriptionTranslation" => null,
                    "descSourceLanguageType" => null,
                    "descTargetLanguageType" => null
                ];
            }
            foreach ($menuCategory['menuItemList'] as $menuItem) {
                $choiceGroupOpenItemCodeList = [];

                foreach ($menuItem['modifierGroupsList'] as $modifierGroup) {
                    if (empty($modifierGroup['itemsList'])) {
                        continue;
                    }

                    $choiceGroupOpenItemCode = $modifierGroup['sdmModGroupId'];
                    $choiceGroupName = $modifierGroup['name'];
                    $maxNumber = $modifierGroup['maximum'] == 0 ? 1 : $modifierGroup['maximum'];

                    if (!in_array($choiceGroupOpenItemCode, $usedChoiceGroupCodes)) {
                        $usedChoiceGroupCodes[] = $choiceGroupOpenItemCode;

                        $choices = [];
                        foreach ($modifierGroup['itemsList'] as $item) {
                            if ($choiceGroupOpenItemCode == 10047 && in_array($item['sdmItemId'], [16003, 16002])) {
                                continue;
                            }
                            $choices[] = [
                                "name" => $item['itemName'],
                                "spuId" => null,
                                "price" => $item['price'],
                                "currency" => null,
                                "status" => 1,
                                "id" => $item['itemId'],
                                "openItemCode" => $item['sdmItemId'],
                                "nameTranslateType" => 1,
                                "sourceLanguageType" => "en",
                                "targetLanguageType" => "ar",
                                "nameTranslation" => $item['arName'] ?? null,
                                "pickPrice" => $item['price'],
                                "propertyList" => null
                            ];
                        }

                        $choiceGroupList[] = [
                            "id" => $modifierGroup['id'],
                            "name" => $choiceGroupName,
                            "minNumber" => $modifierGroup['minimum'],
                            "maxNumber" => $maxNumber,
                            "choiceGroupSkuList" => $choices,
                            "openItemCode" => $choiceGroupOpenItemCode,
                            "nameTranslateType" => 1,
                            "sourceLanguageType" => "en",
                            "targetLanguageType" => "ar",
                            "nameTranslation" => $modifierGroup['arName'] ?? null,
                            "repeatable" => 1
                        ];
                    }

                    $choiceGroupOpenItemCodeList[] = $choiceGroupOpenItemCode;
                }

                if (!in_array($menuItem['sdmItemId'], $usedSpuOpenItemCodes)) {
                    $usedSpuOpenItemCodes[] = $menuItem['sdmItemId'];

                    $spuList[] = [
                        "id" => $menuItem['menuItemId'],
                        "name" => $menuItem['menuItemName'],
                        "status" => 1,
                        "description" => $menuItem['menuItemDescription'],
                        "shopCategoryList" => [$menuCategory['menuCategoryId']],
                        "pictureList" => [
                            ["url" => "{$menuItem['imageURL']}"]
                        ],
                        "skuList" => [
                            [
                                "id" => $menuItem['sdmItemId'],
                                "spec" => $menuItem['spec'] ?? null,
                                "price" => $menuItem['price'],
                                "currency" => $menuItem['currency'] ?? 'SAR',
                                "choiceGroupList" => $menuItem['choiceGroupList'] ?? null,
                                "openItemCode" => $menuItem['sdmItemId'],
                                "specTranslateType" => $menuItem['specTranslateType'] ?? 1,
                                "specTranslation" => $menuItem['specTranslation'] ?? null,
                                "sourceLanguageType" => "en",
                                "targetLanguageType" => "ar",
                                "choiceGroupOpenItemCodeList" => $choiceGroupOpenItemCodeList,
                                "pickPrice" => $menuItem['pickPrice'] ?? $menuItem['price'],
                                "propertyList" => $menuItem['propertyList'] ?? null
                            ]
                        ],
                        "availableTime" => [
                            "code" => 1,
                            "values" => ["00:00-23:59", "00:00-23:59", "00:00-23:59", "00:00-23:59", "00:00-23:59", "00:00-23:59", "00:00-23:59"]
                        ],
                        "openItemCode" => $menuItem['sdmItemId'],
                        "nameTranslateType" => 1,
                        "sourceLanguageType" => "en",
                        "targetLanguageType" => "ar",
                        "nameTranslation" => $menuItem['arName'] ?? null,
                        "descriptionTranslateType" => 1,
                        "descriptionTranslation" => $menuItem['arDescription'] ?? null,
                        "descSourceLanguageType" => "en",
                        "descTargetLanguageType" => "ar",
                        "shopCategoryOpenItemCodeList" => [$shopCategoryOpenItemCode],
                        "userGetModeList" => ["delivery", "pickup"]
                    ];

                    $spuSequenceCodeMap[$shopCategoryOpenItemCode][] = $menuItem['sdmItemId'];
                }
            }
        }

        $params = [
            "appId" => "3493808037",
            "timestamp" => $timestamp,
            "shopCategoryList" => $shopCategoryList,
            "choiceGroupList" => $choiceGroupList,
            "spuList" => $spuList,
            "spuSequenceCodeMap" => $spuSequenceCodeMap,
            "spuExternalDataMap" => "null",
            "skuExternalDataMap" => "null",
            "groupExternalDataMap" => "null"
        ];

        foreach ($saraBranches as $branch) {
            dispatch(new \App\Jobs\SyncKeetaMenuJob(
                $branch->keeta_id,
                $params,
                $latestToken->accessToken,
                $brandId
            ));
        }

        Log::info('Dispatched ' . $saraBranches->count() . ' SARA sync jobs to the queue');
        return response()->json([
            'message' => 'SARA Menu sync jobs have been queued for processing',
            'branches_count' => $saraBranches->count()
        ]);
    }


    public function store(Request $request)
    {
        $request->validate([
            'pos_key' => 'required|integer|max:99999999999|unique:keeta_branches,pos_key',
            'branch_name' => 'required|string|max:800|unique:keeta_branches,branch_name',
            'brand_id' => 'required|integer',
            'pos_system' => 'nullable|string',
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
            'conceptID' => $request->conceptID,
            'menuTemplateID' => $request->menuTemplateID
        ]);

        return response()->json(['message' => 'Branch added successfully!']);
    }
}
