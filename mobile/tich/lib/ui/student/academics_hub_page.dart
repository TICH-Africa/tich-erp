import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../data/api_client.dart';
import '../../data/student_api.dart';
import '../../theme/tich_theme.dart';
import '../widgets/date_time_pickers.dart';
import '../widgets/tich_widgets.dart';
import 'assessment_take_page.dart';
import 'student_helpers.dart';

class StudentAcademicsHubPage extends StatefulWidget {
  const StudentAcademicsHubPage({super.key, required this.api});

  final StudentApi api;

  @override
  State<StudentAcademicsHubPage> createState() => _StudentAcademicsHubPageState();
}

class _StudentAcademicsHubPageState extends State<StudentAcademicsHubPage> {
  late Future<Map<String, dynamic>> _future;

  @override
  void initState() {
    super.initState();
    _future = widget.api.academics();
  }

  void _reload() => setState(() => _future = widget.api.academics());

  void _openSection(String title, Widget Function(Map<String, dynamic>) builder) {
    _future.then((data) {
      if (!mounted) return;
      Navigator.of(context).push(
        MaterialPageRoute(
          builder: (_) => AdaptiveScaffold(
            title: title,
            body: builder(data),
          ),
        ),
      );
    });
  }

  Widget _listSection({
    required String empty,
    required List items,
    required Widget Function(Map<String, dynamic>) tile,
  }) {
    if (items.isEmpty) {
      return ListView(
        padding: const EdgeInsets.all(16),
        children: [TichCard(child: TichEmpty(empty))],
      );
    }
    return ListView.separated(
      padding: const EdgeInsets.all(16),
      itemCount: items.length,
      separatorBuilder: (_, __) => const SizedBox(height: 8),
      itemBuilder: (context, i) =>
          tile(Map<String, dynamic>.from(items[i] as Map)),
    );
  }

