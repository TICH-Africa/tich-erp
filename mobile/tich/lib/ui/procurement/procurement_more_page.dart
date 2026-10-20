import 'package:flutter/material.dart';
import '../../adaptive/adaptive.dart';
import '../../data/procurement_api.dart';
import '../shell/portal_nav_counts_scope.dart';
import '../widgets/tich_global_more_footer.dart';
import '../widgets/tich_more_drawer.dart';
import 'procurement_badge_keys.dart';
import 'procurement_module_nav.dart';
import 'procurement_modules.dart';

/// Primary modules on the procurement bottom bar — omitted from More.
const procurementBottomNavModuleKeys = {
  'requisitions',
  'stock_issues',
  'suppliers',
};

class ProcurementMorePage extends StatelessWidget {
  const ProcurementMorePage({super.key, required this.api});

  final ProcurementApi api;

  static List<TichMoreItem> mainItems(BuildContext context, ProcurementApi api) {
    final nav = PortalNavCountsScope.maybeOf(context);

    int badge(String moduleKey) {
      final countKey = procurementModuleBadgeKey[moduleKey];
      if (countKey == null) return 0;
      return nav?.countFor(countKey) ?? 0;
    }

    final moduleItems = kProcurementModules
        .where((mod) => !procurementBottomNavModuleKeys.contains(mod.key))
        .map((mod) {
      return TichMoreItem(
        label: mod.label,
        icon: mod.icon,
        badgeCount: badge(mod.key),
        onTap: () => openProcurementModule(
          context,
          api: api,
          key: mod.key,
          label: mod.label,
          webPath: mod.webPath,
          mobile: mod.mobile,
        ),
      );
        })
        .toList();

    return moduleItems;
  }

  static List<TichMoreItem> items(BuildContext context, ProcurementApi api) => [
        ...mainItems(context, api),
        ...tichMoreMenuFooter(context, TichPortalMenuContext.procurement),
      ];

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'More',
      body: TichMoreList(items: items(context, api)),
    );
  }
}
