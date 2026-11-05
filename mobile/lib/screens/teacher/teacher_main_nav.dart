import 'package:flutter/material.dart';
import '../../core/theme/app_theme.dart';
import '../../core/utils/session_manager.dart';
import '../../models/user_model.dart';
import '../bk/bk_leave_verification_screen.dart';
import '../profile/profile_screen.dart';
import 'teacher_home_screen.dart';
import 'teacher_schedule_screen.dart';
import 'teacher_journal_screen.dart';

class TeacherMainNav extends StatefulWidget {
  const TeacherMainNav({super.key});

  @override
  State<TeacherMainNav> createState() => _TeacherMainNavState();
}

class _TeacherMainNavState extends State<TeacherMainNav> {
  int _currentIndex = 0;
  UserModel? _user;
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadUser();
  }

  Future<void> _loadUser() async {
    final user = await SessionManager.getUser();
    setState(() {
      _user = user;
      _isLoading = false;
    });
  }

  void _onTabChange(int index) {
    setState(() {
      _currentIndex = index;
    });
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return const Scaffold(
        backgroundColor: AppTheme.bgDark,
        body: Center(child: CircularProgressIndicator(color: AppTheme.primaryIndigo)),
      );
    }

    final isHomeroom = _user?.isHomeroom == true;

    final screens = <Widget>[
      TeacherHomeScreen(onTabChange: _onTabChange),
      const TeacherScheduleScreen(),
      const TeacherJournalScreen(),
      if (isHomeroom)
        const BkLeaveVerificationScreen(title: 'Verifikasi Izin Perwalian'),
      const ProfileScreen(),
    ];

    final navItems = <BottomNavigationBarItem>[
      const BottomNavigationBarItem(
        icon: Icon(Icons.home_outlined),
        activeIcon: Icon(Icons.home_rounded),
        label: 'Beranda',
      ),
      const BottomNavigationBarItem(
        icon: Icon(Icons.calendar_today_outlined),
        activeIcon: Icon(Icons.calendar_today_rounded),
        label: 'Jadwal',
      ),
      const BottomNavigationBarItem(
        icon: Icon(Icons.auto_stories_outlined),
        activeIcon: Icon(Icons.auto_stories_rounded),
        label: 'Jurnal',
      ),
      if (isHomeroom)
        const BottomNavigationBarItem(
          icon: Icon(Icons.verified_outlined),
          activeIcon: Icon(Icons.verified_rounded),
          label: 'Verif Izin',
        ),
      const BottomNavigationBarItem(
        icon: Icon(Icons.person_outline),
        activeIcon: Icon(Icons.person_rounded),
        label: 'Profil',
      ),
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
          items: navItems,
        ),
      ),
    );
  }
}
