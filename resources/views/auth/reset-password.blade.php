<x-guest-layout>
    <!-- Header SCP -->
    <div class="text-center mb-6">
        <div class="inline-flex items-center justify-center mb-3">
            <img src="{{ asset('images/logo.png') }}?v=20261107" alt="Logo SCP" class="h-20 w-20 object-contain drop-shadow-lg" />
        </div>
        <h2 class="text-2xl font-black text-slate-800 tracking-tight">Verifikasi OTP & Sandi Baru</h2>
        <p class="text-xs font-semibold text-slate-500 mt-1.5 leading-relaxed">
            Masukkan 6 digit kode OTP yang telah dikirimkan ke email Anda (berlaku selama 5 menit) beserta kata sandi baru.
        </p>
    </div>

    <!-- Notifikasi Status Pengiriman OTP -->
    @if (session('status'))
        <div role="status" class="mb-4 p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-xs font-bold text-emerald-800 flex items-center justify-between">
            <span>{{ session('status') }}</span>
            <span class="text-[10px] uppercase tracking-wider text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-md font-extrabold">Terkirim</span>
        </div>
    @endif

    <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
        @csrf

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                Alamat Email Akun
            </label>
            <input id="email" 
                   style="border-radius: 15px;"
                   class="block w-full border border-slate-200 bg-slate-100 px-4 py-3 text-sm text-slate-700 font-semibold focus:border-indigo-600 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-500/15" 
                   type="email" 
                   name="email" 
                   value="{{ old('email', $email) }}" 
                   required 
                   placeholder="Alamat email akun" />
            <x-input-error :messages="$errors->get('email')" class="mt-1 text-xs" />
        </div>

        <!-- Kode OTP 6 Digit -->
        <div>
            <div class="flex items-center justify-between mb-1.5">
                <label for="otp" class="block text-xs font-bold uppercase tracking-wider text-slate-600">
                    Kode OTP (6 Digit)
                </label>
            </div>
            <input id="otp" 
                   style="border-radius: 15px; letter-spacing: 0.35em;"
                   class="block w-full text-center border-2 border-indigo-200 bg-indigo-50/40 px-4 py-3 text-lg font-mono font-black text-indigo-950 placeholder-slate-400 transition focus:border-indigo-600 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-500/15" 
                   type="text" 
                   name="otp" 
                   maxlength="6"
                   value="{{ old('otp') }}" 
                   required 
                   autofocus 
                   placeholder="000000" />
            <x-input-error :messages="$errors->get('otp')" class="mt-1 text-xs" />
        </div>

        <!-- Password Baru -->
        <div x-data="{ showPass: false }">
            <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                Kata Sandi Baru
            </label>
            <div class="scp-password-wrapper" style="position: relative; width: 100%; display: block;">
                <input id="password" 
                       class="block w-full border border-slate-200 bg-slate-50 pl-4 py-3 text-sm text-slate-800 transition focus:border-indigo-600 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-500/15 font-semibold" 
                       style="border-radius: 15px; padding-right: 48px; width: 100%;" 
                       :type="showPass ? 'text' : 'password'" 
                       name="password" 
                       required 
                       autocomplete="new-password" 
                       placeholder="Minimal 8 karakter" />
                <button type="button" @click="showPass = !showPass" class="scp-password-toggle" style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); background: transparent; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; padding: 4px; color: #94a3b8; z-index: 10;" :title="showPass ? 'Sembunyikan kata sandi' : 'Lihat kata sandi'">
                    <svg x-show="!showPass" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <svg x-show="showPass" class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-1 text-xs" />
        </div>

        <!-- Konfirmasi Password Baru -->
        <div x-data="{ showConfirm: false }">
            <label for="password_confirmation" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                Konfirmasi Kata Sandi Baru
            </label>
            <div class="scp-password-wrapper" style="position: relative; width: 100%; display: block;">
                <input id="password_confirmation" 
                       class="block w-full border border-slate-200 bg-slate-50 pl-4 py-3 text-sm text-slate-800 transition focus:border-indigo-600 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-500/15 font-semibold" 
                       style="border-radius: 15px; padding-right: 48px; width: 100%;" 
                       :type="showConfirm ? 'text' : 'password'" 
                       name="password_confirmation" 
                       required 
                       autocomplete="new-password" 
                       placeholder="Ulangi kata sandi baru" />
                <button type="button" @click="showConfirm = !showConfirm" class="scp-password-toggle" style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); background: transparent; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; padding: 4px; color: #94a3b8; z-index: 10;" :title="showConfirm ? 'Sembunyikan kata sandi' : 'Lihat kata sandi'">
                    <svg x-show="!showConfirm" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <svg x-show="showConfirm" class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1 text-xs" />
        </div>

        <!-- Tombol Simpan Sandi Baru -->
        <div class="pt-3">
            <button type="submit" 
                    style="border-radius: 15px;"
                    class="w-full inline-flex items-center justify-center py-4 px-6 font-black text-sm scp-btn-gradient cursor-pointer tracking-wider uppercase shadow-lg shadow-indigo-500/25 hover:shadow-indigo-500/40 transition-all duration-200">
                SIMPAN KATA SANDI BARU
            </button>
        </div>
    </form>

    <!-- Opsi Jika Kode OTP Tidak Masuk / Minta Kode Lagi (Jeda 1 Menit) -->
    <div class="mt-6 pt-4 border-t border-slate-100 text-center"
         x-data="{
             cooldown: {{ (int) ($cooldown ?? 0) }},
             init() {
                 if (this.cooldown > 0) {
                     const interval = setInterval(() => {
                         this.cooldown--;
                         if (this.cooldown <= 0) {
                             clearInterval(interval);
                         }
                     }, 1000);
                 }
             }
         }">
        <p class="text-xs text-slate-500 mb-2 font-medium">
            Tidak menerima email berisi kode OTP?
        </p>
        <form method="POST" action="{{ route('password.email') }}" class="inline-block">
            @csrf
            <input type="hidden" name="email" value="{{ old('email', $email) }}">
            <button type="submit" 
                    :disabled="cooldown > 0"
                    :class="cooldown > 0 ? 'opacity-60 cursor-not-allowed bg-slate-100 text-slate-400 border-slate-200' : 'bg-indigo-50 hover:bg-indigo-100 text-indigo-600 hover:text-indigo-800 border-indigo-200/80 cursor-pointer'"
                    class="text-xs font-bold transition inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border">
                <svg class="w-3.5 h-3.5" :class="cooldown > 0 ? 'animate-spin text-slate-400' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <span x-show="cooldown <= 0">Kirim Ulang Kode OTP Baru</span>
                <span x-show="cooldown > 0" x-cloak>Kirim Ulang (<span x-text="cooldown"></span>s)</span>
            </button>
        </form>
        <div x-show="cooldown > 0" x-cloak class="mt-1.5 text-[11px] text-slate-400 font-semibold">
            Tunggu <span x-text="cooldown" class="text-indigo-600 font-bold"></span> detik sebelum meminta kode baru
        </div>
    </div>

    <!-- Kembali ke Halaman Login -->
    <div class="text-center pt-4">
        <a href="{{ route('login') }}" class="text-xs font-bold text-slate-500 hover:text-indigo-600 transition hover:underline">
            ← Batalkan & Kembali ke Halaman Masuk
        </a>
    </div>
</x-guest-layout>
