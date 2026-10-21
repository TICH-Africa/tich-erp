import 'package:equatable/equatable.dart';

class AuthUser extends Equatable {
  const AuthUser({
    required this.id,
    required this.email,
    required this.name,
    required this.userType,
    this.roles = const [],
    this.staffId,
    this.studentId,
    this.isTeachingStaff = false,
    this.canAccessEmployeePortal = false,
    this.canAccessStaffPortal = false,
    this.canAccessProcurementPortal = false,
  });

  final int id;
  final String email;
  final String name;
  final String userType;
  final List<String> roles;
  final int? staffId;
  final int? studentId;
  final bool isTeachingStaff;
  final bool canAccessEmployeePortal;
  final bool canAccessStaffPortal;
  final bool canAccessProcurementPortal;

  bool get isStudent =>
      userType.toLowerCase() == 'student' || studentId != null;

  /// Linked institutional employee (HR / My Employee Portal).
  bool get isStaffLike =>
      userType == 'staff' ||
      userType == 'super_admin' ||
      userType == 'platform_operator' ||
      userType == 'institution_admin' ||
      staffId != null;

  bool get hasBothStaffPortals =>
      canAccessEmployeePortal && canAccessStaffPortal;

  int get accessiblePortalCount {
    var n = 0;
    if (canAccessEmployeePortal) n++;
    if (canAccessStaffPortal) n++;
    if (canAccessProcurementPortal) n++;
    return n;
  }

  bool get hasMultiplePortals => accessiblePortalCount > 1;

  static int? _idFromJson(dynamic value) {
    if (value == null) return null;
    if (value is num) return value.toInt();
    return int.tryParse(value.toString());
  }

  factory AuthUser.fromJson(Map<String, dynamic> json) {
    final rolesRaw = json['roles'];
    final roles = <String>[];
    if (rolesRaw is List) {
      for (final item in rolesRaw) {
        if (item is String) {
          roles.add(item);
        } else if (item is Map && item['role_name'] != null) {
          roles.add(item['role_name'].toString());
        } else if (item is Map && item['name'] != null) {
          roles.add(item['name'].toString());
        }
      }
    }

    final teachingRoles = {
      'Lecturer/Tutor',
      'HOD',
      'Dean of Students',
      'Academic Registrar',
      'Super Admin',
    };
    final roleTeaching = roles.any(teachingRoles.contains);
    final isTeaching = json['is_teaching_staff'] == true || roleTeaching;
    final canEmployee = json['can_access_employee_portal'] == true ||
        (json['staff_id'] != null && json['user_type'] != 'student');
    final canStaff = json['can_access_staff_portal'] == true || isTeaching;
    final canProcurement =
        json['can_access_procurement_portal'] == true;

    return AuthUser(
      id: (json['id'] as num).toInt(),
      email: (json['email'] ?? '').toString(),
      name: (json['name'] ?? json['email'] ?? 'User').toString(),
      userType: (json['user_type'] ?? '').toString(),
      roles: roles,
      staffId: _idFromJson(json['staff_id']),
      studentId: _idFromJson(json['student_id']),
      isTeachingStaff: isTeaching,
      canAccessEmployeePortal: canEmployee && json['user_type'] != 'student',
      canAccessStaffPortal: canStaff && json['user_type'] != 'student',
      canAccessProcurementPortal:
          canProcurement && json['user_type'] != 'student',
    );
  }

  @override
  List<Object?> get props => [
        id,
        email,
        name,
        userType,
        roles,
        staffId,
        studentId,
        isTeachingStaff,
        canAccessEmployeePortal,
        canAccessStaffPortal,
        canAccessProcurementPortal,
      ];
}
