import 'package:flutter/material.dart';
import '../../core/api/api_client.dart';
import '../../core/api/api_endpoints.dart';
import '../../core/theme/app_theme.dart';
import '../../core/utils/session_manager.dart';
import '../../models/journal_model.dart';
import '../../models/schedule_model.dart';
import '../../widgets/custom_card.dart';
import 'teacher_journal_create_screen.dart';
import 'teacher_attendance_input_screen.dart';

class TeacherJournalScreen extends StatefulWidget {
  const TeacherJournalScreen({super.key});

  @override
  State<TeacherJournalScreen> createState() => _TeacherJournalScreenState();
}

class _TeacherJournalScreenState extends State<TeacherJournalScreen> {
  List<JournalModel> _journals = [];
  List<ScheduleModel> _schedules = [];
  String _baseUrl = '';
  bool _isLoading = true;
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    _fetchData();
  }

  Future<void> _fetchData() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    final url = await SessionManager.getBaseUrl();
    setState(() {
      _baseUrl = url.replaceAll('/api', '');
    });

    // Ambil Jurnal & Jadwal
    final journalRes = await ApiClient.get(ApiEndpoints.teacherJournals);
    final scheduleRes = await ApiClient.get(ApiEndpoints.teacherSchedules);

    setState(() {
      _isLoading = false;
    });

    if (journalRes.isSuccess && journalRes.data != null) {
      try {
        final list = (journalRes.data['data']['data'] as List? ?? journalRes.data['data'] as List? ?? [])
            .map((item) => JournalModel.fromJson(item))
            .toList();
        setState(() => _journals = list);
      } catch (e) {
        setState(() => _errorMessage = 'Gagal memproses data jurnal: ${e.toString()}');
      }
    } else {
      setState(() => _errorMessage = journalRes.message);
    }

    if (scheduleRes.isSuccess && scheduleRes.data != null) {
      try {
        final sList = (scheduleRes.data['data'] as List? ?? [])
            .map((item) => ScheduleModel.fromJson(item))
            .toList();
        setState(() => _schedules = sList);
      } catch (_) {}
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppTheme.bgDark,
      appBar: AppBar(
        title: const Text('Jurnal Mengajar Guru'),
        actions: [
          IconButton(icon: const Icon(Icons.refresh_rounded), onPressed: _fetchData),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        backgroundColor: AppTheme.primaryIndigo,
        icon: const Icon(Icons.add_rounded, color: Colors.white),
        label: const Text('Isi Jurnal KBM', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.white)),
        onPressed: () async {
          final res = await Navigator.push<bool>(
            context,
            MaterialPageRoute(
              builder: (_) => TeacherJournalCreateScreen(schedules: _schedules),
            ),
          );
          if (res == true) _fetchData();
        },
      ),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_isLoading) {
      return const Center(child: CircularProgressIndicator(color: AppTheme.primaryIndigo));
    }

    if (_errorMessage != null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.error_outline_rounded, size: 48, color: AppTheme.danger),
              const SizedBox(height: 12),
              Text(_errorMessage!, textAlign: TextAlign.center, style: const TextStyle(color: AppTheme.textSecondary)),
              const SizedBox(height: 16),
              ElevatedButton(onPressed: _fetchData, child: const Text('Coba Lagi')),
            ],
          ),
        ),
      );
    }

    if (_journals.isEmpty) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                padding: const EdgeInsets.all(18),
                decoration: BoxDecoration(
                  color: AppTheme.surfaceDark,
                  shape: BoxShape.circle,
                  border: Border.all(color: AppTheme.borderDark),
                ),
                child: const Icon(Icons.auto_stories_outlined, size: 48, color: AppTheme.textMuted),
              ),
              const SizedBox(height: 16),
              const Text('Belum Ada Jurnal Pembelajaran', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
              const SizedBox(height: 6),
              const Text(
                'Klik tombol "Isi Jurnal KBM" di bawah untuk mencatat materi dan mengambil foto realtime kegiatan kelas.',
                textAlign: TextAlign.center,
                style: TextStyle(fontSize: 12, color: AppTheme.textSecondary),
              ),
            ],
          ),
        ),
      );
    }

    return RefreshIndicator(
      color: AppTheme.primaryIndigo,
      backgroundColor: AppTheme.surfaceDark,
      onRefresh: _fetchData,
      child: ListView.separated(
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 80),
        itemCount: _journals.length,
        separatorBuilder: (_, __) => const SizedBox(height: 12),
        itemBuilder: (context, index) {
          final journal = _journals[index];
          final schedule = journal.schedule;

          String? photoFullUrl;
          if (journal.photoPath != null && journal.photoPath!.isNotEmpty) {
            photoFullUrl = '$_baseUrl/storage/${journal.photoPath}';
          }

          return CustomCard(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Expanded(
                      child: Text(
                        schedule?.subjectName ?? 'Mata Pelajaran',
                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: Colors.white),
                      ),
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                      decoration: BoxDecoration(
                        color: AppTheme.primaryIndigo.withValues(alpha: 0.15),
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: Text(
                        journal.date,
                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 11, color: AppTheme.primaryIndigo),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 4),
                Text(
                  'Kelas: ${schedule?.className ?? "-"} • ${schedule?.roomName ?? "-"}',
                  style: const TextStyle(fontSize: 12, color: AppTheme.textSecondary),
                ),
                const SizedBox(height: 10),

                // Topik
                Text(
                  journal.topicDescription,
                  style: const TextStyle(fontSize: 13, color: AppTheme.textPrimary, height: 1.4),
                ),
                const SizedBox(height: 12),

                // Foto Bukti KBM Realtime jika ada
                if (photoFullUrl != null) ...[
                  ClipRRect(
                    borderRadius: BorderRadius.circular(10),
                    child: Container(
                      height: 120,
                      width: double.infinity,
                      color: AppTheme.surfaceDark,
                      child: Image.network(
                        photoFullUrl,
                        fit: BoxFit.cover,
                        errorBuilder: (_, __, ___) => const Center(
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Icon(Icons.image_not_supported_rounded, color: AppTheme.textMuted, size: 20),
                              SizedBox(width: 6),
                              Text('Foto KBM Realtime Tersimpan', style: TextStyle(fontSize: 11, color: AppTheme.textMuted)),
                            ],
                          ),
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(height: 12),
                ],

                const Divider(color: AppTheme.borderDark, height: 1),
                const SizedBox(height: 10),

                // Tombol Presensi Siswa
                Row(
                  mainAxisAlignment: MainAxisAlignment.end,
                  children: [
                    ElevatedButton.icon(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppTheme.success,
                        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                      ),
                      icon: const Icon(Icons.checklist_rounded, size: 16, color: Colors.white),
                      label: const Text('Presensi Siswa', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.white)),
                      onPressed: () {
                        Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (_) => TeacherAttendanceInputScreen(journal: journal),
                          ),
                        );
                      },
                    ),
                  ],
                ),
              ],
            ),
          );
        },
      ),
    );
  }
}
