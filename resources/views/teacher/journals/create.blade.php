<x-app-layout>
    <div x-data="{ 
        photoPreview: null,
        searchStudent: '',
        counts: { Hadir: 0, Sakit: 0, Izin: 0, Alpa: 0 },
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
            document.querySelectorAll('input[type=radio][value=' + status + ']').forEach(el => el.checked = true);
            this.updateCounts();
        },
        init() {
            this.$nextTick(() => { this.updateCounts(); });
        }
    }" class="space-y-6">

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
                        {{ $selectedSchedule->day_of_week }} • {{ substr($selectedSchedule->start_time, 0, 5) }} - {{ substr($selectedSchedule->end_time, 0, 5) }} WIB
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
                                        {{ $item->schoolClass->class_name }} — {{ $item->subject }} ({{ $item->day_of_week }})
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

                            <!-- Upload Foto Bukti KBM -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">
                                    Foto Bukti KBM <span class="text-red-400">*</span>
                                </label>
                                <div class="mt-1 flex flex-col items-center justify-center p-4 border-2 border-dashed border-slate-700 rounded-xl bg-slate-900/60 hover:bg-slate-900 transition cursor-pointer"
                                     @click="$refs.photoInput.click()">
                                    
                                    <template x-if="!photoPreview">
                                        <div class="text-center py-2">
                                            <p class="text-xs font-bold text-indigo-400">Klik untuk upload foto</p>
                                            <p class="text-[11px] text-slate-400 mt-0.5">JPG, PNG, atau WebP (Maks. 3 MB)</p>
                                        </div>
                                    </template>

                                    <!-- Preview Gambar Terpilih -->
                                    <template x-if="photoPreview">
                                        <div class="relative w-full">
                                            <img :src="photoPreview" class="h-44 w-full object-cover rounded-lg shadow-sm" alt="Preview Foto KBM">
                                            <span class="absolute bottom-2 right-2 bg-slate-950/80 text-white text-[10px] px-2 py-0.5 rounded font-bold border border-slate-700">
                                                Ganti Foto
                                            </span>
                                        </div>
                                    </template>

                                    <input type="file" name="photo" x-ref="photoInput" required accept="image/*" class="hidden"
                                           @change="
                                               const file = $refs.photoInput.files[0];
                                               if (file) {
                                                   const reader = new FileReader();
                                                   reader.onload = (e) => { photoPreview = e.target.result; };
                                                   reader.readAsDataURL(file);
                                               }
                                           ">
                                </div>
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
                                        <div x-show="!searchStudent || '{{ strtolower(addslashes($student->name)) }}'.includes(searchStudent.toLowerCase())"
                                             class="p-3 rounded-xl border border-slate-800/80 bg-slate-900/40 hover:bg-slate-800/40 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 transition">
                                            <!-- Info Siswa -->
                                            <div class="flex items-center gap-2.5 min-w-0">
                                                <span class="text-xs font-bold text-slate-500 w-5 text-right">{{ $index + 1 }}.</span>
                                                <div class="min-w-0">
                                                    <p class="text-xs font-bold text-white truncate">{{ $student->name }}</p>
                                                    <p class="text-[10px] text-slate-400">@<span>{{ $student->username }}</span></p>
                                                </div>
                                            </div>

                                            <!-- Opsi Presensi (Hadir, Sakit, Izin, Alpa) -->
                                            <div class="flex items-center gap-1.5 shrink-0 self-end sm:self-auto">
                                                <!-- Hadir -->
                                                <label class="cursor-pointer">
                                                    <input type="radio" name="attendances[{{ $student->id }}]" value="Hadir" 
                                                           class="sr-only peer" 
                                                           {{ old('attendances.'.$student->id, 'Hadir') === 'Hadir' ? 'checked' : '' }}>
                                                    <span class="px-2.5 py-1 rounded-md text-[11px] font-bold border border-slate-700 text-slate-400 bg-slate-900 peer-checked:bg-emerald-600 peer-checked:text-white peer-checked:border-emerald-600 transition inline-block">
                                                        H
                                                    </span>
                                                </label>

                                                <!-- Sakit -->
                                                <label class="cursor-pointer">
                                                    <input type="radio" name="attendances[{{ $student->id }}]" value="Sakit" 
                                                           class="sr-only peer" 
                                                           {{ old('attendances.'.$student->id) === 'Sakit' ? 'checked' : '' }}>
                                                    <span class="px-2.5 py-1 rounded-md text-[11px] font-bold border border-slate-700 text-slate-400 bg-slate-900 peer-checked:bg-amber-500 peer-checked:text-white peer-checked:border-amber-500 transition inline-block">
                                                        S
                                                    </span>
                                                </label>

                                                <!-- Izin -->
                                                <label class="cursor-pointer">
                                                    <input type="radio" name="attendances[{{ $student->id }}]" value="Izin" 
                                                           class="sr-only peer" 
                                                           {{ old('attendances.'.$student->id) === 'Izin' ? 'checked' : '' }}>
                                                    <span class="px-2.5 py-1 rounded-md text-[11px] font-bold border border-slate-700 text-slate-400 bg-slate-900 peer-checked:bg-blue-600 peer-checked:text-white peer-checked:border-blue-600 transition inline-block">
                                                        I
                                                    </span>
                                                </label>

                                                <!-- Alpa -->
                                                <label class="cursor-pointer">
                                                    <input type="radio" name="attendances[{{ $student->id }}]" value="Alpa" 
                                                           class="sr-only peer" 
                                                           {{ old('attendances.'.$student->id) === 'Alpa' ? 'checked' : '' }}>
                                                    <span class="px-2.5 py-1 rounded-md text-[11px] font-bold border border-slate-700 text-slate-400 bg-slate-900 peer-checked:bg-red-600 peer-checked:text-white peer-checked:border-red-600 transition inline-block">
                                                        A
                                                    </span>
                                                </label>
                                            </div>
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
</x-app-layout>
