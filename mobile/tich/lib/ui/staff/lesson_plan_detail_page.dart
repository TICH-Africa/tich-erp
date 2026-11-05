import 'dart:io';
import 'package:flutter/material.dart';
import 'package:open_file/open_file.dart';
import 'package:path_provider/path_provider.dart';

import '../../adaptive/adaptive.dart';
import '../../data/api_client.dart';
import '../../data/staff_api.dart';
import '../../theme/tich_theme.dart';
import '../widgets/date_time_pickers.dart';
import '../widgets/tich_generated_document_viewer.dart';
import '../widgets/tich_widgets.dart';
import 'staff_helpers.dart';

/// Preview generated PDF (form plans), verify, then submit — matches staff web portal.
class StaffLessonPlanDetailPage extends StatefulWidget {
  const StaffLessonPlanDetailPage({
    super.key,
    required this.api,
    required this.planId,
    this.initialPlan,
  });

  final StaffApi api;
  final int planId;
  final Map<String, dynamic>? initialPlan;

  @override
  State<StaffLessonPlanDetailPage> createState() =>
      _StaffLessonPlanDetailPageState();
}

class _StaffLessonPlanDetailPageState extends State<StaffLessonPlanDetailPage> {
  Map<String, dynamic>? _plan;
  bool _busy = false;

  bool get _isForm => (_plan?['source_type'] ?? 'form') == 'form';
  bool get _isVerified => _plan?['is_verified'] == true;
  bool get _canSubmit => _plan?['can_submit'] == true;
  String get _status => _plan?['status']?.toString() ?? 'draft';
  bool get _editable =>
      const {'draft', 'modified', 'rejected'}.contains(_status);

  @override
  void initState() {
    super.initState();
    _plan = widget.initialPlan;
    _refresh();
  }

  Future<void> _refresh() async {
    try {
      final data = await widget.api.lessonPlan(widget.planId);
      if (!mounted) return;
      setState(() {
        _plan = Map<String, dynamic>.from(data['plan'] as Map? ?? {});
      });
    } on ApiException catch (e) {
      if (!mounted) return;
      _snack(e.message);
    }
  }

