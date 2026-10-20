import 'package:flutter/material.dart';

import '../../data/api_client.dart';
import '../../theme/tich_theme.dart';
import '../shell/procurement_nav_signals.dart';

void procurementNotifyCountsChanged() {
  ProcurementPortalNavSignals.onCountsChanged?.call();
}

void procurementShowError(BuildContext context, Object error) {
  ScaffoldMessenger.of(context).showSnackBar(
    SnackBar(content: Text(tichDescribeError(error))),
  );
}

Future<String?> procurementPromptText(
  BuildContext context, {
  required String title,
  required String hint,
  bool required = true,
}) async {
  final controller = TextEditingController();
  final result = await showDialog<String>(
    context: context,
    builder: (ctx) => AlertDialog(
      title: Text(title),
      content: TextField(
        controller: controller,
        maxLines: 3,
        decoration: InputDecoration(hintText: hint),
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(ctx),
          child: const Text('Cancel'),
        ),
        FilledButton(
          onPressed: () {
            final text = controller.text.trim();
            if (required && text.isEmpty) return;
            Navigator.pop(ctx, text);
          },
          child: const Text('OK'),
        ),
      ],
    ),
  );
  controller.dispose();
  return result;
}

Widget procurementStatusChip(String label) {
  if (label.isEmpty) return const SizedBox.shrink();
  return Container(
    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
    decoration: BoxDecoration(
      color: TichColors.blue.withValues(alpha: 0.12),
      borderRadius: BorderRadius.circular(8),
    ),
    child: Text(
      label,
      style: tichUi(fontSize: 11, color: TichColors.blue),
    ),
  );
}

Widget procurementDetailRow(String label, dynamic value) {
  final text = (value ?? '—').toString();
  if (text.isEmpty || text == '—') return const SizedBox.shrink();
  return Padding(
    padding: const EdgeInsets.only(bottom: 8),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: tichUi(fontSize: 11, color: TichColors.neutralMuted),
        ),
        Text(text, style: tichUi(fontSize: 14)),
      ],
    ),
  );
}
