import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../data/student_api.dart';
import '../../navigation/tich_root_nav.dart';
import '../shell/portal_nav_counts_scope.dart';
import '../shell/student_portal_menu_scope.dart';
import '../widgets/tich_global_more_footer.dart';
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

  static List<TichMoreItem> mainItems(BuildContext context, StudentApi api) {
    final nav = PortalNavCountsScope.maybeOf(context);

    void open(Widget page) => TichRootNav.push(page);

    int badge(String key) => nav?.countFor(key) ?? 0;

    return [
      TichMoreItem(
        label: 'My profile',
        icon: Icons.person_outline,
        badgeCount: badge('profile'),
        showProfilePhoto: true,
        photoUrl: StudentPortalMenuScope.photoUrlOf(context),
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
    ];
  }

  static List<TichMoreItem> items(BuildContext context, StudentApi api) => [
        ...mainItems(context, api),
        ...tichMoreMenuFooter(context, TichPortalMenuContext.student),
      ];

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'More',
      body: TichMoreList(items: items(context, api)),
    );
  }
}
