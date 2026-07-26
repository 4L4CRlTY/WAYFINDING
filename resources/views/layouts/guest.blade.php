<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Smart Campus Navigation System') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link
            rel="stylesheet"
            href="{{ asset('css/futuristic-public.css') }}?v={{ filemtime(public_path('css/futuristic-public.css')) }}"
        >
    </head>
    <body class="system-future system-guest antialiased">
        <div class="system-guest-shell">
            <div class="system-guest-stage">
                <div class="system-guest-brand">
                    <a href="/" class="system-guest-brand-link" aria-label="Return to Smart Campus home">
                        <span class="system-guest-logo-frame">
                            <img
                                class="system-guest-logo"
                                src="{{ asset('background/slsu-logo.jpg') }}"
                                alt=""
                                width="56"
                                height="56"
                            >
                        </span>
                        <span class="system-guest-brand-copy">
                            <strong>Smart Campus</strong>
                            <small>Navigation System</small>
                        </span>
                    </a>
                </div>

                <div class="system-guest-card">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
