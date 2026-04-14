<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class ToYouLCPMenuController extends Controller
{

    public function importMenu()
    {
        $email = 'integration+casapasta@toyou.io';
        $password = 'PNnjDoFnHPrK2NEBuKks';
        // Generate the token
        $tokenResponse = $this->generateToken($email, $password);

        if (!$tokenResponse->successful()) {
            return $tokenResponse->json(); // Return the raw response from token endpoint
        }

        $tokenData = $tokenResponse->json();
        $token = $tokenData['authToken'] ?? null;

        if ($token) {
            // Remove 'Bearer ' prefix if present
            $token = str_replace('Bearer ', '', $token);
        }

        if (!$token) {
            return [
                'message' => 'Failed to retrieve token',
                'details' => $tokenData // Include details from the token response
            ];
        }

        // Fetch menu data
        $menuResponse = $this->getMenuByHsMenuId(8, 1);


        if (!$menuResponse->successful()) {
            return $menuResponse->json(); // Return the raw response from menu endpoint
        }

        $responseData = $menuResponse->json();
        //return $responseData;
        Log::debug('Menu Response', ['response' => $responseData]);

        if (isset($responseData['result']) && is_array($responseData['result'])) {
            $allMenuItems = [];
            $menuCategoryNames = [];

            foreach ($responseData['result'] as $resultItem) {
                if (isset($resultItem['menuItemList']) && is_array($resultItem['menuItemList'])) {
                    foreach ($resultItem['menuItemList'] as $menuItem) {
                        // Initialize array if not already set for this item ID
                        if (!isset($menuCategoryNames[$menuItem['sdmItemId']])) {
                            $menuCategoryNames[$menuItem['sdmItemId']] = [];
                        }

                        // Add the current category name to the list of categories for this item
                        $menuCategoryNames[$menuItem['sdmItemId']][] = $resultItem['menuCategoryName'] ?? '';

                        $allMenuItems[] = $menuItem;
                    }
                }
            }

            $mappedData = $this->mapResponseData($allMenuItems, $menuCategoryNames);
            //return $mappedData;

            // Map and check for duplicate product keys
            $importResponse = $this->startMenuImport($mappedData, $token);

            if (!$importResponse->successful()) {
                return $importResponse->json(); // Return the raw response from menu import endpoint
            }

            return $importResponse->json(); // Return success response from menu import endpoint
        }

        return [
            'message' => 'Failed to fetch menu',
            'details' => $menuResponse->json()
        ];
    }
    public function importMenuPoshak()
    {
        $email = 'integration+poshak@toyou.io'; // Adjust with actual email
        $password = 'jRPQTdMHzGYMWxKQ7xjU'; // Adjust with actual password

        // Generate the token
        $tokenResponse = $this->generateToken($email, $password);

        if (!$tokenResponse->successful()) {
            return $tokenResponse->json(); // Return the raw response from token endpoint
        }

        $tokenData = $tokenResponse->json();
        $token = $tokenData['authToken'] ?? null;

        if ($token) {
            $token = str_replace('Bearer ', '', $token);
        }

        if (!$token) {
            return [
                'message' => 'Failed to retrieve token',
                'details' => $tokenData
            ];
        }

        // Fetch menu data specific for Okashi
        $menuResponse = $this->getMenuByHsMenuId(1, 3); // Adjust HsMenuId and brandId as needed

        if (!$menuResponse->successful()) {
            return $menuResponse->json(); // Return the raw response from menu endpoint
        }

        $responseData = $menuResponse->json();
        Log::debug('Menu Response', ['response' => $responseData]);

        if (isset($responseData['result']) && is_array($responseData['result'])) {
            $allMenuItems = [];
            $menuCategoryNames = [];

            foreach ($responseData['result'] as $resultItem) {
                if (isset($resultItem['menuItemList']) && is_array($resultItem['menuItemList'])) {
                    foreach ($resultItem['menuItemList'] as $menuItem) {
                        // Initialize array if not already set for this item ID
                        if (!isset($menuCategoryNames[$menuItem['sdmItemId']])) {
                            $menuCategoryNames[$menuItem['sdmItemId']] = [];
                        }

                        // Add the current category name to the list of categories for this item
                        $menuCategoryNames[$menuItem['sdmItemId']][] = $resultItem['menuCategoryName'] ?? '';

                        $allMenuItems[] = $menuItem;
                    }
                }
            }

            $mappedData = $this->mapResponseData($allMenuItems, $menuCategoryNames);

            // Map and check for duplicate product keys
            $importResponse = $this->startMenuImport($mappedData, $token);

            if (!$importResponse->successful()) {
                return $importResponse->json(); // Return the raw response from menu import endpoint
            }

            return $importResponse->json(); // Return success response from menu import endpoint
        }

        return [
            'message' => 'Failed to fetch menu',
            'details' => $menuResponse->json()
        ];
    }

    public function importMenuOkashi()
    {
        $email = 'integration+okashi@toyou.io'; // Adjust with actual email
        $password = 'B9vqnxjmErB0LEBQK9hp'; // Adjust with actual password

        // Generate the token
        $tokenResponse = $this->generateToken($email, $password);

        if (!$tokenResponse->successful()) {
            return $tokenResponse->json(); // Return the raw response from token endpoint
        }

        $tokenData = $tokenResponse->json();
        $token = $tokenData['authToken'] ?? null;

        if ($token) {
            $token = str_replace('Bearer ', '', $token);
        }

        if (!$token) {
            return [
                'message' => 'Failed to retrieve token',
                'details' => $tokenData
            ];
        }

        // Fetch menu data specific for Okashi
        $menuResponse = $this->getMenuByHsMenuId(1, 3); // Adjust HsMenuId and brandId as needed

        if (!$menuResponse->successful()) {
            return $menuResponse->json(); // Return the raw response from menu endpoint
        }

        $responseData = $menuResponse->json();
        Log::debug('Menu Response', ['response' => $responseData]);

        if (isset($responseData['result']) && is_array($responseData['result'])) {
            $allMenuItems = [];
            $menuCategoryNames = [];

            foreach ($responseData['result'] as $resultItem) {
                if (isset($resultItem['menuItemList']) && is_array($resultItem['menuItemList'])) {
                    foreach ($resultItem['menuItemList'] as $menuItem) {
                        // Initialize array if not already set for this item ID
                        if (!isset($menuCategoryNames[$menuItem['sdmItemId']])) {
                            $menuCategoryNames[$menuItem['sdmItemId']] = [];
                        }

                        // Add the current category name to the list of categories for this item
                        $menuCategoryNames[$menuItem['sdmItemId']][] = $resultItem['menuCategoryName'] ?? '';

                        $allMenuItems[] = $menuItem;
                    }
                }
            }

            $mappedData = $this->mapResponseData($allMenuItems, $menuCategoryNames);
            // return $mappedData;
            // Map and check for duplicate product keys
            $importResponse = $this->startMenuImport($mappedData, $token);

            if (!$importResponse->successful()) {
                return $importResponse->json(); // Return the raw response from menu import endpoint
            }

            return $importResponse->json(); // Return success response from menu import endpoint
        }

        return [
            'message' => 'Failed to fetch menu',
            'details' => $menuResponse->json()
        ];
    }

    public function importMenuCND()
    {
        $email = 'integration+chickndip@toyou.io';
        $password = 'b6Ex6APnqRN0wqWgu1nK';

        // Generate the token
        $tokenResponse = $this->generateToken($email, $password);

        if (!$tokenResponse->successful()) {
            return $tokenResponse->json(); // Return the raw response from token endpoint
        }

        $tokenData = $tokenResponse->json();
        $token = $tokenData['authToken'] ?? null;

        if ($token) {
            $token = str_replace('Bearer ', '', $token); // Remove 'Bearer ' prefix if present
        }

        if (!$token) {
            return [
                'message' => 'Failed to retrieve token',
                'details' => $tokenData // Include details from the token response
            ];
        }

        // Fetch menu data specific for Chick 'n' Dip
        $menuResponse = $this->getMenuByHsMenuId(5, 2); // Changed HsMenuId and brandId

        if (!$menuResponse->successful()) {
            return $menuResponse->json(); // Return the raw response from menu endpoint
        }

        $responseData = $menuResponse->json();
        Log::debug('Menu Response', ['response' => $responseData]);

        if (isset($responseData['result']) && is_array($responseData['result'])) {
            $allMenuItems = [];
            $menuCategoryNames = [];

            foreach ($responseData['result'] as $resultItem) {
                if (isset($resultItem['menuItemList']) && is_array($resultItem['menuItemList'])) {
                    foreach ($resultItem['menuItemList'] as $menuItem) {
                        // Initialize array if not already set for this item ID
                        if (!isset($menuCategoryNames[$menuItem['sdmItemId']])) {
                            $menuCategoryNames[$menuItem['sdmItemId']] = [];
                        }

                        // Add the current category name to the list of categories for this item
                        $menuCategoryNames[$menuItem['sdmItemId']][] = $resultItem['menuCategoryName'] ?? '';

                        $allMenuItems[] = $menuItem;
                    }
                }
            }

            $mappedData = $this->mapResponseData($allMenuItems, $menuCategoryNames);
            //return $mappedData;

            // Map and check for duplicate product keys
            $importResponse = $this->startMenuImport($mappedData, $token);

            if (!$importResponse->successful()) {
                return $importResponse->json(); // Return the raw response from menu import endpoint
            }

            return $importResponse->json(); // Return success response from menu import endpoint
        }

        return [
            'message' => 'Failed to fetch menu',
            'details' => $menuResponse->json()
        ];
    }

    private function generateToken($email, $password)
    {
        $response = Http::post('https://app.toyou.delivery/catalog/v1/merchantuser/authtoken', [
            'email' => $email,
            'password' => $password,
        ]);

        return $response; // Return the entire response object
    }


    private function getMenuByHsMenuId($HsMenuId, $brandId)
    {
        $url = "https://afco.althawaqh.com:97/api/services/app/hungerstation/GetRestaurantMenuByHsMenuId?HsMenuId=$HsMenuId&brandId=$brandId";
        return Http::timeout(60)->post($url);
    }

    private function mapResponseData($menuItemList, $menuCategoryNames)
    {
        $products = [];
        $productOptions = [];
        $existingProductOptionKeys = []; // Array to store already added productOptionKeys
        $existingProductKeys = []; // Array to store already added productKeys

        // Define the productOptionKey to be removed
        $productOptionKeyToRemove = "10073";

        foreach ($menuItemList as $item) {
            // Check if the productKey is already added to avoid duplicates
            $productKey = (string) $item['sdmItemId'];
            if (in_array($productKey, $existingProductKeys)) {
                continue; // Skip this item if the productKey is already added
            }

            $itemProductOptions = []; // Array to store product option keys for this item

            foreach ($item['modifierGroupsList'] as $modifierGroup) {
                $productOptionKey = (string) $modifierGroup['sdmModGroupId'];

                // Skip if the productOptionKey matches the one to be removed
                if ($productOptionKey === $productOptionKeyToRemove) {
                    continue;
                }

                // Check if the productOptionKey is already added
                if (!in_array($productOptionKey, $existingProductOptionKeys)) {
                    $options = [];
                    foreach ($modifierGroup['itemsList'] as $option) {
                        $options[] = [
                            'nameAr' => $option['arName'],
                            'nameEn' => $option['enName'],
                            'optionValueKey' => (string) $option['sdmItemId'],
                            'price' => [
                                'price' => $option['price']
                            ]
                        ];
                    }

                    // Replace max 0 with 10
                    $maxValue = $modifierGroup['maximum'] === 0 ? 10 : $modifierGroup['maximum'];

                    $productOptions[] = [
                        'enableQuantity' => false,
                        'max' => $maxValue,
                        'min' => $modifierGroup['minimum'],
                        'nameAr' => $modifierGroup['arName'],
                        'nameEn' => $modifierGroup['enName'],
                        'optionValues' => $options,
                        'productOptionKey' => $productOptionKey,
                        'titleAr' => $modifierGroup['arName'],
                        'titleEn' => $modifierGroup['enName']
                    ];

                    // Mark this productOptionKey as added
                    $existingProductOptionKeys[] = $productOptionKey;
                }

                // Add the productOptionKey to the itemProductOptions array
                $itemProductOptions[] = $productOptionKey;
            }

            $products[] = [
                'descriptionAr' => $item['arDescription'],
                'descriptionEn' => $item['enDescription'],
                'groups' => [],
                'mediaFiles' => ["https://afco.althawaqh.com:89/FileStore/" . $item['imageURL']],
                'nameAr' => $item['arName'],
                'nameEn' => $item['enName'],
                'price' => [
                    'discountedPrice' => 0,
                    'price' => $item['price']
                ],
                'productKey' => $productKey,
                'productOptions' => $itemProductOptions,
                'schedule' => '',

                // Combine multiple category names into tags
                'tags' => array_unique($menuCategoryNames[$item['sdmItemId']] ?? []),
            ];

            // Mark this productKey as added to avoid duplicates
            $existingProductKeys[] = $productKey;
        }

        return [
            'productOptions' => $productOptions,
            'products' => $products,
            'schedules' => []
        ];
    }

    private function startMenuImport($data, $token)
    {

        $url = "https://app.toyou.delivery:443/publiccatalog/v2/actions/start-menu-import?skip-manual-verification=true";

        // Add the skip-manual-verification parameter to the request payload
        //$data['skip-manual-verification'] = true;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->post($url, $data);

        return $response; // Return the entire response object
    }

    public function mapPOSLCP(Request $request)
    {
        // Retrieve pos-id and pos-key from the request
        $posId = $request->input('pos-id');
        $posKey = $request->input('pos-key');

        // Email and password for generating the token
        $email = 'integration+casapasta@toyou.io';
        $password = 'PNnjDoFnHPrK2NEBuKks';

        // Generate the token
        $tokenResponse = $this->generateToken($email, $password);

        if (!$tokenResponse->successful()) {
            return response()->json($tokenResponse->json(), $tokenResponse->status());
        }

        $tokenData = $tokenResponse->json();
        $token = $tokenData['authToken'] ?? null;

        if ($token) {
            // Remove 'Bearer ' prefix if present
            $token = str_replace('Bearer ', '', $token);
        }

        if (!$token) {
            return response()->json([
                'message' => 'Failed to retrieve token',
                'details' => $tokenData
            ], 401);
        }

        // Define the endpoint for POS mapping with query parameters
        $url = "https://app.toyou.delivery:443/publiccatalog/v2/pos-key?pos-id=$posId&pos-key=$posKey";

        // Send the request with the token in the header
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->post($url);

        // Extract the status and body from the response
        $status = $response->status();
        $responseBody = $response->body();

        // Log the response with status and body
        \Log::info('POS Mapping Response', [
            'status' => $status,
            'body' => $responseBody,
        ]);

        // Return the response as JSON
        return response()->json([
            'status' => $status,
            'body' => $responseBody,
        ], $status);
    }

    public function mapPOSPSK(Request $request)
    {
        // Retrieve pos-id and pos-key from the request
        $posId = $request->input('pos-id');
        $posKey = $request->input('pos-key');

        // Email and password for generating the token
        $email = 'integration+poshak@toyou.io';
        $password = 'jRPQTdMHzGYMWxKQ7xjU';

        // Generate the token
        $tokenResponse = $this->generateToken($email, $password);

        if (!$tokenResponse->successful()) {
            return response()->json($tokenResponse->json(), $tokenResponse->status());
        }

        $tokenData = $tokenResponse->json();
        $token = $tokenData['authToken'] ?? null;

        if ($token) {
            // Remove 'Bearer ' prefix if present
            $token = str_replace('Bearer ', '', $token);
        }

        if (!$token) {
            return response()->json([
                'message' => 'Failed to retrieve token',
                'details' => $tokenData
            ], 401);
        }

        // Define the endpoint for POS mapping with query parameters
        $url = "https://app.toyou.delivery:443/publiccatalog/v2/pos-key?pos-id=$posId&pos-key=$posKey";

        // Send the request with the token in the header
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->post($url);

        // Extract the status and body from the response
        $status = $response->status();
        $responseBody = $response->body();

        // Log the response with status and body
        \Log::info('POS Mapping Response', [
            'status' => $status,
            'body' => $responseBody,
        ]);

        // Return the response as JSON
        return response()->json([
            'status' => $status,
            'body' => $responseBody,
        ], $status);
    }


    public function fetchPOSLocations(Request $request)
    {
        $posType = $request->query('posType');

        // Determine the email and password based on the posType parameter
        switch ($posType) {
            case 'LCP':
                $email = 'integration+casapasta@toyou.io';
                $password = 'PNnjDoFnHPrK2NEBuKks';
                break;
            case 'PSK':
                $email = 'integration+poshak@toyou.io';
                $password = 'jRPQTdMHzGYMWxKQ7xjU';
                break;
            case 'CND':
                $email = 'integration+chickndip@toyou.io';  // Replace with actual CND credentials
                $password = 'b6Ex6APnqRN0wqWgu1nK';           // Replace with actual CND password
                break;
            case 'OKS':
                $email = 'integration+okashi@toyou.io';  // Replace with actual OKS credentials
                $password = 'B9vqnxjmErB0LEBQK9hp';           // Replace with actual OKS password
                break;
            default:
                return response()->json(['message' => 'Invalid POS type'], 400);
        }

        // Generate the token
        $tokenResponse = $this->generateToken($email, $password);

        if (!$tokenResponse->successful()) {
            Log::error('Failed to generate token.', [
                'email' => $email,
                'response' => $tokenResponse->json(),
                'status' => $tokenResponse->status()
            ]);
            return response()->json($tokenResponse->json(), $tokenResponse->status());
        }

        $tokenData = $tokenResponse->json();
        $token = $tokenData['authToken'] ?? null;

        if ($token) {
            $token = str_replace('Bearer ', '', $token);
        }

        if (!$token) {
            Log::error('Token generation returned null.', [
                'email' => $email,
                'tokenData' => $tokenData
            ]);
            return response()->json([
                'message' => 'Failed to retrieve token',
                'details' => $tokenData
            ], 401);
        }

        // Define the endpoint to fetch POS locations
        $url = "https://app.toyou.delivery:443/publiccatalog/v2/poses";

        // Send the request with the token in the header
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->get($url);

        if ($response->successful()) {
            return response()->json($response->json(), 200);
        } else {
            // Log the error with details
            Log::error('Failed to fetch POS locations.', [
                'url' => $url,
                'response' => $response->json(),
                'status' => $response->status()
            ]);
            return response()->json([
                'message' => 'Failed to fetch POS locations',
                'details' => $response->json()
            ], $response->status());
        }
    }

    public function mapPOSCND(Request $request)
    {
        // Retrieve pos-id and pos-key from the request
        $posId = $request->input('pos-id');
        $posKey = $request->input('pos-key');

        // Email and password for generating the token
        $email = 'integration+chickndip@toyou.io';
        $password = 'b6Ex6APnqRN0wqWgu1nK';

        // Generate the token
        $tokenResponse = $this->generateToken($email, $password);

        if (!$tokenResponse->successful()) {
            return response()->json($tokenResponse->json(), $tokenResponse->status());
        }

        $tokenData = $tokenResponse->json();
        $token = $tokenData['authToken'] ?? null;

        if ($token) {
            // Remove 'Bearer ' prefix if present
            $token = str_replace('Bearer ', '', $token);
        }

        if (!$token) {
            return response()->json([
                'message' => 'Failed to retrieve token',
                'details' => $tokenData
            ], 401);
        }

        // Define the endpoint for POS mapping with query parameters
        $url = "https://app.toyou.delivery:443/publiccatalog/v2/pos-key?pos-id=$posId&pos-key=$posKey";

        // Send the request with the token in the header
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->post($url);

        $status = $response->status();
        $responseBody = $response->body();

        \Log::info('POS Mapping Response for CND', [
            'status' => $status,
            'body' => $responseBody,
        ]);

        return response()->json([
            'status' => $status,
            'body' => $responseBody,
        ], $status);
    }

    public function mapPOSOKS(Request $request)
    {
        // Retrieve pos-id and pos-key from the request
        $posId = $request->input('pos-id');
        $posKey = $request->input('pos-key');

        // Email and password for generating the token
        $email = 'integration+okashi@toyou.io';
        $password = 'B9vqnxjmErB0LEBQK9hp';  // Replace with actual password

        // Generate the token
        $tokenResponse = $this->generateToken($email, $password);

        if (!$tokenResponse->successful()) {
            return response()->json($tokenResponse->json(), $tokenResponse->status());
        }

        $tokenData = $tokenResponse->json();
        $token = $tokenData['authToken'] ?? null;

        if ($token) {
            $token = str_replace('Bearer ', '', $token);
        }

        if (!$token) {
            return response()->json([
                'message' => 'Failed to retrieve token',
                'details' => $tokenData
            ], 401);
        }

        $url = "https://app.toyou.delivery:443/publiccatalog/v2/pos-key?pos-id=$posId&pos-key=$posKey";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->post($url);

        $status = $response->status();
        $responseBody = $response->body();

        \Log::info('POS Mapping Response for OKS', [
            'status' => $status,
            'body' => $responseBody,
        ]);

        return response()->json([
            'status' => $status,
            'body' => $responseBody,
        ], $status);
    }

    private function mapPOS(Request $request, $email, $password)
    {
        $posId = $request->input('pos-id');
        $posKey = $request->input('pos-key');

        $tokenResponse = $this->generateToken($email, $password);

        if (!$tokenResponse->successful()) {
            return response()->json($tokenResponse->json(), $tokenResponse->status());
        }

        $tokenData = $tokenResponse->json();
        $token = $tokenData['authToken'] ?? null;

        if ($token) {
            $token = str_replace('Bearer ', '', $token);
        }

        if (!$token) {
            return response()->json([
                'message' => 'Failed to retrieve token',
                'details' => $tokenData
            ], 401);
        }

        $url = "https://app.toyou.delivery:443/publiccatalog/v2/pos-key?pos-id=$posId&pos-key=$posKey";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->get($url);

        return response()->json($response->json(), $response->status());
    }


}
