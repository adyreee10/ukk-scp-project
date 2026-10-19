<x-app-layout>
    <div class="space-y-6">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">Profil Pengguna</h1>
            <p class="text-xs font-semibold text-slate-400 mt-0.5">Kelola informasi profil akun, kata sandi, dan preferensi akun Anda</p>
        </div>

        <div class="space-y-6">
            <!-- Informasi Akun -->
            <div class="p-6 sm:p-8 bg-[#0f172a]/90 backdrop-blur-md border border-slate-800 shadow-xl" style="border-radius: 15px;">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <!-- Ubah Kata Sandi -->
            <div class="p-6 sm:p-8 bg-[#0f172a]/90 backdrop-blur-md border border-slate-800 shadow-xl" style="border-radius: 15px;">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <!-- Hapus Akun -->
            <div class="p-6 sm:p-8 bg-[#0f172a]/90 backdrop-blur-md border border-slate-800 shadow-xl" style="border-radius: 15px;">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
