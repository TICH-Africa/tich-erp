import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../theme/tich_theme.dart';
import '../widgets/tich_widgets.dart';

/// Work from home is disabled server-side; menu entry shows a placeholder only.
class WfhPage extends StatelessWidget {
  const WfhPage({super.key});

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'Work from home',
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          TichCard(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: TichColors.neutralMuted.withValues(alpha: 0.15),
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Text(
                    'Inactive',
                    style: tichUi(
                      fontWeight: FontWeight.w600,
                      color: TichColors.neutralMuted,
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                const TichEmpty('This page is unavailable.'),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
