import 'dart:io';
import 'dart:typed_data';

import 'package:flutter/material.dart';
import 'package:open_file/open_file.dart';
import 'package:path_provider/path_provider.dart';

import '../../adaptive/adaptive.dart';
import '../../data/tich_pdf_cache.dart';
import '../../theme/tich_theme.dart';
import 'tich_generated_document_viewer.dart';
import 'tich_widgets.dart';

class TichTimetableDocumentRef {
  const TichTimetableDocumentRef({
    required this.id,
    required this.kind,
    required this.title,
    this.mimeType,
    this.sourceName,
    this.isUploadDocument = false,
    this.isCurrent = false,
  });

  final int id;
  final String kind;
  final String title;
  final String? mimeType;
  final String? sourceName;
  final bool isUploadDocument;
  final bool isCurrent;

  bool get isPdf {
    final mime = mimeType?.toLowerCase() ?? '';
    final name = (sourceName ?? title).toLowerCase();
    return mime.contains('pdf') || name.endsWith('.pdf') || !isUploadDocument;
  }

  bool get isImage {
    final mime = mimeType?.toLowerCase() ?? '';
    final name = (sourceName ?? title).toLowerCase();
    return mime.startsWith('image/') ||
        name.endsWith('.png') ||
        name.endsWith('.jpg') ||
        name.endsWith('.jpeg') ||
        name.endsWith('.webp') ||
        name.endsWith('.gif');
  }

  String get cacheFileName {
    final base = 'timetable-$kind-$id';
    if (isUploadDocument && sourceName != null && sourceName!.contains('.')) {
      final ext = sourceName!.split('.').last.toLowerCase();
      if (ext.isNotEmpty && ext.length <= 5) {
        return '$base.$ext';
      }
    }
    return '$base.pdf';
  }

  static List<TichTimetableDocumentRef> parseDocuments(
    List<dynamic>? raw, {
    List<dynamic>? timetablesFallback,
  }) {
    final source = (raw != null && raw.isNotEmpty)
        ? raw
        : (timetablesFallback ?? const []);
    if (source.isEmpty) return const [];

    return source
        .map((e) => Map<String, dynamic>.from(e as Map))
        .where((m) => (m['id'] as num?) != null)
        .where((m) => m['has_document'] != false)
        .map(
          (m) => TichTimetableDocumentRef(
            id: (m['id'] as num).toInt(),
            kind: m['kind']?.toString() ?? 'lesson',
            title: m['display_title']?.toString() ??
                m['title']?.toString() ??
                'Timetable',
            mimeType: m['mime_type']?.toString(),
            sourceName: m['source_name']?.toString(),
            isUploadDocument: m['is_upload_document'] == true,
            isCurrent: m['is_current'] == true,
          ),
        )
        .toList();
  }

  static TichTimetableDocumentRef? forKind(
    List<TichTimetableDocumentRef> docs,
    String kind,
  ) {
    if (kind == 'exam') {
      for (final k in ['exam', 'supplementary', 'special_exam']) {
        final picked = _pickBest(docs.where((d) => d.kind == k));
        if (picked != null) return picked;
      }
      return null;
    }
    return _pickBest(docs.where((d) => d.kind == kind));
  }

  static TichTimetableDocumentRef? _pickBest(
    Iterable<TichTimetableDocumentRef> docs,
  ) {
    final list = docs.toList();
    if (list.isEmpty) return null;
    list.sort((a, b) {
      if (a.isCurrent != b.isCurrent) {
        return a.isCurrent ? -1 : 1;
      }
      return 0;
    });
    return list.first;
  }
}

typedef TimetablePdfLoader = Future<List<int>> Function(int timetableId);

class TichTimetableDocumentPane extends StatefulWidget {
  const TichTimetableDocumentPane({
    super.key,
    required this.document,
    required this.loadPdf,
    this.provisional = false,
    this.unavailableMessage,
    this.active = true,
  });

  final TichTimetableDocumentRef? document;
  final TimetablePdfLoader loadPdf;
  final bool provisional;
  final String? unavailableMessage;
  final bool active;

  @override
  State<TichTimetableDocumentPane> createState() =>
      _TichTimetableDocumentPaneState();
}

class _TichTimetableDocumentPaneState extends State<TichTimetableDocumentPane> {
  bool _started = false;

  @override
  void didUpdateWidget(covariant TichTimetableDocumentPane oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (widget.active && !_started) {
      setState(() => _started = true);
    }
  }

