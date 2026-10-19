import 'package:flutter/material.dart';

import '../../theme/tich_theme.dart';
import 'staff_helpers.dart';

/// Weekday columns shown in the staff grid (Mon–Fri).
const staffTimetableWeekdays = [1, 2, 3, 4, 5];

String staffTimetableTimeKey(dynamic value) {
  final raw = value?.toString().trim() ?? '';
  if (raw.length >= 5) return raw.substring(0, 5);
  return raw;
}

/// Builds a scrollable Mon–Fri timetable table (time rows × day columns).
class StaffTimetableGrid extends StatelessWidget {
  const StaffTimetableGrid({
    super.key,
    required this.sessions,
    this.dayLabels,
    this.compact = true,
    this.dayColumnMinWidth = 108,
    this.enableZoom = false,
  });

  final List<Map<String, dynamic>> sessions;
  final Map? dayLabels;
  final bool compact;
  final double dayColumnMinWidth;
  final bool enableZoom;

  List<Map<String, dynamic>> get _weekSessions => sessions
      .where((s) {
        final day = (s['day_of_week'] as num?)?.toInt() ?? 0;
        return staffTimetableWeekdays.contains(day);
      })
      .toList();

  List<String> get _timeSlots {
    final slots = <String>{};
    for (final s in _weekSessions) {
      final key = staffTimetableTimeKey(s['start_time']);
      if (key.isNotEmpty) slots.add(key);
    }
    final list = slots.toList()..sort();
    return list;
  }

  Map<String, List<Map<String, dynamic>>> get _cellIndex {
    final map = <String, List<Map<String, dynamic>>>{};
    for (final s in _weekSessions) {
      final day = (s['day_of_week'] as num?)?.toInt() ?? 0;
      final start = staffTimetableTimeKey(s['start_time']);
      if (start.isEmpty) continue;
      final key = '$day|$start';
      map.putIfAbsent(key, () => []).add(s);
    }
    return map;
  }

  @override
  Widget build(BuildContext context) {
    if (_weekSessions.isEmpty) {
      return const SizedBox.shrink();
    }

    const timeColumnWidth = 56.0;
    final dayWidth = dayColumnMinWidth;
    final tableWidth = timeColumnWidth + dayWidth * staffTimetableWeekdays.length;

    Widget table = _TimetableTable(
      timeSlots: _timeSlots,
      cellIndex: _cellIndex,
      dayLabels: dayLabels,
      compact: compact,
      timeColumnWidth: timeColumnWidth,
      dayColumnWidth: dayWidth,
      tableWidth: tableWidth,
    );

    if (enableZoom) {
      table = InteractiveViewer(
        minScale: 0.65,
        maxScale: 2.5,
        boundaryMargin: const EdgeInsets.all(48),
        child: table,
      );
    }

    return LayoutBuilder(
      builder: (context, constraints) {
        final fitsWidth = constraints.maxWidth >= tableWidth;
        if (fitsWidth && !enableZoom) {
          final expandedDay =
              (constraints.maxWidth - timeColumnWidth) / staffTimetableWeekdays.length;
          return _TimetableTable(
            timeSlots: _timeSlots,
            cellIndex: _cellIndex,
            dayLabels: dayLabels,
            compact: compact,
            timeColumnWidth: timeColumnWidth,
            dayColumnWidth: expandedDay,
            tableWidth: constraints.maxWidth,
          );
        }
        return SingleChildScrollView(
          scrollDirection: Axis.horizontal,
          child: SizedBox(
            width: tableWidth,
            child: table,
          ),
        );
      },
    );
  }
}

class _TimetableTable extends StatelessWidget {
  const _TimetableTable({
    required this.timeSlots,
    required this.cellIndex,
    required this.dayLabels,
    required this.compact,
    required this.timeColumnWidth,
    required this.dayColumnWidth,
    required this.tableWidth,
  });

