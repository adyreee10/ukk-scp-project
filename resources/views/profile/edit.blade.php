<x-app-layout>
    <div class="space-y-6">
        <!-- Header Halaman -->
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">Profil & Informasi Akun</h1>
            <p class="text-xs font-semibold text-slate-400 mt-0.5">Lihat data pribadi resmi dan kelola username, nomor HP, email, serta kata sandi akun Anda</p>
        </div>

        <!-- ============================================================= -->
        <!-- KARTU 1: DETAIL DATA DIRI PENGGUNA (INFORMASI PRIBADI)        -->
        <!-- ============================================================= -->
        <div class="p-6 sm:p-8 bg-[#0f172a]/90 backdrop-blur-md border border-slate-800 shadow-xl space-y-6" style="border-radius: 20px;">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-5">
                <div class="flex items-center gap-4">
                    <div class="h-16 w-16 rounded-2xl scp-badge-gradient flex items-center justify-center font-black text-2xl text-white shadow-lg shadow-indigo-500/20">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    <div>
                        <div class="flex items-center gap-2.5 flex-wrap">
                            <h2 class="text-lg sm:text-xl font-black text-white">{{ $user->name }}</h2>
                            <span class="px-2.5 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                                {{ $user->role?->role_name ?? 'Pengguna' }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-400 font-mono mt-0.5">{{ '@' . $user->username }} &bull; {{ $user->email }}</p>
                    </div>
                </div>
            </div>

            <!-- Grid Detail Informasi Pribadi Sesuai Role -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 text-xs">
                <!-- Info 1: Identitas Resmi (NIP / NIS) -->
                <div class="p-4 rounded-xl bg-slate-900/80 border border-slate-800">
                    <span class="text-slate-400 text-[11px] font-bold block mb-1">
                        @if(in_array($user->role?->role_name, ['Guru', 'Wali Kelas', 'Guru BK', 'Waka Kurikulum']))
                            Nomor Induk Pegawai (NIP)
                        @elseif($user->role?->role_name === 'Siswa')
                            Nomor Induk Siswa (NIS)
                        @else
                            ID Pengguna
                        @endif
                    </span>
                    <span class="text-sm font-black text-white font-mono">
                        {{ $user->nip ?? $user->nis ?? '-' }}
                    </span>
                </div>

                <!-- Info 2: Penugasan / Kelas -->
                <div class="p-4 rounded-xl bg-slate-900/80 border border-slate-800">
                    <span class="text-slate-400 text-[11px] font-bold block mb-1">
                        @if($user->role?->role_name === 'Siswa')
                            Kelas Terdaftar
                        @elseif($user->role?->role_name === 'Wali Kelas')
                            Kelas Perwalian
                        @elseif($user->role?->role_name === 'Orang Tua')
                            Anak / Siswa Terhubung
                        @else
                            Peran Utama
                        @endif
                    </span>
                    <span class="text-sm font-black text-white">
                        @if($user->role?->role_name === 'Siswa')
                            {{ $user->schoolClass?->class_name ?? 'Belum Ditentukan' }}
                        @elseif($user->role?->role_name === 'Wali Kelas')
                            {{ $user->homeroomClass?->class_name ?? 'Belum Ditugaskan' }}
                        @elseif($user->role?->role_name === 'Orang Tua')
                            {{ $user->children->pluck('name')->join(', ') ?: 'Belum Ada Siswa' }}
                        @else
                            {{ $user->role?->role_name ?? '-' }}
                        @endif
                    </span>
                </div>

                <!-- Info 3: Nomor HP Aktif -->
                <div class="p-4 rounded-xl bg-slate-900/80 border border-slate-800">
                    <span class="text-slate-400 text-[11px] font-bold block mb-1">Nomor WhatsApp / HP</span>
                    <span class="text-sm font-black font-mono {{ $user->phone ? 'text-emerald-400' : 'text-slate-500' }}">
                        {{ $user->phone ?? 'Belum diisi' }}
                    </span>
                </div>

                <!-- Info 4: Alamat Tempat Tinggal -->
                <div class="p-4 rounded-xl bg-slate-900/80 border border-slate-800 sm:col-span-2">
                    <span class="text-slate-400 text-[11px] font-bold block mb-1">Alamat Tempat Tinggal</span>
                    <span class="text-xs font-semibold text-slate-300">
                        {{ $user->address ?? 'Alamat belum dilengkapi.' }}
                    </span>
                </div>

                <!-- Info 5: Tanggal Terdaftar -->
                <div class="p-4 rounded-xl bg-slate-900/80 border border-slate-800">
                    <span class="text-slate-400 text-[11px] font-bold block mb-1">Terdaftar Sejak</span>
                    <span class="text-xs font-semibold text-slate-300">
                        {{ $user->created_at ? $user->created_at->translatedFormat('d F Y') : '-' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- ============================================================= -->
        <!-- KARTU 2: UBAH INFORMASI PROFIL (USERNAME, EMAIL, NO HP)       -->
        <!-- ============================================================= -->
        <div class="p-6 sm:p-8 bg-[#0f172a]/90 backdrop-blur-md border border-slate-800 shadow-xl" style="border-radius: 20px;">
            <div class="max-w-2xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <!-- ============================================================= -->
        <!-- KARTU 3: UBAH KATA SANDI (PASSWORD)                           -->
        <!-- ============================================================= -->
        <div class="p-6 sm:p-8 bg-[#0f172a]/90 backdrop-blur-md border border-slate-800 shadow-xl" style="border-radius: 20px;">
            <div class="max-w-2xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>
    </div>
</x-app-layout>
