import 'package:flutter/material.dart';

import '../../theme/tich_theme.dart';
import '../widgets/tich_widgets.dart';

/// Choose a semester before opening provisional results.
class StudentProvisionalResultsSemesterList extends StatelessWidget {
  const StudentProvisionalResultsSemesterList({
    super.key,
    required this.semesters,
    required this.onSemesterTap,
  });

  final List<Map<String, dynamic>> semesters;
  final void Function(Map<String, dynamic> semester) onSemesterTap;

  static int _unitCount(Map<String, dynamic> sem) {
    final units = sem['units'];
    if (units is! List) return 0;
    return units.length;
  }

  static int _unitsWithMarks(Map<String, dynamic> sem) {
    final units = sem['units'];
    if (units is! List) return 0;
    var n = 0;
    for (final raw in units) {
      if (raw is! Map) continue;
      final u = Map<String, dynamic>.from(raw);
      final hasCats = (u['cats'] as List? ?? const []).isNotEmpty;
      if (hasCats || u['exam'] != null || u['final_grade'] != null) {
        n++;
      }
    }
    return n;
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
          'Select semester',
          style: tichUi(fontWeight: FontWeight.w700, fontSize: 15),
        ),
        const SizedBox(height: 4),
        Text(
          'Choose a semester to view provisional CAT and exam results.',
          style: tichUi(fontSize: 12, color: TichColors.neutralMuted),
        ),
        const SizedBox(height: 16),
        for (final sem in semesters) ...[
          TichCard(
            onTap: () => onSemesterTap(sem),
            child: ListTile(
              contentPadding: EdgeInsets.zero,
              title: Text(
                sem['semester_label']?.toString() ?? 'Semester',
                style: tichUi(fontWeight: FontWeight.w600),
              ),
              subtitle: Text(_subtitle(sem)),
              trailing: const Icon(Icons.chevron_right),
            ),
          ),
          const SizedBox(height: 8),
        ],
      ],
    );
  }

  String _subtitle(Map<String, dynamic> sem) {
    final total = _unitCount(sem);
    final marked = _unitsWithMarks(sem);
    if (total == 0) return 'No units';
    if (marked == 0) return '$total unit${total == 1 ? '' : 's'} · No marks yet';
    if (marked == total) {
      return '$total unit${total == 1 ? '' : 's'} · Provisional results';
    }
    return '$marked of $total units with provisional marks';
  }
}

/// Provisional results for one semester (tabular layout).
class StudentProvisionalResultsSection extends StatelessWidget {
  const StudentProvisionalResultsSection({
    super.key,
    required this.semester,
  });

  final Map<String, dynamic> semester;

  static String _formatScore(dynamic obtained, dynamic max) {
    if (obtained != null && max != null) {
      return '${_num(obtained)}/${_num(max)}';
    }
    if (obtained != null) return _num(obtained);
    return '-';
  }

