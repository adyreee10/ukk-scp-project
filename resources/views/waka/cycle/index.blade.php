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
                    <button type="submit" 
                            class="w-full scp-btn-gradient py-3 px-4 text-xs font-bold rounded-xl text-center cursor-pointer uppercase tracking-wider"
                            style="border-radius: 12px;">
                        Ganti Menjadi {{ $configuration->active_cycle === 'Putaran 1' ? 'Putaran 2' : 'Putaran 1' }}
                    </button>
                </form>
            </div>

            <!-- KARTU 2: Status Kuncian Jadwal Putaran Aktif (1 Tombol Tunggal Sesuai Opsi 2) -->
            <div class="bg-[#0f172a]/90 backdrop-blur-md p-6 border border-slate-800 shadow-xl flex flex-col justify-between" style="border-radius: 15px;">
                @php
                    $isLocked = $configuration->isCycleLocked();
                    $activeCycle = $configuration->active_cycle;
                    $otherCycle = $activeCycle === 'Putaran 1' ? 'Putaran 2' : 'Putaran 1';
                    $isOtherLocked = $configuration->isCycleLocked($otherCycle);
                @endphp
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Kuncian Jadwal</span>
                        @if($isLocked)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold badge-locked shadow-sm">
                                <span class="h-2 w-2 rounded-full bg-red-400"></span>
                                {{ $activeCycle }} Terkunci
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold badge-open shadow-sm">
                                <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                                {{ $activeCycle }} Terbuka
                            </span>
                        @endif
                    </div>

                    <h2 class="text-base font-bold text-slate-300">Status Modifikasi {{ $activeCycle }}</h2>
                    <div class="my-3">
                        <span class="text-3xl font-black {{ $isLocked ? 'text-red-500 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }} tracking-tight">
                            {{ $isLocked ? 'Jadwal Terkunci' : 'Jadwal Terbuka' }}
                        </span>
                    </div>

                    <p class="text-xs text-slate-400 leading-relaxed mb-4">
                        @if($isLocked)
                            Saat jadwal <strong class="text-white">{{ $activeCycle }}</strong> terkunci, perubahan jadwal dinonaktifkan untuk menjaga stabilitas KBM. Putaran lain yang terbuka tetap dapat disesuaikan.
                        @else
                            Saat jadwal <strong class="text-white">{{ $activeCycle }}</strong> terbuka, Waka Kurikulum dapat leluasa menambah, mengedit, mengimpor, atau menghapus jadwal pelajaran.
                        @endif
                    </p>

                    <div class="rounded-xl p-4 border text-xs mb-4 shadow-sm {{ $isLocked ? 'box-desc-locked' : 'box-desc-open' }}">
                        <p class="font-extrabold uppercase tracking-wider text-[11px] mb-1">Keterangan:</p>
                        <p class="font-semibold text-xs leading-relaxed">
                            {{ $isLocked 
                                ? "Modifikasi jadwal {$activeCycle} dinonaktifkan sementara. Buka kunci jadwal terlebih dahulu jika ingin menambah atau mengedit jadwal {$activeCycle}." 
                                : "Modifikasi jadwal {$activeCycle} diizinkan sepenuhnya. Kunci jadwal setelah selesai penyusunan agar jadwal tidak berubah selama KBM berjalan." }}
                        </p>
                    </div>

                    <!-- Indikator Ringkas Status Putaran Pasangannya -->
                    <div class="flex items-center justify-between px-3.5 py-2.5 rounded-xl bg-slate-900/60 border border-slate-800 text-xs text-slate-400 mb-6">
                        <span>Status Kuncian {{ $otherCycle }}:</span>
                        <span class="font-bold {{ $isOtherLocked ? 'text-red-400' : 'text-emerald-400' }}">
                            {{ $isOtherLocked ? 'Terkunci' : 'Terbuka' }}
                        </span>
                    </div>
                </div>

                <!-- 1 Tombol Aksi Tunggal untuk Putaran Aktif -->
                <form method="POST" action="{{ route('waka.toggle-lock') }}">
                    @csrf
                    <input type="hidden" name="cycle" value="{{ $activeCycle }}">
                    <button type="submit" 
                            onclick="return confirm('Apakah Anda yakin ingin {{ $isLocked ? 'membuka kunci' : 'mengunci' }} jadwal {{ $activeCycle }}?')"
                            class="w-full py-3 px-4 text-xs font-bold rounded-xl text-center cursor-pointer transition uppercase tracking-wider {{ $isLocked ? 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm' : 'bg-red-600 hover:bg-red-700 text-white shadow-sm' }}"
                            style="border-radius: 12px;">
                        {{ $isLocked ? 'Buka Kunci Jadwal ' . $activeCycle : 'Kunci Jadwal ' . $activeCycle }}
                    </button>
                </form>
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
