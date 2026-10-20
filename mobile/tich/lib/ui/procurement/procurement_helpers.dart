import 'package:flutter/material.dart';

import '../../data/api_client.dart';
import '../shell/procurement_nav_signals.dart';
import 'procurement_detail_widgets.dart';

export 'procurement_detail_widgets.dart';

void procurementNotifyCountsChanged() {
  ProcurementPortalNavSignals.onCountsChanged?.call();
}

/// Starts a new API request and stores its [Future] in state without returning it from [setState].
void procurementUpdateFuture(
  void Function(VoidCallback fn) setState,
  void Function(Future<Map<String, dynamic>> future) assign,
  Future<Map<String, dynamic>> Function() fetch,
) {
  final next = fetch();
  setState(() {
    assign(next);
  });
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

Widget procurementDetailRow(String label, dynamic value) =>
    procurementDetailField(label, value);
