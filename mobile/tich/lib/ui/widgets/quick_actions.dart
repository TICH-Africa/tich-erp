import 'package:flutter/material.dart';

import '../../theme/tich_theme.dart';
import 'tich_widgets.dart';

class QuickAction {
  const QuickAction({
    required this.label,
    required this.icon,
    required this.onTap,
  });

  final String label;
  final IconData icon;
  final VoidCallback onTap;
}

class QuickActionsGrid extends StatelessWidget {
  const QuickActionsGrid({super.key, required this.actions});

  final List<QuickAction> actions;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const TichSectionHeader('Quick actions'),
        GridView.count(
          crossAxisCount: 2,
          mainAxisSpacing: 8,
          crossAxisSpacing: 8,
          childAspectRatio: 2.4,
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          children: [
            for (final action in actions)
              Material(
                color: Theme.of(context).cardColor,
                borderRadius: BorderRadius.circular(12),
                child: InkWell(
                  borderRadius: BorderRadius.circular(12),
                  onTap: action.onTap,
                  child: Container(
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: TichColors.neutralBorder),
                    ),
                    padding: const EdgeInsets.symmetric(horizontal: 12),
                    child: Row(
                      children: [
                        CircleAvatar(
                          radius: 16,
                          backgroundColor: TichColors.greenLight,
                          child: Icon(action.icon,
                              size: 18, color: TichColors.greenDark),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: Text(
                            action.label,
                            style: tichUi(
                              fontWeight: FontWeight.w700,
                              fontSize: 13,
                            ),
                          ),
                        ),
                        const Icon(Icons.chevron_right, size: 18),
                      ],
                    ),
                  ),
                ),
              ),
          ],
        ),
      ],
    );
  }
}
