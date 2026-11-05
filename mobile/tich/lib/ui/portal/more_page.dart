import 'package:flutter/material.dart';
import '../../adaptive/adaptive.dart';
import '../../data/employee_api.dart';
import '../../navigation/tich_root_nav.dart';
import '../shell/employee_portal_menu_scope.dart';
import '../shell/portal_nav_counts_scope.dart';
import '../widgets/tich_global_more_footer.dart';
import '../widgets/tich_more_drawer.dart';
import 'employee_module_nav.dart';
import 'employee_prefetch.dart';
import 'wfh_page.dart';

class MorePage extends StatelessWidget {
  const MorePage({super.key, required this.api});

  final EmployeeApi api;

  /// Portal-specific links (drawer footer adds Choose portal + Settings).
  static List<TichMoreItem> mainItems(BuildContext context, EmployeeApi api) {
    final nav = PortalNavCountsScope.maybeOf(context);

    int badge(String key) => nav?.countFor(key) ?? 0;

    return [
      TichMoreItem(
        label: 'My profile',
        icon: Icons.person_outline,
        badgeCount: badge('profile-changes'),
        showProfilePhoto: true,
        photoUrl: EmployeePortalMenuScope.photoUrlOf(context),
        onTapDown: (_) => EmployeePrefetch.profile(api),
        onTap: () => openEmployeeProfile(api),
      ),
      TichMoreItem(
        label: 'Documents',
        icon: Icons.folder_outlined,
        onTapDown: (_) => EmployeePrefetch.documents(api),
        onTap: () => openEmployeeDocuments(api),
      ),
      TichMoreItem(
        label: 'HR policies',
        icon: Icons.policy_outlined,
        badgeCount: badge('policies'),
        onTapDown: (_) => EmployeePrefetch.policies(api),
        onTap: () => openEmployeePolicies(api),
      ),
      TichMoreItem(
        label: 'Work from home',
        icon: Icons.home_work_outlined,
        onTap: () => TichRootNav.push(const WfhPage()),
      ),
      TichMoreItem(
        label: 'Time sheets',
        icon: Icons.schedule_outlined,
        onTapDown: (_) => EmployeePrefetch.timeLogs(api),
        onTap: () => openEmployeeTimeLogs(api),
      ),
      TichMoreItem(
        label: 'Appraisals',
        icon: Icons.flag_outlined,
        badgeCount: badge('appraisals'),
        onTapDown: (_) => EmployeePrefetch.appraisals(api),
        onTap: () => openEmployeeAppraisals(api),
      ),
      TichMoreItem(
        label: 'Concerns',
        icon: Icons.report_problem_outlined,
        badgeCount: badge('concerns'),
        onTapDown: (_) => EmployeePrefetch.concerns(api),
        onTap: () => openEmployeeConcerns(api),
      ),
      TichMoreItem(
        label: 'Feedback',
        icon: Icons.feedback_outlined,
        badgeCount: badge('feedback'),
        onTapDown: (_) => EmployeePrefetch.feedback(api),
        onTap: () => openEmployeeFeedback(api),
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