  static String _num(dynamic v) {
    if (v == null) return '-';
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

  static Map<String, dynamic> _unitRow(Map<String, dynamic> unit) {
    final exam = unit['exam'] is Map
        ? Map<String, dynamic>.from(unit['exam'] as Map)
        : <String, dynamic>{};
    final fg = unit['final_grade'] is Map
        ? Map<String, dynamic>.from(unit['final_grade'] as Map)
        : <String, dynamic>{};

    final grade = exam['grade_letter']?.toString() ??
        fg['grade_letter']?.toString() ??
        '-';
    final total = exam['final_total_score'] ?? fg['final_score'];
    final catTotal = exam['cat_total'];
    final examMark = exam['final_exam_score'];
    final status = exam['status_label']?.toString() ?? '';

    return {
      'unit_code': unit['unit_code']?.toString() ?? '',
      'unit_name': unit['unit_name']?.toString() ?? '',
      'cat_total': catTotal,
      'exam_mark': examMark,
      'total': total,
      'grade': grade,
      'status': status,
      'cats': unit['cats'] as List? ?? const [],
      'has_marks': (unit['cats'] as List? ?? const []).isNotEmpty ||
          unit['exam'] != null ||
          unit['final_grade'] != null,
    };
  }

  @override
  Widget build(BuildContext context) {
    final border = Theme.of(context).dividerColor.withValues(alpha: 0.85);
    final brightness = Theme.of(context).brightness;
    final headerBg = TichColors.tableHeadBackground(brightness);
    final headerFg = TichColors.tableHeadForeground(brightness);
    final headerStyle = tichUi(
      fontWeight: FontWeight.w700,
      fontSize: 11,
      color: headerFg,
    );
    final units = [
      for (final u in (semester['units'] as List? ?? const []))
        if (u is Map) _unitRow(Map<String, dynamic>.from(u)),
    ];
    final unitMaps = [
      for (final u in (semester['units'] as List? ?? const []))
        if (u is Map) Map<String, dynamic>.from(u),
    ];

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Text(
          'Unofficial until the Academic Registrar publishes final results.',
          style: tichUi(fontSize: 12, color: TichColors.neutralMuted),
        ),
        const SizedBox(height: 12),
        TichCard(
          padding: EdgeInsets.zero,
          child: ClipRRect(
            borderRadius: BorderRadius.circular(8),
            child: SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              child: ConstrainedBox(
                constraints: const BoxConstraints(minWidth: 520),
                child: _ResultsTable(
                  borderColor: border,
                  headerBackground: headerBg,
                  headerStyle: headerStyle,
                  units: units,
                ),
              ),
            ),
          ),
        ),
        _CatDetailTable(
          borderColor: border,
          headerBackground: headerBg,
          headerStyle: headerStyle,
          units: unitMaps,
        ),
      ],
    );
  }
}

class _ResultsTable extends StatelessWidget {
  const _ResultsTable({
    required this.borderColor,
    required this.headerBackground,
    required this.headerStyle,
    required this.units,
  });

  final Color borderColor;
  final Color headerBackground;
  final TextStyle headerStyle;
  final List<Map<String, dynamic>> units;

  @override
  Widget build(BuildContext context) {
    if (units.isEmpty) {
      return Padding(
        padding: const EdgeInsets.all(16),
        child: Text(
          'No units in this semester.',
          style: tichUi(fontSize: 13, color: TichColors.neutralMuted),
        ),
      );
    }

    final cellStyle = tichUi(fontSize: 12);

    TableRow headerRow() => TableRow(
          decoration: BoxDecoration(color: headerBackground),
          children: [
            _tableCell('Unit', headerStyle, bold: true),
            _tableCell('CAT', headerStyle, bold: true, align: TextAlign.center),
            _tableCell('Exam', headerStyle, bold: true, align: TextAlign.center),
            _tableCell('Total', headerStyle, bold: true, align: TextAlign.center),
            _tableCell('Grade', headerStyle, bold: true, align: TextAlign.center),
            _tableCell('Status', headerStyle, bold: true),
          ],
        );

    return Table(
      border: TableBorder.all(color: borderColor, width: 1),
      columnWidths: const {
        0: FlexColumnWidth(2.2),
        1: FlexColumnWidth(0.9),
        2: FlexColumnWidth(0.9),
        3: FlexColumnWidth(0.9),
        4: FlexColumnWidth(0.7),
        5: FlexColumnWidth(1.2),
      },
      defaultVerticalAlignment: TableCellVerticalAlignment.middle,
      children: [
        headerRow(),
        for (final row in units)
          TableRow(
            children: [
              _tableCell(
                [
                  row['unit_code'],
                  row['unit_name'],
                ].where((e) => '$e'.trim().isNotEmpty).join('\n'),
                cellStyle,
                maxLines: 3,
              ),
              _tableCell(
                row['has_marks'] == true
                    ? StudentProvisionalResultsSection._num(row['cat_total'])
                    : '-',
                cellStyle,
                align: TextAlign.center,
              ),
              _tableCell(
                row['has_marks'] == true
                    ? StudentProvisionalResultsSection._num(row['exam_mark'])
                    : '-',
                cellStyle,
                align: TextAlign.center,
              ),
              _tableCell(
                row['has_marks'] == true
                    ? StudentProvisionalResultsSection._num(row['total'])
                    : '-',
                cellStyle,
                align: TextAlign.center,
              ),
              _tableCell(
                '${row['grade']}',
                cellStyle,
                align: TextAlign.center,
                bold: '${row['grade']}' != '-',
              ),
              _tableCell(
                row['status']?.toString().trim().isNotEmpty == true
                    ? row['status'].toString()
                    : (row['has_marks'] == true ? 'Provisional' : '-'),
                cellStyle,
                maxLines: 2,
              ),
            ],
          ),
      ],
    );
  }

