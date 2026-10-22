import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../data/staff_api.dart';
import '../../theme/tich_theme.dart';
import '../widgets/date_time_pickers.dart';
import '../widgets/tich_widgets.dart';
import '../widgets/tich_live_refresh.dart';
import 'content_upload_page.dart';
import 'staff_helpers.dart';

class StaffContentPage extends StatefulWidget {
  const StaffContentPage({super.key, required this.api});

  final StaffApi api;

  @override
  State<StaffContentPage> createState() => _StaffContentPageState();
}

class _StaffContentPageState extends State<StaffContentPage> {
  late Future<Map<String, dynamic>> _future;
  Object? _dataCache;

  @override
  void initState() {
    super.initState();
    staffPrimeScreen(
      persistKey: 'staff.content',
      setCache: (c) => _dataCache = c,
      setFuture: (f) => _future = f,
      fetch: () => widget.api.content(),
    );
  }

  Future<void> _reload() => staffCachedReload(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.content(),
      onData: (data) => _dataCache = data,
      persistKey: 'staff.content',
    );

  void _liveRefresh() {
    tichSilentReload(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.content(),
      onData: (data) => _dataCache = data,
      persistKey: 'staff.content',
    );
  }

  Future<void> _openUpload(List<Map<String, dynamic>> allocations) async {
    if (allocations.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('No units assigned yet.')),
      );
      return;
    }
    final posted = await Navigator.of(context).push<bool>(
      MaterialPageRoute(
        builder: (_) => StaffContentUploadPage(
          api: widget.api,
          allocations: allocations,
        ),
      ),
    );
    if (posted == true) _reload();
  }

  String _formatContentType(dynamic value) {
    final raw = value?.toString().replaceAll('_', ' ') ?? '';
    if (raw.isEmpty) return '';
    return raw[0].toUpperCase() + raw.substring(1);
  }

  @override
  Widget build(BuildContext context) {
    return TichLiveRefreshBinder(
      onLiveRefresh: _liveRefresh,
      child: AdaptiveScaffold(
        title: 'Learning content',
        floatingActionButton: FutureBuilder<Map<String, dynamic>>(
          future: _future,
          builder: (context, snap) {
            final merged = tichMergeSnapshot(snap, _dataCache as dynamic, persistKey: 'staff.content')
                as Map<String, dynamic>?;
            final allocations = ((merged?['allocations'] as List?) ??
                    (snap.data?['allocations'] as List?) ??
                    const [])
                .map((e) => Map<String, dynamic>.from(e as Map))
                .toList();
            return FloatingActionButton.extended(
              onPressed: () => _openUpload(allocations),
              icon: const Icon(Icons.upload_file),
              label: const Text('Upload'),
            );
          },
        ),
        body: FutureBuilder<Map<String, dynamic>>(
          future: _future,
          builder: (context, snap) {
            final merged = tichMergeSnapshot(snap, _dataCache as dynamic, persistKey: 'staff.content');
            if (tichShowLoadingSpinner(snap, merged)) { return staffLoadingBody(onRefresh: () async => _reload()); }
            if (merged == null && snap.hasError) {
              return staffErrorBody(snap.error, _reload);
            }
            final data = merged! as Map<String, dynamic>;
            final items = data['items'] as List? ?? const [];
            return RefreshIndicator(
              onRefresh: () async => _reload(),
              child: items.isEmpty
                  ? ListView(
                      physics: const AlwaysScrollableScrollPhysics(),
                      children: const [
                        TichCard(
                          child: TichEmpty('No learning content published yet.'),
                        ),
                      ],
                    )
                  : ListView.separated(
                      physics: const AlwaysScrollableScrollPhysics(),
                      padding: const EdgeInsets.all(16),
                      itemCount: items.length,
                      separatorBuilder: (_, _) => const SizedBox(height: 8),
                      itemBuilder: (context, i) {
                        final c =
                            Map<String, dynamic>.from(items[i] as Map);
                        final unitLine = staffContentUnitLine(c);
                        final cohort = staffContentCohort(c);
                        final type = _formatContentType(c['content_type']);
                        final when = c['published_at'] ?? c['created_at'];

                        return TichCard(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                c['title']?.toString() ?? 'Content',
                                style: tichUi(fontWeight: FontWeight.w700),
                              ),
                              if (unitLine.isNotEmpty) ...[
                                const SizedBox(height: 4),
                                Text(
                                  unitLine,
                                  style: tichUi(
                                    fontSize: 13,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                              ],
                              if (cohort != null) ...[
                                const SizedBox(height: 2),
                                Text(
                                  cohort,
                                  style: tichUi(
                                    fontSize: 12,
                                    color: TichColors.blueDark,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                              ],
                              if (type.isNotEmpty) ...[
                                const SizedBox(height: 4),
                                Text(
                                  type,
                                  style: tichUi(
                                    fontSize: 12,
                                    color: TichColors.neutralMuted,
                                  ),
                                ),
                              ],
                              if (c['has_file'] == true &&
                                  (c['file_name']?.toString() ?? '')
                                      .isNotEmpty) ...[
                                const SizedBox(height: 4),
                                Text(
                                  'Attachment: ${c['file_name']}',
                                  style: tichUi(fontSize: 11),
                                ),
                              ],
                              if (when != null)
                                Text(
                                  formatDate(when),
                                  style: tichUi(
                                    fontSize: 11,
                                    color: TichColors.neutralMuted,
                                  ),
                                ),
                            ],
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
