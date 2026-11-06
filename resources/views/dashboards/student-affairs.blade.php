<x-app-layout>
    <div x-data="{ 
        searchStudent: '',
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
        },
        rejectModalOpen: false,
        approveModalOpen: false,
        selectedLeave: null,
        rejectionNote: '',
        openRejectModal(leave) {
            this.selectedLeave = leave;
            this.rejectionNote = '';
            this.rejectModalOpen = true;
        },
        closeRejectModal() {
            this.rejectModalOpen = false;
            this.selectedLeave = null;
            this.rejectionNote = '';
        },
        openApproveModal(leave) {
            this.selectedLeave = leave;
            this.approveModalOpen = true;
        },
        closeApproveModal() {
            this.approveModalOpen = false;
            this.selectedLeave = null;
        }
    }" class="space-y-6">

        <!-- HEADER LAMAN VERIFIKASI IZIN -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">Verifikasi Izin Siswa</h1>
                <p class="text-xs font-semibold text-slate-400 mt-0.5">
                    Permohonan izin KBM siswa yang menunggu persetujuan • {{ now()->translatedFormat('l, d F Y') }}
                </p>
            </div>
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
            <div class="bg-[#0f172a]/90 backdrop-blur-md p-5 border border-slate-800 shadow-xl" style="border-radius: 15px;">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Menunggu Verifikasi</span>
                <div class="text-3xl font-black text-white mt-1 mb-0.5">
                    {{ $pendingLeaves->count() }} <span class="text-xs font-semibold text-slate-400">Permohonan</span>
                </div>
                <p class="text-[11px] font-medium text-slate-400">Perlu tindakan persetujuan</p>
            </div>

            <div class="bg-[#0f172a]/90 backdrop-blur-md p-5 border border-slate-800 shadow-xl" style="border-radius: 15px;">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Permohonan Sakit</span>
                <div class="text-3xl font-black text-amber-400 mt-1 mb-0.5">
                    {{ $pendingLeaves->where('leave_type', 'Sakit')->count() }} <span class="text-xs font-semibold text-slate-400">Siswa</span>
                </div>
                <p class="text-[11px] font-medium text-slate-400">Disertai surat dokter / keterangan</p>
            </div>

            <div class="bg-[#0f172a]/90 backdrop-blur-md p-5 border border-slate-800 shadow-xl" style="border-radius: 15px;">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Izin Keperluan</span>
                <div class="text-3xl font-black text-blue-400 mt-1 mb-0.5">
                    {{ $pendingLeaves->where('leave_type', 'Izin')->count() }} <span class="text-xs font-semibold text-slate-400">Siswa</span>
                </div>
                <p class="text-[11px] font-medium text-slate-400">Keperluan keluarga / dinas</p>
            </div>
        </div>

        <!-- KONTEN UTAMA: PENCARIAN & DAFTAR PERMOHONAN PENDING -->
        <div class="bg-[#0f172a]/90 backdrop-blur-md border border-slate-800 shadow-xl overflow-hidden" style="border-radius: 15px;">
            <div class="p-4 sm:p-5 border-b border-slate-800 flex items-center justify-between gap-3">
                <div class="text-xs font-bold text-slate-300">
                    Daftar Antrean Permohonan ({{ $pendingLeaves->count() }})
                </div>

                <!-- Input Pencarian -->
                <div class="relative w-full sm:w-72">
                    <input type="text" x-model="searchStudent" 
                           placeholder="Cari siswa atau kelas..." 
                           class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2 pl-9 text-xs text-white placeholder-slate-500 focus:border-indigo-500 focus:bg-slate-900 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
            </div>

            @if($pendingLeaves->isEmpty())
                <div class="text-center py-16 bg-slate-900/40">
                    <div class="w-12 h-12 rounded-2xl bg-slate-800/80 border border-slate-700 text-slate-400 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <p class="text-sm font-bold text-white">Tidak Ada Permohonan Izin Pending</p>
                    <p class="text-xs text-slate-400 mt-1">Semua permohonan izin siswa telah selesai diproses.</p>
                </div>
            @else
                <!-- Mobile Card View -->
                <div class="p-4 space-y-3.5 md:hidden">
                    @foreach ($pendingLeaves as $leave)
                        <div x-show="!searchStudent || '{{ strtolower(addslashes($leave->student_name)) }} {{ strtolower(addslashes($leave->class_name)) }}'.includes(searchStudent.toLowerCase())"
                             class="p-4 rounded-xl border border-slate-800 bg-slate-900/60 hover:bg-slate-800/40 transition">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <span class="inline-block px-2.5 py-0.5 rounded-md text-[10px] font-bold uppercase border {{ $leave->leave_type === 'Sakit' ? 'bg-amber-950/60 text-amber-300 border-amber-800/60' : (in_array($leave->leave_type, ['Potong Jam Pelajaran', 'Dispensasi']) ? 'bg-purple-950/60 text-purple-300 border-purple-800/60' : 'bg-blue-950/60 text-blue-300 border-blue-800/60') }}">
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
                                    <button type="button" 
                                            @click="openPreviewDoc('{{ url('media/' . $leave->document_path) }}', 'Surat Izin - {{ addslashes($leave->student_name) }}')"
                                            class="inline-flex items-center gap-1.5 text-xs font-bold text-indigo-400 hover:text-indigo-300 transition hover:underline cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                        Lihat Surat Bukti Izin →
                                    </button>
                                </div>
                            @endif

                            <div class="mt-4 pt-3 border-t border-slate-800 flex gap-2">
                                <div class="flex-1">
                                    <button type="button" 
                                            @click="openRejectModal({
                                                id: {{ $leave->id }},
                                                student_name: '{{ addslashes($leave->student_name) }}',
                                                class_name: '{{ addslashes($leave->class_name) }}',
                                                leave_type: '{{ addslashes($leave->leave_type) }}',
                                                reason: '{{ addslashes($leave->reason) }}',
                                                dates: '{{ \Carbon\Carbon::parse($leave->start_date)->translatedFormat('d M Y') }}@if($leave->start_date !== $leave->end_date) s/d {{ \Carbon\Carbon::parse($leave->end_date)->translatedFormat('d M Y') }}@endif'
                                            })"
                                            class="w-full py-2 rounded-xl bg-red-950/60 border border-red-800/60 text-red-300 hover:bg-red-900 text-xs font-bold transition cursor-pointer">
                                        Tolak
                                    </button>
                                </div>
                                <div class="flex-1">
                                    <button type="button" 
                                            @click="openApproveModal({
                                                id: {{ $leave->id }},
                                                student_name: '{{ addslashes($leave->student_name) }}',
                                                class_name: '{{ addslashes($leave->class_name) }}',
                                                leave_type: '{{ addslashes($leave->leave_type) }}',
                                                reason: '{{ addslashes($leave->reason) }}',
                                                dates: '{{ \Carbon\Carbon::parse($leave->start_date)->translatedFormat('d M Y') }}@if($leave->start_date !== $leave->end_date) s/d {{ \Carbon\Carbon::parse($leave->end_date)->translatedFormat('d M Y') }}@endif'
                                            })"
                                            class="w-full py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-md transition cursor-pointer"
                                            style="color: #ffffff !important;">
                                        Setujui
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Desktop Table View -->
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
                                    <td class="p-4">
                                        <div class="font-bold text-white">{{ $leave->student_name }}</div>
                                        <span class="inline-block mt-1 rounded-md bg-indigo-950/60 px-2 py-0.5 text-[11px] font-bold text-indigo-300 border border-indigo-800/60">
                                            {{ $leave->class_name }}
                                        </span>
                                    </td>

                                    <td class="p-4 text-xs font-mono text-slate-300 whitespace-nowrap">
                                        {{ \Carbon\Carbon::parse($leave->start_date)->translatedFormat('d M Y') }}
                                        @if($leave->start_date !== $leave->end_date)
                                            <div class="text-[11px] text-slate-400">s/d {{ \Carbon\Carbon::parse($leave->end_date)->translatedFormat('d M Y') }}</div>
                                        @elseif(!empty($leave->period_label))
                                            <div class="text-[11px] text-purple-400 font-bold mt-0.5">{{ $leave->period_label }}</div>
                                        @endif
                                    </td>


                                    <td class="p-4 max-w-xs">
                                        <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase border mb-1 {{ $leave->leave_type === 'Sakit' ? 'bg-amber-950/60 text-amber-300 border-amber-800/60' : (in_array($leave->leave_type, ['Potong Jam Pelajaran', 'Dispensasi']) ? 'bg-purple-950/60 text-purple-300 border-purple-800/60' : 'bg-blue-950/60 text-blue-300 border-blue-800/60') }}">
                                            {{ $leave->leave_type }}
                                        </span>
                                        <p class="text-xs text-slate-300 line-clamp-2">{{ $leave->reason }}</p>
                                    </td>

                                    <td class="p-4 text-center whitespace-nowrap">
                                        @if($leave->document_path)
                                            <button type="button" 
                                                    @click="openPreviewDoc('{{ url('media/' . $leave->document_path) }}', 'Surat Izin - {{ addslashes($leave->student_name) }}')"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-indigo-950/60 border border-indigo-700/60 text-indigo-300 hover:bg-indigo-900/60 text-xs font-bold transition cursor-pointer">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                Lihat Berkas
                                            </button>
                                        @else
                                            <span class="text-slate-500 text-xs italic">Tanpa Berkas</span>
                                        @endif
                                    </td>

                                    <td class="p-4 text-center whitespace-nowrap">
                                        <div class="flex items-center justify-center gap-2">
                                            <button type="button" 
                                                    @click="openRejectModal({
                                                        id: {{ $leave->id }},
                                                        student_name: '{{ addslashes($leave->student_name) }}',
                                                        class_name: '{{ addslashes($leave->class_name) }}',
                                                        leave_type: '{{ addslashes($leave->leave_type) }}',
                                                        reason: '{{ addslashes($leave->reason) }}',
                                                        dates: '{{ \Carbon\Carbon::parse($leave->start_date)->translatedFormat('d M Y') }}@if($leave->start_date !== $leave->end_date) s/d {{ \Carbon\Carbon::parse($leave->end_date)->translatedFormat('d M Y') }}@endif'
                                                    })"
                                                    class="px-3.5 py-1.5 rounded-xl bg-red-950/60 border border-red-800/60 text-red-300 hover:bg-red-900 text-xs font-bold transition cursor-pointer">
                                                Tolak
                                            </button>
                                            <button type="button" 
                                                    @click="openApproveModal({
                                                        id: {{ $leave->id }},
                                                        student_name: '{{ addslashes($leave->student_name) }}',
                                                        class_name: '{{ addslashes($leave->class_name) }}',
                                                        leave_type: '{{ addslashes($leave->leave_type) }}',
                                                        reason: '{{ addslashes($leave->reason) }}',
                                                        dates: '{{ \Carbon\Carbon::parse($leave->start_date)->translatedFormat('d M Y') }}@if($leave->start_date !== $leave->end_date) s/d {{ \Carbon\Carbon::parse($leave->end_date)->translatedFormat('d M Y') }}@endif'
                                                    })"
                                                    class="px-4 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-md transition cursor-pointer"
                                                    style="color: #ffffff !important;">
                                                Setujui
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- MODAL TOLAK IZIN -->
        <div x-show="rejectModalOpen" 
             x-cloak 
             class="fixed inset-0 z-50 overflow-y-auto" 
             style="display: none;"
             @keydown.escape.window="closeRejectModal()">
            <div class="fixed inset-0 bg-black/75 backdrop-blur-sm transition-opacity" 
                 @click="closeRejectModal()"></div>

            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div class="reject-modal-box relative transform overflow-hidden rounded-2xl bg-[#0f172a] border border-slate-700/80 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg p-6"
                     @click.stop>
                    <div class="flex items-start justify-between pb-4 border-b border-slate-800">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-red-500/10 border border-red-500/30 flex items-center justify-center text-red-400 shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-white">Tolak Permohonan Izin</h3>
                                <p class="text-xs text-slate-400 mt-0.5">Wajib berikan alasan penolakan untuk siswa & orang tua</p>
                            </div>
                        </div>
                        <button type="button" @click="closeRejectModal()" class="text-slate-400 hover:text-white rounded-lg p-1.5 hover:bg-slate-800/60 transition cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <div class="mt-4 p-3.5 rounded-xl bg-slate-900/80 border border-slate-800 text-xs space-y-1.5">
                        <div class="flex justify-between items-center">
                            <span class="text-slate-400 font-semibold">Siswa & Kelas:</span>
                            <span class="text-white font-bold" x-text="(selectedLeave ? selectedLeave.student_name : '') + ' (' + (selectedLeave ? selectedLeave.class_name : '') + ')'"></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-400 font-semibold">Jenis & Tanggal:</span>
                            <span class="font-mono text-slate-300" x-text="(selectedLeave ? selectedLeave.leave_type : '') + ' • ' + (selectedLeave ? selectedLeave.dates : '')"></span>
                        </div>
                        <div class="flex justify-between items-start gap-2 pt-1 border-t border-slate-800/60">
                            <span class="text-slate-400 font-semibold shrink-0">Alasan Pemohon:</span>
                            <span class="text-slate-300 text-right italic" x-text="'&ldquo;' + (selectedLeave ? selectedLeave.reason : '') + '&rdquo;'"></span>
                        </div>
                    </div>

                    <form :action="'{{ url('/student-affairs/leaves') }}/' + (selectedLeave ? selectedLeave.id : '') + '/reject'" 
                          method="POST" 
                          class="mt-4 space-y-4">
                        @csrf
                        <div>
                            <label for="rejection_note" class="block text-xs font-bold text-slate-200 mb-1.5">
                                Alasan Penolakan <span class="text-red-400">*</span>
                            </label>
                            <textarea id="rejection_note" 
                                      name="rejection_note" 
                                      x-model="rejectionNote"
                                      rows="3" 
                                      required
                                      placeholder="Contoh: Berkas surat keterangan tidak jelas/buram, atau tanggal izin bertepatan dengan jadwal asesmen..."
                                      class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:border-red-500 focus:bg-slate-900 focus:outline-none focus:ring-1 focus:ring-red-500"></textarea>
                            <div class="flex items-center justify-between mt-1 text-[11px] text-slate-400">
                                <span>Alasan ini akan tampil di dashboard siswa/orang tua.</span>
                                <span class="font-mono" :class="rejectionNote.length > 500 ? 'text-red-400 font-bold' : ''" x-text="rejectionNote.length + '/500'"></span>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-800">
                            <button type="button" @click="closeRejectModal()" class="px-4 py-2 rounded-xl border border-slate-700 text-slate-300 hover:text-white hover:bg-slate-800 text-xs font-bold transition cursor-pointer">
                                Batal
                            </button>
                            <button type="submit" 
                                    :disabled="!rejectionNote.trim() || rejectionNote.trim().length < 3 || rejectionNote.length > 500"
                                    :class="(!rejectionNote.trim() || rejectionNote.trim().length < 3 || rejectionNote.length > 500) ? 'opacity-50 cursor-not-allowed bg-red-900/60 text-red-300 border border-red-800/40' : 'bg-red-600 hover:bg-red-500 text-white shadow-lg cursor-pointer'"
                                    class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                <span>Konfirmasi Tolak Izin</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL APPROVE IZIN -->
        <div x-show="approveModalOpen" 
             x-cloak 
             class="fixed inset-0 z-50 overflow-y-auto" 
             style="display: none;"
             @keydown.escape.window="closeApproveModal()">
            <div class="fixed inset-0 bg-black/75 backdrop-blur-sm transition-opacity" 
                 @click="closeApproveModal()"></div>

            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div class="approve-modal-box relative transform overflow-hidden rounded-2xl bg-[#0f172a] border border-slate-700/80 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg p-6"
                     @click.stop>
                    <div class="flex items-start justify-between pb-4 border-b border-slate-800">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400 shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-white">Setujui Permohonan Izin</h3>
                                <p class="text-xs text-slate-400 mt-0.5">Konfirmasi persetujuan surat izin siswa</p>
                            </div>
                        </div>
                        <button type="button" @click="closeApproveModal()" class="text-slate-400 hover:text-white rounded-lg p-1.5 hover:bg-slate-800/60 transition cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <div class="mt-4 p-3.5 rounded-xl bg-slate-900/80 border border-slate-800 text-xs space-y-1.5">
                        <div class="flex justify-between items-center">
                            <span class="text-slate-400 font-semibold">Siswa & Kelas:</span>
                            <span class="text-white font-bold" x-text="(selectedLeave ? selectedLeave.student_name : '') + ' (' + (selectedLeave ? selectedLeave.class_name : '') + ')'"></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-400 font-semibold">Jenis & Tanggal:</span>
                            <span class="font-mono text-emerald-300 font-bold" x-text="(selectedLeave ? selectedLeave.leave_type : '') + ' • ' + (selectedLeave ? selectedLeave.dates : '')"></span>
                        </div>
                        <div class="flex justify-between items-start gap-2 pt-1 border-t border-slate-800/60">
                            <span class="text-slate-400 font-semibold shrink-0">Alasan Pemohon:</span>
                            <span class="text-slate-300 text-right italic" x-text="'&ldquo;' + (selectedLeave ? selectedLeave.reason : '') + '&rdquo;'"></span>
                        </div>
                    </div>

                    <div class="mt-4 p-3 rounded-xl bg-emerald-950/30 border border-emerald-800/40 text-emerald-300 text-xs flex items-start gap-2.5">
                        <svg class="w-4 h-4 shrink-0 mt-0.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>
                            Izin yang disetujui akan otomatis mengunci status presensi siswa menjadi <strong>Sakit / Izin</strong> saat guru membuka jurnal KBM pada tanggal terkait.
                        </span>
                    </div>

                    <form :action="'{{ url('/student-affairs/leaves') }}/' + (selectedLeave ? selectedLeave.id : '') + '/approve'" 
                          method="POST" 
                          class="mt-5">
                        @csrf
                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-800">
                            <button type="button" @click="closeApproveModal()" class="px-4 py-2 rounded-xl border border-slate-700 text-slate-300 hover:text-white hover:bg-slate-800 text-xs font-bold transition cursor-pointer">
                                Batal
                            </button>
                            <button type="submit" 
                                    class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-lg transition cursor-pointer flex items-center gap-1.5"
                                    style="color: #ffffff !important;">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                <span>Konfirmasi Setujui Izin</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL PRATINJAU DOKUMEN -->
        <div x-show="previewDocOpen" 
             x-cloak 
             class="fixed inset-0 z-50 overflow-y-auto" 
             style="display: none;"
             @keydown.escape.window="closePreviewDoc()">
            <div class="fixed inset-0 bg-black/80 backdrop-blur-sm transition-opacity" 
                 @click="closePreviewDoc()"></div>

            <div class="flex min-h-full items-center justify-center p-4 text-center">
                <div class="preview-modal-box relative transform overflow-hidden rounded-2xl bg-[#0f172a] border border-slate-700 text-left shadow-2xl transition-all w-full max-w-2xl p-5"
                     @click.stop>
                    <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                        <h3 class="text-sm font-bold text-white" x-text="previewDocTitle"></h3>
                        <div class="flex items-center gap-2">
                            <a :href="previewDocUrl" target="_blank" 
                               class="text-xs text-indigo-400 hover:text-indigo-300 font-bold hover:underline flex items-center gap-1">
                                <span>Buka Tab Baru</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                            </a>
                            <button type="button" @click="closePreviewDoc()" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                    </div>

                    <div class="mt-4 flex items-center justify-center bg-slate-950/70 rounded-xl p-2 min-h-[300px] border border-slate-800/80 overflow-hidden">
                        <template x-if="previewDocUrl && (previewDocUrl.endsWith('.pdf') || previewDocUrl.includes('.pdf'))">
                            <iframe :src="previewDocUrl" class="w-full h-[500px] rounded-lg border-0"></iframe>
                        </template>
                        <template x-if="previewDocUrl && !(previewDocUrl.endsWith('.pdf') || previewDocUrl.includes('.pdf'))">
                            <img :src="previewDocUrl" alt="Surat Keterangan" class="max-h-[500px] w-auto max-w-full rounded-lg object-contain shadow-md" />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        html.theme-light .reject-modal-box,
        html.theme-light .approve-modal-box {
            background-color: #ffffff !important;
            border-color: #e2e8f0 !important;
        }
        html.theme-light .reject-modal-box textarea {
            background-color: #f8fafc !important;
            border-color: #cbd5e1 !important;
            color: #0f172a !important;
        }
        html.theme-light .reject-modal-box textarea::placeholder {
            color: #94a3b8 !important;
        }
        html.theme-light .reject-modal-box .bg-slate-900\/80,
        html.theme-light .approve-modal-box .bg-slate-900\/80 {
            background-color: #f1f5f9 !important;
            border-color: #e2e8f0 !important;
        }
        html.theme-light .reject-modal-box span.text-white,
        html.theme-light .approve-modal-box span.text-white {
            color: #0f172a !important;
        }
        html.theme-light .reject-modal-box .border-slate-800,
        html.theme-light .approve-modal-box .border-slate-800 {
            border-color: #e2e8f0 !important;
        }
    </style>
</x-app-layout>
