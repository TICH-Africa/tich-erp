import 'dart:io';

import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:open_file/open_file.dart';
import 'package:path_provider/path_provider.dart';

import '../../adaptive/adaptive.dart';
import '../../data/api_client.dart';
import '../../data/student_api.dart';
import '../../data/tich_pdf_cache.dart';
import '../../theme/tich_theme.dart';
import '../widgets/date_time_pickers.dart';
import '../widgets/tich_form_dialog.dart';
import '../widgets/tich_live_refresh.dart';
import '../shell/portal_nav_counts_scope.dart';
import '../widgets/tich_more_row_trailing.dart';
import '../widgets/tich_generated_document_viewer.dart';
import '../widgets/tich_widgets.dart';
import 'student_academics_lazy_section.dart';
import 'student_academics_payload.dart';
import 'student_helpers.dart';
import 'learning_content_page.dart';
import 'student_academic_progress_section.dart';
import 'student_provisional_results_section.dart';
import 'student_module_nav.dart';
import 'student_prefetch.dart';
import '../../data/tich_screen_cache.dart';

class StudentAcademicsHubPage extends StatefulWidget {
  const StudentAcademicsHubPage({super.key, required this.api});

  final StudentApi api;

  @override
  State<StudentAcademicsHubPage> createState() => _StudentAcademicsHubPageState();
}

class _StudentAcademicsHubPageState extends State<StudentAcademicsHubPage> {
  late Future<Map<String, dynamic>> _future;
  Object? _dataCache;
  Object? _payloadSource;
  StudentAcademicsPayload? _payload;
  List<AcademicsHubSection>? _hubSections;
  bool _recoverScheduled = false;

  void _applyAcademicsData(Map<String, dynamic> data) {
    if (identical(_payloadSource, data) && _hubSections != null) return;
    _payloadSource = data;
    _payload = StudentAcademicsPayload.parse(data);
    _hubSections = _buildHubSections(_payload!);
  }

  void _onAcademicsCache(Object? cache) {
    _dataCache = cache;
    if (cache is Map<String, dynamic>) {
      _applyAcademicsData(cache);
    }
  }

