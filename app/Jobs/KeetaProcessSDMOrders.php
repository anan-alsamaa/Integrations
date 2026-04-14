<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class KeetaProcessSDMOrders implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Log job start
            Log::info("Processing SDM Order Job started.");

            // Run the Artisan command in the background
            $exitCode = Artisan::call('KeetaProcessSDMOrders');

            // Log command output
            Log::info("Artisan command executed with exit code: " . $exitCode);

            // Optionally log the output of the Artisan command
            Log::info("Artisan command output: " . Artisan::output());

        } catch (\Exception $e) {
            // Log error in case of failure
            Log::error("Error while processing SDM order: " . $e->getMessage());
        }
    }
}
