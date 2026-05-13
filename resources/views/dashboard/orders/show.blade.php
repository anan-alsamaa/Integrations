@extends('dashboard.layout')
@section('title', 'Order Detail · Keeta Ops')
@section('topbar-title', 'Order Detail')

@section('content')

<div class="breadcrumb">
    <a href="{{ route('dashboard.orders.index') }}">Home</a>
    <span class="sep">/</span>
    <a href="{{ route('dashboard.orders.index') }}">Orders</a>
    <span class="sep">/</span>
    <span class="mono">{{ $order->keeta_order_id }}</span>
</div>

{{-- Header --}}
<div style="display:flex; align-items:center; gap:12px; margin-bottom:20px; flex-wrap:wrap;">
    <div>
        <div style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.07em; color:var(--ink-3); margin-bottom:3px;">Order</div>
        <div style="display:flex; align-items:center; gap:10px;">
            <span class="mono" style="font-size:15px; font-weight:600; color:var(--ink);">{{ $order->keeta_order_id }}</span>
            <span class="badge {{ $order->derived_status_class }}">{{ $order->derived_status }}</span>
        </div>
    </div>
    <a href="{{ route('dashboard.orders.index') }}" class="btn btn-ghost btn-sm" style="margin-left:auto;">← Back</a>
</div>

@if(in_array($order->derived_status, ['Failed','Rejected']))
    <div class="alert alert-danger mb-4">
        <span>⚠</span>
        <div>
            <strong>Processing failed</strong>
            @if(!empty($errorMessage['Message']))
                — {{ $errorMessage['Message'] }}
            @elseif($order->order_status === 'rejected')
                — Rejected by POS
            @else
                — SDM returned –1
            @endif
        </div>
    </div>
@endif

{{-- Two-column cards --}}
<div class="two-col">
    <div class="card">
        <div class="card-head">Order Record</div>
        <table class="kv-table">
            <tr><td>Internal ID</td>      <td><span class="mono">#{{ $order->id }}</span></td></tr>
            <tr><td>Keeta Order ID</td>   <td><span class="mono" style="font-size:11px; word-break:break-all; color:var(--accent);">{{ $order->keeta_order_id }}</span></td></tr>
            <tr><td>SDM / Sara ID</td>
                <td>
                    @if($order->SDM_order_id === '-1')
                        <span class="mono" style="color:var(--red); font-weight:600;">–1 (failed)</span>
                    @elseif($order->SDM_order_id)
                        <span class="mono" style="color:var(--green); font-weight:600;">{{ $order->SDM_order_id }}</span>
                    @else <span class="muted">—</span>
                    @endif
                </td>
            </tr>
            <tr><td>Order Request ID</td>
                <td>
                    @if($order->order_request_id)
                        <span class="mono" style="font-size:11px; color:var(--blue); word-break:break-all;">{{ $order->order_request_id }}</span>
                    @else <span class="muted">—</span>
                    @endif
                </td>
            </tr>
            <tr><td>POS System</td>
                <td>
                    @if($order->pos_system)
                        <span class="mono" style="text-transform:uppercase; font-weight:600; font-size:11px;">{{ $order->pos_system }}</span>
                    @else <span class="muted">—</span>
                    @endif
                </td>
            </tr>
            <tr><td>Branch</td>       <td style="font-weight:500;">{{ $order->branch_name ?? '—' }}</td></tr>
            <tr><td>Shop ID</td>      <td><span class="mono">{{ $order->shop_id }}</span></td></tr>
            <tr><td>Status</td>       <td><span class="badge {{ $order->derived_status_class }}">{{ $order->derived_status }}</span></td></tr>
            <tr><td>Sara Status</td>  <td>{{ $order->order_status ?? '—' }}</td></tr>
            <tr><td>Received</td>     <td><span class="mono" style="font-size:12px;">{{ \Carbon\Carbon::parse($order->created_at)->format('d M Y, H:i:s') }}</span></td></tr>
            <tr><td>Updated</td>      <td><span class="mono" style="font-size:12px;">{{ \Carbon\Carbon::parse($order->updated_at)->format('d M Y, H:i:s') }}</span></td></tr>
            @if($order->last_failure_email_sent_at)
            <tr><td>Failure Email</td><td><span class="mono" style="font-size:12px; color:var(--red);">{{ \Carbon\Carbon::parse($order->last_failure_email_sent_at)->format('d M Y, H:i:s') }}</span></td></tr>
            @endif
        </table>
    </div>

    <div class="card">
        <div class="card-head">Parsed Fields</div>
        <table class="kv-table">
            <tr><td>Order View ID</td>     <td><span class="mono" style="font-size:11px; word-break:break-all;">{{ $parsed['order_view_id'] }}</span></td></tr>
            <tr><td>Order View ID Str</td> <td><span class="mono" style="font-size:11px; word-break:break-all;">{{ $parsed['order_view_id_str'] }}</span></td></tr>
            <tr><td>Shop ID</td>           <td><span class="mono">{{ $parsed['shop_id'] }}</span></td></tr>
            <tr><td>Remark</td>            <td>{{ $parsed['remark'] }}</td></tr>
            <tr><td>Customer Name</td>     <td style="font-weight:500;">{{ $parsed['customer_name'] }}</td></tr>
            <tr><td>Customer Phone</td>    <td><span class="mono">{{ $parsed['customer_phone'] }}</span></td></tr>
            <tr><td>Address</td>           <td>{{ $parsed['customer_address'] }}</td></tr>
            <tr><td>Product Price</td>     <td><span class="mono">{{ $parsed['product_price'] }}</span></td></tr>
            <tr><td>Pay Total</td>         <td><span class="mono" style="font-weight:600; color:var(--green);">{{ $parsed['pay_total'] }}</span></td></tr>
            <tr><td>Items</td>             <td><span class="mono" style="font-weight:600;">{{ $parsed['products_count'] }}</span></td></tr>
        </table>
    </div>
