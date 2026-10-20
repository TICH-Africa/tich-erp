import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../bloc/auth/auth_bloc.dart';
import '../bloc/auth/auth_state.dart';
import '../data/employee_api.dart';
import '../data/staff_api.dart';
import '../models/auth_user.dart';
import '../ui/portal/appraisals_page.dart';
import '../ui/portal/attendance_page.dart';
import '../ui/portal/concerns_page.dart';
import '../ui/portal/documents_page.dart';
import '../ui/portal/feedback_page.dart';
import '../ui/portal/leave_page.dart';
import '../ui/portal/notifications_page.dart';
import '../ui/portal/policies_page.dart';
import '../ui/portal/profile_page.dart';
import '../ui/shell/employee_portal_nav.dart';
import '../ui/shell/staff_portal_nav.dart';
import '../ui/staff/timetable_page.dart';

enum TichDeepLinkTarget {
  employeeAttendance,
  employeeLeave,
  employeeHome,
  employeeNotifications,
  employeeProfile,
  employeeDocuments,
  employeePolicies,
  employeeConcerns,
  employeeFeedback,
  employeeAppraisals,
  staffTimetable,
  staffAttendance,
}

class TichDeepLink {
  const TichDeepLink(this.target, {this.actionUrl});

  final TichDeepLinkTarget target;
  final String? actionUrl;
}

/// Queues notification taps (lock screen, tray, in-app) until the user is unlocked.
class TichNotificationRouter {
  TichNotificationRouter._();
  static final TichNotificationRouter instance = TichNotificationRouter._();

  TichDeepLink? _pending;
  void Function()? onPendingEnqueued;

  void enqueuePayload(String? raw) {
    if (raw == null || raw.trim().isEmpty) return;
    final link = _parsePayload(raw.trim());
    if (link != null) {
      _pending = link;
      onPendingEnqueued?.call();
    }
  }

  void enqueueInApp({
    String? actionUrl,
    String? relatedEntityType,
    String? sidebarKey,
  }) {
    final link = _parseInApp(
      actionUrl: actionUrl,
      relatedEntityType: relatedEntityType,
      sidebarKey: sidebarKey,
    );
    if (link != null) {
      _pending = link;
      onPendingEnqueued?.call();
    }
  }

  TichDeepLink? peekPending() => _pending;

  TichDeepLink? consumePending() {
    final link = _pending;
    _pending = null;
    return link;
  }

  /// Call after [AuthAuthenticated] (and from portal shells once mounted).
  void applyPending(BuildContext context) {
    final auth = context.read<AuthBloc>().state;
    if (auth is! AuthAuthenticated) return;
    final link = consumePending();
    if (link == null) return;
    _navigate(context, auth.user, link);
  }

  void navigateFromInApp(
    BuildContext context, {
    String? actionUrl,
    String? relatedEntityType,
    String? sidebarKey,
  }) {
    enqueueInApp(
      actionUrl: actionUrl,
      relatedEntityType: relatedEntityType,
      sidebarKey: sidebarKey,
    );
    applyPending(context);
  }

  void _navigate(BuildContext context, AuthUser user, TichDeepLink link) {
    if (user.isStudent) return;

    final employeeApi = context.read<EmployeeApi>();
    final staffApi = context.read<StaffApi>();

    void push(Widget page) {
      Navigator.of(context).push(MaterialPageRoute(builder: (_) => page));
    }

    switch (link.target) {
      case TichDeepLinkTarget.employeeAttendance:
        if (user.canAccessEmployeePortal) {
          if (EmployeePortalNav.isEmployeeShellActive) {
            EmployeePortalNav.requestTab(2);
          } else {
            push(AttendancePage(api: employeeApi));
          }
        }
        break;
      case TichDeepLinkTarget.employeeLeave:
        if (user.canAccessEmployeePortal) {
          if (EmployeePortalNav.isEmployeeShellActive) {
            EmployeePortalNav.requestTab(1);
          } else {
            push(LeavePage(api: employeeApi));
          }
        }
        break;
      case TichDeepLinkTarget.employeeHome:
        if (user.canAccessEmployeePortal && EmployeePortalNav.isEmployeeShellActive) {
          EmployeePortalNav.requestTab(0);
        }
        break;
      case TichDeepLinkTarget.employeeNotifications:
        if (user.canAccessEmployeePortal) {
          push(NotificationsPage(api: employeeApi));
        }
        break;
      case TichDeepLinkTarget.employeeProfile:
        if (user.canAccessEmployeePortal) {
          push(ProfilePage(api: employeeApi));
        }
        break;
      case TichDeepLinkTarget.employeeDocuments:
        if (user.canAccessEmployeePortal) {
          push(DocumentsPage(api: employeeApi));
        }
        break;
      case TichDeepLinkTarget.employeePolicies:
        if (user.canAccessEmployeePortal) {
          push(PoliciesPage(api: employeeApi));
        }
        break;
      case TichDeepLinkTarget.employeeConcerns:
        if (user.canAccessEmployeePortal) {
          push(ConcernsPage(api: employeeApi));
        }
        break;
      case TichDeepLinkTarget.employeeFeedback:
        if (user.canAccessEmployeePortal) {
          push(FeedbackPage(api: employeeApi));
        }
        break;
      case TichDeepLinkTarget.employeeAppraisals:
        if (user.canAccessEmployeePortal) {
          push(AppraisalsPage(api: employeeApi));
        }
        break;
      case TichDeepLinkTarget.staffTimetable:
        if (user.canAccessStaffPortal) {
          if (StaffPortalNav.isStaffShellActive) {
            StaffPortalNav.requestTab(2);
          } else {
            push(StaffTimetablePage(api: staffApi));
          }
        }
        break;
      case TichDeepLinkTarget.staffAttendance:
        if (user.canAccessStaffPortal) {
          StaffPortalNav.requestTab(3);
        }
        break;
    }
  }

