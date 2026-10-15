import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../adaptive/adaptive.dart';
import '../../bloc/auth/auth_bloc.dart';
import '../../bloc/auth/auth_event.dart';
import '../../bloc/auth/auth_state.dart';
import '../../data/staff_api.dart';
import '../../theme/tich_theme.dart';
import '../portal/portal_chooser_page.dart';
import '../settings/settings_hub_page.dart';
import '../shell/portal_shell.dart';
import '../widgets/tich_widgets.dart';
import 'content_page.dart';
import 'grading_page.dart';
import 'lesson_plans_page.dart';

class StaffMorePage extends StatelessWidget {
  const StaffMorePage({super.key, required this.api});

  final StaffApi api;

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthBloc>().state;
    final user = auth is AuthAuthenticated ? auth.user : null;

    final items = <({String label, IconData icon, VoidCallback onTap})>[
      (
        label: 'Marks & assessments',
        icon: Icons.grade_outlined,
        onTap: () => Navigator.of(context).push(
          MaterialPageRoute(builder: (_) => StaffGradingPage(api: api)),
        ),
      ),
      (
        label: 'Lesson plans',
        icon: Icons.menu_book_outlined,
        onTap: () => Navigator.of(context).push(
          MaterialPageRoute(builder: (_) => StaffLessonPlansPage(api: api)),
        ),
      ),
      (
        label: 'Learning content',
        icon: Icons.layers_outlined,
        onTap: () => Navigator.of(context).push(
          MaterialPageRoute(builder: (_) => StaffContentPage(api: api)),
        ),
      ),
      (
        label: 'Settings',
        icon: Icons.settings_outlined,
        onTap: () => Navigator.of(context).push(
          MaterialPageRoute(builder: (_) => const SettingsHubPage()),
        ),
      ),
    ];

    if (user?.canAccessEmployeePortal == true) {
      items.insert(
        0,
        (
          label: 'Switch to My Employee Portal',
          icon: Icons.badge_outlined,
          onTap: () => Navigator.of(context).pushAndRemoveUntil(
            MaterialPageRoute(builder: (_) => const PortalShell()),
            (route) => false,
          ),
        ),
      );
    }

    if (user?.hasBothStaffPortals == true) {
      items.insert(
        user?.canAccessEmployeePortal == true ? 1 : 0,
        (
          label: 'Choose portal',
          icon: Icons.apps_outlined,
          onTap: () => Navigator.of(context).pushAndRemoveUntil(
            MaterialPageRoute(
              builder: (_) => PortalChooserPage(user: user!),
            ),
            (route) => false,
          ),
        ),
      );
    }

    return AdaptiveScaffold(
      title: 'More',
      showLogout: true,
      onLogout: () =>
          context.read<AuthBloc>().add(const AuthLogoutRequested()),
      body: ListView.separated(
        padding: const EdgeInsets.all(16),
        itemCount: items.length,
        separatorBuilder: (_, __) => const SizedBox(height: 8),
        itemBuilder: (context, i) {
          final item = items[i];
          return TichCard(
            onTap: item.onTap,
            child: ListTile(
              contentPadding: EdgeInsets.zero,
              leading: Icon(item.icon, color: TichColors.greenDark),
              title: Text(
                item.label,
                style: tichUi(fontWeight: FontWeight.w600),
              ),
              trailing: const Icon(Icons.chevron_right),
            ),
          );
        },
      ),
    );
  }
}
