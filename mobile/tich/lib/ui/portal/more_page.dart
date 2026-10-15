import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../adaptive/adaptive.dart';
import '../../bloc/auth/auth_bloc.dart';
import '../../bloc/auth/auth_state.dart';
import '../../data/employee_api.dart';
import '../../theme/tich_theme.dart';
import '../settings/settings_hub_page.dart';
import '../shell/staff_portal_shell.dart';
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

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthBloc>().state;
    final user = auth is AuthAuthenticated ? auth.user : null;

    final items = <_MoreItem>[
      if (user?.canAccessStaffPortal == true)
        _MoreItem(
          'Switch to Staff Portal',
          Icons.school_outlined,
          () => const StaffPortalShell(),
          replaceRoot: true,
        ),
      if (user?.hasBothStaffPortals == true)
        _MoreItem(
          'Choose portal',
          Icons.apps_outlined,
          () => PortalChooserPage(user: user!),
          replaceRoot: true,
        ),
      _MoreItem('My profile', Icons.person_outline, () => ProfilePage(api: api)),
      _MoreItem(
        'Settings',
        Icons.settings_outlined,
        () => const SettingsHubPage(),
      ),
      _MoreItem('Documents', Icons.folder_outlined, () => DocumentsPage(api: api)),
      _MoreItem(
        'HR policies',
        Icons.policy_outlined,
        () => PoliciesPage(api: api),
      ),
      _MoreItem(
        'Work from home',
        Icons.home_work_outlined,
        () => WfhPage(api: api),
      ),
      _MoreItem(
        'Time logs',
        Icons.schedule_outlined,
        () => TimeLogsPage(api: api),
      ),
      _MoreItem(
        'Appraisals',
        Icons.flag_outlined,
        () => AppraisalsPage(api: api),
      ),
      _MoreItem(
        'Concerns',
        Icons.report_problem_outlined,
        () => ConcernsPage(api: api),
      ),
      _MoreItem(
        'Feedback',
        Icons.feedback_outlined,
        () => FeedbackPage(api: api),
      ),
    ];

    return AdaptiveScaffold(
      title: 'More',
      body: ListView.separated(
        padding: const EdgeInsets.all(16),
        itemCount: items.length,
        separatorBuilder: (_, __) => const SizedBox(height: 8),
        itemBuilder: (context, i) {
          final item = items[i];
          return Card(
            child: ListTile(
              leading: CircleAvatar(
                backgroundColor: TichColors.blueLight,
                child: Icon(item.icon, color: TichColors.blueDark),
              ),
              title: Text(
                item.label,
                style: tichUi(fontWeight: FontWeight.w600),
              ),
              trailing: const Icon(Icons.chevron_right),
              onTap: () {
                final page = item.builder();
                if (item.replaceRoot) {
                  Navigator.of(context).pushAndRemoveUntil(
                    MaterialPageRoute(builder: (_) => page),
                    (route) => false,
                  );
                } else {
                  Navigator.of(context).push(
                    MaterialPageRoute(builder: (_) => page),
                  );
                }
              },
            ),
          );
        },
      ),
    );
  }
}

class _MoreItem {
  const _MoreItem(
    this.label,
    this.icon,
    this.builder, {
    this.replaceRoot = false,
  });

  final String label;
  final IconData icon;
  final Widget Function() builder;
  final bool replaceRoot;
}
