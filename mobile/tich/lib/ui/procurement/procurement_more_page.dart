import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../adaptive/adaptive.dart';
import '../../bloc/auth/auth_bloc.dart';
import '../../bloc/auth/auth_state.dart';
import '../../data/procurement_api.dart';
import '../portal/portal_chooser_page.dart';
import '../settings/settings_hub_page.dart';
import '../shell/portal_nav_counts_scope.dart';
import '../shell/portal_shell.dart';
import '../shell/staff_portal_shell.dart';
import '../widgets/tich_more_drawer.dart';
import 'procurement_badge_keys.dart';
import 'procurement_module_nav.dart';
import 'procurement_modules.dart';
import 'procurement_web_handoff.dart';

class ProcurementMorePage extends StatelessWidget {
  const ProcurementMorePage({super.key, required this.api});

  final ProcurementApi api;

  static List<TichMoreItem> items(BuildContext context, ProcurementApi api) {
    final auth = context.watch<AuthBloc>().state;
    final user = auth is AuthAuthenticated ? auth.user : null;
    final nav = PortalNavCountsScope.maybeOf(context);

    void open(Widget page) =>
        Navigator.of(context).push(MaterialPageRoute(builder: (_) => page));

    int badge(String moduleKey) {
      final countKey = procurementModuleBadgeKey[moduleKey];
      if (countKey == null) return 0;
      return nav?.countFor(countKey) ?? 0;
    }

    final moduleItems = kProcurementModules.map((mod) {
      return TichMoreItem(
        label: mod.mobile ? mod.label : '${mod.label} (web)',
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
    }).toList();

    return [
      ...moduleItems,
      TichMoreItem(
        label: 'Procurement dashboard (web)',
        icon: Icons.open_in_browser,
        onTap: () => tichOpenWebHandoff(context, path: '/procurement'),
      ),
      if (user?.canAccessEmployeePortal == true)
        TichMoreItem(
          label: 'Switch to Employee Portal',
          icon: Icons.badge_outlined,
          onTap: () => Navigator.of(context).pushAndRemoveUntil(
            MaterialPageRoute(builder: (_) => const PortalShell()),
            (route) => false,
          ),
        ),
      if (user?.canAccessStaffPortal == true)
        TichMoreItem(
          label: 'Switch to Staff Portal',
          icon: Icons.school_outlined,
          onTap: () => Navigator.of(context).pushAndRemoveUntil(
            MaterialPageRoute(builder: (_) => const StaffPortalShell()),
            (route) => false,
          ),
        ),
      if (user != null && user.hasMultiplePortals)
        TichMoreItem(
          label: 'Choose portal',
          icon: Icons.apps_outlined,
          onTap: () => Navigator.of(context).pushAndRemoveUntil(
            MaterialPageRoute(builder: (_) => PortalChooserPage(user: user)),
            (route) => false,
          ),
        ),
      TichMoreItem(
        label: 'Settings',
        icon: Icons.settings_outlined,
        onTap: () => open(const SettingsHubPage()),
      ),
    ];
  }

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'More',
      body: TichMoreList(items: items(context, api)),
    );
  }
}
