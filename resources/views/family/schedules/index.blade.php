<x-app-layout>
    <div class="space-y-6">

        <!-- HEADER HALAMAN JADWAL SISWA -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">Jadwal Pembelajaran Kelas</h1>
                <p class="text-xs font-semibold text-slate-400 mt-0.5">
                    Informasi mata pelajaran, guru pengampu, serta waktu jam KBM kelas Anda
                </p>
            </div>
            <div>
                <span class="inline-flex items-center gap-2.5 px-4 py-2 rounded-2xl bg-[#0f172a]/90 border border-indigo-500/40 text-xs sm:text-sm font-bold text-indigo-300 shadow-md">
                    <span class="h-2.5 w-2.5 rounded-full bg-indigo-400 animate-pulse"></span>
                    Kelas: <strong class="text-white">{{ $student->schoolClass?->class_name ?? 'Belum Ditentukan' }}</strong>
                    • {{ $configuration->active_cycle ?? 'Putaran 1' }}
                </span>
            </div>
        </div>

        <!-- FILTER TAB HARI (SENIN - JUMAT) -->
        <div class="flex flex-wrap items-center gap-2">
            @php
                $days = ['Semua', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
            @endphp
            @foreach($days as $d)
                <a href="{{ route('family.schedules.index', ['day' => $d]) }}" 
                   class="px-3.5 py-2 rounded-xl text-xs font-bold transition {{ ($selectedDay === $d) ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'bg-[#0f172a]/90 border border-slate-800 text-slate-300 hover:text-white hover:bg-slate-800' }}">
                    {{ $d === 'Semua' ? 'Semua Hari' : $d }}
                    @if($d === $todayName)
                        <span class="ml-1 text-[10px] px-1.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 font-extrabold">Hari Ini</span>
                    @endif
                </a>
            @endforeach
        </div>

        <!-- DAFTAR JADWAL MATA PELAJARAN -->
        @if($schedules->isEmpty())
            <div class="bg-[#0f172a]/90 backdrop-blur-md border border-slate-800 shadow-xl rounded-2xl p-12 text-center text-slate-400">
                <svg class="w-12 h-12 mx-auto text-slate-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                <p class="font-bold text-white text-base">Tidak Ada Jadwal Pelajaran</p>
                <p class="text-xs mt-1">Belum ada mata pelajaran yang terjadwal untuk hari {{ $selectedDay }}.</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($schedules as $sc)
                    <div class="bg-[#0f172a]/90 backdrop-blur-md border border-slate-800 hover:border-slate-700 shadow-xl rounded-2xl p-5 flex flex-col justify-between transition">
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-2">
                                <span class="px-2.5 py-1 rounded-md text-[10px] font-black uppercase tracking-wider {{ $sc->day_of_week === $todayName ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-slate-800 text-slate-300' }}">
                                    {{ $sc->day_of_week }}
                                </span>
                                <span class="text-xs font-mono font-bold text-indigo-400">
                                    {{ $sc->period_label ?? ($sc->start_time . ' - ' . $sc->end_time) }}
                                </span>
                            </div>

                            <h3 class="text-base font-extrabold text-white mt-1">{{ $sc->subject }}</h3>
                            <p class="text-xs text-slate-400 mt-1 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                <span>Guru: <strong class="text-slate-200">{{ $sc->teacher?->name ?? 'Belum Ditentukan' }}</strong></span>
                            </p>
                        </div>

                        <div class="mt-4 pt-3 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-400">
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                {{ $sc->room?->room_name ?? 'Ruang Teori' }}
                            </span>
                            <span class="text-[11px] font-semibold text-slate-500">
                                {{ $sc->cycle_type }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    </div>
</x-app-layout>
