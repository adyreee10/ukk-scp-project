class LeaveRequestModel {
  final int id;
  final int studentId;
  final String? studentName;
  final String? className;
  final int? applicantId;
  final String? applicantName;
  final int? verifiedBy;
  final String? verifierName;
  final String startDate;
  final String endDate;
  final String leaveType; // Sakit, Izin, Dispensasi
  final String reason;
  final String? documentPath;
  final String statusApproval; // Pending, Approved, Rejected
  final String? rejectionNote;
  final DateTime? createdAt;

  LeaveRequestModel({
    required this.id,
    required this.studentId,
    this.studentName,
    this.className,
    this.applicantId,
    this.applicantName,
    this.verifiedBy,
    this.verifierName,
    required this.startDate,
    required this.endDate,
    required this.leaveType,
    required this.reason,
    this.documentPath,
    required this.statusApproval,
    this.rejectionNote,
    this.createdAt,
  });

  bool get isPending => statusApproval.toLowerCase() == 'pending';
  bool get isApproved => statusApproval.toLowerCase() == 'approved';
  bool get isRejected => statusApproval.toLowerCase() == 'rejected';

  factory LeaveRequestModel.fromJson(Map<String, dynamic> json) {
    return LeaveRequestModel(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id'].toString()) ?? 0,
      studentId: json['student_id'] is int ? json['student_id'] : int.tryParse(json['student_id'].toString()) ?? 0,
      studentName: json['student'] != null ? json['student']['name'] : null,
      className: json['student'] != null && json['student']['school_class'] != null
          ? json['student']['school_class']['class_name']
          : null,
      applicantId: json['applicant_id'] != null ? int.tryParse(json['applicant_id'].toString()) : null,
      applicantName: json['applicant'] != null ? json['applicant']['name'] : null,
      verifiedBy: json['verified_by'] != null ? int.tryParse(json['verified_by'].toString()) : null,
      verifierName: json['verifier'] != null ? json['verifier']['name'] : null,
      startDate: json['start_date'] ?? '',
      endDate: json['end_date'] ?? '',
      leaveType: json['leave_type'] ?? 'Izin',
      reason: json['reason'] ?? '',
      documentPath: json['document_path'],
      statusApproval: json['status_approval'] ?? 'Pending',
      rejectionNote: json['rejection_note'],
      createdAt: json['created_at'] != null ? DateTime.tryParse(json['created_at'].toString()) : null,
    );
  }
}
