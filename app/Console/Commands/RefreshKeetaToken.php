<?php

namespace App\Console\Commands;

use App\Models\KeetaToken;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RefreshKeetaToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'keetatoken:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh Keeta Token for multiple brands and save new tokens to the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Array of brand IDs to process
        /*   Brand ID Refer
         * 1    => La Casa Pasta
         * 1004 => Poshak
         * 3    => Okashi
         * 2    => check and dieb
         */
        $brandIds = [1, 1004, 3, 2];

        foreach ($brandIds as $brandId) {
            $this->info("Processing brandId: $brandId");

            // Fetch the latest KeetaToken record for the brand
            $latestToken = KeetaToken::where('brandId', $brandId)->latest()->first();

            if (!$latestToken) {
                $this->error("No KeetaToken records found for brandId: $brandId");
                continue;
            }

            // Build the signature string
            $sigstring = 'https://open.mykeeta.com/api/open/base/oauth/token?appId=3493808037&grantType=refresh_token&refreshToken=' . $latestToken->refreshToken . '&timestamp=' . Carbon::now()->timestamp . '1a840cb335ba4a30a7d8611a4e5041ce';

            // Generate the signature using SHA-256
            $sig = hash('sha256', $sigstring);

            // Prepare the POST data
            $body = [
                "appId" => 3493808037,
                "refreshToken" => $latestToken->refreshToken,
                "timestamp" => Carbon::now()->timestamp,
                "grantType" => "refresh_token",
                "sig" => $sig
            ];

            // Make the POST request to the API
            $response = Http::post('https://open.mykeeta.com/api/open/base/oauth/token', $body);

            // Check if the response is successful
            if ($response->successful()) {
                // Decode the response body to extract the new token data
                $data = $response->json();
                Log::info('Data:', (array) $data);
                // Save the new KeetaToken record
                KeetaToken::create([
                    'brandId' => $brandId,
                    'accessToken' => $data['accessToken'],
                    'tokenType' => $data['tokenType'],
                    'expiresIn' => $data['expiresIn'],
                    'refreshToken' => $data['refreshToken'],
                    'scope' => $data['scope'],
                    'issuedAtTime' => $data['issuedAtTime'],
                ]);

                // Delete old tokens for this brandId except the latest
                KeetaToken::where('brandId', $brandId)
                    ->where('id', '!=', KeetaToken::latest('id')->value('id'))
                    ->delete();

                // Output success message
                $this->info("New KeetaToken saved successfully for brandId: $brandId!");
            } else {
                // If the request failed, output the error
                $this->error("Failed to refresh token for brandId: $brandId. Response: " . $response->body());
            }
        }
    }
}
