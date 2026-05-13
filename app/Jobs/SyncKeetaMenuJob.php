<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncKeetaMenuJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $keetaId;
    protected $params;
    protected $accessToken;
    protected $brandId;

    /**
     * Create a new job instance.
     *
     * @param int $keetaId
     * @param array $params
     * @param string $accessToken
     * @param int $brandId
     */
    public function __construct($keetaId, $params, $accessToken, $brandId)
    {
        $this->keetaId = $keetaId;
        $this->params = $params;
        $this->accessToken = $accessToken;
        $this->brandId = $brandId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $url = 'https://open.mykeeta.com/api/open/product/menu/sync';
        
        // Added more detailed logging with brand ID and keeta ID
        Log::info('Starting menu sync job', [
            'keeta_id' => $this->keetaId,
            'brand_id' => $this->brandId,
            'menu_data_size' => count($this->params['spuList'] ?? []) // Log menu size
        ]);

        try {
            // Prepare the parameters with the specific keeta_id
            $params = $this->params;
            $params['shopId'] = $this->keetaId;
            $params['accessToken'] = $this->accessToken;

            // Log the full menu data being processed (as JSON)
            Log::debug('Menu data being processed', [
                'keeta_id' => $this->keetaId,
                'menu_data' => json_encode($params, JSON_PRETTY_PRINT)
            ]);

            // Generate signature
            $sortedKeys = array_keys($params);
            sort($sortedKeys);
            $str = [];
            foreach ($sortedKeys as $key) {
                if ($key == 'sig') continue;
                $value = $params[$key];
                $value = is_array($value) ? json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : $value;
                $str[] = $key . '=' . $value;
            }

            $paramsForSignature = implode('&', $str);
            $requestInfoForSignature = 'https://open.mykeeta.com/api/open/product/menu/sync?' . $paramsForSignature . env('YOUR_SECRET_KEY_HERE');

            // Log the signature generation details
            Log::debug('Signature generation details', [
                'keeta_id' => $this->keetaId,
                'params_for_signature' => $paramsForSignature,
                'full_request_info' => $requestInfoForSignature,
                'secret_key_used' => '***' . substr(env('YOUR_SECRET_KEY_HERE'), -4) // Log partial key for security
            ]);

            $sig = hash('sha256', $requestInfoForSignature);
            $params['sig'] = $sig;

            // Log the final request payload (without sensitive data)
            $loggableParams = $params;
            unset($loggableParams['accessToken']); // Remove sensitive data
            Log::debug('Final request payload', [
                'keeta_id' => $this->keetaId,
                'payload' => $loggableParams
            ]);

            Log::info('Making HTTP request to sync menu for keeta_id: ' . $this->keetaId);
            $response = Http::timeout(600)->retry(3, 1000)->post($url, $params);

            if ($response->successful()) {
                Log::info('Successfully synced menu', [
                    'keeta_id' => $this->keetaId,
                    'response' => $response->json(),
                    'status_code' => $response->status()
                ]);
            } else {
                Log::error('Failed to sync menu with external service', [
                    'keeta_id' => $this->keetaId,
                    'brand_id' => $this->brandId,
                    'status' => $response->status(),
                    'response_body' => $response->body(),
                    'request_payload' => $loggableParams // Include request info for debugging
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error in SyncKeetaMenuJob', [
                'keeta_id' => $this->keetaId,
                'brand_id' => $this->brandId,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'params' => $this->params // Include params that caused the error
            ]);
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::error('SyncKeetaMenuJob failed permanently', [
            'keeta_id' => $this->keetaId,
            'brand_id' => $this->brandId,
            'exception' => $exception->getMessage(),
            'last_params' => $this->params, // Include the params that failed
            'access_token' => '***' . substr($this->accessToken, -4) // Log partial token
        ]);
        
        // You could notify administrators here about the failed job
    }
}