import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../data/api_client.dart';
import '../../data/staff_api.dart';
import '../../sync/tich_background_sync.dart';
import '../../sync/tich_offline_outbox.dart';
import '../../theme/tich_theme.dart';
import '../widgets/date_time_pickers.dart';
import '../widgets/tich_form_dialog.dart';
import '../widgets/tich_widgets.dart';
import '../widgets/tich_live_refresh.dart';
import 'staff_helpers.dart';
import 'staff_module_nav.dart';
import 'staff_prefetch.dart';

class StaffAttendancePage extends StatefulWidget {
  const StaffAttendancePage({super.key, required this.api});

  final StaffApi api;

  @override
  State<StaffAttendancePage> createState() => _StaffAttendancePageState();
}

class _StaffAttendancePageState extends State<StaffAttendancePage> {
  late Future<Map<String, dynamic>> _future;
  Object? _dataCache;
  bool _syncing = false;
  bool _flushing = false;
  int _pendingField = 0;

  Future<void> _openSession(int id) async {
    await openStaffAttendanceSession(context, widget.api, id);
    if (mounted) _reload();
  }

  @override
  void initState() {
    super.initState();
    _refreshPending();
    _dataCache = staffReadCache('staff.attendance');
    staffPrimeScreen(
      persistKey: 'staff.attendance',
      setCache: (c) => _dataCache = c,
      setFuture: (f) => _future = f,
      fetch: () => widget.api.attendance(),
    );
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      if (_dataCache != null) {
        Future<void>.delayed(const Duration(seconds: 10), () {
          if (mounted) _liveRefresh();
        });
        return;
      }
      _liveRefresh();
    });
  }

  Future<void> _createManualSession(Map<String, dynamic> data) async {
    final allocations = (data['allocations'] as List? ?? const [])
        .map((e) => Map<String, dynamic>.from(e as Map))
        .where((a) => a['id'] != null)
        .toList();
    if (allocations.isEmpty) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('No unit allocations found.')),
      );
      return;
    }

    var allocationId = (allocations.first['id'] as num).toInt();
    var sessionDate = DateTime.now();
    final venueController = TextEditingController();
    var busy = false;

    final sessionId = await showDialog<int>(
      context: context,
      builder: (ctx) {
        return StatefulBuilder(
          builder: (ctx, setModal) {
            return TichFormDialog(
              title: const Text('Manual class session'),
              scrollable: true,
              content: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  const Text(
                    'For classes outside the timetable. Requires an HOD-approved lesson plan for the unit on this date.',
                    style: TextStyle(fontSize: 12),
                  ),
                  const SizedBox(height: 12),
                  TichDropdownFormField<int>(
                    value: allocationId,
                    decoration: const InputDecoration(labelText: 'Unit'),
                    items: [
                      for (final a in allocations)
                        DropdownMenuItem(
                          value: (a['id'] as num).toInt(),
                          child: Text(a['label']?.toString() ?? 'Unit'),
                        ),
                    ],
                    onChanged: busy
                        ? null
                        : (v) {
                            if (v != null) setModal(() => allocationId = v);
                          },
                  ),
                  const SizedBox(height: 8),
                  ListTile(
                    contentPadding: EdgeInsets.zero,
                    title: const Text('Session date'),
                    subtitle: Text(formatDate(sessionDate)),
                    trailing: const Icon(Icons.calendar_today_outlined),
                    onTap: busy
                        ? null
                        : () async {
                            final picked = await pickDateOnly(
                              ctx,
                              initial: sessionDate,
                            );
                            if (picked != null) {
                              setModal(() => sessionDate = picked);
                            }
                          },
                  ),
                  TextField(
                    controller: venueController,
                    enabled: !busy,
                    decoration: const InputDecoration(labelText: 'Venue (optional)'),
                  ),
                ],
              ),
              actions: [
                TextButton(
                  onPressed: busy ? null : () => Navigator.pop(ctx),
                  child: const Text('Cancel'),
                ),
                FilledButton(
                  onPressed: busy
                      ? null
                      : () async {
                          setModal(() => busy = true);
                          try {
                            final body = <String, dynamic>{
                              'allocation_id': allocationId,
                              'session_date': dateApi.format(sessionDate),
                              if (venueController.text.trim().isNotEmpty)
                                'venue': venueController.text.trim(),
                            };
                            final res =
                                await widget.api.createAttendanceSession(body);
                            final session = res['session'] as Map?;
                            final id = (session?['id'] as num?)?.toInt();
                            if (!ctx.mounted) return;
                            Navigator.pop(ctx, id);
                          } on ApiException catch (e) {
                            if (ctx.mounted) {
                              ScaffoldMessenger.of(ctx).showSnackBar(
                                SnackBar(content: Text(e.message)),
                              );
                            }
                          } finally {
                            if (ctx.mounted) setModal(() => busy = false);
                          }
                        },
                  child: busy
                      ? const SizedBox(
                          width: 18,
                          height: 18,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : const Text('Create session'),
                ),
              ],
            );
          },
        );
      },
    );
    venueController.dispose();
    if (sessionId != null && mounted) {
      await openStaffAttendanceSession(context, widget.api, sessionId);
      _reload();
    }
  }

  Future<void> _reload() => staffCachedReload(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.attendance(),
      onData: (data) => _dataCache = data,
      persistKey: 'staff.attendance',
    );

  void _liveRefresh() {
    tichSilentReload(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.attendance(),
      onData: (data) => _dataCache = data,
      persistKey: 'staff.attendance',
    );
  }

  Future<void> _refreshPending() async {
    final count = await TichOfflineOutbox.instance.pendingCount();
    if (mounted) setState(() => _pendingField = count);
  }

  Future<void> _flushOffline() async {
    final client = context.read<ApiClient>();
    setState(() => _flushing = true);
    try {
      await TichBackgroundSync.instance.tryFlush(client, force: true);
      if (!mounted) return;
      await _refreshPending();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            _pendingField == 0
                ? 'Offline field data uploaded.'
                : 'Some items are still waiting to upload.',
          ),
        ),
      );
      _reload();
    } finally {
      if (mounted) setState(() => _flushing = false);
    }
  }

  Future<void> _sync() async {
    setState(() => _syncing = true);
    try {
      final data = await widget.api.syncAttendance();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(data['message']?.toString() ?? 'Synced.'),
        ),
      );
      setState(() => _dataCache = data);
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) setState(() => _syncing = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return TichLiveRefreshBinder(
      onLiveRefresh: _liveRefresh,
      child: AdaptiveScaffold(
      title: 'Class attendance',
      floatingActionButton: FloatingActionButton.extended(
        heroTag: 'staff-attendance-create',
        onPressed: () {
          final data = _dataCache as Map<String, dynamic>?;
          if (data == null) {
            _future.then((d) {
              if (mounted) _createManualSession(d);
            });
            return;
          }
          _createManualSession(data);
        },
        icon: const Icon(Icons.add),
        label: const Text('Manual session'),
      ),
      actions: [
        if (_pendingField > 0)
          IconButton(
            tooltip: 'Upload offline attendance & marks',
            onPressed: _flushing ? null : _flushOffline,
            icon: _flushing
                ? const SizedBox(
                    width: 18,
                    height: 18,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : Badge(
                    label: Text('$_pendingField'),
                    child: const Icon(Icons.cloud_upload_outlined),
                  ),
          ),
        IconButton(
          tooltip: 'Sync from timetable',
          onPressed: _syncing ? null : _sync,
          icon: _syncing
              ? const SizedBox(
                  width: 18,
                  height: 18,
                  child: CircularProgressIndicator(strokeWidth: 2),
                )
              : const Icon(Icons.sync),
        ),
      ],
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          final merged = tichMergeSnapshot(snap, _dataCache as dynamic, persistKey: 'staff.attendance');
          if (tichShowLoadingSpinner(snap, merged)) { return staffLoadingBody(onRefresh: () async => _reload()); }
          if (merged == null && snap.hasError) {
            return staffErrorBody(snap.error, _reload);
          }
          final data = merged! as Map<String, dynamic>;
          final sessions = data['sessions'] as List? ?? const [];
          final upcoming = data['upcoming'] as List? ?? const [];

          return RefreshIndicator(
            onRefresh: () async => _reload(),
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                const SizedBox(height: 12),
                if (_pendingField > 0)
                  TichCard(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Offline queue',
                          style: tichUi(fontWeight: FontWeight.w700),
                        ),
                        const SizedBox(height: 6),
                        Text(
                          '$_pendingField item(s) waiting to upload (attendance, marks, or photos).',
                          style: tichUi(
                            fontSize: 12,
                            color: TichColors.neutralMuted,
                          ),
                        ),
                        const SizedBox(height: 8),
                        FilledButton.icon(
                          onPressed: _flushing ? null : _flushOffline,
                          icon: const Icon(Icons.cloud_upload_outlined),
                          label: const Text('Upload now'),
                        ),
                      ],
                    ),
                  ),
                if (upcoming.isNotEmpty) ...[
                  const TichSectionHeader('Upcoming / recent'),
                  ...upcoming.take(6).map((raw) {
                    final s = Map<String, dynamic>.from(raw as Map);
                    final id = (s['id'] as num?)?.toInt();
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 8),
                      child: TichCard(
                        onTapDown: id == null
                            ? null
                            : (_) => StaffPrefetch.attendanceSession(
                                  widget.api,
                                  id,
                                ),
                        onTap: id == null ? null : () => _openSession(id),
                        child: _sessionTile(s),
                      ),
                    );
                  }),
                ],
                const TichSectionHeader('All sessions'),
                if (sessions.isEmpty)
                  const TichCard(
                    child: TichEmpty(
                      'No class sessions yet. Sync from timetable or create from an approved lesson plan.',
                    ),
                  )
                else
                  ...sessions.map((raw) {
                    final s = Map<String, dynamic>.from(raw as Map);
                    final id = (s['id'] as num?)?.toInt();
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 8),
                      child: TichCard(
                        onTapDown: id == null
                            ? null
                            : (_) => StaffPrefetch.attendanceSession(
                                  widget.api,
                                  id,
                                ),
                        onTap: id == null ? null : () => _openSession(id),
                        child: _sessionTile(s),
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

  Widget _sessionTile(Map<String, dynamic> s) {
    return Row(
      children: [
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                [
                  s['unit_code'],
                  s['unit_name'],
                ].where((e) => e != null && '$e'.isNotEmpty).join(' · '),
                style: tichUi(fontWeight: FontWeight.w700),
              ),
              const SizedBox(height: 4),
              Text(
                '${formatDate(s['session_date'])}'
                '${s['start_time'] != null ? ' · ${s['start_time'].toString().substring(0, s['start_time'].toString().length.clamp(0, 5))}' : ''}',
                style: tichUi(fontSize: 12, color: TichColors.neutralMuted),
              ),
            ],
          ),
        ),
        TichStatusChip(
          s['verification_status']?.toString() ??
              (s['is_locked'] == true ? 'locked' : 'open'),
          tone: s['is_locked'] == true
              ? TichChipTone.success
              : TichChipTone.info,
        ),
      ],
    );
  }
}
