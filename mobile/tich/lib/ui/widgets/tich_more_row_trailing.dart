import 'package:flutter/material.dart';

import 'tich_nav_badge.dart';

/// Count badge before the chevron on More menu rows.
class TichMoreRowTrailing extends StatelessWidget {
  const TichMoreRowTrailing({super.key, this.badgeCount = 0});

  final int badgeCount;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        if (badgeCount > 0)
          Padding(
            padding: const EdgeInsets.only(right: 6),
            child: TichNavBadge.circleCount(badgeCount),
          ),
        const Icon(Icons.chevron_right),
      ],
    );
  }
}
