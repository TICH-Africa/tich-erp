import 'package:flutter/material.dart';

import 'tich_nav_badge.dart';

/// Red dot with expanding ripple rings (clock-in reminder on bottom nav).
class TichPulsingNavDot extends StatefulWidget {
  const TichPulsingNavDot({super.key, this.size = 10});

  final double size;

  @override
  State<TichPulsingNavDot> createState() => _TichPulsingNavDotState();
}

class _TichPulsingNavDotState extends State<TichPulsingNavDot>
    with SingleTickerProviderStateMixin {
  late final AnimationController _controller;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 2000),
    )..repeat();
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final core = widget.size;
    return SizedBox(
      width: core + 14,
      height: core + 14,
      child: AnimatedBuilder(
        animation: _controller,
        builder: (context, child) {
          return Stack(
            alignment: Alignment.center,
            clipBehavior: Clip.none,
            children: [
              for (var i = 0; i < 3; i++)
                _RippleRing(
                  progress: (_controller.value + i / 3) % 1.0,
                  coreSize: core,
                ),
              Container(
                width: core,
                height: core,
                decoration: const BoxDecoration(
                  color: TichNavBadge.alertRed,
                  shape: BoxShape.circle,
                ),
              ),
            ],
          );
        },
      ),
    );
  }
}

class _RippleRing extends StatelessWidget {
  const _RippleRing({required this.progress, required this.coreSize});

  final double progress;
  final double coreSize;

  @override
  Widget build(BuildContext context) {
    final t = Curves.easeOut.transform(progress);
    final scale = 1.0 + t * 2.2;
    final opacity = (1.0 - t).clamp(0.0, 1.0) * 0.75;
    return Transform.scale(
      scale: scale,
      child: Container(
        width: coreSize,
        height: coreSize,
        decoration: BoxDecoration(
          shape: BoxShape.circle,
          border: Border.all(
            color: TichNavBadge.alertRed.withValues(alpha: opacity),
            width: 2,
          ),
        ),
      ),
    );
  }
}
