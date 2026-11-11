import 'package:flutter/material.dart';

import '../../data/student_api.dart';
import '../../navigation/tich_root_nav.dart';
import 'assessment_take_page.dart';
import 'clearance_page.dart';
import 'documents_page.dart';
import 'enrolment_page.dart';
import 'evaluations_page.dart';
import 'finance_page.dart';
import 'lifecycle_page.dart';
import 'notifications_page.dart';
import 'profile_page.dart';
import 'student_prefetch.dart';
import 'student_exam_requests_page.dart';
import 'suggestions_page.dart';
import 'timetable_page.dart';

Future<T?> studentPushPage<T>(BuildContext context, Widget page) {
  return Navigator.of(context).push<T>(
    MaterialPageRoute(builder: (_) => page),
  );
}

void studentPushFromRoot(StudentApi api, Widget page) {
  TichRootNav.push(page);
}

Future<void> openStudentFinance(BuildContext context, StudentApi api) {
  StudentPrefetch.finance(api);
  return studentPushPage(context, StudentFinancePage(api: api));
}

Future<void> openStudentProfile(BuildContext context, StudentApi api) {
  StudentPrefetch.profile(api);
  return studentPushPage(context, StudentProfilePage(api: api));
}

void openStudentProfileFromMore(StudentApi api) {
  StudentPrefetch.profile(api);
  studentPushFromRoot(api, StudentProfilePage(api: api));
}

Future<void> openStudentDocuments(BuildContext context, StudentApi api) {
  StudentPrefetch.documents(api);
  return studentPushPage(context, StudentDocumentsPage(api: api));
}

void openStudentDocumentsFromMore(StudentApi api) {
  StudentPrefetch.documents(api);
  studentPushFromRoot(api, StudentDocumentsPage(api: api));
}

Future<void> openStudentNotifications(BuildContext context, StudentApi api) {
  StudentPrefetch.notifications(api);
  return studentPushPage(context, StudentNotificationsPage(api: api));
}

void openStudentNotificationsFromMore(StudentApi api) {
  StudentPrefetch.notifications(api);
  studentPushFromRoot(api, StudentNotificationsPage(api: api));
}

Future<void> openStudentTimetable(BuildContext context, StudentApi api) {
  StudentPrefetch.timetable(api);
  return studentPushPage(context, StudentTimetablePage(api: api));
}

void openStudentEnrolmentFromMore(StudentApi api) {
  StudentPrefetch.enrolment(api);
  studentPushFromRoot(api, StudentEnrolmentPage(api: api));
}

void openStudentClearanceFromMore(StudentApi api) {
  StudentPrefetch.clearance(api);
  studentPushFromRoot(api, StudentClearancePage(api: api));
}

void openStudentEvaluationsFromMore(StudentApi api) {
  StudentPrefetch.evaluations(api);
  studentPushFromRoot(api, StudentEvaluationsPage(api: api));
}

void openStudentLifecycleFromMore(StudentApi api) {
  StudentPrefetch.lifecycle(api);
  studentPushFromRoot(api, StudentLifecyclePage(api: api));
}

void openStudentExamRequests(StudentApi api) {
  StudentPrefetch.examRequests(api);
  studentPushFromRoot(api, StudentExamRequestsPage(api: api));
}

Future<void> openStudentSuggestions(BuildContext context, StudentApi api) {
  return studentPushPage(context, StudentSuggestionsPage(api: api));
}

void openStudentSuggestionsFromMore(StudentApi api) {
  studentPushFromRoot(api, StudentSuggestionsPage(api: api));
}

Future<bool?> openStudentAssessmentTake(
  BuildContext context,
  StudentApi api,
  int assessmentId,
) {
  StudentPrefetch.assessment(api, assessmentId);
  return Navigator.of(context).push<bool>(
    MaterialPageRoute(
      builder: (_) => StudentAssessmentTakePage(
        api: api,
        assessmentId: assessmentId,
      ),
    ),
  );
}