  final List<String> timeSlots;
  final Map<String, List<Map<String, dynamic>>> cellIndex;
  final Map? dayLabels;
  final bool compact;
  final double timeColumnWidth;
  final double dayColumnWidth;
  final double tableWidth;

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final borderColor =
        isDark ? TichColors.darkBorder : TichColors.neutralBorder;
    final headBg = isDark ? const Color(0xFF24384A) : TichColors.blueLight;
    final headText = isDark ? TichColors.white : TichColors.blueDark;

    return Table(
      border: TableBorder.all(color: borderColor, width: 1),
      columnWidths: {
        0: FixedColumnWidth(timeColumnWidth),
        for (var i = 0; i < staffTimetableWeekdays.length; i++)
          i + 1: FixedColumnWidth(dayColumnWidth),
      },
      defaultVerticalAlignment: TableCellVerticalAlignment.top,
      children: [
        TableRow(
          decoration: BoxDecoration(color: headBg),
          children: [
            _HeadCell(
              label: 'Time',
              compact: compact,
              color: headText,
              align: Alignment.centerLeft,
            ),
            for (final day in staffTimetableWeekdays)
              _HeadCell(
                label: _dayHeader(day, compact),
                compact: compact,
                color: headText,
              ),
          ],
        ),
        for (final slot in timeSlots)
          TableRow(
            children: [
              _TimeCell(slot: slot, compact: compact, isDark: isDark),
              for (final day in staffTimetableWeekdays)
                _SessionCell(
                  sessions: cellIndex['$day|$slot'] ?? const [],
                  compact: compact,
                  isDark: isDark,
                ),
            ],
          ),
      ],
    );
  }

  String _dayHeader(int day, bool compact) {
    final full = dayLabel(day, dayLabels);
    if (!compact) return full;
    if (full.length <= 3) return full;
    return full.substring(0, 3);
  }
}

class _HeadCell extends StatelessWidget {
  const _HeadCell({
    required this.label,
    required this.compact,
    required this.color,
    this.align = Alignment.center,
  });

  final String label;
  final bool compact;
  final Color color;
  final Alignment align;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.symmetric(
        horizontal: compact ? 6 : 10,
        vertical: compact ? 8 : 10,
      ),
      child: Align(
        alignment: align,
        child: Text(
          label,
          textAlign: TextAlign.center,
          style: tichUi(
            fontSize: compact ? 11 : 13,
            fontWeight: FontWeight.w700,
            color: color,
          ),
        ),
      ),
    );
  }
}

class _TimeCell extends StatelessWidget {
  const _TimeCell({
    required this.slot,
    required this.compact,
    required this.isDark,
  });

  final String slot;
  final bool compact;
  final bool isDark;

  @override
  Widget build(BuildContext context) {
    return Container(
      color: isDark ? TichColors.darkSurface : TichColors.white,
      padding: EdgeInsets.symmetric(
        horizontal: compact ? 4 : 8,
        vertical: compact ? 8 : 10,
      ),
      child: Text(
        slot,
        style: tichUi(
          fontSize: compact ? 11 : 12,
          fontWeight: FontWeight.w700,
          color: isDark ? const Color(0xFF9AA0A6) : TichColors.neutralMuted,
        ),
      ),
    );
  }
}

class _SessionCell extends StatelessWidget {
  const _SessionCell({
    required this.sessions,
    required this.compact,
    required this.isDark,
  });

  final List<Map<String, dynamic>> sessions;
  final bool compact;
  final bool isDark;

  @override
  Widget build(BuildContext context) {
    if (sessions.isEmpty) {
      return Container(
        color: isDark ? TichColors.darkBackground : TichColors.neutral,
        padding: EdgeInsets.all(compact ? 6 : 8),
        child: Text(
          '—',
          style: tichUi(
            fontSize: 11,
            color: isDark ? TichColors.darkBorder : TichColors.neutralBorder,
          ),
        ),
      );
    }

    return Container(
      color: isDark ? TichColors.darkBackground : TichColors.neutral,
      padding: EdgeInsets.all(compact ? 4 : 6),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          for (var i = 0; i < sessions.length; i++) ...[
            if (i > 0) SizedBox(height: compact ? 4 : 6),
            _SessionChip(session: sessions[i], compact: compact, isDark: isDark),
          ],
        ],
      ),
    );
  }
}

