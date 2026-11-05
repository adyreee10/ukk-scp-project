<section>
    <header class="border-b border-slate-800 pb-3 mb-5">
        <h2 class="text-base font-extrabold text-white">
            Ubah Kata Sandi Akun
        </h2>
        <p class="mt-1 text-xs text-slate-400">
            Pastikan akun Anda menggunakan kata sandi yang aman dan tidak mudah ditebak.
        </p>
    </header>

    @if (session('status') === 'password-updated')
        <div role="status" class="mb-4 p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-xs font-bold text-emerald-300 flex items-center justify-between">
            <span>Kata sandi akun Anda berhasil diperbarui!</span>
            <span class="text-[10px] uppercase tracking-wider text-emerald-400 bg-emerald-500/20 px-2 py-0.5 rounded font-black">Sukses</span>
        </div>
    @endif

    <form method="post" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        @method('put')

        <!-- Kata Sandi Saat Ini -->
        <div x-data="{ showCurrent: false }">
            <label for="update_password_current_password" class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                Kata Sandi Saat Ini
            </label>
            <div class="scp-password-wrapper" style="position: relative; width: 100%; display: block;">
                <input id="update_password_current_password" 
                       name="current_password" 
                       :type="showCurrent ? 'text' : 'password'" 
                       class="w-full rounded-xl border border-slate-700 bg-slate-900 pl-3.5 pr-11 py-2.5 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 font-semibold" 
                       autocomplete="current-password" 
                       placeholder="Masukkan kata sandi lama Anda" />
                <button type="button" @click="showCurrent = !showCurrent" class="scp-password-toggle" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: transparent; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; padding: 4px; color: #94a3b8; z-index: 10;">
                    <svg x-show="!showCurrent" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <svg x-show="showCurrent" class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                </button>
            </div>
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-1.5 text-xs" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Kata Sandi Baru -->
            <div x-data="{ showNew: false }">
                <label for="update_password_password" class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                    Kata Sandi Baru
                </label>
                <div class="scp-password-wrapper" style="position: relative; width: 100%; display: block;">
                    <input id="update_password_password" 
                           name="password" 
                           :type="showNew ? 'text' : 'password'" 
                           class="w-full rounded-xl border border-slate-700 bg-slate-900 pl-3.5 pr-11 py-2.5 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 font-semibold" 
                           autocomplete="new-password" 
                           placeholder="Minimal 8 karakter" />
                    <button type="button" @click="showNew = !showNew" class="scp-password-toggle" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: transparent; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; padding: 4px; color: #94a3b8; z-index: 10;">
                        <svg x-show="!showNew" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <svg x-show="showNew" class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                    </button>
                </div>
                <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-1.5 text-xs" />
            </div>

            <!-- Konfirmasi Kata Sandi Baru -->
            <div x-data="{ showConfirm: false }">
                <label for="update_password_password_confirmation" class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                    Ulangi Kata Sandi Baru
                </label>
                <div class="scp-password-wrapper" style="position: relative; width: 100%; display: block;">
                    <input id="update_password_password_confirmation" 
                           name="password_confirmation" 
                           :type="showConfirm ? 'text' : 'password'" 
                           class="w-full rounded-xl border border-slate-700 bg-slate-900 pl-3.5 pr-11 py-2.5 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 font-semibold" 
                           autocomplete="new-password" 
                           placeholder="Ketik ulang kata sandi baru" />
                    <button type="button" @click="showConfirm = !showConfirm" class="scp-password-toggle" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: transparent; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; padding: 4px; color: #94a3b8; z-index: 10;">
                        <svg x-show="!showConfirm" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <svg x-show="showConfirm" class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                    </button>
                </div>
                <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-1.5 text-xs" />
            </div>
        </div>

        <!-- Tombol Simpan Sandi -->
        <div class="pt-2">
            <button type="submit" 
                    class="inline-flex items-center justify-center py-2.5 px-6 font-bold text-xs scp-btn-gradient cursor-pointer tracking-wider uppercase shadow-md transition-all duration-200"
                    style="border-radius: 12px;">
                Perbarui Kata Sandi
            </button>
        </div>
    </form>
</section>
