import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../data/api_client.dart';
import '../../data/student_api.dart';
import '../../theme/tich_theme.dart';
import '../widgets/date_time_pickers.dart';
import '../widgets/tich_widgets.dart';
import '../widgets/tich_live_refresh.dart';
import 'student_helpers.dart';

class StudentLifecyclePage extends StatefulWidget {
  const StudentLifecyclePage({super.key, required this.api});

  final StudentApi api;

  @override
  State<StudentLifecyclePage> createState() => _StudentLifecyclePageState();
}

class _StudentLifecyclePageState extends State<StudentLifecyclePage> {
  late Future<Map<String, dynamic>> _future;
  Object? _dataCache;
  final _reason = TextEditingController();
  int _months = 3;
  final List<({String path, String name})> _files = [];
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    studentPrimeScreen(
      persistKey: 'student.lifecycle',
      setCache: (c) => _dataCache = c,
      setFuture: (f) => _future = f,
      fetch: () => widget.api.lifecycleRequests(),
    );
  }

  @override
  void dispose() {
    _reason.dispose();
    super.dispose();
  }

  Future<void> _reload() => studentCachedReload(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.lifecycleRequests(),
      onData: (data) => _dataCache = data,
      persistKey: 'student.lifecycle',
    );

  void _liveRefresh() {
    tichSilentReload(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.lifecycleRequests(),
      onData: (data) => _dataCache = data,
      persistKey: 'student.lifecycle',
    );
  }

  Future<void> _pickFiles() async {
    final pick = await FilePicker.platform.pickFiles(
      allowMultiple: true,
      type: FileType.custom,
      allowedExtensions: const ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'],
    );
    if (pick == null) return;
    setState(() {
      for (final f in pick.files) {
        if (f.path == null) continue;
        _files.add((path: f.path!, name: f.name));
      }
    });
  }

  Future<void> _submit() async {
    if (_reason.text.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Reason is required.')),
      );
      return;
    }
    if (_files.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Attach at least one supporting document.')),
      );
      return;
    }
    setState(() => _busy = true);
    try {
      final res = await widget.api.submitLifecycle(
        defermentMonths: _months,
        reason: _reason.text.trim(),
        attachments: List.of(_files),
      );
      if (!mounted) return;
      _reason.clear();
      _files.clear();
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(res['message']?.toString() ?? 'Submitted')),
      );
      _reload();
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return TichLiveRefreshBinder(
      onLiveRefresh: _liveRefresh,
      child: AdaptiveScaffold(
      title: 'Deferment requests',
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          final merged = tichMergeSnapshot(snap, _dataCache as dynamic, persistKey: 'student.lifecycle');
          if (tichShowLoadingSpinner(snap, merged)) { return studentLoadingBody(onRefresh: () async => _reload()); }
          if (merged == null && snap.hasError) {
            return studentErrorBody(snap.error, _reload);
          }
          final data = merged! as Map<String, dynamic>;
          final requests = data['requests'] as List? ?? const [];
          return RefreshIndicator(
            onRefresh: () async => _reload(),
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                const TichSectionHeader('Submit deferment'),
                TichCard(
                  child: Column(
                    children: [
                      TichDropdownFormField<int>(
                        value: _months,
                        items: [1, 2, 3, 6, 12, 18, 24, 36]
                            .map(
                              (m) => DropdownMenuItem(
                                value: m,
                                child: Text('$m month${m == 1 ? '' : 's'}'),
                              ),
                            )
                            .toList(),
                        onChanged: _busy
                            ? null
                            : (v) => setState(() => _months = v ?? 3),
                        decoration:
                            const InputDecoration(labelText: 'Deferment period'),
                      ),
                      const SizedBox(height: 10),
                      TextField(
                        controller: _reason,
                        enabled: !_busy,
                        maxLines: 4,
                        decoration: const InputDecoration(labelText: 'Reason'),
                      ),
                      const SizedBox(height: 10),
                      OutlinedButton.icon(
                        onPressed: _busy ? null : _pickFiles,
                        icon: const Icon(Icons.attach_file),
                        label: Text(
                          _files.isEmpty
                              ? 'Attach supporting documents'
                              : '${_files.length} file(s) attached',
                        ),
                      ),
                      if (_files.isNotEmpty) ...[
                        const SizedBox(height: 8),
                        for (final f in _files)
                          Align(
                            alignment: Alignment.centerLeft,
                            child: Text(
                              '• ${f.name}',
                              style: tichUi(
                                fontSize: 12,
                                color: TichColors.neutralMuted,
                              ),
                            ),
                          ),
                      ],
                      const SizedBox(height: 14),
                      AdaptiveFilledButton(
                        onPressed: _busy ? null : _submit,
                        child: _busy
                            ? const SizedBox(
                                width: 18,
                                height: 18,
                                child: AdaptiveProgress(color: Colors.white),
                              )
                            : const Text('Submit deferment request'),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 16),
                const TichSectionHeader('My requests'),
                if (requests.isEmpty)
                  const TichCard(child: TichEmpty('No lifecycle requests yet.'))
                else
                  ...requests.map((raw) {
                    final r = Map<String, dynamic>.from(raw as Map);
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 8),
                      child: TichCard(
                        child: ListTile(
                          contentPadding: EdgeInsets.zero,
                          title: Text(
                            '${r['request_type'] ?? 'Request'}'
                            '${r['deferment_months'] != null ? ' · ${r['deferment_months']} months' : ''}',
                            style: tichUi(fontWeight: FontWeight.w600),
                          ),
                          subtitle: Text(
                            [
                              r['reason'],
                              formatDateTime(r['created_at']),
                            ]
                                .where((e) => e != null && '$e'.isNotEmpty)
                                .join('\n'),
                          ),
                          isThreeLine: true,
                          trailing: TichStatusChip(
                            r['status']?.toString() ?? 'pending',
                            tone: toneForStatus(r['status']?.toString()),
                          ),
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
