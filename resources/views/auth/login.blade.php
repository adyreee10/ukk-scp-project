<x-guest-layout>
    <!-- Header Aplikasi SCP -->
    <div class="text-center mb-7">
        <div class="inline-flex items-center justify-center mb-3">
            <img src="{{ asset('images/logo.png') }}?v=20261107" alt="Logo SCP" class="h-20 w-20 object-contain drop-shadow-lg" />
        </div>
        <h2 class="text-2xl font-black text-slate-800 tracking-tight">School Class Progress</h2>
        <p class="text-xs font-semibold text-slate-500 mt-1">Sistem Presensi, Jurnal Mengajar & Perizinan</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

                <!-- Login: Email / Username / NIS / NIP -->
        <div>
            <label for="login" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                Email / Username / NIS / NIP
            </label>
            <input id="login" 
                   style="border-radius: 15px;"
                   class="block w-full border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 transition focus:border-indigo-600 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-500/15" 
                   type="text" 
                   name="login" 
                   value="{{ old('login') }}" 
                   required 
                   autofocus 
                   autocomplete="username" 
                   placeholder="Masukkan Email, Username, NIS, atau NIP" />
            <x-input-error :messages="$errors->get('login') ?? $errors->get('email')" class="mt-1 text-xs" />
        </div>

        <!-- Password with Visibility Toggle -->
        <div x-data="{ showPassword: false }">
            <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                Kata Sandi
            </label>
            <div class="scp-password-wrapper" style="position: relative; width: 100%; display: block;">
                <input id="password" 
                       style="border-radius: 15px; padding-right: 48px; width: 100%;"
                       class="block w-full border border-slate-200 bg-slate-50 pl-4 py-3 text-sm text-slate-800 transition focus:border-indigo-600 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-500/15"
                       :type="showPassword ? 'text' : 'password'"
                       name="password"
                       required 
                       autocomplete="current-password"
                       placeholder="••••••••" />
                <button type="button" 
                        @click="showPassword = !showPassword" 
                        class="scp-password-toggle"
                        style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); background: transparent; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; padding: 4px; color: #94a3b8; z-index: 10;"
                        :title="showPassword ? 'Sembunyikan Kata Sandi' : 'Lihat Kata Sandi'"
                        :aria-label="showPassword ? 'Sembunyikan Kata Sandi' : 'Lihat Kata Sandi'">
                    <!-- Ikon Mata Terbuka (Ketika tertutup/tersembunyi) -->
                    <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <!-- Ikon Mata Dicoret (Ketika terlihat) -->
                    <svg x-show="showPassword" class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                    </svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-1 text-xs" />
        </div>

        <!-- Remember Me & Forgot Password -->
        <div class="flex items-center justify-between text-xs pt-1">
            <label for="remember_me" class="inline-flex items-center text-slate-600 cursor-pointer select-none">
                <input id="remember_me" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" name="remember">
                <span class="ms-2 font-medium">Ingat saya</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-xs font-bold text-indigo-600 hover:text-indigo-800 transition hover:underline" href="{{ route('password.request') }}">
                    Lupa kata sandi?
                </a>
            @endif
        </div>

        <!-- Tombol Masuk Lebih Besar & Corner Radius 15px -->
        <div class="pt-3" x-data="{
            lockout: {{ (int) session('lockout_seconds', 0) }},
            init() {
                if (this.lockout > 0) {
                    const timer = setInterval(() => {
                        this.lockout--;
                        if (this.lockout <= 0) {
                            clearInterval(timer);
                        }
                    }, 1000);
                }
            }
        }">
            <button type="submit" 
                    :disabled="lockout > 0"
                    style="border-radius: 15px;"
                    :class="lockout > 0 ? 'opacity-60 cursor-not-allowed bg-slate-700 text-slate-300 shadow-none' : 'scp-btn-gradient cursor-pointer hover:shadow-indigo-500/40 shadow-lg shadow-indigo-500/25'"
                    class="w-full inline-flex items-center justify-center py-4 px-6 font-black text-base tracking-wider uppercase transition-all duration-200">
                <span x-show="lockout <= 0">MASUK KE SISTEM</span>
                <span x-show="lockout > 0" x-cloak>AKUN DITANGGUHKAN (<span x-text="lockout"></span>s)</span>
            </button>
        </div>
    </form>
</x-guest-layout>

