import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../data/staff_api.dart';
import '../../theme/tich_theme.dart';
import '../widgets/tich_widgets.dart';
import 'staff_helpers.dart';

class StaffTimetablePage extends StatefulWidget {
  const StaffTimetablePage({super.key, required this.api});

  final StaffApi api;

  @override
  State<StaffTimetablePage> createState() => _StaffTimetablePageState();
}

class _StaffTimetablePageState extends State<StaffTimetablePage> {
  late Future<Map<String, dynamic>> _future;

  @override
  void initState() {
    super.initState();
    _future = widget.api.timetable();
  }

  void _reload() => setState(() => _future = widget.api.timetable());

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'Timetable',
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          if (snap.connectionState != ConnectionState.done) {
            return staffLoadingBody();
          }
          if (snap.hasError) {
            return staffErrorBody(snap.error, _reload);
          }
          final sessions = [...(snap.data!['sessions'] as List? ?? const [])];
          final dayLabels = snap.data!['day_labels'] is Map
              ? Map.from(snap.data!['day_labels'] as Map)
              : null;
          sessions.sort((a, b) {
            final am = Map<String, dynamic>.from(a as Map);
            final bm = Map<String, dynamic>.from(b as Map);
            final d = ((am['day_of_week'] as num?)?.toInt() ?? 0)
                .compareTo((bm['day_of_week'] as num?)?.toInt() ?? 0);
            if (d != 0) return d;
            return '${am['start_time']}'.compareTo('${bm['start_time']}');
          });

          return RefreshIndicator(
            onRefresh: () async => _reload(),
            child: sessions.isEmpty
                ? ListView(
                    children: const [
                      TichCard(child: TichEmpty('No timetable sessions assigned.')),
                    ],
                  )
                : ListView.separated(
                    padding: const EdgeInsets.all(16),
                    itemCount: sessions.length,
                    separatorBuilder: (_, __) => const SizedBox(height: 8),
                    itemBuilder: (context, i) {
                      final s =
                          Map<String, dynamic>.from(sessions[i] as Map);
                      return TichCard(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              [
                                s['unit_code'],
                                s['unit_name'],
                              ]
                                  .where((e) => e != null && '$e'.isNotEmpty)
                                  .join(' · '),
                              style: tichUi(fontWeight: FontWeight.w700),
                            ),
                            const SizedBox(height: 6),
                            Text(
                              '${dayLabel((s['day_of_week'] as num?)?.toInt(), dayLabels)}'
                              ' · ${s['start_time'] ?? '—'} – ${s['end_time'] ?? '—'}',
                              style: tichUi(fontSize: 13),
                            ),
                            if ((s['room']?.toString() ?? '').isNotEmpty ||
                                (s['program']?.toString() ?? '').isNotEmpty) ...[
                              const SizedBox(height: 4),
                              Text(
                                [
                                  if ((s['room']?.toString() ?? '').isNotEmpty)
                                    'Room ${s['room']}',
                                  s['program'],
                                ]
                                    .whereType<String>()
                                    .where((e) => e.isNotEmpty)
                                    .join(' · '),
                                style: tichUi(
                                  fontSize: 12,
                                  color: TichColors.neutralMuted,
                                ),
                              ),
                            ],
                          ],
                        ),
                      );
                    },
                  ),
          );
        },
      ),
    );
  }
}
