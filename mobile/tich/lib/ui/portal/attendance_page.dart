import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';

import '../../adaptive/adaptive.dart';
import '../../data/api_client.dart';
import '../../data/employee_api.dart';
import '../../notifications/tich_notifications.dart';
import '../../permissions/tich_permission_disclosures.dart';
import '../../utils/tich_clock_in_messages.dart';
import '../../theme/tich_theme.dart';
import '../widgets/date_time_pickers.dart';
import '../widgets/tich_widgets.dart';
import '../shell/portal_shell.dart';
import '../../data/tich_screen_cache.dart';
import '../widgets/tich_live_refresh.dart';

class AttendancePage extends StatefulWidget {
  const AttendancePage({super.key, required this.api});

  final EmployeeApi api;

  @override
  State<AttendancePage> createState() => _AttendancePageState();
}

class _AttendancePageState extends State<AttendancePage> {
  late Future<Map<String, dynamic>> _future;
  Object? _dataCache;
  bool _busy = false;
  bool _offCampus = false;
  final _campusOther = TextEditingController();
  Object? _selectedCampusId;
  List<Map<String, dynamic>> _clockInSites = const [];

  @override
  void initState() {
    super.initState();
    _dataCache = TichScreenCache.read<Map<String, dynamic>>('portal.attendance');
    _future = TichScreenCache.ensure(
      'portal.attendance',
      () => widget.api.attendance(),
    ).then((data) {
      final today = data['today'] as Map?;
      final clockedIn = today != null && today['clock_in_at'] != null;
      // ignore: unawaited_futures
      TichNotifications.instance
          .scheduleClockInReminders(alreadyClockedIn: clockedIn);
      return data;
    });
  }

  @override
  void dispose() {
    _campusOther.dispose();
    super.dispose();
  }

  static const _otherSiteId = 'other';

  bool get _otherCampusSelected =>
      _selectedCampusId == _otherSiteId || _selectedCampusId == 'other';

  String? _validateSiteSelection() {
    if (!_offCampus) {
      return null;
    }
    if (_selectedCampusId == null) {
      return 'Select the campus or community college you are clocking in from.';
    }
    if (_otherCampusSelected && _campusOther.text.trim().isEmpty) {
      return 'Enter where you are reporting from when you select Other.';
    }
    return null;
  }

  List<DropdownMenuItem<Object?>> _campusDropdownItems() {
    return _clockInSites.map((site) {
      final id = site['id'];
      final value = id is int ? id : id?.toString();
      return DropdownMenuItem<Object?>(
        value: value,
        child: Text(
          site['label']?.toString() ?? '',
          maxLines: 2,
          overflow: TextOverflow.ellipsis,
          style: tichUi(fontWeight: FontWeight.w600),
        ),
      );
    }).toList();
  }

  Future<void> _reload() => tichRefreshCached<Map<String, dynamic>>(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.attendance(),
      onData: (data) => _dataCache = data,
      persistKey: 'portal.attendance',
    );

  void _liveRefresh() {
    tichSilentReload(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.attendance(),
      onData: (data) => _dataCache = data,
      persistKey: 'portal.attendance',
    );
  }

  Future<Map<String, dynamic>> _locationPayload() async {
    var permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
    }
    if (permission == LocationPermission.denied ||
        permission == LocationPermission.deniedForever) {
      throw ApiException('Location permission is required to clock in.');
    }

