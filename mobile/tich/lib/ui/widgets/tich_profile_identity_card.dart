import 'package:flutter/material.dart';

import '../../theme/tich_theme.dart';
import 'employee_avatar.dart';
import 'tich_widgets.dart';

/// Profile photo + name block for student and employee profile screens.
class TichProfileIdentityCard extends StatelessWidget {
  const TichProfileIdentityCard({
    super.key,
    required this.name,
    this.photoUrl,
    this.lines = const [],
    this.radius = 32,
  });

  final String name;
  final String? photoUrl;
  final List<String> lines;
  final double radius;

  @override
  Widget build(BuildContext context) {
    final detailLines = lines.where((line) => line.trim().isNotEmpty).toList();

    return TichCard(
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          EmployeeAvatar(
            name: name.isNotEmpty ? name : 'User',
            radius: radius,
            photoUrl: photoUrl,
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  name.isNotEmpty ? name : 'Profile',
                  style: TichTheme.display(fontSize: 18),
                ),
                for (final line in detailLines)
                  Padding(
                    padding: const EdgeInsets.only(top: 4),
                    child: Text(
                      line,
                      style: tichUi(
                        color: TichColors.neutralMuted,
                        fontSize: 13,
                        height: 1.35,
                      ),
                    ),
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
