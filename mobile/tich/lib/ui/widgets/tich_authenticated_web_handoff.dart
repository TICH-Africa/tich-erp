import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../data/api_client.dart';
import '../../models/auth_user.dart';
import '../../theme/tich_theme.dart';

/// Confirms identity, mints a one-time web session, and opens the browser.
Future<void> tichConfirmAndOpenWebHandoff(
  BuildContext context, {
  required AuthUser user,
  required Future<Map<String, dynamic>> Function(String path) createHandoff,
  required String path,
  String? intro,
}) async {
  final display = user.name.trim().isNotEmpty ? user.name.trim() : user.email;
  final confirmed = await showDialog<bool>(
    context: context,
    builder: (ctx) => AlertDialog(
      title: Text('Continue as $display?'),
      content: Text(
        intro ??
            'You will open the student portal in your browser, signed in as '
            '$display (${user.email}). Payments are completed on the web portal.',
        style: tichUi(fontSize: 14, height: 1.4),
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(ctx, false),
          child: const Text('Cancel'),
        ),
        FilledButton(
          onPressed: () => Navigator.pop(ctx, true),
          child: const Text('Continue'),
        ),
      ],
    ),
  );
  if (confirmed != true || !context.mounted) return;

  try {
    final res = await createHandoff(path);
    final url = res['url']?.toString().trim();
    if (url == null || url.isEmpty) {
      throw ApiException('Could not start web sign-in.');
    }
    final ok = await launchUrl(
      Uri.parse(url),
      mode: LaunchMode.externalApplication,
    );
    if (!ok && context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Could not open the browser.')),
      );
    }
  } on ApiException catch (e) {
    if (!context.mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(e.message)),
    );
  } catch (e) {
    if (!context.mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(e.toString())),
    );
  }
}
