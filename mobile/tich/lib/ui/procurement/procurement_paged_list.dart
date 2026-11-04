import 'dart:async';

import 'package:flutter/material.dart';

import '../../data/api_client.dart';
import '../../data/tich_screen_cache.dart';
import '../../theme/tich_theme.dart';
import 'procurement_helpers.dart';

typedef ProcurementPagedFetch = Future<Map<String, dynamic>> Function({
  required int page,
  String? search,
});

/// Paginated procurement list with disk cache for page 1 and infinite scroll.
class ProcurementPagedList extends StatefulWidget {
  const ProcurementPagedList({
    super.key,
    required this.cacheKey,
    required this.fetch,
    required this.itemBuilder,
    required this.searchQuery,
    this.emptyMessage = 'No records yet.',
    this.padding = const EdgeInsets.all(12),
  });

  final String cacheKey;
  final ProcurementPagedFetch fetch;
  final Widget Function(Map<String, dynamic> row) itemBuilder;
  final String searchQuery;
  final String emptyMessage;
  final EdgeInsets padding;

  @override
  State<ProcurementPagedList> createState() => ProcurementPagedListState();
}

class ProcurementPagedListState extends State<ProcurementPagedList> {
  final ScrollController _scroll = ScrollController();
  List<Map<String, dynamic>> _items = [];
  bool _hasMore = true;
  int _page = 1;
  bool _loading = false;
  bool _loadingMore = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _seedFromCache();
    _scroll.addListener(_onScroll);
    unawaited(_refresh(showWait: _items.isEmpty));
  }

  @override
  void didUpdateWidget(covariant ProcurementPagedList oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.searchQuery != widget.searchQuery) {
      unawaited(_refresh(showWait: _items.isEmpty));
    }
  }

  @override
  void dispose() {
    _scroll.dispose();
    super.dispose();
  }

  void _seedFromCache() {
    final cached = TichScreenCache.read<Map<String, dynamic>>(widget.cacheKey);
    if (cached == null) return;
    _applyResponse(cached, append: false, persist: false);
  }

  void _applyResponse(
    Map<String, dynamic> data, {
    required bool append,
    required bool persist,
  }) {
    final rawItems = data['items'] as List? ?? const [];
    final mapped = [
      for (final row in rawItems)
        if (row is Map) Map<String, dynamic>.from(row),
    ];
    final meta = data['meta'];
    var hasMore = false;
    var page = _page;
    if (meta is Map) {
      hasMore = meta['has_more'] == true;
      page = (meta['page'] as num?)?.toInt() ?? page;
    }
    setState(() {
      _items = append ? [..._items, ...mapped] : mapped;
      _hasMore = hasMore;
      _page = page;
    });
    if (persist && !append) {
      TichScreenCache.write(widget.cacheKey, data);
    }
  }

  Future<void> refresh() => _refresh(showWait: false);

  Future<void> _refresh({required bool showWait}) async {
    if (_loading) return;
    setState(() {
      _loading = showWait;
      _error = null;
    });
    try {
      final data = await widget.fetch(
        page: 1,
        search: widget.searchQuery.isEmpty ? null : widget.searchQuery,
      );
      if (!mounted) return;
      _applyResponse(data, append: false, persist: true);
    } catch (e) {
      if (mounted) setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _loadMore() async {
    if (_loadingMore || !_hasMore || _loading) return;
    setState(() => _loadingMore = true);
    try {
      final data = await widget.fetch(
        page: _page + 1,
        search: widget.searchQuery.isEmpty ? null : widget.searchQuery,
      );
      if (!mounted) return;
      _applyResponse(data, append: true, persist: false);
    } finally {
      if (mounted) setState(() => _loadingMore = false);
    }
  }

  void _onScroll() {
    if (!_scroll.hasClients || !_hasMore || _loadingMore) return;
    if (_scroll.position.pixels >= _scroll.position.maxScrollExtent - 280) {
      unawaited(_loadMore());
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading && _items.isEmpty) {
      return procurementLoadingBody(onRefresh: () async => _refresh(showWait: false));
    }
    if (_error != null && _items.isEmpty) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(tichDescribeError(_error)),
            TextButton(
              onPressed: () => _refresh(showWait: true),
              child: const Text('Retry'),
            ),
          ],
        ),
      );
    }
    if (_items.isEmpty) {
      return Center(
        child: Text(
          widget.searchQuery.isEmpty
              ? widget.emptyMessage
              : 'No matches for "${widget.searchQuery}".',
          textAlign: TextAlign.center,
          style: tichUi(color: TichColors.neutralMuted),
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: () => _refresh(showWait: false),
      child: ListView.separated(
        controller: _scroll,
        padding: widget.padding,
        itemCount: _items.length + (_loadingMore ? 1 : 0),
        separatorBuilder: (_, __) => const SizedBox(height: 8),
        itemBuilder: (context, index) {
          if (index >= _items.length) {
            return const Padding(
              padding: EdgeInsets.symmetric(vertical: 16),
              child: Center(child: CircularProgressIndicator()),
            );
          }
          return widget.itemBuilder(_items[index]);
        },
      ),
    );
  }
}