</div>

{{-- Order items --}}
<div class="card mb-4">
    <div class="card-head">
        Order Items
        @if(count($products))
            <span style="margin-left:auto; font-size:11px; font-weight:600; color:var(--ink-3);">
                {{ count($products) }} item{{ count($products)>1?'s':'' }}
            </span>
        @endif
    </div>
    @if(!empty($products))
    <div style="overflow-x:auto;">
        <table class="items-tbl">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Item ID</th>
                    <th>Qty</th>
                    <th>Unit Price</th>
                    <th>Line Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($products as $p)
                    @php
                        $iid   = $p['spuOpenItemCode'] ?? $p['articleId'] ?? '—';
                        $iname = $p['spuName'] ?? $p['name'] ?? ('Item #'.$iid);
                        $qty   = (int)($p['count'] ?? $p['quantity'] ?? 1);
                        $lc    = $p['priceWithoutGroup']['amount'] ?? null;
                        $uc    = ($lc!==null && $qty>0) ? round($lc/$qty) : null;
                        $grps  = $p['groups'] ?? [];
                    @endphp
                    <tr>
                        <td style="font-weight:500;">{{ $iname }}</td>
                        <td class="mono muted">{{ $iid }}</td>
                        <td class="mono" style="font-weight:600;">× {{ $qty }}</td>
                        <td class="mono">{{ $uc!==null ? 'SAR '.number_format($uc/100,2) : '—' }}</td>
                        <td class="mono" style="font-weight:600;">{{ $lc!==null ? 'SAR '.number_format($lc/100,2) : '—' }}</td>
                    </tr>
                    @foreach($grps as $g)
                        @foreach($g['shopProductGroupSkuList'] ?? [] as $s)
                            <tr class="addon-row">
                                <td>{{ $s['spuName'] ?? ('Addon #'.($s['groupSkuOpenItemCode']??'?')) }}</td>
                                <td class="mono">{{ $s['groupSkuOpenItemCode'] ?? '—' }}</td>
                                <td class="mono">× {{ $s['count'] ?? 1 }}</td>
                                <td class="mono">{{ isset($s['unitPrice']) ? 'SAR '.number_format($s['unitPrice']/100,2) : '—' }}</td>
                                <td class="mono muted">—</td>
                            </tr>
                        @endforeach
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
    @else
        <div style="padding:24px 18px; color:var(--ink-3); font-size:13px;">No product data in payload.</div>
    @endif
</div>

