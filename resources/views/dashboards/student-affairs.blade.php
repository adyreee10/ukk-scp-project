<x-app-layout>
    <!-- KARTU 1: Menu Utama / Rekap Absensi -->
    <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200 mb-4 flex justify-between items-center">
        <div>
            <h2 class="font-bold text-slate-800 text-lg">Rekapitulasi Absensi</h2>
            <p class="text-sm text-slate-500">Lihat data kehadiran kelas.</p>
        </div>
        <a href="#" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2.5 text-white font-medium text-sm transition hover:bg-indigo-700">
            Lihat Rekap
        </a>
    </section>

    <!-- KARTU 2: Daftar Persetujuan Izin (Prioritas) -->
    <section class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
        <div class="mb-4">
            <h2 class="font-bold text-slate-800 text-lg">Menunggu Persetujuan</h2>
            <p class="text-sm text-slate-500">Prioritaskan izin yang berstatus Pending.</p>
        </div>
        
        <!-- Mobile View (Card Pattern) -->
        <div class="space-y-4 md:hidden">
            @forelse ($pendingLeaves as $leave)
                <article class="rounded-xl border p-4 shadow-sm relative overflow-hidden">
                    <!-- Garis penanda warna di kiri -->
                    <div class="absolute top-0 left-0 w-1 h-full {{ $leave->leave_type == 'Sakit' ? 'bg-red-500' : 'bg-amber-500' }}"></div>
                    
                    <div class="flex justify-between items-start mb-2 pl-2">
                        <div>
                            <p class="font-bold text-slate-800 text-base">{{ $leave->student_name }}</p>
                            <p class="text-xs font-medium text-slate-500">{{ $leave->class_name }}</p>
                        </div>
                        <span class="px-2.5 py-1 rounded-md text-xs font-bold {{ $leave->leave_type == 'Sakit' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700' }}">
                            {{ $leave->leave_type }}
                        </span>
                    </div>
                    
                    <div class="pl-2 mt-2 space-y-1">
                        <p class="text-xs text-slate-600"><strong>Tgl:</strong> {{ \Carbon\Carbon::parse($leave->start_date)->translatedFormat('d M') }} - {{ \Carbon\Carbon::parse($leave->end_date)->translatedFormat('d M Y') }}</p>
                        <p class="text-xs text-slate-600"><strong>Alasan:</strong> {{ $leave->reason }}</p>
                    </div>
                    
                    <div class="mt-4 pl-2 flex gap-2">
                        @if($leave->document_path)
                            <a href="#" class="flex-1 text-center py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg text-xs transition">Preview Surat</a>
                        @endif
                    </div>
                    <div class="mt-2 pl-2 flex gap-2">
                        <form method="POST" action="{{ route('student-affairs.leaves.reject', $leave->id) }}" class="flex-1 flex">
                            @csrf
                            <button type="submit" class="w-full bg-red-100 hover:bg-red-200 text-red-700 font-bold py-2.5 rounded-lg text-xs transition" onclick="return confirm('Yakin TOLAK izin ini?')">Reject</button>
                        </form>
                        <form method="POST" action="{{ route('student-affairs.leaves.approve', $leave->id) }}" class="flex-1 flex">
                            @csrf
                            <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 rounded-lg text-xs transition shadow-sm" onclick="return confirm('Setujui izin ini?')">Approve</button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="py-8 text-center bg-slate-50 rounded-lg border border-dashed border-slate-300">
                    <p class="text-sm text-slate-500 font-medium">Tidak ada permohonan izin baru.</p>
                </div>
            @endforelse
        </div>

        <!-- Desktop View (Table Pattern) -->
        <div class="hidden overflow-x-auto md:block">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead>
                    <tr class="text-left bg-slate-50">
                        <th class="p-4 font-semibold text-slate-700 rounded-tl-lg">Siswa</th>
                        <th class="p-4 font-semibold text-slate-700">Tanggal</th>
                        <th class="p-4 font-semibold text-slate-700">Jenis & Alasan</th>
                        <th class="p-4 font-semibold text-slate-700">Dokumen</th>
                        <th class="p-4 font-semibold text-slate-700 rounded-tr-lg">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($pendingLeaves as $leave)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="p-4">
                                <p class="font-bold text-slate-900">{{ $leave->student_name }}</p>
                                <p class="text-xs text-slate-500">{{ $leave->class_name }}</p>
                            </td>
                            <td class="p-4 text-slate-600 font-medium">
                                {{ \Carbon\Carbon::parse($leave->start_date)->translatedFormat('d M Y') }}
                            </td>
                            <td class="p-4">
                                <span class="px-2 py-0.5 rounded text-xs font-bold {{ $leave->leave_type == 'Sakit' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700' }}">
                                    {{ $leave->leave_type }}
                                </span>
                                <p class="text-xs text-slate-500 mt-1">{{ $leave->reason }}</p>
                            </td>
                            <td class="p-4">
                                @if($leave->document_path)
                                    <a href="#" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 underline">Preview PDF/IMG</a>
                                @else
                                    <span class="text-xs text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="p-4">
                                <div class="flex gap-2">
                                    <form method="POST" action="{{ route('student-affairs.leaves.reject', $leave->id) }}">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 bg-red-100 hover:bg-red-200 text-red-700 font-bold rounded text-xs transition" onclick="return confirm('Tolak izin ini?')">Reject</button>
                                    </form>
                                    <form method="POST" action="{{ route('student-affairs.leaves.approve', $leave->id) }}">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded text-xs shadow-sm transition" onclick="return confirm('Setujui izin ini?')">Approve</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-8 text-center text-slate-500 font-medium">Tidak ada permohonan izin baru.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-app-layout>
