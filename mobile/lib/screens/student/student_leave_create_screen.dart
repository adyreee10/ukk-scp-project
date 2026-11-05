import 'dart:io';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:file_picker/file_picker.dart';
import '../../core/api/api_client.dart';
import '../../core/api/api_endpoints.dart';
import '../../core/theme/app_theme.dart';
import '../../core/utils/date_formatter.dart';
import '../../widgets/custom_button.dart';
import '../../widgets/custom_card.dart';

class StudentLeaveCreateScreen extends StatefulWidget {
  const StudentLeaveCreateScreen({super.key});

  @override
  State<StudentLeaveCreateScreen> createState() => _StudentLeaveCreateScreenState();
}

class _StudentLeaveCreateScreenState extends State<StudentLeaveCreateScreen> {
  final _reasonController = TextEditingController();
  DateTime _startDate = DateTime.now();
  DateTime _endDate = DateTime.now();
  String _leaveType = 'Sakit';
  File? _selectedFile;
  String? _selectedFileName;
  bool _isLoading = false;
  String? _errorMessage;

  final ImagePicker _picker = ImagePicker();

  @override
  void dispose() {
    _reasonController.dispose();
    super.dispose();
  }

  Future<void> _selectStartDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _startDate,
      firstDate: DateTime.now().subtract(const Duration(days: 7)),
      lastDate: DateTime.now().add(const Duration(days: 30)),
      builder: (context, child) => Theme(data: AppTheme.darkTheme, child: child!),
    );
    if (picked != null) {
      setState(() {
        _startDate = picked;
        if (_endDate.isBefore(_startDate)) {
          _endDate = _startDate;
        }
      });
    }
  }

  Future<void> _selectEndDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _endDate,
      firstDate: _startDate,
      lastDate: DateTime.now().add(const Duration(days: 30)),
      builder: (context, child) => Theme(data: AppTheme.darkTheme, child: child!),
    );
    if (picked != null) {
      setState(() {
        _endDate = picked;
      });
    }
  }

  // 1. Ambil Foto Realtime dari Kamera
  Future<void> _takeCameraPhoto() async {
    try {
      final XFile? photo = await _picker.pickImage(
        source: ImageSource.camera,
        imageQuality: 80,
        maxWidth: 1920,
        maxHeight: 1080,
      );
      if (photo != null) {
        setState(() {
          _selectedFile = File(photo.path);
          _selectedFileName = 'Foto_Kamera_${DateTime.now().millisecondsSinceEpoch}.jpg';
          _errorMessage = null;
        });
      }
    } catch (e) {
      setState(() {
        _errorMessage = 'Gagal membuka kamera: ${e.toString()}';
      });
    }
  }

  // 2. Pilih Dokumen atau Galeri
  Future<void> _pickDocumentOrGallery() async {
    try {
      final result = await FilePicker.pickFiles(
        type: FileType.custom,
        allowedExtensions: ['pdf', 'jpg', 'jpeg', 'png'],
      );
      if (result != null && result.files.single.path != null) {
        setState(() {
          _selectedFile = File(result.files.single.path!);
          _selectedFileName = result.files.single.name;
          _errorMessage = null;
        });
      }
    } catch (e) {
      setState(() {
        _errorMessage = 'Gagal memilih dokumen: ${e.toString()}';
      });
    }
  }

  Future<void> _handleSubmit() async {
    final reason = _reasonController.text.trim();
    if (reason.isEmpty) {
      setState(() => _errorMessage = 'Harap isi alasan permohonan izin.');
      return;
    }
    if (_selectedFile == null) {
      setState(() => _errorMessage = 'Wajib melampirkan foto realtime kamera atau dokumen bukti izin.');
      return;
    }

    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    final response = await ApiClient.multipart(
      endpoint: ApiEndpoints.studentLeaveRequests,
      method: 'POST',
      fields: {
        'start_date': DateFormatter.toYmd(_startDate),
        'end_date': DateFormatter.toYmd(_endDate),
        'leave_type': _leaveType,
        'reason': reason,
      },
      fileFieldName: 'document',
      file: _selectedFile,
    );

    setState(() {
      _isLoading = false;
    });

    if (response.isSuccess) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Permohonan izin berhasil diajukan! Menunggu verifikasi Wali Kelas/BK.'),
          backgroundColor: AppTheme.success,
        ),
      );
      Navigator.pop(context, true); // Kembali & refresh
    } else {
      setState(() {
        _errorMessage = response.message;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppTheme.bgDark,
      appBar: AppBar(
        title: const Text('Ajukan Izin Baru'),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Banner Info
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: AppTheme.primaryIndigo.withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: AppTheme.primaryIndigo.withValues(alpha: 0.3)),
              ),
              child: const Row(
                children: [
                  Icon(Icons.camera_alt_rounded, color: AppTheme.primaryIndigo, size: 22),
                  SizedBox(width: 12),
                  Expanded(
                    child: Text(
                      'Sertakan bukti foto realtime (surat keterangan dokter/kegiatan) untuk mempercepat verifikasi Wali Kelas & Guru BK.',
                      style: TextStyle(fontSize: 12, color: AppTheme.textPrimary, height: 1.4),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 20),

            if (_errorMessage != null) ...[
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: AppTheme.danger.withValues(alpha: 0.15),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: AppTheme.danger.withValues(alpha: 0.4)),
                ),
                child: Text(_errorMessage!, style: const TextStyle(color: Color(0xFFFB7185), fontSize: 12)),
              ),
              const SizedBox(height: 16),
            ],

            // Jenis Izin
            const Text('Jenis Permohonan', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
            const SizedBox(height: 8),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 14),
              decoration: BoxDecoration(
                color: AppTheme.cardDark,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: AppTheme.borderDark),
              ),
              child: DropdownButtonHideUnderline(
                child: DropdownButton<String>(
                  value: _leaveType,
                  isExpanded: true,
                  dropdownColor: AppTheme.cardDark,
                  items: const [
                    DropdownMenuItem(value: 'Sakit', child: Text('Sakit (Surat Dokter)')),
                    DropdownMenuItem(value: 'Izin', child: Text('Izin (Keperluan Keluarga/Penting)')),
                    DropdownMenuItem(value: 'Dispensasi', child: Text('Dispensasi (Lomba / Tugas Sekolah)')),
                  ],
                  onChanged: (val) {
                    if (val != null) setState(() => _leaveType = val);
                  },
                ),
              ),
            ),
            const SizedBox(height: 18),

            // Rentang Tanggal
            Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('Mulai Dari', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                      const SizedBox(height: 8),
                      InkWell(
                        onTap: _selectStartDate,
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 13),
                          decoration: BoxDecoration(
                            color: AppTheme.cardDark,
                            borderRadius: BorderRadius.circular(14),
                            border: Border.all(color: AppTheme.borderDark),
                          ),
                          child: Row(
                            children: [
                              const Icon(Icons.calendar_today_rounded, size: 16, color: AppTheme.primaryIndigo),
                              const SizedBox(width: 8),
                              Text(DateFormatter.formatShort(_startDate), style: const TextStyle(fontSize: 13)),
                            ],
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('Sampai Dengan', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                      const SizedBox(height: 8),
                      InkWell(
                        onTap: _selectEndDate,
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 13),
                          decoration: BoxDecoration(
                            color: AppTheme.cardDark,
                            borderRadius: BorderRadius.circular(14),
                            border: Border.all(color: AppTheme.borderDark),
                          ),
                          child: Row(
                            children: [
                              const Icon(Icons.calendar_today_rounded, size: 16, color: AppTheme.primaryIndigo),
                              const SizedBox(width: 8),
                              Text(DateFormatter.formatShort(_endDate), style: const TextStyle(fontSize: 13)),
                            ],
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: 18),

            // Alasan
            const Text('Keterangan / Alasan Lengkap', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
            const SizedBox(height: 8),
            TextField(
              controller: _reasonController,
              maxLines: 4,
              decoration: const InputDecoration(
                hintText: 'Tuliskan rincian alasan izin secara jelas...',
              ),
            ),
            const SizedBox(height: 22),

            // DOKUMEN / FOTO REALTIME BUKTI (FITUR UTAMA REALTIME CAMERA)
            const Text('Unggah Bukti / Foto Realtime', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
            const SizedBox(height: 8),

            if (_selectedFile != null) ...[
              CustomCard(
                padding: const EdgeInsets.all(12),
                backgroundColor: AppTheme.surfaceDark,
                child: Row(
                  children: [
                    ClipRRect(
                      borderRadius: BorderRadius.circular(8),
                      child: _selectedFileName!.toLowerCase().endsWith('.pdf')
                          ? Container(
                              width: 50,
                              height: 50,
                              color: AppTheme.warning.withValues(alpha: 0.2),
                              child: const Icon(Icons.picture_as_pdf_rounded, color: AppTheme.warning, size: 28),
                            )
                          : Image.file(_selectedFile!, width: 50, height: 50, fit: BoxFit.cover),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            _selectedFileName ?? 'File Terpilih',
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
                          ),
                          const SizedBox(height: 2),
                          const Text(
                            'Siap diunggah',
                            style: TextStyle(color: AppTheme.success, fontSize: 11),
                          ),
                        ],
                      ),
                    ),
                    IconButton(
                      icon: const Icon(Icons.close_rounded, color: AppTheme.danger, size: 20),
                      onPressed: () {
                        setState(() {
                          _selectedFile = null;
                          _selectedFileName = null;
                        });
                      },
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 12),
            ],

            // 2 Tombol Pilihan: Kamera Realtime vs Berkas
            Row(
              children: [
                Expanded(
                  child: OutlinedButton.icon(
                    style: OutlinedButton.styleFrom(
                      foregroundColor: AppTheme.primaryIndigo,
                      side: const BorderSide(color: AppTheme.primaryIndigo),
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                    icon: const Icon(Icons.camera_alt_rounded, size: 18),
                    label: const Text('Foto Realtime', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                    onPressed: _takeCameraPhoto,
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: OutlinedButton.icon(
                    style: OutlinedButton.styleFrom(
                      foregroundColor: AppTheme.textSecondary,
                      side: const BorderSide(color: AppTheme.borderDark),
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                    icon: const Icon(Icons.folder_open_rounded, size: 18),
                    label: const Text('Pilih Berkas', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                    onPressed: _pickDocumentOrGallery,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 32),

            // Submit Button
            CustomButton(
              text: 'Kirim Permohonan Izin',
              icon: Icons.send_rounded,
              isLoading: _isLoading,
              onPressed: _handleSubmit,
            ),
            const SizedBox(height: 20),
          ],
        ),
      ),
    );
  }
}