  @override
  void initState() {
    super.initState();
    final cached =
        TichScreenCache.read<Map<String, dynamic>>('student.academics');
    if (cached != null) {
      _applyAcademicsData(cached);
    }
    studentPrimeScreen(
      persistKey: 'student.academics',
      setCache: _onAcademicsCache,
      setFuture: (f) => _future = f,
      fetch: () => widget.api.academics(),
    );
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      _liveRefresh();
      StudentPrefetch.academicsSubsections(widget.api);
    });
  }

  Future<void> _reload() => studentCachedReload(
        mounted: () => mounted,
        setState: setState,
        fetch: () => widget.api.academics(),
        onData: (data) {
          _onAcademicsCache(data);
        },
        persistKey: 'student.academics',
      );

  void _liveRefresh() {
    tichSilentReload(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.academics(),
      onData: (data) {
        _onAcademicsCache(data);
      },
      persistKey: 'student.academics',
    );
  }

  void _openPayloadSection(
    String title,
    Widget Function(StudentAcademicsPayload payload) body,
  ) {
    final payload = _payload;
    if (payload == null) return;
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => AdaptiveScaffold(
          title: title,
          body: body(payload),
        ),
      ),
    );
  }

  Widget _listSection({
    required String empty,
    required List<Map<String, dynamic>> items,
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
      itemBuilder: (context, i) => tile(items[i]),
    );
  }

  int _sectionBadge(BuildContext context, String key) {
    return PortalNavCountsScope.maybeOf(context)?.countFor(key) ?? 0;
  }

  List<AcademicsHubSection> _buildHubSections(StudentAcademicsPayload p) {
    return [
      AcademicsHubSection(
        title: 'My units',
        icon: Icons.menu_book_outlined,
        badgeKey: 'academics.units',
        onTapDown: (_) => StudentPrefetch.academicsUnits(widget.api),
        onTap: () {
          Navigator.of(context).push(
          MaterialPageRoute(
            builder: (_) => StudentAcademicsLazySection(
              title: 'My units',
              cacheKey: 'student.academics.units',
              fetch: () => widget.api.academicsUnits(),
              builder: (data) {
                final units = [
                  for (final u in (data['registered_units'] as List? ?? const []))
                    if (u is Map) Map<String, dynamic>.from(u),
                ];
                return _StudentUnitsSection(units: units);
              },
            ),
          ),
        );
        },
      ),
      AcademicsHubSection(
        title: 'Learning content',
        icon: Icons.folder_open_outlined,
        badgeKey: 'academics.content',
        onTapDown: (_) => StudentPrefetch.academicsContent(widget.api),
        onTap: () {
          Navigator.of(context).push(
          MaterialPageRoute(
            builder: (_) => StudentAcademicsLazySection(
              title: 'Learning content',
              cacheKey: 'student.academics.content',
              fetch: () => widget.api.academicsContent(),
              builder: (data) {
                final items = [
                  for (final c in (data['unit_content'] as List? ?? const []))
                    if (c is Map) Map<String, dynamic>.from(c),
                ];
                return _listSection(
                  empty: 'No published content yet.',
                  items: items,
                  tile: (c) {
                    final hasFile = c['has_file'] == true;
                    final hasLink = (c['external_url']?.toString() ?? '')
                        .trim()
                        .isNotEmpty;
                    return TichCard(
                      onTap: () => openStudentLearningContent(
                        context,
                        widget.api,
                        c,
                      ),
                      child: ListTile(
                        contentPadding: EdgeInsets.zero,
                        title: Text(
                          c['title']?.toString() ?? 'Content',
                          style: tichUi(fontWeight: FontWeight.w600),
                        ),
                        subtitle: Text(
                          [
                            c['unit_code'],
                            c['content_type'],
                            if (hasFile) 'Attachment',
                            if (hasLink) 'Link',
                            formatDateTime(c['published_at']),
                          ]
                              .where((e) => e != null && '$e'.isNotEmpty)
                              .join(' · '),
                        ),
                        trailing: const Icon(Icons.chevron_right),
                      ),
                    );
                  },
                );
              },
            ),
          ),
        );
        },
      ),
      AcademicsHubSection(
        title: 'Assessments',
        icon: Icons.quiz_outlined,
        badgeKey: 'academics.assessments',
        onTapDown: (_) => StudentPrefetch.academicsAssessments(widget.api),
        onTap: () {
          Navigator.of(context).push(
          MaterialPageRoute(
            builder: (_) => StudentAcademicsLazySection(
              title: 'Assessments',
              cacheKey: 'student.academics.assessments',
              fetch: () => widget.api.academicsAssessments(),
              builder: (data) {
                final assessments = [
                  for (final a in (data['assessments'] as List? ?? const []))
                    if (a is Map) Map<String, dynamic>.from(a),
                ];
                return _listSection(
                  empty: 'No assessments available.',
                  items: assessments,
                  tile: (a) {
              final sub = a['submission'] is Map
                  ? a['submission'] as Map<String, dynamic>
                  : null;
                    final id = (a['id'] as num?)?.toInt();
                    final canTake = sub == null &&
                        (a['status']?.toString() == 'ready' ||
                            a['status']?.toString() == 'open');
                    Future<void> openTake() async {
                      if (id == null) return;
                      final done = await openStudentAssessmentTake(
                        context,
                        widget.api,
                        id,
                      );
                      if (done == true) _reload();
                    }

                    return TichCard(
                      onTapDown: id == null
                          ? null
                          : (_) => StudentPrefetch.assessment(widget.api, id),
                      onTap: canTake ? () => openTake() : null,
                      child: ListTile(
                        contentPadding: EdgeInsets.zero,
                        title: Text(a['title']?.toString() ?? 'Assessment',
                            style: tichUi(fontWeight: FontWeight.w600)),
                        subtitle: Text(
                          [
                            a['unit_code'],
                            '${a['question_count'] ?? 0} questions',
                            if (sub != null) 'Score: ${sub['score'] ?? '-'}',
                            if (canTake) 'Tap to take',
                          ].where((e) => e != null && '$e'.isNotEmpty).join(' · '),
                        ),
                        trailing: canTake
                            ? FilledButton(
                                onPressed: id == null ? null : () => openTake(),
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
        );
        },
      ),
      AcademicsHubSection(
        title: 'Exams & grades',
        icon: Icons.grade_outlined,
        badgeKey: 'academics.exams',
        onTapDown: (_) => StudentPrefetch.academics(widget.api),
        onTap: () => _openPayloadSection(
          'Exams & grades',
          (payload) {
            return ListView(
                  padding: const EdgeInsets.all(16),
                  children: [
                    StudentProvisionalResultsSection(
                      semesters: payload.provisionalResultsBySemester,
                    ),
                    const SizedBox(height: 16),
                    AdaptiveFilledButton(
                      onPressed: () async {
                        final method = await showDialog<String>(
                          context: context,
                          builder: (ctx) => TichFormDialog(
                            title: const Text('Request transcript'),
                            content: Column(
                              mainAxisSize: MainAxisSize.min,
                              crossAxisAlignment: CrossAxisAlignment.stretch,
                              children: [
                                for (final m in ['download', 'email', 'collect'])
                                  Padding(
                                    padding: const EdgeInsets.only(bottom: 8),
                                    child: OutlinedButton(
                                      onPressed: () => Navigator.pop(ctx, m),
                                      child: Text(m),
                                    ),
                                  ),
                              ],
                            ),
                            actions: [
                              TextButton(
                                onPressed: () => Navigator.pop(ctx),
                                child: const Text('Cancel'),
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
      AcademicsHubSection(
        title: 'Attendance',
        icon: Icons.how_to_reg_outlined,
        badgeKey: 'academics.attendance',
        onTapDown: (_) => StudentPrefetch.academics(widget.api),
        onTap: () => _openPayloadSection(
          'Attendance',
          (payload) => _listSection(
            empty: 'No attendance summaries yet.',
            items: payload.attendance,
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
                  studentAttendanceStatusLabel(a),
                  tone: studentAttendanceTone(a),
                ),
              ),
            ),
          ),
        ),
      ),
      AcademicsHubSection(
        title: 'Academic progress',
        icon: Icons.timeline_outlined,
        badgeKey: 'academics.progress',
        onTapDown: (_) => StudentPrefetch.academics(widget.api),
        onTap: () => _openPayloadSection(
          'Academic progress',
          (payload) => ListView(
            padding: const EdgeInsets.all(16),
            children: [
              StudentAcademicProgressSection(academics: payload.academics),
            ],
          ),
        ),
      ),
      AcademicsHubSection(
        title: 'Exam eligibility',
        icon: Icons.verified_outlined,
        badgeKey: 'academics.eligibility',
        onTapDown: (_) => StudentPrefetch.academics(widget.api),
        onTap: () => _openPayloadSection(
          'Exam eligibility',
          (payload) => _StudentExamEligibilitySection(
            api: widget.api,
            academics: payload.academics,
            feeClearanceStatus:
                payload.student['fee_clearance_status']?.toString(),
          ),
        ),
      ),
      AcademicsHubSection(
        title: 'Supp / special exams',
        icon: Icons.edit_note_outlined,
        badgeKey: 'academics.exam-requests',
        onTapDown: (_) => StudentPrefetch.academics(widget.api),
        onTap: () => _openPayloadSection(
          'Supp / special exams',
          (payload) => _ExamRequestsInteractive(
            api: widget.api,
            units: payload.registeredUnits,
            special: payload.specialExamRequests,
            supplementary: payload.supplementaryExamRequests,
            onChanged: _reload,
          ),
        ),
      ),
    ];
  }

  @override
  Widget build(BuildContext context) {
    return TichLiveRefreshBinder(
      onLiveRefresh: _liveRefresh,
      child: AdaptiveScaffold(
        title: 'Academics',
        body: FutureBuilder<Map<String, dynamic>>(
          future: _future,
          builder: (context, snap) {
            return studentCachedMapBody(
              snap: snap,
              memoryCache: _dataCache,
              persistKey: 'student.academics',
              onRetry: _reload,
              autoRecoverOnError: true,
              recoverScheduled: _recoverScheduled,
              setRecoverScheduled: (v) => _recoverScheduled = v,
              mounted: () => mounted,
              builder: (data) {
            _applyAcademicsData(data);
            final sections = _hubSections ?? const <AcademicsHubSection>[];
            return RefreshIndicator(
              onRefresh: () async => _reload(),
              child: ListView.separated(
                padding: const EdgeInsets.all(16),
                itemCount: sections.length,
                separatorBuilder: (_, __) => const SizedBox(height: 8),
                itemBuilder: (context, i) {
                  final s = sections[i];
                  final badge = _sectionBadge(context, s.badgeKey);
                  return TichCard(
                    onTapDown: s.onTapDown,
                    onTap: s.onTap,
                    child: ListTile(
                      leading: CircleAvatar(
                        backgroundColor: TichColors.blueLight,
                        child: Icon(s.icon, color: TichColors.blueDark),
                      ),
                      title: Text(
                        s.title,
                        style: tichUi(fontWeight: FontWeight.w600),
                      ),
                      trailing: TichMoreRowTrailing(badgeCount: badge),
                    ),
                  );
                },
              ),
            );
              },
            );
          },
        ),
      ),
    );
  }
}

class _StudentUnitsSection extends StatefulWidget {
  const _StudentUnitsSection({required this.units});

  final List<Map<String, dynamic>> units;

  @override
  State<_StudentUnitsSection> createState() => _StudentUnitsSectionState();
}

class _StudentUnitsSectionState extends State<_StudentUnitsSection> {
  int? _semesterId;

  List<({int id, String label})> get _semesters {
    final seen = <int>{};
    final out = <({int id, String label})>[];
    for (final u in widget.units) {
      final id = (u['semester_id'] as num?)?.toInt();
      if (id == null || seen.contains(id)) continue;
      seen.add(id);
      final label = u['semester_label']?.toString().trim();
      out.add((id: id, label: label?.isNotEmpty == true ? label! : 'Semester $id'));
    }
    out.sort((a, b) => b.id.compareTo(a.id));
    return out;
  }

  @override
  void initState() {
    super.initState();
    final semesters = _semesters;
    if (semesters.isNotEmpty) {
      _semesterId = semesters.first.id;
    }
  }

  @override
  Widget build(BuildContext context) {
    final semesters = _semesters;
    if (widget.units.isEmpty) {
      return ListView(
        padding: const EdgeInsets.all(16),
        children: const [TichCard(child: TichEmpty('No registered units yet.'))],
      );
    }

    final filtered = widget.units.where((u) {
      if (_semesterId == null) return true;
      return (u['semester_id'] as num?)?.toInt() == _semesterId;
    }).toList();

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        if (semesters.length > 1)
          Padding(
            padding: const EdgeInsets.only(bottom: 12),
            child: TichDropdownFormField<int>(
              value: _semesterId,
              decoration: const InputDecoration(labelText: 'Semester'),
              items: [
                for (final s in semesters)
                  DropdownMenuItem(value: s.id, child: Text(s.label)),
              ],
              onChanged: (v) => setState(() => _semesterId = v),
            ),
          )
        else if (semesters.length == 1)
          Padding(
            padding: const EdgeInsets.only(bottom: 8),
            child: Text(
              semesters.first.label,
              style: tichUi(fontWeight: FontWeight.w600, fontSize: 14),
            ),
          ),
        if (filtered.isEmpty)
          const TichCard(child: TichEmpty('No units for this semester.'))
        else
          ...filtered.map(
            (u) => Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: TichCard(
                child: ListTile(
                  contentPadding: EdgeInsets.zero,
                  title: Text(
                    '${u['unit_code'] ?? ''} · ${u['unit_name'] ?? ''}',
                    style: tichUi(fontWeight: FontWeight.w600),
                  ),
                  subtitle: Text(
                    [
                      u['registration_status'],
                      if (u['credit_hours'] != null) '${u['credit_hours']} CH',
                    ].where((e) => e != null && '$e'.isNotEmpty).join(' · '),
                  ),
                ),
              ),
            ),
          ),
      ],
    );
  }
}

class _StudentExamEligibilitySection extends StatefulWidget {
  const _StudentExamEligibilitySection({
    required this.api,
    required this.academics,
    required this.feeClearanceStatus,
  });

  final StudentApi api;
  final Map<String, dynamic> academics;
  final String? feeClearanceStatus;

  @override
  State<_StudentExamEligibilitySection> createState() =>
      _StudentExamEligibilitySectionState();
}

class _StudentExamEligibilitySectionState
    extends State<_StudentExamEligibilitySection> {
  bool _downloading = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _warmExamCardPdf());
  }

  void _warmExamCardPdf() {
    final portal = widget.academics['exam_portal'] is Map
        ? Map<String, dynamic>.from(widget.academics['exam_portal'] as Map)
        : null;
    final semesterId = (portal?['semester_id'] as num?)?.toInt();
    final canDownload =
        portal?['can_view_exam_card'] == true && semesterId != null;
    if (!canDownload) return;
    StudentPrefetch.examCardPdf(widget.api, semesterId);
  }

  Future<List<int>> _loadExamCardPdf(int semesterId) {
    return TichPdfCache.load(
      StudentPrefetch.examCardPdfKey(semesterId),
      () => widget.api.downloadExamCardPdf(semesterId: semesterId),
    );
  }

  Future<void> _viewExamCard(int semesterId) async {
    await Navigator.of(context).push<void>(
      MaterialPageRoute(
        builder: (_) => TichGeneratedDocumentViewer(
          cacheFileName: 'student-exam-card-$semesterId.pdf',
          loadPdf: () => _loadExamCardPdf(semesterId),
        ),
      ),
    );
  }

  Future<void> _downloadExamCard(int semesterId) async {
    setState(() => _downloading = true);
    try {
      final bytes = await _loadExamCardPdf(semesterId);
      final dir = await getTemporaryDirectory();
      final file = File('${dir.path}/exam-card-$semesterId.pdf');
      await file.writeAsBytes(bytes, flush: true);
      final result = await OpenFile.open(file.path);
      if (!mounted) return;
      if (result.type != ResultType.done) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              result.message.isNotEmpty
                  ? result.message
                  : 'Exam card saved. Open it from your files app if needed.',
            ),
          ),
        );
      }
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(tichDescribeError(e))),
      );
    } finally {
      if (mounted) setState(() => _downloading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final portal = widget.academics['exam_portal'] is Map
        ? Map<String, dynamic>.from(widget.academics['exam_portal'] as Map)
        : null;
    final attendance = (widget.academics['attendance'] as List? ?? const [])
        .map((e) => Map<String, dynamic>.from(e as Map))
        .toList();
    final upcoming = (portal?['upcoming_exams'] as List? ?? const [])
        .map((e) => Map<String, dynamic>.from(e as Map))
        .toList();
    final semesterId = (portal?['semester_id'] as num?)?.toInt();
    final canDownload = portal?['can_view_exam_card'] == true && semesterId != null;
    final feeCleared =
        (widget.feeClearanceStatus ?? '').toLowerCase() == 'cleared';
    final examCard = portal?['exam_card'] is Map
        ? Map<String, dynamic>.from(portal!['exam_card'] as Map)
        : null;

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        TichCard(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text(
                'Fee clearance',
                style: tichUi(fontWeight: FontWeight.w700),
              ),
              const SizedBox(height: 6),
              TichStatusChip(
                feeCleared ? 'Cleared' : (widget.feeClearanceStatus ?? 'Pending'),
                tone: feeCleared ? TichChipTone.success : TichChipTone.warning,
              ),
              if (canDownload) ...[
                const SizedBox(height: 16),
                AdaptiveFilledButton(
                  onPressed: _downloading
                      ? null
                      : () => _viewExamCard(semesterId),
                  child: const Text('View examination card'),
                ),
                const SizedBox(height: 8),
                OutlinedButton(
                  onPressed: _downloading
                      ? null
                      : () {
                          StudentPrefetch.examCardPdf(widget.api, semesterId);
                          _downloadExamCard(semesterId);
                        },
                  child: _downloading
                      ? const SizedBox(
                          width: 18,
                          height: 18,
                          child: AdaptiveProgress(),
                        )
                      : const Text('Download PDF'),
                ),
                if (examCard?['exam_card_number'] != null)
                  Padding(
                    padding: const EdgeInsets.only(top: 8),
                    child: Text(
                      'Card no. ${examCard!['exam_card_number']}',
                      style: tichUi(fontSize: 12, color: TichColors.neutralMuted),
                    ),
                  ),
              ] else if (portal != null)
                Padding(
                  padding: const EdgeInsets.only(top: 12),
                  child: Text(
                    'Clear fees and meet attendance requirements (≥90%) to download your exam card.',
                    style: tichUi(fontSize: 13, height: 1.4),
                  ),
                ),
            ],
          ),
        ),
        if (upcoming.isNotEmpty) ...[
          const SizedBox(height: 16),
          const TichSectionHeader('Upcoming examinations'),
          ...upcoming.map((exam) {
            final eligible = exam['eligible_for_exams'] == true ||
                exam['eligible_for_exams'] == 1;
            return Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: TichCard(
                child: ListTile(
                  contentPadding: EdgeInsets.zero,
                  title: Text(
                    '${exam['unit_code'] ?? ''} · ${exam['unit_name'] ?? ''}',
                    style: tichUi(fontWeight: FontWeight.w600),
                  ),
                  subtitle: Text(
                    [
                      formatDateTime(exam['exam_date']),
                      if (exam['start_time'] != null && exam['end_time'] != null)
                        '${exam['start_time']} – ${exam['end_time']}',
                      exam['venue'],
                    ].where((e) => e != null && '$e'.isNotEmpty).join(' · '),
                  ),
                  trailing: TichStatusChip(
                    eligible ? 'Eligible' : 'Not cleared',
                    tone: eligible ? TichChipTone.success : TichChipTone.warning,
                  ),
                ),
              ),
            );
          }),
        ],
        const SizedBox(height: 16),
        const TichSectionHeader('Unit attendance & eligibility'),
        if (attendance.isEmpty)
          const TichCard(child: TichEmpty('No eligibility data yet.'))
        else
          ...attendance.map((a) {
            final eligible = a['eligible_for_exams'] == true ||
                a['eligible_for_exams'] == 1;
            final blocked = a['exam_eligibility_blocked'] == true ||
                a['exam_eligibility_blocked'] == 1;
            return Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: TichCard(
                child: ListTile(
                  contentPadding: EdgeInsets.zero,
                  title: Text(
                    '${a['unit_code'] ?? ''} · ${a['unit_name'] ?? ''}',
                    style: tichUi(fontWeight: FontWeight.w600),
                  ),
                  subtitle: Text(
                    'Attendance ${a['attendance_percentage'] ?? 0}% · '
                    '${studentAttendanceStatusLabel(a)}',
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
              ),
            );
          }),
      ],
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
              TichDropdownFormField<String>(
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
              TichDropdownFormField<int>(
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
              const SizedBox(height: 10),
              OutlinedButton.icon(
                onPressed: _busy ? null : _pick,
                icon: const Icon(Icons.attach_file),
                label: Text(
                  _files.isNotEmpty
                      ? '${_files.length} file(s) attached'
                      : _type == 'special_exam'
                          ? 'Attach documents (required)'
                          : 'Attach documents (optional)',
                ),
              ),
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
