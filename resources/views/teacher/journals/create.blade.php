<x-app-layout>
    <div x-data="teacherJournalManager()" class="space-y-6">

        <!-- Header & Navigasi Kembali (Judul Teks Putih) -->
        <div class="text-center pb-2" style="text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%;">
            <a href="{{ route('teacher.dashboard') }}" class="inline-flex items-center text-xs font-bold text-indigo-400 hover:text-indigo-300 transition hover:underline mb-2">
                ← Kembali ke Dashboard Guru
            </a>
            <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight" style="text-align: center;">
                Isi Jurnal & Presensi Siswa
            </h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1 font-medium" style="text-align: center;">
                Dokumentasikan aktivitas KBM dan catat kehadiran siswa di kelas
            </p>
            
            @if($selectedSchedule)
                <div class="mt-2.5 flex items-center justify-center" style="display: flex; justify-content: center;">
                    <span class="px-3.5 py-1.5 rounded-xl text-xs font-black bg-[#0f172a]/90 text-indigo-300 border border-slate-800 shadow-sm">
                        {{ $selectedSchedule->day_of_week }} • {{ $selectedSchedule->period_label }}
                    </span>
                </div>
            @endif
        </div>

        @if(!$selectedSchedule)
            <!-- Peringatan jika belum ada jadwal -->
            <div class="p-8 text-center bg-[#0f172a]/90 backdrop-blur-md rounded-2xl border border-slate-800 shadow-xl">
                <p class="text-base font-bold text-white">Tidak ada jadwal yang dipilih atau tersedia</p>
                <p class="text-xs text-slate-400 mt-1">Pastikan Anda memiliki jadwal mengajar pada {{ $configuration->active_cycle ?? 'Putaran Aktif' }}.</p>
                <div class="mt-4">
                    <a href="{{ route('teacher.dashboard') }}" class="px-5 py-2.5 rounded-xl scp-btn-gradient text-xs font-bold text-white shadow-sm inline-block" style="color: #ffffff !important;">
                        Kembali ke Jadwal Saya
                    </a>
                </div>
            </div>
        @elseif($existingJournalToday)
            <!-- TAMPILAN JIKA JURNAL UNTUK JADWAL INI SUDAH DIISI HARI INI (CUKUP 1 KALI PENGISIAN) -->
            <div class="space-y-6">
                <!-- BANNER NOTIFIKASI SUDAH DIISI -->
                <div class="p-6 bg-[#0f172a]/95 backdrop-blur-md rounded-2xl border border-emerald-500/40 shadow-xl space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-black text-white">Jurnal KBM Hari Ini Sudah Berhasil Diisi!</h2>
                            <p class="text-xs text-slate-300 mt-0.5">
                                Jurnal untuk mata pelajaran <strong>{{ $selectedSchedule->subject }}</strong> di kelas <strong>{{ $selectedSchedule->schoolClass?->class_name }}</strong> pada hari ini telah tersimpan (cukup 1 kali pengisian per hari).
                            </p>
                        </div>
                    </div>

                    <!-- Dropdown Pilih Jadwal Lain -->
                    <div class="pt-3 border-t border-slate-800">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">
                            Pilih Jadwal Mengajar Lainnya:
                        </label>
                        <select onchange="window.location.href = '{{ route('teacher.journals.create') }}?schedule_id=' + this.value" 
                                class="w-full sm:w-96 rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2.5 text-xs sm:text-sm font-semibold text-white focus:border-indigo-500 focus:outline-none">
                            @foreach ($schedules as $item)
                                <option value="{{ $item->id }}" {{ $item->id == $selectedSchedule->id ? 'selected' : '' }} class="bg-slate-900 text-white">
                                    {{ $item->schoolClass->class_name }} — {{ $item->subject }} ({{ $item->day_of_week }}) {{ in_array($item->id, $filledScheduleIdsToday ?? []) ? '— [✓ Sudah Diisi Hari Ini]' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- KARTU RINGKASAN JURNAL YANG SUDAH TERCATAT -->
                <div class="bg-[#0f172a]/90 backdrop-blur-md p-6 border border-slate-800 shadow-xl space-y-5" style="border-radius: 15px;">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                        <h3 class="text-sm font-extrabold text-white">Ringkasan Jurnal KBM yang Tercatat</h3>
                        <span class="text-xs font-mono text-emerald-400 font-bold bg-emerald-950/70 border border-emerald-800 px-2.5 py-1 rounded-lg">
                            Tanggal: {{ \Carbon\Carbon::parse($existingJournalToday->date)->translatedFormat('d F Y') }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <span class="block text-xs font-bold text-slate-400 mb-1">Materi / Topik Pembelajaran:</span>
                            <div class="p-3.5 rounded-xl bg-slate-900/80 border border-slate-800 text-xs sm:text-sm text-slate-200 leading-relaxed whitespace-pre-line">
                                {{ $existingJournalToday->topic_description }}
                            </div>
                        </div>

                        <div>
                            <span class="block text-xs font-bold text-slate-400 mb-1">Dokumentasi Bukti KBM:</span>
                            @if($existingJournalToday->photo_url)
                                <img src="{{ $existingJournalToday->photo_url }}" alt="Bukti KBM" class="w-full max-h-48 object-cover rounded-xl border border-slate-800 shadow-md">
                            @else
                                <div class="p-4 rounded-xl bg-slate-900 border border-slate-800 text-xs text-slate-400 text-center">
                                    Tidak ada foto dokumentasi
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Rekap Presensi Siswa -->
                    <div class="pt-4 border-t border-slate-800">
                        <span class="block text-xs font-bold text-slate-400 mb-2">Rekapitulasi Kehadiran Siswa:</span>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-4">
                            <div class="px-3 py-2 rounded-xl bg-emerald-950/40 border border-emerald-800/50 flex items-center justify-between">
                                <span class="text-xs font-bold text-emerald-300">Hadir</span>
                                <span class="text-sm font-black text-white">{{ $existingJournalToday->attendances->where('status', 'Hadir')->count() }}</span>
                            </div>
                            <div class="px-3 py-2 rounded-xl bg-amber-950/40 border border-amber-800/50 flex items-center justify-between">
                                <span class="text-xs font-bold text-amber-300">Sakit</span>
                                <span class="text-sm font-black text-white">{{ $existingJournalToday->attendances->where('status', 'Sakit')->count() }}</span>
                            </div>
                            <div class="px-3 py-2 rounded-xl bg-blue-950/40 border border-blue-800/50 flex items-center justify-between">
                                <span class="text-xs font-bold text-blue-300">Izin</span>
                                <span class="text-sm font-black text-white">{{ $existingJournalToday->attendances->where('status', 'Izin')->count() }}</span>
                            </div>
                            <div class="px-3 py-2 rounded-xl bg-red-950/40 border border-red-800/50 flex items-center justify-between">
                                <span class="text-xs font-bold text-red-300">Alpa</span>
                                <span class="text-sm font-black text-white">{{ $existingJournalToday->attendances->where('status', 'Alpa')->count() }}</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <a href="{{ route('teacher.dashboard') }}" class="px-4 py-2 rounded-xl scp-btn-gradient text-xs font-bold text-white shadow-md transition" style="color: #ffffff !important;">
                                ← Kembali ke Dashboard Guru
                            </a>
                            <a href="{{ route('teacher.journals.history') }}" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-bold text-slate-300 transition">
                                Lihat Semua Riwayat Jurnal
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <!-- FORMULIR UTAMA -->
            <form method="POST" action="{{ route('teacher.journals.store') }}" enctype="multipart/form-data" @change="updateCounts()" class="space-y-6">
                @csrf
                <input type="hidden" name="schedule_id" value="{{ $selectedSchedule->id }}">

                <!-- KARTU 1: Pilih / Konfirmasi Kelas & Jadwal (Dark Card) -->
                <div class="bg-[#0f172a]/90 backdrop-blur-md p-5 border border-slate-800 shadow-xl" style="border-radius: 15px;">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Dropdown Pilih Jadwal Lain -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">
                                Kelas & Mata Pelajaran
                            </label>
                            <select onchange="window.location.href = '{{ route('teacher.journals.create') }}?schedule_id=' + this.value" 
                                    class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2.5 text-xs sm:text-sm font-semibold text-white focus:border-indigo-500 focus:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                                @foreach ($schedules as $item)
                                    <option value="{{ $item->id }}" {{ $item->id == $selectedSchedule->id ? 'selected' : '' }} class="bg-slate-900 text-white">
                                        {{ $item->schoolClass->class_name }} — {{ $item->subject }} ({{ $item->day_of_week }}) {{ in_array($item->id, $filledScheduleIdsToday ?? []) ? '— [✓ Sudah Diisi Hari Ini]' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Ruangan -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">
                                Ruang Kelas / Laboratorium
                            </label>
                            <input type="text" readonly value="{{ $selectedSchedule->room->room_name }}" 
                                   class="w-full rounded-xl border border-slate-700 bg-slate-900/60 px-3.5 py-2.5 text-xs sm:text-sm font-bold text-slate-300 cursor-not-allowed">
                        </div>

                        <!-- Tanggal KBM -->
                        <div>
                            <label for="date" class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">
                                Tanggal Pelaksanaan KBM <span class="text-red-400">*</span>
                            </label>
                            <input type="date" id="date" name="date" required 
                                   value="{{ old('date', date('Y-m-d')) }}" 
                                   max="{{ date('Y-m-d') }}"
                                   class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2.5 text-xs sm:text-sm font-semibold text-white focus:border-indigo-500 focus:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                            <x-input-error :messages="$errors->get('date')" class="mt-1 text-xs" />
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    
                    <!-- KARTU 2: Materi & Foto Dokumentasi KBM (Kiri / 5 Kolom) -->
                    <div class="lg:col-span-5 space-y-5">
                        <div class="bg-[#0f172a]/90 backdrop-blur-md p-5 border border-slate-800 shadow-xl space-y-4" style="border-radius: 15px;">
                            <h2 class="text-base font-bold text-white pb-2 border-b border-slate-800">
                                Dokumentasi Pembelajaran
                            </h2>

                            <!-- Ringkasan Materi / Topik -->
                            <div>
                                <label for="topic_description" class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">
                                    Materi / Topik KBM Hari Ini <span class="text-red-400">*</span>
                                </label>
                                <textarea id="topic_description" name="topic_description" rows="4" required
                                          placeholder="Contoh: Pembahasan Bab 3 tentang Algoritma Pencarian. Praktik langsung di laboratorium komputer."
                                          class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2.5 text-xs sm:text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">{{ old('topic_description') }}</textarea>
                                <x-input-error :messages="$errors->get('topic_description')" class="mt-1 text-xs" />
                            </div>

                            <!-- Foto Bukti KBM Real-Time Kamera -->
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400">
                                        Foto Bukti KBM (Kamera Real-Time) <span class="text-red-400">*</span>
                                    </label>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-950/70 border border-rose-700/60 text-rose-300 flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-400 animate-pulse"></span>
                                        Kamera Langsung
                                    </span>
                                </div>

                                <!-- Box Kamera & Foto Real-Time -->
                                <div class="overflow-hidden rounded-2xl border-2 border-slate-700 bg-slate-950 relative">
                                    <!-- 1. Kondisi Belum Ambil Foto & Kamera Belum Aktif -->
                                    <div x-show="!photoPreview && !cameraActive" class="p-6 text-center space-y-3">
                                        <div class="w-14 h-14 rounded-2xl bg-indigo-950/60 border border-indigo-500/40 text-indigo-400 mx-auto flex items-center justify-center">
                                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold text-white">Kamera Belum Aktif</p>
                                            <p class="text-[11px] text-slate-400 mt-0.5">Wajib jepret langsung foto suasana KBM saat ini (galeri dinonaktifkan)</p>
                                        </div>
                                        
                                        <div class="flex items-center justify-center pt-1">
                                            <button type="button" @click="startCamera()" class="px-6 py-2.5 rounded-xl scp-btn-gradient text-xs font-bold text-white shadow-lg flex items-center justify-center gap-2 cursor-pointer transition">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                                <span>Buka & Nyalakan Kamera Real-Time</span>
                                            </button>
                                        </div>

                                        <template x-if="cameraError">
                                            <p class="text-xs text-rose-400 font-semibold mt-2" x-text="cameraError"></p>
                                        </template>
                                    </div>

                                    <!-- 2. Kondisi Kamera Aktif (Live Streaming Viewfinder) -->
                                    <div x-show="cameraActive && !photoPreview" style="display: none;" class="relative">
                                        <div class="relative bg-black">
                                            <video x-ref="videoPlayer" autoplay playsinline muted class="w-full h-64 sm:h-72 object-cover bg-black block"></video>
                                            
                                            <!-- Overlay Status Live -->
                                            <div class="absolute top-3 left-3 bg-slate-950/85 backdrop-blur-sm border border-slate-700 text-rose-400 px-3 py-1 rounded-full text-[10px] font-black tracking-wider uppercase flex items-center gap-1.5 shadow-md" style="z-index: 20;">
                                                <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                                                LIVE KAMERA AKTIF
                                            </div>

                                            <!-- Tombol Putar Kamera Depan/Belakang -->
                                            <button type="button" @click="switchCamera()" class="absolute top-3 right-3 bg-slate-900/85 hover:bg-slate-800 text-white p-2 rounded-full text-xs font-bold border border-slate-700 shadow-md transition" style="z-index: 20;" title="Putar Kamera Depan/Belakang">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                            </button>
                                        </div>

                                        <!-- Panel Tombol Kontrol Kamera (Tepat di Bawah Video, Selalu Terlihat & Mudah Ditekan) -->
                                        <div class="p-3 bg-slate-900 border-t border-slate-800 flex flex-wrap items-center justify-center gap-2.5">
                                            <button type="button" @click="snapPhoto()" class="px-6 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-extrabold text-xs shadow-lg shadow-rose-950/60 flex items-center gap-2 transition transform active:scale-95 cursor-pointer">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                <span>Ambil Foto KBM Sekarang</span>
                                            </button>
                                            <button type="button" @click="switchCamera()" class="px-3.5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold border border-slate-700 flex items-center gap-1.5 transition cursor-pointer" title="Putar Kamera Depan/Belakang">
                                                <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                                <span>Putar Kamera</span>
                                            </button>
                                            <button type="button" @click="stopCamera()" class="px-3.5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white text-xs font-semibold border border-slate-700 flex items-center gap-1.5 transition cursor-pointer">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                <span>Tutup</span>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- 3. Kondisi Foto Berhasil Diambil -->
                                    <div x-show="photoPreview" style="display: none;" class="relative">
                                        <div class="relative bg-black flex items-center justify-center overflow-hidden">
                                            <img :src="photoPreview" class="w-full max-h-80 sm:max-h-96 object-contain bg-black mx-auto block" alt="Hasil Foto Real-Time KBM">
                                        </div>
                                        
                                        <div class="absolute top-3 left-3 bg-emerald-950/90 border border-emerald-600 text-emerald-300 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider flex items-center gap-1.5 shadow-md">
                                            <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            FOTO REAL-TIME TERCATAT
                                        </div>

                                        <div class="p-3 bg-slate-900 border-t border-slate-800 flex items-center justify-between">
                                            <span class="text-[11px] text-emerald-400 font-semibold flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                Foto berhasil diambil & ter-watermark
                                            </span>
                                            <button type="button" @click="retake()" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-white text-xs font-bold shadow-md transition flex items-center gap-1.5 cursor-pointer">
                                                <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                                <span>Ambil Ulang Foto</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Input Tersembunyi (Menyimpan Hasil Jepretan Kamera) -->
                                <input type="file" name="photo" x-ref="realFileInput" class="hidden" accept="image/*">
                                <input type="hidden" name="photo_base64" x-model="photoBase64">
                                <x-input-error :messages="$errors->get('photo')" class="mt-1 text-xs" />
                            </div>
                        </div>

                        <!-- Tombol Submit -->
                        <div class="bg-[#0f172a]/90 backdrop-blur-md p-5 border border-slate-800 shadow-xl" style="border-radius: 15px;">
                            <button type="submit" 
                                    style="border-radius: 12px; color: #ffffff !important;"
                                    class="w-full py-3.5 px-6 font-black text-sm scp-btn-gradient cursor-pointer tracking-wider uppercase shadow-md hover:shadow-lg transition">
                                Simpan Jurnal & Presensi Siswa
                            </button>
                            <p class="text-[11px] text-center text-slate-400 mt-2">
                                Data kehadiran dan jurnal akan tercatat di sistem sekolah
                            </p>
                        </div>
                    </div>

                    <!-- KARTU 3: Daftar Presensi Siswa Kelas (Kanan / 7 Kolom) -->
                    <div class="lg:col-span-7">
                        <div class="bg-[#0f172a]/90 backdrop-blur-md p-5 border border-slate-800 shadow-xl" style="border-radius: 15px;">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-800 mb-3">
                                <div>
                                    <h2 class="text-base font-bold text-white">Presensi Siswa</h2>
                                    <p class="text-xs text-slate-400 mt-0.5">
                                        Kelas {{ $selectedSchedule->schoolClass->class_name }} ({{ $students->count() }} Siswa)
                                    </p>
                                </div>
                                
                                <!-- Tombol Cepat: Set Semua Hadir -->
                                <button type="button" @click="setAll('Hadir')" 
                                        class="text-xs font-bold px-3 py-1.5 rounded-lg bg-emerald-950/60 text-emerald-300 hover:bg-emerald-900 border border-emerald-800/60 transition cursor-pointer self-start sm:self-auto">
                                    ✓ Set Semua Hadir
                                </button>
                            </div>

                            @if($students->isEmpty())
                                <div class="text-center py-8 bg-slate-900/50 rounded-xl border border-dashed border-slate-800">
                                    <p class="text-xs font-bold text-white">Belum Ada Siswa di Kelas Ini</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">Waka Kurikulum belum mendaftarkan siswa ke dalam kelas {{ $selectedSchedule->schoolClass->class_name }}.</p>
                                </div>
                            @else
                                <!-- LIVE COUNTER STATUS KEHADIRAN SISWA -->
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-3">
                                    <div class="px-3 py-2 rounded-xl bg-emerald-950/40 border border-emerald-800/50 flex items-center justify-between">
                                        <span class="text-[11px] font-bold text-emerald-300">Hadir</span>
                                        <span x-text="counts.Hadir" class="text-sm font-black text-white">0</span>
                                    </div>
                                    <div class="px-3 py-2 rounded-xl bg-amber-950/40 border border-amber-800/50 flex items-center justify-between">
                                        <span class="text-[11px] font-bold text-amber-300">Sakit</span>
                                        <span x-text="counts.Sakit" class="text-sm font-black text-white">0</span>
                                    </div>
                                    <div class="px-3 py-2 rounded-xl bg-blue-950/40 border border-blue-800/50 flex items-center justify-between">
                                        <span class="text-[11px] font-bold text-blue-300">Izin</span>
                                        <span x-text="counts.Izin" class="text-sm font-black text-white">0</span>
                                    </div>
                                    <div class="px-3 py-2 rounded-xl bg-red-950/40 border border-red-800/50 flex items-center justify-between">
                                        <span class="text-[11px] font-bold text-red-300">Alpa</span>
                                        <span x-text="counts.Alpa" class="text-sm font-black text-white">0</span>
                                    </div>
                                </div>

                                <!-- KOLOM PENCARIAN CEPAT SISWA -->
                                <div class="mb-3 relative">
                                    <input type="text" x-model="searchStudent" 
                                           placeholder="Cari nama siswa di kelas ini..." 
                                           class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2 pl-9 text-xs text-white placeholder-slate-500 focus:border-indigo-500 focus:bg-slate-900 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>

                                <div class="space-y-2.5 max-h-[500px] overflow-y-auto pr-1">
                                    @foreach ($students as $index => $student)
                                        @php
                                            $approvedLeave = isset($approvedLeavesToday) ? $approvedLeavesToday->get($student->id) : null;
                                            $defaultLeaveStatus = $approvedLeave ? ($approvedLeave->leave_type === 'Sakit' ? 'Sakit' : 'Izin') : 'Hadir';
                                            $currentStatus = old('attendances.'.$student->id, $defaultLeaveStatus);
                                        @endphp
                                        <div x-show="!searchStudent || '{{ strtolower(addslashes($student->name)) }}'.includes(searchStudent.toLowerCase())"
                                             class="p-3 rounded-xl border {{ $approvedLeave ? 'border-indigo-500/40 bg-indigo-950/20' : 'border-slate-800/80 bg-slate-900/40' }} hover:bg-slate-800/40 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 transition">
                                            <!-- Info Siswa & Badge Izin ACC -->
                                            <div class="flex items-center gap-2.5 min-w-0">
                                                <span class="text-xs font-bold text-slate-500 w-5 text-right">{{ $index + 1 }}.</span>
                                                <div class="min-w-0">
                                                    <div class="flex items-center gap-2 flex-wrap">
                                                        <p class="text-xs font-bold text-white truncate">{{ $student->name }}</p>
                                                        @if($approvedLeave)
                                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-black {{ $approvedLeave->leave_type === 'Sakit' ? 'bg-amber-950/80 text-amber-300 border border-amber-600/70' : 'bg-blue-950/80 text-blue-300 border border-blue-600/70' }}" 
                                                                  title="Surat izin/sakit telah disetujui (ACC) oleh Wali Kelas / Guru BK">
                                                                ✓ ACC: {{ $approvedLeave->leave_type }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <p class="text-[10px] text-slate-400">@<span>{{ $student->username }}</span></p>
                                                </div>
                                            </div>

                                            <!-- Opsi Presensi (Hadir, Sakit, Izin, Alpa) -->
                                            <div class="flex items-center gap-1.5 shrink-0 self-end sm:self-auto">
                                                <!-- Hadir -->
                                                <label class="cursor-pointer">
                                                    <input type="radio" name="attendances[{{ $student->id }}]" value="Hadir" 
                                                           data-has-approved-leave="{{ $approvedLeave ? '1' : '' }}"
                                                           class="sr-only peer" 
                                                           {{ $currentStatus === 'Hadir' ? 'checked' : '' }}>
                                                    <span class="px-2.5 py-1 rounded-md text-[11px] font-bold border border-slate-700 text-slate-400 bg-slate-900 peer-checked:bg-emerald-600 peer-checked:text-white peer-checked:border-emerald-600 transition inline-block">
                                                        H
                                                    </span>
                                                </label>

                                                <!-- Sakit -->
                                                <label class="cursor-pointer">
                                                    <input type="radio" name="attendances[{{ $student->id }}]" value="Sakit" 
                                                           data-has-approved-leave="{{ $approvedLeave ? '1' : '' }}"
                                                           class="sr-only peer" 
                                                           {{ $currentStatus === 'Sakit' ? 'checked' : '' }}>
                                                    <span class="px-2.5 py-1 rounded-md text-[11px] font-bold border border-slate-700 text-slate-400 bg-slate-900 peer-checked:bg-amber-500 peer-checked:text-white peer-checked:border-amber-500 transition inline-block">
                                                        S
                                                    </span>
                                                </label>

                                                <!-- Izin -->
                                                <label class="cursor-pointer">
                                                    <input type="radio" name="attendances[{{ $student->id }}]" value="Izin" 
                                                           data-has-approved-leave="{{ $approvedLeave ? '1' : '' }}"
                                                           class="sr-only peer" 
                                                           {{ $currentStatus === 'Izin' ? 'checked' : '' }}>
                                                    <span class="px-2.5 py-1 rounded-md text-[11px] font-bold border border-slate-700 text-slate-400 bg-slate-900 peer-checked:bg-blue-600 peer-checked:text-white peer-checked:border-blue-600 transition inline-block">
                                                        I
                                                    </span>
                                                </label>

                                                <!-- Alpa -->
                                                <label class="cursor-pointer">
                                                    <input type="radio" name="attendances[{{ $student->id }}]" value="Alpa" 
                                                           data-has-approved-leave="{{ $approvedLeave ? '1' : '' }}"
                                                           class="sr-only peer" 
                                                           {{ $currentStatus === 'Alpa' ? 'checked' : '' }}>
                                                    <span class="px-2.5 py-1 rounded-md text-[11px] font-bold border border-slate-700 text-slate-400 bg-slate-900 peer-checked:bg-red-600 peer-checked:text-white peer-checked:border-red-600 transition inline-block">
                                                        A
                                                    </span>
                                                </label>
                                            </div>

                                            <!-- Input Catatan Khusus Siswa (Opsional / Otomatis Alasan Izin) -->
                                            <input type="text" name="attendance_notes[{{ $student->id }}]" 
                                                   value="{{ old('attendance_notes.'.$student->id, $approvedLeave ? $approvedLeave->reason : '') }}"
                                                   placeholder="{{ $approvedLeave ? 'Alasan ACC: ' . $approvedLeave->reason : 'Catatan khusus (opsional)...' }}" 
                                                   class="w-full sm:w-44 text-[11px] rounded-lg bg-slate-950/80 border border-slate-700/80 text-slate-200 placeholder-slate-500 px-2.5 py-1 focus:border-indigo-500 focus:outline-none">
                                        </div>
                                    @endforeach
                                </div>

                                <div class="mt-3 pt-3 border-t border-slate-800 flex items-center justify-between text-[11px] text-slate-400 font-semibold">
                                    <span>Keterangan:</span>
                                    <div class="flex gap-2.5">
                                        <span class="text-emerald-400 font-bold">H = Hadir</span>
                                        <span class="text-amber-400 font-bold">S = Sakit</span>
                                        <span class="text-blue-400 font-bold">I = Izin</span>
                                        <span class="text-red-400 font-bold">A = Alpa</span>
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
            searchStudent: '',
            counts: { Hadir: 0, Sakit: 0, Izin: 0, Alpa: 0 },

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
                    const video = this.$refs.videoPlayer;
                    if (video) {
                        video.srcObject = this.cameraStream;
                        await video.play();
                        this.cameraActive = true;
                    }
                } catch (err) {
                    console.error('Kamera error:', err);
                    this.cameraError = 'Gagal mengakses kamera langsung browser. Pastikan izin kamera aktif pada browser Anda.';
                    this.cameraActive = false;
                }
            },

            stopCamera() {
                if (this.cameraStream) {
                    this.cameraStream.getTracks().forEach(t => t.stop());
                    this.cameraStream = null;
                }
                this.cameraActive = false;
            },

            switchCamera() {
                this.facingMode = this.facingMode === 'environment' ? 'user' : 'environment';
                this.startCamera();
            },

            snapPhoto() {
                const video = this.$refs.videoPlayer;
                if (!video || !this.cameraActive) return;

                const canvas = document.createElement('canvas');
                canvas.width = video.videoWidth || 1280;
                canvas.height = video.videoHeight || 720;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                // Watermark waktu real-time proporsional & terbaca jelas di HP/Laptop
                const now = new Date();
                const dateStr = now.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) + ' • ' +
                                now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) + ' WIB';

                const w = canvas.width;
                const h = canvas.height;
                const minDim = Math.min(w, h);
                const fontSize = Math.max(16, Math.round(minDim * 0.035));
                const barHeight = Math.round(fontSize * 2.5);
                const paddingX = Math.round(fontSize * 0.8);

                // Strip pita gelap semi-transparan di bagian bawah foto
                ctx.fillStyle = 'rgba(15, 23, 42, 0.90)';
                ctx.fillRect(0, h - barHeight, w, barHeight);

                // Garis aksen neon rose di atas pita
                ctx.fillStyle = '#e11d48';
                ctx.fillRect(0, h - barHeight, w, Math.max(3, Math.round(fontSize * 0.12)));

                // Indikator Titik Merah Real-Time
                const dotRadius = Math.max(5, Math.round(fontSize * 0.28));
                const centerY = h - (barHeight / 2);
                ctx.fillStyle = '#ef4444';
                ctx.beginPath();
                ctx.arc(paddingX + dotRadius, centerY, dotRadius, 0, Math.PI * 2);
                ctx.fill();

                // Teks Watermark
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
