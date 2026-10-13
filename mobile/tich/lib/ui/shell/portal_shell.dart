import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:google_fonts/google_fonts.dart';

import '../../bloc/auth/auth_bloc.dart';
import '../../bloc/auth/auth_event.dart';
import '../../data/employee_api.dart';
import '../../theme/tich_theme.dart';
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

  @override
  Widget build(BuildContext context) {
    final api = context.read<EmployeeApi>();
    final pages = [
      DashboardPage(api: api),
      LeavePage(api: api),
      AttendancePage(api: api),
      PayslipsPage(api: api),
      MorePage(api: api),
    ];

    return Scaffold(
      body: IndexedStack(index: _index, children: pages),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _index,
        onDestinationSelected: (i) => setState(() => _index = i),
        destinations: const [
          NavigationDestination(
            icon: Icon(Icons.home_outlined),
            selectedIcon: Icon(Icons.home),
            label: 'Home',
          ),
          NavigationDestination(
            icon: Icon(Icons.event_available_outlined),
            selectedIcon: Icon(Icons.event_available),
            label: 'Leave',
          ),
          NavigationDestination(
            icon: Icon(Icons.fingerprint_outlined),
            selectedIcon: Icon(Icons.fingerprint),
            label: 'Clock',
          ),
          NavigationDestination(
            icon: Icon(Icons.payments_outlined),
            selectedIcon: Icon(Icons.payments),
            label: 'Payslips',
          ),
          NavigationDestination(
            icon: Icon(Icons.menu),
            selectedIcon: Icon(Icons.menu_open),
            label: 'More',
          ),
        ],
      ),
    );
  }
}

PreferredSizeWidget tichAppBar(
  BuildContext context, {
  required String title,
  List<Widget>? actions,
  bool showLogout = false,
}) {
  return AppBar(
    title: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'TICH',
          style: GoogleFonts.merriweather(
            fontSize: 12,
            fontWeight: FontWeight.w700,
            color: TichColors.greenLight,
            letterSpacing: 1.2,
          ),
        ),
        Text(title),
      ],
    ),
    actions: [
      ...?actions,
      if (showLogout)
        IconButton(
          tooltip: 'Sign out',
          onPressed: () =>
              context.read<AuthBloc>().add(const AuthLogoutRequested()),
          icon: const Icon(Icons.logout),
        ),
    ],
  );
}
