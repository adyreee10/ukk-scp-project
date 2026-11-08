<x-app-layout>
    <div class="space-y-6">

        <!-- Header Dashboard Wali Kelas / Guru (Ukuran Font Persis Milik Waka) -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-7">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                    {{ auth()->user()->role?->role_name === 'Wali Kelas' ? 'Dashboard Wali Kelas' : (auth()->user()->role?->role_name === 'Guru BK' ? 'Dashboard Guru BK' : 'Dashboard Guru') }}
                </h1>
                <p class="text-sm sm:text-base text-slate-800 dark:text-slate-300 font-semibold mt-2 flex items-center flex-wrap gap-2">
                    <span>Selamat mengajar,</span>
                    <strong class="font-extrabold text-indigo-600 dark:text-indigo-400 text-base sm:text-lg">{{ auth()->user()->name }}</strong>
                    <span class="text-slate-400 dark:text-slate-500">•</span>
                    <span class="text-black dark:text-white font-extrabold tracking-wide">{{ now()->translatedFormat('l, d F Y') }}</span>
                </p>
            </div>
            <!-- BADGE SIKLUS PUTARAN (DIPERBESAR & KONTRAS LEBIH JELAS) -->
            <div>
                <span class="inline-flex items-center gap-2.5 px-5 py-2.5 rounded-2xl bg-[#0f172a]/90 border border-indigo-500/40 text-sm sm:text-base font-black text-indigo-300 shadow-md">
                    <span class="h-3 w-3 rounded-full bg-indigo-400 animate-pulse shadow-sm shadow-indigo-400"></span>
                    Siklus: {{ $configuration->active_cycle }}
                </span>
            </div>
        </div>

        <!-- ALERT PERINGATAN KETERLAMBATAN JURNAL -->
        @if(isset($overdueSchedules) && $overdueSchedules->isNotEmpty())
            <div class="p-4 rounded-xl bg-red-950/30 border border-red-800/60 text-red-300 flex items-start gap-3 shadow-lg">
                <svg class="w-5 h-5 text-red-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <div class="flex-1">
                    <h3 class="text-xs sm:text-sm font-bold text-red-200">Peringatan Pengisian Jurnal Terlambat</h3>
                    <p class="text-xs text-red-300/90 mt-0.5">
                        Anda memiliki {{ $overdueSchedules->count() }} sesi KBM yang telah melewati batas toleransi pengisian jurnal (>30 menit setelah jam pelajaran selesai). Mohon segera lengkapi jurnal dan kehadiran siswa.
                    </p>
                </div>
            </div>
        @endif

        <!-- 3 KARTU STATISTIK RINGKAS -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <!-- Kartu 1: Jadwal Hari Ini -->
            <div class="bg-[#0f172a]/90 backdrop-blur-md p-5 border border-slate-800 shadow-xl" style="border-radius: 15px;">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Jadwal Hari Ini</span>
                <div class="text-3xl font-black text-white mt-1 mb-0.5">
                    {{ $todaySchedules->count() }} <span class="text-xs font-semibold text-slate-400">Sesi KBM</span>
                </div>
                <p class="text-[11px] font-medium text-slate-400">Hari {{ $todayName }}</p>
            </div>

            <!-- Kartu 2: Total Jadwal Mingguan -->
            <div class="bg-[#0f172a]/90 backdrop-blur-md p-5 border border-slate-800 shadow-xl" style="border-radius: 15px;">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Mengajar</span>
                <div class="text-3xl font-black text-indigo-400 mt-1 mb-0.5">
                    {{ $allSchedules->count() }} <span class="text-xs font-semibold text-slate-400">Jadwal / Minggu</span>
                </div>
                <p class="text-[11px] font-medium text-slate-400">Di putaran aktif saat ini</p>
            </div>

            <!-- Kartu 3: Jurnal KBM Terisi -->
            <div class="bg-[#0f172a]/90 backdrop-blur-md p-5 border border-slate-800 shadow-xl" style="border-radius: 15px;">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Jurnal KBM Terakhir</span>
                <div class="text-3xl font-black text-emerald-400 mt-1 mb-0.5">
                    {{ $recentJournals->count() }} <span class="text-xs font-semibold text-slate-400">Tercatat</span>
                </div>
                <p class="text-[11px] font-medium text-slate-400">Histori pembelajaran terbaru</p>
            </div>
        </div>

        <!-- JADWAL MENGAJAR HARI INI -->
        <div class="bg-[#0f172a]/90 backdrop-blur-md border border-slate-800 shadow-xl overflow-hidden" style="border-radius: 15px;">
            <div class="p-4 sm:p-5 border-b border-slate-800 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-white">Jadwal Mengajar Hari Ini</h2>
                    <p class="text-xs text-slate-400">Daftar kelas yang harus Anda ajar hari ini ({{ $todayName }})</p>
                </div>
                <a href="{{ route('teacher.schedules.index') }}" class="text-xs font-bold text-indigo-400 hover:text-indigo-300 transition hover:underline">
                    Lihat Semua Jadwal →
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-800 text-sm">
                    <thead class="bg-slate-900/70">
                        <tr class="text-left text-xs font-bold text-slate-400 uppercase tracking-wider">
                            <th class="p-4">Mata Pelajaran</th>
                            <th class="p-4">Kelas</th>
                            <th class="p-4">Waktu</th>
                            <th class="p-4">Ruangan</th>
                            <th class="p-4 text-center">Aksi Jurnal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @forelse ($todaySchedules as $schedule)
                            <tr class="hover:bg-slate-800/30 transition">
                                <td class="p-4">
                                    <span class="font-bold text-white">{{ $schedule->subject }}</span>
                                </td>
                                <td class="p-4">
                                    <span class="inline-block rounded-md bg-indigo-950/60 px-2.5 py-1 text-xs font-bold text-indigo-300 border border-indigo-800/60">
                                        {{ $schedule->schoolClass?->class_name ?? '-' }}
                                    </span>
                                </td>
                                <td class="p-4 text-xs font-mono text-slate-300">
                                    {{ $schedule->period_label }}
                                </td>
                                <td class="p-4 text-xs text-slate-300">
                                    {{ $schedule->room?->room_name ?? '-' }}
                                </td>
                                 <td class="p-4 text-center">
                                    @if(in_array($schedule->id, $filledScheduleIdsToday ?? []))
                                        <!-- BADGE HIJAU: JIKA SUDAH DIISI HARI INI -->
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-950/70 border border-emerald-700/60 text-emerald-300 text-xs font-bold shadow-sm whitespace-nowrap">
                                            <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            Sudah Diisi
                                        </span>
                                    @else
                                        <!-- TOMBOL ISI JURNAL: JIKA BELUM DIISI -->
                                        <a href="{{ route('teacher.journals.create', ['schedule_id' => $schedule->id]) }}" 
                                           class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl scp-btn-gradient text-xs font-bold shadow-md transition whitespace-nowrap"
                                           style="color: #ffffff !important;">
                                            <svg class="w-3.5 h-3.5 text-white" style="color: #ffffff !important;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                            <span style="color: #ffffff !important;">Isi Jurnal & Presensi</span>
                                        </a>
                                    @endif
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-8 text-center text-slate-400 text-sm">
                                    Tidak ada jadwal mengajar untuk Anda pada hari ini. Nikmati waktu istirahat atau persiapan materi!
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- RIWAYAT 5 JURNAL KBM TERAKHIR -->
        @if(isset($recentJournals) && $recentJournals->isNotEmpty())
            <div class="bg-[#0f172a]/90 backdrop-blur-md border border-slate-800 shadow-xl overflow-hidden" style="border-radius: 15px;">
                <div class="p-4 sm:p-5 border-b border-slate-800">
                    <h2 class="text-base font-bold text-white">Riwayat Jurnal KBM Terakhir</h2>
                    <p class="text-xs text-slate-400">5 sesi pembelajaran terakhir yang telah Anda dokumentasikan</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-800 text-sm">
                        <thead class="bg-slate-900/70">
                            <tr class="text-left text-xs font-bold text-slate-400 uppercase tracking-wider">
                                <th class="p-4">Tanggal KBM</th>
                                <th class="p-4">Mapel & Kelas</th>
                                <th class="p-4 text-center">Status Jurnal</th>
                                <th class="p-4">Topik Materi Pembelajaran</th>
                                <th class="p-4 text-center">Status Presensi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            @foreach ($recentJournals as $journal)
                                <tr class="hover:bg-slate-800/30 transition">
                                    <td class="p-4 text-xs font-mono text-slate-300 whitespace-nowrap">
                                        {{ \Carbon\Carbon::parse($journal->date)->translatedFormat('d M Y') }}
                                    </td>
                                    <td class="p-4">
                                        <div class="font-bold text-white text-xs">{{ $journal->schedule?->subject }}</div>
                                        <div class="text-[11px] text-slate-400">{{ $journal->schedule?->schoolClass?->class_name }} • {{ $journal->schedule?->room?->room_name }}</div>
                                    </td>
                                    <td class="p-4 text-center whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-black bg-emerald-950/70 text-emerald-300 border border-emerald-600/60 shadow-sm">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                            <span>Mengisi Jurnal</span>
                                        </span>
                                    </td>
                                    <td class="p-4 text-xs text-slate-300 max-w-xs truncate">
                                        {{ $journal->topic_description }}
                                    </td>
                                    <td class="p-4 text-center">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-950/60 text-emerald-300 border border-emerald-800/60">
                                            {{ $journal->attendances->count() }} Siswa Hadir/Tercatat
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

    </div>
</x-app-layout>
