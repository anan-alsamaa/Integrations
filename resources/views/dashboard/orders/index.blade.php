@extends('dashboard.layout')
@section('title', 'Orders · Keeta Ops')
@section('topbar-title', 'Orders')
@section('last-order-id', $maxOrderId)

@section('content')

<div class="breadcrumb">
    <a href="{{ route('dashboard.orders.index') }}">Home</a>
    <span class="sep">/</span>
    <span>Orders</span>
</div>

{{-- Stats --}}
<div class="stats-row">
    <div class="stat stat-accent s-total">
        <div class="stat-num">{{ $stats['total'] }}</div>
        <div class="stat-lbl">Total Today</div>
    </div>
    <div class="stat stat-accent s-ok">
        <div class="stat-num">{{ $stats['success'] }}</div>
        <div class="stat-lbl">Success</div>
    </div>
    <div class="stat stat-accent s-pend">
        <div class="stat-num">{{ $stats['pending'] }}</div>
        <div class="stat-lbl">Pending</div>
    </div>
    <div class="stat stat-accent s-wait">
        <div class="stat-num">{{ $stats['sara_waiting'] }}</div>
        <div class="stat-lbl">Sara Waiting</div>
    </div>
    <div class="stat stat-accent s-fail">
        <div class="stat-num">{{ $stats['failed'] }}</div>
        <div class="stat-lbl">Failed</div>
    </div>
</div>

