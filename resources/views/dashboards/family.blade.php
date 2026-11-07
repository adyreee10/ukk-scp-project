<x-app-layout>
    <div class="space-y-6">

        <!-- HEADER PORTAL KELUARGA & SISWA -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">Dashboard Presensi & Siswa</h1>
                <p class="text-xs font-semibold text-slate-400 mt-0.5">
                    Ringkasan statistik kehadiran siswa dan akses cepat layanan KBM
                </p>
            </div>
            <!-- BADGE IDENTITAS SISWA -->
            <div>
                <span class="inline-flex items-center gap-2.5 px-4 py-2 rounded-2xl bg-[#0f172a]/90 border border-indigo-500/40 text-xs sm:text-sm font-bold text-indigo-300 shadow-md">
                    <span class="h-2.5 w-2.5 rounded-full bg-indigo-400 animate-pulse"></span>
                    {{ auth()->user()->role?->role_name === 'Orang Tua' ? 'Orang tua dari:' : 'Siswa:' }}
                    <strong class="text-white">{{ $student->name ?? auth()->user()->name }}</strong>
                    @if(isset($student->schoolClass))
                        • {{ $student->schoolClass->class_name }}
                    @endif
                </span>
            </div>
        </div>

        <!-- BANNER TINGKAT KEHADIRAN (ATTENDANCE RATE) -->
        <div class="bg-[#0f172a]/90 backdrop-blur-md p-6 border border-slate-800 shadow-xl flex flex-col md:flex-row items-center justify-between gap-6" style="border-radius: 15px;">
            <div class="space-y-1.5 text-center md:text-left">
                <span class="px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-emerald-950/70 border border-emerald-700/60 text-emerald-300">
                    Status Akademik Aktif
                </span>
                <h2 class="text-lg font-bold text-white mt-1">Tingkat Kehadiran Pembelajaran</h2>
                <p class="text-xs text-slate-400 max-w-xl">
                    Persentase akumulasi kehadiran fisik siswa dalam mengikuti kegiatan belajar mengajar (KBM) pada semester berjalan.
                </p>
            </div>
            <div class="flex items-center gap-4 shrink-0">
                <div class="text-center">
                    <div class="text-4xl font-black {{ $attendanceSummary['Rate'] >= 85 ? 'text-emerald-400' : ($attendanceSummary['Rate'] >= 75 ? 'text-amber-400' : 'text-red-400') }}">
                        {{ $attendanceSummary['Rate'] }}%
                    </div>
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Persentase Hadir</span>
                </div>
            </div>
        </div>

        <!-- 4 KARTU STATISTIK KEHADIRAN SISWA -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <!-- Hadir -->
            <div class="bg-[#0f172a]/90 backdrop-blur-md p-5 border border-slate-800 shadow-xl" style="border-radius: 15px;">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Hadir</span>
                <div class="text-3xl font-black text-emerald-400 mt-1 mb-0.5">
                    {{ $attendanceSummary['Hadir'] }} <span class="text-xs font-semibold text-slate-400">Sesi</span>
                </div>
                <p class="text-[11px] font-medium text-slate-400">Kehadiran fisik di KBM</p>
            </div>

            <!-- Sakit -->
            <div class="bg-[#0f172a]/90 backdrop-blur-md p-5 border border-slate-800 shadow-xl" style="border-radius: 15px;">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Izin Sakit</span>
                <div class="text-3xl font-black text-amber-400 mt-1 mb-0.5">
                    {{ $attendanceSummary['Sakit'] }} <span class="text-xs font-semibold text-slate-400">Sesi</span>
                </div>
                <p class="text-[11px] font-medium text-slate-400">Dengan surat keterangan</p>
            </div>

            <!-- Izin -->
            <div class="bg-[#0f172a]/90 backdrop-blur-md p-5 border border-slate-800 shadow-xl" style="border-radius: 15px;">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Izin Keperluan</span>
                <div class="text-3xl font-black text-blue-400 mt-1 mb-0.5">
                    {{ $attendanceSummary['Izin'] }} <span class="text-xs font-semibold text-slate-400">Sesi</span>
                </div>
                <p class="text-[11px] font-medium text-slate-400">Disetujui wali kelas/BK</p>
            </div>

            <!-- Alpa -->
            <div class="bg-[#0f172a]/90 backdrop-blur-md p-5 border border-slate-800 shadow-xl" style="border-radius: 15px;">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Alpa / Tanpa Ket.</span>
                <div class="text-3xl font-black text-red-400 mt-1 mb-0.5">
                    {{ $attendanceSummary['Alpa'] }} <span class="text-xs font-semibold text-slate-400">Sesi</span>
                </div>
                <p class="text-[11px] font-medium text-slate-400">Tidak hadir tanpa izin</p>
            </div>
        </div>

        <!-- 2 KARTU MENU CEPAT (MENU NAVIGASI PINTAS) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <!-- Shortcut Pengajuan Izin -->
            <div class="bg-[#0f172a]/90 backdrop-blur-md p-6 border border-slate-800 shadow-xl flex flex-col justify-between gap-4" style="border-radius: 15px;">
                <div class="space-y-1.5">
                    <div class="w-10 h-10 rounded-xl bg-indigo-500/10 border border-indigo-500/30 flex items-center justify-center text-indigo-400 mb-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    </div>
                    <h3 class="text-base font-bold text-white">Formulir Pengajuan Izin</h3>
                    <p class="text-xs text-slate-400">
                        Berhalangan mengikuti pembelajaran karena sakit atau keperluan penting? Ajukan surat permohonan izin secara mandiri.
                    </p>
                </div>
                <a href="{{ route('family.leaves.create') }}" 
                   style="border-radius: 12px; color: #ffffff !important;"
                   class="inline-flex items-center justify-center gap-2 py-3 px-5 font-bold text-xs scp-btn-gradient shadow-md hover:shadow-lg transition">
                    <span style="color: #ffffff !important;">Buka Formulir Pengajuan Izin</span>
                    <svg class="w-4 h-4 text-white" style="color: #ffffff !important;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                </a>
            </div>

            <!-- Shortcut Riwayat & Rekapan -->
            <div class="bg-[#0f172a]/90 backdrop-blur-md p-6 border border-slate-800 shadow-xl flex flex-col justify-between gap-4" style="border-radius: 15px;">
                <div class="space-y-1.5">
                    <div class="w-10 h-10 rounded-xl bg-blue-500/10 border border-blue-500/30 flex items-center justify-center text-blue-400 mb-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                    </div>
                    <h3 class="text-base font-bold text-white">Riwayat & Rekapitulasi Presensi</h3>
                    <p class="text-xs text-slate-400">
                        Pantau status persetujuan surat izin (Disetujui/Ditolak) serta log rincian presensi kehadiran siswa di setiap sesi KBM.
                    </p>
                </div>
                <a href="{{ route('family.leaves.history') }}" 
                   class="inline-flex items-center justify-center gap-2 py-3 px-5 rounded-xl font-bold text-xs bg-slate-800/80 hover:bg-slate-700/80 text-white border border-slate-700 transition">
                    <span>Lihat Riwayat & Rekap Presensi</span>
                    <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                </a>
            </div>
        </div>

        <!-- RINGKASAN 3 PENGAJUAN IZIN TERAKHIR -->
        <div class="bg-[#0f172a]/90 backdrop-blur-md border border-slate-800 shadow-xl overflow-hidden" style="border-radius: 15px;">
            <div class="p-5 border-b border-slate-800 flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-base font-bold text-white">Pengajuan Izin Terkini</h2>
                        @if($recentLeaves->isNotEmpty())
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                                {{ $recentLeaves->count() }}
                            </span>
                        @endif
                    </div>
                    <p class="text-xs text-slate-400 mt-0.5">
                        @if($recentLeaves->isEmpty())
                            Belum ada permohonan izin yang diajukan
                        @else
                            {{ $recentLeaves->count() }} permohonan izin terakhir yang diajukan
                        @endif
                    </p>
                </div>
                <a href="{{ route('family.leaves.history') }}" class="text-xs font-bold text-indigo-400 hover:text-indigo-300 transition hover:underline">
                    Lihat Semua Riwayat →
                </a>
            </div>

            @if($recentLeaves->isEmpty())
                <div class="text-center py-10 px-4 bg-slate-900/30">
                    <div class="w-12 h-12 mx-auto mb-3 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <p class="text-sm font-bold text-white">Belum Ada Pengajuan Izin</p>
                    <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">Siswa belum pernah mengajukan permohonan izin sakit atau keperluan.</p>
                    <div class="mt-4">
                        <a href="{{ route('family.leaves.create') }}" 
                           class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white shadow-md transition cursor-pointer">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            <span>Buat Pengajuan Izin Baru</span>
                        </a>
                    </div>
                </div>
            @else
                <div class="divide-y divide-slate-800/60">
                    @foreach($recentLeaves as $req)
                        <div class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-slate-800/30 transition">
                            <div class="flex items-start gap-3">
                                <span class="px-2.5 py-1 rounded-md text-[10px] font-bold uppercase border shrink-0 {{ $req->leave_type === 'Sakit' ? 'bg-amber-950/60 text-amber-300 border-amber-800/60' : 'bg-blue-950/60 text-blue-300 border-blue-800/60' }}">
                                    {{ $req->leave_type }}
                                </span>
                                <div>
                                    <p class="text-xs font-semibold text-slate-200">{{ $req->reason }}</p>
                                    <p class="text-[11px] font-mono text-slate-400 mt-0.5">
                                        {{ \Carbon\Carbon::parse($req->start_date)->translatedFormat('d M Y') }}
                                            @if(\Carbon\Carbon::parse($req->start_date)->toDateString() != \Carbon\Carbon::parse($req->end_date)->toDateString())
                                            s/d {{ \Carbon\Carbon::parse($req->end_date)->translatedFormat('d M Y') }}
                                        @endif
                                    </p>
                                    @if($req->status_approval === 'Rejected' && $req->rejection_note)
                                        <p class="text-[11px] text-red-300 mt-1">
                                            <span class="font-bold text-red-400">Alasan Ditolak:</span> {{ $req->rejection_note }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-3 shrink-0 self-end sm:self-center">
                                @if($req->status_approval === 'Pending')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-amber-950/70 border border-amber-700/60 text-amber-300 text-xs font-bold">
                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                                        Sedang Diproses
                                    </span>
                                @elseif($req->status_approval === 'Approved')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-emerald-950/70 border border-emerald-700/60 text-emerald-300 text-xs font-bold">
                                        Disetujui
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-red-950/70 border border-red-700/60 text-red-300 text-xs font-bold">
                                        Ditolak
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>
</x-app-layout>
