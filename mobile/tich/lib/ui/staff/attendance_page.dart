import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../data/api_client.dart';
import '../../data/staff_api.dart';
import '../../theme/tich_theme.dart';
import '../widgets/date_time_pickers.dart';
import '../widgets/tich_widgets.dart';
import '../widgets/tich_live_refresh.dart';
import 'attendance_session_page.dart';
import 'staff_helpers.dart';

class StaffAttendancePage extends StatefulWidget {
  const StaffAttendancePage({super.key, required this.api});

  final StaffApi api;

  @override
  State<StaffAttendancePage> createState() => _StaffAttendancePageState();
}

class _StaffAttendancePageState extends State<StaffAttendancePage> {
  late Future<Map<String, dynamic>> _future;
  Object? _dataCache;
  bool _syncing = false;

  @override
  void initState() {
    super.initState();
    _future = widget.api.attendance();
  }

  void _reload() => setState(() => _future = widget.api.attendance());

  void _liveRefresh() {
    tichSilentReload(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.attendance(),
      onData: (data) => _dataCache = data,
    );
  }

  Future<void> _sync() async {
    setState(() => _syncing = true);
    try {
      final data = await widget.api.syncAttendance();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(data['message']?.toString() ?? 'Synced.'),
        ),
      );
      setState(() => _future = Future.value(data));
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) setState(() => _syncing = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return TichLiveRefreshBinder(
      onLiveRefresh: _liveRefresh,
      child: AdaptiveScaffold(
      title: 'Class attendance',
      actions: [
        IconButton(
          tooltip: 'Sync from timetable',
          onPressed: _syncing ? null : _sync,
          icon: _syncing
              ? const SizedBox(
                  width: 18,
                  height: 18,
                  child: CircularProgressIndicator(strokeWidth: 2),
                )
              : const Icon(Icons.sync),
        ),
      ],
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          final merged = tichMergeSnapshot(snap, _dataCache as dynamic);
          if (merged == null && snap.connectionState != ConnectionState.done) {
            return staffLoadingBody();
          }
          if (merged == null && snap.hasError) {
            return staffErrorBody(snap.error, _reload);
          }
          final data = merged! as Map<String, dynamic>;
          final sessions = data['sessions'] as List? ?? const [];
          final upcoming = data['upcoming'] as List? ?? const [];

          return RefreshIndicator(
            onRefresh: () async => _reload(),
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                const SizedBox(height: 12),
                if (upcoming.isNotEmpty) ...[
                  const TichSectionHeader('Upcoming / recent'),
                  ...upcoming.take(6).map((raw) {
                    final s = Map<String, dynamic>.from(raw as Map);
                    final id = (s['id'] as num?)?.toInt();
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 8),
                      child: TichCard(
                        onTap: id == null
                            ? null
                            : () async {
                                await Navigator.of(context).push(
                                  MaterialPageRoute(
                                    builder: (_) => StaffAttendanceSessionPage(
                                      api: widget.api,
                                      sessionId: id,
                                    ),
                                  ),
                                );
                                _reload();
                              },
                        child: _sessionTile(s),
                      ),
                    );
                  }),
                ],
                const TichSectionHeader('All sessions'),
                if (sessions.isEmpty)
                  const TichCard(
                    child: TichEmpty(
                      'No class sessions yet. Sync from timetable or create from an approved lesson plan.',
                    ),
                  )
                else
                  ...sessions.map((raw) {
                    final s = Map<String, dynamic>.from(raw as Map);
                    final id = (s['id'] as num?)?.toInt();
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 8),
                      child: TichCard(
                        onTap: id == null
                            ? null
                            : () async {
                                await Navigator.of(context).push(
                                  MaterialPageRoute(
                                    builder: (_) => StaffAttendanceSessionPage(
                                      api: widget.api,
                                      sessionId: id,
                                    ),
                                  ),
                                );
                                _reload();
                              },
                        child: _sessionTile(s),
                      ),
                    );
                  }),
              ],
            ),
          );
        },
      ),
      ),
    );
  }

  Widget _sessionTile(Map<String, dynamic> s) {
    return Row(
      children: [
        Expanded(
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
              const SizedBox(height: 4),
              Text(
                '${formatDate(s['session_date'])}'
                '${s['start_time'] != null ? ' · ${s['start_time'].toString().substring(0, s['start_time'].toString().length.clamp(0, 5))}' : ''}',
                style: tichUi(fontSize: 12, color: TichColors.neutralMuted),
              ),
            ],
          ),
        ),
        TichStatusChip(
          s['verification_status']?.toString() ??
              (s['is_locked'] == true ? 'locked' : 'open'),
          tone: s['is_locked'] == true
              ? TichChipTone.success
              : TichChipTone.info,
        ),
      ],
    );
  }
}
