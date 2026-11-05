class ScheduleModel {
  final int id;
  final int? teacherId;
  final String? teacherName;
  final int? classId;
  final String? className;
  final int? roomId;
  final String? roomName;
  final String subjectName;
  final String dayOfWeek;
  final String startTime;
  final String endTime;
  final String? cycleType;

  ScheduleModel({
    required this.id,
    this.teacherId,
    this.teacherName,
    this.classId,
    this.className,
    this.roomId,
    this.roomName,
    required this.subjectName,
    required this.dayOfWeek,
    required this.startTime,
    required this.endTime,
    this.cycleType,
  });

  factory ScheduleModel.fromJson(Map<String, dynamic> json) {
    return ScheduleModel(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id'].toString()) ?? 0,
      teacherId: json['teacher_id'] != null ? int.tryParse(json['teacher_id'].toString()) : null,
      teacherName: json['teacher'] != null ? json['teacher']['name'] : null,
      classId: json['class_id'] != null ? int.tryParse(json['class_id'].toString()) : null,
      className: json['school_class'] != null
          ? json['school_class']['class_name']
          : (json['class'] != null ? json['class']['class_name'] : null),
      roomId: json['room_id'] != null ? int.tryParse(json['room_id'].toString()) : null,
      roomName: json['room'] != null ? json['room']['name'] : null,
      subjectName: json['subject_name'] ?? json['subject'] ?? '-',
      dayOfWeek: json['day_of_week'] ?? '-',
      startTime: json['start_time'] ?? '-',
      endTime: json['end_time'] ?? '-',
      cycleType: json['cycle_type'],
    );
  }
}
