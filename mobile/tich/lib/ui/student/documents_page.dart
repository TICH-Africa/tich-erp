import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../data/api_client.dart';
import '../../data/student_api.dart';
import '../../theme/tich_theme.dart';
import '../widgets/date_time_pickers.dart';
import '../widgets/tich_form_dialog.dart';
import '../widgets/tich_generated_document_viewer.dart';
import '../widgets/tich_live_refresh.dart';
import '../widgets/tich_widgets.dart';
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
      persistKey: 'student.documents',
    );
  }

  Map<String, String> _uploadTypes(Map<String, dynamic> data) {
    final raw = data['upload_types'];
    if (raw is Map) {
      return raw.map((k, v) => MapEntry(k.toString(), v.toString()));
    }
    return const {};
  }

  Future<void> _upload(Map<String, dynamic> data) async {
    final types = _uploadTypes(data);
    if (types.isEmpty) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Upload types are not configured.')),
      );
      return;
    }
    String? selected = types.keys.first;
    final notes = TextEditingController();

    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setModal) => TichFormDialog(
          title: const Text('Upload document'),
          scrollable: true,
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text(
                'New types are saved immediately. Replacing a file already on record needs registrar approval.',
                style: tichUi(fontSize: 13, color: TichColors.neutralMuted),
              ),
              const SizedBox(height: 12),
              TichDropdownFormField<String>(
                value: selected,
                items: types.entries
                    .map(
                      (e) => DropdownMenuItem(
                        value: e.key,
                        child: Text(e.value),
                      ),
                    )
                    .toList(),
                onChanged: (v) => setModal(() => selected = v),
                decoration: const InputDecoration(labelText: 'Document type'),
              ),
              const SizedBox(height: 10),
              TextField(
                controller: notes,
                maxLines: 2,
                decoration: const InputDecoration(
                  labelText: 'Notes (optional)',
                ),
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
              child: const Text('Choose file'),
            ),
          ],
        ),
      ),
    );
    if (ok != true || selected == null) return;

    final pick = await FilePicker.platform.pickFiles(withData: false);
    if (pick == null || pick.files.isEmpty || pick.files.first.path == null) {
      return;
    }
    final file = pick.files.first;

    try {
      final res = await widget.api.uploadDocument(
        documentType: selected!,
        filePath: file.path!,
        fileName: file.name,
        notes: notes.text.trim().isEmpty ? null : notes.text.trim(),
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(res['message']?.toString() ?? 'Uploaded')),
      );
      _reload();
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    }
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

  bool _isPdf(Map<String, dynamic> item) {
    final mime = item['mime_type']?.toString().toLowerCase() ?? '';
    final name = item['document_name']?.toString().toLowerCase() ??
        item['original_filename']?.toString().toLowerCase() ??
        '';
    return mime.contains('pdf') || name.endsWith('.pdf');
  }

  Future<void> _viewDocument(Map<String, dynamic> doc) async {
    final id = (doc['id'] as num?)?.toInt();
    if (id == null) return;
    if (!_isPdf(doc)) {
      try {
        await widget.api.downloadDocumentFile(id, download: true);
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Use Download for this file type.')),
        );
      } catch (_) {}
      return;
    }
    final title = doc['document_type_label']?.toString() ??
        doc['document_name']?.toString() ??
        'Document';
    if (!mounted) return;
    await Navigator.of(context).push<void>(
      MaterialPageRoute(
        builder: (_) => AdaptiveScaffold(
          title: title,
          body: TichGeneratedDocumentViewer(
            cacheFileName: 'student-doc-$id.pdf',
            loadPdf: () => widget.api.downloadDocumentFile(id),
          ),
        ),
      ),
    );
  }

  Future<void> _viewPending(Map<String, dynamic> row) async {
    final id = (row['id'] as num?)?.toInt();
    if (id == null) return;
    final title = row['document_type_label']?.toString() ?? 'Pending change';
    if (!mounted) return;
    await Navigator.of(context).push<void>(
      MaterialPageRoute(
        builder: (_) => AdaptiveScaffold(
          title: title,
          body: TichGeneratedDocumentViewer(
            cacheFileName: 'student-doc-pending-$id.pdf',
            emptyMessage: 'Could not load the proposed file.',
            loadPdf: () => widget.api.downloadDocumentSubmissionFile(id),
          ),
        ),
      ),
    );
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
            final fabData = tichMergeSnapshot(
              snap,
              _dataCache as dynamic,
              persistKey: 'student.documents',
            ) as Map<String, dynamic>?;
            if (fabData == null) return const SizedBox.shrink();
            final requestTypes = fabData['request_types'] is Map
                ? Map.from(fabData['request_types'] as Map)
                : <String, String>{};
            return Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                FloatingActionButton.extended(
                  heroTag: 'doc-upload',
                  onPressed: () => _upload(fabData),
                  icon: const Icon(Icons.upload_file),
                  label: const Text('Upload'),
                ),
                const SizedBox(height: 10),
                FloatingActionButton.extended(
                  heroTag: 'doc-request',
                  onPressed: () => _requestDoc(requestTypes),
                  icon: const Icon(Icons.description_outlined),
                  label: const Text('Request'),
                ),
              ],
            );
          },
        ),
        body: FutureBuilder<Map<String, dynamic>>(
          future: _future,
          builder: (context, snap) {
            final merged = tichMergeSnapshot(
              snap,
              _dataCache as dynamic,
              persistKey: 'student.documents',
            );
            if (tichShowLoadingSpinner(snap, merged)) {
              return studentLoadingBody(onRefresh: () async => _reload());
            }
            if (merged == null && snap.hasError) {
              return studentErrorBody(snap.error, _reload);
            }
            final data = merged! as Map<String, dynamic>;
            final docs = data['documents'] as List? ?? const [];
            final pending = data['pending_changes'] as List? ?? const [];
            final requests = data['document_requests'] as List? ?? const [];

            return RefreshIndicator(
              onRefresh: () async => _reload(),
              child: ListView(
                padding: const EdgeInsets.fromLTRB(16, 16, 16, 160),
                children: [
                  const TichSectionHeader('Documents on file'),
                  if (docs.isEmpty)
                    const TichCard(child: TichEmpty('No documents on file yet.'))
                  else
                    ...docs.map((raw) {
                      final d = Map<String, dynamic>.from(raw as Map);
                      final id = (d['id'] as num?)?.toInt();
                      return Padding(
                        padding: const EdgeInsets.only(bottom: 8),
                        child: TichCard(
                          child: ListTile(
                            contentPadding: EdgeInsets.zero,
                            title: Text(
                              d['document_type_label']?.toString() ??
                                  d['document_name']?.toString() ??
                                  d['document_type']?.toString() ??
                                  'Document',
                              style: tichUi(fontWeight: FontWeight.w600),
                            ),
                            subtitle: Text(
                              [
                                d['document_name'],
                                formatDateTime(d['uploaded_at']),
                              ]
                                  .where((e) => e != null && '$e'.isNotEmpty)
                                  .join(' · '),
                            ),
                            trailing: d['is_verified'] == true
                                ? const TichStatusChip('verified', tone: TichChipTone.success)
                                : const TichStatusChip('on file', tone: TichChipTone.neutral),
                            onTap: id != null && d['can_view'] != false
                                ? () => _viewDocument(d)
                                : null,
                          ),
                        ),
                      );
                    }),
                  if (pending.isNotEmpty) ...[
                    const SizedBox(height: 16),
                    const TichSectionHeader('Pending changes (awaiting approval)'),
                    ...pending.map((raw) {
                      final r = Map<String, dynamic>.from(raw as Map);
                      return Padding(
                        padding: const EdgeInsets.only(bottom: 8),
                        child: TichCard(
                          child: ListTile(
                            contentPadding: EdgeInsets.zero,
                            title: Text(
                              r['document_type_label']?.toString() ??
                                  r['document_type']?.toString() ??
                                  'Change request',
                              style: tichUi(fontWeight: FontWeight.w600),
                            ),
                            subtitle: Text(
                              r['original_filename']?.toString() ?? '',
                            ),
                            trailing: const TichStatusChip(
                              'pending',
                              tone: TichChipTone.warning,
                            ),
                            onTap: () => _viewPending(r),
                          ),
                        ),
                      );
                    }),
                  ],
                  const SizedBox(height: 16),
                  const TichSectionHeader('Document requests (from registrar)'),
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
