<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    
    <!-- === INI ADALAH KODE ASLI MURNI DARI PANDUAN FASE 14 === -->
    <body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
        <div class="mx-auto min-h-screen max-w-7xl pb-20 sm:pb-0">
            <header class="sticky top-0 z-30 border-b bg-white/95 px-4 py-3 backdrop-blur sm:px-6">
                <div class="flex items-center justify-between">
                    <a href="{{ route('dashboard') }}" class="font-bold text-indigo-700">SCP</a>
                    <div class="flex items-center gap-4">
                        <span class="text-sm font-semibold text-slate-700">{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-xs text-red-600 font-bold hover:underline">Keluar</button>
                        </form>
                    </div>
                </div>
            </header>

            <main class="space-y-4 p-4 sm:p-6">
                @if (session('success'))
                    <div role="status" class="rounded-lg bg-emerald-50 p-3 text-emerald-800">
                        {{ session('success') }}
                    </div>
                @endif
                {{ $slot }}

                @if (session('error'))
                    <div role="status" class="rounded-lg bg-red-50 p-3 text-red-800 border border-red-200">
                        {{ session('error') }}
                    </div>
                @endif
                
                @if ($errors->any())
                    <div role="status" class="rounded-lg bg-red-50 p-3 text-red-800 border border-red-200">
                        <ul class="list-disc list-inside text-sm">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

            </main>

            <nav class="fixed inset-x-0 bottom-0 z-30 border-t bg-white p-2 sm:static sm:border-0">
                {{-- Link disusun berdasarkan role; target sentuh minimal h-11 --}}
            </nav>
        </div>
    </body>
</html>
