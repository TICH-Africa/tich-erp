import 'package:flutter/material.dart';

import '../../data/employee_api.dart';
import '../../navigation/tich_root_nav.dart';
import 'appraisals_page.dart';
import 'concerns_page.dart';
import 'documents_page.dart';
import 'employee_prefetch.dart';
import 'feedback_page.dart';
import 'notifications_page.dart';
import 'policies_page.dart';
import 'profile_page.dart';
import 'time_logs_page.dart';
void employeePushPage(BuildContext context, EmployeeApi api, Widget page) {
  Navigator.of(context).push(
    MaterialPageRoute(builder: (_) => page),
  );
}

void employeePushFromRoot(EmployeeApi api, Widget page) {
  TichRootNav.push(page);
}

void openEmployeeProfile(EmployeeApi api) {
  EmployeePrefetch.profile(api);
  employeePushFromRoot(api, ProfilePage(api: api));
}

void openEmployeeDocuments(EmployeeApi api) {
  EmployeePrefetch.documents(api);
  employeePushFromRoot(api, DocumentsPage(api: api));
}

void openEmployeePolicies(EmployeeApi api) {
  EmployeePrefetch.policies(api);
  employeePushFromRoot(api, PoliciesPage(api: api));
}

void openEmployeeTimeLogs(EmployeeApi api) {
  EmployeePrefetch.timeLogs(api);
  employeePushFromRoot(api, TimeLogsPage(api: api));
}

void openEmployeeAppraisals(EmployeeApi api) {
  EmployeePrefetch.appraisals(api);
  employeePushFromRoot(api, AppraisalsPage(api: api));
}

void openEmployeeConcerns(EmployeeApi api) {
  EmployeePrefetch.concerns(api);
  employeePushFromRoot(api, ConcernsPage(api: api));
}

void openEmployeeFeedback(EmployeeApi api) {
  EmployeePrefetch.feedback(api);
  employeePushFromRoot(api, FeedbackPage(api: api));
}

Future<void> openEmployeeNotifications(
  BuildContext context,
  EmployeeApi api,
) {
  EmployeePrefetch.notifications(api);
  return Navigator.of(context).push(
    MaterialPageRoute(builder: (_) => NotificationsPage(api: api)),
  );
}
