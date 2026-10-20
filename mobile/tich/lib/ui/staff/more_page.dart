import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../adaptive/adaptive.dart';
import '../../bloc/auth/auth_bloc.dart';
import '../../bloc/auth/auth_event.dart';
import '../../data/staff_api.dart';
import '../shell/portal_nav_counts_scope.dart';
import '../widgets/tich_global_more_footer.dart';
import '../widgets/tich_more_drawer.dart';
import 'content_page.dart';
import 'grading_page.dart';
import 'lesson_plans_page.dart';

class StaffMorePage extends StatelessWidget {
  const StaffMorePage({super.key, required this.api});

  final StaffApi api;

  static List<TichMoreItem> mainItems(BuildContext context, StaffApi api) {
    final nav = PortalNavCountsScope.maybeOf(context);

    void open(Widget page) =>
        Navigator.of(context).push(MaterialPageRoute(builder: (_) => page));

    int badge(String key) => nav?.countFor(key) ?? 0;

    final items = <TichMoreItem>[
      TichMoreItem(
        label: 'Marks & assessments',
        icon: Icons.grade_outlined,
        badgeCount: badge('exam-papers'),
        onTap: () => open(StaffGradingPage(api: api)),
      ),
      TichMoreItem(
        label: 'Lesson plans',
        icon: Icons.menu_book_outlined,
        badgeCount: badge('lesson-plans'),
        onTap: () => open(StaffLessonPlansPage(api: api)),
      ),
      TichMoreItem(
        label: 'Learning content',
        icon: Icons.layers_outlined,
        onTap: () => open(StaffContentPage(api: api)),
      ),
      TichMoreItem(
        label: 'Sign out',
        icon: Icons.logout,
        onTap: () => context.read<AuthBloc>().add(const AuthLogoutRequested()),
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
