import 'package:flutter/material.dart';
import '../../core/theme/app_theme.dart';
import '../profile/profile_screen.dart';
import 'bk_home_screen.dart';
import 'bk_leave_verification_screen.dart';
import 'bk_attendance_recap_screen.dart';

class BkMainNav extends StatefulWidget {
  const BkMainNav({super.key});

  @override
  State<BkMainNav> createState() => _BkMainNavState();
}

class _BkMainNavState extends State<BkMainNav> {
  int _currentIndex = 0;

  void _onTabChange(int index) {
    setState(() {
      _currentIndex = index;
    });
  }

  @override
  Widget build(BuildContext context) {
    final screens = [
      BkHomeScreen(onTabChange: _onTabChange),
      const BkLeaveVerificationScreen(title: 'Verifikasi Izin Siswa (BK)'),
      const BkAttendanceRecapScreen(),
      const ProfileScreen(),
    ];

    return Scaffold(
      body: IndexedStack(
        index: _currentIndex,
        children: screens,
      ),
      bottomNavigationBar: Container(
        decoration: const BoxDecoration(
          border: Border(top: BorderSide(color: AppTheme.borderDark, width: 1)),
        ),
        child: BottomNavigationBar(
          currentIndex: _currentIndex,
          onTap: _onTabChange,
          backgroundColor: AppTheme.surfaceDark,
          selectedItemColor: AppTheme.primaryIndigo,
          unselectedItemColor: AppTheme.textMuted,
          selectedLabelStyle: const TextStyle(fontWeight: FontWeight.bold, fontSize: 11),
          unselectedLabelStyle: const TextStyle(fontSize: 10),
          type: BottomNavigationBarType.fixed,
          items: const [
            BottomNavigationBarItem(
              icon: Icon(Icons.dashboard_outlined),
              activeIcon: Icon(Icons.dashboard_rounded),
              label: 'Beranda',
            ),
            BottomNavigationBarItem(
              icon: Icon(Icons.verified_outlined),
              activeIcon: Icon(Icons.verified_rounded),
              label: 'Verif Izin',
            ),
            BottomNavigationBarItem(
              icon: Icon(Icons.bar_chart_outlined),
              activeIcon: Icon(Icons.bar_chart_rounded),
              label: 'Rekap Absen',
            ),
            BottomNavigationBarItem(
              icon: Icon(Icons.person_outline),
              activeIcon: Icon(Icons.person_rounded),
              label: 'Profil',
            ),
          ],
        ),
      ),
    );
  }
}
