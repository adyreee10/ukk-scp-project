<x-app-layout>
    <div x-data="{ 
            addModalOpen: false, 
            editModalOpen: false, 
            viewModalOpen: false, 
            importModalOpen: false,
            deleteAllModalOpen: false,
            deleteSelectedModalOpen: false,
            selectedStudents: [],
            selectAll: false,
            classesList: {{ json_encode($structuredClasses) }},
            edit_grade: '',
            edit_major: '',
            edit_class_id: '',

            getMajors(grade) {
                if (!grade) return [];
                const filtered = this.classesList.filter(c => c.grade === grade);
                return [...new Set(filtered.map(c => c.major))].sort();
            },
            getRombels(grade, major) {
                if (!grade || !major) return [];
                return this.classesList.filter(c => c.grade === grade && c.major === major);
            },
            openEdit(studentData) {
                this.selectedStudent = { ...studentData };
                const foundCls = this.classesList.find(c => String(c.id) === String(studentData.class_id));
                if (foundCls) {
                    this.edit_grade = foundCls.grade;
                    this.edit_major = foundCls.major;
                    this.edit_class_id = foundCls.id;
                } else {
                    this.edit_grade = '';
                    this.edit_major = '';
                    this.edit_class_id = studentData.class_id || '';
                }
                this.editModalOpen = true;
            },
            toggleSelectAll(allIds) {
                if (this.selectAll) {
                    this.selectedStudents = [];
                    this.selectAll = false;
                } else {
                    this.selectedStudents = allIds.map(String);
                    this.selectAll = true;
                }
            },
            selectedStudent: { 
                id: '', 
                name: '', 
                username: '', 
                nis: '', 
                email: '', 
                phone: '', 
                address: '', 
                birth_date: '',
                class_id: '', 
                class_name: '',
                parent_id: '',
                parent_name: '',
                parent_phone: ''
            } 
         }" 
         class="space-y-6">
        
        <!-- Header & Action Bar -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 w-full">
            <div class="text-left">
                <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">Data Siswa</h1>
                <p class="text-xs font-semibold text-slate-400 mt-0.5">Kelola data peserta didik, penautan orang tua, pembagian kelas, dan akun login siswa</p>
            </div>
            <div class="flex items-center gap-2 shrink-0 flex-wrap">
                <!-- Tombol Hapus Semua Siswa -->
                <button @click="deleteAllModalOpen = true" type="button" class="px-3.5 sm:px-4 py-2 sm:py-2.5 rounded-xl bg-rose-600/20 hover:bg-rose-600 text-rose-300 hover:text-white border border-rose-500/40 text-xs sm:text-sm font-bold shadow-md cursor-pointer transition flex items-center gap-1.5 whitespace-nowrap">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    <span>Hapus Semua Siswa</span>
                </button>
                <button @click="importModalOpen = true" type="button" class="px-3.5 sm:px-4 py-2 sm:py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-xs sm:text-sm font-bold text-white shadow-md shadow-emerald-950/40 cursor-pointer transition flex items-center gap-1.5">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    <span>Import Excel</span>
                </button>
                <button @click="addModalOpen = true" type="button" class="px-4 sm:px-5 py-2 sm:py-2.5 rounded-xl scp-btn-gradient text-xs sm:text-sm font-bold text-white shadow-md cursor-pointer transition whitespace-nowrap">
                    + Tambah Siswa Baru
                </button>
            </div>
        </div>

        <!-- Filter & Pencarian -->
        <div class="bg-[#0f172a]/90 backdrop-blur-md p-4 border border-slate-800 shadow-xl" style="border-radius: 15px;">
            <form method="GET" action="{{ route('waka.students.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="sm:col-span-2">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, NIS, NISN, atau nomor HP siswa..." class="w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-2.5 text-xs sm:text-sm text-white placeholder-slate-400 focus:border-indigo-500 focus:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" />
                </div>
                <div class="flex gap-2">
                    <select name="class_id" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-xs font-bold text-white focus:border-indigo-500 focus:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        <option value="" class="bg-slate-900 text-white">Semua Kelas</option>
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}" {{ request('class_id') == $c->id ? 'selected' : '' }} class="bg-slate-900 text-white">{{ $c->class_name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="rounded-xl scp-btn-gradient px-6 py-2.5 text-xs font-bold text-white transition shadow-sm cursor-pointer">
                        Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- BAR AKSI SELEKSI SISWA TERPILIH (BULK ACTION BAR) -->
        <div x-show="selectedStudents.length > 0" 
             x-transition
             class="p-3.5 px-5 rounded-2xl bg-indigo-950/90 border border-indigo-500/50 shadow-2xl flex flex-col sm:flex-row items-center justify-between gap-3 text-white">
            <div class="flex items-center gap-2.5">
                <span class="flex h-3 w-3 rounded-full bg-rose-500 animate-pulse"></span>
                <span class="text-xs sm:text-sm font-bold">
                    <span x-text="selectedStudents.length" class="text-indigo-300 font-black text-base"></span> siswa dipilih dari halaman ini
                </span>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" @click="selectedStudents = []; selectAll = false" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-800 hover:bg-slate-700 text-slate-300 transition cursor-pointer">
                    Batal Pilih
                </button>
                <button type="button" @click="deleteSelectedModalOpen = true" class="px-4 py-2 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-500 text-white shadow-md shadow-rose-950/60 transition flex items-center gap-1.5 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    <span>Hapus (<span x-text="selectedStudents.length"></span>) Siswa Terpilih</span>
                </button>
            </div>
        </div>

        <!-- Tabel Data Siswa -->
        <div class="bg-[#0f172a]/90 backdrop-blur-md border border-slate-800 shadow-xl overflow-hidden" style="border-radius: 15px;">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-800 text-sm">
                    <thead class="bg-slate-900/70">
                        <tr class="text-left text-xs font-bold text-slate-400 uppercase tracking-wider">
                            <th class="p-4 text-center w-10">
                                <input type="checkbox" 
                                       @change="toggleSelectAll([{{ $students->pluck('id')->implode(',') }}])" 
                                       :checked="selectAll && selectedStudents.length > 0"
                                       class="rounded border-slate-700 bg-slate-900 text-indigo-600 focus:ring-indigo-500/30 cursor-pointer w-4 h-4"
                                       title="Pilih Semua Siswa di Halaman Ini">
                            </th>
                            <th class="p-4 text-center w-12">No</th>
                            <th class="p-4">Nama Siswa</th>
                            <th class="p-4">NIS</th>
                            <th class="p-4">Kelas</th>
                            <th class="p-4">Kontak / HP</th>
                            <th class="p-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @forelse ($students as $student)
                            <tr class="hover:bg-slate-800/30 transition">
                                <!-- Checkbox Seleksi -->
                                <td class="p-4 text-center">
                                    <input type="checkbox" 
                                           value="{{ $student->id }}" 
                                           x-model="selectedStudents"
                                           class="rounded border-slate-700 bg-slate-900 text-indigo-600 focus:ring-indigo-500/30 cursor-pointer w-4 h-4">
                                </td>

                                <!-- Nomor Urut -->
                                <td class="p-4 text-center text-xs font-bold text-slate-400">
                                    {{ $students->firstItem() + $loop->index }}
                                </td>

                                <!-- Nama & Email Siswa -->
                                <td class="p-4">
                                    <div>
                                        <p class="font-bold text-white">{{ $student->name }}</p>
                                        <p class="text-xs text-slate-400 font-mono">{{ $student->email }}</p>
                                    </div>
                                </td>

                                <!-- NIS -->
                                <td class="p-4 text-xs font-mono text-slate-300">
                                    {{ $student->nis ?? '-' }}
                                </td>

                                <!-- Kelas -->
                                <td class="p-4">
                                    <span class="inline-block rounded-md px-2.5 py-1 text-xs font-bold badge-class shadow-sm">
                                        {{ $student->schoolClass?->class_name ?? '-' }}
                                    </span>
                                </td>

                                <!-- Kontak Siswa -->
                                <td class="p-4 text-xs font-medium text-slate-300">
                                    @if($student->phone)
                                        <span class="text-emerald-400 font-mono">{{ $student->phone }}</span>
                                    @else
                                        <span class="text-slate-500">-</span>
                                    @endif
                                </td>

                                <!-- Aksi (Lihat, Edit, Hapus) -->
                                <td class="p-4 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        <!-- Tombol Lihat -->
                                        <button type="button" 
                                                @click="selectedStudent = {
                                                    id: '{{ $student->id }}',
                                                    name: '{{ addslashes($student->name) }}',
                                                    username: '{{ addslashes($student->username) }}',
                                                    nis: '{{ addslashes($student->nis ?? '-') }}',
                                                    email: '{{ addslashes($student->email) }}',
                                                    phone: '{{ addslashes($student->phone ?? '-') }}',
                                                    address: '{{ addslashes($student->address ?? '-') }}',
                                                    birth_date: '{{ $student->birth_date ? \Carbon\Carbon::parse($student->birth_date)->translatedFormat('d F Y') : '-' }}',
                                                    class_name: '{{ addslashes($student->schoolClass?->class_name ?? '-') }}',
                                                    parent_name: '{{ addslashes($student->parent?->name ?? 'Belum Ditautkan') }}',
                                                    parent_phone: '{{ addslashes($student->parent?->phone ?? '-') }}'
                                                }; viewModalOpen = true;"
                                                class="px-2.5 py-1 text-xs font-bold rounded-lg transition cursor-pointer btn-action-view shadow-sm"
                                                title="Lihat Detail Profil Siswa">
                                            Lihat
                                        </button>

                                        <!-- Tombol Edit -->
                                        <button type="button" 
                                                @click="openEdit({
                                                    id: '{{ $student->id }}',
                                                    name: '{{ addslashes($student->name) }}',
                                                    username: '{{ addslashes($student->username) }}',
                                                    nis: '{{ addslashes($student->nis ?? '') }}',
                                                    email: '{{ addslashes($student->email ?? '') }}',
                                                    phone: '{{ addslashes($student->phone ?? '') }}',
                                                    address: '{{ addslashes($student->address ?? '') }}',
                                                    birth_date: '{{ $student->birth_date ? \Carbon\Carbon::parse($student->birth_date)->format('Y-m-d') : '' }}',
                                                    class_id: '{{ $student->class_id }}'
                                                })"
                                                class="px-2.5 py-1 text-xs font-bold rounded-lg transition cursor-pointer btn-action-edit shadow-sm"
                                                title="Edit Data Siswa">
                                            Edit
                                        </button>

                                        <!-- Tombol Hapus -->
                                        <form method="POST" action="{{ route('waka.students.destroy', $student) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data siswa ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-2.5 py-1 text-xs font-bold rounded-lg transition cursor-pointer btn-action-delete shadow-sm" title="Hapus Siswa">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-8 text-center text-slate-400 text-sm">
                                    Tidak ada data siswa yang sesuai.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($students->hasPages())
                <div class="p-4 border-t border-slate-800">
                    {{ $students->links() }}
                </div>
            @endif
        </div>

        <!-- ============================================================= -->
        <!-- MODAL 1: LIHAT DETAIL PROFIL SISWA                            -->
        <!-- ============================================================= -->
        <div x-show="viewModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
            <div @click="viewModalOpen = false" class="fixed inset-0 bg-black/75 backdrop-blur-sm"></div>
            
            <div class="relative w-full max-w-lg bg-[#0f172a] p-6 sm:p-7 shadow-2xl border border-slate-800 space-y-5 my-auto max-h-[90vh] overflow-y-auto" style="border-radius: 20px;">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <span>Detail Profil Siswa</span>
                    </h3>
                    <button @click="viewModalOpen = false" class="text-xs font-bold text-slate-400 hover:text-white px-2 py-1">
                        [Tutup]
                    </button>
                </div>

                    <!-- Kartu Identitas Atas -->
                    <div class="p-4 rounded-xl bg-slate-900/80 border border-slate-800">
                        <h4 class="text-base font-extrabold text-white truncate" x-text="selectedStudent.name"></h4>
                        <p class="text-xs text-indigo-400 font-mono mt-0.5" x-text="'Username: ' + selectedStudent.username"></p>
                        <div class="flex items-center gap-2 mt-2">
                            <span class="inline-block px-2.5 py-0.5 rounded-md text-[10px] font-bold badge-class" x-text="'Kelas: ' + selectedStudent.class_name"></span>
                        </div>
                    </div>

                <!-- Rincian Data Lengkap -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                    <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800/80">
                        <span class="text-slate-400 font-bold block mb-1">NIS (Nomor Induk Siswa)</span>
                        <span class="text-white font-mono font-bold" x-text="selectedStudent.nis || '-'"></span>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800/80">
                        <span class="text-slate-400 font-bold block mb-1">Tanggal Lahir</span>
                        <span class="text-white font-bold" x-text="selectedStudent.birth_date || '-'"></span>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800/80">
                        <span class="text-slate-400 font-bold block mb-1">Nomor HP Siswa</span>
                        <span class="text-emerald-400 font-mono font-bold" x-text="selectedStudent.phone || '-'"></span>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800/80">
                        <span class="text-slate-400 font-bold block mb-1">Email Siswa</span>
                        <span class="text-white font-mono break-all" x-text="selectedStudent.email || '-'"></span>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800/80 sm:col-span-2">
                        <span class="text-slate-400 font-bold block mb-1">Alamat Tempat Tinggal</span>
                        <p class="text-slate-200 leading-relaxed" x-text="selectedStudent.address || 'Belum diisi'"></p>
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button @click="viewModalOpen = false" type="button" class="px-5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs transition cursor-pointer">
                        Tutup Tampilan
                    </button>
                </div>
            </div>
        </div>

        <!-- ============================================================= -->
        <!-- MODAL 2: EDIT DATA SISWA                                      -->
        <!-- ============================================================= -->
        <div x-show="editModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
            <div @click="editModalOpen = false" class="fixed inset-0 bg-black/75 backdrop-blur-sm"></div>
            
            <div class="relative w-full max-w-lg bg-[#0f172a] p-6 sm:p-8 shadow-2xl border border-slate-800 space-y-4 my-auto max-h-[90vh] overflow-y-auto" style="border-radius: 20px;">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-lg font-bold text-white">Edit Data Siswa</h3>
                    <button @click="editModalOpen = false" class="text-xs font-bold text-slate-400 hover:text-white px-2 py-1">
                        [Tutup]
                    </button>
                </div>

                <form method="POST" :action="'/waka/students/' + selectedStudent.id" class="space-y-3.5">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Nama Lengkap Siswa</label>
                        <input type="text" name="name" x-model="selectedStudent.name" required class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">NIS (Opsional)</label>
                            <input type="text" name="nis" x-model="selectedStudent.nis" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" placeholder="12345" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Username</label>
                            <input type="text" name="username" x-model="selectedStudent.username" required class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" />                       
                        </div>
                    </div>

                    <!-- Pemilihan Kelas Siswa: Dipecah Menjadi 3 Kolom (Tingkat, Jurusan, Rombel) -->
                    <div class="p-3.5 rounded-xl bg-slate-900/90 border border-slate-800 space-y-2">
                        <label class="block text-xs font-bold text-slate-300">Pilih Kelas Siswa:</label>
                        <div class="grid grid-cols-3 gap-2">
                            <!-- 1. Tingkat -->
                            <div>
                                <label class="block text-[10px] text-slate-400 font-semibold mb-1">1. Tingkat</label>
                                <select x-model="edit_grade" @change="edit_major = ''; edit_class_id = ''" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-2.5 py-1.5 text-xs text-white focus:border-indigo-500 focus:outline-none">
                                    <option value="">-- Tingkat --</option>
                                    <option value="X">Kelas X</option>
                                    <option value="XI">Kelas XI</option>
                                    <option value="XII">Kelas XII</option>
                                </select>
                            </div>
                            <!-- 2. Jurusan -->
                            <div>
                                <label class="block text-[10px] text-slate-400 font-semibold mb-1">2. Jurusan</label>
                                <select x-model="edit_major" @change="edit_class_id = ''" :disabled="!edit_grade" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-2.5 py-1.5 text-xs text-white focus:border-indigo-500 focus:outline-none disabled:opacity-50">
                                    <option value="">-- Jurusan --</option>
                                    <template x-for="m in getMajors(edit_grade)" :key="m">
                                        <option :value="m" x-text="m"></option>
                                    </template>
                                </select>
                            </div>
                            <!-- 3. Rombel -->
                            <div>
                                <label class="block text-[10px] text-slate-400 font-semibold mb-1">3. Rombel</label>
                                <select x-model="edit_class_id" :disabled="!edit_major" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-2.5 py-1.5 text-xs text-white focus:border-indigo-500 focus:outline-none disabled:opacity-50">
                                    <option value="">-- Rombel --</option>
                                    <template x-for="c in getRombels(edit_grade, edit_major)" :key="c.id">
                                        <option :value="c.id" x-text="c.rombel === '-' ? 'Umum' : 'Rombel ' + c.rombel"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                        <input type="hidden" name="class_id" :value="edit_class_id" required>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Tanggal Lahir (Opsional)</label>
                            <input type="date" name="birth_date" x-model="selectedStudent.birth_date" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2 text-sm text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Nomor HP Siswa (Opsional)</label>
                            <input type="text" name="phone" x-model="selectedStudent.phone" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" placeholder="0812..." />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Email Siswa (Opsional)</label>
                        <input type="email" name="email" x-model="selectedStudent.email" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" placeholder="siswa@sekolah.sch.id (bisa diisi sendiri oleh siswa)" />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Alamat Tempat Tinggal (Opsional)</label>
                        <textarea name="address" x-model="selectedStudent.address" rows="2" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" placeholder="Alamat lengkap..."></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-3">
                        <button @click="editModalOpen = false" type="button" class="rounded-xl px-4 py-2 text-xs font-bold text-slate-400 hover:text-white hover:bg-slate-800 transition cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl scp-btn-gradient text-xs font-bold text-white shadow-md cursor-pointer transition">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ============================================================= -->
        <!-- MODAL 3: TAMBAH SISWA BARU                                    -->
        <!-- ============================================================= -->
        <div x-show="addModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
            <div @click="addModalOpen = false" class="fixed inset-0 bg-black/75 backdrop-blur-sm"></div>
            
            <div class="relative w-full max-w-lg bg-[#0f172a] p-6 sm:p-8 shadow-2xl border border-slate-800 space-y-4 my-auto max-h-[90vh] overflow-y-auto" style="border-radius: 20px;">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-lg font-bold text-white">Tambah Siswa Baru</h3>
                    <button @click="addModalOpen = false" class="text-xs font-bold text-slate-400 hover:text-white px-2 py-1 cursor-pointer">
                        [Tutup]
                    </button>
                </div>

                <form method="POST" action="{{ route('waka.students.store') }}" class="space-y-3.5">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Nama Lengkap Siswa</label>
                        <input type="text" name="name" required class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" placeholder="Contoh: Muhammad Rizky" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">NIS (Opsional)</label>
                            <input type="text" name="nis" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" placeholder="Contoh: 12345" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">NISN / Username (Opsional)</label>
                            <input type="text" name="username" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" placeholder="Otomatis jika dikosongkan" />
                        </div>
                    </div>

                    <!-- Pilihan Kelas Dijabarkan: Tingkat, Jurusan, dan Rombel -->
                    <div class="grid grid-cols-3 gap-2.5">
                        <!-- 1. Tingkat Kelas -->
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Tingkat</label>
                            <select name="grade" required class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-xs text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                                <option value="X">Kelas 10 (X)</option>
                                <option value="XI">Kelas 11 (XI)</option>
                                <option value="XII">Kelas 12 (XII)</option>
                            </select>
                        </div>

                        <!-- 2. Jurusan -->
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Jurusan</label>
                            <select name="major" required class="w-full rounded-xl border border-slate-700 bg-slate-900 px-2.5 py-2 text-xs text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                                <option value="RPL">RPL (Rekayasa Perangkat Lunak)</option>
                                <option value="TKJ">TKJ (Teknik Komputer Jaringan)</option>
                                <option value="DKV">DKV (Desain Komunikasi Visual)</option>
                                <option value="AK">AK (Akuntansi)</option>
                                <option value="LPS">LPS (Layanan Perbankan Syariah)</option>
                                <option value="MP">MP (Manajemen Perkantoran)</option>
                                <option value="BR">BR (Bisnis Ritel)</option>
                                <option value="BD">BD (Bisnis Digital)</option>
                            </select>
                        </div>

                        <!-- 3. Rombel (1 - 5 atau Umum) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Rombel</label>
                            <select name="rombel" required class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-xs text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                                <option value="1">Rombel 1</option>
                                <option value="2">Rombel 2</option>
                                <option value="3">Rombel 3</option>
                                <option value="4">Rombel 4</option>
                                <option value="-">Umum (Tanpa Rombel)</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Email Siswa (Opsional)</label>
                            <input type="email" name="email" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" placeholder="siswa@sekolah.sch.id" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Nomor HP Siswa (Opsional)</label>
                            <input type="text" name="phone" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" placeholder="08123456789" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Tanggal Lahir (Opsional)</label>
                            <input type="date" name="birth_date" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2 text-sm text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Alamat Tempat Tinggal (Opsional)</label>
                            <textarea name="address" rows="1" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" placeholder="Alamat tempat tinggal..."></textarea>
                        </div>
                    </div>

                    <!-- Catatan Info Password Awal -->
                    <div class="p-3 rounded-xl bg-indigo-950/60 border border-indigo-500/30 text-xs text-indigo-200">
                        <span class="font-bold text-white">Catatan:</span> Kata sandi awal siswa otomatis disetel <code class="px-1.5 py-0.5 rounded bg-indigo-900 text-amber-300 font-mono font-bold">password123</code>. Data pribadi seperti email & HP dapat dilengkapi mandiri oleh siswa melalui menu profil masing-masing.
                    </div>

                    <div class="flex justify-end gap-2 pt-3">
                        <button @click="addModalOpen = false" type="button" class="rounded-xl px-4 py-2 text-xs font-bold text-slate-400 hover:text-white hover:bg-slate-800 transition cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl scp-btn-gradient text-xs font-bold text-white shadow-md cursor-pointer transition">
                            Simpan Data Siswa
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ================= MODAL IMPORT EXCEL DATA SISWA ================= -->
        <div x-show="importModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
            <div @click="importModalOpen = false" class="fixed inset-0 bg-black/70 backdrop-blur-sm"></div>
            <div class="relative w-full max-w-lg bg-[#0f172a] p-6 shadow-2xl border border-slate-800 space-y-4 my-auto max-h-[90vh] overflow-y-auto" style="border-radius: 20px;">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div>
                        <h3 class="text-base font-extrabold text-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                            Import Data Siswa Terpadu (PDF / Excel / CSV)
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">Unggah berkas PDF per rombel/presensi/Dapodik atau template spreadsheet</p>
                    </div>
                    <button @click="importModalOpen = false" class="text-xs font-bold text-slate-400 hover:text-white px-2 py-1 cursor-pointer">[Tutup]</button>
                </div>

                <!-- Petunjuk & Unduh Template -->
                <div class="p-3.5 rounded-xl bg-slate-900 border border-slate-800 space-y-2 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-slate-300">Format Berkas yang Didukung:</span>
                        <a href="{{ route('waka.students.template') }}" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-indigo-600/30 hover:bg-indigo-600 text-indigo-300 hover:text-white border border-indigo-500/30 font-bold transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Template Excel/CSV
                        </a>
                    </div>
                    <ul class="text-slate-400 text-[11px] space-y-1 list-disc list-inside">
                        <li><strong class="text-indigo-400">PDF Dokumen / Dapodik / Presensi:</strong> Berkas PDF daftar siswa resmi per rombel kelas. Nama kelas dideteksi otomatis dari judul halaman atau nama berkas PDF (contoh: <code class="text-indigo-300 font-mono">Daftar_Siswa_X_RPL_1.pdf</code>).</li>
                        <li><strong class="text-emerald-400">Auto-Provisioning:</strong> Kelas & jurusan baru dibuat otomatis, akun siswa aktif dengan NIS (kata sandi awal: <code class="text-amber-300 font-bold">password123</code>).</li>
                        <li><strong class="text-amber-400">Excel / CSV:</strong> Format kolom: <code class="text-indigo-300 font-mono">nama, nis, kelas, username, email, phone, birth_date, address</code>.</li>
                    </ul>
                </div>

                <form method="POST" action="{{ route('waka.students.import') }}" enctype="multipart/form-data" 
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
                                    <span x-text="selectedFiles.length + ' berkas siswa dipilih untuk diimpor serentak:'"></span>
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
                                        <!-- Tombol Silang (X) untuk Membatalkan / Menghapus File dari List -->
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
                        <p class="text-[10px] text-slate-500 mt-1 italic">
                            *Kata sandi awal siswa otomatis disetel <code class="text-amber-300 font-bold">password123</code>.
                        </p>
                    </div>

                    <!-- Opsi Penanganan Data Duplikat / Siswa yang Sudah Ada -->
                    <div class="p-3.5 rounded-xl bg-slate-900 border border-slate-800 space-y-2">
                        <label class="block text-[11px] font-bold text-slate-300">
                            Jika Ditemukan Data Siswa yang Sudah Ada (Dobel):
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                            <label class="flex items-start gap-2.5 p-2.5 rounded-lg border border-slate-800 bg-slate-950/60 hover:border-indigo-500/50 cursor-pointer transition">
                                <input type="radio" name="duplicate_action" value="replace" checked class="mt-0.5 text-indigo-600 focus:ring-indigo-500 bg-slate-900 border-slate-700" />
                                <div>
                                    <span class="font-bold text-white block text-[11px]">Ganti / Perbarui yang Baru</span>
                                    <span class="text-[10px] text-slate-400 leading-tight block mt-0.5">Timpa data lama dengan data terbaru dari dokumen</span>
                                </div>
                            </label>
                            <label class="flex items-start gap-2.5 p-2.5 rounded-lg border border-slate-800 bg-slate-950/60 hover:border-indigo-500/50 cursor-pointer transition">
                                <input type="radio" name="duplicate_action" value="keep" class="mt-0.5 text-indigo-600 focus:ring-indigo-500 bg-slate-900 border-slate-700" />
                                <div>
                                    <span class="font-bold text-white block text-[11px]">Pertahankan Data Awal</span>
                                    <span class="text-[10px] text-slate-400 leading-tight block mt-0.5">Lewati jika NIS atau Nama siswa sudah terdaftar</span>
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
                            Mulai Import Data Siswa
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL KONFIRMASI: HAPUS SISWA TERPILIH -->
        <div x-show="deleteSelectedModalOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
            <div @click="deleteSelectedModalOpen = false" class="fixed inset-0 bg-black/70 backdrop-blur-sm"></div>
            <div class="relative w-full max-w-md bg-[#0f172a] p-6 shadow-2xl border border-rose-500/30 space-y-4 my-auto max-h-[90vh] overflow-y-auto" style="border-radius: 20px;">
                <div class="flex items-center gap-3 border-b border-slate-800 pb-3">
                    <div class="w-10 h-10 rounded-xl bg-rose-500/20 text-rose-400 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-white">Hapus Siswa Terpilih</h3>
                        <p class="text-xs text-slate-400">Konfirmasi tindakan penghapusan massal</p>
                    </div>
                </div>

                <div class="p-3.5 rounded-xl bg-rose-950/30 border border-rose-800/40 text-xs text-rose-300 leading-relaxed">
                    Anda yakin ingin menghapus <strong x-text="selectedStudents.length" class="text-white underline"></strong> data siswa yang dipilih? Riwayat presensi dan perizinan siswa tersebut juga akan dihapus secara permanen.
                </div>

                <form method="POST" action="{{ route('waka.students.delete-selected') }}" class="space-y-4">
                    @csrf
                    <!-- Loop Hidden Inputs IDs -->
                    <template x-for="id in selectedStudents" :key="id">
                        <input type="hidden" name="student_ids[]" :value="id">
                    </template>

                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
                        <button @click="deleteSelectedModalOpen = false" type="button" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-400 hover:bg-slate-800 hover:text-white transition cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-xs font-bold text-white shadow-md shadow-rose-950/60 transition cursor-pointer flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            <span>Ya, Hapus Siswa Terpilih</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL KONFIRMASI: HAPUS SEMUA SISWA -->
        <div x-show="deleteAllModalOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
            <div @click="deleteAllModalOpen = false" class="fixed inset-0 bg-black/70 backdrop-blur-sm"></div>
            <div class="relative w-full max-w-md bg-[#0f172a] p-6 shadow-2xl border border-rose-500/40 space-y-4 my-auto max-h-[90vh] overflow-y-auto" style="border-radius: 20px;">
                <div class="flex items-center gap-3 border-b border-slate-800 pb-3">
                    <div class="w-10 h-10 rounded-xl bg-rose-500/20 text-rose-400 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-white">Hapus Semua Data Siswa</h3>
                        <p class="text-xs text-rose-400 font-semibold">Tindakan ini tidak dapat dibatalkan!</p>
                    </div>
                </div>

                <div class="p-3.5 rounded-xl bg-rose-950/40 border border-rose-800/50 text-xs text-rose-200 leading-relaxed">
                    PERINGATAN: Tindakan ini akan menghapus <strong>seluruh data siswa</strong> dari sistem, termasuk riwayat akun login, presensi, dan perizinan. Pastikan Anda sudah mencadangkan (backup) data jika diperlukan.
                </div>

                <form method="POST" action="{{ route('waka.students.delete-all') }}" class="space-y-4 text-xs">
                    @csrf
                    <div>
                        <label class="block font-bold text-slate-300 mb-1.5">Cakupan Penghapusan:</label>
                        <select name="class_id" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2.5 text-xs font-semibold text-white focus:border-rose-500 focus:outline-none focus:ring-1 focus:ring-rose-500">
                            <option value="all">⚠️ Hapus SELURUH Siswa di SEMUA Kelas</option>
                            @foreach($classes as $c)
                                <option value="{{ $c->id }}" {{ request('class_id') == $c->id ? 'selected' : '' }}>
                                    Hanya Siswa di Kelas: {{ $c->class_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
                        <button @click="deleteAllModalOpen = false" type="button" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-400 hover:bg-slate-800 hover:text-white transition cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-xs font-bold text-white shadow-md shadow-rose-950/60 transition cursor-pointer flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            <span>Saya Yakin, Hapus Sekarang</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>
