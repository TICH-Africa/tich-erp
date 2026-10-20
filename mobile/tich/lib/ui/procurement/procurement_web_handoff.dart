import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../adaptive/adaptive.dart';
import '../../config/api_config.dart';
import '../../theme/tich_theme.dart';
import '../widgets/tich_widgets.dart';
import 'procurement_helpers.dart';

/// Opens a procurement (or other ERP) web route in the device browser.
Future<void> tichOpenWebHandoff(
  BuildContext context, {
  required String path,
}) async {
  try {
    final uri = ApiConfig.webUri(path);
    final ok = await launchUrl(uri, mode: LaunchMode.externalApplication);
    if (!ok && context.mounted) {
      procurementShowError(context, 'Could not open the browser.');
    }
  } catch (e) {
    if (context.mounted) {
      procurementShowError(context, e);
    }
  }
}

/// Placeholder when a feature is web-only.
class ProcurementWebPlaceholderPage extends StatelessWidget {
  const ProcurementWebPlaceholderPage({
    super.key,
    required this.title,
    required this.description,
    required this.webPath,
  });

  final String title;
  final String description;
  final String webPath;

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: title,
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          TichCard(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title, style: TichTheme.display(fontSize: 18)),
                const SizedBox(height: 8),
                Text(
                  description,
                  style: tichUi(
                    fontSize: 14,
                    color: TichColors.neutralMuted,
                    height: 1.4,
                  ),
                ),
                const SizedBox(height: 16),
                Text(
                  'This opens the full web portal in your browser at ${ApiConfig.webOrigin}$webPath. '
                  'Sign in there if prompted.',
                  style: tichUi(fontSize: 13, height: 1.35),
                ),
                const SizedBox(height: 16),
                FilledButton.icon(
                  onPressed: () => tichOpenWebHandoff(context, path: webPath),
                  icon: const Icon(Icons.open_in_browser),
                  label: const Text('Open on web'),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
