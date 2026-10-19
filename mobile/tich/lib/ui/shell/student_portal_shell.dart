import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../adaptive/adaptive.dart';
import '../../data/student_api.dart';
import '../student/academics_hub_page.dart';
import '../student/finance_page.dart';
import '../student/home_page.dart';
import '../student/more_page.dart';
import '../student/timetable_page.dart';
import '../widgets/tich_more_drawer.dart';
import 'portal_nav_counts_scope.dart';
import 'shell_nav_badge_mixin.dart';
import 'tich_logout.dart';

class StudentPortalShell extends StatefulWidget {
  const StudentPortalShell({super.key});

  @override
  State<StudentPortalShell> createState() => _StudentPortalShellState();
}

class _StudentPortalShellState extends State<StudentPortalShell>
    with WidgetsBindingObserver, ShellNavBadgeMixin {
  int _index = 0;
  List<Widget>? _pages;
  Map<String, int> _counts = const {};
  int _homeBadge = 0;
  int _academicsBadge = 0;
  int _timetableBadge = 0;
  int _financeBadge = 0;
  int _moreDot = 0;

  static const _moreMenuKeys = [
    'profile',
    'enrolment',
    'documents',
    'clearance',
    'evaluations',
    'requests',
    'notifications',
    'suggestions',
  ];

  static const _academicsKeys = [
    'academics',
    'academics.units',
    'academics.content',
    'academics.assessments',
    'academics.exams',
    'academics.exam-requests',
    'academics.progress',
    'academics.eligibility',
    'academics.calendar',
    'academics.registration',
    'academics.attendance',
  ];

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (_pages != null) return;
    final api = context.read<StudentApi>();
    _pages = [
      StudentHomePage(api: api),
      StudentAcademicsHubPage(api: api),
      StudentTimetablePage(api: api),
      StudentFinancePage(api: api),
      StudentMorePage(api: api),
    ];
  }

  @override
  Future<void> refreshNavBadges() {
    scheduleNavBadgeFetch(() async {
      if (!mounted) return;
      final api = context.read<StudentApi>();
      try {
        final data = await api.sidebarNotifications();
        if (!mounted) return;
        final counts = parseSidebarCounts(data['counts']);
        var academics = 0;
        for (final key in _academicsKeys) {
          academics += counts[key] ?? 0;
        }
        var moreDot = 0;
        for (final key in _moreMenuKeys) {
          moreDot += counts[key] ?? 0;
        }
        final timetable = (counts['timetable'] ?? 0) +
            (counts['timetable.lesson'] ?? 0) +
            (counts['timetable.exam'] ?? 0);
        setState(() {
          _counts = counts;
          _homeBadge = counts['overview'] ?? counts['notifications'] ?? 0;
          _academicsBadge = academics;
          _timetableBadge = timetable;
          _financeBadge = counts['finance'] ?? 0;
          _moreDot = moreDot;
        });
      } catch (_) {}
    });
    return Future.value();
  }

  @override
  Widget build(BuildContext context) {
    return PortalNavCountsScope(
      counts: _counts,
      child: AdaptiveTabScaffold(
        currentIndex: _index,
        onTap: (i) => setState(() => _index = i),
        pages: _pages!,
        portalName: 'Student portal',
        onSignOut: tichLogout(context),
        moreIndex: 4,
        moreDrawer: (context) => TichMoreDrawer(
          portalName: 'Student portal',
          items: StudentMorePage.items(context, context.read<StudentApi>()),
        ),
        items: [
          AdaptiveTabItem(
            label: 'Home',
            materialIcon: Icons.home_outlined,
            materialActiveIcon: Icons.home,
            cupertinoIcon: CupertinoIcons.house,
            cupertinoActiveIcon: CupertinoIcons.house_fill,
            badgeCount: _homeBadge,
          ),
          AdaptiveTabItem(
            label: 'Academics',
            materialIcon: Icons.school_outlined,
            materialActiveIcon: Icons.school,
            cupertinoIcon: CupertinoIcons.book,
            cupertinoActiveIcon: CupertinoIcons.book_fill,
            badgeCount: _academicsBadge,
          ),
          AdaptiveTabItem(
            label: 'Timetable',
            materialIcon: Icons.calendar_month_outlined,
            materialActiveIcon: Icons.calendar_month,
            cupertinoIcon: CupertinoIcons.calendar,
            cupertinoActiveIcon: CupertinoIcons.calendar_today,
            badgeCount: _timetableBadge,
          ),
          AdaptiveTabItem(
            label: 'Finance',
            materialIcon: Icons.account_balance_wallet_outlined,
            materialActiveIcon: Icons.account_balance_wallet,
            cupertinoIcon: CupertinoIcons.money_dollar,
            cupertinoActiveIcon: CupertinoIcons.money_dollar_circle_fill,
            badgeCount: _financeBadge,
          ),
          AdaptiveTabItem(
            label: 'More',
            materialIcon: Icons.menu,
            materialActiveIcon: Icons.menu_open,
            cupertinoIcon: CupertinoIcons.ellipsis_circle,
            cupertinoActiveIcon: CupertinoIcons.ellipsis_circle_fill,
            badgeCount: _moreDot,
            badgeDotOnly: true,
          ),
        ],
      ),
    );
  }
}
