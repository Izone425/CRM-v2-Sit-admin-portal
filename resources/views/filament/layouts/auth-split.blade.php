<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ filament()->getBrandName() }} — Sign In</title>
    @filamentStyles
    <link href="https://fonts.bunny.net/css?family=poppins:300,400,500,600,700,800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --brand-blue: #2eaadc;
            --brand-blue-dark: #1f8fc0;
            --brand-blue-light: #6fc8e8;
            --ink: #0a2540;
            --ink-soft: #46607a;
            --muted: #7a8fa5;
            --card: #ffffff;
            --line: #e3edf3;
        }

        html, body { min-height: 100vh; }

        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
            color: var(--ink);
            background:
                radial-gradient(120% 90% at 0% 100%, #b6e7c9 0%, transparent 55%),
                radial-gradient(110% 100% at 100% 100%, #2eaadc 0%, #7fd0ea 35%, transparent 70%),
                linear-gradient(135deg, #eaf7f1 0%, #d6f0e3 25%, #b9e3d8 50%, #7fc8e0 80%, #2eaadc 100%);
        }

        /* decorative flowing lines on the lower half */
        .bg-waves {
            position: fixed;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            overflow: hidden;
        }
        .bg-waves svg {
            position: absolute;
            bottom: -10%;
            left: -5%;
            width: 110%;
            height: 80%;
            opacity: 0.55;
        }

        /* ── Layout ── */
        .auth-shell {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1fr 1fr;
            align-items: center;
            gap: 40px;
            padding: 40px 6vw;
        }

        /* ── Left side ── */
        .auth-hero {
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 28px;
            max-width: 520px;
            margin-left: auto;
            margin-right: 0;
        }
        .auth-hero-logo {
            display: flex;
            align-items: center;
            gap: 0;
        }
        .auth-hero-logo img {
            height: 76px;
            width: auto;
            object-fit: contain;
        }
        .auth-hero h1 {
            font-size: clamp(2.4rem, 4.4vw, 3.6rem);
            font-weight: 800;
            color: var(--ink);
            letter-spacing: -1px;
            line-height: 1.05;
        }
        .auth-hero h1 .accent {
            display: block;
        }
        .auth-hero p {
            font-size: 1rem;
            color: var(--ink-soft);
            line-height: 1.6;
            max-width: 420px;
            font-weight: 400;
        }

        /* ── Right side card ── */
        .auth-card-wrap {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 18px;
            justify-self: start;
            width: 100%;
            max-width: 460px;
        }
        .auth-card {
            width: 100%;
            background: var(--card);
            border-radius: 22px;
            padding: 40px 38px 32px;
            box-shadow:
                0 30px 70px rgba(11, 67, 99, 0.18),
                0 6px 18px rgba(11, 67, 99, 0.08);
        }

        .auth-title {
            text-align: center;
            font-size: 1.7rem;
            font-weight: 800;
            color: var(--ink);
            letter-spacing: -0.3px;
            margin-bottom: 6px;
        }
        .auth-sub {
            text-align: center;
            font-size: 0.85rem;
            color: var(--muted);
            margin-bottom: 22px;
            line-height: 1.5;
        }

        /* tabs */
        .auth-tabs {
            display: flex;
            justify-content: center;
            gap: 28px;
            margin-bottom: 26px;
            border-bottom: 1px solid var(--line);
        }
        .auth-tab {
            font-size: 0.92rem;
            font-weight: 600;
            color: var(--muted);
            padding: 10px 4px;
            position: relative;
            cursor: pointer;
            background: none;
            border: none;
            font-family: inherit;
        }
        .auth-tab.active {
            color: var(--brand-blue);
        }
        .auth-tab.active::after {
            content: '';
            position: absolute;
            left: 0; right: 0; bottom: -1px;
            height: 2px;
            background: var(--brand-blue);
            border-radius: 2px;
        }
        .auth-tab-sep {
            color: var(--line);
            align-self: center;
            font-weight: 300;
        }

        /* ── Filament input overrides ── */
        .form-slot .fi-fo-field-wrp { margin-bottom: 16px; }
        .form-slot .fi-fo-text-input,
        .form-slot .fi-input-wrapper,
        .form-slot [class*="fi-fo-text-input"] { overflow: visible !important; }

        .form-slot label {
            font-size: 0.8rem !important;
            font-weight: 600 !important;
            color: var(--ink) !important;
            letter-spacing: 0 !important;
            margin-bottom: 6px !important;
            display: block !important;
        }

        .form-slot .fi-input-wrp {
            position: relative !important;
            display: flex !important;
            align-items: center !important;
            width: 100% !important;
            border-radius: 12px !important;
            border: 1px solid var(--line) !important;
            background: #ffffff !important;
            box-shadow: none !important;
            overflow: visible !important;
            transition: border-color 0.2s, box-shadow 0.2s !important;
        }
        .form-slot .fi-input-wrp-input { flex: 1 1 auto !important; min-width: 0 !important; width: 100% !important; }

        .form-slot .fi-input-wrp:focus-within {
            border-color: var(--brand-blue) !important;
            box-shadow: 0 0 0 3px rgba(46, 170, 220, 0.15) !important;
        }

        .form-slot input[type="email"],
        .form-slot input[type="text"],
        .form-slot input[type="password"] {
            flex: 1 !important;
            border: none !important;
            background: transparent !important;
            font-family: 'Poppins', sans-serif !important;
            font-size: 0.9rem !important;
            padding: 13px 14px !important;
            outline: none !important;
            color: var(--ink) !important;
            box-shadow: none !important;
            min-width: 0 !important;
            width: 100% !important;
        }
        .form-slot input[type="password"] {
            padding-right: 44px !important;
        }
        .form-slot input::placeholder {
            color: #b6c2cf !important;
        }

        /* autofill fix */
        .form-slot input:-webkit-autofill,
        .form-slot input:-webkit-autofill:hover,
        .form-slot input:-webkit-autofill:focus,
        .form-slot input:-webkit-autofill:active {
            -webkit-box-shadow: 0 0 0 999px #ffffff inset !important;
            box-shadow: 0 0 0 999px #ffffff inset !important;
            -webkit-text-fill-color: var(--ink) !important;
            caret-color: var(--ink) !important;
            transition: background-color 9999s ease-in-out 0s !important;
        }

        /* eye toggle button */
        .form-slot .fi-input-wrp button[type="button"] {
            position: absolute !important;
            right: 12px !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            width: 22px !important;
            height: 22px !important;
            padding: 0 !important;
            border: none !important;
            background: transparent !important;
            color: var(--muted) !important;
            cursor: pointer !important;
            align-items: center !important;
            justify-content: center !important;
            flex-shrink: 0 !important;
            line-height: 1 !important;
        }
        .form-slot .fi-input-wrp button[type="button"]:not([style*="display: none"]):not([style*="display:none"]) {
            display: flex !important;
        }
        .form-slot .fi-input-wrp button[type="button"]:hover { color: var(--brand-blue) !important; }
        .form-slot .fi-input-wrp button[type="button"] svg {
            width: 18px !important;
            height: 18px !important;
            stroke: currentColor !important;
            color: inherit !important;
            flex-shrink: 0 !important;
            pointer-events: none !important;
        }
        .form-slot [class*="fi-input"]:not(.fi-input-wrp) {
            border: none !important;
            box-shadow: none !important;
            background: transparent !important;
        }

        /* row for remember + forgot */
        .form-slot .fi-fo-checkbox,
        .form-slot label:has(input[type="checkbox"]) {
            font-size: 0.82rem !important;
            color: var(--ink-soft) !important;
            font-weight: 500 !important;
        }
        .form-slot input[type="checkbox"] {
            accent-color: var(--brand-blue);
            width: 16px; height: 16px;
        }

        /* submit button */
        .form-slot .fi-btn-primary,
        .form-slot button[type="submit"] {
            background: linear-gradient(135deg, var(--brand-blue) 0%, var(--brand-blue-light) 100%) !important;
            border: none !important;
            border-radius: 999px !important;
            font-family: 'Poppins', sans-serif !important;
            font-size: 0.95rem !important;
            font-weight: 600 !important;
            padding: 14px 20px !important;
            letter-spacing: 0.2px !important;
            cursor: pointer !important;
            width: 100% !important;
            margin-top: 14px;
            color: #fff !important;
            box-shadow:
                0 10px 24px rgba(46, 170, 220, 0.35),
                0 1px 0 rgba(255, 255, 255, 0.3) inset !important;
            transition: opacity 0.2s, transform 0.15s, box-shadow 0.2s !important;
            position: relative !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 8px !important;
        }
        .form-slot .fi-btn-primary .fi-btn-label,
        .form-slot button[type="submit"] .fi-btn-label,
        .form-slot .fi-btn-primary span,
        .form-slot button[type="submit"] > span:not(.fi-btn-loading-indicator) {
            color: #fff !important;
            font-weight: 600 !important;
            font-size: 0.95rem !important;
            display: inline !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
        .form-slot .fi-btn-primary:hover,
        .form-slot button[type="submit"]:hover {
            transform: translateY(-1px) !important;
            box-shadow:
                0 14px 30px rgba(46, 170, 220, 0.45),
                0 1px 0 rgba(255, 255, 255, 0.3) inset !important;
        }
        .form-slot .fi-btn-primary:active,
        .form-slot button[type="submit"]:active { transform: translateY(0) !important; }

        .form-slot .fi-btn-loading-indicator { display: none !important; }
        [wire\:loading\.class] { opacity: 1 !important; }
        [x-cloak] { display: none !important; }

        /* links (forgot password) */
        .form-slot a {
            color: var(--brand-blue) !important;
            font-weight: 600 !important;
            font-size: 0.82rem !important;
            text-decoration: none !important;
        }
        .form-slot a:hover { color: var(--brand-blue-dark) !important; }

        .auth-footer {
            position: fixed;
            right: 28px;
            bottom: 18px;
            font-size: 0.78rem;
            color: rgba(255, 255, 255, 0.85);
            text-align: right;
            z-index: 2;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        /* ══ Splash ══ */
        #splash {
            position: fixed; inset: 0; z-index: 9999;
            background: linear-gradient(135deg, #eaf7f1 0%, #b9e3d8 50%, #2eaadc 100%);
            display: flex; flex-direction: column;
            align-items: center; justify-content: center; gap: 24px;
            transition: opacity 0.5s ease, visibility 0.5s ease;
            opacity: 0; visibility: hidden; pointer-events: none;
        }
        #splash.show { opacity: 1; visibility: visible; pointer-events: auto; }
        .splash-logo-wrap { position: relative; animation: popIn 0.6s cubic-bezier(0.34,1.56,0.64,1) both; }
        .splash-logo-box {
            width: 110px; height: 110px;
            background: rgba(255,255,255,0.6);
            border: 1px solid rgba(255,255,255,0.8);
            border-radius: 24px; padding: 14px;
            display: flex; align-items: center; justify-content: center;
            backdrop-filter: blur(10px);
            box-shadow: 0 12px 30px rgba(11, 67, 99, 0.15);
        }
        .splash-logo-box img { width: 100%; height: 100%; object-fit: contain; }
        @keyframes popIn { from { transform: scale(0.5); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        .splash-text { text-align: center; animation: fadeUp 0.5s 0.2s ease both; }
        .splash-title { font-size: 1.5rem; font-weight: 700; color: var(--ink); }
        .splash-sub { font-size: 0.8rem; color: var(--ink-soft); margin-top: 4px; }
        @keyframes fadeUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .splash-track { width: 180px; height: 3px; background: rgba(11, 67, 99, 0.12); border-radius: 99px; overflow: hidden; animation: fadeUp 0.5s 0.3s ease both; }
        .splash-fill { height: 100%; background: linear-gradient(90deg, var(--brand-blue), var(--brand-blue-light)); border-radius: 99px; width: 0; animation: fillBar 1.2s 0.5s cubic-bezier(0.4,0,0.2,1) forwards; }
        @keyframes fillBar { to { width: 80%; } }
        .splash-fill.indeterminate { animation: barPulse 1.4s ease-in-out infinite !important; }
        @keyframes barPulse { 0% { width: 80%; opacity: 1; } 50% { width: 92%; opacity: 0.75; } 100% { width: 80%; opacity: 1; } }

        /* ── Responsive ── */
        @media (max-width: 960px) {
            .auth-shell {
                grid-template-columns: 1fr;
                padding: 40px 24px 80px;
                gap: 32px;
            }
            .auth-hero {
                margin: 0 auto;
                text-align: center;
                align-items: center;
            }
            .auth-hero p { margin: 0 auto; }
            .auth-card-wrap { margin: 0 auto; justify-self: center; }
            .auth-footer {
                position: static;
                margin-top: 24px;
                text-align: center;
                color: var(--ink-soft);
                text-shadow: none;
            }
        }
        @media (max-width: 480px) {
            .auth-card { padding: 28px 22px 24px; border-radius: 18px; }
            .auth-title { font-size: 1.4rem; }
            .auth-hero-logo img { height: 60px; }
        }
    </style>
</head>
<body>

    {{-- Background waves --}}
    <div class="bg-waves" aria-hidden="true">
        <svg viewBox="0 0 1200 600" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
            <g fill="none" stroke="rgba(255,255,255,0.55)" stroke-width="1.2">
                <path d="M-50,520 C200,420 450,560 700,460 C900,380 1050,500 1250,420" />
                <path d="M-50,540 C220,440 460,580 720,480 C920,400 1070,520 1250,440" />
                <path d="M-50,560 C240,460 470,600 740,500 C940,420 1090,540 1250,460" />
                <path d="M-50,580 C260,480 480,620 760,520 C960,440 1110,560 1250,480" />
                <path d="M-50,600 C280,500 490,640 780,540 C980,460 1130,580 1250,500" />
            </g>
            <g fill="none" stroke="rgba(255,255,255,0.35)" stroke-width="1">
                <path d="M-50,420 C200,340 480,460 720,380 C920,320 1080,420 1250,360" />
                <path d="M-50,460 C220,380 490,500 740,420 C940,360 1100,460 1250,400" />
            </g>
        </svg>
    </div>

    {{-- Splash --}}
    <div id="splash">
        <div class="splash-logo-wrap">
            <div class="splash-logo-box">
                <img src="{{ asset('img/logo-ttc.png') }}" alt="TimeTec" />
            </div>
        </div>
        <div class="splash-text">
            <div class="splash-title">TimeTec CRM</div>
            <div class="splash-sub">Sales & Operations Command Center</div>
        </div>
        <div class="splash-track">
            <div class="splash-fill"></div>
        </div>
    </div>

    {{-- Main layout --}}
    <div class="auth-shell">
        {{-- Left: Hero --}}
        <div class="auth-hero">
            <div class="auth-hero-logo">
                <img src="{{ asset('img/logo-ttc.png') }}" alt="TimeTec HR" />
            </div>
            <h1>
                Hello,
                <span class="accent">welcome!</span>
            </h1>
            <p>Streamline your CRM workflow with TimeTec. Access leads, quotations, handovers, and more in one place.</p>
        </div>

        {{-- Right: Sign In Card --}}
        <div class="auth-card-wrap">
            <div class="auth-card">
                <h1 class="auth-title">Sign In</h1>
                <p class="auth-sub">Welcome back! Please enter your credentials.</p>

                <div class="form-slot">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>

    <div class="auth-footer">
        &copy; {{ date('Y') }} TimeTec Computing Sdn Bhd
    </div>

    @livewire('notifications')
    @filamentScripts

    <script>
        document.addEventListener('livewire:initialized', function () {
            var splash = document.getElementById('splash');
            var splashFill = splash ? splash.querySelector('.splash-fill') : null;
            var fillTimer = null;

            function showSplash() {
                if (!splash) return;
                if (splashFill) {
                    splashFill.classList.remove('indeterminate');
                    splashFill.style.animation = 'none';
                    splashFill.offsetHeight;
                    splashFill.style.animation = '';
                    clearTimeout(fillTimer);
                    fillTimer = setTimeout(function () {
                        if (splash.classList.contains('show')) {
                            splashFill.classList.add('indeterminate');
                        }
                    }, 1700);
                }
                splash.classList.add('show');
            }

            function hideSplash() {
                clearTimeout(fillTimer);
                if (splashFill) splashFill.classList.remove('indeterminate');
                if (splash) splash.classList.remove('show');
            }

            document.addEventListener('submit', function () { showSplash(); });

            Livewire.hook('commit', ({ succeed, fail }) => {
                succeed(({ effect }) => {
                    if (!effect.redirect) setTimeout(hideSplash, 150);
                });
                fail(() => hideSplash());
            });
        });
    </script>
</body>
</html>
