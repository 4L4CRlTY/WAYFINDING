<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Smart Campus Navigation System</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800" rel="stylesheet" />

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --green: #0c7a43;
            --green-dark: #075f35;
            --blue: #173b8f;
            --text: #183126;
            --muted: #61776d;
            --bg: #f5fbf8;
            --white: #ffffff;
            --line: rgba(11, 122, 67, 0.12);
            --shadow: 0 18px 50px rgba(8, 45, 28, 0.10);
            --shadow-soft: 0 10px 25px rgba(8, 45, 28, 0.06);
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: "Instrument Sans", ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at top left, rgba(23, 59, 143, 0.08), transparent 25%),
                radial-gradient(circle at bottom right, rgba(12, 122, 67, 0.10), transparent 30%),
                linear-gradient(180deg, #f8fcfa 0%, #f3faf6 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }

        img {
            max-width: 100%;
            display: block;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .container {
            width: min(1200px, calc(100% - 2rem));
            margin: 0 auto;
        }

        .navbar {
            position: sticky;
            top: 0;
            z-index: 50;
            backdrop-filter: blur(14px);
            background: rgba(245, 251, 248, 0.88);
            border-bottom: 1px solid rgba(11, 122, 67, 0.08);
        }

        .nav-inner {
            min-height: 84px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 0.9rem;
        }

        .brand-logo {
            width: 58px;
            height: 58px;
            object-fit: cover;
            border-radius: 50%;
            background: #fff;
            border: 3px solid rgba(255,255,255,0.95);
            box-shadow: 0 12px 30px rgba(0, 50, 110, 0.16);
        }

        .brand-text h1 {
            font-size: clamp(1rem, 2vw, 1.22rem);
            line-height: 1.05;
            font-weight: 800;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            color: var(--blue);
        }

        .brand-text span {
            display: block;
            margin-top: 0.25rem;
            font-size: 0.78rem;
            letter-spacing: 0.17em;
            text-transform: uppercase;
            font-weight: 800;
            color: var(--green-dark);
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            min-height: 48px;
            padding: 0 1.2rem;
            border-radius: 999px;
            font-size: 0.95rem;
            font-weight: 800;
            transition: 0.22s ease;
            border: none;
            cursor: pointer;
            white-space: nowrap;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-primary {
            color: #fff;
            background: linear-gradient(135deg, var(--green-dark), var(--green));
            box-shadow: 0 14px 32px rgba(12, 122, 67, 0.25);
        }

        .btn-outline {
            color: var(--green-dark);
            background: rgba(255,255,255,0.9);
            border: 1px solid rgba(11, 122, 67, 0.14);
            box-shadow: var(--shadow-soft);
        }

        .hero {
            padding: 3.75rem 0 3rem;
        }

        .hero-grid {
            display: grid;
            grid-template-columns: 1.03fr 0.97fr;
            align-items: center;
            gap: 3rem;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            background: rgba(255,255,255,0.86);
            border: 1px solid var(--line);
            color: var(--green-dark);
            box-shadow: var(--shadow-soft);
            border-radius: 999px;
            padding: 0.75rem 1rem;
            font-size: 0.84rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            font-weight: 800;
        }

        .badge-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #18b764;
            box-shadow: 0 0 0 6px rgba(24, 183, 100, 0.16);
        }

        .hero-title {
            margin-top: 1.2rem;
            font-size: clamp(2.8rem, 6vw, 5.8rem);
            line-height: 0.95;
            letter-spacing: -0.06em;
            font-weight: 800;
            color: var(--green-dark);
            max-width: 680px;
        }

        .hero-title span {
            color: var(--green);
        }

        .hero-text {
            margin-top: 1.2rem;
            max-width: 620px;
            font-size: 1.06rem;
            line-height: 1.8;
            color: var(--muted);
        }

        .hero-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 0.85rem;
            margin-top: 1.8rem;
        }

        .hero-stats {
            margin-top: 2rem;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
            max-width: 680px;
        }

        .stat-card {
            background: rgba(255,255,255,0.86);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 1rem;
            box-shadow: var(--shadow-soft);
        }

        .stat-card strong {
            display: block;
            color: var(--green-dark);
            font-size: 1.1rem;
            font-weight: 800;
        }

        .stat-card span {
            display: block;
            margin-top: 0.3rem;
            color: var(--muted);
            font-size: 0.9rem;
            line-height: 1.45;
        }

        .hero-visual {
            position: relative;
        }

        .glow {
            position: absolute;
            inset: 24px -15px -15px 35px;
            border-radius: 32px;
            background: linear-gradient(135deg, rgba(23, 59, 143, 0.12), rgba(12, 122, 67, 0.18));
            filter: blur(22px);
            z-index: 0;
        }

        .image-shell {
            position: relative;
            z-index: 1;
            background: rgba(255,255,255,0.78);
            border: 1px solid rgba(255,255,255,0.72);
            border-radius: 30px;
            padding: 1rem;
            backdrop-filter: blur(14px);
            box-shadow: var(--shadow);
        }

        .image-shell img {
            width: 100%;
            border-radius: 22px;
        }

        .floating-card {
            position: absolute;
            z-index: 2;
            background: rgba(255,255,255,0.95);
            border: 1px solid rgba(11, 122, 67, 0.10);
            box-shadow: 0 16px 35px rgba(0,0,0,0.09);
            border-radius: 18px;
            padding: 0.9rem 1rem;
            max-width: 220px;
        }

        .floating-card strong {
            display: block;
            color: var(--green-dark);
            font-size: 0.95rem;
            font-weight: 800;
        }

        .floating-card span {
            display: block;
            margin-top: 0.28rem;
            color: var(--muted);
            font-size: 0.84rem;
            line-height: 1.45;
        }

        .card-top {
            top: -18px;
            left: -18px;
        }

        .card-bottom {
            right: -18px;
            bottom: 20px;
        }

        .section {
            padding: 2rem 0 4.5rem;
        }

        .section-head {
            text-align: center;
            margin-bottom: 2rem;
        }

        .section-head h2 {
            font-size: clamp(1.9rem, 4vw, 2.8rem);
            color: var(--green-dark);
            font-weight: 800;
            letter-spacing: -0.04em;
        }

        .section-head p {
            max-width: 680px;
            margin: 0.75rem auto 0;
            color: var(--muted);
            line-height: 1.75;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 1rem;
        }

        .feature-card {
            background: rgba(255,255,255,0.9);
            border: 1px solid var(--line);
            border-radius: 22px;
            padding: 1.35rem 1rem;
            text-align: center;
            box-shadow: var(--shadow-soft);
            transition: 0.25s ease;
        }

        .feature-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 40px rgba(8, 45, 28, 0.10);
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 1rem;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: rgba(12, 122, 67, 0.10);
            color: var(--green-dark);
            font-size: 1.5rem;
        }

        .feature-card h3 {
            color: var(--green-dark);
            font-size: 1rem;
            font-weight: 800;
            line-height: 1.35;
        }

        .feature-card p {
            margin-top: 0.55rem;
            color: var(--muted);
            font-size: 0.9rem;
            line-height: 1.6;
        }

        .cta {
            padding-bottom: 4.5rem;
        }

        .cta-box {
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #0a6d3c, #0f7d47 45%, #123d92 100%);
            border-radius: 32px;
            color: #fff;
            padding: 2.2rem;
            box-shadow: 0 22px 55px rgba(10, 60, 38, 0.18);
        }

        .cta-box::before {
            content: "";
            position: absolute;
            right: -60px;
            top: -60px;
            width: 220px;
            height: 220px;
            border-radius: 50%;
            background: rgba(255,255,255,0.08);
        }

        .cta-box::after {
            content: "";
            position: absolute;
            left: -40px;
            bottom: -80px;
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: rgba(255,255,255,0.06);
        }

        .cta-inner {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
            flex-wrap: wrap;
        }

        .cta-text h3 {
            font-size: clamp(1.7rem, 4vw, 2.4rem);
            font-weight: 800;
            letter-spacing: -0.04em;
        }

        .cta-text p {
            margin-top: 0.65rem;
            max-width: 640px;
            line-height: 1.7;
            color: rgba(255,255,255,0.88);
        }

        .cta-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.8rem;
        }

        .btn-light {
            background: #fff;
            color: var(--green-dark);
            box-shadow: 0 12px 24px rgba(0,0,0,0.10);
        }

        .btn-ghost {
            background: rgba(255,255,255,0.12);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.18);
        }

        .footer {
            padding: 1.2rem 0 2rem;
            color: var(--muted);
            font-size: 0.92rem;
        }

        .footer-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
            border-top: 1px solid rgba(11, 122, 67, 0.08);
            padding-top: 1.4rem;
        }

        .footer-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 700;
            color: var(--green-dark);
        }

        .footer-brand img {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            object-fit: cover;
        }

        @media (max-width: 1100px) {
            .hero-grid {
                grid-template-columns: 1fr;
            }

            .hero-text,
            .hero-title {
                max-width: 100%;
            }

            .features-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 768px) {
            .nav-inner {
                min-height: auto;
                padding: 1rem 0;
                flex-direction: column;
                align-items: flex-start;
            }

            .nav-actions {
                width: 100%;
                justify-content: flex-start;
                flex-wrap: wrap;
            }

            .hero {
                padding-top: 2.5rem;
            }

            .hero-buttons {
                flex-direction: column;
                align-items: stretch;
            }

            .hero-buttons .btn,
            .cta-actions .btn {
                width: 100%;
            }

            .hero-stats {
                grid-template-columns: 1fr;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .floating-card {
                position: static;
                max-width: 100%;
                margin-top: 1rem;
            }

            .card-top,
            .card-bottom {
                top: auto;
                left: auto;
                right: auto;
                bottom: auto;
            }

            .cta-box {
                padding: 1.6rem;
            }
        }
    </style>
    <link rel="stylesheet" href="{{ asset('css/futuristic-public.css') }}?v={{ filemtime(public_path('css/futuristic-public.css')) }}">
</head>
<body class="public-future public-welcome">
    @php
        $dashboardRoute = null;

        if (auth()->check()) {
            $dashboardRoute = auth()->user()->role === 'admin'
                ? route('admin.dashboard')
                : route('user.dashboard');
        }
    @endphp

    <header class="navbar">
        <div class="container nav-inner">
            <a href="/" class="brand">
                <img
                    src="{{ asset('background/slsu-logo.jpg') }}"
                    alt="Southern Leyte State University Logo"
                    class="brand-logo"
                >

                <div class="brand-text">
                    <h1>Southern Leyte<br>State University</h1>
                    <span>Tomas Oppus Campus</span>
                </div>
            </a>

            @if (Route::has('login'))
                <nav class="nav-actions">
                    @auth
                        <a href="{{ $dashboardRoute }}" class="btn btn-primary">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-outline">Log in</a>

                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="btn btn-primary">Register</a>
                        @endif

                        <a href="{{ route('guest.dashboard') }}" class="btn btn-guest">Guest</a>
                    @endauth
                </nav>
            @endif
        </div>
    </header>

    <main>
        <section class="hero">
            <div class="container hero-grid">
                <div>
                    <div class="badge">
                        <span class="badge-dot"></span>
                        GIS-Enabled Campus Wayfinding
                    </div>

                    <h2 class="hero-title">
                        Smart Campus <span>Navigation System</span>
                    </h2>

                    <p class="hero-text">
                        Navigate Southern Leyte State University - Tomas Oppus Campus with ease through a smart and modern wayfinding platform built for students, faculty, staff, and visitors. Find buildings quickly, speak your desired destination, follow optimized routes, and receive safety-aware navigation assistance in real time.
                    </p>

                    <div class="hero-buttons">
                        @auth
                            <a href="{{ $dashboardRoute }}" class="btn btn-primary">
                                {{ auth()->user()->role === 'admin' ? 'Open Admin Dashboard' : 'Open Navigation' }}
                            </a>

                            <a href="#features" class="btn btn-outline">Explore Features</a>
                        @else
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="btn btn-primary">Get Started</a>
                            @endif

                            @if (Route::has('login'))
                                <a href="{{ route('login') }}" class="btn btn-outline">Log In</a>
                            @else
                                <a href="#features" class="btn btn-outline">Explore Features</a>
                            @endif

                            <a href="{{ route('guest.dashboard') }}" class="btn btn-guest">
                                Continue as Guest
                            </a>
                        @endauth
                    </div>

                    <div class="hero-stats">
                        <div class="stat-card">
                            <strong>Real-Time</strong>
                            <span>Smart location-aware directions across campus.</span>
                        </div>

                        <div class="stat-card">
                            <strong>Voice Search</strong>
                            <span>Speak your destination to quickly search campus locations.</span>
                        </div>

                        <div class="stat-card">
                            <strong>Safe Routes</strong>
                            <span>Guided paths with hazard-aware assistance.</span>
                        </div>
                    </div>
                </div>

                <div class="hero-visual">
                    <div class="glow"></div>

                    <div class="floating-card card-top">
                        <strong>Smart Guidance</strong>
                        <span>Faster wayfinding with clear campus directions.</span>
                    </div>

                    <div class="image-shell">
                        <img
                            src="{{ asset('background/background.png') }}"
                            alt="Smart Campus Navigation System Preview"
                        >
                    </div>

                    <div class="floating-card card-bottom">
                        <strong>Voice Search</strong>
                        <span>Tell the system where you want to go by speaking your destination.</span>
                    </div>
                </div>
            </div>
        </section>

        <section id="features" class="section">
            <div class="container">
                <div class="section-head">
                    <h2>Key Features</h2>
                    <p>
                        The Smart Campus Navigation System is designed to improve mobility, safety, and convenience throughout Southern Leyte State University - Tomas Oppus Campus.
                    </p>
                </div>

                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">📍</div>
                        <h3>Dual-Mode Navigation</h3>
                        <p>Use map-based and location-aware navigation for easier campus travel.</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">🎙️</div>
                        <h3>Voice-Driven Wayfinding</h3>
                        <p>Speak or voice out your desired destination, and the system will help find the best campus route for you.</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">🗓️</div>
                        <h3>Event-Aware Mapping</h3>
                        <p>View destinations and guidance related to ongoing campus events.</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">🧭</div>
                        <h3>Route Optimization</h3>
                        <p>Get efficient paths to buildings, offices, and campus facilities.</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">⚠️</div>
                        <h3>Hazard Alerts</h3>
                        <p>Stay informed about unsafe areas, blocked routes, or disruptions.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="cta">
            <div class="container">
                <div class="cta-box">
                    <div class="cta-inner">
                        <div class="cta-text">
                            <h3>Start Navigating the Campus Smarter</h3>
                            <p>
                                Experience a safer, easier, and more intelligent way to move around Southern Leyte State University - Tomas Oppus Campus. Search campus locations, speak your destination, and follow optimized routes using the Smart Campus Navigation System.
                            </p>
                        </div>

                        <div class="cta-actions">
                            @auth
                                <a href="{{ $dashboardRoute }}" class="btn btn-light">
                                    {{ auth()->user()->role === 'admin' ? 'Open Admin Dashboard' : 'Open User Dashboard' }}
                                </a>
                            @else
                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}" class="btn btn-light">Create Account</a>
                                @endif

                                @if (Route::has('login'))
                                    <a href="{{ route('login') }}" class="btn btn-ghost">Open System</a>
                                @endif
                            @endauth
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="container footer-inner">
            <div class="footer-brand">
                <img src="{{ asset('background/slsu-logo.jpg') }}" alt="SLSU Logo">
                <span>Southern Leyte State University - Tomas Oppus Campus</span>
            </div>

            <div>
                © {{ date('Y') }} Smart Campus Navigation System. All rights reserved.
            </div>
        </div>
    </footer>
    @include('components.futuristic-dialogs')
</body>
</html>
