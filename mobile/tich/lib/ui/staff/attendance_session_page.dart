import 'dart:async';

import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../data/api_client.dart';
import '../../data/staff_api.dart';
import '../../theme/tich_theme.dart';
import '../widgets/date_time_pickers.dart';
import '../widgets/tich_document_capture.dart';
import '../widgets/tich_live_refresh.dart';
import '../widgets/tich_widgets.dart';
import 'staff_helpers.dart';

class StaffAttendanceSessionPage extends StatefulWidget {
  const StaffAttendanceSessionPage({
    super.key,
    required this.api,
    required this.sessionId,
  });

  final StaffApi api;
  final int sessionId;

  @override
  State<StaffAttendanceSessionPage> createState() =>
      _StaffAttendanceSessionPageState();
}

class _StaffAttendanceSessionPageState
    extends State<StaffAttendanceSessionPage> {
  late Future<Map<String, dynamic>> _future;
  Object? _dataCache;
  final Set<int> _present = {};
  bool _busy = false;
  bool _locked = false;

  String get _cacheKey => 'staff.attendance_session.${widget.sessionId}';

  @override
  void initState() {
    super.initState();
    _dataCache = staffReadCache(_cacheKey);
    _future = _fetchSession();
  }

  Future<Map<String, dynamic>> _fetchSession() {
    return widget.api.attendanceSession(widget.sessionId).then((data) {
      final records = data['records'] as List? ?? const [];
      _present
        ..clear()
        ..addAll(
          records
              .map((e) => Map<String, dynamic>.from(e as Map))
              .where((r) => r['is_present'] == true)
              .map((r) => (r['student_id'] as num).toInt()),
        );
      final session =
          Map<String, dynamic>.from(data['session'] as Map? ?? {});
      _locked = session['is_locked'] == true;
      return data;
    });
  }

  Future<void> _reload() => staffCachedReload(
        mounted: () => mounted,
        setState: setState,
        fetch: _fetchSession,
        onData: (data) => _dataCache = data,
        persistKey: _cacheKey,
      );

  void _liveRefresh() {
    if (_busy) return;
    tichSilentReload(
      mounted: () => mounted,
      setState: setState,
      fetch: _fetchSession,
      onData: (data) => _dataCache = data,
      persistKey: _cacheKey,
    );
  }

  Future<void> _uploadSignedSheet() async {
    final path = await captureScannedDocument(context);
    if (path == null || !mounted) return;
    setState(() => _busy = true);
    try {
      await widget.api.uploadSignedSheet(widget.sessionId, path);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Signed attendance sheet uploaded.')),
      );
      _reload();
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _uploadClassPhoto() async {
    final path = await captureClassPhoto(context);
    if (path == null || !mounted) return;
    setState(() => _busy = true);
    try {
      await widget.api.uploadClassPhoto(widget.sessionId, path);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Class photo uploaded.')),
      );
      _reload();
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _save({bool lock = false}) async {
    setState(() => _busy = true);
    try {
      await widget.api.saveAttendance(
        widget.sessionId,
        presentStudentIds: _present.toList(),
        lock: lock,
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(lock ? 'Attendance submitted.' : 'Roster saved.'),
        ),
      );
      _reload();
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return TichLiveRefreshBinder(
      onLiveRefresh: _liveRefresh,
      enabled: !_busy,
      child: AdaptiveScaffold(
      title: 'Attendance session',
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          final merged = tichMergeSnapshot(
            snap,
            _dataCache as dynamic,
            persistKey: _cacheKey,
          );
          if (tichShowLoadingSpinner(snap, merged)) {
            return staffLoadingBody(onRefresh: () async => _reload());
          }
          if (merged == null && snap.hasError) {
            return staffErrorBody(snap.error, _reload);
          }
          final payload = merged! as Map<String, dynamic>;
          final session =
              Map<String, dynamic>.from(payload['session'] as Map? ?? {});
          final records = payload['records'] as List? ?? const [];

          final hasSheet = session['has_signed_sheet'] == true;
          final hasPhoto = session['has_class_photo'] == true;
          final sheetUrl = session['signed_sheet_url']?.toString();
          final photoUrl = session['class_photo_url']?.toString();

          return Column(
            children: [
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
                child: TichCard(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        [
                          session['unit_code'],
                          session['unit_name'],
                        ]
                            .where((e) => e != null && '$e'.isNotEmpty)
                            .join(' · '),
                        style: TichTheme.display(fontSize: 16),
                      ),
                      const SizedBox(height: 6),
                      Text(
                        '${formatDate(session['session_date'])}'
                        ' · ${session['start_time'] ?? '—'} – ${session['end_time'] ?? '—'}',
                        style: tichUi(fontSize: 13),
                      ),
                      if ((session['venue']?.toString() ?? '').isNotEmpty) ...[
                        const SizedBox(height: 4),
                        Text(
                          'Venue: ${session['venue']}',
                          style: tichUi(
                            fontSize: 12,
                            color: TichColors.neutralMuted,
                          ),
                        ),
                      ],
                      const SizedBox(height: 8),
                      Text(
                        '${_present.length} / ${records.length} present',
                        style: tichUi(fontWeight: FontWeight.w700),
                      ),
                    ],
                  ),
                ),
              ),
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
                child: TichCard(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Text(
                        'Verification photos',
                        style: TichTheme.display(fontSize: 15),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        'Scan the signed paper sheet (document mode), then optionally add a class photo.',
                        style: tichUi(
                          fontSize: 12,
                          color: TichColors.neutralMuted,
                        ),
                      ),
                      const SizedBox(height: 12),
                      _AttendanceCaptureTile(
                        title: 'Signed attendance sheet',
                        subtitle: hasSheet
                            ? 'Uploaded — scan again to replace'
                            : 'Required before submit',
                        requiredForSubmit: true,
                        uploaded: hasSheet,
                        imageUrl: sheetUrl,
                        locked: _locked,
                        busy: _busy,
                        onCapture: _uploadSignedSheet,
                        captureLabel: 'Scan sheet',
                        icon: Icons.document_scanner_outlined,
                      ),
                      const SizedBox(height: 10),
                      _AttendanceCaptureTile(
                        title: 'Class photo',
                        subtitle: hasPhoto
                            ? 'Uploaded — retake to replace'
                            : 'Optional photo of students present',
                        requiredForSubmit: false,
                        uploaded: hasPhoto,
                        imageUrl: photoUrl,
                        locked: _locked,
                        busy: _busy,
                        onCapture: _uploadClassPhoto,
                        captureLabel: 'Take photo',
                        icon: Icons.photo_camera_outlined,
                      ),
                    ],
                  ),
                ),
              ),
              Expanded(
                child: ListView.separated(
                  padding: const EdgeInsets.symmetric(horizontal: 16),
                  itemCount: records.length,
                  separatorBuilder: (_, _) => const SizedBox(height: 6),
                  itemBuilder: (context, i) {
                    final r = Map<String, dynamic>.from(records[i] as Map);
                    final id = (r['student_id'] as num).toInt();
                    final checked = _present.contains(id);
                    return TichCard(
                      child: CheckboxListTile(
                        contentPadding: EdgeInsets.zero,
                        value: checked,
                        onChanged: _locked || _busy
                            ? null
                            : (v) {
                                setState(() {
                                  if (v == true) {
                                    _present.add(id);
                                  } else {
                                    _present.remove(id);
                                  }
                                });
                              },
                        title: Text(
                          r['student_name']?.toString() ?? 'Student',
                          style: tichUi(fontWeight: FontWeight.w600),
                        ),
                        subtitle: Text(
                          r['registration_number']?.toString() ?? '',
                          style: tichUi(fontSize: 12),
                        ),
                      ),
                    );
                  },
                ),
              ),
              if (!_locked)
                SafeArea(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Row(
                      children: [
                        Expanded(
                          child: OutlinedButton(
                            onPressed: _busy ? null : () => _save(),
                            child: const Text('Save roster'),
                          ),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: FilledButton(
                            onPressed: _busy || !hasSheet
                                ? null
                                : () => _save(lock: true),
                            child: Text(
                              hasSheet ? 'Submit' : 'Submit (needs sheet)',
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
            ],
          );
        },
      ),
      ),
    );
  }
}

