<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'SCP - School Class Progress') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            .scp-bg-gradient {
                background: linear-gradient(135deg, #090d16 0%, #0f172a 50%, #1e293b 100%) !important;
                min-height: 100vh;
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
                background: radial-gradient(circle, rgba(99, 102, 241, 0.18) 0%, rgba(99, 102, 241, 0) 70%);
            }
            .scp-glow-2 {
                background: radial-gradient(circle, rgba(148, 163, 184, 0.12) 0%, rgba(148, 163, 184, 0) 70%);
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
    <body class="font-sans antialiased scp-bg-gradient min-h-screen flex items-center justify-center p-4 sm:p-6 py-8 sm:py-12 relative overflow-x-hidden overflow-y-auto">
        <!-- Ambient Glowing Orbs -->
        <div class="absolute -top-32 -right-32 h-96 w-96 rounded-full scp-glow-1 pointer-events-none"></div>
        <div class="absolute -bottom-32 -left-32 h-96 w-96 rounded-full scp-glow-2 pointer-events-none"></div>

        <!-- Login Card with 15px Corner Radius -->
        <div class="relative w-full sm:max-w-md bg-white p-8 sm:p-10 shadow-2xl border border-slate-200 my-auto" style="border-radius: 15px;">
            {{ $slot }}
        </div>
    </body>
</html>

