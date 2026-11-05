<x-app-layout>
    <div x-data="{ 
            addModalOpen: false, 
            viewModalOpen: false, 
            importModalOpen: false,
            selectedRoom: {
                id: '',
                room_name: '',
                schedules_count: 0,
                schedules: []
            },
            openView(roomData, schedulesJson) {
                this.selectedRoom = {
                    ...roomData,
                    schedules: schedulesJson ? JSON.parse(schedulesJson) : []
                };
                this.viewModalOpen = true;
            }
         }" 
         class="space-y-6">
        
        <!-- Header & Action Bar -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 w-full">
            <div class="text-left">
                <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">Master Data Ruang</h1>
                <p class="text-xs font-semibold text-slate-400 mt-0.5">Kelola data ruang teori, laboratorium komputer, dan pantau jadwal penggunaan ruangan</p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <button @click="importModalOpen = true" type="button" class="px-3.5 sm:px-4 py-2 sm:py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-xs sm:text-sm font-bold text-white shadow-md shadow-emerald-950/40 cursor-pointer transition flex items-center gap-1.5">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    <span>Import Excel / Dokumen</span>
                </button>
                <button @click="addModalOpen = true" type="button" class="px-4 sm:px-5 py-2 sm:py-2.5 rounded-xl scp-btn-gradient text-xs sm:text-sm font-bold text-white shadow-md cursor-pointer transition whitespace-nowrap">
                    + Tambah Ruang Baru
                </button>
            </div>
        </div>

        <!-- Tabel Ruangan -->
        <div class="bg-[#0f172a]/90 backdrop-blur-md border border-slate-800 shadow-xl overflow-hidden" style="border-radius: 15px;">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-800/80 text-sm">
                    <thead class="bg-slate-900/70">
                        <tr class="text-left text-xs font-bold text-slate-400 uppercase tracking-wider">
                            <th class="p-4 text-center w-12">No</th>
                            <th class="p-4">Nama Ruangan</th>
                            <th class="p-4 text-center">Jadwal Menggunakan</th>
                            <th class="p-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @forelse ($rooms as $room)
                            <tr class="hover:bg-slate-800/30 transition">
                                <td class="p-4 text-center text-xs font-bold text-slate-400">
                                    {{ $rooms->firstItem() + $loop->index }}
                                </td>
                                <td class="p-4">
                                    <span class="font-bold text-white text-base">{{ $room->room_name }}</span>
                                </td>
                                <td class="p-4 text-center">
                                    <span class="inline-flex items-center justify-center rounded-full bg-indigo-950/60 px-3 py-1 text-xs font-bold text-indigo-300 border border-indigo-800/60 shadow-sm">
                                        {{ $room->schedules_count }} Jadwal Aktif
                                    </span>
                                </td>
                                <td class="p-4 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        <!-- Tombol Lihat Jadwal Ruangan -->
                                        <button type="button" 
                                                @click="openView({
                                                    id: '{{ $room->id }}',
                                                    room_name: '{{ addslashes($room->room_name) }}',
                                                    schedules_count: '{{ $room->schedules_count }}'
                                                }, '{{ addslashes(json_encode($room->schedules->map(function($s) {
                                                    $start = (int) substr($s->start_time, 0, 2);
                                                    $end = (int) substr($s->end_time, 0, 2);
                                                    if ($start === 0 && $end === 0) {
                                                        $pLabel = '-';
                                                    } else {
                                                        $pLabel = ($start === $end || $end === 0) ? "Jam Ke-{$start}" : "Jam Ke-{$start} - {$end}";
                                                    }
                                                    return [
                                                        'id' => $s->id,
                                                        'day_of_week' => $s->day_of_week,
                                                        'period_label' => $pLabel,
                                                        'subject' => $s->subject,
                                                        'cycle_type' => $s->cycle_type,
                                                        'class_name' => $s->schoolClass?->class_name ?? '-',
                                                        'teacher_name' => $s->teacher?->name ?? '-'
                                                    ];
                                                }))) }}')"
                                                class="px-2.5 py-1 text-xs font-bold rounded-lg transition cursor-pointer btn-action-view shadow-sm"
                                                title="Lihat Jadwal yang Menggunakan Ruangan Ini">
                                            Lihat
                                        </button>

                                        <!-- Tombol Hapus Ruangan -->
                                        <form method="POST" action="{{ route('waka.rooms.destroy', $room) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus ruangan ini beserta seluruh penugasan jadwalnya?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-2.5 py-1 text-xs font-bold rounded-lg transition cursor-pointer btn-action-delete shadow-sm" title="Hapus Ruangan">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-8 text-center text-slate-500 text-sm">
                                    Belum ada data ruangan yang terdaftar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($rooms->hasPages())
                <div class="p-4 border-t border-slate-800">
                    {{ $rooms->links() }}
                </div>
            @endif
        </div>

        <!-- ============================================================= -->
        <!-- MODAL 1: LIHAT JADWAL PENGGUNAAN RUANGAN                      -->
        <!-- ============================================================= -->
        <div x-show="viewModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
            <div @click="viewModalOpen = false" class="fixed inset-0 bg-black/75 backdrop-blur-sm"></div>
            
            <div class="relative w-full max-w-4xl bg-white dark:bg-[#0f172a] p-6 sm:p-7 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4 my-auto max-h-[90vh] overflow-y-auto" style="border-radius: 20px;">
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                            <span>Jadwal Penggunaan Ruangan</span>
                        </h3>
                        <p class="text-xs text-indigo-600 dark:text-indigo-400 font-mono font-bold mt-0.5" x-text="selectedRoom.room_name"></p>
                    </div>
                    <button @click="viewModalOpen = false" class="text-xs font-bold text-slate-400 hover:text-slate-800 dark:hover:text-white px-2 py-1 cursor-pointer">
                        [Tutup]
                    </button>
                </div>

                <!-- Info Ringkasan Ruangan -->
                <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 flex items-center justify-between">
                    <div>
                        <span class="text-xs text-slate-500 dark:text-slate-400 font-bold block">Ruangan:</span>
                        <span class="text-base font-extrabold text-slate-900 dark:text-white" x-text="selectedRoom.room_name"></span>
                    </div>
                    <span class="px-3.5 py-1.5 rounded-full text-xs font-bold bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 shadow-sm whitespace-nowrap" x-text="selectedRoom.schedules_count + ' Jadwal Menggunakan'"></span>
                </div>

                <!-- Tabel Daftar Jadwal Ruangan -->
                <div class="rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden bg-white dark:bg-slate-950/60 shadow-sm">
                    <div class="max-h-80 overflow-y-auto overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800 text-xs">
                            <thead class="bg-slate-50 dark:bg-slate-900/90 sticky top-0 border-b border-slate-200 dark:border-slate-800">
                                <tr class="text-left font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                    <th class="p-3.5 whitespace-nowrap min-w-[150px]">Hari & Jam</th>
                                    <th class="p-3.5 whitespace-nowrap min-w-[110px]">Kelas</th>
                                    <th class="p-3.5 min-w-[180px]">Mata Pelajaran</th>
                                    <th class="p-3.5 min-w-[200px]">Guru Pengajar</th>
                                    <th class="p-3.5 text-center whitespace-nowrap min-w-[110px]">Siklus</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-slate-700 dark:text-slate-300">
                                <template x-for="sch in selectedRoom.schedules" :key="sch.id">
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                                        <!-- Kolom Hari & Jam Pelajaran (Format Manusiawi: Jam Ke-X - Y) -->
                                        <td class="p-3.5 whitespace-nowrap">
                                            <span class="font-bold text-slate-900 dark:text-white block text-xs" x-text="sch.day_of_week"></span>
                                            <span class="inline-flex items-center gap-1 mt-1 px-2.5 py-0.5 rounded-md text-[11px] font-bold bg-slate-100 text-slate-700 border border-slate-300 dark:bg-indigo-950/80 dark:text-indigo-300 dark:border-indigo-700/50 shadow-xs" x-text="sch.period_label"></span>
                                        </td>
                                        <!-- Kolom Kelas (Diproteksi whitespace-nowrap agar nomor rombel tidak melipat ke bawah) -->
                                        <td class="p-3.5 whitespace-nowrap">
                                            <span class="inline-flex items-center whitespace-nowrap px-3 py-1 rounded-lg text-xs font-extrabold bg-indigo-50 text-indigo-700 border border-indigo-200 dark:bg-indigo-950 dark:text-indigo-200 dark:border-indigo-700/60 shadow-xs tracking-wide" x-text="sch.class_name"></span>
                                        </td>
                                        <!-- Kolom Mata Pelajaran -->
                                        <td class="p-3.5 font-bold text-slate-900 dark:text-white text-xs" x-text="sch.subject"></td>
                                        <!-- Kolom Guru Pengajar -->
                                        <td class="p-3.5 font-medium text-slate-700 dark:text-slate-300 text-xs" x-text="sch.teacher_name"></td>
                                        <!-- Kolom Siklus (Diproteksi whitespace-nowrap agar nomor putaran tidak melipat ke bawah) -->
                                        <td class="p-3.5 text-center whitespace-nowrap">
                                            <span class="inline-flex items-center whitespace-nowrap px-3 py-1 rounded-md text-xs font-bold bg-slate-100 text-slate-700 border border-slate-300 dark:bg-slate-900 dark:text-amber-300 dark:border-amber-500/30 shadow-xs" x-text="sch.cycle_type || 'Putaran 1'"></span>
                                        </td>
                                    </tr>
                                </template>
                                <template x-if="!selectedRoom.schedules || selectedRoom.schedules.length === 0">
                                    <tr>
                                        <td colspan="5" class="p-8 text-center text-slate-500 italic">
                                            Ruangan ini belum digunakan dalam jadwal pelajaran aktif.
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button @click="viewModalOpen = false" type="button" class="px-5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-white font-bold text-xs transition cursor-pointer">
                        Tutup Tampilan
                    </button>
                </div>
            </div>
        </div>

        <!-- ============================================================= -->
        <!-- MODAL 2: TAMBAH RUANGAN BARU                                  -->
        <!-- ============================================================= -->
        <div x-show="addModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
            <div @click="addModalOpen = false" class="fixed inset-0 bg-black/70 backdrop-blur-sm"></div>
            
            <div class="relative w-full max-w-md bg-[#0f172a] p-6 sm:p-8 shadow-2xl border border-slate-800 space-y-4 my-auto max-h-[90vh] overflow-y-auto" style="border-radius: 20px;">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-lg font-bold text-white">Tambah Ruangan Baru</h3>
                    <button @click="addModalOpen = false" class="text-xs font-bold text-slate-400 hover:text-white px-2 py-1 cursor-pointer">
                        [Tutup]
                    </button>
                </div>

                <form method="POST" action="{{ route('waka.rooms.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Nama Ruangan</label>
                        <input type="text" name="room_name" required class="w-full rounded-xl border border-slate-700 bg-slate-900/90 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" placeholder="Contoh: Ruang 101 atau Lab Komputer 1" />
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button @click="addModalOpen = false" type="button" class="rounded-xl px-4 py-2 text-xs font-bold text-slate-400 hover:bg-slate-800 hover:text-white transition cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl scp-btn-gradient text-xs font-bold text-white shadow-md cursor-pointer transition">
                            Simpan Ruangan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ============================================================= -->
        <!-- MODAL 3: IMPORT DATA RUANGAN TERPADU (SESUAI FOTO KE-2)       -->
        <!-- ============================================================= -->
        <div x-show="importModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
            <div @click="importModalOpen = false" class="fixed inset-0 bg-black/70 backdrop-blur-sm"></div>
            <div class="relative w-full max-w-lg bg-[#0f172a] p-6 shadow-2xl border border-slate-800 space-y-4 my-auto max-h-[90vh] overflow-y-auto" style="border-radius: 20px;">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div>
                        <h3 class="text-base font-extrabold text-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                            Import Data Ruangan Terpadu (PDF / Excel / CSV)
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">Unggah berkas PDF matriks/legenda ruang FET atau template spreadsheet</p>
                    </div>
                    <button @click="importModalOpen = false" class="text-xs font-bold text-slate-400 hover:text-white px-2 py-1 cursor-pointer">[Tutup]</button>
                </div>

                <!-- Petunjuk & Unduh Template -->
                <div class="p-3.5 rounded-xl bg-slate-900 border border-slate-800 space-y-2 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-slate-300">Format Berkas yang Didukung:</span>
                        <a href="{{ route('waka.rooms.template') }}" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-indigo-600/30 hover:bg-indigo-600 text-indigo-300 hover:text-white border border-indigo-500/30 font-bold transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Template Excel/CSV
                        </a>
                    </div>
                    <ul class="text-slate-400 text-[11px] space-y-1 list-disc list-inside">
                        <li><strong class="text-indigo-400">PDF Dokumen / FET:</strong> Berkas PDF jadwal matriks ruang FET atau sarana prasarana sekolah. Sistem otomatis membaca legenda lab (contoh: <code class="text-indigo-300 font-mono">L.1 : Laboratorium Komputer 1, L.IPA, OR, HALL</code>).</li>
                        <li><strong class="text-emerald-400">Auto-Provisioning:</strong> Ruangan baru dan kode ruangan unik <span class="text-white font-bold">dibuat otomatis</span> ke sistem tanpa duplikasi data.</li>
                        <li><strong class="text-amber-400">Excel / CSV:</strong> Format kolom: <code class="text-indigo-300 font-mono">nama_ruang</code> (contoh: Lab Komputer 1, Ruang Teori 101, Bengkel Otomotif).</li>
                    </ul>
                </div>

                <form method="POST" action="{{ route('waka.rooms.import') }}" enctype="multipart/form-data" 
                      x-data="{ 
                          selectedFiles: [],
                          handleFiles(e) {
                              const newFiles = Array.from(e.target.files);
                              this.selectedFiles = [...this.selectedFiles, ...newFiles];
                              this.syncFiles();
                          },
                          removeFile(index) {
                              this.selectedFiles.splice(index, 1);
                              this.syncFiles();
                          },
                          syncFiles() {
                              const dt = new DataTransfer();
                              this.selectedFiles.forEach(file => dt.items.add(file));
                              this.$refs.fileInput.files = dt.files;
                          }
                      }" 
                      class="space-y-4 text-xs">
                    @csrf
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block font-bold text-slate-300">Pilih Berkas Dokumen (Bisa Pilih Beberapa File Sekaligus)</label>
                            <span class="text-[10px] text-emerald-400 font-semibold">Multiple Files (.pdf, .xlsx, .csv)</span>
                        </div>
                        <input type="file" name="files[]" x-ref="fileInput" multiple accept=".pdf,.xlsx,.xls,.csv,.txt" required 
                               @change="handleFiles($event)"
                               class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-slate-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-indigo-600 file:text-white hover:file:bg-indigo-500 cursor-pointer" />

                        <!-- List Preview Berkas Terpilih dengan Tombol Silang / Hapus -->
                        <div x-show="selectedFiles.length > 0" class="mt-2.5 p-3 rounded-xl bg-indigo-950/40 border border-indigo-800/40 text-[11px] text-indigo-300 space-y-2">
                            <div class="flex items-center justify-between text-white font-bold">
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    <span x-text="selectedFiles.length + ' berkas ruangan dipilih untuk diimpor serentak:'"></span>
                                </span>
                                <button type="button" @click="selectedFiles = []; syncFiles()" class="text-[10px] text-rose-400 hover:text-rose-300 underline cursor-pointer">
                                    Hapus Semua
                                </button>
                            </div>
                            <div class="max-h-32 overflow-y-auto space-y-1 pr-1">
                                <template x-for="(file, index) in selectedFiles" :key="file.name + index">
                                    <div class="flex items-center justify-between gap-2 p-1.5 px-2.5 rounded-lg bg-slate-900/80 border border-slate-800 text-slate-200">
                                        <div class="flex items-center gap-1.5 truncate">
                                            <svg class="w-3.5 h-3.5 text-indigo-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                            <span x-text="file.name" class="font-mono text-[10.5px] truncate"></span>
                                            <span x-text="'(' + (file.size / 1024).toFixed(1) + ' KB)'" class="text-[9px] text-slate-400 shrink-0"></span>
                                        </div>
                                        <button type="button" 
                                                @click="removeFile(index)" 
                                                title="Batalkan / Hapus berkas ini dari daftar" 
                                                class="p-1 rounded-md text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 transition cursor-pointer shrink-0">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Opsi Penanganan Data Duplikat / Ruangan yang Sudah Ada (Sesuai Foto ke-2) -->
                    <div class="p-3.5 rounded-xl bg-slate-900 border border-slate-800 space-y-2">
                        <label class="block text-[11px] font-bold text-slate-300">
                            Jika Ditemukan Data Ruangan yang Sudah Ada (Dobel):
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                            <label class="flex items-start gap-2.5 p-2.5 rounded-lg border border-slate-800 bg-slate-950/60 hover:border-indigo-500/50 cursor-pointer transition">
                                <input type="radio" name="duplicate_action" value="replace" checked class="mt-0.5 text-indigo-600 focus:ring-indigo-500 bg-slate-900 border-slate-700" />
                                <div>
                                    <span class="font-bold text-white block text-[11px]">Ganti / Perbarui yang Baru</span>
                                    <span class="text-[10px] text-slate-400 leading-tight block mt-0.5">Pertahankan konsistensi nama ruangan terbaru</span>
                                </div>
                            </label>
                            <label class="flex items-start gap-2.5 p-2.5 rounded-lg border border-slate-800 bg-slate-950/60 hover:border-indigo-500/50 cursor-pointer transition">
                                <input type="radio" name="duplicate_action" value="keep" class="mt-0.5 text-indigo-600 focus:ring-indigo-500 bg-slate-900 border-slate-700" />
                                <div>
                                    <span class="font-bold text-white block text-[11px]">Pertahankan Data Awal</span>
                                    <span class="text-[10px] text-slate-400 leading-tight block mt-0.5">Lewati jika nama ruangan sudah terdaftar di sistem</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
                        <button @click="importModalOpen = false" type="button" class="px-4 py-2 rounded-xl text-slate-400 hover:bg-slate-800 hover:text-white transition cursor-pointer">Batal</button>
                        <button type="submit" 
                                :disabled="selectedFiles.length === 0"
                                :class="selectedFiles.length === 0 ? 'opacity-50 cursor-not-allowed bg-slate-700' : 'bg-emerald-600 hover:bg-emerald-500 cursor-pointer'"
                                class="px-5 py-2.5 rounded-xl font-bold text-white shadow-md shadow-emerald-950/40 transition flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                            Mulai Import Ruangan
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>
