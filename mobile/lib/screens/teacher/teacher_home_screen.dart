import 'package:flutter/material.dart';
import '../../core/api/api_client.dart';
import '../../core/api/api_endpoints.dart';
import '../../core/theme/app_theme.dart';
import '../../core/utils/date_formatter.dart';
import '../../core/utils/session_manager.dart';
import '../../models/schedule_model.dart';
import '../../models/user_model.dart';
import '../../widgets/custom_card.dart';
import 'teacher_journal_create_screen.dart';

class TeacherHomeScreen extends StatefulWidget {
  final Function(int) onTabChange;

  const TeacherHomeScreen({super.key, required this.onTabChange});

  @override
  State<TeacherHomeScreen> createState() => _TeacherHomeScreenState();
}

class _TeacherHomeScreenState extends State<TeacherHomeScreen> {
  UserModel? _user;
  List<ScheduleModel> _allSchedules = [];
  List<ScheduleModel> _todaySchedules = [];
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
      final now = DateTime.now();
      final dayNames = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
      final todayName = dayNames[now.weekday - 1];

      final scheduleRes = await ApiClient.get(ApiEndpoints.teacherSchedules);
      if (scheduleRes.isSuccess && scheduleRes.data != null) {
        final list = (scheduleRes.data['data'] as List? ?? [])
            .map((item) => ScheduleModel.fromJson(item))
            .toList();

        final todayList = list
            .where((s) => s.dayOfWeek.toLowerCase() == todayName.toLowerCase())
            .toList();

        setState(() {
          _allSchedules = list;
          _todaySchedules = todayList;
        });
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
                // Header Profil Guru
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
                            user?.name ?? 'Guru Pengajar',
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Colors.white),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            user?.isHomeroom == true
                                ? 'Wali Kelas ${user?.homeroomClassName ?? "-"}'
                                : 'Guru Mata Pelajaran',
                            style: const TextStyle(fontSize: 12, color: AppTheme.primaryIndigo, fontWeight: FontWeight.bold),
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
                          (user?.name.isNotEmpty ?? false) ? user!.name[0].toUpperCase() : 'G',
                          style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Colors.white),
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 20),

                // Banner Menu Cepat
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
                            'Agenda KBM Hari Ini',
                            style: TextStyle(fontSize: 12, color: Colors.white70, fontWeight: FontWeight.w600),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            '${_todaySchedules.length} Sesi Mengajar',
                            style: const TextStyle(fontSize: 24, fontWeight: FontWeight.w900, color: Colors.white),
                          ),
                          const SizedBox(height: 4),
                          const Text(
                            'Ambil foto realtime KBM & rekap presensi kelas',
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
                        onPressed: () async {
                          final res = await Navigator.push<bool>(
                            context,
                            MaterialPageRoute(
                              builder: (_) => TeacherJournalCreateScreen(schedules: _allSchedules),
                            ),
                          );
                          if (res == true) _loadData();
                        },
                        child: const Text('Isi Jurnal', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 24),

                // Menu Navigasi Pintasan
                const Text('Menu Utama', style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Colors.white)),
                const SizedBox(height: 12),
                Row(
                  children: [
                    _quickButton(
                      icon: Icons.camera_alt_rounded,
                      label: 'Jurnal KBM\n(Foto Realtime)',
                      color: AppTheme.primaryIndigo,
                      onTap: () => widget.onTabChange(2), // Tab Jurnal
                    ),
                    const SizedBox(width: 12),
                    _quickButton(
                      icon: Icons.calendar_month_rounded,
                      label: 'Jadwal\nMengajar',
                      color: AppTheme.info,
                      onTap: () => widget.onTabChange(1), // Tab Jadwal
                    ),
                    if (user?.isHomeroom == true) ...[
                      const SizedBox(width: 12),
                      _quickButton(
                        icon: Icons.verified_user_rounded,
                        label: 'Verifikasi\nIzin Siswa',
                        color: AppTheme.warning,
                        onTap: () => widget.onTabChange(3), // Tab Verifikasi Izin
                      ),
                    ],
                  ],
                ),
                const SizedBox(height: 28),

                // Jadwal Mengajar Hari Ini
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text('Jadwal Hari Ini', style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Colors.white)),
                    TextButton(
                      onPressed: () => widget.onTabChange(1),
                      child: const Text('Lihat Jadwal Lengkap →', style: TextStyle(fontSize: 12, color: AppTheme.primaryIndigo, fontWeight: FontWeight.bold)),
                    ),
                  ],
                ),
                const SizedBox(height: 8),

                if (_isLoading)
                  const Center(child: Padding(padding: EdgeInsets.all(24), child: CircularProgressIndicator(color: AppTheme.primaryIndigo)))
                else if (_todaySchedules.isEmpty)
                  CustomCard(
                    padding: const EdgeInsets.all(20),
                    child: Center(
                      child: Column(
                        children: [
                          const Icon(Icons.beach_access_rounded, color: AppTheme.textMuted, size: 36),
                          const SizedBox(height: 8),
                          const Text('Tidak ada jadwal mengajar hari ini.', style: TextStyle(fontSize: 13, color: AppTheme.textSecondary)),
                          const SizedBox(height: 2),
                          const Text('Semua jadwal KBM tertera di tab Jadwal Mengajar.', style: TextStyle(fontSize: 11, color: AppTheme.textMuted)),
                        ],
                      ),
                    ),
                  )
                else
                  ..._todaySchedules.map((schedule) {
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 10),
                      child: CustomCard(
                        padding: const EdgeInsets.all(14),
                        child: Row(
                          children: [
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                              decoration: BoxDecoration(
                                color: AppTheme.surfaceDark,
                                borderRadius: BorderRadius.circular(10),
                                border: Border.all(color: AppTheme.borderDark),
                              ),
                              child: Text(
                                DateFormatter.formatTime(schedule.startTime),
                                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: AppTheme.primaryIndigo),
                              ),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    schedule.subjectName,
                                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Colors.white),
                                  ),
                                  const SizedBox(height: 3),
                                  Text(
                                    'Kelas: ${schedule.className ?? "-"} • ${schedule.roomName ?? "Ruang Kelas"}',
                                    style: const TextStyle(fontSize: 11, color: AppTheme.textSecondary),
                                  ),
                                ],
                              ),
                            ),
                            ElevatedButton(
                              style: ElevatedButton.styleFrom(
                                backgroundColor: AppTheme.primaryIndigo,
                                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                              ),
                              onPressed: () {
                                Navigator.push(
                                  context,
                                  MaterialPageRoute(
                                    builder: (_) => TeacherJournalCreateScreen(
                                      schedules: _allSchedules,
                                      initialSchedule: schedule,
                                    ),
                                  ),
                                );
                              },
                              child: const Text('Isi', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Colors.white)),
                            ),
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

  Widget _quickButton({
    required IconData icon,
    required String label,
    required Color color,
    required VoidCallback onTap,
  }) {
    return Expanded(
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 8),
          decoration: BoxDecoration(
            color: AppTheme.cardDark,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: AppTheme.borderDark),
          ),
          child: Column(
            children: [
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: color.withValues(alpha: 0.15),
                  shape: BoxShape.circle,
                ),
                child: Icon(icon, color: color, size: 22),
              ),
              const SizedBox(height: 8),
              Text(
                label,
                textAlign: TextAlign.center,
                style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Colors.white, height: 1.2),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
