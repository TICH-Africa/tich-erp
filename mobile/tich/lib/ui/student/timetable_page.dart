import 'dart:async';

import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../data/student_api.dart';
import '../../notifications/tich_notifications.dart';
import '../../theme/tich_theme.dart';
import '../widgets/tich_live_refresh.dart';
import '../widgets/tich_timetable_documents.dart';
import 'student_helpers.dart';
import 'student_prefetch.dart';

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
  int _visibleTab = 0;
  bool _recoverScheduled = false;

  static const _cacheKey = 'student.timetable';

  @override
  void initState() {
    super.initState();
    _tabs = TabController(length: 2, vsync: this);
    _tabs.addListener(_onTabChanged);
    studentPrimeScreen(
      persistKey: _cacheKey,
      setCache: (c) => _dataCache = c,
      setFuture: (f) => _future = f,
      fetch: () => widget.api.timetable(),
    );
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      StudentPrefetch.timetableDocumentsFromCache(widget.api);
      final cached = studentReadCache(_cacheKey);
      if (cached != null) {
        _dataCache = cached;
        _warmVisibleDocument(cached);
        scheduleMicrotask(() {
          if (!mounted) return;
          _scheduleLessonReminders(cached);
        });
        Future<void>.delayed(const Duration(seconds: 10), () {
          if (!mounted) return;
          _liveRefresh();
        });
        return;
      }
      _liveRefresh();
    });
  }

  void _onTabChanged() {
    if (!_tabs.indexIsChanging && _tabs.index != _visibleTab) {
      setState(() => _visibleTab = _tabs.index);
    }
  }

  @override
  void dispose() {
    _tabs.removeListener(_onTabChanged);
    _tabs.dispose();
    super.dispose();
  }

  Future<void> _reload() => studentCachedReload(
        mounted: () => mounted,
        setState: setState,
        fetch: () => widget.api.timetable(),
        onData: (data) {
          _dataCache = data;
          _warmVisibleDocument(data);
          _scheduleLessonReminders(data);
        },
        persistKey: _cacheKey,
      );

  void _liveRefresh() {
    tichSilentReload(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.timetable(),
      onData: (data) {
        _dataCache = data;
        _warmVisibleDocument(data);
        scheduleMicrotask(() {
          if (!mounted) return;
          _scheduleLessonReminders(data);
        });
      },
      persistKey: _cacheKey,
    );
  }

  void _scheduleLessonReminders(Map<String, dynamic> data) {
    final tt = Map<String, dynamic>.from(data['timetable'] as Map? ?? {});
    final reminder = tt['lesson_reminder_sessions'] as List?;
    final sessions = reminder != null && reminder.isNotEmpty
        ? reminder
            .map((e) => Map<String, dynamic>.from(e as Map))
            .toList()
        : _sessionsFor(tt['timetables'] as List? ?? const [], 'lesson');
    unawaited(TichNotifications.instance.scheduleLessonReminders(sessions));
  }

  void _warmVisibleDocument(Map<String, dynamic> data) {
    final tt = Map<String, dynamic>.from(data['timetable'] as Map? ?? {});
    if (tt['is_provisional'] == true) return;
    final docs = _documentsFrom(tt);
    final kind = _visibleTab == 0 ? 'lesson' : 'exam';
    final doc = TichTimetableDocumentRef.forKind(docs, kind);
    if (doc == null) return;
    StudentPrefetch.timetablePdf(widget.api, {
      'id': doc.id,
      'kind': doc.kind,
      'is_upload_document': doc.isUploadDocument,
      'source_name': doc.sourceName,
    });
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
      timetableBlock['documents'] as List?,
      timetablesFallback: timetableBlock['timetables'] as List?,
    );
  }

  Widget _timetableBody(Map<String, dynamic> merged) {
    final tt = Map<String, dynamic>.from(merged['timetable'] as Map? ?? {});
    final provisional = tt['is_provisional'] == true;
    final docs = _documentsFrom(tt);
    final lesson = TichTimetableDocumentRef.forKind(docs, 'lesson');
    final exam = TichTimetableDocumentRef.forKind(docs, 'exam');

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        if (provisional)
          MaterialBanner(
            content: const Text('Showing provisional draft timetable.'),
            actions: [
              TextButton(onPressed: _reload, child: const Text('Refresh')),
            ],
          ),
        Expanded(
          child: LayoutBuilder(
            builder: (context, constraints) {
              return RefreshIndicator(
                onRefresh: () async => _reload(),
                child: SingleChildScrollView(
                  physics: const AlwaysScrollableScrollPhysics(),
                  child: SizedBox(
                    height: constraints.maxHeight,
                    child: IndexedStack(
                      index: _visibleTab,
                      children: [
                        TichTimetableDocumentPane(
                          document: lesson,
                          loadPdf: widget.api.downloadTimetablePdf,
                          provisional: provisional,
                          active: _visibleTab == 0,
                          unavailableMessage:
                              'No lesson timetable published yet.',
                        ),
                        TichTimetableDocumentPane(
                          document: exam,
                          loadPdf: widget.api.downloadTimetablePdf,
                          provisional: provisional,
                          active: _visibleTab == 1,
                          unavailableMessage:
                              'No exam timetable published yet.',
                        ),
                      ],
                    ),
                  ),
                ),
              );
            },
          ),
        ),
      ],
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
              onTap: (index) {
                setState(() => _visibleTab = index);
                final cache = _dataCache;
                if (cache is Map<String, dynamic>) {
                  _warmVisibleDocument(cache);
                }
              },
              tabs: const [
                Tab(text: 'Lesson'),
                Tab(text: 'Exam'),
              ],
            ),
            Expanded(
              child: FutureBuilder<Map<String, dynamic>>(
                future: _future,
                builder: (context, snap) {
                  return studentCachedMapBody(
                    snap: snap,
                    memoryCache: _dataCache,
                    persistKey: _cacheKey,
                    onRetry: _reload,
                    autoRecoverOnError: true,
                    recoverScheduled: _recoverScheduled,
                    setRecoverScheduled: (v) => _recoverScheduled = v,
                    mounted: () => mounted,
                    builder: (merged) {
                      if (_dataCache != merged) {
                        scheduleMicrotask(() {
                          if (!mounted) return;
                          setState(() => _dataCache = merged);
                          _warmVisibleDocument(merged);
                        });
                      }
                      return _timetableBody(merged);
                    },
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
