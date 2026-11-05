<x-app-layout>
        <div x-data="{ 
            viewModalOpen: false, 
            editModalOpen: false,
            activeStudent: { id: '', name: '', nis: '', username: '', email: '', phone: '', address: '', class_id: '', class_name: '', student_status: 'Aktif' },
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
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 w-full">
            <div>
                <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">Master Tingkat Kelas & Kelulusan</h1>
                <p class="text-xs font-semibold text-slate-400 mt-0.5">Kelola kenaikan jenjang kelas 10, 11, 12 serta penetapan kelulusan siswa menjadi alumni</p>
            </div>

                        <!-- Tombol Kenaikan / Kelulusan Berdasarkan Checklist -->
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
                            onclick="return confirm('Proses siswa yang dicentang lulus menjadi Alumni, dan siswa yang TIDAK dicentang tinggal di Kelas 12?')" 
                            class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-xs font-extrabold text-white shadow-lg flex items-center gap-2 cursor-pointer transition">
                        <span>Proses Kelulusan Siswa (Siswa Tercentang)</span>
                    </button>
                @endif
            </div>
        </div>

        <!-- 4 Kartu Tab Tingkat (Bersih Tanpa Tulisan Fase) -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
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
        <div class="p-4 rounded-2xl bg-[#0f172a]/90 border border-slate-800 shadow-xl">
            <form method="GET" action="{{ route('waka.grades.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 text-xs">
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
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Ketik nama atau NIS..." class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white placeholder-slate-500 focus:border-indigo-500" />
                        <button type="submit" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold transition">Cari</button>
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
                                <th class="p-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            @forelse ($students as $s)
                                <tr class="hover:bg-slate-800/30 transition">
                                    @if($activeTab !== 'lulus')
                                        <td class="p-4 text-center">
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
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                                                ✓ Lulus (Alumni)
                                            </span>
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
                                    <td class="p-4 text-center">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <!-- Tombol Detail -->
                                            <button @click="activeStudent = {
                                                        id: '{{ $s->id }}',
                                                        name: '{{ $s->name }}',
                                                        nis: '{{ $s->nis }}',
                                                        username: '{{ $s->username }}',
                                                        email: '{{ $s->email }}',
                                                        phone: '{{ $s->phone }}',
                                                        address: '{{ $s->address }}',
                                                        class_name: '{{ $s->schoolClass?->class_name ?? '-' }}',
                                                        student_status: '{{ $s->student_status }}'
                                                    }; viewModalOpen = true" 
                                                    type="button" 
                                                    class="px-2.5 py-1 text-xs font-bold bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 rounded-lg transition cursor-pointer">
                                                Detail
                                            </button>

                                            <!-- Tombol Edit Cepat -->
                                            <button @click="activeStudent = {
                                                        id: '{{ $s->id }}',
                                                        name: '{{ $s->name }}',
                                                        nis: '{{ $s->nis }}',
                                                        class_id: '{{ $s->class_id }}',
                                                        student_status: '{{ $s->student_status }}'
                                                    }; editModalOpen = true" 
                                                    type="button" 
                                                    class="px-2.5 py-1 text-xs font-bold bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 border border-amber-500/30 rounded-lg transition cursor-pointer">
                                                Edit
                                            </button>

                                            <!-- Tombol Hapus -->
                                            <button type="submit" form="deleteForm_{{ $s->id }}" 
                                                    class="px-2.5 py-1 text-xs font-bold text-red-400 hover:text-white hover:bg-red-600/80 border border-red-500/30 rounded-lg transition cursor-pointer">
                                                Hapus
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $activeTab !== 'lulus' ? '6' : '5' }}" class="p-8 text-center text-slate-500 text-sm">
                                        Tidak ada data siswa pada filter ini.
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
        </form>

        <!-- Hidden Delete Forms untuk setiap siswa (agar tidak merusak form tabel checklist) -->
        @foreach ($students as $s)
            <form id="deleteForm_{{ $s->id }}" method="POST" action="{{ route('waka.students.destroy', $s->id) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data siswa ini?')" style="display: none;">
                @csrf
                @method('DELETE')
            </form>
        @endforeach

        <!-- ================= MODAL DETAIL SISWA ================= -->
        <div x-show="viewModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div @click="viewModalOpen = false" class="fixed inset-0 bg-black/70 backdrop-blur-sm"></div>
            <div class="relative w-full max-w-md bg-[#0f172a] p-6 shadow-2xl border border-slate-800 space-y-4 rounded-2xl">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-white">Detail Siswa</h3>
                    <button @click="viewModalOpen = false" class="text-xs font-bold text-slate-400 hover:text-white px-2 py-1">[Tutup]</button>
                </div>

                <div class="space-y-3 text-xs">
                    <div class="p-3 bg-slate-900/80 rounded-xl border border-slate-800">
                        <span class="text-slate-400 block mb-0.5">Nama Siswa:</span>
                        <span class="text-sm font-extrabold text-white" x-text="activeStudent.name"></span>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="p-3 bg-slate-900/80 rounded-xl border border-slate-800">
                            <span class="text-slate-400 block mb-0.5">NIS:</span>
                            <span class="font-mono font-bold text-indigo-300" x-text="activeStudent.nis || '-'"></span>
                        </div>
                        <div class="p-3 bg-slate-900/80 rounded-xl border border-slate-800">
                            <span class="text-slate-400 block mb-0.5">Kelas:</span>
                            <span class="font-bold text-slate-200" x-text="activeStudent.class_name"></span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="p-3 bg-slate-900/80 rounded-xl border border-slate-800">
                            <span class="text-slate-400 block mb-0.5">Status:</span>
                            <span class="font-bold text-emerald-400" x-text="activeStudent.student_status"></span>
                        </div>
                        <div class="p-3 bg-slate-900/80 rounded-xl border border-slate-800">
                            <span class="text-slate-400 block mb-0.5">Telepon:</span>
                            <span class="font-bold text-slate-200" x-text="activeStudent.phone || '-'"></span>
                        </div>
                    </div>

                    <div class="p-3 bg-slate-900/80 rounded-xl border border-slate-800">
                        <span class="text-slate-400 block mb-0.5">Alamat:</span>
                        <span class="text-slate-200" x-text="activeStudent.address || 'Belum diisi'"></span>
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button @click="viewModalOpen = false" type="button" class="px-4 py-2 rounded-xl bg-slate-800 text-xs font-bold text-white hover:bg-slate-700 transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>

        <!-- ================= MODAL EDIT SISWA & STATUS ================= -->
        <div x-show="editModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div @click="editModalOpen = false" class="fixed inset-0 bg-black/70 backdrop-blur-sm"></div>
            <div class="relative w-full max-w-md bg-[#0f172a] p-6 shadow-2xl border border-slate-800 space-y-4 rounded-2xl">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-white">Edit Siswa & Kelulusan</h3>
                    <button @click="editModalOpen = false" class="text-xs font-bold text-slate-400 hover:text-white px-2 py-1">[Tutup]</button>
                </div>

                <form :action="'{{ url('/waka/grades') }}/' + activeStudent.id" method="POST" class="space-y-3.5 text-xs">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block font-bold text-slate-300 mb-1">Nama Siswa</label>
                        <input type="text" name="name" x-model="activeStudent.name" required class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white focus:border-indigo-500 font-bold" />
                    </div>

                    <div>
                        <label class="block font-bold text-slate-300 mb-1">NIS Siswa</label>
                        <input type="text" name="nis" x-model="activeStudent.nis" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white focus:border-indigo-500" />
                    </div>

                    <div>
                        <label class="block font-bold text-slate-300 mb-1">Pilihan Kelas</label>
                        <select name="class_id" x-model="activeStudent.class_id" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white focus:border-indigo-500">
                            <option value="">-- Tanpa Kelas (Alumni) --</option>
                            @foreach($allClasses as $ac)
                                <option value="{{ $ac->id }}">{{ $ac->class_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-300 mb-1">Status Keaktifan</label>
                        <select name="student_status" x-model="activeStudent.student_status" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white focus:border-indigo-500">
                            <option value="Aktif">Aktif Belajar</option>
                            <option value="Lulus">Lulus (Alumni)</option>
                        </select>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button @click="editModalOpen = false" type="button" class="px-4 py-2 rounded-xl text-slate-400 hover:bg-slate-800 hover:text-white transition cursor-pointer">Batal</button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl scp-btn-gradient font-bold text-white shadow-md cursor-pointer transition">Simpan</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>
