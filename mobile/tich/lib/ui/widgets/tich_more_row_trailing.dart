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
            child: _CountPill(count: badgeCount),
          ),
        const Icon(Icons.chevron_right),
      ],
    );
  }
}

class _CountPill extends StatelessWidget {
  const _CountPill({required this.count});

  final int count;

  @override
  Widget build(BuildContext context) {
    final label = count > 99 ? '99+' : '$count';
    return Container(
      constraints: const BoxConstraints(minWidth: 20, minHeight: 20),
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
      decoration: BoxDecoration(
        color: TichNavBadge.alertRed,
        borderRadius: BorderRadius.circular(10),
      ),
      alignment: Alignment.center,
      child: Text(
        label,
        style: const TextStyle(
          color: Colors.white,
          fontSize: 11,
          fontWeight: FontWeight.w700,
          height: 1.1,
        ),
      ),
    );
  }
}
