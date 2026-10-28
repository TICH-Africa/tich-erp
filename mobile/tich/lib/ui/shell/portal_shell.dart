import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../adaptive/adaptive.dart';
import '../../data/employee_api.dart';
import '../../data/tich_portal_warmup.dart';
import '../../data/tich_screen_cache.dart';
import '../portal/attendance_page.dart';
import '../portal/dashboard_page.dart';
import '../portal/leave_page.dart';
import '../portal/more_page.dart';
import '../portal/payslips_page.dart';
import '../widgets/tich_global_more_footer.dart';
import '../widgets/tich_more_drawer.dart';
import '../../notifications/tich_notification_router.dart';
import '../../permissions/tich_permission_disclosures.dart';
import 'employee_clock_nav.dart';
import 'employee_portal_menu_scope.dart';
import 'employee_portal_nav.dart';
import 'portal_nav_counts_scope.dart';
import 'shell_nav_badge_mixin.dart';
import 'tich_logout.dart';

/// Called after clock-in/out so the bottom nav pulse updates immediately.
abstract final class EmployeePortalNavSignals {
  static VoidCallback? onAttendanceChanged;
  static VoidCallback? onStaffPhotoChanged;
}

class PortalShell extends StatefulWidget {
  const PortalShell({super.key});

  @override
  State<PortalShell> createState() => _PortalShellState();
}

