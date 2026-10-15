import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../data/api_client.dart';
import '../../data/staff_api.dart';
import '../../theme/tich_theme.dart';
import '../widgets/date_time_pickers.dart';
import '../widgets/tich_widgets.dart';
import 'staff_helpers.dart';

class StaffAttendanceSessionPage extends StatefulWidget {
  const StaffAttendanceSessionPage({
    super.key,
    required this.api,
    required this.sessionId,
  });

  final StaffApi api;
  final int sessionId;

  @override
  State<StaffAttendanceSessionPage> createState() =>
      _StaffAttendanceSessionPageState();
}

class _StaffAttendanceSessionPageState
    extends State<StaffAttendanceSessionPage> {
  late Future<Map<String, dynamic>> _future;
  final Set<int> _present = {};
  bool _busy = false;
  bool _locked = false;

  @override
  void initState() {
    super.initState();
    _future = widget.api.attendanceSession(widget.sessionId).then((data) {
      final records = data['records'] as List? ?? const [];
      _present
        ..clear()
        ..addAll(
          records
              .map((e) => Map<String, dynamic>.from(e as Map))
              .where((r) => r['is_present'] == true)
              .map((r) => (r['student_id'] as num).toInt()),
        );
      final session =
          Map<String, dynamic>.from(data['session'] as Map? ?? {});
      _locked = session['is_locked'] == true;
      return data;
    });
  }

  void _reload() {
    setState(() {
      _future = widget.api.attendanceSession(widget.sessionId).then((data) {
        final records = data['records'] as List? ?? const [];
        _present
          ..clear()
          ..addAll(
            records
                .map((e) => Map<String, dynamic>.from(e as Map))
                .where((r) => r['is_present'] == true)
                .map((r) => (r['student_id'] as num).toInt()),
          );
        final session =
            Map<String, dynamic>.from(data['session'] as Map? ?? {});
        _locked = session['is_locked'] == true;
        return data;
      });
    });
  }

  Future<void> _save({bool lock = false}) async {
    setState(() => _busy = true);
    try {
      await widget.api.saveAttendance(
        widget.sessionId,
        presentStudentIds: _present.toList(),
        lock: lock,
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(lock ? 'Attendance submitted.' : 'Roster saved.'),
        ),
      );
      _reload();
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'Attendance session',
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          if (snap.connectionState != ConnectionState.done) {
            return staffLoadingBody();
          }
          if (snap.hasError) {
            return staffErrorBody(snap.error, _reload);
          }
          final session =
              Map<String, dynamic>.from(snap.data!['session'] as Map? ?? {});
          final records = snap.data!['records'] as List? ?? const [];

          return Column(
            children: [
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
                child: TichCard(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        [
                          session['unit_code'],
                          session['unit_name'],
                        ]
                            .where((e) => e != null && '$e'.isNotEmpty)
                            .join(' · '),
                        style: TichTheme.display(fontSize: 16),
                      ),
                      const SizedBox(height: 6),
                      Text(
                        '${formatDate(session['session_date'])}'
                        ' · ${session['start_time'] ?? '—'} – ${session['end_time'] ?? '—'}',
                        style: tichUi(fontSize: 13),
                      ),
                      if ((session['venue']?.toString() ?? '').isNotEmpty) ...[
                        const SizedBox(height: 4),
                        Text(
                          'Venue: ${session['venue']}',
                          style: tichUi(
                            fontSize: 12,
                            color: TichColors.neutralMuted,
                          ),
                        ),
                      ],
                      const SizedBox(height: 8),
                      Text(
                        '${_present.length} / ${records.length} present',
                        style: tichUi(fontWeight: FontWeight.w700),
                      ),
                    ],
                  ),
                ),
              ),
              Expanded(
                child: ListView.separated(
                  padding: const EdgeInsets.symmetric(horizontal: 16),
                  itemCount: records.length,
                  separatorBuilder: (_, __) => const SizedBox(height: 6),
                  itemBuilder: (context, i) {
                    final r = Map<String, dynamic>.from(records[i] as Map);
                    final id = (r['student_id'] as num).toInt();
                    final checked = _present.contains(id);
                    return TichCard(
                      child: CheckboxListTile(
                        contentPadding: EdgeInsets.zero,
                        value: checked,
                        onChanged: _locked || _busy
                            ? null
                            : (v) {
                                setState(() {
                                  if (v == true) {
                                    _present.add(id);
                                  } else {
                                    _present.remove(id);
                                  }
                                });
                              },
                        title: Text(
                          r['student_name']?.toString() ?? 'Student',
                          style: tichUi(fontWeight: FontWeight.w600),
                        ),
                        subtitle: Text(
                          r['registration_number']?.toString() ?? '',
                          style: tichUi(fontSize: 12),
                        ),
                      ),
                    );
                  },
                ),
              ),
              if (!_locked)
                SafeArea(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Row(
                      children: [
                        Expanded(
                          child: OutlinedButton(
                            onPressed: _busy ? null : () => _save(),
                            child: const Text('Save roster'),
                          ),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: FilledButton(
                            onPressed: _busy ? null : () => _save(lock: true),
                            child: const Text('Submit'),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
            ],
          );
        },
      ),
    );
  }
}
