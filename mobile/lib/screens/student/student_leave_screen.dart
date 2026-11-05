import 'package:flutter/material.dart';
import '../../core/api/api_client.dart';
import '../../core/api/api_endpoints.dart';
import '../../core/theme/app_theme.dart';
import '../../models/leave_request_model.dart';
import '../../widgets/custom_card.dart';
import '../../widgets/status_badge.dart';
import 'student_leave_create_screen.dart';

class StudentLeaveScreen extends StatefulWidget {
  const StudentLeaveScreen({super.key});

  @override
  State<StudentLeaveScreen> createState() => _StudentLeaveScreenState();
}

class _StudentLeaveScreenState extends State<StudentLeaveScreen> {
  List<LeaveRequestModel> _leaves = [];
  bool _isLoading = true;
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    _fetchLeaves();
  }

  Future<void> _fetchLeaves() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    final response = await ApiClient.get(ApiEndpoints.studentLeaveRequests);

    setState(() {
      _isLoading = false;
    });

    if (response.isSuccess && response.data != null) {
      try {
        final list = (response.data['data']['data'] as List? ?? response.data['data'] as List? ?? [])
            .map((item) => LeaveRequestModel.fromJson(item))
            .toList();
        setState(() {
          _leaves = list;
        });
      } catch (e) {
        setState(() {
          _errorMessage = 'Gagal memproses data izin: ${e.toString()}';
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
        title: const Text('Pengajuan Izin'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded),
            onPressed: _fetchLeaves,
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        backgroundColor: AppTheme.primaryIndigo,
        icon: const Icon(Icons.add_rounded, color: Colors.white),
        label: const Text('Ajukan Izin', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.white)),
        onPressed: () async {
          final refreshed = await Navigator.push<bool>(
            context,
            MaterialPageRoute(builder: (_) => const StudentLeaveCreateScreen()),
          );
          if (refreshed == true) {
            _fetchLeaves();
          }
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
              ElevatedButton(onPressed: _fetchLeaves, child: const Text('Coba Lagi')),
            ],
          ),
        ),
      );
    }

    if (_leaves.isEmpty) {
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
                child: const Icon(Icons.mark_email_read_outlined, size: 48, color: AppTheme.textMuted),
              ),
              const SizedBox(height: 16),
              const Text('Belum Ada Riwayat Izin', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
              const SizedBox(height: 6),
              const Text(
                'Tekan tombol "Ajukan Izin" di bawah untuk membuat permohonan baru disertai foto realtime kamera.',
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
      onRefresh: _fetchLeaves,
      child: ListView.separated(
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 80),
        itemCount: _leaves.length,
        separatorBuilder: (_, __) => const SizedBox(height: 12),
        itemBuilder: (context, index) {
          final leave = _leaves[index];
          return CustomCard(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                      decoration: BoxDecoration(
                        color: AppTheme.primaryIndigo.withValues(alpha: 0.15),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Text(
                        leave.leaveType,
                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: AppTheme.primaryIndigo),
                      ),
                    ),
                    StatusBadge(status: leave.statusApproval),
                  ],
                ),
                const SizedBox(height: 12),

                // Rentang Tanggal
                Row(
                  children: [
                    const Icon(Icons.date_range_rounded, size: 16, color: AppTheme.textMuted),
                    const SizedBox(width: 8),
                    Text(
                      '${leave.startDate} s/d ${leave.endDate}',
                      style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: AppTheme.textPrimary),
                    ),
                  ],
                ),
                const SizedBox(height: 8),

                // Alasan
                Text(
                  leave.reason,
                  style: const TextStyle(fontSize: 12, color: AppTheme.textSecondary, height: 1.4),
                ),
                const SizedBox(height: 12),

                // Verifikator / Catatan
                if (leave.verifierName != null) ...[
                  const Divider(color: AppTheme.borderDark, height: 16),
                  Row(
                    children: [
                      const Icon(Icons.verified_rounded, size: 14, color: AppTheme.success),
                      const SizedBox(width: 6),
                      Text(
                        'Diverifikasi oleh: ${leave.verifierName}',
                        style: const TextStyle(fontSize: 11, color: AppTheme.success, fontWeight: FontWeight.w500),
                      ),
                    ],
                  ),
                ],

                if (leave.isRejected && leave.rejectionNote != null) ...[
                  const SizedBox(height: 6),
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: AppTheme.danger.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      'Alasan Ditolak: ${leave.rejectionNote}',
                      style: const TextStyle(fontSize: 11, color: AppTheme.danger),
                    ),
                  ),
                ],
              ],
            ),
          );
        },
      ),
    );
  }
}
