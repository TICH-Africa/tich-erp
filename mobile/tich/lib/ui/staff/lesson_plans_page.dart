import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../data/staff_api.dart';
import '../../theme/tich_theme.dart';
import '../widgets/date_time_pickers.dart';
import '../widgets/tich_widgets.dart';
import '../widgets/tich_live_refresh.dart';
import 'lesson_plan_create_page.dart';
import 'lesson_plan_detail_page.dart';
import 'lesson_plan_upload_page.dart';
import 'staff_helpers.dart';
import 'staff_prefetch.dart';

class StaffLessonPlansPage extends StatefulWidget {
  const StaffLessonPlansPage({super.key, required this.api});

  final StaffApi api;

  @override
  State<StaffLessonPlansPage> createState() => _StaffLessonPlansPageState();
}

class _StaffLessonPlansPageState extends State<StaffLessonPlansPage> {
  late Future<Map<String, dynamic>> _future;
  Object? _dataCache;

  @override
  void initState() {
    super.initState();
    StaffPrefetch.timetable(widget.api);
    staffPrimeScreen(
      persistKey: 'staff.lesson_plans',
      setCache: (c) => _dataCache = c,
      setFuture: (f) => _future = f,
      fetch: () => widget.api.lessonPlans(),
    );
  }

  Future<void> _reload() => staffCachedReload(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.lessonPlans(),
      onData: (data) => _dataCache = data,
      persistKey: 'staff.lesson_plans',
    );

  void _liveRefresh() {
    tichSilentReload(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.lessonPlans(),
      onData: (data) => _dataCache = data,
      persistKey: 'staff.lesson_plans',
    );
  }

