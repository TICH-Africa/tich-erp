import '../../data/staff_api.dart';
import 'staff_helpers.dart';

/// Starts list/detail payloads before route transition (stale-while-revalidate).
abstract final class StaffPrefetch {
  static const homeKey = 'staff.home';
  static const unitsKey = 'staff.units';
  static const timetableKey = 'staff.timetable';
  static const attendanceKey = 'staff.attendance';
  static const gradingKey = 'staff.grading';
  static const lessonPlansKey = 'staff.lesson_plans';
  static const contentKey = 'staff.content';

  static void home(StaffApi api) {
    staffWarm(homeKey, () => api.dashboard());
  }

  static void units(StaffApi api) {
    staffWarm(unitsKey, () => api.units());
  }

  static void timetable(StaffApi api) {
    staffWarm(timetableKey, () => api.timetable());
  }

  static void attendanceList(StaffApi api) {
    staffWarm(attendanceKey, () => api.attendance());
  }

  static void gradingList(StaffApi api) {
    staffWarm(gradingKey, () => api.grading());
  }

  static void lessonPlansList(StaffApi api) {
    staffWarm(lessonPlansKey, () => api.lessonPlans());
  }

  static void contentList(StaffApi api) {
    staffWarm(contentKey, () => api.content());
  }

  static String attendanceSessionKey(int id) =>
      staffDetailCacheKey('staff.attendance_session', id);

  static void attendanceSession(StaffApi api, int id) {
    staffWarm(attendanceSessionKey(id), () => api.attendanceSession(id));
  }

  static String lessonPlanContextKey(int allocationId, String plannedDate) =>
      'staff.lesson_plan_context.$allocationId.$plannedDate';

  static void lessonPlanContext(
    StaffApi api, {
    required int allocationId,
    required String plannedDate,
  }) {
    staffWarm(
      lessonPlanContextKey(allocationId, plannedDate),
      () => api.lessonPlanContext(
        allocationId: allocationId,
        plannedDate: plannedDate,
      ),
    );
  }

  /// Tab index or More-menu module key.
  static void warmListForModule(StaffApi api, String moduleKey) {
    switch (moduleKey) {
      case 'home':
        home(api);
      case 'units':
        units(api);
      case 'timetable':
        timetable(api);
      case 'attendance':
        attendanceList(api);
      case 'grading':
        gradingList(api);
      case 'lesson_plans':
        lessonPlansList(api);
        timetable(api);
      case 'content':
        contentList(api);
      default:
        break;
    }
  }

  /// Before opening the new-lesson-plan flow.
  static void lessonPlanCreate(
    StaffApi api, {
    int? defaultAllocationId,
    String? plannedDate,
  }) {
    lessonPlansList(api);
    timetable(api);
    if (defaultAllocationId != null && plannedDate != null) {
      lessonPlanContext(
        api,
        allocationId: defaultAllocationId,
        plannedDate: plannedDate,
      );
    }
  }

  static void contentUpload(StaffApi api) {
    contentList(api);
  }
}
