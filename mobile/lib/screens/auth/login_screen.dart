import 'package:flutter/material.dart';
import '../../core/api/api_client.dart';
import '../../core/api/api_endpoints.dart';
import '../../core/theme/app_theme.dart';
import '../../core/utils/session_manager.dart';
import '../../models/user_model.dart';
import '../../widgets/custom_button.dart';
import '../student/student_main_nav.dart';
import '../teacher/teacher_main_nav.dart';
import '../bk/bk_main_nav.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _usernameController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _obscurePassword = true;
  bool _isLoading = false;
  String? _errorMessage;

  @override
  void dispose() {
    _usernameController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _handleLogin() async {
    final username = _usernameController.text.trim();
    final password = _passwordController.text;

    if (username.isEmpty || password.isEmpty) {
      setState(() {
        _errorMessage = 'Harap isi username dan kata sandi.';
      });
      return;
    }

    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    final response = await ApiClient.post(
      ApiEndpoints.login,
      body: {
        'username': username,
        'password': password,
        'device_name': 'flutter-mobile-app',
      },
    );

    setState(() {
      _isLoading = false;
    });

    if (response.isSuccess && response.data != null) {
      try {
        final data = response.data['data'];
        final token = data['token'];
        final userJson = data['user'];

        final user = UserModel.fromJson(userJson);

        // Hanya izinkan role Siswa, Guru, Wali Kelas, Guru BK di versi Mobile
        if (!user.isStudent && !user.isTeacher && !user.isHomeroom && !user.isBk) {
          setState(() {
            _errorMessage = 'Akun ${user.role} disarankan menggunakan aplikasi Web Waka Kurikulum.';
          });
          return;
        }

        await SessionManager.setToken(token);
        await SessionManager.setUser(user);

        if (!mounted) return;

        if (user.isStudent) {
          Navigator.pushReplacement(
            context,
            MaterialPageRoute(builder: (_) => const StudentMainNav()),
          );
        } else if (user.isTeacher || user.isHomeroom) {
          Navigator.pushReplacement(
            context,
            MaterialPageRoute(builder: (_) => const TeacherMainNav()),
          );
        } else if (user.isBk) {
          Navigator.pushReplacement(
            context,
            MaterialPageRoute(builder: (_) => const BkMainNav()),
          );
        }
      } catch (e) {
        setState(() {
          _errorMessage = 'Gagal memproses data login: ${e.toString()}';
        });
      }
    } else {
      setState(() {
        _errorMessage = response.message;
      });
    }
  }

  void _showServerSettingsDialog() async {
    final currentUrl = await SessionManager.getBaseUrl();
    final controller = TextEditingController(text: currentUrl);

    if (!mounted) return;

    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: AppTheme.cardDark,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: const Row(
          children: [
            Icon(Icons.dns_rounded, color: AppTheme.primaryIndigo),
            SizedBox(width: 10),
            Text('Konfigurasi Server API', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Sesuaikan alamat backend Laravel agar sesuai dengan jaringan perangkat:',
              style: TextStyle(fontSize: 12, color: AppTheme.textSecondary),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: controller,
              decoration: const InputDecoration(
                hintText: 'http://10.0.2.2:8000/api',
                labelText: 'Base URL API',
              ),
              style: const TextStyle(fontSize: 13),
            ),
            const SizedBox(height: 14),
            Wrap(
              spacing: 8,
              runSpacing: 6,
              children: [
                _quickIpChip('Emulator (10.0.2.2)', 'http://10.0.2.2:8000/api', controller),
                _quickIpChip('Localhost (127.0.0.1)', 'http://127.0.0.1:8000/api', controller),
              ],
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Batal', style: TextStyle(color: AppTheme.textMuted)),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: AppTheme.primaryIndigo,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
            ),
            onPressed: () async {
              final scaffoldMessenger = ScaffoldMessenger.of(context);
              final navigator = Navigator.of(ctx);
              await SessionManager.setBaseUrl(controller.text);
              navigator.pop();
              scaffoldMessenger.showSnackBar(
                const SnackBar(
                  content: Text('Alamat server API berhasil diperbarui!'),
                  backgroundColor: AppTheme.success,
                ),
              );
            },
            child: const Text('Simpan', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
          ),
        ],
      ),
    );
  }

  Widget _quickIpChip(String label, String url, TextEditingController controller) {
    return ActionChip(
      backgroundColor: AppTheme.surfaceDark,
      label: Text(label, style: const TextStyle(fontSize: 11, color: AppTheme.textPrimary)),
      onPressed: () {
        controller.text = url;
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppTheme.bgDark,
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        actions: [
          IconButton(
            tooltip: 'Konfigurasi Alamat Server API',
            icon: const Icon(Icons.settings_ethernet_rounded, color: AppTheme.textSecondary),
            onPressed: _showServerSettingsDialog,
          ),
          const SizedBox(width: 8),
        ],
      ),
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 420),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  // Logo & Brand
                  Center(
                    child: Container(
                      width: 72,
                      height: 72,
                      decoration: BoxDecoration(
                        gradient: AppTheme.primaryGradient,
                        borderRadius: BorderRadius.circular(20),
                        boxShadow: [
                          BoxShadow(
                            color: AppTheme.primaryIndigo.withValues(alpha: 0.35),
                            blurRadius: 18,
                            offset: const Offset(0, 6),
                          ),
                        ],
                      ),
                      child: const Icon(Icons.school_rounded, size: 38, color: Colors.white),
                    ),
                  ),
                  const SizedBox(height: 20),

                  const Text(
                    'Selamat Datang di SCP',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      fontSize: 22,
                      fontWeight: FontWeight.w900,
                      color: Colors.white,
                    ),
                  ),
                  const SizedBox(height: 6),
                  const Text(
                    'Masuk untuk mengakses Jadwal, Jurnal Mengajar, dan Presensi Kehadiran',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      fontSize: 12,
                      color: AppTheme.textSecondary,
                    ),
                  ),
                  const SizedBox(height: 28),

                  // Alert Error jika ada
                  if (_errorMessage != null) ...[
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: AppTheme.danger.withValues(alpha: 0.15),
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: AppTheme.danger.withValues(alpha: 0.4)),
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.error_outline_rounded, color: AppTheme.danger, size: 20),
                          const SizedBox(width: 10),
                          Expanded(
                            child: Text(
                              _errorMessage!,
                              style: const TextStyle(color: Color(0xFFFB7185), fontSize: 12),
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 18),
                  ],

                  // Form Input Username
                  const Text('Username / NIS / NIP', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: AppTheme.textSecondary)),
                  const SizedBox(height: 6),
                  TextField(
                    controller: _usernameController,
                    decoration: const InputDecoration(
                      prefixIcon: Icon(Icons.person_outline_rounded, size: 20, color: AppTheme.textMuted),
                      hintText: 'Masukkan username',
                    ),
                  ),
                  const SizedBox(height: 16),

                  // Form Input Password
                  const Text('Kata Sandi', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: AppTheme.textSecondary)),
                  const SizedBox(height: 6),
                  TextField(
                    controller: _passwordController,
                    obscureText: _obscurePassword,
                    decoration: InputDecoration(
                      prefixIcon: const Icon(Icons.lock_outline_rounded, size: 20, color: AppTheme.textMuted),
                      hintText: 'Masukkan kata sandi',
                      suffixIcon: IconButton(
                        icon: Icon(
                          _obscurePassword ? Icons.visibility_outlined : Icons.visibility_off_outlined,
                          size: 20,
                          color: AppTheme.textMuted,
                        ),
                        onPressed: () {
                          setState(() {
                            _obscurePassword = !_obscurePassword;
                          });
                        },
                      ),
                    ),
                  ),
                  const SizedBox(height: 24),

                  // Tombol Masuk
                  CustomButton(
                    text: 'Masuk ke Aplikasi',
                    icon: Icons.login_rounded,
                    isLoading: _isLoading,
                    onPressed: _handleLogin,
                  ),
                  const SizedBox(height: 24),

                  // Info Tambahan UKK Demo
                  Container(
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      color: AppTheme.surfaceDark,
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(color: AppTheme.borderDark),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Row(
                          children: [
                            Icon(Icons.info_outline_rounded, size: 16, color: AppTheme.info),
                            SizedBox(width: 6),
                            Text('Akses Cepat Pengujian UKK:', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: AppTheme.textPrimary)),
                          ],
                        ),
                        const SizedBox(height: 8),
                        Wrap(
                          spacing: 8,
                          runSpacing: 6,
                          children: [
                            _demoUserChip('Siswa Demo', 'siswa_demo', 'password123'),
                            _demoUserChip('Guru Demo', 'guru_demo', 'password123'),
                            _demoUserChip('Guru BK', 'bk_demo', 'password123'),
                          ],
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _demoUserChip(String label, String username, String pass) {
    return InkWell(
      onTap: () {
        _usernameController.text = username;
        _passwordController.text = pass;
      },
      borderRadius: BorderRadius.circular(8),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
        decoration: BoxDecoration(
          color: AppTheme.cardDark,
          borderRadius: BorderRadius.circular(8),
          border: Border.all(color: AppTheme.borderDark),
        ),
        child: Text(
          label,
          style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: AppTheme.primaryIndigo),
        ),
      ),
    );
  }
}
