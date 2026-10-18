import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../adaptive/adaptive.dart';
import '../../data/staff_api.dart';
import '../staff/attendance_page.dart';
import '../staff/home_page.dart';
import '../staff/more_page.dart';
import '../staff/timetable_page.dart';
import '../staff/units_page.dart';
import 'portal_nav_counts_scope.dart';
import 'shell_nav_badge_mixin.dart';

class StaffPortalShell extends StatefulWidget {
  const StaffPortalShell({super.key});

  @override
  State<StaffPortalShell> createState() => _StaffPortalShellState();
}

class _StaffPortalShellState extends State<StaffPortalShell>
    with WidgetsBindingObserver, ShellNavBadgeMixin {
  int _index = 0;
  List<Widget>? _pages;
  Map<String, int> _counts = const {};
  int _homeBadge = 0;
  int _attendanceBadge = 0;
  int _moreDot = 0;

  static const _moreMenuKeys = ['lesson-plans', 'exam-papers'];

  static const _homeKeys = [
    'lesson-plans',
    'attendance.incomplete',
    'leave.returned',
    'documents',
    'exam-papers',
  ];

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (_pages != null) return;
    final api = context.read<StaffApi>();
    _pages = [
      StaffHomePage(api: api),
      StaffUnitsPage(api: api),
      StaffTimetablePage(api: api),
      StaffAttendancePage(api: api),
      StaffMorePage(api: api),
    ];
  }

  @override
  Future<void> refreshNavBadges() {
    scheduleNavBadgeFetch(() async {
      if (!mounted) return;
      final api = context.read<StaffApi>();
      try {
        final data = await api.notifications();
        if (!mounted) return;
        final counts = parseSidebarCounts(data['counts']);
        var home = 0;
        for (final key in _homeKeys) {
          home += counts[key] ?? 0;
        }
        var moreDot = 0;
        for (final key in _moreMenuKeys) {
          moreDot += counts[key] ?? 0;
        }
        setState(() {
          _counts = counts;
          _homeBadge = home;
          _attendanceBadge = counts['attendance.incomplete'] ?? 0;
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
        items: [
          AdaptiveTabItem(
            label: 'Home',
            materialIcon: Icons.home_outlined,
            materialActiveIcon: Icons.home,
            cupertinoIcon: CupertinoIcons.house,
            cupertinoActiveIcon: CupertinoIcons.house_fill,
            badgeCount: _homeBadge,
          ),
          const AdaptiveTabItem(
            label: 'Units',
            materialIcon: Icons.menu_book_outlined,
            materialActiveIcon: Icons.menu_book,
            cupertinoIcon: CupertinoIcons.book,
            cupertinoActiveIcon: CupertinoIcons.book_fill,
          ),
          const AdaptiveTabItem(
            label: 'Timetable',
            materialIcon: Icons.calendar_month_outlined,
            materialActiveIcon: Icons.calendar_month,
            cupertinoIcon: CupertinoIcons.calendar,
            cupertinoActiveIcon: CupertinoIcons.calendar_today,
          ),
          AdaptiveTabItem(
            label: 'Attendance',
            materialIcon: Icons.fact_check_outlined,
            materialActiveIcon: Icons.fact_check,
            cupertinoIcon: CupertinoIcons.checkmark_seal,
            cupertinoActiveIcon: CupertinoIcons.checkmark_seal_fill,
            badgeCount: _attendanceBadge,
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
