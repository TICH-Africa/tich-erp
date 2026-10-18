import 'api_client.dart';

class EmployeeApi {
  EmployeeApi(this._client);

  final ApiClient _client;

  Future<Map<String, dynamic>> dashboard() => _client.get('/employee/dashboard');

  Future<Map<String, dynamic>> notifications() =>
      _client.get('/employee/notifications');

  Future<Map<String, dynamic>> markNotificationRead(int id) =>
      _client.post('/employee/notifications/$id/read');

  Future<Map<String, dynamic>> profile() => _client.get('/employee/profile');

  Future<Map<String, dynamic>> updateProfile(Map<String, dynamic> body) =>
      _client.put('/employee/profile', body: body);

  Future<Map<String, dynamic>> leave() => _client.get('/employee/leave');

  Future<Map<String, dynamic>> submitLeave(Map<String, dynamic> body) =>
      _client.post('/employee/leave', body: body);

  Future<Map<String, dynamic>> cancelLeave(int id, {String? reason}) =>
      _client.post('/employee/leave/$id/cancel', body: {
        if (reason != null) 'cancellation_reason': reason,
      });

  Future<Map<String, dynamic>> acceptCoverage(int id, {String? notes}) =>
      _client.post('/employee/leave/coverages/$id/accept', body: {
        if (notes != null) 'response_notes': notes,
      });

  Future<Map<String, dynamic>> declineCoverage(int id, {String? notes}) =>
      _client.post('/employee/leave/coverages/$id/decline', body: {
        if (notes != null) 'response_notes': notes,
      });

  Future<Map<String, dynamic>> attendance() =>
      _client.get('/employee/attendance');

  Future<Map<String, dynamic>> clockIn(Map<String, dynamic> body) =>
      _client.post('/employee/attendance/clock-in', body: body);

  Future<Map<String, dynamic>> clockOut({String? notes}) =>
      _client.post('/employee/attendance/clock-out', body: {
        if (notes != null) 'notes': notes,
      });

  Future<Map<String, dynamic>> documents() =>
      _client.get('/employee/documents');

  Future<Map<String, dynamic>> uploadDocument({
    required String type,
    required String name,
    required String filePath,
    required String fileName,
  }) =>
      _client.multipart(
        '/employee/documents',
        fields: {
          'document_type': type,
          'document_name': name,
        },
        fileField: 'file',
        filePath: filePath,
        fileName: fileName,
      );

  Future<Map<String, dynamic>> payslips() => _client.get('/employee/payslips');

  Future<Map<String, dynamic>> payslip(int id) =>
      _client.get('/employee/payslips/$id');

  Future<Map<String, dynamic>> policies() => _client.get('/employee/policies');

  Future<Map<String, dynamic>> acknowledgePolicy(
    int id, {
    required String fullName,
    required String employeeNumber,
  }) =>
      _client.post('/employee/policies/$id/acknowledge', body: {
        'full_name': fullName,
        'employee_number': employeeNumber,
        'read_confirmed': true,
      });

  Future<Map<String, dynamic>> wfh() => _client.get('/employee/wfh');

  Future<Map<String, dynamic>> submitWfh({
    required String workDate,
    required String reason,
    String startTime = '08:00',
    String endTime = '17:00',
  }) =>
      _client.post('/employee/wfh', body: {
        'work_date': workDate,
        'start_time': startTime,
        'end_time': endTime,
        'remote_tasks': [reason],
        'reason': reason,
      });

  Future<Map<String, dynamic>> concerns() => _client.get('/employee/concerns');

  Future<Map<String, dynamic>> submitConcern(Map<String, dynamic> body) =>
      _client.post('/employee/concerns', body: body);

  Future<Map<String, dynamic>> appraisals() =>
      _client.get('/employee/appraisals');

  Future<Map<String, dynamic>> timeLogs() =>
      _client.get('/employee/time-logs');

  Future<Map<String, dynamic>> timeLogWeeks({
    required int year,
    required int month,
  }) =>
      _client.get(
        '/employee/time-logs/weeks?year=$year&month=$month',
      );

  Future<Map<String, dynamic>> createTimeLog({
    required int year,
    required int month,
    required int weekNumber,
  }) =>
      _client.post('/employee/time-logs', body: {
        'year': year,
        'month': month,
        'week_number': weekNumber,
      });

  Future<Map<String, dynamic>> timeLog(int id) =>
      _client.get('/employee/time-logs/$id');

  Future<Map<String, dynamic>> updateTimeLog(
    int id, {
    required Map<String, dynamic> days,
  }) =>
      _client.put('/employee/time-logs/$id', body: {'days': days});

  Future<Map<String, dynamic>> submitTimeLog(
    int id, {
    required Map<String, dynamic> days,
    bool selfEndorse = false,
  }) =>
      _client.post('/employee/time-logs/$id/submit', body: {
        'days': days,
        if (selfEndorse) 'self_endorse': true,
      });

  Future<Map<String, dynamic>> feedback() => _client.get('/employee/feedback');

  Future<Map<String, dynamic>> submitFeedback({
    String? type,
    required String description,
  }) =>
      _client.post('/employee/feedback', body: {
        if (type != null && type.trim().isNotEmpty)
          'feedback_type': type.trim(),
        'description': description,
      });
}
