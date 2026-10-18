import 'package:flutter/material.dart';

/// Red sidebar-style alert badges (matches web `--sb-count-alert-bg`).
abstract final class TichNavBadge {
  static const Color alertRed = Color(0xFFDC2626);

  static Widget wrap({
    required Widget child,
    int count = 0,
    bool dotOnly = false,
  }) {
    if (dotOnly) {
      if (count <= 0) return child;
      return Badge(
        isLabelVisible: false,
        backgroundColor: alertRed,
        smallSize: 8,
        child: child,
      );
    }
    if (count <= 0) return child;
    final label = count > 99 ? '99+' : '$count';
    return Badge(
      label: Text(
        label,
        style: const TextStyle(
          fontSize: 10,
          fontWeight: FontWeight.w700,
          height: 1,
        ),
      ),
      backgroundColor: alertRed,
      child: child,
    );
  }
}
