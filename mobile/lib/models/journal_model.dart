import 'schedule_model.dart';

class JournalModel {
  final int id;
  final int scheduleId;
  final String date;
  final String topicDescription;
  final String? photoPath;
  final ScheduleModel? schedule;

  JournalModel({
    required this.id,
    required this.scheduleId,
    required this.date,
    required this.topicDescription,
    this.photoPath,
    this.schedule,
  });

  factory JournalModel.fromJson(Map<String, dynamic> json) {
    return JournalModel(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id'].toString()) ?? 0,
      scheduleId: json['schedule_id'] is int ? json['schedule_id'] : int.tryParse(json['schedule_id'].toString()) ?? 0,
      date: json['date'] ?? '',
      topicDescription: json['topic_description'] ?? '',
      photoPath: json['photo_path'],
      schedule: json['schedule'] != null ? ScheduleModel.fromJson(json['schedule']) : null,
    );
  }
}
