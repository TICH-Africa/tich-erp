import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../theme/tich_theme.dart';
import 'appearance_settings_page.dart';
import 'help_feedback_page.dart';
import 'security_settings_page.dart';

class SettingsHubPage extends StatelessWidget {
  const SettingsHubPage({super.key});

  @override
  Widget build(BuildContext context) {
    final items = [
      (
        'Log in and security',
        Icons.lock_outline,
        () => const SecuritySettingsPage(),
      ),
      (
        'Appearance',
        Icons.palette_outlined,
        () => const AppearanceSettingsPage(),
      ),
      (
        'Help and feedback',
        Icons.help_outline,
        () => const HelpFeedbackPage(),
      ),
    ];

    return AdaptiveScaffold(
      title: 'Settings',
      body: ListView.separated(
        padding: const EdgeInsets.all(16),
        itemCount: items.length,
        separatorBuilder: (_, __) => const SizedBox(height: 8),
        itemBuilder: (context, i) {
          final item = items[i];
          return Card(
            child: ListTile(
              leading: CircleAvatar(
                backgroundColor: TichColors.blueLight,
                child: Icon(item.$2, color: TichColors.blueDark),
              ),
              title: Text(item.$1, style: tichUi(fontWeight: FontWeight.w600)),
              trailing: const Icon(Icons.chevron_right),
              onTap: () => Navigator.of(context).push(
                MaterialPageRoute(builder: (_) => item.$3()),
              ),
            ),
          );
        },
      ),
    );
  }
}
