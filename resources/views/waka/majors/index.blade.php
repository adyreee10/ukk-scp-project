<x-app-layout>
    <div x-data="{ 
            addModalOpen: false, 
            editModalOpen: false, 
            viewModalOpen: false, 
            periodModalOpen: false,
            activeMajor: { id: '', code: '', name: '', rombel_x_xi: 2, rombel_xii: 2, description: '', total_classes: 0, total_students: 0, classes: [] }
         }" 
         class="space-y-6">
        
        <!-- Header & Action Bar -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 w-full">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">Master Data Jurusan</h1>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                        {{ $majors->count() }} Konsentrasi Keahlian
                    </span>
                </div>
                <p class="text-xs font-semibold text-slate-400 mt-0.5">
                    Kelola data program jurusan dan konfigurasi rombel yang fleksibel setiap tahun ajaran
                </p>
            </div>

            <!-- Tombol Aksi Kanan -->
            <div class="flex items-center gap-2 self-stretch sm:self-auto">
                <!-- Link Pintas ke Laman Tahun Ajaran -->
                <a href="{{ route('waka.academic-year.index') }}" class="px-3.5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-bold text-slate-200 border border-slate-700 transition flex items-center gap-2 cursor-pointer" title="Kelola Tahun Ajaran & Semester">
                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    <span>{{ $config->academic_year }} • {{ $config->semester }}</span>
                </a>

                <!-- Tombol Tambah Jurusan -->
                <button @click="addModalOpen = true" type="button" class="px-4 py-2.5 rounded-xl scp-btn-gradient text-xs font-bold text-white shadow-md cursor-pointer transition whitespace-nowrap">
                    + Tambah Jurusan
                </button>
            </div>
        </div>

        <!-- Filter Dropdown Tingkat Kelas (Fleksibel Setiap Tahun Ajaran) -->
        <div class="p-4 rounded-2xl bg-[#0f172a]/90 border border-slate-800 shadow-xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <form method="GET" action="{{ route('waka.majors.index') }}" class="flex items-center gap-3">
                <label class="text-xs font-bold text-slate-300">Pilih Tingkat Kelas:</label>
                <select name="grade" onchange="this.form.submit()" class="rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2 text-xs font-bold text-white focus:border-indigo-500">
                    <option value="10" {{ $selectedGrade == '10' ? 'selected' : '' }}>Kelas 10 (Tingkat X)</option>
                    <option value="11" {{ $selectedGrade == '11' ? 'selected' : '' }}>Kelas 11 (Tingkat XI)</option>
                    <option value="12" {{ $selectedGrade == '12' ? 'selected' : '' }}>Kelas 12 (Tingkat XII)</option>
                </select>
            </form>
            <div class="text-xs text-slate-400">
                Menampilkan data rombongan belajar aktif pada <strong class="text-white">Kelas {{ $selectedGrade }} (Tingkat {{ $romanGrade }})</strong>
            </div>
        </div>

        <!-- Tabel Data Jurusan -->
        <div class="bg-[#0f172a]/90 backdrop-blur-md border border-slate-800 shadow-xl overflow-hidden rounded-2xl">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-800/80 text-sm">
                    <thead class="bg-slate-900/70">
                        <tr class="text-left text-xs font-bold text-slate-400 uppercase tracking-wider">
                            <th class="p-4">Kode</th>
                            <th class="p-4">Nama Konsentrasi Keahlian / Jurusan</th>
                            <th class="p-4 text-center">Jumlah Rombel (Kls {{ $selectedGrade }})</th>
                            <th class="p-4">Rombel Aktif Terdaftar</th>
                            <th class="p-4 text-center">Siswa Kls {{ $selectedGrade }}</th>
                            <th class="p-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @forelse ($majors as $m)
                            <tr class="hover:bg-slate-800/30 transition">
                                <td class="p-4">
                                    <span class="inline-block px-3 py-1 rounded-lg text-xs font-extrabold bg-indigo-950/60 text-indigo-300 border border-indigo-800/60">
                                        {{ $m->code }}
                                    </span>
                                </td>
                                <td class="p-4">
                                    <div class="font-bold text-white text-base">{{ $m->name }}</div>
                                    <div class="text-xs text-slate-400 line-clamp-1 mt-0.5">{{ $m->description ?? 'Tidak ada deskripsi khusus' }}</div>
                                </td>
                                <td class="p-4 text-center">
                                    <span class="inline-flex items-center px-3 py-1 rounded-md text-xs font-extrabold bg-slate-800 text-emerald-400 border border-emerald-500/30">
                                        {{ $m->current_rombel }} Rombel
                                    </span>
                                </td>
                                <td class="p-4">
                                    <div class="flex flex-wrap gap-1">
                                        @forelse($m->classes as $c)
                                            <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-slate-800 text-slate-200 border border-slate-700">
                                                {{ $c->class_name }}
                                            </span>
                                        @empty
                                            <span class="text-xs text-slate-500 italic">Belum dibuat di Master Kelas</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="p-4 text-center">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-indigo-950/40 text-slate-200 border border-slate-700">
                                        {{ $m->total_students }} Siswa
                                    </span>
                                </td>
                                <td class="p-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <!-- Detail Button -->
                                        <button @click="activeMajor = {{ json_encode($m) }}; viewModalOpen = true" 
                                                type="button" 
                                                class="px-2.5 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer btn-action-view shadow-sm"
                                                title="Lihat Detail Jurusan">
                                            Detail
                                        </button>
                                        
                                        <!-- Edit Button -->
                                        <button @click="activeMajor = {{ json_encode($m) }}; editModalOpen = true" 
                                                type="button" 
                                                class="px-2.5 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer btn-action-edit shadow-sm"
                                                title="Edit Jurusan">
                                            Edit
                                        </button>

                                        <!-- Hapus Button -->
                                        <form method="POST" action="{{ route('waka.majors.destroy', $m->id) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus jurusan ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-2.5 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer btn-action-delete shadow-sm" title="Hapus Jurusan">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-8 text-center text-slate-500 text-sm">
                                    Belum ada data jurusan yang terdaftar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ================= MODAL DETAIL JURUSAN ================= -->
        <div x-show="viewModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
            <div @click="viewModalOpen = false" class="fixed inset-0 bg-black/70 backdrop-blur-sm"></div>
            <div class="relative w-full max-w-lg bg-[#0f172a] p-6 shadow-2xl border border-slate-800 space-y-4 rounded-2xl my-auto max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div>
                        <h3 class="text-base font-bold text-white flex items-center gap-2">
                            <span>Detail Jurusan:</span>
                            <span class="text-indigo-400" x-text="activeMajor.name"></span>
                        </h3>
                        <p class="text-xs text-slate-400">Kode Jurusan: <span class="font-bold text-slate-200" x-text="activeMajor.code"></span></p>
                    </div>
                    <button @click="viewModalOpen = false" class="text-xs font-bold text-slate-400 hover:text-white px-2 py-1">[Tutup]</button>
                </div>

                <div class="grid grid-cols-3 gap-2.5 text-xs">
                    <div class="p-3 bg-slate-900/80 rounded-xl border border-slate-800 text-center">
                        <span class="text-slate-400 block mb-1">Kelas 10 (X):</span>
                        <span class="font-bold text-emerald-400 text-sm" x-text="(activeMajor.rombel_x || activeMajor.rombel_x_xi || 2) + ' Rombel'"></span>
                    </div>
                    <div class="p-3 bg-slate-900/80 rounded-xl border border-slate-800 text-center">
                        <span class="text-slate-400 block mb-1">Kelas 11 (XI):</span>
                        <span class="font-bold text-amber-400 text-sm" x-text="(activeMajor.rombel_xi || activeMajor.rombel_x_xi || 2) + ' Rombel'"></span>
                    </div>
                    <div class="p-3 bg-slate-900/80 rounded-xl border border-slate-800 text-center">
                        <span class="text-slate-400 block mb-1">Kelas 12 (XII):</span>
                        <span class="font-bold text-blue-400 text-sm" x-text="(activeMajor.rombel_xii || 2) + ' Rombel'"></span>
                    </div>
                </div>

                <div class="text-xs space-y-1">
                    <span class="text-slate-400 font-bold block">Deskripsi:</span>
                    <p class="text-slate-300 bg-slate-900/50 p-3 rounded-xl border border-slate-800" x-text="activeMajor.description || 'Tidak ada deskripsi khusus.'"></p>
                </div>

                <div class="pt-2 flex justify-end">
                    <button @click="viewModalOpen = false" type="button" class="px-4 py-2 rounded-xl bg-slate-800 text-xs font-bold text-white hover:bg-slate-700 transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>

        <!-- ================= MODAL TAMBAH JURUSAN ================= -->
        <div x-show="addModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
            <div @click="addModalOpen = false" class="fixed inset-0 bg-black/70 backdrop-blur-sm"></div>
            <div class="relative w-full max-w-md bg-[#0f172a] p-6 shadow-2xl border border-slate-800 space-y-4 rounded-2xl my-auto max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-white">Tambah Jurusan Baru</h3>
                    <button @click="addModalOpen = false" class="text-xs font-bold text-slate-400 hover:text-white px-2 py-1">[Tutup]</button>
                </div>

                <form method="POST" action="{{ route('waka.majors.store') }}" class="space-y-3.5 text-xs">
                    @csrf
                    <div>
                        <label class="block font-bold text-slate-300 mb-1">Kode Jurusan</label>
                        <input type="text" name="code" required placeholder="Contoh: RPL, TKJ, DKV" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white uppercase placeholder-slate-500 focus:border-indigo-500" />
                    </div>

                    <div>
                        <label class="block font-bold text-slate-300 mb-1">Nama Jurusan Lengkap</label>
                        <input type="text" name="name" required placeholder="Contoh: Rekayasa Perangkat Lunak" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white placeholder-slate-500 focus:border-indigo-500" />
                    </div>

                    <div class="grid grid-cols-3 gap-2.5">
                        <div>
                            <label class="block font-bold text-slate-300 mb-1">Rombel Kelas 10</label>
                            <input type="number" name="rombel_x" value="2" min="1" max="10" required class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white focus:border-indigo-500 text-center font-bold" />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-300 mb-1">Rombel Kelas 11</label>
                            <input type="number" name="rombel_xi" value="2" min="1" max="10" required class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white focus:border-indigo-500 text-center font-bold" />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-300 mb-1">Rombel Kelas 12</label>
                            <input type="number" name="rombel_xii" value="2" min="1" max="10" required class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white focus:border-indigo-500 text-center font-bold" />
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-300 mb-1">Deskripsi Jurusan (Opsional)</label>
                        <textarea name="description" rows="2" placeholder="Keterangan singkat fokus keahlian..." class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white placeholder-slate-500 focus:border-indigo-500"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button @click="addModalOpen = false" type="button" class="px-4 py-2 rounded-xl text-slate-400 hover:bg-slate-800 hover:text-white transition cursor-pointer">Batal</button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl scp-btn-gradient font-bold text-white shadow-md cursor-pointer transition">Simpan Jurusan</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ================= MODAL EDIT JURUSAN ================= -->
        <div x-show="editModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
            <div @click="editModalOpen = false" class="fixed inset-0 bg-black/70 backdrop-blur-sm"></div>
            <div class="relative w-full max-w-md bg-[#0f172a] p-6 shadow-2xl border border-slate-800 space-y-4 rounded-2xl my-auto max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-white">Edit Data Jurusan</h3>
                    <button @click="editModalOpen = false" class="text-xs font-bold text-slate-400 hover:text-white px-2 py-1">[Tutup]</button>
                </div>

                <form :action="'{{ url('/waka/majors') }}/' + activeMajor.id" method="POST" class="space-y-3.5 text-xs">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block font-bold text-slate-300 mb-1">Kode Jurusan</label>
                        <input type="text" name="code" x-model="activeMajor.code" required class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white uppercase focus:border-indigo-500" />
                    </div>

                    <div>
                        <label class="block font-bold text-slate-300 mb-1">Nama Jurusan Lengkap</label>
                        <input type="text" name="name" x-model="activeMajor.name" required class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white focus:border-indigo-500" />
                    </div>

                    <div class="grid grid-cols-3 gap-2.5">
                        <div>
                            <label class="block font-bold text-slate-300 mb-1">Rombel Kelas 10</label>
                            <input type="number" name="rombel_x" x-model="activeMajor.rombel_x" min="1" max="10" required class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white focus:border-indigo-500 text-center font-bold" />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-300 mb-1">Rombel Kelas 11</label>
                            <input type="number" name="rombel_xi" x-model="activeMajor.rombel_xi" min="1" max="10" required class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white focus:border-indigo-500 text-center font-bold" />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-300 mb-1">Rombel Kelas 12</label>
                            <input type="number" name="rombel_xii" x-model="activeMajor.rombel_xii" min="1" max="10" required class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white focus:border-indigo-500 text-center font-bold" />
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-300 mb-1">Deskripsi Jurusan</label>
                        <textarea name="description" x-model="activeMajor.description" rows="2" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white focus:border-indigo-500"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button @click="editModalOpen = false" type="button" class="px-4 py-2 rounded-xl text-slate-400 hover:bg-slate-800 hover:text-white transition cursor-pointer">Batal</button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl scp-btn-gradient font-bold text-white shadow-md cursor-pointer transition">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ================= MODAL TAHUN AJARAN & SEMESTER ================= -->
        <div x-show="periodModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
            <div @click="periodModalOpen = false" class="fixed inset-0 bg-black/70 backdrop-blur-sm"></div>
            <div class="relative w-full max-w-sm bg-[#0f172a] p-6 shadow-2xl border border-slate-800 space-y-4 rounded-2xl my-auto max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-white">Atur Periode Realtime</h3>
                    <button @click="periodModalOpen = false" class="text-xs font-bold text-slate-400 hover:text-white px-2 py-1">[Tutup]</button>
                </div>

                                <form method="POST" action="{{ route('waka.academic-period.update') }}" class="space-y-3.5 text-xs">
                    @csrf
                    <div>
                        <label class="block font-bold text-slate-300 mb-1">Tahun Ajaran</label>
                        <select name="academic_year" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white focus:border-indigo-500">
                            <option value="2024/2025" {{ $config->academic_year === '2024/2025' ? 'selected' : '' }}>2024/2025 (Tahun Masuk Angkatan 2024)</option>
                            <option value="2025/2026" {{ $config->academic_year === '2025/2026' ? 'selected' : '' }}>2025/2026</option>
                            <option value="2026/2027" {{ $config->academic_year === '2026/2027' ? 'selected' : '' }}>2026/2027 (Tahun Ajaran Berjalan Saat Ini)</option>
                            <option value="2027/2028" {{ $config->academic_year === '2027/2028' ? 'selected' : '' }}>2027/2028 (Mendatang)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-300 mb-1">Semester Aktif</label>
                        <select name="semester" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white focus:border-indigo-500">
                            <option value="Semester 1 (Ganjil)" {{ $config->semester === 'Semester 1 (Ganjil)' ? 'selected' : '' }}>Semester 1 (Ganjil) — [Sedang Berjalan]</option>
                            <option value="Semester 2 (Genap)" {{ $config->semester === 'Semester 2 (Genap)' ? 'selected' : '' }}>Semester 2 (Genap)</option>
                        </select>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button @click="periodModalOpen = false" type="button" class="px-4 py-2 rounded-xl text-slate-400 hover:bg-slate-800 hover:text-white transition cursor-pointer">Batal</button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl scp-btn-gradient font-bold text-white shadow-md cursor-pointer transition">Simpan Periode</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>
