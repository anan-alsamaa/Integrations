<?php

namespace App\Http\Controllers\posKeeta;

use App\Http\Controllers\Controller;
use App\Models\KetaaOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use App\Mail\OrderProcessingError;
use Illuminate\Support\Facades\Mail;

class SaraCallbackController extends Controller
{
    /**
     * POST /api/Order/UpdateSaraOrder
     */
    public function updateSaraOrder(Request $request): JsonResponse
    {
        $data = $request->all();
        Log::info('Sara POS callback received', $data);

        $orderRequestedId = $data['order_requested_id'];
        if (empty($orderRequestedId)) {
            Log::warning('Sara callback missing Order_requested_id', $data);
            return response()->json(['error' => 'Order_requested_id missing'], 400);
        }

        $order = KetaaOrder::where('order_request_id', $orderRequestedId)->first();
        if (!$order) {
            Log::error('SARA_CALLBACK_UNMATCHED', [
            'reason' => 'order_request_id_not_found',
            'order_request_id' => $orderRequestedId,
            'payload' => $data,
        ]);
            return response()->json(['error' => 'order not found'], 404);// status to be changed from 200 as order_request_id is empty/null.
        }
        
        $order->callback_response = json_encode($data);

        $order->error_message = json_encode([
            'Message' => $data['message'] ?? null
        ]);

        // Sara's order id -> store in SDM_order_id 
        if (!empty($data['order_id'])) {
            $order->SDM_order_id = $data['order_id']; 
        }


        $statusCode  = $data['status_code'] ?? null;
        $orderStatus = $data['order_status'] ?? null;

        if (empty($data['order_id'])) {
            Log::warning('SARA_CALLBACK_MISSING_ORDER_ID', [
                'order_request_id' => $orderRequestedId,
                'status_code' => $statusCode,
                'order_status' => $orderStatus,
                'payload' => $data,
            ]);
        }

        if ($statusCode == 201 && $orderStatus === 'in_kitchen') {
            Log::info('Sara order accepted (in_kitchen)', [
                'order_request_id' => $order->order_request_id, 
            ]);
            $order->order_status = 'in_kitchen';
        }

        if ($statusCode == 400) {
            Log::error('Sara order rejected', [
                'order_request_id' => $order->order_request_id,
                'Keeta_Order_Id' => $order->keeta_order_id,
                'message' => $data['message'] ?? null,
            ]);
            $order->order_status = 'rejected';
            $order->SDM_order_id = '-1';
            }


        if ($statusCode == 200 && $orderStatus === 'closed') {
            Log::info('Sara order closed', [
                'order_request_id' => $order->order_request_id]);
            $order->order_status = 'closed'; // new field in ketaa_orders table
        }


        /**
         * FAILURE DETECTION + EMAIL
         * Send mail ONLY after callback and ONLY if failure detected
         */
        $hasFailure =
            ($statusCode == 400) ||
            ($orderStatus === 'rejected');

        if ($hasFailure) {
            Log::error('Sara order failure detected - sending email', [
                'keeta_order_id'   => $order->keeta_order_id,
                'order_request_id' => $order->order_request_id,
                'status_code'      => $statusCode,
                'order_status'     => $orderStatus,
                'message'          => $data['message'] ?? null,
            ]);

            $errorMessage = $data['message'];

            Mail::to("e.habibi@anan.sa")->send(
                new OrderProcessingError(
                    [
                        'keeta_order_id'   => $order->keeta_order_id,
                        'order_request_id' => $order->order_request_id,
                        'sara_status_code' => $statusCode,
                        'sara_status'      => $orderStatus,
                    ],
                    $errorMessage
                )
            );

            Mail::to("m.tamam@anan.sa")->send(
                new OrderProcessingError(
                    [
                        'keeta_order_id'   => $order->keeta_order_id,
                        'order_request_id' => $order->order_request_id,
                        'sara_status_code' => $statusCode,
                        'sara_status'      => $orderStatus,
                    ],
                    $errorMessage
                )
            );
        }

        $order->save();

        return response()->json(['ok' => true], 200);
    }
}