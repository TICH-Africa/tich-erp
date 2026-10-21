import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../data/api_client.dart';
import '../../data/employee_api.dart';
import '../../theme/tich_theme.dart';
import '../widgets/date_time_pickers.dart';
import '../widgets/tich_live_refresh.dart';
import '../widgets/tich_widgets.dart';

class TimeLogDetailPage extends StatefulWidget {
  const TimeLogDetailPage({
    super.key,
    required this.api,
    required this.timeLogId,
  });

  final EmployeeApi api;
  final int timeLogId;

  @override
  State<TimeLogDetailPage> createState() => _TimeLogDetailPageState();
}

class _TimeLogDetailPageState extends State<TimeLogDetailPage> {
  late Future<Map<String, dynamic>> _future;
  Object? _dataCache;
  final _dayEditors = <String, _DayEditor>{};
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<Map<String, dynamic>> _load() {
    return widget.api.timeLog(widget.timeLogId).then((res) {
      _hydrateDays(res['log'] as Map? ?? {});
      return res;
    });
  }

  void _reload() => setState(() => _future = _load());

  void _liveRefresh() {
    tichSilentReload(
      mounted: () => mounted,
      setState: setState,
      fetch: _load,
      onData: (data) => _dataCache = data,
    );
  }

  void _hydrateDays(Map log) {
    _dayEditors.clear();
    final days = log['days'] as List? ?? const [];
    for (final raw in days) {
      final day = Map<String, dynamic>.from(raw as Map);
      if (day['in_month'] != true || day['is_weekend'] == true) continue;
      final key = day['work_date']?.toString();
      if (key == null || key.isEmpty) continue;
      _dayEditors[key] = _DayEditor.fromDay(day);
    }
  }

  Map<String, dynamic> _daysPayload() {
    final out = <String, dynamic>{};
    for (final e in _dayEditors.entries) {
      out[e.key] = e.value.toPayload();
    }
    return out;
  }

