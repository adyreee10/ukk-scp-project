<x-app-layout>
    <div x-data="{ searchStudent: '' }" class="space-y-6">

        <!-- HEADER LAMAN REKAP PRESENSI -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 print:hidden">
            <div>
                <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">Rekapitulasi Presensi Siswa</h1>
                <p class="text-xs font-semibold text-slate-400 mt-0.5">
                    Monitoring kehadiran siswa & deteksi dini kedisiplinan • {{ now()->translatedFormat('l, d F Y') }}
                </p>
            </div>
            <div class="flex items-center gap-3">
                <!-- BADGE HAK AKSES -->
                <span class="inline-flex items-center gap-2.5 px-4 py-2 rounded-2xl bg-[#0f172a]/90 border border-indigo-500/40 text-xs sm:text-sm font-bold text-indigo-300 shadow-md">
                    <span class="h-2.5 w-2.5 rounded-full bg-indigo-400 animate-pulse"></span>
                    @if(auth()->user()->role?->role_name === 'Wali Kelas')
                        Wali Kelas: {{ auth()->user()->homeroomClass?->class_name ?? 'Belum Ditentukan' }}
                    @elseif(auth()->user()->role?->role_name === 'Guru BK')
                        Guru BK (Bimbingan Konseling)
                    @else
                        Waka Kurikulum
                    @endif
                </span>

                <!-- TOMBOL CETAK REKAP A4 (UNTUK WAKA KURIKULUM, GURU BK, & WALI KELAS) -->
                @if(in_array(auth()->user()->role?->role_name, ['Wali Kelas', 'Guru BK', 'Waka Kurikulum']))
                <button type="button" 
                        onclick="const oldT = document.title; document.title = ''; window.print(); setTimeout(() => { document.title = oldT; }, 1000);" 
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs sm:text-sm font-bold shadow-lg shadow-indigo-600/30 transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    <span>Cetak Rekap (A4)</span>
                </button>
                @endif
            </div>
        </div>

        <!-- HEADER KOP LAPORAN (HANYA MUNCUL SAAT DI-PRINT A4) -->
        <div class="hidden print:block mb-5 border-b-2 border-black pb-3 text-center">
            <h1 class="text-base font-black tracking-wider text-black uppercase">SMK NEGERI 1 BANTUL</h1>
            <h2 class="text-sm font-bold uppercase tracking-wider text-black mt-0.5">LAPORAN REKAPITULASI PRESENSI SISWA</h2>
            <p class="text-[11px] text-gray-700 mt-1">
                Periode: <strong>{{ $periodLabel }}</strong> &nbsp;•&nbsp; 
                Kelas: <strong>{{ $activeClass?->class_name ?? 'Semua Kelas' }}</strong> &nbsp;•&nbsp; 
                Dicetak: {{ now()->translatedFormat('d F Y, H:i') }} WIB &nbsp;•&nbsp; 
                Oleh: {{ auth()->user()->name }} ({{ auth()->user()->role?->role_name }})
            </p>
        </div>

        <!-- PANEL FILTER KELAS & INDIKATOR DISIPLIN (DIPERBAIKI: LEGA & TIDAK MEPET) -->
        {{-- =========================================================================
             FITUR UKK: PANEL FILTER TERPADU (KELAS & PERIODE PRESENSI)
             - Disusun sejajar, rapi, dan proporsional untuk Waka, Guru BK, & Wali Kelas
             - Teks periode aktif putih solid di Light & Dark Mode
             ========================================================================= --}}
        <div class="bg-[#0f172a]/90 backdrop-blur-md p-4 sm:p-5 border border-slate-800 shadow-xl print:hidden" style="border-radius: 15px;">
            <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4">
                
                <!-- FILTER KELAS: WALI KELAS (1 KELAS), GURU BK (KELAS BINAAN), WAKA (3 KRITERIA) -->
                <div class="flex flex-wrap items-center gap-3">
                    @if(auth()->user()->role?->role_name === 'Wali Kelas')
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Kelas Perwalian:</span>
                            <span class="px-3.5 py-1.5 rounded-xl bg-indigo-950/70 border border-indigo-700/60 text-indigo-300 font-bold text-xs">
                                Kelas {{ $activeClass?->class_name ?? 'Belum Ditentukan' }}
                            </span>
                        </div>
                    @elseif(auth()->user()->role?->role_name === 'Guru BK')
                        <form method="GET" action="{{ route('student-affairs.attendance') }}" class="flex flex-wrap items-center gap-2">
                            @if(request('period')) <input type="hidden" name="period" value="{{ request('period') }}"> @endif
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Pilih Kelas:</span>
                            <div class="flex items-center gap-1.5 bg-slate-900/80 p-1 rounded-xl border border-slate-700">
                                <select name="class_id" onchange="this.form.submit()" 
                                        class="bg-slate-950 border border-slate-700 text-white text-xs font-semibold rounded-lg pl-2.5 pr-8 py-1 focus:outline-none focus:border-indigo-500 cursor-pointer min-w-[9rem]">
                                    @foreach($classes as $c)
                                        <option value="{{ $c->id }}" {{ ($selectedClassId ?? '') == $c->id ? 'selected' : '' }}>
                                            Kelas {{ $c->class_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <span class="text-[11px] font-bold text-indigo-400 bg-indigo-950/60 border border-indigo-700/50 px-2 py-1 rounded-xl">
                                Total: {{ $classes->count() }} Kelas
                            </span>
                        </form>
                    @else
                        {{-- Waka Kurikulum (Filter 3 Kriteria Sejajar Horizontal) --}}
                        <form method="GET" action="{{ route('student-affairs.attendance') }}" class="flex flex-wrap items-center gap-2">
                            @if(request('period')) <input type="hidden" name="period" value="{{ request('period') }}"> @endif
                            <!-- 1. Tingkat -->
                            <div class="flex items-center gap-1 bg-slate-900/80 p-1 rounded-xl border border-slate-700">
                                <span class="text-slate-400 font-bold text-[10px] uppercase px-1.5">Tingkat:</span>
                                <select name="grade" onchange="this.form.submit()" 
                                        class="bg-slate-950 border border-slate-700 text-white text-xs font-semibold rounded-lg pl-2 pr-7 py-1 focus:outline-none focus:border-indigo-500 cursor-pointer min-w-[6.5rem]">
                                    <option value="">Semua</option>
                                    <option value="X" {{ ($selectedGrade ?? request('grade')) === 'X' ? 'selected' : '' }}>Kelas 10 (X)</option>
                                    <option value="XI" {{ ($selectedGrade ?? request('grade')) === 'XI' ? 'selected' : '' }}>Kelas 11 (XI)</option>
                                    <option value="XII" {{ ($selectedGrade ?? request('grade')) === 'XII' ? 'selected' : '' }}>Kelas 12 (XII)</option>
                                </select>
                            </div>

                            <!-- 2. Jurusan -->
                            <div class="flex items-center gap-1 bg-slate-900/80 p-1 rounded-xl border border-slate-700">
                                <span class="text-slate-400 font-bold text-[10px] uppercase px-1.5">Jurusan:</span>
                                <select name="major" onchange="this.form.submit()" 
                                        class="bg-slate-950 border border-slate-700 text-white text-xs font-semibold rounded-lg pl-2 pr-7 py-1 focus:outline-none focus:border-indigo-500 cursor-pointer min-w-[6.5rem] max-w-[10rem] truncate">
                                    <option value="">Semua</option>
                                    @foreach($majors as $m)
                                        <option value="{{ $m->code }}" {{ ($selectedMajor ?? request('major')) === $m->code ? 'selected' : '' }}>
                                            {{ $m->code }} - {{ $m->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- 3. Rombel -->
                            <div class="flex items-center gap-1 bg-slate-900/80 p-1 rounded-xl border border-slate-700">
                                <span class="text-slate-400 font-bold text-[10px] uppercase px-1.5">Rombel:</span>
                                <select name="rombel" onchange="this.form.submit()" 
                                        class="bg-slate-950 border border-slate-700 text-white text-xs font-semibold rounded-lg pl-2 pr-7 py-1 focus:outline-none focus:border-indigo-500 cursor-pointer min-w-[6.5rem]">
                                    <option value="">Semua</option>
                                    @for($r = 1; $r <= 5; $r++)
                                        <option value="{{ $r }}" {{ ($selectedRombel ?? request('rombel')) == $r ? 'selected' : '' }}>
                                            Rombel {{ $r }}
                                        </option>
                                    @endfor
                                </select>
                            </div>

                            @if(request()->hasAny(['grade', 'major', 'rombel']))
                                <a href="{{ route('student-affairs.attendance', request('period') ? ['period' => request('period')] : []) }}" class="px-2.5 py-1 rounded-lg bg-red-500/10 text-red-400 border border-red-500/30 hover:bg-red-500/20 text-xs font-bold transition inline-flex items-center gap-1" title="Reset Filter">
                                    ✕ <span class="hidden sm:inline">Reset</span>
                                </a>
                            @endif
                        </form>
                    @endif
                </div>

                {{-- =========================================================================
                     FITUR UKK: FILTER PERIODE REKAP PRESENSI (HARIAN, MINGGUAN, BULANAN, SEMESTER)
                     - Teks aktif putih solid dan kontras di Mode Terang & Gelap
                     ========================================================================= --}}
                <div class="flex items-center gap-1.5 bg-slate-900/80 p-1 rounded-xl border border-slate-700 shrink-0">
                    <span class="text-slate-400 font-bold text-[10px] uppercase px-2">Periode:</span>
                    <a href="{{ request()->fullUrlWithQuery(['period' => 'daily']) }}" 
                       class="px-2.5 py-1.5 rounded-lg transition text-xs font-semibold {{ ($selectedPeriod ?? 'semester') === 'daily' ? 'period-pill-active bg-indigo-600 text-white font-bold shadow' : 'text-slate-400 hover:text-white' }}"
                       @if(($selectedPeriod ?? 'semester') === 'daily') style="color: #ffffff !important; background-color: #2563eb !important;" @endif>Harian</a>
                    <a href="{{ request()->fullUrlWithQuery(['period' => 'weekly']) }}" 
                       class="px-2.5 py-1.5 rounded-lg transition text-xs font-semibold {{ ($selectedPeriod ?? 'semester') === 'weekly' ? 'period-pill-active bg-indigo-600 text-white font-bold shadow' : 'text-slate-400 hover:text-white' }}"
                       @if(($selectedPeriod ?? 'semester') === 'weekly') style="color: #ffffff !important; background-color: #2563eb !important;" @endif>Mingguan</a>
                    <a href="{{ request()->fullUrlWithQuery(['period' => 'monthly']) }}" 
                       class="px-2.5 py-1.5 rounded-lg transition text-xs font-semibold {{ ($selectedPeriod ?? 'semester') === 'monthly' ? 'period-pill-active bg-indigo-600 text-white font-bold shadow' : 'text-slate-400 hover:text-white' }}"
                       @if(($selectedPeriod ?? 'semester') === 'monthly') style="color: #ffffff !important; background-color: #2563eb !important;" @endif>Bulanan</a>
                    <a href="{{ request()->fullUrlWithQuery(['period' => 'semester']) }}" 
                       class="px-2.5 py-1.5 rounded-lg transition text-xs font-semibold {{ ($selectedPeriod ?? 'semester') === 'semester' ? 'period-pill-active bg-indigo-600 text-white font-bold shadow' : 'text-slate-400 hover:text-white' }}"
                       @if(($selectedPeriod ?? 'semester') === 'semester') style="color: #ffffff !important; background-color: #2563eb !important;" @endif>Semester</a>
                </div>
            </div>
        </div>

        <!-- KONTEN UTAMA: PENCARIAN & TABEL REKAP -->
        <div class="bg-[#0f172a]/90 backdrop-blur-md border border-slate-800 shadow-xl overflow-hidden print:border-none print:shadow-none print:bg-transparent" style="border-radius: 15px;">
            <!-- Kolom Pencarian -->
            <div class="p-4 sm:p-5 border-b border-slate-800/80 bg-slate-900/40 flex items-center justify-between gap-4 print:hidden">
                <div class="text-xs font-bold text-slate-300">
                    Daftar Siswa Kelas {{ $activeClass?->class_name ?? 'Semua Kelas' }} ({{ $studentsAttendance->count() }} Siswa)
                </div>
                <div class="relative w-full sm:w-72">
                    <input type="text" x-model="searchStudent" 
                           placeholder="Cari nama atau NIS siswa..." 
                           class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2 pl-9 text-xs text-white placeholder-slate-500 focus:border-indigo-500 focus:bg-slate-900 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
            </div>

            @if($studentsAttendance->isEmpty())
                <div class="text-center py-16 bg-slate-900/40">
                    <div class="w-12 h-12 rounded-2xl bg-slate-800/80 border border-slate-700 text-slate-400 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    </div>
                    <p class="text-sm font-bold text-white">Tidak Ada Data Siswa</p>
                    <p class="text-xs text-slate-400 mt-1">Belum ada data siswa yang terdaftar pada kelas ini.</p>
                </div>
            @else
                <!-- Mobile Card View -->
                <div class="p-4 space-y-3.5 md:hidden print:hidden">
                    @foreach ($studentsAttendance as $st)
                        <div x-show="!searchStudent || '{{ strtolower(addslashes($st->name)) }} {{ strtolower(addslashes($st->nis)) }}'.includes(searchStudent.toLowerCase())"
                             class="p-4 rounded-xl border border-slate-800 bg-slate-900/60 transition">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <h3 class="font-bold text-white text-sm">{{ $st->name }}</h3>
                                    <p class="text-xs text-slate-400 font-mono">NIS: {{ $st->nis }} @if(!$activeClass) • Kelas {{ $st->class_name }} @endif</p>
                                </div>
                                @if($st->status === 'Kritis')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-red-950/70 text-red-300 border border-red-800/70 animate-pulse">
                                        Panggilan BK
                                    </span>
                                @elseif($st->status === 'Perhatian')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-950/70 text-amber-300 border border-amber-800/70">
                                        Perhatian
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-950/70 text-emerald-300 border border-emerald-800/70">
                                        Tertib
                                    </span>
                                @endif
                            </div>

                            <div class="grid grid-cols-4 gap-2 mt-3 text-center text-xs">
                                <div class="bg-slate-950/50 p-2 rounded-lg border border-slate-800/60">
                                    <span class="text-[10px] text-slate-400 block">Hadir</span>
                                    <strong class="text-emerald-400 font-bold">{{ $st->hadir }}</strong>
                                </div>
                                <div class="bg-slate-950/50 p-2 rounded-lg border border-slate-800/60">
                                    <span class="text-[10px] text-slate-400 block">Sakit</span>
                                    <strong class="text-amber-400 font-bold">{{ $st->sakit }}</strong>
                                </div>
                                <div class="bg-slate-950/50 p-2 rounded-lg border border-slate-800/60">
                                    <span class="text-[10px] text-slate-400 block">Izin</span>
                                    <strong class="text-blue-400 font-bold">{{ $st->izin }}</strong>
                                </div>
                                <div class="bg-slate-950/50 p-2 rounded-lg border border-slate-800/60">
                                    <span class="text-[10px] text-slate-400 block">Alpa</span>
                                    <strong class="{{ $st->alpa > 0 ? 'text-red-400 font-black' : 'text-slate-400' }}">{{ $st->alpa }}</strong>
                                </div>
                            </div>

                            <div class="mt-3 pt-2.5 border-t border-slate-800 flex justify-between items-center text-xs">
                                <span class="text-slate-400">Tingkat Kehadiran:</span>
                                <strong class="{{ $st->rate >= 85 ? 'text-emerald-400' : ($st->rate >= 75 ? 'text-amber-400' : 'text-red-400') }}">
                                    {{ $st->rate }}% ({{ $st->total_sessions }} Sesi)
                                </strong>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Desktop Table View (Cocok juga untuk Format Cetak) -->
                <div class="hidden overflow-x-auto md:block print:block">
                    <table class="min-w-full divide-y divide-slate-800 text-sm print:divide-gray-400 print:text-black print:w-full print:table-fixed">
                        <thead class="bg-slate-900/70 print:bg-gray-100">
                            <tr class="text-left text-xs font-bold text-slate-400 uppercase tracking-wider print:text-black print:text-[10px] print:tracking-normal">
                                <th class="p-3.5 text-center print:border print:border-gray-400 print:py-1.5 print:px-0.5 print:w-[4%] print:whitespace-nowrap nowrap-col">No</th>
                                <th class="p-3.5 print:border print:border-gray-400 print:py-1.5 print:px-2 print:w-[37%]">Siswa (NIS)</th>
                                <th class="p-3.5 text-center print:border print:border-gray-400 print:py-1.5 print:px-0.5 print:w-[7%] print:whitespace-nowrap nowrap-col">Hadir</th>
                                <th class="p-3.5 text-center print:border print:border-gray-400 print:py-1.5 print:px-0.5 print:w-[7%] print:whitespace-nowrap nowrap-col">Sakit</th>
                                <th class="p-3.5 text-center print:border print:border-gray-400 print:py-1.5 print:px-0.5 print:w-[7%] print:whitespace-nowrap nowrap-col">Izin</th>
                                <th class="p-3.5 text-center print:border print:border-gray-400 print:py-1.5 print:px-0.5 print:w-[7%] print:whitespace-nowrap nowrap-col">Alpa</th>
                                <th class="p-3.5 text-center print:border print:border-gray-400 print:py-1 print:px-0.5 print:w-[15.5%] leading-tight">
                                    <span class="block">Tingkat<br class="hidden print:inline"> Kehadiran</span>
                                </th>
                                <th class="p-3.5 text-center print:border print:border-gray-400 print:py-1 print:px-0.5 print:w-[15.5%] leading-tight">
                                    <span class="block">Status<br class="hidden print:inline"> Disiplin</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 print:divide-gray-400">
                            @foreach ($studentsAttendance as $index => $st)
                                <tr x-show="!searchStudent || '{{ strtolower(addslashes($st->name)) }} {{ strtolower(addslashes($st->nis)) }}'.includes(searchStudent.toLowerCase())"
                                    class="hover:bg-slate-800/30 transition print:hover:bg-transparent">
                                    <td class="p-3.5 text-center text-slate-400 font-mono text-xs print:border print:border-gray-400 print:text-black print:py-1.5 print:px-0.5 print:text-[11px] print:whitespace-nowrap">
                                        {{ $index + 1 }}
                                    </td>

                                    <td class="p-3.5 whitespace-nowrap print:whitespace-normal print:border print:border-gray-400 print:py-1.5 print:px-2 student-name-col">
                                        <div class="font-bold text-white print:text-black print:text-xs tracking-tight print:break-normal" style="word-break: keep-all; overflow-wrap: normal;">{{ $st->name }}</div>
                                        <span class="text-xs font-mono text-slate-400 print:text-gray-700 print:text-[10px]">NIS: {{ $st->nis }} @if(!$activeClass) • Kelas {{ $st->class_name }} @endif</span>
                                    </td>

                                    <td class="p-3.5 text-center font-bold text-emerald-400 font-mono print:border print:border-gray-400 print:text-black print:py-1.5 print:px-0.5 print:text-xs print:whitespace-nowrap">
                                        {{ $st->hadir }}
                                    </td>

                                    <td class="p-3.5 text-center font-mono text-amber-300 print:border print:border-gray-400 print:text-black print:py-1.5 print:px-0.5 print:text-xs print:whitespace-nowrap">
                                        {{ $st->sakit }}
                                    </td>

                                    <td class="p-3.5 text-center font-mono text-blue-300 print:border print:border-gray-400 print:text-black print:py-1.5 print:px-0.5 print:text-xs print:whitespace-nowrap">
                                        {{ $st->izin }}
                                    </td>

                                    <td class="p-3.5 text-center font-mono font-bold {{ $st->alpa > 0 ? 'text-red-400 bg-red-950/20' : 'text-slate-400' }} print:border print:border-gray-400 print:text-black print:bg-transparent print:py-1.5 print:px-0.5 print:text-xs print:whitespace-nowrap">
                                        {{ $st->alpa }}
                                    </td>

                                    <td class="p-3.5 text-center whitespace-nowrap print:whitespace-normal print:border print:border-gray-400 print:p-1.5">
                                        <div class="font-bold text-xs {{ $st->rate >= 85 ? 'text-emerald-400' : ($st->rate >= 75 ? 'text-amber-400' : 'text-red-400') }} print:text-black print:text-xs">
                                            {{ $st->rate }}%
                                        </div>
                                        <div class="text-[11px] text-slate-500 font-mono print:text-gray-600 print:text-[10px]">{{ $st->total_sessions }} Sesi Total</div>
                                    </td>

                                    <td class="p-3.5 text-center whitespace-nowrap print:whitespace-normal print:border print:border-gray-400 print:p-1.5">
                                        @if($st->status === 'Kritis')
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-red-950/70 border border-red-700/60 text-red-300 text-xs font-bold shadow-sm print:bg-transparent print:border-none print:text-red-700 print:font-bold print:p-0 print:text-[11px]">
                                                <svg class="w-3.5 h-3.5 text-red-400 print:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                                Panggilan BK
                                            </span>
                                        @elseif($st->status === 'Perhatian')
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-amber-950/70 border border-amber-700/60 text-amber-300 text-xs font-bold shadow-sm print:bg-transparent print:border-none print:text-amber-800 print:font-bold print:p-0 print:text-[11px]">
                                                <span class="h-1.5 w-1.5 rounded-full bg-amber-400 print:hidden"></span>
                                                Perhatian
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-emerald-950/70 border border-emerald-700/60 text-emerald-300 text-xs font-bold shadow-sm print:bg-transparent print:border-none print:text-emerald-700 print:font-bold print:p-0 print:text-[11px]">
                                                <svg class="w-3.5 h-3.5 text-emerald-400 print:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                                Tertib
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- TANDA TANGAN (HANYA MUNCUL DI CETAK A4) -->
        <div class="hidden print:grid grid-cols-2 gap-8 mt-8 pt-4 text-xs text-black page-break-inside-avoid">
            <div class="text-center">
                <p>Mengetahui,</p>
                <p class="font-bold">Kepala Sekolah / Waka Kesiswaan</p>
                <div class="h-16"></div>
                <p class="font-bold underline">_________________________</p>
                <p class="text-[11px]">NIP. ........................................</p>
            </div>
            <div class="text-center">
                <p>Bantul, {{ now()->translatedFormat('d F Y') }}</p>
                <p class="font-bold">{{ auth()->user()->role?->role_name }}</p>
                <div class="h-16"></div>
                <p class="font-bold underline">{{ auth()->user()->name }}</p>
                <p class="text-[11px]">NIP/NUPTK: {{ auth()->user()->nip ?? '........................................' }}</p>
            </div>
        </div>

    </div>

    <!-- STYLE KHUSUS PRINT A4 -->
    <style>
        @media print {
            body {
                background: #ffffff !important;
                color: #000000 !important;
            }
            nav, aside, header, .sidebar, [class*="sidebar"] {
                display: none !important;
            }
            main {
                margin: 0 !important;
                padding: 0 !important;
                max-width: 100% !important;
            }
            @page {
                size: A4 portrait;
                margin: 1cm 0.8cm;
            }
        }
    </style>
</x-app-layout>
