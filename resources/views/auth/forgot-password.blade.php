<x-guest-layout>
    <!-- Header SCP -->
    <div class="text-center mb-6">
        <div class="inline-flex items-center justify-center mb-3">
            <img src="{{ asset('images/logo.png') }}" alt="Logo SCP" class="h-20 w-20 object-contain drop-shadow-lg" />
        </div>
        <h2 class="text-2xl font-black text-slate-800 tracking-tight">Lupa Kata Sandi?</h2>
        <p class="text-xs font-semibold text-slate-500 mt-1.5 leading-relaxed">
            Masukkan alamat email akun Anda. Kami akan mengirimkan 6 digit kode OTP untuk mengatur ulang kata sandi Anda.
        </p>
    </div>

    <!-- Status Pengiriman OTP -->
    @if (session('status'))
        <div role="status" class="mb-4 p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-xs font-bold text-emerald-800 flex items-center justify-between">
            <span>{{ session('status') }}</span>
            <span class="text-[10px] uppercase tracking-wider text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-md font-extrabold">Terkirim</span>
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                Alamat Email Akun Terdaftar
            </label>
            <input id="email" 
                   style="border-radius: 15px;"
                   class="block w-full border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 transition focus:border-indigo-600 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-500/15 font-semibold" 
                   type="email" 
                   name="email" 
                   value="{{ old('email') }}" 
                   required 
                   autofocus 
                   placeholder="contoh: waka@smk.sch.id" />
            <x-input-error :messages="$errors->get('email')" class="mt-1 text-xs" />
        </div>

        <!-- Tombol Kirim Kode OTP -->
        <div class="pt-3">
            <button type="submit" 
                    style="border-radius: 15px;"
                    class="w-full inline-flex items-center justify-center py-4 px-6 font-black text-sm scp-btn-gradient cursor-pointer tracking-wider uppercase shadow-lg shadow-indigo-500/25 hover:shadow-indigo-500/40 transition-all duration-200">
                KIRIM KODE OTP
            </button>
        </div>

        <!-- Kembali ke Halaman Login -->
        <div class="text-center pt-2">
            <a href="{{ route('login') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 transition hover:underline">
                ← Kembali ke Halaman Masuk
            </a>
        </div>
    </form>
</x-guest-layout>
