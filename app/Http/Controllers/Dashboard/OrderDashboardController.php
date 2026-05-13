<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\KeetaBranch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Jobs\ExportKeetaOrdersJob;

class OrderDashboardController extends Controller
{
    /**
     * Resolve brand name from branch columns.
     * Logic per business rules:
     *   brand_id=1, brand_reference_id=3  → Okashi
     *   brand_id=3                         → Okashi
     *   brand_id=2, brand_reference_id=1004→ Poshak
     *   brand_id=1                         → Casa Pasta
     *   brand_id=2                         → Check & Dip
     */
    public static function resolveBrandName(?int $brandId, ?int $brandRefId): string
    {
        if ($brandId === 3)                                    return 'Okashi';
        if ($brandId === 1 && $brandRefId === 3)               return 'Okashi';
        if ($brandId === 2 && $brandRefId === 1004)            return 'Poshak';
        if ($brandId === 1)                                    return 'Casa Pasta';
        if ($brandId === 2)                                    return 'Chick & Dip';
        return '—';
    }

    // Brand filter values shown in dropdown → maps to SQL conditions
    private function applyBrandFilter($query, string $brand): void
    {
        switch ($brand) {
            case 'casa_pasta':
                $query->where('b.brand_id', 1)
                      ->where(function($q) {
                          $q->where('b.brand_reference_id', '!=', 3)->orWhereNull('b.brand_reference_id');
                      });
                break;
            case 'check_dip':
                $query->where('b.brand_id', 2)
                      ->where(function($q) {
                          $q->where('b.brand_reference_id', '!=', 1004)->orWhereNull('b.brand_reference_id');
                      });
                break;
            case 'okashi':
                $query->where(function($q) {
                    $q->where('b.brand_id', 3)
                      ->orWhere(function($q2) {
                          $q2->where('b.brand_id', 1)->where('b.brand_reference_id', 3);
                      });
                });
                break;
            case 'poshak':
                $query->where('b.brand_id', 2)->where('b.brand_reference_id', 1004);
                break;
        }
    }

    public static function deriveStatus(object $order): array
    {
        $sdm   = $order->SDM_order_id    ?? null;
        $pos   = strtolower(trim((string)($order->pos_system     ?? '')));
        $oStat = $order->order_status    ?? null;
        $reqId = $order->order_request_id ?? null;

        if ($sdm === '-1')           return ['label' => 'Failed',       'class' => 'status-failed'];
        if ($oStat === 'rejected')   return ['label' => 'Rejected',     'class' => 'status-failed'];
        if ($oStat === 'closed')     return ['label' => 'Closed',       'class' => 'status-success'];
        if ($oStat === 'in_kitchen') return ['label' => 'In Kitchen',   'class' => 'status-success'];
        if ($pos === 'sara' && $reqId && is_null($sdm))
                                     return ['label' => 'Sara Waiting', 'class' => 'status-waiting'];
        if ($sdm && $sdm !== '0')    return ['label' => 'Success',      'class' => 'status-success'];
        return                              ['label' => 'Pending',      'class' => 'status-pending'];
    }

