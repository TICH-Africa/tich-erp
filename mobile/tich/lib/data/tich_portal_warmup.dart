import 'dart:async';

import '../models/auth_user.dart';
import 'api_client.dart';
import 'employee_api.dart';
import 'procurement_api.dart';
import 'staff_api.dart';
import 'student_api.dart';
import '../ui/portal/employee_prefetch.dart';
import '../ui/student/student_prefetch.dart';
import 'tich_cold_start.dart';
import 'tich_screen_cache.dart';

/// Background cache warming - kept minimal so the UI thread and API stay responsive.
abstract final class TichPortalWarmup {
  static const _deferredGap = Duration(seconds: 2);
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
    StudentPrefetch.home(api);
    _deferredTimer = Timer(TichColdStart.deferredWarmupDelay, () {
      if (!TichColdStart.allowDeferredWarmup) return;
      _runDeferred([
        () => StudentPrefetch.academics(api),
        () => StudentPrefetch.timetable(api),
        () => StudentPrefetch.finance(api),
      ]);
    });
  }

  static void studentTabSelected(StudentApi api, int index) {
    if (TichColdStart.isEmptyDiskSession && !TichColdStart.allowSecondaryEndpoints) {
      if (index == 0) {
        StudentPrefetch.home(api);
      }
      return;
    }
    switch (index) {
      case 0:
        StudentPrefetch.home(api);
        if (TichColdStart.allowSecondaryEndpoints) {
          StudentPrefetch.timetable(api);
        }
        break;
      case 1:
        StudentPrefetch.academics(api);
        if (TichColdStart.allowSecondaryEndpoints) {
          StudentPrefetch.academicsSubsections(api);
        }
        break;
      case 2:
        StudentPrefetch.timetable(api);
        if (TichColdStart.allowSecondaryEndpoints) {
          StudentPrefetch.timetableDocumentsFromCache(api);
        }
        break;
      case 3:
        StudentPrefetch.finance(api);
        break;
      default:
        break;
    }
  }

  /// More drawer links - warm only when the user opens the menu.
  static void studentMoreOpened(StudentApi api) {
    if (TichColdStart.isEmptyDiskSession && !TichColdStart.allowSecondaryEndpoints) {
      return;
    }
    _runDeferred([
      () => StudentPrefetch.profile(api),
      () => StudentPrefetch.enrolment(api),
      () => StudentPrefetch.notifications(api),
      () => StudentPrefetch.documents(api),
      () => StudentPrefetch.clearance(api),
      () => StudentPrefetch.evaluations(api),
      () => StudentPrefetch.lifecycle(api),
    ]);
  }

  static void staffShellOpened(StaffApi api) {
    _cancelDeferred();
    TichScreenCache.warm('staff.home', () => api.dashboard());
    _deferredTimer = Timer(TichColdStart.deferredWarmupDelay, () {
      if (!TichColdStart.allowDeferredWarmup) return;
      _runDeferred([
        () => TichScreenCache.warm('staff.units', () => api.units()),
        () => TichScreenCache.warm('staff.timetable', () => api.timetable()),
        () => TichScreenCache.warm('staff.attendance', () => api.attendance()),
      ]);
    });
  }

  static void staffTabSelected(StaffApi api, int index) {
    if (TichColdStart.isEmptyDiskSession && !TichColdStart.allowSecondaryEndpoints) {
      if (index == 0) {
        TichScreenCache.warm('staff.home', () => api.dashboard());
      }
      return;
    }
    switch (index) {
      case 0:
        TichScreenCache.warm('staff.home', () => api.dashboard());
        break;
      case 1:
        TichScreenCache.warm('staff.units', () => api.units());
        break;
      case 2:
        TichScreenCache.warm('staff.timetable', () => api.timetable());
        break;
      case 3:
        TichScreenCache.warm('staff.attendance', () => api.attendance());
        break;
      default:
        break;
    }
  }

  static void staffMoreOpened(StaffApi api) {
    if (TichColdStart.isEmptyDiskSession && !TichColdStart.allowSecondaryEndpoints) {
      return;
    }
    _runDeferred([
      () => TichScreenCache.warm('staff.lesson_plans', () => api.lessonPlans()),
      () => TichScreenCache.warm('staff.grading', () => api.grading()),
      () => TichScreenCache.warm('staff.content', () => api.content()),
    ]);
  }

  static void employeeShellOpened(EmployeeApi api) {
    _cancelDeferred();
    EmployeePrefetch.dashboard(api);
    _deferredTimer = Timer(TichColdStart.deferredWarmupDelay, () {
      if (!TichColdStart.allowDeferredWarmup) return;
      _runDeferred([
        () => EmployeePrefetch.leave(api),
        () => EmployeePrefetch.payslipsTab(api),
      ]);
    });
  }

  static void employeeTabSelected(EmployeeApi api, int index) {
    if (TichColdStart.isEmptyDiskSession && !TichColdStart.allowSecondaryEndpoints) {
      if (index == 0) {
        EmployeePrefetch.dashboard(api);
      }
      return;
    }
    switch (index) {
      case 0:
        EmployeePrefetch.dashboard(api);
        if (TichColdStart.allowSecondaryEndpoints) {
          EmployeePrefetch.notifications(api);
        }
        break;
      case 1:
        EmployeePrefetch.leave(api);
        break;
      case 2:
        EmployeePrefetch.attendance(api);
        break;
      case 3:
        EmployeePrefetch.payslipsTab(api);
        break;
      default:
        break;
    }
  }

  static void employeeMoreOpened(EmployeeApi api) {
    if (TichColdStart.isEmptyDiskSession && !TichColdStart.allowSecondaryEndpoints) {
      return;
    }
    _runDeferred([
      () => EmployeePrefetch.warmMoreMenu(api),
      () => EmployeePrefetch.notifications(api),
    ]);
  }

  static void procurementShellOpened(ProcurementApi api) {
    TichScreenCache.warm('procurement.home', () => api.dashboard());
  }

  static void procurementTabSelected(ProcurementApi api, int index) {
    if (TichColdStart.isEmptyDiskSession && !TichColdStart.allowSecondaryEndpoints) {
      if (index == 0) {
        TichScreenCache.warm('procurement.home', () => api.dashboard());
      }
      return;
    }
    switch (index) {
      case 0:
        TichScreenCache.warm('procurement.home', () => api.dashboard());
        break;
      case 1:
        TichScreenCache.warm('procurement.requisitions', () => api.requisitions());
        if (TichColdStart.allowSecondaryEndpoints) {
          TichScreenCache.warm(
            'procurement.requisition_form_options',
            () => api.requisitionFormOptions(),
          );
        }
        break;
      case 2:
        TichScreenCache.warm('procurement.stock_issues', () => api.stockIssues());
        if (TichColdStart.allowSecondaryEndpoints) {
          TichScreenCache.warm(
            'procurement.stock_issue_form_options',
            () => api.stockIssueFormOptions(),
          );
        }
        break;
      case 3:
        TichScreenCache.warm('procurement.suppliers', () => api.suppliers());
        break;
      default:
        break;
    }
  }

  /// Warm common More-menu lists after the drawer opens (idle, staggered).
  static void procurementMoreOpened(ProcurementApi api) {
    if (TichColdStart.isEmptyDiskSession && !TichColdStart.allowSecondaryEndpoints) {
      return;
    }
    _runDeferred([
      () => TichScreenCache.warm('procurement.grns', () => api.grns()),
      () => TichScreenCache.warm('procurement.stock_alerts', () => api.stockAlerts()),
      () => TichScreenCache.warm('procurement.assets', () => api.assets()),
      () => TichScreenCache.warm('procurement.rfqs', () => api.rfqs()),
      () => TichScreenCache.warm('procurement.inventory', () => api.inventoryItems()),
      () => TichScreenCache.warm('procurement.disposals', () => api.assetDisposals()),
      () => TichScreenCache.warm('procurement.asset_audits', () => api.assetAudits()),
      () => TichScreenCache.warm(
            'procurement.payment_verification',
            () => api.paymentVerification(),
          ),
      () => TichScreenCache.warm(
            'procurement.asset_disposal_form_options',
            () => api.assetDisposalFormOptions(),
          ),
    ]);
  }

  static void _cancelDeferred() {
    _deferredTimer?.cancel();
    _deferredTimer = null;
  }

  static Future<void> _runDeferred(List<void Function()> tasks) async {
    for (final task in tasks) {
      if (!TichColdStart.allowDeferredWarmup) break;
      task();
      await Future<void>.delayed(_deferredGap);
    }
  }
}