  Future<void> _saveDraft() async {
    setState(() => _busy = true);
    try {
      final res = await widget.api.updateTimeLog(
        widget.timeLogId,
        days: _daysPayload(),
      );
      if (!mounted) return;
      _hydrateDays(res['log'] as Map? ?? {});
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(res['message']?.toString() ?? 'Saved')),
      );
      setState(() => _future = Future.value(res));
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _submit(Map log) async {
    final selfEndorse = log['can_self_endorse'] == true;
    final ok = await showAdaptiveConfirm<bool>(
      context: context,
      title: 'Submit to HR?',
      content: Text(
        selfEndorse
            ? 'Your signature and today’s date will be recorded. As HOD you will also self-endorse and lock this log.'
            : 'Your signature and today’s date will be recorded.',
      ),
      confirmLabel: 'Submit',
    );
    if (ok != true) return;

    setState(() => _busy = true);
    try {
      final res = await widget.api.submitTimeLog(
        widget.timeLogId,
        days: _daysPayload(),
        selfEndorse: selfEndorse,
      );
      if (!mounted) return;
      _hydrateDays(res['log'] as Map? ?? {});
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(res['message']?.toString() ?? 'Submitted')),
      );
      setState(() => _future = Future.value(res));
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  void dispose() {
    for (final e in _dayEditors.values) {
      e.dispose();
    }
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return TichLiveRefreshBinder(
      onLiveRefresh: _liveRefresh,
      enabled: !_busy,
      child: AdaptiveScaffold(
        title: 'Weekly time log',
        body: FutureBuilder<Map<String, dynamic>>(
          future: _future,
          builder: (context, snap) {
            final merged = tichMergeSnapshot(snap, _dataCache as dynamic);
            if (tichShowLoadingSpinner(snap, merged)) { return tichInstantWaitBody(onRefresh: () async => _reload()); }
            if (merged == null && snap.hasError) {
              return TichErrorRefresh(
                message: snap.error.toString(),
                onRetry: _reload,
              );
            }
            final data = merged! as Map<String, dynamic>;
            final log = Map<String, dynamic>.from(data['log'] as Map? ?? {});
            final editable = log['editable'] == true;
            final canSubmit = log['can_submit'] == true;
            final departments = (log['departments'] as List? ?? const [])
                .map((e) => Map<String, dynamic>.from(e as Map))
                .toList();
            final status = log['status']?.toString() ?? '';

            if (_dayEditors.isEmpty) {
              _hydrateDays(log);
            }

            return ListView(
              padding: const EdgeInsets.all(16),
              children: [
                TichCard(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        log['log_code']?.toString() ?? 'Time log',
                        style: tichUi(fontWeight: FontWeight.w700, fontSize: 16),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        '${log['week_ref'] ?? ''} · ${formatDate(log['period_start'])} – ${formatDate(log['period_end'])}',
                        style: tichUi(fontSize: 13, color: TichColors.neutralMuted),
                      ),
                      const SizedBox(height: 8),
                      Row(
                        children: [
                          TichStatusChip(status, tone: toneForStatus(status)),
                          const SizedBox(width: 8),
                          if (log['total_hours'] != null)
                            Text(
                              '${log['total_hours']} h total',
                              style: tichUi(fontWeight: FontWeight.w600),
                            ),
                        ],
                      ),
                      if ((log['hr_notes']?.toString() ?? '').isNotEmpty &&
                          status == 'returned') ...[
                        const SizedBox(height: 10),
                        Text(
                          'HR: ${log['hr_notes']}',
                          style: tichUi(
                            fontSize: 13,
                            color: TichColors.blueDark,
                          ),
                        ),
                      ],
                    ],
                  ),
                ),
                const SizedBox(height: 16),
                const TichSectionHeader('Daily entries'),
                ..._dayEditors.entries.map((entry) {
                  final editor = entry.value;
                  return Padding(
                    padding: const EdgeInsets.only(bottom: 10),
                    child: TichCard(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            editor.dayLabel,
                            style: tichUi(fontWeight: FontWeight.w700),
                          ),
                          Text(
                            formatDate(entry.key),
                            style: tichUi(
                              fontSize: 12,
                              color: TichColors.neutralMuted,
                            ),
                          ),
                          const SizedBox(height: 10),
                          Row(
                            children: [
                              Expanded(
                                child: TextField(
                                  controller: editor.timeIn,
                                  enabled: editable && !_busy,
                                  decoration: const InputDecoration(
                                    labelText: 'Time in',
                                    hintText: '08:00',
                                  ),
                                ),
                              ),
                              const SizedBox(width: 10),
                              Expanded(
                                child: TextField(
                                  controller: editor.timeOut,
                                  enabled: editable && !_busy,
                                  decoration: const InputDecoration(
                                    labelText: 'Time out',
                                    hintText: '17:00',
                                  ),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 10),
                          TextField(
                            controller: editor.tasks,
                            enabled: editable && !_busy,
                            maxLines: 3,
                            decoration: const InputDecoration(
                              labelText: 'Tasks accomplished',
                            ),
                          ),
                          if (departments.isNotEmpty) ...[
                            const SizedBox(height: 10),
                            Text(
                              'Departments',
                              style: tichUi(
                                fontSize: 12,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                            Wrap(
                              spacing: 6,
                              runSpacing: 4,
                              children: [
                                for (final dept in departments)
                                  FilterChip(
                                    label: Text(
                                      dept['code']?.toString() ??
                                          dept['name']?.toString() ??
                                          'Dept',
                                      style: const TextStyle(fontSize: 12),
                                    ),
                                    selected: editor.deptIds
                                        .contains(dept['id'] as int),
                                    onSelected: editable && !_busy
                                        ? (v) {
                                            setState(() {
                                              final id = dept['id'] as int;
                                              if (v) {
                                                editor.deptIds.add(id);
                                              } else {
                                                editor.deptIds.remove(id);
                                              }
                                            });
                                          }
                                        : null,
                                  ),
                              ],
                            ),
                          ],
                        ],
                      ),
                    ),
                  );
                }),
                if (editable) ...[
                  const SizedBox(height: 8),
                  OutlinedButton(
                    onPressed: _busy ? null : _saveDraft,
                    child: _busy
                        ? const SizedBox(
                            width: 18,
                            height: 18,
                            child: AdaptiveProgress(),
                          )
                        : const Text('Save draft'),
                  ),
                  if (canSubmit) ...[
                    const SizedBox(height: 8),
                    AdaptiveFilledButton(
                      onPressed: _busy ? null : () => _submit(log),
                      child: const Text('Submit to HR'),
                    ),
                  ],
                ] else
                  Padding(
                    padding: const EdgeInsets.only(top: 8),
                    child: Text(
                      log['manager_signed_at'] != null
                          ? 'Locked after line-manager endorsement.'
                          : 'This log is read-only.',
                      style: tichUi(color: TichColors.neutralMuted),
                    ),
                  ),
              ],
            );
          },
        ),
      ),
    );
  }
}

class _DayEditor {
  _DayEditor({
    required this.dayLabel,
    required this.timeIn,
    required this.timeOut,
    required this.tasks,
    required this.deptIds,
  });

  final String dayLabel;
  final TextEditingController timeIn;
  final TextEditingController timeOut;
  final TextEditingController tasks;
  final Set<int> deptIds;

  factory _DayEditor.fromDay(Map<String, dynamic> day) {
    final deptRaw = day['department_ids'] as List? ?? const [];
    return _DayEditor(
      dayLabel: day['day_label']?.toString() ?? 'Day',
      timeIn: TextEditingController(text: day['time_in']?.toString() ?? ''),
      timeOut: TextEditingController(text: day['time_out']?.toString() ?? ''),
      tasks: TextEditingController(
        text: day['tasks_accomplished']?.toString() ?? '',
      ),
      deptIds: deptRaw.map((e) => (e as num).toInt()).toSet(),
    );
  }

  Map<String, dynamic> toPayload() {
    return {
      if (timeIn.text.trim().isNotEmpty) 'time_in': timeIn.text.trim(),
      if (timeOut.text.trim().isNotEmpty) 'time_out': timeOut.text.trim(),
      if (tasks.text.trim().isNotEmpty) 'tasks_accomplished': tasks.text.trim(),
      'department_ids': deptIds.toList(),
    };
  }

  void dispose() {
    timeIn.dispose();
    timeOut.dispose();
    tasks.dispose();
  }
}
