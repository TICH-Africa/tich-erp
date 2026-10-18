import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../data/api_client.dart';
import '../../data/student_api.dart';
import '../../theme/tich_theme.dart';
import '../widgets/tich_live_refresh.dart';
import '../widgets/tich_widgets.dart';
import 'student_helpers.dart';

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

class _StudentAssessmentTakePageState extends State<StudentAssessmentTakePage> {
  late Future<Map<String, dynamic>> _future;
  final Map<String, String> _responses = {};
  bool _busy = false;
  DateTime? _startedAt;

  @override
  void initState() {
    super.initState();
    _future = widget.api.assessment(widget.assessmentId);
    _startedAt = DateTime.now();
  }

  void _liveRefresh() {
    tichSilentReload(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.assessment(widget.assessmentId),
      apply: (f) => _future = f,
    );
  }

  Future<void> _submit(Map assessment) async {
    final questions = assessment['questions'] as List? ?? const [];
    for (final raw in questions) {
      final q = Map<String, dynamic>.from(raw as Map);
      final id = '${q['id']}';
      if ((_responses[id] ?? '').trim().isEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Answer all questions (missing Q${q['sort_order']}).')),
        );
        return;
      }
    }

    setState(() => _busy = true);
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
      final submission =
          Map<String, dynamic>.from(res['submission'] as Map? ?? {});
      await showDialog<void>(
        context: context,
        builder: (ctx) => AlertDialog(
          title: const Text('Submitted'),
          content: Text(
            'Score: ${submission['score'] ?? '—'}'
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
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) setState(() => _busy = false);
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
                  : (v) => setState(() {
                        if (v != null) _responses[id] = v;
                      }),
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
              title: Text(opt == 'true' ? 'True' : 'False',
                  style: tichUi(fontSize: 14)),
              value: opt,
              groupValue: _responses[id],
              onChanged: _busy
                  ? null
                  : (v) => setState(() {
                        if (v != null) _responses[id] = v;
                      }),
            ),
        ],
      );
    }

    final isLong = type == 'essay' || type == 'long_answer';
    return TextField(
      enabled: !_busy,
      maxLines: isLong ? 6 : 1,
      decoration: InputDecoration(
        labelText: isLong ? 'Your answer' : 'Answer',
      ),
      onChanged: (v) => _responses[id] = v,
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
          if (snap.connectionState != ConnectionState.done) {
            return studentLoadingBody();
          }
          if (snap.hasError) {
            return studentErrorBody(
              snap.error,
              () => setState(
                () => _future = widget.api.assessment(widget.assessmentId),
              ),
            );
          }
          final assessment =
              Map<String, dynamic>.from(snap.data!['assessment'] as Map? ?? {});
          final submission = snap.data!['submission'];
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
                        style: tichUi(fontWeight: FontWeight.w700, fontSize: 16),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        submission is Map
                            ? 'Already submitted. Score: ${submission['score'] ?? '—'}'
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
                      style: tichUi(fontWeight: FontWeight.w700, fontSize: 16),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      [
                        assessment['unit_code'],
                        if (assessment['max_score'] != null)
                          '${assessment['max_score']} marks',
                        if (assessment['time_limit_minutes'] != null)
                          '${assessment['time_limit_minutes']} min',
                      ].where((e) => e != null && '$e'.isNotEmpty).join(' · '),
                      style: tichUi(fontSize: 13, color: TichColors.neutralMuted),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 16),
              for (final raw in questions) ...[
                Builder(builder: (context) {
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
                }),
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
