<x-app-layout>
    <div class="space-y-6">
        <!-- Header Halaman -->
        <div>
            <h1 class="text-2xl font-black text-white tracking-tight">Siklus Blok & Status Jadwal</h1>
            <p class="text-xs sm:text-sm font-semibold text-slate-400 mt-1">
                Kelola putaran aktif (Putaran 1 & Putaran 2) serta kontrol kuncian jadwal pelajaran
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- KARTU 1: Putaran Siklus Blok Aktif -->
            <div class="bg-[#0f172a]/90 backdrop-blur-md p-6 border border-slate-800 shadow-xl flex flex-col justify-between" style="border-radius: 15px;">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Siklus Pembelajaran</span>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold badge-running shadow-sm">
                            <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            Sedang Berjalan
                        </span>
                    </div>

                    <h2 class="text-base font-bold text-slate-300">Putaran Aktif Saat Ini</h2>
                    <div class="my-3">
                        <span class="text-4xl font-black text-indigo-400 tracking-tight">
                            {{ $configuration->active_cycle }}
                        </span>
                    </div>

                    <p class="text-xs text-slate-400 leading-relaxed mb-4">
                        Jadwal mengajar seluruh guru saat ini beroperasi berdasarkan <strong class="text-white">{{ $configuration->active_cycle }}</strong>. Penggantian putaran akan mengubah jadwal yang tampil di dashboard guru dan presensi siswa.
                    </p>

                    <div class="rounded-xl bg-slate-900/80 p-3.5 border border-slate-800 text-xs text-slate-300 mb-6">
                        <p class="font-bold text-white">Durasi Pelaksanaan:</p>
                        <p class="text-slate-300 mt-0.5">1 Putaran Berlangsung Selama <strong class="text-indigo-400 font-black">2 Minggu (10 Hari Efektif KBM)</strong></p>
                        <p class="text-[11px] text-slate-500 mt-1">Rotasi bergantian antara Putaran 1 dan Putaran 2 sepanjang semester berjalan.</p>
                    </div>
                </div>

                <!-- Aksi Ubah Putaran -->
                <form method="POST" action="{{ route('waka.toggle-cycle') }}">
                    @csrf
                    @if($configuration->is_schedule_locked)
                        <button type="button" 
                                onclick="alert('Jadwal saat ini sedang TERKUNCI. Anda harus membuka kunci jadwal terlebih dahulu di kartu sebelah kanan jika ingin mengubah putaran!')"
                                class="w-full bg-slate-800 text-slate-500 py-3 px-4 text-xs font-bold rounded-xl text-center cursor-not-allowed uppercase tracking-wider"
                                style="border-radius: 12px;">
                            Putaran Terkunci (Buka Kunci Dahulu)
                        </button>
                    @else
                        <button type="submit" 
                                class="w-full scp-btn-gradient py-3 px-4 text-xs font-bold rounded-xl text-center cursor-pointer uppercase tracking-wider"
                                style="border-radius: 12px;">
                            Ganti Menjadi {{ $configuration->active_cycle === 'Putaran 1' ? 'Putaran 2' : 'Putaran 1' }}
                        </button>
                    @endif
                </form>
            </div>

            <!-- KARTU 2: Status Kuncian Jadwal -->
            <div class="bg-[#0f172a]/90 backdrop-blur-md p-6 border border-slate-800 shadow-xl flex flex-col justify-between" style="border-radius: 15px;">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Kuncian Jadwal</span>
                        @if($configuration->is_schedule_locked)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold badge-locked shadow-sm">
                                <span class="h-2 w-2 rounded-full bg-red-400"></span>
                                Terkunci
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold badge-open shadow-sm">
                                <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                                Terbuka
                            </span>
                        @endif
                    </div>


                    <h2 class="text-base font-bold text-slate-300">Status Modifikasi Jadwal</h2>
                    <div class="my-3">
                        <span class="text-3xl font-black {{ $configuration->is_schedule_locked ? 'text-red-500 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }} tracking-tight">
                            {{ $configuration->is_schedule_locked ? 'Jadwal Terkunci' : 'Jadwal Terbuka' }}
                        </span>
                    </div>

                    <p class="text-xs text-slate-400 leading-relaxed mb-4">
                        @if($configuration->is_schedule_locked)
                            Saat jadwal <strong class="text-white">terkunci</strong>, perubahan jadwal pelajaran dinonaktifkan untuk menjaga stabilitas agenda kegiatan belajar mengajar yang sedang berlangsung.
                        @else
                            Saat jadwal <strong class="text-white">terbuka</strong>, Waka Kurikulum dapat leluasa menambah, mengubah, atau menghapus jadwal mengajar guru dan kelas.
                        @endif
                    </p>

                    <div class="rounded-xl p-4 border text-xs mb-6 shadow-sm {{ $configuration->is_schedule_locked ? 'box-desc-locked' : 'box-desc-open' }}">
                        <p class="font-extrabold uppercase tracking-wider text-[11px] mb-1">Keterangan:</p>
                        <p class="font-semibold text-xs leading-relaxed">
                            {{ $configuration->is_schedule_locked ? 'Perubahan jadwal dinonaktifkan sementara. Buka kunci jadwal terlebih dahulu jika ingin menambah atau mengedit jadwal mengajar.' : 'Perubahan jadwal diizinkan sepenuhnya. Waka Kurikulum dapat menambah, mengedit, atau menghapus jadwal mengajar.' }}
                        </p>
                    </div>
                </div>

                <!-- Aksi Kunci / Buka Jadwal -->
                <form method="POST" action="{{ route('waka.toggle-lock') }}">
                    @csrf
                    <button type="submit" 
                            onclick="return confirm('Apakah Anda yakin ingin mengubah status kuncian jadwal?')"
                            class="w-full py-3 px-4 text-xs font-bold rounded-xl text-center cursor-pointer transition uppercase tracking-wider {{ $configuration->is_schedule_locked ? 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm' : 'bg-red-600 hover:bg-red-700 text-white shadow-sm' }}"
                            style="border-radius: 12px;">
                        {{ $configuration->is_schedule_locked ? 'Buka Kunci Jadwal' : 'Kunci Jadwal Pelajaran' }}
                    </button>
                </form>
            </div>
        </div>

        <!-- KARTU 3: Panduan Sistem Siklus Blok -->
        <div class="bg-[#0f172a]/90 backdrop-blur-md p-6 border border-slate-800 shadow-xl" style="border-radius: 15px;">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-300 mb-2">Panduan Sistem Siklus Blok</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs text-slate-400 leading-relaxed">
                <div class="p-4 rounded-xl bg-slate-900/80 border border-slate-800">
                    <p class="font-bold text-white mb-1">Mekanisme Putaran 1 & Putaran 2</p>
                    <p>Sistem pembelajaran sistem blok membagi kalender ke dalam dua rotasi putaran. Jadwal pelajaran yang diinput di menu Jadwal Mengajar terikat pada siklus putaran masing-masing.</p>
                </div>
                <div class="p-4 rounded-xl bg-slate-900/80 border border-slate-800">
                    <p class="font-bold text-white mb-1">Dampak Kuncian Jadwal</p>
                    <p>Waka Kurikulum disarankan mengunci jadwal ketika seluruh jadwal pelajaran pada semester berjalan telah final, guna mencegah perubahan tidak disengaja.</p>
                </div>
            </div>
        </div>
    </div>
        <style>
        /* Mode Gelap (Default) */
        .badge-running, .badge-open {
            background-color: rgba(6, 78, 59, 0.7) !important;
            border: 1px solid #10b981 !important;
            color: #6ee7b7 !important;
        }
        .badge-locked {
            background-color: rgba(127, 29, 29, 0.7) !important;
            border: 1px solid #ef4444 !important;
            color: #fca5a5 !important;
        }
        .box-desc-locked {
            background-color: rgba(127, 29, 29, 0.35) !important;
            border: 1px solid rgba(239, 68, 68, 0.6) !important;
            border-left: 5px solid #ef4444 !important;
            color: #fecaca !important;
        }
        .box-desc-locked p.font-extrabold {
            color: #f87171 !important;
        }
        .box-desc-open {
            background-color: rgba(6, 78, 59, 0.35) !important;
            border: 1px solid rgba(16, 185, 129, 0.6) !important;
            border-left: 5px solid #10b981 !important;
            color: #d1fae5 !important;
        }
        .box-desc-open p.font-extrabold {
            color: #34d399 !important;
        }

        /* Mode Terang (Light Mode) */
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
        html.theme-light .box-desc-locked {
            background-color: #fef2f2 !important;
            border: 1px solid #f87171 !important;
            border-left: 5px solid #dc2626 !important;
            color: #7f1d1d !important;
        }
        html.theme-light .box-desc-locked p.font-extrabold {
            color: #991b1b !important;
        }
        html.theme-light .box-desc-open {
            background-color: #ecfdf5 !important;
            border: 1px solid #34d399 !important;
            border-left: 5px solid #059669 !important;
            color: #064e3b !important;
        }
        html.theme-light .box-desc-open p.font-extrabold {
            color: #065f46 !important;
        }
    </style>
</x-app-layout>
