import 'journal_model.dart';
import 'user_model.dart';

class AttendanceModel {
  final int id;
  final int? journalId;
  final int studentId;
  final String status; // Hadir, Sakit, Izin, Alfa
  final String date;
  final UserModel? student;
  final JournalModel? journal;

  AttendanceModel({
    required this.id,
    this.journalId,
    required this.studentId,
    required this.status,
    required this.date,
    this.student,
    this.journal,
  });

  factory AttendanceModel.fromJson(Map<String, dynamic> json) {
    return AttendanceModel(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id'].toString()) ?? 0,
      journalId: json['journal_id'] != null ? int.tryParse(json['journal_id'].toString()) : null,
      studentId: json['student_id'] is int ? json['student_id'] : int.tryParse(json['student_id'].toString()) ?? 0,
      status: json['status'] ?? 'Alfa',
      date: json['date'] ?? '',
      student: json['student'] != null ? UserModel.fromJson(json['student']) : null,
      journal: json['journal'] != null ? JournalModel.fromJson(json['journal']) : null,
    );
  }
}

class AttendanceSummary {
  final int hadir;
  final int sakit;
  final int izin;
  final int alfa;

  AttendanceSummary({
    required this.hadir,
    required this.sakit,
    required this.izin,
    required this.alfa,
  });

  int get total => hadir + sakit + izin + alfa;
  double get percentage => total > 0 ? (hadir / total) * 100 : 0.0;

  factory AttendanceSummary.fromJson(Map<String, dynamic>? json) {
    if (json == null) {
      return AttendanceSummary(hadir: 0, sakit: 0, izin: 0, alfa: 0);
    }
    return AttendanceSummary(
      hadir: int.tryParse(json['Hadir']?.toString() ?? '0') ?? 0,
      sakit: int.tryParse(json['Sakit']?.toString() ?? '0') ?? 0,
      izin: int.tryParse(json['Izin']?.toString() ?? '0') ?? 0,
      alfa: int.tryParse(json['Alfa']?.toString() ?? '0') ?? 0,
    );
  }
}
