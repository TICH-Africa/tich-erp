import 'package:flutter/widgets.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../models/auth_user.dart';
import '../navigation/tich_root_nav.dart';
import 'api_client.dart';
import 'employee_api.dart';
import 'procurement_api.dart';
import 'staff_api.dart';
import 'student_api.dart';

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
    final providers = <RepositoryProvider<dynamic>>[];

    if (user.isStudent) {
      providers.add(RepositoryProvider(create: (_) => StudentApi(client)));
    } else {
      if (user.canAccessEmployeePortal) {
        providers.add(RepositoryProvider(create: (_) => EmployeeApi(client)));
      }
      if (user.canAccessStaffPortal) {
        providers.add(RepositoryProvider(create: (_) => StaffApi(client)));
      }
      if (user.canAccessProcurementPortal) {
        providers.add(
          RepositoryProvider(create: (_) => ProcurementApi(client)),
        );
      }
    }

    if (providers.isEmpty) {
      return child;
    }
    return MultiRepositoryProvider(
      providers: providers,
      child: child,
    );
  }
}

/// Replaces the nav stack with [portal] and registers only APIs this user needs.
void tichOpenPortalRoot(AuthUser user, Widget portal) {
  TichRootNav.pushAndRemoveUntil(
    TichApiScope(user: user, child: portal),
  );
}
