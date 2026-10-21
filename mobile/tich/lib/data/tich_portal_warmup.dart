import 'dart:async';

import 'employee_api.dart';
import 'procurement_api.dart';
import 'staff_api.dart';
import 'student_api.dart';
import 'tich_screen_cache.dart';

/// Background cache warming — kept minimal so the UI thread and API stay responsive.
abstract final class TichPortalWarmup {
  static const _deferredGap = Duration(milliseconds: 250);
  static Timer? _deferredTimer;

  /// Call when a portal shell opens: warm the visible tab only.
  static void studentShellOpened(StudentApi api) {
    _cancelDeferred();
    TichScreenCache.warm('student.home', () => api.dashboard());
    _deferredTimer = Timer(const Duration(seconds: 4), () {
      _runDeferred([
        () => TichScreenCache.warm('student.academics', () => api.academics()),
        () => TichScreenCache.warm('student.timetable', () => api.timetable()),
        () => TichScreenCache.warm('student.finance', () => api.finance()),
      ]);
    });
  }

  static void studentTabSelected(StudentApi api, int index) {
    switch (index) {
      case 0:
        TichScreenCache.warm('student.home', () => api.dashboard());
      case 1:
        TichScreenCache.warm('student.academics', () => api.academics());
      case 2:
        TichScreenCache.warm('student.timetable', () => api.timetable());
      case 3:
        TichScreenCache.warm('student.finance', () => api.finance());
      default:
        break;
    }
  }

  /// More drawer links — warm only when the user opens the menu.
  static void studentMoreOpened(StudentApi api) {
    unawaited(_runDeferred([
      () => TichScreenCache.warm('student.profile', () => api.profile()),
      () => TichScreenCache.warm('student.documents', () => api.documents()),
      () => TichScreenCache.warm('student.enrolment', () => api.profile()),
      () => TichScreenCache.warm('student.clearance', () => api.clearance()),
      () => TichScreenCache.warm('student.evaluations', () => api.evaluations()),
      () => TichScreenCache.warm('student.lifecycle', () => api.lifecycleRequests()),
      () => TichScreenCache.warm('student.notifications', () => api.notifications()),
    ]));
  }

  static void staffShellOpened(StaffApi api) {
    _cancelDeferred();
    TichScreenCache.warm('staff.home', () => api.dashboard());
    _deferredTimer = Timer(const Duration(seconds: 4), () {
      _runDeferred([
        () => TichScreenCache.warm('staff.units', () => api.units()),
        () => TichScreenCache.warm('staff.timetable', () => api.timetable()),
        () => TichScreenCache.warm('staff.attendance', () => api.attendance()),
      ]);
    });
  }

  static void staffTabSelected(StaffApi api, int index) {
    switch (index) {
      case 0:
        TichScreenCache.warm('staff.home', () => api.dashboard());
      case 1:
        TichScreenCache.warm('staff.units', () => api.units());
      case 2:
        TichScreenCache.warm('staff.timetable', () => api.timetable());
      case 3:
        TichScreenCache.warm('staff.attendance', () => api.attendance());
      default:
        break;
    }
  }

  static void staffMoreOpened(StaffApi api) {
    unawaited(_runDeferred([
      () => TichScreenCache.warm('staff.grading', () => api.grading()),
      () => TichScreenCache.warm('staff.content', () => api.content()),
      () => TichScreenCache.warm('staff.lesson_plans', () => api.lessonPlans()),
    ]));
  }

  static void employeeShellOpened(EmployeeApi api) {
    _cancelDeferred();
    TichScreenCache.warm('portal.dashboard', () => api.dashboard());
    _deferredTimer = Timer(const Duration(seconds: 4), () {
      _runDeferred([
        () => TichScreenCache.warm('portal.attendance', () => api.attendance()),
        () => TichScreenCache.warm('portal.leave', () => api.leave()),
        () => TichScreenCache.warm('portal.payslips_bundle', () async {
            final payslips = await api.payslips();
            final dashboard = await api.dashboard();
            return {
              'payslips': payslips['payslips'],
              'compensation': dashboard['compensation'],
            };
          }),
      ]);
    });
  }

  static void employeeTabSelected(EmployeeApi api, int index) {
    switch (index) {
      case 0:
        TichScreenCache.warm('portal.dashboard', () => api.dashboard());
      case 1:
        TichScreenCache.warm('portal.leave', () => api.leave());
      case 2:
        TichScreenCache.warm('portal.attendance', () => api.attendance());
      case 3:
        TichScreenCache.warm('portal.payslips', () => api.payslips());
      default:
        break;
    }
  }

  static void employeeMoreOpened(EmployeeApi api) {
    unawaited(_runDeferred([
      () => TichScreenCache.warm('portal.profile', () => api.profile()),
      () => TichScreenCache.warm('portal.documents', () => api.documents()),
      () => TichScreenCache.warm('portal.notifications', () => api.notifications()),
      () => TichScreenCache.warm('portal.time_logs', () => api.timeLogs()),
      () => TichScreenCache.warm('portal.policies', () => api.policies()),
      () => TichScreenCache.warm('portal.concerns', () => api.concerns()),
      () => TichScreenCache.warm('portal.feedback', () => api.feedback()),
      () => TichScreenCache.warm('portal.wfh', () => api.wfh()),
      () => TichScreenCache.warm('portal.appraisals', () => api.appraisals()),
    ]));
  }

  static void procurementShellOpened(ProcurementApi api) {
    TichScreenCache.warm('procurement.home', () => api.dashboard());
  }

  static void _cancelDeferred() {
    _deferredTimer?.cancel();
    _deferredTimer = null;
  }

  static Future<void> _runDeferred(List<void Function()> tasks) async {
    for (final task in tasks) {
      task();
      await Future<void>.delayed(_deferredGap);
    }
  }
}
