import 'package:equatable/equatable.dart';

class AuthUser extends Equatable {
  const AuthUser({
    required this.id,
    required this.email,
    required this.name,
    required this.userType,
    this.roles = const [],
  });

  final int id;
  final String email;
  final String name;
  final String userType;
  final List<String> roles;

  bool get isStudent => userType == 'student';

  bool get isStaffLike =>
      userType == 'staff' ||
      userType == 'super_admin' ||
      userType == 'platform_operator' ||
      userType == 'institution_admin';

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

    return AuthUser(
      id: (json['id'] as num).toInt(),
      email: (json['email'] ?? '').toString(),
      name: (json['name'] ?? json['email'] ?? 'User').toString(),
      userType: (json['user_type'] ?? '').toString(),
      roles: roles,
    );
  }

  @override
  List<Object?> get props => [id, email, name, userType, roles];
}
