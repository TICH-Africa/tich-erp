import '../../data/student_api.dart';
import 'student_helpers.dart';

/// Starts list/detail payloads before route transition (stale-while-revalidate).
abstract final class StudentPrefetch {
  static const homeKey = 'student.home';
  static const academicsKey = 'student.academics';
  static const academicsUnitsKey = 'student.academics.units';
  static const academicsContentKey = 'student.academics.content';
  static const academicsAssessmentsKey = 'student.academics.assessments';
  static const timetableKey = 'student.timetable';
  static const financeKey = 'student.finance';
  static const profileKey = 'student.profile';
  static const enrolmentKey = 'student.enrolment';
  static const documentsKey = 'student.documents';
  static const notificationsKey = 'student.notifications';
  static const clearanceKey = 'student.clearance';
  static const evaluationsKey = 'student.evaluations';
  static const lifecycleKey = 'student.lifecycle';

  static void home(StudentApi api) {
    studentWarm(homeKey, () => api.dashboard());
  }

  static void academics(StudentApi api) {
    studentWarm(academicsKey, () => api.academics());
  }

  static void academicsUnits(StudentApi api) {
    studentWarm(academicsUnitsKey, () => api.academicsUnits());
  }

  static void academicsContent(StudentApi api) {
    studentWarm(academicsContentKey, () => api.academicsContent());
  }

  static void academicsAssessments(StudentApi api) {
    studentWarm(academicsAssessmentsKey, () => api.academicsAssessments());
  }

  static void timetable(StudentApi api) {
    studentWarm(timetableKey, () => api.timetable());
  }

  static void finance(StudentApi api) {
    studentWarm(financeKey, () => api.finance());
  }

  static void profile(StudentApi api) {
    studentWarm(profileKey, () => api.profile());
  }

  static void enrolment(StudentApi api) {
    studentWarm(enrolmentKey, () => api.profile());
  }

  static void documents(StudentApi api) {
    studentWarm(documentsKey, () => api.documents());
  }

  static void notifications(StudentApi api) {
    studentWarm(notificationsKey, () => api.notifications());
  }

  static void clearance(StudentApi api) {
    studentWarm(clearanceKey, () => api.clearance());
  }

  static void evaluations(StudentApi api) {
    studentWarm(evaluationsKey, () => api.evaluations());
  }

  static void lifecycle(StudentApi api) {
    studentWarm(lifecycleKey, () => api.lifecycleRequests());
  }

  static String assessmentKey(int id) =>
      studentDetailCacheKey('student.assessment', id);

  static void assessment(StudentApi api, int id) {
    studentWarm(assessmentKey(id), () => api.assessment(id));
  }

  /// Prefetch all academics hub sub-lists (idle / tab warm).
  static void academicsSubsections(StudentApi api) {
    academicsUnits(api);
    academicsContent(api);
    academicsAssessments(api);
  }

  /// More-menu module keys match drawer labels loosely.
  static void warmListForModule(StudentApi api, String moduleKey) {
    switch (moduleKey) {
      case 'profile':
        profile(api);
      case 'enrolment':
        enrolment(api);
      case 'documents':
        documents(api);
      case 'clearance':
        clearance(api);
      case 'evaluations':
        evaluations(api);
      case 'lifecycle':
        lifecycle(api);
      case 'notifications':
        notifications(api);
      case 'finance':
        finance(api);
      case 'timetable':
        timetable(api);
      case 'academics':
        academics(api);
        academicsSubsections(api);
      case 'home':
        home(api);
      default:
        break;
    }
  }
}
