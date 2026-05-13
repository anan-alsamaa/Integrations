<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ExportKeetaOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600; // 1 hour max
    public int $tries   = 1;    // no retries — file state would be inconsistent

    public function __construct(
        private string $token,
        private array  $selectedKeys,
        private array  $allFields,
        private array  $filters,
    ) {}

    private function combinedOrdersSql(): string
    {
        // Simple UNION ALL -- zero overlaps confirmed, no deduplication needed.
        return <<<'SQL'
            SELECT id, keeta_order_id, "SDM_order_id", order_request_id,
                   pos_system, order_status, shop_id,
                   last_failure_email_sent_at, error_message,
                   created_at, updated_at, message
            FROM ketaa_orders
            UNION ALL
            SELECT id, keeta_order_id, "SDM_order_id",
                   NULL AS order_request_id,
                   NULL AS pos_system,
                   NULL AS order_status,
                   shop_id,
                   NULL AS last_failure_email_sent_at,
                   NULL AS error_message,
                   created_at, updated_at, message
            FROM ketaa_orders_backup
        SQL;
    }

    public function handle(): void
    {
        Cache::put("export:{$this->token}", ['status' => 'processing'], now()->addHours(2));

        try {
            $unionSql = $this->combinedOrdersSql();
            // Only fetch message column if customer_phone is selected
            // message is ~5KB per row -- skipping it on large exports saves GBs of data transfer
            $needsMessage = in_array('customer_phone', $this->selectedKeys);
            $selectCols = [
                'o.id', 'o.keeta_order_id', 'o.SDM_order_id', 'o.order_request_id',
                'o.pos_system', 'o.order_status', 'o.shop_id',
                'o.last_failure_email_sent_at', 'o.error_message',
                'o.created_at', 'o.updated_at',
                'b.branch_name', 'b.brand_id', 'b.brand_reference_id',
            ];
            if ($needsMessage) {
                $selectCols[] = 'o.message';
            }
            $query    = DB::table(DB::raw("({$unionSql}) as o"))
                ->leftJoin('keeta_branches as b', 'b.keeta_id', '=', 'o.shop_id')
                ->select($selectCols)
                ->orderByDesc('o.created_at');

            // Apply filters
            $f = $this->filters;
            if (!empty($f['search']))    $query->where('o.keeta_order_id', 'like', '%'.$f['search'].'%');
            if (!empty($f['brand']))     $this->applyBrandFilter($query, $f['brand']);
            if (!empty($f['branch']))    $query->where('o.shop_id', $f['branch']);
            if (!empty($f['pos_system'])) {
                if ($f['pos_system'] === 'sara') {
                    $query->where('o.pos_system', 'sara');
                } elseif ($f['pos_system'] === 'sdm') {
                    $query->where(function($q) {
                        $q->whereNull('o.pos_system')->orWhere('o.pos_system', '!=', 'sara');
                    });
                }
            }
            if (!empty($f['date_from'])) $query->whereDate('o.created_at', '>=', $f['date_from']);
            if (!empty($f['date_to']))   $query->whereDate('o.created_at', '<=', $f['date_to']);
            if (!empty($f['status']) && $f['status'] !== 'all') {
                $this->applyStatusFilter($query, $f['status']);
            }

            // Write CSV to storage
            $path = "exports/{$this->token}.csv";
            Storage::put($path, ''); // create empty file

            $handle   = fopen(Storage::path($path), 'w');
            fwrite($handle, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
            fputcsv($handle, array_map(fn($k) => $this->allFields[$k], $this->selectedKeys));

            $rowCount = 0;
            $needsMessage = in_array('customer_phone', $this->selectedKeys);
            $query->chunk(500, function ($chunk) use ($handle, &$rowCount, $needsMessage) {
                foreach ($chunk as $order) {
                    $customerPhone = '';
                    if ($needsMessage) {
                        $payload       = json_decode($order->message ?? '{}', true) ?? [];
                        $customerPhone = $payload['recipientInfo']['phone'] ?? '';
                    }
                    $derived       = null;
                    $brandName     = null;
                    $errorText     = null;

                    if (in_array('derived_status', $this->selectedKeys)) {
                        $derived = $this->deriveStatusLabel($order);
                    }
                    if (in_array('brand_name', $this->selectedKeys)) {
                        $brandName = $this->resolveBrandName(
                            isset($order->brand_id)           ? (int)$order->brand_id           : null,
                            isset($order->brand_reference_id) ? (int)$order->brand_reference_id : null
                        );
                    }
                    if (in_array('error_message', $this->selectedKeys)) {
                        $raw       = $order->error_message ?? '';
                        $decoded   = json_decode($raw, true);
                        $errorText = is_array($decoded) ? ($decoded['Message'] ?? $raw) : $raw;
                    }

                    $row = [];
                    foreach ($this->selectedKeys as $key) {
                        $row[] = match ($key) {
                            'customer_phone' => $customerPhone,
                            'derived_status' => $derived ?? '',
                            'brand_name'     => $brandName ?? '',
                            'error_message'  => $errorText ?? '',
                            'pos_system'     => $order->pos_system ? strtoupper($order->pos_system) : 'SDM',
                            default          => $order->{$key} ?? '',
                        };
                    }
                    fputcsv($handle, $row);
                    $rowCount++;
                }
            });

            fclose($handle);

            Cache::put("export:{$this->token}", [
                'status'    => 'done',
                'row_count' => $rowCount,
                'file'      => $path,
                'filename'  => 'keeta-orders-' . now()->format('Y-m-d_His') . '.csv',
            ], now()->addHours(2));

            Log::info("Export {$this->token} completed: {$rowCount} rows");

        } catch (\Throwable $e) {
            Log::error("Export {$this->token} failed: " . $e->getMessage());
            Cache::put("export:{$this->token}", [
                'status'  => 'failed',
                'message' => $e->getMessage(),
            ], now()->addHours(2));
        }
    }

    private function deriveStatusLabel(object $order): string
    {
        $sdm   = $order->SDM_order_id    ?? null;
        $pos   = strtolower(trim((string)($order->pos_system ?? '')));
        $oStat = $order->order_status    ?? null;
        $reqId = $order->order_request_id ?? null;

        if ($sdm === '-1')           return 'Failed';
        if ($oStat === 'rejected')   return 'Rejected';
        if ($oStat === 'closed')     return 'Closed';
        if ($oStat === 'in_kitchen') return 'In Kitchen';
        if ($pos === 'sara' && $reqId && is_null($sdm)) return 'Sara Waiting';
        if ($sdm && $sdm !== '0')    return 'Success';
        return 'Pending';
    }

    private function resolveBrandName(?int $brandId, ?int $brandRefId): string
    {
        if ($brandId === 3)                         return 'Okashi';
        if ($brandId === 1 && $brandRefId === 3)    return 'Okashi';
        if ($brandId === 2 && $brandRefId === 1004) return 'Poshak';
        if ($brandId === 1)                         return 'Casa Pasta';
        if ($brandId === 2)                         return 'Chick & Dip';
        return '—';
    }

    private function applyBrandFilter($query, string $brand): void
    {
        switch ($brand) {
            case 'casa_pasta':
                $query->where('b.brand_id', 1)->where(function($q) {
                    $q->where('b.brand_reference_id', '!=', 3)->orWhereNull('b.brand_reference_id');
                });
                break;
            case 'check_dip':
                $query->where('b.brand_id', 2)->where(function($q) {
                    $q->where('b.brand_reference_id', '!=', 1004)->orWhereNull('b.brand_reference_id');
                });
                break;
            case 'okashi':
                $query->where(function($q) {
                    $q->where('b.brand_id', 3)->orWhere(function($q2) {
                        $q2->where('b.brand_id', 1)->where('b.brand_reference_id', 3);
                    });
                });
                break;
            case 'poshak':
                $query->where('b.brand_id', 2)->where('b.brand_reference_id', 1004);
                break;
        }
    }

    private function applyStatusFilter($query, string $status): void
    {
        switch (strtolower($status)) {
            case 'success':
                $query->whereNotNull('o.SDM_order_id')
                      ->where('o.SDM_order_id', '!=', '0')
                      ->where('o.SDM_order_id', '!=', '-1')
                      ->where(function($q) {
                          $q->where('o.pos_system', '!=', 'sara')->orWhereNull('o.pos_system');
                      });
                break;
            case 'in kitchen':  $query->where('o.order_status', 'in_kitchen'); break;
            case 'closed':      $query->where('o.order_status', 'closed'); break;
            case 'rejected':    $query->where('o.order_status', 'rejected'); break;
            case 'failed':
                $query->where(function($q) {
                    $q->where('o.SDM_order_id', '-1')->orWhere('o.order_status', 'rejected');
                });
                break;
            case 'sara waiting':
                $query->where('o.pos_system', 'sara')
                      ->whereNotNull('o.order_request_id')
                      ->whereNull('o.SDM_order_id');
                break;
            case 'pending':
                $query->whereNull('o.SDM_order_id')->whereNull('o.order_request_id');
                break;
        }
    }
}