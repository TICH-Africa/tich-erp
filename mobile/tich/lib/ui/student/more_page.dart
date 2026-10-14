import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../data/student_api.dart';
import '../../theme/tich_theme.dart';
import '../settings/settings_hub_page.dart';
import 'clearance_page.dart';
import 'documents_page.dart';
import 'enrolment_page.dart';
import 'evaluations_page.dart';
import 'lifecycle_page.dart';
import 'notifications_page.dart';
import 'profile_page.dart';
import 'suggestions_page.dart';

class StudentMorePage extends StatelessWidget {
  const StudentMorePage({super.key, required this.api});

  final StudentApi api;

  @override
  Widget build(BuildContext context) {
    final items = [
      _Item('My profile', Icons.person_outline, () => StudentProfilePage(api: api)),
      _Item('Enrolment', Icons.badge_outlined, () => StudentEnrolmentPage(api: api)),
      _Item('Documents', Icons.folder_outlined, () => StudentDocumentsPage(api: api)),
      _Item('Clearance', Icons.task_alt_outlined, () => StudentClearancePage(api: api)),
      _Item('Course evaluations', Icons.rate_review_outlined,
          () => StudentEvaluationsPage(api: api)),
      _Item('Deferment requests', Icons.event_busy_outlined,
          () => StudentLifecyclePage(api: api)),
      _Item('Suggestion box', Icons.lightbulb_outline,
          () => StudentSuggestionsPage(api: api)),
      _Item('Notifications', Icons.notifications_outlined,
          () => StudentNotificationsPage(api: api)),
      _Item('Settings', Icons.settings_outlined, () => const SettingsHubPage()),
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
              title: Text(item.label, style: tichUi(fontWeight: FontWeight.w600)),
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

class _Item {
  const _Item(this.label, this.icon, this.builder);
  final String label;
  final IconData icon;
  final Widget Function() builder;
}
