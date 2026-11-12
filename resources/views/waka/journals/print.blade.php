<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        {{ $isMulti ? 'Cetak Semua Jurnal KBM - ' . \Carbon\Carbon::parse($date)->translatedFormat('d M Y') : 'Jurnal KBM - ' . ($processedJournals->first()?->schedule?->subject ?? 'Jurnal') . ' (' . ($processedJournals->first()?->schedule?->schoolClass?->class_name ?? '') . ') - ' . \Carbon\Carbon::parse($date)->translatedFormat('d M Y') }}
    </title>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Pengaturan Khusus Halaman Cetak A4 */
        @media print {
            body {
                background: #ffffff !important;
                color: #000000 !important;
                font-size: 10.5px !important;
                line-height: 1.35 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .print\:hidden {
                display: none !important;
            }
            .print-page {
                box-shadow: none !important;
                margin: 0 !important;
                padding: 0 !important;
                max-width: 100% !important;
                width: 100% !important;
            }
            .page-break-after-always {
                page-break-after: always !important;
                break-after: page !important;
            }
            @page {
                size: A4 portrait;
                margin: 1cm 0.8cm;
            }
            .avoid-break {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
        }

        /* Styling dasar dokumen */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #0f172a;
            color: #1e293b;
            margin: 0;
            padding: 0;
        }

        .print-page {
            max-width: 210mm;
            min-height: 297mm;
            margin: 20px auto;
            background: #ffffff;
            padding: 22px 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
            box-sizing: border-box;
            border-radius: 4px;
        }

        table.tbl-presensi {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }
        table.tbl-presensi th, table.tbl-presensi td {
            border: 1px solid #94a3b8;
            padding: 2.5px 5px;
            vertical-align: middle;
        }
        table.tbl-presensi th {
            background-color: #f1f5f9;
            font-weight: 700;
            text-transform: uppercase;
        }
    </style>
</head>
<body>

    {{-- =========================================================================
         BAR TINDAKAN ATAS (HANYA MUNCUL DI LAYAR MONITOR, OTOMATIS HILANG SAAT PRINT)
         ========================================================================= --}}
    <div class="print:hidden bg-slate-900 border-b border-slate-800 px-4 py-3 sticky top-0 z-50 shadow-md">
        <div class="max-w-[210mm] mx-auto flex items-center justify-between gap-4">
            <a href="{{ route('waka.journals.index', request()->query()) }}" 
               class="inline-flex items-center gap-2 text-xs font-bold text-slate-300 hover:text-white transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                <span>Kembali ke Monitoring Jurnal</span>
            </a>

            <div class="flex items-center gap-3">
                <span class="text-xs text-slate-400 font-semibold hidden sm:inline">
                    @if($isMulti)
                        Total: <strong class="text-white">{{ $processedJournals->count() }} Jurnal Terisi</strong> (Siap Cetak)
                    @else
                        Format: <strong class="text-white">Kertas A4 Portrait</strong>
                    @endif
                </span>
                <button type="button" 
                        onclick="window.print()" 
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-extrabold text-xs shadow-lg shadow-indigo-600/30 transition cursor-pointer"
                        style="background-color: #2563eb !important; color: #ffffff !important;">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    <span style="color: #ffffff !important;">
                        {{ $isMulti ? 'Cetak Semua Jurnal (' . $processedJournals->count() . ' Lembar)' : 'Cetak Jurnal (Print / PDF)' }}
                    </span>
                </button>
            </div>
        </div>
    </div>

    {{-- KONDISI JIKA TIDAK ADA JURNAL YANG SUDAH DIISI PADA PRINT ALL --}}
    @if($processedJournals->isEmpty())
        <div class="max-w-[210mm] mx-auto my-12 p-8 bg-white rounded-xl shadow-lg text-center text-slate-700">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <h3 class="text-base font-bold text-slate-800">Tidak Ada Jurnal yang Sudah Diisi</h3>
            <p class="text-xs text-slate-500 mt-1">Belum ada jurnal yang berstatus 'Sudah Diisi' pada tanggal atau filter yang dipilih.</p>
            <div class="mt-5">
                <a href="{{ route('waka.journals.index', request()->query()) }}" class="px-4 py-2 rounded-xl bg-indigo-600 text-white font-bold text-xs inline-block">Kembali ke Monitoring Jurnal</a>
            </div>
        </div>
    @endif

    {{-- =========================================================================
         LOOP SETIAP LEMBAR KERJA JURNAL KBM (1 JURNAL = 1 LEMBAR A4)
         ========================================================================= --}}
    @foreach($processedJournals as $idx => $item)
        <div class="print-page text-black {{ !$loop->last ? 'page-break-after-always' : '' }}">
            
            <!-- 1. KOP SURAT RESMI SEKOLAH -->
            <div class="text-center pb-2 mb-2.5">
                <h3 class="text-[10.5px] font-bold tracking-wider uppercase text-gray-800 m-0">PEMERINTAH DAERAH DAERAH ISTIMEWA YOGYAKARTA</h3>
                <h2 class="text-[11.5px] font-black uppercase text-gray-900 m-0">DINAS PENDIDIKAN, PEMUDA DAN OLAHRAGA</h2>
                <h1 class="text-base font-black uppercase tracking-wider text-black mt-0.5 mb-0.5">SMK NEGERI 1 BANTUL</h1>
                <p class="text-[9.5px] text-gray-600 m-0">Jalan Parangtritis Km 11, Sabdodadi, Bantul, D.I. Yogyakarta 55715 • Telp: (0274) 367156</p>
                <p class="text-[9px] text-gray-500 m-0">Website: smkn1bantul.sch.id &nbsp;•&nbsp; Email: info@smkn1bantul.sch.id</p>
                
                <!-- Garis Ganda Kop Surat -->
                <div class="border-t-2 border-black mt-1.5 mb-[1px]"></div>
                <div class="border-t-[1px] border-black"></div>
            </div>

            <!-- 2. JUDUL DOKUMEN -->
            <div class="text-center mb-2.5">
                <h4 class="text-xs font-black uppercase tracking-wide underline text-black m-0">LEMBAR JURNAL DAN PRESENSI KEGIATAN BELAJAR MENGAJAR (KBM)</h4>
                <p class="text-[9.5px] font-bold text-gray-700 mt-0.5 m-0">
                    Tahun Ajaran {{ $academicYear }} &nbsp;•&nbsp; {{ $semester }}
                </p>
            </div>

            <!-- 3. IDENTITAS SESI KBM (GRID 2 KOLOM RAPI) -->
            <div class="border border-gray-300 rounded-lg p-2.5 mb-2.5 bg-gray-50/50 text-[10px]">
                <div class="grid grid-cols-2 gap-x-6 gap-y-1">
                    <div class="flex items-start">
                        <span class="w-28 font-bold text-gray-700 shrink-0">Guru Pengampu</span>
                        <span class="mr-1.5 font-bold">:</span>
                        <span class="font-extrabold text-black">{{ $item->schedule?->teacher?->name ?? 'Guru Pengampu' }}</span>
                    </div>
                    <div class="flex items-start">
                        <span class="w-28 font-bold text-gray-700 shrink-0">Hari, Tanggal</span>
                        <span class="mr-1.5 font-bold">:</span>
                        <span class="font-semibold text-black">{{ $item->dayOfWeek }}, {{ \Carbon\Carbon::parse($item->date)->translatedFormat('d F Y') }}</span>
                    </div>

                    <div class="flex items-start">
                        <span class="w-28 font-bold text-gray-700 shrink-0">NIP / NUPTK</span>
                        <span class="mr-1.5 font-bold">:</span>
                        <span class="font-mono text-gray-800">{{ $item->schedule?->teacher?->nip ?? '-' }}</span>
                    </div>
                    <div class="flex items-start">
                        <span class="w-28 font-bold text-gray-700 shrink-0">Jam Pelajaran</span>
                        <span class="mr-1.5 font-bold">:</span>
                        <span class="font-semibold text-black">
                            {{ $item->schedule?->period_label }} 
                            @if($item->schedule?->start_time)
                                ({{ substr($item->schedule->start_time, 0, 5) }} - {{ substr($item->schedule->end_time, 0, 5) }} WIB)
                            @endif
                        </span>
                    </div>

                    <div class="flex items-start">
                        <span class="w-28 font-bold text-gray-700 shrink-0">Mata Pelajaran</span>
                        <span class="mr-1.5 font-bold">:</span>
                        <span class="font-extrabold text-indigo-900">{{ $item->schedule?->subject }}</span>
                    </div>
                    <div class="flex items-start">
                        <span class="w-28 font-bold text-gray-700 shrink-0">Kelas / Ruang</span>
                        <span class="mr-1.5 font-bold">:</span>
                        <span class="font-bold text-black">
                            Kelas {{ $item->schedule?->schoolClass?->class_name }} &nbsp;•&nbsp; {{ $item->schedule?->room?->room_name ?? 'Ruang KBM' }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- =========================================================================
                 4. TATA LETAK PROPORSIAL: MATERI PEMBELAJARAN (72%) & FOTO KBM (28%)
                 - Catatan materi lega dan proporsional (area utama)
                 - Foto dokumentasi KBM berukuran pas & rapi (maksimal 80px, proporsional)
                 ========================================================================= --}}
            <table style="width: 100%; border: 1px solid #cbd5e1; border-collapse: collapse; margin-bottom: 10px; background-color: #ffffff; table-layout: fixed;">
                <tr>
                    @if($item->journal->photo_path)
                        <!-- Kolom Kiri: Catatan Materi (72% Lebar) -->
                        <td style="width: 72%; vertical-align: top; padding: 8px 12px; border-right: 1px solid #cbd5e1;">
                            <div style="font-size: 9.5px; font-weight: 700; text-transform: uppercase; color: #475569; margin-bottom: 4px; letter-spacing: 0.5px;">
                                Catatan Materi & Aktivitas Pembelajaran:
                            </div>
                            <div style="font-size: 10px; line-height: 1.45; color: #0f172a; min-height: 85px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 6px 10px; word-break: break-word;">
                                {{ $item->journal->topic_description ?? 'Tidak ada catatan uraian materi pembelajaran.' }}
                            </div>
                        </td>

                        <!-- Kolom Kanan: Foto Dokumentasi KBM (28% Lebar - Rapi & Proporsional) -->
                        <td style="width: 28%; vertical-align: top; padding: 8px 10px; text-align: center;">
                            <div style="font-size: 9.5px; font-weight: 700; text-transform: uppercase; color: #475569; margin-bottom: 4px; letter-spacing: 0.5px;">
                                Dokumentasi KBM:
                            </div>
                            <div style="border: 1px solid #cbd5e1; border-radius: 6px; padding: 2px; background-color: #f8fafc; display: flex; align-items: center; justify-content: center; height: 85px; overflow: hidden;">
                                <img src="{{ url('media/' . $item->journal->photo_path) }}" 
                                     alt="Dokumentasi KBM" 
                                     style="max-height: 80px; max-width: 100%; width: auto; object-fit: contain; border-radius: 4px;" />
                            </div>
                        </td>
                    @else
                        <!-- Tanpa Foto: Materi Mengisi 100% Lebar -->
                        <td style="width: 100%; vertical-align: top; padding: 8px 12px;">
                            <div style="font-size: 9.5px; font-weight: 700; text-transform: uppercase; color: #475569; margin-bottom: 4px; letter-spacing: 0.5px;">
                                Catatan Materi & Aktivitas Pembelajaran:
                            </div>
                            <div style="font-size: 10px; line-height: 1.45; color: #0f172a; min-height: 85px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 6px 10px; word-break: break-word;">
                                {{ $item->journal->topic_description ?? 'Tidak ada catatan uraian materi pembelajaran.' }}
                            </div>
                        </td>
                    @endif
                </tr>
            </table>

            <!-- 5. REKAPITULASI KEHADIRAN SISWA PADA SESI INI -->
            <div class="mb-2.5">
                <div class="flex items-center justify-between pb-1">
                    <h5 class="font-bold uppercase text-[9.5px] text-gray-700 tracking-wider m-0">
                        Daftar Presensi Kehadiran Siswa:
                    </h5>
                    <div class="text-[9.5px] font-semibold text-gray-800 flex items-center gap-2">
                        <span class="font-bold text-emerald-800">Hadir: {{ $item->hadirCount }}</span>
                        <span>•</span>
                        <span class="font-bold text-amber-800">Sakit: {{ $item->sakitCount }}</span>
                        <span>•</span>
                        <span class="font-bold text-blue-800">Izin: {{ $item->izinCount }}</span>
                        <span>•</span>
                        <span class="font-bold text-red-800">Alpa: {{ $item->alpaCount }}</span>
                        <span>•</span>
                        <span class="font-bold">Total: {{ $item->attendances->count() }} Siswa</span>
                    </div>
                </div>

                @if($item->attendances->isEmpty())
                    <div class="text-center py-3 border border-dashed border-gray-300 rounded text-gray-500 text-[10px] italic">
                        Belum ada data presensi yang tersimpan pada sesi jurnal ini.
                    </div>
                @else
                    @php
                        $half = (int) ceil($item->attendances->count() / 2);
                        $col1 = $item->attendances->slice(0, $half)->values();
                        $col2 = $item->attendances->slice($half)->values();
                    @endphp

                    <!-- Tabel Presensi 2 Kolom Berdampingan (Muat Pas 1 Lembar A4) -->
                    <div class="grid grid-cols-2 gap-2.5">
                        <!-- Kolom 1 (Siswa 1 s/d Half) -->
                        <div>
                            <table class="tbl-presensi">
                                <thead>
                                    <tr>
                                        <th style="width: 7%; text-align: center;">No</th>
                                        <th style="width: 21%;">NIS</th>
                                        <th style="width: 52%;">Nama Siswa</th>
                                        <th style="width: 20%; text-align: center;">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($col1 as $i => $att)
                                        <tr>
                                            <td style="text-align: center;">{{ $i + 1 }}</td>
                                            <td class="font-mono text-[9px]">{{ $att->student?->nis ?? '-' }}</td>
                                            <td class="font-bold truncate text-[9.5px]" style="max-width: 120px;" title="{{ $att->student?->name }}">
                                                {{ $att->student?->name ?? 'Siswa' }}
                                            </td>
                                            <td style="text-align: center; font-weight: bold; font-size: 9.5px;">
                                                @if($att->status === 'Hadir')
                                                    <span class="text-emerald-800">Hadir</span>
                                                @elseif($att->status === 'Sakit')
                                                    <span class="text-amber-800">Sakit</span>
                                                @elseif($att->status === 'Izin')
                                                    <span class="text-blue-800">Izin</span>
                                                @else
                                                    <span class="text-red-800">Alpa</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Kolom 2 (Siswa Half+1 s/d End) -->
                        <div>
                            <table class="tbl-presensi">
                                <thead>
                                    <tr>
                                        <th style="width: 7%; text-align: center;">No</th>
                                        <th style="width: 21%;">NIS</th>
                                        <th style="width: 52%;">Nama Siswa</th>
                                        <th style="width: 20%; text-align: center;">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($col2 as $j => $att)
                                        <tr>
                                            <td style="text-align: center;">{{ $half + $j + 1 }}</td>
                                            <td class="font-mono text-[9px]">{{ $att->student?->nis ?? '-' }}</td>
                                            <td class="font-bold truncate text-[9.5px]" style="max-width: 120px;" title="{{ $att->student?->name }}">
                                                {{ $att->student?->name ?? 'Siswa' }}
                                            </td>
                                            <td style="text-align: center; font-weight: bold; font-size: 9.5px;">
                                                @if($att->status === 'Hadir')
                                                    <span class="text-emerald-800">Hadir</span>
                                                @elseif($att->status === 'Sakit')
                                                    <span class="text-amber-800">Sakit</span>
                                                @elseif($att->status === 'Izin')
                                                    <span class="text-blue-800">Izin</span>
                                                @else
                                                    <span class="text-red-800">Alpa</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>

            <!-- 6. LEMBAR PENGESAHAN / TANDA TANGAN LEGAL -->
            <div class="avoid-break grid grid-cols-2 gap-8 mt-4 pt-1 text-[10px] text-black">
                <div class="text-center">
                    <p class="m-0">Mengetahui,</p>
                    <p class="font-bold m-0">Kepala Sekolah / Waka Kurikulum</p>
                    <div class="h-12"></div>
                    <p class="font-bold underline m-0">_________________________</p>
                    <p class="text-[9.5px] text-gray-600 m-0">NIP. ........................................</p>
                </div>
                <div class="text-center">
                    <p class="m-0">Bantul, {{ \Carbon\Carbon::parse($item->date)->translatedFormat('d F Y') }}</p>
                    <p class="font-bold m-0">Guru Mata Pelajaran</p>
                    <div class="h-12"></div>
                    <p class="font-bold underline m-0">{{ $item->schedule?->teacher?->name ?? 'Guru Pengampu' }}</p>
                    <p class="text-[9.5px] text-gray-600 m-0">NIP/NUPTK: {{ $item->schedule?->teacher?->nip ?? '........................................' }}</p>
                </div>
            </div>

        </div>
    @endforeach

</body>
</html>
