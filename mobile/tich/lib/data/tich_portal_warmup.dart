import 'dart:async';

import 'employee_api.dart';
import 'procurement_api.dart';
import 'staff_api.dart';
import 'student_api.dart';
import 'tich_screen_cache.dart';

/// Prefetch portal payloads so tabs and More links open on cached data.
abstract final class TichPortalWarmup {
  static const _gap = Duration(milliseconds: 60);

  static void student(StudentApi api) {
    unawaited(_run([
      () => TichScreenCache.warm(
            'student.home',
            () => api.dashboard(),
          ),
      () => TichScreenCache.warm(
            'student.academics',
            () => api.academics(),
          ),
      () => TichScreenCache.warm(
            'student.timetable',
            () => api.timetable(),
          ),
      () => TichScreenCache.warm(
            'student.finance',
            () => api.finance(),
          ),
      () => TichScreenCache.warm(
            'student.profile',
            () => api.profile(),
          ),
      () => TichScreenCache.warm(
            'student.documents',
            () => api.documents(),
          ),
      () => TichScreenCache.warm(
            'student.enrolment',
            () => api.profile(),
          ),
      () => TichScreenCache.warm(
            'student.clearance',
            () => api.clearance(),
          ),
      () => TichScreenCache.warm(
            'student.evaluations',
            () => api.evaluations(),
          ),
      () => TichScreenCache.warm(
            'student.lifecycle',
            () => api.lifecycleRequests(),
          ),
      () => TichScreenCache.warm(
            'student.notifications',
            () => api.notifications(),
          ),
    ]));
  }

  static void staff(StaffApi api) {
    unawaited(_run([
      () => TichScreenCache.warm('staff.home', () => api.dashboard()),
      () => TichScreenCache.warm('staff.units', () => api.units()),
      () => TichScreenCache.warm('staff.timetable', () => api.timetable()),
      () => TichScreenCache.warm('staff.attendance', () => api.attendance()),
      () => TichScreenCache.warm('staff.grading', () => api.grading()),
      () => TichScreenCache.warm('staff.content', () => api.content()),
      () => TichScreenCache.warm('staff.lesson_plans', () => api.lessonPlans()),
    ]));
  }

  static void employee(EmployeeApi api) {
    unawaited(_run([
      () => TichScreenCache.warm('portal.dashboard', () => api.dashboard()),
      () => TichScreenCache.warm('portal.profile', () => api.profile()),
      () => TichScreenCache.warm('portal.attendance', () => api.attendance()),
      () => TichScreenCache.warm('portal.leave', () => api.leave()),
      () => TichScreenCache.warm('portal.documents', () => api.documents()),
      () => TichScreenCache.warm('portal.notifications', () => api.notifications()),
      () => TichScreenCache.warm('portal.time_logs', () => api.timeLogs()),
      () => TichScreenCache.warm('portal.payslips', () => api.payslips()),
      () => TichScreenCache.warm('portal.policies', () => api.policies()),
      () => TichScreenCache.warm('portal.concerns', () => api.concerns()),
      () => TichScreenCache.warm('portal.feedback', () => api.feedback()),
      () => TichScreenCache.warm('portal.wfh', () => api.wfh()),
      () => TichScreenCache.warm('portal.appraisals', () => api.appraisals()),
    ]));
  }

  static void procurement(ProcurementApi api) {
    unawaited(_run([
      () => TichScreenCache.warm('procurement.home', () => api.dashboard()),
    ]));
  }

  static Future<void> _run(List<void Function()> tasks) async {
    for (final task in tasks) {
      task();
      await Future<void>.delayed(_gap);
    }
  }
}
