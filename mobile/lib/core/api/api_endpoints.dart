class ApiEndpoints {
  // Default base URL (bisa disesuaikan via SharedPreferences)
  // Untuk Emulator Android gunakan: http://10.0.2.2:8000/api
  // Untuk Perangkat Fisik (HP) gunakan IP laptop/PC: http://192.168.x.x:8000/api
  static const String defaultBaseUrl = 'http://10.0.2.2:8000/api';

  // Auth
  static const String login = '/login';
  static const String logout = '/logout';
  static const String me = '/me';
  static const String profile = '/profile';
  static const String updatePassword = '/profile/password';

  // Siswa
  static const String studentSchedules = '/student/schedules';
  static const String studentJournals = '/student/journals';
  static const String studentAttendances = '/student/attendances';
  static const String studentLeaveRequests = '/leave-requests';

  // Guru & Wali Kelas
  static const String teacherDashboard = '/teacher/dashboard';
  static const String teacherSchedules = '/teacher/schedules';
  static const String teacherJournals = '/teacher/journals';
  static const String teacherAttendances = '/teacher/attendances';

  // Wali Kelas & Guru BK (Student Affairs)
  static const String bkLeaveRequests = '/student-affairs/leave-requests';
  static const String bkAttendanceRecap = '/student-affairs/attendance-recap';
}