{{-- Table card --}}
<div class="card">
    <form method="GET" action="{{ route('dashboard.orders.index') }}">
        <div class="filters">

            <input type="text" name="search" placeholder="Search order ID…"
                   value="{{ request('search') }}" style="width:180px;">

            <select name="brand" style="width:140px;">
                <option value="">All Brands</option>
                @foreach($brandNames as $key => $name)
                    <option value="{{ $key }}" {{ request('brand') === $key ? 'selected' : '' }}>
                        {{ $name }}
                    </option>
                @endforeach
            </select>

            <select name="branch" style="width:160px;">
                <option value="">All Branches</option>
                @foreach($branches as $b)
                    <option value="{{ $b->keeta_id }}" {{ request('branch') == $b->keeta_id ? 'selected' : '' }}>
                        {{ $b->branch_name }}
                    </option>
                @endforeach
            </select>

            <select name="status" style="width:135px;">
                <option value="all"          {{ request('status','all')==='all'         ? 'selected':'' }}>All Statuses</option>
                <option value="success"      {{ request('status')==='success'           ? 'selected':'' }}>Success</option>
                <option value="in kitchen"   {{ request('status')==='in kitchen'        ? 'selected':'' }}>In Kitchen</option>
                <option value="closed"       {{ request('status')==='closed'            ? 'selected':'' }}>Closed</option>
                <option value="pending"      {{ request('status')==='pending'           ? 'selected':'' }}>Pending</option>
                <option value="sara waiting" {{ request('status')==='sara waiting'      ? 'selected':'' }}>Sara Waiting</option>
                <option value="failed"       {{ request('status')==='failed'            ? 'selected':'' }}>Failed</option>
                <option value="rejected"     {{ request('status')==='rejected'          ? 'selected':'' }}>Rejected</option>
            </select>

            <select name="pos_system" style="width:120px;">
                <option value="">All Systems</option>
                <option value="sara" {{ request('pos_system')==='sara' ? 'selected':'' }}>Sara</option>
                <option value="sdm"  {{ request('pos_system')==='sdm'  ? 'selected':'' }}>SDM</option>
            </select>

            <input type="date" name="date_from" value="{{ request('date_from') }}">
            <input type="date" name="date_to"   value="{{ request('date_to') }}">

            <button type="submit" class="btn btn-dark">Filter</button>
            <a href="{{ route('dashboard.orders.index') }}" class="btn btn-ghost">Clear</a>
            <button type="button" class="btn btn-ghost" onclick="openExportDrawer()"
                    style="color:var(--green); border-color:var(--green); gap:6px;">
                <svg width="13" height="13" viewBox="0 0 14 14" fill="none" style="flex-shrink:0;">
                    <path d="M7 1v8M3.5 6l3.5 3.5L10.5 6M1.5 11.5h11" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Export CSV
            </button>

            <span style="margin-left:auto; font-size:12px; color:var(--ink-3); white-space:nowrap;">
                {{ $orders->firstItem() ?? 0 }}–{{ $orders->lastItem() ?? 0 }}
                of {{ $orders->total() }}
            </span>
        </div>
    </form>

    <div style="overflow-x:auto;">
        <table class="tbl">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Keeta Order ID</th>
                    <th>SDM / Sara ID</th>
                    <th>Order Request ID</th>
                    <th>Brand</th>
                    <th>Branch</th>
                    <th>POS</th>
                    <th>Status</th>
                    <th>Received</th>
                    <th>Updated</th>
                    <th>Customer Phone</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="ordersBody">
                @forelse($orders as $o)
                    <tr class="{{ in_array($o->derived_status,['Failed','Rejected']) ? 'failed-row' : '' }}"
                        data-id="{{ $o->id }}">

                        <td class="mono muted">{{ $o->id }}</td>

                        <td>
                            <a href="{{ route('dashboard.orders.show', $o->id) }}"
                               class="mono" style="color:var(--accent); text-decoration:none; font-weight:500;">
                                {{ $o->keeta_order_id }}
                            </a>
                        </td>

                        <td class="mono">
                            @if($o->SDM_order_id === '-1')
                                <span style="color:var(--red); font-weight:600;">–1</span>
                            @elseif($o->SDM_order_id)
                                <span style="color:var(--green);">{{ $o->SDM_order_id }}</span>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>

                        <td class="mono" style="max-width:140px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                            @if($o->order_request_id)
                                <span style="color:var(--blue);" title="{{ $o->order_request_id }}">
                                    {{ $o->order_request_id }}
                                </span>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>

                        <td>
                            @if(isset($o->brand_name) && $o->brand_name !== '—')
                                <span style="font-size:11.5px; font-weight:600; color:var(--ink-2);">
                                    {{ $o->brand_name }}
                                </span>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>

                        <td style="font-weight:500; white-space:nowrap;">{{ $o->branch_name ?? '—' }}</td>

                        <td>
                            @if($o->pos_system)
                                <span class="mono muted" style="font-size:11px; text-transform:uppercase; letter-spacing:0.05em;">
                                    {{ $o->pos_system }}
                                </span>
                            @else
                                <span class="muted" style="font-size:11px;">SDM</span>
                            @endif
                        </td>

                        <td>
                            <span class="badge {{ $o->derived_status_class }}">
                                {{ $o->derived_status }}
                            </span>
                        </td>

                        <td class="mono muted" style="font-size:11.5px; white-space:nowrap;">
                            {{ \Carbon\Carbon::parse($o->created_at)->format('d M, H:i:s') }}
                        </td>

                        <td class="mono muted" style="font-size:11.5px; white-space:nowrap;">
                            {{ \Carbon\Carbon::parse($o->updated_at)->format('d M, H:i:s') }}
                        </td>

                        <td class="mono" style="font-size:12px; white-space:nowrap;">
                            {{ $o->customer_phone ?? '—' }}
                        </td>

                        <td>
                            <a href="{{ route('dashboard.orders.show', $o->id) }}"
                               class="btn btn-ghost btn-sm">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12" style="text-align:center; padding:50px; color:var(--ink-3);">
                            No orders match the current filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($orders->hasPages())
        <div class="pager">
            <span>Page {{ $orders->currentPage() }} of {{ $orders->lastPage() }}</span>
            <div class="pager-links">
                @if($orders->onFirstPage())
                    <span>‹</span>
                @else
                    <a href="{{ $orders->previousPageUrl() }}">‹</a>
                @endif

                @php $s = max(1,$orders->currentPage()-2); $e = min($orders->lastPage(),$orders->currentPage()+2); @endphp
                @for($p=$s; $p<=$e; $p++)
                    @if($p===$orders->currentPage())
                        <span class="cur">{{ $p }}</span>
                    @else
                        <a href="{{ $orders->url($p) }}">{{ $p }}</a>
                    @endif
                @endfor

                @if($orders->hasMorePages())
                    <a href="{{ $orders->nextPageUrl() }}">›</a>
                @else
                    <span>›</span>
                @endif
            </div>
        </div>
    @endif
</div>



{{-- ── EXPORT DRAWER ── --}}
@push('head')
<style>
/* Overlay */
#exportOverlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.35);
    z-index: 400;
    backdrop-filter: blur(1px);
    animation: fadeIn 0.18s ease;
}
#exportOverlay.open { display: block; }

@keyframes fadeIn { from { opacity:0; } to { opacity:1; } }

