import 'dart:async';

import 'employee_api.dart';
import 'procurement_api.dart';
import 'staff_api.dart';
import 'student_api.dart';
import 'tich_screen_cache.dart';

/// Background cache warming - kept minimal so the UI thread and API stay responsive.
abstract final class TichPortalWarmup {
  static const _deferredGap = Duration(milliseconds: 400);
  static Timer? _deferredTimer;

  /// Call when a portal shell opens: warm the visible tab only.
  static void studentShellOpened(StudentApi api) {
    _cancelDeferred();
    TichScreenCache.warm('student.home', () => api.dashboard());
    _deferredTimer = Timer(const Duration(seconds: 20), () {
      _runDeferred([
        () => TichScreenCache.warm('student.academics', () => api.academics()),
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

  /// More drawer links - warm only when the user opens the menu.
  static void studentMoreOpened(StudentApi api) {}

  static void staffShellOpened(StaffApi api) {
    _cancelDeferred();
    TichScreenCache.warm('staff.home', () => api.dashboard());
    _deferredTimer = Timer(const Duration(seconds: 20), () {
      _runDeferred([
        () => TichScreenCache.warm('staff.units', () => api.units()),
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

  static void staffMoreOpened(StaffApi api) {}

  static void employeeShellOpened(EmployeeApi api) {
    _cancelDeferred();
    // Home tab owns `portal.dashboard`; prefetch other tabs only after idle.
    _deferredTimer = Timer(const Duration(seconds: 12), () {
      _runDeferred([
        () => TichScreenCache.warm('portal.leave', () => api.leave()),
        () => TichScreenCache.warm('portal.attendance', () => api.attendance()),
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

  /// Drawer open - avoid blasting the API; screens warm on navigation.
  static void employeeMoreOpened(EmployeeApi api) {}

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
