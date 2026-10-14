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

class StudentPortalShell extends StatefulWidget {
  const StudentPortalShell({super.key});

  @override
  State<StudentPortalShell> createState() => _StudentPortalShellState();
}

class _StudentPortalShellState extends State<StudentPortalShell> {
  int _index = 0;

  @override
  Widget build(BuildContext context) {
    final api = context.read<StudentApi>();
    final pages = [
      StudentHomePage(api: api),
      StudentAcademicsHubPage(api: api),
      StudentTimetablePage(api: api),
      StudentFinancePage(api: api),
      StudentMorePage(api: api),
    ];

    return AdaptiveTabScaffold(
      currentIndex: _index,
      onTap: (i) => setState(() => _index = i),
      pages: pages,
      items: const [
        AdaptiveTabItem(
          label: 'Home',
          materialIcon: Icons.home_outlined,
          materialActiveIcon: Icons.home,
          cupertinoIcon: CupertinoIcons.house,
          cupertinoActiveIcon: CupertinoIcons.house_fill,
        ),
        AdaptiveTabItem(
          label: 'Academics',
          materialIcon: Icons.school_outlined,
          materialActiveIcon: Icons.school,
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
          label: 'Finance',
          materialIcon: Icons.account_balance_wallet_outlined,
          materialActiveIcon: Icons.account_balance_wallet,
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
