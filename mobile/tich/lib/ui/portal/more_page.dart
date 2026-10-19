import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../adaptive/adaptive.dart';
import '../../bloc/auth/auth_bloc.dart';
import '../../bloc/auth/auth_state.dart';
import '../../data/employee_api.dart';
import '../settings/settings_hub_page.dart';
import '../shell/portal_nav_counts_scope.dart';
import '../shell/staff_portal_shell.dart';
import '../widgets/tich_more_drawer.dart';
import 'appraisals_page.dart';
import 'concerns_page.dart';
import 'documents_page.dart';
import 'feedback_page.dart';
import 'policies_page.dart';
import 'portal_chooser_page.dart';
import 'profile_page.dart';
import 'time_logs_page.dart';
import 'wfh_page.dart';

class MorePage extends StatelessWidget {
  const MorePage({super.key, required this.api});

  final EmployeeApi api;

  /// The links the More side bar shows (and the iOS More page lists).
  static List<TichMoreItem> items(BuildContext context, EmployeeApi api) {
    final auth = context.watch<AuthBloc>().state;
    final user = auth is AuthAuthenticated ? auth.user : null;
    final nav = PortalNavCountsScope.maybeOf(context);

    void open(Widget page) =>
        Navigator.of(context).push(MaterialPageRoute(builder: (_) => page));

    int badge(String key) => nav?.countFor(key) ?? 0;

    return [
      if (user?.canAccessStaffPortal == true)
        TichMoreItem(
          label: 'Switch to Staff Portal',
          icon: Icons.school_outlined,
          onTap: () => Navigator.of(context).pushAndRemoveUntil(
            MaterialPageRoute(builder: (_) => const StaffPortalShell()),
            (route) => false,
          ),
        ),
      if (user?.hasBothStaffPortals == true)
        TichMoreItem(
          label: 'Choose portal',
          icon: Icons.apps_outlined,
          onTap: () => Navigator.of(context).pushAndRemoveUntil(
            MaterialPageRoute(builder: (_) => PortalChooserPage(user: user!)),
            (route) => false,
          ),
        ),
      TichMoreItem(
        label: 'My profile',
        icon: Icons.person_outline,
        badgeCount: badge('profile-changes'),
        onTap: () => open(ProfilePage(api: api)),
      ),
      TichMoreItem(
        label: 'Settings',
        icon: Icons.settings_outlined,
        onTap: () => open(const SettingsHubPage()),
      ),
      TichMoreItem(
        label: 'Documents',
        icon: Icons.folder_outlined,
        onTap: () => open(DocumentsPage(api: api)),
      ),
      TichMoreItem(
        label: 'HR policies',
        icon: Icons.policy_outlined,
        badgeCount: badge('policies'),
        onTap: () => open(PoliciesPage(api: api)),
      ),
      TichMoreItem(
        label: 'Work from home',
        icon: Icons.home_work_outlined,
        onTap: () => open(WfhPage(api: api)),
      ),
      TichMoreItem(
        label: 'Time logs',
        icon: Icons.schedule_outlined,
        onTap: () => open(TimeLogsPage(api: api)),
      ),
      TichMoreItem(
        label: 'Appraisals',
        icon: Icons.flag_outlined,
        badgeCount: badge('appraisals'),
        onTap: () => open(AppraisalsPage(api: api)),
      ),
      TichMoreItem(
        label: 'Concerns',
        icon: Icons.report_problem_outlined,
        badgeCount: badge('concerns'),
        onTap: () => open(ConcernsPage(api: api)),
      ),
      TichMoreItem(
        label: 'Feedback',
        icon: Icons.feedback_outlined,
        badgeCount: badge('feedback'),
        onTap: () => open(FeedbackPage(api: api)),
      ),
    ];
  }

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'More',
      body: TichMoreList(items: items(context, api)),
    );
  }
}
