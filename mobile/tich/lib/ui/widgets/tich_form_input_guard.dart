import 'package:flutter/widgets.dart';

/// True when focus is in an editable control (text field, Cupertino field, etc.).
bool tichTextInputHasFocus() {
  final focus = FocusManager.instance.primaryFocus;
  if (focus == null || !focus.hasFocus) {
    return false;
  }
  final ctx = focus.context;
  if (ctx == null) {
    return false;
  }
  return ctx.findAncestorStateOfType<EditableTextState>() != null;
}

/// Apply server text without clobbering what the user is typing.
void tichSyncControllerText(
  TextEditingController controller,
  String next, {
  FocusNode? focus,
}) {
  if (focus?.hasFocus == true) {
    return;
  }
  if (controller.text == next) {
    return;
  }
  controller.value = controller.value.copyWith(
    text: next,
    selection: TextSelection.collapsed(offset: next.length),
    composing: TextRange.empty,
  );
}