class _PortalShellState extends State<PortalShell>
    with WidgetsBindingObserver, ShellNavBadgeMixin {
  final GlobalKey<AdaptiveTabScaffoldState> _scaffoldKey =
      GlobalKey<AdaptiveTabScaffoldState>();
  List<Widget>? _pages;
  Widget? _root;
  Map<String, int> _counts = const {};
  int _homeBadge = 0;
  int _leaveBadge = 0;
  int _clockBadge = 0;
  bool _clockPulse = false;
  int _moreDot = 0;
  String? _profilePhotoUrl;
  bool _staffPhotoLoaded = false;

  static const _moreMenuKeys = [
    'profile-changes',
    'policies',
    'appraisals',
    'concerns',
    'feedback',
  ];

  @override
  void initState() {
    super.initState();
    EmployeePortalNav.isEmployeeShellActive = true;
    EmployeePortalNav.onTabRequest = _applyPendingTab;
    EmployeePortalNavSignals.onAttendanceChanged = refreshNavBadges;
    EmployeePortalNavSignals.onStaffPhotoChanged = _loadStaffPhoto;
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _applyPendingTab();
      TichNotificationRouter.instance.applyPending(context);
      TichPermissionDisclosures.maybePromptForNotifications(context);
    });
  }

  void _applyPendingTab() {
    if (!mounted) return;
    final tab = EmployeePortalNav.consumePendingTab();
    if (tab != null) {
      _scaffoldKey.currentState?.selectTab(tab);
      if (tab == 2) refreshNavBadges();
    }
  }

  @override
  void dispose() {
    EmployeePortalNav.isEmployeeShellActive = false;
    if (EmployeePortalNav.onTabRequest == _applyPendingTab) {
      EmployeePortalNav.onTabRequest = null;
    }
    if (EmployeePortalNavSignals.onAttendanceChanged == refreshNavBadges) {
      EmployeePortalNavSignals.onAttendanceChanged = null;
    }
    if (EmployeePortalNavSignals.onStaffPhotoChanged == _loadStaffPhoto) {
      EmployeePortalNavSignals.onStaffPhotoChanged = null;
    }
    super.dispose();
  }

  Future<void> _loadStaffPhoto() async {
    if (!mounted) return;
    final api = context.read<EmployeeApi>();
    try {
      final data = await TichScreenCache.ensure<Map<String, dynamic>>(
        'portal.dashboard',
        () => api.dashboard(),
      );
      if (!mounted) return;
      final staff = Map<String, dynamic>.from(data['staff'] as Map? ?? {});
      final url = staff['photo_url']?.toString().trim();
      setState(() {
        _profilePhotoUrl = (url != null && url.isNotEmpty) ? url : null;
        _staffPhotoLoaded = true;
      });
    } catch (_) {
      if (mounted) setState(() => _staffPhotoLoaded = true);
    }
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (_root != null) return;
    final api = context.read<EmployeeApi>();
    if (!_staffPhotoLoaded) {
      _loadStaffPhoto();
    }
    _pages = [
      DashboardPage(api: api),
      LeavePage(api: api),
      AttendancePage(api: api),
      PayslipsPage(api: api),
      MorePage(api: api),
    ];
    TichPortalWarmup.employeeShellOpened(api);
    _root = AdaptiveTabScaffold(
      key: _scaffoldKey,
      pages: _pages!,
      portalName: 'Employee portal',
      onSignOut: tichLogout(context),
      moreIndex: 4,
      onTabSelected: (i) {
        TichPortalWarmup.employeeTabSelected(api, i);
        if (i == 2) refreshNavBadges();
      },
      onMoreOpen: () => TichPortalWarmup.employeeMoreOpened(api),
      moreDrawer: (context) => TichMoreDrawer(
        portalName: 'Employee portal',
        menuContext: TichPortalMenuContext.employee,
        items: MorePage.mainItems(context, api),
      ),
      items: _tabItems(),
    );
  }

  List<AdaptiveTabItem> _tabItems() {
    final showClockPulse = _clockPulse;
    final showClockCount = !showClockPulse && _clockBadge > 0;
    return [
      AdaptiveTabItem(
        label: 'Home',
        materialIcon: Icons.home_outlined,
        materialActiveIcon: Icons.home,
        cupertinoIcon: CupertinoIcons.house,
        cupertinoActiveIcon: CupertinoIcons.house_fill,
        badgeCount: _homeBadge,
      ),
      AdaptiveTabItem(
        label: 'Leave',
        materialIcon: Icons.event_available_outlined,
        materialActiveIcon: Icons.event_available,
        cupertinoIcon: CupertinoIcons.calendar,
        cupertinoActiveIcon: CupertinoIcons.calendar_today,
        badgeCount: _leaveBadge,
      ),
      AdaptiveTabItem(
        label: 'Clock in',
        materialIcon: Icons.fingerprint_outlined,
        materialActiveIcon: Icons.fingerprint,
        cupertinoIcon: CupertinoIcons.clock,
        cupertinoActiveIcon: CupertinoIcons.clock_fill,
        badgeCount: showClockPulse ? 1 : (showClockCount ? _clockBadge : 0),
        badgeDotOnly: showClockPulse,
        badgePulse: showClockPulse,
      ),
      const AdaptiveTabItem(
        label: 'Payslips',
        materialIcon: Icons.payments_outlined,
        materialActiveIcon: Icons.payments,
        cupertinoIcon: CupertinoIcons.money_dollar,
        cupertinoActiveIcon: CupertinoIcons.money_dollar_circle_fill,
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
    ];
  }

  void _pushNavBadgesToScaffold() {
    _scaffoldKey.currentState?.updateNavCounts(_counts);
    _scaffoldKey.currentState?.updateNavItems(_tabItems());
  }

  @override
  Future<void> refreshNavBadges() {
    scheduleNavBadgeFetch(() async {
      if (!mounted) return;
      final api = context.read<EmployeeApi>();

      var clockPulse = _clockPulse;
      try {
        final attendance = await api.attendance();
        if (mounted) {
          clockPulse = employeeNeedsClockInPulse(attendance);
        }
      } catch (_) {}

      try {
        final data = await api.notifications();
        if (!mounted) return;
        final counts = parseSidebarCounts(data['counts']);
        final total = (data['total'] as num?)?.toInt() ?? 0;
        var moreDot = 0;
        for (final key in _moreMenuKeys) {
          moreDot += counts[key] ?? 0;
        }
        _counts = counts;
        _homeBadge = total;
        _leaveBadge = counts['leave.returned'] ?? 0;
        _clockBadge = counts['attendance_clock_in'] ?? 0;
        _clockPulse = clockPulse;
        _moreDot = moreDot;
        _pushNavBadgesToScaffold();
      } catch (_) {
        if (mounted) {
          _clockPulse = clockPulse;
          _pushNavBadgesToScaffold();
        }
      }
    });
    return Future.value();
  }

  @override
  Widget build(BuildContext context) {
    return EmployeePortalMenuScope(
      profilePhotoUrl: _profilePhotoUrl,
      child: _root ?? const SizedBox.shrink(),
    );
  }
}
