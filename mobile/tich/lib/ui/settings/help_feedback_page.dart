import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../adaptive/adaptive.dart';
import '../../config/api_config.dart';
import '../../theme/tich_theme.dart';
import '../widgets/tich_widgets.dart';

class HelpFeedbackPage extends StatelessWidget {
  const HelpFeedbackPage({super.key});

  Uri _site(String path) {
    final base = ApiConfig.baseUrl.replaceAll(RegExp(r'/+$'), '');
    return Uri.parse('$base$path');
  }

  Future<void> _open(Uri uri) async {
    await launchUrl(uri, mode: LaunchMode.externalApplication);
  }

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'Help and feedback',
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          const TichSectionHeader('Legal'),
          TichCard(
            onTap: () => _open(_site('/terms')),
            child: ListTile(
              contentPadding: EdgeInsets.zero,
              leading: const Icon(Icons.description_outlined, color: TichColors.blue),
              title: Text('Terms of service', style: tichUi(fontWeight: FontWeight.w600)),
              trailing: const Icon(Icons.open_in_new, size: 18),
            ),
          ),
          const SizedBox(height: 8),
          TichCard(
            onTap: () => _open(_site('/privacy')),
            child: ListTile(
              contentPadding: EdgeInsets.zero,
              leading: const Icon(Icons.privacy_tip_outlined, color: TichColors.blue),
              title: Text('Privacy policy', style: tichUi(fontWeight: FontWeight.w600)),
              trailing: const Icon(Icons.open_in_new, size: 18),
            ),
          ),
          const SizedBox(height: 16),
          Text(
            'Opens the official pages on the TICH website.',
            style: tichUi(fontSize: 12, color: TichColors.neutralMuted),
          ),
        ],
      ),
    );
  }
}
