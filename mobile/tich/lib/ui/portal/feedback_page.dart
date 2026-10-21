import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../data/api_client.dart';
import '../../data/employee_api.dart';
import '../../theme/tich_theme.dart';
import '../widgets/date_time_pickers.dart';
import '../widgets/tich_widgets.dart';
import '../../data/tich_screen_cache.dart';
import '../widgets/tich_live_refresh.dart';

class FeedbackPage extends StatefulWidget {
  const FeedbackPage({super.key, required this.api});

  final EmployeeApi api;

  @override
  State<FeedbackPage> createState() => _FeedbackPageState();
}

class _FeedbackPageState extends State<FeedbackPage> {
  late Future<Map<String, dynamic>> _future;
  Object? _dataCache;

  @override
  void initState() {
    super.initState();
    _dataCache = TichScreenCache.read<Map<String, dynamic>>('portal.feedback');
    _future = TichScreenCache.ensure('portal.feedback', () => widget.api.feedback());
  }

  Future<void> _reload() => tichRefreshCached<Map<String, dynamic>>(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.feedback(),
      onData: (data) => _dataCache = data,
      persistKey: 'portal.feedback',
    );

  void _liveRefresh() {
    tichSilentReload(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.feedback(),
      onData: (data) => _dataCache = data,
      persistKey: 'portal.feedback',
    );
  }

  Future<void> _createFeedback() async {
    final created = await Navigator.of(context).push<bool>(
      MaterialPageRoute(
        builder: (_) => _FeedbackCreatePage(api: widget.api),
      ),
    );
    if (created == true) {
      _reload();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Feedback submitted to HR.')),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return TichLiveRefreshBinder(
      onLiveRefresh: _liveRefresh,
      child: AdaptiveScaffold(
      title: 'My feedback',
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _createFeedback,
        icon: const Icon(Icons.add),
        label: const Text('Send feedback'),
      ),
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          final merged = tichMergeSnapshot(snap, _dataCache as dynamic, persistKey: 'portal.feedback');
          if (tichShowLoadingSpinner(snap, merged)) { return tichInstantWaitBody(onRefresh: () async => _reload()); }
          if (merged == null && snap.hasError) {
            return TichErrorRefresh(
              message: snap.error.toString(),
              onRetry: _reload,
            );
          }
          final data = merged! as Map<String, dynamic>;

          final items = data['items'] as List? ?? const [];
          return RefreshIndicator(
            onRefresh: () async => _reload(),
            child: ListView(
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 88),
              children: [
                if (items.isEmpty)
                  const TichCard(
                    child: TichEmpty(
                      'No feedback submitted yet. Share a suggestion or comment with HR.',
                    ),
                  )
                else
                  ...items.map((raw) {
                    final item = Map<String, dynamic>.from(raw as Map);
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 10),
                      child: TichCard(
                        onTap: () => Navigator.of(context).push(
                          MaterialPageRoute(
                            builder: (_) => _FeedbackDetailPage(item: item),
                          ),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              children: [
                                Expanded(
                                  child: Text(
                                    item['feedback_type']?.toString().isNotEmpty ==
                                            true
                                        ? item['feedback_type'].toString()
                                        : 'Feedback',
                                    style: tichUi(
                                      fontWeight: FontWeight.w700,
                                      fontSize: 15,
                                    ),
                                  ),
                                ),
                                TichStatusChip(
                                  item['status']?.toString() ?? 'open',
                                  tone: toneForStatus(
                                    item['status']?.toString(),
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 8),
                            Text(
                              item['description']?.toString() ?? '',
                              maxLines: 3,
                              overflow: TextOverflow.ellipsis,
                              style: tichUi(color: TichColors.neutralMuted),
                            ),
                            const SizedBox(height: 8),
                            Text(
                              formatDateTime(item['created_at']),
                              style: tichUi(
                                fontSize: 12,
                                color: TichColors.neutralMuted,
                              ),
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
      ),
    );
  }
}

class _FeedbackCreatePage extends StatefulWidget {
  const _FeedbackCreatePage({required this.api});

  final EmployeeApi api;

  @override
  State<_FeedbackCreatePage> createState() => _FeedbackCreatePageState();
}

class _FeedbackCreatePageState extends State<_FeedbackCreatePage> {
  final _formKey = GlobalKey<FormState>();
  final _type = TextEditingController();
  final _description = TextEditingController();
  bool _saving = false;

  @override
  void dispose() {
    _type.dispose();
    _description.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _saving = true);
    try {
      await widget.api.submitFeedback(
        type: _type.text,
        description: _description.text.trim(),
      );
      if (!mounted) return;
      Navigator.of(context).pop(true);
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.message)),
      );
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'Send feedback',
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          TichCard(
            child: Row(
              children: [
                const Icon(Icons.feedback_outlined, color: TichColors.blue),
                const SizedBox(width: 12),
                Expanded(
                  child: Text(
                    'Share a suggestion, compliment, or concern with HR.',
                    style: tichUi(color: TichColors.blueDark),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),
          Form(
            key: _formKey,
            child: Column(
              children: [
                TextFormField(
                  controller: _type,
                  maxLength: 100,
                  decoration: const InputDecoration(
                    labelText: 'Feedback type (optional)',
                    hintText: 'Suggestion, compliment, comment...',
                  ),
                ),
                const SizedBox(height: 12),
                TextFormField(
                  controller: _description,
                  minLines: 5,
                  maxLines: 8,
                  maxLength: 5000,
                  decoration: const InputDecoration(
                    labelText: 'Your feedback',
                    alignLabelWithHint: true,
                  ),
                  validator: (value) {
                    if (value == null || value.trim().isEmpty) {
                      return 'Enter your feedback.';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 16),
                SizedBox(
                  width: double.infinity,
                  child: AdaptiveFilledButton(
                    onPressed: _saving ? null : _submit,
                    child: _saving
                        ? const SizedBox(
                            width: 18,
                            height: 18,
                            child: AdaptiveProgress(color: Colors.white),
                          )
                        : const Text('Submit to HR'),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _FeedbackDetailPage extends StatelessWidget {
  const _FeedbackDetailPage({required this.item});

  final Map<String, dynamic> item;

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'Feedback details',
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          TichCard(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        item['feedback_type']?.toString().isNotEmpty == true
                            ? item['feedback_type'].toString()
                            : 'Feedback',
                        style: tichUi(
                          fontSize: 17,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ),
                    TichStatusChip(
                      item['status']?.toString() ?? 'open',
                      tone: toneForStatus(item['status']?.toString()),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                Text(
                  formatDateTime(item['created_at']),
                  style: tichUi(
                    fontSize: 12,
                    color: TichColors.neutralMuted,
                  ),
                ),
                const Divider(height: 24),
                Text(
                  item['description']?.toString() ?? '',
                  style: tichUi(height: 1.5),
                ),
              ],
            ),
          ),
          if ((item['response']?.toString() ?? '').isNotEmpty) ...[
            const SizedBox(height: 16),
            const TichSectionHeader('HR response'),
            TichCard(child: Text(item['response'].toString())),
          ],
          if ((item['hr_comments']?.toString() ?? '').isNotEmpty) ...[
            const SizedBox(height: 16),
            const TichSectionHeader('HR comments'),
            TichCard(child: Text(item['hr_comments'].toString())),
          ],
        ],
      ),
    );
  }
}