/* Drawer panel */
#exportDrawer {
    position: fixed;
    top: 0;
    right: -420px;
    bottom: 0;
    width: 380px;
    background: var(--surface);
    border-left: 1px solid var(--border);
    z-index: 401;
    display: flex;
    flex-direction: column;
    transition: right 0.28s cubic-bezier(0.16,1,0.3,1);
    box-shadow: -8px 0 40px rgba(0,0,0,0.12);
}
#exportDrawer.open { right: 0; }

/* Drawer header */
.exp-hdr {
    padding: 18px 20px 14px;
    border-bottom: 1px solid var(--border2);
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: var(--surface2);
    flex-shrink: 0;
}
.exp-hdr-left h2 {
    font-size: 14px;
    font-weight: 600;
    color: var(--ink);
    letter-spacing: -0.01em;
    margin-bottom: 2px;
}
.exp-hdr-left p {
    font-size: 11.5px;
    color: var(--ink-3);
    font-weight: 400;
}
.exp-close {
    width: 28px; height: 28px;
    border-radius: 6px;
    border: 1px solid var(--border);
    background: transparent;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    color: var(--ink-3);
    transition: all 0.1s;
    flex-shrink: 0;
}
.exp-close:hover { background: var(--red-bg); border-color: #fecaca; color: var(--red); }

/* Active filter badge */
.exp-filter-summary {
    padding: 10px 20px;
    background: var(--accent-bg);
    border-bottom: 1px solid #fde0d1;
    font-size: 11.5px;
    color: var(--accent);
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 6px;
    flex-shrink: 0;
}
.exp-filter-summary.hidden { display: none; }

/* Scrollable field list */
.exp-body {
    flex: 1;
    overflow-y: auto;
    padding: 16px 20px;
}

/* Section label */
.exp-section-lbl {
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--ink-3);
    margin: 14px 0 8px;
}
.exp-section-lbl:first-child { margin-top: 0; }

/* Field checkbox row */
.exp-field {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 10px;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.1s;
    user-select: none;
    margin-bottom: 2px;
}
.exp-field:hover { background: var(--surface2); }
.exp-field input[type="checkbox"] {
    width: 15px; height: 15px;
    accent-color: var(--accent);
    cursor: pointer;
    flex-shrink: 0;
}
.exp-field-info { flex: 1; min-width: 0; }
.exp-field-name {
    font-size: 13px;
    font-weight: 500;
    color: var(--ink);
    line-height: 1;
}
.exp-field-hint {
    font-size: 11px;
    color: var(--ink-3);
    margin-top: 2px;
    font-family: 'DM Mono', monospace;
}

/* Select all toggle */
.exp-toggle-all {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 0 12px;
    border-bottom: 1px solid var(--border2);
    margin-bottom: 4px;
}
.exp-toggle-all span { font-size: 12px; color: var(--ink-3); }
.exp-toggle-all button {
    font-size: 11.5px;
    font-weight: 500;
    color: var(--accent);
    background: none;
    border: none;
    cursor: pointer;
    padding: 2px 6px;
    border-radius: 4px;
    transition: background 0.1s;
    font-family: 'DM Sans', sans-serif;
}
.exp-toggle-all button:hover { background: var(--accent-bg); }

