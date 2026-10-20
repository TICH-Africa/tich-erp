import 'package:flutter/material.dart';

import '../../settings/app_settings.dart';
import '../../theme/tich_theme.dart';

String tichThemeModeLabel(ThemeMode mode) => switch (mode) {
      ThemeMode.system => 'System default',
      ThemeMode.light => 'Light',
      ThemeMode.dark => 'Dark',
    };

/// Bottom sheet theme picker (More drawer footer and elsewhere).
void showTichThemePicker(BuildContext context) {
  showModalBottomSheet<void>(
    context: context,
    useRootNavigator: true,
    showDragHandle: true,
    isScrollControlled: false,
    builder: (ctx) {
      final settings = AppSettings.instance;
      return SafeArea(
        child: ListenableBuilder(
          listenable: settings,
          builder: (context, _) {
            return Padding(
              padding: const EdgeInsets.fromLTRB(8, 0, 8, 12),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Padding(
                    padding: const EdgeInsets.fromLTRB(12, 4, 12, 8),
                    child: Text(
                      'Theme',
                      style: TichTheme.display(fontSize: 17),
                    ),
                  ),
                  for (final mode in ThemeMode.values)
                    ListTile(
                      title: Text(
                        tichThemeModeLabel(mode),
                        style: tichUi(fontWeight: FontWeight.w600),
                      ),
                      trailing: settings.themeMode == mode
                          ? Icon(Icons.check, color: Theme.of(context).colorScheme.primary)
                          : null,
                      onTap: () {
                        settings.setThemeMode(mode);
                        Navigator.of(ctx).pop();
                      },
                    ),
                ],
              ),
            );
          },
        ),
      );
    },
  );
}
