import 'dart:io';
import 'dart:typed_data';

import 'package:flutter/material.dart';
import 'package:open_file/open_file.dart';
import 'package:path_provider/path_provider.dart';
import 'package:pdfx/pdfx.dart';

import '../../adaptive/adaptive.dart';
import '../../data/tich_pdf_cache.dart';
import '../../theme/tich_theme.dart';

/// CamScanner-style viewer for server-generated PDFs (pinch-zoom on a paper sheet).
class TichGeneratedDocumentViewer extends StatefulWidget {
  const TichGeneratedDocumentViewer({
    super.key,
    required this.loadPdf,
    this.cacheFileName,
    this.emptyMessage = 'No document available yet.',
  });

  final Future<List<int>> Function() loadPdf;
  final String? cacheFileName;
  final String emptyMessage;

  @override
  State<TichGeneratedDocumentViewer> createState() =>
      _TichGeneratedDocumentViewerState();
}

class _TichGeneratedDocumentViewerState
    extends State<TichGeneratedDocumentViewer> {
  PdfControllerPinch? _controller;
  Object? _error;
  bool _loading = true;
  List<int>? _bytes;

  @override
  void initState() {
    super.initState();
    _fetch();
  }

  @override
  void dispose() {
    _controller?.dispose();
    super.dispose();
  }

  Future<void> _fetch() async {
    setState(() {
      _loading = true;
      _error = null;
      _controller?.dispose();
      _controller = null;
      _bytes = null;
    });
    try {
      final cacheKey = widget.cacheFileName ?? 'tich-document';
      final bytes = await TichPdfCache.load(cacheKey, widget.loadPdf);
      if (!mounted) return;
      if (bytes.isEmpty) {
        setState(() {
          _loading = false;
          _error = StateError('empty');
        });
        return;
      }
      _bytes = bytes;
      _controller = PdfControllerPinch(
        document: PdfDocument.openData(Uint8List.fromList(bytes)),
      );
      setState(() => _loading = false);
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
    final name = widget.cacheFileName ?? 'document-${DateTime.now().millisecondsSinceEpoch}.pdf';
    final file = File('${dir.path}/$name');
    await file.writeAsBytes(bytes, flush: true);
    await OpenFile.open(file.path);
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final desk = isDark ? const Color(0xFF2A2D32) : const Color(0xFFADB5BD);

    if (_loading) {
      return ColoredBox(
        color: desk,
        child: const Center(child: AdaptiveProgress()),
      );
    }

    if (_error != null || _controller == null) {
      return ColoredBox(
        color: desk,
        child: Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(
                  widget.emptyMessage,
                  textAlign: TextAlign.center,
                  style: tichUi(color: TichColors.white),
                ),
                const SizedBox(height: 16),
                AdaptiveFilledButton(
                  onPressed: _fetch,
                  child: const Text('Try again'),
                ),
              ],
            ),
          ),
        ),
      );
    }

    return ColoredBox(
      color: desk,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(12, 8, 8, 4),
            child: Row(
              children: [
                Expanded(
                  child: Text(
                    'Pinch to zoom · drag to pan',
                    style: tichUi(
                      fontSize: 12,
                      color: TichColors.white.withValues(alpha: 0.92),
                    ),
                  ),
                ),
                IconButton(
                  tooltip: 'Open in viewer app',
                  onPressed: _openExternally,
                  color: TichColors.white,
                  icon: const Icon(Icons.open_in_new),
                ),
                IconButton(
                  tooltip: 'Refresh',
                  onPressed: _fetch,
                  color: TichColors.white,
                  icon: const Icon(Icons.refresh),
                ),
              ],
            ),
          ),
          Expanded(
            child: Padding(
              padding: const EdgeInsets.fromLTRB(16, 4, 16, 20),
              child: DecoratedBox(
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(4),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: isDark ? 0.55 : 0.35),
                      blurRadius: 18,
                      offset: const Offset(0, 6),
                    ),
                  ],
                ),
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(4),
                  child: PdfViewPinch(
                    controller: _controller!,
                    padding: 10,
                    builders: PdfViewPinchBuilders<DefaultBuilderOptions>(
                      options: const DefaultBuilderOptions(),
                      documentLoaderBuilder: (_) =>
                          const Center(child: AdaptiveProgress()),
                      pageLoaderBuilder: (_) =>
                          const Center(child: AdaptiveProgress()),
                      errorBuilder: (_, error) => Center(
                        child: Text(
                          'Could not render page.\n$error',
                          textAlign: TextAlign.center,
                          style: tichUi(fontSize: 13),
                        ),
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
