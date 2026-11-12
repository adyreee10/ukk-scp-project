<x-app-layout>
    <div x-data="{ 
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
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">Monitoring & Riwayat Jurnal Guru</h1>
            <p class="text-xs font-semibold text-slate-400 mt-0.5">
                Pantau kepatuhan pengisian jurnal KBM harian serta telusuri riwayat jurnal minggu dan bulan sebelumnya
            </p>
        </div>

        <!-- RIWAYAT & MONITORING JURNAL KBM LENGKAP -->
        <div class="bg-[#0f172a]/90 backdrop-blur-md p-6 border border-slate-800 shadow-xl space-y-5" style="border-radius: 15px;">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-base font-extrabold text-white">Arsip & Riwayat Jurnal KBM</h2>
                    <p class="text-xs text-slate-400">Monitoring seluruh jadwal pelajaran dan status pengisian jurnal harian guru</p>
                </div>
                <div class="flex flex-wrap items-center gap-2.5 self-start sm:self-auto">
                    <div class="text-xs font-bold px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-700 text-slate-300 inline-flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span>
                        <span>Hari Terpilih: <strong class="text-white">{{ $dayOfWeek }}</strong>, {{ \Carbon\Carbon::parse($date)->translatedFormat('d F Y') }}</span>
                    </div>

                    {{-- =========================================================================
                         FITUR UKK: TOMBOL CETAK SEMUA JURNAL KBM TERISI (ROLE WAKA)
                         Mencetak seluruh lembar jurnal yang sudah diisi sesuai filter/tanggal aktif
                         ========================================================================= --}}
                    @if($totalFilled > 0)
                        <a href="{{ route('waka.journals.print-all', request()->query()) }}" 
                           target="_blank" 
                           class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl text-xs font-extrabold shadow-md transition cursor-pointer hover:opacity-90 active:scale-95"
                           style="background-color: #2563eb !important; color: #ffffff !important;"
                           title="Cetak Seluruh Dokumen Jurnal yang Sudah Diisi Pada Tanggal & Filter Ini">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                            </svg>
                            <span style="color: #ffffff !important;">Cetak Semua Jurnal ({{ $totalFilled }})</span>
                        </a>
                    @else
                        <button type="button" 
                                disabled 
                                class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl text-xs font-bold bg-slate-800/80 text-slate-500 cursor-not-allowed border border-slate-700/60"
                                title="Belum ada jurnal yang berstatus 'Sudah Diisi' pada filter ini">
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                            </svg>
                            <span>Cetak Semua Jurnal (0)</span>
                        </button>
                    @endif
                </div>
            </div>

            <!-- Filter Bar Lengkap (Tanggal, Status Jurnal, Tingkat, Jurusan, Rombel, Guru) -->
            <div class="bg-slate-900/60 p-4 rounded-xl border border-slate-800/80">
                <form method="GET" action="{{ route('waka.journals.index') }}" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-7 gap-2.5 sm:gap-3 items-end">
                    @if(request('per_page'))
                        <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                    @endif

                    <!-- 1. Tanggal Pelaksanaan -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 mb-1">Tanggal</label>
                        <input type="date" name="date" value="{{ $date }}" onchange="this.form.submit()" 
                               class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-xs font-bold text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 cursor-pointer">
                    </div>

                    <!-- 2. Status Jurnal (Sudah / Belum) -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 mb-1">Status Jurnal</label>
                        <select name="status" onchange="this.form.submit()" 
                                class="w-full rounded-xl border border-slate-700 bg-slate-900 pl-3 pr-8 py-2 text-xs font-bold text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 cursor-pointer truncate">
                            <option value="all" {{ ($statusFilter ?? 'all') === 'all' ? 'selected' : '' }}>Semua</option>
                            <option value="sudah" {{ ($statusFilter ?? '') === 'sudah' ? 'selected' : '' }}>Sudah Diisi</option>
                            <option value="belum" {{ ($statusFilter ?? '') === 'belum' ? 'selected' : '' }}>Belum Diisi</option>
                        </select>
                    </div>

                    <!-- 3. Tingkat Kelas (X, XI, XII) -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 mb-1">Tingkat</label>
                        <select name="grade" onchange="this.form.submit()" 
                                class="w-full rounded-xl border border-slate-700 bg-slate-900 pl-3 pr-8 py-2 text-xs font-bold text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 cursor-pointer truncate">
                            <option value="all" {{ ($grade ?? 'all') === 'all' ? 'selected' : '' }}>Semua</option>
                            @foreach($availableGrades as $g)
                                <option value="{{ $g }}" {{ ($grade ?? '') === $g ? 'selected' : '' }}>Kelas {{ $g }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 4. Jurusan -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 mb-1">Jurusan</label>
                        <select name="major" onchange="this.form.submit()" 
                                class="w-full rounded-xl border border-slate-700 bg-slate-900 pl-3 pr-8 py-2 text-xs font-bold text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 cursor-pointer truncate">
                            <option value="all" {{ ($major ?? 'all') === 'all' ? 'selected' : '' }}>Semua</option>
                            @foreach($availableMajors as $m)
                                <option value="{{ $m }}" {{ ($major ?? '') === $m ? 'selected' : '' }}>{{ $m }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 5. Rombel -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 mb-1">Rombel</label>
                        <select name="rombel" onchange="this.form.submit()" 
                                class="w-full rounded-xl border border-slate-700 bg-slate-900 pl-3 pr-8 py-2 text-xs font-bold text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 cursor-pointer truncate">
                            <option value="all" {{ ($rombel ?? 'all') === 'all' ? 'selected' : '' }}>Semua</option>
                            @foreach($availableRombels as $r)
                                <option value="{{ $r }}" {{ ($rombel ?? '') === (string)$r ? 'selected' : '' }}>Rombel {{ $r }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 6. Guru Pengampu -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 mb-1">Guru</label>
                        <select name="teacher_id" onchange="this.form.submit()" 
                                class="w-full rounded-xl border border-slate-700 bg-slate-900 pl-3 pr-8 py-2 text-xs text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 cursor-pointer truncate">
                            <option value="">Semua Guru</option>
                            @foreach($teachers as $t)
                                <option value="{{ $t->id }}" {{ ($teacherId ?? '') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 7. Tombol Aksi (Terapkan & Reset) -->
                    <div class="flex items-center gap-1.5">
                        <button type="submit" class="flex-1 rounded-xl scp-btn-gradient px-4 py-2 text-xs font-bold text-white transition shadow-sm cursor-pointer text-center">
                            Filter
                        </button>
                        @if(request()->hasAny(['teacher_id', 'status', 'grade', 'major', 'rombel', 'class_id']) || (request('date') && request('date') !== now()->toDateString()))
                            <a href="{{ route('waka.journals.index') }}" class="px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-bold transition flex items-center justify-center shrink-0" title="Reset Semua Filter">
                                ✕
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <!-- SUMMARY KPI CARDS HARI INI / TANGGAL TERPILIH -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                <!-- Card 1: Total Jadwal -->
                <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-4 flex items-center justify-between shadow-lg">
                    <div>
                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Jadwal ({{ $dayOfWeek }})</p>
                        <h3 class="text-2xl sm:text-3xl font-black text-white mt-0.5">{{ $totalScheduled }}</h3>
                        <p class="text-[11px] text-slate-400 mt-0.5">Jadwal KBM terjadwal</p>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400 shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                </div>

                <!-- Card 2: Sudah Diisi -->
                <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-4 flex items-center justify-between shadow-lg">
                    <div>
                        <p class="text-[11px] font-bold text-emerald-400 uppercase tracking-wider">Sudah Diisi</p>
                        <h3 class="text-2xl sm:text-3xl font-black text-emerald-400 mt-0.5">{{ $totalFilled }}</h3>
                        <p class="text-[11px] text-slate-400 mt-0.5">
                            {{ $totalScheduled > 0 ? round(($totalFilled / $totalScheduled) * 100) : 0 }}% tingkat kepatuhan
                        </p>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>

                <!-- Card 3: Belum Diisi -->
                <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-4 flex items-center justify-between shadow-lg">
                    <div>
                        <p class="text-[11px] font-bold text-rose-400 uppercase tracking-wider">Belum Diisi</p>
                        <h3 class="text-2xl sm:text-3xl font-black text-rose-400 mt-0.5">{{ $totalUnfilled }}</h3>
                        <p class="text-[11px] text-slate-400 mt-0.5">
                            {{ $totalScheduled > 0 ? round(($totalUnfilled / $totalScheduled) * 100) : 0 }}% menunggu pengisian
                        </p>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-rose-500/10 border border-rose-500/20 flex items-center justify-center text-rose-400 shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
            </div>

            <!-- TAMPILAN DATA TABEL MONITORING -->
            @if($isWeekend)
                <div class="text-center py-12 bg-slate-900/40 rounded-xl border border-slate-800">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-amber-500/10 text-amber-400 mb-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <p class="text-sm font-bold text-white">Hari Libur ({{ $dayOfWeek }})</p>
                    <p class="text-xs text-slate-400 mt-1">Tidak ada jadwal KBM terjadwal pada hari {{ $dayOfWeek }}. Silakan pilih tanggal pada hari kerja (Senin - Jumat).</p>
                </div>
            @elseif($journals->isEmpty())
                <div class="text-center py-12 bg-slate-900/40 rounded-xl border border-slate-800">
                    <p class="text-sm font-bold text-white">Tidak Ada Data Jadwal / Jurnal</p>
                    <p class="text-xs text-slate-400 mt-1">Tidak ditemukan jadwal pelajaran yang sesuai dengan kriteria filter yang dipilih.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-800 text-sm">
                        <thead class="bg-slate-900/60 text-xs font-bold text-slate-400 uppercase tracking-wider">
                            <tr>
                                <th class="p-3.5 text-left whitespace-nowrap" style="white-space: nowrap !important;">Jam & Hari</th>
                                <th class="p-3.5 text-left whitespace-nowrap" style="white-space: nowrap !important;">Guru Pengampu</th>
                                <th class="p-3.5 text-left whitespace-nowrap" style="white-space: nowrap !important;">Kelas & Ruang</th>
                                <th class="p-3.5 text-center whitespace-nowrap" style="white-space: nowrap !important; width: 1%; min-width: 140px;">Status</th>
                                <th class="p-3.5 text-left">Materi KBM</th>
                                <th class="p-3.5 text-center whitespace-nowrap" style="white-space: nowrap !important;">Kehadiran Siswa</th>
                                <th class="p-3.5 text-center whitespace-nowrap" style="white-space: nowrap !important;">Foto Bukti</th>
                                <th class="p-3.5 text-center whitespace-nowrap" style="white-space: nowrap !important;">Cetak Jurnal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            @foreach ($journals as $j)
                                <tr class="hover:bg-slate-800/30 transition">
                                    <!-- 1. Jam & Hari -->
                                    <td class="p-3.5 whitespace-nowrap text-xs">
                                        <div class="font-bold text-white">{{ $j->period_label }}</div>
                                        <div class="text-[11px] font-mono text-slate-400">{{ \Carbon\Carbon::parse($j->date)->translatedFormat('d M Y') }}</div>
                                    </td>

                                    <!-- 2. Guru & Mapel -->
                                    <td class="p-3.5">
                                        <p class="font-bold text-white text-xs whitespace-nowrap" style="white-space: nowrap !important;">{{ $j->teacher?->name }}</p>
                                        <p class="text-[11px] text-slate-400">{{ $j->subject }}</p>
                                    </td>

                                    <!-- 3. Kelas & Ruang -->
                                    <td class="p-3.5 whitespace-nowrap text-xs" style="white-space: nowrap !important;">
                                        <p class="font-bold text-indigo-400">{{ $j->schoolClass?->class_name }}</p>
                                        <p class="text-[11px] text-slate-400">{{ $j->room?->room_name ?? 'Ruang KBM' }}</p>
                                    </td>

                                    <!-- 4. Status Jurnal (Sudah / Belum) -->
                                    <td class="p-3.5 text-center whitespace-nowrap" style="white-space: nowrap !important; width: 1%; min-width: 140px;">
                                        @if($j->is_filled)
                                            <span class="badge-filled-status" style="white-space: nowrap !important; display: inline-flex !important; align-items: center !important; justify-content: center !important; gap: 6px !important;">
                                                <span style="width: 6px; height: 6px; border-radius: 9999px; background-color: #10b981; flex-shrink: 0; display: inline-block;"></span>
                                                <span style="white-space: nowrap !important;">Sudah Diisi</span>
                                            </span>
                                        @else
                                            <span class="badge-late-status" style="white-space: nowrap !important; display: inline-flex !important; align-items: center !important; justify-content: center !important; gap: 6px !important;">
                                                <span style="width: 6px; height: 6px; border-radius: 9999px; background-color: #ef4444; flex-shrink: 0; display: inline-block;"></span>
                                                <span style="white-space: nowrap !important;">Belum Diisi</span>
                                            </span>
                                        @endif
                                    </td>

                                    <!-- 5. Materi KBM -->
                                    <td class="p-3.5 max-w-xs text-xs">
                                        @if($j->is_filled && !empty($j->topic_description))
                                            <p class="text-slate-300 line-clamp-2" title="{{ $j->topic_description }}">
                                                {{ $j->topic_description }}
                                            </p>
                                        @else
                                            <span class="text-slate-500 italic text-[11px]">- Belum Diisi -</span>
                                        @endif
                                    </td>

                                    <!-- 6. Kehadiran Siswa -->
                                    <td class="p-3.5 text-center whitespace-nowrap text-xs font-mono" style="white-space: nowrap !important;">
                                        @if($j->is_filled && $j->attendances->isNotEmpty())
                                            <span class="text-emerald-400 font-bold">H: {{ $j->attendances->where('status', 'Hadir')->count() }}</span> •
                                            <span class="text-amber-400 font-bold">S: {{ $j->attendances->where('status', 'Sakit')->count() }}</span> •
                                            <span class="text-blue-400 font-bold">I: {{ $j->attendances->where('status', 'Izin')->count() }}</span> •
                                            <span class="text-rose-400 font-bold">A: {{ $j->attendances->where('status', 'Alpa')->count() }}</span>
                                        @else
                                            <span class="text-slate-500">-</span>
                                        @endif
                                    </td>

                                    <!-- 7. Foto Bukti -->
                                    <td class="p-3.5 text-center whitespace-nowrap" style="white-space: nowrap !important;">
                                        @if($j->photo_path)
                                            <button type="button" 
                                                    @click="openPreviewDoc('{{ url('media/' . $j->photo_path) }}', 'Foto Dokumentasi KBM: {{ addslashes($j->subject ?? 'Jurnal') }} - {{ addslashes($j->teacher?->name ?? 'Guru') }} ({{ addslashes($j->schoolClass?->class_name ?? '') }})')"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold btn-photo shadow-sm transition cursor-pointer">
                                                Lihat Foto
                                            </button>
                                        @else
                                            <span class="text-slate-500 text-xs">-</span>
                                        @endif
                                    </td>

                                    {{-- =========================================================================
                                         FITUR UKK: TOMBOL CETAK DOKUMEN JURNAL KBM PER SESI (ROLE WAKA)
                                         Mencetak dokumen lembar kerja A4 untuk sesi jurnal yang dipilih
                                         ========================================================================= --}}
                                    <td class="p-3.5 text-center whitespace-nowrap">
                                        @if($j->is_filled && $j->id)
                                            <a href="{{ route('waka.journals.print', $j->id) }}" 
                                               target="_blank" 
                                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold shadow-sm transition cursor-pointer"
                                               style="background-color: #2563eb !important; color: #ffffff !important;"
                                               title="Cetak Dokumen Lembar Jurnal & Presensi KBM Ini (Format A4)">
                                                <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                                                </svg>
                                                <span style="color: #ffffff !important;">Cetak</span>
                                            </a>
                                        @else
                                            <span class="text-slate-500 text-xs italic">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <x-table-pagination :paginator="$journals" />
            @endif
        </div>
                <!-- MODAL PRATINJAU FOTO DOKUMENTASI KBM (LANGSUNG MUNCUL DI LAYAR) -->
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
                                <span>Buka Penuh</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                            </a>
                            <button type="button" @click="closePreviewDoc()" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 transition cursor-pointer">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                    </div>

                    <div class="mt-4 flex items-center justify-center bg-slate-950/70 rounded-xl p-2 min-h-[300px] border border-slate-800/80 overflow-hidden">
                        <img :src="previewDocUrl" alt="Foto Dokumentasi KBM" class="max-h-[500px] w-auto max-w-full rounded-lg object-contain shadow-md" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
