import 'package:flutter/material.dart';
import '../../adaptive/adaptive.dart';
import '../../data/staff_api.dart';
import '../shell/portal_nav_counts_scope.dart';
import '../widgets/tich_global_more_footer.dart';
import '../widgets/tich_more_drawer.dart';
import 'staff_module_nav.dart';

class StaffMorePage extends StatelessWidget {
  const StaffMorePage({super.key, required this.api});

  final StaffApi api;

  static List<TichMoreItem> mainItems(BuildContext context, StaffApi api) {
    final nav = PortalNavCountsScope.maybeOf(context);

    int badge(String key) => nav?.countFor(key) ?? 0;

    final items = <TichMoreItem>[
      TichMoreItem(
        label: 'Marks & assessments',
        icon: Icons.grade_outlined,
        badgeCount: badge('exam-papers'),
        onTap: () => openStaffGradingFromMore(api),
      ),
      TichMoreItem(
        label: 'Lesson plans',
        icon: Icons.menu_book_outlined,
        badgeCount: badge('lesson-plans'),
        onTap: () => openStaffLessonPlansFromMore(api),
      ),
      TichMoreItem(
        label: 'Learning content',
        icon: Icons.layers_outlined,
        onTap: () => openStaffContentFromMore(api),
      ),
    ];

    return items;
  }

  static List<TichMoreItem> items(BuildContext context, StaffApi api) => [
        ...mainItems(context, api),
        ...tichMoreMenuFooter(context, TichPortalMenuContext.staff),
      ];

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'More',
      body: TichMoreList(items: items(context, api)),
    );
  }
}
