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
import '../widgets/tich_global_more_footer.dart';
import '../widgets/tich_more_drawer.dart';
import '../../notifications/tich_notification_router.dart';
import 'portal_nav_counts_scope.dart';
import 'staff_portal_nav.dart';
import 'shell_nav_badge_mixin.dart';
import 'tich_logout.dart';

class StaffPortalShell extends StatefulWidget {
  const StaffPortalShell({super.key});

  @override
  State<StaffPortalShell> createState() => _StaffPortalShellState();
}

class _StaffPortalShellState extends State<StaffPortalShell>
    with WidgetsBindingObserver, ShellNavBadgeMixin {
  final GlobalKey<AdaptiveTabScaffoldState> _scaffoldKey =
      GlobalKey<AdaptiveTabScaffoldState>();
  List<Widget>? _pages;
  Widget? _root;

  static const _moreMenuKeys = ['lesson-plans', 'exam-papers'];

  static const _homeKeys = [
    'lesson-plans',
    'attendance.incomplete',
    'leave.returned',
    'documents',
    'exam-papers',
  ];

  @override
  void initState() {
    super.initState();
    StaffPortalNav.isStaffShellActive = true;
    StaffPortalNav.onTabRequest = _applyPendingTab;
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _applyPendingTab();
      TichNotificationRouter.instance.applyPending(context);
    });
  }

  void _applyPendingTab() {
    if (!mounted) return;
    final tab = StaffPortalNav.consumePendingTab();
    if (tab != null) _scaffoldKey.currentState?.selectTab(tab);
  }

  @override
  void dispose() {
    StaffPortalNav.isStaffShellActive = false;
    if (StaffPortalNav.onTabRequest == _applyPendingTab) {
      StaffPortalNav.onTabRequest = null;
    }
    super.dispose();
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (_root != null) return;
    final api = context.read<StaffApi>();
    _pages = [
      StaffHomePage(api: api),
      StaffUnitsPage(api: api),
      StaffTimetablePage(api: api),
      StaffAttendancePage(api: api),
      StaffMorePage(api: api),
    ];
    _root = AdaptiveTabScaffold(
      key: _scaffoldKey,
      pages: _pages!,
      portalName: 'Staff portal',
      onSignOut: tichLogout(context),
      moreIndex: 4,
      moreDrawer: (context) => TichMoreDrawer(
        portalName: 'Staff portal',
        menuContext: TichPortalMenuContext.staff,
        items: StaffMorePage.mainItems(context, api),
      ),
      items: _tabItems(const {}),
    );
  }

  List<AdaptiveTabItem> _tabItems(Map<String, int> counts) {
    var home = 0;
    for (final key in _homeKeys) {
      home += counts[key] ?? 0;
    }
    var moreDot = 0;
    for (final key in _moreMenuKeys) {
      moreDot += counts[key] ?? 0;
    }

    return [
      AdaptiveTabItem(
        label: 'Home',
        materialIcon: Icons.home_outlined,
        materialActiveIcon: Icons.home,
        cupertinoIcon: CupertinoIcons.house,
        cupertinoActiveIcon: CupertinoIcons.house_fill,
        badgeCount: home,
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
        badgeCount: counts['attendance.incomplete'] ?? 0,
      ),
      AdaptiveTabItem(
        label: 'More',
        materialIcon: Icons.menu,
        materialActiveIcon: Icons.menu_open,
        cupertinoIcon: CupertinoIcons.ellipsis_circle,
        cupertinoActiveIcon: CupertinoIcons.ellipsis_circle_fill,
        badgeCount: moreDot,
        badgeDotOnly: true,
      ),
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
        _scaffoldKey.currentState?.updateNavCounts(counts);
        _scaffoldKey.currentState?.updateNavItems(_tabItems(counts));
      } catch (_) {}
    });
    return Future.value();
  }

  @override
  Widget build(BuildContext context) {
    return _root ?? const SizedBox.shrink();
  }
}
