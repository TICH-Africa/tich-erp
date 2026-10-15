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

class StaffPortalShell extends StatefulWidget {
  const StaffPortalShell({super.key});

  @override
  State<StaffPortalShell> createState() => _StaffPortalShellState();
}

class _StaffPortalShellState extends State<StaffPortalShell> {
  int _index = 0;
  List<Widget>? _pages;

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
          label: 'Units',
          materialIcon: Icons.menu_book_outlined,
          materialActiveIcon: Icons.menu_book,
          cupertinoIcon: CupertinoIcons.book,
          cupertinoActiveIcon: CupertinoIcons.book_fill,
        ),
        AdaptiveTabItem(
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
