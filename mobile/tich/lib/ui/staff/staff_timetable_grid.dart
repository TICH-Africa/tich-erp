import 'package:flutter/material.dart';

import '../../theme/tich_theme.dart';
import 'staff_helpers.dart';

/// Weekday rows in the staff grid (Mon–Fri).
const staffTimetableWeekdays = [1, 2, 3, 4, 5];

String staffTimetableTimeKey(dynamic value) {
  final raw = value?.toString().trim() ?? '';
  if (raw.length >= 5) return raw.substring(0, 5);
  return raw;
}

String staffTimetableSessionTimeRange(Map<String, dynamic> session) {
  final start = staffTimetableTimeKey(session['start_time']);
  final end = staffTimetableTimeKey(session['end_time']);
  if (start.isEmpty) return '';
  if (end.isNotEmpty && end != start) return '$start - $end';
  return start;
}

/// Days down the left, time slots across the top — sized to the screen width.
class StaffTimetableGrid extends StatelessWidget {
  const StaffTimetableGrid({
    super.key,
    required this.sessions,
    this.dayLabels,
    this.compact = true,
  });

  final List<Map<String, dynamic>> sessions;
  final Map? dayLabels;
  final bool compact;

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
    return slots.toList()..sort();
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

  String _slotHeader(String slot) {
    for (final day in staffTimetableWeekdays) {
      final list = _cellIndex['$day|$slot'];
      if (list != null && list.isNotEmpty) {
        return staffTimetableSessionTimeRange(list.first);
      }
    }
    return slot;
  }

  @override
  Widget build(BuildContext context) {
    if (_weekSessions.isEmpty) {
      return const SizedBox.shrink();
    }

    final timeSlots = _timeSlots;
    if (timeSlots.isEmpty) {
      return const SizedBox.shrink();
    }

    return LayoutBuilder(
      builder: (context, constraints) {
        final width = constraints.maxWidth;
        const dayColWidth = 52.0;
        final slotCount = timeSlots.length;
        final slotColWidth = (width - dayColWidth) / slotCount;

        return _TimetableTable(
          timeSlots: timeSlots,
          cellIndex: _cellIndex,
          dayLabels: dayLabels,
          compact: compact,
          dayColWidth: dayColWidth,
          slotColWidth: slotColWidth,
          slotHeader: _slotHeader,
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
    required this.dayColWidth,
    required this.slotColWidth,
    required this.slotHeader,
  });

  final List<String> timeSlots;
  final Map<String, List<Map<String, dynamic>>> cellIndex;
  final Map? dayLabels;
  final bool compact;
  final double dayColWidth;
  final double slotColWidth;
  final String Function(String slot) slotHeader;

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
        0: FixedColumnWidth(dayColWidth),
        for (var i = 0; i < timeSlots.length; i++)
          i + 1: FixedColumnWidth(slotColWidth),
      },
      defaultVerticalAlignment: TableCellVerticalAlignment.top,
      children: [
        TableRow(
          decoration: BoxDecoration(color: headBg),
          children: [
            _HeadCell(
              label: 'Day',
              compact: compact,
              color: headText,
              align: Alignment.centerLeft,
            ),
            for (final slot in timeSlots)
              _HeadCell(
                label: slotHeader(slot),
                compact: compact,
                color: headText,
                maxLines: 3,
              ),
          ],
        ),
        for (final day in staffTimetableWeekdays)
          TableRow(
            children: [
              _DayCell(
                label: _dayLabel(day),
                compact: compact,
                isDark: isDark,
              ),
              for (final slot in timeSlots)
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

  String _dayLabel(int day) {
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
    this.maxLines = 2,
  });

  final String label;
  final bool compact;
  final Color color;
  final Alignment align;
  final int maxLines;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.symmetric(
        horizontal: compact ? 3 : 6,
        vertical: compact ? 6 : 8,
      ),
      child: Align(
        alignment: align,
        child: Text(
          label,
          textAlign: TextAlign.center,
          maxLines: maxLines,
          overflow: TextOverflow.ellipsis,
          style: tichUi(
            fontSize: compact ? 9 : 11,
            fontWeight: FontWeight.w700,
            color: color,
            height: 1.15,
          ),
        ),
      ),
    );
  }
}

class _DayCell extends StatelessWidget {
  const _DayCell({
    required this.label,
    required this.compact,
    required this.isDark,
  });

  final String label;
  final bool compact;
  final bool isDark;

  @override
  Widget build(BuildContext context) {
    return Container(
      color: isDark ? TichColors.darkSurface : TichColors.white,
      padding: EdgeInsets.symmetric(
        horizontal: compact ? 4 : 6,
        vertical: compact ? 8 : 10,
      ),
      child: Text(
        label,
        maxLines: 2,
        overflow: TextOverflow.ellipsis,
        style: tichUi(
          fontSize: compact ? 10 : 12,
          fontWeight: FontWeight.w700,
          color: isDark ? const Color(0xFF9AA0A6) : TichColors.blueDark,
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
        padding: EdgeInsets.all(compact ? 4 : 6),
        child: Text(
          '—',
          textAlign: TextAlign.center,
          style: tichUi(
            fontSize: 10,
            color: isDark ? TichColors.darkBorder : TichColors.neutralBorder,
          ),
        ),
      );
    }

    return Container(
      color: isDark ? TichColors.darkBackground : TichColors.neutral,
      padding: EdgeInsets.all(compact ? 3 : 5),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          for (var i = 0; i < sessions.length; i++) ...[
            if (i > 0) SizedBox(height: compact ? 3 : 4),
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
      _ => TichColors.blue,
    };
  }

  @override
  Widget build(BuildContext context) {
    final code = session['unit_code']?.toString() ?? '';
    final name = session['unit_name']?.toString() ?? '';
    final title = code.isNotEmpty ? code : name;
    final room = session['room']?.toString() ?? '';

    return Container(
      decoration: BoxDecoration(
        color: isDark ? TichColors.darkSurface : TichColors.white,
        borderRadius: TichRadius.borderRadiusSm,
        border: Border(
          left: BorderSide(color: _accent, width: 2),
          top: BorderSide(
            color: _accent.withValues(alpha: isDark ? 0.45 : 0.3),
          ),
          right: BorderSide(
            color: _accent.withValues(alpha: isDark ? 0.45 : 0.3),
          ),
          bottom: BorderSide(
            color: _accent.withValues(alpha: isDark ? 0.45 : 0.3),
          ),
        ),
      ),
      padding: EdgeInsets.symmetric(
        horizontal: compact ? 3 : 5,
        vertical: compact ? 4 : 5,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title.isNotEmpty ? title : 'Session',
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
            style: tichUi(
              fontSize: compact ? 9 : 10,
              fontWeight: FontWeight.w700,
              height: 1.15,
            ),
          ),
          if (name.isNotEmpty && code.isNotEmpty) ...[
            const SizedBox(height: 2),
            Text(
              name,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style: tichUi(
                fontSize: compact ? 8 : 9,
                color: TichColors.neutralMuted,
                height: 1.15,
              ),
            ),
          ],
          if (room.isNotEmpty) ...[
            const SizedBox(height: 2),
            Text(
              room,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: tichUi(
                fontSize: compact ? 8 : 9,
                color: TichColors.neutralMuted,
              ),
            ),
          ],
        ],
      ),
    );
  }
}
