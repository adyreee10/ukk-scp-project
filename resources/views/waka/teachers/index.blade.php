<x-app-layout>
    <div x-data="{ addModalOpen: false }" class="space-y-6">
        
        <!-- Header & Action Bar -->
        <div class="flex items-center justify-between gap-4 w-full" style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
            <div class="text-left" style="text-align: left;">
                <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight" style="text-align: left;">Data Guru</h1>
                <p class="text-xs font-semibold text-slate-400 mt-0.5" style="text-align: left;">Kelola data tenaga pendidik, penugasan wali kelas, dan akun login guru</p>
            </div>
            <div class="shrink-0" style="flex-shrink: 0; margin-left: auto;">
                <button @click="addModalOpen = true" type="button" class="px-4 sm:px-5 py-2 sm:py-2.5 rounded-xl scp-btn-gradient text-xs sm:text-sm font-bold text-white shadow-md cursor-pointer transition whitespace-nowrap" style="white-space: nowrap;">
                    + Tambah Guru Baru
                </button>
            </div>
        </div>

        <!-- Filter & Pencarian (Dark Card) -->
        <div class="bg-[#0f172a]/90 backdrop-blur-md p-4 border border-slate-800 shadow-xl" style="border-radius: 15px;">
            <form method="GET" action="{{ route('waka.teachers.index') }}" class="flex gap-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, email, atau username guru..." class="w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-2.5 text-xs sm:text-sm text-white placeholder-slate-400 focus:border-indigo-500 focus:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" />
                <button type="submit" class="rounded-xl scp-btn-gradient px-6 py-2.5 text-xs font-bold text-white transition shadow-sm cursor-pointer">
                    Cari
                </button>
            </form>
        </div>

        <!-- Tabel Guru (Dark Card & High Contrast Rows) -->
        <div class="bg-[#0f172a]/90 backdrop-blur-md border border-slate-800 shadow-xl overflow-hidden" style="border-radius: 15px;">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-800 text-sm">
                    <thead class="bg-slate-900/70">
                        <tr class="text-left text-xs font-bold text-slate-400 uppercase tracking-wider">
                            <th class="p-4">Guru</th>
                            <th class="p-4">Email</th>
                            <th class="p-4">Peran</th>
                            <th class="p-4">Wali Kelas</th>
                            <th class="p-4 text-center">Jadwal Mengajar</th>
                            <th class="p-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @forelse ($teachers as $teacher)
                            <tr class="hover:bg-slate-800/30 transition">
                                <td class="p-4">
                                    <div>
                                        <p class="font-bold text-white">{{ $teacher->name }}</p>
                                        <p class="text-xs text-slate-400">@<span>{{ $teacher->username }}</span></p>
                                    </div>
                                </td>
                                <td class="p-4 text-slate-300 text-xs sm:text-sm">{{ $teacher->email }}</td>
                                <td class="p-4">
                                    <span class="inline-block rounded-md bg-slate-800 px-2.5 py-1 text-xs font-bold text-slate-200 border border-slate-700">
                                        {{ $teacher->role?->role_name ?? 'Guru' }}
                                    </span>
                                </td>
                                <td class="p-4 text-xs font-semibold">
                                    @if($teacher->homeroomClass)
                                        <span class="text-indigo-300 bg-indigo-950/60 px-2.5 py-1 rounded-md border border-indigo-800/60">
                                            {{ $teacher->homeroomClass->class_name }}
                                        </span>
                                    @else
                                        <span class="text-slate-500 font-normal">-</span>
                                    @endif
                                </td>
                                <td class="p-4 text-center">
                                    <span class="inline-flex items-center justify-center rounded-full bg-slate-800 px-3 py-1 text-xs font-bold text-slate-200 border border-slate-700">
                                        {{ $teacher->schedules_count }} Jadwal
                                    </span>
                                </td>
                                <td class="p-4 text-center">
                                    @if($teacher->id !== auth()->id())
                                        <form method="POST" action="{{ route('waka.teachers.destroy', $teacher) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data guru ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-3 py-1 text-xs font-bold text-red-400 hover:text-white hover:bg-red-600/80 border border-red-500/30 rounded-lg transition cursor-pointer">
                                                Hapus
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-xs font-medium text-slate-500 italic">Akun Anda</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-8 text-center text-slate-400 text-sm">
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

        <!-- Modal Tambah Guru (Dark Theme) -->
        <div x-show="addModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div @click="addModalOpen = false" class="fixed inset-0 bg-black/70 backdrop-blur-sm"></div>
            
            <div class="relative w-full max-w-lg bg-[#0f172a] p-6 sm:p-8 shadow-2xl border border-slate-800 space-y-4" style="border-radius: 20px;">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-lg font-bold text-white">Tambah Guru Baru</h3>
                    <button @click="addModalOpen = false" class="text-xs font-bold text-slate-400 hover:text-white px-2 py-1">
                        [Tutup]
                    </button>
                </div>

                <form method="POST" action="{{ route('waka.teachers.store') }}" class="space-y-3.5">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Nama Lengkap & Gelar</label>
                        <input type="text" name="name" required class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" placeholder="Contoh: Budi Santoso, M.Kom" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Username / NIP</label>
                            <input type="text" name="username" required class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" placeholder="19850101..." />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Email Sekolah</label>
                            <input type="email" name="email" required class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" placeholder="budi@sekolah.test" />
                        </div>
                    </div>

                    <div x-data="{ showPass: false }">
                        <label class="block text-xs font-bold text-slate-300 mb-1">Kata Sandi</label>
                        <div class="scp-password-wrapper" style="position: relative; width: 100%; display: block;">
                            <input :type="showPass ? 'text' : 'password'" name="password" required class="w-full rounded-xl border border-slate-700 bg-slate-900 pl-3.5 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" style="padding-right: 42px; width: 100%;" placeholder="Minimal 6 karakter" />
                            <button type="button" @click="showPass = !showPass" class="scp-password-toggle" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: transparent; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; padding: 4px; color: #94a3b8; z-index: 10;" :title="showPass ? 'Sembunyikan kata sandi' : 'Lihat kata sandi'">
                                <svg x-show="!showPass" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <svg x-show="showPass" class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Penugasan Peran</label>
                            <select name="role_id" required class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-xs text-white focus:border-indigo-500 focus:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}" class="bg-slate-900 text-white">{{ $role->role_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Wali Kelas (Opsional)</label>
                            <select name="homeroom_class_id" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-xs text-white focus:border-indigo-500 focus:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                                <option value="" class="bg-slate-900 text-white">-- Bukan Wali Kelas --</option>
                                @foreach($classes as $c)
                                    <option value="{{ $c->id }}" class="bg-slate-900 text-white">{{ $c->class_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

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

    </div>
</x-app-layout>
