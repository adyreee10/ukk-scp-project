class UserModel {
  final int id;
  final String name;
  final String username;
  final String email;
  final int? roleId;
  final String role;
  final int? classId;
  final String? className;
  final int? homeroomClassId;
  final String? homeroomClassName;
  final String? nis;
  final String? phone;
  final String? address;
  final String? studentStatus;

  UserModel({
    required this.id,
    required this.name,
    required this.username,
    required this.email,
    this.roleId,
    required this.role,
    this.classId,
    this.className,
    this.homeroomClassId,
    this.homeroomClassName,
    this.nis,
    this.phone,
    this.address,
    this.studentStatus,
  });

  bool get isStudent => role.toLowerCase() == 'siswa';
  bool get isTeacher => role.toLowerCase() == 'guru';
  bool get isHomeroom => role.toLowerCase() == 'wali kelas';
  bool get isBk => role.toLowerCase() == 'guru bk';
  bool get isParent => role.toLowerCase() == 'orang tua';

  factory UserModel.fromJson(Map<String, dynamic> json) {
    return UserModel(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id'].toString()) ?? 0,
      name: json['name'] ?? '',
      username: json['username'] ?? '',
      email: json['email'] ?? '',
      roleId: json['role_id'] != null ? (json['role_id'] is int ? json['role_id'] : int.tryParse(json['role_id'].toString())) : null,
      role: json['role'] ?? json['role_name'] ?? '',
      classId: json['class_id'] != null ? (json['class_id'] is int ? json['class_id'] : int.tryParse(json['class_id'].toString())) : null,
      className: json['class_name'] ?? (json['school_class'] != null ? json['school_class']['class_name'] : null),
      homeroomClassId: json['homeroom_class_id'] != null ? (json['homeroom_class_id'] is int ? json['homeroom_class_id'] : int.tryParse(json['homeroom_class_id'].toString())) : null,
      homeroomClassName: json['homeroom_class_name'] ?? (json['homeroom_class'] != null ? json['homeroom_class']['class_name'] : null),
      nis: json['nis']?.toString(),
      phone: json['phone']?.toString(),
      address: json['address']?.toString(),
      studentStatus: json['student_status']?.toString(),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'username': username,
      'email': email,
      'role_id': roleId,
      'role': role,
      'class_id': classId,
      'class_name': className,
      'homeroom_class_id': homeroomClassId,
      'homeroom_class_name': homeroomClassName,
      'nis': nis,
      'phone': phone,
      'address': address,
      'student_status': studentStatus,
    };
  }
}
