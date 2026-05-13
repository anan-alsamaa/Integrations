<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\AggregatorsConfiguration;
use App\Models\KeetaBranch;
use App\Models\KetaaOrder;
use App\Models\OrderLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use OpenApi\Annotations as OA;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use App\Jobs\KeetaProcessSDMOrders;
use App\Models\KeetaToken;

/**
 * @OA\Info(
 *     title="Order Webhook API",
 *     version="1.0.0",
 *     description="API for receiving order webhooks and storing order data"
 * )
 */
class Ketawebhook extends Controller
{
    /**
     * @OA\Post(
     *     path="/Integrations/public/api/order",
     *
     *     summary="Receive order webhook",
     *     description="Stores order data and forwards it to an external API",
     *     tags={"Order"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="additionalDeliverys",
     *                 type="object",
     *                 nullable=true,
     *                 description="Additional delivery information (optional)"
     *             ),
     *             @OA\Property(
     *                 property="bigOrderTag",
     *                 type="boolean",
     *                 description="Whether the order is tagged as a big order"
     *             ),
     *             @OA\Property(
     *                 property="feeDtl",
     *                 type="object",
     *                 @OA\Property(
     *                     property="customerFee",
     *                     type="object",
     *                     @OA\Property(property="actTotal", type="integer", example=0, description="The actual total amount for the customer"),
     *                     @OA\Property(property="actTotalStr", type="string", example="ر.س.‏ 0.00", description="The actual total amount in string format"),
     *                     @OA\Property(property="diffPrice", type="integer", example=0, description="The difference in price compared to the original"),
     *                     @OA\Property(property="diffPriceStr", type="string", example="ر.س.‏ 0.00", description="The difference price in string format"),
     *                     @OA\Property(property="discounts", type="integer", example=0, description="Discount applied to the order"),
     *                     @OA\Property(property="discountsStr", type="string", example="-ر.س.‏ 0.00", description="Discount amount in string format"),
     *                     @OA\Property(property="i18n", type="object",
     *                         @OA\Property(property="country", type="string", nullable=true),
     *                         @OA\Property(property="currency", type="string", example="SAR"),
     *                         @OA\Property(property="locale", type="string", nullable=true),
     *                         @OA\Property(property="region", type="string", nullable=true)
     *                     ),
     *                     @OA\Property(property="originPrice", type="integer", example=0, description="The original price of the order"),
     *                     @OA\Property(property="originPriceStr", type="string", example="ر.س.‏ 0.00", description="The original price in string format"),
     *                     @OA\Property(property="payTotal", type="integer", example=0, description="The total price the customer will pay"),
     *                     @OA\Property(property="payTotalStr", type="string", example="ر.س.‏ 0.00", description="The total price the customer will pay in string format"),
     *                     @OA\Property(property="platformFee", type="integer", example=0, description="The platform fee for the order"),
     *                     @OA\Property(property="platformFeeStr", type="string", example="ر.س.‏ 0.00", description="The platform fee in string format"),
     *                     @OA\Property(property="productPrice", type="integer", example=0, description="The price of the products in the order"),
     *                     @OA\Property(property="productPriceStr", type="string", example="ر.س.‏ 0.00", description="The product price in string format"),
     *                     @OA\Property(property="productPriceSubTotal", type="integer", example=0, description="The subtotal of product prices"),
     *                     @OA\Property(property="productPriceSubTotalStr", type="string", example="ر.س.‏ 0.00", description="The subtotal of product prices in string format"),
     *                     @OA\Property(property="shippingFee", type="integer", example=0, description="The shipping fee for the order"),
     *                     @OA\Property(property="shippingFeeStr", type="string", example="ر.س.‏ 0.00", description="The shipping fee in string format"),
     *                     @OA\Property(property="showDiscounts", type="integer", example=0, description="The amount of discount applied for display purposes"),
     *                     @OA\Property(property="showDiscountsStr", type="string", example="-ر.س.‏ 0.00", description="The display discount amount in string format"),
     *                     @OA\Property(property="tip", type="integer", example=0, description="The tip for the order"),
     *                     @OA\Property(property="tipStr", type="string", example="ر.س.‏ 0.00", description="The tip in string format")
     *                 ),
     *                 @OA\Property(property="merchantFee", type="object",
     *                     @OA\Property(property="activityFee", type="integer", example=0, description="The activity fee for the merchant"),
     *                     @OA\Property(property="activityFeeStr", type="string", example="-ر.س.‏ 0.00", description="The activity fee in string format"),
     *                     @OA\Property(property="bankTransactionFee", type="integer", example=0, description="The bank transaction fee for the merchant"),
     *                     @OA\Property(property="bankTransactionFeeStr", type="string", example="-ر.س.‏ 0.00", description="The bank transaction fee in string format"),
     *                     @OA\Property(property="brokerage", type="integer", example=0, description="The brokerage fee for the merchant"),
     *                     @OA\Property(property="brokerageStr", type="string", example="-ر.س.‏ 0.00", description="The brokerage fee in string format"),
     *                     @OA\Property(property="merchantActivityFeeDtls", type="array",
     *                         @OA\Items(type="object",
     *                             @OA\Property(property="activityId", type="string", example="0", description="The activity ID for the merchant fee"),
     *                             @OA\Property(property="merchantActivityFee", type="integer", example=0, description="The merchant activity fee"),
     *                             @OA\Property(property="merchantActivityFeeStr", type="string", example="0", description="The merchant activity fee in string format"),
     *                             @OA\Property(property="platformActivityFee", type="integer", example=0, description="The platform activity fee"),
     *                             @OA\Property(property="platformActivityFeeStr", type="string", example="ر.س.‏ 0.00", description="The platform activity fee in string format"),
     *                             @OA\Property(property="reduceFee", type="integer", example=0, description="The fee reduction"),
     *                             @OA\Property(property="sillAmount", type="integer", example=0, description="The sill amount (default 0)"),
     *                             @OA\Property(property="type", type="integer", example=0, description="The fee type"),
     *                             @OA\Property(property="typeName", type="string", example="0", description="The name of the fee type")
     *                         )
     *                     ),
     *                     @OA\Property(property="platformServiceFee", type="integer", example=0, description="The platform service fee for the merchant"),
     *                     @OA\Property(property="platformServiceFeeStr", type="string", example="ر.س.‏ 0.00", description="The platform service fee in string format"),
     *                     @OA\Property(property="total", type="integer", example=0, description="The total merchant fee"),
     *                     @OA\Property(property="totalStr", type="string", example="ر.س.‏ 0.00", description="The total merchant fee in string format")
     *                 )
     *             ),
     *             @OA\Property(property="foodPrepareInfo", type="object", nullable=true, description="Additional food preparation details (optional)"),
     *             @OA\Property(property="groupChoiceCompensationTag", type="boolean", description="Whether there is compensation for group choices"),
     *             @OA\Property(property="merchantOrder", type="object",
     *                 @OA\Property(property="accId", type="string", nullable=true),
     *                 @OA\Property(property="confirmStatus", type="string", nullable=true),
     *                 @OA\Property(property="createdDate", type="string", example="0", description="Creation date of the merchant order"),
     *                 @OA\Property(property="createdDateStr", type="string", example="2024-11-13", description="Creation date string of the merchant order")
     *             ),
     *             @OA\Property(property="merchantOrderlines", type="array", @OA\Items(type="object",
     *                 @OA\Property(property="articleId", type="string", example="0", description="The article ID"),
     *                 @OA\Property(property="orderLineId", type="string", example="0", description="The order line ID"),
     *                 @OA\Property(property="quantity", type="integer", example=0, description="Quantity of the product"),
     *                 @OA\Property(property="sku", type="string", example="0", description="SKU of the product")
     *             )),
     *             @OA\Property(property="orderId", type="string", example="0", description="The unique ID of the order"),
     *             @OA\Property(property="orderSource", type="string", example="0", description="The source of the order")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully received the webhook",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="success", description="Response message")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid request",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid data", description="Error message")
     *         )
     *     )
     * )
     */