  Widget _tableCell(
    String text,
    TextStyle style, {
    TextAlign align = TextAlign.start,
    int maxLines = 2,
    bool bold = false,
  }) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 10),
      child: Text(
        text,
        style: bold ? style.copyWith(fontWeight: FontWeight.w700) : style,
        textAlign: align,
        maxLines: maxLines,
        overflow: TextOverflow.ellipsis,
      ),
    );
  }
}

class _CatDetailTable extends StatelessWidget {
  const _CatDetailTable({
    required this.borderColor,
    required this.headerBackground,
    required this.headerStyle,
    required this.units,
  });

  final Color borderColor;
  final Color headerBackground;
  final TextStyle headerStyle;
  final List<Map<String, dynamic>> units;

  @override
  Widget build(BuildContext context) {
    final rows = <Map<String, String>>[];
    for (final unit in units) {
      final code = unit['unit_code']?.toString() ?? '';
      for (final raw in unit['cats'] as List? ?? const []) {
        if (raw is! Map) continue;
        final cat = Map<String, dynamic>.from(raw);
        rows.add({
          'unit': code,
          'assessment': StudentProvisionalResultsSection._catLabel(cat),
          'score': StudentProvisionalResultsSection._formatScore(
            cat['score_obtained'],
            cat['max_score'],
          ),
          'weight': cat['weight_in_final'] != null
              ? '${StudentProvisionalResultsSection._num(cat['weight_in_final'])}%'
              : '-',
        });
      }
    }

    if (rows.isEmpty) return const SizedBox.shrink();

    final cellStyle = tichUi(fontSize: 12);

    return Padding(
      padding: const EdgeInsets.only(top: 12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(
            'CAT / continuous assessment detail',
            style: tichUi(
              fontSize: 12,
              fontWeight: FontWeight.w600,
              color: TichColors.neutralMuted,
            ),
          ),
          const SizedBox(height: 6),
          TichCard(
            padding: EdgeInsets.zero,
            child: SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              child: ConstrainedBox(
                constraints: const BoxConstraints(minWidth: 480),
                child: Table(
                  border: TableBorder.all(color: borderColor, width: 1),
                  columnWidths: const {
                    0: FlexColumnWidth(0.8),
                    1: FlexColumnWidth(1.8),
                    2: FlexColumnWidth(0.9),
                    3: FlexColumnWidth(0.7),
                  },
                  defaultVerticalAlignment: TableCellVerticalAlignment.middle,
                  children: [
                    TableRow(
                      decoration: BoxDecoration(color: headerBackground),
                      children: [
                        _cell('Unit', headerStyle),
                        _cell('Assessment', headerStyle),
                        _cell('Score', headerStyle, center: true),
                        _cell('Weight', headerStyle, center: true),
                      ],
                    ),
                    for (final r in rows)
                      TableRow(
                        children: [
                          _cell(r['unit'] ?? '', cellStyle),
                          _cell(r['assessment'] ?? '', cellStyle),
                          _cell(r['score'] ?? '-', cellStyle, center: true),
                          _cell(r['weight'] ?? '-', cellStyle, center: true),
                        ],
                      ),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _cell(String text, TextStyle style, {bool center = false}) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 8),
      child: Text(
        text,
        style: style,
        textAlign: center ? TextAlign.center : TextAlign.start,
        maxLines: 2,
        overflow: TextOverflow.ellipsis,
      ),
    );
  }
}
