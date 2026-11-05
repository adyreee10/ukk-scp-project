<x-app-layout>
    <div class="space-y-6">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">Hasil Pencarian</h1>
            <p class="text-xs font-semibold text-slate-400 mt-0.5">
                Kata kunci: <span class="text-indigo-400 font-bold">"{{ $q }}"</span> • Ditemukan <strong class="text-white">{{ $totalResults }}</strong> data
            </p>
        </div>

        @if($totalResults === 0)
            <div class="bg-[#0f172a]/90 backdrop-blur-md p-12 text-center border border-slate-800 shadow-xl" style="border-radius: 15px;">
                <p class="text-base font-bold text-white">Tidak ada data yang cocok</p>
                <p class="text-xs text-slate-400 mt-1">Coba gunakan kata kunci nama guru, nama siswa, atau mata pelajaran lainnya.</p>
            </div>
        @else
            <!-- Data Guru -->
            @if($teachers->isNotEmpty())
                <div class="bg-[#0f172a]/90 backdrop-blur-md p-5 border border-slate-800 shadow-xl space-y-3" style="border-radius: 15px;">
                    <h2 class="text-sm font-bold text-white uppercase tracking-wider">Guru & Tenaga Pendidik ({{ $teachers->count() }})</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                        @foreach($teachers as $t)
                            <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800">
                                <p class="text-xs font-bold text-white">{{ $t->name }}</p>
                                <p class="text-[11px] text-slate-400">{{ $t->email }} • {{ $t->role?->role_name }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Data Siswa -->
            @if($students->isNotEmpty())
                <div class="bg-[#0f172a]/90 backdrop-blur-md p-5 border border-slate-800 shadow-xl space-y-3" style="border-radius: 15px;">
                    <h2 class="text-sm font-bold text-white uppercase tracking-wider">Peserta Didik ({{ $students->count() }})</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                        @foreach($students as $s)
                            <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800">
                                <p class="text-xs font-bold text-white">{{ $s->name }}</p>
                                <p class="text-[11px] text-slate-400">Kelas: {{ $s->schoolClass?->class_name ?? '-' }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Jadwal Pelajaran -->
            @if($schedules->isNotEmpty())
                <div class="bg-[#0f172a]/90 backdrop-blur-md p-5 border border-slate-800 shadow-xl space-y-3" style="border-radius: 15px;">
                    <h2 class="text-sm font-bold text-white uppercase tracking-wider">Jadwal Pelajaran ({{ $schedules->count() }})</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach($schedules as $sc)
                            <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800 flex justify-between items-center">
                                <div>
                                    <p class="text-xs font-bold text-white">{{ $sc->subject }}</p>
                                    <p class="text-[11px] text-slate-400">{{ $sc->teacher?->name }} • {{ $sc->schoolClass?->class_name }}</p>
                                </div>
                                <span class="text-xs font-mono font-bold text-indigo-400">{{ $sc->day_of_week }} ({{ $sc->period_label }})</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Jurnal KBM -->
            @if($journals->isNotEmpty())
                <div class="bg-[#0f172a]/90 backdrop-blur-md p-5 border border-slate-800 shadow-xl space-y-3" style="border-radius: 15px;">
                    <h2 class="text-sm font-bold text-white uppercase tracking-wider">Topik Jurnal KBM ({{ $journals->count() }})</h2>
                    <div class="space-y-2">
                        @foreach($journals as $j)
                            <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800 flex justify-between items-center">
                                <div>
                                    <p class="text-xs font-bold text-white">{{ $j->topic_description }}</p>
                                    <p class="text-[11px] text-slate-400">{{ $j->schedule?->subject }} • {{ $j->schedule?->teacher?->name }}</p>
                                </div>
                                <span class="text-xs font-mono text-slate-400">{{ \Carbon\Carbon::parse($j->date)->translatedFormat('d M Y') }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endif
    </div>
</x-app-layout>
