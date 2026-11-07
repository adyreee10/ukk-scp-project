import 'dart:io';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import '../../core/api/api_client.dart';
import '../../core/api/api_endpoints.dart';
import '../../core/theme/app_theme.dart';
import '../../core/utils/date_formatter.dart';
import '../../models/schedule_model.dart';
import '../../widgets/custom_button.dart';
import '../../widgets/custom_card.dart';

class TeacherJournalCreateScreen extends StatefulWidget {
  final List<ScheduleModel> schedules;
  final ScheduleModel? initialSchedule;

  const TeacherJournalCreateScreen({
    super.key,
    required this.schedules,
    this.initialSchedule,
  });

  @override
  State<TeacherJournalCreateScreen> createState() => _TeacherJournalCreateScreenState();
}

class _TeacherJournalCreateScreenState extends State<TeacherJournalCreateScreen> {
  final _topicController = TextEditingController();
  ScheduleModel? _selectedSchedule;
  File? _capturedPhoto;
  String? _photoName;
  bool _isLoading = false;
  String? _errorMessage;

  final ImagePicker _picker = ImagePicker();

  @override
  void initState() {
    super.initState();
    if (widget.initialSchedule != null) {
      _selectedSchedule = widget.initialSchedule;
    } else if (widget.schedules.isNotEmpty) {
      _selectedSchedule = widget.schedules.first;
    }
  }

  @override
  void dispose() {
    _topicController.dispose();
    super.dispose();
  }

  // AMBIL FOTO REALTIME KAMERA KBM (FITUR REALTIME KHUSUS MOBILE)
  Future<void> _takeRealtimeKbmPhoto() async {
    try {
      final XFile? photo = await _picker.pickImage(
        source: ImageSource.camera,
        imageQuality: 80,
        maxWidth: 1920,
        maxHeight: 1080,
      );
      if (photo != null) {
        setState(() {
          _capturedPhoto = File(photo.path);
          _photoName = 'KBM_Realtime_${DateTime.now().millisecondsSinceEpoch}.jpg';
          _errorMessage = null;
        });
      }
    } catch (e) {
      setState(() {
        _errorMessage = 'Gagal mengakses kamera: ${e.toString()}';
      });
    }
  }

