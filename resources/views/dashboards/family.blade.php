<x-app-layout>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        
        <!-- KOLOM KIRI: Ringkasan & Aksi -->
        <div class="md:col-span-1 space-y-4">
            <!-- KARTU 1: Ringkasan Kehadiran -->
            <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h2 class="font-bold text-slate-800 text-lg mb-4">Ringkasan Kehadiran</h2>
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-emerald-50 rounded-lg p-3 border border-emerald-100 text-center">
                        <p class="text-xs font-semibold text-emerald-600 uppercase tracking-wider mb-1">Hadir</p>
                        <p class="text-2xl font-bold text-emerald-700">{{ $attendanceSummary['Hadir'] }}</p>
                    </div>
                    <div class="bg-amber-50 rounded-lg p-3 border border-amber-100 text-center">
                        <p class="text-xs font-semibold text-amber-600 uppercase tracking-wider mb-1">Izin</p>
                        <p class="text-2xl font-bold text-amber-700">{{ $attendanceSummary['Izin'] }}</p>
                    </div>
                    <div class="bg-red-50 rounded-lg p-3 border border-red-100 text-center">
                        <p class="text-xs font-semibold text-red-600 uppercase tracking-wider mb-1">Sakit</p>
                        <p class="text-2xl font-bold text-red-700">{{ $attendanceSummary['Sakit'] }}</p>
                    </div>
                    <div class="bg-slate-100 rounded-lg p-3 border border-slate-200 text-center">
                        <p class="text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Alpa</p>
                        <p class="text-2xl font-bold text-slate-700">{{ $attendanceSummary['Alpa'] }}</p>
                    </div>
                </div>
            </section>

                        <!-- KARTU 2: Aksi Pengajuan Izin -->
            <section x-data="{ open: false }" class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h2 class="font-bold text-slate-800 text-lg mb-2">Pengajuan Izin</h2>
                <p class="text-sm text-slate-500 mb-4">Upload surat dokter atau keterangan izin di sini.</p>
                <button @click="open = true" type="button" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-xl transition flex items-center justify-center gap-2 shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Buat Pengajuan Baru
                </button>

                <!-- MODAL FORM PENGAJUAN IZIN -->
                <div x-show="open" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm">
                    <div @click.away="open = false" class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl">
                        <div class="flex items-center justify-between border-b pb-3 mb-4">
                            <h3 class="font-bold text-slate-800 text-lg">Formulir Pengajuan Izin</h3>
                            <button @click="open = false" type="button" class="text-slate-400 hover:text-slate-600 font-bold text-xl">&times;</button>
                        </div>

                        <form method="POST" action="{{ route('family.leaves.store') }}" enctype="multipart/form-data" class="space-y-4">
                            @csrf
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Jenis Izin</label>
                                <select name="leave_type" required class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="Sakit">Sakit</option>
                                    <option value="Izin">Izin</option>
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Mulai Tanggal</label>
                                    <input type="date" name="start_date" required value="{{ date('Y-m-d') }}" class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Sampai Tanggal</label>
                                    <input type="date" name="end_date" required value="{{ date('Y-m-d') }}" class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Alasan / Keterangan</label>
                                <textarea name="reason" rows="3" required placeholder="Tuliskan keterangan izin secara jelas..." class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Unggah Surat Bukti (Wajib PDF/JPG/PNG)</label>
                                <input type="file" name="document" required accept=".pdf,.jpg,.jpeg,.png" class="w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                            </div>

                            <div class="flex gap-2 pt-2">
                                <button @click="open = false" type="button" class="flex-1 py-2.5 rounded-lg border border-slate-300 text-slate-700 font-semibold text-sm hover:bg-slate-50 transition">Batal</button>
                                <button type="submit" class="flex-1 py-2.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm transition shadow-sm">Kirim Pengajuan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </section>
        </div>

        <!-- KOLOM KANAN: Riwayat Pengajuan & Presensi -->
        <div class="md:col-span-2 space-y-4">
            <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200 h-full">
                <h2 class="font-bold text-slate-800 text-lg mb-4">Riwayat Pengajuan</h2>
                
                <div class="space-y-3">
                    @forelse($leaveRequests as $req)
                        <div class="flex items-center justify-between p-3 rounded-lg border border-slate-100 bg-slate-50 hover:bg-slate-100 transition">
                            <div>
                                <p class="font-bold text-slate-800">
                                    {{ $req->leave_type }}
                                </p>
                                <p class="text-xs text-slate-500">
                                    Diajukan pada {{ \Carbon\Carbon::parse($req->start_date)->translatedFormat('d F Y') }}
                                </p>
                            </div>
                            
                            @if($req->status == 'Pending')
                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-700 border border-amber-200">
                                    Diproses
                                </span>
                            @elseif($req->status == 'Approved')
                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700 border border-emerald-200">
                                    Disetujui
                                </span>
                            @else
                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700 border border-red-200">
                                    Ditolak
                                </span>
                            @endif
                        </div>
                    @empty
                        <div class="text-center py-6 text-slate-500">
                            <p class="text-sm">Belum ada riwayat pengajuan.</p>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
