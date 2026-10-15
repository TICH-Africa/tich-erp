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

class PortalShell extends StatefulWidget {
  const PortalShell({super.key});

  @override
  State<PortalShell> createState() => _PortalShellState();
}

class _PortalShellState extends State<PortalShell> {
  int _index = 0;
  List<Widget>? _pages;

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
  Widget build(BuildContext context) {
    return AdaptiveTabScaffold(
      currentIndex: _index,
      onTap: (i) => setState(() => _index = i),
      pages: _pages!,
      items: const [
        AdaptiveTabItem(
          label: 'Home',
          materialIcon: Icons.home_outlined,
          materialActiveIcon: Icons.home,
          cupertinoIcon: CupertinoIcons.house,
          cupertinoActiveIcon: CupertinoIcons.house_fill,
        ),
        AdaptiveTabItem(
          label: 'Leave',
          materialIcon: Icons.event_available_outlined,
          materialActiveIcon: Icons.event_available,
          cupertinoIcon: CupertinoIcons.calendar,
          cupertinoActiveIcon: CupertinoIcons.calendar_today,
        ),
        AdaptiveTabItem(
          label: 'Clock',
          materialIcon: Icons.fingerprint_outlined,
          materialActiveIcon: Icons.fingerprint,
          cupertinoIcon: CupertinoIcons.clock,
          cupertinoActiveIcon: CupertinoIcons.clock_fill,
        ),
        AdaptiveTabItem(
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
        ),
      ],
    );
  }
}

/// Shared logout callback used by AdaptiveScaffold pages.
VoidCallback tichLogout(BuildContext context) {
  return () => context.read<AuthBloc>().add(const AuthLogoutRequested());
}
