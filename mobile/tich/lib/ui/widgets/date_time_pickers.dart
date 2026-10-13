import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../../adaptive/adaptive.dart';

final dateTimeDisplay = DateFormat('dd MMM yyyy · HH:mm');
final dateTimeApi = DateFormat('yyyy-MM-dd HH:mm:ss');
final dateApi = DateFormat('yyyy-MM-dd');
final timeApi = DateFormat('HH:mm');

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
                use24hFormat: true,
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

String formatDateTime(dynamic value) {
  if (value == null) return '—';
  if (value is DateTime) return dateTimeDisplay.format(value);
  final parsed = DateTime.tryParse(value.toString());
  if (parsed != null) return dateTimeDisplay.format(parsed.toLocal());
  return value.toString();
}
