import 'dart:async';

import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../adaptive/adaptive.dart';
import '../../data/employee_api.dart';
import '../../data/employee_badge_service.dart';
import '../../data/tich_portal_warmup.dart';
import '../../data/tich_screen_cache.dart';
import '../portal/attendance_page.dart';
import '../portal/dashboard_page.dart';
import '../portal/leave_page.dart';
import '../portal/more_page.dart';
import '../portal/payslips_page.dart';
import '../portal/time_logs_page.dart';
import '../widgets/tich_global_more_footer.dart';
import '../widgets/tich_more_drawer.dart';
import '../../notifications/tich_notification_router.dart';
import '../../permissions/tich_permission_disclosures.dart';
import 'employee_portal_menu_scope.dart';
import 'employee_portal_nav.dart';
import 'shell_nav_badge_mixin.dart';
import 'tich_logout.dart';

import 'employee_portal_signals.dart';

class PortalShell extends StatefulWidget {
  const PortalShell({super.key});

  @override
  State<PortalShell> createState() => _PortalShellState();
}

class _PortalShellState extends State<PortalShell>
    with WidgetsBindingObserver, ShellNavBadgeMixin {
  static const _complianceCacheKey = 'portal.time_log_compliance';

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
  bool _timeLogsGate = false;

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
    EmployeePortalNavSignals.onTimeLogComplianceChanged =
        _recheckTimeLogCompliance;
    _applyCachedCompliance();
    _loadStaffPhoto();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      unawaited(_refreshTimeLogCompliance());
      _applyPendingTab();
      TichNotificationRouter.instance.applyPending(context);
      TichPermissionDisclosures.maybePromptForNotifications(context);
    });
  }

  void _applyCachedCompliance() {
    final cached =
        TichScreenCache.read<Map<String, dynamic>>(_complianceCacheKey);
    if (cached != null) {
      _timeLogsGate = cached['blocked'] == true;
    }
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
    if (EmployeePortalNavSignals.onTimeLogComplianceChanged ==
        _recheckTimeLogCompliance) {
      EmployeePortalNavSignals.onTimeLogComplianceChanged = null;
    }
    super.dispose();
  }

  Future<void> _refreshTimeLogCompliance() async {
    try {
      final data = await context.read<EmployeeApi>().timeLogCompliance();
      if (!mounted) return;
      TichScreenCache.write(_complianceCacheKey, {
        'blocked': data['blocked'] == true,
        if (data['hold'] != null) 'hold': data['hold'],
      });
      final blocked = data['blocked'] == true;
      if (blocked == _timeLogsGate) return;
      setState(() {
        _timeLogsGate = blocked;
        if (!blocked) {
          _ensureShellBuilt();
        } else {
          _root = null;
        }
      });
    } catch (_) {}
  }

  Future<void> _recheckTimeLogCompliance() async {
    await _refreshTimeLogCompliance();
  }

  void _loadStaffPhoto() {
    final cached = TichScreenCache.read<Map<String, dynamic>>('portal.dashboard');
    if (cached != null) {
      final staff = Map<String, dynamic>.from(cached['staff'] as Map? ?? {});
      final url = staff['photo_url']?.toString().trim();
      if (mounted) {
        setState(() {
          _profilePhotoUrl = (url != null && url.isNotEmpty) ? url : null;
          _staffPhotoLoaded = true;
        });
      }
      return;
    }
    if (mounted && !_staffPhotoLoaded) {
      setState(() => _staffPhotoLoaded = true);
    }
  }

  void _ensureShellBuilt() {
    if (_root != null || _timeLogsGate) {
      return;
    }
    final api = context.read<EmployeeApi>();
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

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    _ensureShellBuilt();
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

  void _applyBadgeSnapshot(EmployeeBadgeSnapshot snap) {
    var moreDot = 0;
    for (final key in _moreMenuKeys) {
      moreDot += snap.sidebarCounts[key] ?? 0;
    }
    _counts = snap.sidebarCounts;
    _homeBadge = snap.notificationTotal;
    _leaveBadge = snap.sidebarCounts['leave.returned'] ?? 0;
    _clockBadge = snap.sidebarCounts['attendance_clock_in'] ?? 0;
    _clockPulse = snap.clockPulse;
    _moreDot = moreDot;
    _pushNavBadgesToScaffold();
  }

  @override
  Future<void> refreshNavBadges() {
    scheduleNavBadgeFetch((force) async {
      if (!mounted) return;
      final badges = context.read<EmployeeBadgeService>();
      await badges.refresh(force: force);
      if (!mounted) return;
      final snap = badges.snapshot;
      if (snap != null) {
        _applyBadgeSnapshot(snap);
      }
    });
    return Future.value();
  }

  @override
  Widget build(BuildContext context) {
    if (_root == null && !_timeLogsGate) {
      _ensureShellBuilt();
    }

    if (_timeLogsGate) {
      final api = context.read<EmployeeApi>();
      return EmployeePortalMenuScope(
        profilePhotoUrl: _profilePhotoUrl,
        child: AdaptiveScaffold(
          title: 'Weekly time log',
          onLogout: tichLogout(context),
          body: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 16, 16, 0),
                child: Text(
                  'Complete and submit your weekly time logs for this month '
                  'before using other employee portal sections.',
                  style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                        color: Theme.of(context).colorScheme.error,
                      ),
                ),
              ),
              Expanded(child: TimeLogsPage(api: api)),
            ],
          ),
        ),
      );
    }

    return EmployeePortalMenuScope(
      profilePhotoUrl: _profilePhotoUrl,
      child: _root!,
    );
  }
}
