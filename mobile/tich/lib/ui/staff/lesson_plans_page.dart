import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../data/api_client.dart';
import '../../data/staff_api.dart';
import '../../theme/tich_theme.dart';
import '../widgets/date_time_pickers.dart';
import '../widgets/tich_widgets.dart';
import '../widgets/tich_live_refresh.dart';
import 'staff_helpers.dart';

class StaffLessonPlansPage extends StatefulWidget {
  const StaffLessonPlansPage({super.key, required this.api});

  final StaffApi api;

  @override
  State<StaffLessonPlansPage> createState() => _StaffLessonPlansPageState();
}

class _StaffLessonPlansPageState extends State<StaffLessonPlansPage> {
  late Future<Map<String, dynamic>> _future;

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
      apply: (f) => _future = f,
    );
  }

  Future<void> _create(List allocations) async {
    if (allocations.isEmpty) return;
    final title = TextEditingController();
    final objectives = TextEditingController();
    final topics = TextEditingController();
    DateTime planned = DateTime.now();
    int? allocationId = (allocations.first as Map)['id'] as int?;

    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setLocal) => AlertDialog(
          title: const Text('New lesson plan'),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                DropdownButtonFormField<int>(
                  initialValue: allocationId,
                  items: [
                    for (final raw in allocations)
                      DropdownMenuItem(
                        value: (raw as Map)['id'] as int?,
                        child: Text(
                          raw['label']?.toString() ?? 'Unit',
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                  ],
                  onChanged: (v) => allocationId = v,
                  decoration: const InputDecoration(labelText: 'Unit'),
                ),
                TextField(
                  controller: title,
                  decoration: const InputDecoration(labelText: 'Lesson title'),
                ),
                TextField(
                  controller: objectives,
                  maxLines: 3,
                  decoration:
                      const InputDecoration(labelText: 'Objectives (required)'),
                ),
                TextField(
                  controller: topics,
                  decoration: const InputDecoration(labelText: 'Topics'),
                ),
                ListTile(
                  contentPadding: EdgeInsets.zero,
                  title: Text('Date: ${formatDate(planned)}'),
                  trailing: const Icon(Icons.calendar_today),
                  onTap: () async {
                    final picked = await showDatePicker(
                      context: ctx,
                      initialDate: planned,
                      firstDate: DateTime.now().subtract(const Duration(days: 30)),
                      lastDate: DateTime.now().add(const Duration(days: 180)),
                    );
                    if (picked != null) setLocal(() => planned = picked);
                  },
                ),
              ],
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(ctx, false),
              child: const Text('Cancel'),
            ),
            FilledButton(
              onPressed: () => Navigator.pop(ctx, true),
              child: const Text('Create'),
            ),
          ],
        ),
      ),
    );

    if (ok != true || allocationId == null) return;
    if (objectives.text.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Objectives are required.')),
      );
      return;
    }

    try {
      await widget.api.createLessonPlan({
        'allocation_id': allocationId,
        'lesson_title': title.text.trim(),
        'lesson_objectives': objectives.text.trim(),
        'topics_covered': topics.text.trim(),
        'planned_date':
            '${planned.year.toString().padLeft(4, '0')}-${planned.month.toString().padLeft(2, '0')}-${planned.day.toString().padLeft(2, '0')}',
        'week_number': 1,
        'contact_hours': 2,
      });
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Lesson plan created.')),
      );
      _reload();
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(e.message)));
    }
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
          final allocations = snap.data?['allocations'] as List? ?? const [];
          return FloatingActionButton.extended(
            onPressed: () => _create(allocations),
            icon: const Icon(Icons.add),
            label: const Text('New plan'),
          );
        },
      ),
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          if (snap.connectionState != ConnectionState.done) {
            return staffLoadingBody();
          }
          if (snap.hasError) {
            return staffErrorBody(snap.error, _reload);
          }
          final items = snap.data!['items'] as List? ?? const [];
          return RefreshIndicator(
            onRefresh: () async => _reload(),
            child: items.isEmpty
                ? ListView(
                    children: const [
                      TichCard(child: TichEmpty('No lesson plans yet.')),
                    ],
                  )
                : ListView.separated(
                    padding: const EdgeInsets.all(16),
                    itemCount: items.length,
                    separatorBuilder: (_, __) => const SizedBox(height: 8),
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
                                          content: Text('Submitted for approval.'),
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
