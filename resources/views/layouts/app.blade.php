<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'School Class Progress') }}</title>

        <!-- Favicon / Logo Aplikasi SCP -->
        <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}?v=20261107">
        <link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v=20261107">
        <link rel="apple-touch-icon" href="{{ asset('images/logo-sm.png') }}?v=20261107">

        <!-- Script Deteksi Tema Cepat & Penghapus Header Cetak Browser Otomatis -->
        <script>
            if (localStorage.getItem('scp_theme') === 'light') {
                document.documentElement.classList.add('theme-light');
                document.documentElement.classList.remove('dark');
            } else {
                document.documentElement.classList.remove('theme-light');
                document.documentElement.classList.add('dark');
            }

            // Hapus teks judul di header atas saat cetak A4 / PDF (Foto 3)
            window.addEventListener('beforeprint', function () {
                window.__scp_orig_title = document.title;
                document.title = '';
            });
            window.addEventListener('afterprint', function () {
                document.title = window.__scp_orig_title || 'School Class Progress';
            });
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            [x-cloak] { display: none !important; }
            
            /* ===================================================
               GAYA UMUM (TOMBOL & BADGE GRADASI)
               =================================================== */
            /* Tombol & Badge Solid Enterprise (Tanpa Gradasi AI Slop) */
            .scp-badge-gradient {
                background-color: #2563eb !important;
                color: #ffffff !important;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1) !important;
            }
            .scp-btn-gradient {
                background-color: #2563eb !important;
                color: #ffffff !important;
                border: 1px solid #1d4ed8 !important;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
                transition: all 0.15s ease-in-out !important;
            }
            .scp-btn-gradient:hover {
                background-color: #1d4ed8 !important;
                box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2) !important;
                transform: translateY(-0.5px);
            }

            /* ===================================================
               MODE GELAP DEFAULT (CLEAN MATTE SLATE)
               =================================================== */
            body {
                background-color: #0b0f19 !important;
                min-height: 100vh;
                color: #f1f5f9;
            }

                        /* ===================================================
               MODERN ENTERPRISE SYSTEM (ANTI AI-SLOP)
               =================================================== */
            /* 1. Sidebar & Header Solid Slate (Bukan Hitam Neon) */
            aside, header {
                background-color: #0f172a !important; /* Solid Slate 900 */
                border-color: #1e293b !important;     /* Slate 800 Border */
            }
            html.theme-light aside, 
            html.theme-light header {
                background-color: #ffffff !important; /* Pure White */
                border-color: #e2e8f0 !important;     /* Clean Slate 200 */
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05) !important;
            }

            /* 2. Menu Navigasi Aktif: Solid Royal Blue (Linear Style) */
            .bg-indigo-600 {
                background-color: #2563eb !important; /* Royal Blue Solid */
                color: #ffffff !important;
                box-shadow: 0 1px 2px rgba(37, 99, 235, 0.3) !important;
            }
            .bg-indigo-600 svg {
                color: #ffffff !important;
            }
            .text-indigo-400 {
                color: #3b82f6 !important;
            }
            .text-indigo-300 {
                color: #60a5fa !important;
            }

            /* 3. Kotak Kartu Konten: Solid Flat Surface (Tinggalkan Glassmorphism Buram) */
            .bg-\[\#0f172a\],
            .bg-\[\#0f172a\]\/90,
            .bg-slate-900 {
                background-color: #111827 !important; /* Dark Surface */
                border: 1px solid #1f2937 !important; /* Crisp 1px Border */
            }
            html.theme-light .bg-\[\#0f172a\],
            html.theme-light .bg-\[\#0f172a\]\/90,
            html.theme-light .bg-slate-900 {
                background-color: #ffffff !important; /* Crisp White Surface */
                border: 1px solid #e5e7eb !important; /* 1px Border Slate 200 */
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04) !important;
            }

            /* 4. Input Pencarian di Header: Lebih Rapi & Presisi */
            header input {
                border-radius: 10px !important;
                border: 1px solid #334155 !important;
                transition: all 0.15s ease !important;
            }
            header input:focus {
                border-color: #2563eb !important;
                box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15) !important;
            }

            /* ===================================================
               PENYESUAIAN WARNA MODE TERANG (LIGHT SLATE THEME)
               =================================================== */
                html.theme-light body {
                background-color: #f8fafc !important; /* Crisp Slate 50 - Bersih & Cerah */
                color: #0f172a !important;            /* Hitam Pekat Kontras Tinggi */
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

            /* Header Topbar & Aside Input Light Mode */
            html.theme-light header {
                background-color: #ffffff !important;
                border-color: #e2e8f0 !important;
            }
            html.theme-light header input,
            html.theme-light aside input {
                background-color: #f8fafc !important;
                border-color: #cbd5e1 !important;
                color: #0f172a !important;
            }
            html.theme-light header input::placeholder,
            html.theme-light aside input::placeholder {
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
            html.theme-light main .text-white:not(.scp-btn-gradient):not(.scp-btn-gradient *):not(button):not(button *):not(.table-page-active):not(.table-page-active *):not(.room-count-badge):not(.room-count-badge *) {
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

            /* Tombol Hapus Merah Solid (Kontras di Light & Dark Mode) */
            button.bg-red-600,
            html.theme-light button.bg-red-600 {
                background-color: #dc2626 !important;
                color: #ffffff !important;
                border-color: #b91c1c !important;
            }
            button.bg-red-600:hover,
            html.theme-light button.bg-red-600:hover {
                background-color: #b91c1c !important;
                color: #ffffff !important;
            }
            button.bg-red-600 svg,
            html.theme-light button.bg-red-600 svg {
                color: #ffffff !important;
                stroke: #ffffff !important;
            }

            /* Notifikasi Banner Kontras Tinggi */
            .flash-success {
                background-color: rgba(6, 78, 59, 0.85) !important;
                border: 1px solid #10b981 !important;
                border-left: 5px solid #34d399 !important;
                color: #ecfdf5 !important;
            }
            .flash-success svg {
                color: #34d399 !important;
            }
            .flash-error {
                background-color: rgba(127, 29, 29, 0.85) !important;
                border: 1px solid #ef4444 !important;
                border-left: 5px solid #f87171 !important;
                color: #fef2f2 !important;
            }
            .flash-error svg {
                color: #f87171 !important;
            }

            html.theme-light .flash-success {
                background-color: #ecfdf5 !important;
                border: 1px solid #6ee7b7 !important;
                border-left: 5px solid #059669 !important;
                color: #064e3b !important;
            }
            html.theme-light .flash-success svg {
                color: #059669 !important;
            }
            html.theme-light .flash-error {
                background-color: #fef2f2 !important;
                border: 1px solid #fca5a5 !important;
                border-left: 5px solid #dc2626 !important;
                color: #7f1d1d !important;
            }
            html.theme-light .flash-error svg {
                color: #dc2626 !important;
            }

            /* ===================================================
            BADGE & INDIKATOR KONTRAS TINGGI (MODE GELAP & TERANG)
               =================================================== */
            /* Badge Status Siklus & Kuncian */
            .badge-running, .badge-open {
                background-color: rgba(6, 78, 59, 0.75) !important;
                border: 1px solid #10b981 !important;
                color: #6ee7b7 !important;
            }
            .badge-locked {
                background-color: rgba(127, 29, 29, 0.75) !important;
                border: 1px solid #ef4444 !important;
                color: #fca5a5 !important;
            }
            html.theme-light .badge-running, 
            html.theme-light .badge-open {
                background-color: #d1fae5 !important;
                border: 1px solid #059669 !important;
                color: #064e3b !important;
            }
            html.theme-light .badge-locked {
                background-color: #fee2e2 !important;
                border: 1px solid #dc2626 !important;
                color: #7f1d1d !important;
            }

            /* Badge Akademik & Statistik */
            .badge-academic {
                background-color: rgba(30, 41, 59, 0.95) !important;
                border: 1px solid #64748b !important;
                color: #e2e8f0 !important;
            }
            html.theme-light .badge-academic {
                background-color: #f1f5f9 !important;
                border: 1px solid #94a3b8 !important;
                color: #0f172a !important;
            }

            /* Badge Aktif & Kelas */
            .badge-active, .badge-class {
                background-color: rgba(49, 46, 129, 0.75) !important;
                border: 1px solid #6366f1 !important;
                color: #c7d2fe !important;
            }
            html.theme-light .badge-active,
            html.theme-light .badge-class {
                background-color: #e0e7ff !important;
                border: 1px solid #818cf8 !important;
                color: #3730a3 !important;
            }

            /* Badge Peran Guru */
            .badge-role {
                background-color: rgba(51, 65, 85, 0.9) !important;
                border: 1px solid #64748b !important;
                color: #f8fafc !important;
            }
            html.theme-light .badge-role {
                background-color: #f1f5f9 !important;
                border: 1px solid #94a3b8 !important;
                color: #0f172a !important;
            }

            /* Badge Jumlah Jadwal */
            .badge-schedule-count {
                background-color: rgba(30, 41, 59, 0.95) !important;
                border: 1px solid #64748b !important;
                color: #e2e8f0 !important;
            }
            html.theme-light .badge-schedule-count {
                background-color: #f8fafc !important;
                border: 1px solid #cbd5e1 !important;
                color: #1e293b !important;
            }

            /* Box Banner Kondisi Ideal */
            .box-ideal {
                background-color: rgba(6, 78, 59, 0.4) !important;
                border: 1px solid rgba(16, 185, 129, 0.6) !important;
                border-left: 5px solid #10b981 !important;
                color: #d1fae5 !important;
            }
            html.theme-light .box-ideal {
                background-color: #ecfdf5 !important;
                border: 1px solid #34d399 !important;
                border-left: 5px solid #059669 !important;
                color: #064e3b !important;
            }

            /* Tombol Aksi Hapus & Lihat Foto */
            .btn-action-delete {
                background-color: rgba(127, 29, 29, 0.55) !important;
                border: 1px solid #ef4444 !important;
                color: #fca5a5 !important;
            }
            .btn-action-delete:hover {
                background-color: #dc2626 !important;
                color: #ffffff !important;
                border-color: #dc2626 !important;
            }
            html.theme-light .btn-action-delete {
                background-color: #fee2e2 !important;
                border: 1px solid #f87171 !important;
                color: #991b1b !important;
            }
            html.theme-light .btn-action-delete:hover {
                background-color: #dc2626 !important;
                color: #ffffff !important;
                border-color: #dc2626 !important;
            }

            .btn-photo {
                background-color: rgba(49, 46, 129, 0.75) !important;
                border: 1px solid #6366f1 !important;
                color: #c7d2fe !important;
            }

                        /* Tombol Aksi Lihat & Edit */
            .btn-action-view {
                background-color: rgba(14, 116, 144, 0.45) !important;
                border: 1px solid #06b6d4 !important;
                color: #a5f3fc !important;
            }
            .btn-action-view:hover {
                background-color: #0891b2 !important;
                color: #ffffff !important;
            }
            html.theme-light .btn-action-view {
                background-color: #e0f2fe !important;
                border: 1px solid #38bdf8 !important;
                color: #0369a1 !important;
            }
            html.theme-light .btn-action-view:hover {
                background-color: #0284c7 !important;
                color: #ffffff !important;
            }

            .btn-action-edit {
                background-color: rgba(79, 70, 229, 0.45) !important;
                border: 1px solid #6366f1 !important;
                color: #c7d2fe !important;
            }
            .btn-action-edit:hover {
                background-color: #4f46e5 !important;
                color: #ffffff !important;
            }
            html.theme-light .btn-action-edit {
                background-color: #ede9fe !important;
                border: 1px solid #a78bfa !important;
                color: #5b21b6 !important;
            }
            html.theme-light .btn-action-edit:hover {
                background-color: #7c3aed !important;
                color: #ffffff !important;
            }

            /* Tombol / Indikator Jadwal Terkunci (Merah Menyala Mencolok di Kedua Mode) */
            .btn-schedule-locked,
            .btn-schedule-locked *,
            html.theme-light .btn-schedule-locked,
            html.theme-light .btn-schedule-locked * {
                background-color: #dc2626 !important;
                border: 1px solid #b91c1c !important;
                color: #ffffff !important;
                box-shadow: 0 4px 14px rgba(220, 38, 38, 0.4) !important;
            }
            .btn-schedule-locked:hover,
            .btn-schedule-locked:hover *,
            html.theme-light .btn-schedule-locked:hover,
            html.theme-light .btn-schedule-locked:hover * {
                background-color: #b91c1c !important;
                color: #ffffff !important;
            }

            .btn-photo:hover {
                background-color: #4f46e5 !important;
                color: #ffffff !important;
                border-color: #4f46e5 !important;
            }
            html.theme-light .btn-photo {
                background-color: #e0e7ff !important;
                border: 1px solid #818cf8 !important;
                color: #3730a3 !important;
            }
            html.theme-light .btn-photo:hover {
                background-color: #4f46e5 !important;
                color: #ffffff !important;
                border-color: #4f46e5 !important;
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

            /* Input Form & Dropdown Light Mode (Kecuali Checkbox & Radio yang Memiliki Desain Khusus) */
            html.theme-light main input:not([type="checkbox"]):not([type="radio"]),
            html.theme-light main select,
            html.theme-light main textarea,
            html.theme-light [x-show="addModalOpen"] input:not([type="checkbox"]):not([type="radio"]),
            html.theme-light [x-show="addModalOpen"] select {
                background-color: #ffffff !important; /* Putih Solid */
                border: 1px solid #cbd5e1 !important; /* Border Halus Slate 300 */
                color: #0f172a !important;            /* Teks Hitam Jelas */
                border-radius: 10px !important;
                transition: all 0.15s ease !important;
            }
            html.theme-light main input:not([type="checkbox"]):not([type="radio"]):focus,
            html.theme-light main select:focus,
            html.theme-light main textarea:focus {
                border-color: #2563eb !important;     /* Aksen Royal Blue */
                box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15) !important;
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
               MODAL DETAIL PROFIL (GURU & SISWA) DI LIGHT MODE
               =================================================== */
            /* 1. Username Siswa & @handle Guru di Kartu Atas */
            html.theme-light [x-show="viewModalOpen"] p.text-indigo-400,
            html.theme-light [x-show="viewModalOpen"] .text-indigo-400 {
                color: #4338ca !important; /* Deep Indigo 700 - sangat tajam & terbaca jelas */
                font-weight: 700 !important;
            }

            /* 2. Label Kolom (NIS, NIP, Tanggal Lahir, Orang Tua, Email, dll) */
            html.theme-light [x-show="viewModalOpen"] span.text-slate-400 {
                color: #475569 !important; /* Slate 600 - abu-abu tegas untuk label */
                font-weight: 700 !important;
            }

            /* 3. Nilai/Data Teks (Nilai NIS, NIP, Tanggal Lahir, Orang Tua, Email Resmi) */
            html.theme-light [x-show="viewModalOpen"] span.text-white,
            html.theme-light [x-show="viewModalOpen"] p.text-slate-200 {
                color: #0f172a !important; /* Hitam pekat kontras tinggi */
                font-weight: 700 !important;
            }

            /* 4. Nomor HP / WhatsApp */
            html.theme-light [x-show="viewModalOpen"] span.text-emerald-400 {
                color: #047857 !important; /* Hijau Zamrud Pekat (Emerald 700) */
                font-weight: 700 !important;
            }

            /* 5. Tombol Tutup Tampilan di Bawah Modal */
            html.theme-light [x-show="viewModalOpen"] button.bg-slate-800 {
                background-color: #e2e8f0 !important;
                color: #0f172a !important;
                border: 1px solid #cbd5e1 !important;
            }
            html.theme-light [x-show="viewModalOpen"] button.bg-slate-800:hover {
                background-color: #cbd5e1 !important;
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
            html.theme-light table button.text-red-400 {
                color: #dc2626 !important;
                border-color: #fca5a5 !important;
            }
            html.theme-light table button.text-red-400:hover {
                background-color: #dc2626 !important;
                color: #ffffff !important;
            }

            /* Tombol Logout di Aside Light Mode */
            html.theme-light aside button.text-red-400 {
                color: #dc2626 !important;
                border-color: transparent !important;
            }
            html.theme-light aside button.text-red-400:hover {
                background-color: #fee2e2 !important;
                color: #b91c1c !important;
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

            /* ===================================================
               SEMBUNYIKAN INDIKATOR SCROLLBAR (TETAP BISA SCROLL)
               =================================================== */
            /* Browser WebKit/Blink (Chrome, Edge, Safari, Opera) */
            html::-webkit-scrollbar,
            body::-webkit-scrollbar,
            *::-webkit-scrollbar {
                display: none !important;
                width: 0 !important;
                height: 0 !important;
            }

            /* Browser Firefox & IE/Edge */
            html,
            body,
            * {
                -ms-overflow-style: none !important; /* IE dan Edge */
                scrollbar-width: none !important;    /* Firefox */
            }

                        /* ===================================================
               FOTO 1: MODAL KONFIRMASI GLOBAL DI LIGHT MODE
               =================================================== */
            html.theme-light .scp-confirm-box {
                background-color: #ffffff !important;
                border-color: #cbd5e1 !important;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15) !important;
            }
            html.theme-light .scp-confirm-box h3 {
                color: #0f172a !important;
            }
            html.theme-light .scp-confirm-box p {
                color: #475569 !important;
            }
            html.theme-light .scp-confirm-box button.bg-slate-800 {
                background-color: #f1f5f9 !important;
                border-color: #cbd5e1 !important;
                color: #334155 !important;
            }
            html.theme-light .scp-confirm-box button.bg-slate-800:hover {
                background-color: #e2e8f0 !important;
                color: #0f172a !important;
            }

            /* ===================================================
               FOTO 2: BANNER TAHUN AJARAN DI LIGHT MODE
               =================================================== */
            html.theme-light .academic-banner {
                background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 50%, #f8fafc 100%) !important;
                border-color: #c7d2fe !important;
                box-shadow: 0 4px 20px -2px rgba(99, 102, 241, 0.12) !important;
            }
            html.theme-light .academic-banner .text-slate-300,
            html.theme-light .academic-banner .text-slate-400 {
                color: #475569 !important;
            }
            html.theme-light .academic-banner .text-indigo-400 {
                color: #4338ca !important;
            }
            html.theme-light .academic-banner .academic-stat-box {
                background-color: #ffffff !important;
                border-color: #cbd5e1 !important;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04) !important;
            }
            html.theme-light .academic-banner .academic-stat-box span.text-slate-400 {
                color: #64748b !important;
            }
            html.theme-light .academic-banner .academic-stat-box span.text-white {
                color: #0f172a !important;
            }

            /* Perbaikan Kontras Badge Periode Sedang Berjalan (Foto 4) */
            html.theme-light .academic-banner .bg-emerald-500\/20 {
                background-color: #d1fae5 !important;
                border-color: #6ee7b7 !important;
                color: #065f46 !important; /* Hijau Zamrud Gelap (Emerald 800) Sangat Tegas */
            }
            html.theme-light .academic-banner .bg-emerald-500\/20 span {
                color: #065f46 !important;
            }
            html.theme-light .academic-banner span.text-slate-400 {
                color: #475569 !important; /* Abu-abu Slate Tegas */
            }

            /* ===================================================
               FOTO 3: BANNER NOTES JAM PEMBELAJARAN DI LIGHT MODE
               =================================================== */
            html.theme-light .lesson-notes-banner {
                background: linear-gradient(135deg, #f8fafc 0%, #eef2f6 50%, #f1f5f9 100%) !important;
                border-color: #cbd5e1 !important;
                box-shadow: 0 4px 14px rgba(0, 0, 0, 0.05) !important;
            }
            html.theme-light .lesson-notes-banner .border-b {
                border-color: #cbd5e1 !important;
            }
            html.theme-light .lesson-notes-banner h2.text-amber-400 {
                color: #b45309 !important; /* Amber 700 - tajam di mode terang */
            }
            html.theme-light .lesson-notes-banner span.text-slate-400 {
                color: #475569 !important;
            }
            html.theme-light .lesson-notes-banner [class*="bg-slate-900"] {
                background-color: #ffffff !important;
                border-color: #cbd5e1 !important;
                box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04) !important;
            }
            html.theme-light .lesson-notes-banner [class*="bg-slate-900"] span.text-slate-400 {
                color: #64748b !important;
            }
            html.theme-light .lesson-notes-banner [class*="bg-slate-900"] p.text-white {
                color: #0f172a !important;
            }
            html.theme-light .lesson-notes-banner [class*="bg-slate-900"] span.text-amber-300 {
                color: #b45309 !important;
            }
            html.theme-light .lesson-notes-banner [class*="bg-slate-900"] p.text-indigo-300 {
                color: #4338ca !important;
            }
            html.theme-light .lesson-notes-banner [class*="bg-slate-900"] span.text-slate-500 {
                color: #64748b !important;
            }
            html.theme-light .lesson-notes-banner [class*="bg-slate-900"] p.text-slate-300 {
                color: #334155 !important;
            }
            html.theme-light .lesson-notes-banner [class*="bg-slate-900"] p.text-slate-300 strong {
                color: #0f172a !important;
            }
            html.theme-light main .text-indigo-300 {
                color: #4338ca !important;
            }

            /* ===================================================
               FOTO 4: CHECKBOX DI LIGHT MODE (Jelas & Kontras)
               =================================================== */
            html.theme-light input[type="checkbox"] {
                background-color: #ffffff !important;
                border: 2px solid #64748b !important;
                accent-color: #4f46e5 !important;
                cursor: pointer !important;
            }
            html.theme-light input[type="checkbox"]:checked {
                background-color: #4f46e5 !important;
                border-color: #4f46e5 !important;
            }

            /* ===================================================
               INDIKATOR RADIO PILIHAN DUPLIKAT IMPORT (FOTO 1 & FOTO 2)
               (Berlaku di Data Siswa, Guru, Jadwal Mengajar, Ruangan)
               =================================================== */
            .import-dup-radio,
            html.theme-light .import-dup-radio,
            html.dark .import-dup-radio {
                appearance: none !important;
                -webkit-appearance: none !important;
                width: 1.125rem !important; /* 18px */
                height: 1.125rem !important; /* 18px */
                border-radius: 9999px !important;
                display: inline-grid !important;
                place-content: center !important;
                cursor: pointer !important;
                margin-top: 0.125rem !important;
                flex-shrink: 0 !important;
                transition: all 0.15s ease !important;
                box-sizing: border-box !important;
            }

            /* Unchecked di Dark Mode: Lingkaran putih bersih seperti Foto 2 kiri */
            html.dark .import-dup-radio,
            .import-dup-radio {
                background-color: #ffffff !important;
                border: 2px solid #ffffff !important;
            }

            /* Unchecked di Light Mode: Lingkaran putih dengan border slate seperti Foto 1 kiri */
            html.theme-light .import-dup-radio {
                background-color: #ffffff !important;
                border: 2px solid #94a3b8 !important;
            }

            /* Checked di Mode Gelap & Mode Terang: Lingkaran Indigo 600 (#4f46e5) seperti Foto 2 kanan */
            .import-dup-radio:checked,
            html.theme-light .import-dup-radio:checked,
            html.dark .import-dup-radio:checked {
                background-color: #4f46e5 !important;
                border: 2px solid #4f46e5 !important;
                box-shadow: 0 0 0 1px #4f46e5 !important;
            }

            /* Titik Tengah Putih (Checked Dot) yang Presisi */
            .import-dup-radio::before,
            html.theme-light .import-dup-radio::before,
            html.dark .import-dup-radio::before {
                content: "" !important;
                width: 0.45rem !important; /* 7-8px */
                height: 0.45rem !important;
                border-radius: 50% !important;
                background-color: #ffffff !important;
                transform: scale(0) !important;
                transition: 0.12s transform ease-in-out !important;
            }

            .import-dup-radio:checked::before,
            html.theme-light .import-dup-radio:checked::before,
            html.dark .import-dup-radio:checked::before {
                transform: scale(1) !important;
            }

            /* ===================================================
               PILIHAN JUMLAH BARIS PER HALAMAN (50, 100, 500, 1000)
               =================================================== */
            .table-rows-btn {
                transition: all 0.15s ease !important;
            }
            html.theme-light .table-rows-btn {
                background-color: #ffffff !important;
                border: 1px solid #cbd5e1 !important;
                color: #0f172a !important;
            }
            html.theme-light .table-rows-btn:hover {
                background-color: #f1f5f9 !important;
                border-color: #94a3b8 !important;
            }
            html.theme-light .table-rows-popover {
                background-color: #ffffff !important;
                border: 1px solid #cbd5e1 !important;
                box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
            }
            html.theme-light .table-rows-popover a,
            html.theme-light .table-rows-popover button {
                color: #334155 !important;
            }
            html.theme-light .table-rows-popover a:hover,
            html.theme-light .table-rows-popover button:hover {
                background-color: #4f46e5 !important;
                color: #ffffff !important;
            }
            html.theme-light .table-rows-popover a.is-active,
            html.theme-light .table-rows-popover button.is-active {
                background-color: #ede9fe !important;
                color: #4f46e5 !important;
                font-weight: 700 !important;
            }
            html.theme-light .table-rows-popover .border-b {
                border-color: #e2e8f0 !important;
            }

            /* Tombol Navigasi Halaman Ringkas (Table Pagination Buttons) */
            .table-page-btn, .table-page-active, .table-page-disabled {
                transition: all 0.15s ease !important;
            }
            html.theme-light .table-page-btn {
                background-color: #ffffff !important;
                border: 1px solid #cbd5e1 !important;
                color: #334155 !important;
            }
            html.theme-light .table-page-btn:hover {
                background-color: #f1f5f9 !important;
                border-color: #94a3b8 !important;
                color: #0f172a !important;
            }
            .table-page-active,
            html.theme-light main .table-page-active,
            html.theme-light .table-page-active,
            html.theme-light span.table-page-active {
                background-color: #2563eb !important;
                border: 1px solid #1d4ed8 !important;
                color: #ffffff !important;
                font-weight: 800 !important;
            }
            .table-page-active *,
            html.theme-light main .table-page-active *,
            html.theme-light .table-page-active * {
                color: #ffffff !important;
            }
            html.theme-light .table-page-disabled {
                background-color: #f8fafc !important;
                border: 1px solid #e2e8f0 !important;
                color: #94a3b8 !important;
            }
            html.theme-light .table-page-dots {
                color: #64748b !important;
            }

            /* ===================================================
               KOTAK PERINGATAN MODAL HAPUS DI MODE TERANG
               (bg-rose-950, text-rose-200, text-rose-300, text-rose-400)
               =================================================== */
            html.theme-light [class*="bg-rose-950"] {
                background-color: #fff1f2 !important; /* Soft rose / red light background */
                border-color: #fecdd3 !important;      /* Soft rose border */
                color: #9f1239 !important;             /* Dark rose / crimson text */
            }
            html.theme-light [class*="bg-rose-950"] * {
                color: #9f1239 !important;
            }
            html.theme-light [class*="bg-rose-950"] strong {
                color: #881337 !important;             /* Darker crimson strong text */
                font-weight: 800 !important;
            }
            html.theme-light .text-rose-200,
            html.theme-light .text-rose-300 {
                color: #9f1239 !important;
            }
            html.theme-light .text-rose-400 {
                color: #be123c !important;             /* Crisp dark rose */
            }
            html.theme-light [class*="border-rose-800"] {
                border-color: #fecdd3 !important;
            }

            /* ===================================================
               TRANSFORMASI WARNA KUNING DI MODE TERANG (SEMUA ROLE)
               (Kuning terang diubah menjadi Amber pekat kontras tinggi)
               =================================================== */
            html.theme-light .text-amber-300,
            html.theme-light .text-amber-400,
            html.theme-light .text-yellow-300,
            html.theme-light .text-yellow-400,
            html.theme-light main .text-amber-300,
            html.theme-light main .text-amber-400,
            html.theme-light main .text-yellow-300,
            html.theme-light main .text-yellow-400 {
                color: #b45309 !important; /* Deep Amber 700 - tajam & kontras tinggi */
                font-weight: 700 !important;
            }

            html.theme-light .bg-amber-500\/20,
            html.theme-light .bg-amber-500\/10,
            html.theme-light .bg-yellow-500\/20,
            html.theme-light .bg-yellow-500\/10 {
                background-color: #fef3c7 !important; /* Amber 100 Lembut */
                border-color: #fcd34d !important;     /* Amber 300 Tegas */
                color: #92400e !important;            /* Amber 800 */
            }

            html.theme-light .bg-amber-500\/20 *,
            html.theme-light .bg-yellow-500\/20 * {
                color: #92400e !important;
            }

            /* ===================================================
               BADGE KHUSUS MODAL DETAIL JADWAL RUANGAN
               (Kontras Tinggi & Warna Berbeda Antar Kolom di Light/Dark)
               =================================================== */
            .room-count-badge,
            html.theme-light .room-count-badge,
            html.theme-light [x-show="viewModalOpen"] .room-count-badge,
            html.theme-light [x-show="viewModalOpen"] span.room-count-badge {
                background-color: #2563eb !important; /* Biru Solid */
                color: #ffffff !important;            /* Teks Putih Pekat */
                font-weight: 800 !important;
                border: none !important;
            }
            .room-period-badge,
            html.theme-light .room-period-badge {
                background-color: #e0f2fe !important; /* Sky 100 */
                color: #334155 !important;            /* Abu-abu Slate 700 Jelas & Elegan (Foto 3) */
                border: 1px solid #7dd3fc !important; /* Sky 300 */
                font-weight: 800 !important;
            }
            html.dark .room-period-badge {
                background-color: rgba(3, 105, 161, 0.25) !important;
                color: #7dd3fc !important;
                border: 1px solid rgba(3, 105, 161, 0.5) !important;
            }
            .room-class-badge,
            html.theme-light .room-class-badge {
                background-color: #ede9fe !important; /* Indigo 100 */
                color: #334155 !important;            /* Abu-abu Slate 700 Jelas & Elegan (Foto 3) */
                border: 1px solid #a5b4fc !important; /* Indigo 300 */
                font-weight: 800 !important;
            }
            html.dark .room-class-badge {
                background-color: rgba(67, 56, 202, 0.3) !important;
                color: #c7d2fe !important;
                border: 1px solid rgba(67, 56, 202, 0.6) !important;
            }
            /* Siklus: Teks Abu-abu di Mode Terang, Emas di Mode Gelap */
            .room-cycle-badge,
            html.theme-light .room-cycle-badge {
                background-color: #f1f5f9 !important; /* Slate 100 Bersih */
                color: #334155 !important;            /* Abu-abu Slate 700 Jelas & Elegan (Foto 3) */
                border: 1px solid #cbd5e1 !important; /* Slate 300 */
                font-weight: 800 !important;
            }
            html.dark .room-cycle-badge {
                background-color: rgba(217, 119, 6, 0.25) !important;
                color: #fde68a !important;
                border: 1px solid rgba(217, 119, 6, 0.4) !important;
            }

            /* Container Tabel Modal Detail Ruangan (Dark & Light Mode Konsisten) */
            .room-table-container {
                background-color: #ffffff !important;
                border: 1px solid #e2e8f0 !important;
            }
            html.dark .room-table-container {
                background-color: #0f172a !important;
                border: 1px solid #1e293b !important;
            }
            html.dark .room-table-container table thead {
                background-color: #1e293b !important;
                border-bottom: 1px solid #334155 !important;
            }
            html.dark .room-table-container table th {
                color: #e2e8f0 !important;
            }
            html.dark .room-table-container table tbody tr {
                background-color: #0f172a !important;
                color: #e2e8f0 !important;
            }
            html.dark .room-table-container table tbody tr:nth-child(even) {
                background-color: #162032 !important;
            }
            html.dark .room-table-container table td {
                border-color: #1e293b !important;
            }
            html.dark .room-table-container table td span.text-slate-900,
            html.dark .room-table-container table td.text-slate-900 {
                color: #ffffff !important;
            }
            html.dark .room-table-container table td.text-slate-700 {
                color: #cbd5e1 !important;
            }

            html.theme-light .room-table-container table thead {
                background-color: #f8fafc !important;
                border-bottom: 1px solid #e2e8f0 !important;
            }
            html.theme-light .room-table-container table th {
                color: #334155 !important;
            }
            html.theme-light .room-table-container table tbody tr {
                background-color: #ffffff !important;
                color: #0f172a !important;
            }
            html.theme-light .room-table-container table tbody tr:nth-child(even) {
                background-color: #f8fafc !important;
            }
            html.theme-light .room-table-container table td {
                border-color: #e2e8f0 !important;
            }
            html.theme-light .room-table-container table td span.text-slate-900,
            html.theme-light .room-table-container table td.text-slate-900 {
                color: #0f172a !important;
            }

            /* Tombol Sinkronisasi Orang Tua (Solid Royal Blue Seperti Tombol Hapus Semua) */
            .sync-parent-btn,
            html.theme-light .sync-parent-btn,
            html.dark .sync-parent-btn {
                background-color: #2563eb !important;
                color: #ffffff !important;
                border: none !important;
            }
            .sync-parent-btn:hover,
            html.theme-light .sync-parent-btn:hover,
            html.dark .sync-parent-btn:hover {
                background-color: #1d4ed8 !important;
                color: #ffffff !important;
            }
            .sync-parent-btn:active,
            html.theme-light .sync-parent-btn:active,
            html.dark .sync-parent-btn:active {
                background-color: #1e40af !important;
                color: #ffffff !important;
            }
            .sync-parent-btn svg,
            .sync-parent-btn span,
            html.theme-light .sync-parent-btn svg,
            html.theme-light .sync-parent-btn span,
            html.dark .sync-parent-btn svg,
            html.dark .sync-parent-btn span {
                color: #ffffff !important;
            }

            /* ===================================================
               FORMAT CETAK REKAP A4 SIMETRIS & RAPI (SEMUA ROLE)
               =================================================== */
            @media print {
                @page {
                    size: A4 portrait;
                    margin: 10mm 10mm 12mm 10mm;
                }
                *, *:before, *:after {
                    box-shadow: none !important;
                    text-shadow: none !important;
                }
                html, 
                html.dark, 
                html.theme-light, 
                body {
                    background-color: #ffffff !important;
                    background: #ffffff !important;
                    color: #000000 !important;
                    font-size: 10pt !important;
                    line-height: 1.3 !important;
                    width: 100% !important;
                    margin: 0 !important;
                    padding: 0 !important;
                }
                aside, 
                header, 
                nav, 
                .theme-toggle-btn,
                .print\:hidden {
                    display: none !important;
                }
                main {
                    padding: 0 !important;
                    margin: 0 !important;
                    width: 100% !important;
                    max-width: 100% !important;
                    background-color: #ffffff !important;
                    background: #ffffff !important;
                }
                /* Reset Semua Kontainer & Kartu Gelap saat Print (Foto 2) */
                .bg-\[\#0f172a\],
                .bg-\[\#0f172a\]\/90,
                .bg-slate-900,
                .bg-slate-950,
                [class*="bg-slate-900"],
                [class*="bg-\[\#0f172a\]"] {
                    background-color: #ffffff !important;
                    background: #ffffff !important;
                    border: none !important;
                    box-shadow: none !important;
                }
                .print-full-width,
                table {
                    width: 100% !important;
                    max-width: 100% !important;
                    table-layout: fixed !important;
                    border-collapse: collapse !important;
                    background-color: #ffffff !important;
                    background: #ffffff !important;
                }
                table thead,
                table thead tr,
                table thead th {
                    background-color: #f3f4f6 !important;
                    background: #f3f4f6 !important;
                    color: #000000 !important;
                    border: 1px solid #9ca3af !important;
                }
                table tbody,
                table tbody tr,
                table tbody td {
                    background-color: #ffffff !important;
                    background: #ffffff !important;
                    color: #000000 !important;
                    border: 1px solid #9ca3af !important;
                }
                table td div,
                table td span {
                    background-color: transparent !important;
                    background: transparent !important;
                }
                th {
                    word-break: keep-all !important;
                    overflow-wrap: normal !important;
                    word-wrap: normal !important;
                    letter-spacing: normal !important;
                    text-align: center !important;
                    vertical-align: middle !important;
                }
                th.nowrap-col,
                .nowrap-col {
                    white-space: nowrap !important;
                }
                td {
                    word-wrap: break-word !important;
                    overflow-wrap: break-word !important;
                    white-space: normal !important;
                }
                td.student-name-col,
                td.student-name-col * {
                    word-break: normal !important;
                    word-wrap: normal !important;
                    overflow-wrap: normal !important;
                    white-space: normal !important;
                    hyphens: none !important;
                }
                .page-break-inside-avoid {
                    page-break-inside: avoid !important;
                    break-inside: avoid !important;
                }
            }

            /* Pastikan Sidebar Desktop Selalu Terkunci Permanen di Viewport (Anti-Scroll) */
            @media (min-width: 1024px) {
                aside {
                    position: fixed !important;
                    top: 0 !important;
                    bottom: 0 !important;
                    left: 0 !important;
                    height: 100vh !important;
                    height: 100dvh !important;
                    transform: none !important;
                    z-index: 50 !important;
                }
            }

        </style>
    </head>
    <body class="min-h-screen text-slate-100 antialiased font-sans transition-colors duration-200">
    <!-- Indikator Loading Bar Navigasi Instan untuk Seluruh Role (Klik 1x Langsung Merespons) -->
        <div id="scp-page-loader" class="fixed top-0 left-0 right-0 h-[3px] z-[9999] pointer-events-none opacity-0 transition-opacity duration-150">
            <div id="scp-page-progress" class="h-full bg-blue-600 w-0 transition-all duration-300 ease-out shadow-sm shadow-blue-500/30"></div>
        </div>
    
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
             x-init="$watch('sidebarOpen', val => { 
                 if (val) {
                     document.body.style.overflow = 'hidden';
                 } else {
                     document.body.style.overflow = '';
                 }
             })"
             class="flex min-h-screen w-full relative">
            
            <!-- BACKDROP GELAP DI LAYAR MOBILE -->
            <div x-show="sidebarOpen" 
                 @click="sidebarOpen = false" 
                 @touchmove.prevent
                 x-cloak 
                 class="fixed inset-0 bg-black/70 backdrop-blur-sm z-40 lg:hidden transition-opacity">
            </div>

            <!-- SIDEBAR RESPONSIVE (Dinamis Sesuai Role Pengguna) -->
            <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:transform-none'"
                   class="fixed inset-y-0 left-0 z-50 w-72 max-w-[85vw] h-full h-[100dvh] lg:w-64 lg:max-w-none bg-[#0c1322] border-r border-slate-800 flex flex-col justify-between transition-transform duration-300 ease-in-out shadow-2xl lg:shadow-none overscroll-contain">
                
                <div class="p-5 flex flex-col h-full overflow-y-auto overscroll-contain no-scrollbar">
                    <!-- Brand / Logo & Subtitle Role -->
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center gap-3">
                            <img src="{{ asset('images/logo-sm.png') }}?v=20261107" alt="Logo SCP" class="h-10 w-10 object-contain shrink-0 drop-shadow-md" />
                            <div>
                                <h2 class="text-base font-extrabold text-white leading-tight">School Class Progress</h2>
                                <p class="text-[11px] font-bold text-indigo-400 tracking-wider uppercase">
                                    {{ auth()->user()->role?->role_name ?? 'PORTAL AKADEMIK' }}
                                </p>
                            </div>
                        </div>

                        <!-- Tombol Silang [X] hanya di Mobile -->
                        <button @click="sidebarOpen = false" 
                                type="button" 
                                class="lg:hidden p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition cursor-pointer shrink-0"
                                title="Tutup Menu">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>



                                        <!-- Navigation Links Berdasarkan Role -->
                    <nav class="space-y-6 flex-1">

                                                {{-- ======================================================== --}}
                        {{-- 1. MENU GURU PENGAJAR (GURU, WALI KELAS, & GURU BK)      --}}
                        {{-- Guru BK dan Wali Kelas otomatis mendapat menu mengajar   --}}
                        {{-- ======================================================== --}}
                        @if(in_array(auth()->user()->role?->role_name, ['Guru', 'Wali Kelas', 'Guru BK']))
                            <div>
                                <p class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider px-3 mb-2">MENU GURU</p>
                                <div class="space-y-1">
                                    <a href="{{ route('teacher.dashboard') }}" 
                                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition {{ request()->routeIs('teacher.dashboard') ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-600/30' : 'text-slate-100 hover:bg-slate-800/80 hover:text-white font-semibold' }}">
                                        <svg class="w-4 h-4 {{ request()->routeIs('teacher.dashboard') ? 'text-white' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                                        Dashboard
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
                                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition {{ request()->routeIs('teacher.journals.create') ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-600/30' : 'text-slate-100 hover:bg-slate-800/80 hover:text-white font-semibold' }}">
                                        <svg class="w-4 h-4 {{ request()->routeIs('teacher.journals.create') ? 'text-white' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        Isi Jurnal & Presensi
                                    </a>

                                    <a href="{{ route('teacher.journals.history') }}" 
                                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition {{ request()->routeIs('teacher.journals.history') ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-600/30' : 'text-slate-100 hover:bg-slate-800/80 hover:text-white font-semibold' }}">
                                        <svg class="w-4 h-4 {{ request()->routeIs('teacher.journals.history') ? 'text-white' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        Riwayat Jurnal KBM
                                    </a>
                                </div>
                            </div>
                        @endif

                        {{-- ======================================================== --}}
                        {{-- 2. MENU KESISWAAN (WALI KELAS & GURU BK)                 --}}
                        {{-- Wali Kelas: Verifikasi, Riwayat, Rekap Presensi           --}}
                        {{-- Guru BK: Rekap Izin Siswa & Rekap Presensi               --}}
                        {{-- ======================================================== --}}
                        @if(in_array(auth()->user()->role?->role_name, ['Wali Kelas', 'Guru BK']))
                            <div>
                                <p class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider px-3 mb-2">
                                    {{ auth()->user()->role?->role_name === 'Guru BK' ? 'KESISWAAN & BK' : 'KESISWAAN & PERWALIAN' }}
                                </p>
                                <div class="space-y-1">
                                    <!-- 1. Verifikasi Izin Siswa (Dapat Diverifikasi oleh Wali Kelas & Guru BK) -->
                                    <a href="{{ route('student-affairs.leaves.pending') }}" 
                                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition {{ request()->routeIs('student-affairs.leaves.pending') || request()->routeIs('student-affairs.dashboard') ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-600/30' : 'text-slate-100 hover:bg-slate-800/80 hover:text-white font-semibold' }}">
                                        <svg class="w-4 h-4 {{ request()->routeIs('student-affairs.leaves.pending') || request()->routeIs('student-affairs.dashboard') ? 'text-white' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        <span>Verifikasi Izin Siswa</span>
                                    </a>

                                    <!-- 2. Rekapan Izin Siswa (Telah Diverifikasi Wali Kelas) -->
                                    <a href="{{ route('student-affairs.leaves.history') }}" 
                                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition {{ request()->routeIs('student-affairs.leaves.history') ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-600/30' : 'text-slate-100 hover:bg-slate-800/80 hover:text-white font-semibold' }}">
                                        <svg class="w-4 h-4 {{ request()->routeIs('student-affairs.leaves.history') ? 'text-white' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        <span>{{ auth()->user()->role?->role_name === 'Guru BK' ? 'Rekap Izin Siswa' : 'Riwayat Izin Siswa' }}</span>
                                    </a>

                                    <!-- 3. Rekap Presensi Siswa -->
                                    <a href="{{ route('student-affairs.attendance') }}" 
                                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition {{ request()->routeIs('student-affairs.attendance') ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-600/30' : 'text-slate-100 hover:bg-slate-800/80 hover:text-white font-semibold' }}">
                                        <svg class="w-4 h-4 {{ request()->routeIs('student-affairs.attendance') ? 'text-white' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                                        <span>Rekap Presensi Siswa</span>
                                    </a>
                                </div>
                            </div>
                        @endif

                        {{-- ======================================================== --}}
                        {{-- 3. MENU WAKA KURIKULUM                                   --}}
                        {{-- ======================================================== --}}
                        @if(auth()->user()->role?->role_name === 'Waka Kurikulum')
                            <div>
                                <p class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider px-3 mb-2">MENU UTAMA</p>
                                <div class="space-y-1">
                                    <a href="{{ route('waka.dashboard') }}" 
                                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition {{ request()->routeIs('waka.dashboard') ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-600/30' : 'text-slate-100 hover:bg-slate-800/80 hover:text-white font-semibold' }}">
                                        <svg class="w-4 h-4 {{ request()->routeIs('waka.dashboard') ? 'text-white' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                                        Dashboard
                                    </a>

                                    <a href="{{ route('waka.cycle.index') }}" 
                                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition {{ request()->routeIs('waka.cycle.*') ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-600/30' : 'text-slate-100 hover:bg-slate-800/80 hover:text-white font-semibold' }}">
                                        <svg class="w-4 h-4 {{ request()->routeIs('waka.cycle.*') ? 'text-white' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                        Siklus Blok
                                    </a>
                                    
                                    <a href="{{ route('waka.journals.index') }}" 
                                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition {{ request()->routeIs('waka.journals.*') ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-600/30' : 'text-slate-100 hover:bg-slate-800/80 hover:text-white font-semibold' }}">
                                        <svg class="w-4 h-4 {{ request()->routeIs('waka.journals.*') ? 'text-white' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        Monitoring Jurnal
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
                                    <a href="{{ route('waka.academic-year.index') }}" 
                                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition {{ request()->routeIs('waka.academic-year.*') ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-600/30' : 'text-slate-100 hover:bg-slate-800/80 hover:text-white font-semibold' }}">
                                        <svg class="w-4 h-4 {{ request()->routeIs('waka.academic-year.*') ? 'text-white' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        Tahun Ajaran
                                    </a>

                                    <a href="{{ route('waka.lesson-hours.index') }}" 
                                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition {{ request()->routeIs('waka.lesson-hours.*') ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-600/30' : 'text-slate-100 hover:bg-slate-800/80 hover:text-white font-semibold' }}">
                                        <svg class="w-4 h-4 {{ request()->routeIs('waka.lesson-hours.*') ? 'text-white' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        Jam Pembelajaran
                                    </a>

                                    <a href="{{ route('waka.majors.index') }}" 
                                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition {{ request()->routeIs('waka.majors.*') ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-600/30' : 'text-slate-100 hover:bg-slate-800/80 hover:text-white font-semibold' }}">
                                        <svg class="w-4 h-4 {{ request()->routeIs('waka.majors.*') ? 'text-white' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                        Data Jurusan
                                    </a>

                                    <a href="{{ route('waka.classes.index') }}" 
                                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition {{ request()->routeIs('waka.classes.*') ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-600/30' : 'text-slate-100 hover:bg-slate-800/80 hover:text-white font-semibold' }}">
                                        <svg class="w-4 h-4 {{ request()->routeIs('waka.classes.*') ? 'text-white' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                                        Data Perkelas
                                    </a>

                                    <a href="{{ route('waka.grades.index') }}" 
                                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition {{ request()->routeIs('waka.grades.*') ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-600/30' : 'text-slate-100 hover:bg-slate-800/80 hover:text-white font-semibold' }}">
                                        <svg class="w-4 h-4 {{ request()->routeIs('waka.grades.*') ? 'text-white' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path></svg>
                                        Data Tingkat & Kelulusan
                                    </a>

                                    <a href="{{ route('waka.schedules.index') }}" 
                                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition {{ request()->routeIs('waka.schedules.*') ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-600/30' : 'text-slate-100 hover:bg-slate-800/80 hover:text-white font-semibold' }}">
                                        <svg class="w-4 h-4 {{ request()->routeIs('waka.schedules.*') ? 'text-white' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        Jadwal Mengajar
                                    </a>

                                    <a href="{{ route('waka.rooms.index') }}" 
                                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition {{ request()->routeIs('waka.rooms.*') ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-600/30' : 'text-slate-100 hover:bg-slate-800/80 hover:text-white font-semibold' }}">
                                        <svg class="w-4 h-4 {{ request()->routeIs('waka.rooms.*') ? 'text-white' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                                        Data Ruangan
                                    </a>
                                </div>
                            </div>

                            <!-- MENU BARU: MONITORING KESISWAAN UNTUK WAKA KURIKULUM -->
                            <div>
                                <p class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider px-3 mb-2">MONITORING KESISWAAN</p>
                                <div class="space-y-1">
                                    <a href="{{ route('student-affairs.leaves.history') }}" 
                                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition {{ request()->routeIs('student-affairs.leaves.history') ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-600/30' : 'text-slate-100 hover:bg-slate-800/80 hover:text-white font-semibold' }}">
                                        <svg class="w-4 h-4 {{ request()->routeIs('student-affairs.leaves.history') ? 'text-white' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        <span>Rekap Izin Siswa</span>
                                    </a>

                                    <a href="{{ route('student-affairs.attendance') }}" 
                                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition {{ request()->routeIs('student-affairs.attendance') ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-600/30' : 'text-slate-100 hover:bg-slate-800/80 hover:text-white font-semibold' }}">
                                        <svg class="w-4 h-4 {{ request()->routeIs('student-affairs.attendance') ? 'text-white' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                                        <span>Rekap Presensi Siswa</span>
                                    </a>
                                </div>
                            </div>
                        @endif


                        {{-- ======================================================== --}}
                        {{-- 4. MENU KHUSUS SISWA & ORANG TUA (PORTAL KELUARGA)       --}}
                        {{-- ======================================================== --}}
                        @if(in_array(auth()->user()->role?->role_name, ['Siswa', 'Orang Tua']))
                            <div>
                                <p class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider px-3 mb-2">PORTAL KELUARGA</p>
                                <div class="space-y-1">
                                    <a href="{{ route('family.dashboard') }}" 
                                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition {{ request()->routeIs('family.dashboard') ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-600/30' : 'text-slate-100 hover:bg-slate-800/80 hover:text-white font-semibold' }}">
                                        <svg class="w-4 h-4 {{ request()->routeIs('family.dashboard') ? 'text-white' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                                        Dashboard & Kehadiran
                                    </a>

                                    <a href="{{ route('family.schedules.index') }}" 
                                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition {{ request()->routeIs('family.schedules.*') ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-600/30' : 'text-slate-100 hover:bg-slate-800/80 hover:text-white font-semibold' }}">
                                        <svg class="w-4 h-4 {{ request()->routeIs('family.schedules.*') ? 'text-white' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        Jadwal Pembelajaran
                                    </a>

                                    <a href="{{ route('family.leaves.create') }}" 
                                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition {{ request()->routeIs('family.leaves.create') ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-600/30' : 'text-slate-100 hover:bg-slate-800/80 hover:text-white font-semibold' }}">
                                        <svg class="w-4 h-4 {{ request()->routeIs('family.leaves.create') ? 'text-white' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                        Pengajuan Izin KBM
                                    </a>

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
            <div class="flex-1 flex flex-col min-h-screen min-w-0 lg:pl-64">
                
                <!-- TOPBAR -->
                <header class="h-16 bg-[#0c1322] border-b border-slate-800 px-3 sm:px-6 md:px-8 flex items-center justify-between sticky top-0 z-30 transition-colors duration-200">
                    <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                        <!-- Tombol Garis 3 (Mobile) -->
                        <button @click="sidebarOpen = true" 
                                type="button" 
                                class="lg:hidden p-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800 focus:outline-none transition cursor-pointer shrink-0" 
                                aria-label="Buka Menu Sidebar">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="flex items-center gap-2 sm:gap-3.5 shrink-0">
                        <!-- INDIKATOR PERIODE REALTIME GLOBAL & SEKOLAH UNTUK SEMUA USER (DIBESARKAN & JELAS DIBACA) -->
                        <div class="flex items-center gap-2 sm:gap-2.5 px-3.5 sm:px-4 py-2 rounded-xl bg-slate-900 border border-slate-700/90 text-xs sm:text-sm font-bold shadow-md shrink-0">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse shrink-0"></span>
                            <span class="text-white font-extrabold tracking-wide hidden sm:inline">SMK Negeri 1 Bantul</span>
                            <span class="text-slate-500 hidden sm:inline">•</span>
                            <span class="text-amber-400 font-black tracking-wide">{{ \App\Models\SystemConfiguration::current()->academic_year }}</span>
                            <span class="text-slate-500">•</span>
                            <span class="text-indigo-300 font-extrabold">{{ \App\Models\SystemConfiguration::current()->semester }}</span>
                        </div>

                        <!-- TOMBOL GANTI TEMA (DARK / LIGHT MODE TOGGLE) -->
                        <button @click="toggleTheme()" 
                                type="button" 
                                class="theme-toggle-btn p-2.5 rounded-xl border border-slate-700 bg-slate-800/80 text-slate-300 hover:text-white hover:bg-slate-800 transition cursor-pointer flex items-center justify-center shadow-sm shrink-0"
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
                    </div>
                </header>

                <!-- KONTEN HALAMAN -->
                <main class="flex-1 p-3 sm:p-5 lg:p-6 w-full max-w-7xl 2xl:max-w-[1600px] mx-auto">
                    @if (session('success'))
                        <div class="mb-5 p-4 rounded-xl flash-success flex items-center justify-between shadow-md">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-xs sm:text-sm font-bold">{{ session('success') }}</span>
                            </div>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="mb-5 p-4 rounded-xl flash-error flex items-center justify-between shadow-md">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                                <span class="text-xs sm:text-sm font-bold">{{ session('error') }}</span>
                            </div>
                        </div>
                    @endif

                    {{ $slot }}
                </main>
            </div>
        </div>
                    <!-- ===================================================
             MODAL POPUP KONFIRMASI GLOBAL DI TENGAH LAYAR (FOTO 1 & 2)
             (In-page view elegan dengan latar belakang transparan blur)
             =================================================== -->
        <div x-data="globalConfirmModal()" 
             x-show="isOpen" 
             x-cloak 
             class="fixed inset-0 z-[100] flex items-center justify-center p-4"
             style="display: none;"
             @keydown.escape.window="close()">
            
            <!-- Backdrop Transparan Gelap dengan Efek Blur (Tidak Tertimpa Mode Terang) -->
            <div class="fixed inset-0 backdrop-blur-sm transition-opacity" 
                 style="background-color: rgba(15, 23, 42, 0.65) !important;"
                 @click="close()"></div>

            <!-- Kotak Modal Elegan di Tengah Layar -->
            <div class="scp-confirm-box relative w-full max-w-md transform overflow-hidden rounded-2xl bg-[#0f172a] border border-slate-700/80 p-6 text-center shadow-2xl transition-all scale-100 z-10"
                 @click.stop>
                
                <!-- Ikon Status Dinamis -->
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl border shadow-lg mb-4"
                     :class="{
                         'bg-red-500/10 border-red-500/30 text-red-400': isDanger,
                         'bg-amber-500/10 border-amber-500/30 text-amber-400': isWarning,
                         'bg-indigo-500/10 border-indigo-500/30 text-indigo-400': !isDanger && !isWarning
                     }">
                    <template x-if="isDanger">
                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </template>
                    <template x-if="!isDanger">
                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </template>
                </div>

                <!-- Judul & Pesan -->
                <h3 class="text-base font-extrabold text-white" x-text="title"></h3>
                <p class="mt-2 text-xs text-slate-300 leading-relaxed" x-text="message"></p>
                <p class="mt-1 text-[11px] text-slate-500">Tindakan ini akan langsung diproses oleh sistem.</p>

                <!-- Tombol Aksi: Batal & Ya -->
                <div class="mt-6 flex items-center justify-center gap-3">
                    <button type="button" 
                            @click="close()" 
                            class="w-full py-2.5 px-4 rounded-xl text-xs font-bold bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 transition cursor-pointer">
                        <span x-text="cancelText"></span>
                    </button>
                    <button type="button" 
                            @click="confirmAction()" 
                            class="w-full py-2.5 px-4 rounded-xl text-xs font-extrabold text-white shadow-lg transition cursor-pointer"
                            :class="{
                                'bg-red-600 hover:bg-red-500 shadow-red-600/30': isDanger,
                                'bg-amber-600 hover:bg-amber-500 shadow-amber-600/30': isWarning,
                                'scp-btn-gradient': !isDanger && !isWarning
                            }">
                        <span x-text="confirmText"></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Script Interceptor Otomatis untuk Seluruh Form & Tombol Confirm (Foto 2 & Foto 5) -->
        <script>
            function globalConfirmModal() {
                return {
                    isOpen: false,
                    title: 'Konfirmasi Tindakan',
                    message: 'Apakah Anda yakin ingin melanjutkan tindakan ini?',
                    confirmText: 'Ya, Lanjutkan',
                    cancelText: 'Batal',
                    isDanger: true,
                    isWarning: false,
                    pendingCallback: null,

                    init() {
                        window.__showSCPConfirm = (options) => {
                            this.title = options.title || (options.isDanger ? 'Konfirmasi Hapus Data' : 'Konfirmasi Tindakan');
                            this.message = options.message || 'Apakah Anda yakin ingin melanjutkan tindakan ini?';
                            this.confirmText = options.confirmText || (options.isDanger ? 'Ya, Hapus Data' : 'Ya, Lanjutkan');
                            this.cancelText = options.cancelText || 'Batal';
                            this.isDanger = options.isDanger !== undefined ? options.isDanger : true;
                            this.isWarning = options.isWarning || false;
                            this.pendingCallback = options.onConfirm || null;
                            this.isOpen = true;
                        };

                                                // Tangkap klik sebelum dialog bawaan browser muncul
                        document.addEventListener('click', (e) => {
                            const target = e.target.closest('button, a, input[type="submit"]');
                            if (!target) return;

                            // 1. Navigasi Link Menu Biasa (<a>): Beri visual feedback instan & jangan halangi navigasi
                            if (target.tagName === 'A') {
                                const href = target.getAttribute('href') || '';
                                const isExternal = target.getAttribute('target') === '_blank' || href.startsWith('#') || href.startsWith('javascript:');
                                const hasConfirm = target.getAttribute('onclick')?.includes('confirm(');

                                if (!isExternal && !hasConfirm && href) {
                                    // Tampilkan Top Progress Bar seketika pada klik pertama
                                    const loader = document.getElementById('scp-page-loader');
                                    const progress = document.getElementById('scp-page-progress');
                                    if (loader && progress) {
                                        loader.style.opacity = '1';
                                        progress.style.width = '70%';
                                    }
                                    return; // Langsung biarkan browser berpindah halaman dengan lancar
                                }
                            }

                            // 2. Cek tombol dengan onclick confirm
                            const onclickAttr = target.getAttribute('onclick') || '';
                            if (onclickAttr.includes('confirm(')) {
                                e.preventDefault();
                                e.stopImmediatePropagation();

                                const match = onclickAttr.match(/confirm\(['"`](.*?)['"`]\)/);
                                const msg = match ? match[1] : 'Apakah Anda yakin ingin melanjutkan tindakan ini?';
                                const isDanger = msg.toLowerCase().includes('hapus') || msg.toLowerCase().includes('delete');
                                const isWarning = msg.toLowerCase().includes('kunci') || msg.toLowerCase().includes('tinggal');

                                window.__showSCPConfirm({
                                    title: isDanger ? 'Konfirmasi Hapus Data' : 'Konfirmasi Tindakan',
                                    message: msg,
                                    confirmText: isDanger ? 'Ya, Hapus Data' : 'Ya, Lanjutkan',
                                    isDanger: isDanger,
                                    isWarning: isWarning,
                                    onConfirm: () => {
                                        target.removeAttribute('onclick');
                                        target.click();
                                        target.setAttribute('onclick', onclickAttr);
                                    }
                                });
                                return;
                            }

                            // 3. Cek tombol yang terhubung ke Form
                            const formId = target.getAttribute('form');
                            const form = target.form || target.closest('form') || (formId ? document.getElementById(formId) : null);
                            
                            if (form) {
                                const onsubmitAttr = form.getAttribute('onsubmit') || '';
                                if (onsubmitAttr.includes('confirm(')) {
                                    e.preventDefault();
                                    e.stopImmediatePropagation();

                                    const match = onsubmitAttr.match(/confirm\(['"`](.*?)['"`]\)/);
                                    const msg = match ? match[1] : 'Apakah Anda yakin ingin melanjutkan tindakan ini?';
                                    const isDanger = msg.toLowerCase().includes('hapus') || msg.toLowerCase().includes('delete');

                                    window.__showSCPConfirm({
                                        title: isDanger ? 'Konfirmasi Hapus Data' : 'Konfirmasi Tindakan',
                                        message: msg,
                                        confirmText: isDanger ? 'Ya, Hapus Data' : 'Ya, Lanjutkan',
                                        isDanger: isDanger,
                                        onConfirm: () => {
                                            form.removeAttribute('onsubmit');
                                            form.submit();
                                        }
                                    });
                                    return;
                                }
                            }
                        }, true); // useCapture: true agar mendahului confirm bawaan sistem!
                    },

                    confirmAction() {
                        this.isOpen = false;
                        if (typeof this.pendingCallback === 'function') {
                            this.pendingCallback();
                        }
                    },

                    close() {
                        this.isOpen = false;
                        this.pendingCallback = null;
                    }
                };
            }
        </script>
    </body>
</html>
