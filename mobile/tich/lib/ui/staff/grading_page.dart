import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../data/api_client.dart';
import '../../data/staff_api.dart';
import '../../theme/tich_theme.dart';
import '../widgets/tich_widgets.dart';
import '../widgets/tich_form_dialog.dart';
import '../widgets/tich_live_refresh.dart';
import 'staff_helpers.dart';

class StaffGradingPage extends StatefulWidget {
  const StaffGradingPage({super.key, required this.api});

  final StaffApi api;

  @override
  State<StaffGradingPage> createState() => _StaffGradingPageState();
}

class _StaffGradingPageState extends State<StaffGradingPage> {
  late Future<Map<String, dynamic>> _future;

  @override
  void initState() {
    super.initState();
    _future = widget.api.grading();
  }

  void _reload() => setState(() => _future = widget.api.grading());

  void _liveRefresh() {
    tichSilentReload(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.grading(),
      apply: (f) => _future = f,
    );
  }

  Future<void> _recordScore(List allocations) async {
    if (allocations.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('No unit allocations available.')),
      );
      return;
    }

    final studentId = TextEditingController();
    final name = TextEditingController(text: 'CAT 1');
    final max = TextEditingController(text: '30');
    final obtained = TextEditingController();
    int? allocationId = (allocations.first as Map)['id'] as int?;

    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => TichFormDialog(
        title: const Text('Record CAT score'),
        scrollable: true,
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
              TichDropdownFormField<int>(
                initialValue: allocationId,
                items: [
                  for (final raw in allocations)
                    DropdownMenuItem(
                      value: (raw as Map)['id'] as int?,
                      child: Text(
                        [
                          raw['unit_code'],
                          raw['unit_name'],
                        ]
                            .where((e) => e != null && '$e'.isNotEmpty)
                            .join(' · '),
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                ],
                onChanged: (v) => allocationId = v,
                decoration: const InputDecoration(labelText: 'Unit'),
              ),
              TextField(
                controller: studentId,
                keyboardType: TextInputType.number,
                decoration: const InputDecoration(labelText: 'Student ID'),
              ),
              TextField(
                controller: name,
                decoration: const InputDecoration(labelText: 'Assessment name'),
              ),
              TextField(
                controller: max,
                keyboardType: TextInputType.number,
                decoration: const InputDecoration(labelText: 'Max score'),
              ),
              TextField(
                controller: obtained,
                keyboardType: TextInputType.number,
                decoration: const InputDecoration(labelText: 'Score obtained'),
              ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Cancel'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Save'),
          ),
        ],
      ),
    );

    if (ok != true || allocationId == null) return;

    try {
      await widget.api.recordCat({
        'allocation_id': allocationId,
        'student_id': int.parse(studentId.text.trim()),
        'assessment_name': name.text.trim(),
        'assessment_type': 'cat',
        'max_score': double.parse(max.text.trim()),
        'score_obtained': double.parse(obtained.text.trim()),
      });
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Score recorded.')),
      );
      _reload();
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(e.message)));
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text('$e')));
    }
  }

  @override
  Widget build(BuildContext context) {
    return TichLiveRefreshBinder(
      onLiveRefresh: _liveRefresh,
      child: AdaptiveScaffold(
      title: 'Marks & assessments',
      floatingActionButton: FutureBuilder(
        future: _future,
        builder: (context, snap) {
          final allocations = snap.data?['allocations'] as List? ?? const [];
          return FloatingActionButton.extended(
            onPressed: () => _recordScore(allocations),
            icon: const Icon(Icons.add),
            label: const Text('Record score'),
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
          final scores = snap.data!['recent_scores'] as List? ?? const [];
          return RefreshIndicator(
            onRefresh: () async => _reload(),
            child: scores.isEmpty
                ? ListView(
                    children: const [
                      TichCard(child: TichEmpty('No recorded scores yet.')),
                    ],
                  )
                : ListView.separated(
                    padding: const EdgeInsets.all(16),
                    itemCount: scores.length,
                    separatorBuilder: (_, _) => const SizedBox(height: 8),
                    itemBuilder: (context, i) {
                      final s =
                          Map<String, dynamic>.from(scores[i] as Map);
                      return TichCard(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              s['assessment_name']?.toString() ?? 'Assessment',
                              style: tichUi(fontWeight: FontWeight.w700),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              '${s['student_name'] ?? ''} · ${s['registration_number'] ?? ''}',
                              style: tichUi(fontSize: 13),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              '${s['unit_code'] ?? ''} · '
                              '${s['score_obtained']}/${s['max_score']} '
                              '(${s['percentage_score']}%)',
                              style: tichUi(
                                fontSize: 12,
                                color: TichColors.neutralMuted,
                              ),
                            ),
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
