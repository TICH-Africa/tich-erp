import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../data/student_api.dart';
import '../shell/portal_nav_counts_scope.dart';
import '../shell/student_portal_menu_scope.dart';
import '../widgets/tich_global_more_footer.dart';
import '../widgets/tich_more_drawer.dart';
import 'student_module_nav.dart';
import 'student_prefetch.dart';

class StudentMorePage extends StatelessWidget {
  const StudentMorePage({super.key, required this.api});

  final StudentApi api;

  static List<TichMoreItem> mainItems(BuildContext context, StudentApi api) {
    final nav = PortalNavCountsScope.maybeOf(context);

    int badge(String key) => nav?.countFor(key) ?? 0;

    return [
      TichMoreItem(
        label: 'My profile',
        icon: Icons.person_outline,
        badgeCount: badge('profile'),
        showProfilePhoto: true,
        photoUrl: StudentPortalMenuScope.photoUrlOf(context),
        onTapDown: (_) => StudentPrefetch.profile(api),
        onTap: () => openStudentProfileFromMore(api),
      ),
      TichMoreItem(
        label: 'Enrolment',
        icon: Icons.badge_outlined,
        badgeCount: badge('enrolment'),
        onTapDown: (_) => StudentPrefetch.enrolment(api),
        onTap: () => openStudentEnrolmentFromMore(api),
      ),
      TichMoreItem(
        label: 'Documents',
        icon: Icons.folder_outlined,
        badgeCount: badge('documents'),
        onTapDown: (_) => StudentPrefetch.documents(api),
        onTap: () => openStudentDocumentsFromMore(api),
      ),
      TichMoreItem(
        label: 'Clearance',
        icon: Icons.task_alt_outlined,
        badgeCount: badge('clearance'),
        onTapDown: (_) => StudentPrefetch.clearance(api),
        onTap: () => openStudentClearanceFromMore(api),
      ),
      TichMoreItem(
        label: 'Course evaluations',
        icon: Icons.rate_review_outlined,
        badgeCount: badge('evaluations'),
        onTapDown: (_) => StudentPrefetch.evaluations(api),
        onTap: () => openStudentEvaluationsFromMore(api),
      ),
      TichMoreItem(
        label: 'Deferment requests',
        icon: Icons.event_busy_outlined,
        badgeCount: badge('requests'),
        onTapDown: (_) => StudentPrefetch.lifecycle(api),
        onTap: () => openStudentLifecycleFromMore(api),
      ),
      TichMoreItem(
        label: 'Supplementary & special exams',
        icon: Icons.edit_note_outlined,
        badgeCount: badge('academics.exam-requests'),
        onTapDown: (_) => StudentPrefetch.examRequests(api),
        onTap: () => openStudentExamRequests(api),
      ),
      TichMoreItem(
        label: 'Suggestion box',
        icon: Icons.lightbulb_outline,
        badgeCount: badge('suggestions'),
        onTap: () => openStudentSuggestionsFromMore(api),
      ),
      TichMoreItem(
        label: 'Notifications',
        icon: Icons.notifications_outlined,
        badgeCount: badge('notifications'),
        onTapDown: (_) => StudentPrefetch.notifications(api),
        onTap: () => openStudentNotificationsFromMore(api),
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
