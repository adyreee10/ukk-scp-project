<x-app-layout>
    <div x-data="{ addModalOpen: false }" class="space-y-6">
        
        <!-- Header & Action Bar -->
        <div class="flex items-center justify-between gap-4 w-full">
            <div class="text-left">
                <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">Jadwal Mengajar</h1>
                <p class="text-xs font-semibold text-slate-400 mt-0.5">Input dan kelola jadwal pelajaran mandiri untuk seluruh guru dan kelas</p>
            </div>
            <div class="shrink-0">
                <button @click="addModalOpen = true" type="button" class="px-4 sm:px-5 py-2 sm:py-2.5 rounded-xl scp-btn-gradient text-xs sm:text-sm font-bold text-white shadow-md cursor-pointer transition whitespace-nowrap">
                    + Tambah Jadwal Baru
                </button>
            </div>
        </div>

        <!-- Filter Bar (Gelap Keabuan) -->
        <div class="bg-[#0f172a]/90 backdrop-blur-md p-4 border border-slate-800 shadow-xl" style="border-radius: 15px;">
            <form method="GET" action="{{ route('waka.schedules.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                <div>
                    <select name="cycle_type" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2.5 text-xs font-bold text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        <option value="Putaran 1" {{ request('cycle_type', $config->active_cycle) == 'Putaran 1' ? 'selected' : '' }}>Putaran 1</option>
                        <option value="Putaran 2" {{ request('cycle_type', $config->active_cycle) == 'Putaran 2' ? 'selected' : '' }}>Putaran 2</option>
                    </select>
                </div>
                <div>
                    <select name="day_of_week" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2.5 text-xs text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        <option value="">Semua Hari</option>
                        @foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $d)
                            <option value="{{ $d }}" {{ request('day_of_week') == $d ? 'selected' : '' }}>{{ $d }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <select name="class_id" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2.5 text-xs text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        <option value="">Semua Kelas</option>
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}" {{ request('class_id') == $c->id ? 'selected' : '' }}>{{ $c->class_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2">
                    <select name="teacher_id" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2.5 text-xs text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        <option value="">Semua Guru</option>
                        @foreach($teachers as $t)
                            <option value="{{ $t->id }}" {{ request('teacher_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="rounded-xl scp-btn-gradient px-5 py-2.5 text-xs font-bold text-white transition shadow-sm cursor-pointer">
                        Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Tabel Jadwal (Gelap Keabuan) -->
        <div class="bg-[#0f172a]/90 backdrop-blur-md border border-slate-800 shadow-xl overflow-hidden" style="border-radius: 15px;">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-800/80 text-sm">
                    <thead class="bg-slate-900/70">
                        <tr class="text-left text-xs font-bold text-slate-400 uppercase tracking-wider">
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
                                <td class="p-4">
                                    <span class="font-bold text-white">{{ $item->day_of_week }}</span>
                                    <p class="text-xs text-indigo-400 font-semibold">{{ substr($item->start_time, 0, 5) }} - {{ substr($item->end_time, 0, 5) }}</p>
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
                                <td class="p-4 text-center">
                                    <form method="POST" action="{{ route('waka.schedules.destroy', $item) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus jadwal ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-3 py-1 text-xs font-bold text-red-400 hover:text-white hover:bg-red-600/80 border border-red-500/30 rounded-lg transition cursor-pointer">
                                            Hapus
                                        </button>
                                    </form>
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

            @if($schedules->hasPages())
                <div class="p-4 border-t border-slate-800">
                    {{ $schedules->links() }}
                </div>
            @endif
        </div>

        <!-- Modal Tambah Jadwal Pelajaran -->
        <div x-show="addModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div @click="addModalOpen = false" class="fixed inset-0 bg-black/70 backdrop-blur-sm"></div>
            
            <div class="relative w-full max-w-lg bg-[#0f172a] p-6 sm:p-8 shadow-2xl border border-slate-800 space-y-4" style="border-radius: 20px;">
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
                                <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
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

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Hari</label>
                            <select name="day_of_week" required class="w-full rounded-xl border border-slate-700 bg-slate-900 px-2.5 py-2 text-xs text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                                @foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $d)
                                    <option value="{{ $d }}">{{ $d }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Jam Mulai</label>
                            <input type="time" name="start_time" required class="w-full rounded-xl border border-slate-700 bg-slate-900 px-2.5 py-1.5 text-xs text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Jam Selesai</label>
                            <input type="time" name="end_time" required class="w-full rounded-xl border border-slate-700 bg-slate-900 px-2.5 py-1.5 text-xs text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" />
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

    </div>
</x-app-layout>
