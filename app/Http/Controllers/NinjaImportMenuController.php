<?php

namespace App\Http\Controllers;

use App\Models\NinjaBranch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NinjaImportMenuController extends Controller
{

    public function syncMenuAction(Request $request)
    {
        try {
            $posId = $request->input('pos_id');

            // Fetch and transform menu
            $fetchedMenu = $this->fetchMenu();
            if ($fetchedMenu) {
                $transformedMenu = $this->transformMenu($fetchedMenu);

                if ($transformedMenu) {
                    // Append POS ID to branch_ids
                    $transformedMenu['branch_ids'][] = $posId;

                    // Push the updated menu
                    $response = $this->pushMenu($transformedMenu);

                    if ($response) {
                        return response()->json(['message' => 'Menu synced successfully!']);
                    }
                    else {
                        Log::error('Failed to push the transformed menu.', [
                            'transformedMenu' => $transformedMenu,
                            'fetchedMenu' => $fetchedMenu,
                        ]);
                    }
                }
                else {
                    Log::error('Failed to transform the fetched menu.', [
                        'fetchedMenu' => $fetchedMenu,
                    ]);
                }
            }
            else {
                Log::error('Failed to fetch the menu.');
            }
        }
        catch (\Exception $e) {
            Log::error('Error in syncMenuAction method: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return response()->json(['message' => 'Failed to sync menu.'], 500); // Return an error message or an empty response
    }

    protected $brands = [
        1 => ['name_en' => 'AFCO - Lacasa Pasta', 'name_ar' => 'AFCO - Lacasa Pasta'],
        2 => ['name_en' => 'CND Brand', 'name_ar' => 'CND Brand'],
        3 => ['name_en' => 'Okashi Brand', 'name_ar' => 'Okashi Brand'],
        1004 => ['name_en' => 'Poshak Brand', 'name_ar' => 'Poshak Brand'],
    ];

    private function getUrlForBrand($brandId)
    {
        switch ($brandId) {
            case 1:
                $hsMenuId = 22;
                break;
            case 2:
            case 3:
                $hsMenuId = 7;
                break;
            case 1004:
                $hsMenuId = 6;
                break;
            default:
                $hsMenuId = null; // Default case if no brandId matches
                break;
        }
        return "https://afco.althawaqh.com:97/api/services/app/hungerstation/GetRestaurantMenuByHsMenuId?HsMenuId={$hsMenuId}&brandId={$brandId}";
    }

    private function getUrlForBranch($aggregator_menu_id, $brandId)
    {
        $url = "https://afco.althawaqh.com:97/api/services/app/hungerstation/GetRestaurantMenuByHsMenuId?HsMenuId={$aggregator_menu_id}&brandId={$brandId}";
        Log::info('Generated Sara URL: ' . $url);
        return $url;
    }

    public function fetchMenu($brandId)
    {
        $url = $this->getUrlForBrand($brandId);

        try {
            $response = Http::timeout(60)->post($url);

            if ($response->successful()) {
                return $response->json(); // Returns the JSON response as an array
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

        return null; // Handle error as needed
    }

    public function fetchMenuBranch($aggregator_menu_id, $brandId)
    {
        $url = $this->getUrlForBranch($aggregator_menu_id, $brandId);

        try {
            $response = Http::timeout(600)->retry(3, 1000)->post($url);

            if ($response->successful()) {
                return $response->json(); // Returns the JSON response as an array
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

        return null; // Handle error as needed
    }

    public function transformMenu(array $menuData, $brandId): array
    {
        $branchIds = NinjaBranch::where('brand_id', $brandId)->pluck('pos_key')->toArray();
        $brandName = $this->brands[$brandId] ?? ['name_en' => 'Default Name', 'name_ar' => 'Default Name'];

        // Initialize the transformed menu structure
        $transformedMenu = [
            'menu' => [
                'id' => '64235126c921b7323fa2fdb5', // Hardcoded ID or dynamically set if needed
                'name_en' => $brandName['name_en'],
                'name_ar' => $brandName['name_ar'],
                'branch_working_times' => [
                    ['weekday' => 1, 'start_minutes' => 810, 'end_minutes' => 1439],
                    ['weekday' => 1, 'start_minutes' => 0, 'end_minutes' => 180],
                    ['weekday' => 2, 'start_minutes' => 810, 'end_minutes' => 1439],
                    ['weekday' => 2, 'start_minutes' => 0, 'end_minutes' => 180],
                    ['weekday' => 3, 'start_minutes' => 810, 'end_minutes' => 1439],
                    ['weekday' => 3, 'start_minutes' => 0, 'end_minutes' => 180],
                    ['weekday' => 4, 'start_minutes' => 810, 'end_minutes' => 1439],
                    ['weekday' => 4, 'start_minutes' => 0, 'end_minutes' => 180],
                    ['weekday' => 5, 'start_minutes' => 810, 'end_minutes' => 1439],
                    ['weekday' => 5, 'start_minutes' => 0, 'end_minutes' => 180],
                    ['weekday' => 6, 'start_minutes' => 810, 'end_minutes' => 1439],
                    ['weekday' => 6, 'start_minutes' => 0, 'end_minutes' => 180],
                    ['weekday' => 0, 'start_minutes' => 810, 'end_minutes' => 1439],
                    ['weekday' => 0, 'start_minutes' => 0, 'end_minutes' => 180]
                ],
                'categories' => [],
            ],
            'branch_ids' => $branchIds,
        ];

        // Process each menu category
        foreach ($menuData['result'] as $category) {
            $categoryId = "{$category['menuCategoryId']}";
            $transformedMenu['menu']['categories'][] = [
                'id' => $categoryId,
                'name_ar' => $category['arName'],
                'name_en' => $category['enName'],
                'products' => array_map(function ($product) use ($categoryId) {
                // Create product variants and toppings
                $productVariants = [
                        [
                            'id' => "{$product['sdmItemId']}",
                            'name_ar' => $product['arName'],
                            'name_en' => $product['enName'],
                            'price_cents' => $product['price'] * 100, // Adjust as needed
                            'weight' => 0,
                            'in_stock' => true,
                            'toppings' => array_map(function ($modifierGroup) use ($product) {
                    return [
                    'id' => "{$modifierGroup['sdmModGroupId']}",
                    'name_ar' => $modifierGroup['arName'],
                    'name_en' => $modifierGroup['enName'],
                    'minimum_quantity' => $modifierGroup['minimum'],
                    'maximum_quantity' => $modifierGroup['maximum'],
                    'description_ar' => $modifierGroup['arName'] ?? '-',
                    'description_en' => $modifierGroup['enName'] ?? '-',
                    'weight' => 2,
                    'in_stock' => true,
                    'topping_options' => array_map(function ($topping) {
                                return [
                                'id' => "{$topping['sdmItemId']}",
                                'product_variant_id' => "{$topping['sdmItemId']}",
                                'name_ar' => $topping['arName'],
                                'name_en' => $topping['enName'],
                                'price_cents' => $topping['price'] * 100, // Convert to cents
                                'weight' => 0,
                                'in_stock' => true
                                ];
                            }
                            , $modifierGroup['itemsList']),
                            ];
                        }
                                    , $product['modifierGroupsList'])
                                ]
                            ];

                        return [
                        'id' => "{$product['sdmItemId']}",
                        'name_ar' => $product['arName'],
                        'name_en' => $product['enName'],
                        'description_ar' => $product['arDescription'] ?? '-',
                        'description_en' => $product['enDescription'] ?? '-',
                        'weight' => 0,
                        'in_stock' => true,
                        'product_variants' => $productVariants,
                        'product_images' => [
                        [
                        'id' => $product['uuid'],
                        'url' => "https://afco.althawaqh.com:89/FileStore/{$product['imageURL']}",
                        'product_id' => "{$product['sdmItemId']}"
                        ]
                        ]
                        ];
                    }, $category['menuItemList']),
                'weight' => 2,
                'description_ar' => '',
                'description_en' => ''
            ];
        }

        return $transformedMenu;
    }

    public function transformMenuBranch(array $menuData, $brandId, $pos_key): array
    {
        $brandName = $this->brands[$brandId] ?? ['name_en' => 'Default Name', 'name_ar' => 'Default Name'];

        // Initialize the transformed menu structure
        $transformedMenu = [
            'menu' => [
                'id' => "64235126c921b7323fa2fdb5_{$pos_key}", // Hardcoded ID or dynamically set if needed
                'name_en' => $brandName['name_en'],
                'name_ar' => $brandName['name_ar'],
                'branch_working_times' => [
                    ['weekday' => 1, 'start_minutes' => 810, 'end_minutes' => 1439],
                    ['weekday' => 1, 'start_minutes' => 0, 'end_minutes' => 180],
                    ['weekday' => 2, 'start_minutes' => 810, 'end_minutes' => 1439],
                    ['weekday' => 2, 'start_minutes' => 0, 'end_minutes' => 180],
                    ['weekday' => 3, 'start_minutes' => 810, 'end_minutes' => 1439],
                    ['weekday' => 3, 'start_minutes' => 0, 'end_minutes' => 180],
                    ['weekday' => 4, 'start_minutes' => 810, 'end_minutes' => 1439],
                    ['weekday' => 4, 'start_minutes' => 0, 'end_minutes' => 180],
                    ['weekday' => 5, 'start_minutes' => 810, 'end_minutes' => 1439],
                    ['weekday' => 5, 'start_minutes' => 0, 'end_minutes' => 180],
                    ['weekday' => 6, 'start_minutes' => 810, 'end_minutes' => 1439],
                    ['weekday' => 6, 'start_minutes' => 0, 'end_minutes' => 180],
                    ['weekday' => 0, 'start_minutes' => 810, 'end_minutes' => 1439],
                    ['weekday' => 0, 'start_minutes' => 0, 'end_minutes' => 180]
                ],
                'categories' => [],
            ],
            'branch_ids' => [$pos_key],
        ];

        // Process each menu category
        foreach ($menuData['result'] as $category) {
            $categoryId = "{$category['menuCategoryId']}";
            $transformedMenu['menu']['categories'][] = [
                'id' => $categoryId,
                'name_ar' => $category['arName'],
                'name_en' => $category['enName'],
                'products' => array_map(function ($product) use ($categoryId) {
                // Create product variants and toppings
                $productVariants = [
                        [
                            'id' => "{$product['sdmItemId']}",
                            'name_ar' => $product['arName'],
                            'name_en' => $product['enName'],
                            'price_cents' => $product['price'] * 100, // Adjust as needed
                            'weight' => 0,
                            'in_stock' => true,
                            'toppings' => array_map(function ($modifierGroup) use ($product) {
                    return [
                    'id' => "{$modifierGroup['sdmModGroupId']}",
                    'name_ar' => $modifierGroup['arName'],
                    'name_en' => $modifierGroup['enName'],
                    'minimum_quantity' => $modifierGroup['minimum'],
                    'maximum_quantity' => $modifierGroup['maximum'],
                    'description_ar' => $modifierGroup['arName'] ?? '-',
                    'description_en' => $modifierGroup['enName'] ?? '-',
                    'weight' => 2,
                    'in_stock' => true,
                    'topping_options' => array_map(function ($topping) {
                                return [
                                'id' => "{$topping['sdmItemId']}",
                                'product_variant_id' => "{$topping['sdmItemId']}",
                                'name_ar' => $topping['arName'],
                                'name_en' => $topping['enName'],
                                'price_cents' => $topping['price'] * 100, // Convert to cents
                                'weight' => 0,
                                'in_stock' => true
                                ];
                            }
                            , $modifierGroup['itemsList']),
                            ];
                        }
                                    , $product['modifierGroupsList'])
                                ]
                            ];

                        return [
                        'id' => "{$product['sdmItemId']}",
                        'name_ar' => $product['arName'],
                        'name_en' => $product['enName'],
                        'description_ar' => $product['arDescription'] ?? '-',
                        'description_en' => $product['enDescription'] ?? '-',
                        'weight' => 0,
                        'in_stock' => true,
                        'product_variants' => $productVariants,
                        'product_images' => [
                        [
                        'id' => $product['uuid'],
                        'url' => "{$product['imageURL']}",
                        'product_id' => "{$product['sdmItemId']}"
                        ]
                        ]
                        ];
                    }, $category['menuItemList']),
                'weight' => 2,
                'description_ar' => '',
                'description_en' => ''
            ];
        }

        return $transformedMenu;
    }

    public function pushMenu(array $transformedMenu)
    {
        //prod
        $url = 'https://public.ananinja.com/restaurants/integrations/afco/webhooks/menu/sync';
        $token = '6b699278-57a2-4f4c-bb05-42a92b220225-0e874b8b-113b-456c-a737-ea3fb1eef601-366a0455-d4c4-4555-8034-40621061321e';

        //staging
        //$url = 'https://public.ananinja.dev/restaurants/integrations/afco/webhooks/menu/sync';
        //$token = '19ac8f61-c954-43f6-a39a-f4bd1517da2e-16145b56-d1cb-4570-a7b4-15aef2f849ce-7ffe3ac5-e8ff-4e15-ae46-721988c1093c';

        try {
            $response = Http::withToken($token)->post($url, $transformedMenu);

            if ($response->successful()) {
                return $response->json(); // Handle successful response
            }
            else {
                Log::error('Failed to push menu. Status Code: ' . $response->status(), [
                    'url' => $url,
                    'payload' => $transformedMenu,
                    'response_body' => $response->body(),
                ]);
            }
        }
        catch (\Exception $e) {
            Log::error('Error pushing menu: ' . $e->getMessage(), [
                'url' => $url,
                'payload' => $transformedMenu,
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return null; // Handle error as needed
    }

    public function syncMenu(Request $request)
    {
        $brandId = $request->input('brand_id');

        if (!array_key_exists($brandId, $this->brands)) {
            return response()->json(['message' => 'Invalid brand ID.'], 400);
        }

        try {
            $fetchedMenu = $this->fetchMenu($brandId);

            if ($fetchedMenu) {
                $transformedMenu = $this->transformMenu($fetchedMenu, $brandId);
                //return  $transformedMenu;
                if ($transformedMenu) {
                    $response = $this->pushMenu($transformedMenu);

                    if ($response) {
                        return $response; // Return the actual response from pushMenu
                    }
                    else {
                        Log::error('Failed to push the transformed menu.', [
                            'transformedMenu' => $transformedMenu,
                            'fetchedMenu' => $fetchedMenu,
                        ]);
                    }
                }
                else {
                    Log::error('Failed to transform the fetched menu.', [
                        'fetchedMenu' => $fetchedMenu,
                    ]);
                }
            }
            else {
                Log::error('Failed to fetch the menu.');
            }
        }
        catch (\Exception $e) {
            Log::error('Error in syncMenu method: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return response()->json(['message' => 'Failed to sync menu.']); // Return an error message or an empty response
    }

    public function syncMenuBranch(Request $request)
    {
        $brandId = $request->input('brand_id');
        $pos_key = $request->input('pos_key');

        Log::info("Value of Brand ID: " . $brandId);
        Log::info("Value of POS Key: " . $pos_key);

        if (!$brandId) {
            Log::info('Brand ID is missing in the request');
            return response()->json(['error' => 'Failed to get brand ID'], 500);
        }

        if (!$pos_key) {
            return response()->json(['error' => "Pos key not found"], 500);
        }

        if (!array_key_exists($brandId, $this->brands)) {
            return response()->json(['message' => 'Invalid brand ID.'], 400);
        }

        $branch = NinjaBranch::where("pos_key", $pos_key)->first();
        if (!$branch) {
            Log::info('Branch not found for POS Key: ' . $pos_key);
            return response()->json(['error' => 'Failed to fetch branch details'], 500);
        }

        $aggregator_menu_id = $branch->aggregator_menu_id;
        if (!$aggregator_menu_id) {
            return response()->json(['error' => 'Failed to get Aggregator Menu ID'], 500);
        }

        try {
            $fetchedMenu = $this->fetchMenuBranch($aggregator_menu_id, $brandId);

            if ($fetchedMenu) {
                $transformedMenu = $this->transformMenuBranch($fetchedMenu, $brandId, $pos_key);
                Log::info("Value of Transformed Menu: " . json_encode($transformedMenu));

                if ($transformedMenu) {
                    // Update branch_ids to push only to the specified restaurant level
                    // $transformedMenu['branch_ids'] = [$pos_key];

                    $response = $this->pushMenu($transformedMenu);
                    if ($response) {
                        return $response; // Return the actual response from pushMenu
                    }
                    else {
                        Log::error('Failed to push the transformed menu.', [
                            'transformedMenu' => $transformedMenu,
                            'fetchedMenu' => $fetchedMenu,
                        ]);
                    }
                }
                else {
                    Log::error('Failed to transform the fetched menu.', [
                        'fetchedMenu' => $fetchedMenu,
                    ]);
                }
            }
            else {
                Log::error('Failed to fetch the menu.');
            }
        }
        catch (\Exception $e) {
            Log::error('Error in syncMenuBranch method: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return response()->json(['message' => 'Failed to sync menu branch.'], 500);
    }

    public function syncMenuSara(Request $request)
    {
        $brandId = $request->input('brand_id');

        Log::info("Starting Ninja SARA batch sync for Brand ID: " . $brandId);

        if (!$brandId) {
            Log::info('Brand ID is missing in the request');
            return response()->json(['error' => 'Failed to get brand ID'], 500);
        }

        if (!array_key_exists($brandId, $this->brands)) {
            return response()->json(['message' => 'Invalid brand ID.'], 400);
        }

        $saraBranches = NinjaBranch::where('brand_id', $brandId)
            ->where('pos_system', 'sara')
            ->get();

        Log::info('Fetched Ninja SARA branches: ' . $saraBranches->count());

        if ($saraBranches->isEmpty()) {
            Log::info('No SARA branches found for Brand ID: ' . $brandId);
            return response()->json(['message' => 'No SARA branches found for this brand'], 404);
        }

        // Fetch menu once using the first branch's aggregator_menu_id
        $firstSaraBranch = $saraBranches->first();
        $aggregator_menu_id = $firstSaraBranch->aggregator_menu_id;

        if (!$aggregator_menu_id) {
            Log::warning("No aggregator_menu_id found for the first SARA branch under Brand {$brandId}.");
            return response()->json(['error' => 'No aggregator_menu_id found for SARA branches'], 404);
        }

        Log::info("Fetching SARA menu using aggregator ID: {$aggregator_menu_id}");
        $fetchedMenu = $this->fetchMenuBranch($aggregator_menu_id, $brandId);

        if (!$fetchedMenu) {
            Log::error("Failed to fetch SARA menu data for Brand {$brandId}");
            return response()->json(['error' => 'Failed to fetch SARA menu data'], 500);
        }

        $responses = [];

        foreach ($saraBranches as $branch) {
            try {
                // Transform menu per branch to apply _$pos_key to menu id and branch_ids array
                $transformedMenu = $this->transformMenuBranch($fetchedMenu, $brandId, $branch->pos_key);
                Log::info("Value of Pos Key: " . json_encode($branch->pos_key));
                if ($transformedMenu) {
                    $response = $this->pushMenu($transformedMenu);

                    if ($response) {
                        $responses[$branch->pos_key] = ['status' => 'success', 'response' => $response];
                    }
                    else {
                        Log::error('Failed to push the transformed SARA menu for branch.', [
                            'pos_key' => $branch->pos_key,
                        ]);
                        $responses[$branch->pos_key] = ['error' => 'Failed to push the transformed menu.'];
                    }
                }
                else {
                    Log::error('Failed to transform the fetched SARA menu for branch.', [
                        'pos_key' => $branch->pos_key,
                    ]);
                    $responses[$branch->pos_key] = ['error' => 'Failed to transform the menu.'];
                }
            }
            catch (\Exception $e) {
                Log::error('Error pushing SARA menu for branch: ' . $branch->pos_key . ' - ' . $e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                ]);
                $responses[$branch->pos_key] = ['error' => 'Exception occurred during sync.'];
            }
        }

        Log::info('Finished Ninja SARA batch sync loop for ' . $saraBranches->count() . ' branches');

        return response()->json([
            'message' => 'SARA Menu sync process completed',
            'branches_count' => $saraBranches->count(),
            'responses' => $responses
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'pos_key' => 'required|integer|max:100000|unique:ninja_branches,pos_key',
            'branch_name' => 'required|string|max:800',
            'brand_id' => 'required|integer',
            'pos_system' => 'required|in:sara,sdm',
        ], [
            'pos_key.unique' => 'The POS ID has already been taken. Please use a different one.',

        ]);

        NinjaBranch::create([
            'pos_key' => $request->pos_key,
            'branch_name' => $request->branch_name,
            'brand_id' => $request->brand_id, // Use the brand_id from the request
            'pos_system' => $request->pos_system,
        ]);

        return response()->json(['message' => 'Branch added successfully!']);
    }



}