  TichDeepLink? _parsePayload(String raw) {
    if (raw == 'attendance') {
      return const TichDeepLink(TichDeepLinkTarget.employeeAttendance);
    }
    if (raw == 'timetable') {
      return const TichDeepLink(TichDeepLinkTarget.staffTimetable);
    }
    try {
      final decoded = jsonDecode(raw);
      if (decoded is Map) {
        return _parseInApp(
          actionUrl: decoded['action_url']?.toString(),
          relatedEntityType: decoded['entity_type']?.toString(),
          sidebarKey: decoded['key']?.toString(),
        );
      }
    } catch (_) {}
    return _parseActionUrl(raw);
  }

  TichDeepLink? _parseInApp({
    String? actionUrl,
    String? relatedEntityType,
    String? sidebarKey,
  }) {
    final fromKey = _fromSidebarKey(sidebarKey);
    if (fromKey != null) return fromKey;

    final fromEntity = _fromEntityType(relatedEntityType);
    if (fromEntity != null) return fromEntity;

    return _parseActionUrl(actionUrl);
  }

  TichDeepLink? _fromSidebarKey(String? key) {
    return switch (key) {
      'attendance_clock_in' => const TichDeepLink(TichDeepLinkTarget.employeeAttendance),
      'leave.returned' => const TichDeepLink(TichDeepLinkTarget.employeeLeave),
      'profile-changes' => const TichDeepLink(TichDeepLinkTarget.employeeProfile),
      'policies' => const TichDeepLink(TichDeepLinkTarget.employeePolicies),
      'appraisals' => const TichDeepLink(TichDeepLinkTarget.employeeAppraisals),
      'concerns' => const TichDeepLink(TichDeepLinkTarget.employeeConcerns),
      'feedback' => const TichDeepLink(TichDeepLinkTarget.employeeFeedback),
      'lesson-plans' => const TichDeepLink(TichDeepLinkTarget.staffTimetable),
      'attendance.incomplete' => const TichDeepLink(TichDeepLinkTarget.staffAttendance),
      _ => null,
    };
  }

  TichDeepLink? _fromEntityType(String? type) {
    return switch (type) {
      'attendance_clock_in' => const TichDeepLink(TichDeepLinkTarget.employeeAttendance),
      'timetable_lesson' => const TichDeepLink(TichDeepLinkTarget.staffTimetable),
      'leave_request' => const TichDeepLink(TichDeepLinkTarget.employeeLeave),
      'employee_concern' => const TichDeepLink(TichDeepLinkTarget.employeeConcerns),
      'staff_profile_change' => const TichDeepLink(TichDeepLinkTarget.employeeProfile),
      _ => null,
    };
  }

  TichDeepLink? _parseActionUrl(String? url) {
    if (url == null || url.trim().isEmpty) return null;
    final path = Uri.tryParse(url)?.path ?? url;
    final lower = path.toLowerCase();

    if (lower.contains('attendance')) {
      if (lower.contains('staff') && !lower.contains('employee')) {
        return const TichDeepLink(TichDeepLinkTarget.staffAttendance);
      }
      return const TichDeepLink(TichDeepLinkTarget.employeeAttendance);
    }
    if (lower.contains('leave')) {
      return const TichDeepLink(TichDeepLinkTarget.employeeLeave);
    }
    if (lower.contains('timetable') || lower.contains('lesson-plan')) {
      return const TichDeepLink(TichDeepLinkTarget.staffTimetable);
    }
    if (lower.contains('concern') || lower.contains('grievance')) {
      return const TichDeepLink(TichDeepLinkTarget.employeeConcerns);
    }
    if (lower.contains('document')) {
      return const TichDeepLink(TichDeepLinkTarget.employeeDocuments);
    }
    if (lower.contains('polic')) {
      return const TichDeepLink(TichDeepLinkTarget.employeePolicies);
    }
    if (lower.contains('appraisal')) {
      return const TichDeepLink(TichDeepLinkTarget.employeeAppraisals);
    }
    if (lower.contains('feedback')) {
      return const TichDeepLink(TichDeepLinkTarget.employeeFeedback);
    }
    if (lower.contains('profile')) {
      return const TichDeepLink(TichDeepLinkTarget.employeeProfile);
    }
    if (lower.contains('notification')) {
      return const TichDeepLink(TichDeepLinkTarget.employeeNotifications);
    }
    if (lower.contains('employee') && lower.contains('dashboard')) {
      return const TichDeepLink(TichDeepLinkTarget.employeeHome);
    }
    return null;
  }

  String payloadForLocal({
    String? target,
    String? actionUrl,
    String? entityType,
  }) {
    if (target == 'attendance') {
      return 'attendance';
    }
    return jsonEncode({
      if (target != null) 'target': target,
      if (actionUrl != null) 'action_url': actionUrl,
      if (entityType != null) 'entity_type': entityType,
    });
  }
}
