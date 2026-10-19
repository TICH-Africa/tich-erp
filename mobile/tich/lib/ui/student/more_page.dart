import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../data/student_api.dart';
import '../settings/settings_hub_page.dart';
import '../shell/portal_nav_counts_scope.dart';
import '../widgets/tich_more_drawer.dart';
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

  /// The links the More side bar shows (and the iOS More page lists).
  static List<TichMoreItem> items(BuildContext context, StudentApi api) {
    final nav = PortalNavCountsScope.maybeOf(context);

    void open(Widget page) =>
        Navigator.of(context).push(MaterialPageRoute(builder: (_) => page));

    int badge(String key) => nav?.countFor(key) ?? 0;

    return [
      TichMoreItem(
        label: 'My profile',
        icon: Icons.person_outline,
        badgeCount: badge('profile'),
        onTap: () => open(StudentProfilePage(api: api)),
      ),
      TichMoreItem(
        label: 'Enrolment',
        icon: Icons.badge_outlined,
        badgeCount: badge('enrolment'),
        onTap: () => open(StudentEnrolmentPage(api: api)),
      ),
      TichMoreItem(
        label: 'Documents',
        icon: Icons.folder_outlined,
        badgeCount: badge('documents'),
        onTap: () => open(StudentDocumentsPage(api: api)),
      ),
      TichMoreItem(
        label: 'Clearance',
        icon: Icons.task_alt_outlined,
        badgeCount: badge('clearance'),
        onTap: () => open(StudentClearancePage(api: api)),
      ),
      TichMoreItem(
        label: 'Course evaluations',
        icon: Icons.rate_review_outlined,
        badgeCount: badge('evaluations'),
        onTap: () => open(StudentEvaluationsPage(api: api)),
      ),
      TichMoreItem(
        label: 'Deferment requests',
        icon: Icons.event_busy_outlined,
        badgeCount: badge('requests'),
        onTap: () => open(StudentLifecyclePage(api: api)),
      ),
      TichMoreItem(
        label: 'Suggestion box',
        icon: Icons.lightbulb_outline,
        badgeCount: badge('suggestions'),
        onTap: () => open(StudentSuggestionsPage(api: api)),
      ),
      TichMoreItem(
        label: 'Notifications',
        icon: Icons.notifications_outlined,
        badgeCount: badge('notifications'),
        onTap: () => open(StudentNotificationsPage(api: api)),
      ),
      TichMoreItem(
        label: 'Settings',
        icon: Icons.settings_outlined,
        onTap: () => open(const SettingsHubPage()),
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