  void _snack(String message) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message)));
  }

  Future<void> _verify() async {
    setState(() => _busy = true);
    try {
      final data = await widget.api.verifyLessonPlan(widget.planId);
      if (!mounted) return;
      setState(() {
        _plan = Map<String, dynamic>.from(data['plan'] as Map? ?? _plan ?? {});
      });
      _snack(data['message']?.toString() ?? 'Verified.');
    } on ApiException catch (e) {
      if (!mounted) return;
      _snack(e.message);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _submit() async {
    setState(() => _busy = true);
    try {
      final data = await widget.api.submitLessonPlan(widget.planId);
      if (!mounted) return;
      setState(() {
        _plan = Map<String, dynamic>.from(data['plan'] as Map? ?? _plan ?? {});
      });
      _snack(data['message']?.toString() ?? 'Submitted.');
      Navigator.of(context).pop(true);
    } on ApiException catch (e) {
      if (!mounted) return;
      _snack(e.message);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _openUploadedExternally() async {
    setState(() => _busy = true);
    try {
      final bytes = await widget.api.downloadLessonPlanUpload(widget.planId);
      if (!mounted || bytes.isEmpty) return;
      final name = (_plan?['uploaded_file_name'] ?? 'lesson-plan').toString();
      final dir = await getTemporaryDirectory();
      final file = File('${dir.path}/$name');
      await file.writeAsBytes(bytes, flush: true);
      await OpenFile.open(file.path);
    } on ApiException catch (e) {
      if (!mounted) return;
      _snack(e.message);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Widget _previewPane() {
    if (_isForm) {
      return TichGeneratedDocumentViewer(
        cacheFileName: 'lesson-plan-${widget.planId}.pdf',
        loadPdf: () => widget.api.downloadLessonPlanPdf(widget.planId),
        emptyMessage: 'Could not generate the lesson plan preview.',
      );
    }

    final fileName =
        (_plan?['uploaded_file_name'] ?? '').toString().toLowerCase();
    if (fileName.endsWith('.pdf')) {
      return TichGeneratedDocumentViewer(
        cacheFileName: 'lesson-plan-upload-${widget.planId}.pdf',
        loadPdf: () => widget.api.downloadLessonPlanUpload(widget.planId),
        emptyMessage: 'Uploaded document not available.',
      );
    }

    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.description_outlined, size: 48),
            const SizedBox(height: 12),
            Text(
              _plan?['uploaded_file_name']?.toString() ?? 'Uploaded document',
              textAlign: TextAlign.center,
              style: tichUi(fontWeight: FontWeight.w600),
            ),
            const SizedBox(height: 16),
            FilledButton.icon(
              onPressed: _busy ? null : _openUploadedExternally,
              icon: const Icon(Icons.open_in_new),
              label: const Text('Open document'),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final plan = _plan;
    final title = plan?['lesson_title']?.toString().isNotEmpty == true
        ? plan!['lesson_title'].toString()
        : plan?['plan_number']?.toString() ?? 'Lesson plan';

    return AdaptiveScaffold(
      title: title,
      body: plan == null
          ? staffLoadingBody(onRefresh: () async => _refresh())
          : Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Padding(
                  padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
                  child: TichCard(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(title, style: TichTheme.display(fontSize: 16)),
                        const SizedBox(height: 4),
                        Text(
                          [
                            plan['unit_code'],
                            plan['unit_name'],
                          ].where((e) => e != null && '$e'.isNotEmpty).join(' · '),
                          style: tichUi(fontSize: 13),
                        ),
                        Text(
                          formatDate(plan['planned_date']),
                          style: tichUi(
                            fontSize: 12,
                            color: TichColors.neutralMuted,
                          ),
                        ),
                        const SizedBox(height: 8),
                        Wrap(
                          spacing: 8,
                          runSpacing: 4,
                          children: [
                            TichStatusChip(_status),
                            TichStatusChip(
                              _isForm ? 'Online form' : 'Upload',
                              tone: TichChipTone.info,
                            ),
                            if (_isForm && !_isVerified && _editable)
                              const TichStatusChip(
                                'Not verified',
                                tone: TichChipTone.warning,
                              ),
                          ],
                        ),
                        if ((plan['hod_comments'] ?? '').toString().isNotEmpty) ...[
                          const SizedBox(height: 8),
                          Text(
                            'HOD: ${plan['hod_comments']}',
                            style: tichUi(fontSize: 12),
                          ),
                        ],
                      ],
                    ),
                  ),
                ),
                if (_isForm && _editable)
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                    child: Text(
                      'Preview the generated lesson plan and verify it is accurate before submitting to HOD, Academic Registrar, and QA.',
                      style: tichUi(
                        fontSize: 12,
                        color: TichColors.neutralMuted,
                      ),
                    ),
                  ),
                Expanded(child: _previewPane()),
                if (_editable) ...[
                  Padding(
                    padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        if (_isForm && !_isVerified)
                          FilledButton(
                            onPressed: _busy ? null : _verify,
                            child: _busy
                                ? const SizedBox(
                                    height: 22,
                                    width: 22,
                                    child: CircularProgressIndicator(
                                      strokeWidth: 2,
                                    ),
                                  )
                                : const Text(
                                    'I verify this document is accurate',
                                  ),
                          ),
                        if (_isForm && _isVerified)
                          Text(
                            'Verified — you can submit for approval.',
                            textAlign: TextAlign.center,
                            style: tichUi(
                              fontSize: 12,
                              color: TichColors.greenDark,
                            ),
                          ),
                        if (!_isForm && !_canSubmit)
                          Text(
                            'Upload a lesson plan document before submitting.',
                            textAlign: TextAlign.center,
                            style: tichUi(fontSize: 12),
                          ),
                        const SizedBox(height: 8),
                        FilledButton(
                          onPressed: (_busy || !_canSubmit) ? null : _submit,
                          child: const Text('Submit for approval'),
                        ),
                      ],
                    ),
                  ),
                ],
              ],
            ),
    );
  }
}