/* Footer */
.exp-footer {
    padding: 14px 20px;
    border-top: 1px solid var(--border2);
    background: var(--surface2);
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
}
.exp-count {
    font-size: 11.5px;
    color: var(--ink-3);
    margin-right: auto;
    font-family: 'DM Mono', monospace;
}
.btn-export-go {
    background: var(--ink);
    color: #fff;
    border: none;
    border-radius: var(--radius);
    padding: 8px 18px;
    font-size: 13px;
    font-weight: 500;
    font-family: 'DM Sans', sans-serif;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: background 0.12s;
    white-space: nowrap;
}
.btn-export-go:hover { background: #27272a; }
.btn-export-go:disabled { opacity: 0.45; cursor: not-allowed; }
</style>
@endpush

{{-- Overlay --}}
<div id="exportOverlay" onclick="closeExportDrawer()"></div>

{{-- Drawer --}}
<div id="exportDrawer">
    <div class="exp-hdr">
        <div class="exp-hdr-left">
            <h2>Export CSV</h2>
            <p>Choose columns to include</p>
        </div>
        <button class="exp-close" onclick="closeExportDrawer()" title="Close">✕</button>
    </div>

    {{-- Active filters summary --}}
    @php
        $activeFilters = array_filter([
            request('search')     ? 'Search: "'.request('search').'"'      : null,
            request('brand')      ? 'Brand: '.request('brand')             : null,
            request('branch')     ? 'Branch filter active'                  : null,
            request('status') && request('status') !== 'all' ? 'Status: '.request('status') : null,
            request('pos_system') ? 'POS: '.request('pos_system')          : null,
            request('date_from')  ? 'From: '.request('date_from')          : null,
            request('date_to')    ? 'To: '.request('date_to')              : null,
        ]);
    @endphp
    <div class="exp-filter-summary {{ empty($activeFilters) ? 'hidden' : '' }}">
        <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
            <path d="M1 2.5h10M3 6h6M5 9.5h2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
        Exports filtered data only · {{ count($activeFilters) }} filter{{ count($activeFilters) !== 1 ? 's' : '' }} active
    </div>

    <div class="exp-body">
        <div class="exp-toggle-all">
            <span id="expSelectedCount">15 of 15 selected</span>
            <button onclick="toggleAllFields()">Select all / None</button>
        </div>

        <div class="exp-section-lbl">Order identifiers</div>

        <label class="exp-field">
            <input type="checkbox" name="exp_field" value="id" checked>
            <div class="exp-field-info">
                <div class="exp-field-name">Internal ID</div>
                <div class="exp-field-hint">id</div>
            </div>
        </label>
        <label class="exp-field">
            <input type="checkbox" name="exp_field" value="keeta_order_id" checked>
            <div class="exp-field-info">
                <div class="exp-field-name">Keeta Order ID</div>
                <div class="exp-field-hint">keeta_order_id</div>
            </div>
        </label>
        <label class="exp-field">
            <input type="checkbox" name="exp_field" value="SDM_order_id" checked>
            <div class="exp-field-info">
                <div class="exp-field-name">SDM / Sara ID</div>
                <div class="exp-field-hint">SDM_order_id</div>
            </div>
        </label>
        <label class="exp-field">
            <input type="checkbox" name="exp_field" value="order_request_id" checked>
            <div class="exp-field-info">
                <div class="exp-field-name">Order Request ID</div>
                <div class="exp-field-hint">order_request_id</div>
            </div>
        </label>

        <label class="exp-field">
            <input type="checkbox" name="exp_field" value="customer_phone" checked>
            <div class="exp-field-info">
                <div class="exp-field-name">Customer Phone</div>
                <div class="exp-field-hint">recipientInfo.phone</div>
            </div>
        </label>

        <div class="exp-section-lbl">Branch &amp; brand</div>

        <label class="exp-field">
            <input type="checkbox" name="exp_field" value="brand_name" checked>
            <div class="exp-field-info">
                <div class="exp-field-name">Brand</div>
                <div class="exp-field-hint">Derived from brand_id / brand_reference_id</div>
            </div>
        </label>
        <label class="exp-field">
            <input type="checkbox" name="exp_field" value="branch_name" checked>
            <div class="exp-field-info">
                <div class="exp-field-name">Branch Name</div>
                <div class="exp-field-hint">branch_name</div>
            </div>
        </label>
        <label class="exp-field">
            <input type="checkbox" name="exp_field" value="shop_id" checked>
            <div class="exp-field-info">
                <div class="exp-field-name">Shop ID</div>
                <div class="exp-field-hint">shop_id</div>
            </div>
        </label>

        <div class="exp-section-lbl">Status &amp; routing</div>

        <label class="exp-field">
            <input type="checkbox" name="exp_field" value="pos_system" checked>
            <div class="exp-field-info">
                <div class="exp-field-name">POS System</div>
                <div class="exp-field-hint">pos_system (SDM / Sara)</div>
            </div>
        </label>
        <label class="exp-field">
            <input type="checkbox" name="exp_field" value="derived_status" checked>
            <div class="exp-field-info">
                <div class="exp-field-name">Derived Status</div>
                <div class="exp-field-hint">Success / Failed / Pending / …</div>
            </div>
        </label>
        <label class="exp-field">
            <input type="checkbox" name="exp_field" value="order_status" checked>
            <div class="exp-field-info">
                <div class="exp-field-name">Sara Order Status</div>
                <div class="exp-field-hint">order_status</div>
            </div>
        </label>

        <div class="exp-section-lbl">Errors &amp; alerts</div>

        <label class="exp-field">
            <input type="checkbox" name="exp_field" value="error_message" checked>
            <div class="exp-field-info">
                <div class="exp-field-name">Error Message</div>
                <div class="exp-field-hint">error_message</div>
            </div>
        </label>
        <label class="exp-field">
            <input type="checkbox" name="exp_field" value="last_failure_email_sent_at" checked>
            <div class="exp-field-info">
                <div class="exp-field-name">Failure Email Sent At</div>
                <div class="exp-field-hint">last_failure_email_sent_at</div>
            </div>
        </label>

        <div class="exp-section-lbl">Timestamps</div>

        <label class="exp-field">
            <input type="checkbox" name="exp_field" value="created_at" checked>
            <div class="exp-field-info">
                <div class="exp-field-name">Received At</div>
                <div class="exp-field-hint">created_at</div>
            </div>
        </label>
        <label class="exp-field">
            <input type="checkbox" name="exp_field" value="updated_at" checked>
            <div class="exp-field-info">
                <div class="exp-field-name">Updated At</div>
                <div class="exp-field-hint">updated_at</div>
            </div>
        </label>
    </div>

    <div class="exp-footer">
        <span class="exp-count" id="expFooterCount">15 fields</span>
        <button class="btn btn-ghost btn-sm" onclick="closeExportDrawer()">Cancel</button>
        <button class="btn-export-go" id="expDownloadBtn" onclick="doExport()">
            <svg width="12" height="12" viewBox="0 0 14 14" fill="none">
                <path d="M7 1v8M3.5 6l3.5 3.5L10.5 6M1.5 11.5h11" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Download CSV
        </button>
    </div>
</div>

@endsection
@push('scripts')
<script>
function refreshPage() {
    fetch(window.location.href)
        .then(r => r.text())
        .then(html => {
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const nb = doc.getElementById('ordersBody');
            const ob = document.getElementById('ordersBody');
            if (nb && ob) {
                const existing = new Set([...ob.querySelectorAll('tr[data-id]')].map(r => r.dataset.id));
                ob.innerHTML = nb.innerHTML;
                ob.querySelectorAll('tr[data-id]').forEach(r => {
                    if (!existing.has(r.dataset.id)) r.classList.add('new-row');
                });
            }
            doc.querySelectorAll('.stat-num').forEach((el, i) => {
                const current = document.querySelectorAll('.stat-num')[i];
                if (current && current.textContent.trim() !== el.textContent.trim()) {
                    current.textContent = el.textContent;
                    current.style.transition = 'opacity 0.3s';
                    current.style.opacity = '0.3';
                    setTimeout(() => current.style.opacity = '1', 300);
                }
            });
        })
        .catch(() => {});
}

setInterval(refreshPage, 6000);

// ── EXPORT DRAWER ──
function openExportDrawer() {
    document.getElementById('exportOverlay').classList.add('open');
    document.getElementById('exportDrawer').classList.add('open');
    document.body.style.overflow = 'hidden';
    updateExpCount();
}

function closeExportDrawer() {
    document.getElementById('exportOverlay').classList.remove('open');
    document.getElementById('exportDrawer').classList.remove('open');
    document.body.style.overflow = '';
}

// Close on Escape
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeExportDrawer();
});