  @override
  void initState() {
    super.initState();
    _started = widget.active;
  }

  @override
  Widget build(BuildContext context) {
    if (widget.document == null) {
      return Center(
        child: TichEmpty(
          widget.unavailableMessage ??
              'No published timetable document for this tab.',
        ),
      );
    }
    if (widget.provisional) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Text(
            'This timetable is still provisional. The official document will appear once it is published.',
            textAlign: TextAlign.center,
            style: tichUi(color: TichColors.neutralMuted),
          ),
        ),
      );
    }
    if (!_started) {
      return const Center(child: AdaptiveProgress());
    }

    final doc = widget.document!;
    if (doc.isPdf) {
      return TichGeneratedDocumentViewer(
        key: ValueKey('pdf-${doc.id}'),
        cacheFileName: doc.cacheFileName,
        emptyMessage: 'Could not load the timetable document.',
        loadPdf: () => widget.loadPdf(doc.id),
      );
    }

    return _UploadedTimetablePane(
      key: ValueKey('upload-${doc.id}'),
      document: doc,
      loadBytes: () => widget.loadPdf(doc.id),
    );
  }
}

class _UploadedTimetablePane extends StatefulWidget {
  const _UploadedTimetablePane({
    super.key,
    required this.document,
    required this.loadBytes,
  });

  final TichTimetableDocumentRef document;
  final Future<List<int>> Function() loadBytes;

  @override
  State<_UploadedTimetablePane> createState() => _UploadedTimetablePaneState();
}

class _UploadedTimetablePaneState extends State<_UploadedTimetablePane> {
  List<int>? _bytes;
  Object? _error;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _fetch();
  }

  Future<void> _fetch() async {
    setState(() {
      _loading = true;
      _error = null;
      _bytes = null;
    });
    try {
      final bytes = await TichPdfCache.load(
        widget.document.cacheFileName,
        widget.loadBytes,
      );
      if (!mounted) return;
      if (bytes.isEmpty) {
        setState(() {
          _loading = false;
          _error = StateError('empty');
        });
        return;
      }
      setState(() {
        _bytes = bytes;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _error = e;
      });
    }
  }

  Future<void> _openExternally() async {
    final bytes = _bytes;
    if (bytes == null || bytes.isEmpty) return;
    final dir = await getTemporaryDirectory();
    final file = File('${dir.path}/${widget.document.cacheFileName}');
    await file.writeAsBytes(bytes, flush: true);
    await OpenFile.open(file.path);
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return const Center(child: AdaptiveProgress());
    }
    if (_error != null || _bytes == null) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              'Could not load the uploaded timetable.',
              textAlign: TextAlign.center,
              style: tichUi(color: TichColors.neutralMuted),
            ),
            const SizedBox(height: 16),
            AdaptiveFilledButton(
              onPressed: _fetch,
              child: const Text('Try again'),
            ),
          ],
        ),
      );
    }

    final doc = widget.document;
    final label = doc.sourceName ?? doc.title;

    if (doc.isImage) {
      return Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(12, 8, 12, 4),
            child: Text(
              label,
              style: tichUi(fontSize: 13, color: TichColors.neutralMuted),
            ),
          ),
          Expanded(
            child: InteractiveViewer(
              minScale: 0.5,
              maxScale: 4,
              child: Center(
                child: Image.memory(
                  Uint8List.fromList(_bytes!),
                  fit: BoxFit.contain,
                ),
              ),
            ),
          ),
          Padding(
            padding: const EdgeInsets.all(12),
            child: AdaptiveFilledButton(
              onPressed: _openExternally,
              child: const Text('Open in another app'),
            ),
          ),
        ],
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
              label,
              textAlign: TextAlign.center,
              style: tichUi(fontWeight: FontWeight.w600),
            ),
            const SizedBox(height: 8),
            Text(
              'This timetable was uploaded as a document. Open it with an app on your device.',
              textAlign: TextAlign.center,
              style: tichUi(color: TichColors.neutralMuted, fontSize: 13),
            ),
            const SizedBox(height: 20),
            AdaptiveFilledButton(
              onPressed: _openExternally,
              child: const Text('Open document'),
            ),
            const SizedBox(height: 8),
            TextButton(onPressed: _fetch, child: const Text('Refresh')),
          ],
        ),
      ),
    );
  }
}
