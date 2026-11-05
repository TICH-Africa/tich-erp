import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import '../../data/api_client.dart';
import '../../data/employee_api.dart';
import '../../theme/tich_theme.dart';
import '../../adaptive/adaptive.dart';
import '../widgets/tich_widgets.dart';
import '../widgets/tich_form_dialog.dart';
import '../widgets/tich_live_refresh.dart';
import 'portal_helpers.dart';

class DocumentsPage extends StatefulWidget {
  const DocumentsPage({super.key, required this.api});

  final EmployeeApi api;

  @override
  State<DocumentsPage> createState() => _DocumentsPageState();
}

class _DocumentsPageState extends State<DocumentsPage> {
  late Future<Map<String, dynamic>> _future;
  Object? _dataCache;

  @override
  void initState() {
    super.initState();
    portalPrimeScreen(
      persistKey: 'portal.documents',
      setCache: (c) => _dataCache = c,
      setFuture: (f) => _future = f,
      fetch: () => widget.api.documents(),
    );
  }

  Future<void> _reload() => portalCachedReload(
        mounted: () => mounted,
        setState: setState,
        fetch: () => widget.api.documents(),
        onData: (data) => _dataCache = data,
        persistKey: 'portal.documents',
      );

  void _liveRefresh() {
    tichSilentReload(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.documents(),
      onData: (data) => _dataCache = data,
      persistKey: 'portal.documents',
    );
  }

  Future<void> _upload(Map<String, dynamic> data) async {
    final types = Map<String, dynamic>.from(data['document_types'] as Map? ?? {});
    String? selected = (data['requested_types'] as List?)?.cast<dynamic>().firstOrNull?.toString()
        ?? types.keys.firstOrNull;
    final nameCtrl = TextEditingController();

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setModal) => TichFormDialog(
          title: Text(
            'Upload document',
            style: TichTheme.display(
              fontSize: 16,
              color: Theme.of(ctx).colorScheme.onSurface,
            ),
          ),
          scrollable: true,
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              TichDropdownFormField<String>(
                value: selected,
                items: types.entries
                    .map((e) => DropdownMenuItem(value: e.key, child: Text('${e.value}')))
                    .toList(),
                onChanged: (v) => setModal(() => selected = v),
                decoration: const InputDecoration(labelText: 'Type'),
              ),
              const SizedBox(height: 10),
              TextField(
                controller: nameCtrl,
                decoration: const InputDecoration(labelText: 'Document name'),
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
              child: const Text('Choose file & upload'),
            ),
          ],
        ),
      ),
    );

    if (confirmed != true || selected == null) return;
    final pick = await FilePicker.platform.pickFiles(withData: false);
    if (pick == null || pick.files.isEmpty || pick.files.first.path == null) return;
    final file = pick.files.first;
    try {
      await widget.api.uploadDocument(
        type: selected!,
        name: nameCtrl.text.trim().isEmpty ? file.name : nameCtrl.text.trim(),
        filePath: file.path!,
        fileName: file.name,
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Document uploaded.')),
      );
      _reload();
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    }
  }

  void _uploadFromCache() {
    final cached = _dataCache;
    if (cached is Map<String, dynamic>) {
      _upload(cached);
      return;
    }
    _future.then((data) {
      if (mounted) _upload(data);
    });
  }

  @override
  Widget build(BuildContext context) {
    return TichLiveRefreshBinder(
      onLiveRefresh: _liveRefresh,
      child: AdaptiveScaffold(
      title: 'Documents',
      floatingActionButton: FloatingActionButton.extended(
        backgroundColor: TichColors.green,
        onPressed: _uploadFromCache,
        icon: const Icon(Icons.upload_file),
        label: const Text('Upload'),
      ),
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          final merged = tichMergeSnapshot(snap, _dataCache as dynamic, persistKey: 'portal.documents');
          if (tichShowLoadingSpinner(snap, merged)) { return tichInstantWaitBody(onRefresh: () async => _reload()); }
          if (merged == null && snap.hasError) {
            return TichErrorRefresh(message: snap.error.toString(), onRetry: _reload);
          }
          final data = merged! as Map<String, dynamic>;
          final docs = data['documents'] as List? ?? const [];
          final requested = data['requested_types'] as List? ?? const [];
          final leading = requested.isNotEmpty ? 2 : 0;
          final docSlots = docs.isEmpty ? 1 : docs.length;
          return RefreshIndicator(
            onRefresh: () async => _reload(),
            child: ListView.builder(
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 88),
              itemCount: leading + docSlots,
              itemBuilder: (context, index) {
                if (requested.isNotEmpty) {
                  if (index == 0) {
                    return TichCard(
                      child: Text(
                        'Requested: ${requested.join(', ')}',
                        style: tichUi(color: TichColors.blueDark),
                      ),
                    );
                  }
                  if (index == 1) {
                    return const SizedBox(height: 12);
                  }
                }
                final docIndex = index - leading;
                if (docs.isEmpty) {
                  return const TichCard(
                    child: TichEmpty('No documents uploaded yet.'),
                  );
                }
                final d =
                    Map<String, dynamic>.from(docs[docIndex] as Map);
                return Padding(
                  padding: const EdgeInsets.only(bottom: 8),
                  child: TichCard(
                    child: Row(
                      children: [
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                d['document_name']?.toString() ?? '',
                                style: tichUi(fontWeight: FontWeight.w700),
                              ),
                              Text(
                                '${d['document_type']} · ${d['status'] ?? (d['is_verified'] == true ? 'verified' : 'pending')}',
                                style: tichUi(
                                  fontSize: 12,
                                  color: TichColors.neutralMuted,
                                ),
                              ),
                            ],
                          ),
                        ),
                        TichStatusChip(
                          d['is_verified'] == true ? 'verified' : 'pending',
                          tone: d['is_verified'] == true
                              ? TichChipTone.success
                              : TichChipTone.warning,
                        ),
                      ],
                    ),
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
