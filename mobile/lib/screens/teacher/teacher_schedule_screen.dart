import 'package:flutter/material.dart';
import '../../core/api/api_client.dart';
import '../../core/api/api_endpoints.dart';
import '../../core/theme/app_theme.dart';
import '../../core/utils/date_formatter.dart';
import '../../models/schedule_model.dart';
import '../../widgets/custom_card.dart';
import 'teacher_journal_create_screen.dart';

class TeacherScheduleScreen extends StatefulWidget {
  const TeacherScheduleScreen({super.key});

  @override
  State<TeacherScheduleScreen> createState() => _TeacherScheduleScreenState();
}

class _TeacherScheduleScreenState extends State<TeacherScheduleScreen> with SingleTickerProviderStateMixin {
  final List<String> _days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
  late TabController _tabController;
  List<ScheduleModel> _allSchedules = [];
  Map<String, List<ScheduleModel>> _groupedSchedules = {};
  bool _isLoading = true;
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: _days.length, vsync: this);
    _fetchSchedules();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _fetchSchedules() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    final response = await ApiClient.get(ApiEndpoints.teacherSchedules);

    setState(() {
      _isLoading = false;
    });

    if (response.isSuccess && response.data != null) {
      try {
        final list = (response.data['data'] as List? ?? [])
            .map((item) => ScheduleModel.fromJson(item))
            .toList();

        final map = <String, List<ScheduleModel>>{};
        for (var day in _days) {
          map[day] = [];
        }
        for (var s in list) {
          if (map.containsKey(s.dayOfWeek)) {
            map[s.dayOfWeek]!.add(s);
          } else {
            map[s.dayOfWeek] = [s];
          }
        }

        setState(() {
          _allSchedules = list;
          _groupedSchedules = map;
        });
      } catch (e) {
        setState(() {
          _errorMessage = 'Gagal memproses jadwal: ${e.toString()}';
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
        title: const Text('Jadwal Mengajar Guru'),
        actions: [
          IconButton(icon: const Icon(Icons.refresh_rounded), onPressed: _fetchSchedules),
        ],
        bottom: TabBar(
          controller: _tabController,
          isScrollable: true,
          indicatorColor: AppTheme.primaryIndigo,
          indicatorWeight: 3,
          labelColor: Colors.white,
          unselectedLabelColor: AppTheme.textMuted,
          labelStyle: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
          tabs: _days.map((day) => Tab(text: day)).toList(),
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
              ElevatedButton(onPressed: _fetchSchedules, child: const Text('Coba Lagi')),
            ],
          ),
        ),
      );
    }

    return TabBarView(
      controller: _tabController,
      children: _days.map((day) {
        final schedules = _groupedSchedules[day] ?? [];
        if (schedules.isEmpty) {
          return Center(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Icon(Icons.event_busy_rounded, size: 48, color: AppTheme.textMuted),
                const SizedBox(height: 12),
                Text('Tidak ada jam mengajar di hari $day', style: const TextStyle(color: AppTheme.textSecondary, fontSize: 13)),
              ],
            ),
          );
        }

        return ListView.separated(
          padding: const EdgeInsets.all(16),
          itemCount: schedules.length,
          separatorBuilder: (_, __) => const SizedBox(height: 12),
          itemBuilder: (context, index) {
            final schedule = schedules[index];
            return CustomCard(
              padding: const EdgeInsets.all(16),
              child: Column(
                children: [
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                        decoration: BoxDecoration(
                          color: AppTheme.surfaceDark,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: AppTheme.borderDark),
                        ),
                        child: Column(
                          children: [
                            Text(
                              DateFormatter.formatTime(schedule.startTime),
                              style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: AppTheme.primaryIndigo),
                            ),
                            const Text('s/d', style: TextStyle(fontSize: 9, color: AppTheme.textMuted)),
                            Text(
                              DateFormatter.formatTime(schedule.endTime),
                              style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: AppTheme.textSecondary),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(width: 14),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              schedule.subjectName,
                              style: const TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Colors.white),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              'Kelas: ${schedule.className ?? "-"} • ${schedule.roomName ?? "Ruang"}',
                              style: const TextStyle(fontSize: 12, color: AppTheme.textSecondary),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  const Divider(color: AppTheme.borderDark, height: 1),
                  const SizedBox(height: 10),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.end,
                    children: [
                      ElevatedButton.icon(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: AppTheme.primaryIndigo,
                          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                        ),
                        icon: const Icon(Icons.edit_note_rounded, size: 16, color: Colors.white),
                        label: const Text('Isi Jurnal KBM', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.white)),
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
                      ),
                    ],
                  ),
                ],
              ),
            );
          },
        );
      }).toList(),
    );
  }
}
