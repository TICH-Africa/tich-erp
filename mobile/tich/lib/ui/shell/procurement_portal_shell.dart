import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../adaptive/adaptive.dart';
import '../../data/procurement_api.dart';
import '../../data/tich_portal_warmup.dart';
import '../procurement/procurement_badge_keys.dart';
import '../procurement/procurement_extra_pages.dart';
import '../procurement/procurement_home_page.dart';
import '../procurement/procurement_modules.dart';
import '../procurement/procurement_more_page.dart';
import '../procurement/procurement_pages.dart';
import '../widgets/tich_global_more_footer.dart';
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
  final GlobalKey<AdaptiveTabScaffoldState> _scaffoldKey =
      GlobalKey<AdaptiveTabScaffoldState>();
  List<Widget>? _pages;
  Widget? _root;

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
    if (_root != null) return;
    final api = context.read<ProcurementApi>();
    _pages = [
      ProcurementHomePage(api: api),
      ProcurementRequisitionsPage(api: api),
      ProcurementStockIssuesPage(api: api),
      ProcurementSuppliersPage(api: api),
      ProcurementMorePage(api: api),
    ];
    TichPortalWarmup.procurement(api);
    _root = AdaptiveTabScaffold(
      key: _scaffoldKey,
      pages: _pages!,
      portalName: 'Procurement',
      onSignOut: tichLogout(context),
      moreIndex: 4,
      onTabSelected: (i) {
        if (i <= 3) refreshNavBadges();
      },
      moreDrawer: (context) => TichMoreDrawer(
        portalName: 'Procurement',
        menuContext: TichPortalMenuContext.procurement,
        items: ProcurementMorePage.mainItems(context, api),
      ),
      items: _tabItems(const {}),
    );
  }

  List<AdaptiveTabItem> _tabItems(Map<String, int> counts) {
    var home = 0;
    for (final key in procurementHomeBadgeKeys) {
      home += counts[key] ?? 0;
    }
    var moreDot = 0;
    for (final mod in kProcurementModules) {
      if (procurementBottomNavModuleKeys.contains(mod.key)) continue;
      final countKey = procurementModuleBadgeKey[mod.key];
      if (countKey != null) {
        moreDot += counts[countKey] ?? 0;
      }
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
      AdaptiveTabItem(
        label: 'Requisitions',
        materialIcon: Icons.assignment_outlined,
        materialActiveIcon: Icons.assignment,
        cupertinoIcon: CupertinoIcons.doc_text,
        cupertinoActiveIcon: CupertinoIcons.doc_text_fill,
        badgeCount: counts['requisitions.pending'] ?? 0,
      ),
      AdaptiveTabItem(
        label: 'Stock issues',
        materialIcon: Icons.output_outlined,
        materialActiveIcon: Icons.output,
        cupertinoIcon: CupertinoIcons.arrow_up_doc,
        cupertinoActiveIcon: CupertinoIcons.arrow_up_doc_fill,
        badgeCount: counts['stock-issues.pending'] ?? 0,
      ),
      AdaptiveTabItem(
        label: 'Suppliers',
        materialIcon: Icons.storefront_outlined,
        materialActiveIcon: Icons.storefront,
        cupertinoIcon: CupertinoIcons.building_2_fill,
        cupertinoActiveIcon: CupertinoIcons.building_2_fill,
        badgeCount: counts['suppliers.pending'] ?? 0,
      ),
      AdaptiveTabItem(
        label: 'More',
        materialIcon: Icons.menu,
        materialActiveIcon: Icons.menu_open,
        cupertinoIcon: CupertinoIcons.ellipsis_circle,
        cupertinoActiveIcon: CupertinoIcons.ellipsis_circle_fill,
        badgeCount: moreDot > 0 ? 1 : 0,
        badgeDotOnly: true,
      ),
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
