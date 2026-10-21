import 'package:flutter/material.dart';

import '../../theme/tich_theme.dart';

/// Lightweight placeholders shown the same frame a route opens (no blank gap).
class TichPageSkeleton extends StatelessWidget {
  const TichPageSkeleton({super.key, this.cardCount = 4});

  final int cardCount;

  @override
  Widget build(BuildContext context) {
    final base = Theme.of(context).brightness == Brightness.dark
        ? TichColors.darkBorder
        : TichColors.neutralMuted.withValues(alpha: 0.14);
    final highlight = Theme.of(context).brightness == Brightness.dark
        ? TichColors.darkBorder.withValues(alpha: 0.55)
        : TichColors.neutralMuted.withValues(alpha: 0.22);

    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 32),
      physics: const AlwaysScrollableScrollPhysics(),
      children: [
        _Block(height: 88, color: highlight),
        const SizedBox(height: 16),
        for (var i = 0; i < cardCount; i++) ...[
          _Block(height: 72, color: base),
          const SizedBox(height: 10),
        ],
      ],
    );
  }
}

class _Block extends StatelessWidget {
  const _Block({required this.height, required this.color});

  final double height;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      height: height,
      decoration: BoxDecoration(
        color: color,
        borderRadius: BorderRadius.circular(12),
      ),
    );
  }
}
