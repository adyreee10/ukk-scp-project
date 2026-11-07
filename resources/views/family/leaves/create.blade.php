<x-app-layout>
    <div x-data="{
        docPreview: null,
        docBase64: '',
        cameraStream: null,
        cameraActive: false,
        cameraError: null,
        facingMode: 'environment',

        async startCamera() {
            this.cameraError = null;
            try {
                if (this.cameraStream) {
                    this.stopCamera();
                }
                const constraints = {
                    video: {
                        facingMode: this.facingMode,
                        width: { ideal: 1280 },
                        height: { ideal: 720 }
                    },
                    audio: false
                };
                this.cameraStream = await navigator.mediaDevices.getUserMedia(constraints);
                const video = this.$refs.docVideoPlayer;
                if (video) {
                    video.srcObject = this.cameraStream;
                    await video.play();
                    this.cameraActive = true;
                }
            } catch (err) {
                console.error('Kamera error:', err);
                this.cameraError = 'Gagal mengakses kamera langsung browser. Pastikan izin kamera aktif pada browser Anda, atau gunakan tombol Kamera HP di bawah.';
                this.cameraActive = false;
            }
        },

        stopCamera() {
            if (this.cameraStream) {
                this.cameraStream.getTracks().forEach(t => t.stop());
                this.cameraStream = null;
            }
            this.cameraActive = false;
        },

        switchCamera() {
            this.facingMode = this.facingMode === 'environment' ? 'user' : 'environment';
            this.startCamera();
        },

        snapPhoto() {
            const video = this.$refs.docVideoPlayer;
            if (!video || !this.cameraActive) return;

            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth || 1280;
            canvas.height = video.videoHeight || 720;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            // Watermark waktu real-time pada foto surat izin
            const now = new Date();
            const dateStr = now.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) + ' • ' +
                            now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) + ' WIB';
            
            ctx.fillStyle = 'rgba(15, 23, 42, 0.75)';
            ctx.fillRect(12, canvas.height - 42, 400, 30);
            ctx.fillStyle = '#38bdf8';
            ctx.font = 'bold 13px monospace';
            ctx.fillText('Surat Izin Real-Time • ' + dateStr, 22, canvas.height - 22);

            const dataUrl = canvas.toDataURL('image/jpeg', 0.88);
            this.docPreview = dataUrl;
            this.docBase64 = dataUrl;

            try {
                canvas.toBlob((blob) => {
                    if (blob && this.$refs.docRealFileInput) {
                        const file = new File([blob], 'Surat_Izin_Realtime_' + Date.now() + '.jpg', { type: 'image/jpeg' });
                        const dt = new DataTransfer();
                        dt.items.add(file);
                        this.$refs.docRealFileInput.files = dt.files;
                    }
                }, 'image/jpeg', 0.88);
            } catch(e) {}

            this.stopCamera();
        },

        retake() {
            this.docPreview = null;
            this.docBase64 = '';
            if (this.$refs.docRealFileInput) this.$refs.docRealFileInput.value = '';
            this.startCamera();
        }
    }" class="max-w-4xl mx-auto space-y-6">

        <!-- HEADER HALAMAN -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <a href="{{ route('family.dashboard') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-400 hover:text-white transition mb-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Kembali ke Dashboard
                </a>
                <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">Formulir Pengajuan Izin Siswa</h1>
                <p class="text-xs font-semibold text-slate-400 mt-0.5">
                    Isi rincian permohonan izin dan sertakan foto surat bukti langsung dari kamera
                </p>
            </div>
            <!-- IDENTITAS SISWA -->
            <div>
                <span class="inline-flex items-center gap-2.5 px-4 py-2 rounded-2xl bg-[#0f172a]/90 border border-indigo-500/40 text-xs font-bold text-indigo-300 shadow-md">
                    {{ auth()->user()->role?->role_name === 'Orang Tua' ? 'Orang tua dari:' : 'Siswa:' }}
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

                                                <!-- Jenis Izin (3 Opsi: Sakit, Izin, Potong Jam Pelajaran) -->
                        <div x-data="{ leaveType: '{{ old('leave_type', 'Sakit') }}' }" class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">
                                    Jenis Izin <span class="text-red-400">*</span>
                                </label>
                                
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                                    
                                    <!-- Opsi 1: Sakit (Seharian) -->
                                    <label @click="leaveType = 'Sakit'"
                                           :class="leaveType === 'Sakit' 
                                                ? 'leave-active-sakit ring-2 ring-amber-500/50' 
                                                : 'leave-inactive hover:border-slate-400'"
                                           class="relative flex items-center justify-between p-3.5 rounded-xl border cursor-pointer transition-all duration-150">
                                        <input type="radio" name="leave_type" value="Sakit" x-model="leaveType" class="sr-only">
                                        
                                        <div class="flex items-center gap-3">
                                            <div :class="leaveType === 'Sakit' ? 'border-amber-500 bg-amber-500' : 'border-slate-400 bg-transparent'"
                                                 class="w-4 h-4 rounded-full border-2 flex items-center justify-center shrink-0 transition">
                                                <div x-show="leaveType === 'Sakit'" class="w-1.5 h-1.5 rounded-full bg-white"></div>
                                            </div>

                                            <div>
                                                <span :class="leaveType === 'Sakit' ? '!text-amber-600 dark:!text-amber-400 font-black' : 'font-bold'"
                                                      class="block text-xs">
                                                    Sakit
                                                </span>
                                                <span class="block text-[10px] text-slate-500 dark:text-slate-400 font-medium">
                                                    Surat dokter / sakit seharian
                                                </span>
                                            </div>
                                        </div>
                                    </label>

                                    <!-- Opsi 2: Izin (Seharian) -->
                                    <label @click="leaveType = 'Izin'"
                                           :class="leaveType === 'Izin' 
                                                ? 'leave-active-izin ring-2 ring-blue-500/50' 
                                                : 'leave-inactive hover:border-slate-400'"
                                           class="relative flex items-center justify-between p-3.5 rounded-xl border cursor-pointer transition-all duration-150">
                                        <input type="radio" name="leave_type" value="Izin" x-model="leaveType" class="sr-only">
                                        
                                        <div class="flex items-center gap-3">
                                            <div :class="leaveType === 'Izin' ? 'border-blue-500 bg-blue-500' : 'border-slate-400 bg-transparent'"
                                                 class="w-4 h-4 rounded-full border-2 flex items-center justify-center shrink-0 transition">
                                                <div x-show="leaveType === 'Izin'" class="w-1.5 h-1.5 rounded-full bg-white"></div>
                                            </div>

                                            <div>
                                                <span :class="leaveType === 'Izin' ? '!text-blue-600 dark:!text-blue-400 font-black' : 'font-bold'"
                                                      class="block text-xs">
                                                    Izin
                                                </span>
                                                <span class="block text-[10px] text-slate-500 dark:text-slate-400 font-medium">
                                                    Acara keluarga, lomba / urusan penting
                                                </span>
                                            </div>
                                        </div>
                                    </label>

                                    <!-- Opsi 3: Potong Jam Pelajaran -->
                                    <label @click="leaveType = 'Potong Jam Pelajaran'"
                                           :class="leaveType === 'Potong Jam Pelajaran' 
                                                ? 'border-purple-500 bg-purple-950/30 ring-2 ring-purple-500/50' 
                                                : 'leave-inactive hover:border-slate-400'"
                                           class="relative flex items-center justify-between p-3.5 rounded-xl border cursor-pointer transition-all duration-150">
                                        <input type="radio" name="leave_type" value="Potong Jam Pelajaran" x-model="leaveType" class="sr-only">
                                        
                                        <div class="flex items-center gap-3">
                                            <div :class="leaveType === 'Potong Jam Pelajaran' ? 'border-purple-500 bg-purple-500' : 'border-slate-400 bg-transparent'"
                                                 class="w-4 h-4 rounded-full border-2 flex items-center justify-center shrink-0 transition">
                                                <div x-show="leaveType === 'Potong Jam Pelajaran'" class="w-1.5 h-1.5 rounded-full bg-white"></div>
                                            </div>

                                            <div>
                                                <span :class="leaveType === 'Potong Jam Pelajaran' ? '!text-purple-400 font-black' : 'font-bold'"
                                                      class="block text-xs">
                                                    Potong Jam Pelajaran
                                                </span>
                                                <span class="block text-[10px] text-slate-500 dark:text-slate-400 font-medium">
                                                    Izin pada jam KBM tertentu
                                                </span>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- FORM RENTANG TANGGAL (JIKA SAKIT ATAU IZIN SEHARIAN) -->
                            <div x-show="leaveType === 'Sakit' || leaveType === 'Izin'" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">
                                        Tanggal Mulai <span class="text-red-400">*</span>
                                    </label>
                                    <input type="date" name="start_date" value="{{ old('start_date', date('Y-m-d')) }}"
                                           :required="leaveType === 'Sakit' || leaveType === 'Izin'"
                                           class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-indigo-500 transition">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">
                                        Tanggal Selesai <span class="text-red-400">*</span>
                                    </label>
                                    <input type="date" name="end_date" value="{{ old('end_date', date('Y-m-d')) }}"
                                           :required="leaveType === 'Sakit' || leaveType === 'Izin'"
                                           class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-indigo-500 transition">
                                </div>
                            </div>

                                                        <!-- FORM JAM KHUSUS (JIKA POTONG JAM PELAJARAN) -->
                            <div x-show="leaveType === 'Potong Jam Pelajaran'" style="display: none;" class="p-4 rounded-xl bg-purple-950/20 border border-purple-500/30 space-y-3">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-purple-300 mb-1.5">
                                        Tanggal Izin <span class="text-red-400">*</span>
                                    </label>
                                    <input type="date" name="single_date" value="{{ old('single_date', date('Y-m-d')) }}"
                                           :required="leaveType === 'Potong Jam Pelajaran'"
                                           class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-purple-500 transition">
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold uppercase tracking-wider text-purple-300 mb-1.5">
                                            Dari Jam Pelajaran <span class="text-red-400">*</span>
                                        </label>
                                        <select name="start_time" 
                                                :required="leaveType === 'Potong Jam Pelajaran'"
                                                class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-purple-500 transition cursor-pointer">
                                            @for($i = 1; $i <= 10; $i++)
                                                @php $val = sprintf('%02d:00', $i); @endphp
                                                <option value="{{ $val }}" {{ old('start_time', '01:00') === $val ? 'selected' : '' }}>
                                                    Jam ke - {{ $i }}
                                                </option>
                                            @endfor
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold uppercase tracking-wider text-purple-300 mb-1.5">
                                            Sampai Jam Pelajaran <span class="text-red-400">*</span>
                                        </label>
                                        <select name="end_time" 
                                                :required="leaveType === 'Potong Jam Pelajaran'"
                                                class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-purple-500 transition cursor-pointer">
                                            @for($i = 1; $i <= 10; $i++)
                                                @php $val = sprintf('%02d:00', $i); @endphp
                                                <option value="{{ $val }}" {{ old('end_time', '02:00') === $val ? 'selected' : '' }}>
                                                    Jam ke - {{ $i }}
                                                </option>
                                            @endfor
                                        </select>
                                    </div>
                                </div>

                                <p class="text-[11px] text-purple-300/80">
                                    Siswa hanya izin pada rentang jam tersebut dan tetap mengikuti pembelajaran sebelum atau sesudahnya.
                                </p>
                            </div>
                        </div>

                        <!-- Alasan / Keterangan Izin -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">
                                Alasan / Keterangan Lengkap <span class="text-red-400">*</span>
                            </label>
                            <textarea name="reason" rows="4" required placeholder="Contoh: Mengikuti kejuaraan lomba / menghadiri acara keluarga / keperluan penting lainnya..."
                                      class="w-full bg-slate-900 border border-slate-700 rounded-xl p-3 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition">{{ old('reason') }}</textarea>
                        </div>

                        <!-- Foto Surat Bukti Real-Time Kamera -->
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400">
                                    Foto Fisik Surat Bukti (Kamera Real-Time) <span class="text-red-400">*</span>
                                </label>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-950/70 border border-rose-700/60 text-rose-300 flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-400 animate-pulse"></span>
                                    Kamera Langsung
                                </span>
                            </div>

                            <!-- Alert Edukasi Anti-Kecurangan -->
                            <div class="p-2.5 rounded-xl bg-slate-900/80 border border-slate-800 text-[11px] text-slate-300 flex items-center gap-2">
                                <svg class="w-4 h-4 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <span>Pilihan galeri dinonaktifkan. Foto fisik surat dokter / surat izin wajib difoto secara langsung dari kamera.</span>
                            </div>

                            <!-- Box Kamera & Foto Real-Time -->
                            <div class="overflow-hidden rounded-2xl border-2 border-slate-700 bg-slate-950 relative">
                                <!-- 1. Kondisi Belum Ambil Foto & Kamera Belum Aktif -->
                                <div x-show="!docPreview && !cameraActive" class="p-6 text-center space-y-3">
                                    <div class="w-14 h-14 rounded-2xl bg-indigo-950/60 border border-indigo-500/40 text-indigo-400 mx-auto flex items-center justify-center">
                                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-white">Kamera Belum Aktif</p>
                                        <p class="text-[11px] text-slate-400 mt-0.5">Arahkan kamera langsung ke fisik surat izin / surat dokter</p>
                                    </div>
                                    
                                    <div class="flex items-center justify-center pt-1">
                                        <button type="button" @click="startCamera()" class="px-6 py-2.5 rounded-xl scp-btn-gradient text-xs font-bold text-white shadow-lg flex items-center justify-center gap-2 cursor-pointer transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                            <span>Buka & Nyalakan Kamera Real-Time</span>
                                        </button>
                                    </div>

                                    <template x-if="cameraError">
                                        <p class="text-xs text-rose-400 font-semibold mt-2" x-text="cameraError"></p>
                                    </template>
                                </div>

                                <!-- 2. Kondisi Kamera Aktif (Live Streaming Viewfinder) -->
                                <div x-show="cameraActive && !docPreview" style="display: none;" class="relative">
                                    <div class="relative bg-black">
                                        <video x-ref="docVideoPlayer" autoplay playsinline muted class="w-full h-64 sm:h-72 object-cover bg-black block"></video>
                                        
                                        <!-- Overlay Status Live -->
                                        <div class="absolute top-3 left-3 bg-slate-950/85 backdrop-blur-sm border border-slate-700 text-rose-400 px-3 py-1 rounded-full text-[10px] font-black tracking-wider uppercase flex items-center gap-1.5 shadow-md" style="z-index: 20;">
                                            <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                                            LIVE KAMERA SURAT IZIN
                                        </div>

                                        <!-- Tombol Putar Kamera Depan/Belakang -->
                                        <button type="button" @click="switchCamera()" class="absolute top-3 right-3 bg-slate-900/85 hover:bg-slate-800 text-white p-2 rounded-full text-xs font-bold border border-slate-700 shadow-md transition" style="z-index: 20;" title="Putar Kamera Depan/Belakang">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                        </button>
                                    </div>

                                    <!-- Panel Tombol Kontrol Kamera (Tepat di Bawah Video, Selalu Terlihat & Mudah Ditekan) -->
                                    <div class="p-3 bg-slate-900 border-t border-slate-800 flex flex-wrap items-center justify-center gap-2.5">
                                        <button type="button" @click="snapPhoto()" class="px-6 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-extrabold text-xs shadow-lg shadow-rose-950/60 flex items-center gap-2 transition transform active:scale-95 cursor-pointer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                            <span>Ambil Foto Surat Sekarang</span>
                                        </button>
                                        <button type="button" @click="switchCamera()" class="px-3.5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold border border-slate-700 flex items-center gap-1.5 transition cursor-pointer" title="Putar Kamera Depan/Belakang">
                                            <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                            <span>Putar Kamera</span>
                                        </button>
                                        <button type="button" @click="stopCamera()" class="px-3.5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white text-xs font-semibold border border-slate-700 flex items-center gap-1.5 transition cursor-pointer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            <span>Tutup</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- 3. Kondisi Foto Berhasil Diambil -->
                                <div x-show="docPreview" style="display: none;" class="relative">
                                    <img :src="docPreview" class="w-full h-64 sm:h-72 object-cover bg-black" alt="Hasil Foto Fisik Surat Izin">
                                    
                                    <div class="absolute top-3 left-3 bg-emerald-950/90 border border-emerald-600 text-emerald-300 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider flex items-center gap-1.5 shadow-md">
                                        <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        FOTO SURAT REAL-TIME TERCATAT
                                    </div>

                                    <div class="p-3 bg-slate-900 border-t border-slate-800 flex items-center justify-between">
                                        <span class="text-[11px] text-emerald-400 font-semibold flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            Foto surat izin berhasil diambil & ter-watermark
                                        </span>
                                        <button type="button" @click="retake()" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-white text-xs font-bold shadow-md transition flex items-center gap-1.5 cursor-pointer">
                                            <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                            <span>Foto Ulang Surat</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Input Tersembunyi (Menyimpan Hasil Jepretan Kamera) -->
                            <input type="file" name="document" x-ref="docRealFileInput" class="hidden" accept="image/*">
                            <input type="hidden" name="document_base64" x-model="docBase64">
                            <x-input-error :messages="$errors->get('document')" class="mt-1 text-xs" />
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
                            <span>Izin <strong>Keperluan</strong> menyertakan surat orang tua/wali atau surat tugas dispensasi (misal: lomba/event resmi).</span>
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
