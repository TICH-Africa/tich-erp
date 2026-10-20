import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../adaptive/adaptive.dart';
import '../../data/procurement_api.dart';
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
  int _index = 0;
  List<Widget>? _pages;
  Map<String, int> _counts = const {};
  int _homeBadge = 0;
  int _requisitionsBadge = 0;
  int _stockIssuesBadge = 0;
  int _suppliersBadge = 0;
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
      ProcurementRequisitionsPage(api: api),
      ProcurementStockIssuesPage(api: api),
      ProcurementSuppliersPage(api: api),
      ProcurementMorePage(api: api),
    ];
  }

  void _applyCounts(Map<String, int> counts) {
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
    setState(() {
      _counts = counts;
      _homeBadge = home;
      _requisitionsBadge = counts['requisitions.pending'] ?? 0;
      _stockIssuesBadge = counts['stock-issues.pending'] ?? 0;
      _suppliersBadge = counts['suppliers.pending'] ?? 0;
      _moreDot = moreDot > 0 ? 1 : 0;
    });
  }

  @override
  Future<void> refreshNavBadges() {
    scheduleNavBadgeFetch(() async {
      if (!mounted) return;
      final api = context.read<ProcurementApi>();
      try {
        final data = await api.sidebarNotifications();
        if (!mounted) return;
        _applyCounts(parseSidebarCounts(data['counts']));
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
          if (i <= 3) refreshNavBadges();
        },
        pages: _pages!,
        portalName: 'Procurement',
        onSignOut: tichLogout(context),
        moreIndex: 4,
        moreDrawer: (context) => TichMoreDrawer(
          portalName: 'Procurement',
          menuContext: TichPortalMenuContext.procurement,
          items: ProcurementMorePage.mainItems(
            context,
            context.read<ProcurementApi>(),
          ),
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
            label: 'Requisitions',
            materialIcon: Icons.assignment_outlined,
            materialActiveIcon: Icons.assignment,
            cupertinoIcon: CupertinoIcons.doc_text,
            cupertinoActiveIcon: CupertinoIcons.doc_text_fill,
            badgeCount: _requisitionsBadge,
          ),
          AdaptiveTabItem(
            label: 'Stock issues',
            materialIcon: Icons.output_outlined,
            materialActiveIcon: Icons.output,
            cupertinoIcon: CupertinoIcons.arrow_up_doc,
            cupertinoActiveIcon: CupertinoIcons.arrow_up_doc_fill,
            badgeCount: _stockIssuesBadge,
          ),
          AdaptiveTabItem(
            label: 'Suppliers',
            materialIcon: Icons.storefront_outlined,
            materialActiveIcon: Icons.storefront,
            cupertinoIcon: CupertinoIcons.building_2_fill,
            cupertinoActiveIcon: CupertinoIcons.building_2_fill,
            badgeCount: _suppliersBadge,
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
