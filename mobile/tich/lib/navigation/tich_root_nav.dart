import 'package:flutter/material.dart';

/// App-wide navigator (see [TichApp] home). Use for pushes from the More drawer
/// so closing the drawer does not pop the route we just opened.
abstract final class TichRootNav {
  static final GlobalKey<NavigatorState> navigatorKey =
      GlobalKey<NavigatorState>();

  static Future<T?>? push<T>(Widget page) {
    return navigatorKey.currentState?.push<T>(
      PageRouteBuilder<T>(
        pageBuilder: (context, animation, secondaryAnimation) => page,
        transitionsBuilder: (context, animation, secondaryAnimation, child) {
          return FadeTransition(
            opacity: CurvedAnimation(
              parent: animation,
              curve: Curves.easeOutCubic,
            ),
            child: child,
          );
        },
        transitionDuration: const Duration(milliseconds: 180),
        reverseTransitionDuration: const Duration(milliseconds: 160),
      ),
    );
  }

  static void pushAndRemoveUntil(Widget page) {
    navigatorKey.currentState?.pushAndRemoveUntil(
      MaterialPageRoute(builder: (_) => page),
      (route) => route.isFirst,
    );
  }
}
