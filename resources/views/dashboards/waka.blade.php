<x-app-layout>
    <!-- Header Title & Subtitle (Diperbesar Lebih Jelas & Mudah Dibaca) -->
    <div class="mb-7">
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">Dashboard Waka Kurikulum</h1>
        <p class="text-sm sm:text-base text-slate-800 dark:text-slate-300 font-semibold mt-2 flex items-center flex-wrap gap-2">
            <span>Selamat datang kembali,</span>
            <strong class="font-extrabold text-indigo-600 dark:text-indigo-400 text-base sm:text-lg">{{ auth()->user()->name }}</strong>
            <span class="text-slate-400 dark:text-slate-500">•</span>
            <span class="text-black dark:text-white font-extrabold tracking-wide">{{ now()->translatedFormat('l, d F Y') }}</span>
        </p>
    </div>

    <!-- 6 Stat Cards (Dibagi 3 : 3 -> Baris Atas: Guru, Siswa, Ruangan; Baris Bawah: Kelas, Mapel, Jurnal) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5 mb-7">
        <!-- Kartu 1: Semua Guru -->
        <div class="bg-[#0f172a]/90 backdrop-blur-md p-5 border border-slate-800 shadow-xl transition hover:border-slate-700 flex flex-col justify-between" style="border-radius: 15px;">
            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Pendidik</span>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full badge-active shadow-sm">
                        Guru
                    </span> 
                </div>
                <div class="text-3xl sm:text-4xl font-black text-white tracking-tight my-1">
                    {{ $teacherCount ?? 0 }}
                </div>
            </div>
            <div class="text-[11px] font-semibold text-slate-400 mt-2">
                Guru, Wali Kelas & BK
            </div>
        </div>

        <!-- Kartu 2: Siswa Terdaftar -->
        <div class="bg-[#0f172a]/90 backdrop-blur-md p-5 border border-slate-800 shadow-xl transition hover:border-slate-700 flex flex-col justify-between" style="border-radius: 15px;">
            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Peserta Didik</span>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full badge-active shadow-sm">
                        Siswa
                    </span> 
                </div>
                <div class="text-3xl sm:text-4xl font-black text-white tracking-tight my-1">
                    {{ $studentCount ?? 0 }}
                </div>
            </div>
            <div class="text-[11px] font-semibold text-slate-400 mt-2">
                Siswa Seluruh Rombel
            </div>
        </div>

        <!-- Kartu 3: Ruangan -->
        <div class="bg-[#0f172a]/90 backdrop-blur-md p-5 border border-slate-800 shadow-xl transition hover:border-slate-700 flex flex-col justify-between" style="border-radius: 15px;">
            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Sarpras</span>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full badge-active shadow-sm">
                        Ruang
                    </span> 
                </div>
                <div class="text-3xl sm:text-4xl font-black text-white tracking-tight my-1">
                    {{ $roomCount ?? 0 }}
                </div>
            </div>
            <div class="text-[11px] font-semibold text-slate-400 mt-2">
                Ruang Teori & Lab
            </div>
        </div>

        <!-- Kartu 4: Kelas / Rombel -->
        <div class="bg-[#0f172a]/90 backdrop-blur-md p-5 border border-slate-800 shadow-xl transition hover:border-slate-700 flex flex-col justify-between" style="border-radius: 15px;">
            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Rombel</span>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full badge-active shadow-sm">
                        Kelas
                    </span> 
                </div>
                <div class="text-3xl sm:text-4xl font-black text-white tracking-tight my-1">
                    {{ $classCount ?? 0 }}
                </div>
            </div>
            <div class="text-[11px] font-semibold text-slate-400 mt-2">
                Kelas X, XI, & XII
            </div>
        </div>

        <!-- Kartu 5: Mata Pelajaran -->
        <div class="bg-[#0f172a]/90 backdrop-blur-md p-5 border border-slate-800 shadow-xl transition hover:border-slate-700 flex flex-col justify-between" style="border-radius: 15px;">
            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Kurikulum</span>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full badge-active shadow-sm">
                        Mapel
                    </span> 
                </div>
                <div class="text-3xl sm:text-4xl font-black text-white tracking-tight my-1">
                    {{ $subjectCount ?? 0 }}
                </div>
            </div>
            <div class="text-[11px] font-semibold text-slate-400 mt-2">
                Mata Pelajaran KBM
            </div>
        </div>

        <!-- Kartu 6: Progress Jurnal KBM Hari Ini -->
        <div class="bg-[#0f172a]/90 backdrop-blur-md p-5 border border-slate-800 shadow-xl transition hover:border-slate-700 flex flex-col justify-between" style="border-radius: 15px;">
            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Jurnal Hari Ini</span>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full badge-active shadow-sm">
                        {{ $isWeekend ? 'Libur KBM' : $dayName }}
                    </span> 
                </div>
                <div class="text-3xl sm:text-4xl font-black text-emerald-400 tracking-tight my-1 flex items-baseline gap-1">
                    <span>{{ $filledJournalsToday ?? 0 }}</span>
                    <span class="text-xl sm:text-2xl text-slate-500 font-bold">/{{ $targetJournalsToday ?? 0 }}</span>
                </div>
            </div>
            <div class="mt-2">
                @if($targetJournalsToday > 0)
                    @php $pct = round(($filledJournalsToday / $targetJournalsToday) * 100); @endphp
                    <div class="w-full bg-slate-800 h-1.5 rounded-full overflow-hidden mb-1">
                        <div class="bg-emerald-500 h-full transition-all duration-500" style="width: {{ $pct }}%;"></div>
                    </div>
                    <div class="text-[10px] font-bold text-slate-400">
                        {{ $pct }}% Jurnal Terisi
                    </div>
                @else
                    <div class="text-[11px] font-bold text-slate-500 dark:text-slate-400">
                        {{ $isWeekend ? 'Senin: ' . ($mondayTarget ?? 0) . ' jurnal' : 'Nihil jadwal KBM' }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- KARTU SIKLUS BLOK AKTIF -->
    <div class="bg-[#0f172a]/90 backdrop-blur-md p-6 border border-slate-800 shadow-xl mb-6" style="border-radius: 15px;">
        <div class="flex items-center justify-between mb-2">
            <h2 class="text-base font-extrabold text-white">Siklus Blok Aktif</h2>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold badge-running shadow-sm">
                <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
                Sedang Berjalan
            </span>
        </div>

        <!-- Angka Putaran Besar -->
        <div class="text-4xl font-black text-indigo-400 mt-2">
            {{ str_replace('Putaran ', '', $configuration->active_cycle) }}
        </div>

        <p class="text-xs text-slate-400 mt-2">
            Durasi Pelaksanaan: <span class="font-bold text-slate-200">2 Minggu per Putaran (10 Hari Efektif KBM)</span>
        </p>

        <!-- Tombol Aksi Menuju Halaman Siklus Blok & Status Jadwal -->
        <div class="mt-4 pt-4 border-t border-slate-800 flex items-center justify-between">
            <a href="{{ route('waka.cycle.index') }}" 
               class="px-4 py-2.5 text-xs font-bold rounded-xl text-center cursor-pointer transition uppercase tracking-wider bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm inline-flex items-center gap-1.5"
               style="color: #ffffff !important;">
                Atur Siklus Blok →
            </a>
            <div class="flex items-center gap-2">
                <span class="text-xs font-semibold text-slate-400">Status Jadwal ({{ $configuration->active_cycle }}):</span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold {{ $configuration->isCycleLocked() ? 'badge-locked' : 'badge-open' }} shadow-sm">
                    {{ $configuration->isCycleLocked() ? 'Terkunci' : 'Terbuka' }}
                </span>
            </div>
        </div>

    </div>

    <!-- MONITORING KETERLAMBATAN PENGISIAN JURNAL -->
    <div class="bg-[#0f172a]/90 backdrop-blur-md p-6 border border-slate-800 shadow-xl" style="border-radius: 15px;">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-extrabold text-white">Monitoring Keterlambatan Pengisian Jurnal</h2>
            <span class="text-xs text-slate-500 font-medium">Hari ini: {{ now()->translatedFormat('d M Y') }}</span>
        </div>

        @if($lateTeachers->isEmpty())
            <!-- Kotak Hijau Bersih Tanpa Emoji Checklist -->
            <div class="p-4 rounded-xl box-ideal shadow-sm">
                <div class="text-xs sm:text-sm font-semibold flex items-center gap-2">
                    <strong class="font-extrabold uppercase tracking-wide text-emerald-400">[Kondisi Ideal]</strong>
                    <span>Tidak ada keterlambatan pengisian jurnal hari ini. Seluruh guru telah mengisi jurnal pembelajaran tepat waktu.</span>
                </div>
            </div>
        @else
            <!-- Tampilan Tabel Jika Ada Keterlambatan -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-800 text-sm">
                    <thead>
                        <tr class="text-left bg-slate-900/60 text-xs font-bold text-slate-400 uppercase tracking-wider">
                            <th class="p-3">Nama Guru</th>
                            <th class="p-3">Mata Pelajaran</th>
                            <th class="p-3">Jam Terlambat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @foreach ($lateTeachers as $schedule)
                            <tr class="bg-red-950/20">
                                <td class="p-3 font-medium text-slate-200">{{ $schedule->teacher->name }}</td>
                                <td class="p-3 text-slate-400">{{ $schedule->subject }}</td>
                                <td class="p-3 font-bold text-red-400">{{ $schedule->period_label }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-app-layout>
