import 'package:flutter/material.dart';
import '../../core/api/api_client.dart';
import '../../core/api/api_endpoints.dart';
import '../../core/theme/app_theme.dart';
import '../../models/journal_model.dart';
import '../../widgets/custom_button.dart';
import '../../widgets/custom_card.dart';

class TeacherAttendanceInputScreen extends StatefulWidget {
  final JournalModel journal;

  const TeacherAttendanceInputScreen({super.key, required this.journal});

  @override
  State<TeacherAttendanceInputScreen> createState() => _TeacherAttendanceInputScreenState();
}

class _TeacherAttendanceInputScreenState extends State<TeacherAttendanceInputScreen> {
  List<Map<String, dynamic>> _studentAttendances = [];
  bool _isLoading = true;
  bool _isSaving = false;
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    _loadJournalAttendances();
  }

  Future<void> _loadJournalAttendances() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    final response = await ApiClient.get(
      '${ApiEndpoints.teacherJournals}/${widget.journal.id}',
    );

    setState(() {
      _isLoading = false;
    });

    if (response.isSuccess && response.data != null) {
      try {
        final journalData = response.data['data'];
        final attendances = (journalData['attendances'] as List? ?? []);

        final list = <Map<String, dynamic>>[];
        for (var att in attendances) {
          final student = att['student'] ?? {};
          final isAutoApproved = att['status'] == 'Izin' || att['status'] == 'Sakit';

          list.add({
            'student_id': att['student_id'],
            'student_name': student['name'] ?? 'Siswa',
            'nis': student['nis'] ?? '-',
            'status': att['status'] ?? 'Hadir',
            'is_locked': isAutoApproved, // jika disetujui izin oleh BK/Walikelas
          });
        }

        setState(() {
          _studentAttendances = list;
        });
      } catch (e) {
        setState(() {
          _errorMessage = 'Gagal memproses daftar siswa: ${e.toString()}';
        });
      }
    } else {
      setState(() {
        _errorMessage = response.message;
      });
    }
  }

  void _setAllPresent() {
    setState(() {
      for (var s in _studentAttendances) {
        if (!s['is_locked']) {
          s['status'] = 'Hadir';
        }
      }
    });
  }

  Future<void> _handleSaveAttendances() async {
    if (_studentAttendances.isEmpty) return;

    setState(() {
      _isSaving = true;
      _errorMessage = null;
    });

    final payload = {
      'journal_id': widget.journal.id,
      'attendances': _studentAttendances.map((s) => {
        'student_id': s['student_id'],
        'status': s['status'],
      }).toList(),
    };

    final response = await ApiClient.post(
      ApiEndpoints.teacherAttendances,
      body: payload,
    );

    setState(() {
      _isSaving = false;
    });

    if (response.isSuccess) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Presensi siswa berhasil disimpan!'),
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
    final schedule = widget.journal.schedule;

    return Scaffold(
      backgroundColor: AppTheme.bgDark,
      appBar: AppBar(
        title: Text('Presensi: ${schedule?.className ?? "Kelas"}'),
        actions: [
          IconButton(icon: const Icon(Icons.refresh_rounded), onPressed: _loadJournalAttendances),
        ],
      ),
      bottomNavigationBar: Container(
        padding: const EdgeInsets.all(16),
        decoration: const BoxDecoration(
          color: AppTheme.surfaceDark,
          border: Border(top: BorderSide(color: AppTheme.borderDark)),
        ),
        child: CustomButton(
          text: 'Simpan Rekap Presensi Siswa',
          icon: Icons.check_circle_rounded,
          isLoading: _isSaving,
          onPressed: _handleSaveAttendances,
        ),
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
              ElevatedButton(onPressed: _loadJournalAttendances, child: const Text('Coba Lagi')),
            ],
          ),
        ),
      );
    }

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        // Ringkasan Info KBM
        CustomCard(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    widget.journal.schedule?.subjectName ?? 'Mata Pelajaran',
                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Colors.white),
                  ),
                  Text(
                    widget.journal.date,
                    style: const TextStyle(fontSize: 12, color: AppTheme.primaryIndigo, fontWeight: FontWeight.bold),
                  ),
                ],
              ),
              const SizedBox(height: 6),
              Text(
                'Topik: ${widget.journal.topicDescription}',
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(fontSize: 12, color: AppTheme.textSecondary),
              ),
            ],
          ),
        ),
        const SizedBox(height: 14),

        // Quick Action Bar
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(
              'Daftar Siswa (${_studentAttendances.length})',
              style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold),
            ),
            OutlinedButton.icon(
              style: OutlinedButton.styleFrom(
                foregroundColor: AppTheme.success,
                side: const BorderSide(color: AppTheme.success),
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
              ),
              icon: const Icon(Icons.done_all_rounded, size: 16),
              label: const Text('Set Semua Hadir', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
              onPressed: _setAllPresent,
            ),
          ],
        ),
        const SizedBox(height: 12),

        // List Siswa dengan Toggle Hadir, Sakit, Izin, Alfa
        ..._studentAttendances.map((student) {
          final isLocked = student['is_locked'] == true;

          return Padding(
            padding: const EdgeInsets.only(bottom: 10),
            child: CustomCard(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              student['student_name'],
                              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Colors.white),
                            ),
                            Text(
                              'NIS: ${student['nis']}',
                              style: const TextStyle(fontSize: 11, color: AppTheme.textMuted),
                            ),
                          ],
                        ),
                      ),
                      if (isLocked)
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                          decoration: BoxDecoration(
                            color: AppTheme.warning.withValues(alpha: 0.15),
                            borderRadius: BorderRadius.circular(6),
                            border: Border.all(color: AppTheme.warning.withValues(alpha: 0.4)),
                          ),
                          child: const Text(
                            '✓ ACC Izin/Sakit',
                            style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: AppTheme.warning),
                          ),
                        ),
                    ],
                  ),
                  const SizedBox(height: 10),

                  // 4 Pilihan Status: Hadir, Sakit, Izin, Alfa
                  Row(
                    children: [
                      _statusChoice(student, 'Hadir', 'H', AppTheme.success, isLocked),
                      const SizedBox(width: 8),
                      _statusChoice(student, 'Sakit', 'S', AppTheme.warning, isLocked),
                      const SizedBox(width: 8),
                      _statusChoice(student, 'Izin', 'I', AppTheme.info, isLocked),
                      const SizedBox(width: 8),
                      _statusChoice(student, 'Alfa', 'A', AppTheme.danger, isLocked),
                    ],
                  ),
                ],
              ),
            ),
          );
        }),
      ],
    );
  }

  Widget _statusChoice(Map<String, dynamic> student, String statusVal, String shortLabel, Color color, bool isLocked) {
    final isSelected = student['status'] == statusVal;

    return Expanded(
      child: InkWell(
        onTap: () {
          setState(() {
            student['status'] = statusVal;
          });
        },
        borderRadius: BorderRadius.circular(8),
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 8),
          decoration: BoxDecoration(
            color: isSelected ? color : AppTheme.surfaceDark,
            borderRadius: BorderRadius.circular(8),
            border: Border.all(
              color: isSelected ? color : AppTheme.borderDark,
              width: isSelected ? 1.5 : 1,
            ),
          ),
          alignment: Alignment.center,
          child: Text(
            shortLabel,
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.bold,
              color: isSelected ? Colors.white : AppTheme.textSecondary,
            ),
          ),
        ),
      ),
    );
  }
}