{{-- Sara callback --}}
@if(!empty($order->callback_response) && !in_array($order->callback_response, ['{}','null']))
<div class="card mb-4">
    <div class="collapse-hdr">
        Sara Callback Response
        <span class="toggle-chevron">▾</span>
    </div>
    <div class="collapse-body">
        <div class="json-toolbar">
            <input type="text" placeholder="Search… (Ctrl+F)" id="cbSearch" oninput="jsSearch('cb')">
            <span class="match-info" id="cbCount"></span>
            <button class="json-nav" onclick="jsNav('cb',-1)">↑</button>
            <button class="json-nav" onclick="jsNav('cb',1)">↓</button>
        </div>
        <pre class="json-block" id="cbBlock">{{ json_encode($callbackResponse, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) }}</pre>
    </div>
</div>
@endif

{{-- Full payload --}}
<div class="card mb-4">
    <div class="collapse-hdr">
        Full Keeta Payload
        <span class="toggle-chevron">▾</span>
    </div>
    <div class="collapse-body">
        <div class="json-toolbar">
            <input type="text" placeholder="Search… (Ctrl+F)" id="plSearch" oninput="jsSearch('pl')">
            <span class="match-info" id="plCount"></span>
            <button class="json-nav" onclick="jsNav('pl',-1)">↑</button>
            <button class="json-nav" onclick="jsNav('pl',1)">↓</button>
        </div>
        <pre class="json-block" id="plBlock">{{ json_encode($payload, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) }}</pre>
    </div>
</div>

@endsection

@push('scripts')
<script>
const js = {
    pl: { raw:'', matches:[], cur:0 },
    cb: { raw:'', matches:[], cur:0 },
};

window.addEventListener('DOMContentLoaded', () => {
    const pl = document.getElementById('plBlock');
    const cb = document.getElementById('cbBlock');
    if (pl) js.pl.raw = pl.textContent;
    if (cb) js.cb.raw = cb.textContent;

    document.addEventListener('keydown', e => {
        if ((e.ctrlKey||e.metaKey) && e.key==='f') {
            e.preventDefault();
            const cbOpen = cb && cb.closest('.collapse-body.open');
            if (cbOpen) { document.getElementById('cbSearch').focus(); return; }
            const hdr = pl?.closest('.collapse-body')?.previousElementSibling;
            if (hdr && !hdr.classList.contains('open')) hdr.click();
            document.getElementById('plSearch')?.focus();
        }
    });
});

function esc(t) {
    return t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function re(s) { return s.replace(/[.*+?^${}()|[\]\\]/g,'\\$&'); }

function jsSearch(k) {
    const inp  = document.getElementById(k==='pl'?'plSearch':'cbSearch');
    const blk  = document.getElementById(k==='pl'?'plBlock':'cbBlock');
    const cnt  = document.getElementById(k==='pl'?'plCount':'cbCount');
    const st   = js[k];
    const q    = inp.value.trim();

    if (!q) { blk.innerHTML = esc(st.raw); cnt.textContent=''; st.matches=[]; st.cur=0; return; }

    const raw = esc(st.raw);
    const rx  = new RegExp(re(esc(q)),'gi');
    const pos = []; let m;
    while ((m=rx.exec(raw))!==null) pos.push(m.index);

    st.matches = pos; st.cur = 0;

    if (!pos.length) { blk.innerHTML=raw; cnt.textContent='0'; cnt.style.color='#f87171'; return; }
    cnt.textContent = pos.length; cnt.style.color='';
    render(k, raw, q);
}

function render(k, raw, q) {
    const blk = document.getElementById(k==='pl'?'plBlock':'cbBlock');
    const st  = js[k];
    let i = 0;
    blk.innerHTML = raw.replace(new RegExp(re(esc(q)),'gi'), m => {
        const cls = i===st.cur ? 'hl cur':'hl';
        i++; return `<mark class="${cls}">${m}</mark>`;
    });
    blk.querySelectorAll('mark')[st.cur]?.scrollIntoView({block:'center',behavior:'smooth'});
    const cnt = document.getElementById(k==='pl'?'plCount':'cbCount');
    cnt.textContent = `${st.cur+1}/${st.matches.length}`;
}

function jsNav(k, d) {
    const st = js[k];
    if (!st.matches.length) return;
    st.cur = (st.cur+d+st.matches.length) % st.matches.length;
    const inp = document.getElementById(k==='pl'?'plSearch':'cbSearch');
    render(k, esc(st.raw), inp.value.trim());
}
</script>
@endpush