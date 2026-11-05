import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../data/api_client.dart';
import '../../data/staff_api.dart';
import '../../theme/tich_theme.dart';
import '../widgets/tich_widgets.dart';
import 'lesson_plan_detail_page.dart';

class StaffLessonPlanUploadPage extends StatefulWidget {
  const StaffLessonPlanUploadPage({
    super.key,
    required this.api,
    required this.allocations,
  });

  final StaffApi api;
  final List<Map<String, dynamic>> allocations;

  @override
  State<StaffLessonPlanUploadPage> createState() =>
      _StaffLessonPlanUploadPageState();
}

class _StaffLessonPlanUploadPageState extends State<StaffLessonPlanUploadPage> {
  int? _allocationId;
  final _title = TextEditingController();
  DateTime _plannedDate = DateTime.now();
  final _weekNumber = TextEditingController(text: '1');
  final _contactHours = TextEditingController(text: '2');
  String? _filePath;
  String? _fileName;
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    if (widget.allocations.isNotEmpty) {
      _allocationId = (widget.allocations.first['id'] as num?)?.toInt();
    }
  }

  @override
  void dispose() {
    _title.dispose();
    _weekNumber.dispose();
    _contactHours.dispose();
    super.dispose();
  }

  String _dateApi(DateTime d) =>
      '${d.year.toString().padLeft(4, '0')}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';

  Future<void> _pickFile() async {
    final pick = await FilePicker.platform.pickFiles(
      type: FileType.custom,
      allowedExtensions: const ['pdf', 'doc', 'docx'],
    );
    if (pick == null || pick.files.isEmpty) return;
    final file = pick.files.first;
    if (file.path == null) return;
    setState(() {
      _filePath = file.path;
      _fileName = file.name;
    });
  }

  void _snack(String message) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message)));
  }

  Future<void> _save({required bool submit}) async {
    if (_allocationId == null) {
      _snack('Choose a unit.');
      return;
    }
    if (_title.text.trim().isEmpty) {
      _snack('Enter the lesson topic.');
      return;
    }
    if (_filePath == null || _fileName == null) {
      _snack('Choose a PDF or Word document.');
      return;
    }
    setState(() => _busy = true);
    try {
      final res = await widget.api.createLessonPlanUpload(
        allocationId: _allocationId!,
        lessonTitle: _title.text.trim(),
        plannedDate: _dateApi(_plannedDate),
        weekNumber: int.tryParse(_weekNumber.text.trim()) ?? 1,
        contactHours: int.tryParse(_contactHours.text.trim()) ?? 2,
        filePath: _filePath!,
        fileName: _fileName!,
        submit: submit,
      );
      if (!mounted) return;
      final plan = Map<String, dynamic>.from(res['plan'] as Map? ?? {});
      final id = (plan['id'] as num?)?.toInt();
      if (submit) {
        _snack(res['message']?.toString() ?? 'Submitted.');
        Navigator.of(context).pop(true);
        return;
      }
      if (id == null) {
        Navigator.of(context).pop(true);
        return;
      }
      await Navigator.of(context).pushReplacement(
        MaterialPageRoute(
          builder: (_) => StaffLessonPlanDetailPage(
            api: widget.api,
            planId: id,
            initialPlan: plan,
          ),
        ),
      );
    } on ApiException catch (e) {
      if (!mounted) return;
      _snack(e.message);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'Upload lesson plan',
      body: widget.allocations.isEmpty
          ? const Center(child: TichEmpty('No units assigned.'))
          : SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Text(
                    'Upload a completed Word or PDF lesson plan. You can submit immediately after upload.',
                    style: tichUi(fontSize: 13, color: TichColors.neutralMuted),
                  ),
                  const SizedBox(height: 16),
                  TichDropdownFormField<int>(
                    value: _allocationId,
                    decoration: const InputDecoration(labelText: 'Unit'),
                    items: [
                      for (final raw in widget.allocations)
                        DropdownMenuItem(
                          value: (raw['id'] as num).toInt(),
                          child: Text(
                            raw['label']?.toString() ??
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
                    onChanged: (v) => setState(() => _allocationId = v),
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _title,
                    decoration: const InputDecoration(labelText: 'Lesson topic'),
                  ),
                  const SizedBox(height: 12),
                  ListTile(
                    contentPadding: EdgeInsets.zero,
                    title: const Text('Planned date'),
                    subtitle: Text(_dateApi(_plannedDate)),
                    trailing: const Icon(Icons.calendar_today_outlined),
                    onTap: _busy
                        ? null
                        : () async {
                            final picked = await showDatePicker(
                              context: context,
                              initialDate: _plannedDate,
                              firstDate: DateTime(2020),
                              lastDate: DateTime(2035),
                            );
                            if (picked != null) {
                              setState(() => _plannedDate = picked);
                            }
                          },
                  ),
                  const SizedBox(height: 8),
                  TextFormField(
                    controller: _weekNumber,
                    keyboardType: TextInputType.number,
                    decoration: const InputDecoration(labelText: 'Week number'),
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _contactHours,
                    keyboardType: TextInputType.number,
                    decoration: const InputDecoration(labelText: 'Contact hours'),
                  ),
                  const SizedBox(height: 16),
                  OutlinedButton.icon(
                    onPressed: _busy ? null : _pickFile,
                    icon: const Icon(Icons.upload_file),
                    label: Text(
                      _fileName ?? 'Choose PDF or Word document',
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    'PDF or Word (.pdf, .doc, .docx), up to 10 MB.',
                    style: tichUi(fontSize: 12, color: TichColors.neutralMuted),
                  ),
                  const SizedBox(height: 24),
                  FilledButton(
                    onPressed: _busy ? null : () => _save(submit: false),
                    child: _busy
                        ? const SizedBox(
                            height: 22,
                            width: 22,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : const Text('Save draft'),
                  ),
                  const SizedBox(height: 10),
                  FilledButton.tonal(
                    onPressed: _busy ? null : () => _save(submit: true),
                    child: const Text('Save & submit'),
                  ),
                ],
              ),
            ),
    );
  }
}
