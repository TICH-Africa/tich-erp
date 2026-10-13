import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

import '../../data/api_client.dart';
import '../../data/employee_api.dart';
import '../../theme/tich_theme.dart';
import '../shell/portal_shell.dart';
import '../widgets/tich_widgets.dart';

class DocumentsPage extends StatefulWidget {
  const DocumentsPage({super.key, required this.api});

  final EmployeeApi api;

  @override
  State<DocumentsPage> createState() => _DocumentsPageState();
}

class _DocumentsPageState extends State<DocumentsPage> {
  late Future<Map<String, dynamic>> _future;

  @override
  void initState() {
    super.initState();
    _future = widget.api.documents();
  }

  void _reload() => setState(() => _future = widget.api.documents());

  Future<void> _upload(Map<String, dynamic> data) async {
    final types = Map<String, dynamic>.from(data['document_types'] as Map? ?? {});
    String? selected = (data['requested_types'] as List?)?.cast<dynamic>().firstOrNull?.toString()
        ?? types.keys.firstOrNull;
    final nameCtrl = TextEditingController();

    final confirmed = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      builder: (ctx) {
        return Padding(
          padding: EdgeInsets.only(
            left: 16,
            right: 16,
            top: 16,
            bottom: MediaQuery.of(ctx).viewInsets.bottom + 16,
          ),
          child: StatefulBuilder(
            builder: (ctx, setModal) {
              return Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text('Upload document', style: GoogleFonts.merriweather(fontWeight: FontWeight.w700)),
                  const SizedBox(height: 12),
                  DropdownButtonFormField<String>(
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
                  const SizedBox(height: 12),
                  FilledButton(
                    onPressed: () => Navigator.pop(ctx, true),
                    child: const Text('Choose file & upload'),
                  ),
                ],
              );
            },
          ),
        );
      },
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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: tichAppBar(context, title: 'Documents'),
      floatingActionButton: FutureBuilder(
        future: _future,
        builder: (context, snap) {
          if (!snap.hasData) return const SizedBox.shrink();
          return FloatingActionButton.extended(
            backgroundColor: TichColors.green,
            onPressed: () => _upload(snap.data!),
            icon: const Icon(Icons.upload_file),
            label: const Text('Upload'),
          );
        },
      ),
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          if (snap.connectionState != ConnectionState.done) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snap.hasError) {
            return TichErrorRefresh(message: snap.error.toString(), onRetry: _reload);
          }
          final docs = snap.data!['documents'] as List? ?? const [];
          final requested = snap.data!['requested_types'] as List? ?? const [];
          return RefreshIndicator(
            onRefresh: () async => _reload(),
            child: ListView(
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 88),
              children: [
                if (requested.isNotEmpty)
                  TichCard(
                    child: Text(
                      'Requested: ${requested.join(', ')}',
                      style: tichUi(color: TichColors.blueDark),
                    ),
                  ),
                const SizedBox(height: 12),
                if (docs.isEmpty)
                  const TichCard(child: TichEmpty('No documents uploaded yet.'))
                else
                  ...docs.map((raw) {
                    final d = Map<String, dynamic>.from(raw as Map);
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
                  }),
              ],
            ),
          );
        },
      ),
    );
  }
}
