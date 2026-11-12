<x-app-layout>
    <div x-data="{ addYearModalOpen: false }" class="space-y-6">
        
        <!-- Header & Tombol Tambah Tahun Ajaran -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 w-full">
            <div>
                <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">Master Data Tahun Ajaran & Semester</h1>
                <p class="text-xs font-semibold text-slate-400 mt-0.5">Kelola periode akademik aktif, semester berjalan, dan riwayat linimasa angkatan sekolah</p>
            </div>
            <div>
                <!-- Tombol Tambah Tahun Ajaran Baru -->
                <button @click="addYearModalOpen = true" type="button" class="px-4 py-2.5 rounded-xl scp-btn-gradient text-xs font-extrabold text-white shadow-md cursor-pointer transition whitespace-nowrap">
                    + Tambah Tahun Ajaran
                </button>
            </div>
        </div>

        <!-- Banner Periode Realtime Aktif Saat Ini -->
        <div class="academic-banner p-6 rounded-2xl bg-gradient-to-r from-slate-900 via-indigo-950/40 to-slate-900 border border-indigo-500/30 shadow-xl relative overflow-hidden">            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6 relative z-10">
                <div class="space-y-2">
                    <div class="flex items-center gap-2.5">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                            PERIODE SEDANG BERJALAN
                        </span>
                        <span class="text-xs text-slate-400 font-semibold">• Realtime Sistem</span>
                    </div>

                    <div class="flex flex-wrap items-baseline gap-3 pt-1">
                        <span class="text-2xl sm:text-4xl font-black text-white tracking-tight">{{ $config->academic_year }}</span>
                        <span class="text-lg sm:text-2xl font-extrabold text-indigo-400">{{ $config->semester }}</span>
                    </div>

                    <p class="text-xs text-slate-300">
                        Periode ini berlaku secara global untuk seluruh jadwal pembelajaran, absensi, pengisian jurnal guru, dan portal siswa.
                    </p>
                </div>

                <!-- Status Tambahan -->
                <div class="grid grid-cols-2 gap-3 shrink-0 w-full md:w-auto">
                    <div class="academic-stat-box p-3 rounded-xl bg-slate-900/80 border border-slate-800 text-center">                        <span class="text-[10px] font-bold text-slate-400 uppercase block">Status Putaran</span>
                        <span class="text-sm font-extrabold text-white mt-0.5 block">{{ $config->active_cycle }}</span>
                    </div>
                    <div class="academic-stat-box p-3 rounded-xl bg-slate-900/80 border border-slate-800 text-center">                        <span class="text-[10px] font-bold text-slate-400 uppercase block">Jadwal ({{ $config->active_cycle }})</span>
                        <span class="text-sm font-extrabold {{ $config->isCycleLocked() ? 'text-red-400' : 'text-emerald-400' }} mt-0.5 block">
                            {{ $config->isCycleLocked() ? 'Terkunci' : 'Terbuka' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2 Kolom: Form Ganti Periode Aktif & Pemetaan Linimasa Angkatan -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- 1. Form Ganti Periode Aktif (Tahun Ajaran Selesai Otomatis Hilang dari Dropdown) -->
            <div class="p-5 rounded-2xl bg-[#0f172a]/90 border border-slate-800 shadow-xl space-y-4">
                <div class="border-b border-slate-800 pb-3">
                    <h2 class="text-sm font-extrabold text-white">Ganti Periode Aktif</h2>
                    <p class="text-[11px] text-slate-400 font-semibold mt-0.5">Hanya menampilkan tahun ajaran yang sedang berjalan dan mendatang</p>
                </div>

                <form method="POST" action="{{ route('waka.academic-period.update') }}" class="space-y-4 text-xs">
                    @csrf
                    <div>
                        <label class="block font-bold text-slate-300 mb-1.5">Tahun Ajaran</label>
                        <select name="academic_year" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2.5 text-white font-semibold focus:border-indigo-500">
                            @forelse($selectableYears as $sy)
                                <option value="{{ $sy->year }}" {{ $config->academic_year === $sy->year ? 'selected' : '' }}>
                                    {{ $sy->year }} ({{ $sy->status }})
                                </option>
                            @empty
                                <option value="{{ $config->academic_year }}">{{ $config->academic_year }} (Tahun ajaran berjalan)</option>
                            @endforelse
                        </select>
                        <span class="text-[10px] text-slate-500 mt-1 block">*Tahun ajaran yang sudah selesai tidak dapat dipilih lagi</span>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-300 mb-1.5">Semester Aktif</label>
                        <select name="semester" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2.5 text-white font-semibold focus:border-indigo-500">
                            <option value="Semester 1 (Ganjil)" {{ $config->semester === 'Semester 1 (Ganjil)' ? 'selected' : '' }}>Semester 1 (Ganjil)</option>
                            <option value="Semester 2 (Genap)" {{ $config->semester === 'Semester 2 (Genap)' ? 'selected' : '' }}>Semester 2 (Genap)</option>
                        </select>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full py-2.5 rounded-xl scp-btn-gradient text-xs font-bold text-white shadow-md cursor-pointer transition">
                            Terapkan Periode Aktif
                        </button>
                    </div>
                </form>
            </div>

            <!-- 2. Pemetaan Linimasa Angkatan dengan Filter Angkatan Masuk -->
            <div class="lg:col-span-2 space-y-4">
                
                <!-- Statistik Mini -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="p-4 rounded-2xl bg-[#0f172a]/90 border border-slate-800">
                        <span class="text-xs font-bold text-slate-400">Total Siswa Aktif</span>
                        <h3 class="text-xl font-black text-white mt-1">{{ $totalStudents }} Siswa</h3>
                    </div>

                    <div class="p-4 rounded-2xl bg-[#0f172a]/90 border border-slate-800">
                        <span class="text-xs font-bold text-slate-400">Total Siswa Lulus</span>
                        <h3 class="text-xl font-black text-emerald-400 mt-1">{{ $totalAlumni }} Alumni</h3>
                    </div>

                    <div class="p-4 rounded-2xl bg-[#0f172a]/90 border border-slate-800">
                        <span class="text-xs font-bold text-slate-400">Total Rombel Aktif</span>
                        <h3 class="text-xl font-black text-blue-400 mt-1">{{ $totalClasses }} Kelas</h3>
                    </div>
                </div>

                <!-- Card Linimasa Angkatan dengan Filter -->
                <div class="p-5 rounded-2xl bg-[#0f172a]/90 border border-slate-800 space-y-4 shadow-xl">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border-b border-slate-800 pb-3">
                        <div>
                            <h2 class="text-sm font-extrabold text-white">Pemetaan Linimasa Angkatan</h2>
                            <p class="text-[11px] text-slate-400 font-semibold mt-0.5">Lacak perjalanan tingkat kelas berdasarkan tahun masuk siswa</p>
                        </div>

                        <!-- Filter Angkatan Masuk Sekolah -->
                        <form method="GET" action="{{ route('waka.academic-year.index') }}" class="flex items-center gap-2">
                            <label class="text-xs font-bold text-slate-300 whitespace-nowrap">Angkatan Masuk:</label>
                            <select name="entry_year" onchange="this.form.submit()" class="rounded-xl border border-slate-700 bg-slate-900 pl-3.5 pr-9 py-1.5 text-xs font-bold text-amber-400 focus:border-indigo-500 cursor-pointer min-w-[145px]">
                                @foreach([2022, 2023, 2024, 2025, 2026] as $yr)
                                    <option value="{{ $yr }}" {{ $selectedEntryYear == $yr ? 'selected' : '' }}>
                                        Angkatan {{ $yr }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    </div>

                    <!-- Highlight Kesimpulan Status Angkatan -->
                    <div class="p-3 rounded-xl border flex items-center justify-between gap-3 {{ $entryStatusClass }}">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span class="text-xs font-extrabold">{{ $entryStatusText }}</span>
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-black border border-current shrink-0">
                            {{ $entryStatusBadge }}
                        </span>
                    </div>

                    <!-- Baris Linimasa 3 Tahun -->
                    <div class="space-y-2 text-xs">
                        @foreach($timeline as $tl)
                            <div class="flex items-center justify-between p-3 rounded-xl border transition {{ $tl['is_current'] ? 'bg-indigo-950/40 border-indigo-500/50 shadow-md' : 'bg-slate-900/60 border-slate-800' }}">
                                <div class="flex items-center gap-3">
                                    <span class="font-extrabold {{ $tl['is_current'] ? 'text-amber-400 text-sm' : 'text-slate-300' }}">
                                        {{ $tl['academic_year'] }}
                                    </span>
                                    @if($tl['is_current'])
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                                            Tahun Ajaran Berjalan
                                        </span>
                                    @endif
                                </div>
                                <span class="font-semibold {{ $tl['is_current'] ? 'text-white font-bold' : 'text-slate-400' }}">
                                    Siswa Angkatan {{ $selectedEntryYear }} berada di <strong class="{{ $tl['is_current'] ? 'text-indigo-300' : 'text-slate-300' }}">{{ $tl['grade_level'] }}</strong>
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>

            </div>

        </div>

        <!-- Tabel Riwayat Data Tahun Ajaran -->
        <div class="bg-[#0f172a]/90 backdrop-blur-md border border-slate-800 shadow-xl overflow-hidden rounded-2xl">
            <div class="p-4 border-b border-slate-800 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-extrabold text-white">Daftar Data Tahun Ajaran</h2>
                    <p class="text-xs text-slate-400">Tahun ajaran selesai terkunci permanen dan tidak dapat diaktifkan kembali</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-800/80 text-sm">
                    <thead class="bg-slate-900/70">
                        <tr class="text-left text-xs font-bold text-slate-400 uppercase tracking-wider">
                            <th class="p-4">Tahun Ajaran</th>
                            <th class="p-4">Keterangan</th>
                            <th class="p-4 text-center">Aksi Cepat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @foreach($academicYears as $ay)
                            <tr class="hover:bg-slate-800/30 transition {{ $ay->status === 'Tahun ajaran berjalan' ? 'bg-indigo-950/20' : '' }}">
                                <td class="p-4">
                                    <span class="text-sm font-black {{ $ay->status === 'Tahun ajaran berjalan' ? 'text-amber-400' : 'text-white' }}">
                                        {{ $ay->year }}
                                    </span>
                                </td>
                                <td class="p-4">
                                    @if($ay->status === 'Tahun ajaran berjalan')
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-extrabold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                                            Tahun ajaran berjalan
                                        </span>
                                    @elseif($ay->status === 'Tahun ajaran selesai')
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-slate-800 text-slate-400 border border-slate-700">
                                            Tahun ajaran selesai
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-500/10 text-blue-400 border border-blue-500/20">
                                            Tahun ajaran mendatang
                                        </span>
                                    @endif
                                </td>
                                <td class="p-4 text-center">
                                    @if($ay->status === 'Tahun ajaran selesai')
                                        <!-- Tahun Ajaran Selesai Terkunci Permanen -->
                                        <span class="inline-block px-3 py-1 rounded-lg text-xs font-bold text-slate-500 bg-slate-900/60 border border-slate-800 cursor-not-allowed">
                                            Terkunci / Selesai
                                        </span>
                                    @elseif($ay->status === 'Tahun ajaran berjalan')
                                        <span class="text-xs text-emerald-400 font-bold">
                                            ✓ Sedang Berjalan
                                        </span>
                                    @else
                                        <!-- Tahun Ajaran Mendatang Bisa Diaktifkan -->
                                        <form method="POST" action="{{ route('waka.academic-period.update') }}" class="inline">
                                            @csrf
                                            <input type="hidden" name="academic_year" value="{{ $ay->year }}" />
                                            <input type="hidden" name="semester" value="Semester 1 (Ganjil)" />
                                            <button type="submit" onclick="return confirm('Aktifkan tahun ajaran {{ $ay->year }}? Tahun ajaran sebelumnya akan otomatis diset Selesai dan tidak bisa diaktifkan kembali.')" class="px-3.5 py-1.5 rounded-lg text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white shadow transition cursor-pointer">
                                                Aktifkan
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ================= MODAL TAMBAH TAHUN AJARAN BARU ================= -->
        <div x-show="addYearModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div @click="addYearModalOpen = false" class="fixed inset-0 bg-black/70 backdrop-blur-sm"></div>
            <div class="relative w-full max-w-sm bg-[#0f172a] p-6 shadow-2xl border border-slate-800 space-y-4 rounded-2xl">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-white">Tambah Tahun Ajaran Baru</h3>
                    <button @click="addYearModalOpen = false" class="text-xs font-bold text-slate-400 hover:text-white px-2 py-1">[Tutup]</button>
                </div>

                <form method="POST" action="{{ route('waka.academic-year.store') }}" class="space-y-4 text-xs">
                    @csrf
                    <div>
                        <label class="block font-bold text-slate-300 mb-1">Tahun Ajaran</label>
                        <input type="text" name="year" required placeholder="Contoh: 2028/2029" pattern="\d{4}/\d{4}" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2.5 text-white placeholder-slate-500 focus:border-indigo-500 font-semibold" />
                        <span class="text-[11px] text-slate-400 mt-1 block">Format: 4 digit tahun / 4 digit tahun (misal: 2028/2029)</span>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 text-[11px] text-slate-300">
                        Status awal tahun ajaran baru akan otomatis diset sebagai <strong>Tahun ajaran mendatang</strong>.
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button @click="addYearModalOpen = false" type="button" class="px-4 py-2 rounded-xl text-slate-400 hover:bg-slate-800 hover:text-white transition cursor-pointer">Batal</button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl scp-btn-gradient font-bold text-white shadow-md cursor-pointer transition">Simpan Tahun Ajaran</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>
