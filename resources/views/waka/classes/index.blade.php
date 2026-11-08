<x-app-layout>
    <div x-data="{ 
            addModalOpen: false, 
            editModalOpen: false, 
            viewModalOpen: false,
            activeClass: { id: '', class_name: '', homeroom_id: '', homeroom_name: '', users: [] },
            formTingkat: 'X',
            formJurusan: 'RPL',
            formRombel: '1'
         }" 
         class="space-y-6">
        
        <!-- Header & Action Bar -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 w-full">
            <div>
                <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">Master Data Perkelas</h1>
                <p class="text-xs font-semibold text-slate-400 mt-0.5">Kelola rombongan belajar per tingkat & jurusan, penugasan wali kelas, serta daftar siswa</p>
            </div>
            <div class="shrink-0">
                <button @click="addModalOpen = true" type="button" class="px-4 sm:px-5 py-2 sm:py-2.5 rounded-xl scp-btn-gradient text-xs sm:text-sm font-bold text-white shadow-md cursor-pointer transition whitespace-nowrap">
                    + Tambah Kelas Baru
                </button>
            </div>
        </div>

        <!-- 3 Statistik Ringkas -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="p-4 rounded-2xl bg-[#0f172a]/90 border border-slate-800 flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-400">Total Kelas / Rombel</span>
                    <h3 class="text-xl font-extrabold text-white mt-1">{{ $totalClasses }} Kelas</h3>
                </div>
                <div class="p-2.5 rounded-xl bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-[#0f172a]/90 border border-slate-800 flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-400">Total Siswa Aktif</span>
                    <h3 class="text-xl font-extrabold text-emerald-400 mt-1">{{ $totalStudents }} Siswa</h3>
                </div>
                <div class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-[#0f172a]/90 border border-slate-800 flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-400">Kelas Terisi Wali Kelas</span>
                    <h3 class="text-xl font-extrabold text-blue-400 mt-1">{{ $assignedHomerooms }} / {{ $totalClasses }}</h3>
                </div>
                <div class="p-2.5 rounded-xl bg-blue-500/10 text-blue-400 border border-blue-500/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                </div>
            </div>
        </div>

        <!-- Filter Bar Interaktif -->
        <div class="p-4 rounded-2xl bg-[#0f172a]/90 border border-slate-800 shadow-xl">
            <form method="GET" action="{{ route('waka.classes.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 text-xs">
                @if(request('per_page'))
                    <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                @endif
                <!-- Filter Tingkat -->
                <div>
                    <label class="block font-bold text-slate-400 mb-1">Tingkat Kelas</label>
                    <select name="grade" onchange="this.form.submit()" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white focus:border-indigo-500">
                        <option value="">Semua Tingkat</option>
                        <option value="X" {{ request('grade') === 'X' ? 'selected' : '' }}>Kelas 10 (X)</option>
                        <option value="XI" {{ request('grade') === 'XI' ? 'selected' : '' }}>Kelas 11 (XI)</option>
                        <option value="XII" {{ request('grade') === 'XII' ? 'selected' : '' }}>Kelas 12 (XII)</option>
                    </select>
                </div>

                <!-- Filter Jurusan -->
                <div>
                    <label class="block font-bold text-slate-400 mb-1">Jurusan</label>
                    <select name="major" onchange="this.form.submit()" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white focus:border-indigo-500">
                        <option value="">Semua Jurusan</option>
                        @foreach($majors as $m)
                            <option value="{{ $m->code }}" {{ request('major') === $m->code ? 'selected' : '' }}>
                                {{ $m->code }} - {{ $m->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Status Wali Kelas -->
                <div>
                    <label class="block font-bold text-slate-400 mb-1">Status Wali Kelas</label>
                    <select name="homeroom_status" onchange="this.form.submit()" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white focus:border-indigo-500">
                        <option value="">Semua Status</option>
                        <option value="has" {{ request('homeroom_status') === 'has' ? 'selected' : '' }}>Sudah Ada Wali Kelas</option>
                        <option value="none" {{ request('homeroom_status') === 'none' ? 'selected' : '' }}>Belum Ada Wali Kelas</option>
                    </select>
                </div>

                <!-- Cari Nama Kelas / Wali Kelas -->
                <div>
                    <label class="block font-bold text-slate-400 mb-1">Cari Kelas / Wali</label>
                    <div class="flex gap-2">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama kelas / wali..." class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white placeholder-slate-500 focus:border-indigo-500" />
                        <button type="submit" class="px-4 py-2 rounded-xl scp-btn-gradient text-white font-bold transition shadow-sm cursor-pointer">Cari</button>
                        @if(request()->hasAny(['grade', 'major', 'homeroom_status', 'search']))
                            <a href="{{ route('waka.classes.index') }}" class="px-2.5 py-2 rounded-xl bg-red-500/10 text-red-400 border border-red-500/20 hover:bg-red-500/20 transition flex items-center justify-center font-bold" title="Reset Filter">✕</a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        <!-- Tabel Kelas -->
        <div class="bg-[#0f172a]/90 backdrop-blur-md border border-slate-800 shadow-xl overflow-hidden rounded-2xl">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-800/80 text-sm">
                    <thead class="bg-slate-900/70">
                        <tr class="text-left text-xs font-bold text-slate-400 uppercase tracking-wider">
                            <th class="p-4">Nama Kelas</th>
                            <th class="p-4">Tingkat & Jurusan</th>
                            <th class="p-4">Wali Kelas</th>
                            <th class="p-4 text-center">Jumlah Siswa</th>
                            <th class="p-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @forelse ($classes as $class)
                            @php
                                $homeroomTeacher = $class->homeroomTeachers->first();
                            @endphp
                            <tr class="hover:bg-slate-800/30 transition">
                                <td class="p-4">
                                    <span class="font-extrabold text-white text-base">{{ $class->class_name }}</span>
                                </td>
                                <td class="p-4">
                                    <div class="flex items-center gap-1.5">
                                        <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-slate-800 text-slate-300 border border-slate-700">
                                            Tingkat {{ $class->grade }}
                                        </span>
                                        <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-indigo-950/60 text-indigo-300 border border-indigo-800/60">
                                            {{ $class->major_code }}
                                        </span>
                                        <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-slate-800 text-amber-400 border border-slate-700">
                                            Rombel {{ $class->rombel }}
                                        </span>
                                    </div>
                                </td>
                                <td class="p-4">
                                    @if($homeroomTeacher)
                                        <div class="font-bold text-slate-200">{{ $homeroomTeacher->name }}</div>
                                        <div class="text-[11px] text-slate-400">NIP: {{ $homeroomTeacher->nip ?? '-' }}</div>
                                    @else
                                        <span class="text-xs text-amber-400/80 italic font-semibold">Belum Ditentukan</span>
                                    @endif
                                </td>
                                <td class="p-4 text-center">
                                    <span class="inline-flex items-center justify-center rounded-full bg-indigo-950/60 px-3 py-1 text-xs font-bold text-indigo-300 border border-indigo-800/60">
                                        {{ $class->users_count }} Siswa
                                    </span>
                                </td>
                                <td class="p-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <!-- Tombol Detail -->
                                        <button @click="activeClass = { 
                                                    id: '{{ $class->id }}', 
                                                    class_name: '{{ $class->class_name }}', 
                                                    homeroom_name: '{{ $homeroomTeacher?->name ?? 'Belum Ditentukan' }}',
                                                    users: {{ json_encode($class->users) }}
                                                }; viewModalOpen = true" 
                                                type="button" 
                                                class="px-2.5 py-1 text-xs font-bold rounded-lg transition cursor-pointer btn-action-view shadow-sm"
                                                title="Lihat Detail Kelas">
                                            Detail
                                        </button>

                                        <!-- Tombol Edit -->
                                        <button @click="activeClass = { 
                                                    id: '{{ $class->id }}', 
                                                    class_name: '{{ $class->class_name }}', 
                                                    homeroom_id: '{{ $homeroomTeacher?->id ?? '' }}' 
                                                }; editModalOpen = true" 
                                                type="button" 
                                                class="px-2.5 py-1 text-xs font-bold rounded-lg transition cursor-pointer btn-action-edit shadow-sm"
                                                title="Edit Kelas & Wali Kelas">
                                            Edit
                                        </button>

                                        <!-- Tombol Hapus -->
                                        <form method="POST" action="{{ route('waka.classes.destroy', $class) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus kelas ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-2.5 py-1 text-xs font-bold rounded-lg transition cursor-pointer btn-action-delete shadow-sm" title="Hapus Kelas">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-8 text-center text-slate-500 text-sm">
                                    Belum ada data kelas yang sesuai dengan filter pencarian.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-table-pagination :paginator="$classes" />
        </div>

        <!-- ================= MODAL DETAIL KELAS & DAFTAR SISWA ================= -->
        <div x-show="viewModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div @click="viewModalOpen = false" class="fixed inset-0 bg-black/70 backdrop-blur-sm"></div>
            <div class="relative w-full max-w-xl bg-[#0f172a] p-6 shadow-2xl border border-slate-800 space-y-4 rounded-2xl">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div>
                        <h3 class="text-base font-bold text-white flex items-center gap-2">
                            <span>Daftar Siswa Kelas:</span>
                            <span class="text-indigo-400" x-text="activeClass.class_name"></span>
                        </h3>
                        <p class="text-xs text-slate-400">Wali Kelas: <span class="font-bold text-slate-200" x-text="activeClass.homeroom_name"></span></p>
                    </div>
                    <button @click="viewModalOpen = false" class="text-xs font-bold text-slate-400 hover:text-white px-2 py-1">[Tutup]</button>
                </div>

                <!-- Tabel Siswa di Kelas Ini -->
                <div class="max-h-80 overflow-y-auto rounded-xl border border-slate-800">
                    <table class="min-w-full divide-y divide-slate-800 text-xs">
                        <thead class="bg-slate-900 sticky top-0">
                            <tr class="text-left font-bold text-slate-400">
                                <th class="p-2.5">No</th>
                                <th class="p-2.5">NIS</th>
                                <th class="p-2.5">Nama Siswa</th>
                                <th class="p-2.5">Username / Email</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            <template x-if="activeClass.users && activeClass.users.length > 0">
                                <template x-for="(u, idx) in activeClass.users" :key="u.id">
                                    <tr class="hover:bg-slate-800/30">
                                        <td class="p-2.5 text-slate-500 font-mono" x-text="idx + 1"></td>
                                        <td class="p-2.5 font-mono text-indigo-300" x-text="u.nis || '-'"></td>
                                        <td class="p-2.5 font-bold text-white" x-text="u.name"></td>
                                        <td class="p-2.5 text-slate-400" x-text="u.username || u.email"></td>
                                    </tr>
                                </template>
                            </template>
                            <template x-if="!activeClass.users || activeClass.users.length === 0">
                                <tr>
                                    <td colspan="4" class="p-6 text-center text-slate-500 italic">
                                        Belum ada siswa yang terdaftar di kelas ini.
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center justify-between pt-2 text-xs">
                    <span class="text-slate-400 font-semibold" x-text="'Total Siswa: ' + (activeClass.users ? activeClass.users.length : 0) + ' orang'"></span>
                    <button @click="viewModalOpen = false" type="button" class="px-4 py-2 rounded-xl bg-slate-800 text-xs font-bold text-white hover:bg-slate-700 transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>

        <!-- ================= MODAL TAMBAH KELAS BARU ================= -->
        <div x-show="addModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div @click="addModalOpen = false" class="fixed inset-0 bg-black/70 backdrop-blur-sm"></div>
            <div class="relative w-full max-w-md bg-[#0f172a] p-6 shadow-2xl border border-slate-800 space-y-4 rounded-2xl">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-white">Tambah Kelas Baru</h3>
                    <button @click="addModalOpen = false" class="text-xs font-bold text-slate-400 hover:text-white px-2 py-1">[Tutup]</button>
                </div>

                <form method="POST" action="{{ route('waka.classes.store') }}" class="space-y-4 text-xs">
                    @csrf
                    <!-- Pembagian Cepat: Tingkat, Jurusan, Rombel -->
                    <div class="p-3 bg-slate-900/80 rounded-xl border border-slate-800 space-y-3">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Format Kelas Otomatis</span>
                        <div class="grid grid-cols-3 gap-2">
                            <div>
                                <label class="block font-bold text-slate-300 mb-1">Tingkat</label>
                                <select x-model="formTingkat" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-2.5 py-2 text-white">
                                    <option value="X">Kelas 10 (X)</option>
                                    <option value="XI">Kelas 11 (XI)</option>
                                    <option value="XII">Kelas 12 (XII)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block font-bold text-slate-300 mb-1">Jurusan</label>
                                <select x-model="formJurusan" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-2.5 py-2 text-white">
                                    @foreach($majors as $m)
                                        <option value="{{ $m->code }}">{{ $m->code }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block font-bold text-slate-300 mb-1">Rombel</label>
                                <select x-model="formRombel" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-2.5 py-2 text-white">
                                    <option value="1">Rombel 1</option>
                                    <option value="2">Rombel 2</option>
                                    <option value="3">Rombel 3</option>
                                    <option value="4">Rombel 4</option>
                                    <option value="5">Rombel 5</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-300 mb-1">Hasil Nama Kelas</label>
                        <input type="text" name="class_name" :value="formTingkat + ' ' + formJurusan + ' ' + formRombel" required class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white font-extrabold focus:border-indigo-500" />
                        <p class="text-[11px] text-slate-500 mt-1">Anda juga dapat mengedit nama kelas secara bebas jika memiliki format tersendiri.</p>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button @click="addModalOpen = false" type="button" class="px-4 py-2 rounded-xl text-slate-400 hover:bg-slate-800 hover:text-white transition cursor-pointer">Batal</button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl scp-btn-gradient font-bold text-white shadow-md cursor-pointer transition">Simpan Kelas</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ================= MODAL EDIT KELAS & WALI KELAS ================= -->
        <div x-show="editModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div @click="editModalOpen = false" class="fixed inset-0 bg-black/70 backdrop-blur-sm"></div>
            <div class="relative w-full max-w-md bg-[#0f172a] p-6 shadow-2xl border border-slate-800 space-y-4 rounded-2xl">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-white">Edit Kelas & Wali Kelas</h3>
                    <button @click="editModalOpen = false" class="text-xs font-bold text-slate-400 hover:text-white px-2 py-1">[Tutup]</button>
                </div>

                <form :action="'{{ url('/waka/classes') }}/' + activeClass.id" method="POST" class="space-y-3.5 text-xs">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block font-bold text-slate-300 mb-1">Nama Kelas</label>
                        <input type="text" name="class_name" x-model="activeClass.class_name" required class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white focus:border-indigo-500 font-bold" />
                    </div>

                    <div>
                        <label class="block font-bold text-slate-300 mb-1">Tugaskan Wali Kelas</label>
                        <select name="homeroom_teacher_id" x-model="activeClass.homeroom_id" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white focus:border-indigo-500">
                            <option value="">-- Belum Ada Wali Kelas --</option>
                            @foreach($teachers as $t)
                                <option value="{{ $t->id }}">{{ $t->name }} (NIP: {{ $t->nip ?? '-' }})</option>
                            @endforeach
                        </select>
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
