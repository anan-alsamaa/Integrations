<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Keeta Ops')</title>
    <meta name="last-order-id" content="@yield('last-order-id', '0')">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:         #f4f3f0;
            --surface:    #ffffff;
            --surface2:   #f9f8f6;
            --border:     #e6e3de;
            --border2:    #ede9e4;
            --ink:        #18181b;
            --ink-2:      #52525b;
            --ink-3:      #a1a1aa;
            --accent:     #dc4a0a;
            --accent-bg:  #fdf1ec;
            --green:      #15803d;
            --green-bg:   #f0fdf4;
            --red:        #b91c1c;
            --red-bg:     #fef2f2;
            --red-row:    #fff5f5;
            --amber:      #b45309;
            --amber-bg:   #fefce8;
            --blue:       #1d4ed8;
            --blue-bg:    #eff6ff;
            --sidebar-w:  220px;
            --radius:     8px;
            --radius-lg:  12px;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--ink);
            min-height: 100vh;
            display: flex;
            font-size: 13.5px;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        /* ── SIDEBAR ── */
        .sidebar {
            width: var(--sidebar-w);
            min-height: 100vh;
            background: var(--ink);
            position: fixed;
            top: 0; left: 0; bottom: 0;
            display: flex;
            flex-direction: column;
            z-index: 100;
        }

        .sidebar-top {
            padding: 20px 16px 16px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 14px;
        }

        .brand-icon {
            width: 30px; height: 30px;
            background: var(--accent);
            border-radius: 7px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
        }

        .brand-name {
            font-size: 14px;
            font-weight: 600;
            color: #fff;
            letter-spacing: -0.01em;
        }

        .brand-sub {
            font-size: 10px;
            color: rgba(255,255,255,0.3);
            letter-spacing: 0.06em;
            text-transform: uppercase;
            margin-top: 1px;
        }

        .live-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 9px;
            background: rgba(21,128,61,0.15);
            border: 1px solid rgba(21,128,61,0.25);
            border-radius: 20px;
        }

        .live-dot {
            width: 6px; height: 6px;
            background: #4ade80;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%,100% { opacity: 1; }
            50%      { opacity: 0.4; }
        }

        .live-pill span {
            font-size: 10px;
            font-weight: 600;
            color: #4ade80;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            font-family: 'DM Mono', monospace;
        }

        .sidebar-nav {
            padding: 12px 10px;
            flex: 1;
        }

        .nav-label {
            font-size: 9.5px;
            font-weight: 600;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.2);
            padding: 0 6px;
            margin-bottom: 4px;
            margin-top: 8px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 7px 8px;
            border-radius: var(--radius);
            font-size: 13px;
            font-weight: 400;
            color: rgba(255,255,255,0.45);
            text-decoration: none;
            transition: background 0.12s, color 0.12s;
            margin-bottom: 1px;
            position: relative;
        }

        .nav-link:hover {
            background: rgba(255,255,255,0.06);
            color: rgba(255,255,255,0.8);
        }

        .nav-link.active {
            background: rgba(220,74,10,0.15);
            color: #ffffff;
            font-weight: 500;
        }

        .nav-link.active .nav-pip { opacity: 1; }

        .nav-pip {
            position: absolute;
            left: 0; top: 25%; bottom: 25%;
            width: 2.5px;
            background: var(--accent);
            border-radius: 0 2px 2px 0;
            opacity: 0;
        }

        .nav-icon { width: 16px; text-align: center; font-size: 12px; opacity: 0.7; }

        .sidebar-footer {
            padding: 12px 10px;
            border-top: 1px solid rgba(255,255,255,0.05);
        }

        .sidebar-credit {
            padding: 4px 8px 10px;
            font-size: 10px;
            color: rgba(255,255,255,0.22);
            font-family: 'DM Mono', monospace;
            letter-spacing: 0.03em;
            line-height: 1.6;
        }

        .sidebar-credit a {
            color: rgba(255,255,255,0.35);
            text-decoration: none;
        }

        .signout-link {
            display: flex;
            align-items: center;
            gap: 7px;
            padding: 7px 8px;
            border-radius: var(--radius);
            font-size: 12px;
            color: rgba(255,255,255,0.25);
            text-decoration: none;
            transition: all 0.12s;
        }

        .signout-link:hover {
            background: rgba(185,28,28,0.12);
            color: #fca5a5;
        }

        /* ── MAIN ── */
        .main {
            margin-left: var(--sidebar-w);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* ── TOPBAR ── */
        .topbar {
            height: 52px;
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .topbar-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--ink);
            letter-spacing: -0.01em;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sound-btn {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 5px 11px;
            border-radius: 20px;
            border: 1px solid var(--border);
            background: transparent;
            font-size: 12px;
            font-weight: 500;
            color: var(--ink-2);
            cursor: pointer;
            font-family: 'DM Sans', sans-serif;
            transition: all 0.12s;
        }

        .sound-btn:hover { border-color: var(--ink-3); color: var(--ink); }
        .sound-btn.muted { color: var(--ink-3); }

        .clock {
            font-family: 'DM Mono', monospace;
            font-size: 12px;
            color: var(--ink-3);
            padding: 5px 11px;
            border-radius: 20px;
            border: 1px solid var(--border);
            background: var(--surface2);
        }

        /* ── TOAST ── */
        #toast {
            position: fixed;
            top: 62px;
            right: 20px;
            z-index: 9999;
            display: none;
        }

        .toast-inner {
            background: var(--surface);
            border: 1px solid var(--border);
            border-left: 3px solid var(--accent);
            border-radius: var(--radius-lg);
            padding: 13px 16px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.10);
            min-width: 280px;
            display: flex;
            align-items: center;
            gap: 11px;
            animation: slideIn 0.3s cubic-bezier(0.16,1,0.3,1);
        }

        @keyframes slideIn {
            from { opacity:0; transform: translateX(16px); }
            to   { opacity:1; transform: translateX(0); }
        }

        .toast-dot {
            width: 8px; height: 8px;
            background: var(--accent);
            border-radius: 50%;
            flex-shrink: 0;
            animation: pulse 1s infinite;
        }

        .toast-title { font-weight: 600; font-size: 13px; }
        .toast-sub   { font-size: 11.5px; color: var(--ink-2); margin-top: 1px; }

        /* ── PAGE ── */
        .page { padding: 24px; flex: 1; }

        /* ── BREADCRUMB ── */
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            color: var(--ink-3);
            margin-bottom: 18px;
        }

        .breadcrumb a { color: var(--ink-3); text-decoration: none; }
        .breadcrumb a:hover { color: var(--ink-2); }
        .breadcrumb .sep { color: var(--border); }

        /* ── STATS ── */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 12px;
            margin-bottom: 22px;
        }

        .stat {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 18px 20px;
            position: relative;
            transition: box-shadow 0.15s;
        }

        .stat:hover { box-shadow: 0 2px 12px rgba(0,0,0,0.07); }

        .stat-num {
            font-family: 'DM Mono', monospace;
            font-size: 30px;
            font-weight: 500;
            line-height: 1;
            margin-bottom: 5px;
        }

        .stat-lbl {
            font-size: 11px;
            font-weight: 500;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: var(--ink-3);
        }

        .stat-accent { border-top: 2px solid var(--border2); }
        .stat-accent.s-total  { border-top-color: var(--ink-3); }
        .stat-accent.s-ok     { border-top-color: var(--green); }
        .stat-accent.s-pend   { border-top-color: var(--amber); }
        .stat-accent.s-wait   { border-top-color: var(--blue); }
        .stat-accent.s-fail   { border-top-color: var(--red); }

        .s-total .stat-num { color: var(--ink); }
        .s-ok    .stat-num { color: var(--green); }
        .s-pend  .stat-num { color: var(--amber); }
        .s-wait  .stat-num { color: var(--blue); }
        .s-fail  .stat-num { color: var(--red); }

        /* ── CARD ── */
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            overflow: hidden;
        }

        /* ── FILTER BAR ── */
        .filters {
            display: flex;
            gap: 8px;
            align-items: center;
            padding: 13px 18px;
            border-bottom: 1px solid var(--border2);
            background: var(--surface2);
            flex-wrap: wrap;
        }

        .filters input,
        .filters select {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            color: var(--ink);
            font-family: 'DM Sans', sans-serif;
            font-size: 13px;
            padding: 6px 11px;
            outline: none;
            transition: border-color 0.12s;
            -webkit-appearance: none;
        }

        .filters input:focus,
        .filters select:focus { border-color: var(--ink-2); }
        .filters input::placeholder { color: var(--ink-3); }
        .filters input[type="date"] { color-scheme: light; }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 14px;
            border-radius: var(--radius);
            font-family: 'DM Sans', sans-serif;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            border: 1px solid transparent;
            text-decoration: none;
            transition: all 0.12s;
            white-space: nowrap;
        }

        .btn-dark { background: var(--ink); color: #fff; }
        .btn-dark:hover { background: #27272a; }
        .btn-ghost { background: transparent; color: var(--ink-2); border-color: var(--border); }
        .btn-ghost:hover { border-color: var(--ink-3); color: var(--ink); }
        .btn-sm { padding: 5px 11px; font-size: 12px; }

        /* ── TABLE ── */
        .tbl { width: 100%; border-collapse: collapse; font-size: 13px; }
        .tbl thead { background: var(--surface2); }

        .tbl th {
            padding: 9px 16px;
            text-align: left;
            font-size: 10.5px;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--ink-3);
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }

        .tbl td {
            padding: 12px 16px;
            border-bottom: 1px solid var(--border2);
            vertical-align: middle;
        }

        .tbl tbody tr:last-child td { border-bottom: none; }
        .tbl tbody tr { transition: background 0.1s; }
        .tbl tbody tr:hover td { background: var(--surface2); }
        .tbl tbody tr.failed-row { background: var(--red-row); }
        .tbl tbody tr.failed-row:hover td { background: #fee2e2; }
        .tbl tbody tr.new-row { animation: flashNew 3s ease-out forwards; }

        @keyframes flashNew {
            0%   { background: rgba(220,74,10,0.07); }
            100% { background: transparent; }
        }

        .mono { font-family: 'DM Mono', monospace; font-size: 12px; }

        /* ── BADGES ── */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            border-radius: 5px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.02em;
            white-space: nowrap;
        }

        .status-success { background: var(--green-bg); color: var(--green); }
        .status-failed  { background: var(--red-bg);   color: var(--red); }
        .status-pending { background: var(--amber-bg); color: var(--amber); }
        .status-waiting { background: var(--blue-bg);  color: var(--blue); }

        /* ── PAGINATION ── */
        .pager {
            padding: 13px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-top: 1px solid var(--border2);
            font-size: 12px;
            color: var(--ink-3);
            background: var(--surface2);
        }

        .pager-links { display: flex; gap: 3px; }

        .pager-links a,
        .pager-links span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px; height: 30px;
            border-radius: var(--radius);
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
            color: var(--ink-2);
            background: var(--surface);
            border: 1px solid var(--border);
            transition: all 0.1s;
        }

        .pager-links a:hover { border-color: var(--ink-3); color: var(--ink); }
        .pager-links span.cur { background: var(--ink); color: #fff; border-color: var(--ink); }

        /* ── DETAIL ── */
        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }

        .card-head {
            padding: 13px 18px;
            border-bottom: 1px solid var(--border2);
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: var(--ink-3);
            display: flex;
            align-items: center;
            gap: 7px;
            background: var(--surface2);
        }

        .kv-table { width: 100%; font-size: 13px; }
        .kv-table tr { border-bottom: 1px solid var(--border2); }
        .kv-table tr:last-child { border-bottom: none; }

        .kv-table td:first-child {
            padding: 9px 18px;
            font-size: 11px;
            font-weight: 500;
            color: var(--ink-3);
            letter-spacing: 0.03em;
            width: 150px;
            vertical-align: top;
        }

        .kv-table td:last-child { padding: 9px 18px; color: var(--ink); font-weight: 400; }

        /* ── ITEMS TABLE ── */
        .items-tbl { width: 100%; border-collapse: collapse; font-size: 13px; }

        .items-tbl th {
            padding: 9px 14px;
            text-align: left;
            font-size: 10.5px;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--ink-3);
            border-bottom: 1px solid var(--border);
            background: var(--surface2);
        }

        .items-tbl td { padding: 10px 14px; border-bottom: 1px solid var(--border2); vertical-align: top; }
        .items-tbl tr:last-child td { border-bottom: none; }

        .addon-row td { background: #fafafa; padding-left: 28px; font-size: 12px; color: var(--ink-2); }
        .addon-row td:first-child::before { content: '↳ '; color: var(--blue); font-weight: 600; }

        /* ── CODE / JSON ── */
        .collapse-hdr {
            padding: 12px 18px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: var(--ink-3);
            user-select: none;
            transition: background 0.1s;
            background: var(--surface2);
            border-bottom: 1px solid var(--border2);
        }

        .collapse-hdr:hover { background: var(--bg); }
        .collapse-body { display: none; }
        .collapse-body.open { display: block; }
        .toggle-chevron { transition: transform 0.18s; font-size: 10px; }
        .collapse-hdr.open .toggle-chevron { transform: rotate(180deg); }

        .json-toolbar {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 9px 14px;
            background: #16181f;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }

        .json-toolbar input {
            flex: 1;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 5px;
            padding: 5px 10px;
            color: #e4e4e7;
            font-family: 'DM Mono', monospace;
            font-size: 12px;
            outline: none;
        }

        .json-toolbar input:focus { border-color: rgba(255,255,255,0.25); }
        .json-toolbar input::placeholder { color: rgba(255,255,255,0.25); }

        .match-info {
            font-family: 'DM Mono', monospace;
            font-size: 11px;
            color: rgba(255,255,255,0.3);
            white-space: nowrap;
            min-width: 70px;
        }

        .json-nav {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 4px;
            color: rgba(255,255,255,0.5);
            padding: 3px 7px;
            cursor: pointer;
            font-size: 11px;
            transition: all 0.1s;
        }

        .json-nav:hover { background: rgba(255,255,255,0.12); color: #fff; }

        .json-block {
            background: #0d0f14;
            padding: 18px;
            font-family: 'DM Mono', monospace;
            font-size: 12px;
            line-height: 1.75;
            color: #9db8d2;
            overflow: auto;
            max-height: 520px;
            white-space: pre;
        }

        mark.hl { background: rgba(245,158,11,0.3); color: #fbbf24; border-radius: 2px; }
        mark.hl.cur { background: rgba(220,74,10,0.5); color: #fff; }

        /* ── ALERT ── */
        .alert {
            padding: 11px 14px;
            border-radius: var(--radius);
            font-size: 13px;
            margin-bottom: 14px;
            display: flex;
            align-items: flex-start;
            gap: 9px;
            font-weight: 400;
        }

        .alert-danger  { background: var(--red-bg);  border: 1px solid #fecaca; color: var(--red); }
        .alert-success { background: var(--green-bg); border: 1px solid #bbf7d0; color: var(--green); }

        /* ── MOBILE ── */
        .menu-toggle {
            display: none;
            align-items: center;
            justify-content: center;
            width: 34px; height: 34px;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            background: transparent;
            cursor: pointer;
            color: var(--ink);
            font-size: 16px;
            flex-shrink: 0;
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.45);
            z-index: 99;
        }

        .sidebar-overlay.open { display: block; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); transition: transform 0.25s cubic-bezier(0.16,1,0.3,1); z-index: 200; }
            .sidebar.open { transform: translateX(0); }
            .main { margin-left: 0; }
            .menu-toggle { display: flex; }
            .stats-row { grid-template-columns: repeat(2, 1fr); }
            .stats-row .stat:last-child { grid-column: span 2; }
            .two-col { grid-template-columns: 1fr; }
        }

        /* ── UTIL ── */
        .muted { color: var(--ink-3); }
        .mb-4  { margin-bottom: 16px; }

        ::-webkit-scrollbar { width: 4px; height: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 2px; }

        .page > * { animation: up 0.3s cubic-bezier(0.16,1,0.3,1) both; }
        .page > *:nth-child(1) { animation-delay: 0.04s; }
        .page > *:nth-child(2) { animation-delay: 0.08s; }
        .page > *:nth-child(3) { animation-delay: 0.12s; }

        @keyframes up {
            from { opacity:0; transform:translateY(10px); }
            to   { opacity:1; transform:translateY(0); }
        }
    </style>
    @stack('head')
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-top">
        <div class="brand">
            <div class="brand-icon">⚡</div>
            <div>
                <div class="brand-name">Keeta</div>
                <div class="brand-sub">Ops Dashboard</div>
            </div>
        </div>
        <div class="live-pill">
            <div class="live-dot"></div>
            <span>Live</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-label">Orders</div>
        <a href="{{ route('dashboard.orders.index') }}"
           class="nav-link {{ request()->routeIs('dashboard.orders.index') && !request()->input('status') ? 'active' : '' }}">
            <div class="nav-pip"></div>
            <span class="nav-icon">▤</span> All Orders
        </a>
        <a href="{{ route('dashboard.orders.index', ['status' => 'failed']) }}"
           class="nav-link {{ request()->input('status') === 'failed' ? 'active' : '' }}">
            <div class="nav-pip"></div>
            <span class="nav-icon">✕</span> Failed
        </a>
        <a href="{{ route('dashboard.orders.index', ['status' => 'pending']) }}"
           class="nav-link {{ request()->input('status') === 'pending' ? 'active' : '' }}">
            <div class="nav-pip"></div>
            <span class="nav-icon">◷</span> Pending
        </a>
        <a href="{{ route('dashboard.orders.index', ['status' => 'sara waiting']) }}"
           class="nav-link {{ request()->input('status') === 'sara waiting' ? 'active' : '' }}">
            <div class="nav-pip"></div>
            <span class="nav-icon">⏱</span> Sara Waiting
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-credit">
            © 2026 Keeta Dashboard<br>
            Developed by <a href="mailto:e.habibi@anan.sa">e.habibi@anan.sa</a>
        </div>
        <a href="{{ route('dashboard.logout') }}" class="signout-link">
            <span>⎋</span> Sign out
        </a>
    </div>
</aside>

<div class="main">
    <header class="topbar">
        <div class="topbar-left" style="display:flex; align-items:center; gap:10px;">
            <button class="menu-toggle" onclick="toggleSidebar()" aria-label="Menu">☰</button>
            <div class="topbar-title">@yield('topbar-title', 'Dashboard')</div>
        </div>
        <div class="topbar-right">
            <button class="sound-btn" id="soundBtn" onclick="toggleSound()">
                <span id="soundIcon">🔔</span>
                <span id="soundLabel">Sound</span>
            </button>
            <div class="clock" id="clock"></div>
        </div>
    </header>

    <div id="toast">
        <div class="toast-inner">
            <div class="toast-dot"></div>
            <div>
                <div class="toast-title">New Order</div>
                <div class="toast-sub" id="toast-msg">—</div>
            </div>
        </div>
    </div>

    <div class="page">
        @if(session('success'))
            <div class="alert alert-success">✓ {{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">✕ {{ session('error') }}</div>
        @endif
        @yield('content')
    </div>
</div>

<script>
    (function tick() {
        document.getElementById('clock').textContent =
            new Date().toLocaleTimeString('en-GB', {hour:'2-digit',minute:'2-digit',second:'2-digit'});
        setTimeout(tick, 1000);
    })();

    document.querySelectorAll('.collapse-hdr').forEach(h => {
        h.addEventListener('click', () => {
            h.classList.toggle('open');
            h.nextElementSibling.classList.toggle('open');
        });
    });

    let muted = localStorage.getItem('k_muted') === '1';
    syncSoundUI();

    function toggleSound() { muted = !muted; localStorage.setItem('k_muted', muted ? '1' : '0'); syncSoundUI(); }

    function syncSoundUI() {
        document.getElementById('soundIcon').textContent  = muted ? '🔕' : '🔔';
        document.getElementById('soundLabel').textContent = muted ? 'Muted' : 'Sound';
        document.getElementById('soundBtn').classList.toggle('muted', muted);
    }

    function chime() {
        if (muted) return;
        try {
            const ac = new (window.AudioContext || window.webkitAudioContext)();
            [[440,0],[554,0.13],[659,0.26]].forEach(([f,t]) => {
                const o = ac.createOscillator(), g = ac.createGain();
                o.connect(g); g.connect(ac.destination);
                o.type = 'sine'; o.frequency.value = f;
                g.gain.setValueAtTime(0, ac.currentTime + t);
                g.gain.linearRampToValueAtTime(0.15, ac.currentTime + t + 0.02);
                g.gain.exponentialRampToValueAtTime(0.001, ac.currentTime + t + 0.38);
                o.start(ac.currentTime + t); o.stop(ac.currentTime + t + 0.4);
            });
        } catch(e) {}
    }

    function showToast(msg) {
        document.getElementById('toast-msg').textContent = msg;
        const el = document.getElementById('toast');
        el.style.display = 'block';
        clearTimeout(el._t);
        el._t = setTimeout(() => el.style.display = 'none', 5000);
    }

    let lastId = parseInt(document.querySelector('meta[name="last-order-id"]').content) || 0;
    let pollReady = lastId > 0;

    function poll() {
        fetch('{{ route("dashboard.orders.poll") }}?after=' + lastId)
            .then(r => r.json())
            .then(d => {
                if (!pollReady) {
                    lastId    = d.latest_id || 0;
                    pollReady = true;
                    return;
                }
                if (d.new_orders && d.new_orders.length) {
                    d.new_orders.forEach(o => {
                        chime();
                        showToast(o.keeta_order_id + ' · ' + (o.branch_name || 'Unknown branch'));
                    });
                    lastId = d.latest_id;
                    if (typeof refreshPage === 'function') refreshPage();
                }
            })
            .catch(() => {});
    }

    setInterval(poll, 6000);

    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('open');
        document.getElementById('sidebarOverlay').classList.toggle('open');
    }

    function closeSidebar() {
        document.getElementById('sidebar').classList.remove('open');
        document.getElementById('sidebarOverlay').classList.remove('open');
    }

    document.querySelectorAll('.nav-link').forEach(l => {
        l.addEventListener('click', closeSidebar);
    });
</script>
@stack('scripts')
</body>
</html>