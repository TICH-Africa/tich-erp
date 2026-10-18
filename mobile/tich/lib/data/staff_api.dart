import 'api_client.dart';

class StaffApi {
  StaffApi(this._client);

  final ApiClient _client;

  Future<Map<String, dynamic>> dashboard() => _client.get('/staff/dashboard');

  Future<Map<String, dynamic>> units() => _client.get('/staff/units');

  Future<Map<String, dynamic>> timetable() => _client.get('/staff/timetable');

  Future<Map<String, dynamic>> attendance() => _client.get('/staff/attendance');

  Future<Map<String, dynamic>> syncAttendance() =>
      _client.post('/staff/attendance/sync');

  Future<Map<String, dynamic>> attendanceSession(int id) =>
      _client.get('/staff/attendance/$id');

  Future<Map<String, dynamic>> createAttendanceSession(
    Map<String, dynamic> body,
  ) =>
      _client.post('/staff/attendance', body: body);

  Future<Map<String, dynamic>> saveAttendance(
    int sessionId, {
    required List<int> presentStudentIds,
    bool lock = false,
  }) =>
      _client.post('/staff/attendance/$sessionId', body: {
        'present': presentStudentIds,
        'lock': lock,
      });

  Future<Map<String, dynamic>> grading() => _client.get('/staff/grading');

  Future<Map<String, dynamic>> recordCat(Map<String, dynamic> body) =>
      _client.post('/staff/grading/cat', body: body);

  Future<Map<String, dynamic>> lessonPlans() =>
      _client.get('/staff/lesson-plans');

  Future<Map<String, dynamic>> createLessonPlan(Map<String, dynamic> body) =>
      _client.post('/staff/lesson-plans', body: body);

  Future<Map<String, dynamic>> submitLessonPlan(int id) =>
      _client.post('/staff/lesson-plans/$id/submit');

  Future<Map<String, dynamic>> content() => _client.get('/staff/content');

  Future<Map<String, dynamic>> notifications() =>
      _client.get('/staff/notifications');
}
