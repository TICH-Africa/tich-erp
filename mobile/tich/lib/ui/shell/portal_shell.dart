import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../adaptive/adaptive.dart';
import '../../bloc/auth/auth_bloc.dart';
import '../../bloc/auth/auth_event.dart';
import '../../data/employee_api.dart';
import '../portal/attendance_page.dart';
import '../portal/dashboard_page.dart';
import '../portal/leave_page.dart';
import '../portal/more_page.dart';
import '../portal/payslips_page.dart';
import 'employee_clock_nav.dart';
import 'portal_nav_counts_scope.dart';
import 'shell_nav_badge_mixin.dart';

/// Called after clock-in/out so the bottom nav pulse updates immediately.
abstract final class EmployeePortalNavSignals {
  static VoidCallback? onAttendanceChanged;
}

class PortalShell extends StatefulWidget {
  const PortalShell({super.key});

  @override
  State<PortalShell> createState() => _PortalShellState();
}

class _PortalShellState extends State<PortalShell>
    with WidgetsBindingObserver, ShellNavBadgeMixin {
  int _index = 0;
  List<Widget>? _pages;
  Map<String, int> _counts = const {};
  int _homeBadge = 0;
  int _leaveBadge = 0;
  int _clockBadge = 0;
  bool _clockPulse = false;
  int _moreDot = 0;

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
    EmployeePortalNavSignals.onAttendanceChanged = refreshNavBadges;
  }

  @override
  void dispose() {
    if (EmployeePortalNavSignals.onAttendanceChanged == refreshNavBadges) {
      EmployeePortalNavSignals.onAttendanceChanged = null;
    }
    super.dispose();
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (_pages != null) return;
    final api = context.read<EmployeeApi>();
    _pages = [
      DashboardPage(api: api),
      LeavePage(api: api),
      AttendancePage(api: api),
      PayslipsPage(api: api),
      MorePage(api: api),
    ];
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
        setState(() {
          _counts = counts;
          _homeBadge = total;
          _leaveBadge = counts['leave.returned'] ?? 0;
          _clockBadge = counts['attendance_clock_in'] ?? 0;
          _clockPulse = clockPulse;
          _moreDot = moreDot;
        });
      } catch (_) {
        if (mounted) {
          setState(() => _clockPulse = clockPulse);
        }
      }
    });
    return Future.value();
  }

  @override
  Widget build(BuildContext context) {
    final showClockPulse = _clockPulse;
    final showClockCount = !showClockPulse && _clockBadge > 0;

    return PortalNavCountsScope(
      counts: _counts,
      child: AdaptiveTabScaffold(
        currentIndex: _index,
        onTap: (i) {
          setState(() => _index = i);
          if (i == 2) {
            refreshNavBadges();
          }
        },
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
        ],
      ),
    );
  }
}

/// Shared logout callback used by AdaptiveScaffold pages.
VoidCallback tichLogout(BuildContext context) {
  return () => context.read<AuthBloc>().add(const AuthLogoutRequested());
}
