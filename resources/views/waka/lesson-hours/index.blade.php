<x-app-layout>
    <div x-data="{ 
            editModalOpen: false, 
            activeHour: { id: '', day_of_week: '', session_number: '', name: '', start_time: '', end_time: '', duration_minutes: 40, notes: '' } 
         }" 
         class="space-y-6">
        
        <!-- Header -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 w-full">
            <div>
                <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">Master Data Jam Pembelajaran</h1>
                <p class="text-xs font-semibold text-slate-400 mt-0.5">Kelola alokasi durasi menit per sesi pembelajaran (Jam ke-1 sampai dengan Jam ke-10) per hari maupun universal</p>
            </div>
            
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-900 border border-slate-700 text-indigo-300">
                    Mulai Normal: 07:15 • Default: 40 Menit
                </span>
            </div>
        </div>

        <!-- Card Agenda Tambahan & Istirahat (Notes Waktu Resmi) -->
        <div class="lesson-notes-banner p-5 rounded-2xl bg-gradient-to-r from-slate-900 via-indigo-950/30 to-slate-900 border border-slate-800 shadow-xl space-y-3">            <div class="flex items-center justify-between border-b border-slate-800/80 pb-2.5">
                <h2 class="text-xs font-black uppercase tracking-wider text-amber-400">
                    Notes Agenda Khusus dan Waktu Istirahat Sekolah
                </h2>
                <span class="text-[11px] text-slate-400 font-semibold">Tersinkronisasi otomatis dengan jam pelajaran</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
                <!-- 1. Agenda Pagi Khusus -->
                <div class="p-3 rounded-xl bg-slate-900/80 border border-slate-800 space-y-1">
                    <span class="text-[10px] font-extrabold uppercase text-slate-400 block">Agenda Pagi Khusus</span>
                    <p class="text-white font-bold">Senin: Upacara Bendera (07:00 - 08:00)</p>
                    <span class="text-[10px] text-amber-300 block">Dilaksanakan 2 minggu sekali atau kondisional</span>
                    <p class="text-indigo-300 font-bold pt-1">Selasa: Literasi Membaca (07:00 - 07:30)</p>
                    <span class="text-[10px] text-slate-500 block">Rabu, Kamis, Jumat: Mulai 07:15</span>
                </div>

                <!-- 2. Istirahat 1 (15 Menit) -->
                <div class="p-3 rounded-xl bg-slate-900/80 border border-slate-800 space-y-1">
                    <span class="text-[10px] font-extrabold uppercase text-slate-400 block">Istirahat 1 (15 Menit)</span>
                    <p class="text-slate-300">• Senin: <strong>09:45 - 10:00</strong></p>
                    <p class="text-slate-300">• Selasa: <strong>09:30 - 09:45</strong></p>
                    <p class="text-slate-300">• Rabu sampai dengan Jumat: <strong>09:15 - 09:30</strong></p>
                </div>

                <!-- 3. Istirahat 2 (Istirahat, Sholat, dan Makan) -->
                <div class="p-3 rounded-xl bg-slate-900/80 border border-slate-800 space-y-1">
                    <span class="text-[10px] font-extrabold uppercase text-slate-400 block">Istirahat 2 (Istirahat, Sholat, dan Makan)</span>
                    <p class="text-slate-300">• Senin dan Selasa: <strong>11:45 - 12:45</strong></p>
                    <p class="text-slate-300">• Rabu dan Kamis: <strong>11:30 - 12:30</strong></p>
                    <p class="text-amber-300 font-bold">• Jumat: 11:25 - 12:40 (75 Menit Sholat Jumat)</p>
                </div>

                <!-- 4. Istirahat 3 / Penutup -->
                <div class="p-3 rounded-xl bg-slate-900/80 border border-slate-800 space-y-1">
                    <span class="text-[10px] font-extrabold uppercase text-slate-400 block">Istirahat 3 (Sholat Ashar dan Piket Kelas)</span>
                    <p class="text-slate-300">• Senin sampai dengan Kamis: <strong>15:10 - 15:30</strong></p>
                    <span class="text-[10px] text-slate-400 block">Sholat Ashar berjamaah dan piket kelas</span>
                    <p class="text-slate-300">• Jumat: <strong>13:50 - 14:00</strong> (Piket Kelas)</p>
                </div>
            </div>
        </div>

        <!-- Form Pengaturan Durasi Cepat (Universal / Per Hari) -->
        <div class="p-5 rounded-2xl bg-[#0f172a]/90 border border-slate-800 shadow-xl space-y-3">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border-b border-slate-800 pb-3">
                <div>
                    <h2 class="text-sm font-extrabold text-white">Ubah Durasi Pembelajaran</h2>
                    <p class="text-[11px] text-slate-400 font-semibold mt-0.5">Pilih berapa menit durasi yang diinginkan, jam selesai otomatis terkalkulasi tanpa input jam manual</p>
                </div>
            </div>

            <form method="POST" action="{{ route('waka.lesson-hours.preset') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs pt-1">
                @csrf
                <!-- Pilih Target Hari -->
                <div>
                    <label class="block font-bold text-slate-300 mb-1">Terapkan Pada Hari</label>
                    <select name="target_day" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2.5 text-white font-semibold focus:border-indigo-500">
                        <option value="all">Seluruh Hari Sekaligus (Universal)</option>
                        <option value="Senin">Hari Senin Saja</option>
                        <option value="Selasa">Hari Selasa Saja</option>
                        <option value="Rabu">Hari Rabu Saja</option>
                        <option value="Kamis">Hari Kamis Saja</option>
                        <option value="Jumat">Hari Jumat Saja</option>
                    </select>
                </div>

                <!-- Pilih Durasi Menit -->
                <div>
                    <label class="block font-bold text-slate-300 mb-1">Durasi Menit Per Sesi</label>
                    <select name="duration_minutes" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2.5 text-white font-semibold focus:border-indigo-500">
                        <option value="20">20 Menit</option>
                        <option value="25">25 Menit</option>
                        <option value="30">30 Menit (Durasi Ujian / Khusus)</option>
                        <option value="35">35 Menit (Durasi Singkat / Khusus)</option>
                        <option value="40" selected>40 Menit (Default Standar Sekolah)</option>
                        <option value="45">45 Menit (Durasi Panjang)</option>
                    </select>
                </div>

                <!-- Tombol Submit -->
                <div class="flex items-end">
                    <button type="submit" class="w-full py-2.5 rounded-xl scp-btn-gradient text-xs font-bold text-white shadow-md cursor-pointer transition">
                        Terapkan Durasi
                    </button>
                </div>
            </form>
        </div>

        <!-- Navigasi Tab Hari -->
        <div class="flex items-center gap-2 overflow-x-auto pb-1">
            @foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'] as $day)
                <a href="{{ route('waka.lesson-hours.index', ['day' => $day]) }}" 
                   class="px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 whitespace-nowrap {{ $selectedDay === $day ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'bg-[#0f172a] text-slate-300 hover:bg-slate-800 border border-slate-800' }}">
                    <span>Hari {{ $day }}</span>
                </a>
            @endforeach
        </div>

        <!-- Tabel Data Jam Pembelajaran untuk Hari Terpilih -->
        <div class="bg-[#0f172a]/90 backdrop-blur-md border border-slate-800 shadow-xl overflow-hidden rounded-2xl">
            <div class="p-4 border-b border-slate-800 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-extrabold text-white">Jadwal Sesi Hari {{ $selectedDay }}</h2>
                    <p class="text-xs text-slate-400">
                        @if($selectedDay === 'Senin')
                            Dimulai pukul 08:00 (Jika ada Upacara Bendera)
                        @elseif($selectedDay === 'Selasa')
                            Dimulai pukul 07:30 (Jika ada Literasi Membaca)
                        @elseif($selectedDay === 'Jumat')
                            Dimulai pukul 07:15 Normal (Maksimal sampai Jam ke - 8)
                        @else
                            Dimulai pukul 07:15 Normal
                        @endif
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-800/80 text-sm">
                    <thead class="bg-slate-900/70">
                        <tr class="text-left text-xs font-bold text-slate-400 uppercase tracking-wider">
                            <th class="p-4">Sesi Pembelajaran</th>
                            <th class="p-4">Jam Mulai</th>
                            <th class="p-4">Jam Selesai</th>
                            <th class="p-4 text-center">Durasi</th>
                            <th class="p-4">Keterangan</th>
                            <th class="p-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @forelse($lessonHours as $hour)
                            <tr class="hover:bg-slate-800/30 transition">
                                <td class="p-4">
                                    <span class="font-extrabold text-white">{{ $hour->name }}</span>
                                </td>
                                <td class="p-4">
                                    <span class="font-mono text-sm font-bold text-indigo-300 bg-indigo-950/40 px-2.5 py-1 rounded-md border border-indigo-500/20">
                                        {{ $hour->start_time }}
                                    </span>
                                </td>
                                <td class="p-4">
                                    <span class="font-mono text-sm font-bold text-indigo-300 bg-indigo-950/40 px-2.5 py-1 rounded-md border border-indigo-500/20">
                                        {{ $hour->end_time }}
                                    </span>
                                </td>
                                <td class="p-4 text-center">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-slate-800 text-emerald-400 border border-slate-700">
                                        {{ $hour->duration_minutes }} Menit
                                    </span>
                                </td>
                                <td class="p-4 text-xs text-slate-400 font-semibold">
                                    {{ $hour->notes ?? '-' }}
                                </td>
                                <td class="p-4 text-center">
                                    <button @click="activeHour = {
                                                id: '{{ $hour->id }}',
                                                day_of_week: '{{ $hour->day_of_week }}',
                                                session_number: {{ $hour->session_number }},
                                                name: '{{ $hour->name }}',
                                                start_time: '{{ $hour->start_time }}',
                                                end_time: '{{ $hour->end_time }}',
                                                duration_minutes: '{{ $hour->duration_minutes }}',
                                                notes: '{{ $hour->notes }}'
                                            }; editModalOpen = true" 
                                            type="button" 
                                            class="px-3 py-1 rounded-lg text-xs font-bold bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition cursor-pointer">
                                        Edit Sesi
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-6 text-center text-xs text-slate-500">
                                    Belum ada data jam pembelajaran untuk hari {{ $selectedDay }}. Silakan jalankan migrasi database.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ================= MODAL EDIT SESI PEMBELAJARAN ================= -->
        <div x-show="editModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div @click="editModalOpen = false" class="fixed inset-0 bg-black/70 backdrop-blur-sm"></div>
            <div class="relative w-full max-w-sm bg-[#0f172a] p-6 shadow-2xl border border-slate-800 space-y-4 rounded-2xl">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-white">Edit <span x-text="activeHour.name" class="text-indigo-400"></span> (<span x-text="activeHour.day_of_week"></span>)</h3>
                    <button @click="editModalOpen = false" class="text-xs font-bold text-slate-400 hover:text-white px-2 py-1">[Tutup]</button>
                </div>

                <form :action="'{{ url('/waka/lesson-hours') }}/' + activeHour.id" method="POST" class="space-y-4 text-xs">
                    @csrf
                    @method('PUT')
                    
                    <!-- Khusus Jam ke - 1: Bisa input jam mulai pembelajaran -->
                    <template x-if="activeHour.session_number == 1">
                        <div>
                            <label class="block font-bold text-slate-300 mb-1">Jam Mulai Pembelajaran</label>
                            <input type="time" name="start_time" x-model="activeHour.start_time" required class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white font-mono font-bold focus:border-indigo-500" />
                            <span class="text-[10px] text-indigo-300 mt-1 block">
                                Khusus Jam ke-1: Mengubah jam mulai di sini akan otomatis menghitung jam sesi berikutnya secara berantai.
                            </span>
                        </div>
                    </template>

                    <!-- Untuk Jam ke-2 sampai Jam ke-10: Jam mulai readonly -->
                    <template x-if="activeHour.session_number > 1">
                        <div>
                            <label class="block font-bold text-slate-400 mb-1">Jam Mulai (Otomatis)</label>
                            <div class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-slate-400 font-mono font-bold">
                                <span x-text="activeHour.start_time"></span>
                                <span class="text-[10px] text-slate-500 font-sans font-normal ml-1">(Meneruskan dari jam sebelumnya)</span>
                            </div>
                        </div>
                    </template>

                    <!-- Pilihan Durasi Menit -->
                    <div>
                        <label class="block font-bold text-slate-300 mb-1">Pilih Durasi Menit Sesi Ini</label>
                        <select name="duration_minutes" x-model="activeHour.duration_minutes" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white font-semibold focus:border-indigo-500">
                            <option value="20">20 Menit</option>
                            <option value="25">25 Menit</option>
                            <option value="30">30 Menit</option>
                            <option value="35">35 Menit</option>
                            <option value="40">40 Menit (Standar)</option>
                            <option value="45">45 Menit</option>
                            <option value="50">50 Menit</option>
                        </select>
                        <span class="text-[10px] text-slate-400 mt-1 block">
                            *Jam selesai dan jam sesi berikutnya akan otomatis diperbarui secara berantai.
                        </span>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-300 mb-1">Keterangan Sesi</label>
                        <input type="text" name="notes" x-model="activeHour.notes" placeholder="Misal: Sesi Belajar Pagi" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white focus:border-indigo-500" />
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button @click="editModalOpen = false" type="button" class="px-4 py-2 rounded-xl text-slate-400 hover:bg-slate-800 hover:text-white transition cursor-pointer">Batal</button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl scp-btn-gradient font-bold text-white shadow-md cursor-pointer transition">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>
