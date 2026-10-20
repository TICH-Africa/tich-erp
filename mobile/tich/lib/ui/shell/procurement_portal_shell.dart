import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../adaptive/adaptive.dart';
import '../../data/procurement_api.dart';
import '../procurement/procurement_badge_keys.dart';
import '../procurement/procurement_home_page.dart';
import '../procurement/procurement_more_page.dart';
import '../widgets/tich_more_drawer.dart';
import 'portal_nav_counts_scope.dart';
import 'procurement_nav_signals.dart';
import 'shell_nav_badge_mixin.dart';
import 'tich_logout.dart';

class ProcurementPortalShell extends StatefulWidget {
  const ProcurementPortalShell({super.key});

  @override
  State<ProcurementPortalShell> createState() => _ProcurementPortalShellState();
}

class _ProcurementPortalShellState extends State<ProcurementPortalShell>
    with WidgetsBindingObserver, ShellNavBadgeMixin {
  int _index = 0;
  List<Widget>? _pages;
  Map<String, int> _counts = const {};
  int _homeBadge = 0;
  int _moreDot = 0;

  @override
  void initState() {
    super.initState();
    ProcurementPortalNavSignals.onCountsChanged = refreshNavBadges;
  }

  @override
  void dispose() {
    if (ProcurementPortalNavSignals.onCountsChanged == refreshNavBadges) {
      ProcurementPortalNavSignals.onCountsChanged = null;
    }
    super.dispose();
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (_pages != null) return;
    final api = context.read<ProcurementApi>();
    _pages = [
      ProcurementHomePage(api: api),
      ProcurementMorePage(api: api),
    ];
  }

  @override
  Future<void> refreshNavBadges() {
    scheduleNavBadgeFetch(() async {
      if (!mounted) return;
      final api = context.read<ProcurementApi>();
      try {
        final data = await api.sidebarNotifications();
        if (!mounted) return;
        final counts = parseSidebarCounts(data['counts']);
        var home = 0;
        for (final key in procurementHomeBadgeKeys) {
          home += counts[key] ?? 0;
        }
        setState(() {
          _counts = counts;
          _homeBadge = home;
          _moreDot = home > 0 ? 1 : 0;
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
        onTap: (i) {
          setState(() => _index = i);
          if (i == 0) refreshNavBadges();
        },
        pages: _pages!,
        portalName: 'Procurement',
        onSignOut: tichLogout(context),
        moreIndex: 1,
        moreDrawer: (context) => TichMoreDrawer(
          portalName: 'Procurement',
          items: ProcurementMorePage.items(context, context.read<ProcurementApi>()),
        ),
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
