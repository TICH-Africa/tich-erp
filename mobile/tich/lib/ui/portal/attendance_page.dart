import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:google_fonts/google_fonts.dart';

import '../../data/api_client.dart';
import '../../data/employee_api.dart';
import '../../theme/tich_theme.dart';
import '../../adaptive/adaptive.dart';
import '../shell/portal_shell.dart';
import '../widgets/tich_widgets.dart';

class AttendancePage extends StatefulWidget {
  const AttendancePage({super.key, required this.api});

  final EmployeeApi api;

  @override
  State<AttendancePage> createState() => _AttendancePageState();
}

class _AttendancePageState extends State<AttendancePage> {
  late Future<Map<String, dynamic>> _future;
  bool _busy = false;
  bool _offCampus = false;
  final _project = TextEditingController();

  @override
  void initState() {
    super.initState();
    _future = widget.api.attendance();
  }

  @override
  void dispose() {
    _project.dispose();
    super.dispose();
  }

  void _reload() => setState(() => _future = widget.api.attendance());

  Future<Map<String, dynamic>> _locationPayload() async {
    final requireLocation = true;
    var permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
    }
    if (permission == LocationPermission.denied ||
        permission == LocationPermission.deniedForever) {
      if (!requireLocation) return {};
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
    setState(() => _busy = true);
    try {
      final payload = <String, dynamic>{
        'is_off_campus': _offCampus,
        if (_offCampus && _project.text.trim().isNotEmpty)
          'field_project_name': _project.text.trim(),
        ...await _locationPayload(),
      };
      await widget.api.clockIn(payload);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Clocked in.')),
      );
      _reload();
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
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
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'Clock in / out',
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
                        'Today',
                        style: GoogleFonts.merriweather(
                          fontWeight: FontWeight.w700,
                          fontSize: 16,
                        ),
                      ),
                      const SizedBox(height: 8),
                      if (today == null)
                        Text(
                          'Not clocked in yet.',
                          style: tichUi(color: TichColors.neutralMuted),
                        )
                      else ...[
                        Text('In: ${today['clock_in_at'] ?? '—'}'),
                        Text('Out: ${today['clock_out_at'] ?? '—'}'),
                      ],
                      const SizedBox(height: 12),
                      SwitchListTile(
                        contentPadding: EdgeInsets.zero,
                        title: const Text('Off campus / field work'),
                        value: _offCampus,
                        onChanged: _busy
                            ? null
                            : (v) => setState(() => _offCampus = v),
                      ),
                      if (_offCampus)
                        TextField(
                          controller: _project,
                          enabled: !_busy,
                          decoration: const InputDecoration(
                            labelText: 'Field / project name',
                          ),
                        ),
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
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 8),
                      child: TichCard(
                        child: Row(
                          children: [
                            Expanded(
                              child: Text(
                                r['attendance_date']?.toString() ?? '',
                                style: tichUi(fontWeight: FontWeight.w600),
                              ),
                            ),
                            Text(
                              r['is_off_campus'] == true ? 'Field' : 'Campus',
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
    );
  }
}