  Future<void> _openCreateSheet(List allocations) async {
    if (allocations.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('No units assigned yet.')),
      );
      return;
    }
    final maps = allocations
        .map((e) => Map<String, dynamic>.from(e as Map))
        .toList();

    final choice = await showModalBottomSheet<String>(
      context: context,
      showDragHandle: true,
      builder: (ctx) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              leading: const Icon(Icons.edit_note_outlined),
              title: const Text('Fill in online'),
              subtitle: const Text('CHP template — preview & verify before submit'),
              onTap: () => Navigator.pop(ctx, 'form'),
            ),
            ListTile(
              leading: const Icon(Icons.upload_file_outlined),
              title: const Text('Upload document'),
              subtitle: const Text('PDF or Word lesson plan'),
              onTap: () => Navigator.pop(ctx, 'upload'),
            ),
          ],
        ),
      ),
    );
    if (!mounted || choice == null) return;

    if (choice == 'upload') {
      final created = await Navigator.of(context).push<bool>(
        MaterialPageRoute(
          builder: (_) => StaffLessonPlanUploadPage(
            api: widget.api,
            allocations: maps,
          ),
        ),
      );
      if (created == true) _reload();
      return;
    }

    final defaultId = (maps.first['id'] as num?)?.toInt();
    final today =
        '${DateTime.now().year.toString().padLeft(4, '0')}-${DateTime.now().month.toString().padLeft(2, '0')}-${DateTime.now().day.toString().padLeft(2, '0')}';
    StaffPrefetch.lessonPlanCreate(
      widget.api,
      defaultAllocationId: defaultId,
      plannedDate: today,
    );
    final created = await Navigator.of(context).push<bool>(
      MaterialPageRoute(
        builder: (_) => StaffLessonPlanCreatePage(
          api: widget.api,
          allocations: maps,
        ),
      ),
    );
    if (created == true) _reload();
  }

  Future<void> _openDetail(Map<String, dynamic> summary) async {
    final id = (summary['id'] as num?)?.toInt();
    if (id == null) return;
    await Navigator.of(context).push<bool>(
      MaterialPageRoute(
        builder: (_) => StaffLessonPlanDetailPage(
          api: widget.api,
          planId: id,
          initialPlan: summary,
        ),
      ),
    );
    if (mounted) _reload();
  }

  @override
  Widget build(BuildContext context) {
    return TichLiveRefreshBinder(
      onLiveRefresh: _liveRefresh,
      child: AdaptiveScaffold(
        title: 'Lesson plans',
        floatingActionButton: FutureBuilder(
          future: _future,
          builder: (context, snap) {
            final merged = tichMergeSnapshot(snap, _dataCache as dynamic, persistKey: 'staff.lesson_plans')
                as Map<String, dynamic>?;
            final allocations = (merged?['allocations'] as List?) ??
                (snap.data?['allocations'] as List?) ??
                const [];
            return FloatingActionButton.extended(
              onPressed: () => _openCreateSheet(allocations),
              icon: const Icon(Icons.add),
              label: const Text('New plan'),
            );
          },
        ),
        body: FutureBuilder<Map<String, dynamic>>(
          future: _future,
          builder: (context, snap) {
            final merged = tichMergeSnapshot(snap, _dataCache as dynamic, persistKey: 'staff.lesson_plans');
            if (tichShowLoadingSpinner(snap, merged)) { return staffLoadingBody(onRefresh: () async => _reload()); }
            if (merged == null && snap.hasError) {
              return staffErrorBody(snap.error, _reload);
            }
            final data = merged! as Map<String, dynamic>;
            final items = data['items'] as List? ?? const [];
            return RefreshIndicator(
              onRefresh: () async => _reload(),
              child: items.isEmpty
                  ? ListView(
                      physics: const AlwaysScrollableScrollPhysics(),
                      children: const [
                        TichCard(child: TichEmpty('No lesson plans yet.')),
                      ],
                    )
                  : ListView.separated(
                      physics: const AlwaysScrollableScrollPhysics(),
                      padding: const EdgeInsets.all(16),
                      itemCount: items.length,
                      separatorBuilder: (_, _) => const SizedBox(height: 8),
                      itemBuilder: (context, i) {
                        final p =
                            Map<String, dynamic>.from(items[i] as Map);
                        final id = (p['id'] as num?)?.toInt();
                        final status = p['status']?.toString() ?? 'draft';
                        final source =
                            (p['source_type'] ?? 'form').toString();
                        final canSubmit = p['can_submit'] == true;
                        return TichCard(
                          onTap: id == null ? null : () => _openDetail(p),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                children: [
                                  Expanded(
                                    child: Text(
                                      p['lesson_title']?.toString().isNotEmpty ==
                                              true
                                          ? p['lesson_title'].toString()
                                          : (p['plan_number']?.toString() ??
                                              'Lesson plan'),
                                      style:
                                          tichUi(fontWeight: FontWeight.w700),
                                    ),
                                  ),
                                  TichStatusChip(status),
                                ],
                              ),
                              const SizedBox(height: 4),
                              Text(
                                [
                                  p['unit_code'],
                                  p['unit_name'],
                                ]
                                    .where((e) => e != null && '$e'.isNotEmpty)
                                    .join(' · '),
                                style: tichUi(fontSize: 13),
                              ),
                              Text(
                                [
                                  formatDate(p['planned_date']),
                                  source == 'upload' ? 'Upload' : 'Online',
                                ].join(' · '),
                                style: tichUi(
                                  fontSize: 12,
                                  color: TichColors.neutralMuted,
                                ),
                              ),
                              if (const {'draft', 'modified', 'rejected'}
                                  .contains(status)) ...[
                                const SizedBox(height: 8),
                                Align(
                                  alignment: Alignment.centerRight,
                                  child: TextButton(
                                    onPressed: id == null
                                        ? null
                                        : () => _openDetail(p),
                                    child: Text(
                                      canSubmit
                                          ? 'Preview & submit'
                                          : source == 'upload'
                                              ? 'Preview upload'
                                              : 'Preview & verify',
                                    ),
                                  ),
                                ),
                              ],
                            ],
                          ),
                        );
                      },
                    ),
            );
          },
        ),
      ),
    );
  }
}
