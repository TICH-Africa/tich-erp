import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

import '../../adaptive/adaptive.dart';
import '../../data/api_client.dart';
import '../../data/employee_api.dart';
import '../../theme/tich_theme.dart';
import '../widgets/date_time_pickers.dart';
import '../widgets/tich_widgets.dart';
import '../widgets/tich_live_refresh.dart';

class LeavePage extends StatefulWidget {
  const LeavePage({super.key, required this.api});

  final EmployeeApi api;

  @override
  State<LeavePage> createState() => _LeavePageState();
}

class _LeavePageState extends State<LeavePage> {
  late Future<Map<String, dynamic>> _future;

  @override
  void initState() {
    super.initState();
    _future = widget.api.leave();
  }

  void _reload() => setState(() => _future = widget.api.leave());

  void _liveRefresh() {
    tichSilentReload(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.leave(),
      apply: (f) => _future = f,
    );
  }

  Future<void> _openApply(Map<String, dynamic> data) async {
    final result = await Navigator.of(context).push<bool>(
      MaterialPageRoute(
        builder: (_) => LeaveApplyPage(api: widget.api, leaveData: data),
      ),
    );
    if (result == true) _reload();
  }

  @override
  Widget build(BuildContext context) {
    return TichLiveRefreshBinder(
      onLiveRefresh: _liveRefresh,
      child: AdaptiveScaffold(
      title: 'Leave',
      floatingActionButton: FutureBuilder(
        future: _future,
        builder: (context, snap) {
          if (!snap.hasData) return const SizedBox.shrink();
          return FloatingActionButton.extended(
            backgroundColor: TichColors.green,
            onPressed: () => _openApply(snap.data!),
            icon: const Icon(Icons.add),
            label: const Text('Apply'),
          );
        },
      ),
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          if (snap.connectionState != ConnectionState.done) {
            return const Center(child: AdaptiveProgress());
          }
          if (snap.hasError) {
            return TichErrorRefresh(
              message: snap.error.toString(),
              onRetry: _reload,
            );
          }
          final data = snap.data!;
          final balances = data['balances'] as List? ?? const [];
          final requests = data['requests'] as List? ?? const [];
          final pending = data['pending_coverages'] as List? ?? const [];

          return RefreshIndicator(
            onRefresh: () async => _reload(),
            child: ListView(
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 88),
              children: [
                const TichSectionHeader('Balances'),
                if (balances.isEmpty)
                  const TichCard(child: TichEmpty('No balances.'))
                else
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: balances.map((raw) {
                      final b = Map<String, dynamic>.from(raw as Map);
                      final scheme = Theme.of(context).colorScheme;
                      return Container(
                        width: (MediaQuery.of(context).size.width - 40) / 2,
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: scheme.surface,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(
                            color: scheme.outline.withValues(alpha: 0.35),
                          ),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              b['leave_type_name']?.toString() ?? '',
                              style: tichUi(
                                fontSize: 12,
                                color: scheme.onSurface.withValues(alpha: 0.65),
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              '${b['balance_days']}',
                              style: GoogleFonts.merriweather(
                                fontSize: 22,
                                fontWeight: FontWeight.w700,
                                color: scheme.primary,
                              ),
                            ),
                          ],
                        ),
                      );
                    }).toList(),
                  ),
                if (pending.isNotEmpty) ...[
                  const SizedBox(height: 16),
                  const TichSectionHeader('Stand-in requests'),
                  ...pending.map((raw) {
                    final c = Map<String, dynamic>.from(raw as Map);
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 8),
                      child: TichCard(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              '${c['applicant']} · ${c['leave_type']}',
                              style: tichUi(fontWeight: FontWeight.w700),
                            ),
                            Text(
                              '${c['start_date']} → ${c['end_date']}',
                              style: tichUi(
                                fontSize: 12,
                                color: Theme.of(context)
                                    .colorScheme
                                    .onSurface
                                    .withValues(alpha: 0.65),
                              ),
                            ),
                            const SizedBox(height: 10),
                            Row(
                              children: [
                                Expanded(
                                  child: OutlinedButton(
                                    onPressed: () async {
                                      try {
                                        await widget.api.declineCoverage(
                                          (c['id'] as num).toInt(),
                                        );
                                        _reload();
                                      } on ApiException catch (e) {
                                        if (!mounted) return;
                                        ScaffoldMessenger.of(context)
                                            .showSnackBar(
                                          SnackBar(content: Text(e.message)),
                                        );
                                      }
                                    },
                                    child: const Text('Decline'),
                                  ),
                                ),
                                const SizedBox(width: 8),
                                Expanded(
                                  child: FilledButton(
                                    onPressed: () async {
                                      try {
                                        await widget.api.acceptCoverage(
                                          (c['id'] as num).toInt(),
                                        );
                                        _reload();
                                      } on ApiException catch (e) {
                                        if (!mounted) return;
                                        ScaffoldMessenger.of(context)
                                            .showSnackBar(
                                          SnackBar(content: Text(e.message)),
                                        );
                                      }
                                    },
                                    child: const Text('Accept'),
                                  ),
                                ),
                              ],
                            ),
                          ],
                        ),
                      ),
                    );
                  }),
                ],
                const SizedBox(height: 16),
                const TichSectionHeader('My requests'),
                if (requests.isEmpty)
                  const TichCard(child: TichEmpty('No leave requests yet.'))
                else
                  ...requests.map((raw) {
                    final r = Map<String, dynamic>.from(raw as Map);
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 8),
                      child: TichCard(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              children: [
                                Expanded(
                                  child: Text(
                                    r['leave_type_name']?.toString() ?? 'Leave',
                                    style: tichUi(
                                      fontWeight: FontWeight.w700,
                                    ),
                                  ),
                                ),
                                TichStatusChip(
                                  r['overall_status']?.toString() ?? '',
                                  tone: toneForStatus(
                                    r['overall_status']?.toString(),
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 6),
                            Text(
                              '${r['start_date']} → ${r['end_date']} · ${r['days_requested']} day(s)',
                              style: tichUi(fontSize: 13),
                            ),
                            if (r['reason'] != null) ...[
                              const SizedBox(height: 4),
                              Text(
                                r['reason'].toString(),
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis,
                                style: tichUi(
                                  fontSize: 12,
                                  color: Theme.of(context)
                                      .colorScheme
                                      .onSurface
                                      .withValues(alpha: 0.65),
                                ),
                              ),
                            ],
                            if (r['editable'] == true) ...[
                              const SizedBox(height: 8),
                              Align(
                                alignment: Alignment.centerRight,
                                child: TextButton(
                                  onPressed: () async {
                                    try {
                                      await widget.api.cancelLeave(
                                        (r['id'] as num).toInt(),
                                      );
                                      _reload();
                                    } on ApiException catch (e) {
                                      if (!mounted) return;
                                      ScaffoldMessenger.of(context).showSnackBar(
                                        SnackBar(content: Text(e.message)),
                                      );
                                    }
                                  },
                                  child: const Text('Cancel request'),
                                ),
                              ),
                            ],
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

class LeaveApplyPage extends StatefulWidget {
  const LeaveApplyPage({
    super.key,
    required this.api,
    required this.leaveData,
  });

  final EmployeeApi api;
  final Map<String, dynamic> leaveData;

  @override
  State<LeaveApplyPage> createState() => _LeaveApplyPageState();
}

class _LeaveApplyPageState extends State<LeaveApplyPage> {
  final _reason = TextEditingController();
  int? _typeId;
  DateTime? _start;
  DateTime? _end;
  bool _busy = false;
  String? _error;

  @override
  void dispose() {
    _reason.dispose();
    super.dispose();
  }

  Future<void> _pickDate({required bool start}) async {
    final today = DateTime.now();
    final todayDate = DateTime(today.year, today.month, today.day);
    final picked = await pickDateOnly(
      context,
      initial: start ? (_start ?? todayDate) : (_end ?? _start ?? todayDate),
      firstDate: todayDate,
      lastDate: todayDate.add(const Duration(days: 365)),
    );
    if (picked == null) return;
    setState(() {
      if (start) {
        _start = picked;
        if (_end != null && _end!.isBefore(picked)) _end = picked;
      } else {
        _end = picked;
      }
    });
  }

  Future<void> _submit() async {
    if (_typeId == null || _start == null || _end == null || _reason.text.trim().isEmpty) {
      setState(() => _error = 'Leave type, dates, and reason are required.');
      return;
    }
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await widget.api.submitLeave({
        'leave_type_id': _typeId,
        'start_date': dateApi.format(_start!),
        'end_date': dateApi.format(_end!),
        'reason': _reason.text.trim(),
      });
      if (!mounted) return;
      Navigator.pop(context, true);
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final types = (widget.leaveData['leave_types'] as List? ?? const [])
        .map((e) => Map<String, dynamic>.from(e as Map))
        .toList();

    final scheme = Theme.of(context).colorScheme;
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final reasonFill = isDark ? const Color(0xFF1F2C34) : const Color(0xFFF0F2F5);
    final reasonBorder = isDark ? const Color(0xFF2A3942) : const Color(0xFFE9EDEF);

    return AdaptiveScaffold(
      title: 'Apply for leave',
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          TichDropdownFormField<int>(
            value: _typeId,
            decoration: const InputDecoration(labelText: 'Leave type'),
            items: types
                .map(
                  (t) => DropdownMenuItem(
                    value: (t['id'] as num).toInt(),
                    child: Text(
                      '${t['name']} (${t['available_balance']} left)',
                    ),
                  ),
                )
                .toList(),
            onChanged: _busy ? null : (v) => setState(() => _typeId = v),
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: OutlinedButton(
                  onPressed: _busy ? null : () => _pickDate(start: true),
                  child: Text(
                    _start == null ? 'Start date' : dateDisplay.format(_start!),
                    textAlign: TextAlign.center,
                  ),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: OutlinedButton(
                  onPressed: _busy ? null : () => _pickDate(start: false),
                  child: Text(
                    _end == null ? 'End date' : dateDisplay.format(_end!),
                    textAlign: TextAlign.center,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          Text(
            'Reason',
            style: tichUi(
              fontSize: 13,
              fontWeight: FontWeight.w600,
              color: scheme.onSurface.withValues(alpha: 0.75),
            ),
          ),
          const SizedBox(height: 8),
          Container(
            decoration: BoxDecoration(
              color: reasonFill,
              borderRadius: BorderRadius.circular(20),
              border: Border.all(color: reasonBorder),
            ),
            padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 4),
            child: TextField(
              controller: _reason,
              enabled: !_busy,
              minLines: 4,
              maxLines: 8,
              keyboardType: TextInputType.multiline,
              textCapitalization: TextCapitalization.sentences,
              style: tichUi(
                fontSize: 16,
                height: 1.35,
                color: scheme.onSurface,
              ),
              decoration: InputDecoration(
                hintText: 'Type a message…',
                hintStyle: tichUi(
                  fontSize: 16,
                  color: scheme.onSurface.withValues(alpha: 0.45),
                ),
                border: InputBorder.none,
                enabledBorder: InputBorder.none,
                focusedBorder: InputBorder.none,
                filled: false,
                contentPadding: const EdgeInsets.symmetric(
                  horizontal: 14,
                  vertical: 10,
                ),
              ),
            ),
          ),
          if (_error != null) ...[
            const SizedBox(height: 12),
            Text(_error!, style: TextStyle(color: scheme.error)),
          ],
          const SizedBox(height: 20),
          FilledButton(
            onPressed: _busy ? null : _submit,
            child: _busy
                ? const SizedBox(
                    width: 18,
                    height: 18,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : const Text('Submit leave request'),
          ),
        ],
      ),
    );
  }
}
