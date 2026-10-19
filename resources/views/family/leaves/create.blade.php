<x-app-layout>
    <div class="max-w-4xl mx-auto space-y-6">

        <!-- HEADER HALAMAN -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <a href="{{ route('family.dashboard') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-400 hover:text-white transition mb-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Kembali ke Dashboard
                </a>
                <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">Formulir Pengajuan Izin Siswa</h1>
                <p class="text-xs font-semibold text-slate-400 mt-0.5">
                    Isi rincian permohonan izin dan sertakan berkas bukti untuk verifikasi sekolah
                </p>
            </div>
            <!-- IDENTITAS SISWA -->
            <div>
                <span class="inline-flex items-center gap-2.5 px-4 py-2 rounded-2xl bg-[#0f172a]/90 border border-indigo-500/40 text-xs font-bold text-indigo-300 shadow-md">
                    {{ auth()->user()->role?->role_name === 'Orang Tua' ? 'Wali Murid dari:' : 'Siswa:' }}
                    <strong class="text-white">{{ $student->name ?? auth()->user()->name }}</strong>
                    @if(isset($student->schoolClass))
                        • {{ $student->schoolClass->class_name }}
                    @endif
                </span>
            </div>
        </div>

        <!-- NOTIFIKASI ERROR VALIDASI -->
        @if ($errors->any())
            <div class="p-4 rounded-xl bg-red-950/80 border border-red-700 text-red-200 text-xs space-y-1">
                <p class="font-bold">Mohon periksa kembali formulir Anda:</p>
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- FORMULIR (8 Kolom) -->
            <div class="lg:col-span-8">
                <div class="bg-[#0f172a]/90 backdrop-blur-md p-6 border border-slate-800 shadow-xl" style="border-radius: 15px;">
                    <form method="POST" action="{{ route('family.leaves.store') }}" enctype="multipart/form-data" class="space-y-5">
                        @csrf

                        <!-- Jenis Izin (Interaktif & Kontras Tinggi di Light/Dark Mode) -->
                        <div x-data="{ leaveType: '{{ old('leave_type', 'Sakit') }}' }">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">
                                Jenis Izin <span class="text-red-400">*</span>
                            </label>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                
                                <!-- Opsi 1: Sakit -->
                                <label @click="leaveType = 'Sakit'"
                                       :class="leaveType === 'Sakit' 
                                            ? 'leave-active-sakit ring-2 ring-amber-500/50' 
                                            : 'leave-inactive hover:border-slate-400'"
                                       class="relative flex items-center justify-between p-4 rounded-xl border cursor-pointer transition-all duration-150">
                                    <input type="radio" name="leave_type" value="Sakit" x-model="leaveType" class="sr-only">
                                    
                                    <div class="flex items-center gap-3.5">
                                        <!-- Indikator Bulat Radio -->
                                        <div :class="leaveType === 'Sakit' ? 'border-amber-500 bg-amber-500' : 'border-slate-400 bg-transparent'"
                                             class="w-5 h-5 rounded-full border-2 flex items-center justify-center shrink-0 transition">
                                            <div x-show="leaveType === 'Sakit'" class="w-2 h-2 rounded-full bg-white"></div>
                                        </div>

                                        <div>
                                            <span :class="leaveType === 'Sakit' ? '!text-amber-600 dark:!text-amber-400 font-black' : 'font-bold'"
                                                  class="block text-xs">
                                                Sakit
                                            </span>
                                            <span class="block text-[11px] text-slate-500 dark:text-slate-400 font-medium">
                                                Surat dokter / keterangan sakit
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Ikon Centang Aktif -->
                                    <div x-show="leaveType === 'Sakit'" class="text-amber-600 dark:text-amber-400 shrink-0">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                </label>

                                <!-- Opsi 2: Izin Keperluan -->
                                <label @click="leaveType = 'Izin'"
                                       :class="leaveType === 'Izin' 
                                            ? 'leave-active-izin ring-2 ring-blue-500/50' 
                                            : 'leave-inactive hover:border-slate-400'"
                                       class="relative flex items-center justify-between p-4 rounded-xl border cursor-pointer transition-all duration-150">
                                    <input type="radio" name="leave_type" value="Izin" x-model="leaveType" class="sr-only">
                                    
                                    <div class="flex items-center gap-3.5">
                                        <!-- Indikator Bulat Radio -->
                                        <div :class="leaveType === 'Izin' ? 'border-blue-500 bg-blue-500' : 'border-slate-400 bg-transparent'"
                                             class="w-5 h-5 rounded-full border-2 flex items-center justify-center shrink-0 transition">
                                            <div x-show="leaveType === 'Izin'" class="w-2 h-2 rounded-full bg-white"></div>
                                        </div>

                                        <div>
                                            <span :class="leaveType === 'Izin' ? '!text-blue-600 dark:!text-blue-400 font-black' : 'font-bold'"
                                                  class="block text-xs">
                                                Izin Keperluan
                                            </span>
                                            <span class="block text-[11px] text-slate-500 dark:text-slate-400 font-medium">
                                                Kepentingan keluarga / dinas
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Ikon Centang Aktif -->
                                    <div x-show="leaveType === 'Izin'" class="text-blue-600 dark:text-blue-400 shrink-0">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                </label>

                            </div>
                        </div>


                        <!-- Rentang Tanggal -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">
                                    Tanggal Mulai <span class="text-red-400">*</span>
                                </label>
                                <input type="date" name="start_date" value="{{ old('start_date', date('Y-m-d')) }}" required 
                                       class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-indigo-500 transition">
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">
                                    Tanggal Selesai <span class="text-red-400">*</span>
                                </label>
                                <input type="date" name="end_date" value="{{ old('end_date', date('Y-m-d')) }}" required 
                                       class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-indigo-500 transition">
                            </div>
                        </div>

                        <!-- Alasan / Keterangan Izin -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">
                                Alasan / Keterangan Lengkap <span class="text-red-400">*</span>
                            </label>
                            <textarea name="reason" rows="4" required placeholder="Contoh: Mengalami demam dan disarankan istirahat oleh dokter / menghadiri acara keluarga penting..." 
                                      class="w-full bg-slate-900 border border-slate-700 rounded-xl p-3 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition">{{ old('reason') }}</textarea>
                        </div>

                        <!-- Upload Dokumen / Surat Bukti -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">
                                Dokumen Bukti (Surat Dokter / Orang Tua) <span class="text-red-400">*</span>
                            </label>
                            <input type="file" name="document" required accept=".pdf,.jpg,.jpeg,.png"
                                   class="w-full text-xs text-slate-400 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-600 file:text-white hover:file:bg-indigo-700 cursor-pointer bg-slate-900 border border-slate-700 rounded-xl p-1.5">
                            <p class="text-[11px] text-slate-500 mt-1">Format yang didukung: PDF, JPG, PNG (Maksimal 2MB).</p>
                        </div>

                        <!-- Tombol Submit -->
                        <div class="pt-3 border-t border-slate-800 flex items-center justify-end gap-3">
                            <a href="{{ route('family.dashboard') }}" class="px-4 py-2.5 rounded-xl border border-slate-700 text-xs font-bold text-slate-300 hover:bg-slate-800 transition">
                                Batal
                            </a>
                            <button type="submit" 
                                    style="border-radius: 12px; color: #ffffff !important;"
                                    class="py-2.5 px-6 font-black text-xs scp-btn-gradient cursor-pointer tracking-wider uppercase shadow-md hover:shadow-lg transition">
                                <span style="color: #ffffff !important;">Kirim Permohonan Izin</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- PANDUAN PENGAJUAN (4 Kolom) -->
            <div class="lg:col-span-4 space-y-4">
                <div class="bg-[#0f172a]/90 backdrop-blur-md p-5 border border-slate-800 shadow-xl space-y-3" style="border-radius: 15px;">
                    <h3 class="text-sm font-bold text-white border-b border-slate-800 pb-2">Ketentuan Pengajuan Izin</h3>
                    <ul class="text-xs text-slate-400 space-y-2.5">
                        <li class="flex items-start gap-2">
                            <span class="text-indigo-400 font-bold">•</span>
                            <span>Izin <strong>Sakit</strong> wajib menyertakan foto surat keterangan dari dokter atau klinik resmi.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-indigo-400 font-bold">•</span>
                            <span>Izin <strong>Keperluan</strong> wajib menyertakan surat pemberitahuan yang ditandatangani orang tua / wali.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-indigo-400 font-bold">•</span>
                            <span>Permohonan akan diverifikasi oleh <strong>Wali Kelas</strong> atau <strong>Guru BK</strong> sekolah.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-indigo-400 font-bold">•</span>
                            <span>Setelah disetujui, kehadiran otomatis terhitung sebagai izin sah pada rekap presensi guru.</span>
                        </li>
                    </ul>
                </div>

                <div class="p-4 rounded-xl bg-indigo-950/40 border border-indigo-800/40 text-xs text-indigo-300">
                    <p class="font-bold mb-1">Sudah pernah mengajukan izin?</p>
                    <p class="text-[11px] text-slate-400 mb-3">Lihat perkembangan status permohonan izin Anda pada halaman riwayat.</p>
                    <a href="{{ route('family.leaves.history') }}" class="font-bold text-white hover:underline">
                        Buka Riwayat Pengajuan →
                    </a>
                </div>
            </div>
        </div>
    </div>
        <style>
        /* Tampilan Mode Gelap (Default) */
        .leave-inactive {
            background-color: rgba(15, 23, 42, 0.6) !important;
            border-color: #334155 !important;
        }
        .leave-active-sakit {
            background-color: rgba(217, 119, 6, 0.15) !important;
            border-color: #d97706 !important;
        }
        .leave-active-izin {
            background-color: rgba(37, 99, 235, 0.15) !important;
            border-color: #2563eb !important;
        }

        /* Tampilan Mode Cerah (Light Mode) */
        html.theme-light .leave-inactive {
            background-color: #f8fafc !important;
            border-color: #cbd5e1 !important;
        }
        html.theme-light .leave-active-sakit {
            background-color: #fef3c7 !important;
            border-color: #d97706 !important;
            box-shadow: 0 2px 8px rgba(217, 119, 6, 0.25) !important;
        }
        html.theme-light .leave-active-izin {
            background-color: #dbeafe !important;
            border-color: #2563eb !important;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.25) !important;
        }
    </style>

</x-app-layout>
