import 'package:intl/intl.dart';

class DateFormatter {
  static const Map<String, String> _days = {
    'Monday': 'Senin',
    'Tuesday': 'Selasa',
    'Wednesday': 'Rabu',
    'Thursday': 'Kamis',
    'Friday': 'Jumat',
    'Saturday': 'Sabtu',
    'Sunday': 'Minggu',
  };

  static const Map<String, String> _months = {
    'January': 'Januari',
    'February': 'Februari',
    'March': 'Maret',
    'April': 'April',
    'May': 'Mei',
    'June': 'Juni',
    'July': 'Juli',
    'August': 'Agustus',
    'September': 'September',
    'October': 'Oktober',
    'November': 'November',
    'December': 'Desember',
  };

  static String formatIndonesian(DateTime date) {
    final engDay = DateFormat('EEEE').format(date);
    final dayName = _days[engDay] ?? engDay;
    final dayNum = DateFormat('d').format(date);
    final engMonth = DateFormat('MMMM').format(date);
    final monthName = _months[engMonth] ?? engMonth;
    final year = DateFormat('y').format(date);

    return '$dayName, $dayNum $monthName $year';
  }

  static String formatShort(DateTime date) {
    return DateFormat('dd/MM/yyyy').format(date);
  }

  static String toYmd(DateTime date) {
    return DateFormat('yyyy-MM-dd').format(date);
  }

  static String formatTime(String? timeStr) {
    if (timeStr == null || timeStr.isEmpty) return '-';
    try {
      final parts = timeStr.split(':');
      if (parts.length >= 2) {
        return '${parts[0]}:${parts[1]}';
      }
    } catch (_) {}
    return timeStr;
  }
}
