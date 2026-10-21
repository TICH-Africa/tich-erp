import 'dart:async';

import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../adaptive/adaptive.dart';
import '../../data/student_api.dart';
import '../../data/tich_portal_warmup.dart';
import '../student/academics_hub_page.dart';
import '../student/finance_page.dart';
import '../student/home_page.dart';
import '../student/more_page.dart';
import '../student/timetable_page.dart';
import '../widgets/tich_global_more_footer.dart';
import '../widgets/tich_more_drawer.dart';
import 'portal_nav_counts_scope.dart';
import 'shell_nav_badge_mixin.dart';
import 'student_portal_menu_scope.dart';
import 'tich_logout.dart';

class StudentPortalShell extends StatefulWidget {
  const StudentPortalShell({super.key});

  @override
  State<StudentPortalShell> createState() => _StudentPortalShellState();
}

class _StudentPortalShellState extends State<StudentPortalShell>
    with WidgetsBindingObserver, ShellNavBadgeMixin {
  final GlobalKey<AdaptiveTabScaffoldState> _scaffoldKey =
      GlobalKey<AdaptiveTabScaffoldState>();
  List<Widget>? _pages;
  Widget? _root;
  String? _profilePhotoUrl;
  bool _profilePhotoLoaded = false;

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
    if (_root != null) return;
    final api = context.read<StudentApi>();
    if (!_profilePhotoLoaded) {
      unawaited(_loadProfilePhoto(api));
    }
    _pages = [
      StudentHomePage(api: api),
      StudentAcademicsHubPage(api: api),
      StudentTimetablePage(api: api),
      StudentFinancePage(api: api),
      StudentMorePage(api: api),
    ];
    TichPortalWarmup.student(api);
    _root = AdaptiveTabScaffold(
      key: _scaffoldKey,
      pages: _pages!,
      portalName: 'Student portal',
      onSignOut: tichLogout(context),
      moreIndex: 4,
      moreDrawer: (context) => TichMoreDrawer(
        portalName: 'Student portal',
        menuContext: TichPortalMenuContext.student,
        items: StudentMorePage.mainItems(context, api),
      ),
      items: _tabItems(counts: const {}),
    );
  }

  List<AdaptiveTabItem> _tabItems({required Map<String, int> counts}) {
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

    return [
      AdaptiveTabItem(
        label: 'Home',
        materialIcon: Icons.home_outlined,
        materialActiveIcon: Icons.home,
        cupertinoIcon: CupertinoIcons.house,
        cupertinoActiveIcon: CupertinoIcons.house_fill,
        badgeCount: counts['overview'] ?? counts['notifications'] ?? 0,
      ),
      AdaptiveTabItem(
        label: 'Academics',
        materialIcon: Icons.school_outlined,
        materialActiveIcon: Icons.school,
        cupertinoIcon: CupertinoIcons.book,
        cupertinoActiveIcon: CupertinoIcons.book_fill,
        badgeCount: academics,
      ),
      AdaptiveTabItem(
        label: 'Timetable',
        materialIcon: Icons.calendar_month_outlined,
        materialActiveIcon: Icons.calendar_month,
        cupertinoIcon: CupertinoIcons.calendar,
        cupertinoActiveIcon: CupertinoIcons.calendar_today,
        badgeCount: timetable,
      ),
      AdaptiveTabItem(
        label: 'Finance',
        materialIcon: Icons.account_balance_wallet_outlined,
        materialActiveIcon: Icons.account_balance_wallet,
        cupertinoIcon: CupertinoIcons.money_dollar,
        cupertinoActiveIcon: CupertinoIcons.money_dollar_circle_fill,
        badgeCount: counts['finance'] ?? 0,
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

  Future<void> _loadProfilePhoto(StudentApi api) async {
    try {
      final data = await api.dashboard();
      if (!mounted) return;
      final student =
          Map<String, dynamic>.from(data['student'] as Map? ?? {});
      final url = student['photo_url']?.toString().trim();
      setState(() {
        _profilePhotoUrl = (url != null && url.isNotEmpty) ? url : null;
        _profilePhotoLoaded = true;
      });
    } catch (_) {
      if (mounted) setState(() => _profilePhotoLoaded = true);
    }
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
        _scaffoldKey.currentState?.updateNavCounts(counts);
        _scaffoldKey.currentState?.updateNavItems(_tabItems(counts: counts));
      } catch (_) {}
    });
    return Future.value();
  }

  @override
  Widget build(BuildContext context) {
    return StudentPortalMenuScope(
      profilePhotoUrl: _profilePhotoUrl,
      child: _root ?? const SizedBox.shrink(),
    );
  }
}
