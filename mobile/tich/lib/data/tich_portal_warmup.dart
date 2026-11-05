import 'dart:async';

import '../models/auth_user.dart';
import 'api_client.dart';
import 'employee_api.dart';
import 'procurement_api.dart';
import 'staff_api.dart';
import 'student_api.dart';
import 'tich_screen_cache.dart';

/// Background cache warming - kept minimal so the UI thread and API stay responsive.
abstract final class TichPortalWarmup {
  static const _deferredGap = Duration(milliseconds: 400);
  static Timer? _deferredTimer;

  /// Start the home-tab API as soon as the user is signed in (before first paint).
  static void prefetchHome(AuthUser user, ApiClient client) {
    if (user.isStudent) {
      TichScreenCache.warm(
        'student.home',
        () => StudentApi(client).dashboard(),
      );
      return;
    }
    if (user.canAccessEmployeePortal) {
      TichScreenCache.warm(
        'portal.dashboard',
        () => EmployeeApi(client).dashboard(),
      );
      return;
    }
    if (user.canAccessStaffPortal) {
      TichScreenCache.warm(
        'staff.home',
        () => StaffApi(client).dashboard(),
      );
      return;
    }
    if (user.canAccessProcurementPortal) {
      TichScreenCache.warm(
        'procurement.home',
        () => ProcurementApi(client).dashboard(),
      );
    }
  }

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
  static void studentMoreOpened(StudentApi api) {
    _runDeferred([
      () => TichScreenCache.warm('student.notifications', () => api.notifications()),
      () => TichScreenCache.warm('student.documents', () => api.documents()),
    ]);
  }

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

  static void staffMoreOpened(StaffApi api) {
    _runDeferred([
      () => TichScreenCache.warm('staff.lesson_plans', () => api.lessonPlans()),
      () => TichScreenCache.warm('staff.grading', () => api.grading()),
      () => TichScreenCache.warm('staff.content', () => api.content()),
    ]);
  }

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

  /// Screens behind the employee More menu, in the order a phone taps them.
  ///
  /// These are the payloads login preload never reaches (the tabs are always
  /// newer), so every link used to wait on the network. Warming only the two
  /// coldest per drawer open keeps a serialized server or a weak connection from
  /// answering seven requests at once, and a tap joins the request already in
  /// flight instead of starting its own.
  static void employeeMoreOpened(EmployeeApi api) {
    final screens = <String, Future<Object> Function()>{
      'portal.profile': () => api.profile(),
      'portal.time_logs': () => api.timeLogs(),
      'portal.documents': () => api.documents(),
      'portal.policies': () => api.policies(),
      'portal.appraisals': () => api.appraisals(),
      'portal.concerns': () => api.concerns(),
      'portal.feedback': () => api.feedback(),
    };

    final cold = screens.entries
        .where((e) => TichScreenCache.read<Map<String, dynamic>>(e.key) == null)
        .take(2)
        .toList();
    if (cold.isEmpty) return;

    _runDeferred([
      for (final screen in cold)
        () => TichScreenCache.warm(screen.key, screen.value),
    ]);
  }

  static void procurementShellOpened(ProcurementApi api) {
    TichScreenCache.warm('procurement.home', () => api.dashboard());
  }

  static void procurementTabSelected(ProcurementApi api, int index) {
    switch (index) {
      case 0:
        TichScreenCache.warm('procurement.home', () => api.dashboard());
      case 1:
        TichScreenCache.warm('procurement.requisitions', () => api.requisitions());
      case 2:
        TichScreenCache.warm('procurement.stock_issues', () => api.stockIssues());
      case 3:
        TichScreenCache.warm('procurement.suppliers', () => api.suppliers());
      default:
        break;
    }
  }

  /// Warm common More-menu lists after the drawer opens (idle, staggered).
  static void procurementMoreOpened(ProcurementApi api) {
    _runDeferred([
      () => TichScreenCache.warm('procurement.grns', () => api.grns()),
      () => TichScreenCache.warm(
            'procurement.payment_verification',
            () => api.paymentVerification(),
          ),
    ]);
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
