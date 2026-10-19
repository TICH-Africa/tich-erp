import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../data/api_client.dart';
import '../../data/student_api.dart';
import '../../theme/tich_theme.dart';
import '../widgets/date_time_pickers.dart';
import '../widgets/tich_widgets.dart';
import '../widgets/tich_form_dialog.dart';
import '../widgets/tich_live_refresh.dart';
import 'student_helpers.dart';

class StudentEvaluationsPage extends StatefulWidget {
  const StudentEvaluationsPage({super.key, required this.api});

  final StudentApi api;

  @override
  State<StudentEvaluationsPage> createState() => _StudentEvaluationsPageState();
}

class _StudentEvaluationsPageState extends State<StudentEvaluationsPage> {
  late Future<Map<String, dynamic>> _future;
  Object? _dataCache;

  @override
  void initState() {
    super.initState();
    _future = widget.api.evaluations();
  }

  void _reload() => setState(() => _future = widget.api.evaluations());

  void _liveRefresh() {
    tichSilentReload(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.evaluations(),
      onData: (data) => _dataCache = data,
    );
  }

  Future<void> _submit(Map<String, dynamic> window) async {
    int rating = 5;
    final comments = TextEditingController();
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setModal) => TichFormDialog(
          title: Text(window['title']?.toString() ?? 'Evaluation'),
          scrollable: true,
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              TichDropdownFormField<int>(
                value: rating,
                items: [1, 2, 3, 4, 5]
                    .map((v) => DropdownMenuItem(value: v, child: Text('$v')))
                    .toList(),
                onChanged: (v) => setModal(() => rating = v ?? 5),
                decoration: const InputDecoration(labelText: 'Rating'),
              ),
              const SizedBox(height: 10),
              TextField(
                controller: comments,
                maxLines: 4,
                decoration: const InputDecoration(labelText: 'Comments'),
              ),
            ],
          ),
          actions: [
            TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
            FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Submit')),
          ],
        ),
      ),
    );
    if (ok != true) return;
    try {
      final res = await widget.api.submitEvaluation({
        'window_id': window['id'],
        'rating': rating,
        'comments': comments.text.trim(),
      });
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(res['message']?.toString() ?? 'Submitted')),
      );
      _reload();
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    }
  }

  @override
  Widget build(BuildContext context) {
    return TichLiveRefreshBinder(
      onLiveRefresh: _liveRefresh,
      child: AdaptiveScaffold(
      title: 'Course evaluations',
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          final merged = tichMergeSnapshot(snap, _dataCache as dynamic);
          if (merged == null && snap.connectionState != ConnectionState.done) {
            return studentLoadingBody();
          }
          if (merged == null && snap.hasError) {
            return studentErrorBody(snap.error, _reload);
          }
          final data = merged! as Map<String, dynamic>;
          final windows = data['windows'] as List? ?? const [];
          final mine = data['my_evaluations'] as List? ?? const [];

          return RefreshIndicator(
            onRefresh: () async => _reload(),
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                const TichSectionHeader('Open windows'),
                if (windows.isEmpty)
                  const TichCard(child: TichEmpty('No open evaluation windows.'))
                else
                  ...windows.map((raw) {
                    final w = Map<String, dynamic>.from(raw as Map);
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 8),
                      child: TichCard(
                        child: ListTile(
                          contentPadding: EdgeInsets.zero,
                          title: Text(
                            w['title']?.toString() ?? 'Evaluation',
                            style: tichUi(fontWeight: FontWeight.w600),
                          ),
                          subtitle: Text(
                            'Closes ${formatDateTime(w['closes_at'])}',
                            style: tichUi(fontSize: 12, color: TichColors.neutralMuted),
                          ),
                          trailing: FilledButton(
                            onPressed: () => _submit(w),
                            child: const Text('Rate'),
                          ),
                        ),
                      ),
                    );
                  }),
                const SizedBox(height: 16),
                const TichSectionHeader('My submissions'),
                if (mine.isEmpty)
                  const TichCard(child: TichEmpty('No evaluations submitted.'))
                else
                  ...mine.map((raw) {
                    final e = Map<String, dynamic>.from(raw as Map);
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 8),
                      child: TichCard(
                        child: ListTile(
                          contentPadding: EdgeInsets.zero,
                          title: Text('Rating: ${e['rating']}/5',
                              style: tichUi(fontWeight: FontWeight.w600)),
                          subtitle: Text(formatDateTime(e['submitted_at'])),
                        ),
                      ),
                    );
                  }),
              ],
            ),
          );
        },
      ),
      ),
    );
  }
}
