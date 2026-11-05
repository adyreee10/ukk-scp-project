import 'package:flutter/material.dart';
import '../../core/api/api_client.dart';
import '../../core/api/api_endpoints.dart';
import '../../core/theme/app_theme.dart';
import '../../core/utils/session_manager.dart';
import '../../models/leave_request_model.dart';
import '../../widgets/custom_card.dart';
import '../../widgets/status_badge.dart';

class BkLeaveVerificationScreen extends StatefulWidget {
  final String title;

  const BkLeaveVerificationScreen({
    super.key,
    this.title = 'Verifikasi Izin Siswa',
  });

  @override
  State<BkLeaveVerificationScreen> createState() => _BkLeaveVerificationScreenState();
}

class _BkLeaveVerificationScreenState extends State<BkLeaveVerificationScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  List<LeaveRequestModel> _pendingLeaves = [];
  List<LeaveRequestModel> _historyLeaves = [];
  String _baseUrl = '';
  bool _isLoading = true;
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _fetchLeaves();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _fetchLeaves() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    final url = await SessionManager.getBaseUrl();
    setState(() {
      _baseUrl = url.replaceAll('/api', '');
    });

    final response = await ApiClient.get(ApiEndpoints.bkLeaveRequests);

    setState(() {
      _isLoading = false;
    });

    if (response.isSuccess && response.data != null) {
      try {
        final list = (response.data['data']['data'] as List? ?? response.data['data'] as List? ?? [])
            .map((item) => LeaveRequestModel.fromJson(item))
            .toList();

        setState(() {
          _pendingLeaves = list.where((l) => l.isPending).toList();
          _historyLeaves = list.where((l) => !l.isPending).toList();
        });
      } catch (e) {
        setState(() => _errorMessage = 'Gagal memproses data izin: ${e.toString()}');
      }
    } else {
      setState(() => _errorMessage = response.message);
    }
  }

  // ACC IZIN
  Future<void> _handleApprove(LeaveRequestModel leave) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: AppTheme.cardDark,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: const Text('Setujui Permohonan Izin?', style: TextStyle(fontWeight: FontWeight.bold)),
        content: Text('Setujui izin ${leave.studentName ?? "Siswa"} (${leave.leaveType}) untuk tanggal ${leave.startDate} s/d ${leave.endDate}? Presensi akan otomatis disesuaikan.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Batal', style: TextStyle(color: AppTheme.textMuted)),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: AppTheme.success,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
            ),
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Setujui (ACC)', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
          ),
        ],
      ),
    );

    if (confirm != true) return;

    final response = await ApiClient.put(
      '${ApiEndpoints.bkLeaveRequests}/${leave.id}/approve',
    );

    if (response.isSuccess) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Izin siswa ${leave.studentName} berhasil disetujui!'),
          backgroundColor: AppTheme.success,
        ),
      );
      _fetchLeaves();
    } else {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(response.message),
          backgroundColor: AppTheme.danger,
        ),
      );
    }
  }

  // TOLAK IZIN
  Future<void> _handleReject(LeaveRequestModel leave) async {
    final noteController = TextEditingController();

    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: AppTheme.cardDark,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: const Text('Tolak Permohonan Izin', style: TextStyle(fontWeight: FontWeight.bold)),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Tolak permohonan izin ${leave.studentName ?? "Siswa"}? Masukkan alasan penolakan untuk siswa:'),
            const SizedBox(height: 12),
            TextField(
              controller: noteController,
              decoration: const InputDecoration(
                hintText: 'Alasan penolakan (misal: bukti tidak jelas/sudah terlewat)...',
              ),
              maxLines: 2,
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Batal', style: TextStyle(color: AppTheme.textMuted)),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: AppTheme.danger,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
            ),
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Tolak Izin', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
          ),
        ],
      ),
    );

    if (confirm != true) return;

    final response = await ApiClient.put(
      '${ApiEndpoints.bkLeaveRequests}/${leave.id}/reject',
      body: {
        'rejection_note': noteController.text.trim(),
      },
    );

    if (response.isSuccess) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Permohonan izin ${leave.studentName} telah ditolak.'),
          backgroundColor: AppTheme.warning,
        ),
      );
      _fetchLeaves();
    } else {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(response.message),
          backgroundColor: AppTheme.danger,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppTheme.bgDark,
      appBar: AppBar(
        title: Text(widget.title),
        actions: [
          IconButton(icon: const Icon(Icons.refresh_rounded), onPressed: _fetchLeaves),
        ],
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: AppTheme.primaryIndigo,
          indicatorWeight: 3,
          labelColor: Colors.white,
          unselectedLabelColor: AppTheme.textMuted,
          labelStyle: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
          tabs: [
            Tab(text: 'Menunggu (${_pendingLeaves.length})'),
            Tab(text: 'Riwayat (${_historyLeaves.length})'),
          ],
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
              ElevatedButton(onPressed: _fetchLeaves, child: const Text('Coba Lagi')),
            ],
          ),
        ),
      );
    }

    return TabBarView(
      controller: _tabController,
      children: [
        _buildLeaveList(_pendingLeaves, isPendingTab: true),
        _buildLeaveList(_historyLeaves, isPendingTab: false),
      ],
    );
  }

  Widget _buildLeaveList(List<LeaveRequestModel> list, {required bool isPendingTab}) {
    if (list.isEmpty) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(
                isPendingTab ? Icons.check_circle_outline_rounded : Icons.history_rounded,
                size: 48,
                color: AppTheme.textMuted,
              ),
              const SizedBox(height: 12),
              Text(
                isPendingTab ? 'Tidak ada antrean verifikasi izin pending.' : 'Belum ada riwayat verifikasi izin.',
                style: const TextStyle(color: AppTheme.textSecondary, fontSize: 13),
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
        padding: const EdgeInsets.all(16),
        itemCount: list.length,
        separatorBuilder: (_, __) => const SizedBox(height: 12),
        itemBuilder: (context, index) {
          final leave = list[index];

          String? docUrl;
          if (leave.documentPath != null && leave.documentPath!.isNotEmpty) {
            docUrl = '$_baseUrl/storage/${leave.documentPath}';
          }

          return CustomCard(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Header Siswa & Status
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            leave.studentName ?? 'Nama Siswa',
                            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: Colors.white),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            'Kelas: ${leave.className ?? "-"}',
                            style: const TextStyle(fontSize: 12, color: AppTheme.primaryIndigo, fontWeight: FontWeight.bold),
                          ),
                        ],
                      ),
                    ),
                    StatusBadge(status: leave.statusApproval),
                  ],
                ),
                const SizedBox(height: 12),

                // Jenis & Rentang Tanggal
                Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: AppTheme.surfaceDark,
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(color: AppTheme.borderDark),
                  ),
                  child: Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                        decoration: BoxDecoration(
                          color: AppTheme.warning.withValues(alpha: 0.15),
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: Text(
                          leave.leaveType,
                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 11, color: AppTheme.warning),
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Text(
                          '${leave.startDate} s/d ${leave.endDate}',
                          style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: Colors.white),
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 10),

                // Alasan
                Text(
                  'Alasan: ${leave.reason}',
                  style: const TextStyle(fontSize: 12, color: AppTheme.textSecondary, height: 1.4),
                ),
                const SizedBox(height: 12),

                // Bukti Foto / Dokumen Realtime
                if (docUrl != null) ...[
                  ClipRRect(
                    borderRadius: BorderRadius.circular(10),
                    child: Container(
                      height: 110,
                      width: double.infinity,
                      color: AppTheme.surfaceDark,
                      child: docUrl.toLowerCase().endsWith('.pdf')
                          ? Container(
                              padding: const EdgeInsets.all(12),
                              color: AppTheme.warning.withValues(alpha: 0.15),
                              child: const Row(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  Icon(Icons.picture_as_pdf_rounded, color: AppTheme.warning, size: 24),
                                  SizedBox(width: 8),
                                  Text('Dokumen PDF Surat Izin Terlampir', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: AppTheme.warning)),
                                ],
                              ),
                            )
                          : Image.network(
                              docUrl,
                              fit: BoxFit.cover,
                              errorBuilder: (_, __, ___) => const Center(
                                child: Text('Foto Bukti Realtime Tersedia di Server', style: TextStyle(fontSize: 11, color: AppTheme.textMuted)),
                              ),
                            ),
                    ),
                  ),
                  const SizedBox(height: 12),
                ],

                // Verifikator Info jika tab Riwayat
                if (!isPendingTab && leave.verifierName != null) ...[
                  Row(
                    children: [
                      const Icon(Icons.verified_user_rounded, size: 14, color: AppTheme.textMuted),
                      const SizedBox(width: 6),
                      Text(
                        'Diverifikasi oleh: ${leave.verifierName}',
                        style: const TextStyle(fontSize: 11, color: AppTheme.textSecondary),
                      ),
                    ],
                  ),
                ],

                // Action Buttons (Setujui & Tolak) jika tab Pending
                if (isPendingTab) ...[
                  const Divider(color: AppTheme.borderDark, height: 1),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(
                        child: OutlinedButton.icon(
                          style: OutlinedButton.styleFrom(
                            foregroundColor: AppTheme.danger,
                            side: const BorderSide(color: AppTheme.danger),
                            padding: const EdgeInsets.symmetric(vertical: 10),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                          ),
                          icon: const Icon(Icons.close_rounded, size: 16),
                          label: const Text('Tolak Izin', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                          onPressed: () => _handleReject(leave),
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: ElevatedButton.icon(
                          style: ElevatedButton.styleFrom(
                            backgroundColor: AppTheme.success,
                            padding: const EdgeInsets.symmetric(vertical: 10),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                          ),
                          icon: const Icon(Icons.check_rounded, size: 16, color: Colors.white),
                          label: const Text('Setujui (ACC)', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.white)),
                          onPressed: () => _handleApprove(leave),
                        ),
                      ),
                    ],
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
