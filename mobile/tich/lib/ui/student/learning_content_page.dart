import 'dart:io';

import 'package:flutter/material.dart';
import 'package:open_file/open_file.dart';
import 'package:path_provider/path_provider.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../adaptive/adaptive.dart';
import '../../data/api_client.dart';
import '../../data/student_api.dart';
import '../../theme/tich_theme.dart';
import '../widgets/tich_generated_document_viewer.dart';
import '../widgets/tich_widgets.dart';
import 'student_helpers.dart';

class StudentLearningContentPage extends StatefulWidget {
  const StudentLearningContentPage({
    super.key,
    required this.api,
    required this.contentId,
    this.preview,
  });

  final StudentApi api;
  final int contentId;
  final Map<String, dynamic>? preview;

  @override
  State<StudentLearningContentPage> createState() =>
      _StudentLearningContentPageState();
}

class _StudentLearningContentPageState extends State<StudentLearningContentPage> {
  Map<String, dynamic>? _item;
  bool _loading = false;
  bool _downloading = false;
  Object? _error;

  @override
  void initState() {
    super.initState();
    _item = widget.preview;
    if (_item == null || _item!['content_text'] == null && _item!['has_file'] != true) {
      _refreshDetail();
    }
  }

  Future<void> _refreshDetail() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final data = await widget.api.learningContent(widget.contentId);
      if (!mounted) return;
      setState(() {
        _item = Map<String, dynamic>.from(data['content'] as Map? ?? {});
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e;
        _loading = false;
      });
    }
  }

  bool _isPdf(Map<String, dynamic> item) {
    final mime = item['mime_type']?.toString().toLowerCase() ?? '';
    final name = item['file_name']?.toString().toLowerCase() ?? '';
    return mime.contains('pdf') || name.endsWith('.pdf');
  }

  Future<void> _downloadFile(Map<String, dynamic> item) async {
    setState(() => _downloading = true);
    try {
      final bytes = await widget.api.downloadLearningContentFile(
        widget.contentId,
        download: true,
      );
      final dir = await getTemporaryDirectory();
      final rawName = item['file_name']?.toString().trim();
      final safeName = (rawName != null && rawName.isNotEmpty)
          ? rawName.replaceAll(RegExp(r'[^\w.\-()]+'), '_')
          : 'learning-${widget.contentId}';
      final file = File('${dir.path}/$safeName');
      await file.writeAsBytes(bytes, flush: true);
      final result = await OpenFile.open(file.path);
      if (!mounted) return;
      if (result.type != ResultType.done) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              result.message.isNotEmpty
                  ? result.message
                  : 'File saved. Open it from your files app if needed.',
            ),
          ),
        );
      }
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(tichDescribeError(e))),
      );
    } finally {
      if (mounted) setState(() => _downloading = false);
    }
  }

  Future<void> _viewInApp(Map<String, dynamic> item) async {
    await Navigator.of(context).push<void>(
      MaterialPageRoute(
        builder: (_) => TichGeneratedDocumentViewer(
          cacheFileName: 'student-learning-${widget.contentId}.pdf',
          loadPdf: () => widget.api.downloadLearningContentFile(widget.contentId),
        ),
      ),
    );
  }

  Future<void> _openExternal(String url) async {
    final uri = Uri.tryParse(url);
    if (uri == null) return;
    if (!await launchUrl(uri, mode: LaunchMode.externalApplication)) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Could not open link.')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final item = _item;
    final title = item?['title']?.toString() ?? 'Learning content';

    return AdaptiveScaffold(
      title: title,
      body: _loading && item == null
          ? studentLoadingBody(onRefresh: () async => _refreshDetail())
          : _error != null && item == null
              ? studentErrorBody(_error, () => _refreshDetail())
              : item == null
                  ? const Center(child: TichEmpty('Content unavailable.'))
                  : ListView(
                      padding: const EdgeInsets.all(16),
                      children: [
                        TichCard(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                title,
                                style: tichUi(fontWeight: FontWeight.w700, fontSize: 16),
                              ),
                              if ((item['unit_code'] ?? item['unit_name']) != null) ...[
                                const SizedBox(height: 6),
                                Text(
                                  [
                                    item['unit_code'],
                                    item['unit_name'],
                                  ].where((e) => e != null && '$e'.isNotEmpty).join(' · '),
                                  style: tichUi(
                                    fontSize: 13,
                                    color: TichColors.neutralMuted,
                                  ),
                                ),
                              ],
                            ],
                          ),
                        ),
                        if ((item['content_text']?.toString() ?? '').trim().isNotEmpty) ...[
                          const SizedBox(height: 12),
                          TichCard(
                            child: Text(
                              item['content_text'].toString(),
                              style: tichUi(fontSize: 14, height: 1.5),
                            ),
                          ),
                        ],
                        if (item['has_file'] == true) ...[
                          const SizedBox(height: 12),
                          if (_isPdf(item))
                            AdaptiveFilledButton(
                              onPressed: _downloading ? null : () => _viewInApp(item),
                              child: const Text('View attachment'),
                            ),
                          const SizedBox(height: 8),
                          OutlinedButton.icon(
                            onPressed: _downloading
                                ? null
                                : () => _downloadFile(item),
                            icon: _downloading
                                ? const SizedBox(
                                    width: 18,
                                    height: 18,
                                    child: CircularProgressIndicator(strokeWidth: 2),
                                  )
                                : const Icon(Icons.download_outlined),
                            label: Text(
                              item['file_name']?.toString().trim().isNotEmpty == true
                                  ? 'Download ${item['file_name']}'
                                  : 'Download attachment',
                            ),
                          ),
                        ],
                        if ((item['external_url']?.toString() ?? '').trim().isNotEmpty) ...[
                          const SizedBox(height: 8),
                          OutlinedButton.icon(
                            onPressed: () => _openExternal(item['external_url'].toString()),
                            icon: const Icon(Icons.open_in_new),
                            label: const Text('Open link'),
                          ),
                        ],
                        if (item['has_file'] != true &&
                            (item['content_text']?.toString() ?? '').trim().isEmpty &&
                            (item['external_url']?.toString() ?? '').trim().isEmpty)
                          const Padding(
                            padding: EdgeInsets.only(top: 12),
                            child: TichCard(
                              child: TichEmpty('No file or notes attached to this post.'),
                            ),
                          ),
                      ],
                    ),
    );
  }
}

void openStudentLearningContent(
  BuildContext context,
  StudentApi api,
  Map<String, dynamic> item,
) {
  final id = (item['id'] as num?)?.toInt();
  if (id == null) return;
  Navigator.of(context).push(
    MaterialPageRoute(
      builder: (_) => StudentLearningContentPage(
        api: api,
        contentId: id,
        preview: item,
      ),
    ),
  );
}
