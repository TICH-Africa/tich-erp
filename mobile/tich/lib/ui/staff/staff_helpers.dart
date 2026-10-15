import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../data/api_client.dart';
import '../../theme/tich_theme.dart';
import '../widgets/tich_widgets.dart';

Widget staffLoadingBody() => const Center(child: AdaptiveProgress());

Widget staffErrorBody(Object? error, VoidCallback onRetry) {
  return TichErrorRefresh(
    message: error is ApiException ? error.toString() : 'Could not load data.',
    onRetry: onRetry,
  );
}

String dayLabel(int? day, Map? labels) {
  if (day == null) return '—';
  if (labels != null && labels['$day'] != null) return labels['$day'].toString();
  const fallback = {
    1: 'Mon',
    2: 'Tue',
    3: 'Wed',
    4: 'Thu',
    5: 'Fri',
    6: 'Sat',
    7: 'Sun',
  };
  return fallback[day] ?? 'Day $day';
}

class StaffStatTile extends StatelessWidget {
  const StaffStatTile({
    super.key,
    required this.label,
    required this.value,
  });

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: TichCard(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              label,
              style: tichUi(fontSize: 11, color: TichColors.neutralMuted),
            ),
            const SizedBox(height: 6),
            Text(
              value,
              style: tichUi(fontWeight: FontWeight.w700, fontSize: 16),
            ),
          ],
        ),
      ),
    );
  }
}
