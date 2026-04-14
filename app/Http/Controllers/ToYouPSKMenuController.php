<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class ToYouPSKMenuController extends Controller
{

    public function importMenu()
    {
        $email = 'integration+poshak@toyou.io';
        $password = 'jRPQTdMHzGYMWxKQ7xjU';
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
        $menuResponse = $this->getMenuByHsMenuId(3, 1004);

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

        foreach ($menuItemList as $item) {
            // Check if the productKey is already added to avoid duplicates
            $productKey = (string) $item['sdmItemId'];
            if (in_array($productKey, $existingProductKeys)) {
                continue; // Skip this item if the productKey is already added
            }

            $itemProductOptions = []; // Array to store product option keys for this item

            foreach ($item['modifierGroupsList'] as $modifierGroup) {
                $productOptionKey = (string) $modifierGroup['sdmModGroupId'];

                // Skip productOptionKey "11121"
                if ($productOptionKey === "11121") {
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
            // Remove "11121" from productOptions list
            $itemProductOptions = array_filter($itemProductOptions, function ($option) {
                return $option !== "11121";
            });

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
}
