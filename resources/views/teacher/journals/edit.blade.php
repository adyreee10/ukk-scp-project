<x-app-layout>
    <div x-data="teacherJournalEditManager()" class="space-y-4">

        {{-- 
            ========================================================================
            Fitur Header & Navigasi Halaman Edit Presensi Siswa
            ========================================================================
            Didedikasikan khusus untuk memperbarui data presensi siswa dari Riwayat Jurnal
        --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pb-2 border-b border-slate-800/80">
            <div>
                <a href="{{ route('teacher.journals.history') }}" class="inline-flex items-center text-xs font-bold text-indigo-400 hover:text-indigo-300 transition hover:underline mb-1">
                    ← Kembali ke Riwayat Jurnal KBM
                </a>
                <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">
                    Edit Presensi Siswa
                </h1>
                <p class="text-xs text-slate-400 mt-0.5 font-medium">
                    Perbarui catatan kehadiran siswa untuk sesi KBM {{ $schedule->subject }} • Kelas {{ $schedule->schoolClass?->class_name }}
                </p>
            </div>
            
            <div class="shrink-0">
                <span class="px-3.5 py-1.5 rounded-xl text-xs font-black bg-[#0f172a]/90 text-indigo-300 border border-slate-800 shadow-sm flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-indigo-400 animate-pulse"></span>
                    <span>{{ \Carbon\Carbon::parse($journal->date)->translatedFormat('l, d F Y') }} • {{ $schedule->period_label }}</span>
                </span>
            </div>
        </div>

        <!-- BANNER STATUS MASA EDIT AKTIF (24 JAM) -->
        <div class="p-4 sm:p-5 bg-white dark:bg-[#0f172a]/90 backdrop-blur-md rounded-2xl border border-indigo-200 dark:border-indigo-500/50 shadow-sm dark:shadow-xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-200 dark:bg-indigo-500/20 dark:text-indigo-400 dark:border-transparent flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 class="text-base font-extrabold text-slate-900 dark:text-white">Masa Edit Presensi Aktif</h2>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-indigo-50 text-indigo-700 border border-indigo-200 dark:bg-indigo-950/80 dark:text-indigo-300 dark:border-indigo-600/70">
                            Batas Toleransi 24 Jam
                        </span>
                    </div>
                    <p class="text-xs text-slate-600 dark:text-slate-300 mt-0.5">
                        Dokumentasi materi dan foto KBM terkunci demi integritas jurnal. Anda dapat memperbarui data kehadiran siswa di bawah ini hingga pukul 23:59 WIB.
                    </p>
                </div>
            </div>
        </div>

        {{-- 
            ========================================================================
            Fitur Form Update Presensi Siswa (Metode PUT ke teacher.journals.update-attendance)
            ========================================================================
        --}}
        <form method="POST" action="{{ route('teacher.journals.update-attendance', $journal->id) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <!-- KARTU 1: Informasi Otomatis Kelas, Ruang, dan Tanggal KBM -->
            <div class="bg-white dark:bg-[#0f172a]/90 backdrop-blur-md p-3.5 sm:p-4 border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-xl space-y-3" style="border-radius: 15px;">
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                        Kelas & Mata Pelajaran
                    </label>
                    <input type="text" readonly 
                           value="{{ $schedule->schoolClass?->class_name }} — {{ $schedule->subject }}" 
                           class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/80 px-3.5 py-2.5 text-xs sm:text-sm font-bold text-slate-900 dark:text-white focus:outline-none cursor-default shadow-sm dark:shadow-inner" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                            Ruang Kelas / Laboratorium
                        </label>
                        <input type="text" readonly 
                               value="{{ $schedule->room?->room_name ?? '-' }}" 
                               class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/80 px-3.5 py-2.5 text-xs sm:text-sm font-bold text-slate-900 dark:text-white focus:outline-none cursor-default shadow-sm dark:shadow-inner" />
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                            Tanggal Pelaksanaan KBM
                        </label>
                        <input type="text" readonly 
                               value="{{ \Carbon\Carbon::parse($journal->date)->format('d/m/Y') }}" 
                               class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/80 px-3.5 py-2.5 text-xs sm:text-sm font-bold text-slate-900 dark:text-white focus:outline-none cursor-default shadow-sm dark:shadow-inner" />
                    </div>
                </div>
            </div>

            <!-- GRID 2 KOLOM: DOKUMENTASI (TERKUNCI) & FORM EDIT PRESENSI SISWA -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">
                
                <!-- KIRI (5 Kolom): Ringkasan Dokumentasi KBM & Tombol Aksi Simpan -->
                <div class="lg:col-span-5 space-y-3">
                    <div class="bg-white dark:bg-[#0f172a]/90 backdrop-blur-md p-4 border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-xl space-y-3" style="border-radius: 15px;">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-200 dark:border-slate-800">
                            <h2 class="text-sm font-extrabold text-slate-900 dark:text-white">Dokumentasi Pembelajaran</h2>
                            <span class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400">✓ Tersimpan</span>
                        </div>

                        <div>
                            <span class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Materi / Topik KBM:</span>
                            <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 text-xs text-slate-800 dark:text-slate-200 leading-relaxed whitespace-pre-line max-h-32 overflow-y-auto">
                                {{ $journal->topic_description }}
                            </div>
                        </div>

                        <div>
                            <span class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Bukti Foto Real-Time KBM:</span>
                            @php
                                $photoSrc = $journal->photo_path 
                                    ? (str_starts_with($journal->photo_path, 'http') ? $journal->photo_path : url('media/' . ltrim($journal->photo_path, '/')))
                                    : $journal->photo_url;
                            @endphp
                            @if($photoSrc)
                                <div class="relative group rounded-xl overflow-hidden border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-md bg-slate-900">
                                    <a href="{{ $photoSrc }}" target="_blank" rel="noopener noreferrer" title="Klik untuk membuka foto bukti KBM penuh">
                                        <img src="{{ $photoSrc }}" alt="Bukti KBM" class="w-full max-h-48 object-cover rounded-xl transition duration-200 group-hover:scale-105">
                                        <div class="absolute inset-0 bg-slate-950/40 opacity-0 group-hover:opacity-100 transition flex items-center justify-center text-white text-xs font-bold gap-1.5 backdrop-blur-[1px]">
                                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            <span>Buka Foto Penuh</span>
                                        </div>
                                    </a>
                                </div>
                            @else
                                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs text-slate-500 dark:text-slate-400 text-center">
                                    Tidak ada foto dokumentasi
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Tombol Aksi Simpan Presensi -->
                    <div class="bg-white dark:bg-[#0f172a]/90 backdrop-blur-md p-3.5 border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-xl space-y-2" style="border-radius: 15px;">
                        <button type="submit" 
                                style="border-radius: 12px; color: #ffffff !important;"
                                class="w-full py-2.5 px-4 font-bold text-xs scp-btn-gradient cursor-pointer tracking-wider uppercase shadow-md hover:shadow-lg transition">
                            Simpan Perubahan Presensi Siswa
                        </button>
                        <a href="{{ route('teacher.journals.history') }}" 
                           class="w-full py-2 px-4 block text-center rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold transition">
                            Batal & Kembali ke Riwayat
                        </a>
                        <p class="text-[10px] text-center text-slate-500 dark:text-slate-400">
                            Perubahan kehadiran siswa akan otomatis tersinkronisasi ke rekapitulasi presensi sekolah
                        </p>
                    </div>
                </div>

                <!-- KANAN (7 Kolom): Formulir Presensi Siswa -->
                <div class="lg:col-span-7">
                    <div class="bg-white dark:bg-[#0f172a]/90 backdrop-blur-md p-4 border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-xl" style="border-radius: 15px;">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 pb-2.5 border-b border-slate-200 dark:border-slate-800 mb-2.5">
                            <div>
                                <h2 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white">Presensi Siswa</h2>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                    Kelas {{ $schedule->schoolClass?->class_name }} ({{ $students->count() }} Siswa)
                                </p>
                            </div>
                            
                            <!-- Tombol Cepat: Set Semua Hadir -->
                            <button type="button" @click="setAll('Hadir')" 
                                    class="text-xs font-bold px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:hover:bg-emerald-900 dark:border-emerald-800/60 transition cursor-pointer self-start sm:self-auto">
                                ✓ Set Semua Hadir
                            </button>
                        </div>

                        <!-- LIVE COUNTER STATUS KEHADIRAN SISWA -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5 mb-2.5">
                            <div class="px-2.5 py-1.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 dark:bg-emerald-950/40 dark:border-emerald-800/50 dark:text-emerald-300 flex items-center justify-between">
                                <span class="text-[11px] font-bold">Hadir</span>
                                <span x-text="counts.Hadir" class="text-xs sm:text-sm font-black text-emerald-950 dark:text-white">0</span>
                            </div>
                            <div class="px-2.5 py-1.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-700 dark:bg-amber-950/40 dark:border-amber-800/50 dark:text-amber-300 flex items-center justify-between">
                                <span class="text-[11px] font-bold">Sakit</span>
                                <span x-text="counts.Sakit" class="text-xs sm:text-sm font-black text-amber-950 dark:text-white">0</span>
                            </div>
                            <div class="px-2.5 py-1.5 rounded-xl bg-blue-50 border border-blue-200 text-blue-700 dark:bg-blue-950/40 dark:border-blue-800/50 dark:text-blue-300 flex items-center justify-between">
                                <span class="text-[11px] font-bold">Izin</span>
                                <span x-text="counts.Izin" class="text-xs sm:text-sm font-black text-blue-950 dark:text-white">0</span>
                            </div>
                            <div class="px-2.5 py-1.5 rounded-xl bg-red-50 border border-red-200 text-red-700 dark:bg-red-950/40 dark:border-red-800/50 dark:text-red-300 flex items-center justify-between">
                                <span class="text-[11px] font-bold">Alpa</span>
                                <span x-text="counts.Alpa" class="text-xs sm:text-sm font-black text-red-950 dark:text-white">0</span>
                            </div>
                        </div>

                        <!-- KOLOM PENCARIAN SISWA -->
                        <div class="mb-2.5 relative">
                            <input type="text" x-model="searchStudent" 
                                   placeholder="Cari nama siswa di kelas ini..." 
                                   class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 px-3.5 py-1.5 pl-9 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:border-indigo-500 focus:bg-white dark:focus:bg-slate-900 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>

                        <!-- DAFTAR SISWA -->
                        @php
                            $existingAttendances = $journal->attendances->keyBy('student_id');
                        @endphp
                        <div class="space-y-2 max-h-[300px] sm:max-h-[360px] xl:max-h-[400px] overflow-y-auto overscroll-contain pr-1 relative">
                            @foreach ($students as $index => $student)
                                @php
                                    $att = $existingAttendances->get($student->id);
                                    $approvedLeave = isset($approvedLeavesToday) ? $approvedLeavesToday->get($student->id) : null;
                                    $currentStatus = $att ? $att->status : ($approvedLeave ? ($approvedLeave->leave_type === 'Sakit' ? 'Sakit' : 'Izin') : 'Hadir');
                                    $currentNote = $att ? $att->note : ($approvedLeave ? 'Alasan ACC: ' . $approvedLeave->reason : '');
                                @endphp
                                <div x-show="!searchStudent || '{{ strtolower(addcslashes($student->name, "'\\")) }}'.includes(searchStudent.toLowerCase())"
                                     class="p-2.5 sm:p-3 rounded-xl border relative {{ $approvedLeave ? 'border-indigo-300 bg-indigo-50/60 dark:border-indigo-500/40 dark:bg-indigo-950/20' : 'border-slate-200 bg-slate-50/70 hover:bg-slate-100 dark:border-slate-800/80 dark:bg-slate-900/40 dark:hover:bg-slate-800/40' }} flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 transition">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <span class="text-xs font-bold text-slate-400 dark:text-slate-500 w-5 text-right">{{ $index + 1 }}.</span>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <p class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ $student->name }}</p>
                                                @if($approvedLeave)
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-black {{ $approvedLeave->leave_type === 'Sakit' ? 'bg-amber-100 text-amber-800 border border-amber-300 dark:bg-amber-950/80 dark:text-amber-300 dark:border-amber-600/70' : 'bg-blue-100 text-blue-800 border border-blue-300 dark:bg-blue-950/80 dark:text-blue-300 dark:border-blue-600/70' }}">
                                                        ✓ ACC: {{ $approvedLeave->leave_type }}
                                                    </span>
                                                @endif
                                            </div>
                                            <p class="text-[10px] text-slate-500 dark:text-slate-400">@<span>{{ $student->username }}</span></p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-1.5 shrink-0 self-end sm:self-auto">
                                        <label class="cursor-pointer relative">
                                            <input type="radio" name="attendances[{{ $student->id }}]" value="Hadir" 
                                                   @change="updateCounts()"
                                                   data-has-approved-leave="{{ $approvedLeave ? '1' : '' }}"
                                                   class="sr-only peer" 
                                                   {{ $currentStatus === 'Hadir' ? 'checked' : '' }}>
                                            <span class="attendance-pill attendance-pill-h px-2.5 py-1 rounded-md text-[11px] font-bold border transition inline-block">H</span>
                                        </label>
                                        <label class="cursor-pointer relative">
                                            <input type="radio" name="attendances[{{ $student->id }}]" value="Sakit" 
                                                   @change="updateCounts()"
                                                   data-has-approved-leave="{{ $approvedLeave ? '1' : '' }}"
                                                   class="sr-only peer" 
                                                   {{ $currentStatus === 'Sakit' ? 'checked' : '' }}>
                                            <span class="attendance-pill attendance-pill-s px-2.5 py-1 rounded-md text-[11px] font-bold border transition inline-block">S</span>
                                        </label>
                                        <label class="cursor-pointer relative">
                                            <input type="radio" name="attendances[{{ $student->id }}]" value="Izin" 
                                                   @change="updateCounts()"
                                                   data-has-approved-leave="{{ $approvedLeave ? '1' : '' }}"
                                                   class="sr-only peer" 
                                                   {{ $currentStatus === 'Izin' ? 'checked' : '' }}>
                                            <span class="attendance-pill attendance-pill-i px-2.5 py-1 rounded-md text-[11px] font-bold border transition inline-block">I</span>
                                        </label>
                                        <label class="cursor-pointer relative">
                                            <input type="radio" name="attendances[{{ $student->id }}]" value="Alpa" 
                                                   @change="updateCounts()"
                                                   data-has-approved-leave="{{ $approvedLeave ? '1' : '' }}"
                                                   class="sr-only peer" 
                                                   {{ $currentStatus === 'Alpa' ? 'checked' : '' }}>
                                            <span class="attendance-pill attendance-pill-a px-2.5 py-1 rounded-md text-[11px] font-bold border transition inline-block">A</span>
                                        </label>
                                    </div>

                                    <input type="text" name="attendance_notes[{{ $student->id }}]" 
                                           value="{{ $currentNote }}"
                                           placeholder="{{ $approvedLeave ? 'Alasan ACC: ' . $approvedLeave->reason : 'Catatan khusus (opsional)...' }}" 
                                           class="w-full sm:w-44 text-[11px] rounded-lg bg-white dark:bg-slate-950/80 border border-slate-200 dark:border-slate-700/80 text-slate-900 dark:text-slate-200 placeholder-slate-400 dark:placeholder-slate-500 px-2.5 py-1 focus:border-indigo-500 focus:outline-none">
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-3 pt-3 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400 font-semibold">
                            <span>Keterangan:</span>
                            <div class="flex gap-2.5">
                                <span class="text-emerald-600 dark:text-emerald-400 font-bold">H = Hadir</span>
                                <span class="text-amber-600 dark:text-amber-400 font-bold">S = Sakit</span>
                                <span class="text-blue-600 dark:text-blue-400 font-bold">I = Izin</span>
                                <span class="text-red-600 dark:text-red-400 font-bold">A = Alpa</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>

    </div>

    <script>
    function teacherJournalEditManager() {
        return {
            searchStudent: '',
            counts: {
                Hadir: 0,
                Sakit: 0,
                Izin: 0,
                Alpa: 0
            },
            init() {
                this.updateCounts();
            },
            updateCounts() {
                let hadir = 0, sakit = 0, izin = 0, alpa = 0;
                document.querySelectorAll('input[type="radio"]:checked').forEach(radio => {
                    if (radio.value === 'Hadir') hadir++;
                    else if (radio.value === 'Sakit') sakit++;
                    else if (radio.value === 'Izin') izin++;
                    else if (radio.value === 'Alpa') alpa++;
                });
                this.counts.Hadir = hadir;
                this.counts.Sakit = sakit;
                this.counts.Izin = izin;
                this.counts.Alpa = alpa;
            },
            setAll(status) {
                document.querySelectorAll(`input[type="radio"][value="${status}"]`).forEach(radio => {
                    const hasLeave = radio.dataset.hasApprovedLeave === '1';
                    if (!hasLeave) {
                        radio.checked = true;
                    }
                });
                this.updateCounts();
            }
        };
    }
    </script>
</x-app-layout>

