<x-app-layout>
        {{-- =========================================================================
             FITUR UKK: MASTER TINGKAT KELAS & KELULUSAN
             Fokus pada manajemen evaluasi kenaikan jenjang (Naik / Tinggal Kelas)
             dan penetapan kelulusan siswa menjadi alumni.
             Aksi CRUD data siswa (Detail/Edit/Hapus) dipusatkan di Data Siswa.
             ========================================================================= --}}
        <div x-data="{ 
            selectedIds: [{{ $students->pluck('id')->implode(',') }}],
            toggleAll(e) {
                if (e.target.checked) {
                    this.selectedIds = [{{ $students->pluck('id')->implode(',') }}];
                } else {
                    this.selectedIds = [];
                }
            }
         }" 
         class="space-y-6">
        
        <!-- Header -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 w-full print:hidden">
            <div>
                <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">Master Tingkat Kelas & Kelulusan</h1>
                <p class="text-xs font-semibold text-slate-400 mt-0.5">Kelola kenaikan jenjang kelas 10, 11, 12 serta penetapan kelulusan siswa menjadi alumni</p>
            </div>

            <!-- Tombol Kenaikan / Kelulusan Berdasarkan Checklist / Cetak Dokumen Lulus -->
            <div>
                @if($activeTab === 'X')
                    <button type="submit" form="promoteChecklistForm" 
                            onclick="return confirm('Proses siswa yang dicentang naik ke Kelas 11, dan siswa yang TIDAK dicentang tinggal di Kelas 10?')" 
                            class="px-4 py-2.5 rounded-xl scp-btn-gradient text-xs font-extrabold text-white shadow-lg flex items-center gap-2 cursor-pointer transition">
                        <span>Proses Kenaikan Kelas 11 (Siswa Tercentang)</span>
                    </button>
                @elseif($activeTab === 'XI')
                    <button type="submit" form="promoteChecklistForm" 
                            onclick="return confirm('Proses siswa yang dicentang naik ke Kelas 12, dan siswa yang TIDAK dicentang tinggal di Kelas 11?')" 
                            class="px-4 py-2.5 rounded-xl scp-btn-gradient text-xs font-extrabold text-white shadow-lg flex items-center gap-2 cursor-pointer transition">
                        <span>Proses Kenaikan Kelas 12 (Siswa Tercentang)</span>
                    </button>
                @elseif($activeTab === 'XII')
                    <button type="submit" form="promoteChecklistForm" 
                            onclick="return confirm('Proses siswa yang dicentang lulus menjadi Alumni, dan siswa yang TIDAK dicentang tinggal di Kelas 12? (Akun siswa yang lulus akan otomatis dinonaktifkan)')" 
                            class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-xs font-extrabold text-white shadow-lg flex items-center gap-2 cursor-pointer transition">
                        <span>Proses Kelulusan Siswa (Siswa Tercentang)</span>
                    </button>
                @elseif($activeTab === 'lulus')
                    <!-- Tombol Cetak Dokumen Siswa Lulus (A4) -->
                    <button type="button" 
                            onclick="window.print()" 
                            class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-xs font-extrabold text-white shadow-lg flex items-center gap-2 cursor-pointer transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        <span>Cetak Dokumen Siswa Lulus (A4)</span>
                    </button>
                @endif
            </div>
        </div>

        <!-- HEADER KOP LAPORAN KELULUSAN (HANYA MUNCUL DI PRINT A4) -->
        <div class="hidden print:block mb-5 border-b-2 border-black pb-3 text-center">
            <h1 class="text-base font-black tracking-wider text-black uppercase">SMK NEGERI 1 BANTUL</h1>
            <h2 class="text-sm font-bold uppercase tracking-wider text-black mt-0.5">DAFTAR SISWA KELULUSAN & ALUMNI</h2>
            <p class="text-[11px] text-gray-700 mt-1">
                Tahun Ajaran: <strong>{{ \App\Models\SystemConfiguration::current()->academic_year ?? '2026/2027' }}</strong> &nbsp;•&nbsp; 
                Dicetak pada: {{ now()->translatedFormat('d F Y, H:i') }} WIB &nbsp;•&nbsp; 
                Oleh: {{ auth()->user()->name }} (Waka Kurikulum)
            </p>
        </div>

        <!-- 4 Kartu Tab Tingkat (Bersih Tanpa Tulisan Fase) -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 print:hidden">
            <!-- Tab Kelas 10 -->
            <a href="{{ route('waka.grades.index', ['tab' => 'X']) }}" 
               class="p-4 rounded-2xl border transition {{ $activeTab === 'X' ? 'bg-indigo-950/70 border-indigo-500 shadow-lg shadow-indigo-600/20' : 'bg-[#0f172a]/90 border-slate-800 hover:bg-slate-800/50' }}">
                <span class="text-xs font-extrabold text-slate-300 block">Kelas 10</span>
                <div class="flex items-baseline justify-between mt-1">
                    <span class="text-xl sm:text-2xl font-black text-white">{{ $countX }}</span>
                    <span class="text-[11px] font-bold text-indigo-400">Siswa Aktif</span>
                </div>
            </a>

            <!-- Tab Kelas 11 -->
            <a href="{{ route('waka.grades.index', ['tab' => 'XI']) }}" 
               class="p-4 rounded-2xl border transition {{ $activeTab === 'XI' ? 'bg-indigo-950/70 border-indigo-500 shadow-lg shadow-indigo-600/20' : 'bg-[#0f172a]/90 border-slate-800 hover:bg-slate-800/50' }}">
                <span class="text-xs font-extrabold text-slate-300 block">Kelas 11</span>
                <div class="flex items-baseline justify-between mt-1">
                    <span class="text-xl sm:text-2xl font-black text-white">{{ $countXI }}</span>
                    <span class="text-[11px] font-bold text-indigo-400">Siswa Aktif</span>
                </div>
            </a>

            <!-- Tab Kelas 12 -->
            <a href="{{ route('waka.grades.index', ['tab' => 'XII']) }}" 
               class="p-4 rounded-2xl border transition {{ $activeTab === 'XII' ? 'bg-indigo-950/70 border-indigo-500 shadow-lg shadow-indigo-600/20' : 'bg-[#0f172a]/90 border-slate-800 hover:bg-slate-800/50' }}">
                <span class="text-xs font-extrabold text-slate-300 block">Kelas 12</span>
                <div class="flex items-baseline justify-between mt-1">
                    <span class="text-xl sm:text-2xl font-black text-white">{{ $countXII }}</span>
                    <span class="text-[11px] font-bold text-blue-400">Calon Lulus</span>
                </div>
            </a>

            <!-- Tab Siswa Lulus -->
            <a href="{{ route('waka.grades.index', ['tab' => 'lulus']) }}" 
               class="p-4 rounded-2xl border transition {{ $activeTab === 'lulus' ? 'bg-emerald-950/70 border-emerald-500 shadow-lg shadow-emerald-600/20' : 'bg-[#0f172a]/90 border-slate-800 hover:bg-slate-800/50' }}">
                <span class="text-xs font-extrabold text-slate-300 block">Siswa yang Lulus</span>
                <div class="flex items-baseline justify-between mt-1">
                    <span class="text-xl sm:text-2xl font-black text-emerald-400">{{ $countLulus }}</span>
                    <span class="text-[11px] font-bold text-emerald-400">Alumni</span>
                </div>
            </a>
        </div>

        <!-- Filter Bar Dijabarkan: Tingkat, Jurusan, Rombel, Pencarian -->
        <div class="p-4 rounded-2xl bg-[#0f172a]/90 border border-slate-800 shadow-xl print:hidden">
            <form method="GET" action="{{ route('waka.grades.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 text-xs">
                @if(request('per_page'))
                    <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                @endif
                <!-- 1. Filter Tingkat -->
                <div>
                    <label class="block font-bold text-slate-400 mb-1">Tingkat Kelas</label>
                    <select name="tab" onchange="this.form.submit()" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white focus:border-indigo-500">
                        <option value="X" {{ $activeTab === 'X' ? 'selected' : '' }}>Kelas 10 (X)</option>
                        <option value="XI" {{ $activeTab === 'XI' ? 'selected' : '' }}>Kelas 11 (XI)</option>
                        <option value="XII" {{ $activeTab === 'XII' ? 'selected' : '' }}>Kelas 12 (XII)</option>
                        <option value="lulus" {{ $activeTab === 'lulus' ? 'selected' : '' }}>Siswa yang Lulus (Alumni)</option>
                    </select>
                </div>

                <!-- 2. Filter Jurusan -->
                <div>
                    <label class="block font-bold text-slate-400 mb-1">Jurusan</label>
                    <select name="major" onchange="this.form.submit()" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white focus:border-indigo-500">
                        <option value="">Semua Jurusan</option>
                        @foreach($majors as $m)
                            <option value="{{ $m->code }}" {{ request('major') === $m->code ? 'selected' : '' }}>{{ $m->code }} - {{ $m->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- 3. Filter Rombel -->
                <div>
                    <label class="block font-bold text-slate-400 mb-1">Nomor Rombel</label>
                    <select name="rombel" onchange="this.form.submit()" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white focus:border-indigo-500">
                        <option value="">Semua Rombel</option>
                        <option value="1" {{ request('rombel') === '1' ? 'selected' : '' }}>Rombel 1</option>
                        <option value="2" {{ request('rombel') === '2' ? 'selected' : '' }}>Rombel 2</option>
                        <option value="3" {{ request('rombel') === '3' ? 'selected' : '' }}>Rombel 3</option>
                        <option value="4" {{ request('rombel') === '4' ? 'selected' : '' }}>Rombel 4</option>
                        <option value="5" {{ request('rombel') === '5' ? 'selected' : '' }}>Rombel 5</option>
                    </select>
                </div>

                <!-- 4. Cari Nama / NIS -->
                <div>
                    <label class="block font-bold text-slate-400 mb-1">Cari Siswa</label>
                    <div class="flex gap-2">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Ketik nama, NIS, atau kelas..." class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white placeholder-slate-500 focus:border-indigo-500" />
                        <button type="submit" class="px-4 py-2 rounded-xl scp-btn-gradient text-white font-bold transition shadow-sm cursor-pointer">Cari</button>
                        @if(request()->hasAny(['major', 'rombel', 'search']))
                            <a href="{{ route('waka.grades.index', ['tab' => $activeTab]) }}" class="px-2.5 py-2 rounded-xl bg-red-500/10 text-red-400 border border-red-500/20 hover:bg-red-500/20 transition flex items-center justify-center font-bold" title="Reset Filter">✕</a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        <!-- Form dan Tabel Siswa Checklist -->
        <form id="promoteChecklistForm" method="POST" action="{{ route('waka.grades.promote-selected') }}">
            @csrf
            <input type="hidden" name="from_grade" value="{{ $activeTab }}" />

            <div class="bg-[#0f172a]/90 backdrop-blur-md border border-slate-800 shadow-xl overflow-hidden rounded-2xl">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-800/80 text-sm">
                        <thead class="bg-slate-900/70">
                            <tr class="text-left text-xs font-bold text-slate-400 uppercase tracking-wider">
                                @if($activeTab !== 'lulus')
                                    <th class="p-4 w-12 text-center">
                                        <input type="checkbox" @change="toggleAll($event)" checked
                                            class="w-4 h-4 rounded border-2 border-slate-500 dark:border-slate-700 bg-white dark:bg-slate-900 text-indigo-600 focus:ring-indigo-500 cursor-pointer accent-indigo-600"
                                            title="Centang / Hilangkan Semua">
                                    </th>
                                @endif
                                <th class="p-4">NIS</th>
                                <th class="p-4">Nama Siswa</th>
                                <th class="p-4">Kelas</th>
                                <th class="p-4">Status Rencana</th>
                                {{-- Kolom Aksi (Detail, Edit, Hapus) dihapus karena pengelolaan CRUD siswa dipusatkan di menu Data Siswa --}}
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            @forelse ($students as $s)
                                <tr class="hover:bg-slate-800/30 transition">
                                    @if($activeTab !== 'lulus')
                                        <td class="p-4 text-center print:hidden">
                                            <!-- Hidden input untuk tracking semua siswa yang dievaluasi -->
                                            <input type="hidden" name="evaluated_students[]" value="{{ $s->id }}" />
                                            <!-- Checkbox keputusan -->
                                            <input type="checkbox" name="selected_students[]" value="{{ $s->id }}"
                                                x-model.number="selectedIds"
                                                class="w-4 h-4 rounded border-2 border-slate-500 dark:border-slate-700 bg-white dark:bg-slate-900 text-indigo-600 focus:ring-indigo-500 cursor-pointer accent-indigo-600">
                                        </td>
                                    @endif
                                    <td class="p-4">
                                        <span class="font-mono text-xs text-indigo-300 font-bold">{{ $s->nis ?? '-' }}</span>
                                    </td>
                                    <td class="p-4">
                                        <div class="font-bold text-white text-base">{{ $s->name }}</div>
                                        <div class="text-xs text-slate-400">{{ $s->email }} • {{ $s->phone ?? 'Tanpa telepon' }}</div>
                                    </td>
                                    <td class="p-4">
                                        <span class="inline-block px-2.5 py-1 rounded-md text-xs font-bold bg-slate-800 text-slate-200 border border-slate-700">
                                            {{ $s->schoolClass?->class_name ?? 'Alumni / Tanpa Kelas' }}
                                        </span>
                                    </td>
                                    <td class="p-4">
                                        @if($activeTab === 'lulus')
                                            <div class="flex flex-col gap-1 items-start">
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                                                    ✓ Lulus (Alumni)
                                                </span>
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                                    Akun SCP Dinonaktifkan
                                                </span>
                                            </div>
                                        @else
                                            <!-- Indikator Dinamis Real-Time sesuai Checklist -->
                                            <template x-if="selectedIds.includes({{ $s->id }})">
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                                                    ✓ {{ $activeTab === 'XII' ? 'Akan Lulus' : ($activeTab === 'X' ? 'Naik Kelas 11' : 'Naik Kelas 12') }}
                                                </span>
                                            </template>
                                            <template x-if="!selectedIds.includes({{ $s->id }})">
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-500/10 text-amber-400 border border-amber-500/30">
                                                    ✕ Tinggal Kelas {{ $activeTab }}
                                                </span>
                                            </template>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $activeTab !== 'lulus' ? '5' : '4' }}" class="p-8 text-center text-slate-500 text-sm">
                                        Tidak ada data siswa pada filter ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="print:hidden">
                    <x-table-pagination :paginator="$students" />
                </div>
            </div>
        </form>

        <!-- TANDA TANGAN PENGESAHAN KELULUSAN (HANYA MUNCUL DI PRINT A4) -->
        <div class="hidden print:flex justify-between items-end mt-12 pt-4 text-xs text-black">
            <div class="text-center">
                <p>Mengetahui,</p>
                <p class="font-bold">Kepala Sekolah</p>
                <div class="h-20"></div>
                <p class="font-bold underline">Tri Rahayu, M.Pd.</p>
                <p>NIP. 19700101 199512 2 001</p>
            </div>
            <div class="text-center">
                <p>Bantul, {{ now()->translatedFormat('d F Y') }}</p>
                <p class="font-bold">Waka Kurikulum</p>
                <div class="h-20"></div>
                <p class="font-bold underline">{{ auth()->user()->name }}</p>
                <p>NIP. {{ auth()->user()->nip ?? '-' }}</p>
            </div>
        </div>

    </div>
</x-app-layout>
