import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../data/student_api.dart';
import '../../theme/tich_theme.dart';
import '../widgets/tich_widgets.dart';
import 'student_helpers.dart';

class StudentTimetablePage extends StatefulWidget {
  const StudentTimetablePage({super.key, required this.api});

  final StudentApi api;

  @override
  State<StudentTimetablePage> createState() => _StudentTimetablePageState();
}

class _StudentTimetablePageState extends State<StudentTimetablePage>
    with SingleTickerProviderStateMixin {
  late Future<Map<String, dynamic>> _future;
  late TabController _tabs;

  @override
  void initState() {
    super.initState();
    _tabs = TabController(length: 2, vsync: this);
    _future = widget.api.timetable();
  }

  @override
  void dispose() {
    _tabs.dispose();
    super.dispose();
  }

  void _reload() => setState(() => _future = widget.api.timetable());

  List<Map<String, dynamic>> _sessionsFor(
    List timetables,
    String kind,
  ) {
    final match = timetables.cast<dynamic>().map((e) => Map<String, dynamic>.from(e as Map)).where(
          (t) => (t['kind']?.toString() ?? '') == kind,
        );
    if (match.isEmpty) return const [];
    return (match.first['sessions'] as List? ?? const [])
        .map((e) => Map<String, dynamic>.from(e as Map))
        .toList();
  }

  Widget _sessionList(List<Map<String, dynamic>> sessions, Map? dayLabels) {
    if (sessions.isEmpty) {
      return const Center(child: TichEmpty('No sessions published yet.'));
    }
    sessions = [...sessions]..sort((a, b) {
        final d = ((a['day_of_week'] as num?)?.toInt() ?? 0)
            .compareTo((b['day_of_week'] as num?)?.toInt() ?? 0);
        if (d != 0) return d;
        return '${a['start_time']}'.compareTo('${b['start_time']}');
      });
    return ListView.separated(
      padding: const EdgeInsets.all(16),
      itemCount: sessions.length,
      separatorBuilder: (_, __) => const SizedBox(height: 8),
      itemBuilder: (context, i) {
        final s = sessions[i];
        return TichCard(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                [
                  s['unit_code'],
                  s['unit_name'],
                ].where((e) => e != null && '$e'.isNotEmpty).join(' · '),
                style: tichUi(fontWeight: FontWeight.w700),
              ),
              const SizedBox(height: 6),
              Text(
                '${dayName((s['day_of_week'] as num?)?.toInt(), dayLabels)}'
                ' · ${s['start_time'] ?? '—'} – ${s['end_time'] ?? '—'}',
                style: tichUi(fontSize: 13),
              ),
              if ((s['room']?.toString() ?? '').isNotEmpty ||
                  (s['staff_name']?.toString() ?? '').isNotEmpty) ...[
                const SizedBox(height: 4),
                Text(
                  [
                    if ((s['room']?.toString() ?? '').isNotEmpty) 'Room ${s['room']}',
                    s['staff_name'],
                  ].whereType<String>().where((e) => e.isNotEmpty).join(' · '),
                  style: tichUi(fontSize: 12, color: TichColors.neutralMuted),
                ),
              ],
            ],
          ),
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'Timetable',
      body: Column(
        children: [
          TabBar(
            controller: _tabs,
            labelColor: TichColors.green,
            tabs: const [
              Tab(text: 'Lesson'),
              Tab(text: 'Exam'),
            ],
          ),
          Expanded(
            child: FutureBuilder<Map<String, dynamic>>(
              future: _future,
              builder: (context, snap) {
                if (snap.connectionState != ConnectionState.done) {
                  return studentLoadingBody();
                }
                if (snap.hasError) {
                  return studentErrorBody(snap.error, _reload);
                }
                final tt = Map<String, dynamic>.from(
                  snap.data!['timetable'] as Map? ?? {},
                );
                final timetables = tt['timetables'] as List? ?? const [];
                final dayLabels =
                    tt['day_labels'] is Map ? Map.from(tt['day_labels'] as Map) : null;
                final provisional = tt['is_provisional'] == true;

                return Column(
                  children: [
                    if (provisional)
                      MaterialBanner(
                        content: const Text('Showing provisional draft timetable.'),
                        actions: [
                          TextButton(onPressed: _reload, child: const Text('Refresh')),
                        ],
                      ),
                    Expanded(
                      child: TabBarView(
                        controller: _tabs,
                        children: [
                          RefreshIndicator(
                            onRefresh: () async => _reload(),
                            child: _sessionList(
                              _sessionsFor(timetables, 'lesson'),
                              dayLabels,
                            ),
                          ),
                          RefreshIndicator(
                            onRefresh: () async => _reload(),
                            child: _sessionList(
                              _sessionsFor(timetables, 'exam'),
                              dayLabels,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}
