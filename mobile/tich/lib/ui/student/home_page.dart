import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

import '../../adaptive/adaptive.dart';
import '../../data/student_api.dart';
import '../../theme/tich_theme.dart';
import '../widgets/date_time_pickers.dart';
import '../widgets/tich_widgets.dart';
import 'notifications_page.dart';
import 'student_helpers.dart';

class StudentHomePage extends StatefulWidget {
  const StudentHomePage({super.key, required this.api});

  final StudentApi api;

  @override
  State<StudentHomePage> createState() => _StudentHomePageState();
}

class _StudentHomePageState extends State<StudentHomePage> {
  late Future<Map<String, dynamic>> _future;
  int _unread = 0;

  @override
  void initState() {
    super.initState();
    _reload();
  }

  void _reload() {
    setState(() {
      _future = widget.api.dashboard().then((data) {
        _unread = (data['unread_notifications'] as num?)?.toInt() ?? 0;
        return data;
      });
    });
  }

  Widget _notificationAction() {
    final icon =
        usesCupertino ? CupertinoIcons.bell : Icons.notifications_outlined;
    return Stack(
      clipBehavior: Clip.none,
      children: [
        IconButton(
          tooltip: 'Notifications',
          onPressed: () async {
            await Navigator.of(context).push(
              MaterialPageRoute(
                builder: (_) => StudentNotificationsPage(api: widget.api),
              ),
            );
            _reload();
          },
          icon: Icon(icon, color: usesCupertino ? TichColors.white : null),
        ),
        if (_unread > 0)
          Positioned(
            right: 6,
            top: 6,
            child: Container(
              constraints: const BoxConstraints(minWidth: 16),
              height: 16,
              padding: const EdgeInsets.symmetric(horizontal: 4),
              alignment: Alignment.center,
              decoration: BoxDecoration(
                color: Colors.red.shade600,
                borderRadius: BorderRadius.circular(999),
              ),
              child: Text(
                _unread > 99 ? '99+' : '$_unread',
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 9,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ),
          ),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'Student Portal',
      actions: [_notificationAction()],
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          if (snap.connectionState != ConnectionState.done) {
            return studentLoadingBody();
          }
          if (snap.hasError) {
            return studentErrorBody(snap.error, _reload);
          }
          final data = snap.data!;
          final student =
              Map<String, dynamic>.from(data['student'] as Map? ?? {});
          final stats =
              Map<String, dynamic>.from(data['overview_stats'] as Map? ?? {});
          final finance =
              Map<String, dynamic>.from(data['finance_summary'] as Map? ?? {});
          final preview = data['notifications_preview'] as List? ?? const [];

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
                        student['full_name']?.toString() ?? 'Student',
                        style: GoogleFonts.merriweather(
                          fontSize: 18,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        [
                          student['registration_number'],
                          student['program'],
                        ]
                            .where((e) => e != null && '$e'.isNotEmpty)
                            .join(' · '),
                        style: tichUi(
                          color: TichColors.neutralMuted,
                          fontSize: 13,
                        ),
                      ),
                      const SizedBox(height: 8),
                      Wrap(
                        spacing: 8,
                        runSpacing: 8,
                        children: [
                          TichStatusChip(
                            stats['enrollment_status']?.toString() ??
                                student['enrollment_status']?.toString() ??
                                'enrolled',
                            tone: TichChipTone.info,
                          ),
                          TichStatusChip(
                            'Fees: ${stats['fee_clearance'] ?? finance['fee_clearance_status'] ?? 'pending'}',
                            tone: toneForStatus(
                              '${stats['fee_clearance'] ?? finance['fee_clearance_status']}',
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 16),
                const TichSectionHeader('Overview'),
                Row(
                  children: [
                    StudentStatTile(
                      label: 'Outstanding',
                      value: money(
                        stats['outstanding_balance'] ??
                            finance['outstanding_balance'],
                      ),
                    ),
                    const SizedBox(width: 8),
                    StudentStatTile(
                      label: 'Units',
                      value: '${stats['registered_unit_count'] ?? 0}',
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                Row(
                  children: [
                    StudentStatTile(
                      label: 'Grades',
                      value: '${stats['grade_count'] ?? 0}',
                    ),
                    const SizedBox(width: 8),
                    StudentStatTile(
                      label: 'Documents',
                      value: '${stats['document_count'] ?? 0}',
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                const TichSectionHeader('Recent notifications'),
                if (preview.isEmpty)
                  const TichCard(child: TichEmpty('No notifications yet.'))
                else
                  ...preview.map((raw) {
                    final n = Map<String, dynamic>.from(raw as Map);
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 8),
                      child: TichCard(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              n['title']?.toString() ?? 'Notification',
                              style: tichUi(fontWeight: FontWeight.w600),
                            ),
                            if ((n['body']?.toString() ?? '').isNotEmpty) ...[
                              const SizedBox(height: 4),
                              Text(
                                n['body'].toString(),
                                style: tichUi(
                                  fontSize: 13,
                                  color: TichColors.neutralMuted,
                                ),
                              ),
                            ],
                            const SizedBox(height: 6),
                            Text(
                              formatDateTime(n['created_at']),
                              style: tichUi(fontSize: 11),
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
