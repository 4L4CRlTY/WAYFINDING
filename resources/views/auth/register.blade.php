<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Register - Smart Campus Navigation System</title>

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
            --line: rgba(11, 122, 67, 0.16);
            --danger: #b42318;
        }

        body {
            min-height: 100vh;
            font-family: "Instrument Sans", sans-serif;
            background:
                radial-gradient(circle at top left, rgba(23, 59, 143, 0.10), transparent 30%),
                radial-gradient(circle at bottom right, rgba(12, 122, 67, 0.15), transparent 35%),
                linear-gradient(180deg, #f8fcfa 0%, #f3faf6 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            color: var(--text);
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .register-box {
            width: min(960px, 100%);
            min-height: 610px;
            display: grid;
            grid-template-columns: 1.05fr 0.95fr;
            background: rgba(255, 255, 255, 0.96);
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 24px 70px rgba(8, 45, 28, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.8);
        }

        .mascot-side {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 34px;
            background:
                radial-gradient(circle at 20% 20%, rgba(255,255,255,0.16), transparent 30%),
                linear-gradient(135deg, #062a1a, #075f35 45%, #102f7a 100%);
            overflow: hidden;
        }

        .mascot-side::before {
            content: "";
            position: absolute;
            width: 280px;
            height: 280px;
            border-radius: 50%;
            top: -100px;
            right: -90px;
            background: rgba(255, 255, 255, 0.10);
        }

        .mascot-side::after {
            content: "";
            position: absolute;
            width: 240px;
            height: 240px;
            border-radius: 50%;
            bottom: -100px;
            left: -80px;
            background: rgba(255, 255, 255, 0.08);
        }

        .mascot-content {
            position: relative;
            z-index: 2;
            width: 100%;
            text-align: center;
        }

        .mascot-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 13px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.20);
            color: #ffffff;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            backdrop-filter: blur(10px);
        }

        .badge-dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: #7ff0b2;
            box-shadow: 0 0 0 6px rgba(127, 240, 178, 0.14);
        }

        .mascot-img {
            width: min(350px, 100%);
            max-height: 410px;
            object-fit: contain;
            margin: 14px auto 8px;
            filter: drop-shadow(0 20px 30px rgba(0, 0, 0, 0.28));
        }

        .mascot-title {
            color: #ffffff;
            font-size: clamp(28px, 4vw, 42px);
            font-weight: 800;
            line-height: 1;
            letter-spacing: -0.04em;
            margin-top: 4px;
        }

        .mascot-text {
            max-width: 330px;
            margin: 10px auto 0;
            color: rgba(255, 255, 255, 0.86);
            font-size: 14px;
            line-height: 1.6;
        }

        .mini-features {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 18px;
        }

        .mini-feature {
            padding: 8px 11px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.13);
            border: 1px solid rgba(255, 255, 255, 0.16);
            color: #ffffff;
            font-size: 12px;
            font-weight: 700;
            backdrop-filter: blur(10px);
        }

        .form-side {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 34px;
            background:
                radial-gradient(circle at top right, rgba(12, 122, 67, 0.06), transparent 35%),
                #ffffff;
        }

        .register-card {
            width: 100%;
            max-width: 370px;
        }

        .logo {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 50%;
            display: block;
            margin: 0 auto 14px;
            border: 3px solid #fff;
            box-shadow: 0 10px 25px rgba(12, 122, 67, 0.18);
        }

        .title {
            text-align: center;
            font-size: 29px;
            font-weight: 800;
            color: var(--green-dark);
            line-height: 1.1;
            letter-spacing: -0.03em;
        }

        .subtitle {
            text-align: center;
            margin-top: 8px;
            font-size: 14px;
            color: var(--muted);
        }

        form {
            margin-top: 22px;
        }

        .form-group {
            margin-top: 14px;
        }

        .label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 800;
            color: var(--text);
        }

        .input {
            width: 100%;
            height: 48px;
            border-radius: 15px;
            border: 1px solid var(--line);
            padding: 0 14px;
            font-size: 14px;
            outline: none;
            color: var(--text);
            background: #ffffff;
            transition: 0.2s ease;
        }

        .input:focus {
            border-color: var(--green);
            box-shadow: 0 0 0 4px rgba(12, 122, 67, 0.12);
        }

        .input::placeholder {
            color: #8ca096;
        }

        .error {
            margin-top: 6px;
            color: var(--danger);
            font-size: 13px;
            line-height: 1.4;
        }

        .bottom-row {
            margin-top: 18px;
            text-align: center;
        }

        .btn-register {
            width: 100%;
            height: 51px;
            border: none;
            border-radius: 999px;
            background: linear-gradient(135deg, var(--green-dark), var(--green));
            color: #ffffff;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 14px 30px rgba(12, 122, 67, 0.25);
            transition: 0.2s ease;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 18px 38px rgba(12, 122, 67, 0.30);
        }

        .login-text {
            margin-top: 18px;
            text-align: center;
            color: var(--muted);
            font-size: 14px;
        }

        .login-text a {
            color: var(--green-dark);
            font-weight: 800;
        }

        .login-text a:hover {
            color: var(--blue);
            text-decoration: underline;
        }

        .back {
            display: block;
            margin-top: 14px;
            text-align: center;
            color: var(--muted);
            font-size: 14px;
        }

        .back:hover {
            color: var(--green-dark);
            text-decoration: underline;
        }

        @media (max-width: 880px) {
            .register-box {
                grid-template-columns: 1fr;
            }

            .mascot-side {
                min-height: 390px;
                padding: 28px 22px;
            }

            .mascot-img {
                max-height: 250px;
            }

            .mascot-title {
                font-size: 30px;
            }

            .form-side {
                padding: 32px 22px;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 14px;
            }

            .register-box {
                border-radius: 22px;
            }

            .mascot-side {
                min-height: 340px;
            }

            .mascot-img {
                max-height: 220px;
            }

            .title {
                font-size: 25px;
            }
        }
    </style>
