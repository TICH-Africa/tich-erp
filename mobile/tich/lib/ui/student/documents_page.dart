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

class StudentDocumentsPage extends StatefulWidget {
  const StudentDocumentsPage({super.key, required this.api});

  final StudentApi api;

  @override
  State<StudentDocumentsPage> createState() => _StudentDocumentsPageState();
}

class _StudentDocumentsPageState extends State<StudentDocumentsPage> {
  late Future<Map<String, dynamic>> _future;
  Object? _dataCache;

  @override
  void initState() {
    super.initState();
    studentPrimeScreen(
      persistKey: 'student.documents',
      setCache: (c) => _dataCache = c,
      setFuture: (f) => _future = f,
      fetch: () => widget.api.documents(),
    );
  }

  Future<void> _reload() => studentCachedReload(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.documents(),
      onData: (data) => _dataCache = data,
      persistKey: 'student.documents',
    );

  void _liveRefresh() {
    tichSilentReload(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.documents(),
      onData: (data) => _dataCache = data,
    );
  }

  Future<void> _requestDoc(Map types) async {
    String? selected = types.keys.isEmpty ? null : types.keys.first.toString();
    final notes = TextEditingController();
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setModal) => TichFormDialog(
          title: const Text('Request document'),
          scrollable: true,
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              TichDropdownFormField<String>(
                value: selected,
                items: types.entries
                    .map(
                      (e) => DropdownMenuItem(
                        value: e.key.toString(),
                        child: Text(e.value.toString()),
                      ),
                    )
                    .toList(),
                onChanged: (v) => setModal(() => selected = v),
                decoration: const InputDecoration(labelText: 'Document type'),
              ),
              const SizedBox(height: 10),
              TextField(
                controller: notes,
                maxLines: 3,
                decoration: const InputDecoration(labelText: 'Notes (optional)'),
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
    if (ok != true || selected == null) return;
    try {
      final res = await widget.api.requestDocument(
        documentType: selected!,
        notes: notes.text.trim().isEmpty ? null : notes.text.trim(),
      );
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
      title: 'Documents',
      floatingActionButton: FutureBuilder(
        future: _future,
        builder: (context, snap) {
          final fabData = tichMergeSnapshot(snap, _dataCache as dynamic, persistKey: 'student.documents')
              as Map<String, dynamic>?;
          if (fabData == null) return const SizedBox.shrink();
          final types = fabData['request_types'] is Map
              ? Map.from(fabData['request_types'] as Map)
              : <String, String>{};
          return FloatingActionButton.extended(
            onPressed: () => _requestDoc(types),
            icon: const Icon(Icons.add),
            label: const Text('Request'),
          );
        },
      ),
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          final merged = tichMergeSnapshot(snap, _dataCache as dynamic, persistKey: 'student.documents');
          if (tichShowLoadingSpinner(snap, merged)) { return studentLoadingBody(onRefresh: () async => _reload()); }
          if (merged == null && snap.hasError) {
            return studentErrorBody(snap.error, _reload);
          }
          final data = merged! as Map<String, dynamic>;
          final docs = data['documents'] as List? ?? const [];
          final requests =
              data['document_requests'] as List? ?? const [];

          return RefreshIndicator(
            onRefresh: () async => _reload(),
            child: ListView(
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 88),
              children: [
                const TichSectionHeader('Uploaded documents'),
                if (docs.isEmpty)
                  const TichCard(child: TichEmpty('No documents on file.'))
                else
                  ...docs.map((raw) {
                    final d = Map<String, dynamic>.from(raw as Map);
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 8),
                      child: TichCard(
                        child: ListTile(
                          contentPadding: EdgeInsets.zero,
                          title: Text(
                            d['document_name']?.toString() ??
                                d['document_type']?.toString() ??
                                'Document',
                            style: tichUi(fontWeight: FontWeight.w600),
                          ),
                          subtitle: Text(
                            [
                              d['document_type'],
                              formatDateTime(d['uploaded_at']),
                            ].where((e) => e != null && '$e'.isNotEmpty).join(' · '),
                          ),
                          trailing: d['status'] != null
                              ? TichStatusChip(
                                  d['status'].toString(),
                                  tone: toneForStatus(d['status']?.toString()),
                                )
                              : null,
                        ),
                      ),
                    );
                  }),
                const SizedBox(height: 16),
                const TichSectionHeader('Document requests'),
                if (requests.isEmpty)
                  const TichCard(child: TichEmpty('No requests yet.'))
                else
                  ...requests.map((raw) {
                    final r = Map<String, dynamic>.from(raw as Map);
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 8),
                      child: TichCard(
                        child: ListTile(
                          contentPadding: EdgeInsets.zero,
                          title: Text(
                            r['document_type_label']?.toString() ??
                                r['document_type']?.toString() ??
                                'Request',
                            style: tichUi(fontWeight: FontWeight.w600),
                          ),
                          subtitle: Text(formatDateTime(r['created_at'])),
                          trailing: TichStatusChip(
                            r['status']?.toString() ?? 'pending',
                            tone: toneForStatus(r['status']?.toString()),
                          ),
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
