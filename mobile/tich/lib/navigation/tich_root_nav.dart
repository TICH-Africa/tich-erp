import 'package:flutter/material.dart';

/// App-wide navigator (see [TichApp] home). Use for pushes from the More drawer
/// so closing the drawer does not pop the route we just opened.
abstract final class TichRootNav {
  static final GlobalKey<NavigatorState> navigatorKey =
      GlobalKey<NavigatorState>();

  static Future<T?>? push<T>(Widget page) {
    return navigatorKey.currentState?.push<T>(
      MaterialPageRoute(builder: (_) => page),
    );
  }

  static void pushAndRemoveUntil(Widget page) {
    navigatorKey.currentState?.pushAndRemoveUntil(
      MaterialPageRoute(builder: (_) => page),
      (route) => false,
    );
  }
}
