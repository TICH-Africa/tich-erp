import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../data/api_client.dart';
import '../../data/student_api.dart';
import '../../theme/tich_theme.dart';
import '../widgets/tich_live_refresh.dart';
import '../widgets/tich_widgets.dart';
import 'student_helpers.dart';

/// Supplementary & special exam applications (lightweight API — not full academics hub).
class StudentExamRequestsPage extends StatefulWidget {
  const StudentExamRequestsPage({super.key, required this.api});

  final StudentApi api;

  @override
  State<StudentExamRequestsPage> createState() => _StudentExamRequestsPageState();
}

class _StudentExamRequestsPageState extends State<StudentExamRequestsPage> {
  static const _cacheKey = 'student.academics.exam_requests';

  late Future<Map<String, dynamic>> _future;
  Object? _dataCache;

  String _type = 'special_exam';
  int? _unitId;
  int? _semesterId;
  final _reason = TextEditingController();
  final List<({String path, String name})> _files = [];
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    _dataCache = studentReadCache(_cacheKey);
    studentPrimeScreen(
      persistKey: _cacheKey,
      setCache: (c) {
        _dataCache = c;
        _primeUnitSelection(c);
      },
      setFuture: (f) => _future = f,
      fetch: () => widget.api.academicsExamRequests(),
    );
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      if (_dataCache != null) {
        Future<void>.delayed(const Duration(seconds: 8), () {
          if (mounted) _liveRefresh();
        });
        return;
      }
      _liveRefresh();
    });
  }

  void _primeUnitSelection(Object? cache) {
    if (cache is! Map<String, dynamic>) return;
    final units = cache['registered_units'] as List? ?? const [];
    if (units.isEmpty || _unitId != null) return;
    final first = Map<String, dynamic>.from(units.first as Map);
    _unitId = (first['unit_id'] as num?)?.toInt();
    _semesterId = (first['semester_id'] as num?)?.toInt();
  }

  @override
  void dispose() {
    _reason.dispose();
    super.dispose();
  }

  Future<void> _reload() => studentCachedReload(
        mounted: () => mounted,
        setState: setState,
        fetch: () => widget.api.academicsExamRequests(),
        onData: (data) {
          _dataCache = data;
          _primeUnitSelection(data);
        },
        persistKey: _cacheKey,
      );

  void _liveRefresh() {
    tichSilentReload(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.academicsExamRequests(),
      onData: (data) {
        _dataCache = data;
        _primeUnitSelection(data);
      },
      persistKey: _cacheKey,
    );
  }

  Future<void> _pick() async {
    final pick = await FilePicker.platform.pickFiles(
      allowMultiple: true,
      type: FileType.custom,
      allowedExtensions: const ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'],
    );
    if (pick == null) return;
    setState(() {
      for (final f in pick.files) {
        if (f.path != null) _files.add((path: f.path!, name: f.name));
      }
    });
  }

  Future<void> _submit() async {
    if (_unitId == null || _semesterId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('No registered unit available to request against.')),
      );
      return;
    }
    if (_type == 'special_exam' && _reason.text.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Reason is required for special exams.')),
      );
      return;
    }
    if (_type == 'special_exam' && _files.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Attach supporting documents for special exams.')),
      );
      return;
    }
    setState(() => _busy = true);
    try {
      final res = await widget.api.submitExamSitting(
        requestType: _type,
        unitId: _unitId!,
        semesterId: _semesterId!,
        reason: _reason.text.trim().isEmpty ? null : _reason.text.trim(),
        attachments: List.of(_files),
      );
      if (!mounted) return;
      _reason.clear();
      _files.clear();
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(res['message']?.toString() ?? 'Submitted')),
      );
      await _reload();
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
        title: 'Supplementary & special exams',
        body: FutureBuilder<Map<String, dynamic>>(
          future: _future,
          builder: (context, snap) {
            final merged = tichMergeSnapshot(snap, _dataCache as dynamic, persistKey: _cacheKey);
            if (tichShowLoadingSpinner(snap, merged)) {
              return tichInstantWaitBody(onRefresh: () async => _reload());
            }
            if (merged == null && snap.hasError) {
              return TichErrorRefresh(
                message: tichDescribeError(snap.error),
                onRetry: _reload,
              );
            }
            final data = merged! as Map<String, dynamic>;
            final units = data['registered_units'] as List? ?? const [];
            final special = data['special_exam_requests'] as List? ?? const [];
            final supplementary = data['supplementary_exam_requests'] as List? ?? const [];

            final unitItems = units
                .map((e) => Map<String, dynamic>.from(e as Map))
                .where((u) => u['unit_id'] != null && u['semester_id'] != null)
                .toList();

            return RefreshIndicator(
              onRefresh: _reload,
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  const TichSectionHeader('New request'),
                  TichCard(
                    child: Column(
                      children: [
                        TichDropdownFormField<String>(
                          value: _type,
                          items: const [
                            DropdownMenuItem(
                              value: 'special_exam',
                              child: Text('Special exam'),
                            ),
                            DropdownMenuItem(
                              value: 'supplementary',
                              child: Text('Supplementary exam'),
                            ),
                          ],
                          onChanged: _busy ? null : (v) => setState(() => _type = v ?? 'special_exam'),
                          decoration: const InputDecoration(labelText: 'Request type'),
                        ),
                        const SizedBox(height: 10),
                        TichDropdownFormField<int>(
                          value: _unitId,
                          items: unitItems
                              .map(
                                (u) => DropdownMenuItem(
                                  value: (u['unit_id'] as num).toInt(),
                                  child: Text(
                                    '${u['unit_code'] ?? ''} · ${u['unit_name'] ?? ''}',
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                ),
                              )
                              .toList(),
                          onChanged: _busy
                              ? null
                              : (v) {
                                  final match = unitItems.firstWhere(
                                    (u) => (u['unit_id'] as num).toInt() == v,
                                  );
                                  setState(() {
                                    _unitId = v;
                                    _semesterId = (match['semester_id'] as num?)?.toInt();
                                  });
                                },
                          decoration: const InputDecoration(labelText: 'Unit'),
                        ),
                        const SizedBox(height: 10),
                        TextField(
                          controller: _reason,
                          enabled: !_busy,
                          maxLines: 3,
                          decoration: InputDecoration(
                            labelText: _type == 'special_exam'
                                ? 'Reason (required)'
                                : 'Notes (optional)',
                          ),
                        ),
                        const SizedBox(height: 10),
                        OutlinedButton.icon(
                          onPressed: _busy ? null : _pick,
                          icon: const Icon(Icons.attach_file),
                          label: Text(
                            _files.isNotEmpty
                                ? '${_files.length} file(s) attached'
                                : _type == 'special_exam'
                                    ? 'Attach documents (required)'
                                    : 'Attach documents (optional)',
                          ),
                        ),
                        const SizedBox(height: 12),
                        AdaptiveFilledButton(
                          onPressed: _busy ? null : _submit,
                          child: _busy
                              ? const SizedBox(
                                  width: 18,
                                  height: 18,
                                  child: AdaptiveProgress(color: Colors.white),
                                )
                              : const Text('Submit request'),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),
                  const TichSectionHeader('Special exam requests'),
                  if (special.isEmpty)
                    const TichCard(child: TichEmpty('None yet.'))
                  else
                    ...special.map((raw) {
                      final r = Map<String, dynamic>.from(raw as Map);
                      return Padding(
                        padding: const EdgeInsets.only(bottom: 8),
                        child: TichCard(
                          child: ListTile(
                            contentPadding: EdgeInsets.zero,
                            title: Text(
                              '${r['unit_code'] ?? ''} · ${r['unit_name'] ?? ''}',
                              style: tichUi(fontWeight: FontWeight.w600),
                            ),
                            subtitle: Text(r['reason']?.toString() ?? ''),
                            trailing: TichStatusChip(
                              r['status']?.toString() ?? 'pending',
                              tone: toneForStatus(r['status']?.toString()),
                            ),
                          ),
                        ),
                      );
                    }),
                  const SizedBox(height: 12),
                  const TichSectionHeader('Supplementary requests'),
                  if (supplementary.isEmpty)
                    const TichCard(child: TichEmpty('None yet.'))
                  else
                    ...supplementary.map((raw) {
                      final r = Map<String, dynamic>.from(raw as Map);
                      return Padding(
                        padding: const EdgeInsets.only(bottom: 8),
                        child: TichCard(
                          child: ListTile(
                            contentPadding: EdgeInsets.zero,
                            title: Text(
                              '${r['unit_code'] ?? ''} · ${r['unit_name'] ?? ''}',
                              style: tichUi(fontWeight: FontWeight.w600),
                            ),
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