  @override
  Widget build(BuildContext context) {
    final sections = [
      (
        'My units',
        Icons.menu_book_outlined,
        () => _openSection(
              'My units',
              (data) {
                final academics =
                    Map<String, dynamic>.from(data['academics'] as Map? ?? {});
                final units = academics['registered_units'] as List? ?? const [];
                return _listSection(
                  empty: 'No registered units yet.',
                  items: units,
                  tile: (u) => TichCard(
                    child: ListTile(
                      contentPadding: EdgeInsets.zero,
                      title: Text(
                        '${u['unit_code'] ?? ''} · ${u['unit_name'] ?? ''}',
                        style: tichUi(fontWeight: FontWeight.w600),
                      ),
                      subtitle: Text(
                        [
                          u['semester_label'],
                          u['registration_status'],
                        ].where((e) => e != null && '$e'.isNotEmpty).join(' · '),
                      ),
                    ),
                  ),
                );
              },
            ),
      ),
      (
        'Learning content',
        Icons.folder_open_outlined,
        () => _openSection(
              'Learning content',
              (data) {
                final academics =
                    Map<String, dynamic>.from(data['academics'] as Map? ?? {});
                final content = academics['unit_content'] as List? ?? const [];
                return _listSection(
                  empty: 'No published content yet.',
                  items: content,
                  tile: (c) => TichCard(
                    child: ListTile(
                      contentPadding: EdgeInsets.zero,
                      title: Text(c['title']?.toString() ?? 'Content',
                          style: tichUi(fontWeight: FontWeight.w600)),
                      subtitle: Text(
                        [
                          c['unit_code'],
                          c['content_type'],
                          formatDateTime(c['published_at']),
                        ].where((e) => e != null && '$e'.isNotEmpty).join(' · '),
                      ),
                    ),
                  ),
                );
              },
            ),
      ),
      (
        'Assessments',
        Icons.quiz_outlined,
        () => _openSection(
              'Assessments',
              (data) {
                final academics =
                    Map<String, dynamic>.from(data['academics'] as Map? ?? {});
                final items = academics['assessments'] as List? ?? const [];
                return _listSection(
                  empty: 'No assessments available.',
                  items: items,
                  tile: (a) {
                    final sub = a['submission'] is Map
                        ? Map<String, dynamic>.from(a['submission'] as Map)
                        : null;
                    final id = (a['id'] as num?)?.toInt();
                    final canTake = sub == null &&
                        (a['status']?.toString() == 'ready' ||
                            a['status']?.toString() == 'open');
                    return TichCard(
                      onTap: id == null
                          ? null
                          : () async {
                              final done = await Navigator.of(context).push<bool>(
                                MaterialPageRoute(
                                  builder: (_) => StudentAssessmentTakePage(
                                    api: widget.api,
                                    assessmentId: id,
                                  ),
                                ),
                              );
                              if (done == true) _reload();
                            },
                      child: ListTile(
                        contentPadding: EdgeInsets.zero,
                        title: Text(a['title']?.toString() ?? 'Assessment',
                            style: tichUi(fontWeight: FontWeight.w600)),
                        subtitle: Text(
                          [
                            a['unit_code'],
                            '${a['question_count'] ?? 0} questions',
                            if (sub != null) 'Score: ${sub['score'] ?? '—'}',
                            if (canTake) 'Tap to take',
                          ].where((e) => e != null && '$e'.isNotEmpty).join(' · '),
                        ),
                        trailing: canTake
                            ? FilledButton(
                                onPressed: id == null
                                    ? null
                                    : () async {
                                        final done =
                                            await Navigator.of(context).push<bool>(
                                          MaterialPageRoute(
                                            builder: (_) =>
                                                StudentAssessmentTakePage(
                                              api: widget.api,
                                              assessmentId: id,
                                            ),
                                          ),
                                        );
                                        if (done == true) _reload();
                                      },
                                child: const Text('Take'),
                              )
                            : TichStatusChip(
                                sub != null
                                    ? 'Submitted'
                                    : (a['status']?.toString() ?? 'open'),
                                tone: sub != null
                                    ? TichChipTone.success
                                    : TichChipTone.info,
                              ),
                      ),
                    );
                  },
                );
              },
            ),
      ),
      (
        'Exams & grades',
        Icons.grade_outlined,
        () => _openSection(
              'Exams & grades',
              (data) {
                final academics =
                    Map<String, dynamic>.from(data['academics'] as Map? ?? {});
                final grades = academics['grades'] as List? ?? const [];
                final cats = academics['cat_scores'] as List? ?? const [];
                final exams = academics['exam_results'] as List? ?? const [];
                return ListView(
                  padding: const EdgeInsets.all(16),
                  children: [
                    const TichSectionHeader('Grades'),
                    if (grades.isEmpty)
                      const TichCard(child: TichEmpty('No grades yet.'))
                    else
                      ...grades.map((raw) {
                        final g = Map<String, dynamic>.from(raw as Map);
                        return Padding(
                          padding: const EdgeInsets.only(bottom: 8),
                          child: TichCard(
                            child: ListTile(
                              contentPadding: EdgeInsets.zero,
                              title: Text(
                                '${g['unit_code'] ?? ''} · ${g['unit_name'] ?? ''}',
                                style: tichUi(fontWeight: FontWeight.w600),
                              ),
                              subtitle: Text(
                                '${g['semester_label'] ?? ''} · ${g['grade_letter'] ?? '—'}'
                                ' (${g['final_score'] ?? '—'})',
                              ),
                            ),
                          ),
                        );
                      }),
                    const SizedBox(height: 12),
                    const TichSectionHeader('CAT scores'),
                    if (cats.isEmpty)
                      const TichCard(child: TichEmpty('No CAT scores yet.'))
                    else
                      ...cats.take(20).map((raw) {
                        final c = Map<String, dynamic>.from(raw as Map);
                        return Padding(
                          padding: const EdgeInsets.only(bottom: 8),
                          child: TichCard(
                            child: Text(
                              '${c['unit_code']} · ${c['assessment_name'] ?? c['assessment_type']}'
                              ' — ${c['score_obtained']}/${c['max_score']}',
                              style: tichUi(fontSize: 13),
                            ),
                          ),
                        );
                      }),
                    const SizedBox(height: 12),
                    const TichSectionHeader('Exam results'),
                    if (exams.isEmpty)
                      const TichCard(child: TichEmpty('No exam results yet.'))
                    else
                      ...exams.take(20).map((raw) {
                        final e = Map<String, dynamic>.from(raw as Map);
                        return Padding(
                          padding: const EdgeInsets.only(bottom: 8),
                          child: TichCard(
                            child: Text(
                              '${e['unit_code']} · ${e['score_obtained'] ?? e['marks'] ?? '—'}'
                              ' (${e['semester_label'] ?? ''})',
                              style: tichUi(fontSize: 13),
                            ),
                          ),
                        );
                      }),
                    const SizedBox(height: 16),
                    AdaptiveFilledButton(
                      onPressed: () async {
                        final method = await showDialog<String>(
                          context: context,
                          builder: (ctx) => SimpleDialog(
                            title: const Text('Request transcript'),
                            children: [
                              for (final m in ['download', 'email', 'collect'])
                                SimpleDialogOption(
                                  onPressed: () => Navigator.pop(ctx, m),
                                  child: Text(m),
                                ),
                            ],
                          ),
                        );
                        if (method == null) return;
                        try {
                          final res = await widget.api.requestTranscript(
                            deliveryMethod: method,
                          );
                          if (!context.mounted) return;
                          ScaffoldMessenger.of(context).showSnackBar(
                            SnackBar(content: Text(res['message']?.toString() ?? 'Submitted')),
                          );
                          _reload();
                        } on ApiException catch (e) {
                          if (!context.mounted) return;
                          ScaffoldMessenger.of(context).showSnackBar(
                            SnackBar(content: Text(e.message)),
                          );
                        }
                      },
                      child: const Text('Request official transcript'),
                    ),
                  ],
                );
              },
            ),
      ),
      (
        'Attendance',
        Icons.how_to_reg_outlined,
        () => _openSection(
              'Attendance',
              (data) {
                final academics =
                    Map<String, dynamic>.from(data['academics'] as Map? ?? {});
                final items = academics['attendance'] as List? ?? const [];
                return _listSection(
                  empty: 'No attendance summaries yet.',
                  items: items,
                  tile: (a) => TichCard(
                    child: ListTile(
                      contentPadding: EdgeInsets.zero,
                      title: Text(
                        '${a['unit_code'] ?? ''} · ${a['unit_name'] ?? ''}',
                        style: tichUi(fontWeight: FontWeight.w600),
                      ),
                      subtitle: Text(
                        '${a['attendance_percentage'] ?? 0}% · '
                        '${a['total_present'] ?? 0}/${a['total_sessions'] ?? 0} sessions',
                      ),
                      trailing: TichStatusChip(
                        a['status_flag']?.toString() ?? 'ok',
                        tone: toneForStatus(a['status_flag']?.toString()),
                      ),
                    ),
                  ),
                );
              },
            ),
      ),
      (
        'Academic progress',
        Icons.timeline_outlined,
        () => _openSection(
              'Academic progress',
              (data) {
                final academics =
                    Map<String, dynamic>.from(data['academics'] as Map? ?? {});
                final periods = academics['periods'] as List? ?? const [];
                final curriculum =
                    academics['curriculum_units'] as List? ?? const [];
                return ListView(
                  padding: const EdgeInsets.all(16),
                  children: [
                    TichCard(
                      child: Text(
                        'Registered units: ${academics['registered_unit_count'] ?? 0}\n'
                        'Curriculum units: ${curriculum.length}\n'
                        'Current period: ${academics['current_period'] is Map ? (academics['current_period'] as Map)['label'] ?? '—' : '—'}',
                        style: tichUi(fontSize: 13, height: 1.5),
                      ),
                    ),
                    const SizedBox(height: 12),
                    const TichSectionHeader('Periods'),
                    if (periods.isEmpty)
                      const TichCard(child: TichEmpty('No curriculum periods.'))
                    else
                      ...periods.map((raw) {
                        final p = Map<String, dynamic>.from(raw as Map);
                        final label = p['label']?.toString().isNotEmpty == true
                            ? p['label'].toString()
                            : 'Semester ${p['semester'] ?? ''}';
                        return Padding(
                          padding: const EdgeInsets.only(bottom: 8),
                          child: TichCard(
                            child: Text(
                              '$label · ${formatDateTime(p['starts_at'])} → ${formatDateTime(p['ends_at'])}',
                              style: tichUi(fontSize: 13),
                            ),
                          ),
                        );
                      }),
                  ],
                );
              },
            ),
      ),
      (
        'Exam eligibility',
        Icons.verified_outlined,
        () => _openSection(
              'Exam eligibility',
              (data) {
                final academics =
                    Map<String, dynamic>.from(data['academics'] as Map? ?? {});
                final items = academics['attendance'] as List? ?? const [];
                return _listSection(
                  empty: 'No eligibility data yet.',
                  items: items,
                  tile: (a) {
                    final eligible = a['eligible_for_exams'] == true ||
                        a['eligible_for_exams'] == 1;
                    final blocked = a['exam_eligibility_blocked'] == true ||
                        a['exam_eligibility_blocked'] == 1;
                    return TichCard(
                      child: ListTile(
                        contentPadding: EdgeInsets.zero,
                        title: Text(
                          '${a['unit_code'] ?? ''} · ${a['unit_name'] ?? ''}',
                          style: tichUi(fontWeight: FontWeight.w600),
                        ),
                        subtitle: Text(
                          'Attendance ${a['attendance_percentage'] ?? 0}%',
                        ),
                        trailing: TichStatusChip(
                          blocked
                              ? 'Blocked'
                              : (eligible ? 'Eligible' : 'Pending'),
                          tone: blocked
                              ? TichChipTone.danger
                              : (eligible
                                  ? TichChipTone.success
                                  : TichChipTone.warning),
                        ),
                      ),
                    );
                  },
                );
              },
            ),
      ),
      (
        'Supp / special exams',
        Icons.edit_note_outlined,
        () => _openSection(
              'Supp / special exams',
              (data) {
                final academics =
                    Map<String, dynamic>.from(data['academics'] as Map? ?? {});
                final units =
                    academics['registered_units'] as List? ?? const [];
                final special =
                    data['special_exam_requests'] as List? ?? const [];
                final supp =
                    data['supplementary_exam_requests'] as List? ?? const [];
                return _ExamRequestsInteractive(
                  api: widget.api,
                  units: units,
                  special: special,
                  supplementary: supp,
                  onChanged: _reload,
                );
              },
            ),
      ),
    ];

    return AdaptiveScaffold(
      title: 'Academics',
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          if (snap.connectionState != ConnectionState.done) {
            return studentLoadingBody();
          }
          if (snap.hasError) {
            return studentErrorBody(snap.error, _reload);
          }
          return RefreshIndicator(
            onRefresh: () async => _reload(),
            child: ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: sections.length,
              separatorBuilder: (_, __) => const SizedBox(height: 8),
              itemBuilder: (context, i) {
                final s = sections[i];
                return Card(
                  child: ListTile(
                    leading: CircleAvatar(
                      backgroundColor: TichColors.blueLight,
                      child: Icon(s.$2, color: TichColors.blueDark),
                    ),
                    title: Text(s.$1, style: tichUi(fontWeight: FontWeight.w600)),
                    trailing: const Icon(Icons.chevron_right),
                    onTap: s.$3,
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

class _ExamRequestsInteractive extends StatefulWidget {
  const _ExamRequestsInteractive({
    required this.api,
    required this.units,
    required this.special,
    required this.supplementary,
    required this.onChanged,
  });

  final StudentApi api;
  final List units;
  final List special;
  final List supplementary;
  final VoidCallback onChanged;

  @override
  State<_ExamRequestsInteractive> createState() =>
      _ExamRequestsInteractiveState();
}

class _ExamRequestsInteractiveState extends State<_ExamRequestsInteractive> {
  String _type = 'special_exam';
  int? _unitId;
  int? _semesterId;
  final _reason = TextEditingController();
  final List<({String path, String name})> _files = [];
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    if (widget.units.isNotEmpty) {
      final first = Map<String, dynamic>.from(widget.units.first as Map);
      _unitId = (first['unit_id'] as num?)?.toInt();
      _semesterId = (first['semester_id'] as num?)?.toInt();
    }
  }

  @override
  void dispose() {
    _reason.dispose();
    super.dispose();
  }

  Future<void> _pick() async {
    final pick = await FilePicker.platform.pickFiles(
      allowMultiple: true,
      type: FileType.custom,
      allowedExtensions: const ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'],
    );
    if (pick == null) return;
    setState(() {
      for (final f in pick.files) {
        if (f.path != null) _files.add((path: f.path!, name: f.name));
      }
    });
  }

  Future<void> _submit() async {
    if (_unitId == null || _semesterId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('No registered unit available to request against.')),
      );
      return;
    }
    if (_type == 'special_exam' && _reason.text.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Reason is required for special exams.')),
      );
      return;
    }
    if (_type == 'special_exam' && _files.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Attach supporting documents for special exams.')),
      );
      return;
    }
    setState(() => _busy = true);
    try {
      final res = await widget.api.submitExamSitting(
        requestType: _type,
        unitId: _unitId!,
        semesterId: _semesterId!,
        reason: _reason.text.trim().isEmpty ? null : _reason.text.trim(),
        attachments: List.of(_files),
      );
      if (!mounted) return;
      _reason.clear();
      _files.clear();
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(res['message']?.toString() ?? 'Submitted')),
      );
      widget.onChanged();
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final unitItems = widget.units
        .map((e) => Map<String, dynamic>.from(e as Map))
        .where((u) => u['unit_id'] != null && u['semester_id'] != null)
        .toList();

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        const TichSectionHeader('New request'),
        TichCard(
          child: Column(
            children: [
              DropdownButtonFormField<String>(
                value: _type,
                items: const [
                  DropdownMenuItem(
                    value: 'special_exam',
                    child: Text('Special exam'),
                  ),
                  DropdownMenuItem(
                    value: 'supplementary',
                    child: Text('Supplementary exam'),
                  ),
                ],
                onChanged: _busy ? null : (v) => setState(() => _type = v ?? 'special_exam'),
                decoration: const InputDecoration(labelText: 'Request type'),
              ),
              const SizedBox(height: 10),
              DropdownButtonFormField<int>(
                value: _unitId,
                items: unitItems
                    .map(
                      (u) => DropdownMenuItem(
                        value: (u['unit_id'] as num).toInt(),
                        child: Text(
                          '${u['unit_code'] ?? ''} · ${u['unit_name'] ?? ''}',
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                    )
                    .toList(),
                onChanged: _busy
                    ? null
                    : (v) {
                        final match = unitItems.firstWhere(
                          (u) => (u['unit_id'] as num).toInt() == v,
                        );
                        setState(() {
                          _unitId = v;
                          _semesterId =
                              (match['semester_id'] as num?)?.toInt();
                        });
                      },
                decoration: const InputDecoration(labelText: 'Unit'),
              ),
              const SizedBox(height: 10),
              TextField(
                controller: _reason,
                enabled: !_busy,
                maxLines: 3,
                decoration: InputDecoration(
                  labelText: _type == 'special_exam'
                      ? 'Reason (required)'
                      : 'Notes (optional)',
                ),
              ),
              if (_type == 'special_exam') ...[
                const SizedBox(height: 10),
                OutlinedButton.icon(
                  onPressed: _busy ? null : _pick,
                  icon: const Icon(Icons.attach_file),
                  label: Text(
                    _files.isEmpty
                        ? 'Attach documents'
                        : '${_files.length} file(s) attached',
                  ),
                ),
              ],
              const SizedBox(height: 12),
              AdaptiveFilledButton(
                onPressed: _busy ? null : _submit,
                child: _busy
                    ? const SizedBox(
                        width: 18,
                        height: 18,
                        child: AdaptiveProgress(color: Colors.white),
                      )
                    : const Text('Submit request'),
              ),
            ],
          ),
        ),
        const SizedBox(height: 16),
        const TichSectionHeader('Special exam requests'),
        if (widget.special.isEmpty)
          const TichCard(child: TichEmpty('None yet.'))
        else
          ...widget.special.map((raw) {
            final r = Map<String, dynamic>.from(raw as Map);
            return Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: TichCard(
                child: ListTile(
                  contentPadding: EdgeInsets.zero,
                  title: Text(
                    '${r['unit_code'] ?? ''} · ${r['unit_name'] ?? ''}',
                    style: tichUi(fontWeight: FontWeight.w600),
                  ),
                  subtitle: Text(r['reason']?.toString() ?? ''),
                  trailing: TichStatusChip(
                    r['status']?.toString() ?? 'pending',
                    tone: toneForStatus(r['status']?.toString()),
                  ),
                ),
              ),
            );
          }),
        const SizedBox(height: 12),
        const TichSectionHeader('Supplementary requests'),
        if (widget.supplementary.isEmpty)
          const TichCard(child: TichEmpty('None yet.'))
        else
          ...widget.supplementary.map((raw) {
            final r = Map<String, dynamic>.from(raw as Map);
            return Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: TichCard(
                child: ListTile(
                  contentPadding: EdgeInsets.zero,
                  title: Text(
                    '${r['unit_code'] ?? ''} · ${r['unit_name'] ?? ''}',
                    style: tichUi(fontWeight: FontWeight.w600),
                  ),
                  trailing: TichStatusChip(
                    r['status']?.toString() ?? 'pending',
                    tone: toneForStatus(r['status']?.toString()),
                  ),
                ),
              ),
            );
          }),
      ],
    );
  }
}
