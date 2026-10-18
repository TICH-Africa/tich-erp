import 'package:flutter/material.dart';

/// Shared body width for popup / modal forms across the app.
double tichFormDialogWidth(BuildContext context) {
  final screenWidth = MediaQuery.sizeOf(context).width;
  return (screenWidth - 48).clamp(300.0, 420.0);
}

/// Standard [AlertDialog] shell for data-entry popups (consistent width & insets).
class TichFormDialog extends StatelessWidget {
  const TichFormDialog({
    super.key,
    required this.title,
    required this.content,
    required this.actions,
    this.scrollable = false,
  });

  final Widget title;
  final Widget content;
  final List<Widget> actions;
  final bool scrollable;

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      insetPadding: const EdgeInsets.symmetric(horizontal: 24, vertical: 24),
      scrollable: scrollable,
      title: title,
      content: SizedBox(
        width: tichFormDialogWidth(context),
        child: content,
      ),
      actions: actions,
    );
  }
}

Future<T?> showTichFormDialog<T>({
  required BuildContext context,
  required Widget title,
  required Widget content,
  required List<Widget> actions,
  bool scrollable = false,
}) {
  return showDialog<T>(
    context: context,
    builder: (ctx) => TichFormDialog(
      title: title,
      content: content,
      actions: actions,
      scrollable: scrollable,
    ),
  );
}
