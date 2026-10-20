import 'package:flutter/material.dart';
import '../../adaptive/adaptive.dart';
import '../../data/employee_api.dart';
import '../shell/employee_portal_menu_scope.dart';
import '../shell/portal_nav_counts_scope.dart';
import '../widgets/tich_global_more_footer.dart';
import '../widgets/tich_more_drawer.dart';
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

  /// Portal-specific links (drawer footer adds Choose portal + Settings).
  static List<TichMoreItem> mainItems(BuildContext context, EmployeeApi api) {
    final nav = PortalNavCountsScope.maybeOf(context);

    void open(Widget page) =>
        Navigator.of(context).push(MaterialPageRoute(builder: (_) => page));

    int badge(String key) => nav?.countFor(key) ?? 0;

    return [
      TichMoreItem(
        label: 'My profile',
        icon: Icons.person_outline,
        badgeCount: badge('profile-changes'),
        showProfilePhoto: true,
        photoUrl: EmployeePortalMenuScope.photoUrlOf(context),
        onTap: () => open(ProfilePage(api: api)),
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

  static List<TichMoreItem> items(BuildContext context, EmployeeApi api) => [
        ...mainItems(context, api),
        ...tichMoreMenuFooter(context, TichPortalMenuContext.employee),
      ];

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'More',
      body: TichMoreList(items: items(context, api)),
    );
  }
}