</head>

<body>
    <main class="register-box">
        <section class="mascot-side">
            <div class="mascot-content">
                <div class="mascot-badge">
                    <span class="badge-dot"></span>
                    Join the Campus Map
                </div>

                <img
                    src="{{ asset('background/mascot-register.png') }}"
                    alt="Register Mascot"
                    class="mascot-img"
                >

                <h2 class="mascot-title">Start your journey.</h2>

                <p class="mascot-text">
                    Create your account and explore the campus smarter.
                </p>

                <div class="mini-features">
                    <span class="mini-feature">Voice Search</span>
                    <span class="mini-feature">Safe Routes</span>
                    <span class="mini-feature">Smart Map</span>
                </div>
            </div>
        </section>

        <section class="form-side">
            <div class="register-card">
                <img
                    src="{{ asset('background/slsu-logo.jpg') }}"
                    alt="SLSU Logo"
                    class="logo"
                >

                <h1 class="title">Create Account</h1>
                <p class="subtitle">Smart Campus Navigation System</p>

                <form method="POST" action="{{ route('register') }}">
                    @csrf

                    <div class="form-group">
                        <label for="username" class="label">Username</label>

                        <input
                            id="username"
                            class="input"
                            type="text"
                            name="username"
                            value="{{ old('username') }}"
                            placeholder="Enter your username"
                            required
                            autofocus
                            autocomplete="username"
                        >

                        @error('username')
                            <div class="error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="email" class="label">Email Address</label>

                        <input
                            id="email"
                            class="input"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="Enter your email"
                            required
                            autocomplete="username"
                        >

                        @error('email')
                            <div class="error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="password" class="label">Password</label>

                        <input
                            id="password"
                            class="input"
                            type="password"
                            name="password"
                            placeholder="Enter password"
                            required
                            autocomplete="new-password"
                        >

                        @error('password')
                            <div class="error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="password_confirmation" class="label">Confirm Password</label>

                        <input
                            id="password_confirmation"
                            class="input"
                            type="password"
                            name="password_confirmation"
                            placeholder="Confirm password"
                            required
                            autocomplete="new-password"
                        >

                        @error('password_confirmation')
                            <div class="error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="bottom-row">
                        <button type="submit" class="btn-register">
                            Register
                        </button>

                        <p class="login-text">
                            Already registered?
                            <a href="{{ route('login') }}">Log in</a>
                        </p>

                        <a href="{{ url('/') }}" class="back">
                            ← Back to homepage
                        </a>
                    </div>
                </form>
            </div>
        </section>
    </main>
</body>
</html>
