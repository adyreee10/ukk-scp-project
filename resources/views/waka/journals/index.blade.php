<x-app-layout>
    <div x-data="{ 
        previewDocOpen: false, 
        previewDocUrl: '', 
        previewDocTitle: '',
        openPreviewDoc(url, title) { 
            this.previewDocUrl = url; 
            this.previewDocTitle = title; 
            this.previewDocOpen = true; 
        },
        closePreviewDoc() { 
            this.previewDocOpen = false; 
            this.previewDocUrl = ''; 
        } 
    }" class="space-y-6">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">Monitoring & Riwayat Jurnal Guru</h1>
            <p class="text-xs font-semibold text-slate-400 mt-0.5">
                Pantau kepatuhan pengisian jurnal KBM harian serta telusuri riwayat jurnal minggu dan bulan sebelumnya
            </p>
        </div>

        <!-- 1. STATUS KEPATUHAN HARI INI -->
        <div class="bg-[#0f172a]/90 backdrop-blur-md p-6 border border-slate-800 shadow-xl" style="border-radius: 15px;">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-extrabold text-white">Status Pengisian Hari Ini</h2>
                <span class="text-xs text-slate-400 font-medium">{{ now()->translatedFormat('l, d F Y') }}</span>
            </div>

            @if($lateTeachers->isEmpty())
            <div class="p-4 rounded-xl box-ideal shadow-sm">
                <div class="text-xs sm:text-sm font-semibold flex items-center gap-2">
                    <strong class="font-extrabold uppercase tracking-wide text-emerald-400">[Kondisi Ideal]</strong>
                    <span>Tidak ada keterlambatan pengisian jurnal hari ini. Seluruh guru telah mengisi jurnal tepat waktu.</span>
                </div>
            </div>

            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-800 text-sm">
                        <thead class="bg-slate-900/60 text-xs font-bold text-slate-400 uppercase tracking-wider">
                            <tr>
                                <th class="p-3 text-left">Nama Guru</th>
                                <th class="p-3 text-left">Mata Pelajaran</th>
                                <th class="p-3 text-left">Kelas & Jam</th>
                                <th class="p-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            @foreach ($lateTeachers as $late)
                                <tr class="hover:bg-slate-800/30 transition">
                                    <td class="p-3 font-bold text-white">{{ $late->teacher?->name }}</td>
                                    <td class="p-3 text-slate-300">{{ $late->subject }}</td>
                                    <td class="p-3 text-xs text-slate-400">
                                            {{ $late->schoolClass?->class_name }} • {{ $late->period_label }}
                                    </td>
                                    <td class="p-3 text-center">
                                        <span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-red-950/70 border border-red-700/60 text-red-300">
                                            Belum Mengisi (Terlambat)
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- 2. RIWAYAT JURNAL KBM LENGKAP -->
        <div class="bg-[#0f172a]/90 backdrop-blur-md p-6 border border-slate-800 shadow-xl space-y-4" style="border-radius: 15px;">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-base font-extrabold text-white">Arsip & Riwayat Jurnal KBM</h2>
                    <p class="text-xs text-slate-400">Telusuri catatan materi yang telah disampaikan guru</p>
                </div>
                
                <!-- Filter Bar Lengkap (Tanggal, Rekapan Bulan, Guru, Kelas, dan Preset) -->
                <form method="GET" action="{{ route('waka.journals.index') }}" class="flex flex-wrap items-center gap-2.5 text-xs">
                    <!-- Filter Tanggal Spesifik -->
                    <div class="flex items-center gap-1.5 bg-slate-900 px-3 py-1.5 rounded-xl border border-slate-700">
                        <span class="text-slate-400 font-bold uppercase text-[10px]">Tanggal:</span>
                        <input type="date" name="date" value="{{ request('date') }}" onchange="this.form.submit()" 
                               class="bg-transparent text-white text-xs font-semibold focus:outline-none cursor-pointer">
                    </div>

                    <!-- Filter Rekapan 1 Bulan -->
                    <div class="flex items-center gap-1.5 bg-slate-900 px-3 py-1.5 rounded-xl border border-slate-700">
                        <span class="text-slate-400 font-bold uppercase text-[10px]">Bulan:</span>
                        <input type="month" name="month" value="{{ request('month') }}" onchange="this.form.submit()" 
                               class="bg-transparent text-white text-xs font-semibold focus:outline-none cursor-pointer">
                    </div>

                    <!-- Filter Guru -->
                    <select name="teacher_id" onchange="this.form.submit()" class="bg-slate-900 border border-slate-700 text-white rounded-xl pl-3 pr-8 py-2 text-xs font-semibold focus:border-indigo-500 focus:outline-none cursor-pointer">
                        <option value="" class="bg-slate-900 text-white">Semua Guru</option>
                        @foreach($teachers as $t)
                            <option value="{{ $t->id }}" {{ request('teacher_id') == $t->id ? 'selected' : '' }} class="bg-slate-900 text-white">{{ $t->name }}</option>
                        @endforeach
                    </select>

                    <!-- Filter Kelas -->
                    <select name="class_id" onchange="this.form.submit()" class="bg-slate-900 border border-slate-700 text-white rounded-xl pl-3 pr-8 py-2 text-xs font-semibold focus:border-indigo-500 focus:outline-none cursor-pointer">
                        <option value="" class="bg-slate-900 text-white">Semua Kelas</option>
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}" {{ request('class_id') == $c->id ? 'selected' : '' }} class="bg-slate-900 text-white">{{ $c->class_name }}</option>
                        @endforeach
                    </select>

                    <!-- Tombol Reset Filter jika ada filter aktif -->
                    @if(request()->hasAny(['date', 'month', 'teacher_id', 'class_id', 'range']))
                        <a href="{{ route('waka.journals.index') }}" class="px-3 py-2 rounded-xl bg-red-500/10 text-red-400 border border-red-500/30 hover:bg-red-500/20 font-bold text-xs transition">
                            ✕ Reset Filter
                        </a>
                    @endif
                </form>
            </div>

            @if($journals->isEmpty())
                <div class="text-center py-12 bg-slate-900/40 rounded-xl">
                    <p class="text-sm font-bold text-white">Tidak Ada Data Jurnal</p>
                    <p class="text-xs text-slate-400 mt-1">Tidak ditemukan jurnal KBM pada rentang filter yang dipilih.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-800 text-sm">
                        <thead class="bg-slate-900/60 text-xs font-bold text-slate-400 uppercase tracking-wider">
                            <tr>
                                <th class="p-4 text-left">Tanggal</th>
                                <th class="p-4 text-left">Guru & Mapel</th>
                                <th class="p-4 text-left">Materi KBM</th>
                                <th class="p-4 text-center">Foto Bukti</th>
                                <th class="p-4 text-center">Kehadiran Siswa</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            @foreach ($journals as $j)
                                <tr class="hover:bg-slate-800/30 transition">
                                    <td class="p-4 whitespace-nowrap text-xs font-mono text-slate-300">
                                        {{ \Carbon\Carbon::parse($j->date)->translatedFormat('d M Y') }}
                                    </td>
                                    <td class="p-4">
                                        <p class="font-bold text-white text-xs">{{ $j->schedule?->teacher?->name }}</p>
                                        <p class="text-[11px] text-slate-400">{{ $j->schedule?->subject }} ({{ $j->schedule?->schoolClass?->class_name }})</p>
                                    </td>
                                    <td class="p-4 max-w-sm text-xs text-slate-300">
                                        {{ $j->topic_description }}
                                    </td>
                                    <td class="p-4 text-center whitespace-nowrap">
                                                                        @if($j->photo_path)
                                        <button type="button" 
                                                @click="openPreviewDoc('{{ url('media/' . $j->photo_path) }}', 'Foto Dokumentasi KBM: {{ addslashes($j->schedule?->subject ?? 'Jurnal') }} - {{ addslashes($j->schedule?->teacher?->name ?? 'Guru') }} ({{ addslashes($j->schedule?->schoolClass?->class_name ?? '') }})')"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold btn-photo shadow-sm transition cursor-pointer">
                                            Lihat Foto
                                        </button>
                                    @else

                                            <span class="text-slate-500 text-xs">-</span>
                                        @endif
                                    </td>
                                    <td class="p-4 text-center whitespace-nowrap text-xs">
                                        <span class="text-emerald-400 font-bold">H: {{ $j->attendances->where('status', 'Hadir')->count() }}</span> •
                                        <span class="text-amber-400 font-bold">S: {{ $j->attendances->where('status', 'Sakit')->count() }}</span> •
                                        <span class="text-blue-400 font-bold">I: {{ $j->attendances->where('status', 'Izin')->count() }}</span> •
                                        <span class="text-red-400 font-bold">A: {{ $j->attendances->where('status', 'Alpa')->count() }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($journals->hasPages())
                    <div class="pt-4 border-t border-slate-800">
                        {{ $journals->links() }}
                    </div>
                @endif
            @endif
        </div>
                <!-- MODAL PRATINJAU FOTO DOKUMENTASI KBM (LANGSUNG MUNCUL DI LAYAR) -->
        <div x-show="previewDocOpen" 
             x-cloak 
             class="fixed inset-0 z-50 overflow-y-auto" 
             style="display: none;"
             @keydown.escape.window="closePreviewDoc()">
            <div class="fixed inset-0 bg-black/80 backdrop-blur-sm transition-opacity" 
                 @click="closePreviewDoc()"></div>

            <div class="flex min-h-full items-center justify-center p-4 text-center">
                <div class="relative transform overflow-hidden rounded-2xl bg-[#0f172a] border border-slate-700 text-left shadow-2xl transition-all w-full max-w-2xl p-5"
                     @click.stop>
                    <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                        <h3 class="text-sm font-bold text-white" x-text="previewDocTitle"></h3>
                        <div class="flex items-center gap-2">
                            <a :href="previewDocUrl" target="_blank" 
                               class="text-xs text-indigo-400 hover:text-indigo-300 font-bold hover:underline flex items-center gap-1">
                                <span>Buka Penuh</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                            </a>
                            <button type="button" @click="closePreviewDoc()" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 transition cursor-pointer">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                    </div>

                    <div class="mt-4 flex items-center justify-center bg-slate-950/70 rounded-xl p-2 min-h-[300px] border border-slate-800/80 overflow-hidden">
                        <img :src="previewDocUrl" alt="Foto Dokumentasi KBM" class="max-h-[500px] w-auto max-w-full rounded-lg object-contain shadow-md" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
