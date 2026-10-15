import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../adaptive/adaptive.dart';
import '../../bloc/auth/auth_bloc.dart';
import '../../bloc/auth/auth_event.dart';
import '../../models/auth_user.dart';
import '../../theme/tich_theme.dart';
import '../shell/portal_shell.dart';
import '../shell/staff_portal_shell.dart';
import '../widgets/portal_hero.dart';
import '../widgets/tich_widgets.dart';

/// Chooses between My Employee Portal (HR) and Staff Portal (teaching).
class PortalChooserPage extends StatelessWidget {
  const PortalChooserPage({super.key, required this.user});

  final AuthUser user;

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'Choose portal',
      showLogout: true,
      onLogout: () =>
          context.read<AuthBloc>().add(const AuthLogoutRequested()),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          PortalHero(
            title: 'Hello, ${user.name}',
            subtitle:
                'Employee Portal and Staff Portal are separate workspaces.',
            accent: TichColors.blue,
          ),
          const SizedBox(height: 16),
          if (user.canAccessEmployeePortal)
            _PortalCard(
              title: 'My Employee Portal',
              description:
                  'Leave, clock in/out, payslips, documents, and HR self-service.',
              icon: Icons.badge_outlined,
              color: TichColors.blue,
              onTap: () => Navigator.of(context).pushReplacement(
                MaterialPageRoute(builder: (_) => const PortalShell()),
              ),
            ),
          if (user.canAccessEmployeePortal && user.canAccessStaffPortal)
            const SizedBox(height: 12),
          if (user.canAccessStaffPortal)
            _PortalCard(
              title: 'Staff Portal',
              description:
                  'Teaching units, class attendance, timetable, marks, and lesson plans.',
              icon: Icons.school_outlined,
              color: TichColors.greenDark,
              onTap: () => Navigator.of(context).pushReplacement(
                MaterialPageRoute(builder: (_) => const StaffPortalShell()),
              ),
            ),
          const SizedBox(height: 20),
          Text(
            'You can switch portals anytime from More.',
            style: tichUi(color: TichColors.neutralMuted, fontSize: 12),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }
}

class _PortalCard extends StatelessWidget {
  const _PortalCard({
    required this.title,
    required this.description,
    required this.icon,
    required this.color,
    required this.onTap,
  });

  final String title;
  final String description;
  final IconData icon;
  final Color color;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return TichCard(
      onTap: onTap,
      child: Row(
        children: [
          Container(
            width: 52,
            height: 52,
            decoration: BoxDecoration(
              color: color.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(14),
            ),
            child: Icon(icon, color: color),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title, style: TichTheme.display(fontSize: 16)),
                const SizedBox(height: 4),
                Text(
                  description,
                  style: tichUi(
                    fontSize: 13,
                    color: TichColors.neutralMuted,
                    height: 1.35,
                  ),
                ),
              ],
            ),
          ),
          const Icon(Icons.chevron_right, color: TichColors.neutralMuted),
        ],
      ),
    );
  }
}
