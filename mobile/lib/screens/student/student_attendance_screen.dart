import 'package:flutter/material.dart';
import '../../core/api/api_client.dart';
import '../../core/api/api_endpoints.dart';
import '../../core/theme/app_theme.dart';
import '../../models/attendance_model.dart';
import '../../widgets/custom_card.dart';
import '../../widgets/status_badge.dart';

class StudentAttendanceScreen extends StatefulWidget {
  const StudentAttendanceScreen({super.key});

  @override
  State<StudentAttendanceScreen> createState() => _StudentAttendanceScreenState();
}

class _StudentAttendanceScreenState extends State<StudentAttendanceScreen> {
  AttendanceSummary _summary = AttendanceSummary(hadir: 0, sakit: 0, izin: 0, alfa: 0);
  List<AttendanceModel> _attendances = [];
  bool _isLoading = true;
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    _fetchAttendance();
  }

  Future<void> _fetchAttendance() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    final response = await ApiClient.get(ApiEndpoints.studentAttendances);

    setState(() {
      _isLoading = false;
    });

    if (response.isSuccess && response.data != null) {
      try {
        final summaryJson = response.data['summary'];
        final dataJson = response.data['data']['data'] as List? ?? response.data['data'] as List? ?? [];

        setState(() {
          _summary = AttendanceSummary.fromJson(summaryJson);
          _attendances = dataJson.map((item) => AttendanceModel.fromJson(item)).toList();
        });
      } catch (e) {
        setState(() {
          _errorMessage = 'Gagal memproses data absensi: ${e.toString()}';
        });
      }
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
        title: const Text('Riwayat Kehadiran'),
        actions: [
          IconButton(icon: const Icon(Icons.refresh_rounded), onPressed: _fetchAttendance),
        ],
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
              ElevatedButton(onPressed: _fetchAttendance, child: const Text('Coba Lagi')),
            ],
          ),
        ),
      );
    }

    return RefreshIndicator(
      color: AppTheme.primaryIndigo,
      backgroundColor: AppTheme.surfaceDark,
      onRefresh: _fetchAttendance,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          // 4 Kartu Statistik Kehadiran
          Row(
            children: [
              _statBox('Hadir', _summary.hadir, AppTheme.success),
              const SizedBox(width: 8),
              _statBox('Sakit', _summary.sakit, AppTheme.warning),
              const SizedBox(width: 8),
              _statBox('Izin', _summary.izin, AppTheme.info),
              const SizedBox(width: 8),
              _statBox('Alfa', _summary.alfa, AppTheme.danger),
            ],
          ),
          const SizedBox(height: 16),

          // Persentase Kehadiran
          CustomCard(
            padding: const EdgeInsets.all(16),
            child: Row(
              children: [
                Stack(
                  alignment: Alignment.center,
                  children: [
                    SizedBox(
                      width: 54,
                      height: 54,
                      child: CircularProgressIndicator(
                        value: _summary.percentage / 100,
                        strokeWidth: 6,
                        backgroundColor: AppTheme.borderDark,
                        valueColor: const AlwaysStoppedAnimation<Color>(AppTheme.success),
                      ),
                    ),
                    Text(
                      '${_summary.percentage.toStringAsFixed(0)}%',
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Colors.white),
                    ),
                  ],
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Tingkat Kehadiran Kumulatif',
                        style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Colors.white),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        'Total ${_summary.total} sesi pelajaran tercatat pada semester aktif ini.',
                        style: const TextStyle(fontSize: 11, color: AppTheme.textSecondary),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 20),

          // Judul Riwayat
          const Text('Catatan Absensi Sesi Pelajaran', style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold)),
          const SizedBox(height: 12),

          if (_attendances.isEmpty)
            Container(
              padding: const EdgeInsets.all(32),
              alignment: Alignment.center,
              child: const Text('Belum ada sesi presensi yang terekam.', style: TextStyle(color: AppTheme.textMuted, fontSize: 13)),
            )
          else
            ..._attendances.map((att) {
              final subject = att.journal?.schedule?.subjectName ?? 'Sesi Pelajaran';
              final teacher = att.journal?.schedule?.teacherName ?? 'Guru Pengampu';

              return Padding(
                padding: const EdgeInsets.only(bottom: 10),
                child: CustomCard(
                  padding: const EdgeInsets.all(14),
                  child: Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              subject,
                              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Colors.white),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              'Guru: $teacher • ${att.date}',
                              style: const TextStyle(fontSize: 11, color: AppTheme.textSecondary),
                            ),
                          ],
                        ),
                      ),
                      StatusBadge(status: att.status),
                    ],
                  ),
                ),
              );
            }),
        ],
      ),
    );
  }

  Widget _statBox(String label, int count, Color color) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 12),
        decoration: BoxDecoration(
          color: color.withValues(alpha: 0.12),
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: color.withValues(alpha: 0.35)),
        ),
        child: Column(
          children: [
            Text(
              count.toString(),
              style: TextStyle(fontSize: 20, fontWeight: FontWeight.w900, color: color),
            ),
            const SizedBox(height: 2),
            Text(
              label,
              style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: AppTheme.textPrimary),
            ),
          ],
        ),
      ),
    );
  }
}
