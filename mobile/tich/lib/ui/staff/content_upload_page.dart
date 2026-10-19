import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../data/api_client.dart';
import '../../data/staff_api.dart';
import '../widgets/tich_widgets.dart';

class StaffContentUploadPage extends StatefulWidget {
  const StaffContentUploadPage({
    super.key,
    required this.api,
    required this.allocations,
  });

  final StaffApi api;
  final List<Map<String, dynamic>> allocations;

  @override
  State<StaffContentUploadPage> createState() => _StaffContentUploadPageState();
}

class _StaffContentUploadPageState extends State<StaffContentUploadPage> {
  final _title = TextEditingController();
  final _notes = TextEditingController();
  int? _unitId;
  String? _pickedPath;
  String? _pickedName;
  bool _busy = false;

  List<Map<String, dynamic>> get _unitOptions {
    final seen = <int>{};
    final out = <Map<String, dynamic>>[];
    for (final raw in widget.allocations) {
      final id = (raw['unit_id'] as num?)?.toInt();
      if (id == null || !seen.add(id)) continue;
      out.add(raw);
    }
    return out;
  }

  @override
  void initState() {
    super.initState();
    final options = _unitOptions;
    if (options.isNotEmpty) {
      _unitId = (options.first['unit_id'] as num?)?.toInt();
    }
  }

  @override
  void dispose() {
    _title.dispose();
    _notes.dispose();
    super.dispose();
  }

  Future<void> _pickFile() async {
    final pick = await FilePicker.platform.pickFiles(withData: false);
    if (pick == null || pick.files.isEmpty) return;
    final file = pick.files.first;
    if (file.path == null) return;
    setState(() {
      _pickedPath = file.path;
      _pickedName = file.name;
    });
  }

  Future<void> _submit() async {
    if (_unitId == null) {
      _snack('Choose a unit.');
      return;
    }
    if (_title.text.trim().isEmpty) {
      _snack('Enter a title.');
      return;
    }
    setState(() => _busy = true);
    try {
      await widget.api.uploadContent(
        unitId: _unitId!,
        title: _title.text.trim(),
        contentText: _notes.text.trim(),
        filePath: _pickedPath,
        fileName: _pickedName,
      );
      if (!mounted) return;
      _snack('Material posted to students.');
      Navigator.of(context).pop(true);
    } on ApiException catch (e) {
      if (!mounted) return;
      _snack(e.message);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  void _snack(String message) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message)));
  }

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'Upload material',
      body: widget.allocations.isEmpty
          ? const Center(child: TichEmpty('No units assigned.'))
          : SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  TichDropdownFormField<int>(
                    value: _unitId,
                    decoration: const InputDecoration(labelText: 'Unit'),
                    items: [
                      for (final raw in _unitOptions)
                        DropdownMenuItem(
                          value: (raw['unit_id'] as num).toInt(),
                          child: Text(
                            raw['label']?.toString() ?? 'Unit',
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                    ],
                    onChanged: _busy
                        ? null
                        : (v) => setState(() => _unitId = v),
                  ),
                  const SizedBox(height: 12),
                  TextField(
                    controller: _title,
                    decoration: const InputDecoration(labelText: 'Title'),
                    textInputAction: TextInputAction.next,
                  ),
                  const SizedBox(height: 12),
                  TextField(
                    controller: _notes,
                    decoration: const InputDecoration(
                      labelText: 'Short notes / description',
                      hintText: 'Brief notes for students…',
                      alignLabelWithHint: true,
                    ),
                    minLines: 3,
                    maxLines: 6,
                  ),
                  const SizedBox(height: 12),
                  OutlinedButton.icon(
                    onPressed: _busy ? null : _pickFile,
                    icon: const Icon(Icons.attach_file),
                    label: Text(
                      _pickedName ?? 'Attachment (optional)',
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                  if (_pickedName != null) ...[
                    const SizedBox(height: 6),
                    Align(
                      alignment: Alignment.centerLeft,
                      child: TextButton(
                        onPressed: _busy
                            ? null
                            : () => setState(() {
                                  _pickedPath = null;
                                  _pickedName = null;
                                }),
                        child: const Text('Remove attachment'),
                      ),
                    ),
                  ],
                  const SizedBox(height: 20),
                  FilledButton(
                    onPressed: _busy ? null : _submit,
                    child: _busy
                        ? const SizedBox(
                            height: 22,
                            width: 22,
                            child: AdaptiveProgress(),
                          )
                        : const Text('Post to students'),
                  ),
                ],
              ),
            ),
    );
  }
}