class _AttendanceCaptureTile extends StatelessWidget {
  const _AttendanceCaptureTile({
    required this.title,
    required this.subtitle,
    required this.requiredForSubmit,
    required this.uploaded,
    required this.imageUrl,
    required this.locked,
    required this.busy,
    required this.onCapture,
    required this.captureLabel,
    required this.icon,
  });

  final String title;
  final String subtitle;
  final bool requiredForSubmit;
  final bool uploaded;
  final String? imageUrl;
  final bool locked;
  final bool busy;
  final VoidCallback onCapture;
  final String captureLabel;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Icon(
              uploaded ? Icons.check_circle : icon,
              color: uploaded ? TichColors.greenDark : TichColors.blue,
              size: 22,
            ),
            const SizedBox(width: 10),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          title,
                          style: tichUi(fontWeight: FontWeight.w700),
                        ),
                      ),
                      if (requiredForSubmit)
                        Text(
                          'Required',
                          style: tichUi(
                            fontSize: 11,
                            fontWeight: FontWeight.w700,
                            color: TichColors.errorDark,
                          ),
                        ),
                    ],
                  ),
                  const SizedBox(height: 2),
                  Text(
                    subtitle,
                    style: tichUi(
                      fontSize: 12,
                      color: TichColors.neutralMuted,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
        if (imageUrl != null && imageUrl!.isNotEmpty) ...[
          const SizedBox(height: 8),
          ClipRRect(
            borderRadius: TichRadius.borderRadiusMd,
            child: Image.network(
              imageUrl!,
              height: 120,
              width: double.infinity,
              fit: BoxFit.cover,
              errorBuilder: (_, _, _) => const SizedBox.shrink(),
            ),
          ),
        ],
        if (!locked) ...[
          const SizedBox(height: 8),
          OutlinedButton.icon(
            onPressed: busy ? null : onCapture,
            icon: Icon(icon, size: 20),
            label: Text(uploaded ? 'Replace' : captureLabel),
          ),
        ],
      ],
    );
  }
}
