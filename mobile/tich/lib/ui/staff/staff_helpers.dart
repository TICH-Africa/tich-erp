import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../data/api_client.dart';
import '../../theme/tich_theme.dart';
import '../../data/tich_screen_cache.dart';
import '../widgets/tich_live_refresh.dart';
import '../widgets/tich_widgets.dart';

Widget staffLoadingBody({Future<void> Function()? onRefresh}) =>
    tichInstantWaitBody(onRefresh: onRefresh);

Future<void> staffCachedReload({
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

Map<String, dynamic>? staffReadCache(String key) =>
    TichScreenCache.read<Map<String, dynamic>>(key);

Future<Map<String, dynamic>> staffEnsure(
  String key,
  Future<Map<String, dynamic>> Function() fetch,
) =>
    TichScreenCache.ensure(key, fetch);

void staffPrimeScreen({
  required String persistKey,
  required void Function(Object? cache) setCache,
  required void Function(Future<Map<String, dynamic>> future) setFuture,
  required Future<Map<String, dynamic>> Function() fetch,
}) {
  setCache(staffReadCache(persistKey));
  setFuture(staffEnsure(persistKey, fetch));
}

Widget staffErrorBody(Object? error, VoidCallback onRetry) {
  return TichErrorRefresh(
    message: tichDescribeError(error),
    onRetry: onRetry,
  );
}

String? staffTrimmed(dynamic value) {
  if (value == null) return null;
  final text = value.toString().trim();
  return text.isEmpty ? null : text;
}

/// Cohort (intake) label from a unit allocation payload.
String? staffUnitCohort(Map<String, dynamic> unit) =>
    staffTrimmed(unit['cohort']) ?? staffTrimmed(unit['intake_label']);

/// "Cohort · Academic year" line for staff unit cards.
String staffContentUnitLine(Map<String, dynamic> item) {
  return [
    staffTrimmed(item['unit_code']),
    staffTrimmed(item['unit_name']),
  ].whereType<String>().join(' · ');
}

String? staffContentCohort(Map<String, dynamic> item) =>
    staffTrimmed(item['cohort']);

String staffUnitCohortYearLine(Map<String, dynamic> unit) {
  final cohort = staffUnitCohort(unit);
  final year = staffTrimmed(unit['academic_year']);
  return [
    ?cohort,
    ?year,
  ].join(' · ');
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
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style: tichUi(fontSize: 11, color: TichColors.neutralMuted),
            ),
            const SizedBox(height: 6),
            Text(
              value,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style: tichUi(fontWeight: FontWeight.w700, fontSize: 16),
            ),
          ],
        ),
      ),
    );
  }
}
