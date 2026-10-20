import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../adaptive/adaptive.dart';
import '../../bloc/auth/auth_bloc.dart';
import '../../bloc/auth/auth_event.dart';
import '../../bloc/auth/auth_state.dart';
import '../../data/staff_api.dart';
import '../../models/auth_user.dart';
import '../portal/portal_chooser_page.dart';
import '../settings/settings_hub_page.dart';
import '../shell/portal_nav_counts_scope.dart';
import '../shell/portal_shell.dart';
import '../shell/procurement_portal_shell.dart';
import '../widgets/tich_more_drawer.dart';
import 'content_page.dart';
import 'grading_page.dart';
import 'lesson_plans_page.dart';

class StaffMorePage extends StatelessWidget {
  const StaffMorePage({super.key, required this.api});

  final StaffApi api;

  static bool _canSwitchToEmployee(AuthUser? user) {
    if (user == null || user.isStudent) return false;
    return user.canAccessEmployeePortal ||
        user.hasBothStaffPortals ||
        (user.staffId != null && user.isTeachingStaff);
  }

  /// The links the More side bar shows (and the iOS More page lists).
  static List<TichMoreItem> items(BuildContext context, StaffApi api) {
    final auth = context.watch<AuthBloc>().state;
    final user = auth is AuthAuthenticated ? auth.user : null;
    final nav = PortalNavCountsScope.maybeOf(context);

    void open(Widget page) =>
        Navigator.of(context).push(MaterialPageRoute(builder: (_) => page));

    int badge(String key) => nav?.countFor(key) ?? 0;

    final items = <TichMoreItem>[
      TichMoreItem(
        label: 'Marks & assessments',
        icon: Icons.grade_outlined,
        badgeCount: badge('exam-papers'),
        onTap: () => open(StaffGradingPage(api: api)),
      ),
      TichMoreItem(
        label: 'Lesson plans',
        icon: Icons.menu_book_outlined,
        badgeCount: badge('lesson-plans'),
        onTap: () => open(StaffLessonPlansPage(api: api)),
      ),
      TichMoreItem(
        label: 'Learning content',
        icon: Icons.layers_outlined,
        onTap: () => open(StaffContentPage(api: api)),
      ),
      TichMoreItem(
        label: 'Settings',
        icon: Icons.settings_outlined,
        onTap: () => open(const SettingsHubPage()),
      ),
      TichMoreItem(
        label: 'Sign out',
        icon: Icons.logout,
        onTap: () => context.read<AuthBloc>().add(const AuthLogoutRequested()),
      ),
    ];

    if (user?.canAccessProcurementPortal == true) {
      items.insert(
        0,
        TichMoreItem(
          label: 'Switch to Procurement Portal',
          icon: Icons.inventory_outlined,
          onTap: () => Navigator.of(context).pushAndRemoveUntil(
            MaterialPageRoute(builder: (_) => const ProcurementPortalShell()),
            (route) => false,
          ),
        ),
      );
    }

    if (_canSwitchToEmployee(user)) {
      items.insert(
        0,
        TichMoreItem(
          label: 'Switch to My Employee Portal',
          icon: Icons.badge_outlined,
          onTap: () => Navigator.of(context).pushAndRemoveUntil(
            MaterialPageRoute(builder: (_) => const PortalShell()),
            (route) => false,
          ),
        ),
      );
    }

    if (user?.hasMultiplePortals == true) {
      items.insert(
        _canSwitchToEmployee(user) ? 1 : 0,
        TichMoreItem(
          label: 'Choose portal',
          icon: Icons.apps_outlined,
          onTap: () => Navigator.of(context).pushAndRemoveUntil(
            MaterialPageRoute(builder: (_) => PortalChooserPage(user: user!)),
            (route) => false,
          ),
        ),
      );
    }

    return items;
  }

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'More',
      body: TichMoreList(items: items(context, api)),
    );
  }
}