function getChecked() {
    return [...document.querySelectorAll('input[name="exp_field"]:checked')].map(c => c.value);
}

function updateExpCount() {
    const all   = document.querySelectorAll('input[name="exp_field"]').length;
    const checked = getChecked().length;
    document.getElementById('expSelectedCount').textContent = checked + ' of ' + all + ' selected';
    document.getElementById('expFooterCount').textContent   = checked + ' field' + (checked !== 1 ? 's' : '');
    document.getElementById('expDownloadBtn').disabled      = checked === 0;
}

// Listen to every checkbox change
document.querySelectorAll('input[name="exp_field"]').forEach(cb => {
    cb.addEventListener('change', updateExpCount);
});

let _allSelected = true;
function toggleAllFields() {
    _allSelected = !_allSelected;
    document.querySelectorAll('input[name="exp_field"]').forEach(cb => cb.checked = _allSelected);
    updateExpCount();
}

function doExport() {
    const fields  = getChecked();
    if (!fields.length) return;

    // Build URL: carry all current query params + selected fields
    const url = new URL('{{ route("dashboard.orders.export") }}', window.location.origin);

    // Forward every active filter from the current page URL
    const pageParams = new URLSearchParams(window.location.search);
    pageParams.forEach((v, k) => { if (v) url.searchParams.set(k, v); });

    // Append selected field keys
    fields.forEach(f => url.searchParams.append('fields[]', f));

    // Trigger download (no page navigation)
    const a = document.createElement('a');
    a.href = url.toString();
    a.download = '';
    document.body.appendChild(a);
    a.click();
    a.remove();

    closeExportDrawer();
}
</script>
@endpush