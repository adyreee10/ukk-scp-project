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
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
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
                        <p class="font-bold text-white">Periode Pelaksanaan:</p>
                        <p class="text-slate-400 mt-0.5">01 Juli 2026 s.d. 31 Desember 2026</p>
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

            <!-- KARTU 2: Status Kuncian Jadwal -->
            <div class="bg-[#0f172a]/90 backdrop-blur-md p-6 border border-slate-800 shadow-xl flex flex-col justify-between" style="border-radius: 15px;">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Keamanan Jadwal</span>
                        @if($configuration->is_schedule_locked)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-red-500/10 text-red-400 border border-red-500/20">
                                Terkunci
                            </span>
                        @else
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                Terbuka
                            </span>
                        @endif
                    </div>

                    <h2 class="text-base font-bold text-slate-300">Status Modifikasi Jadwal</h2>
                    <div class="my-3">
                        <span class="text-3xl font-black {{ $configuration->is_schedule_locked ? 'text-red-400' : 'text-emerald-400' }} tracking-tight">
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

                    <div class="rounded-xl {{ $configuration->is_schedule_locked ? 'bg-red-950/20 border-red-900/40 text-red-300' : 'bg-emerald-950/20 border-emerald-900/40 text-emerald-300' }} p-3.5 border text-xs mb-6">
                        <p class="font-bold text-white">Keterangan:</p>
                        <p class="mt-0.5">
                            {{ $configuration->is_schedule_locked ? 'Perubahan jadwal dinonaktifkan sementara.' : 'Perubahan jadwal diizinkan sepenuhnya.' }}
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
</x-app-layout>
