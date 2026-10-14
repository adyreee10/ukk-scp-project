<x-app-layout>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        
        <!-- KARTU 1: Status Sistem & Kontrol (Putaran & Lock) -->
        <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-2">
                    <h2 class="font-bold text-slate-800 text-lg">Status Sistem</h2>
                    @if($configuration->is_schedule_locked)
                        <span class="inline-flex items-center gap-1.5 py-1 px-3 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8V7a4 4 0 00-8 0v4h8z"></path></svg>
                            Terkunci
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 py-1 px-3 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path></svg>
                            Terbuka
                        </span>
                    @endif
                </div>
                <p class="text-sm text-slate-600 mb-6">Siklus aktif saat ini adalah <strong class="text-indigo-600">{{ $configuration->active_cycle }}</strong>.</p>
            </div>
            
            <div class="flex gap-2">
                <form method="POST" action="{{ route('waka.toggle-cycle') }}" class="flex-1 flex">
                    @csrf
                    <button type="submit" class="w-full bg-slate-100 hover:bg-slate-200 text-slate-800 font-semibold py-2.5 rounded-lg text-sm transition">
                        Ubah Putaran
                    </button>
                </form>
                <form method="POST" action="{{ route('waka.toggle-lock') }}" class="flex-1 flex">
                    @csrf
                    <button type="submit" class="w-full {{ $configuration->is_schedule_locked ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-red-600 hover:bg-red-700' }} text-white font-semibold py-2.5 rounded-lg text-sm transition shadow-sm" onclick="return confirm('Yakin ingin mengubah status kuncian jadwal?')">
                        {{ $configuration->is_schedule_locked ? 'Buka Jadwal' : 'Kunci Jadwal' }}
                    </button>
                </form>
            </div>
        </section>

        <!-- KARTU 2: Akses Cepat Master Data -->
        <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <h2 class="font-bold text-slate-800 text-lg mb-4">Master Data</h2>
            <div class="grid grid-cols-3 gap-3">
                <a href="#" class="flex flex-col items-center justify-center p-4 rounded-xl bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition ring-1 ring-indigo-100">
                    <svg class="w-8 h-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    <span class="text-xs font-bold">User</span>
                </a>
                <a href="#" class="flex flex-col items-center justify-center p-4 rounded-xl bg-amber-50 text-amber-700 hover:bg-amber-100 transition ring-1 ring-amber-100">
                    <svg class="w-8 h-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    <span class="text-xs font-bold">Kelas</span>
                </a>
                <a href="#" class="flex flex-col items-center justify-center p-4 rounded-xl bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition ring-1 ring-emerald-100">
                    <svg class="w-8 h-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    <span class="text-xs font-bold">Ruang</span>
                </a>
            </div>
        </section>

    </div>

    <!-- KARTU 3: Daftar Guru Terlambat Jurnal -->
    <section class="mt-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
        <h2 class="font-bold text-slate-800 text-lg mb-3">Guru Terlambat Jurnal</h2>
        
        <!-- Mobile View (Card Pattern) -->
        <div class="space-y-3 md:hidden">
            @forelse ($lateTeachers as $schedule)
                <article class="rounded-lg border border-red-100 bg-red-50/30 p-3">
                    <p class="font-semibold text-slate-800">{{ $schedule->teacher->name }}</p>
                    <p class="mt-1 text-sm text-slate-600">{{ $schedule->subject }}</p>
                    <p class="text-xs font-medium text-red-600 mt-2">
                        Jadwal: {{ substr($schedule->start_time, 0, 5) }} - {{ substr($schedule->end_time, 0, 5) }}
                    </p>
                </article>
            @empty
                <p class="text-sm text-slate-500 text-center py-4">Semua guru tertib mengisi jurnal.</p>
            @endforelse
        </div>

        <!-- Desktop View (Table Pattern) -->
        <div class="hidden overflow-x-auto md:block">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead>
                    <tr class="text-left">
                        <th class="p-3 font-semibold text-slate-700">Nama Guru</th>
                        <th class="p-3 font-semibold text-slate-700">Mata Pelajaran</th>
                        <th class="p-3 font-semibold text-slate-700">Jam Terlambat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($lateTeachers as $schedule)
                        <tr class="bg-red-50/20">
                            <td class="p-3 font-medium text-slate-900">{{ $schedule->teacher->name }}</td>
                            <td class="p-3 text-slate-600">{{ $schedule->subject }}</td>
                            <td class="p-3 font-medium text-red-600">{{ substr($schedule->start_time, 0, 5) }} - {{ substr($schedule->end_time, 0, 5) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="p-3 text-center text-slate-500 py-4">Semua guru tertib mengisi jurnal.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-app-layout>
