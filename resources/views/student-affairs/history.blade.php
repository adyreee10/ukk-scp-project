<x-app-layout>
    <div x-data="{ 
        filterStatus: 'all',
        filterType: 'all',
        searchStudent: '{{ addslashes(request('search', '')) }}',
        previewDocOpen: false,
        previewDocUrl: '',
        previewDocTitle: '',
        openPreviewDoc(url, title) {
            this.previewDocUrl = url;
            this.previewDocTitle = title;
            this.previewDocOpen = true;
        },
        closePreviewDoc() {
            this.previewDocOpen = false;
            this.previewDocUrl = '';
        }
    }" class="space-y-6">

                <!-- HEADER LAMAN RIWAYAT / REKAP IZIN -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 print:hidden">
            <div>
                <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">
                    {{ in_array(auth()->user()->role?->role_name, ['Guru BK', 'Waka Kurikulum']) ? 'Rekap Izin Siswa' : 'Riwayat Izin Siswa' }}
                </h1>
                <p class="text-xs font-semibold text-slate-400 mt-0.5">
                    {{ in_array(auth()->user()->role?->role_name, ['Guru BK', 'Waka Kurikulum']) ? 'Rekapitulasi izin siswa per kelas yang diverifikasi oleh wali kelas' : 'Arsip permohonan izin KBM yang telah diproses (Disetujui / Ditolak)' }} • {{ now()->translatedFormat('l, d F Y') }}
                </p>
            </div>

            <div class="flex items-center gap-3">
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

                <!-- TOMBOL CETAK DOKUMEN REKAP IZIN (A4) UNTUK WAKA, GURU BK, & WALI KELAS -->
                <button type="button" 
                        onclick="const oldT = document.title; document.title = ''; window.print(); setTimeout(() => { document.title = oldT; }, 1000);" 
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs sm:text-sm font-bold shadow-lg shadow-indigo-600/30 transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    <span>Cetak Rekap (A4)</span>
                </button>
            </div>
        </div>

        <!-- HEADER KOP LAPORAN REKAP IZIN (HANYA MUNCUL SAAT DI-PRINT A4) -->
        <div class="hidden print:block mb-5 border-b-2 border-black pb-3 text-center">
            <h1 class="text-base font-black tracking-wider text-black uppercase">SMK NEGERI 1 BANTUL</h1>
            <h2 class="text-sm font-bold uppercase tracking-wider text-black mt-0.5">LAPORAN REKAPITULASI PERIZINAN SISWA</h2>
            <p class="text-[11px] text-gray-700 mt-1">
                Periode: <strong>{{ $periodLabel }}</strong> &nbsp;•&nbsp; 
                Kelas: <strong>{{ $activeClass?->class_name ?? ($selectedGrade || $selectedMajor || $selectedRombel ? trim(($selectedGrade ?? '') . ' ' . ($selectedMajor ?? '') . ' ' . ($selectedRombel ?? '')) : 'Semua Kelas') }}</strong> &nbsp;•&nbsp; 
                Dicetak: {{ now()->translatedFormat('d F Y, H:i') }} WIB &nbsp;•&nbsp; 
                Oleh: {{ auth()->user()->name }} ({{ auth()->user()->role?->role_name }})
            </p>
        </div>

        <!-- 3 KARTU STATISTIK RINGKAS -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 print:hidden">
            <div class="bg-[#0f172a]/90 backdrop-blur-md p-5 border border-slate-800 shadow-xl" style="border-radius: 15px;">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Riwayat</span>
                <div class="text-3xl font-black text-white mt-1 mb-0.5">
                    {{ $processedLeaves->count() }} <span class="text-xs font-semibold text-slate-400">Permohonan</span>
                </div>
                <p class="text-[11px] font-medium text-slate-400">Seluruh izin yang telah diverifikasi</p>
            </div>

            <div class="bg-[#0f172a]/90 backdrop-blur-md p-5 border border-slate-800 shadow-xl" style="border-radius: 15px;">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Izin Disetujui</span>
                <div class="text-3xl font-black text-emerald-400 mt-1 mb-0.5">
                    {{ $processedLeaves->where('status', 'Approved')->count() }} <span class="text-xs font-semibold text-slate-400">Siswa</span>
                </div>
                <p class="text-[11px] font-medium text-slate-400">Tersinkron otomatis ke presensi</p>
            </div>

            <div class="bg-[#0f172a]/90 backdrop-blur-md p-5 border border-slate-800 shadow-xl" style="border-radius: 15px;">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Izin Ditolak</span>
                <div class="text-3xl font-black text-red-400 mt-1 mb-0.5">
                    {{ $processedLeaves->where('status', 'Rejected')->count() }} <span class="text-xs font-semibold text-slate-400">Siswa</span>
                </div>
                <p class="text-[11px] font-medium text-slate-400">Disertai catatan penolakan resmi</p>
            </div>
        </div>

        <!-- KONTEN UTAMA: FILTER & TABEL RIWAYAT -->
        <div class="bg-[#0f172a]/90 backdrop-blur-md border border-slate-800 shadow-xl overflow-hidden print:border-none print:shadow-none print:bg-transparent print:overflow-visible" style="border-radius: 15px;">
            <!-- Filter Bar & Search -->
            <div class="p-4 sm:p-5 border-b border-slate-800/80 bg-slate-900/40 flex flex-col md:flex-row md:items-center justify-between gap-4 print:hidden">
                <div class="flex flex-wrap items-center gap-3 text-xs">
                    {{-- =========================================================================
                         FITUR UKK: FILTER PERIODE REKAP IZIN (HARIAN, MINGGUAN, BULANAN, SEMESTER)
                         Memungkinkan Waka, Guru BK, dan Wali Kelas memfilter arsip izin
                         sesuai rentang waktu yang dibutuhkan sebelum mencetak dokumen.
                         ========================================================================= --}}
                    <div class="flex items-center gap-1.5 bg-slate-950/60 dark:bg-slate-950/60 p-1 rounded-xl border border-slate-800">
                        <span class="text-slate-400 font-bold text-[10px] uppercase px-2">Periode:</span>
                        <a href="{{ request()->fullUrlWithQuery(['period' => 'daily']) }}" 
                           class="px-2.5 py-1 rounded-lg transition text-xs font-semibold {{ ($selectedPeriod ?? 'semester') === 'daily' ? 'period-pill-active bg-indigo-600 text-white font-bold shadow' : 'text-slate-400 hover:text-white' }}"
                           @if(($selectedPeriod ?? 'semester') === 'daily') style="color: #ffffff !important; background-color: #2563eb !important;" @endif>Harian</a>
                        <a href="{{ request()->fullUrlWithQuery(['period' => 'weekly']) }}" 
                           class="px-2.5 py-1 rounded-lg transition text-xs font-semibold {{ ($selectedPeriod ?? 'semester') === 'weekly' ? 'period-pill-active bg-indigo-600 text-white font-bold shadow' : 'text-slate-400 hover:text-white' }}"
                           @if(($selectedPeriod ?? 'semester') === 'weekly') style="color: #ffffff !important; background-color: #2563eb !important;" @endif>Mingguan</a>
                        <a href="{{ request()->fullUrlWithQuery(['period' => 'monthly']) }}" 
                           class="px-2.5 py-1 rounded-lg transition text-xs font-semibold {{ ($selectedPeriod ?? 'semester') === 'monthly' ? 'period-pill-active bg-indigo-600 text-white font-bold shadow' : 'text-slate-400 hover:text-white' }}"
                           @if(($selectedPeriod ?? 'semester') === 'monthly') style="color: #ffffff !important; background-color: #2563eb !important;" @endif>Bulanan</a>
                        <a href="{{ request()->fullUrlWithQuery(['period' => 'semester']) }}" 
                           class="px-2.5 py-1 rounded-lg transition text-xs font-semibold {{ ($selectedPeriod ?? 'semester') === 'semester' ? 'period-pill-active bg-indigo-600 text-white font-bold shadow' : 'text-slate-400 hover:text-white' }}"
                           @if(($selectedPeriod ?? 'semester') === 'semester') style="color: #ffffff !important; background-color: #2563eb !important;" @endif>Semester</a>
                    </div>

                    <!-- Filter Status -->
                    <div class="flex items-center gap-1.5 bg-slate-950/60 p-1 rounded-xl border border-slate-800">
                        <span class="text-slate-400 font-bold text-[10px] uppercase px-2">Status:</span>
                        <button type="button" @click="filterStatus = 'all'" 
                                :class="filterStatus === 'all' ? 'bg-indigo-600 text-white font-bold shadow' : 'text-slate-400 hover:text-white'"
                                :style="filterStatus === 'all' ? 'color: #ffffff !important; background-color: #2563eb !important;' : ''"
                                class="px-2.5 py-1 rounded-lg transition cursor-pointer text-xs">Semua</button>
                        <button type="button" @click="filterStatus = 'Approved'" 
                                :class="filterStatus === 'Approved' ? 'bg-emerald-600 text-white font-bold shadow' : 'text-slate-400 hover:text-white'"
                                :style="filterStatus === 'Approved' ? 'color: #ffffff !important; background-color: #059669 !important;' : ''"
                                class="px-2.5 py-1 rounded-lg transition cursor-pointer text-xs">Disetujui</button>
                        <button type="button" @click="filterStatus = 'Rejected'" 
                                :class="filterStatus === 'Rejected' ? 'bg-red-600 text-white font-bold shadow' : 'text-slate-400 hover:text-white'"
                                :style="filterStatus === 'Rejected' ? 'color: #ffffff !important; background-color: #dc2626 !important;' : ''"
                                class="px-2.5 py-1 rounded-lg transition cursor-pointer text-xs">Ditolak</button>
                    </div>

                    <!-- Filter Jenis -->
                    <div class="flex items-center gap-1.5 bg-slate-950/60 p-1 rounded-xl border border-slate-800">
                        <span class="text-slate-400 font-bold text-[10px] uppercase px-2">Jenis:</span>
                        <button type="button" @click="filterType = 'all'" 
                                :class="filterType === 'all' ? 'bg-indigo-600 text-white font-bold shadow' : 'text-slate-400 hover:text-white'"
                                :style="filterType === 'all' ? 'color: #ffffff !important; background-color: #2563eb !important;' : ''"
                                class="px-2.5 py-1 rounded-lg transition cursor-pointer text-xs">Semua</button>
                        <button type="button" @click="filterType = 'Sakit'" 
                                :class="filterType === 'Sakit' ? 'bg-amber-600 text-white font-bold shadow' : 'text-slate-400 hover:text-white'"
                                :style="filterType === 'Sakit' ? 'color: #ffffff !important; background-color: #d97706 !important;' : ''"
                                class="px-2.5 py-1 rounded-lg transition cursor-pointer text-xs">Sakit</button>
                        <button type="button" @click="filterType = 'Izin'" 
                                :class="filterType === 'Izin' ? 'bg-blue-600 text-white font-bold shadow' : 'text-slate-400 hover:text-white'"
                                :style="filterType === 'Izin' ? 'color: #ffffff !important; background-color: #2563eb !important;' : ''"
                                class="px-2.5 py-1 rounded-lg transition cursor-pointer text-xs">Izin</button>
                        <button type="button" @click="filterType = 'Potong Jam Pelajaran'" 
                                :class="filterType === 'Potong Jam Pelajaran' ? 'bg-teal-600 text-white font-bold shadow' : 'text-slate-400 hover:text-white'"
                                :style="filterType === 'Potong Jam Pelajaran' ? 'color: #ffffff !important; background-color: #0d9488 !important;' : ''"
                                class="px-2.5 py-1 rounded-lg transition cursor-pointer text-xs">Potong Jam</button>
                    </div>

                    <!-- Filter 3 Kriteria: Tingkat, Jurusan, Rombel (Waka & Guru BK) -->
                    @if(in_array(auth()->user()->role?->role_name, ['Guru BK', 'Waka Kurikulum']))
                        <form method="GET" action="{{ route('student-affairs.leaves.history') }}" class="flex flex-wrap items-center gap-2">
                            @if(request('period')) <input type="hidden" name="period" value="{{ request('period') }}"> @endif
                            <!-- 1. Tingkat -->
                            <div class="flex items-center gap-1 bg-slate-950/60 p-1 rounded-xl border border-slate-800">
                                <span class="text-slate-400 font-bold text-[10px] uppercase px-1.5">Tingkat:</span>
                                <select name="grade" onchange="this.form.submit()" 
                                        class="bg-slate-900 border border-slate-700 text-white text-xs font-semibold rounded-lg pl-2.5 pr-8 py-1 focus:outline-none focus:border-indigo-500 cursor-pointer min-w-[7rem]">
                                    <option value="">Semua</option>
                                    <option value="X" {{ ($selectedGrade ?? request('grade')) === 'X' ? 'selected' : '' }}>Kelas 10 (X)</option>
                                    <option value="XI" {{ ($selectedGrade ?? request('grade')) === 'XI' ? 'selected' : '' }}>Kelas 11 (XI)</option>
                                    <option value="XII" {{ ($selectedGrade ?? request('grade')) === 'XII' ? 'selected' : '' }}>Kelas 12 (XII)</option>
                                </select>
                            </div>

                            <!-- 2. Jurusan -->
                            <div class="flex items-center gap-1 bg-slate-950/60 p-1 rounded-xl border border-slate-800">
                                <span class="text-slate-400 font-bold text-[10px] uppercase px-1.5">Jurusan:</span>
                                <select name="major" onchange="this.form.submit()" 
                                        class="bg-slate-900 border border-slate-700 text-white text-xs font-semibold rounded-lg pl-2.5 pr-8 py-1 focus:outline-none focus:border-indigo-500 cursor-pointer min-w-[6.5rem]">
                                    <option value="">Semua</option>
                                    @foreach($majors as $m)
                                        <option value="{{ $m->code }}" {{ ($selectedMajor ?? request('major')) === $m->code ? 'selected' : '' }}>
                                            {{ $m->code }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- 3. Rombel -->
                            <div class="flex items-center gap-1 bg-slate-950/60 p-1 rounded-xl border border-slate-800">
                                <span class="text-slate-400 font-bold text-[10px] uppercase px-1.5">Rombel:</span>
                                <select name="rombel" onchange="this.form.submit()" 
                                        class="bg-slate-900 border border-slate-700 text-white text-xs font-semibold rounded-lg pl-2.5 pr-8 py-1 focus:outline-none focus:border-indigo-500 cursor-pointer min-w-[6.5rem]">
                                    <option value="">Semua</option>
                                    @for($r = 1; $r <= 5; $r++)
                                        <option value="{{ $r }}" {{ ($selectedRombel ?? request('rombel')) == $r ? 'selected' : '' }}>
                                            {{ $r }}
                                        </option>
                                    @endfor
                                </select>
                            </div>

                            @if(request()->hasAny(['grade', 'major', 'rombel']))
                                <a href="{{ route('student-affairs.leaves.history', request('period') ? ['period' => request('period')] : []) }}" class="px-2 py-1 rounded-lg bg-red-500/10 text-red-400 border border-red-500/30 hover:bg-red-500/20 text-xs font-bold" title="Reset Filter">✕</a>
                            @endif
                        </form>
                    @endif
                </div>

                <!-- Input Pencarian (Universal: Nama Siswa, NIS, Kelas, Jenis Izin, Alasan) -->
                <form method="GET" action="{{ route('student-affairs.leaves.history') }}" class="relative w-full md:w-72 flex items-center">
                    @if(request('period')) <input type="hidden" name="period" value="{{ request('period') }}"> @endif
                    @if(request('grade')) <input type="hidden" name="grade" value="{{ request('grade') }}"> @endif
                    @if(request('major')) <input type="hidden" name="major" value="{{ request('major') }}"> @endif
                    @if(request('rombel')) <input type="hidden" name="rombel" value="{{ request('rombel') }}"> @endif
                    @if(request('class_id')) <input type="hidden" name="class_id" value="{{ request('class_id') }}"> @endif
                    <input type="text" name="search" x-model="searchStudent" 
                           placeholder="Cari siswa, NIS, kelas, alasan..." 
                           class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2 pl-9 pr-8 text-xs text-white placeholder-slate-500 focus:border-indigo-500 focus:bg-slate-900 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <template x-if="searchStudent">
                        <button type="button" @click="searchStudent = ''; $nextTick(() => $el.closest('form').submit())" class="absolute right-2.5 text-slate-400 hover:text-white cursor-pointer text-xs p-1" title="Hapus Pencarian">✕</button>
                    </template>
                </form>
            </div>

            <!-- TABEL / CARD VIEW RIWAYAT -->
            @if($processedLeaves->isEmpty())
                <div class="text-center py-16 bg-slate-900/40">
                    <div class="w-12 h-12 rounded-2xl bg-slate-800/80 border border-slate-700 text-slate-400 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <p class="text-sm font-bold text-white">Belum Ada Riwayat Izin</p>
                    <p class="text-xs text-slate-400 mt-1">Belum ada permohonan izin yang pernah diproses.</p>
                </div>
            @else
                <!-- Mobile Card View -->
                <div class="p-4 space-y-3.5 md:hidden print:hidden">
                    @foreach ($processedLeaves as $leave)
                        <div x-show="(filterStatus === 'all' || '{{ $leave->status }}' === filterStatus) && (filterType === 'all' || '{{ $leave->leave_type }}' === filterType) && (!searchStudent || '{{ strtolower(addslashes($leave->student_name . ' ' . ($leave->student_nis ?? '') . ' ' . $leave->class_name . ' ' . $leave->leave_type . ' ' . $leave->reason)) }}'.includes(searchStudent.toLowerCase().trim()))"
                             class="p-4 rounded-xl border border-slate-800 bg-slate-900/60 hover:bg-slate-800/40 transition">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase border {{ $leave->status === 'Approved' ? 'bg-emerald-950/60 text-emerald-300 border-emerald-800/60' : 'bg-red-950/60 text-red-300 border-red-800/60' }}">
                                            {{ $leave->status === 'Approved' ? 'Disetujui' : 'Ditolak' }}
                                        </span>
                                        <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase border {{ $leave->leave_type === 'Sakit' ? 'bg-amber-950/60 text-amber-300 border-amber-800/60' : (in_array($leave->leave_type, ['Potong Jam Pelajaran', 'Dispensasi']) ? 'bg-teal-950/60 text-teal-300 border-teal-800/60' : 'bg-blue-950/60 text-blue-300 border-blue-800/60') }}">
                                            {{ $leave->leave_type }}
                                        </span>

                                    </div>
                                    <h3 class="font-bold text-white text-sm mt-1.5">{{ $leave->student_name }}</h3>
                                    <p class="text-xs text-slate-400">{{ $leave->class_name }}</p>
                                </div>
                                <span class="text-xs font-mono text-slate-300 shrink-0">
                                    {{ \Carbon\Carbon::parse($leave->start_date)->translatedFormat('d M Y') }}
                                </span>
                            </div>

                            <div class="mt-2 text-xs text-slate-300">
                                <span class="text-[10px] uppercase font-bold text-slate-500">Alasan:</span> {{ $leave->reason }}
                            </div>

                            @if($leave->status === 'Rejected' && !empty($leave->rejection_note))
                                <div class="mt-2 text-xs bg-red-950/40 border border-red-800/50 rounded-lg p-2.5 text-red-200">
                                    <span class="text-[10px] uppercase font-bold text-red-400 block mb-0.5">Alasan Penolakan:</span>
                                    {{ $leave->rejection_note }}
                                </div>
                            @endif

                            @if($leave->document_path)
                                <div class="mt-2.5">
                                    <button type="button" 
                                            @click="openPreviewDoc('{{ url('media/' . $leave->document_path) }}', 'Surat Izin - {{ addslashes($leave->student_name) }}')"
                                            class="inline-flex items-center gap-1.5 text-xs font-bold text-indigo-400 hover:text-indigo-300 transition hover:underline cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                        Lihat Bukti Izin →
                                    </button>
                                </div>
                            @endif

                            <div class="mt-3 pt-2.5 border-t border-slate-800 flex items-center justify-between text-[11px] text-slate-400">
                                <span>Oleh: <strong class="text-slate-300">{{ $leave->verifier_name }}</strong></span>
                                <span>{{ $leave->processed_date }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Desktop Table View (Cocok juga untuk Cetak Dokumen A4) -->
                <div class="hidden overflow-x-auto md:block print:block print:w-full print:overflow-visible">
                    <table class="min-w-full divide-y divide-slate-800 text-sm print:divide-gray-400 print:text-black print:w-full print:table-fixed print:border-collapse print:border print:border-gray-400 print-leave-table">
                        <thead class="bg-slate-900/70 print:bg-gray-100">
                            <tr class="text-left text-xs font-bold text-slate-400 uppercase tracking-wider print:text-black print:text-[10px]">
                                <th class="p-3.5 text-center col-print-no print:border print:border-gray-400 print:py-1.5 print:px-1">No</th>
                                <th class="p-3.5 col-print-student print:border print:border-gray-400 print:py-1.5 print:px-2">Siswa & Kelas</th>
                                <th class="p-3.5 col-print-date print:border print:border-gray-400 print:py-1.5 print:px-1.5">Tanggal Izin</th>
                                <th class="p-3.5 col-print-reason print:border print:border-gray-400 print:py-1.5 print:px-2">Jenis & Alasan</th>
                                <th class="p-3.5 text-center col-print-doc print:border print:border-gray-400 print:py-1.5 print:px-1">Dokumen</th>
                                <th class="p-3.5 text-center col-print-status print:border print:border-gray-400 print:py-1.5 print:px-1">Status</th>
                                <th class="p-3.5 col-print-verifier print:border print:border-gray-400 print:py-1.5 print:px-2.5">Verifikator</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 print:divide-gray-400">
                            @foreach ($processedLeaves as $idx => $leave)
                                <tr x-show="(filterStatus === 'all' || '{{ $leave->status }}' === filterStatus) && (filterType === 'all' || '{{ $leave->leave_type }}' === filterType) && (!searchStudent || '{{ strtolower(addslashes($leave->student_name . ' ' . ($leave->student_nis ?? '') . ' ' . $leave->class_name . ' ' . $leave->leave_type . ' ' . $leave->reason)) }}'.includes(searchStudent.toLowerCase().trim()))"
                                    class="hover:bg-slate-800/30 transition print:hover:bg-transparent">
                                    <td class="p-3.5 text-center text-slate-400 font-mono text-xs col-print-no print:border print:border-gray-400 print:text-black print:py-1.5 print:px-1 print:text-[10px]">
                                        {{ $idx + 1 }}
                                    </td>
                                    <td class="p-3.5 col-print-student print:border print:border-gray-400 print:py-1.5 print:px-2">
                                        <div class="font-bold text-white print:text-black print:text-xs">{{ $leave->student_name }}</div>
                                        <span class="inline-block mt-0.5 rounded-md bg-indigo-950/60 px-2 py-0.5 text-[11px] font-bold text-indigo-300 border border-indigo-800/60 print:bg-transparent print:border-none print:text-gray-700 print:p-0">
                                            {{ $leave->class_name }} {{ $leave->student_nis ? '(' . $leave->student_nis . ')' : '' }}
                                        </span>
                                    </td>

                                    <td class="p-3.5 text-xs font-mono text-slate-300 whitespace-nowrap col-print-date print:whitespace-normal print:border print:border-gray-400 print:text-black print:py-1.5 print:px-1.5 print:text-[11px]">
                                        <div>{{ \Carbon\Carbon::parse($leave->start_date)->translatedFormat('d M Y') }}</div>
                                        @if($leave->start_date !== $leave->end_date)
                                            <div class="text-[11px] text-slate-400 print:text-gray-600">s/d {{ \Carbon\Carbon::parse($leave->end_date)->translatedFormat('d M Y') }}</div>
                                        @endif
                                        @if($leave->period_label)
                                            <div class="text-[10px] text-indigo-400 print:text-gray-600 font-bold mt-0.5">{{ $leave->period_label }}</div>
                                        @endif
                                    </td>

                                    <td class="p-3.5 max-w-xs col-print-reason print:border print:border-gray-400 print:text-black print:py-1.5 print:px-2">
                                        <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase border {{ $leave->leave_type === 'Sakit' ? 'bg-amber-950/60 text-amber-300 border-amber-800/60' : (in_array($leave->leave_type, ['Potong Jam Pelajaran', 'Dispensasi']) ? 'bg-teal-950/60 text-teal-300 border-teal-800/60' : 'bg-blue-950/60 text-blue-300 border-blue-800/60') }} print:bg-transparent print:border-none print:text-black print:p-0 print:font-bold">
                                            {{ $leave->leave_type }}
                                        </span>
                                        <p class="text-xs text-slate-300 print:text-gray-800 line-clamp-2 print:line-clamp-none print:text-[11px]">{{ $leave->reason }}</p>
                                    </td>

                                    <td class="p-3.5 text-center whitespace-nowrap col-print-doc print:border print:border-gray-400 print:text-black print:py-1.5 print:px-1">
                                        @if($leave->document_path)
                                            <button type="button" 
                                                    @click="openPreviewDoc('{{ url('media/' . $leave->document_path) }}', 'Surat Izin - {{ addslashes($leave->student_name) }}')"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-indigo-950/60 border border-indigo-700/60 text-indigo-300 hover:bg-indigo-900/60 text-xs font-bold transition cursor-pointer print:hidden">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                Lihat
                                            </button>
                                            <span class="hidden print:inline text-xs font-semibold">Ada</span>
                                        @else
                                            <span class="text-slate-500 print:text-gray-500 text-xs italic">-</span>
                                        @endif
                                    </td>

                                    <td class="p-3.5 text-center col-print-status print:border print:border-gray-400 print:text-black print:py-1.5 print:px-1">
                                        @if($leave->status === 'Approved')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-emerald-950/70 border border-emerald-700/60 text-emerald-300 text-xs font-bold shadow-sm whitespace-nowrap print:bg-transparent print:border-none print:text-emerald-800 print:font-bold print:p-0 print:text-[11px]">
                                                <svg class="w-3.5 h-3.5 text-emerald-400 print:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                                Disetujui
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-red-950/70 border border-red-700/60 text-red-300 text-xs font-bold shadow-sm whitespace-nowrap print:bg-transparent print:border-none print:text-red-800 print:font-bold print:p-0 print:text-[11px]">
                                                <svg class="w-3.5 h-3.5 text-red-400 print:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                Ditolak
                                            </span>
                                        @endif
                                    </td>

                                    <td class="p-3.5 text-xs col-print-verifier print:border print:border-gray-400 print:text-black print:py-2 print:px-3">
                                        <div class="font-bold text-slate-200 print:text-black print:text-xs leading-snug break-words" style="white-space: normal !important; word-break: break-word !important; overflow-wrap: break-word !important;">
                                            {{ $leave->verifier_name }}
                                        </div>
                                        <div class="text-[11px] text-slate-400 print:text-gray-600 font-mono print:text-[10px] mt-1" style="white-space: normal !important;">
                                            {{ $leave->processed_date }}
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- MODAL PRATINJAU DOKUMEN / SURAT BUKTI -->
        <div x-show="previewDocOpen" 
             x-cloak 
             class="fixed inset-0 z-50 overflow-y-auto" 
             style="display: none;"
             @keydown.escape.window="closePreviewDoc()">
            <div class="fixed inset-0 bg-black/80 backdrop-blur-sm transition-opacity" 
                 @click="closePreviewDoc()"></div>

            <div class="flex min-h-full items-center justify-center p-4 text-center">
                <div class="relative transform overflow-hidden rounded-2xl bg-[#0f172a] border border-slate-700 text-left shadow-2xl transition-all w-full max-w-2xl p-5"
                     @click.stop>
                    <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                        <h3 class="text-sm font-bold text-white" x-text="previewDocTitle"></h3>
                        <div class="flex items-center gap-2">
                            <a :href="previewDocUrl" target="_blank" 
                               class="text-xs text-indigo-400 hover:text-indigo-300 font-bold hover:underline flex items-center gap-1">
                                <span>Buka Tab Baru</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                            </a>
                            <button type="button" @click="closePreviewDoc()" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 transition cursor-pointer">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                    </div>

                    <div class="mt-4 flex items-center justify-center bg-slate-950/70 rounded-xl p-2 min-h-[300px] border border-slate-800/80 overflow-hidden">
                        <template x-if="previewDocUrl && (previewDocUrl.endsWith('.pdf') || previewDocUrl.includes('.pdf'))">
                            <iframe :src="previewDocUrl" class="w-full h-[500px] rounded-lg border-0"></iframe>
                        </template>
                        <template x-if="previewDocUrl && !(previewDocUrl.endsWith('.pdf') || previewDocUrl.includes('.pdf'))">
                            <img :src="previewDocUrl" alt="Surat Keterangan" class="max-h-[500px] w-auto max-w-full rounded-lg object-contain shadow-md" />
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- =========================================================================
             FITUR UKK: TANDA TANGAN DOKUMEN REKAP IZIN (CETAK A4)
             Format legalitas resmi dengan mengetahui Kepala Sekolah / Waka Kesiswaan
             dan verifikator guru penanggung jawab.
             ========================================================================= --}}
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
            nav, aside, header, .sidebar, [class*="sidebar"], .print\:hidden {
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

            /* Penataan Kolom & Lebar Presisi Tabel Cetak A4 */
            table.print-leave-table {
                width: 100% !important;
                table-layout: fixed !important;
                border-collapse: collapse !important;
            }
            table.print-leave-table th,
            table.print-leave-table td {
                overflow: visible !important;
                white-space: normal !important;
                word-break: normal !important;
                overflow-wrap: break-word !important;
                box-sizing: border-box !important;
            }
            .col-print-no {
                width: 4% !important;
                text-align: center !important;
            }
            .col-print-student {
                width: 21% !important;
            }
            .col-print-date {
                width: 14% !important;
            }
            .col-print-reason {
                width: 25% !important;
            }
            .col-print-doc {
                width: 8% !important;
                text-align: center !important;
            }
            .col-print-status {
                width: 10% !important;
                text-align: center !important;
            }
            .col-print-verifier {
                width: 18% !important;
                padding-left: 10px !important;
                padding-right: 10px !important;
                text-align: left !important;
            }
            .col-print-verifier div {
                white-space: normal !important;
                word-break: break-word !important;
                overflow-wrap: break-word !important;
                line-height: 1.35 !important;
            }
        }
    </style>
</x-app-layout>
