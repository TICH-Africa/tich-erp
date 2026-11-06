import 'package:flutter/material.dart';

import '../../theme/tich_theme.dart';
import '../widgets/date_time_pickers.dart';
import '../widgets/tich_widgets.dart';

/// Mirrors web portal Academics → Academic calendar tab.
class StudentAcademicCalendarSection extends StatelessWidget {
  const StudentAcademicCalendarSection({
    super.key,
    required this.academics,
  });

  final Map<String, dynamic> academics;

  List<Map<String, dynamic>> get _periods {
    final raw = academics['periods'];
    if (raw is! List) return const [];
    return [
      for (final e in raw)
        if (e is Map) Map<String, dynamic>.from(e),
    ];
  }

  @override
  Widget build(BuildContext context) {
    final currentSemester = academics['current_semester'];
    final currentPeriod = academics['current_period'];
    final periods = _periods;

    if (currentSemester is! Map && currentPeriod is! Map && periods.isEmpty) {
      return const ListView(
        padding: EdgeInsets.all(16),
        children: [
          TichCard(
            child: TichEmpty(
              'No academic calendar data yet for your programme.',
            ),
          ),
        ],
      );
    }

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        if (currentSemester is Map || currentPeriod is Map)
          TichCard(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Current period',
                  style: tichUi(fontWeight: FontWeight.w700),
                ),
                const SizedBox(height: 8),
                if (currentSemester is Map) ...[
                  Text(
                    'Semester: ${currentSemester['label'] ?? currentSemester['number'] ?? '-'}',
                    style: tichUi(fontSize: 13),
                  ),
                  if (currentSemester['year'] != null)
                    Text(
                      'Year: ${currentSemester['year']}',
                      style: tichUi(fontSize: 13),
                    ),
                ],
                if (currentPeriod is Map) ...[
                  const SizedBox(height: 4),
                  Text(
                    'Curriculum term: Semester ${currentPeriod['semester'] ?? '-'}',
                    style: tichUi(fontSize: 13),
                  ),
                  if (currentPeriod['label'] != null &&
                      currentPeriod['label'].toString().trim().isNotEmpty)
                    Text(
                      currentPeriod['label'].toString(),
                      style: tichUi(fontSize: 13),
                    ),
                ],
              ],
            ),
          ),
        if (periods.isNotEmpty) ...[
          const SizedBox(height: 12),
          const TichSectionHeader('Programme timeline'),
          for (final period in periods)
            Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: TichCard(
                child: Text(
                  '${period['label']?.toString().isNotEmpty == true ? period['label'] : 'Semester ${period['semester'] ?? ''}'} · '
                  '${formatDateTime(period['starts_at'])} → ${formatDateTime(period['ends_at'])}',
                  style: tichUi(fontSize: 13),
                ),
              ),
            ),
        ],
      ],
    );
  }
}