    private function applyStatusFilter($query, string $status): void
    {
        switch (strtolower($status)) {
            case 'success':
                $query->whereNotNull('o.SDM_order_id')
                      ->where('o.SDM_order_id', '!=', '0')
                      ->where('o.SDM_order_id', '!=', '-1')
                      ->where(function ($q) {
                          $q->where('o.pos_system', '!=', 'sara')->orWhereNull('o.pos_system');
                      });
                break;
            case 'in kitchen':  $query->where('o.order_status', 'in_kitchen'); break;
            case 'closed':      $query->where('o.order_status', 'closed'); break;
            case 'rejected':    $query->where('o.order_status', 'rejected'); break;
            case 'failed':
                $query->where(function ($q) {
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


    private function combinedOrdersSql(): string
    {
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
    public function showLogin()
    {
        if (session('dashboard_authed')) {
            return redirect()->route('dashboard.orders.index');
        }
        return view('dashboard.login');
    }

    public function postLogin(Request $request)
    {
        $users = [
            env('DASHBOARD_USER',   'admin') => env('DASHBOARD_PASSWORD',   'secret'),
            env('DASHBOARD_USER_2', '')      => env('DASHBOARD_PASSWORD_2', ''),
            env('DASHBOARD_USER_3', '')      => env('DASHBOARD_PASSWORD_3', ''),
        ];

        $username = $request->input('username');
        $password = $request->input('password');

        if (
            !empty($username) &&
            isset($users[$username]) &&
            !empty($users[$username]) &&
            $users[$username] === $password
        ) {
            session(['dashboard_authed' => true]);
            return redirect()->route('dashboard.orders.index');
        }

        return back()->with('error', 'Invalid username or password.');
    }

    public function logout()
    {
        session()->forget('dashboard_authed');
        return redirect()->route('dashboard.login');
    }

    public function index(Request $request)
    {
        $branches   = KeetaBranch::orderBy('branch_name', 'asc')->get();
        $brandNames = [
            'casa_pasta' => 'Casa Pasta',
            'check_dip'  => 'Chick & Dip',
            'okashi'     => 'Okashi',
            'poshak'     => 'Poshak',
        ];

        // Always pass real max ID so the poll anchor is correct regardless of filters
        $maxOrderId = (int)(DB::table('ketaa_orders')->max('id') ?? 0);

        $unionSql = $this->combinedOrdersSql();
        $query = DB::table(DB::raw("({$unionSql}) as o"))
            ->leftJoin('keeta_branches as b', 'b.keeta_id', '=', 'o.shop_id')
            ->select(
                'o.id', 'o.keeta_order_id', 'o.SDM_order_id', 'o.order_request_id',
                'o.pos_system', 'o.order_status', 'o.shop_id',
                'o.last_failure_email_sent_at', 'o.error_message',
                'o.created_at', 'o.updated_at', 'o.message',
                'b.branch_name', 'b.brand_id', 'b.brand_reference_id'
            );

        if ($search = $request->input('search')) {
            $query->where('o.keeta_order_id', 'like', '%' . $search . '%');
        }
        if ($brand = $request->input('brand')) {
            $this->applyBrandFilter($query, $brand);
        }
        if ($branchId = $request->input('branch')) {
            $query->where('o.shop_id', $branchId);
        }
        if ($posSystem = $request->input('pos_system')) {
            if ($posSystem === 'sara') {
                $query->where('o.pos_system', 'sara');
            } elseif ($posSystem === 'sdm') {
                // SDM orders have pos_system NULL (set only after routing) or explicitly not sara
                $query->where(function($q) {
                    $q->whereNull('o.pos_system')
                      ->orWhere('o.pos_system', '!=', 'sara');
                });
            }
        }
        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('o.created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('o.created_at', '<=', $dateTo);
        }
        if ($statusFilter = $request->input('status')) {
            if ($statusFilter !== 'all') {
                $this->applyStatusFilter($query, $statusFilter);
            }
        }

        $orders = $query->orderByDesc('o.created_at')->paginate(50)->appends($request->query());

        foreach ($orders as $order) {
            $d = self::deriveStatus($order);
            $order->derived_status       = $d['label'];
            $order->derived_status_class = $d['class'];
            $order->brand_name           = self::resolveBrandName(
                isset($order->brand_id) ? (int)$order->brand_id : null,
                isset($order->brand_reference_id) ? (int)$order->brand_reference_id : null
            );
            $payload = json_decode($order->message ?? '{}', true) ?? [];
            $order->customer_phone = $payload['recipientInfo']['phone'] ?? null;
        }

        $todayOrders = DB::table('ketaa_orders')
            ->whereRaw("created_at::date = CURRENT_DATE")
            ->get(['SDM_order_id', 'pos_system', 'order_status', 'order_request_id']);

        $stats = [
            'total'        => $todayOrders->count(),
            'success'      => $todayOrders->filter(fn($o) =>
                                  in_array(self::deriveStatus($o)['label'], ['Success','In Kitchen','Closed'])
                              )->count(),
            'pending'      => $todayOrders->filter(fn($o) =>
                                  self::deriveStatus($o)['label'] === 'Pending'
                              )->count(),
            'sara_waiting' => $todayOrders->filter(fn($o) =>
                                  self::deriveStatus($o)['label'] === 'Sara Waiting'
                              )->count(),
            'failed'       => $todayOrders->filter(fn($o) =>
                                  in_array(self::deriveStatus($o)['label'], ['Failed','Rejected'])
                              )->count(),
        ];

        return view('dashboard.orders.index', compact('orders', 'branches', 'brandNames', 'stats', 'maxOrderId'));
    }

    public function show($id)
    {
        $order = DB::table('ketaa_orders as o')
            ->leftJoin('keeta_branches as b', 'b.keeta_id', '=', 'o.shop_id')
            ->select('o.*', 'b.branch_name', 'b.brand_id', 'b.brand_reference_id')
            ->where('o.id', $id)
            ->first();

        if (!$order) {
            $order = DB::table('ketaa_orders_backup as o')
                ->leftJoin('keeta_branches as b', 'b.keeta_id', '=', 'o.shop_id')
                ->select('o.*', 'b.branch_name', 'b.brand_id', 'b.brand_reference_id')
                ->where('o.id', $id)
                ->first();
        }

        abort_if(!$order, 404);

        $d = self::deriveStatus($order);
        $order->derived_status       = $d['label'];
        $order->derived_status_class = $d['class'];
        $order->brand_name = self::resolveBrandName(
            isset($order->brand_id) ? (int)$order->brand_id : null,
            isset($order->brand_reference_id) ? (int)$order->brand_reference_id : null
        );

        $payload          = json_decode($order->message           ?? '{}', true) ?? [];
        $callbackResponse = json_decode($order->callback_response ?? '{}', true) ?? [];
        $errorMessage     = json_decode($order->error_message     ?? '{}', true) ?? [];
        $products         = $payload['products'] ?? [];

        $parsed = [
            'order_view_id'     => $payload['baseOrder']['orderViewId']     ?? '—',
            'order_view_id_str' => $payload['baseOrder']['orderViewIdStr']  ?? '—',
            'shop_id'           => $payload['merchantOrder']['shopId']      ?? $order->shop_id,
            'remark'            => $payload['baseOrder']['remark']          ?? '—',
            'customer_name'     => $payload['recipientInfo']['name']        ?? '—',
            'customer_phone'    => $payload['recipientInfo']['phone']       ?? '—',
            'customer_address'  => $payload['recipientInfo']['addressName'] ?? '—',
            'product_price'     => isset($payload['feeDtl']['customerFee']['productPrice'])
                ? 'SAR ' . number_format($payload['feeDtl']['customerFee']['productPrice'] / 100, 2) : '—',
            'pay_total'         => isset($payload['feeDtl']['customerFee']['payTotal'])
                ? 'SAR ' . number_format($payload['feeDtl']['customerFee']['payTotal'] / 100, 2) : '—',
            'products_count'    => count($products),
        ];

        return view('dashboard.orders.show', compact(
            'order', 'parsed', 'products', 'payload', 'callbackResponse', 'errorMessage'
        ));
    }

    public function poll(Request $request)
    {
        $afterId = (int) $request->input('after', 0);

        if ($afterId === 0) {
            return response()->json([
                'new_orders' => [],
                'latest_id'  => (int)(DB::table('ketaa_orders')->max('id') ?? 0),
            ]);
        }

        $newOrders = DB::table('ketaa_orders as o')
            ->leftJoin('keeta_branches as b', 'b.keeta_id', '=', 'o.shop_id')
            ->select('o.id', 'o.keeta_order_id', 'b.branch_name')
            ->where('o.id', '>', $afterId)
            ->orderByDesc('o.id')
            ->limit(20)
            ->get();

        return response()->json([
            'new_orders' => $newOrders,
            'latest_id'  => (int)($newOrders->max('id') ?? $afterId),
        ]);
    }

    public function requestExport(Request $request)
    {
        $allFields = [
            'id'                         => 'Internal ID',
            'keeta_order_id'             => 'Keeta Order ID',
            'SDM_order_id'               => 'SDM / Sara ID',
            'order_request_id'           => 'Order Request ID',
            'customer_phone'             => 'Customer Phone',
            'brand_name'                 => 'Brand',
            'branch_name'                => 'Branch',
            'shop_id'                    => 'Shop ID',
            'pos_system'                 => 'POS System',
            'derived_status'             => 'Derived Status',
            'order_status'               => 'Sara Order Status',
            'error_message'              => 'Error Message',
            'last_failure_email_sent_at' => 'Failure Email Sent At',
            'created_at'                 => 'Received At',
            'updated_at'                 => 'Updated At',
        ];

        $requested    = $request->input('fields', []);
        $selectedKeys = !empty($requested)
            ? array_values(array_filter(array_keys($allFields), fn($k) => in_array($k, $requested)))
            : array_keys($allFields);

        $filters = array_filter([
            'search'     => $request->input('search'),
            'brand'      => $request->input('brand'),
            'branch'     => $request->input('branch'),
            'pos_system' => $request->input('pos_system'),
            'date_from'  => $request->input('date_from'),
            'date_to'    => $request->input('date_to'),
            'status'     => $request->input('status'),
        ]);

        $token = Str::uuid()->toString();

        Cache::put("export:{$token}", ['status' => 'pending'], now()->addHours(2));

        ExportKeetaOrdersJob::dispatch($token, $selectedKeys, $allFields, $filters);

        return response()->json(['token' => $token]);
    }

    public function exportStatus(string $token)
    {
        $state = Cache::get("export:{$token}");

        if (!$state) {
            return response()->json(['status' => 'expired'], 404);
        }

        return response()->json($state);
    }

    public function exportDownload(string $token)
    {
        $state = Cache::get("export:{$token}");

        abort_if(!$state || $state['status'] !== 'done', 404);

        $path     = $state['file'];
        $filename = $state['filename'] ?? 'keeta-orders.csv';

        abort_if(!Storage::exists($path), 404);

        $fullPath = Storage::path($path);

        // Delete cache entry so it can't be downloaded twice
        Cache::forget("export:{$token}");

        return response()->download($fullPath, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Cache-Control'       => 'no-store, no-cache',
        ])->deleteFileAfterSend(true);
    }
}