    final pos = await Geolocator.getCurrentPosition(
      locationSettings: const LocationSettings(accuracy: LocationAccuracy.high),
    );
    return {
      'clock_in_latitude': pos.latitude,
      'clock_in_longitude': pos.longitude,
      'clock_in_accuracy_m': pos.accuracy,
      'location_lat_long': '${pos.latitude},${pos.longitude}',
    };
  }

  Future<void> _clockIn() async {
    final siteError = _validateSiteSelection();
    if (siteError != null) {
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(siteError)));
      return;
    }

    if (!await TichPermissionDisclosures.confirmLocationForClockIn(context)) {
      return;
    }

    setState(() => _busy = true);
    try {
      final payload = <String, dynamic>{
        'is_off_campus': _offCampus,
        ...await _locationPayload(),
      };
      if (_offCampus) {
        if (_otherCampusSelected) {
          payload['clock_in_campus_id'] = _otherSiteId;
          payload['clock_in_campus_other'] = _campusOther.text.trim();
        } else {
          payload['clock_in_campus_id'] = _selectedCampusId;
        }
      }
      await widget.api.clockIn(payload);
      await TichNotifications.instance
          .scheduleClockInReminders(alreadyClockedIn: true);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Clocked in.')),
      );
      _reload();
      EmployeePortalNavSignals.onAttendanceChanged?.call();
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(
              content: Text(tichFormatClockInDistanceMessage(e.message))));
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text('$e')));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _clockOut() async {
    setState(() => _busy = true);
    try {
      await widget.api.clockOut();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Clocked out.')),
      );
      _reload();
      EmployeePortalNavSignals.onAttendanceChanged?.call();
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(
              content: Text(tichFormatClockInDistanceMessage(e.message))));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Widget _stampRow(String label, dynamic value) {
    final hasValue = parseAppDateTime(value) != null;
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        children: [
          SizedBox(
            width: 36,
            child: Text(label, style: tichUi(fontWeight: FontWeight.w700)),
          ),
          Expanded(
            child: Text(
              hasValue ? formatDate(value) : '-',
              style: tichUi(fontWeight: FontWeight.w600),
            ),
          ),
          Text(
            hasValue ? formatTime(value) : '',
            style: tichUi(
              fontWeight: FontWeight.w700,
              color: TichColors.blueDark,
            ),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return TichLiveRefreshBinder(
      onLiveRefresh: _liveRefresh,
      child: AdaptiveScaffold(
      title: 'Clock in / out',
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          final merged = tichMergeSnapshot(snap, _dataCache as dynamic, persistKey: 'portal.attendance');
          if (tichShowLoadingSpinner(snap, merged)) { return tichInstantWaitBody(onRefresh: () async => _reload()); }
          if (merged == null && snap.hasError) {
            return TichErrorRefresh(
              message: snap.error.toString(),
              onRetry: _reload,
            );
          }
          final data = merged! as Map<String, dynamic>;
          final sites = data['clock_in_sites'] as List? ?? const [];
          if (!identical(sites, _clockInSites)) {
            _clockInSites = sites
                .map((e) => Map<String, dynamic>.from(e as Map))
                .toList();
          }
          final today = data['today'] as Map?;
          final recent = data['recent'] as List? ?? const [];
          final clockedIn = today != null && today['clock_in_at'] != null;
          final clockedOut = today != null && today['clock_out_at'] != null;

          return RefreshIndicator(
            onRefresh: () async => _reload(),
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                TichCard(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Today · ${formatDate(DateTime.now())}',
                        style: TichTheme.display(fontSize: 16),
                      ),
                      const SizedBox(height: 12),
                      if (today == null)
                        Text(
                          'Not clocked in yet.',
                          style: tichUi(color: TichColors.neutralMuted),
                        )
                      else ...[
                        _stampRow('In', today['clock_in_at']),
                        _stampRow('Out', today['clock_out_at']),
                      ],
                      const SizedBox(height: 12),
                      SwitchListTile(
                        contentPadding: EdgeInsets.zero,
                        title: const Text('Off campus (not at main campus)'),
                        subtitle: Text(
                          'Turn on when clocking in from another campus, community college, or other site.',
                          style: tichUi(
                            fontSize: 12,
                            color: TichColors.neutralMuted,
                          ),
                        ),
                        value: _offCampus,
                        onChanged: _busy
                            ? null
                            : (v) => setState(() {
                                  _offCampus = v;
                                  if (!v) {
                                    _selectedCampusId = null;
                                    _campusOther.clear();
                                  }
                                }),
                      ),
                      if (_offCampus && _clockInSites.isNotEmpty) ...[
                        TichDropdownFormField<Object?>(
                          value: _selectedCampusId,
                          hint: const Text('Campus / community college'),
                          decoration: const InputDecoration(
                            labelText: 'Where you are clocking in from',
                          ),
                          items: _campusDropdownItems(),
                          onChanged: _busy
                              ? null
                              : (v) => setState(() => _selectedCampusId = v),
                        ),
                        if (_otherCampusSelected)
                          TextField(
                            controller: _campusOther,
                            enabled: !_busy,
                            decoration: const InputDecoration(
                              labelText: 'Other location',
                            ),
                          ),
                      ],
                      const SizedBox(height: 12),
                      if (!clockedIn || clockedOut)
                        FilledButton(
                          onPressed: _busy ? null : _clockIn,
                          child: const Text('Clock in'),
                        )
                      else
                        FilledButton(
                          style: FilledButton.styleFrom(
                            backgroundColor: TichColors.green,
                          ),
                          onPressed: _busy ? null : _clockOut,
                          child: const Text('Clock out'),
                        ),
                    ],
                  ),
                ),
                const SizedBox(height: 16),
                const TichSectionHeader('Recent'),
                if (recent.isEmpty)
                  const TichCard(child: TichEmpty('No attendance records.'))
                else
                  ...recent.map((raw) {
                    final r = Map<String, dynamic>.from(raw as Map);
                    final dateLabel = formatDate(
                      r['clock_in_at'] ?? r['attendance_date'],
                    );
                    final inTime = r['clock_in_at'] != null
                        ? formatTime(r['clock_in_at'])
                        : '-';
                    final outTime = r['clock_out_at'] != null
                        ? formatTime(r['clock_out_at'])
                        : '-';
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 8),
                      child: TichCard(
                        child: Row(
                          children: [
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    dateLabel,
                                    style: tichUi(fontWeight: FontWeight.w700),
                                  ),
                                  const SizedBox(height: 2),
                                  Text(
                                    r['reporting_site_label']?.toString() ??
                                        (r['is_off_campus'] == true
                                            ? 'Field'
                                            : 'Campus'),
                                    style: tichUi(
                                      fontSize: 12,
                                      color: TichColors.neutralMuted,
                                    ),
                                    maxLines: 2,
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                ],
                              ),
                            ),
                            Text(
                              '$inTime – $outTime',
                              style: tichUi(
                                fontWeight: FontWeight.w600,
                                fontSize: 13,
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
