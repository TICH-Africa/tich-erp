import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../data/api_client.dart';
import '../../data/staff_api.dart';
import '../../theme/tich_theme.dart';
import '../widgets/date_time_pickers.dart';
import '../widgets/tich_widgets.dart';
import '../widgets/tich_live_refresh.dart';
import 'lesson_plan_create_page.dart';
import 'staff_helpers.dart';

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
    _future = widget.api.lessonPlans();
  }

  void _reload() => setState(() => _future = widget.api.lessonPlans());

  void _liveRefresh() {
    tichSilentReload(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.lessonPlans(),
      onData: (data) => _dataCache = data,
    );
  }

  Future<void> _openCreate(List allocations) async {
    if (allocations.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('No units assigned yet.')),
      );
      return;
    }
    final created = await Navigator.of(context).push<bool>(
      MaterialPageRoute(
        builder: (_) => StaffLessonPlanCreatePage(
          api: widget.api,
          allocations: allocations
              .map((e) => Map<String, dynamic>.from(e as Map))
              .toList(),
        ),
      ),
    );
    if (created == true) _reload();
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
            final merged = tichMergeSnapshot(snap, _dataCache as dynamic)
                as Map<String, dynamic>?;
            final allocations = (merged?['allocations'] as List?) ??
                (snap.data?['allocations'] as List?) ??
                const [];
            return FloatingActionButton.extended(
              onPressed: () => _openCreate(allocations),
              icon: const Icon(Icons.add),
              label: const Text('New plan'),
            );
          },
        ),
        body: FutureBuilder<Map<String, dynamic>>(
          future: _future,
          builder: (context, snap) {
            final merged = tichMergeSnapshot(snap, _dataCache as dynamic);
            if (merged == null && snap.connectionState != ConnectionState.done) {
              return staffLoadingBody();
            }
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
                        return TichCard(
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
                                formatDate(p['planned_date']),
                                style: tichUi(
                                  fontSize: 12,
                                  color: TichColors.neutralMuted,
                                ),
                              ),
                              if (status == 'draft' && id != null) ...[
                                const SizedBox(height: 8),
                                Align(
                                  alignment: Alignment.centerRight,
                                  child: TextButton(
                                    onPressed: () async {
                                      try {
                                        await widget.api.submitLessonPlan(id);
                                        if (!mounted) return;
                                        ScaffoldMessenger.of(context)
                                            .showSnackBar(
                                          const SnackBar(
                                            content: Text(
                                              'Submitted for approval.',
                                            ),
                                          ),
                                        );
                                        _reload();
                                      } on ApiException catch (e) {
                                        if (!mounted) return;
                                        ScaffoldMessenger.of(context)
                                            .showSnackBar(
                                          SnackBar(content: Text(e.message)),
                                        );
                                      }
                                    },
                                    child: const Text('Submit'),
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
