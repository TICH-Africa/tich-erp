import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../data/student_api.dart';
import '../../theme/tich_theme.dart';
import '../widgets/date_time_pickers.dart';
import '../widgets/tich_widgets.dart';
import '../widgets/tich_live_refresh.dart';
import 'student_helpers.dart';

class StudentEnrolmentPage extends StatefulWidget {
  const StudentEnrolmentPage({super.key, required this.api});

  final StudentApi api;

  @override
  State<StudentEnrolmentPage> createState() => _StudentEnrolmentPageState();
}

class _StudentEnrolmentPageState extends State<StudentEnrolmentPage> {
  late Future<Map<String, dynamic>> _future;
  Object? _dataCache;

  @override
  void initState() {
    super.initState();
    studentPrimeScreen(
      persistKey: 'student.enrolment',
      setCache: (c) => _dataCache = c,
      setFuture: (f) => _future = f,
      fetch: () => widget.api.profile(),
    );
  }

  Future<void> _reload() => studentCachedReload(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.profile(),
      onData: (data) => _dataCache = data,
      persistKey: 'student.enrolment',
    );

  void _liveRefresh() {
    tichSilentReload(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.profile(),
      onData: (data) => _dataCache = data,
      persistKey: 'student.enrolment',
    );
  }

  Widget _kv(String label, dynamic value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 120,
            child: Text(label,
                style: tichUi(color: TichColors.neutralMuted, fontSize: 13)),
          ),
          Expanded(
            child: Text(
              value?.toString().isNotEmpty == true ? value.toString() : '-',
              style: tichUi(fontWeight: FontWeight.w600, fontSize: 13),
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
      title: 'Enrolment',
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          final merged = tichMergeSnapshot(snap, _dataCache as dynamic, persistKey: 'student.enrolment');
          if (tichShowLoadingSpinner(snap, merged)) { return studentLoadingBody(onRefresh: () async => _reload()); }
          if (merged == null && snap.hasError) {
            return studentErrorBody(snap.error, _reload);
          }
          final data = merged! as Map<String, dynamic>;
          final biodata =
              Map<String, dynamic>.from(data['biodata'] as Map? ?? {});
          final academic =
              Map<String, dynamic>.from(biodata['academic'] as Map? ?? {});
          final enrollment =
              Map<String, dynamic>.from(biodata['enrollment'] as Map? ?? {});
          final application =
              Map<String, dynamic>.from(biodata['application'] as Map? ?? {});
          final identity =
              Map<String, dynamic>.from(biodata['identity'] as Map? ?? {});

          return RefreshIndicator(
            onRefresh: () async => _reload(),
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                const TichSectionHeader('Academic'),
                TichCard(
                  child: Column(
                    children: [
                      _kv('Registration', academic['registration_number']),
                      _kv('Program', academic['program']),
                      _kv('Department', academic['department']),
                      _kv('Intake', academic['cohort_intake']),
                      _kv('Year of study', academic['year_of_study']),
                      _kv('Semester', academic['current_semester']),
                      _kv('Entry pathway', academic['entry_pathway']),
                    ],
                  ),
                ),
                const SizedBox(height: 16),
                const TichSectionHeader('Enrolment'),
                TichCard(
                  child: Column(
                    children: [
                      _kv('Status', enrollment['enrollment_status']),
                      _kv('Campus', enrollment['campus']),
                      _kv('Admitted', formatDateTime(enrollment['date_of_admission'])),
                      _kv('Fee clearance', enrollment['fee_clearance_status']),
                      _kv('Balance', money(enrollment['overall_balance'])),
                      _kv('Portal activated',
                          formatDateTime(enrollment['portal_activated_at'])),
                    ],
                  ),
                ),
                const SizedBox(height: 16),
                const TichSectionHeader('Application'),
                TichCard(
                  child: Column(
                    children: [
                      _kv('Application #', application['application_number']),
                      _kv('Status', application['status']),
                      _kv('Preferred campus', application['preferred_campus']),
                      _kv('Sponsorship', application['sponsorship_type']),
                      _kv('Gender', identity['gender']),
                      _kv('Nationality', identity['nationality']),
                    ],
                  ),
                ),
              ],
            ),
          );
        },
      ),
      ),
    );
  }
}
