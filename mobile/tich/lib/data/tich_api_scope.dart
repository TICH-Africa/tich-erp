import 'package:flutter/widgets.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../models/auth_user.dart';
import '../navigation/tich_root_nav.dart';
import 'api_client.dart';
import 'employee_api.dart';
import 'procurement_api.dart';
import 'staff_api.dart';
import 'student_api.dart';

/// Returns a scoped repository when registered for this user, else null.
T? tichTryRead<T>(BuildContext context) {
  try {
    return context.read<T>();
  } catch (_) {
    return null;
  }
}

/// Registers only the API clients this user can reach (lighter than loading all four globally).
class TichApiScope extends StatelessWidget {
  const TichApiScope({
    super.key,
    required this.user,
    required this.child,
  });

  final AuthUser user;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    final client = context.read<ApiClient>();

    if (user.isStudent) {
      return MultiRepositoryProvider(
        providers: [
          RepositoryProvider<StudentApi>(create: (_) => StudentApi(client)),
        ],
        child: child,
      );
    }

    return MultiRepositoryProvider(
      providers: [
        if (user.canAccessEmployeePortal)
          RepositoryProvider<EmployeeApi>(create: (_) => EmployeeApi(client)),
        if (user.canAccessStaffPortal)
          RepositoryProvider<StaffApi>(create: (_) => StaffApi(client)),
        if (user.canAccessProcurementPortal)
          RepositoryProvider<ProcurementApi>(
            create: (_) => ProcurementApi(client),
          ),
      ],
      child: child,
    );
  }
}

/// Stable key so portal repositories rebuild when capabilities change.
Key tichApiScopeKey(AuthUser user) => ValueKey(
      'api:${user.id}:${user.isStudent}:'
      '${user.canAccessEmployeePortal}:'
      '${user.canAccessStaffPortal}:'
      '${user.canAccessProcurementPortal}',
    );

/// Replaces the nav stack with [portal] and registers only APIs this user needs.
void tichOpenPortalRoot(AuthUser user, Widget portal) {
  TichRootNav.pushAndRemoveUntil(portal);
}