class _SessionChip extends StatelessWidget {
  const _SessionChip({
    required this.session,
    required this.compact,
    required this.isDark,
  });

  final Map<String, dynamic> session;
  final bool compact;
  final bool isDark;

  Color get _accent {
    final type = session['session_type']?.toString() ?? 'lesson';
    return switch (type) {
      'exam' => const Color(0xFFC53030),
      'supplementary' => const Color(0xFF8A6D1A),
      'special_exam' => const Color(0xFF7C3AED),
      'break' => TichColors.neutralMuted,
      _ => TichColors.blue,
    };
  }

  @override
  Widget build(BuildContext context) {
    final code = session['unit_code']?.toString() ?? '';
    final name = session['unit_name']?.toString() ?? '';
    final title = code.isNotEmpty
        ? code
        : (name.isNotEmpty ? name : 'Session');
    final start = staffTimetableTimeKey(session['start_time']);
    final end = staffTimetableTimeKey(session['end_time']);
    final timeLine = end.isNotEmpty && end != start ? '$start–$end' : start;
    final room = session['room']?.toString() ?? '';
    final program = session['program']?.toString() ?? '';
    final type = session['session_type']?.toString() ?? 'lesson';

    return Container(
      decoration: BoxDecoration(
        color: isDark ? TichColors.darkSurface : TichColors.white,
        borderRadius: TichRadius.borderRadiusSm,
        border: Border(
          left: BorderSide(color: _accent, width: 3),
          top: BorderSide(
            color: _accent.withValues(alpha: isDark ? 0.55 : 0.35),
          ),
          right: BorderSide(
            color: _accent.withValues(alpha: isDark ? 0.55 : 0.35),
          ),
          bottom: BorderSide(
            color: _accent.withValues(alpha: isDark ? 0.55 : 0.35),
          ),
        ),
      ),
      padding: EdgeInsets.symmetric(
        horizontal: compact ? 5 : 8,
        vertical: compact ? 5 : 7,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            maxLines: compact ? 2 : 3,
            overflow: TextOverflow.ellipsis,
            style: tichUi(
              fontSize: compact ? 11 : 12,
              fontWeight: FontWeight.w700,
            ),
          ),
          if (name.isNotEmpty && code.isNotEmpty) ...[
            SizedBox(height: compact ? 2 : 3),
            Text(
              name,
              maxLines: compact ? 2 : 3,
              overflow: TextOverflow.ellipsis,
              style: tichUi(
                fontSize: compact ? 10 : 11,
                color: TichColors.neutralMuted,
              ),
            ),
          ],
          if (timeLine.isNotEmpty) ...[
            SizedBox(height: compact ? 2 : 3),
            Text(
              timeLine,
              style: tichUi(fontSize: 10, color: TichColors.neutralMuted),
            ),
          ],
          if (room.isNotEmpty) ...[
            SizedBox(height: compact ? 2 : 3),
            Text(
              'Room $room',
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: tichUi(fontSize: 10, color: TichColors.neutralMuted),
            ),
          ],
          if (program.isNotEmpty) ...[
            SizedBox(height: compact ? 2 : 3),
            Text(
              program,
              maxLines: compact ? 1 : 2,
              overflow: TextOverflow.ellipsis,
              style: tichUi(fontSize: 10, color: TichColors.neutralMuted),
            ),
          ],
          if (!compact && type != 'lesson') ...[
            const SizedBox(height: 2),
            Text(
              type.replaceAll('_', ' '),
              style: tichUi(
                fontSize: 10,
                fontWeight: FontWeight.w600,
                color: _accent,
              ),
            ),
          ],
        ],
      ),
    );
  }
}
