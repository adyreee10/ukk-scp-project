<x-app-layout>
    <div x-data="{ 
            addModalOpen: false, 
            editModalOpen: false, 
            importModalOpen: false,
            deleteSelectedModalOpen: false,
            deleteAllModalOpen: false,
            selectedSchedules: [],
            selectAll: false,
            editSchedule: { id: '', subject: '', teacher_id: '', class_id: '', room_id: '', day_of_week: '', start_time: '', end_time: '', cycle_type: '' },

            toggleSelectAll(allIds) {
                if (this.selectAll) {
                    this.selectedSchedules = [];
                    this.selectAll = false;
                } else {
                    this.selectedSchedules = allIds.map(String);
                    this.selectAll = true;
                }
            }
         }" 
         class="space-y-6">

        
        <!-- Header & Action Bar -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 w-full">
            <div class="text-left">
                <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">Jadwal Mengajar</h1>
                <p class="text-xs font-semibold text-slate-400 mt-0.5">Input dan kelola jadwal pelajaran mandiri untuk seluruh guru dan kelas</p>
            </div>
            <div class="flex items-center gap-2 shrink-0 flex-wrap">
                @if($config->is_schedule_locked)
                    <button type="button" 
                            onclick="alert('Jadwal saat ini sedang TERKUNCI. Buka kunci siklus blok terlebih dahulu untuk menambah, mengimpor, atau menghapus jadwal!')" 
                            class="px-4 sm:px-5 py-2 sm:py-2.5 rounded-xl btn-schedule-locked text-xs sm:text-sm font-black whitespace-nowrap cursor-pointer transition inline-flex items-center gap-2 tracking-wide uppercase">
                        <svg class="w-4 h-4 shrink-0 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <span>Jadwal Terkunci</span>
                    </button>
                @else
                    <!-- Tombol Hapus Semua Jadwal (Merah Solid & Kontras di Mode Terang Maupun Gelap) -->
                    <button @click="deleteAllModalOpen = true" type="button" class="px-3.5 sm:px-4 py-2 sm:py-2.5 rounded-xl bg-red-600 hover:bg-red-700 active:bg-red-800 text-white font-bold text-xs sm:text-sm shadow-md shadow-red-950/30 cursor-pointer transition flex items-center gap-1.5 whitespace-nowrap">
                        <svg class="w-4 h-4 shrink-0 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        <span class="text-white">Hapus Semua Jadwal</span>
                    </button>
                    <button @click="importModalOpen = true" type="button" class="px-3.5 sm:px-4 py-2 sm:py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-xs sm:text-sm font-bold text-white shadow-md shadow-emerald-950/40 cursor-pointer transition flex items-center gap-1.5">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        <span>Import Excel / Dokumen</span>
                    </button>
                    <button @click="addModalOpen = true" type="button" class="px-4 sm:px-5 py-2 sm:py-2.5 rounded-xl scp-btn-gradient text-xs sm:text-sm font-bold text-white shadow-md cursor-pointer transition whitespace-nowrap">
                        + Tambah Jadwal Baru
                    </button>
                @endif
            </div>
            
        </div>

        <!-- Filter Bar (Dipecah Tingkat, Jurusan, Rombel Sesuai Permintaan) -->
        <div class="bg-[#0f172a]/90 backdrop-blur-md p-4 sm:p-5 border border-slate-800 shadow-xl" style="border-radius: 15px;">
            <form method="GET" action="{{ route('waka.schedules.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-7 gap-2.5 sm:gap-3 items-end">
                @if(request('per_page'))
                    <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                @endif
                <!-- 1. Putaran Siklus -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 mb-1">Siklus</label>
                    <select name="cycle_type" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-xs font-bold text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        <option value="Putaran 1" {{ request('cycle_type', $config->active_cycle) == 'Putaran 1' ? 'selected' : '' }}>Putaran 1</option>
                        <option value="Putaran 2" {{ request('cycle_type', $config->active_cycle) == 'Putaran 2' ? 'selected' : '' }}>Putaran 2</option>
                    </select>
                </div>

                <!-- 2. Hari -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 mb-1">Hari</label>
                    <select name="day_of_week" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-xs text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        <option value="">Semua Hari</option>
                        @foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'] as $d)
                            <option value="{{ $d }}" {{ request('day_of_week') == $d ? 'selected' : '' }}>{{ $d }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- 3. Tingkat Kelas -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 mb-1">Tingkat</label>
                    <select name="grade" class="w-full rounded-xl border border-slate-700 bg-slate-900 pl-3 pr-7 py-2 text-xs font-bold text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        <option value="all" {{ request('grade', 'all') === 'all' ? 'selected' : '' }}>Semua Tingkat</option>
                        @foreach($availableGrades as $g)
                            <option value="{{ $g }}" {{ request('grade') === $g ? 'selected' : '' }}>Kelas {{ $g }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- 4. Jurusan -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 mb-1">Jurusan</label>
                    <select name="major" class="w-full rounded-xl border border-slate-700 bg-slate-900 pl-3 pr-7 py-2 text-xs font-bold text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        <option value="all" {{ request('major', 'all') === 'all' ? 'selected' : '' }}>Semua Jurusan</option>
                        @foreach($availableMajors as $m)
                            <option value="{{ $m }}" {{ request('major') === $m ? 'selected' : '' }}>{{ $m }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- 5. Rombel -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 mb-1">Rombel</label>
                    <select name="rombel" class="w-full rounded-xl border border-slate-700 bg-slate-900 pl-3 pr-7 py-2 text-xs font-bold text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        <option value="all" {{ request('rombel', 'all') === 'all' ? 'selected' : '' }}>Semua Rombel</option>
                        @foreach($availableRombels as $r)
                            <option value="{{ $r }}" {{ request('rombel') === (string)$r ? 'selected' : '' }}>Rombel {{ $r }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- 6. Guru Pengajar -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 mb-1">Guru</label>
                    <select name="teacher_id" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-xs text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        <option value="">Semua Guru</option>
                        @foreach($teachers as $t)
                            <option value="{{ $t->id }}" {{ request('teacher_id') == $t->id ? 'selected' : '' }}>
                                {{ $t->name }} {{ $t->role?->role_name === 'Guru BK' ? '(BK)' : ($t->role?->role_name === 'Wali Kelas' ? '(Walas)' : '') }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- 7. Tombol Filter & Reset -->
                <div class="flex items-center gap-1.5">
                    <button type="submit" class="flex-1 rounded-xl scp-btn-gradient px-4 py-2 text-xs font-bold text-white transition shadow-sm cursor-pointer text-center">
                        Filter
                    </button>
                    @if(request()->hasAny(['day_of_week', 'grade', 'major', 'rombel', 'class_id', 'teacher_id']))
                        <a href="{{ route('waka.schedules.index') }}" class="px-2.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-bold transition flex items-center justify-center" title="Reset Semua Filter">
                            ✕
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- BAR AKSI SELEKSI JADWAL TERPILIH (BULK ACTION BAR) -->
        <div x-show="selectedSchedules.length > 0" 
             x-transition
             class="p-3.5 px-5 rounded-2xl bg-indigo-950/90 border border-indigo-500/50 shadow-2xl flex flex-col sm:flex-row items-center justify-between gap-3 text-white">
            <div class="flex items-center gap-2.5">
                <span class="flex h-3 w-3 rounded-full bg-rose-500 animate-pulse"></span>
                <span class="text-xs sm:text-sm font-bold">
                    <span x-text="selectedSchedules.length" class="text-indigo-300 font-black text-base"></span> jadwal dipilih dari halaman ini
                </span>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" @click="selectedSchedules = []; selectAll = false" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-800 hover:bg-slate-700 text-slate-300 transition cursor-pointer">
                    Batal Pilih
                </button>
                <button type="button" 
                        @if($config->is_schedule_locked)
                            onclick="alert('Jadwal saat ini sedang TERKUNCI.')"
                        @else
                            @click="deleteSelectedModalOpen = true" 
                        @endif
                        class="px-4 py-2 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-500 text-white shadow-md shadow-rose-950/60 transition flex items-center gap-1.5 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    <span>Hapus (<span x-text="selectedSchedules.length"></span>) Jadwal Terpilih</span>
                </button>
            </div>
        </div>

        <!-- Tabel Jadwal (Gelap Keabuan) -->
        <div class="bg-[#0f172a]/90 backdrop-blur-md border border-slate-800 shadow-xl overflow-hidden" style="border-radius: 15px;">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-800/80 text-sm">
                    <thead class="bg-slate-900/70">
                        <tr class="text-left text-xs font-bold text-slate-400 uppercase tracking-wider">
                            <th class="p-4 text-center w-10">
                                <input type="checkbox" 
                                       @change="toggleSelectAll([{{ $schedules->pluck('id')->implode(',') }}])" 
                                       :checked="selectAll && selectedSchedules.length > 0"
                                       class="rounded border-slate-700 bg-slate-900 text-indigo-600 focus:ring-indigo-500/30 cursor-pointer w-4 h-4"
                                       title="Pilih Semua Jadwal di Halaman Ini">
                            </th>
                            <th class="p-4">Hari & Jam</th>
                            <th class="p-4">Mata Pelajaran</th>
                            <th class="p-4">Guru Pengampu</th>
                            <th class="p-4">Kelas & Ruang</th>
                            <th class="p-4 text-center">Siklus</th>
                            <th class="p-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @forelse ($schedules as $item)
                            <tr class="hover:bg-slate-800/30 transition">
                                <!-- Checkbox Seleksi -->
                                <td class="p-4 text-center">
                                    <input type="checkbox" 
                                           value="{{ $item->id }}" 
                                           x-model="selectedSchedules"
                                           class="rounded border-slate-700 bg-slate-900 text-indigo-600 focus:ring-indigo-500/30 cursor-pointer w-4 h-4">
                                </td>
                                <td class="p-4">
                                    <p class="font-bold text-white">{{ $item->day_of_week }}</p>
                                    <p class="text-xs text-indigo-400 font-semibold">{{ $item->period_label }}</p>
                                </td>
                                <td class="p-4 font-bold text-white">{{ $item->subject }}</td>
                                <td class="p-4 text-slate-300">
                                    <span class="font-medium text-slate-200">{{ $item->teacher?->name ?? '-' }}</span>
                                </td>
                                <td class="p-4 text-xs font-medium text-slate-300">
                                    <span class="font-bold text-indigo-400">{{ $item->schoolClass?->class_name }}</span>
                                    <span class="text-slate-500"> • </span>
                                    <span>{{ $item->room?->room_name }}</span>
                                </td>
                                <td class="p-4 text-center">
                                    <span class="inline-block rounded-md bg-slate-800 px-2.5 py-1 text-xs font-bold text-slate-300 border border-slate-700">
                                        {{ $item->cycle_type }}
                                    </span>
                                </td>
                                <td class="p-4 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">

                                        <!-- Tombol Edit -->

                                        @if($config->is_schedule_locked)
                                            <button type="button" onclick="alert('Jadwal sedang TERKUNCI. Buka kunci siklus blok untuk mengedit!')" class="px-2.5 py-1 text-xs font-bold text-slate-500 bg-slate-800/60 rounded-lg cursor-not-allowed">
                                                Edit
                                            </button>
                                        @else
                                            <button type="button" 
                                                    @click="editSchedule = { 
                                                        id: '{{ $item->id }}', 
                                                        subject: '{{ addslashes($item->subject) }}', 
                                                        teacher_id: '{{ $item->teacher_id }}', 
                                                        class_id: '{{ $item->class_id }}', 
                                                        room_id: '{{ $item->room_id }}', 
                                                        day_of_week: '{{ $item->day_of_week }}', 
                                                        start_time: '{{ substr($item->start_time, 0, 5) }}', 
                                                        end_time: '{{ substr($item->end_time, 0, 5) }}', 
                                                        cycle_type: '{{ $item->cycle_type }}' 
                                                    }; editModalOpen = true;"
                                                    class="px-2.5 py-1 text-xs font-bold rounded-lg transition cursor-pointer btn-action-edit shadow-sm"
                                                    title="Edit Jadwal">
                                                Edit
                                            </button>
                                        @endif

                                        <!-- Tombol Hapus -->
                                        @if(!$config->is_schedule_locked)
                                            <form method="POST" action="{{ route('waka.schedules.destroy', $item) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus jadwal ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="px-2.5 py-1 text-xs font-bold rounded-lg transition cursor-pointer btn-action-delete shadow-sm" title="Hapus Jadwal">
                                                    Hapus
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-8 text-center text-slate-500 text-sm">
                                    Belum ada data jadwal pelajaran pada filter ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-table-pagination :paginator="$schedules" />
        </div>

        <!-- Modal Tambah Jadwal Pelajaran -->
        <div x-show="addModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
            <div @click="addModalOpen = false" class="fixed inset-0 bg-black/70 backdrop-blur-sm"></div>
            
            <div class="relative w-full max-w-lg bg-[#0f172a] p-6 sm:p-8 shadow-2xl border border-slate-800 space-y-4 my-auto max-h-[90vh] overflow-y-auto" style="border-radius: 20px;">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-lg font-bold text-white">Tambah Jadwal Mengajar</h3>
                    <button @click="addModalOpen = false" class="text-xs font-bold text-slate-400 hover:text-white px-2 py-1">
                        [Tutup]
                    </button>
                </div>

                <form method="POST" action="{{ route('waka.schedules.store') }}" class="space-y-3.5">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Mata Pelajaran</label>
                        <input type="text" name="subject" required class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" placeholder="Contoh: Pemrograman Berorientasi Objek" />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Guru Pengampu</label>
                        <select name="teacher_id" required class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2 text-sm text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                            <option value="">-- Pilih Guru --</option>
                            @foreach($teachers as $teacher)
                                <option value="{{ $teacher->id }}">
                                    {{ $teacher->name }} {{ $teacher->role?->role_name === 'Guru BK' ? '(Guru BK)' : ($teacher->role?->role_name === 'Wali Kelas' ? '(Wali Kelas)' : '') }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Kelas</label>
                            <select name="class_id" required class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-xs text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                                <option value="">-- Pilih Kelas --</option>
                                @foreach($classes as $c)
                                    <option value="{{ $c->id }}">{{ $c->class_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Ruangan</label>
                            <select name="room_id" required class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-xs text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                                <option value="">-- Pilih Ruang --</option>
                                @foreach($rooms as $r)
                                    <option value="{{ $r->id }}">{{ $r->room_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                                        <div x-data="{ addDay: 'Senin' }" class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Hari</label>
                            <select name="day_of_week" x-model="addDay" required class="w-full rounded-xl border border-slate-700 bg-slate-900 px-2.5 py-2 text-xs text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                                @foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'] as $d)
                                    <option value="{{ $d }}">{{ $d }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Jam Mulai</label>
                            <select name="start_time" required class="w-full rounded-xl border border-slate-700 bg-slate-900 px-2.5 py-2 text-xs text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                                <option value="01:00">Jam ke - 1</option>
                                <option value="02:00">Jam ke - 2</option>
                                <option value="03:00">Jam ke - 3</option>
                                <option value="04:00">Jam ke - 4</option>
                                <option value="05:00">Jam ke - 5</option>
                                <option value="06:00">Jam ke - 6</option>
                                <option value="07:00">Jam ke - 7</option>
                                <option value="08:00">Jam ke - 8</option>
                                <option value="09:00" x-show="addDay !== 'Jumat'">Jam ke - 9</option>
                                <option value="10:00" x-show="addDay !== 'Jumat'">Jam ke - 10</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Jam Selesai</label>
                            <select name="end_time" required class="w-full rounded-xl border border-slate-700 bg-slate-900 px-2.5 py-2 text-xs text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                                <option value="01:00">Jam ke - 1</option>
                                <option value="02:00">Jam ke - 2</option>
                                <option value="03:00">Jam ke - 3</option>
                                <option value="04:00">Jam ke - 4</option>
                                <option value="05:00">Jam ke - 5</option>
                                <option value="06:00">Jam ke - 6</option>
                                <option value="07:00">Jam ke - 7</option>
                                <option value="08:00">Jam ke - 8</option>
                                <option value="09:00" x-show="addDay !== 'Jumat'">Jam ke - 9</option>
                                <option value="10:00" x-show="addDay !== 'Jumat'">Jam ke - 10</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Siklus Putaran</label>
                        <select name="cycle_type" required class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2 text-sm text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                            <option value="Putaran 1" {{ $config->active_cycle == 'Putaran 1' ? 'selected' : '' }}>Putaran 1</option>
                            <option value="Putaran 2" {{ $config->active_cycle == 'Putaran 2' ? 'selected' : '' }}>Putaran 2</option>
                        </select>
                    </div>

                    <div class="flex justify-end gap-2 pt-3">
                        <button @click="addModalOpen = false" type="button" class="rounded-xl px-4 py-2 text-xs font-bold text-slate-400 hover:bg-slate-800 hover:text-white transition cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl scp-btn-gradient text-xs font-bold text-white shadow-md cursor-pointer transition">
                            Simpan Jadwal
                        </button>
                    </div>
                </form>
            </div>
        </div>
            <!-- Modal Edit Jadwal Pelajaran -->
        <div x-show="editModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
            <div @click="editModalOpen = false" class="fixed inset-0 bg-black/70 backdrop-blur-sm"></div>
            
            <div class="relative w-full max-w-lg bg-[#0f172a] p-6 sm:p-8 shadow-2xl border border-slate-800 space-y-4 my-auto max-h-[90vh] overflow-y-auto" style="border-radius: 20px;">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-lg font-bold text-white">Edit Jadwal Mengajar</h3>
                    <button @click="editModalOpen = false" class="text-xs font-bold text-slate-400 hover:text-white px-2 py-1">
                        [Tutup]
                    </button>
                </div>

                <form method="POST" :action="'/waka/schedules/' + editSchedule.id" class="space-y-3.5">
                    @csrf
                    @method('PUT')
                    
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Mata Pelajaran</label>
                        <input type="text" name="subject" x-model="editSchedule.subject" required class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2 text-sm text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" />
                    </div>

                    <div> 
                        <label class="block text-xs font-bold text-slate-300 mb-1">Guru Pengampu</label>
                        <select name="teacher_id" x-model="editSchedule.teacher_id" required class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2 text-sm text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                            @foreach($teachers as $teacher)
                                <option value="{{ $teacher->id }}">
                                    {{ $teacher->name }} {{ $teacher->role?->role_name === 'Guru BK' ? '(Guru BK)' : ($teacher->role?->role_name === 'Wali Kelas' ? '(Wali Kelas)' : '') }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Kelas</label>
                            <select name="class_id" x-model="editSchedule.class_id" required class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-xs text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                                @foreach($classes as $c)
                                    <option value="{{ $c->id }}">{{ $c->class_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Ruangan</label>
                            <select name="room_id" x-model="editSchedule.room_id" required class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-xs text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                                @foreach($rooms as $r)
                                    <option value="{{ $r->id }}">{{ $r->room_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Hari</label>
                            <select name="day_of_week" x-model="editSchedule.day_of_week" required class="w-full rounded-xl border border-slate-700 bg-slate-900 px-2.5 py-2 text-xs text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                                @foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'] as $d)
                                    <option value="{{ $d }}">{{ $d }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Jam Mulai</label>
                            <select name="start_time" x-model="editSchedule.start_time" required class="w-full rounded-xl border border-slate-700 bg-slate-900 px-2.5 py-2 text-xs text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                                <option value="01:00">Jam ke - 1</option>
                                <option value="02:00">Jam ke - 2</option>
                                <option value="03:00">Jam ke - 3</option>
                                <option value="04:00">Jam ke - 4</option>
                                <option value="05:00">Jam ke - 5</option>
                                <option value="06:00">Jam ke - 6</option>
                                <option value="07:00">Jam ke - 7</option>
                                <option value="08:00">Jam ke - 8</option>
                                <option value="09:00" x-show="editSchedule.day_of_week !== 'Jumat'">Jam ke - 9</option>
                                <option value="10:00" x-show="editSchedule.day_of_week !== 'Jumat'">Jam ke - 10</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Jam Selesai</label>
                            <select name="end_time" x-model="editSchedule.end_time" required class="w-full rounded-xl border border-slate-700 bg-slate-900 px-2.5 py-2 text-xs text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                                <option value="01:00">Jam ke - 1</option>
                                <option value="02:00">Jam ke - 2</option>
                                <option value="03:00">Jam ke - 3</option>
                                <option value="04:00">Jam ke - 4</option>
                                <option value="05:00">Jam ke - 5</option>
                                <option value="06:00">Jam ke - 6</option>
                                <option value="07:00">Jam ke - 7</option>
                                <option value="08:00">Jam ke - 8</option>
                                <option value="09:00" x-show="editSchedule.day_of_week !== 'Jumat'">Jam ke - 9</option>
                                <option value="10:00" x-show="editSchedule.day_of_week !== 'Jumat'">Jam ke - 10</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Siklus Putaran</label>
                        <select name="cycle_type" x-model="editSchedule.cycle_type" required class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2 text-sm text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                            <option value="Putaran 1">Putaran 1</option>
                            <option value="Putaran 2">Putaran 2</option>
                        </select>
                    </div>

                    <div class="flex justify-end gap-2 pt-3">
                        <button @click="editModalOpen = false" type="button" class="rounded-xl px-4 py-2 text-xs font-bold text-slate-400 hover:bg-slate-800 hover:text-white transition cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl scp-btn-gradient text-xs font-bold text-white shadow-md cursor-pointer transition">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ================= MODAL IMPORT TERPADU JADWAL MENGAJAR ================= -->
        <div x-show="importModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
            <div @click="importModalOpen = false" class="fixed inset-0 bg-black/70 backdrop-blur-sm"></div>
            <div class="relative w-full max-w-lg bg-[#0f172a] p-6 shadow-2xl border border-slate-800 space-y-4 my-auto max-h-[90vh] overflow-y-auto" style="border-radius: 20px;">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div>
                        <h3 class="text-base font-extrabold text-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                            Import Jadwal Terpadu (PDF FET / HTML / Excel)
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">Unggah berkas PDF dari software FET sekolah, HTML, atau template spreadsheet</p>
                    </div>
                    <button @click="importModalOpen = false" class="text-xs font-bold text-slate-400 hover:text-white px-2 py-1 cursor-pointer">[Tutup]</button>
                </div>

                <!-- Petunjuk & Fitur Auto-Provisioning -->
                <div class="p-3.5 rounded-xl bg-slate-900 border border-slate-800 space-y-2 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-slate-300">Format Berkas yang Didukung:</span>
                        <a href="{{ route('waka.schedules.template') }}" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-indigo-600/30 hover:bg-indigo-600 text-indigo-300 hover:text-white border border-indigo-500/30 font-bold transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Template Excel/CSV
                        </a>
                    </div>
                    <ul class="text-slate-400 text-[11px] space-y-1 list-disc list-inside">
                        <li><strong class="text-indigo-400">PDF FET Timetable:</strong> Berkas PDF jadwal resmi hasil FET 5.44.9 (seperti jadwal per kelas SMKN 1 Bantul).</li>
                        <li><strong class="text-emerald-400">Auto-Provisioning:</strong> Guru, rombel kelas, jurusan, dan ruangan lab yang belum ada akan <span class="text-white font-bold">dibuat otomatis</span> ke sistem.</li>
                        <li><strong class="text-amber-400">Excel / CSV:</strong> Format kolom: <code class="text-indigo-300 font-mono">nama_guru_atau_nip, kelas, ruang, mapel, hari, jam_mulai, jam_selesai, putaran</code>.</li>
                    </ul>
                </div>

                <form method="POST" action="{{ route('waka.schedules.import') }}" enctype="multipart/form-data" 
                      x-data="{ 
                          selectedFiles: [],
                          dupAction: 'replace',
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

                    <!-- Info Ketentuan Import Jadwal Sesuai Aturan Waka -->
                    <div class="p-3 rounded-xl bg-indigo-950/30 border border-indigo-800/40 text-[11px] text-indigo-300 flex items-start gap-2">
                        <svg class="w-4 h-4 text-indigo-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>
                            <strong>Ketentuan Import:</strong> Jadwal yang diimpor mencakup hari, mata pelajaran, jam, guru pengajar, kelas, ruangan, dan putaran. Dokumen yang diimpor <strong>hanya mencocokkan dengan data guru yang sudah ada</strong> di sistem dan <strong>tidak akan menginput/menambah data guru baru</strong>.
                        </span>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-300 mb-1">Target Siklus Putaran</label>
                        <select name="cycle_type" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-xs font-bold text-white focus:border-indigo-500">
                            <option value="Putaran 1" {{ $config->active_cycle === 'Putaran 1' ? 'selected' : '' }}>Putaran 1 (Aktif)</option>
                            <option value="Putaran 2" {{ $config->active_cycle === 'Putaran 2' ? 'selected' : '' }}>Putaran 2</option>
                        </select>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block font-bold text-slate-300">Pilih Berkas Dokumen (Bisa Pilih Beberapa File Sekaligus)</label>
                            <span class="text-[10px] text-emerald-400 font-semibold">Multiple Files (.pdf, .html, .xlsx, .csv)</span>
                        </div>
                        <input type="file" name="files[]" x-ref="fileInput" multiple accept=".pdf,.html,.htm,.xlsx,.xls,.csv,.txt" required 
                               @change="handleFiles($event)"
                               class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-slate-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-indigo-600 file:text-white hover:file:bg-indigo-500 cursor-pointer" />
                        
                        <!-- List Preview Berkas Terpilih dengan Tombol Silang / Hapus -->
                        <div x-show="selectedFiles.length > 0" class="mt-2.5 p-3 rounded-xl bg-indigo-950/40 border border-indigo-800/40 text-[11px] text-indigo-300 space-y-2">
                            <div class="flex items-center justify-between text-white font-bold">
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    <span x-text="selectedFiles.length + ' berkas dipilih untuk diimpor serentak:'"></span>
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
                            *Tips: Tahan tombol <kbd class="px-1 py-0.5 rounded bg-slate-800 text-slate-300 border border-slate-700">Ctrl</kbd> atau <kbd class="px-1 py-0.5 rounded bg-slate-800 text-slate-300 border border-slate-700">Shift</kbd> pada file chooser untuk memilih gabungan file (misal: <em>Jadwal_Kelas.pdf</em>, <em>Jadwal_Guru.pdf</em>, dan <em>Jadwal_Ruang.pdf</em>).
                        </p>
                    </div>

                    <!-- Opsi Penanganan Data Duplikat / Jadwal yang Sudah Ada (Sesuai Foto ke-2) -->
                    <div class="p-3.5 rounded-xl bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 space-y-2">
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300">
                            Jika Ditemukan Data Jadwal yang Sudah Ada (Dobel):
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                            <label :class="dupAction === 'replace' ? 'border-indigo-600 bg-indigo-50/90 dark:bg-indigo-950/60 ring-2 ring-indigo-500/40 shadow-sm' : 'border-slate-300 dark:border-slate-800 bg-white dark:bg-slate-950/60 hover:border-indigo-400/60'"
                                   class="flex items-start gap-2.5 p-2.5 rounded-lg border cursor-pointer transition">
                                <input type="radio" name="duplicate_action" value="replace" x-model="dupAction" class="import-dup-radio" />
                                <div>
                                    <span class="font-bold text-slate-900 dark:text-white block text-[11px]">Ganti / Perbarui yang Baru</span>
                                    <span class="text-[10px] text-slate-600 dark:text-slate-400 leading-tight block mt-0.5">Timpa data jadwal lama pada kelas & jam yang sama dengan data dari dokumen</span>
                                </div>
                            </label>
                            <label :class="dupAction === 'keep' ? 'border-indigo-600 bg-indigo-50/90 dark:bg-indigo-950/60 ring-2 ring-indigo-500/40 shadow-sm' : 'border-slate-300 dark:border-slate-800 bg-white dark:bg-slate-950/60 hover:border-indigo-400/60'"
                                   class="flex items-start gap-2.5 p-2.5 rounded-lg border cursor-pointer transition">
                                <input type="radio" name="duplicate_action" value="keep" x-model="dupAction" class="import-dup-radio" />
                                <div>
                                    <span class="font-bold text-slate-900 dark:text-white block text-[11px]">Pertahankan Data Awal</span>
                                    <span class="text-[10px] text-slate-600 dark:text-slate-400 leading-tight block mt-0.5">Lewati jika kelas & jam tersebut sudah memiliki jadwal di sistem</span>
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
                            Mulai Import Terpadu
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL KONFIRMASI: HAPUS JADWAL TERPILIH -->
        <div x-show="deleteSelectedModalOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
            <div @click="deleteSelectedModalOpen = false" class="fixed inset-0 bg-black/70 backdrop-blur-sm"></div>
            <div class="relative w-full max-w-md bg-[#0f172a] p-6 shadow-2xl border border-rose-500/30 space-y-4 my-auto max-h-[90vh] overflow-y-auto" style="border-radius: 20px;">
                <div class="flex items-center gap-3 border-b border-slate-800 pb-3">
                    <div class="w-10 h-10 rounded-xl bg-rose-500/20 text-rose-400 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-white">Hapus Jadwal Terpilih</h3>
                        <p class="text-xs text-slate-400">Konfirmasi tindakan penghapusan massal jadwal</p>
                    </div>
                </div>

                <div class="p-3.5 rounded-xl bg-rose-950/30 border border-rose-800/40 text-xs text-rose-300 leading-relaxed">
                    Anda yakin ingin menghapus <strong x-text="selectedSchedules.length" class="text-white underline"></strong> jadwal pelajaran yang dipilih? Tindakan ini akan menghapus jadwal tersebut secara permanen.
                </div>

                <form method="POST" action="{{ route('waka.schedules.delete-selected') }}" class="space-y-4">
                    @csrf
                    <!-- Loop Hidden Inputs IDs -->
                    <template x-for="id in selectedSchedules" :key="id">
                        <input type="hidden" name="schedule_ids[]" :value="id">
                    </template>

                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
                        <button @click="deleteSelectedModalOpen = false" type="button" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-400 hover:bg-slate-800 hover:text-white transition cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-xs font-bold text-white shadow-md shadow-rose-950/60 transition cursor-pointer flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            <span>Ya, Hapus Jadwal Terpilih</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL KONFIRMASI: HAPUS SEMUA JADWAL -->
        <div x-show="deleteAllModalOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
            <div @click="deleteAllModalOpen = false" class="fixed inset-0 bg-black/70 backdrop-blur-sm"></div>
            <div class="relative w-full max-w-md bg-[#0f172a] p-6 shadow-2xl border border-rose-500/40 space-y-4 my-auto max-h-[90vh] overflow-y-auto" style="border-radius: 20px;">
                <div class="flex items-center gap-3 border-b border-slate-800 pb-3">
                    <div class="w-10 h-10 rounded-xl bg-rose-500/20 text-rose-400 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-white">Hapus Semua Jadwal</h3>
                        <p class="text-xs text-rose-400 font-semibold">Tindakan ini tidak dapat dibatalkan!</p>
                    </div>
                </div>

                <div class="p-3.5 rounded-xl bg-rose-950/40 border border-rose-800/50 text-xs text-rose-200 leading-relaxed">
                    PERINGATAN: Tindakan ini akan menghapus jadwal pelajaran dari sistem sesuai cakupan yang Anda tentukan di bawah.
                </div>

                <form method="POST" action="{{ route('waka.schedules.delete-all') }}" class="space-y-4 text-xs">
                    @csrf
                    <div>
                        <label class="block font-bold text-slate-300 mb-1.5">Cakupan Putaran / Siklus:</label>
                        <select name="scope" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2.5 text-xs font-semibold text-white focus:border-rose-500 focus:outline-none focus:ring-1 focus:ring-rose-500">
                            <option value="cycle_1" {{ $config->active_cycle == 'Putaran 1' ? 'selected' : '' }}>
                                Hanya Jadwal di Putaran 1{{ $config->active_cycle == 'Putaran 1' ? ' (Putaran Aktif)' : '' }}
                            </option>
                            <option value="cycle_2" {{ $config->active_cycle == 'Putaran 2' ? 'selected' : '' }}>
                                Hanya Jadwal di Putaran 2{{ $config->active_cycle == 'Putaran 2' ? ' (Putaran Aktif)' : '' }}
                            </option>
                            <option value="all_cycles">
                                ⚠️ Hapus SELURUH Jadwal di SEMUA Putaran (Putaran 1 & Putaran 2)
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-300 mb-1.5">Cakupan Kelas (Opsional):</label>
                        <select name="class_id" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2.5 text-xs font-semibold text-white focus:border-rose-500 focus:outline-none focus:ring-1 focus:ring-rose-500">
                            <option value="all">Semua Kelas</option>
                            @foreach($classes as $c)
                                <option value="{{ $c->id }}">Hanya Kelas: {{ $c->class_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
                        <button @click="deleteAllModalOpen = false" type="button" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-400 hover:bg-slate-800 hover:text-white transition cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-xs font-bold text-white shadow-md shadow-rose-950/60 transition cursor-pointer flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            <span>Ya, Hapus Jadwal</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>
