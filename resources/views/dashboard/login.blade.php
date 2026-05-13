<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keeta Dashboard — Sign In</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --cream:    #faf8f4;
            --white:    #ffffff;
            --ink:      #1a1a2e;
            --ink-soft: #4a4a6a;
            --accent:   #e8500a;
            --accent2:  #f5a623;
            --border:   #e8e4dc;
            --shadow:   rgba(26,26,46,0.08);
        }

        body {
            font-family: 'Sora', sans-serif;
            background: var(--cream);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        /* Geometric background pattern */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                radial-gradient(circle at 20% 20%, rgba(232,80,10,0.06) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(245,166,35,0.06) 0%, transparent 50%),
                radial-gradient(circle at 50% 50%, rgba(26,26,46,0.02) 0%, transparent 70%);
            pointer-events: none;
        }

        /* Floating grid lines */
        body::after {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(26,26,46,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(26,26,46,0.03) 1px, transparent 1px);
            background-size: 60px 60px;
            pointer-events: none;
        }

        .login-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 52px 48px;
            width: 420px;
            box-shadow:
                0 4px 6px var(--shadow),
                0 20px 60px rgba(26,26,46,0.10),
                0 0 0 1px rgba(255,255,255,0.8) inset;
            position: relative;
            z-index: 1;
            animation: cardIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        @keyframes cardIn {
            from { opacity: 0; transform: translateY(24px) scale(0.97); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        .login-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 36px;
        }

        .login-logo-mark {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            box-shadow: 0 4px 12px rgba(232,80,10,0.3);
        }

        .login-logo-text {
            display: flex;
            flex-direction: column;
        }

        .login-logo-name {
            font-size: 17px;
            font-weight: 700;
            color: var(--ink);
            letter-spacing: -0.02em;
            line-height: 1;
        }

        .login-logo-sub {
            font-size: 11px;
            color: var(--ink-soft);
            font-weight: 400;
            margin-top: 2px;
        }

        .login-title {
            font-size: 26px;
            font-weight: 700;
            color: var(--ink);
            letter-spacing: -0.03em;
            margin-bottom: 6px;
        }

        .login-subtitle {
            font-size: 13px;
            color: var(--ink-soft);
            margin-bottom: 32px;
            line-height: 1.5;
        }

        .field {
            margin-bottom: 18px;
        }

        .field label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--ink);
            letter-spacing: 0.04em;
            text-transform: uppercase;
            margin-bottom: 7px;
        }

        .field input {
            width: 100%;
            padding: 12px 16px;
            border: 1.5px solid var(--border);
            border-radius: 10px;
            font-family: 'Sora', sans-serif;
            font-size: 14px;
            color: var(--ink);
            background: var(--cream);
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
        }

        .field input:focus {
            border-color: var(--accent);
            background: var(--white);
            box-shadow: 0 0 0 3px rgba(232,80,10,0.10);
        }

        .field input::placeholder { color: #b0aaa0; }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--accent) 0%, #c73d00 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-family: 'Sora', sans-serif;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 8px;
            letter-spacing: 0.01em;
            transition: transform 0.15s, box-shadow 0.15s;
            box-shadow: 0 4px 14px rgba(232,80,10,0.35);
            position: relative;
            overflow: hidden;
        }

        .btn-login::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.15) 0%, transparent 60%);
        }

        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(232,80,10,0.45);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .error-msg {
            background: #fff1ee;
            border: 1px solid #fcd0c3;
            color: #c0330d;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .login-footer {
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
            font-size: 11px;
            color: #b0aaa0;
            text-align: center;
            font-family: 'JetBrains Mono', monospace;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-logo">
            <div class="login-logo-mark">⚡</div>
            <div class="login-logo-text">
                <span class="login-logo-name">Keeta</span>
                <span class="login-logo-sub">Integration Dashboard</span>
            </div>
        </div>

        <h1 class="login-title">Welcome back</h1>
        <p class="login-subtitle">Sign in to access the operations dashboard.</p>

        @if(session('error'))
            <div class="error-msg">✕ {{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('dashboard.login.post') }}">
            @csrf
            <div class="field">
                <label>Username</label>
                <input type="text" name="username" placeholder="Enter username" autocomplete="off" autofocus>
            </div>
            <div class="field">
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter password" autocomplete="off">
            </div>
            <button type="submit" class="btn-login">Sign In →</button>
        </form>

        <div class="login-footer">KEETA OPS v2.0 · INTERNAL USE ONLY</div>
    </div>
</body>
</html>