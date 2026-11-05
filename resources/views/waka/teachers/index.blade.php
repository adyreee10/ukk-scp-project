<x-app-layout>
    <div x-data="{ 
            addModalOpen: false, 
            editModalOpen: false, 
            viewModalOpen: false,
            importModalOpen: false,
            classesList: {{ json_encode($structuredClasses) }},
            rolesList: {{ json_encode($roles) }},

            // Helper Cascading 3 Kolom (Tingkat -> Jurusan -> Rombel)
            getMajors(grade) {
                if (!grade) return [];
                const filtered = this.classesList.filter(c => c.grade === grade);
                return [...new Set(filtered.map(c => c.major))].sort();
            },
            getRombels(grade, major) {
                if (!grade || !major) return [];
                return this.classesList.filter(c => c.grade === grade && c.major === major);
            },

            // State Form Tambah Guru
            addForm: {
                name: '',
                nip: '',
                username: '',
                email: '',
                phone: '',
                address: '',
                role_id: '{{ $roles->firstWhere("role_name", "Guru")?->id ?? "" }}',
                homeroom_grade: '',
                homeroom_major: '',
                homeroom_class_id: '',
                counselor_grade: '',
                counselor_major: '',
                counselor_class_id: '',
                counselor_classes: []
            },
            get addRoleName() {
                const r = this.rolesList.find(role => String(role.id) === String(this.addForm.role_id));
                return r ? r.role_name : '';
            },
            addCounselorClass() {
                if (!this.addForm.counselor_class_id) return;
                const cls = this.classesList.find(c => String(c.id) === String(this.addForm.counselor_class_id));
                if (cls && !this.addForm.counselor_classes.some(item => item.id === cls.id)) {
                    this.addForm.counselor_classes.push({ id: cls.id, class_name: cls.class_name });
                }
                this.addForm.counselor_class_id = '';
            },
            removeCounselorClass(index) {
                this.addForm.counselor_classes.splice(index, 1);
            },

            // State Form Edit Guru
            editForm: {
                id: '',
                name: '',
                nip: '',
                username: '',
                email: '',
                phone: '',
                address: '',
                role_id: '',
                homeroom_grade: '',
                homeroom_major: '',
                homeroom_class_id: '',
                counselor_grade: '',
                counselor_major: '',
                counselor_class_id: '',
                counselor_classes: []
            },
            get editRoleName() {
                const r = this.rolesList.find(role => String(role.id) === String(this.editForm.role_id));
                return r ? r.role_name : '';
            },
            editCounselorClass() {
                if (!this.editForm.counselor_class_id) return;
                const cls = this.classesList.find(c => String(c.id) === String(this.editForm.counselor_class_id));
                if (cls && !this.editForm.counselor_classes.some(item => item.id === cls.id)) {
                    this.editForm.counselor_classes.push({ id: cls.id, class_name: cls.class_name });
                }
                this.editForm.counselor_class_id = '';
            },
            removeEditCounselorClass(index) {
                this.editForm.counselor_classes.splice(index, 1);
            },

            openEdit(teacherData, counselorClassesJson) {
                this.editForm.id = teacherData.id;
                this.editForm.name = teacherData.name;
                this.editForm.username = teacherData.username;
                this.editForm.nip = teacherData.nip || '';
                this.editForm.email = teacherData.email || '';
                this.editForm.phone = teacherData.phone || '';
                this.editForm.address = teacherData.address || '';
                this.editForm.role_id = teacherData.role_id;
                this.editForm.homeroom_class_id = teacherData.homeroom_class_id || '';

                if (teacherData.homeroom_class_id) {
                    const hCls = this.classesList.find(c => String(c.id) === String(teacherData.homeroom_class_id));
                    if (hCls) {
                        this.editForm.homeroom_grade = hCls.grade;
                        this.editForm.homeroom_major = hCls.major;
                    }
                } else {
                    this.editForm.homeroom_grade = '';
                    this.editForm.homeroom_major = '';
                }

                this.editForm.counselor_classes = counselorClassesJson ? JSON.parse(counselorClassesJson) : [];
                this.editForm.counselor_grade = '';
                this.editForm.counselor_major = '';
                this.editForm.counselor_class_id = '';

                this.editModalOpen = true;
            },

            // Modal Detail Guru
            selectedTeacher: { 
                id: '', 
                name: '', 
                username: '', 
                nip: '', 
                email: '', 
                phone: '', 
                address: '', 
                role_id: '', 
                role_name: '',
                homeroom_class_name: '',
                counselor_classes: [],
                schedules_count: 0
            },
            openView(teacherData, counselorClassesJson) {
                this.selectedTeacher = {
                    ...teacherData,
                    counselor_classes: counselorClassesJson ? JSON.parse(counselorClassesJson) : []
                };
                this.viewModalOpen = true;
            }
         }" 
         class="space-y-6">
        
        <!-- Header & Action Bar -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 w-full">
            <div class="text-left">
                <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">Data Guru</h1>
                <p class="text-xs font-semibold text-slate-400 mt-0.5">Kelola data tenaga pendidik, penugasan Wali Kelas & Guru BK (multi-kelas), serta akun login guru</p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <button @click="importModalOpen = true" type="button" class="px-3.5 sm:px-4 py-2 sm:py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-xs sm:text-sm font-bold text-white shadow-md shadow-emerald-950/40 cursor-pointer transition flex items-center gap-1.5">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    <span>Import Excel / Dokumen</span>
                </button>
                <button @click="addModalOpen = true" type="button" class="px-4 sm:px-5 py-2 sm:py-2.5 rounded-xl scp-btn-gradient text-xs sm:text-sm font-bold text-white shadow-md cursor-pointer transition whitespace-nowrap">
                    + Tambah Guru Baru
                </button>
            </div>
        </div>

        <!-- Filter Cepat Peran & Pencarian -->
        <div class="bg-[#0f172a]/90 backdrop-blur-md p-4 sm:p-5 border border-slate-800 shadow-xl space-y-3.5" style="border-radius: 15px;">
            <!-- Tab Tombol Filter Cepat -->
            <div class="flex items-center gap-2 overflow-x-auto pb-1 sm:pb-0 no-scrollbar">
                <span class="text-xs font-bold text-slate-400 shrink-0 mr-1 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    Filter Peran:
                </span>

                <!-- 1. Semua Guru -->
                <a href="{{ route('waka.teachers.index', array_filter(['search' => request('search')])) }}" 
                   class="px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shrink-0 {{ !request('role') || request('role') === 'all' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'bg-slate-900 text-slate-300 hover:bg-slate-800 hover:text-white border border-slate-800' }}">
                    <span>Semua Guru</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ !request('role') || request('role') === 'all' ? 'bg-indigo-800 text-indigo-100' : 'bg-slate-800 text-slate-400' }}">{{ $countAll }}</span>
                </a>

                <!-- 2. Guru Pengajar -->
                <a href="{{ route('waka.teachers.index', array_filter(['role' => 'Guru', 'search' => request('search')])) }}" 
                   class="px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shrink-0 {{ request('role') === 'Guru' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'bg-slate-900 text-slate-300 hover:bg-slate-800 hover:text-white border border-slate-800' }}">
                    <span>Guru Pengajar</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ request('role') === 'Guru' ? 'bg-indigo-800 text-indigo-100' : 'bg-slate-800 text-slate-400' }}">{{ $countGuru }}</span>
                </a>

                <!-- 3. Wali Kelas -->
                <a href="{{ route('waka.teachers.index', array_filter(['role' => 'Wali Kelas', 'search' => request('search')])) }}" 
                   class="px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shrink-0 {{ request('role') === 'Wali Kelas' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'bg-slate-900 text-slate-300 hover:bg-slate-800 hover:text-white border border-slate-800' }}">
                    <span>Wali Kelas</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ request('role') === 'Wali Kelas' ? 'bg-indigo-800 text-indigo-100' : 'bg-slate-800 text-slate-400' }}">{{ $countWali }}</span>
                </a>

                <!-- 4. Guru BK -->
                <a href="{{ route('waka.teachers.index', array_filter(['role' => 'Guru BK', 'search' => request('search')])) }}" 
                   class="px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shrink-0 {{ request('role') === 'Guru BK' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'bg-slate-900 text-slate-300 hover:bg-slate-800 hover:text-white border border-slate-800' }}">
                    <span>Guru BK</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ request('role') === 'Guru BK' ? 'bg-indigo-800 text-indigo-100' : 'bg-slate-800 text-slate-400' }}">{{ $countBK }}</span>
                </a>

                @if(request('role') || request('search'))
                    <a href="{{ route('waka.teachers.index') }}" class="text-[11px] font-bold text-rose-400 hover:text-rose-300 hover:underline transition shrink-0 ml-auto flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Reset Filter
                    </a>
                @endif
            </div>

            <!-- Form Input Pencarian -->
            <form method="GET" action="{{ route('waka.teachers.index') }}" class="flex flex-col sm:flex-row gap-2">
                @if(request('role'))
                    <input type="hidden" name="role" value="{{ request('role') }}">
                @endif
                <div class="relative w-full">
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}" 
                           placeholder="Cari nama guru, NIP, email, atau nomor HP..." 
                           class="w-full rounded-xl border border-slate-700 bg-slate-900 pl-10 pr-4 py-2.5 text-xs sm:text-sm text-white placeholder-slate-400 focus:border-indigo-500 focus:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" />
                    <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-3 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <button type="submit" class="rounded-xl scp-btn-gradient px-6 py-2.5 text-xs font-bold text-white transition shadow-sm cursor-pointer whitespace-nowrap">
                        Cari
                    </button>
                </div>
            </form>
        </div>

        <!-- Tabel Data Guru -->
        <div class="bg-[#0f172a]/90 backdrop-blur-md border border-slate-800 shadow-xl overflow-hidden" style="border-radius: 15px;">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-800 text-sm">
                    <thead class="bg-slate-900/70">
                        <tr class="text-left text-xs font-bold text-slate-400 uppercase tracking-wider">
                            <th class="p-4 text-center w-12">No</th>
                            <th class="p-4">Guru</th>
                            <th class="p-4">NIP</th>
                            <th class="p-4">Peran</th>
                            <th class="p-4">Wali / Perwalian Kelas</th>
                            <th class="p-4">Kontak / HP</th>
                            <th class="p-4 text-center">Jadwal</th>
                            <th class="p-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @forelse ($teachers as $teacher)
                            <tr class="hover:bg-slate-800/30 transition">
                                <!-- Nomor Urut -->
                                <td class="p-4 text-center text-xs font-bold text-slate-400">
                                    {{ $teachers->firstItem() + $loop->index }}
                                </td>

                                <!-- Identitas Guru -->
                                <td class="p-4">
                                    <div>
                                        <p class="font-bold text-white">{{ $teacher->name }}</p>
                                        <p class="text-xs text-slate-400 font-mono">{{ $teacher->email ?? '-' }}</p>
                                    </div>
                                </td>

                                <!-- NIP -->
                                <td class="p-4 text-xs font-mono text-slate-300">
                                    {{ $teacher->nip ?? '-' }}
                                </td>

                                <!-- Peran -->
                                <td class="p-4">
                                    <span class="inline-block rounded-md px-2.5 py-1 text-xs font-bold badge-role shadow-sm">
                                        {{ $teacher->role?->role_name ?? 'Guru' }}
                                    </span>
                                </td>

                                <!-- Wali Kelas / Kelas Binaan Guru BK -->
                                <td class="p-4 text-xs font-semibold">
                                    @if($teacher->role?->role_name === 'Guru BK')
                                        @if($teacher->counselorClasses->isNotEmpty())
                                            <div class="flex flex-wrap gap-1 max-w-xs">
                                                @foreach($teacher->counselorClasses as $cCls)
                                                    <span class="inline-block px-2 py-0.5 rounded-md text-[11px] font-bold bg-indigo-950/80 text-indigo-300 border border-indigo-700/60 shadow-sm">
                                                        {{ $cCls->class_name }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-slate-500 italic text-[11px]">Belum ada kelas binaan</span>
                                        @endif
                                    @elseif($teacher->role?->role_name === 'Wali Kelas')
                                        @if($teacher->homeroomClass)
                                            <span class="inline-block whitespace-nowrap rounded-md px-2.5 py-1 text-xs font-bold badge-class shadow-sm">
                                                {{ $teacher->homeroomClass->class_name }}
                                            </span>
                                        @else
                                            <span class="text-slate-500 italic text-[11px]">Belum ditentukan</span>
                                        @endif
                                    @else
                                        <span class="text-slate-500 font-normal">-</span>
                                    @endif  
                                </td>

                                <!-- Nomor HP -->
                                <td class="p-4 text-xs font-medium text-slate-300">
                                    @if($teacher->phone)
                                        <span class="text-emerald-400 font-mono">{{ $teacher->phone }}</span>
                                    @else
                                        <span class="text-slate-500">-</span>
                                    @endif
                                </td>

                                <!-- Jadwal Mengajar -->
                                <td class="p-4 text-center">
                                    <span class="inline-flex items-center justify-center rounded-full px-3 py-1 text-xs font-bold badge-schedule-count shadow-sm">
                                        {{ $teacher->schedules_count }} Jadwal
                                    </span>
                                </td>

                                <!-- Aksi (Lihat, Edit, Hapus) -->
                                <td class="p-4 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        <!-- Tombol Lihat -->
                                        <button type="button" 
                                                @click="openView({
                                                    id: '{{ $teacher->id }}',
                                                    name: '{{ addslashes($teacher->name) }}',
                                                    username: '{{ addslashes($teacher->username) }}',
                                                    nip: '{{ addslashes($teacher->nip ?? '-') }}',
                                                    email: '{{ addslashes($teacher->email ?? '-') }}',
                                                    phone: '{{ addslashes($teacher->phone ?? '-') }}',
                                                    address: '{{ addslashes($teacher->address ?? '-') }}',
                                                    role_name: '{{ addslashes($teacher->role?->role_name ?? 'Guru') }}',
                                                    homeroom_class_name: '{{ addslashes($teacher->homeroomClass?->class_name ?? '-') }}',
                                                    schedules_count: '{{ $teacher->schedules_count }}'
                                                }, '{{ addslashes(json_encode($teacher->counselorClasses->map(fn($c) => ['id' => $c->id, 'class_name' => $c->class_name]))) }}')"
                                                class="px-2.5 py-1 text-xs font-bold rounded-lg transition cursor-pointer btn-action-view shadow-sm"
                                                title="Lihat Detail Profil">
                                            Lihat
                                        </button>

                                        <!-- Tombol Edit -->
                                        <button type="button" 
                                                @click="openEdit({
                                                    id: '{{ $teacher->id }}',
                                                    name: '{{ addslashes($teacher->name) }}',
                                                    username: '{{ addslashes($teacher->username) }}',
                                                    nip: '{{ addslashes($teacher->nip ?? '') }}',
                                                    email: '{{ addslashes($teacher->email ?? '') }}',
                                                    phone: '{{ addslashes($teacher->phone ?? '') }}',
                                                    address: '{{ addslashes($teacher->address ?? '') }}',
                                                    role_id: '{{ $teacher->role_id }}',
                                                    homeroom_class_id: '{{ $teacher->homeroom_class_id }}'
                                                }, '{{ addslashes(json_encode($teacher->counselorClasses->map(fn($c) => ['id' => $c->id, 'class_name' => $c->class_name]))) }}')"
                                                class="px-2.5 py-1 text-xs font-bold rounded-lg transition cursor-pointer btn-action-edit shadow-sm"
                                                title="Edit Data Guru">
                                            Edit
                                        </button>

                                        <!-- Tombol Hapus -->
                                        @if($teacher->id !== auth()->id())
                                            <form method="POST" action="{{ route('waka.teachers.destroy', $teacher) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data guru ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="px-2.5 py-1 text-xs font-bold rounded-lg transition cursor-pointer btn-action-delete shadow-sm" title="Hapus Guru">
                                                    Hapus
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-[10px] font-bold text-slate-500 italic px-1">Akun Anda</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="p-8 text-center text-slate-400 text-sm">
                                    Tidak ada data guru yang sesuai.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($teachers->hasPages())
                <div class="p-4 border-t border-slate-800">
                    {{ $teachers->links() }}
                </div>
            @endif
        </div>

        <!-- ============================================================= -->
        <!-- MODAL 1: LIHAT DETAIL PROFIL GURU                            -->
        <!-- ============================================================= -->
        <div x-show="viewModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
            <div @click="viewModalOpen = false" class="fixed inset-0 bg-black/75 backdrop-blur-sm"></div>
            
            <div class="relative w-full max-w-lg bg-[#0f172a] p-6 sm:p-7 shadow-2xl border border-slate-800 space-y-5 my-auto max-h-[90vh] overflow-y-auto" style="border-radius: 20px;">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <span>Detail Profil Guru</span>
                    </h3>
                    <button @click="viewModalOpen = false" class="text-xs font-bold text-slate-400 hover:text-white px-2 py-1 cursor-pointer">
                        [Tutup]
                    </button>
                </div>

                <!-- Kartu Identitas Atas -->
                <div class="p-4 rounded-xl bg-slate-900/80 border border-slate-800">
                    <h4 class="text-base font-extrabold text-white truncate" x-text="selectedTeacher.name"></h4>
                    <p class="text-xs text-indigo-400 font-mono mt-0.5" x-text="'@' + selectedTeacher.username"></p>
                    <div class="flex items-center gap-2 mt-2">
                        <span class="inline-block px-2.5 py-0.5 rounded-md text-[10px] font-bold badge-role" x-text="selectedTeacher.role_name"></span>
                        <span class="inline-block px-2.5 py-0.5 rounded-md text-[10px] font-bold badge-schedule-count" x-text="selectedTeacher.schedules_count + ' Jadwal Aktif'"></span>
                    </div>
                </div>

                <!-- Rincian Data Lengkap -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                    <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800/80">
                        <span class="text-slate-400 font-bold block mb-1">NIP (Nomor Induk Pegawai)</span>
                        <span class="text-white font-mono font-bold" x-text="selectedTeacher.nip || '-'"></span>
                    </div>

                    <!-- Jika Wali Kelas -->
                    <template x-if="selectedTeacher.role_name === 'Wali Kelas'">
                        <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800/80">
                            <span class="text-slate-400 font-bold block mb-1">Wali Kelas</span>
                            <span class="text-white font-bold" x-text="selectedTeacher.homeroom_class_name || 'Bukan Wali Kelas'"></span>
                        </div>
                    </template>

                    <!-- Jika Guru BK: Tampilkan Semua Kelas Perwalian -->
                    <template x-if="selectedTeacher.role_name === 'Guru BK'">
                        <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800/80 sm:col-span-2">
                            <span class="text-slate-400 font-bold block mb-1.5">Kelas Perwalian / Binaan BK:</span>
                            <div class="flex flex-wrap gap-1.5">
                                <template x-for="cCls in selectedTeacher.counselor_classes" :key="cCls.id">
                                    <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-indigo-950 text-indigo-300 border border-indigo-700/60" x-text="cCls.class_name"></span>
                                </template>
                                <template x-if="!selectedTeacher.counselor_classes || selectedTeacher.counselor_classes.length === 0">
                                    <span class="text-slate-500 italic">Belum ada kelas perwalian yang ditugaskan.</span>
                                </template>
                            </div>
                        </div>
                    </template>

                    <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800/80">
                        <span class="text-slate-400 font-bold block mb-1">Email Resmi</span>
                        <span class="text-white font-mono break-all" x-text="selectedTeacher.email || '-'"></span>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800/80">
                        <span class="text-slate-400 font-bold block mb-1">Nomor HP / WhatsApp</span>
                        <span class="text-emerald-400 font-mono font-bold" x-text="selectedTeacher.phone || '-'"></span>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800/80 sm:col-span-2">
                        <span class="text-slate-400 font-bold block mb-1">Alamat Tempat Tinggal</span>
                        <p class="text-slate-200 leading-relaxed" x-text="selectedTeacher.address || 'Belum diisi'"></p>
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
        <!-- MODAL 2: EDIT DATA GURU                                       -->
        <!-- ============================================================= -->
        <div x-show="editModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
            <div @click="editModalOpen = false" class="fixed inset-0 bg-black/75 backdrop-blur-sm"></div>
            
            <div class="relative w-full max-w-xl bg-[#0f172a] p-6 sm:p-8 shadow-2xl border border-slate-800 space-y-4 my-auto max-h-[90vh] overflow-y-auto" style="border-radius: 20px;">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-lg font-bold text-white">Edit Data Guru & Peran</h3>
                    <button @click="editModalOpen = false" class="text-xs font-bold text-slate-400 hover:text-white px-2 py-1 cursor-pointer">
                        [Tutup]
                    </button>
                </div>

                <form method="POST" :action="'/waka/teachers/' + editForm.id" class="space-y-3.5">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Nama Lengkap & Gelar</label>
                        <input type="text" name="name" x-model="editForm.name" required class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">NIP (Opsional)</label>
                            <input type="text" name="nip" x-model="editForm.nip" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" placeholder="1985..." />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Username Login</label>
                            <input type="text" name="username" x-model="editForm.username" required class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Email Sekolah (Opsional)</label>
                            <input type="email" name="email" x-model="editForm.email" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" placeholder="guru@sekolah.sch.id" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Nomor HP / WhatsApp (Opsional)</label>
                            <input type="text" name="phone" x-model="editForm.phone" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" placeholder="0812..." />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Alamat Tempat Tinggal</label>
                        <textarea name="address" x-model="editForm.address" rows="2" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" placeholder="Jl. Melati No..."></textarea>
                    </div>

                    <!-- Pemilihan Peran Guru -->
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Penugasan Peran Guru</label>
                        <select name="role_id" x-model="editForm.role_id" required class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2.5 text-sm font-bold text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" class="bg-slate-900 text-white">{{ $role->role_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- KASUS 1: JIKA PERAN = WALI KELAS (PILIH 1 KELAS DENGAN 3 DROPDOWN: TINGKAT, JURUSAN, ROMBEL) -->
                    <template x-if="editRoleName === 'Wali Kelas'">
                        <div class="p-3.5 rounded-xl bg-indigo-950/40 border border-indigo-500/40 space-y-2.5">
                            <label class="block text-xs font-bold text-indigo-300">
                                Pilih Kelas Perwalian (Wali Kelas Mewakili 1 Kelas):
                            </label>
                            <div class="grid grid-cols-3 gap-2">
                                <!-- 1. Tingkat -->
                                <div>
                                    <label class="block text-[10px] text-slate-400 font-semibold mb-1">1. Tingkat</label>
                                    <select x-model="editForm.homeroom_grade" @change="editForm.homeroom_major = ''; editForm.homeroom_class_id = ''" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-2.5 py-1.5 text-xs text-white focus:border-indigo-500 focus:outline-none">
                                        <option value="">-- Tingkat --</option>
                                        <option value="X">Kelas X</option>
                                        <option value="XI">Kelas XI</option>
                                        <option value="XII">Kelas XII</option>
                                    </select>
                                </div>
                                <!-- 2. Jurusan -->
                                <div>
                                    <label class="block text-[10px] text-slate-400 font-semibold mb-1">2. Jurusan</label>
                                    <select x-model="editForm.homeroom_major" @change="editForm.homeroom_class_id = ''" :disabled="!editForm.homeroom_grade" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-2.5 py-1.5 text-xs text-white focus:border-indigo-500 focus:outline-none disabled:opacity-50">
                                        <option value="">-- Jurusan --</option>
                                        <template x-for="m in getMajors(editForm.homeroom_grade)" :key="m">
                                            <option :value="m" x-text="m"></option>
                                        </template>
                                    </select>
                                </div>
                                <!-- 3. Rombel -->
                                <div>
                                    <label class="block text-[10px] text-slate-400 font-semibold mb-1">3. Rombel</label>
                                    <select x-model="editForm.homeroom_class_id" :disabled="!editForm.homeroom_major" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-2.5 py-1.5 text-xs text-white focus:border-indigo-500 focus:outline-none disabled:opacity-50">
                                        <option value="">-- Rombel --</option>
                                        <template x-for="c in getRombels(editForm.homeroom_grade, editForm.homeroom_major)" :key="c.id">
                                            <option :value="c.id" x-text="c.rombel === '-' ? 'Umum' : 'Rombel ' + c.rombel"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>
                            <!-- Input Hidden untuk dikirim ke backend -->
                            <input type="hidden" name="homeroom_class_id" :value="editForm.homeroom_class_id">
                        </div>
                    </template>

                    <!-- KASUS 2: JIKA PERAN = GURU BK (BISA MEWAKILI BANYAK KELAS: 3 DROPDOWN + TOMBOL TAMBAH) -->
                    <template x-if="editRoleName === 'Guru BK'">
                        <div class="p-3.5 rounded-xl bg-indigo-950/40 border border-indigo-500/40 space-y-3">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-bold text-indigo-300">
                                    Kelas yang Diwakili / Binaan Guru BK (Bisa Banyak Kelas):
                                </label>
                                <span class="text-[10px] text-slate-400 font-semibold" x-text="editForm.counselor_classes.length + ' kelas terpilih'"></span>
                            </div>

                            <!-- Pemilih 3 Kolom Bertingkat + Tombol Tambah -->
                            <div class="grid grid-cols-1 sm:grid-cols-4 gap-2 items-end">
                                <div>
                                    <label class="block text-[10px] text-slate-400 font-semibold mb-1">1. Tingkat</label>
                                    <select x-model="editForm.counselor_grade" @change="editForm.counselor_major = ''; editForm.counselor_class_id = ''" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-2.5 py-1.5 text-xs text-white focus:border-indigo-500 focus:outline-none">
                                        <option value="">-- Tingkat --</option>
                                        <option value="X">Kelas X</option>
                                        <option value="XI">Kelas XI</option>
                                        <option value="XII">Kelas XII</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] text-slate-400 font-semibold mb-1">2. Jurusan</label>
                                    <select x-model="editForm.counselor_major" @change="editForm.counselor_class_id = ''" :disabled="!editForm.counselor_grade" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-2.5 py-1.5 text-xs text-white focus:border-indigo-500 focus:outline-none disabled:opacity-50">
                                        <option value="">-- Jurusan --</option>
                                        <template x-for="m in getMajors(editForm.counselor_grade)" :key="m">
                                            <option :value="m" x-text="m"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] text-slate-400 font-semibold mb-1">3. Rombel</label>
                                    <select x-model="editForm.counselor_class_id" :disabled="!editForm.counselor_major" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-2.5 py-1.5 text-xs text-white focus:border-indigo-500 focus:outline-none disabled:opacity-50">
                                        <option value="">-- Rombel --</option>
                                        <template x-for="c in getRombels(editForm.counselor_grade, editForm.counselor_major)" :key="c.id">
                                            <option :value="c.id" x-text="c.rombel === '-' ? 'Umum' : 'Rombel ' + c.rombel"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <button type="button" 
                                            @click="editCounselorClass()" 
                                            :disabled="!editForm.counselor_class_id" 
                                            class="w-full py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white font-bold text-xs transition cursor-pointer flex items-center justify-center gap-1 shadow">
                                        <span>+ Tambah</span>
                                    </button>
                                </div>
                            </div>

                            <!-- List Chips / Badges Kelas yang Diwakili -->
                            <div class="p-2.5 rounded-xl bg-slate-900/80 border border-slate-800 space-y-1.5">
                                <span class="text-[10px] font-bold text-slate-400 block">Daftar Kelas Binaan yang Diwakili Guru BK Ini:</span>
                                <div class="flex flex-wrap gap-1.5 min-h-[32px] items-center">
                                    <template x-for="(cls, idx) in editForm.counselor_classes" :key="cls.id">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-indigo-950 text-indigo-200 border border-indigo-700/60 shadow-sm">
                                            <span x-text="cls.class_name"></span>
                                            <!-- Hidden Input untuk form POST -->
                                            <input type="hidden" name="counselor_class_ids[]" :value="cls.id">
                                            <button type="button" @click="removeEditCounselorClass(idx)" class="text-rose-400 hover:text-rose-300 font-bold ml-0.5 cursor-pointer" title="Hapus kelas ini">✕</button>
                                        </span>
                                    </template>
                                    <template x-if="editForm.counselor_classes.length === 0">
                                        <span class="text-slate-500 text-xs italic">Belum ada kelas yang ditambahkan. Pilih tingkat, jurusan, dan rombel lalu klik Tambah.</span>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>

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
        <!-- MODAL 3: TAMBAH GURU BARU                                     -->
        <!-- ============================================================= -->
        <div x-show="addModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
            <div @click="addModalOpen = false" class="fixed inset-0 bg-black/75 backdrop-blur-sm"></div>
            
            <div class="relative w-full max-w-xl bg-[#0f172a] p-6 sm:p-8 shadow-2xl border border-slate-800 space-y-4 my-auto max-h-[90vh] overflow-y-auto" style="border-radius: 20px;">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-lg font-bold text-white">Tambah Guru Baru</h3>
                    <button @click="addModalOpen = false" class="text-xs font-bold text-slate-400 hover:text-white px-2 py-1 cursor-pointer">
                        [Tutup]
                    </button>
                </div>

                <form method="POST" action="{{ route('waka.teachers.store') }}" class="space-y-3.5">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Nama Lengkap & Gelar</label>
                        <input type="text" name="name" x-model="addForm.name" required class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" placeholder="Contoh: Drs. Budi Santoso, M.Pd" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">NIP (Opsional)</label>
                            <input type="text" name="nip" x-model="addForm.nip" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" placeholder="19850101..." />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Username Login (Opsional)</label>
                            <input type="text" name="username" x-model="addForm.username" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" placeholder="Otomatis jika kosong" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Email Sekolah (Opsional)</label>
                            <input type="email" name="email" x-model="addForm.email" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" placeholder="budi@sekolah.sch.id" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Nomor HP / WhatsApp (Opsional)</label>
                            <input type="text" name="phone" x-model="addForm.phone" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" placeholder="08123456789" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Alamat Tempat Tinggal (Opsional)</label>
                        <textarea name="address" x-model="addForm.address" rows="2" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" placeholder="Alamat lengkap tempat tinggal guru..."></textarea>
                    </div>

                    <!-- Notes Password Default Guru Baru -->
                    <div class="p-3.5 rounded-xl bg-indigo-950/60 border border-indigo-500/30 text-xs text-indigo-200 flex items-start gap-2.5">
                        <svg class="w-4 h-4 text-indigo-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <div>
                            <span class="font-bold text-white block mb-0.5">Catatan Kata Sandi Akun:</span>
                            Setiap akun guru yang baru diinput akan otomatis dibuatkan kata sandi awal: <code class="px-1.5 py-0.5 rounded bg-indigo-900 text-amber-300 font-mono font-bold">password123</code> (guru dapat mengubah kata sandi mandiri di menu profil).
                        </div>
                    </div>

                    <!-- Pilihan Penugasan Peran Guru -->
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Penugasan Peran Guru</label>
                        <select name="role_id" x-model="addForm.role_id" required class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2.5 text-sm font-bold text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" class="bg-slate-900 text-white">{{ $role->role_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- KASUS 1: JIKA PERAN = WALI KELAS (PILIH 1 KELAS DENGAN 3 DROPDOWN) -->
                    <template x-if="addRoleName === 'Wali Kelas'">
                        <div class="p-3.5 rounded-xl bg-indigo-950/40 border border-indigo-500/40 space-y-2.5">
                            <label class="block text-xs font-bold text-indigo-300">
                                Pilih Kelas Perwalian (Wali Kelas Mewakili 1 Kelas):
                            </label>
                            <div class="grid grid-cols-3 gap-2">
                                <div>
                                    <label class="block text-[10px] text-slate-400 font-semibold mb-1">1. Tingkat</label>
                                    <select x-model="addForm.homeroom_grade" @change="addForm.homeroom_major = ''; addForm.homeroom_class_id = ''" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-2.5 py-1.5 text-xs text-white focus:border-indigo-500 focus:outline-none">
                                        <option value="">-- Tingkat --</option>
                                        <option value="X">Kelas X</option>
                                        <option value="XI">Kelas XI</option>
                                        <option value="XII">Kelas XII</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] text-slate-400 font-semibold mb-1">2. Jurusan</label>
                                    <select x-model="addForm.homeroom_major" @change="addForm.homeroom_class_id = ''" :disabled="!addForm.homeroom_grade" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-2.5 py-1.5 text-xs text-white focus:border-indigo-500 focus:outline-none disabled:opacity-50">
                                        <option value="">-- Jurusan --</option>
                                        <template x-for="m in getMajors(addForm.homeroom_grade)" :key="m">
                                            <option :value="m" x-text="m"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] text-slate-400 font-semibold mb-1">3. Rombel</label>
                                    <select x-model="addForm.homeroom_class_id" :disabled="!addForm.homeroom_major" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-2.5 py-1.5 text-xs text-white focus:border-indigo-500 focus:outline-none disabled:opacity-50">
                                        <option value="">-- Rombel --</option>
                                        <template x-for="c in getRombels(addForm.homeroom_grade, addForm.homeroom_major)" :key="c.id">
                                            <option :value="c.id" x-text="c.rombel === '-' ? 'Umum' : 'Rombel ' + c.rombel"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>
                            <input type="hidden" name="homeroom_class_id" :value="addForm.homeroom_class_id">
                        </div>
                    </template>

                    <!-- KASUS 2: JIKA PERAN = GURU BK (BISA MEWAKILI BANYAK KELAS: 3 DROPDOWN + TOMBOL TAMBAH) -->
                    <template x-if="addRoleName === 'Guru BK'">
                        <div class="p-3.5 rounded-xl bg-indigo-950/40 border border-indigo-500/40 space-y-3">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-bold text-indigo-300">
                                    Kelas yang Diwakili / Binaan Guru BK (Bisa Banyak Kelas):
                                </label>
                                <span class="text-[10px] text-slate-400 font-semibold" x-text="addForm.counselor_classes.length + ' kelas terpilih'"></span>
                            </div>

                            <!-- Pemilih 3 Kolom Bertingkat + Tombol Tambah -->
                            <div class="grid grid-cols-1 sm:grid-cols-4 gap-2 items-end">
                                <div>
                                    <label class="block text-[10px] text-slate-400 font-semibold mb-1">1. Tingkat</label>
                                    <select x-model="addForm.counselor_grade" @change="addForm.counselor_major = ''; addForm.counselor_class_id = ''" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-2.5 py-1.5 text-xs text-white focus:border-indigo-500 focus:outline-none">
                                        <option value="">-- Tingkat --</option>
                                        <option value="X">Kelas X</option>
                                        <option value="XI">Kelas XI</option>
                                        <option value="XII">Kelas XII</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] text-slate-400 font-semibold mb-1">2. Jurusan</label>
                                    <select x-model="addForm.counselor_major" @change="addForm.counselor_class_id = ''" :disabled="!addForm.counselor_grade" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-2.5 py-1.5 text-xs text-white focus:border-indigo-500 focus:outline-none disabled:opacity-50">
                                        <option value="">-- Jurusan --</option>
                                        <template x-for="m in getMajors(addForm.counselor_grade)" :key="m">
                                            <option :value="m" x-text="m"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] text-slate-400 font-semibold mb-1">3. Rombel</label>
                                    <select x-model="addForm.counselor_class_id" :disabled="!addForm.counselor_major" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-2.5 py-1.5 text-xs text-white focus:border-indigo-500 focus:outline-none disabled:opacity-50">
                                        <option value="">-- Rombel --</option>
                                        <template x-for="c in getRombels(addForm.counselor_grade, addForm.counselor_major)" :key="c.id">
                                            <option :value="c.id" x-text="c.rombel === '-' ? 'Umum' : 'Rombel ' + c.rombel"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <button type="button" 
                                            @click="addCounselorClass()" 
                                            :disabled="!addForm.counselor_class_id" 
                                            class="w-full py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white font-bold text-xs transition cursor-pointer flex items-center justify-center gap-1 shadow">
                                        <span>+ Tambah</span>
                                    </button>
                                </div>
                            </div>

                            <!-- List Chips / Badges Kelas yang Diwakili -->
                            <div class="p-2.5 rounded-xl bg-slate-900/80 border border-slate-800 space-y-1.5">
                                <span class="text-[10px] font-bold text-slate-400 block">Daftar Kelas Binaan yang Diwakili Guru BK Ini:</span>
                                <div class="flex flex-wrap gap-1.5 min-h-[32px] items-center">
                                    <template x-for="(cls, idx) in addForm.counselor_classes" :key="cls.id">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-indigo-950 text-indigo-200 border border-indigo-700/60 shadow-sm">
                                            <span x-text="cls.class_name"></span>
                                            <input type="hidden" name="counselor_class_ids[]" :value="cls.id">
                                            <button type="button" @click="removeCounselorClass(idx)" class="text-rose-400 hover:text-rose-300 font-bold ml-0.5 cursor-pointer" title="Hapus kelas ini">✕</button>
                                        </span>
                                    </template>
                                    <template x-if="addForm.counselor_classes.length === 0">
                                        <span class="text-slate-500 text-xs italic">Belum ada kelas yang ditambahkan. Pilih tingkat, jurusan, dan rombel lalu klik Tambah.</span>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>

                    <div class="flex justify-end gap-2 pt-3">
                        <button @click="addModalOpen = false" type="button" class="rounded-xl px-4 py-2 text-xs font-bold text-slate-400 hover:text-white hover:bg-slate-800 transition cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl scp-btn-gradient text-xs font-bold text-white shadow-md cursor-pointer transition">
                            Simpan Data Guru
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ============================================================= -->
        <!-- MODAL 4: IMPORT DATA GURU TERPADU (SESUAI FOTO KE-2)          -->
        <!-- ============================================================= -->
        <div x-show="importModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
            <div @click="importModalOpen = false" class="fixed inset-0 bg-black/70 backdrop-blur-sm"></div>
            <div class="relative w-full max-w-lg bg-[#0f172a] p-6 shadow-2xl border border-slate-800 space-y-4 my-auto max-h-[90vh] overflow-y-auto" style="border-radius: 20px;">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div>
                        <h3 class="text-base font-extrabold text-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                            Import Data Guru Terpadu (PDF / Excel / CSV)
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">Unggah berkas PDF daftar guru/SK/FET atau template spreadsheet</p>
                    </div>
                    <button @click="importModalOpen = false" class="text-xs font-bold text-slate-400 hover:text-white px-2 py-1 cursor-pointer">[Tutup]</button>
                </div>

                <!-- Petunjuk & Unduh Template -->
                <div class="p-3.5 rounded-xl bg-slate-900 border border-slate-800 space-y-2 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-slate-300">Format Berkas yang Didukung:</span>
                        <a href="{{ route('waka.teachers.template') }}" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-indigo-600/30 hover:bg-indigo-600 text-indigo-300 hover:text-white border border-indigo-500/30 font-bold transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Template Excel/CSV
                        </a>
                    </div>
                    <ul class="text-slate-400 text-[11px] space-y-1.5 list-disc list-inside">
                        <li><strong class="text-emerald-400">Dokumen Matriks Wali & BK:</strong> Format langsung: <code class="text-emerald-300 font-mono">KELAS, NAMA WALI KELAS, NAMA GURU BK</code>. Sistem otomatis menautkan wali kelas serta mengakumulasikan seluruh rombel binaan untuk Guru BK.</li>
                        <li><strong class="text-indigo-400">Dokumen Data Pegawai:</strong> Format: <code class="text-indigo-300 font-mono">NIP, NAMA, JABATAN, MAPEL, dll.</code> Sistem otomatis mengambil NIP & Nama guru, serta cerdas melewati staf non-guru (Tata Usaha, Keamanan, Kebersihan, dll).</li>
                        <li><strong class="text-amber-400">Dokumen PDF Resmi / SK / FET:</strong> PDF pembagian tugas mengajar atau jadwal FET. Nama dan gelar guru disinkronkan secara cerdas & fleksibel.</li>
                        <li><strong class="text-sky-400">Auto-Provisioning:</strong> Rombel kelas & jurusan yang belum terdaftar akan <span class="text-white font-bold">dibuat otomatis</span> ke sistem tanpa perlu input manual.</li>
                    </ul>
                </div>

                <form method="POST" action="{{ route('waka.teachers.import') }}" enctype="multipart/form-data" 
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
                                    <span x-text="selectedFiles.length + ' berkas guru dipilih untuk diimpor serentak:'"></span>
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
                        <p class="text-[10px] text-slate-500 mt-1 italic">
                            *Kata sandi awal akun baru otomatis disetel <code class="text-amber-300 font-bold">password123</code> (guru dapat mengubah kata sandi mandiri di profil).
                        </p>
                    </div>

                    <!-- Opsi Penanganan Data Duplikat / Guru yang Sudah Ada (Sesuai Foto ke-2) -->
                    <div class="p-3.5 rounded-xl bg-slate-900 border border-slate-800 space-y-2">
                        <label class="block text-[11px] font-bold text-slate-300">
                            Jika Ditemukan Data Guru yang Sudah Ada (Dobel):
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
                                    <span class="text-[10px] text-slate-400 leading-tight block mt-0.5">Lewati jika NIP atau Nama guru sudah terdaftar</span>
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
                            Mulai Import Data Guru
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>
