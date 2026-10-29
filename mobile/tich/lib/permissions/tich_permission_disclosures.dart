import 'dart:io';

import 'package:flutter/cupertino.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../adaptive/adaptive.dart';
import '../notifications/tich_notifications.dart';

const _notifRationaleKey = 'tich_notif_rationale_shown';

/// In-app disclosures before system permission dialogs (Google Play policy).
abstract final class TichPermissionDisclosures {
  static Future<bool> confirmLocationForClockIn(BuildContext context) async {
    final accepted = await _confirm(
      context,
      title: 'Location for clock-in',
      message:
          'We use your device location only while you clock in to verify you are '
          'at campus (or your selected off-campus site). Location is not tracked '
          'in the background.',
      continueLabel: 'Continue',
    );
    return accepted == true;
  }

  static Future<bool> confirmCameraForAttendance(BuildContext context) async {
    final accepted = await _confirm(
      context,
      title: 'Camera for attendance',
      message:
          'We use the camera so you can scan or photograph attendance sheets '
          'and class photos. Images are uploaded to your institution for '
          'attendance records only.',
      continueLabel: 'Continue',
    );
    return accepted == true;
  }

  /// After sign-in, explain notifications before the OS permission prompt.
  static Future<void> maybePromptForNotifications(BuildContext context) async {
    if (kIsWeb || !(Platform.isAndroid || Platform.isIOS)) return;
    if (!context.mounted) return;

    final prefs = await SharedPreferences.getInstance();
    final alreadyShown = prefs.getBool(_notifRationaleKey) ?? false;
    final granted = await TichNotifications.instance
        .notificationPermissionGranted();
    if (granted) return;

    if (!alreadyShown) {
      if (!context.mounted) return;
      final accepted = await _confirm(
        context,
        title: 'Reminders and alerts',
        message:
            'TICH can send local reminders for clock-in and upcoming classes, '
            'and show alerts from your institution. You can turn these off in '
            'device settings at any time.',
        continueLabel: 'Allow notifications',
        cancelLabel: 'Not now',
      );
      await prefs.setBool(_notifRationaleKey, true);
      if (accepted != true) return;
    }

    await TichNotifications.instance.requestNotificationPermissions();
  }

  static Future<bool?> _confirm(
    BuildContext context, {
    required String title,
    required String message,
    required String continueLabel,
    String cancelLabel = 'Cancel',
  }) {
    if (usesCupertino) {
      return showCupertinoDialog<bool>(
        context: context,
        builder: (ctx) => CupertinoAlertDialog(
          title: Text(title),
          content: Padding(
            padding: const EdgeInsets.only(top: 8),
            child: Text(message),
          ),
          actions: [
            CupertinoDialogAction(
              onPressed: () => Navigator.of(ctx).pop(false),
              child: Text(cancelLabel),
            ),
            CupertinoDialogAction(
              isDefaultAction: true,
              onPressed: () => Navigator.of(ctx).pop(true),
              child: Text(continueLabel),
            ),
          ],
        ),
      );
    }
    return showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(title),
        content: Text(message),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(false),
            child: Text(cancelLabel),
          ),
          FilledButton(
            onPressed: () => Navigator.of(ctx).pop(true),
            child: Text(continueLabel),
          ),
        ],
      ),
    );
  }
}