    public function postOrder(Request $ketaarequest)
    {
        try {
            // Call the second method to process the order
            $response = $this->storeOrderData($ketaarequest);

            //KeetaProcessSDMOrders::dispatch();

            // Return the response in the required structure
            return response()->json([
                'code' => 0,  // success
                'message' => 'Success',
                'data' => $response,  // Return the response data from the second method
            ]);
        } catch (\Exception $e) {
            // In case of failure, return the error details
            Log::info('Error from sdm:', ['data' => $e->getMessage()]);
            return response()->json([
                'code' => 1,  // failure
                'message' => 'Error: ' . $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    private function storeOrderData(Request $ketaarequest)
    {
        $message = json_decode($ketaarequest['message'], true);
        $timestamp = Carbon::now()->timestamp;


        // Get data from request
        $data = [
            'sig' => $ketaarequest->input('sig'),
            'eventId' => $ketaarequest->input('eventId'),
            'appId' => $ketaarequest->input('appId'),
            'messageId' => $ketaarequest->input('messageId'),
            'shopId' => $ketaarequest->input('shopId'),
            'message' => $ketaarequest->input('message'),
            'timestamp' => $ketaarequest->input('timestamp'),
        ];

        // Log the data
        Log::info('Received data from Keta:', $data);


        // Store in the database

        $keetaBranch = KeetaBranch::where('keeta_id', $ketaarequest->input('shopId'))->first();
        if ($keetaBranch->pos_system == 'sara') {
            $latestToken = KeetaToken::where('brandId', $keetaBranch->brand_reference_id)->latest()->first();
        } else {
            $latestToken = KeetaToken::where('brandId', $keetaBranch->brand_id)->latest()->first();
        }

        $sigstring = 'https://open.mykeeta.com/api/open/order/confirm?accessToken=' . $latestToken->accessToken . '&appId=' . $data['appId'] . '&orderViewId=' . $message['baseOrder']['orderViewId'] . '&shopId=' . $data['shopId'] . '&timestamp=' . $timestamp . '1a840cb335ba4a30a7d8611a4e5041ce';

        $sig = hash('sha256', $sigstring);


        // Prepare the body data (the parameters you want to send in the request body)
        $body = [
            'appId' => $data['appId'],
            'timestamp' => Carbon::now()->timestamp,
            'sig' => $sig, // Add the generated sig
            'accessToken' => $latestToken->accessToken, // Replace with a valid token if dynamic
            'shopId' => $data['shopId'],
            'orderViewId' => $message['baseOrder']['orderViewId'],
        ];

        // Call the URL with POST data in the body (not in headers)
        $response = Http::post('https://open.mykeeta.com/api/open/order/confirm', $body);

        // Log the response
        Log::info('Keta API response for confirmation:', ['response' => $response->json()]);

        // Check if the API response indicates success
        if ($response->successful() && $response->json('success') === true) {
            // Proceed with database insertion
            DB::table('ketaa_orders')->insert([
                'keeta_order_id' => $message['baseOrder']['orderViewId'],
                'sig' => $data['sig'],
                'event_id' => $data['eventId'],
                'app_id' => $data['appId'],
                'message_id' => $data['messageId'],
                'shop_id' => $data['shopId'],
                'message' => $data['message'],
                'timestamp' => $data['timestamp'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            // Log the data to be inserted into the database
            Log::info('Inserting order data into ketaa_orders table:', [
                'keeta_order_id' => $message['baseOrder']['orderViewId'],
                'sig' => $data['sig'],
                'event_id' => $data['eventId'],
                'app_id' => $data['appId'],
                'message_id' => $data['messageId'],
                'shop_id' => $data['shopId'],
                'message' => $data['message'],
                'timestamp' => $data['timestamp'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            KeetaProcessSDMOrders::dispatch();
        } else {
            // Log an error or handle the failure case
            $apiResponse = $response->json();
            Log::error('Keta API confirmation failed:', ['response' => $apiResponse]);

            // Safely extract the code and message from the response
            $errorCode = $apiResponse['response']['code'] ?? $apiResponse['code'] ?? null;
            $errorMsg = $apiResponse['response']['message'] ?? $apiResponse['message'] ?? '';

            $msgLower = strtolower((string) $errorMsg);

            // Check known token error codes or keywords in the actual message
            $isTokenError = ($errorCode == 115000203) ||
                str_contains($msgLower, 'token') ||
                str_contains($msgLower, 'expire') ||
                str_contains($msgLower, 'unauthorized') ||
                str_contains($msgLower, 'invalid');

            if ($isTokenError) {
                // Ensure variables are captured
                $brandId = $keetaBranch->brand_id ?? 'Unknown';
                $posSystem = $keetaBranch->pos_system ?? 'Unknown';

                $orderDetails = [
                    'keeta_order_id' => $message['baseOrder']['orderViewId'] ?? 'Unknown',
                    'pos_system' => $posSystem,
                    'brand_id' => $brandId,
                ];

                \Illuminate\Support\Facades\Mail::to("e.habibi@anan.sa")->send(
                    new \App\Mail\OrderProcessingError(
                        $orderDetails,
                        'Keeta API Access Token Expired or Invalid: ' . $errorMsg
                    )
                );

                \Illuminate\Support\Facades\Mail::to("m.tamam@anan.sa")->send(
                    new \App\Mail\OrderProcessingError(
                        $orderDetails,
                        'Keeta API Access Token Expired or Invalid: ' . $errorMsg
                    )
                );
            }
        }


    }





}
