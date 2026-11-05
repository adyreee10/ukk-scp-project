import 'package:flutter/material.dart';
import '../../core/api/api_client.dart';
import '../../core/api/api_endpoints.dart';
import '../../core/theme/app_theme.dart';
import '../../core/utils/session_manager.dart';
import '../../models/user_model.dart';
import '../../widgets/custom_card.dart';
import '../../widgets/status_badge.dart';
import '../auth/login_screen.dart';

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  UserModel? _user;
  String _baseUrl = '';
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadProfile();
  }

  Future<void> _loadProfile() async {
    final user = await SessionManager.getUser();
    final url = await SessionManager.getBaseUrl();
    setState(() {
      _user = user;
      _baseUrl = url;
      _isLoading = false;
    });
  }

  Future<void> _handleLogout() async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: AppTheme.cardDark,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: const Text('Konfirmasi Keluar', style: TextStyle(fontWeight: FontWeight.bold)),
        content: const Text('Apakah Anda yakin ingin keluar dari aplikasi SCP Mobile?'),
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
            child: const Text('Keluar', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
          ),
        ],
      ),
    );

    if (confirm != true) return;

    // Call API logout
    try {
      await ApiClient.post(ApiEndpoints.logout);
    } catch (_) {}

    await SessionManager.clearSession();

    if (!mounted) return;

    Navigator.pushAndRemoveUntil(
      context,
      MaterialPageRoute(builder: (_) => const LoginScreen()),
      (route) => false,
    );
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return const Scaffold(
        backgroundColor: AppTheme.bgDark,
        body: Center(child: CircularProgressIndicator(color: AppTheme.primaryIndigo)),
      );
    }

    final user = _user;

    return Scaffold(
      backgroundColor: AppTheme.bgDark,
      appBar: AppBar(
        title: const Text('Profil Akun'),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Column(
          children: [
            // Avatar & Name Card
            Center(
              child: Column(
                children: [
                  Container(
                    width: 80,
                    height: 80,
                    decoration: BoxDecoration(
                      gradient: AppTheme.primaryGradient,
                      shape: BoxShape.circle,
                      boxShadow: [
                        BoxShadow(
                          color: AppTheme.primaryIndigo.withValues(alpha: 0.35),
                          blurRadius: 18,
                          offset: const Offset(0, 6),
                        ),
                      ],
                    ),
                    child: Center(
                      child: Text(
                        (user?.name.isNotEmpty ?? false) ? user!.name[0].toUpperCase() : 'U',
                        style: const TextStyle(fontSize: 32, fontWeight: FontWeight.bold, color: Colors.white),
                      ),
                    ),
                  ),
                  const SizedBox(height: 14),
                  Text(
                    user?.name ?? 'Pengguna',
                    textAlign: TextAlign.center,
                    style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.white),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    '@${user?.username ?? '-'} • ${user?.email ?? '-'}',
                    style: const TextStyle(fontSize: 12, color: AppTheme.textSecondary),
                  ),
                  const SizedBox(height: 10),
                  StatusBadge(status: user?.role ?? 'Role'),
                ],
              ),
            ),
            const SizedBox(height: 24),

            // Detail Akun
            CustomCard(
              padding: const EdgeInsets.all(18),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text('Informasi Akun', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                  const SizedBox(height: 14),
                  _infoTile(Icons.badge_rounded, 'Nomor Induk (NIS/NIP)', user?.nis ?? '-'),
                  const Divider(color: AppTheme.borderDark, height: 20),
                  if (user?.isStudent ?? false) ...[
                    _infoTile(Icons.meeting_room_rounded, 'Kelas Terdaftar', user?.className ?? '-'),
                    const Divider(color: AppTheme.borderDark, height: 20),
                    _infoTile(Icons.verified_user_rounded, 'Status Siswa', user?.studentStatus ?? 'Aktif'),
                    const Divider(color: AppTheme.borderDark, height: 20),
                  ],
                  if (user?.isHomeroom ?? false) ...[
                    _infoTile(Icons.supervisor_account_rounded, 'Wali Kelas Dari', user?.homeroomClassName ?? '-'),
                    const Divider(color: AppTheme.borderDark, height: 20),
                  ],
                  _infoTile(Icons.phone_rounded, 'Nomor Kontak', user?.phone ?? '-'),
                  const Divider(color: AppTheme.borderDark, height: 20),
                  _infoTile(Icons.location_on_rounded, 'Alamat', user?.address ?? '-'),
                ],
              ),
            ),
            const SizedBox(height: 16),

            // Server & Sistem Info
            CustomCard(
              padding: const EdgeInsets.all(18),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text('Koneksi & Sistem', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                  const SizedBox(height: 12),
                  _infoTile(Icons.lan_rounded, 'API Endpoint', _baseUrl),
                  const Divider(color: AppTheme.borderDark, height: 20),
                  _infoTile(Icons.info_rounded, 'Versi Aplikasi', 'SCP Mobile v1.0.0 (UKK Release)'),
                ],
              ),
            ),
            const SizedBox(height: 24),

            // Tombol Logout
            SizedBox(
              width: double.infinity,
              child: OutlinedButton.icon(
                style: OutlinedButton.styleFrom(
                  foregroundColor: AppTheme.danger,
                  side: const BorderSide(color: AppTheme.danger, width: 1.5),
                  padding: const EdgeInsets.symmetric(vertical: 14),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                ),
                icon: const Icon(Icons.logout_rounded, size: 20),
                label: const Text('Keluar dari Akun', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                onPressed: _handleLogout,
              ),
            ),
            const SizedBox(height: 20),
          ],
        ),
      ),
    );
  }

  Widget _infoTile(IconData icon, String label, String value) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, size: 18, color: AppTheme.primaryIndigo),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(label, style: const TextStyle(fontSize: 11, color: AppTheme.textMuted)),
              const SizedBox(height: 2),
              Text(value, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: AppTheme.textPrimary)),
            ],
          ),
        ),
      ],
    );
  }
}
