import 'package:flutter/material.dart';

import '../../data/api_client.dart';
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

Widget procurementOpenWebButton({
  required BuildContext context,
  required String webPath,
  required VoidCallback onOpen,
}) {
  return OutlinedButton.icon(
    onPressed: onOpen,
    icon: const Icon(Icons.open_in_browser),
    label: const Text('Full details on web'),
  );
}
