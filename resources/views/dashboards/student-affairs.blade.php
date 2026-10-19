<x-app-layout>
    <div x-data="{ activeTab: 'pending', searchStudent: '' }" class="space-y-6">

        <!-- HEADER PORTAL KESISWAAN & PERWALIAN -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">Persetujuan Izin Siswa</h1>
                <p class="text-xs font-semibold text-slate-400 mt-0.5">
                    Verifikasi surat dan permohonan izin siswa • {{ now()->translatedFormat('l, d F Y') }}
                </p>
            </div>
            <!-- BADGE HAK AKSES PERWALIAN / BK -->
            <div>
                <span class="inline-flex items-center gap-2.5 px-4 py-2 rounded-2xl bg-[#0f172a]/90 border border-indigo-500/40 text-xs sm:text-sm font-bold text-indigo-300 shadow-md">
                    <span class="h-2.5 w-2.5 rounded-full bg-indigo-400 animate-pulse"></span>
                    @if(auth()->user()->role?->role_name === 'Wali Kelas')
                        Wali Kelas: {{ auth()->user()->homeroomClass?->class_name ?? 'Belum Ditentukan' }}
                    @else
                        Guru BK (Bimbingan Konseling)
                    @endif
                </span>
            </div>
        </div>

        <!-- 3 KARTU STATISTIK RINGKAS -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <!-- Kartu 1: Menunggu Persetujuan -->
            <div class="bg-[#0f172a]/90 backdrop-blur-md p-5 border border-slate-800 shadow-xl" style="border-radius: 15px;">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Menunggu Verifikasi</span>
                <div class="text-3xl font-black text-white mt-1 mb-0.5">
                    {{ $pendingLeaves->count() }} <span class="text-xs font-semibold text-slate-400">Permohonan</span>
                </div>
                <p class="text-[11px] font-medium text-slate-400">Perlu tindakan persetujuan</p>
            </div>

            <!-- Kartu 2: Izin Sakit -->
            <div class="bg-[#0f172a]/90 backdrop-blur-md p-5 border border-slate-800 shadow-xl" style="border-radius: 15px;">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Permohonan Sakit</span>
                <div class="text-3xl font-black text-amber-400 mt-1 mb-0.5">
                    {{ $pendingLeaves->where('leave_type', 'Sakit')->count() }} <span class="text-xs font-semibold text-slate-400">Siswa</span>
                </div>
                <p class="text-[11px] font-medium text-slate-400">Disertai surat dokter / keterangan</p>
            </div>

            <!-- Kartu 3: Izin Keperluan -->
            <div class="bg-[#0f172a]/90 backdrop-blur-md p-5 border border-slate-800 shadow-xl" style="border-radius: 15px;">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Izin Keperluan</span>
                <div class="text-3xl font-black text-blue-400 mt-1 mb-0.5">
                    {{ $pendingLeaves->where('leave_type', 'Izin')->count() }} <span class="text-xs font-semibold text-slate-400">Siswa</span>
                </div>
                <p class="text-[11px] font-medium text-slate-400">Keperluan keluarga / dinas</p>
            </div>
        </div>

        <!-- KONTEN UTAMA: NAVIGASI TAB & KOLOM PENCARIAN -->
        <div class="bg-[#0f172a]/90 backdrop-blur-md border border-slate-800 shadow-xl overflow-hidden" style="border-radius: 15px;">
            <div class="p-4 sm:p-5 border-b border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <!-- Tab Switcher -->
                <div class="flex items-center gap-2">
                    <button type="button" @click="activeTab = 'pending'"
                            :class="activeTab === 'pending' ? 'scp-btn-gradient text-white shadow-md' : 'bg-slate-900 text-slate-400 hover:text-white hover:bg-slate-800 border border-slate-800'"
                            style="border-radius: 12px;"
                            class="px-4 py-2 text-xs font-bold transition flex items-center gap-2 cursor-pointer">
                        <span>Menunggu Verifikasi</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] bg-black/30 font-black">
                            {{ $pendingLeaves->count() }}
                        </span>
                    </button>

                    <button type="button" @click="activeTab = 'history'"
                            :class="activeTab === 'history' ? 'scp-btn-gradient text-white shadow-md' : 'bg-slate-900 text-slate-400 hover:text-white hover:bg-slate-800 border border-slate-800'"
                            style="border-radius: 12px;"
                            class="px-4 py-2 text-xs font-bold transition flex items-center gap-2 cursor-pointer">
                        <span>Riwayat Terverifikasi</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] bg-black/30 font-black">
                            {{ $processedLeaves->count() }}
                        </span>
                    </button>
                </div>

                <!-- Input Pencarian Cepat Nama Siswa / Kelas -->
                <div class="relative w-full sm:w-72">
                    <input type="text" x-model="searchStudent" 
                           placeholder="Cari siswa atau kelas..." 
                           class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2 pl-9 text-xs text-white placeholder-slate-500 focus:border-indigo-500 focus:bg-slate-900 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
            </div>

            {{-- ======================================================== --}}
            {{-- TAB 1: DAFTAR PERMOHONAN IZIN PENDING                    --}}
            {{-- ======================================================== --}}
            <div x-show="activeTab === 'pending'">
                @if($pendingLeaves->isEmpty())
                    <div class="text-center py-12 bg-slate-900/40">
                        <p class="text-sm font-bold text-white">Tidak Ada Permohonan Izin Pending</p>
                        <p class="text-xs text-slate-400 mt-1">Semua permohonan izin siswa telah selesai diproses atau belum ada pengajuan baru.</p>
                    </div>
                @else
                    <!-- Tampilan Mobile (Card View) -->
                    <div class="p-4 space-y-3.5 md:hidden">
                        @foreach ($pendingLeaves as $leave)
                            <div x-show="!searchStudent || '{{ strtolower(addslashes($leave->student_name)) }} {{ strtolower(addslashes($leave->class_name)) }}'.includes(searchStudent.toLowerCase())"
                                 class="p-4 rounded-xl border border-slate-800 bg-slate-900/60 hover:bg-slate-800/40 transition">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <span class="inline-block px-2.5 py-0.5 rounded-md text-[10px] font-bold uppercase border {{ $leave->leave_type === 'Sakit' ? 'bg-amber-950/60 text-amber-300 border-amber-800/60' : 'bg-blue-950/60 text-blue-300 border-blue-800/60' }}">
                                            {{ $leave->leave_type }}
                                        </span>
                                        <h3 class="font-bold text-white text-sm mt-1.5">{{ $leave->student_name }}</h3>
                                        <p class="text-xs text-slate-400">{{ $leave->class_name }}</p>
                                    </div>
                                    <span class="text-xs font-mono text-slate-300 shrink-0">
                                        {{ \Carbon\Carbon::parse($leave->start_date)->translatedFormat('d M') }}
                                        @if($leave->start_date !== $leave->end_date)
                                            - {{ \Carbon\Carbon::parse($leave->end_date)->translatedFormat('d M Y') }}
                                        @else
                                            {{ \Carbon\Carbon::parse($leave->start_date)->translatedFormat('Y') }}
                                        @endif
                                    </span>
                                </div>

                                <div class="mt-2.5 text-xs text-slate-300 bg-slate-950/40 p-2.5 rounded-lg border border-slate-800/60">
                                    <span class="text-[10px] uppercase font-bold text-slate-500 block mb-0.5">Alasan:</span>
                                    {{ $leave->reason }}
                                </div>

                                @if($leave->document_path)
                                    <div class="mt-3">
                                        <a href="{{ asset('storage/' . $leave->document_path) }}" target="_blank" 
                                           class="inline-flex items-center gap-1.5 text-xs font-bold text-indigo-400 hover:text-indigo-300 transition hover:underline">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                            Lihat Surat Bukti Izin →
                                        </a>
                                    </div>
                                @endif

                                <div class="mt-4 pt-3 border-t border-slate-800 flex gap-2">
                                    <form method="POST" action="{{ route('student-affairs.leaves.reject', $leave->id) }}" class="flex-1">
                                        @csrf
                                        <button type="submit" 
                                                class="w-full py-2 rounded-xl bg-red-950/60 border border-red-800/60 text-red-300 hover:bg-red-900 text-xs font-bold transition cursor-pointer"
                                                onclick="return confirm('Yakin ingin MENOLAK permohonan izin ini?')">
                                            Tolak
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('student-affairs.leaves.approve', $leave->id) }}" class="flex-1">
                                        @csrf
                                        <button type="submit" 
                                                class="w-full py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-md transition cursor-pointer"
                                                style="color: #ffffff !important;"
                                                onclick="return confirm('SETUJUI permohonan izin siswa ini?')">
                                            Setujui
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Tampilan Desktop (Tabel) -->
                    <div class="hidden overflow-x-auto md:block">
                        <table class="min-w-full divide-y divide-slate-800 text-sm">
                            <thead class="bg-slate-900/70">
                                <tr class="text-left text-xs font-bold text-slate-400 uppercase tracking-wider">
                                    <th class="p-4">Siswa & Kelas</th>
                                    <th class="p-4">Tanggal Izin</th>
                                    <th class="p-4">Jenis & Alasan</th>
                                    <th class="p-4 text-center">Dokumen Bukti</th>
                                    <th class="p-4 text-center">Tindakan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60">
                                @foreach ($pendingLeaves as $leave)
                                    <tr x-show="!searchStudent || '{{ strtolower(addslashes($leave->student_name)) }} {{ strtolower(addslashes($leave->class_name)) }}'.includes(searchStudent.toLowerCase())"
                                        class="hover:bg-slate-800/30 transition">
                                        <!-- Siswa & Kelas -->
                                        <td class="p-4">
                                            <div class="font-bold text-white">{{ $leave->student_name }}</div>
                                            <span class="inline-block mt-1 rounded-md bg-indigo-950/60 px-2 py-0.5 text-[11px] font-bold text-indigo-300 border border-indigo-800/60">
                                                {{ $leave->class_name }}
                                            </span>
                                        </td>

                                        <!-- Tanggal Izin -->
                                        <td class="p-4 text-xs font-mono text-slate-300 whitespace-nowrap">
                                            {{ \Carbon\Carbon::parse($leave->start_date)->translatedFormat('d M Y') }}
                                            @if($leave->start_date !== $leave->end_date)
                                                <div class="text-[11px] text-slate-400">s/d {{ \Carbon\Carbon::parse($leave->end_date)->translatedFormat('d M Y') }}</div>
                                            @endif
                                        </td>

                                        <!-- Jenis & Alasan -->
                                        <td class="p-4 max-w-xs">
                                            <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase border mb-1 {{ $leave->leave_type === 'Sakit' ? 'bg-amber-950/60 text-amber-300 border-amber-800/60' : 'bg-blue-950/60 text-blue-300 border-blue-800/60' }}">
                                                {{ $leave->leave_type }}
                                            </span>
                                            <p class="text-xs text-slate-300 line-clamp-2">{{ $leave->reason }}</p>
                                        </td>

                                        <!-- Dokumen Bukti -->
                                        <td class="p-4 text-center whitespace-nowrap">
                                            @if($leave->document_path)
                                                <a href="{{ asset('storage/' . $leave->document_path) }}" target="_blank" 
                                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-indigo-950/60 border border-indigo-800/60 text-indigo-300 hover:text-indigo-200 text-xs font-bold transition hover:border-indigo-700">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                    Lihat Berkas
                                                </a>
                                            @else
                                                <span class="text-xs text-slate-500 font-medium">Tanpa Lampiran</span>
                                            @endif
                                        </td>

                                        <!-- Tindakan Aksi (Tolak & Setujui) -->
                                        <td class="p-4 text-center whitespace-nowrap">
                                            <div class="inline-flex items-center gap-2">
                                                <!-- Tolak -->
                                                <form method="POST" action="{{ route('student-affairs.leaves.reject', $leave->id) }}">
                                                    @csrf
                                                    <button type="submit" 
                                                            class="px-3 py-1.5 rounded-xl bg-red-950/60 border border-red-800/60 text-red-300 hover:bg-red-900 text-xs font-bold transition cursor-pointer"
                                                            onclick="return confirm('Tolak permohonan izin ini?')">
                                                        Tolak
                                                    </button>
                                                </form>

                                                <!-- Setujui -->
                                                <form method="POST" action="{{ route('student-affairs.leaves.approve', $leave->id) }}">
                                                    @csrf
                                                    <button type="submit" 
                                                            class="px-4 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-md transition cursor-pointer"
                                                            style="color: #ffffff !important;"
                                                            onclick="return confirm('Setujui permohonan izin ini?')">
                                                        Setujui
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- ======================================================== --}}
            {{-- TAB 2: RIWAYAT IZIN TERVERIFIKASI (APPROVED / REJECTED)  --}}
            {{-- ======================================================== --}}
            <div x-show="activeTab === 'history'" x-cloak>
                @if($processedLeaves->isEmpty())
                    <div class="text-center py-12 bg-slate-900/40">
                        <p class="text-sm font-bold text-white">Belum Ada Riwayat Izin</p>
                        <p class="text-xs text-slate-400 mt-1">Belum ada permohonan izin yang pernah disetujui atau ditolak.</p>
                    </div>
                @else
                    <!-- Tampilan Mobile (Card View) -->
                    <div class="p-4 space-y-3.5 md:hidden">
                        @foreach ($processedLeaves as $leave)
                            <div x-show="!searchStudent || '{{ strtolower(addslashes($leave->student_name)) }} {{ strtolower(addslashes($leave->class_name)) }}'.includes(searchStudent.toLowerCase())"
                                 class="p-4 rounded-xl border border-slate-800 bg-slate-900/60 hover:bg-slate-800/40 transition">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <div class="flex items-center gap-1.5">
                                            <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase border {{ $leave->status === 'Approved' ? 'bg-emerald-950/60 text-emerald-300 border-emerald-800/60' : 'bg-red-950/60 text-red-300 border-red-800/60' }}">
                                                {{ $leave->status === 'Approved' ? 'Disetujui' : 'Ditolak' }}
                                            </span>
                                            <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase border {{ $leave->leave_type === 'Sakit' ? 'bg-amber-950/60 text-amber-300 border-amber-800/60' : 'bg-blue-950/60 text-blue-300 border-blue-800/60' }}">
                                                {{ $leave->leave_type }}
                                            </span>
                                        </div>
                                        <h3 class="font-bold text-white text-sm mt-1.5">{{ $leave->student_name }}</h3>
                                        <p class="text-xs text-slate-400">{{ $leave->class_name }}</p>
                                    </div>
                                    <span class="text-xs font-mono text-slate-300 shrink-0">
                                        {{ \Carbon\Carbon::parse($leave->start_date)->translatedFormat('d M Y') }}
                                    </span>
                                </div>

                                <div class="mt-2 text-xs text-slate-300">
                                    <span class="text-[10px] uppercase font-bold text-slate-500">Alasan:</span> {{ $leave->reason }}
                                </div>

                                <div class="mt-3 pt-2.5 border-t border-slate-800 flex items-center justify-between text-[11px] text-slate-400">
                                    <span>Oleh: <strong class="text-slate-300">{{ $leave->verifier_name }}</strong></span>
                                    <span>{{ $leave->processed_date }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Tampilan Desktop (Tabel) -->
                    <div class="hidden overflow-x-auto md:block">
                        <table class="min-w-full divide-y divide-slate-800 text-sm">
                            <thead class="bg-slate-900/70">
                                <tr class="text-left text-xs font-bold text-slate-400 uppercase tracking-wider">
                                    <th class="p-4">Siswa & Kelas</th>
                                    <th class="p-4">Tanggal Izin</th>
                                    <th class="p-4">Jenis & Alasan</th>
                                    <th class="p-4 text-center">Status Keputusan</th>
                                    <th class="p-4">Verifikator & Waktu</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60">
                                @foreach ($processedLeaves as $leave)
                                    <tr x-show="!searchStudent || '{{ strtolower(addslashes($leave->student_name)) }} {{ strtolower(addslashes($leave->class_name)) }}'.includes(searchStudent.toLowerCase())"
                                        class="hover:bg-slate-800/30 transition">
                                        <!-- Siswa & Kelas -->
                                        <td class="p-4">
                                            <div class="font-bold text-white">{{ $leave->student_name }}</div>
                                            <span class="inline-block mt-1 rounded-md bg-indigo-950/60 px-2 py-0.5 text-[11px] font-bold text-indigo-300 border border-indigo-800/60">
                                                {{ $leave->class_name }}
                                            </span>
                                        </td>

                                        <!-- Tanggal Izin -->
                                        <td class="p-4 text-xs font-mono text-slate-300 whitespace-nowrap">
                                            {{ \Carbon\Carbon::parse($leave->start_date)->translatedFormat('d M Y') }}
                                            @if($leave->start_date !== $leave->end_date)
                                                <div class="text-[11px] text-slate-400">s/d {{ \Carbon\Carbon::parse($leave->end_date)->translatedFormat('d M Y') }}</div>
                                            @endif
                                        </td>

                                        <!-- Jenis & Alasan -->
                                        <td class="p-4 max-w-xs">
                                            <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase border mb-1 {{ $leave->leave_type === 'Sakit' ? 'bg-amber-950/60 text-amber-300 border-amber-800/60' : 'bg-blue-950/60 text-blue-300 border-blue-800/60' }}">
                                                {{ $leave->leave_type }}
                                            </span>
                                            <p class="text-xs text-slate-300 line-clamp-2">{{ $leave->reason }}</p>
                                        </td>

                                        <!-- Status Keputusan -->
                                        <td class="p-4 text-center whitespace-nowrap">
                                            @if($leave->status === 'Approved')
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

                                        <!-- Verifikator & Waktu -->
                                        <td class="p-4 whitespace-nowrap text-xs">
                                            <div class="font-bold text-slate-200">{{ $leave->verifier_name }}</div>
                                            <div class="text-[11px] text-slate-400 font-mono">{{ $leave->processed_date }}</div>
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
