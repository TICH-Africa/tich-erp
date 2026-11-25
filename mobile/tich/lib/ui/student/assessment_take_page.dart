import 'dart:async';
import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../../adaptive/adaptive.dart';
import '../../data/api_client.dart';
import '../../data/student_api.dart';
import '../../data/tich_screen_cache.dart';
import '../../theme/tich_theme.dart';
import '../widgets/tich_form_dialog.dart';
import '../widgets/tich_live_refresh.dart';
import '../widgets/tich_widgets.dart';
import 'student_helpers.dart';
import 'student_prefetch.dart';

class StudentAssessmentTakePage extends StatefulWidget {
  const StudentAssessmentTakePage({
    super.key,
    required this.api,
    required this.assessmentId,
  });

  final StudentApi api;
  final int assessmentId;

  @override
  State<StudentAssessmentTakePage> createState() =>
      _StudentAssessmentTakePageState();
}

class _StudentAssessmentTakePageState extends State<StudentAssessmentTakePage>
    with WidgetsBindingObserver {
  late Future<Map<String, dynamic>> _future;
  Object? _dataCache;
  final Map<String, String> _responses = {};
  final Map<String, TextEditingController> _textControllers = {};
  bool _busy = false;
  bool _initialized = false;
  bool _needsSync = false;
  bool _savingProgress = false;
  bool _saveQueued = false;
  Future<void>? _initialization;
  DateTime? _startedAt;
  DateTime? _deadline;
  int? _serverUpdatedAt;
  int? _remainingSeconds;
  String? _draftKey;
  String _saveStatus = 'Answers save automatically as you work.';
  Timer? _timer;
  Timer? _saveDebounce;
  Timer? _retryTimer;
  Map<String, dynamic>? _activeAssessment;

  String get _cacheKey => StudentPrefetch.assessmentKey(widget.assessmentId);

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _future = _loadAssessment();
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _timer?.cancel();
    _saveDebounce?.cancel();
    _retryTimer?.cancel();
    for (final c in _textControllers.values) {
      c.dispose();
    }
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.inactive ||
        state == AppLifecycleState.paused ||
        state == AppLifecycleState.detached) {
      unawaited(_saveLocalDraft());
      unawaited(_saveProgress());
    }
  }

  Future<Map<String, dynamic>> _loadAssessment() async {
    Map<String, dynamic> payload;
    try {
      payload = Map<String, dynamic>.from(
        await widget.api.assessment(widget.assessmentId),
      )..['_client_fetched_at'] = DateTime.now().millisecondsSinceEpoch;
      TichScreenCache.write(_cacheKey, payload);
    } catch (error) {
      final cached = studentReadCache(_cacheKey);
      if (cached == null) rethrow;
      payload = Map<String, dynamic>.from(cached);
      _saveStatus = 'Offline. Your saved answers are available on this device.';
    }

    await _initializePayload(payload);
    return payload;
  }

  Future<void> _initializePayload(Map<String, dynamic> payload) =>
      _initialization ??= _initializePayloadOnce(payload);

  Future<void> _initializePayloadOnce(Map<String, dynamic> payload) async {
    final assessment = Map<String, dynamic>.from(
      payload['assessment'] as Map? ?? {},
    );
    final submission = Map<String, dynamic>.from(
      payload['submission'] as Map? ?? {},
    );
    _activeAssessment = assessment;
    _remainingSeconds = (submission['remaining_seconds'] as num?)?.toInt();
    _serverUpdatedAt = (submission['updated_at'] as num?)?.toInt() ?? 0;
    _startedAt = DateTime.tryParse(
      submission['student_started_at']?.toString() ?? '',
    );

    final timeLimitSeconds =
        (((assessment['time_limit_minutes'] as num?)?.toInt() ?? 30).clamp(
                  1,
                  1440,
                ) *
                60)
            .toInt();
    final fetchedAt = (payload['_client_fetched_at'] as num?)?.toInt();
    if (_remainingSeconds != null && fetchedAt != null) {
      final elapsedSinceFetch =
          ((DateTime.now().millisecondsSinceEpoch - fetchedAt) / 1000).floor();
      _remainingSeconds = (_remainingSeconds! - elapsedSinceFetch)
          .clamp(0, timeLimitSeconds)
          .toInt();
    } else if (_remainingSeconds == null && _startedAt != null) {
      _remainingSeconds =
          (timeLimitSeconds - DateTime.now().difference(_startedAt!).inSeconds)
              .clamp(0, timeLimitSeconds)
              .toInt();
    }

    _responses
      ..clear()
      ..addAll(
        Map<String, dynamic>.from(
          submission['responses'] as Map? ?? {},
        ).map((key, value) => MapEntry(key, value?.toString() ?? '')),
      );
    await _restoreLocalDraft(submission);
    _initialized = true;
    _startTimer();
  }

  Future<void> _restoreLocalDraft(Map<String, dynamic> submission) async {
    final studentId =
        submission['student_id']?.toString() ??
        (_activeAssessment?['student_id']?.toString() ?? 'unknown');
    final attempt = submission['attempt_number']?.toString() ?? '1';
    _draftKey =
        'student.assessment.draft.$studentId.${widget.assessmentId}.$attempt';

    try {
      final prefs = await SharedPreferences.getInstance();
      final raw = prefs.getString(_draftKey!);
      if (raw == null) return;
      final draft = jsonDecode(raw) as Map<String, dynamic>;
      if ((draft['base_server_updated_at'] as num?)?.toInt() !=
          _serverUpdatedAt) {
        await prefs.remove(_draftKey!);
        return;
      }

      final responses = Map<String, dynamic>.from(
        draft['responses'] as Map? ?? {},
      );
      final savedResponses = Map<String, dynamic>.from(
        submission['responses'] as Map? ?? {},
      );
      final answersMatch =
          savedResponses.length == responses.length &&
          savedResponses.entries.every(
            (entry) =>
                '${entry.value ?? ''}' == '${responses[entry.key] ?? ''}',
          );
      if (answersMatch) {
        await prefs.remove(_draftKey!);
        return;
      }

      _responses
        ..clear()
        ..addAll(
          responses.map((key, value) => MapEntry(key, value?.toString() ?? '')),
        );
      _needsSync = true;
      _saveStatus = 'Restored unsynced answers. Syncing now…';
      unawaited(_saveProgress());
    } catch (error) {
      _saveStatus =
          'Device backup could not be restored; account answers remain available.';
    }
  }

  void _startTimer() {
    _timer?.cancel();
    final remaining = _remainingSeconds;
    if (remaining == null) return;
    _deadline = DateTime.now().add(Duration(seconds: remaining));
    _timer = Timer.periodic(const Duration(seconds: 1), (_) {
      final seconds = _deadline!.difference(DateTime.now()).inSeconds;
      if (mounted) {
        setState(() => _remainingSeconds = seconds.clamp(0, 86400).toInt());
      }
      if (seconds <= 0) {
        _timer?.cancel();
        final assessment = _activeAssessment;
        if (assessment != null && mounted) {
          unawaited(_submit(assessment, force: true));
        }
      }
    });
  }

  Future<void> _saveLocalDraft() async {
    final key = _draftKey;
    if (key == null) return;
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString(
        key,
        jsonEncode({
          'base_server_updated_at': _serverUpdatedAt,
          'responses': _responses,
        }),
      );
    } catch (error) {
      if (mounted)
        setState(() => _saveStatus = 'Device backup is unavailable.');
    }
  }

  Future<void> _clearLocalDraft() async {
    final key = _draftKey;
    if (key == null) return;
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.remove(key);
    } catch (error) {
      if (mounted) {
        setState(
          () => _saveStatus = 'Answers saved, but the device backup remains.',
        );
      }
    }
  }

  void _queueProgressSave() {
    _needsSync = true;
    if (mounted) setState(() => _saveStatus = 'Saving answers…');
    unawaited(_saveLocalDraft());
    _saveDebounce?.cancel();
    _saveDebounce = Timer(const Duration(milliseconds: 700), _saveProgress);
    _retryTimer ??= Timer.periodic(const Duration(seconds: 8), (_) {
      if (_remainingSeconds == 0 && _activeAssessment != null) {
        unawaited(_submit(_activeAssessment!, force: true));
      } else if (_needsSync) {
        unawaited(_saveProgress());
      }
    });
  }

  Future<void> _saveProgress() async {
    if (!_needsSync || _busy) return;
    if (_savingProgress) {
      _saveQueued = true;
      return;
    }

    _savingProgress = true;
    final snapshot = Map<String, String>.from(_responses);
    try {
      final result = await widget.api.saveAssessmentProgress(
        widget.assessmentId,
        responses: snapshot,
      );
      _serverUpdatedAt =
          (result['updated_at'] as num?)?.toInt() ?? _serverUpdatedAt;
      _needsSync = jsonEncode(snapshot) != jsonEncode(_responses);
      if (_needsSync) {
        await _saveLocalDraft();
      } else {
        await _clearLocalDraft();
      }
      if (mounted) {
        setState(() {
          _saveStatus = _needsSync
              ? 'New changes are waiting to sync…'
              : 'Answers saved.';
        });
      }
      if (!_needsSync) {
        _retryTimer?.cancel();
        _retryTimer = null;
      }
    } catch (error) {
      if (mounted) {
        setState(
          () => _saveStatus =
              'Not synced yet. Answers are saved on this device and will retry.',
        );
      }
    } finally {
      _savingProgress = false;
      final retryQueued = _saveQueued;
      _saveQueued = false;
      if (retryQueued) unawaited(_saveProgress());
    }
  }

  TextEditingController _textControllerFor(String id) {
    return _textControllers.putIfAbsent(id, () {
      final controller = TextEditingController(text: _responses[id] ?? '');
      controller.addListener(() {
        _responses[id] = controller.text;
        _queueProgressSave();
      });
      return controller;
    });
  }

  Future<void> _reload() => studentCachedReload(
    mounted: () => mounted,
    setState: setState,
    fetch: _loadAssessment,
    onData: (data) => _dataCache = data,
    persistKey: _cacheKey,
  );

  void _liveRefresh() {
    tichSilentReload(
      mounted: () => mounted,
      setState: setState,
      fetch: _loadAssessment,
      onData: (data) => _dataCache = data,
      persistKey: _cacheKey,
    );
  }

  Future<void> _submit(Map assessment, {bool force = false}) async {
    final questions = assessment['questions'] as List? ?? const [];
    if (!force) {
      for (final raw in questions) {
        final q = Map<String, dynamic>.from(raw as Map);
        final id = '${q['id']}';
        if ((_responses[id] ?? '').trim().isEmpty) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(
                'Answer all questions (missing Q${q['sort_order']}).',
              ),
            ),
          );
          return;
        }
      }
    }

    setState(() => _busy = true);
    _timer?.cancel();
    _saveDebounce?.cancel();
    try {
      final seconds = _startedAt == null
          ? null
          : DateTime.now().difference(_startedAt!).inSeconds;
      final res = await widget.api.submitAssessment(
        widget.assessmentId,
        responses: _responses,
        timeTakenSeconds: seconds,
      );
      if (!mounted) return;
      if (_draftKey != null) {
        final prefs = await SharedPreferences.getInstance();
        await prefs.remove(_draftKey!);
      }
      final submission = Map<String, dynamic>.from(
        res['submission'] as Map? ?? {},
      );
      await showDialog<void>(
        context: context,
        builder: (ctx) => TichFormDialog(
          title: const Text('Submitted'),
          content: Text(
            'Score: ${submission['score'] ?? '-'}'
            '${submission['percentage_score'] != null ? ' (${submission['percentage_score']}%)' : ''}',
          ),
          actions: [
            FilledButton(
              onPressed: () => Navigator.pop(ctx),
              child: const Text('OK'),
            ),
          ],
        ),
      );
      if (!mounted) return;
      Navigator.pop(context, true);
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text(e.message)));
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Assessment submission failed: $error')),
      );
    } finally {
      if (mounted) {
        setState(() => _busy = false);
        if (_remainingSeconds == null || _remainingSeconds! > 0) _startTimer();
      }
    }
  }

  Widget _questionField(Map<String, dynamic> q) {
    final id = '${q['id']}';
    final type = q['question_type']?.toString() ?? '';
    final options = (q['options'] as List? ?? const [])
        .map((e) => e.toString())
        .toList();

    if (type == 'mcq' && options.isNotEmpty) {
      return Column(
        children: [
          for (final opt in options)
            RadioListTile<String>(
              contentPadding: EdgeInsets.zero,
              title: Text(opt, style: tichUi(fontSize: 14)),
              value: opt,
              groupValue: _responses[id],
              onChanged: _busy
                  ? null
                  : (v) {
                      if (v == null) return;
                      setState(() => _responses[id] = v);
                      _queueProgressSave();
                    },
            ),
        ],
      );
    }

    if (type == 'true_false') {
      return Column(
        children: [
          for (final opt in ['true', 'false'])
            RadioListTile<String>(
              contentPadding: EdgeInsets.zero,
              title: Text(
                opt == 'true' ? 'True' : 'False',
                style: tichUi(fontSize: 14),
              ),
              value: opt,
              groupValue: _responses[id],
              onChanged: _busy
                  ? null
                  : (v) {
                      if (v == null) return;
                      setState(() => _responses[id] = v);
                      _queueProgressSave();
                    },
            ),
        ],
      );
    }

    final isLong = type == 'essay' || type == 'long_answer';
    final controller = _textControllerFor(id);
    return TextField(
      key: ValueKey('assessment-answer-$id'),
      controller: controller,
      enabled: !_busy,
      maxLines: isLong ? 6 : 3,
      minLines: isLong ? 4 : 2,
      decoration: InputDecoration(labelText: isLong ? 'Your answer' : 'Answer'),
    );
  }

  @override
  Widget build(BuildContext context) {
    return TichLiveRefreshBinder(
      onLiveRefresh: _liveRefresh,
      enabled: !_busy,
      child: AdaptiveScaffold(
        title: 'Assessment',
        body: FutureBuilder<Map<String, dynamic>>(
          future: _future,
          builder: (context, snap) {
            final merged = tichMergeSnapshot(
              snap,
              _dataCache as dynamic,
              persistKey: _cacheKey,
            );
            if (tichShowLoadingSpinner(snap, merged)) {
              return studentLoadingBody(onRefresh: () async => _reload());
            }
            if (merged == null && snap.hasError) {
              return studentErrorBody(snap.error, _reload);
            }
            final payload = merged! as Map<String, dynamic>;
            if (!_initialized) {
              unawaited(
                _initializePayload(payload).then((_) {
                  if (mounted) setState(() {});
                }),
              );
              return studentLoadingBody();
            }
            final assessment = Map<String, dynamic>.from(
              payload['assessment'] as Map? ?? {},
            );
            final submission = payload['submission'];
            final canTake = assessment['can_take'] == true;
            final questions = assessment['questions'] as List? ?? const [];

            if (!canTake) {
              return ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  TichCard(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          assessment['title']?.toString() ?? 'Assessment',
                          style: tichUi(
                            fontWeight: FontWeight.w700,
                            fontSize: 16,
                          ),
                        ),
                        const SizedBox(height: 8),
                        Text(
                          submission is Map &&
                                  submission['submitted_at'] != null
                              ? 'Already submitted. Score: ${submission['score'] ?? '-'}'
                              : 'This assessment cannot be taken right now.',
                          style: tichUi(color: TichColors.neutralMuted),
                        ),
                      ],
                    ),
                  ),
                ],
              );
            }

            return ListView(
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 32),
              children: [
                TichCard(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        assessment['title']?.toString() ?? 'Assessment',
                        style: tichUi(
                          fontWeight: FontWeight.w700,
                          fontSize: 16,
                        ),
                      ),
                      const SizedBox(height: 6),
                      Text(
                        [
                              assessment['unit_code'],
                              if (assessment['max_score'] != null)
                                '${assessment['max_score']} marks',
                              if (assessment['time_limit_minutes'] != null)
                                '${assessment['time_limit_minutes']} min',
                              if (submission is Map &&
                                  submission['student_started_at'] != null)
                                'In progress',
                            ]
                            .where((e) => e != null && '$e'.isNotEmpty)
                            .join(' · '),
                        style: tichUi(
                          fontSize: 13,
                          color: TichColors.neutralMuted,
                        ),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        _saveStatus,
                        style: tichUi(
                          fontSize: 12,
                          color: TichColors.neutralMuted,
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 16),
                if (_remainingSeconds != null)
                  Padding(
                    padding: const EdgeInsets.only(bottom: 12),
                    child: Text(
                      'Time remaining: ${_remainingSeconds! ~/ 60}:${(_remainingSeconds! % 60).toString().padLeft(2, '0')}',
                      style: tichUi(
                        color: _remainingSeconds! < 60
                            ? TichColors.danger
                            : TichColors.neutralMuted,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
                for (final raw in questions) ...[
                  Builder(
                    builder: (context) {
                      final q = Map<String, dynamic>.from(raw as Map);
                      return Padding(
                        padding: const EdgeInsets.only(bottom: 12),
                        child: TichCard(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'Q${q['sort_order'] ?? ''} · ${q['points'] ?? 0} pts',
                                style: tichUi(
                                  fontSize: 12,
                                  color: TichColors.neutralMuted,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                              const SizedBox(height: 6),
                              Text(
                                q['question_text']?.toString() ?? '',
                                style: tichUi(fontWeight: FontWeight.w600),
                              ),
                              const SizedBox(height: 8),
                              _questionField(q),
                            ],
                          ),
                        ),
                      );
                    },
                  ),
                ],
                AdaptiveFilledButton(
                  onPressed: _busy ? null : () => _submit(assessment),
                  child: _busy
                      ? const SizedBox(
                          width: 18,
                          height: 18,
                          child: AdaptiveProgress(color: Colors.white),
                        )
                      : const Text('Submit assessment'),
                ),
              ],
            );
          },
        ),
      ),
    );
  }
}
