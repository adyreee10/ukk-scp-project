<x-app-layout>
    <div x-data="{ addModalOpen: false }" class="space-y-6">
        
        <!-- Header & Action Bar (Judul Putih Terang, Subjudul Abu-abu) -->
        <div class="flex items-center justify-between gap-4 w-full">
            <div class="text-left">
                <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">Master Data Kelas</h1>
                <p class="text-xs font-semibold text-slate-400 mt-0.5">Kelola data kelas, rombongan belajar, dan penugasan wali kelas</p>
            </div>
            <div class="shrink-0">
                <button @click="addModalOpen = true" type="button" class="px-4 sm:px-5 py-2 sm:py-2.5 rounded-xl scp-btn-gradient text-xs sm:text-sm font-bold text-white shadow-md cursor-pointer transition whitespace-nowrap">
                    + Tambah Kelas Baru
                </button>
            </div>
        </div>

        <!-- Tabel Kelas (Latar Gelap Keabuan Menyatu) -->
        <div class="bg-[#0f172a]/90 backdrop-blur-md border border-slate-800 shadow-xl overflow-hidden" style="border-radius: 15px;">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-800/80 text-sm">
                    <thead class="bg-slate-900/70">
                        <tr class="text-left text-xs font-bold text-slate-400 uppercase tracking-wider">
                            <th class="p-4">Nama Kelas</th>
                            <th class="p-4">Wali Kelas</th>
                            <th class="p-4 text-center">Jumlah Siswa</th>
                            <th class="p-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @forelse ($classes as $class)
                            <tr class="hover:bg-slate-800/30 transition">
                                <td class="p-4">
                                    <span class="font-bold text-white text-base">{{ $class->class_name }}</span>
                                </td>
                                <td class="p-4">
                                    @if($class->homeroomTeachers->isNotEmpty())
                                        <div class="space-y-0.5">
                                            @foreach($class->homeroomTeachers as $teacher)
                                                <span class="inline-block rounded-md bg-slate-800/80 px-2.5 py-1 text-xs font-bold text-slate-200 border border-slate-700">
                                                    {{ $teacher->name }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-xs text-slate-500 italic">Belum ditentukan</span>
                                    @endif
                                </td>
                                <td class="p-4 text-center">
                                    <span class="inline-flex items-center justify-center rounded-full bg-indigo-950/60 px-3 py-1 text-xs font-bold text-indigo-300 border border-indigo-800/60">
                                        {{ $class->users_count }} Siswa
                                    </span>
                                </td>
                                <td class="p-4 text-center">
                                    <form method="POST" action="{{ route('waka.classes.destroy', $class) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus kelas ini?')">
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
                                <td colspan="4" class="p-8 text-center text-slate-500 text-sm">
                                    Belum ada data kelas yang terdaftar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($classes->hasPages())
                <div class="p-4 border-t border-slate-800">
                    {{ $classes->links() }}
                </div>
            @endif
        </div>

        <!-- Modal Tambah Kelas (Latar Gelap Elegan) -->
        <div x-show="addModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div @click="addModalOpen = false" class="fixed inset-0 bg-black/70 backdrop-blur-sm"></div>
            
            <div class="relative w-full max-w-md bg-[#0f172a] p-6 sm:p-8 shadow-2xl border border-slate-800 space-y-4" style="border-radius: 20px;">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-lg font-bold text-white">Tambah Kelas Baru</h3>
                    <button @click="addModalOpen = false" class="text-xs font-bold text-slate-400 hover:text-white px-2 py-1">
                        [Tutup]
                    </button>
                </div>

                <form method="POST" action="{{ route('waka.classes.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Nama Kelas</label>
                        <input type="text" name="class_name" required class="w-full rounded-xl border border-slate-700 bg-slate-900/90 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" placeholder="Contoh: X RPL 1 atau XI TKJ 2" />
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button @click="addModalOpen = false" type="button" class="rounded-xl px-4 py-2 text-xs font-bold text-slate-400 hover:bg-slate-800 hover:text-white transition cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl scp-btn-gradient text-xs font-bold text-white shadow-md cursor-pointer transition">
                            Simpan Kelas
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>