  Future<void> _handleSubmit() async {
    if (_selectedSchedule == null) {
      setState(() => _errorMessage = 'Harap pilih jadwal mengajar.');
      return;
    }

    final topic = _topicController.text.trim();
    if (topic.isEmpty) {
      setState(() => _errorMessage = 'Harap tuliskan materi / topik pembelajaran.');
      return;
    }

    if (_capturedPhoto == null) {
      setState(() => _errorMessage = 'Wajib melampirkan foto realtime kegiatan KBM kelas.');
      return;
    }

    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    final todayStr = DateFormatter.toYmd(DateTime.now());

    final response = await ApiClient.multipart(
      endpoint: ApiEndpoints.teacherJournals,
      method: 'POST',
      fields: {
        'schedule_id': _selectedSchedule!.id.toString(),
        'date': todayStr,
        'topic_description': topic,
      },
      fileFieldName: 'photo',
      file: _capturedPhoto,
    );

    setState(() {
      _isLoading = false;
    });

    if (response.isSuccess) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Jurnal mengajar berhasil disimpan & perizinan siswa otomatis tersinkronisasi!'),
          backgroundColor: AppTheme.success,
        ),
      );
      Navigator.pop(context, true);
    } else {
      setState(() {
        _errorMessage = response.message;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final todayStr = DateFormatter.formatIndonesian(DateTime.now());

    return Scaffold(
      backgroundColor: AppTheme.bgDark,
      appBar: AppBar(
        title: const Text('Isi Jurnal Mengajar'),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Info Bar Hari Ini
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: AppTheme.primaryIndigo.withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: AppTheme.primaryIndigo.withValues(alpha: 0.3)),
              ),
              child: Row(
                children: [
                  const Icon(Icons.calendar_today_rounded, color: AppTheme.primaryIndigo, size: 20),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Text(
                      'Pengisian Jurnal KBM: $todayStr (Cukup 1x per hari)',
                      style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: Colors.white),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 18),

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

            // Pilih Jadwal KBM
            const Text('Pilih Jadwal Mengajar', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
            const SizedBox(height: 8),

            if (widget.schedules.isEmpty)
              Container(
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: AppTheme.cardDark,
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: AppTheme.borderDark),
                ),
                child: const Text('Tidak ada jadwal KBM yang tersedia.', style: TextStyle(color: AppTheme.textMuted)),
              )
            else
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 14),
                decoration: BoxDecoration(
                  color: AppTheme.cardDark,
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: AppTheme.borderDark),
                ),
                child: DropdownButtonHideUnderline(
                  child: DropdownButton<ScheduleModel>(
                    value: _selectedSchedule,
                    isExpanded: true,
                    dropdownColor: AppTheme.cardDark,
                    items: widget.schedules.map((s) {
                      return DropdownMenuItem<ScheduleModel>(
                        value: s,
                        child: Text(
                          '${s.subjectName} - ${s.className ?? "Kelas"} (${DateFormatter.formatTime(s.startTime)}-${DateFormatter.formatTime(s.endTime)})',
                          style: const TextStyle(fontSize: 13, color: Colors.white),
                        ),
                      );
                    }).toList(),
                    onChanged: (val) {
                      if (val != null) setState(() => _selectedSchedule = val);
                    },
                  ),
                ),
              ),
            const SizedBox(height: 18),

            // Topik Pembelajaran
            const Text('Materi / Pembahasan Pembelajaran', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
            const SizedBox(height: 8),
            TextField(
              controller: _topicController,
              maxLines: 4,
              decoration: const InputDecoration(
                hintText: 'Contoh: Pembahasan Konsep Basis Data Relasional dan Praktik Normalisasi Data...',
              ),
            ),
            const SizedBox(height: 22),

            // FOTO REALTIME KEGIATAN KBM
            const Text('Foto Realtime Kegiatan Pembelajaran (KBM)', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
            const SizedBox(height: 4),
            const Text(
              'Jepret langsung suasana kelas saat pembelajaran sedang berlangsung.',
              style: TextStyle(fontSize: 11, color: AppTheme.textSecondary),
            ),
            const SizedBox(height: 10),

            if (_capturedPhoto != null) ...[
              CustomCard(
                padding: const EdgeInsets.all(12),
                backgroundColor: AppTheme.surfaceDark,
                child: Row(
                  children: [
                    ClipRRect(
                      borderRadius: BorderRadius.circular(8),
                      child: Image.file(_capturedPhoto!, width: 60, height: 60, fit: BoxFit.cover),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            _photoName ?? 'Foto KBM',
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
                          ),
                          const SizedBox(height: 2),
                          const Text('Foto Realtime Siap Disimpan', style: TextStyle(color: AppTheme.success, fontSize: 11)),
                        ],
                      ),
                    ),
                    IconButton(
                      icon: const Icon(Icons.close_rounded, color: AppTheme.danger, size: 20),
                      onPressed: () {
                        setState(() {
                          _capturedPhoto = null;
                          _photoName = null;
                        });
                      },
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 12),
            ],

            // Tombol Kamera Realtime (Wajib Kamera Langsung)
            CustomButton(
              text: _capturedPhoto == null ? 'Ambil Foto Kamera Real-Time' : 'Ambil Ulang Foto Kamera',
              icon: Icons.camera_alt_rounded,
              onPressed: _takeRealtimeKbmPhoto,
            ),
            const SizedBox(height: 6),
            const Row(
              children: [
                Icon(Icons.lock_clock_rounded, size: 14, color: AppTheme.warning),
                SizedBox(width: 6),
                Expanded(
                  child: Text(
                    'Wajib foto langsung saat KBM. Pilihan galeri dinonaktifkan untuk mencegah kecurangan.',
                    style: TextStyle(fontSize: 10, color: AppTheme.textSecondary),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 32),

            // Submit Button
            CustomButton(
              text: 'Simpan Jurnal Mengajar',
              icon: Icons.save_rounded,
              gradient: AppTheme.successGradient,
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
