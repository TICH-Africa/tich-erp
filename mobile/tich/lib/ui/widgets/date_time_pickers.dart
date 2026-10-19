import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../../adaptive/adaptive.dart';

/// Display dates as "01 Aug 2025".
final dateDisplay = DateFormat('dd MMM yyyy');

/// Display times as "09.34 AM".
final timeDisplay = DateFormat('hh.mm a');

final dateTimeApi = DateFormat('yyyy-MM-dd HH:mm:ss');
final dateApi = DateFormat('yyyy-MM-dd');
final timeApi = DateFormat('HH:mm');

@Deprecated('Use dateDisplay')
final dateTimeDisplay = dateDisplay;

Future<DateTime?> pickDateTime(
  BuildContext context, {
  DateTime? initial,
  DateTime? firstDate,
  DateTime? lastDate,
}) async {
  final now = DateTime.now();
  final start = initial ?? now;
  final first = firstDate ?? DateTime(now.year - 1);
  final last = lastDate ?? DateTime(now.year + 2);

  if (usesCupertino) {
    DateTime temp = start;
    final confirmed = await showCupertinoModalPopup<bool>(
      context: context,
      builder: (ctx) => Container(
        height: 280,
        color: CupertinoColors.systemBackground.resolveFrom(ctx),
        child: Column(
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                CupertinoButton(
                  child: const Text('Cancel'),
                  onPressed: () => Navigator.pop(ctx, false),
                ),
                CupertinoButton(
                  child: const Text('Done'),
                  onPressed: () => Navigator.pop(ctx, true),
                ),
              ],
            ),
            Expanded(
              child: CupertinoDatePicker(
                mode: CupertinoDatePickerMode.dateAndTime,
                initialDateTime: start,
                minimumDate: first,
                maximumDate: last,
                use24hFormat: false,
                onDateTimeChanged: (v) => temp = v,
              ),
            ),
          ],
        ),
      ),
    );
    return confirmed == true ? temp : null;
  }

  final date = await showDatePicker(
    context: context,
    initialDate: start,
    firstDate: first,
    lastDate: last,
  );
  if (date == null || !context.mounted) return null;

  final time = await showTimePicker(
    context: context,
    initialTime: TimeOfDay.fromDateTime(start),
  );
  if (time == null) return null;

  return DateTime(date.year, date.month, date.day, time.hour, time.minute);
}

/// Calendar date only (no time picker).
Future<DateTime?> pickDateOnly(
  BuildContext context, {
  DateTime? initial,
  DateTime? firstDate,
  DateTime? lastDate,
}) async {
  final now = DateTime.now();
  final start = initial ?? now;
  final first = firstDate ?? DateTime(now.year - 1);
  final last = lastDate ?? DateTime(now.year + 2);

  if (usesCupertino) {
    DateTime temp = DateTime(start.year, start.month, start.day);
    final confirmed = await showCupertinoModalPopup<bool>(
      context: context,
      builder: (ctx) => Container(
        height: 280,
        color: CupertinoColors.systemBackground.resolveFrom(ctx),
        child: Column(
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                CupertinoButton(
                  child: const Text('Cancel'),
                  onPressed: () => Navigator.pop(ctx, false),
                ),
                CupertinoButton(
                  child: const Text('Done'),
                  onPressed: () => Navigator.pop(ctx, true),
                ),
              ],
            ),
            Expanded(
              child: CupertinoDatePicker(
                mode: CupertinoDatePickerMode.date,
                initialDateTime: temp,
                minimumDate: first,
                maximumDate: last,
                onDateTimeChanged: (v) => temp = DateTime(v.year, v.month, v.day),
              ),
            ),
          ],
        ),
      ),
    );
    return confirmed == true ? temp : null;
  }

  final date = await showDatePicker(
    context: context,
    initialDate: start,
    firstDate: first,
    lastDate: last,
  );
  if (date == null) return null;
  return DateTime(date.year, date.month, date.day);
}

DateTime? parseAppDateTime(dynamic value) {
  if (value == null) return null;
  if (value is DateTime) return value.toLocal();
  final raw = value.toString().trim();
  if (raw.isEmpty || raw == '—') return null;
  final parsed = DateTime.tryParse(raw);
  if (parsed != null) return parsed.toLocal();
  // Fallback for "yyyy-MM-dd HH:mm:ss"
  try {
    return dateTimeApi.parse(raw, true).toLocal();
  } catch (_) {
    try {
      return dateApi.parse(raw);
    } catch (_) {
      return null;
    }
  }
}

/// Dates everywhere: "01 Aug 2025".
String formatDateTime(dynamic value) {
  final dt = parseAppDateTime(value);
  if (dt == null) {
    final raw = value?.toString().trim();
    return (raw == null || raw.isEmpty) ? '—' : raw;
  }
  return dateDisplay.format(dt);
}

String formatDate(dynamic value) => formatDateTime(value);

/// Times as "09.34 AM".
String formatTime(dynamic value) {
  final dt = parseAppDateTime(value);
  if (dt == null) return '—';
  return timeDisplay.format(dt);
}
