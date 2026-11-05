import 'package:flutter/material.dart';

import '../../theme/tich_theme.dart';
import '../widgets/date_time_pickers.dart';
import '../widgets/tich_widgets.dart';

class StudentAcademicProgressSection extends StatelessWidget {
  const StudentAcademicProgressSection({
    super.key,
    required this.academics,
  });

  final Map<String, dynamic> academics;

  static String _num(dynamic v) {
    if (v == null) return '-';
    if (v is num) {
      return v == v.roundToDouble() ? '${v.toInt()}' : v.toStringAsFixed(1);
    }
    return '$v';
  }

  Map<String, dynamic>? get _summary {
    final raw = academics['progress_summary'];
    if (raw is Map) return Map<String, dynamic>.from(raw);
    return null;
  }

  List<Map<String, dynamic>> get _semesters {
    final raw = academics['semester_performance'];
    if (raw is! List) return const [];
    return [
      for (final e in raw)
        if (e is Map) Map<String, dynamic>.from(e),
    ];
  }

  List<Map<String, dynamic>> get _periods {
    final raw = academics['periods'];
    if (raw is! List) return const [];
    return [
      for (final e in raw)
        if (e is Map) Map<String, dynamic>.from(e),
    ];
  }

  List<Map<String, dynamic>> get _registrations {
    final raw = academics['registrations'];
    if (raw is! List) return const [];
    return [
      for (final e in raw)
        if (e is Map) Map<String, dynamic>.from(e),
    ];
  }

  @override
  Widget build(BuildContext context) {
    final summary = _summary;
    final semesters = _semesters;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        if (summary != null) ...[
          TichCard(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Overview',
                  style: tichUi(fontWeight: FontWeight.w700),
                ),
                const SizedBox(height: 8),
                Text(
                  'Units with grades: ${summary['units_with_grades'] ?? 0}\n'
                  'Registered units: ${summary['registered_unit_count'] ?? 0}\n'
                  'Programme completion: ${summary['completion_percent'] ?? 0}%\n'
                  '${summary['cumulative_gpa'] != null ? 'Cumulative GPA: ${_num(summary['cumulative_gpa'])}\n' : ''}'
                  '${summary['total_credits_earned'] != null ? 'Credits earned: ${_num(summary['total_credits_earned'])}' : ''}',
                  style: tichUi(fontSize: 13, height: 1.5),
                ),
                if (summary['completion_percent'] is num) ...[
                  const SizedBox(height: 10),
                  ClipRRect(
                    borderRadius: BorderRadius.circular(999),
                    child: LinearProgressIndicator(
                      value: ((summary['completion_percent'] as num) / 100)
                          .clamp(0.0, 1.0),
                      minHeight: 6,
                      backgroundColor: TichColors.neutralBorder,
                      color: TichColors.green,
                    ),
                  ),
                ],
              ],
            ),
          ),
          const SizedBox(height: 12),
        ],
        if (academics['current_period'] is Map) ...[
          Builder(
            builder: (_) {
              final period =
                  Map<String, dynamic>.from(academics['current_period'] as Map);
              final label = period['label']?.toString().trim();
              final status = academics['current_period_status']?.toString();
              return TichCard(
                child: Text(
                  'Current period: ${label?.isNotEmpty == true ? label : 'Semester ${period['semester'] ?? '-'}'}'
                  '${status != null && status.isNotEmpty ? ' · ${status.replaceAll('_', ' ')}' : ''}',
                  style: tichUi(fontSize: 13),
                ),
              );
            },
          ),
          const SizedBox(height: 12),
        ],
        const TichSectionHeader('Progress by semester'),
        if (semesters.isEmpty)
          const TichCard(
            child: TichEmpty(
              'No semester progress yet. Register for units each term; grades appear as marks are recorded.',
            ),
          )
        else
          for (final sem in semesters) ...[
            Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: TichCard(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            [
                              sem['label'],
                              sem['year_label'],
                            ].where((e) => e != null && '$e'.isNotEmpty).join(' · '),
                            style: tichUi(fontWeight: FontWeight.w700),
                          ),
                        ),
                        if (sem['is_current'] == true)
                          const TichStatusChip('Current', tone: TichChipTone.success),
                      ],
                    ),
                    const SizedBox(height: 4),
                    Text(
                      [
                        '${(sem['units'] as List? ?? []).length} units',
                        if (sem['graded_units'] != null)
                          '${sem['graded_units']} graded',
                        if (sem['gpa'] != null) 'GPA ${_num(sem['gpa'])}',
                        if (sem['credits'] != null &&
                            (sem['credits'] as num) > 0)
                          '${_num(sem['credits'])} credits',
                      ].join(' · '),
                      style: tichUi(fontSize: 12, color: TichColors.neutralMuted),
                    ),
                    for (final raw in sem['units'] as List? ?? const [])
                      if (raw is Map) ...[
                        const Divider(height: 16),
                        _UnitProgressRow(unit: Map<String, dynamic>.from(raw)),
                      ],
                  ],
                ),
              ),
            ),
          ],
        if (_periods.isNotEmpty) ...[
          const SizedBox(height: 8),
          const TichSectionHeader('Curriculum periods'),
          for (final period in _periods)
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
        if (_registrations.isNotEmpty) ...[
          const SizedBox(height: 8),
          const TichSectionHeader('Semester registrations'),
          for (final reg in _registrations)
            Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: TichCard(
                child: ListTile(
                  contentPadding: EdgeInsets.zero,
                  title: Text(
                    reg['semester_label']?.toString() ?? 'Semester',
                    style: tichUi(fontWeight: FontWeight.w600),
                  ),
                  subtitle: Text(
                    [
                      reg['status'],
                      formatDateTime(reg['registration_date']),
                    ].where((e) => e != null && '$e'.isNotEmpty).join(' · '),
                    style: tichUi(fontSize: 12),
                  ),
                ),
              ),
            ),
        ],
      ],
    );
  }
}

class _UnitProgressRow extends StatelessWidget {
  const _UnitProgressRow({required this.unit});

  final Map<String, dynamic> unit;

  @override
  Widget build(BuildContext context) {
    final code = unit['unit_code']?.toString() ?? '';
    final name = unit['unit_name']?.toString() ?? '';

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          [code, name].where((e) => e.isNotEmpty).join(' · '),
          style: tichUi(fontWeight: FontWeight.w600, fontSize: 13),
        ),
        const SizedBox(height: 4),
        Text(
          [
            if (unit['cat'] != null) 'CAT ${StudentAcademicProgressSection._num(unit['cat'])}',
            if (unit['exam'] != null) 'Exam ${StudentAcademicProgressSection._num(unit['exam'])}',
            if (unit['total'] != null) 'Total ${StudentAcademicProgressSection._num(unit['total'])}',
            if (unit['grade'] != null) 'Grade ${unit['grade']}',
          ].join(' · '),
          style: tichUi(fontSize: 12),
        ),
        if (unit['status'] != null)
          Text(
            unit['status'].toString(),
            style: tichUi(fontSize: 11, color: TichColors.neutralMuted),
          ),
      ],
    );
  }
}
