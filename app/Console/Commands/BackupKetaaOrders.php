<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BackupKetaaOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backup-ketaa-orders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backup Ketaa orders and clear the table at 6 AM';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            DB::transaction(function () {
                // Fetch all records and convert to array format, excluding the `id` column
                $orders = DB::table('ketaa_orders')->get()->map(function ($order) {
                    $orderArray = (array) $order;
                    unset($orderArray['id']); // Remove the `id` field from the array
                    return $orderArray;
                })->toArray();

                // Insert the data into the backup table
                DB::table('ketaa_orders_backup')->insert($orders);

                // Truncate the original table
                DB::table('ketaa_orders')->truncate();
            });

            // Log success message
            Log::info('Ketaa orders backed up and cleared successfully.');
            $this->info('Ketaa orders backed up and cleared successfully.');

        } catch (\Exception $e) {
            // Log exception message in case of failure
            Log::error('Error occurred while backing up Ketaa orders: ' . $e->getMessage());
            $this->error('Error occurred while backing up Ketaa orders: ' . $e->getMessage());
        }
    }
}
