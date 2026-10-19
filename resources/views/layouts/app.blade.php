<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'SCP - School Class Progress') }}</title>

        <!-- Script Deteksi Tema Cepat (Mencegah Layar Berkedip saat Refresh) -->
        <script>
            if (localStorage.getItem('scp_theme') === 'light') {
                document.documentElement.classList.add('theme-light');
                document.documentElement.classList.remove('dark');
            } else {
                document.documentElement.classList.remove('theme-light');
                document.documentElement.classList.add('dark');
            }
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            [x-cloak] { display: none !important; }
            
            /* ===================================================
               GAYA UMUM (TOMBOL & BADGE GRADASI)
               =================================================== */
            .scp-badge-gradient {
                background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%) !important;
                color: #ffffff !important;
                box-shadow: 0 4px 14px rgba(79, 70, 229, 0.35) !important;
            }
            .scp-btn-gradient {
                background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%) !important;
                color: #ffffff !important;
                box-shadow: 0 4px 14px rgba(79, 70, 229, 0.3) !important;
                transition: all 0.2s ease !important;
            }
            .scp-btn-gradient:hover {
                background: linear-gradient(135deg, #4338ca 0%, #4f46e5 100%) !important;
                box-shadow: 0 6px 20px rgba(79, 70, 229, 0.45) !important;
                transform: translateY(-1px);
            }

            /* ===================================================
               MODE GELAP DEFAULT (DARK SLATE)
               =================================================== */
            body {
                background: linear-gradient(135deg, #090d16 0%, #0f172a 50%, #1e293b 100%) !important;
                min-height: 100vh;
                color: #f8fafc;
            }

            /* ===================================================
               PENYESUAIAN WARNA MODE TERANG (LIGHT SLATE THEME)
               =================================================== */
            html.theme-light body {
                background: #f1f5f9 !important;
                color: #0f172a !important;
            }
            
            /* Sidebar Light Mode */
            html.theme-light aside {
                background-color: #ffffff !important;
                border-color: #e2e8f0 !important;
                box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.05) !important;
            }
            html.theme-light aside h2 {
                color: #0f172a !important;
            }
            html.theme-light aside nav p {
                color: #64748b !important;
            }
            html.theme-light aside nav a:not(.bg-indigo-600) {
                color: #334155 !important;
            }
            html.theme-light aside nav a:not(.bg-indigo-600):hover {
                background-color: #f1f5f9 !important;
                color: #0f172a !important;
            }
            html.theme-light aside nav a:not(.bg-indigo-600) svg {
                color: #64748b !important;
            }
            html.theme-light aside .border-t {
                border-color: #e2e8f0 !important;
            }

            /* Header Topbar Light Mode */
            html.theme-light header {
                background-color: #ffffff !important;
                border-color: #e2e8f0 !important;
            }
            html.theme-light header input {
                background-color: #f8fafc !important;
                border-color: #cbd5e1 !important;
                color: #0f172a !important;
            }
            html.theme-light header input::placeholder {
                color: #94a3b8 !important;
            }
            html.theme-light .theme-toggle-btn {
                border-color: #cbd5e1 !important;
                background-color: #f8fafc !important;
                color: #475569 !important;
            }
            html.theme-light .theme-toggle-btn:hover {
                background-color: #f1f5f9 !important;
                color: #0f172a !important;
            }
            html.theme-light .user-name-label {
                color: #0f172a !important;
            }

            /* Kotak Kartu Konten Light Mode */
            html.theme-light .bg-\[\#0f172a\]\/90,
            html.theme-light .bg-\[\#0f172a\] {
                background-color: #ffffff !important;
                border-color: #e2e8f0 !important;
                box-shadow: 0 4px 12px -2px rgba(0, 0, 0, 0.06), 0 2px 4px -1px rgba(0, 0, 0, 0.04) !important;
            }

            /* ===================================================
               TEKS KONTEN & ANGKA STATISTIK DI LIGHT MODE
               (Semua teks putih berubah otomatis jadi gelap pekat)
               =================================================== */
            html.theme-light main h1,
            html.theme-light main h2,
            html.theme-light main h3,
            html.theme-light main .text-white:not(.scp-btn-gradient):not(.scp-btn-gradient *):not(button):not(button *) {
                color: #0f172a !important;
            }
            html.theme-light main .text-slate-200,
            html.theme-light main .text-slate-300 {
                color: #334155 !important;
            }
            html.theme-light main .text-slate-400,
            html.theme-light main .text-slate-500 {
                color: #64748b !important;
            }

            /* ===================================================
               TOMBOL BERWARNA & GRADASI: WAJIB SELALU TEKS PUTIH
               =================================================== */
            .scp-btn-gradient,
            .scp-btn-gradient *,
            html.theme-light main .scp-btn-gradient,
            html.theme-light main .scp-btn-gradient *,
            html.theme-light main button.scp-btn-gradient,
            html.theme-light main button.scp-btn-gradient *,
            html.theme-light main a.scp-btn-gradient,
            html.theme-light main a.scp-btn-gradient *,
            html.theme-light button.bg-emerald-600,
            html.theme-light button.bg-emerald-600 *,
            html.theme-light button.bg-red-600,
            html.theme-light button.bg-red-600 *,
            html.theme-light a.bg-indigo-600,
            html.theme-light a.bg-indigo-600 * {
                color: #ffffff !important;
            }

            /* ===================================================
               TOMBOL PILIHAN PRESENSI SISWA (H, S, I, A)
               =================================================== */
            /* Saat Belum Dipilih (Unchecked) */
            main input[type="radio"] + span {
                border-color: #334155 !important;
                background-color: #0f172a !important;
                color: #94a3b8 !important;
            }
            html.theme-light main input[type="radio"] + span {
                border-color: #cbd5e1 !important;
                background-color: #ffffff !important;
                color: #475569 !important;
            }

            /* H (Hadir) Terpilih -> Hijau Emerald Solid & Teks Putih */
            main input[type="radio"][value="Hadir"]:checked + span,
            html.theme-light main input[type="radio"][value="Hadir"]:checked + span {
                background-color: #059669 !important;
                border-color: #059669 !important;
                color: #ffffff !important;
                box-shadow: 0 2px 8px rgba(5, 150, 105, 0.4) !important;
            }

            /* S (Sakit) Terpilih -> Kuning/Amber Solid & Teks Putih */
            main input[type="radio"][value="Sakit"]:checked + span,
            html.theme-light main input[type="radio"][value="Sakit"]:checked + span {
                background-color: #d97706 !important;
                border-color: #d97706 !important;
                color: #ffffff !important;
                box-shadow: 0 2px 8px rgba(217, 119, 6, 0.4) !important;
            }

            /* I (Izin) Terpilih -> Biru Solid & Teks Putih */
            main input[type="radio"][value="Izin"]:checked + span,
            html.theme-light main input[type="radio"][value="Izin"]:checked + span {
                background-color: #2563eb !important;
                border-color: #2563eb !important;
                color: #ffffff !important;
                box-shadow: 0 2px 8px rgba(37, 99, 235, 0.4) !important;
            }

            /* A (Alpa) Terpilih -> Merah Solid & Teks Putih */
            main input[type="radio"][value="Alpa"]:checked + span,
            html.theme-light main input[type="radio"][value="Alpa"]:checked + span {
                background-color: #dc2626 !important;
                border-color: #dc2626 !important;
                color: #ffffff !important;
                box-shadow: 0 2px 8px rgba(220, 38, 38, 0.4) !important;
            }


            /* ===================================================
               KOTAK UPLOAD FOTO BUKTI KBM DI LIGHT MODE
               =================================================== */
            html.theme-light .border-dashed {
                background-color: #f8fafc !important;
                border-color: #cbd5e1 !important;
            }
            html.theme-light .border-dashed:hover {
                background-color: #f1f5f9 !important;
            }
            html.theme-light .border-dashed p.text-indigo-400 {
                color: #4f46e5 !important;
            }
            html.theme-light .border-dashed p.text-slate-400 {
                color: #64748b !important;
            }



            /* Input Form & Dropdown Light Mode */
            html.theme-light main input,
            html.theme-light main select,
            html.theme-light main textarea,
            html.theme-light [x-show="addModalOpen"] input,
            html.theme-light [x-show="addModalOpen"] select {
                background-color: #f8fafc !important;
                border-color: #cbd5e1 !important;
                color: #0f172a !important;
            }
            html.theme-light main input::placeholder,
            html.theme-light [x-show="addModalOpen"] input::placeholder {
                color: #94a3b8 !important;
            }
            html.theme-light option {
                background-color: #ffffff !important;
                color: #0f172a !important;
            }

            /* Kotak Info Internal / Kotak Alasan / Sub-kartu di Light Mode */
            html.theme-light [class*="bg-slate-950"],
            html.theme-light [class*="bg-slate-900"],
            html.theme-light .bg-slate-950\/40,
            html.theme-light .bg-slate-900\/80,
            html.theme-light .bg-slate-900\/60,
            html.theme-light .bg-slate-900\/40,
            html.theme-light .bg-slate-900 {
                background-color: #f1f5f9 !important;
                border-color: #e2e8f0 !important;
                color: #1e293b !important;
            }
            html.theme-light [class*="bg-slate-950"] span,
            html.theme-light [class*="bg-slate-900"] span {
                color: #64748b !important;
            }
            html.theme-light [class*="bg-slate-950"] p.font-bold,
            html.theme-light [class*="bg-slate-900"] p.font-bold,
            html.theme-light [class*="bg-slate-950"] strong,
            html.theme-light [class*="bg-slate-900"] strong {
                color: #0f172a !important;
            }


            /* ===================================================
               KOTAK COUNTER STATISTIK PRESENSI DI LIGHT MODE
               (Hadir = Emerald, Sakit = Amber, Izin = Biru, Alpa = Merah)
               =================================================== */
            /* 1. Hadir */
            html.theme-light [class*="bg-emerald-950"],
            html.theme-light .bg-emerald-950\/40,
            html.theme-light .bg-emerald-950\/30,
            html.theme-light .bg-emerald-950\/20 {
                background-color: #ecfdf5 !important;
                border-color: #a7f3d0 !important;
                color: #065f46 !important;
            }
            html.theme-light [class*="bg-emerald-950"] strong,
            html.theme-light [class*="bg-emerald-950"] .text-emerald-200,
            html.theme-light [class*="bg-emerald-950"] .text-emerald-300 {
                color: #047857 !important;
            }

            /* 2. Sakit (Latar Amber Lembut & Teks Oranye/Coklat Pekat) */
            html.theme-light [class*="bg-amber-950"],
            html.theme-light .bg-amber-950\/40 {
                background-color: #fffbeb !important;
                border-color: #fde68a !important;
                color: #92400e !important;
            }
            html.theme-light [class*="bg-amber-950"] .text-amber-300 {
                color: #b45309 !important;
            }

            /* 3. Izin (Latar Biru Lembut & Teks Biru Pekat) */
            html.theme-light [class*="bg-blue-950"],
            html.theme-light .bg-blue-950\/40 {
                background-color: #eff6ff !important;
                border-color: #bfdbfe !important;
                color: #1e40af !important;
            }
            html.theme-light [class*="bg-blue-950"] .text-blue-300 {
                color: #1d4ed8 !important;
            }

            /* 4. Alpa */
            html.theme-light [class*="bg-red-950"],
            html.theme-light .bg-red-950\/40,
            html.theme-light .bg-red-950\/20 {
                background-color: #fff1f2 !important;
                border-color: #fecdd3 !important;
                color: #9f1239 !important;
            }
            html.theme-light [class*="bg-red-950"] strong,
            html.theme-light [class*="bg-red-950"] p,
            html.theme-light [class*="bg-red-950"] .text-red-300 {
                color: #be123c !important;
            }

            /* Teks Keterangan Presensi di Bawah (Kontras Tinggi di Mode Terang) */
            html.theme-light main .text-emerald-400 {
                color: #059669 !important;
            }
            html.theme-light main .text-amber-400 {
                color: #d97706 !important;
            }
            html.theme-light main .text-blue-400 {
                color: #2563eb !important;
            }
            html.theme-light main .text-red-400 {
                color: #dc2626 !important;
            }


            /* Badge Status Pil Hijau & Merah (Sedang Berjalan / Terkunci) */
            html.theme-light .bg-emerald-500\/10 {
                background-color: #d1fae5 !important;
                border-color: #a7f3d0 !important;
                color: #047857 !important;
            }
            html.theme-light .bg-red-500\/10 {
                background-color: #ffe4e6 !important;
                border-color: #fecdd3 !important;
                color: #be123c !important;
            }

            /* Badge / Pil di Tabel (Wali Kelas, Role, Jumlah Jadwal) */
            html.theme-light [class*="bg-slate-800"],
            html.theme-light .bg-slate-800\/80 {
                background-color: #f1f5f9 !important;
                border-color: #cbd5e1 !important;
                color: #334155 !important;
            }
            html.theme-light [class*="bg-indigo-950"],
            html.theme-light .bg-indigo-950\/60 {
                background-color: #e0e7ff !important;
                border-color: #c7d2fe !important;
                color: #4338ca !important;
            }

            /* Tombol Aksi Hapus di Tabel */
            html.theme-light button.text-red-400 {
                color: #dc2626 !important;
                border-color: #fca5a5 !important;
            }
            html.theme-light button.text-red-400:hover {
                background-color: #dc2626 !important;
                color: #ffffff !important;
            }

            /* Tabel Light Mode */
            html.theme-light table thead tr {
                background-color: #f8fafc !important;
            }
            html.theme-light table thead th {
                color: #475569 !important;
            }
            html.theme-light table tbody tr:hover {
                background-color: #f8fafc !important;
            }
            html.theme-light table.divide-slate-800,
            html.theme-light tbody.divide-slate-800,
            html.theme-light tbody.divide-slate-800\/60,
            html.theme-light .border-slate-800 {
                border-color: #e2e8f0 !important;
            }

            /* Modal Pop-up Light Mode */
            html.theme-light [x-show="addModalOpen"] > div:first-child {
                background-color: rgba(15, 23, 42, 0.45) !important;
            }
            html.theme-light [x-show="addModalOpen"] label {
                color: #334155 !important;
            }
            html.theme-light [x-show="addModalOpen"] .border-b {
                border-color: #e2e8f0 !important;
            }
            html.theme-light [x-show="addModalOpen"] button.text-slate-400 {
                color: #64748b !important;
            }
            html.theme-light [x-show="addModalOpen"] button.text-slate-400:hover {
                color: #0f172a !important;
            }
        </style>
    </head>
    <body class="min-h-screen text-slate-100 antialiased font-sans transition-colors duration-200">
        <!-- Kontainer Utama Alpine.js -->
        <div x-data="{ 
                 sidebarOpen: false, 
                 theme: localStorage.getItem('scp_theme') || 'dark',
                 toggleTheme() {
                     this.theme = this.theme === 'dark' ? 'light' : 'dark';
                     localStorage.setItem('scp_theme', this.theme);
                     if (this.theme === 'light') {
                         document.documentElement.classList.add('theme-light');
                         document.documentElement.classList.remove('dark');
                     } else {
                         document.documentElement.classList.remove('theme-light');
                         document.documentElement.classList.add('dark');
                     }
                 }
             }" 
             class="flex min-h-screen w-full relative">
            
            <!-- BACKDROP GELAP DI LAYAR MOBILE -->
            <div x-show="sidebarOpen" 
                 @click="sidebarOpen = false" 
                 x-cloak 
                 class="fixed inset-0 bg-black/75 backdrop-blur-sm z-40 lg:hidden transition-opacity">
            </div>

                        <!-- SIDEBAR RESPONSIVE (Dinamis Sesuai Role Pengguna) -->
            <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
                   class="fixed lg:sticky top-0 left-0 h-screen w-64 min-w-[16rem] bg-[#0c1322] border-r border-slate-800 flex flex-col justify-between z-50 transition-transform duration-300 ease-in-out shadow-2xl lg:shadow-none">
                
                <div class="p-5 flex flex-col h-full overflow-y-auto">
                    <!-- Brand / Logo & Subtitle Role -->
                    <div class="flex items-center justify-between mb-8">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 scp-badge-gradient flex items-center justify-center font-black text-lg text-white shadow-lg" style="border-radius: 12px;">
                                S
                            </div>
                            <div>
                                <h2 class="text-base font-extrabold text-white leading-tight">SCP Sekolah</h2>
                                <p class="text-[11px] font-bold text-indigo-400 tracking-wider uppercase">
                                    {{ auth()->user()->role?->role_name ?? 'PORTAL AKADEMIK' }}
                                </p>
                            </div>
                        </div>

                        <!-- Tombol Silang [X] hanya di Mobile -->
                        <button @click="sidebarOpen = false" 
                                type="button" 
                                class="lg:hidden p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition cursor-pointer"
                                title="Tutup Menu">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <!-- Navigation Links Berdasarkan Role -->
                    <nav class="space-y-6 flex-1">

                        {{-- ======================================================== --}}
                        {{-- MENU KHUSUS GURU PENGAJAR                                --}}
                        {{-- ======================================================== --}}
                        @if(auth()->user()->role?->role_name === 'Guru')
                            <div>
                                <p class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider px-3 mb-2">MENU GURU</p>
                                <div class="space-y-1">
                                    <a href="{{ route('teacher.dashboard') }}" 
                                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition {{ request()->routeIs('teacher.dashboard') ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-600/30' : 'text-slate-100 hover:bg-slate-800/80 hover:text-white font-semibold' }}">
                                        <svg class="w-4 h-4 {{ request()->routeIs('teacher.dashboard') ? 'text-white' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                                        Dashboard Guru
                                    </a>

                                    <a href="{{ route('teacher.schedules.index') }}" 
                                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition {{ request()->routeIs('teacher.schedules.*') ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-600/30' : 'text-slate-100 hover:bg-slate-800/80 hover:text-white font-semibold' }}">
                                        <svg class="w-4 h-4 {{ request()->routeIs('teacher.schedules.*') ? 'text-white' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        Jadwal Mengajar Saya
                                    </a>
                                </div>
                            </div>

                            <div>
                                <p class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider px-3 mb-2">AKTIVITAS KBM</p>
                                <div class="space-y-1">
                                    <a href="{{ route('teacher.journals.create') }}" 
                                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition {{ request()->routeIs('teacher.journals.*') ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-600/30' : 'text-slate-100 hover:bg-slate-800/80 hover:text-white font-semibold' }}">
                                        <svg class="w-4 h-4 {{ request()->routeIs('teacher.journals.*') ? 'text-white' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        Isi Jurnal & Presensi
                                    </a>
                                </div>
                            </div>

                        {{-- ======================================================== --}}
                        {{-- MENU WAKA KURIKULUM                                      --}}
                        {{-- ======================================================== --}}
                        @elseif(auth()->user()->role?->role_name === 'Waka Kurikulum')
                            <div>
                                <p class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider px-3 mb-2">MENU UTAMA</p>
                                <div class="space-y-1">
                                    <a href="{{ route('waka.dashboard') }}" 
                                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition {{ request()->routeIs('waka.dashboard') ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-600/30' : 'text-slate-100 hover:bg-slate-800/80 hover:text-white font-semibold' }}">
                                        <svg class="w-4 h-4 {{ request()->routeIs('waka.dashboard') ? 'text-white' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                                        Dashboard Waka
                                    </a>

                                    <a href="{{ route('waka.cycle.index') }}" 
                                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition {{ request()->routeIs('waka.cycle.*') ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-600/30' : 'text-slate-100 hover:bg-slate-800/80 hover:text-white font-semibold' }}">
                                        <svg class="w-4 h-4 {{ request()->routeIs('waka.cycle.*') ? 'text-white' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                        Siklus Blok
                                    </a>

                                    <a href="{{ route('waka.teachers.index') }}" 
                                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition {{ request()->routeIs('waka.teachers.*') ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-600/30' : 'text-slate-100 hover:bg-slate-800/80 hover:text-white font-semibold' }}">
                                        <svg class="w-4 h-4 {{ request()->routeIs('waka.teachers.*') ? 'text-white' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                        Data Guru
                                    </a>

                                    <a href="{{ route('waka.students.index') }}" 
                                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition {{ request()->routeIs('waka.students.*') ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-600/30' : 'text-slate-100 hover:bg-slate-800/80 hover:text-white font-semibold' }}">
                                        <svg class="w-4 h-4 {{ request()->routeIs('waka.students.*') ? 'text-white' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                        Data Siswa
                                    </a>
                                </div>
                            </div>

                            <div>
                                <p class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider px-3 mb-2">MASTER AKADEMIK</p>
                                <div class="space-y-1">
                                    <a href="{{ route('waka.schedules.index') }}" 
                                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition {{ request()->routeIs('waka.schedules.*') ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-600/30' : 'text-slate-100 hover:bg-slate-800/80 hover:text-white font-semibold' }}">
                                        <svg class="w-4 h-4 {{ request()->routeIs('waka.schedules.*') ? 'text-white' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        Jadwal Mengajar
                                    </a>

                                    <a href="{{ route('waka.classes.index') }}" 
                                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition {{ request()->routeIs('waka.classes.*') ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-600/30' : 'text-slate-100 hover:bg-slate-800/80 hover:text-white font-semibold' }}">
                                        <svg class="w-4 h-4 {{ request()->routeIs('waka.classes.*') ? 'text-white' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                                        Mata Pelajaran & Kelas
                                    </a>

                                    <a href="{{ route('waka.rooms.index') }}" 
                                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition {{ request()->routeIs('waka.rooms.*') ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-600/30' : 'text-slate-100 hover:bg-slate-800/80 hover:text-white font-semibold' }}">
                                        <svg class="w-4 h-4 {{ request()->routeIs('waka.rooms.*') ? 'text-white' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                                        Data Ruangan
                                    </a>
                                </div>
                            </div>

                        {{-- ======================================================== --}}
                        {{-- MENU KHUSUS WALI KELAS & GURU BK                         --}}
                        {{-- ======================================================== --}}
                        @elseif(in_array(auth()->user()->role?->role_name, ['Wali Kelas', 'Guru BK']))
                            <div>
                                <p class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider px-3 mb-2">KESISWAAN & PERWALIAN</p>
                                <div class="space-y-1">
                                    <a href="{{ route('student-affairs.dashboard') }}" 
                                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition {{ request()->routeIs('student-affairs.*') ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-600/30' : 'text-slate-100 hover:bg-slate-800/80 hover:text-white font-semibold' }}">
                                        <svg class="w-4 h-4 {{ request()->routeIs('student-affairs.*') ? 'text-white' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        Persetujuan Izin Siswa
                                    </a>
                                </div>
                            </div>

                        {{-- ======================================================== --}}
                        {{-- MENU KHUSUS SISWA & ORANG TUA (PORTAL KELUARGA)           --}}
                        {{-- ======================================================== --}}
                        @elseif(in_array(auth()->user()->role?->role_name, ['Siswa', 'Orang Tua']))
                            <div>
                                <p class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider px-3 mb-2">PORTAL KELUARGA</p>
                                <div class="space-y-1">
                                    <!-- 1. Dashboard Utama -->
                                    <a href="{{ route('family.dashboard') }}" 
                                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition {{ request()->routeIs('family.dashboard') ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-600/30' : 'text-slate-100 hover:bg-slate-800/80 hover:text-white font-semibold' }}">
                                        <svg class="w-4 h-4 {{ request()->routeIs('family.dashboard') ? 'text-white' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                                        Dashboard & Kehadiran
                                    </a>

                                    <!-- 2. Pengajuan Izin Baru -->
                                    <a href="{{ route('family.leaves.create') }}" 
                                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition {{ request()->routeIs('family.leaves.create') ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-600/30' : 'text-slate-100 hover:bg-slate-800/80 hover:text-white font-semibold' }}">
                                        <svg class="w-4 h-4 {{ request()->routeIs('family.leaves.create') ? 'text-white' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                        Pengajuan Izin KBM
                                    </a>

                                    <!-- 3. Riwayat & Rekap Izin -->
                                    <a href="{{ route('family.leaves.history') }}" 
                                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition {{ request()->routeIs('family.leaves.history') ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-600/30' : 'text-slate-100 hover:bg-slate-800/80 hover:text-white font-semibold' }}">
                                        <svg class="w-4 h-4 {{ request()->routeIs('family.leaves.history') ? 'text-white' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                                        Riwayat & Rekap Izin
                                    </a>
                                </div>
                            </div>
                        @endif



                        <!-- Grup: Akun Saya (Untuk Semua Role) -->
                        <div>
                            <p class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider px-3 mb-2">AKUN SAYA</p>
                            <div class="space-y-1">
                                <a href="{{ route('profile.edit') }}" 
                                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition {{ request()->routeIs('profile.*') ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-600/30' : 'text-slate-100 hover:bg-slate-800/80 hover:text-white font-semibold' }}">
                                    <svg class="w-4 h-4 {{ request()->routeIs('profile.*') ? 'text-white' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                    Profil Pengguna
                                </a>
                            </div>
                        </div>
                    </nav>

                    <!-- Tombol Keluar / Logout -->
                    <div class="pt-4 border-t border-slate-800">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold text-red-400 hover:bg-red-500/20 hover:text-red-300 border border-transparent hover:border-red-500/30 transition cursor-pointer">
                                <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                                Keluar / Logout
                            </button>
                        </form>
                    </div>
                </div>
            </aside>


            <!-- AREA KONTEN UTAMA -->
            <div class="flex-1 flex flex-col min-h-screen min-w-0">
                
                <!-- TOPBAR -->
                <header class="h-16 bg-[#0c1322] border-b border-slate-800 px-4 sm:px-8 flex items-center justify-between sticky top-0 z-30 transition-colors duration-200">
                    <div class="flex items-center gap-3">
                        <!-- Tombol Garis 3 (Mobile) -->
                        <button @click="sidebarOpen = true" 
                                type="button" 
                                class="lg:hidden p-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800 focus:outline-none transition cursor-pointer" 
                                aria-label="Buka Menu Sidebar">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </button>

                        <div class="w-40 sm:w-72">
                            <input type="text" 
                                   placeholder="Cari data..." 
                                   class="w-full px-3.5 py-2 text-xs bg-slate-900 border border-slate-700 rounded-xl text-white placeholder-slate-400 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition" />
                        </div>
                    </div>

                    <div class="flex items-center gap-2.5 sm:gap-3">
                        <!-- TOMBOL GANTI TEMA (DARK / LIGHT MODE TOGGLE) -->
                        <button @click="toggleTheme()" 
                                type="button" 
                                class="theme-toggle-btn p-2 rounded-xl border border-slate-700 bg-slate-800/80 text-slate-300 hover:text-white hover:bg-slate-800 transition cursor-pointer flex items-center justify-center shadow-sm"
                                :title="theme === 'dark' ? 'Ganti ke Mode Terang' : 'Ganti ke Mode Gelap'">
                            <!-- Ikon Matahari: Muncul saat Mode Gelap aktif -->
                            <svg x-show="theme === 'dark'" class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <!-- Ikon Bulan: Muncul saat Mode Terang aktif -->
                            <svg x-show="theme === 'light'" class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                        </button>

                        <span class="user-name-label text-xs font-bold text-white hidden sm:inline">{{ auth()->user()->name }}</span>
                        <div class="h-8 w-8 rounded-full bg-indigo-600 text-white font-bold text-xs flex items-center justify-center shadow-md">
                            {{ substr(auth()->user()->name, 0, 1) }}
                        </div>
                    </div>
                </header>

                <!-- KONTEN HALAMAN -->
                <main class="flex-1 p-4 sm:p-8 max-w-6xl w-full">
                    @if (session('success'))
                        <div class="mb-5 p-4 rounded-xl bg-emerald-950/40 border border-emerald-800 text-emerald-300 text-xs font-semibold">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="mb-5 p-4 rounded-xl bg-red-950/40 border border-red-800 text-red-300 text-xs font-semibold">
                            {{ session('error') }}
                        </div>
                    @endif

                    {{ $slot }}
                </main>
            </div>

        </div>
    </body>
</html>
