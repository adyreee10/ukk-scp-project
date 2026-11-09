<x-app-layout>
    <div x-data="teacherJournalManager()" class="space-y-3.5">

        <!-- Header & Navigasi Halaman Ramping (Anti Runaway Scroll) -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pb-2 border-b border-slate-800/80">
            <div>
                <a href="{{ route('teacher.dashboard') }}" class="inline-flex items-center text-xs font-bold text-indigo-400 hover:text-indigo-300 transition hover:underline mb-1">
                    ← Kembali ke Dashboard
                </a>
                <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">
                    Isi Jurnal & Presensi Siswa
                </h1>
                <p class="text-xs text-slate-400 mt-0.5 font-medium">
                    Dokumentasikan aktivitas KBM dan catat kehadiran siswa di kelas
                </p>
            </div>
            
            <div class="shrink-0">
                @if($isEffectiveDayToday && $selectedSchedule && $selectedSchedule->day_of_week === $todayName)
                    <span class="px-3.5 py-1.5 rounded-xl text-xs font-black bg-[#0f172a]/90 text-indigo-300 border border-slate-800 shadow-sm flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-indigo-400 animate-pulse"></span>
                        <span>{{ $todayName }} • {{ $selectedSchedule->period_label }}</span>
                    </span>
                @else
                    <span class="px-3.5 py-1.5 rounded-xl text-xs font-black bg-[#0f172a]/90 text-indigo-300 border border-slate-800 shadow-sm flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-indigo-400"></span>
                        <span>{{ in_array($todayName, ['Sabtu', 'Minggu']) ? 'Hari ' . $todayName : $todayName }}</span>
                    </span>
                @endif
            </div>
        </div>

        @if(!$selectedSchedule)
            <!-- Peringatan jika belum ada jadwal -->
            <div class="p-8 text-center bg-[#0f172a]/90 backdrop-blur-md rounded-2xl border border-slate-800 shadow-xl">
                <div class="w-14 h-14 rounded-2xl bg-indigo-950/60 border border-indigo-500/40 text-indigo-400 mx-auto flex items-center justify-center mb-3">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
                <p class="text-base font-bold text-white">Tidak Ada Jadwal Mengajar Hari Ini ({{ $todayName ?? 'Hari Ini' }})</p>
                <p class="text-xs text-slate-400 mt-1 max-w-md mx-auto">
                    Sistem pengisian jurnal KBM otomatis mendeteksi jadwal mengajar guru pada hari ini. Anda dapat memeriksa jadwal lengkap di menu Jadwal Mengajar.
                </p>
                <div class="mt-4 flex justify-center gap-3">
                    <a href="{{ route('teacher.dashboard') }}" class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-bold text-white shadow-sm transition inline-block">
                        Ke Dashboard
                    </a>
                    <a href="{{ route('teacher.schedules.index') }}" class="px-5 py-2.5 rounded-xl scp-btn-gradient text-xs font-bold text-white shadow-sm inline-block" style="color: #ffffff !important;">
                        Lihat Jadwal Mengajar Saya
                    </a>
                </div>
            </div>

        @elseif($existingJournalToday)
            <!-- ========================================================================= -->
            <!-- KONDISI A: JURNAL SUDAH DIISI HARI INI                                     -->
            <!-- (Bisa Edit Absensi Siswa dalam 24 Jam, Topic & Foto Terkunci)            -->
            <!-- ========================================================================= -->
            <div class="space-y-6">
                <!-- BANNER STATUS PENGISIAN & MASA EDIT 24 JAM -->
                <div class="p-5 bg-white dark:bg-[#0f172a]/90 backdrop-blur-md rounded-2xl border {{ $isEditableWithin24Hours ? 'border-indigo-200 dark:border-indigo-500/50 shadow-sm dark:shadow-xl' : 'border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-xl' }} flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-xl {{ $isEditableWithin24Hours ? 'bg-indigo-50 text-indigo-600 border border-indigo-200 dark:bg-indigo-500/20 dark:text-indigo-400 dark:border-transparent' : 'bg-emerald-50 text-emerald-600 border border-emerald-200 dark:bg-emerald-500/20 dark:text-emerald-400 dark:border-transparent' }} flex items-center justify-center shrink-0">
                            @if($isEditableWithin24Hours)
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            @else
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                            @endif
                        </div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <h2 class="text-base font-extrabold text-slate-900 dark:text-white">Jurnal KBM Hari Ini Telah Tersimpan</h2>
                                @if($isEditableWithin24Hours)
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-indigo-50 text-indigo-700 border border-indigo-200 dark:bg-indigo-950/80 dark:text-indigo-300 dark:border-indigo-600/70">
                                        Masa Edit Presensi Aktif (Batas 24 Jam)
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-slate-100 text-slate-600 border border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700">
                                        Terkunci Permanen (> 24 Jam)
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs text-slate-600 dark:text-slate-300 mt-0.5">
                                @if($isEditableWithin24Hours)
                                    Dokumentasi dan materi terkunci. Anda masih dapat mengubah dan memperbarui kehadiran siswa di bawah ini selama kurun waktu 24 jam.
                                @else
                                    Batas toleransi 24 jam telah berakhir. Data jurnal dan absensi siswa telah tersimpan permanen di riwayat.
                                @endif
                            </p>
                        </div>
                    </div>

                </div>

                <!-- FORM EDIT PRESENSI SISWA (DALAM 24 JAM) -->
                @if($isEditableWithin24Hours)
                    <form method="POST" action="{{ route('teacher.journals.update-attendance', $existingJournalToday->id) }}" class="space-y-3.5">
                        @csrf
                        @method('PUT')

                        <!-- KARTU 1: Informasi Otomatis Kelas, Ruang, dan Tanggal KBM (Sesuai Foto 5) -->
                        <div class="bg-white dark:bg-[#0f172a]/90 backdrop-blur-md p-3.5 sm:p-4 border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-xl space-y-3" style="border-radius: 15px;">
                            <!-- Baris 1 (Atas - Memanjang Penuh): Kelas & Mata Pelajaran -->
                            <div>
                                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                                    Kelas & Mata Pelajaran
                                </label>
                                <input type="text" readonly 
                                       value="{{ $selectedSchedule->schoolClass?->class_name }} — {{ $selectedSchedule->subject }}" 
                                       class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/80 px-3.5 py-2.5 text-xs sm:text-sm font-bold text-slate-900 dark:text-white focus:outline-none cursor-default shadow-sm dark:shadow-inner" />
                            </div>

                            <!-- Baris 2 (Bawah - 2 Kolom): Ruang Kelas & Tanggal Pelaksanaan KBM -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                                        Ruang Kelas / Laboratorium
                                    </label>
                                    <input type="text" readonly 
                                           value="{{ $selectedSchedule->room?->room_name ?? '-' }}" 
                                           class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/80 px-3.5 py-2.5 text-xs sm:text-sm font-bold text-slate-900 dark:text-white focus:outline-none cursor-default shadow-sm dark:shadow-inner" />
                                </div>

                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                                        Tanggal Pelaksanaan KBM *
                                    </label>
                                    <input type="text" readonly 
                                           value="{{ \Carbon\Carbon::parse($existingJournalToday->date)->format('d/m/Y') }}" 
                                           class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/80 px-3.5 py-2.5 text-xs sm:text-sm font-bold text-slate-900 dark:text-white focus:outline-none cursor-default shadow-sm dark:shadow-inner" />
                                </div>
                            </div>
                        </div>

                        <!-- GRID 2 KOLOM: DOKUMENTASI (TERKUNCI) & EDIT PRESENSI SISWA -->
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">
                            
                            <!-- KIRI (5 Kolom): Ringkasan Dokumentasi KBM & Tombol Simpan -->
                            <div class="lg:col-span-5 space-y-3">
                                <div class="bg-white dark:bg-[#0f172a]/90 backdrop-blur-md p-4 border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-xl space-y-3" style="border-radius: 15px;">
                                    <div class="flex items-center justify-between pb-2 border-b border-slate-200 dark:border-slate-800">
                                        <h2 class="text-sm font-extrabold text-slate-900 dark:text-white">Dokumentasi Pembelajaran</h2>
                                        <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400">Tersimpan</span>
                                    </div>

                                    <div>
                                        <span class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Materi / Topik KBM:</span>
                                        <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 text-xs text-slate-800 dark:text-slate-200 leading-relaxed whitespace-pre-line max-h-32 overflow-y-auto">
                                            {{ $existingJournalToday->topic_description }}
                                        </div>
                                    </div>

                                    <div>
                                        <span class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Bukti Foto Real-Time KBM:</span>
                                        @if($existingJournalToday->photo_url)
                                            <img src="{{ $existingJournalToday->photo_url }}" alt="Bukti KBM" class="w-full max-h-48 object-cover rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-md">
                                        @else
                                            <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs text-slate-500 dark:text-slate-400 text-center">
                                                Tidak ada foto dokumentasi
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Tombol Update Presensi -->
                                <div class="bg-white dark:bg-[#0f172a]/90 backdrop-blur-md p-3.5 border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-xl space-y-1.5" style="border-radius: 15px;">
                                    <button type="submit" 
                                            style="border-radius: 12px; color: #ffffff !important;"
                                            class="w-full py-2.5 px-4 font-bold text-xs scp-btn-gradient cursor-pointer tracking-wider uppercase shadow-md hover:shadow-lg transition">
                                        Simpan Perubahan Presensi Siswa
                                    </button>
                                    <p class="text-[10px] text-center text-slate-500 dark:text-slate-400">
                                        Pengeditan presensi masih dapat dilakukan dalam batas waktu 24 jam sejak jurnal dibuat
                                    </p>
                                </div>
                            </div>

                            <!-- KANAN (7 Kolom): Formulir Edit Presensi Siswa -->
                            <div class="lg:col-span-7">
                                <div class="bg-white dark:bg-[#0f172a]/90 backdrop-blur-md p-4 border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-xl" style="border-radius: 15px;">
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 pb-2.5 border-b border-slate-200 dark:border-slate-800 mb-2.5">
                                        <div>
                                            <h2 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white">Presensi Siswa</h2>
                                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                                Kelas {{ $selectedSchedule->schoolClass->class_name }} ({{ $students->count() }} Siswa)
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

                                    <!-- DAFTAR SISWA (SCROLL DALAM KONTEN AGAR HALAMAN TIDAK HILANG) -->
                                    @php
                                        $existingAttendances = $existingJournalToday->attendances->keyBy('student_id');
                                    @endphp
                                    <div class="space-y-2 max-h-[260px] sm:max-h-[280px] xl:max-h-[320px] overflow-y-auto overscroll-contain pr-1 relative">
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
                                                               data-has-approved-leave="{{ $approvedLeave ? '1' : '' }}"
                                                               class="sr-only peer" 
                                                               {{ $currentStatus === 'Hadir' ? 'checked' : '' }}>
                                                        <span class="px-2.5 py-1 rounded-md text-[11px] font-bold border border-slate-300 text-slate-600 bg-white dark:border-slate-700 dark:text-slate-400 dark:bg-slate-900 peer-checked:bg-emerald-600 peer-checked:!text-white peer-checked:border-emerald-600 transition inline-block">H</span>
                                                    </label>
                                                    <label class="cursor-pointer relative">
                                                        <input type="radio" name="attendances[{{ $student->id }}]" value="Sakit" 
                                                               data-has-approved-leave="{{ $approvedLeave ? '1' : '' }}"
                                                               class="sr-only peer" 
                                                               {{ $currentStatus === 'Sakit' ? 'checked' : '' }}>
                                                        <span class="px-2.5 py-1 rounded-md text-[11px] font-bold border border-slate-300 text-slate-600 bg-white dark:border-slate-700 dark:text-slate-400 dark:bg-slate-900 peer-checked:bg-amber-500 peer-checked:!text-white peer-checked:border-amber-500 transition inline-block">S</span>
                                                    </label>
                                                    <label class="cursor-pointer relative">
                                                        <input type="radio" name="attendances[{{ $student->id }}]" value="Izin" 
                                                               data-has-approved-leave="{{ $approvedLeave ? '1' : '' }}"
                                                               class="sr-only peer" 
                                                               {{ $currentStatus === 'Izin' ? 'checked' : '' }}>
                                                        <span class="px-2.5 py-1 rounded-md text-[11px] font-bold border border-slate-300 text-slate-600 bg-white dark:border-slate-700 dark:text-slate-400 dark:bg-slate-900 peer-checked:bg-blue-600 peer-checked:!text-white peer-checked:border-blue-600 transition inline-block">I</span>
                                                    </label>
                                                    <label class="cursor-pointer relative">
                                                        <input type="radio" name="attendances[{{ $student->id }}]" value="Alpa" 
                                                               data-has-approved-leave="{{ $approvedLeave ? '1' : '' }}"
                                                               class="sr-only peer" 
                                                               {{ $currentStatus === 'Alpa' ? 'checked' : '' }}>
                                                        <span class="px-2.5 py-1 rounded-md text-[11px] font-bold border border-slate-300 text-slate-600 bg-white dark:border-slate-700 dark:text-slate-400 dark:bg-slate-900 peer-checked:bg-red-600 peer-checked:!text-white peer-checked:border-red-600 transition inline-block">A</span>
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
                @else
                    <!-- TAMPILAN TERKUNCI PERMANEN (> 24 JAM) -->
                    <div class="bg-white dark:bg-[#0f172a]/90 backdrop-blur-md p-6 border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-xl space-y-4" style="border-radius: 15px;">
                        <p class="text-xs text-slate-600 dark:text-slate-400">
                            Jurnal ini telah melewati batas waktu 24 jam dan telah dikunci permanen. Seluruh rekapan presensi siswa dapat dilihat melalui menu Riwayat Jurnal KBM.
                        </p>
                        <div class="flex gap-2">
                            <a href="{{ route('teacher.dashboard') }}" class="px-4 py-2 rounded-xl scp-btn-gradient text-xs font-bold text-white shadow-md transition" style="color: #ffffff !important;">
                                ← Ke Dashboard
                            </a>
                            <a href="{{ route('teacher.journals.history') }}" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 text-xs font-bold transition">
                                Buka Riwayat Jurnal
                            </a>
                        </div>
                    </div>
                @endif
            </div>

        @else
            <!-- ========================================================================= -->
            <!-- KONDISI B: PENGISIAN JURNAL BARU OTOMATIS (SESUAI FOTO 5)                 -->
            <!-- ========================================================================= -->
            <form method="POST" action="{{ route('teacher.journals.store') }}" enctype="multipart/form-data" @change="updateCounts()" class="space-y-3.5">
                @csrf
                <input type="hidden" name="schedule_id" value="{{ $selectedSchedule->id }}">

                <!-- KARTU 1: Informasi Otomatis Kelas, Ruang, dan Tanggal KBM (Sesuai Foto 5) -->
                <div class="bg-white dark:bg-[#0f172a]/90 backdrop-blur-md p-3.5 sm:p-4 border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-xl space-y-3" style="border-radius: 15px;">
                    <!-- Baris 1 (Atas - Memanjang Penuh): Kelas & Mata Pelajaran -->
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                            Kelas & Mata Pelajaran
                        </label>
                        <input type="text" readonly 
                               value="{{ $selectedSchedule->schoolClass?->class_name }} — {{ $selectedSchedule->subject }}" 
                               class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/80 px-3.5 py-2.5 text-xs sm:text-sm font-bold text-slate-900 dark:text-white focus:outline-none cursor-default shadow-sm dark:shadow-inner" />
                    </div>

                    <!-- Baris 2 (Bawah - 2 Kolom): Ruang Kelas & Tanggal Pelaksanaan KBM -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                                Ruang Kelas / Laboratorium
                            </label>
                            <input type="text" readonly 
                                   value="{{ $selectedSchedule->room?->room_name ?? '-' }}" 
                                   class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/80 px-3.5 py-2.5 text-xs sm:text-sm font-bold text-slate-900 dark:text-white focus:outline-none cursor-default shadow-sm dark:shadow-inner" />
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                                Tanggal Pelaksanaan KBM *
                            </label>
                            <input type="text" readonly 
                                   value="{{ now()->translatedFormat('d/m/Y') }}" 
                                   class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/80 px-3.5 py-2.5 text-xs sm:text-sm font-bold text-slate-900 dark:text-white focus:outline-none cursor-default shadow-sm dark:shadow-inner" />
                            <input type="hidden" name="date" value="{{ now()->toDateString() }}">
                        </div>
                    </div>
                </div>

                <!-- GRID 2 KOLOM: DOKUMENTASI PEMBELAJARAN (KIRI) & PRESENSI SISWA (KANAN) -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">
                    
                    <!-- KARTU 2: Materi & Foto Dokumentasi KBM (Kiri / 5 Kolom) -->
                    <div class="lg:col-span-5 space-y-3">
                        <div class="bg-white dark:bg-[#0f172a]/90 backdrop-blur-md p-4 border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-xl space-y-3" style="border-radius: 15px;">
                            <h2 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white pb-2 border-b border-slate-200 dark:border-slate-800">
                                Dokumentasi Pembelajaran
                            </h2>

                            <!-- Ringkasan Materi / Topik -->
                            <div>
                                <label for="topic_description" class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                                    Materi / Topik KBM Hari Ini <span class="text-red-500">*</span>
                                </label>
                                <textarea id="topic_description" name="topic_description" rows="3" required
                                          placeholder="Contoh: Pembahasan Bab 3 tentang Algoritma Pencarian. Praktik langsung di laboratorium komputer."
                                          class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 px-3 py-2 text-xs sm:text-sm text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:border-indigo-500 focus:bg-white dark:focus:bg-slate-900 focus:outline-none focus:ring-1 focus:ring-indigo-500/20">{{ old('topic_description') }}</textarea>
                                <x-input-error :messages="$errors->get('topic_description')" class="mt-0.5 text-xs" />
                            </div>

                            <!-- Foto Bukti KBM Real-Time Kamera -->
                            <div class="space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                        Foto Bukti KBM (Kamera Real-Time) <span class="text-red-400">*</span>
                                    </label>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-950/70 border border-rose-700/60 text-rose-300 flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-400 animate-pulse"></span>
                                        Kamera Langsung
                                    </span>
                                </div>

                                <!-- Box Kamera & Foto Real-Time -->
                                <div class="overflow-hidden rounded-xl border border-slate-700 bg-slate-950 relative">
                                    <!-- 1. Kondisi Belum Ambil Foto & Kamera Belum Aktif -->
                                    <div x-show="!photoPreview && !cameraActive" class="p-4 text-center space-y-2">
                                        <div class="w-10 h-10 rounded-xl bg-indigo-950/60 border border-indigo-500/40 text-indigo-400 mx-auto flex items-center justify-center">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold text-white">Kamera Belum Aktif</p>
                                            <p class="text-[10px] text-slate-400">Jepret foto suasana KBM saat ini</p>
                                        </div>
                                        
                                        <div class="flex items-center justify-center pt-0.5">
                                            <button type="button" @click="startCamera()" class="px-4 py-2 rounded-xl scp-btn-gradient text-xs font-bold text-white shadow-md flex items-center justify-center gap-1.5 cursor-pointer transition">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                                <span>Buka & Nyalakan Kamera</span>
                                            </button>
                                        </div>

                                        <template x-if="cameraError">
                                            <p class="text-xs text-rose-400 font-semibold mt-1" x-text="cameraError"></p>
                                        </template>
                                    </div>

                                    <!-- 2. Kondisi Kamera Aktif (Live Streaming Viewfinder) -->
                                    <div x-show="cameraActive && !photoPreview" style="display: none;" class="relative">
                                        <div class="relative bg-black">
                                            <video x-ref="videoPlayer" autoplay playsinline muted :style="isMirrored ? 'transform: scaleX(-1);' : 'transform: none;'" class="w-full h-56 sm:h-64 object-cover bg-black block"></video>
                                            
                                            <div class="absolute top-2 left-2 bg-slate-950/85 backdrop-blur-sm border border-slate-700 text-rose-400 px-2.5 py-0.5 rounded-full text-[9px] font-black tracking-wider uppercase flex items-center gap-1.5 shadow-md" style="z-index: 20;">
                                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                                                <span>LIVE KAMERA</span>
                                            </div>

                                            <button type="button" @click="switchCamera()" class="absolute top-2 right-2 bg-slate-900/85 hover:bg-slate-800 text-white p-1.5 rounded-full text-xs font-bold border border-slate-700 shadow-md transition" style="z-index: 20;" title="Putar Kamera">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                            </button>
                                        </div>

                                        <div class="p-2.5 bg-slate-900 border-t border-slate-800 flex flex-wrap items-center justify-center gap-2">
                                            <button type="button" @click="snapPhoto()" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-extrabold text-xs shadow-md flex items-center gap-1.5 transition transform active:scale-95 cursor-pointer">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                <span>Ambil Foto</span>
                                            </button>
                                            <button type="button" @click="toggleMirror()" class="px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold border border-slate-700 flex items-center gap-1 transition cursor-pointer">
                                                <span x-text="isMirrored ? 'Mode: Cermin' : 'Normal'"></span>
                                            </button>
                                            <button type="button" @click="stopCamera()" class="px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white text-xs font-semibold border border-slate-700 flex items-center gap-1 transition cursor-pointer">
                                                <span>Tutup</span>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- 3. Kondisi Foto Berhasil Diambil -->
                                    <div x-show="photoPreview" style="display: none;" class="relative">
                                        <div class="relative bg-black flex items-center justify-center overflow-hidden">
                                            <img :src="photoPreview" class="w-full max-h-64 sm:max-h-72 object-contain bg-black mx-auto block" alt="Hasil Foto Real-Time KBM">
                                        </div>
                                        
                                        <div class="absolute top-2 left-2 bg-emerald-950/90 border border-emerald-600 text-emerald-300 px-2.5 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider flex items-center gap-1 shadow-md">
                                            <svg class="w-3 h-3 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            TERCATAT
                                        </div>

                                        <div class="p-2.5 bg-slate-900 border-t border-slate-800 flex flex-wrap items-center justify-between gap-1.5">
                                            <span class="text-[10px] text-emerald-400 font-semibold flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                Foto ter-watermark
                                            </span>
                                            <div class="flex items-center gap-1.5">
                                                <button type="button" @click="flipPreview()" class="px-2.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 text-xs font-bold shadow-sm transition flex items-center gap-1 cursor-pointer">
                                                    <span>Balik</span>
                                                </button>
                                                <button type="button" @click="retake()" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-white text-xs font-bold shadow-sm transition flex items-center gap-1 cursor-pointer">
                                                    <span>Foto Ulang</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <input type="file" name="photo" x-ref="realFileInput" class="hidden" accept="image/*">
                                <input type="hidden" name="photo_base64" x-model="photoBase64">
                                <x-input-error :messages="$errors->get('photo')" class="mt-0.5 text-xs" />
                            </div>
                        </div>

                        <!-- Tombol Submit Form Baru -->
                        <div class="bg-white dark:bg-[#0f172a]/90 backdrop-blur-md p-3.5 border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-xl space-y-1.5" style="border-radius: 15px;">
                            <button type="submit" 
                                    style="border-radius: 12px; color: #ffffff !important;"
                                    class="w-full py-2.5 px-4 font-bold text-xs scp-btn-gradient cursor-pointer tracking-wider uppercase shadow-md hover:shadow-lg transition">
                                Simpan Jurnal & Presensi Siswa
                            </button>
                            <p class="text-[10px] text-center text-slate-500 dark:text-slate-400">
                                Data kehadiran dan jurnal akan tercatat di sistem sekolah
                            </p>
                        </div>
                    </div>

                    <!-- KARTU 3: Daftar Presensi Siswa Kelas (Kanan / 7 Kolom) (Sesuai Foto 5) -->
                    <div class="lg:col-span-7">
                        <div class="bg-white dark:bg-[#0f172a]/90 backdrop-blur-md p-4 border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-xl" style="border-radius: 15px;">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 pb-2.5 border-b border-slate-200 dark:border-slate-800 mb-2.5">
                                <div>
                                    <h2 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white">Presensi Siswa</h2>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                        Kelas {{ $selectedSchedule->schoolClass->class_name }} ({{ $students->count() }} Siswa)
                                    </p>
                                </div>
                                
                                <button type="button" @click="setAll('Hadir')" 
                                        class="text-xs font-bold px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:hover:bg-emerald-900 dark:border-emerald-800/60 transition cursor-pointer self-start sm:self-auto">
                                    ✓ Set Semua Hadir
                                </button>
                            </div>

                            @if($students->isEmpty())
                                <div class="text-center py-6 bg-slate-50 dark:bg-slate-900/50 rounded-xl border border-dashed border-slate-200 dark:border-slate-800">
                                    <p class="text-xs font-bold text-slate-900 dark:text-white">Belum Ada Siswa di Kelas Ini</p>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Waka Kurikulum belum mendaftarkan siswa ke dalam kelas {{ $selectedSchedule->schoolClass->class_name }}.</p>
                                </div>
                            @else
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

                                <!-- KOLOM PENCARIAN CEPAT SISWA -->
                                <div class="mb-2.5 relative">
                                    <input type="text" x-model="searchStudent" 
                                           placeholder="Cari nama siswa di kelas ini..." 
                                           class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 px-3.5 py-1.5 pl-9 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:border-indigo-500 focus:bg-white dark:focus:bg-slate-900 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>

                                <!-- DAFTAR SISWA: SCROLL DI DALAM CONTAINER DENGAN TINGGI STABIL -->
                                <div class="space-y-2 max-h-[260px] sm:max-h-[280px] xl:max-h-[320px] overflow-y-auto overscroll-contain pr-1 relative">
                                    @foreach ($students as $index => $student)
                                        @php
                                            $approvedLeave = isset($approvedLeavesToday) ? $approvedLeavesToday->get($student->id) : null;
                                            $defaultLeaveStatus = $approvedLeave ? ($approvedLeave->leave_type === 'Sakit' ? 'Sakit' : 'Izin') : 'Hadir';
                                            $currentStatus = old('attendances.'.$student->id, $defaultLeaveStatus);
                                        @endphp
                                        <div x-show="!searchStudent || '{{ strtolower(addcslashes($student->name, "'\\")) }}'.includes(searchStudent.toLowerCase())"
                                             class="p-2.5 sm:p-3 rounded-xl border relative {{ $approvedLeave ? 'border-indigo-300 bg-indigo-50/60 dark:border-indigo-500/40 dark:bg-indigo-950/20' : 'border-slate-200 bg-slate-50/70 hover:bg-slate-100 dark:border-slate-800/80 dark:bg-slate-900/40 dark:hover:bg-slate-800/40' }} flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 transition">
                                            <div class="flex items-center gap-2.5 min-w-0">
                                                <span class="text-xs font-bold text-slate-400 dark:text-slate-500 w-5 text-right">{{ $index + 1 }}.</span>
                                                <div class="min-w-0">
                                                    <div class="flex items-center gap-2 flex-wrap">
                                                        <p class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ $student->name }}</p>
                                                        @if($approvedLeave)
                                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-black {{ $approvedLeave->leave_type === 'Sakit' ? 'bg-amber-100 text-amber-800 border border-amber-300 dark:bg-amber-950/80 dark:text-amber-300 dark:border-amber-600/70' : 'bg-blue-100 text-blue-800 border border-blue-300 dark:bg-blue-950/80 dark:text-blue-300 dark:border-blue-600/70' }}" 
                                                                  title="Surat izin/sakit telah disetujui (ACC) oleh Wali Kelas / Guru BK">
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
                                                           data-has-approved-leave="{{ $approvedLeave ? '1' : '' }}"
                                                           class="sr-only peer" 
                                                           {{ $currentStatus === 'Hadir' ? 'checked' : '' }}>
                                                    <span class="px-2.5 py-1 rounded-md text-[11px] font-bold border border-slate-300 text-slate-600 bg-white dark:border-slate-700 dark:text-slate-400 dark:bg-slate-900 peer-checked:bg-emerald-600 peer-checked:!text-white peer-checked:border-emerald-600 transition inline-block">
                                                        H
                                                    </span>
                                                </label>

                                                <label class="cursor-pointer relative">
                                                    <input type="radio" name="attendances[{{ $student->id }}]" value="Sakit" 
                                                           data-has-approved-leave="{{ $approvedLeave ? '1' : '' }}"
                                                           class="sr-only peer" 
                                                           {{ $currentStatus === 'Sakit' ? 'checked' : '' }}>
                                                    <span class="px-2.5 py-1 rounded-md text-[11px] font-bold border border-slate-300 text-slate-600 bg-white dark:border-slate-700 dark:text-slate-400 dark:bg-slate-900 peer-checked:bg-amber-500 peer-checked:!text-white peer-checked:border-amber-500 transition inline-block">
                                                        S
                                                    </span>
                                                </label>

                                                <label class="cursor-pointer relative">
                                                    <input type="radio" name="attendances[{{ $student->id }}]" value="Izin" 
                                                           data-has-approved-leave="{{ $approvedLeave ? '1' : '' }}"
                                                           class="sr-only peer" 
                                                           {{ $currentStatus === 'Izin' ? 'checked' : '' }}>
                                                    <span class="px-2.5 py-1 rounded-md text-[11px] font-bold border border-slate-300 text-slate-600 bg-white dark:border-slate-700 dark:text-slate-400 dark:bg-slate-900 peer-checked:bg-blue-600 peer-checked:!text-white peer-checked:border-blue-600 transition inline-block">
                                                        I
                                                    </span>
                                                </label>

                                                <label class="cursor-pointer relative">
                                                    <input type="radio" name="attendances[{{ $student->id }}]" value="Alpa" 
                                                           data-has-approved-leave="{{ $approvedLeave ? '1' : '' }}"
                                                           class="sr-only peer" 
                                                           {{ $currentStatus === 'Alpa' ? 'checked' : '' }}>
                                                    <span class="px-2.5 py-1 rounded-md text-[11px] font-bold border border-slate-300 text-slate-600 bg-white dark:border-slate-700 dark:text-slate-400 dark:bg-slate-900 peer-checked:bg-red-600 peer-checked:!text-white peer-checked:border-red-600 transition inline-block">
                                                        A
                                                    </span>
                                                </label>
                                            </div>

                                            <input type="text" name="attendance_notes[{{ $student->id }}]" 
                                                   value="{{ old('attendance_notes.'.$student->id, $approvedLeave ? $approvedLeave->reason : '') }}"
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
                            @endif
                        </div>
                    </div>

                </div>
            </form>
        @endif

    </div>

    <script>
    function teacherJournalManager() {
        return {
            photoPreview: null,
            photoBase64: '',
            cameraStream: null,
            cameraActive: false,
            cameraError: null,
            facingMode: 'environment',
            isMirrored: false,
            searchStudent: '',
            counts: { Hadir: 0, Sakit: 0, Izin: 0, Alpa: 0 },

            toggleMirror() {
                this.isMirrored = !this.isMirrored;
            },

            async startCamera() {
                this.cameraError = null;
                try {
                    if (this.cameraStream) {
                        this.stopCamera();
                    }

                    const constraints = {
                        video: {
                            facingMode: this.facingMode,
                            width: { ideal: 1280 },
                            height: { ideal: 720 }
                        },
                        audio: false
                    };

                    this.cameraStream = await navigator.mediaDevices.getUserMedia(constraints);
                    this.cameraActive = true;
                    this.photoPreview = null;

                    this.$nextTick(() => {
                        if (this.$refs.videoPlayer) {
                            this.$refs.videoPlayer.srcObject = this.cameraStream;
                            this.$refs.videoPlayer.play();
                        }
                    });
                } catch (err) {
                    this.cameraError = 'Tidak dapat mengakses kamera perangkat. Pastikan izin kamera aktif.';
                    this.cameraActive = false;
                }
            },

            switchCamera() {
                this.facingMode = this.facingMode === 'environment' ? 'user' : 'environment';
                this.startCamera();
            },

            stopCamera() {
                if (this.cameraStream) {
                    this.cameraStream.getTracks().forEach(track => track.stop());
                    this.cameraStream = null;
                }
                this.cameraActive = false;
            },

            snapPhoto() {
                if (!this.$refs.videoPlayer) return;

                const video = this.$refs.videoPlayer;
                const canvas = document.createElement('canvas');
                canvas.width = video.videoWidth || 1280;
                canvas.height = video.videoHeight || 720;
                const ctx = canvas.getContext('2d');

                if (this.isMirrored) {
                    ctx.save();
                    ctx.translate(canvas.width, 0);
                    ctx.scale(-1, 1);
                    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                    ctx.restore();
                } else {
                    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                }

                // Watermark waktu real-time
                const now = new Date();
                const dateStr = now.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) + ' • ' +
                                now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) + ' WIB';

                const w = canvas.width;
                const h = canvas.height;
                const minDim = Math.min(w, h);
                const fontSize = Math.max(16, Math.round(minDim * 0.035));
                const barHeight = Math.round(fontSize * 2.5);
                const paddingX = Math.round(fontSize * 0.8);

                ctx.fillStyle = 'rgba(15, 23, 42, 0.90)';
                ctx.fillRect(0, h - barHeight, w, barHeight);

                ctx.fillStyle = '#e11d48';
                ctx.fillRect(0, h - barHeight, w, Math.max(3, Math.round(fontSize * 0.12)));

                const dotRadius = Math.max(5, Math.round(fontSize * 0.28));
                const centerY = h - (barHeight / 2);
                ctx.fillStyle = '#ef4444';
                ctx.beginPath();
                ctx.arc(paddingX + dotRadius, centerY, dotRadius, 0, Math.PI * 2);
                ctx.fill();

                ctx.fillStyle = '#ffffff';
                ctx.font = 'bold ' + fontSize + 'px system-ui, -apple-system, sans-serif';
                ctx.textBaseline = 'middle';
                ctx.fillText('JURNAL KBM REAL-TIME • ' + dateStr, paddingX + (dotRadius * 2) + Math.round(fontSize * 0.5), centerY);

                const dataUrl = canvas.toDataURL('image/jpeg', 0.90);
                this.photoPreview = dataUrl;
                this.photoBase64 = dataUrl;

                try {
                    canvas.toBlob((blob) => {
                        if (blob && this.$refs.realFileInput) {
                            const file = new File([blob], 'KBM_Realtime_' + Date.now() + '.jpg', { type: 'image/jpeg' });
                            const dt = new DataTransfer();
                            dt.items.add(file);
                            this.$refs.realFileInput.files = dt.files;
                        }
                    }, 'image/jpeg', 0.88);
                } catch(e) {}

                this.stopCamera();
            },

            flipPreview() {
                if (!this.photoPreview) return;
                const img = new Image();
                img.onload = () => {
                    const canvas = document.createElement('canvas');
                    canvas.width = img.width;
                    canvas.height = img.height;
                    const ctx = canvas.getContext('2d');
                    ctx.translate(canvas.width, 0);
                    ctx.scale(-1, 1);
                    ctx.drawImage(img, 0, 0);
                    const dataUrl = canvas.toDataURL('image/jpeg', 0.90);
                    this.photoPreview = dataUrl;
                    this.photoBase64 = dataUrl;

                    canvas.toBlob((blob) => {
                        if (blob && this.$refs.realFileInput) {
                            const file = new File([blob], 'KBM_Realtime_' + Date.now() + '.jpg', { type: 'image/jpeg' });
                            const dt = new DataTransfer();
                            dt.items.add(file);
                            this.$refs.realFileInput.files = dt.files;
                        }
                    }, 'image/jpeg', 0.90);
                };
                img.src = this.photoPreview;
            },

            retake() {
                this.photoPreview = null;
                this.photoBase64 = '';
                if (this.$refs.realFileInput) this.$refs.realFileInput.value = '';
                this.startCamera();
            },

            updateCounts() {
                let h = 0, s = 0, i = 0, a = 0;
                document.querySelectorAll('input[type=radio]:checked').forEach(el => {
                    if (el.value === 'Hadir') h++;
                    else if (el.value === 'Sakit') s++;
                    else if (el.value === 'Izin') i++;
                    else if (el.value === 'Alpa') a++;
                });
                this.counts = { Hadir: h, Sakit: s, Izin: i, Alpa: a };
            },

            setAll(status) {
                document.querySelectorAll('input[type=radio][value=' + status + ']').forEach(el => {
                    if (!el.dataset.hasApprovedLeave || status !== 'Hadir') {
                        el.checked = true;
                    }
                });
                this.updateCounts();
            },

            init() {
                this.$nextTick(() => { this.updateCounts(); });
            }
        };
    }
    </script>
</x-app-layout>
