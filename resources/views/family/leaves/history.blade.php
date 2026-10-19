<x-app-layout>
    <div x-data="{ activeTab: 'leaves' }" class="space-y-6">

        <!-- HEADER HALAMAN -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <a href="{{ route('family.dashboard') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-400 hover:text-white transition mb-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Kembali ke Dashboard
                </a>
                <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">Riwayat Izin & Rekapitulasi Presensi</h1>
                <p class="text-xs font-semibold text-slate-400 mt-0.5">
                    Lacak status permohonan izin dan rincian log kehadiran siswa
                </p>
            </div>
            <!-- TOMBOL AJUKAN IZIN BARU -->
            <div>
                <a href="{{ route('family.leaves.create') }}" 
                   style="border-radius: 12px; color: #ffffff !important;"
                   class="inline-flex items-center gap-2 py-2.5 px-4 font-bold text-xs scp-btn-gradient shadow-md hover:shadow-lg transition">
                    <svg class="w-4 h-4 text-white" style="color: #ffffff !important;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    <span style="color: #ffffff !important;">Buat Pengajuan Baru</span>
                </a>
            </div>
        </div>

        <!-- TAB NAVIGASI -->
        <div class="flex border-b border-slate-800 gap-2">
            <button @click="activeTab = 'leaves'" 
                    :class="activeTab === 'leaves' ? 'border-indigo-500 text-indigo-400 font-bold' : 'border-transparent text-slate-400 hover:text-white font-medium'"
                    class="py-3 px-4 text-xs border-b-2 transition flex items-center gap-2 cursor-pointer">
                <span>Riwayat Pengajuan Surat Izin</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] bg-slate-800 text-slate-300 font-bold">
                    {{ $leaveRequests->count() }}
                </span>
            </button>
            <button @click="activeTab = 'attendances'" 
                    :class="activeTab === 'attendances' ? 'border-indigo-500 text-indigo-400 font-bold' : 'border-transparent text-slate-400 hover:text-white font-medium'"
                    class="py-3 px-4 text-xs border-b-2 transition flex items-center gap-2 cursor-pointer">
                <span>Rekapitulasi Presensi Sesi KBM</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] bg-slate-800 text-slate-300 font-bold">
                    {{ $attendanceSummary['Total'] }}
                </span>
            </button>
        </div>

        <!-- KONTEN TAB 1: RIWAYAT PENGAJUAN IZIN -->
        <div x-show="activeTab === 'leaves'" class="space-y-4">
            <div class="bg-[#0f172a]/90 backdrop-blur-md border border-slate-800 shadow-xl overflow-hidden" style="border-radius: 15px;">
                @if($leaveRequests->isEmpty())
                    <div class="text-center py-16 bg-slate-900/40">
                        <p class="text-sm font-bold text-white">Belum Ada Pengajuan Izin</p>
                        <p class="text-xs text-slate-400 mt-1 mb-4">Anda belum pernah mengajukan surat izin sakit maupun permohonan dinas.</p>
                        <a href="{{ route('family.leaves.create') }}" 
                           style="border-radius: 12px; color: #ffffff !important;"
                           class="inline-flex items-center gap-2 py-2 px-4 font-bold text-xs scp-btn-gradient shadow-md">
                            <span style="color: #ffffff !important;">Buat Pengajuan Pertama</span>
                        </a>
                    </div>
                @else
                    <!-- Tampilan Mobile (Card View) -->
                    <div class="p-4 space-y-3.5 md:hidden">
                        @foreach ($leaveRequests as $req)
                            <div class="p-4 rounded-xl border border-slate-800 bg-slate-900/60 transition">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <div class="flex items-center gap-1.5 mb-1.5">
                                            <span class="inline-block px-2.5 py-0.5 rounded-md text-[10px] font-bold uppercase border {{ $req->leave_type === 'Sakit' ? 'bg-amber-950/60 text-amber-300 border-amber-800/60' : 'bg-blue-950/60 text-blue-300 border-blue-800/60' }}">
                                                {{ $req->leave_type }}
                                            </span>
                                            @if($req->status_approval === 'Pending')
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-950/60 text-amber-300 border border-amber-800/60">
                                                    Sedang Diproses
                                                </span>
                                            @elseif($req->status_approval === 'Approved')
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-950/60 text-emerald-300 border border-emerald-800/60">
                                                    Disetujui
                                                </span>
                                            @else
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-red-950/60 text-red-300 border border-red-800/60">
                                                    Ditolak
                                                </span>
                                            @endif
                                        </div>
                                        <p class="text-xs text-slate-200 font-semibold">{{ $req->reason }}</p>
                                    </div>
                                    <span class="text-xs font-mono text-slate-400 shrink-0">
                                        {{ \Carbon\Carbon::parse($req->start_date)->translatedFormat('d M') }}
                                    </span>
                                </div>

                                @if($req->document_path)
                                    <div class="mt-3 pt-2.5 border-t border-slate-800 flex justify-between items-center text-xs">
                                        <span class="text-slate-400">Lampiran Bukti:</span>
                                        <a href="{{ asset('storage/' . $req->document_path) }}" target="_blank" 
                                           class="font-bold text-indigo-400 hover:text-indigo-300 transition hover:underline">
                                            Lihat Berkas →
                                        </a>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <!-- Tampilan Desktop (Tabel) -->
                    <div class="hidden overflow-x-auto md:block">
                        <table class="min-w-full divide-y divide-slate-800 text-sm">
                            <thead class="bg-slate-900/70">
                                <tr class="text-left text-xs font-bold text-slate-400 uppercase tracking-wider">
                                    <th class="p-4">Jenis & Tanggal Izin</th>
                                    <th class="p-4">Alasan Permohonan</th>
                                    <th class="p-4 text-center">Lampiran</th>
                                    <th class="p-4 text-center">Status Verifikasi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60">
                                @foreach ($leaveRequests as $req)
                                    <tr class="hover:bg-slate-800/30 transition">
                                        <!-- Jenis & Tanggal -->
                                        <td class="p-4">
                                            <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase border mb-1 {{ $req->leave_type === 'Sakit' ? 'bg-amber-950/60 text-amber-300 border-amber-800/60' : 'bg-blue-950/60 text-blue-300 border-blue-800/60' }}">
                                                {{ $req->leave_type }}
                                            </span>
                                            <div class="text-xs font-mono text-slate-300">
                                                {{ \Carbon\Carbon::parse($req->start_date)->translatedFormat('d M Y') }}
                                                @if($req->start_date !== $req->end_date)
                                                    <span class="text-slate-500">s/d</span> {{ \Carbon\Carbon::parse($req->end_date)->translatedFormat('d M Y') }}
                                                @endif
                                            </div>
                                        </td>

                                        <!-- Alasan Izin -->
                                        <td class="p-4 max-w-sm text-xs text-slate-300">
                                            {{ $req->reason }}
                                        </td>

                                        <!-- Dokumen Lampiran -->
                                        <td class="p-4 text-center whitespace-nowrap">
                                            @if($req->document_path)
                                                <a href="{{ asset('storage/' . $req->document_path) }}" target="_blank" 
                                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-indigo-950/60 border border-indigo-800/60 text-indigo-300 hover:text-indigo-200 text-xs font-bold transition hover:border-indigo-700">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                    Lihat Surat
                                                </a>
                                            @else
                                                <span class="text-xs text-slate-500 font-medium">-</span>
                                            @endif
                                        </td>

                                        <!-- Status Verifikasi -->
                                        <td class="p-4 text-center whitespace-nowrap">
                                            @if($req->status_approval === 'Pending')
                                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-amber-950/70 border border-amber-700/60 text-amber-300 text-xs font-bold shadow-sm">
                                                    <span class="h-1.5 w-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                                                    Sedang Diproses
                                                </span>
                                            @elseif($req->status_approval === 'Approved')
                                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-emerald-950/70 border border-emerald-700/60 text-emerald-300 text-xs font-bold shadow-sm">
                                                    <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                                    Disetujui
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-red-950/70 border border-red-700/60 text-red-300 text-xs font-bold shadow-sm">
                                                    <svg class="w-3.5 h-3.5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                    Ditolak
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <!-- KONTEN TAB 2: REKAPITULASI PRESENSI SESI KBM -->
        <div x-show="activeTab === 'attendances'" style="display: none;" class="space-y-4">
            <div class="bg-[#0f172a]/90 backdrop-blur-md border border-slate-800 shadow-xl overflow-hidden" style="border-radius: 15px;">
                @if($attendances->isEmpty())
                    <div class="text-center py-16 bg-slate-900/40">
                        <p class="text-sm font-bold text-white">Belum Ada Rekap Presensi</p>
                        <p class="text-xs text-slate-400 mt-1">Data absensi kehadiran guru pada KBM belum tersedia untuk siswa ini.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-800 text-sm">
                            <thead class="bg-slate-900/70">
                                <tr class="text-left text-xs font-bold text-slate-400 uppercase tracking-wider">
                                    <th class="p-4">Tanggal & Waktu</th>
                                    <th class="p-4">Mata Pelajaran & Guru</th>
                                    <th class="p-4">Materi Jurnal</th>
                                    <th class="p-4 text-center">Status Kehadiran</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60">
                                @foreach($attendances as $att)
                                    <tr class="hover:bg-slate-800/30 transition">
                                        <td class="p-4 whitespace-nowrap text-xs font-mono text-slate-300">
                                            {{ \Carbon\Carbon::parse($att->date)->translatedFormat('l, d M Y') }}
                                        </td>
                                        <td class="p-4">
                                            <div class="text-xs font-bold text-white">
                                                {{ $att->journal?->schedule?->subject ?? 'Mata Pelajaran' }}
                                            </div>
                                            <div class="text-[11px] text-slate-400">
                                                {{ $att->journal?->schedule?->teacher?->name ?? 'Guru Pengajar' }}
                                            </div>
                                        </td>
                                        <td class="p-4 text-xs text-slate-300 max-w-xs truncate">
                                            {{ $att->journal?->topic_description ?? '-' }}
                                        </td>
                                        <td class="p-4 text-center whitespace-nowrap">
                                            @if($att->status === 'Hadir')
                                                <span class="inline-block px-3 py-1 rounded-xl text-xs font-bold bg-emerald-950/70 text-emerald-300 border border-emerald-700/60">
                                                    Hadir
                                                </span>
                                            @elseif($att->status === 'Sakit')
                                                <span class="inline-block px-3 py-1 rounded-xl text-xs font-bold bg-amber-950/70 text-amber-300 border border-amber-700/60">
                                                    Sakit
                                                </span>
                                            @elseif($att->status === 'Izin')
                                                <span class="inline-block px-3 py-1 rounded-xl text-xs font-bold bg-blue-950/70 text-blue-300 border border-blue-700/60">
                                                    Izin
                                                </span>
                                            @else
                                                <span class="inline-block px-3 py-1 rounded-xl text-xs font-bold bg-red-950/70 text-red-300 border border-red-700/60">
                                                    Alpa
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

    </div>
</x-app-layout>
