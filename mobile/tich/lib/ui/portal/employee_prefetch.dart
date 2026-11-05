import '../../data/employee_api.dart';
import 'portal_helpers.dart';

/// Starts employee portal payloads before route transition (stale-while-revalidate).
abstract final class EmployeePrefetch {
  static const dashboardKey = 'portal.dashboard';
  static const leaveKey = 'portal.leave';
  static const attendanceKey = 'portal.attendance';
  static const payslipsBundleKey = 'portal.payslips_bundle';
  static const documentsKey = 'portal.documents';
  static const policiesKey = 'portal.policies';
  static const appraisalsKey = 'portal.appraisals';
  static const concernsKey = 'portal.concerns';
  static const feedbackKey = 'portal.feedback';
  static const profileKey = 'portal.profile';
  static const notificationsKey = 'portal.notifications';
  static const timeLogsKey = 'portal.time_logs';

  static void dashboard(EmployeeApi api) {
    portalWarm(dashboardKey, () => api.dashboard());
  }

  static void leave(EmployeeApi api) {
    portalWarm(leaveKey, () => api.leave());
  }

  static void attendance(EmployeeApi api) {
    portalWarm(attendanceKey, () => api.attendance());
  }

  static void payslipsTab(EmployeeApi api) {
    portalWarm(payslipsBundleKey, () => _payslipsBundle(api));
  }

  static Future<Map<String, dynamic>> _payslipsBundle(EmployeeApi api) async {
    final payslips = await api.payslips();
    final compensation = portalReadCache(dashboardKey)?['compensation'];
    if (compensation is Map) {
      return {
        'payslips': payslips['payslips'],
        'compensation': compensation,
      };
    }
    return {
      'payslips': payslips['payslips'],
      'compensation': const <String, dynamic>{},
    };
  }

  static void documents(EmployeeApi api) {
    portalWarm(documentsKey, () => api.documents());
  }

  static void policies(EmployeeApi api) {
    portalWarm(policiesKey, () => api.policies());
  }

  static void appraisals(EmployeeApi api) {
    portalWarm(appraisalsKey, () => api.appraisals());
  }

  static void concerns(EmployeeApi api) {
    portalWarm(concernsKey, () => api.concerns());
  }

  static void feedback(EmployeeApi api) {
    portalWarm(feedbackKey, () => api.feedback());
  }

  static void profile(EmployeeApi api) {
    portalWarm(profileKey, () => api.profile());
  }

  static void notifications(EmployeeApi api) {
    portalWarm(notificationsKey, () => api.notifications());
  }

  static void timeLogs(EmployeeApi api) {
    portalWarm(timeLogsKey, () => api.timeLogs());
  }

  static String timeLogKey(int id) =>
      portalDetailCacheKey('portal.time_log', id);

  static void timeLogDetail(EmployeeApi api, int id) {
    portalWarm(timeLogKey(id), () => api.timeLog(id));
  }

  static String payslipKey(int id) =>
      portalDetailCacheKey('portal.payslip', id);

  static void payslipDetail(EmployeeApi api, int id) {
    portalWarm(payslipKey(id), () => api.payslip(id));
  }

  /// More-menu / drawer module warm-up.
  static void warmMoreMenu(EmployeeApi api) {
    profile(api);
    documents(api);
    policies(api);
    timeLogs(api);
    appraisals(api);
    concerns(api);
    feedback(api);
  }

  static void warmListForModule(EmployeeApi api, String moduleKey) {
    switch (moduleKey) {
      case 'home':
      case 'dashboard':
        dashboard(api);
      case 'leave':
        leave(api);
      case 'attendance':
      case 'clock':
        attendance(api);
      case 'payslips':
        payslipsTab(api);
      case 'profile':
        profile(api);
      case 'documents':
        documents(api);
      case 'policies':
        policies(api);
      case 'time_logs':
      case 'time-logs':
        timeLogs(api);
      case 'appraisals':
        appraisals(api);
      case 'concerns':
        concerns(api);
      case 'feedback':
        feedback(api);
      case 'notifications':
        notifications(api);
      default:
        break;
    }
  }
}
