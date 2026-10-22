import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../data/api_client.dart';
import '../../theme/tich_theme.dart';
import '../../data/tich_screen_cache.dart';
import '../widgets/tich_live_refresh.dart';
import '../widgets/tich_widgets.dart';

Widget studentLoadingBody({Future<void> Function()? onRefresh}) =>
    tichInstantWaitBody(onRefresh: onRefresh);

Future<void> studentCachedReload({
  required bool Function() mounted,
  required void Function(VoidCallback fn) setState,
  required Future<Map<String, dynamic>> Function() fetch,
  required void Function(Map<String, dynamic> data) onData,
  String? persistKey,
}) =>
    tichRefreshCached<Map<String, dynamic>>(
      mounted: mounted,
      setState: setState,
      fetch: fetch,
      onData: onData,
      persistKey: persistKey,
    );

Map<String, dynamic>? studentReadCache(String key) =>
    TichScreenCache.read<Map<String, dynamic>>(key);

Future<Map<String, dynamic>> studentEnsure(
  String key,
  Future<Map<String, dynamic>> Function() fetch,
) =>
    TichScreenCache.ensure(key, fetch);

void studentPrimeScreen({
  required String persistKey,
  required void Function(Object? cache) setCache,
  required void Function(Future<Map<String, dynamic>> future) setFuture,
  required Future<Map<String, dynamic>> Function() fetch,
}) {
  TichScreenCache.primeMapScreen(
    persistKey: persistKey,
    applyCache: setCache,
    applyFuture: setFuture,
    fetch: fetch,
  );
}

Widget studentErrorBody(Object? error, VoidCallback onRetry) {
  return TichErrorRefresh(
    message: tichDescribeError(error),
    onRetry: onRetry,
  );
}

String money(dynamic value) {
  final n = value is num ? value.toDouble() : double.tryParse('$value') ?? 0;
  return 'KES ${n.toStringAsFixed(2)}';
}

/// Human-readable attendance band for student portal (matches mobile API labels).
String studentAttendanceStatusLabel(Map<String, dynamic> row) {
  final fromApi = row['status_label']?.toString().trim();
  if (fromApi != null && fromApi.isNotEmpty) return fromApi;

  final pct = row['attendance_percentage'] is num
      ? (row['attendance_percentage'] as num).toDouble()
      : double.tryParse('${row['attendance_percentage']}') ?? 0;
  if (pct >= 90) return 'Excellent';
  if (pct >= 75) return 'Good';
  if (pct >= 60) return 'Average';

  final flag = row['status_flag']?.toString().toLowerCase();
  if (flag == 'amber') return 'Needs improvement';
  return 'At risk';
}

TichChipTone studentAttendanceTone(Map<String, dynamic> row) {
  final pct = row['attendance_percentage'] is num
      ? (row['attendance_percentage'] as num).toDouble()
      : double.tryParse('${row['attendance_percentage']}') ?? 0;
  if (pct >= 90) return TichChipTone.success;
  if (pct >= 75) return TichChipTone.info;
  if (pct >= 60) return TichChipTone.warning;
  return TichChipTone.danger;
}

String dayName(int? day, Map? labels) {
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

List<Map<String, dynamic>> lessonSessionsFromTimetablePayload(
  Map<String, dynamic> data,
) {
  final tt = Map<String, dynamic>.from(data['timetable'] as Map? ?? {});
  final timetables = tt['timetables'] as List? ?? const [];
  final match = timetables
      .map((e) => Map<String, dynamic>.from(e as Map))
      .where((t) => (t['kind']?.toString() ?? '') == 'lesson');
  if (match.isEmpty) return const [];
  return (match.first['sessions'] as List? ?? const [])
      .map((e) => Map<String, dynamic>.from(e as Map))
      .toList();
}

/// Next upcoming lesson today (or null).
Map<String, dynamic>? nextLessonToday(List<Map<String, dynamic>> sessions) {
  final now = DateTime.now();
  final todayIso = now.weekday;
  final upcoming = sessions.where((s) {
    if ((s['day_of_week'] as num?)?.toInt() != todayIso) return false;
    final raw = s['start_time']?.toString() ?? '';
    final parts = raw.split(':');
    if (parts.length < 2) return false;
    final h = int.tryParse(parts[0]) ?? 0;
    final m = int.tryParse(parts[1]) ?? 0;
    final start = DateTime(now.year, now.month, now.day, h, m);
    return start.isAfter(now);
  }).toList()
    ..sort((a, b) => '${a['start_time']}'.compareTo('${b['start_time']}'));
  return upcoming.isEmpty ? null : upcoming.first;
}

class StudentStatTile extends StatelessWidget {
  const StudentStatTile({
    super.key,
    required this.label,
    required this.value,
    this.tone = TichChipTone.info,
  });

  final String label;
  final String value;
  final TichChipTone tone;

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
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style: tichUi(fontSize: 11, color: TichColors.neutralMuted),
            ),
            const SizedBox(height: 6),
            Text(
              value,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style: tichUi(fontWeight: FontWeight.w700, fontSize: 14),
            ),
          ],
        ),
      ),
    );
  }
}
