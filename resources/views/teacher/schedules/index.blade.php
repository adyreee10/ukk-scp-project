<x-app-layout>
    <div class="space-y-6">

        <!-- Header Halaman Jadwal (Judul Teks Putih) -->
        <div class="text-center pb-1" style="text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%;">
            <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight" style="text-align: center;">
                Jadwal Mengajar Saya
            </h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1 font-medium" style="text-align: center;">
                Jadwal KBM lengkap mingguan Anda pada <strong class="text-indigo-400">{{ $configuration->active_cycle ?? 'Putaran 1' }}</strong>
            </p>
            <div class="mt-2.5 flex items-center justify-center" style="display: flex; justify-content: center;">
                <span class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-[#0f172a]/90 text-indigo-300 border border-slate-800 shadow-sm">
                    Total: {{ $schedules->count() }} Sesi Mengajar
                </span>
            </div>
        </div>

        <!-- 3 Kartu Ringkasan (Dark Card #0f172a) -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <div class="bg-[#0f172a]/90 backdrop-blur-md p-5 border border-slate-800 shadow-xl" style="border-radius: 15px;">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Siklus Kalender</span>
                <div class="text-2xl font-black text-white mt-1">
                    {{ $configuration->active_cycle ?? 'Putaran 1' }}
                </div>
                <p class="text-xs text-slate-400 mt-0.5">Siklus blok yang berlaku</p>
            </div>

            <div class="bg-[#0f172a]/90 backdrop-blur-md p-5 border border-slate-800 shadow-xl" style="border-radius: 15px;">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Sesi Kelas</span>
                <div class="text-2xl font-black text-indigo-400 mt-1">
                    {{ $schedules->count() }} <span class="text-xs font-bold text-slate-400">Kelas</span>
                </div>
                <p class="text-xs text-slate-400 mt-0.5">Jadwal tatap muka mingguan</p>
            </div>

            <div class="bg-[#0f172a]/90 backdrop-blur-md p-5 border border-slate-800 shadow-xl" style="border-radius: 15px;">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Status Kuncian</span>
                <div class="text-2xl font-black {{ ($configuration->is_schedule_locked ?? false) ? 'text-red-400' : 'text-emerald-400' }} mt-1">
                    {{ ($configuration->is_schedule_locked ?? false) ? 'Terkunci' : 'Terbuka' }}
                </div>
                <p class="text-xs text-slate-400 mt-0.5">
                    {{ ($configuration->is_schedule_locked ?? false) ? 'Jadwal telah difinalisasi waka' : 'Jadwal dapat disesuaikan waka' }}
                </p>
            </div>
        </div>

        <!-- Filter Tab Hari (Dark Card) -->
        <div class="bg-[#0f172a]/90 backdrop-blur-md p-3.5 border border-slate-800 shadow-xl flex flex-wrap gap-2 items-center" style="border-radius: 15px;">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider px-2">Filter Hari:</span>
            
            @php
                $currentDay = request('day', 'Semua');
                $days = ['Semua', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            @endphp

            @foreach ($days as $day)
                <a href="{{ route('teacher.schedules.index', ['day' => $day]) }}" 
                   class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer {{ $currentDay === $day ? 'scp-btn-gradient text-white shadow-md' : 'bg-slate-900 text-slate-300 hover:bg-slate-800 hover:text-white border border-slate-800' }}"
                   @if($currentDay === $day) style="color: #ffffff !important;" @endif>
                    {{ $day }}
                </a>
            @endforeach
        </div>

        <!-- Tabel Jadwal Mingguan Lengkap (Dark Card & High Contrast) -->
        <div class="bg-[#0f172a]/90 backdrop-blur-md border border-slate-800 shadow-xl overflow-hidden" style="border-radius: 15px;">
            <div class="p-5 border-b border-slate-800 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-white">Daftar Jadwal Mengajar ({{ $currentDay }})</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Rincian jam, mata pelajaran, kelas, dan ruang laboratorium/kelas</p>
                </div>
            </div>

            @if($schedules->isEmpty())
                <div class="text-center py-12 bg-slate-900/50">
                    <p class="text-sm font-bold text-white">Tidak Ada Jadwal Mengajar</p>
                    <p class="text-xs text-slate-400 mt-1">Tidak ditemukan jadwal untuk filter hari <strong>{{ $currentDay }}</strong>.</p>
                </div>
            @else
                <!-- Tampilan Mobile (Card View) -->
                <div class="p-4 space-y-3.5 md:hidden">
                    @foreach ($schedules as $schedule)
                        <div class="p-4 rounded-xl border border-slate-800 bg-slate-900/60 hover:bg-slate-800/50 transition">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-md bg-indigo-950/60 text-indigo-300 border border-indigo-800/60">
                                        {{ $schedule->day_of_week }}
                                    </span>
                                    <h3 class="font-bold text-white text-sm mt-1.5">{{ $schedule->subject }}</h3>
                                    <p class="text-xs text-slate-400">{{ $schedule->schoolClass->class_name }} • {{ $schedule->room->room_name }}</p>
                                </div>
                                <span class="text-xs font-mono font-bold text-slate-300 shrink-0">
                                    {{ substr($schedule->start_time, 0, 5) }} - {{ substr($schedule->end_time, 0, 5) }}
                                </span>
                            </div>
                            <div class="mt-3 pt-3 border-t border-slate-800/80 flex justify-end">
                                <a href="{{ route('teacher.journals.create', ['schedule_id' => $schedule->id]) }}" 
                                   class="w-full text-center px-4 py-2 rounded-xl scp-btn-gradient text-xs font-bold shadow-md transition"
                                   style="color: #ffffff !important;">
                                    <span style="color: #ffffff !important;">Isi Jurnal & Absen →</span>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Tampilan Desktop (Table View) -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-800 text-sm">
                        <thead class="bg-slate-900/70">
                            <tr class="text-left text-xs font-bold text-slate-400 uppercase tracking-wider">
                                <th class="p-4">Hari & Jam</th>
                                <th class="p-4">Mata Pelajaran</th>
                                <th class="p-4">Kelas</th>
                                <th class="p-4">Ruang</th>
                                <th class="p-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            @foreach ($schedules as $schedule)
                                <tr class="hover:bg-slate-800/30 transition">
                                    <td class="p-4">
                                        <div class="font-bold text-white">{{ $schedule->day_of_week }}</div>
                                        <div class="text-xs font-mono text-slate-400">
                                            {{ substr($schedule->start_time, 0, 5) }} - {{ substr($schedule->end_time, 0, 5) }}
                                        </div>
                                    </td>
                                    <td class="p-4 font-semibold text-white">{{ $schedule->subject }}</td>
                                    <td class="p-4">
                                        <span class="px-2.5 py-1 rounded-md text-xs font-bold bg-indigo-950/60 text-indigo-300 border border-indigo-800/60">
                                            {{ $schedule->schoolClass->class_name }}
                                        </span>
                                    </td>
                                    <td class="p-4 text-slate-300 text-xs">{{ $schedule->room->room_name }}</td>
                                    <td class="p-4 text-center">
                                        <!-- TOMBOL AKSI DENGAN TEKS PUTIH SOLID (#ffffff) -->
                                        <a href="{{ route('teacher.journals.create', ['schedule_id' => $schedule->id]) }}" 
                                           class="inline-flex items-center justify-center px-4 py-2 rounded-xl scp-btn-gradient text-xs font-bold shadow-md hover:shadow-lg transition cursor-pointer whitespace-nowrap"
                                           style="color: #ffffff !important;">
                                            <span style="color: #ffffff !important;">Isi Jurnal & Absen →</span>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

    </div>
</x-app-layout>
