import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../data/staff_api.dart';
import '../../theme/tich_theme.dart';
import '../widgets/tich_live_refresh.dart';
import '../widgets/tich_timetable_documents.dart';
import '../widgets/tich_widgets.dart';
import 'staff_helpers.dart';

class StaffTimetablePage extends StatefulWidget {
  const StaffTimetablePage({super.key, required this.api});

  final StaffApi api;

  @override
  State<StaffTimetablePage> createState() => _StaffTimetablePageState();
}

class _StaffTimetablePageState extends State<StaffTimetablePage>
    with SingleTickerProviderStateMixin {
  late Future<Map<String, dynamic>> _future;
  Object? _dataCache;
  late TabController _tabs;

  @override
  void initState() {
    super.initState();
    _tabs = TabController(length: 2, vsync: this);
    staffPrimeScreen(
      persistKey: 'staff.timetable',
      setCache: (c) => _dataCache = c,
      setFuture: (f) => _future = f,
      fetch: () => widget.api.timetable(),
    );
  }

  @override
  void dispose() {
    _tabs.dispose();
    super.dispose();
  }

  Future<void> _reload() => staffCachedReload(
        mounted: () => mounted,
        setState: setState,
        fetch: () => widget.api.timetable(),
        onData: (data) => _dataCache = data,
        persistKey: 'staff.timetable',
      );

  void _liveRefresh() {
    tichSilentReload(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.timetable(),
      onData: (data) => _dataCache = data,
      persistKey: 'staff.timetable',
    );
  }

  @override
  Widget build(BuildContext context) {
    return TichLiveRefreshBinder(
      onLiveRefresh: _liveRefresh,
      child: AdaptiveScaffold(
        title: 'Timetable',
        body: FutureBuilder<Map<String, dynamic>>(
          future: _future,
          builder: (context, snap) {
            final merged = tichMergeSnapshot(
              snap,
              _dataCache as dynamic,
              persistKey: 'staff.timetable',
            );
            if (tichShowLoadingSpinner(snap, merged)) {
              return staffLoadingBody(onRefresh: () async => _reload());
            }
            if (merged == null && snap.hasError) {
              return staffErrorBody(snap.error, _reload);
            }
            final data = merged! as Map<String, dynamic>;
            final docs = TichTimetableDocumentRef.parseDocuments(
              data['documents'] as List?,
            );
            final provisional = data['is_provisional'] == true;
            final lesson = TichTimetableDocumentRef.forKind(docs, 'lesson');
            final exam = TichTimetableDocumentRef.forKind(docs, 'exam');

            if (docs.isEmpty) {
              return RefreshIndicator(
                onRefresh: () async => _reload(),
                child: ListView(
                  physics: const AlwaysScrollableScrollPhysics(),
                  children: const [
                    TichCard(child: TichEmpty('No timetable document assigned yet.')),
                  ],
                ),
              );
            }

            Widget pane(TichTimetableDocumentRef? doc, String emptyLabel) {
              return TichTimetableDocumentPane(
                document: doc,
                loadPdf: widget.api.downloadTimetablePdf,
                provisional: false,
                unavailableMessage: emptyLabel,
              );
            }

            final docHeight = MediaQuery.sizeOf(context).height * 0.72;

            return Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                if (provisional)
                  MaterialBanner(
                    content: const Text(
                      'Draft timetable - document may change before publication.',
                    ),
                    actions: [
                      TextButton(onPressed: _reload, child: const Text('Refresh')),
                    ],
                  ),
                TabBar(
                  controller: _tabs,
                  labelColor: TichColors.green,
                  tabs: const [
                    Tab(text: 'Lesson'),
                    Tab(text: 'Exam'),
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
                              height: docHeight,
                              child: pane(
                                lesson,
                                'No lesson timetable assigned yet.',
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
                              height: docHeight,
                              child: pane(
                                exam,
                                'No exam timetable assigned yet.',
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
    );
  }
}
