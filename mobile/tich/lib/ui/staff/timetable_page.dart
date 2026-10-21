import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../data/staff_api.dart';
import '../../theme/tich_theme.dart';
import '../widgets/tich_widgets.dart';
import '../widgets/tich_live_refresh.dart';
import 'staff_helpers.dart';
import 'staff_timetable_grid.dart';

class StaffTimetablePage extends StatefulWidget {
  const StaffTimetablePage({super.key, required this.api});

  final StaffApi api;

  @override
  State<StaffTimetablePage> createState() => _StaffTimetablePageState();
}

class _StaffTimetablePageState extends State<StaffTimetablePage> {
  late Future<Map<String, dynamic>> _future;
  Object? _dataCache;

  @override
  void initState() {
    super.initState();
    staffPrimeScreen(
      persistKey: 'staff.timetable',
      setCache: (c) => _dataCache = c,
      setFuture: (f) => _future = f,
      fetch: () => widget.api.timetable(),
    );
  }

  Future<void> _reload() => staffCachedReload(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.timetable(),
      onData: (data) => _dataCache = data,
      persistKey: 'staff.timetable',
    );

  void _liveRefresh() {
    tichSilentReload(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.timetable(),
      onData: (data) => _dataCache = data,
    );
  }

  List<Map<String, dynamic>> _parseSessions(Map<String, dynamic> data) {
    final sessions = (data['sessions'] as List? ?? const [])
        .map((e) => Map<String, dynamic>.from(e as Map))
        .toList();
    sessions.sort((a, b) {
      final d = ((a['day_of_week'] as num?)?.toInt() ?? 0)
          .compareTo((b['day_of_week'] as num?)?.toInt() ?? 0);
      if (d != 0) return d;
      return '${a['start_time']}'.compareTo('${b['start_time']}');
    });
    return sessions;
  }

  void _openExpandedGrid(
    List<Map<String, dynamic>> sessions,
    Map? dayLabels,
  ) {
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (ctx) => Scaffold(
          appBar: AppBar(
            title: const Text('Weekly timetable'),
          ),
          body: SafeArea(
            child: Padding(
              padding: const EdgeInsets.all(12),
              child: StaffTimetableGrid(
                sessions: sessions,
                dayLabels: dayLabels,
                compact: false,
              ),
            ),
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return TichLiveRefreshBinder(
      onLiveRefresh: _liveRefresh,
      child: AdaptiveScaffold(
        title: 'Timetable',
        body: FutureBuilder<Map<String, dynamic>>(
          future: _future,
          builder: (context, snap) {
            final merged = tichMergeSnapshot(snap, _dataCache as dynamic, persistKey: 'staff.timetable');
            if (tichShowLoadingSpinner(snap, merged)) { return staffLoadingBody(onRefresh: () async => _reload()); }
            if (merged == null && snap.hasError) {
              return staffErrorBody(snap.error, _reload);
            }
            final data = merged! as Map<String, dynamic>;
            final sessions = _parseSessions(data);
            final dayLabels = data['day_labels'] is Map
                ? Map.from(data['day_labels'] as Map)
                : null;
            final weekCount = sessions
                .where(
                  (s) => staffTimetableWeekdays
                      .contains((s['day_of_week'] as num?)?.toInt()),
                )
                .length;
            final weekendSessions = sessions.length - weekCount;

            return RefreshIndicator(
              onRefresh: () async => _reload(),
              child: sessions.isEmpty
                  ? ListView(
                      physics: const AlwaysScrollableScrollPhysics(),
                      children: const [
                        TichCard(child: TichEmpty('No timetable sessions assigned.')),
                      ],
                    )
                  : ListView(
                      physics: const AlwaysScrollableScrollPhysics(),
                      padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
                      children: [
                        Row(
                          children: [
                            Expanded(
                              child: Text(
                                'Monday – Friday',
                                style: TichTheme.display(fontSize: 16),
                              ),
                            ),
                            TextButton.icon(
                              onPressed: () => _openExpandedGrid(
                                sessions,
                                dayLabels,
                              ),
                              icon: const Icon(Icons.open_in_full, size: 18),
                              label: const Text('Expand'),
                            ),
                          ],
                        ),
                        const SizedBox(height: 4),
                        Text(
                          '$weekCount session${weekCount == 1 ? '' : 's'} this week'
                          '${weekendSessions > 0 ? ' · $weekendSessions Sat/Sun (below grid)' : ''}',
                          style: tichUi(
                            fontSize: 12,
                            color: TichColors.neutralMuted,
                          ),
                        ),
                        const SizedBox(height: 12),
                        TichCard(
                          padding: EdgeInsets.zero,
                          child: ClipRRect(
                            borderRadius: TichRadius.borderRadiusLg,
                            child: StaffTimetableGrid(
                              sessions: sessions,
                              dayLabels: dayLabels,
                              compact: true,
                            ),
                          ),
                        ),
                        const SizedBox(height: 8),
                        Text(
                          'Days on the left, your session times across the top.',
                          style: tichUi(
                            fontSize: 11,
                            color: TichColors.neutralMuted,
                          ),
                        ),
                        if (weekendSessions > 0) ...[
                          const SizedBox(height: 20),
                          Text(
                            'Weekend sessions',
                            style: TichTheme.display(fontSize: 15),
                          ),
                          const SizedBox(height: 8),
                          ...sessions.where((s) {
                            final day = (s['day_of_week'] as num?)?.toInt() ?? 0;
                            return !staffTimetableWeekdays.contains(day);
                          }).map(
                            (s) => Padding(
                              padding: const EdgeInsets.only(bottom: 8),
                              child: TichCard(
                                child: _WeekendSessionRow(
                                  session: s,
                                  dayLabels: dayLabels,
                                ),
                              ),
                            ),
                          ),
                        ],
                      ],
                    ),
            );
          },
        ),
      ),
    );
  }
}

class _WeekendSessionRow extends StatelessWidget {
  const _WeekendSessionRow({
    required this.session,
    required this.dayLabels,
  });

  final Map<String, dynamic> session;
  final Map? dayLabels;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          [
            session['unit_code'],
            session['unit_name'],
          ].where((e) => e != null && '$e'.isNotEmpty).join(' · '),
          style: tichUi(fontWeight: FontWeight.w700),
        ),
        const SizedBox(height: 6),
        Text(
          '${dayLabel((session['day_of_week'] as num?)?.toInt(), dayLabels)}'
          ' · ${session['start_time'] ?? '—'} – ${session['end_time'] ?? '—'}',
          style: tichUi(fontSize: 13),
        ),
        if ((session['room']?.toString() ?? '').isNotEmpty ||
            (session['program']?.toString() ?? '').isNotEmpty) ...[
          const SizedBox(height: 4),
          Text(
            [
              if ((session['room']?.toString() ?? '').isNotEmpty)
                'Room ${session['room']}',
              session['program'],
            ].whereType<String>().where((e) => e.isNotEmpty).join(' · '),
            style: tichUi(fontSize: 12, color: TichColors.neutralMuted),
          ),
        ],
      ],
    );
  }
}
