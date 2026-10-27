import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../data/student_api.dart';
import '../../notifications/tich_notifications.dart';
import '../../theme/tich_theme.dart';
import '../widgets/tich_live_refresh.dart';
import '../widgets/tich_timetable_documents.dart';
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
  Object? _dataCache;
  late TabController _tabs;

  static const _cacheKey = 'student.timetable';

  @override
  void initState() {
    super.initState();
    _tabs = TabController(length: 2, vsync: this);
    _dataCache = studentReadCache(_cacheKey);
    _future = studentEnsure(_cacheKey, () => widget.api.timetable());
    WidgetsBinding.instance.addPostFrameCallback((_) {
      Future<void>.delayed(const Duration(seconds: 2), () {
        if (!mounted) return;
        _future.then((data) {
          if (!mounted) return;
          final tt = Map<String, dynamic>.from(data['timetable'] as Map? ?? {});
          final timetables = tt['timetables'] as List? ?? const [];
          // ignore: unawaited_futures
          TichNotifications.instance
              .scheduleLessonReminders(_sessionsFor(timetables, 'lesson'));
        }).catchError((_) {});
      });
    });
  }

  @override
  void dispose() {
    _tabs.dispose();
    super.dispose();
  }

  Future<void> _reload() => studentCachedReload(
        mounted: () => mounted,
        setState: setState,
        fetch: () => widget.api.timetable(),
        onData: (data) => _dataCache = data,
        persistKey: _cacheKey,
      );

  void _liveRefresh() {
    tichSilentReload(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.timetable(),
      onData: (data) => _dataCache = data,
      persistKey: _cacheKey,
    );
  }

  List<Map<String, dynamic>> _sessionsFor(
    List timetables,
    String kind,
  ) {
    final match = timetables
        .cast<dynamic>()
        .map((e) => Map<String, dynamic>.from(e as Map))
        .where((t) => (t['kind']?.toString() ?? '') == kind);
    if (match.isEmpty) return const [];
    return (match.first['sessions'] as List? ?? const [])
        .map((e) => Map<String, dynamic>.from(e as Map))
        .toList();
  }

  List<TichTimetableDocumentRef> _documentsFrom(
    Map<String, dynamic> timetableBlock,
  ) {
    return TichTimetableDocumentRef.parseDocuments(
      null,
      timetablesFallback: timetableBlock['timetables'] as List?,
    );
  }

  @override
  Widget build(BuildContext context) {
    return TichLiveRefreshBinder(
      onLiveRefresh: _liveRefresh,
      child: AdaptiveScaffold(
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
                  final tt = Map<String, dynamic>.from(
                    (merged! as Map<String, dynamic>)['timetable'] as Map? ?? {},
                  );
                  final provisional = tt['is_provisional'] == true;
                  final docs = _documentsFrom(tt);
                  final lesson =
                      TichTimetableDocumentRef.forKind(docs, 'lesson');
                  final exam = TichTimetableDocumentRef.forKind(docs, 'exam');

                  return Column(
                    children: [
                      if (provisional)
                        MaterialBanner(
                          content: const Text(
                            'Showing provisional draft timetable.',
                          ),
                          actions: [
                            TextButton(
                              onPressed: _reload,
                              child: const Text('Refresh'),
                            ),
                          ],
                        ),
                      Expanded(
                        child: TabBarView(
                          controller: _tabs,
                          children: [
                            RefreshIndicator(
                              onRefresh: () async => _reload(),
                              child: ListView(
                                physics: const AlwaysScrollableScrollPhysics(),
                                children: [
                                  SizedBox(
                                    height:
                                        MediaQuery.sizeOf(context).height * 0.72,
                                    child: TichTimetableDocumentPane(
                                      document: lesson,
                                      loadPdf: widget.api.downloadTimetablePdf,
                                      provisional: provisional,
                                      unavailableMessage:
                                          'No lesson timetable published yet.',
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            RefreshIndicator(
                              onRefresh: () async => _reload(),
                              child: ListView(
                                physics: const AlwaysScrollableScrollPhysics(),
                                children: [
                                  SizedBox(
                                    height:
                                        MediaQuery.sizeOf(context).height * 0.72,
                                    child: TichTimetableDocumentPane(
                                      document: exam,
                                      loadPdf: widget.api.downloadTimetablePdf,
                                      provisional: provisional,
                                      unavailableMessage:
                                          'No exam timetable published yet.',
                                    ),
                                  ),
                                ],
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
      ),
    );
  }
}
