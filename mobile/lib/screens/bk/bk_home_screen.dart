import 'package:flutter/material.dart';
import '../../core/api/api_client.dart';
import '../../core/api/api_endpoints.dart';
import '../../core/theme/app_theme.dart';
import '../../core/utils/date_formatter.dart';
import '../../core/utils/session_manager.dart';
import '../../models/attendance_model.dart';
import '../../models/leave_request_model.dart';
import '../../models/user_model.dart';
import '../../widgets/custom_card.dart';
import '../../widgets/status_badge.dart';

class BkHomeScreen extends StatefulWidget {
  final Function(int) onTabChange;

  const BkHomeScreen({super.key, required this.onTabChange});

  @override
  State<BkHomeScreen> createState() => _BkHomeScreenState();
}

class _BkHomeScreenState extends State<BkHomeScreen> {
  UserModel? _user;
  AttendanceSummary _summary = AttendanceSummary(hadir: 0, sakit: 0, izin: 0, alfa: 0);
  List<LeaveRequestModel> _pendingLeaves = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);

    final user = await SessionManager.getUser();
    setState(() => _user = user);

    try {
      final recapRes = await ApiClient.get(ApiEndpoints.bkAttendanceRecap);
      if (recapRes.isSuccess && recapRes.data != null) {
        final summaryJson = recapRes.data['summary'];
        setState(() => _summary = AttendanceSummary.fromJson(summaryJson));
      }

      final leaveRes = await ApiClient.get(ApiEndpoints.bkLeaveRequests);
      if (leaveRes.isSuccess && leaveRes.data != null) {
        final list = (leaveRes.data['data']['data'] as List? ?? leaveRes.data['data'] as List? ?? [])
            .map((item) => LeaveRequestModel.fromJson(item))
            .where((l) => l.isPending)
            .toList();
        setState(() => _pendingLeaves = list);
      }
    } catch (_) {}

    setState(() => _isLoading = false);
  }

  @override
  Widget build(BuildContext context) {
    final user = _user;
    final todayStr = DateFormatter.formatIndonesian(DateTime.now());

    return Scaffold(
      backgroundColor: AppTheme.bgDark,
      body: SafeArea(
        child: RefreshIndicator(
          color: AppTheme.primaryIndigo,
          backgroundColor: AppTheme.surfaceDark,
          onRefresh: _loadData,
          child: SingleChildScrollView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.all(20),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Header Profil BK
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            todayStr,
                            style: const TextStyle(fontSize: 12, color: AppTheme.textSecondary, fontWeight: FontWeight.w500),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            user?.name ?? 'Guru BK',
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Colors.white),
                          ),
                          const SizedBox(height: 2),
                          const Text(
                            'Bimbingan & Konseling (BK)',
                            style: TextStyle(fontSize: 12, color: AppTheme.primaryIndigo, fontWeight: FontWeight.bold),
                          ),
                        ],
                      ),
                    ),
                    Container(
                      width: 48,
                      height: 48,
                      decoration: BoxDecoration(
                        gradient: AppTheme.primaryGradient,
                        shape: BoxShape.circle,
                        boxShadow: [
                          BoxShadow(
                            color: AppTheme.primaryIndigo.withValues(alpha: 0.3),
                            blurRadius: 10,
                          ),
                        ],
                      ),
                      child: Center(
                        child: Text(
                          (user?.name.isNotEmpty ?? false) ? user!.name[0].toUpperCase() : 'B',
                          style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Colors.white),
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 20),

                // Card Statistik Antrean Izin
                Container(
                  padding: const EdgeInsets.all(18),
                  decoration: BoxDecoration(
                    gradient: AppTheme.primaryGradient,
                    borderRadius: BorderRadius.circular(20),
                    boxShadow: [
                      BoxShadow(
                        color: AppTheme.primaryIndigo.withValues(alpha: 0.35),
                        blurRadius: 16,
                        offset: const Offset(0, 6),
                      ),
                    ],
                  ),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text(
                            'Antrean Verifikasi Izin',
                            style: TextStyle(fontSize: 12, color: Colors.white70, fontWeight: FontWeight.w600),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            '${_pendingLeaves.length} Siswa Menunggu',
                            style: const TextStyle(fontSize: 22, fontWeight: FontWeight.w900, color: Colors.white),
                          ),
                          const SizedBox(height: 4),
                          const Text(
                            'Periksa bukti dokumen & foto realtime siswa',
                            style: TextStyle(fontSize: 11, color: Colors.white),
                          ),
                        ],
                      ),
                      ElevatedButton(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.white,
                          foregroundColor: AppTheme.primaryIndigo,
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                        ),
                        onPressed: () => widget.onTabChange(1), // Tab Verif Izin
                        child: const Text('Buka', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 24),

                // 4 Ringkasan Statistik Presensi Sekolah
                const Text('Pantauan Presensi Sekolah', style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Colors.white)),
                const SizedBox(height: 12),
                Row(
                  children: [
                    _statCard('Hadir', _summary.hadir, AppTheme.success),
                    const SizedBox(width: 8),
                    _statCard('Sakit', _summary.sakit, AppTheme.warning),
                    const SizedBox(width: 8),
                    _statCard('Izin', _summary.izin, AppTheme.info),
                    const SizedBox(width: 8),
                    _statCard('Alfa', _summary.alfa, AppTheme.danger),
                  ],
                ),
                const SizedBox(height: 28),

                // Daftar Siswa Menunggu Verifikasi
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text('Izin Menunggu Persetujuan', style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Colors.white)),
                    TextButton(
                      onPressed: () => widget.onTabChange(1),
                      child: const Text('Semua Izin →', style: TextStyle(fontSize: 12, color: AppTheme.primaryIndigo, fontWeight: FontWeight.bold)),
                    ),
                  ],
                ),
                const SizedBox(height: 8),

                if (_isLoading)
                  const Center(child: Padding(padding: EdgeInsets.all(24), child: CircularProgressIndicator(color: AppTheme.primaryIndigo)))
                else if (_pendingLeaves.isEmpty)
                  CustomCard(
                    padding: const EdgeInsets.all(20),
                    child: Center(
                      child: Column(
                        children: [
                          const Icon(Icons.check_circle_outline_rounded, color: AppTheme.success, size: 36),
                          const SizedBox(height: 8),
                          const Text('Semua izin siswa sudah tertangani.', style: TextStyle(fontSize: 13, color: AppTheme.textSecondary)),
                          const SizedBox(height: 2),
                          const Text('Tidak ada permohonan pending saat ini.', style: TextStyle(fontSize: 11, color: AppTheme.textMuted)),
                        ],
                      ),
                    ),
                  )
                else
                  ..._pendingLeaves.take(4).map((leave) {
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 10),
                      child: CustomCard(
                        padding: const EdgeInsets.all(14),
                        onTap: () => widget.onTabChange(1),
                        child: Row(
                          children: [
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    leave.studentName ?? 'Nama Siswa',
                                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Colors.white),
                                  ),
                                  const SizedBox(height: 3),
                                  Text(
                                    'Kelas: ${leave.className ?? "-"} • ${leave.leaveType}',
                                    style: const TextStyle(fontSize: 12, color: AppTheme.textSecondary),
                                  ),
                                  const SizedBox(height: 2),
                                  Text(
                                    '${leave.startDate} s/d ${leave.endDate}',
                                    style: const TextStyle(fontSize: 10, color: AppTheme.textMuted),
                                  ),
                                ],
                              ),
                            ),
                            const StatusBadge(status: 'Pending'),
                          ],
                        ),
                      ),
                    );
                  }),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _statCard(String label, int val, Color col) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 12),
        decoration: BoxDecoration(
          color: col.withValues(alpha: 0.12),
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: col.withValues(alpha: 0.35)),
        ),
        child: Column(
          children: [
            Text(
              val.toString(),
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.w900, color: col),
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
