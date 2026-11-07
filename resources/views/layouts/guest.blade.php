<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'SCP - School Class Progress') }}</title>

        <!-- Favicon / Logo Aplikasi SCP -->
        <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}?v=20261107">
        <link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v=20261107">
        <link rel="apple-touch-icon" href="{{ asset('images/logo-sm.png') }}?v=20261107">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            html, body {
                min-height: 100% !important;
                min-height: 100dvh !important;
                background-color: #0f172a !important;
                background: linear-gradient(135deg, #090d16 0%, #0f172a 30%, #1e1b4b 65%, #1e293b 100%) !important;
                background-attachment: fixed !important;
                background-size: cover !important;
            }
            .scp-btn-gradient {
                background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%) !important;
                color: #ffffff !important;
                box-shadow: 0 10px 20px -5px rgba(79, 70, 229, 0.35) !important;
                transition: all 0.2s ease !important;
            }
            .scp-btn-gradient:hover {
                background: linear-gradient(135deg, #4338ca 0%, #4f46e5 100%) !important;
                box-shadow: 0 14px 25px -5px rgba(79, 70, 229, 0.5) !important;
                transform: translateY(-1px);
            }
            .scp-badge-gradient {
                background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%) !important;
                color: #ffffff !important;
                box-shadow: 0 8px 16px -4px rgba(79, 70, 229, 0.35) !important;
            }
            .scp-glow-1 {
                background: radial-gradient(circle, rgba(99, 102, 241, 0.35) 0%, rgba(99, 102, 241, 0) 70%);
            }
            .scp-glow-2 {
                background: radial-gradient(circle, rgba(56, 189, 248, 0.25) 0%, rgba(56, 189, 248, 0) 70%);
            }
            .scp-password-wrapper {
                position: relative !important;
                width: 100% !important;
                display: block !important;
            }
            .scp-password-toggle {
                position: absolute !important;
                right: 14px !important;
                top: 50% !important;
                transform: translateY(-50%) !important;
                background: transparent !important;
                border: none !important;
                cursor: pointer !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                padding: 4px !important;
                color: #94a3b8 !important;
                z-index: 10 !important;
                transition: color 0.15s ease !important;
            }
            .scp-password-toggle:hover {
                color: #4f46e5 !important;
            }
        </style>
    </head>
    <body class="font-sans antialiased text-slate-100 min-h-screen min-h-[100dvh] flex flex-col items-center justify-center p-4 sm:p-6 py-8 sm:py-12 relative overflow-x-hidden overflow-y-auto" style="background: linear-gradient(135deg, #090d16 0%, #0f172a 30%, #1e1b4b 65%, #1e293b 100%);">
        <!-- Ambient Glowing Orbs (Pinned to Viewport so they never scroll away) -->
        <div class="fixed -top-24 -right-24 h-[450px] w-[450px] rounded-full scp-glow-1 pointer-events-none z-0"></div>
        <div class="fixed -bottom-24 -left-24 h-[450px] w-[450px] rounded-full scp-glow-2 pointer-events-none z-0"></div>

        <!-- Login Card with 15px Corner Radius -->
        <div class="relative w-full sm:max-w-md bg-white p-8 sm:p-10 shadow-2xl border border-slate-200 my-auto z-10" style="border-radius: 15px;">
            {{ $slot }}
        </div>
    </body>
</html>
