import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../settings/app_settings.dart';
import '../widgets/tich_widgets.dart';

class AppearanceSettingsPage extends StatelessWidget {
  const AppearanceSettingsPage({super.key});

  @override
  Widget build(BuildContext context) {
    final settings = AppSettings.instance;

    return AdaptiveScaffold(
      title: 'Appearance',
      body: ListenableBuilder(
        listenable: settings,
        builder: (context, _) {
          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              const TichSectionHeader('Font size'),
              TichCard(
                child: Column(
                  children: [
                    for (final size in AppFontSize.values)
                      RadioListTile<AppFontSize>(
                        contentPadding: EdgeInsets.zero,
                        title: Text(switch (size) {
                          AppFontSize.small => 'Small',
                          AppFontSize.normal => 'Default',
                          AppFontSize.large => 'Large',
                        }),
                        value: size,
                        groupValue: settings.fontSize,
                        onChanged: (v) {
                          if (v != null) settings.setFontSize(v);
                        },
                      ),
                  ],
                ),
              ),
              const SizedBox(height: 16),
              const TichSectionHeader('Theme'),
              TichCard(
                child: Column(
                  children: [
                    for (final mode in ThemeMode.values)
                      RadioListTile<ThemeMode>(
                        contentPadding: EdgeInsets.zero,
                        title: Text(switch (mode) {
                          ThemeMode.system => 'System default',
                          ThemeMode.light => 'Light',
                          ThemeMode.dark => 'Dark',
                        }),
                        value: mode,
                        groupValue: settings.themeMode,
                        onChanged: (v) {
                          if (v != null) settings.setThemeMode(v);
                        },
                      ),
                  ],
                ),
              ),
            ],
          );
        },
      ),
    );
  }
}
