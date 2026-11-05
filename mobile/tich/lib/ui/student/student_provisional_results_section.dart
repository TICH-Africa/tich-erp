import 'package:flutter/material.dart';

import '../../theme/tich_theme.dart';
import '../widgets/tich_widgets.dart';

/// Semester-grouped CAT, exam, and grade rows from [provisional_results_by_semester].
class StudentProvisionalResultsSection extends StatelessWidget {
  const StudentProvisionalResultsSection({
    super.key,
    required this.semesters,
  });

  final List<Map<String, dynamic>> semesters;

  static String _formatScore(dynamic obtained, dynamic max) {
    if (obtained != null && max != null) {
      return '${_num(obtained)}/${_num(max)}';
    }
    return '-';
  }

  static String _num(dynamic v) {
    if (v is num) {
      return v == v.roundToDouble() ? '${v.toInt()}' : v.toStringAsFixed(1);
    }
    return '$v';
  }

  static String _catLabel(Map<String, dynamic> cat) {
    final name = cat['assessment_name']?.toString().trim();
    if (name != null && name.isNotEmpty) return name;
    final type = cat['assessment_type']?.toString().replaceAll('_', ' ') ?? 'CAT';
    if (type.isEmpty) return 'CAT';
    return type[0].toUpperCase() + type.substring(1);
  }

  @override
  Widget build(BuildContext context) {
    if (semesters.isEmpty) {
      return const TichCard(
        child: TichEmpty(
          'No provisional results yet. CAT and exam marks will appear here by semester once recorded.',
        ),
      );
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Text(
          'Provisional results by semester',
          style: tichUi(fontWeight: FontWeight.w700, fontSize: 15),
        ),
        const SizedBox(height: 4),
        Text(
          'Unofficial until the Academic Registrar publishes final results.',
          style: tichUi(fontSize: 12, color: TichColors.neutralMuted),
        ),
        for (final sem in semesters) ...[
          const SizedBox(height: 16),
          TichSectionHeader(
            sem['semester_label']?.toString() ?? 'Semester',
          ),
          for (final unit in (sem['units'] as List? ?? const []))
            if (unit is Map) ...[
              Padding(
                padding: const EdgeInsets.only(bottom: 8),
                child: TichCard(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        [
                          unit['unit_code'],
                          unit['unit_name'],
                        ].where((e) => e != null && '$e'.isNotEmpty).join(' · '),
                        style: tichUi(fontWeight: FontWeight.w600),
                      ),
                      if ((unit['cats'] as List? ?? const []).isNotEmpty) ...[
                        const SizedBox(height: 8),
                        Text(
                          'CAT / continuous assessment',
                          style: tichUi(
                            fontSize: 12,
                            fontWeight: FontWeight.w600,
                            color: TichColors.neutralMuted,
                          ),
                        ),
                        for (final raw in unit['cats'] as List)
                          if (raw is Map)
                            Padding(
                              padding: const EdgeInsets.only(top: 4),
                              child: Text(
                                '${_catLabel(Map<String, dynamic>.from(raw))}: '
                                '${_formatScore(raw['score_obtained'], raw['max_score'])}'
                                '${raw['weight_in_final'] != null ? ' · weight ${_num(raw['weight_in_final'])}%' : ''}',
                                style: tichUi(fontSize: 13),
                              ),
                            ),
                      ],
                      if (unit['exam'] is Map) ...[
                        const SizedBox(height: 8),
                        Text(
                          'Examination',
                          style: tichUi(
                            fontSize: 12,
                            fontWeight: FontWeight.w600,
                            color: TichColors.neutralMuted,
                          ),
                        ),
                        Builder(
                          builder: (_) {
                            final exam =
                                Map<String, dynamic>.from(unit['exam'] as Map);
                            return Padding(
                              padding: const EdgeInsets.only(top: 4),
                              child: Text(
                                [
                                  if (exam['cat_total'] != null)
                                    'CAT total: ${_num(exam['cat_total'])}',
                                  if (exam['final_exam_score'] != null)
                                    'Exam: ${_num(exam['final_exam_score'])}',
                                  if (exam['final_total_score'] != null)
                                    'Total: ${_num(exam['final_total_score'])}',
                                  if (exam['grade_letter'] != null)
                                    'Grade: ${exam['grade_letter']}',
                                  exam['status_label']?.toString(),
                                ].where((e) => e != null && e.isNotEmpty).join(' · '),
                                style: tichUi(fontSize: 13),
                              ),
                            );
                          },
                        ),
                      ],
                      if (unit['final_grade'] is Map) ...[
                        const SizedBox(height: 6),
                        Builder(
                          builder: (_) {
                            final fg = Map<String, dynamic>.from(
                              unit['final_grade'] as Map,
                            );
                            return Text(
                              'Recorded grade: ${fg['grade_letter'] ?? '-'}'
                              '${fg['final_score'] != null ? ' (${_num(fg['final_score'])})' : ''}',
                              style: tichUi(fontSize: 12),
                            );
                          },
                        ),
                      ],
                      if ((unit['cats'] as List? ?? const []).isEmpty &&
                          unit['exam'] == null &&
                          unit['final_grade'] == null)
                        Padding(
                          padding: const EdgeInsets.only(top: 6),
                          child: Text(
                            'No marks recorded yet.',
                            style: tichUi(
                              fontSize: 12,
                              color: TichColors.neutralMuted,
                            ),
                          ),
                        ),
                    ],
                  ),
                ),
              ),
            ],
        ],
      ],
    );
  }
}
