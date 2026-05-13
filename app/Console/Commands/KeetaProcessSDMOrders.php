<?php

namespace App\Console\Commands;

use App\Models\AggregatorsConfiguration;
use App\Models\KetaaOrder;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Mail\OrderProcessingError;
use Illuminate\Support\Facades\Mail;
use App\Models\KeetaBranch;
use App\Models\BrandAggregatorConfiguration;
use Illuminate\Support\Facades\DB;



class KeetaProcessSDMOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'KeetaProcessSDMOrders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $orders = KetaaOrder::whereNull('SDM_order_id')->get();
        
        foreach ($orders as $order) {
            try {
            DB::transaction(function () use ($order) {
            // Reload the order with a lock to prevent concurrent processing
            $order = KetaaOrder::where('keeta_order_id', $order->keeta_order_id)
                    ->lockForUpdate()
                    ->first();

            // Check if the order has already been processed
                if ($order->SDM_order_id) {
                    Log::info('Order already processed:', [
                        'keeta_order_id' => $order->keeta_order_id
                    ]);
                    return;
                }

            Log::info('Processing order snapshot', [
                'id' => $order->id,
                'pos_system' => $order->pos_system,
                'sdm_order_id' => $order->SDM_order_id,
                'order_request_id' => $order->order_request_id,
            ]);
            

        /**
         * Sara order already created → WAIT for callback
         */
        if (
            $order->pos_system === 'sara' &&
            !empty($order->order_request_id) &&
            is_null($order->SDM_order_id)
            ) {
            $minutesWaiting = $order->created_at->diffInMinutes(now());

            if ($minutesWaiting >= 10) {
                Log::warning('SARA_CALLBACK_PENDING_TOO_LONG', [
                    'keeta_order_id'    => $order->keeta_order_id,
                    'order_request_id' => $order->order_request_id,
                    'created_at'       => $order->created_at->toDateTimeString(),
                    'minutes_waiting'  => $minutesWaiting,
                ]);
                $order->SDM_order_id = '-1';
                $order->order_status = 'timeout';
                $order->save();

            }
            return; // skip to next order
        }
    //});
        /**
         * Order NOT yet created
         */
            $this->routeOrder($order);
        });
    }
        catch (\Throwable $e) {
            Log::error('ORDER_PROCESSING_FAILED', [
                'order_id'        => $order->id,
                'keeta_order_id'  => $order->keeta_order_id,
                'error'           => $e->getMessage(),
            ]);
        };
    }
}


    private function sendToSara(array $payload): array
    {
        $client = new Client(['timeout' => 10]);

        $url = rtrim(config('services.sara.base_url'), '/') . '/order/create-order'; //create-order

        $headers = [
        'Accept' => 'application/json',
        'Authorization' => 'Bearer ' . config('services.sara.access_token'),
        'Secret_key' => config('services.sara.secret_key'),
    ];

        Log::info('Sending order to Sara POS', [
        'url' => $url,
        'headers_present' => [
            'Authorization' => !empty($headers['Authorization']),
            'Secret_key' => !empty($headers['Secret_key']),
        ],
        'json' => $payload,
    ]);

    try{
        $response = $client->post($url, [
        'json' => $payload,
        'headers' => $headers,
    ]);
        }
        catch (\GuzzleHttp\Exception\ClientException $e) {
            $body = (string) $e->getResponse()?->getBody();
            Log::error('Sara API error', [
                'status' => $e->getCode(),
                'body' => $body,
            ]);
            throw new \Exception("Sara API error: {$body}");
        }

        $body = json_decode($response->getBody()->getContents(), true);

        Log::info('Sara POS response', [
            'response' => $body,
        ]);

        return $body;
    }


    private function routeOrder(KetaaOrder $order)
{
    $request = json_decode($order->message, true);

    $shopId = $request['merchantOrder']['shopId'] ?? null;
    if (!$shopId) {
        throw new \Exception('Shop ID missing in keeta payload');
    }

    $branch = KeetaBranch::where('keeta_id', $shopId)->first();
    if (!$branch) {
        throw new \Exception("Branch not found for Keeta ID: {$shopId}");
    }

    $branchPosSystem = strtolower(trim((string) $branch->pos_system));

    Log::info('Routing order', [
        'order_id'         => $order->id,
        'keeta_order_id'   => $order->keeta_order_id,
        'Branch id' => $branch->id,
        'pos_system'       => $branchPosSystem,
        'order_request_id' => $order->order_request_id,
    ]);


    // SARA POS FLOW
    if ($branchPosSystem === 'sara') {

        // HARD STOP: never recreate
        if (!empty($order->order_request_id)) {
            Log::info('Sara order already created, waiting for callback', [
                'order_request_id' => $order->order_request_id,
                'keeta_order_id'   => $order->keeta_order_id,
            ]);
            return json_decode($order->message, true);
        }

        // First and ONLY create
        Log::info('Creating Sara order', [
            'keeta_order_id' => $order->keeta_order_id,
        ]);

        $payload = $this->buildSaraRequest($order->message, $branch);

        $saraResponse = $this->sendToSara($payload);

        $orderRequestedId =
            $saraResponse['response']['order_requested_id']
            ?? $saraResponse['order_requested_id']
            ?? null;

        if (!$orderRequestedId) {
            Log::critical('SARA_CREATE_NO_ORDER_REQUEST_ID', [
                'keeta_order_id' => $order->keeta_order_id,
                'response' => $saraResponse,
            ]);
        
            Mail::to("e.habibi@anan.sa")->send(
                new OrderProcessingError(
                    ['keeta_order_id' => $order->keeta_order_id],
                    'Sara did not return order_request_id'
                )
            );
        
            Mail::to("m.tamam@anan.sa")->send(
                new OrderProcessingError(
                    ['keeta_order_id' => $order->keeta_order_id],
                    'Sara did not return order_request_id'
                )
            );
        
            throw new \Exception('Sara did not return order_request_id');
        }
            

        $order->order_request_id = $orderRequestedId;
        $order->pos_system= $branchPosSystem;
        $order->callback_response = json_encode($saraResponse);
        $order->save();

        return $saraResponse;
    }


    //  old SDM soap flow
    else {
        if ($branchPosSystem == 'sara') {
            return;
        }

            $response = $this->sendToSDM($order->message);
            $sdmOrderId = $this->extractSDMOrderId($response);

            if($sdmOrderId && $sdmOrderId!= '0'){

                $wasFailing = !is_null($order->last_failure_email_sent_at);

                $order->SDM_order_id = $sdmOrderId;
                $order->save();

                if ($wasFailing) {
                    Mail::to("e.habibi@anan.sa")->send(new OrderProcessingError(
                        ['keeta_order_id' => $order->keeta_order_id],
                        'SDM order was successful after retry'
                    ));
                    Log::info('SDM Recovery', [
                        'keeta_order_id' => $order->keeta_order_id,
                        'sdm_order_id' => $sdmOrderId,
                    ]);
                }
                return;
            }
            else {
                $minutesSinceCreated = $order->created_at->diffInMinutes(now());

                Log::error('SDM Order ID is null or zero', [
                    'keeta_order_id' => $order->keeta_order_id,
                    'sdm_order_id' => $sdmOrderId,
                    'minutes_since_created' => $minutesSinceCreated,
                ]);

                if ($minutesSinceCreated >= 10) {
                    $order->SDM_order_id = '-1';
                    $order->order_status = 'sdm_timeout';
                    $order->last_failure_email_sent_at = now();
                    $order->save();

                    Mail::to("e.habibi@anan.sa")->send(
                        new OrderProcessingError(
                            ['keeta_order_id' => $order->keeta_order_id],
                            'FINAL FAILURE: SDM order id not received for 10 minutes'
                        )
                    );

                    Mail::to("m.tamam@anan.sa")->send(
                        new OrderProcessingError(
                            ['keeta_order_id' => $order->keeta_order_id],
                            'FINAL FAILURE: SDM order id not received for 10 minutes'
                        )
                    );

                    Log::error('SDM processing failed after 10 minutes, marking order as failed', [
                        'keeta_order_id' => $order->keeta_order_id,
                    ]);

                    return;
                }

                if ($minutesSinceCreated >= 4 && is_null($order->last_failure_email_sent_at)) {
                    Mail::to("e.habibi@anan.sa")->send(
                    new OrderProcessingError(
                        ['keeta_order_id' => $order->keeta_order_id],
                        'SDM returned null or zero'
                    )
                );
                $order->last_failure_email_sent_at = now();
                $order->save();
                }
                
                return;
        }
    // return $response;
}
}


    private function buildRequest($order)
    {
        // Add required logic to build the $ketaarequest
        return [
            'message' => json_encode([
                'orderId' => $order->keeta_order_id,
                // Add other necessary data
            ]),
        ];
    }




    private function sendToSDM($ketaarequest)
    {
        $client = new Client();
        $url = 'http://188.40.176.126:1994';
        $soapRequest = $this->buildSoapRequest($ketaarequest);

        try {
            Log::info('Sending request to SDM', [
                'url' => $url,
                'soapRequest' => $soapRequest
            ]);

            $response = $client->post($url, [
                'headers' => [
                    'SOAPAction' => 'http://tempuri.org/ISDMSDK/UpdateOrder',
                    'Content-Type' => 'text/xml',
                ],
                'body' => $soapRequest,
                'timeout' => 60
            ]);

            $responseBody = $response->getBody()->getContents();

        return $responseBody;



        } catch (\Exception $e) {
            Log::error('Request to SDM failed', [
                'url' => $url,
                'error_message' => $e->getMessage(),
                'request' => $soapRequest,
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }


    /**
     * Extract SDM Order ID from the response.
     */
    private function extractSDMOrderId($response)
    {
        try {
            // Log the raw response for debugging
            Log::info('Raw SDM Response:', ['response' => $response]);

            // Check if the response is empty or invalid
            if (empty($response)) {
                Log::error('Empty or invalid XML response received from SDM.');
                return null;
            }

            // Attempt to load the XML
            libxml_use_internal_errors(true); // Enable internal error handling
            $xml = simplexml_load_string($response);

            if ($xml === false) {
                // Log XML parsing errors
                $errors = libxml_get_errors();
                foreach ($errors as $error) {
                    Log::error('XML Parsing Error:', [
                        'message' => $error->message,
                        'line' => $error->line,
                        'column' => $error->column,
                    ]);
                }
                libxml_clear_errors(); // Clear errors
                return null;
            }

            // Extract the SDM Order ID
            $namespaces = $xml->getNamespaces(true);
            $body = $xml->children($namespaces['s'])->Body;
            $result = $body->children($namespaces[''])->UpdateOrderResponse->UpdateOrderResult;

            return (string)$result; // SDM Order ID
        } catch (\Exception $e) {
            Log::error('Failed to extract SDM Order ID', ['response' => $response, 'error' => $e->getMessage()]);
            return null;
        }
    }


    /**
     * Build the SOAP request body.
     */
    private function buildSoapRequest($ketaarequest)
    {
        $request = json_decode($ketaarequest, true);
        $shopId = $request['merchantOrder']['shopId'];
        $branch = KeetaBranch::where('keeta_id', $shopId)->first();

        $pos_key = $branch->pos_key;
        $menuTemplateID = $branch->menuTemplateID;
        $conceptID = $branch->conceptID;
        Log::info('Building SOAP request with values:', [
            'pos_key' => $pos_key,
            'menuTemplateID' => $menuTemplateID,
            'conceptID' => $conceptID,
        ]);


        $entriesXml = '';
        $products = $request['products'] ?? [];

        foreach ($products as $orderLine) {
            $count = $orderLine['count'] ?? 0;

            // Loop based on the quantity (same as 'quantity' in C#)
            for ($i = 0; $i < $count; $i++) {
                // Create a new entry object for the main entry
                $entryXml = "<sdm:CEntry>";

                // Check if groups exist (equivalent to 'productOptionsValues' in C#)
                if (!empty($orderLine['groups'])) {
                    // Create a nested <sdm:Entries> tag for grouped items
                    $entriesList = "<sdm:Entries>";

                    foreach ($orderLine['groups'] as $group) {
                        foreach ($group['shopProductGroupSkuList'] as $sku) {
                            // Create new entry for each group SKU
                            $groupEntry = "<sdm:CEntry>";
                            $groupEntry .= "<sdm:ItemID>" . intval($sku['groupSkuOpenItemCode'] ?? 0) . "</sdm:ItemID>";
                            $groupEntry .= "<sdm:ModgroupID>" . intval($group['groupOpenItemCode'] ?? 0) . "</sdm:ModgroupID>";
                            $groupEntry .= "</sdm:CEntry>";

                            // Add the group entry to the entries list
                            $entriesList .= $groupEntry;
                        }
                    }

                    // Close the nested <sdm:Entries> tag
                    $entriesList .= "</sdm:Entries>";

                    // Add the nested entries list inside the main entryXml
                    $entryXml .= $entriesList;

                    // Set ItemID to the product's SPU code (if groups exist)
                    $entryXml .= "<sdm:ItemID>" . intval($orderLine['spuOpenItemCode'] ?? 0) . "</sdm:ItemID>";
                } else {
                    // If no groups exist, just use the default SPU code
                    $entryXml .= "<sdm:ItemID>" . intval($orderLine['spuOpenItemCode'] ?? 0) . "</sdm:ItemID>";
                }

                // Close the CEntry element
                $entryXml .= "</sdm:CEntry>";

                // Append this entryXml to the main entriesXml
                $entriesXml .= $entryXml;
            }
        }
        $PaymentAmount = $request['feeDtl']['customerFee']['productPrice']/100;
        $note = "keeta_Credit_order_id:".$request['baseOrder']['orderViewId'];

        // Prepare the SOAP XML request body with all additional fields
        $soapBody = <<<XML
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tem="http://tempuri.org/" xmlns:sdm="http://schemas.datacontract.org/2004/07/SDM_SDK" xmlns:arr="http://schemas.microsoft.com/2003/10/Serialization/Arrays" xmlns:psor="PSOrderingDOM.Classes" xmlns:sys="http://schemas.datacontract.org/2004/07/System.Drawing">
            <soapenv:Header/>
            <soapenv:Body>
                <tem:UpdateOrder>
                    <tem:licenseCode>Web</tem:licenseCode>
                    <tem:conceptID>{$conceptID}</tem:conceptID>
                    <tem:order>
                        <sdm:AddressID>1016225.0</sdm:AddressID>
                        <sdm:AreaID>0.0</sdm:AreaID>
                        <sdm:AuthReq>0</sdm:AuthReq>
                        <sdm:AuthReqReason>0.0</sdm:AuthReqReason>
                        <sdm:AuthTime>0001-01-01T00:00:00</sdm:AuthTime>
                        <sdm:BackupStoreID>0.0</sdm:BackupStoreID>
                        <sdm:Balance>0.0</sdm:Balance>
                        <sdm:CancelAllowed>false</sdm:CancelAllowed>
                        <sdm:CancelTime>0001-01-01T00:00:00</sdm:CancelTime>
                        <sdm:Change>0.0</sdm:Change>
                        <sdm:CheckNumber>0</sdm:CheckNumber>
                        <sdm:CityID>0.0</sdm:CityID>
                        <sdm:ConceptID>{$conceptID}</sdm:ConceptID>
                        <sdm:CountryID>0.0</sdm:CountryID>
                        <sdm:CreateTime>0001-01-01T00:00:00</sdm:CreateTime>
                        <sdm:CustomerID>568914.0</sdm:CustomerID>
                        <sdm:DateOfTrans>0001-01-01T00:00:00</sdm:DateOfTrans>
                        <sdm:DeliveryChargeID>0.0</sdm:DeliveryChargeID>
                        <sdm:Entries>
                            {$entriesXml}
                        </sdm:Entries>
                        <sdm:ExclusiveTaxesTotal>0.0</sdm:ExclusiveTaxesTotal>
                        <sdm:ExternalStatus>0</sdm:ExternalStatus>
                        <sdm:FirstSendTime>0001-01-01T00:00:00</sdm:FirstSendTime>
                        <sdm:GrossTotal>0.0</sdm:GrossTotal>
                        <sdm:InclusiveTaxesTotal>0.0</sdm:InclusiveTaxesTotal>
                        <sdm:OrderID>0.0</sdm:OrderID>
                        <sdm:OrderMode>2</sdm:OrderMode>
                        <sdm:OrderType>0</sdm:OrderType>
                        <sdm:OriginalStoreID>0.0</sdm:OriginalStoreID>
                        <sdm:PaidOnline>1</sdm:PaidOnline>
                        <sdm:PaymentAmount>{$PaymentAmount }</sdm:PaymentAmount>
                        <sdm:PaymentMethod>CreditCard</sdm:PaymentMethod>
                        <sdm:Payments>
                            <sdm:CC_ORDER_PAYMENT>
                                <sdm:PAY_AMOUNT>{$PaymentAmount}</sdm:PAY_AMOUNT>
                                <sdm:PAY_ID>0.0</sdm:PAY_ID>
                                <sdm:PAY_ORDRID>0.0</sdm:PAY_ORDRID>
                                <sdm:PAY_REF_NO>00</sdm:PAY_REF_NO>
                                <sdm:PAY_STATUS>1</sdm:PAY_STATUS>
                                <sdm:PAY_STORE_TENDERID>88</sdm:PAY_STORE_TENDERID>
                                <sdm:PAY_SUB_TYPE>25</sdm:PAY_SUB_TYPE>
                                <sdm:PAY_TYPE>2</sdm:PAY_TYPE>
                            </sdm:CC_ORDER_PAYMENT>
                        </sdm:Payments>
                        <sdm:PromiseTime>0.0</sdm:PromiseTime>
                        <sdm:ProvinceID>0</sdm:ProvinceID>
                        <sdm:RejectReason>0.0</sdm:RejectReason>
                        <sdm:StoreID>{$pos_key}</sdm:StoreID>
                        <sdm:StreetID>0.0</sdm:StreetID>
                        <sdm:SubTotal>0.0</sdm:SubTotal>
                        <sdm:SuspendReason>0.0</sdm:SuspendReason>
                        <sdm:Total>{$PaymentAmount}</sdm:Total>
                    </tem:order>
                    <tem:autoApprove>true</tem:autoApprove>
                    <tem:useBackupStoreIfAvailable>true</tem:useBackupStoreIfAvailable>
                    <tem:orderNotes1>{$note}</tem:orderNotes1>
                    <tem:orderNotes2>0</tem:orderNotes2>
                    <tem:creditCardPaymentbool>true</tem:creditCardPaymentbool>
                    <tem:isSuspended>false</tem:isSuspended>
                    <tem:menuTemplateID>{$menuTemplateID}</tem:menuTemplateID>
                </tem:UpdateOrder>
            </soapenv:Body>
        </soapenv:Envelope>
    XML;

        return $soapBody;
    }


    private function money(int $halala): float
{
    return round($halala / 100, 2);
}

    private function buildSaraRequest(string $ketaarequest, KeetaBranch $branch): array
    {
        $request = json_decode($ketaarequest, true);

        $shopId = $request['merchantOrder']['shopId'];

        // FORCE correct brand from keeta_branches 
        $effectiveBrandId = (int) $branch->brand_id;

        if ($effectiveBrandId <= 0) {
            throw new \Exception("Invalid brand_id on keeta_branches for keeta_id={$shopId}");
        }


        $config = BrandAggregatorConfiguration::where('brand_id',$effectiveBrandId)
                    ->where("aggregator_name",'Keeta')
                    ->first();

        if(!$config){
            throw new \Exception("Missing brand_aggregator_configuration for brand_id={$branch->brand_id}, aggregator=Keeta");
        }

        if (!$request) {
            throw new \Exception('Invalid keeta payload');
        }

        $vatRate = 15;
        $vatFactor = $vatRate / (100 + $vatRate);

        $menuItems = [];
        $totalGrossCents = 0;
        $totalTaxCents   = 0;


        foreach ($request['products'] ?? [] as $product) {

        $itemQty = (int) ($product['count'] ?? 1);

        // PRICE FROM KEETA IS IN CENTS AND INCLUSIVE OF TAX
        $lineGrossCents = (int) $product['priceWithoutGroup']['amount'];

        // Menu Item
        $unitGrossCents = (int) round($lineGrossCents / $itemQty);

        // Extract VAT FROM LINE TOTAL (matches .NET)
        $lineTaxCents = (int) round($lineGrossCents * $vatFactor);
        $lineNetCents = $lineGrossCents - $lineTaxCents;

        // menu item 
        $unitTaxCents = (int) round($lineTaxCents / $itemQty);
        $unitNetCents = $unitGrossCents - $unitTaxCents;

        // Accumulate order totals
        $totalGrossCents += $lineGrossCents;
        $totalTaxCents   += $lineTaxCents;

            $item = [
                'id' => (int) $product['spuOpenItemCode'],
                'quantity' => $itemQty,
                'type' => 'menu_item',

                'price' => $this->money($unitNetCents),
                'final_price' => $this->money($unitGrossCents),
                'tax_amount' =>  $this->money($unitTaxCents),
                
                'discount_amount' => 0,
                "tax_id" =>  (int) $config->tax_id,
                'tax_applied' => 0,
                'tax_type' => 'inclusive',
                'tax_percentage' => 15,

                'menu_addons_list' => [],
                'menu_addons_price' => 0,
                'menu_addons_tax_amount' => 0,
                'menu_addons_discount_amount' => 0,
                'menu_addons_tax_applied' => 0,
            ];

                if (!empty($product['groups'])) {
                    $addons = [];
                    $addonsNetCents = 0;
                    $addonsTaxCents = 0;

                    foreach($product['groups'] as $group) {
                    foreach ($group['shopProductGroupSkuList'] as $sku) {

                        $addonQty = (int) ($sku['count'] ?? 1);
                        $addonRawGrossCents = (int) ($sku['unitPrice'] ?? 0);

                        // LINE TOTAL FOR ADDON
                        $addonLineGrossCents = $addonRawGrossCents * $itemQty * $addonQty;
                        $addonLineTaxCents = (int) round($addonLineGrossCents * $vatFactor);
                        $addonLineNetCents = $addonLineGrossCents - $addonLineTaxCents;

                        $addonUnitGrossCents = (int) round($addonRawGrossCents * $addonQty);
                        $addonUnitTaxCents = (int) round($addonUnitGrossCents * $vatFactor);
                        $addonUnitNetCents = $addonUnitGrossCents - $addonUnitTaxCents;

                        // Accumulate order totals
                        $totalGrossCents += $addonLineGrossCents;
                        $totalTaxCents   += $addonLineTaxCents;

                        // Addons total
                        $addonsNetCents += $addonUnitNetCents;
                        $addonsTaxCents += $addonUnitTaxCents;

                        $addons[] = [
                            'id' => (int) $sku['groupSkuOpenItemCode'],
                            'name' => $sku['spuName'] ,
                            'quantity' => $addonQty,
                            'menu_item_id' => (int) $product['spuOpenItemCode'],

                            'price' => $this->money($addonUnitNetCents),
                            'tax_amount' => $this->money($addonUnitTaxCents),
                            'discount_amount' => 0,
                            'tax_applied' => 0,
                        ];
                    }
                }

                $item['menu_addons_list'] = $addons;
                $item['menu_addons_price'] = $this->money($addonsNetCents);
                $item['menu_addons_tax_amount'] = $this->money($addonsTaxCents);
            }

            $menuItems[] = $item;
        }

        // 🔑 FINAL TOTALS (SINGLE SOURCE OF TRUTH)
        $totalGross = $totalGrossCents;
        $totalTax   = $totalTaxCents;
        $totalNet   = ($totalGrossCents - $totalTaxCents);

        // IMPORTANT: main items count only
        $totalItemsCount = 0;
        foreach ($request['products'] ?? [] as $product) {
            $totalItemsCount += (int) ($product['count'] ?? 1);
        }


        return [
            'third_party_webhook_url' => (string) config('services.sara.webhook_url'),

            'order_amount' => $this->money($totalGross), 
            'sub_total_price' => $this->money($totalNet),
            'tax_total_price' => $this->money($totalTax),
            'payable_amount' => $this->money($totalGross),
            'net_amount' => $this->money($totalGross),
            'tax_type' => 'inclusive',

            "coupon_id" => null,
            "new_coupon_applied" => null,
            "coupon_type" => null,
            "coupon_code" => null,
            "coupon_applied_number" => null,
            "ordered_items" => null,
            "coupon_approval_request_id" => null,
            "coupon_authentication_otp" => null,
            "discount_data" => null,

            'discount_price' => 0,
            'surcharge_price' => 0,
            'surcharge_tax_amount' => 0,

            'payment_method' => (string) $config->payment_method,
            'tender_name' => (string) $config->tender_name,
            'tender_id' => $config->tender_id,
            'order_mode_id' => $config->order_mode_id,

            'customer_name' => $request['recipientInfo']['name'] ?? 'Aggregator',
            'customer_number' => $request['recipientInfo']['phone'] ?? '',
            'customer_address' => $request['recipientInfo']['addressName'] ?? '',
            'customer_instruction' => $request['baseOrder']['remark'] ?? '', 
            
            'country_code' => '966',


            'company_id' => 1,
            'brand_id' => $branch->brand_id,
            'outlet_id' => (int) $branch->pos_restaurant_id,

            'service_type' => [
                'service_type' => 'quick_service',
                'table_name' =>null,
                'table_number' => null,
            ],

            'menu_items_id' => $menuItems,
            'total_items_count' => $totalItemsCount,

            'aggregator_id' => $config->aggregator_id,
            'aggregator_external_id' => (string) $request['baseOrder']['orderViewId'] ?? '', //"18354783902",
            "aggregator_commission" => null,
            'transaction_id' => '',
        ];
    }


}