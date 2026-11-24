import 'dart:async';

import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../data/api_client.dart';
import '../../data/employee_api.dart';
import '../../theme/tich_theme.dart';
import '../widgets/date_time_pickers.dart';
import '../widgets/tich_live_refresh.dart';
import '../widgets/tich_widgets.dart';
import '../shell/employee_portal_signals.dart';
import 'portal_helpers.dart';

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
  bool _editable = false;
  Timer? _autosaveTimer;
  bool _autosaving = false;
  bool _autosaveQueued = false;
  String? _autosaveNote;

  /// Long enough that a person finishes a time or a sentence, short enough that
  /// the sheet is on the server before the screen is left.
  static const _autosaveDelay = Duration(milliseconds: 900);

  String get _cacheKey => 'portal.time_log.${widget.timeLogId}';

  @override
  void initState() {
    super.initState();
    portalPrimeScreen(
      persistKey: _cacheKey,
      setCache: (c) {
        _dataCache = c;
        final log = (c as Map<String, dynamic>?)?['log'] as Map?;
        if (log != null) _hydrateDays(Map<String, dynamic>.from(log));
      },
      setFuture: (f) => _future = f.then((res) {
        _hydrateDays(res['log'] as Map? ?? {});
        return res;
      }),
      fetch: _load,
      mounted: () => mounted,
      scheduleLiveRefresh: _liveRefresh,
    );
  }

  Future<Map<String, dynamic>> _load() {
    return widget.api.timeLog(widget.timeLogId).then((res) {
      _hydrateDays(res['log'] as Map? ?? {});
      return res;
    });
  }

  Future<void> _reload() => portalCachedReload(
        mounted: () => mounted,
        setState: setState,
        fetch: _load,
        onData: (data) {
          _dataCache = data;
          _hydrateDays(data['log'] as Map? ?? {});
        },
        persistKey: _cacheKey,
      );

  void _liveRefresh() {
    tichSilentReload(
      mounted: () => mounted,
      setState: setState,
      fetch: _load,
      onData: (data) {
        _dataCache = data;
        _hydrateDays(data['log'] as Map? ?? {});
      },
      persistKey: _cacheKey,
    );
  }

  /// Merge the server's sheet into the editors on screen. A day the person has
  /// typed in keeps its text - the background refresh must never overwrite work
  /// that has not been saved yet.
  void _hydrateDays(Map log) {
    _editable = log['editable'] == true;
    final days = log['days'] as List? ?? const [];
    final seen = <String>{};

    for (final raw in days) {
      final day = Map<String, dynamic>.from(raw as Map);
      if (day['in_month'] != true || day['is_weekend'] == true) continue;
      final key = day['work_date']?.toString();
      if (key == null || key.isEmpty) continue;
      seen.add(key);

      final editor = _dayEditors[key];
      if (editor == null) {
        _dayEditors[key] = _DayEditor.fromDay(day, _onDayChanged);
      } else {
        editor.applyDay(day);
      }
    }

    for (final stale in _dayEditors.keys.where((k) => !seen.contains(k)).toList()) {
      _dayEditors.remove(stale)?.dispose();
    }
  }

  Map<String, dynamic> _daysPayload() {
    final out = <String, dynamic>{};
    for (final e in _dayEditors.entries) {
      out[e.key] = e.value.toPayload();
    }
    return out;
  }

  bool get _hasUnsavedTyping => _dayEditors.values.any((e) => e.dirty);

  void _onDayChanged() {
    if (!_editable || _busy) return;
    _autosaveTimer?.cancel();
    _autosaveTimer = Timer(_autosaveDelay, _autosave);
  }

  static String _clockNow() {
    final now = DateTime.now();
    return '${now.hour.toString().padLeft(2, '0')}:'
        '${now.minute.toString().padLeft(2, '0')}';
  }

  Future<void> _autosave() async {
    if (_autosaving) {
      _autosaveQueued = true;
      return;
    }
    if (!mounted || !_editable || !_hasUnsavedTyping) return;

    _autosaving = true;
    setState(() => _autosaveNote = 'Saving…');
    try {
      final res = await widget.api.updateTimeLog(
        widget.timeLogId,
        days: _daysPayload(),
      );
      if (!mounted) return;
      for (final editor in _dayEditors.values) {
        editor.markSaved();
      }
      _dataCache = res;
      _hydrateDays(Map<String, dynamic>.from(res['log'] as Map? ?? const {}));
      setState(() {
        _future = Future.value(res);
        _autosaveNote = 'Auto-saved ${_clockNow()}';
      });
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() => _autosaveNote = 'Not saved yet — ${e.message}');
    } finally {
      _autosaving = false;
      if (_autosaveQueued) {
        _autosaveQueued = false;
        _onDayChanged();
      }
    }
  }

  Future<void> _saveDraft() async {
    _autosaveTimer?.cancel();
    setState(() => _busy = true);
    try {
      final res = await widget.api.updateTimeLog(
        widget.timeLogId,
        days: _daysPayload(),
      );
      if (!mounted) return;
      for (final editor in _dayEditors.values) {
        editor.markSaved();
      }
      _hydrateDays(res['log'] as Map? ?? {});
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(res['message']?.toString() ?? 'Saved')),
      );
      setState(() {
        _future = Future.value(res);
        _autosaveNote = null;
      });
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
            ? 'Your signature and today’s date will be recorded. As HOD you will also self-endorse and lock this sheet.'
            : 'Your signature and today’s date will be recorded.',
      ),
      confirmLabel: 'Submit',
    );
    if (ok != true) return;

    _autosaveTimer?.cancel();
    setState(() => _busy = true);
    try {
      final res = await widget.api.submitTimeLog(
        widget.timeLogId,
        days: _daysPayload(),
        selfEndorse: selfEndorse,
      );
      if (!mounted) return;
      for (final editor in _dayEditors.values) {
        editor.markSaved();
      }
      _hydrateDays(res['log'] as Map? ?? {});
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(res['message']?.toString() ?? 'Submitted')),
      );
      setState(() {
        _future = Future.value(res);
        _autosaveNote = null;
      });
      EmployeePortalNavSignals.onTimeLogComplianceChanged?.call();
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  void dispose() {
    _autosaveTimer?.cancel();
    if (_editable && _hasUnsavedTyping) {
      // The last keystrokes belong on the server even if the debounce never fired.
      unawaited(widget.api
          .updateTimeLog(widget.timeLogId, days: _daysPayload())
          .catchError((_) => <String, dynamic>{}));
    }
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
        title: 'Weekly time sheet',
        body: FutureBuilder<Map<String, dynamic>>(
          future: _future,
          builder: (context, snap) {
            final merged = tichMergeSnapshot(
              snap,
              _dataCache as dynamic,
              persistKey: _cacheKey,
            );
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
                        log['log_code']?.toString() ?? 'Time sheet',
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
                      if (_autosaveNote != null) ...[
                        const SizedBox(height: 6),
                        Text(
                          _autosaveNote!,
                          style: tichUi(
                            fontSize: 12,
                            color: TichColors.neutralMuted,
                          ),
                        ),
                      ],
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
                if (editable) ...[
                  const SizedBox(height: 4),
                  Text(
                    'What you type saves by itself, a moment after you stop.',
                    style: tichUi(
                      fontSize: 12,
                      color: TichColors.neutralMuted,
                    ),
                  ),
                ],
                const SizedBox(height: 10),
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
                                  enabled: editable && !_busy && !editor.timeInLocked,
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
                                  enabled: editable && !_busy && !editor.timeOutLocked,
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
                                            _onDayChanged();
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
                          : 'This sheet is read-only.',
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
    required this.clockIn,
    required this.clockOut,
    required this.savedIn,
    required this.savedOut,
    required this.savedTasks,
    required Iterable<int> savedDepts,
  }) : savedDepts = savedDepts.toSet();

  String dayLabel;
  final TextEditingController timeIn;
  final TextEditingController timeOut;
  final TextEditingController tasks;
  final Set<int> deptIds;
  void Function()? onChanged;

  /// The clocked times for this day, when the person actually clocked. Those
  /// hours belong to the clock, so the fields carry them and stay unread-only
  /// for typing - only a day with no clock record is filled in by hand.
  String? clockIn;
  String? clockOut;

  /// What the server holds for this day. Text that differs from it is unsaved
  /// work, and a half-typed time falls back to it instead of going out as noise.
  String savedIn;
  String savedOut;
  String savedTasks;
  Set<int> savedDepts;

  bool get timeInLocked => clockIn != null;

  bool get timeOutLocked => clockOut != null;

  bool get dirty =>
      timeIn.text.trim() != savedIn ||
      timeOut.text.trim() != savedOut ||
      tasks.text.trim() != savedTasks ||
      !_sameIds(deptIds, savedDepts);

  /// `08` while the person is still typing is not a time yet, so it waits for
  /// `08:00`. An emptied field is a deliberate clear.
  String? get sendableTimeIn =>
      _validTime(timeIn.text) ?? (timeIn.text.trim().isEmpty ? '' : null);

  String? get sendableTimeOut =>
      _validTime(timeOut.text) ?? (timeOut.text.trim().isEmpty ? '' : null);

  static String? _validTime(String raw) {
    final value = raw.trim();
    return RegExp(r'^\d{1,2}:\d{2}$').hasMatch(value) ? value : null;
  }

  static bool _sameIds(Set<int> a, Set<int> b) =>
      a.length == b.length && a.every(b.contains);

  static String? _blankToNull(dynamic value) {
    final text = value?.toString() ?? '';
    return text.isEmpty ? null : text;
  }

  static Set<int> _dayDepts(Map<String, dynamic> day) => (day['department_ids']
          as List? ?? const [])
      .map((e) => (e as num).toInt())
      .toSet();

  factory _DayEditor.fromDay(
    Map<String, dynamic> day,
    void Function() onChanged,
  ) {
    final clockIn = _blankToNull(day['clock_in']);
    final clockOut = _blankToNull(day['clock_out']);
    final inText = clockIn ?? (day['time_in']?.toString() ?? '');
    final outText = clockOut ?? (day['time_out']?.toString() ?? '');
    final tasksText = day['tasks_accomplished']?.toString() ?? '';
    final depts = _dayDepts(day);

    return _DayEditor(
      dayLabel: day['day_label']?.toString() ?? 'Day',
      timeIn: TextEditingController(text: inText),
      timeOut: TextEditingController(text: outText),
      tasks: TextEditingController(text: tasksText),
      deptIds: depts.toSet(),
      clockIn: clockIn,
      clockOut: clockOut,
      savedIn: inText,
      savedOut: outText,
      savedTasks: tasksText,
      savedDepts: depts,
    ).._watch(onChanged);
  }

  void _watch(void Function() onChanged) {
    this.onChanged = onChanged;
    for (final controller in [timeIn, timeOut, tasks]) {
      controller.addListener(_onText);
    }
  }

  void _onText() => onChanged?.call();

  /// Fold the server's day in without rebuilding the fields, so the keyboard
  /// stays up and typed text survives a background refresh.
  void applyDay(Map<String, dynamic> day) {
    dayLabel = day['day_label']?.toString() ?? dayLabel;
    final nextClockIn = _blankToNull(day['clock_in']);
    final nextClockOut = _blankToNull(day['clock_out']);
    clockIn = nextClockIn;
    clockOut = nextClockOut;

    final nextIn = nextClockIn ?? (day['time_in']?.toString() ?? '');
    final nextOut = nextClockOut ?? (day['time_out']?.toString() ?? '');
    final nextTasks = day['tasks_accomplished']?.toString() ?? '';
    final nextDepts = _dayDepts(day);

    if (dirty) return;

    if (nextIn != timeIn.text) timeIn.text = nextIn;
    if (nextOut != timeOut.text) timeOut.text = nextOut;
    if (nextTasks != tasks.text) tasks.text = nextTasks;
    if (!_sameIds(deptIds, nextDepts)) {
      deptIds
        ..clear()
        ..addAll(nextDepts);
    }

    savedIn = nextIn;
    savedOut = nextOut;
    savedTasks = nextTasks;
    savedDepts = nextDepts;
  }

  /// Called once a save lands. A time still mid-typing keeps its older baseline
  /// so the field stays marked unsaved and retries when it becomes valid.
  void markSaved() {
    final inValue = sendableTimeIn;
    final outValue = sendableTimeOut;
    if (inValue != null) savedIn = inValue;
    if (outValue != null) savedOut = outValue;
    savedTasks = tasks.text.trim();
    savedDepts = deptIds.toSet();
  }

  Map<String, dynamic> toPayload() {
    final sendIn = sendableTimeIn ?? savedIn;
    final sendOut = sendableTimeOut ?? savedOut;
    final tasksText = tasks.text.trim();

    return {
      if (sendIn.isNotEmpty) 'time_in': sendIn,
      if (sendOut.isNotEmpty) 'time_out': sendOut,
      if (tasksText.isNotEmpty) 'tasks_accomplished': tasksText,
      'department_ids': deptIds.toList(),
    };
  }

  void dispose() {
    for (final controller in [timeIn, timeOut, tasks]) {
      controller.removeListener(_onText);
    }
    timeIn.dispose();
    timeOut.dispose();
    tasks.dispose();
  }
}
