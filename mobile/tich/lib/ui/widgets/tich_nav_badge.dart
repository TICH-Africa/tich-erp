import 'package:flutter/material.dart';

import 'tich_pulsing_nav_dot.dart';

/// Red sidebar-style alert badges (matches web `--sb-count-alert-bg`).
abstract final class TichNavBadge {
  static const Color alertRed = Color(0xFFDC2626);

  static const TextStyle _countTextStyle = TextStyle(
    color: Colors.white,
    fontWeight: FontWeight.w700,
    height: 1,
  );

  /// Circular red count (white digits in light and dark mode).
  static Widget circleCount(
    int count, {
    double fontSize = 11,
  }) {
    if (count <= 0) return const SizedBox.shrink();
    final label = count > 99 ? '99+' : '$count';
    final diameter = label.length <= 1 ? 20.0 : (label.length == 2 ? 22.0 : 24.0);
    return SizedBox(
      width: diameter,
      height: diameter,
      child: DecoratedBox(
        decoration: const BoxDecoration(
          color: alertRed,
          shape: BoxShape.circle,
        ),
        child: Center(
          child: Text(
            label,
            style: _countTextStyle.copyWith(fontSize: fontSize),
            textAlign: TextAlign.center,
          ),
        ),
      ),
    );
  }

  static Widget wrap({
    required Widget child,
    int count = 0,
    bool dotOnly = false,
    bool pulse = false,
  }) {
    if (count <= 0) return child;

    final overlay = pulse
        ? const TichPulsingNavDot(size: 10)
        : dotOnly
            ? const _RedDot(size: 9)
            : circleCount(count, fontSize: 10);

    return _NavIconBadgeSlot(
      overlay: overlay,
      child: child,
    );
  }
}

/// Room for badge ripples so [NavigationBar] does not clip them away.
class _NavIconBadgeSlot extends StatelessWidget {
  const _NavIconBadgeSlot({required this.child, required this.overlay});

  final Widget child;
  final Widget overlay;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: 36,
      height: 32,
      child: Stack(
        clipBehavior: Clip.none,
        alignment: Alignment.center,
        children: [
          child,
          Positioned(
            top: 0,
            right: 2,
            child: overlay,
          ),
        ],
      ),
    );
  }
}

class _RedDot extends StatelessWidget {
  const _RedDot({required this.size});

  final double size;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: size,
      height: size,
      decoration: const BoxDecoration(
        color: TichNavBadge.alertRed,
        shape: BoxShape.circle,
      ),
    );
  }
}
