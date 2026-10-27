import 'package:flutter/material.dart';

import '../../theme/tich_theme.dart';
import 'tich_generated_document_viewer.dart';
import 'tich_widgets.dart';

class TichTimetableDocumentRef {
  const TichTimetableDocumentRef({
    required this.id,
    required this.kind,
    required this.title,
  });

  final int id;
  final String kind;
  final String title;

  static List<TichTimetableDocumentRef> parseDocuments(
    List<dynamic>? raw, {
    List<dynamic>? timetablesFallback,
  }) {
    if (raw != null && raw.isNotEmpty) {
      return raw
          .map((e) => Map<String, dynamic>.from(e as Map))
          .where((m) => (m['id'] as num?) != null)
          .map(
            (m) => TichTimetableDocumentRef(
              id: (m['id'] as num).toInt(),
              kind: m['kind']?.toString() ?? 'lesson',
              title: m['display_title']?.toString() ??
                  m['title']?.toString() ??
                  'Timetable',
            ),
          )
          .toList();
    }
    if (timetablesFallback == null || timetablesFallback.isEmpty) {
      return const [];
    }
    return timetablesFallback
        .map((e) => Map<String, dynamic>.from(e as Map))
        .where((m) => (m['id'] as num?) != null)
        .map(
          (m) => TichTimetableDocumentRef(
            id: (m['id'] as num).toInt(),
            kind: m['kind']?.toString() ?? 'lesson',
            title: m['display_title']?.toString() ??
                m['title']?.toString() ??
                'Timetable',
          ),
        )
        .toList();
  }

  static TichTimetableDocumentRef? forKind(
    List<TichTimetableDocumentRef> docs,
    String kind,
  ) {
    for (final d in docs) {
      if (d.kind == kind) return d;
    }
    return null;
  }
}

typedef TimetablePdfLoader = Future<List<int>> Function(int timetableId);

class TichTimetableDocumentPane extends StatelessWidget {
  const TichTimetableDocumentPane({
    super.key,
    required this.document,
    required this.loadPdf,
    this.provisional = false,
    this.unavailableMessage,
  });

  final TichTimetableDocumentRef? document;
  final TimetablePdfLoader loadPdf;
  final bool provisional;
  final String? unavailableMessage;

  @override
  Widget build(BuildContext context) {
    if (document == null) {
      return Center(
        child: TichEmpty(
          unavailableMessage ?? 'No published timetable document for this tab.',
        ),
      );
    }
    if (provisional) {
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
    final doc = document!;
    return TichGeneratedDocumentViewer(
      key: ValueKey(doc.id),
      cacheFileName: 'timetable-${doc.kind}-${doc.id}.pdf',
      emptyMessage: 'Could not load the timetable document.',
      loadPdf: () => loadPdf(doc.id),
    );
  }
}
