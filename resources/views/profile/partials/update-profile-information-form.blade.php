<section>
    <header class="border-b border-slate-800 pb-3 mb-5">
        <h2 class="text-base font-extrabold text-white">
            Ubah Informasi Pribadi
        </h2>
        <p class="mt-1 text-xs text-slate-400">
            Perbarui nama lengkap, username login, nomor kontak WhatsApp, alamat email, dan alamat tempat tinggal Anda.
        </p>
    </header>

    @if (session('status') === 'profile-updated')
        <div role="status" class="mb-4 p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-xs font-bold text-emerald-300 flex items-center justify-between">
            <span>Perubahan data profil Anda berhasil disimpan!</span>
            <span class="text-[10px] uppercase tracking-wider text-emerald-400 bg-emerald-500/20 px-2 py-0.5 rounded font-black">Tersimpan</span>
        </div>
    @endif

    <form method="post" action="{{ route('profile.update') }}" class="space-y-4">
        @csrf
        @method('patch')

        <!-- Nama Lengkap -->
        <div>
            <label for="name" class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                Nama Lengkap
            </label>
            <input id="name" 
                   name="name" 
                   type="text" 
                   class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" 
                   value="{{ old('name', $user->name) }}" 
                   required 
                   autofocus 
                   placeholder="Nama lengkap Anda" />
            <x-input-error class="mt-1.5 text-xs" :messages="$errors->get('name')" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Username Login -->
            <div>
                <label for="username" class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                    Username Akun
                </label>
                <input id="username" 
                       name="username" 
                       type="text" 
                       class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2.5 text-sm text-white font-mono placeholder-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" 
                       value="{{ old('username', $user->username) }}" 
                       required 
                       placeholder="Username untuk masuk sistem" />
                <x-input-error class="mt-1.5 text-xs" :messages="$errors->get('username')" />
            </div>

            <!-- Nomor HP / WhatsApp -->
            <div>
                <label for="phone" class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                    Nomor HP / WhatsApp
                </label>
                <input id="phone" 
                       name="phone" 
                       type="text" 
                       class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2.5 text-sm text-white font-mono placeholder-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" 
                       value="{{ old('phone', $user->phone) }}" 
                       placeholder="Contoh: 081234567890" />
                <x-input-error class="mt-1.5 text-xs" :messages="$errors->get('phone')" />
            </div>
        </div>

        <!-- Alamat Email -->
        <div>
            <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                Alamat Email Akun
            </label>
            <input id="email" 
                   name="email" 
                   type="email" 
                   class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" 
                   value="{{ old('email', $user->email) }}" 
                   required 
                   placeholder="alamat@sekolah.sch.id" />
            <x-input-error class="mt-1.5 text-xs" :messages="$errors->get('email')" />
        </div>

        <!-- Alamat Tempat Tinggal (Dapat Diubah oleh Semua User) -->
        <div>
            <label for="address" class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                Alamat Tempat Tinggal
            </label>
            <textarea id="address" 
                      name="address" 
                      rows="3" 
                      class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" 
                      placeholder="Masukkan alamat lengkap tempat tinggal Anda...">{{ old('address', $user->address) }}</textarea>
            <x-input-error class="mt-1.5 text-xs" :messages="$errors->get('address')" />
        </div>

        <!-- Tombol Simpan -->
        <div class="pt-2">
            <button type="submit" 
                    class="inline-flex items-center justify-center py-2.5 px-6 font-bold text-xs scp-btn-gradient cursor-pointer tracking-wider uppercase shadow-md transition-all duration-200"
                    style="border-radius: 12px;">
                Simpan Informasi Profil
            </button>
        </div>
    </form>
</section>
