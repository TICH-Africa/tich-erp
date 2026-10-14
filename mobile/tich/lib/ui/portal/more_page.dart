import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../data/employee_api.dart';
import '../../theme/tich_theme.dart';
import '../settings/settings_hub_page.dart';
import 'appraisals_page.dart';
import 'concerns_page.dart';
import 'documents_page.dart';
import 'feedback_page.dart';
import 'policies_page.dart';
import 'profile_page.dart';
import 'time_logs_page.dart';
import 'wfh_page.dart';

class MorePage extends StatelessWidget {
  const MorePage({super.key, required this.api});

  final EmployeeApi api;

  @override
  Widget build(BuildContext context) {
    final items = [
      _MoreItem('My profile', Icons.person_outline, () => ProfilePage(api: api)),
      _MoreItem('Settings', Icons.settings_outlined, () => const SettingsHubPage()),
      _MoreItem('Documents', Icons.folder_outlined, () => DocumentsPage(api: api)),
      _MoreItem('HR policies', Icons.policy_outlined, () => PoliciesPage(api: api)),
      _MoreItem('Work from home', Icons.home_work_outlined, () => WfhPage(api: api)),
      _MoreItem('Time logs', Icons.schedule_outlined, () => TimeLogsPage(api: api)),
      _MoreItem('Appraisals', Icons.flag_outlined, () => AppraisalsPage(api: api)),
      _MoreItem('Concerns', Icons.report_problem_outlined, () => ConcernsPage(api: api)),
      _MoreItem('Feedback', Icons.feedback_outlined, () => FeedbackPage(api: api)),
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
              onTap: () => Navigator.of(context).push(
                MaterialPageRoute(builder: (_) => item.builder()),
              ),
            ),
          );
        },
      ),
    );
  }
}

class _MoreItem {
  const _MoreItem(this.label, this.icon, this.builder);

  final String label;
  final IconData icon;
  final Widget Function() builder;
}
