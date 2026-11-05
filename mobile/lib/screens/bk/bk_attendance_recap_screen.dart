import 'package:flutter/material.dart';
import '../../core/api/api_client.dart';
import '../../core/api/api_endpoints.dart';
import '../../core/theme/app_theme.dart';
import '../../models/attendance_model.dart';
import '../../widgets/custom_card.dart';
import '../../widgets/status_badge.dart';

class BkAttendanceRecapScreen extends StatefulWidget {
  const BkAttendanceRecapScreen({super.key});

  @override
  State<BkAttendanceRecapScreen> createState() => _BkAttendanceRecapScreenState();
}

class _BkAttendanceRecapScreenState extends State<BkAttendanceRecapScreen> {
  AttendanceSummary _summary = AttendanceSummary(hadir: 0, sakit: 0, izin: 0, alfa: 0);
  List<AttendanceModel> _records = [];
  bool _isLoading = true;
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    _fetchRecap();
  }

  Future<void> _fetchRecap() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    final response = await ApiClient.get(ApiEndpoints.bkAttendanceRecap);

    setState(() {
      _isLoading = false;
    });

    if (response.isSuccess && response.data != null) {
      try {
        final summaryJson = response.data['summary'];
        final dataJson = response.data['data']['data'] as List? ?? response.data['data'] as List? ?? [];

        setState(() {
          _summary = AttendanceSummary.fromJson(summaryJson);
          _records = dataJson.map((item) => AttendanceModel.fromJson(item)).toList();
        });
      } catch (e) {
        setState(() => _errorMessage = 'Gagal memproses rekap presensi: ${e.toString()}');
      }
    } else {
      setState(() => _errorMessage = response.message);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppTheme.bgDark,
      appBar: AppBar(
        title: const Text('Rekap Presensi Sekolah'),
        actions: [
          IconButton(icon: const Icon(Icons.refresh_rounded), onPressed: _fetchRecap),
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
              ElevatedButton(onPressed: _fetchRecap, child: const Text('Coba Lagi')),
            ],
          ),
        ),
      );
    }

    return RefreshIndicator(
      color: AppTheme.primaryIndigo,
      backgroundColor: AppTheme.surfaceDark,
      onRefresh: _fetchRecap,
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
          const SizedBox(height: 20),

          const Text('Catatan Presensi Siswa Terkini', style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold)),
          const SizedBox(height: 12),

          if (_records.isEmpty)
            Container(
              padding: const EdgeInsets.all(32),
              alignment: Alignment.center,
              child: const Text('Belum ada data presensi yang masuk.', style: TextStyle(color: AppTheme.textMuted, fontSize: 13)),
            )
          else
            ..._records.map((att) {
              final student = att.student;
              final journal = att.journal;

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
                              student?.name ?? 'Nama Siswa',
                              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Colors.white),
                            ),
                            const SizedBox(height: 3),
                            Text(
                              'Kelas: ${student?.className ?? "-"} • Mapel: ${journal?.schedule?.subjectName ?? "-"}',
                              style: const TextStyle(fontSize: 11, color: AppTheme.textSecondary),
                            ),
                            const SizedBox(height: 2),
                            Text(
                              'Tanggal: ${att.date}',
                              style: const TextStyle(fontSize: 10, color: AppTheme.textMuted),
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
