<x-app-layout>
    <!-- Header Title & Subtitle -->
    <div class="mb-6">
        <h1 class="text-2xl font-extrabold text-white tracking-tight">Dashboard Waka Kurikulum</h1>
        <p class="text-xs text-slate-400 mt-1 font-medium">
            Selamat datang kembali, <strong class="font-bold text-slate-200">{{ auth()->user()->name }}</strong> • {{ now()->translatedFormat('l, d F Y') }}
        </p>
    </div>

    <!-- 2 Stat Cards (Murni Angka & Tulisan Bersih Tanpa Emoji) -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
        <!-- Kartu 1: Guru Pengajar -->
        <div class="bg-[#0f172a]/90 backdrop-blur-md p-6 border border-slate-800 shadow-xl transition hover:border-slate-700" style="border-radius: 15px;">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Tenaga Pendidik</span>
                <span class="text-[10px] font-bold px-2.5 py-0.5 rounded-full badge-academic shadow-sm">
                    Akademik
                </span> 
            </div>
            <div class="text-4xl font-black text-white tracking-tight my-1">
                {{ $teacherCount ?? 0 }}
            </div>
            <div class="text-xs font-semibold text-slate-400 mt-1">
                Guru Pengajar Terdaftar
            </div>
        </div>

        <!-- Kartu 2: Siswa Terdaftar -->
        <div class="bg-[#0f172a]/90 backdrop-blur-md p-6 border border-slate-800 shadow-xl transition hover:border-slate-700" style="border-radius: 15px;">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Peserta Didik</span>
                <span class="text-[10px] font-bold px-2.5 py-0.5 rounded-full badge-active shadow-sm">
                    Aktif
                </span> 
            </div>
            <div class="text-4xl font-black text-white tracking-tight my-1">
                {{ $studentCount ?? 0 }}
            </div>
            <div class="text-xs font-semibold text-slate-400 mt-1">
                Siswa di Seluruh Kelas
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
                <span class="text-xs font-semibold text-slate-400">Status Jadwal:</span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold {{ $configuration->is_schedule_locked ? 'badge-locked' : 'badge-open' }} shadow-sm">
                    {{ $configuration->is_schedule_locked ? 'Terkunci' : 'Terbuka' }}
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
