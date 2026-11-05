import 'package:flutter/material.dart';

import '../../data/staff_api.dart';
import '../../navigation/tich_root_nav.dart';
import 'attendance_page.dart';
import 'attendance_session_page.dart';
import 'content_page.dart';
import 'grading_page.dart';
import 'lesson_plans_page.dart';
import 'staff_prefetch.dart';
import 'timetable_page.dart';
import 'units_page.dart';

void staffPushPage(BuildContext context, StaffApi api, Widget page) {
  Navigator.of(context).push(
    MaterialPageRoute(builder: (_) => page),
  );
}

void staffPushFromRoot(StaffApi api, Widget page) {
  TichRootNav.push(page);
}

void openStaffAttendance(BuildContext context, StaffApi api) {
  StaffPrefetch.attendanceList(api);
  staffPushPage(context, api, StaffAttendancePage(api: api));
}

void openStaffGrading(BuildContext context, StaffApi api) {
  StaffPrefetch.gradingList(api);
  staffPushPage(context, api, StaffGradingPage(api: api));
}

void openStaffGradingFromMore(StaffApi api) {
  StaffPrefetch.gradingList(api);
  staffPushFromRoot(api, StaffGradingPage(api: api));
}

void openStaffLessonPlans(BuildContext context, StaffApi api) {
  StaffPrefetch.lessonPlansList(api);
  staffPushPage(context, api, StaffLessonPlansPage(api: api));
}

void openStaffLessonPlansFromMore(StaffApi api) {
  StaffPrefetch.lessonPlansList(api);
  staffPushFromRoot(api, StaffLessonPlansPage(api: api));
}

void openStaffContentFromMore(StaffApi api) {
  StaffPrefetch.contentList(api);
  staffPushFromRoot(api, StaffContentPage(api: api));
}

void openStaffTimetable(BuildContext context, StaffApi api) {
  StaffPrefetch.timetable(api);
  staffPushPage(context, api, StaffTimetablePage(api: api));
}

void openStaffUnits(BuildContext context, StaffApi api) {
  StaffPrefetch.units(api);
  staffPushPage(context, api, StaffUnitsPage(api: api));
}

Future<void> openStaffAttendanceSession(
  BuildContext context,
  StaffApi api,
  int sessionId,
) async {
  StaffPrefetch.attendanceSession(api, sessionId);
  await Navigator.of(context).push(
    MaterialPageRoute(
      builder: (_) => StaffAttendanceSessionPage(
        api: api,
        sessionId: sessionId,
      ),
    ),
  );
}
