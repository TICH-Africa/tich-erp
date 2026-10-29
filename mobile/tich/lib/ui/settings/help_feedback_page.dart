import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../adaptive/adaptive.dart';
import '../../config/api_config.dart';
import '../../theme/tich_theme.dart';
import '../widgets/tich_widgets.dart';

class HelpFeedbackPage extends StatelessWidget {
  const HelpFeedbackPage({super.key});

  Uri _web(String path) => ApiConfig.webUri(path);

  Future<void> _open(Uri uri) async {
    await launchUrl(uri, mode: LaunchMode.externalApplication);
  }

  @override
  Widget build(BuildContext context) {
    final privacyUrl = ApiConfig.webUri('/privacy').toString();

    return AdaptiveScaffold(
      title: 'Help and feedback',
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          const TichSectionHeader('Your account'),
          TichCard(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Text(
                'Sign-in is managed by your employer or institution. '
                'This app does not offer self-registration or in-app account deletion. '
                'To close your account or request erasure of personal data, contact '
                'your organization or use the privacy policy below.',
                style: tichUi(fontSize: 14, height: 1.45),
              ),
            ),
          ),
          const SizedBox(height: 16),
          const TichSectionHeader('How we use data on this device'),
          TichCard(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _bullet(
                    'Location is used only when you clock in, to verify campus or '
                    'selected site attendance (foreground only).',
                  ),
                  _bullet(
                    'The camera is used when you scan or photograph attendance sheets '
                    'or class photos.',
                  ),
                  _bullet(
                    'Notifications remind you about clock-in and classes, and show '
                    'alerts from your portal.',
                  ),
                  _bullet(
                    'Finance, documents, and HR information come from your institution '
                    'over encrypted HTTPS. Biometric unlock stays on your device.',
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),
          const TichSectionHeader('Legal'),
          TichCard(
            onTap: () => _open(_web('/terms')),
            child: ListTile(
              contentPadding: EdgeInsets.zero,
              leading: const Icon(Icons.description_outlined, color: TichColors.blue),
              title: Text('Terms of service', style: tichUi(fontWeight: FontWeight.w600)),
              trailing: const Icon(Icons.open_in_new, size: 18),
            ),
          ),
          const SizedBox(height: 8),
          TichCard(
            onTap: () => _open(_web('/privacy')),
            child: ListTile(
              contentPadding: EdgeInsets.zero,
              leading: const Icon(Icons.privacy_tip_outlined, color: TichColors.blue),
              title: Text('Privacy policy', style: tichUi(fontWeight: FontWeight.w600)),
              trailing: const Icon(Icons.open_in_new, size: 18),
            ),
          ),
          const SizedBox(height: 16),
          Text(
            'Privacy policy URL for the store listing: $privacyUrl',
            style: tichUi(fontSize: 12, color: TichColors.neutralMuted),
          ),
        ],
      ),
    );
  }

  Widget _bullet(String text) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('· ', style: tichUi(fontSize: 14, height: 1.45)),
          Expanded(
            child: Text(text, style: tichUi(fontSize: 14, height: 1.45)),
          ),
        ],
      ),
    );
  }
}
