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

        <!-- HEADER RIWAYAT JURNAL -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">Riwayat Pengisian Jurnal KBM</h1>
                <p class="text-xs font-semibold text-slate-400 mt-0.5">
                    Arsip lengkap catatan materi, kehadiran siswa, serta dokumentasi pembelajaran yang telah Anda laksanakan
                </p>
            </div>
            <a href="{{ route('teacher.journals.create') }}" 
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl scp-btn-gradient text-xs font-extrabold text-white shadow-lg shadow-indigo-600/30 transition cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span>Isi Jurnal Baru</span>
            </a>
        </div>

        <!-- PERINGATAN JURNAL TERLAMBAT / BELUM DIISI HARI INI -->
        @if($overdueSchedules->isNotEmpty())
            <div class="p-4 rounded-2xl bg-amber-950/40 border border-amber-600/50 text-amber-200 text-xs space-y-2">
                <div class="flex items-center gap-2 font-black text-amber-300">
                    <span class="h-2 w-2 rounded-full bg-amber-400 animate-pulse"></span>
                    <span>Perhatian: Anda memiliki jadwal mengajar hari ini yang belum diisi jurnalnya:</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5 pt-1">
                    @foreach($overdueSchedules as $os)
                        <div class="p-3 rounded-xl bg-slate-900/90 border border-amber-500/30 flex items-center justify-between">
                            <div>
                                <p class="font-bold text-white">{{ $os->subject }}</p>
                                <p class="text-[11px] text-slate-400">Kelas {{ $os->schoolClass?->class_name }} • {{ $os->start_time }} - {{ $os->end_time }}</p>
                            </div>
                            <a href="{{ route('teacher.journals.create', ['schedule_id' => $os->id]) }}" class="px-2.5 py-1 rounded-lg bg-indigo-600 text-white font-bold text-[11px] hover:bg-indigo-500">
                                Isi Sekarang
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- FILTER BAR -->
        <div class="bg-[#0f172a]/90 backdrop-blur-md p-4 rounded-2xl border border-slate-800 shadow-xl">
            <form method="GET" action="{{ route('teacher.journals.history') }}" class="flex flex-wrap items-center gap-3 text-xs">
                <!-- Filter Kelas -->
                <div class="flex items-center gap-1.5 bg-slate-900 px-3 py-1.5 rounded-xl border border-slate-700">
                    <span class="text-slate-400 font-bold uppercase text-[10px]">Kelas:</span>
                    <select name="class_id" onchange="this.form.submit()" class="bg-transparent text-white text-xs font-semibold focus:outline-none cursor-pointer">
                        <option value="" class="bg-slate-900 text-white">Semua Kelas</option>
                        @foreach($myClasses as $c)
                            <option value="{{ $c->id }}" {{ request('class_id') == $c->id ? 'selected' : '' }} class="bg-slate-900 text-white">
                                Kelas {{ $c->class_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Bulan -->
                <div class="flex items-center gap-1.5 bg-slate-900 px-3 py-1.5 rounded-xl border border-slate-700">
                    <span class="text-slate-400 font-bold uppercase text-[10px]">Bulan:</span>
                    <input type="month" name="month" value="{{ request('month') }}" onchange="this.form.submit()" class="bg-transparent text-white text-xs font-semibold focus:outline-none cursor-pointer">
                </div>

                @if(request()->hasAny(['class_id', 'month']))
                    <a href="{{ route('teacher.journals.history') }}" class="px-2.5 py-1.5 rounded-xl bg-red-500/10 text-red-400 border border-red-500/30 hover:bg-red-500/20 font-bold text-xs">
                        ✕ Reset Filter
                    </a>
                @endif
            </form>
        </div>

        <!-- TABEL RIWAYAT JURNAL -->
        <div class="bg-[#0f172a]/90 backdrop-blur-md border border-slate-800 shadow-xl overflow-hidden rounded-2xl">
            @if($journals->isEmpty())
                <div class="text-center py-16 text-slate-400 text-xs">
                    <p class="font-bold text-white text-sm mb-1">Belum Ada Riwayat Jurnal</p>
                    <p>Anda belum mengisi jurnal KBM untuk kriteria pencarian ini.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-800 text-sm">
                        <thead class="bg-slate-900/70">
                            <tr class="text-left text-xs font-bold text-slate-400 uppercase tracking-wider">
                                <th class="p-4">Tanggal KBM</th>
                                <th class="p-4">Kelas & Mapel</th>
                                <th class="p-4">Materi Pembelajaran</th>
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
                                        <p class="font-bold text-white text-xs">{{ $j->schedule?->subject ?? 'Mata Pelajaran' }}</p>
                                        <p class="text-[11px] text-slate-400">Kelas {{ $j->schedule?->schoolClass?->class_name ?? '-' }} • {{ $j->schedule?->room?->room_name ?? 'Ruang Teori' }}</p>
                                    </td>
                                    <td class="p-4 max-w-sm text-xs text-slate-300">
                                        {{ $j->topic_description }}
                                    </td>
                                    <td class="p-4 text-center whitespace-nowrap">
                                        @if($j->photo_path)
                                            <button type="button" 
                                                    @click="openPreviewDoc('{{ url('media/' . $j->photo_path) }}', 'Foto Dokumentasi KBM - {{ addslashes($j->schedule?->subject ?? '') }} ({{ addslashes($j->schedule?->schoolClass?->class_name ?? '') }})')"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold btn-photo shadow-sm transition cursor-pointer">
                                                Lihat Foto
                                            </button>
                                        @else
                                            <span class="text-slate-500 text-xs">-</span>
                                        @endif
                                    </td>
                                    <td class="p-4 text-center whitespace-nowrap text-xs font-mono">
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
                    <div class="p-4 border-t border-slate-800">
                        {{ $journals->links() }}
                    </div>
                @endif
            @endif
        </div>

        <!-- MODAL PRATINJAU FOTO DOKUMENTASI KBM -->
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
