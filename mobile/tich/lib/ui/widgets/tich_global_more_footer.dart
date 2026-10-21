import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../adaptive/adaptive.dart';
import '../../bloc/auth/auth_bloc.dart';
import '../../data/tich_api_scope.dart';
import '../../navigation/tich_root_nav.dart';
import '../../bloc/auth/auth_state.dart';
import '../../models/auth_user.dart';
import '../portal/portal_chooser_page.dart';
import '../shell/tich_logout.dart';
import '../procurement/procurement_web_handoff.dart';
import '../settings/settings_hub_page.dart';
import '../shell/portal_shell.dart';
import '../shell/procurement_portal_shell.dart';
import '../shell/staff_portal_shell.dart';
import 'tich_more_drawer.dart';
import 'tich_theme_picker_sheet.dart';

enum TichPortalMenuContext { employee, staff, student, procurement }

/// Choose portal when the user is not limited to the employee portal alone.
bool tichShowChoosePortal(AuthUser? user) {
  if (user == null || user.isStudent) return false;
  if (user.canAccessStaffPortal || user.canAccessProcurementPortal) {
    return true;
  }
  return user.hasMultiplePortals;
}

bool _canSwitchToEmployee(AuthUser? user) {
  if (user == null || user.isStudent) return false;
  return user.canAccessEmployeePortal ||
      user.hasBothStaffPortals ||
      (user.staffId != null && user.isTeachingStaff);
}

/// Quick portal switches and portal-specific web links (pinned above Choose portal).
List<TichMoreItem> tichPortalSwitchFooterItems(
  BuildContext context,
  TichPortalMenuContext portal,
) {
  final auth = context.watch<AuthBloc>().state;
  final user = auth is AuthAuthenticated ? auth.user : null;

  void switchPortal(Widget shell) {
    if (user == null) return;
    tichOpenPortalRoot(user, shell);
  }

  switch (portal) {
    case TichPortalMenuContext.employee:
      return [
        if (user?.canAccessProcurementPortal == true)
          TichMoreItem(
            label: 'Switch to Procurement Portal',
            icon: Icons.inventory_outlined,
            onTap: () => switchPortal(const ProcurementPortalShell()),
          ),
        if (user?.canAccessStaffPortal == true)
          TichMoreItem(
            label: 'Switch to Staff Portal',
            icon: Icons.school_outlined,
            onTap: () => switchPortal(const StaffPortalShell()),
          ),
      ];
    case TichPortalMenuContext.staff:
      return [
        if (user?.canAccessProcurementPortal == true)
          TichMoreItem(
            label: 'Switch to Procurement Portal',
            icon: Icons.inventory_outlined,
            onTap: () => switchPortal(const ProcurementPortalShell()),
          ),
        if (_canSwitchToEmployee(user))
          TichMoreItem(
            label: 'Switch to My Employee Portal',
            icon: Icons.badge_outlined,
            onTap: () => switchPortal(const PortalShell()),
          ),
      ];
    case TichPortalMenuContext.procurement:
      return [
        TichMoreItem(
          label: 'Procurement dashboard (web)',
          icon: Icons.open_in_browser,
          onTap: () => tichOpenWebHandoff(context, path: '/procurement'),
        ),
        if (user?.canAccessEmployeePortal == true)
          TichMoreItem(
            label: 'Switch to Employee Portal',
            icon: Icons.badge_outlined,
            onTap: () => switchPortal(const PortalShell()),
          ),
        if (user?.canAccessStaffPortal == true)
          TichMoreItem(
            label: 'Switch to Staff Portal',
            icon: Icons.school_outlined,
            onTap: () => switchPortal(const StaffPortalShell()),
          ),
      ];
    case TichPortalMenuContext.student:
      return [];
  }
}

/// Pinned drawer footer: portal switches, Choose portal (when applicable), Settings.
List<TichMoreItem> tichMoreMenuFooter(
  BuildContext context,
  TichPortalMenuContext portal,
) {
  return [
    ...tichPortalSwitchFooterItems(context, portal),
    ...tichGlobalMoreFooterItems(context),
  ];
}

/// Choose portal (when applicable), Settings, then Theme.
List<TichMoreItem> tichGlobalMoreFooterItems(BuildContext context) {
  final auth = context.watch<AuthBloc>().state;
  final user = auth is AuthAuthenticated ? auth.user : null;
  final fallbackSignOut = tichLogout(context);

  void open(Widget page) => TichRootNav.push(page);

  void signOut() {
    final shell = TichPortal.onSignOut;
    if (shell != null) {
      shell();
    } else {
      fallbackSignOut();
    }
  }

  return [
    if (tichShowChoosePortal(user))
      TichMoreItem(
        label: 'Choose portal',
        icon: Icons.apps_outlined,
        onTap: () => tichOpenPortalRoot(
          user!,
          PortalChooserPage(user: user),
        ),
      ),
    TichMoreItem(
      label: 'Settings',
      icon: Icons.settings_outlined,
      onTap: () => open(const SettingsHubPage()),
    ),
    TichMoreItem(
      label: 'Theme',
      icon: Icons.brightness_6_outlined,
      onTap: () {
        WidgetsBinding.instance.addPostFrameCallback((_) {
          final ctx = TichRootNav.navigatorKey.currentContext;
          if (ctx != null) showTichThemePicker(ctx);
        });
      },
    ),
    TichMoreItem(
      label: 'Sign out',
      icon: Icons.logout,
      onTap: signOut,
    ),
  ];
}
