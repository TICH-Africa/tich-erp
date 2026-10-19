import 'dart:io';

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

  Future<Map<String, dynamic>> uploadSignedSheet(
    int sessionId,
    String filePath,
  ) {
    final name = _basename(filePath);
    return _client.multipart(
      '/staff/attendance/$sessionId/sheet',
      fields: const {},
      fileField: 'signed_sheet',
      filePath: filePath,
      fileName: name,
      mimeType: _imageMime(name),
    );
  }

  Future<Map<String, dynamic>> uploadClassPhoto(
    int sessionId,
    String filePath,
  ) {
    final name = _basename(filePath);
    return _client.multipart(
      '/staff/attendance/$sessionId/class-photo',
      fields: const {},
      fileField: 'class_photo',
      filePath: filePath,
      fileName: name,
      mimeType: _imageMime(name),
    );
  }

  String _basename(String filePath) {
    final parts = filePath.split(Platform.pathSeparator);
    final name = parts.isNotEmpty ? parts.last : 'photo.jpg';
    return name.isNotEmpty ? name : 'photo.jpg';
  }

  String _imageMime(String fileName) {
    final ext = fileName.split('.').last.toLowerCase();
    return switch (ext) {
      'png' => 'image/png',
      'webp' => 'image/webp',
      _ => 'image/jpeg',
    };
  }

  Future<Map<String, dynamic>> grading() => _client.get('/staff/grading');

  Future<Map<String, dynamic>> recordCat(Map<String, dynamic> body) =>
      _client.post('/staff/grading/cat', body: body);

  Future<Map<String, dynamic>> lessonPlans() =>
      _client.get('/staff/lesson-plans');

  Future<Map<String, dynamic>> lessonPlanContext({
    required int allocationId,
    String? plannedDate,
  }) =>
      _client.get('/staff/lesson-plans/context', query: {
        'allocation_id': '$allocationId',
        if (plannedDate != null && plannedDate.isNotEmpty)
          'planned_date': plannedDate,
      });

  Future<Map<String, dynamic>> createLessonPlan(Map<String, dynamic> body) =>
      _client.post('/staff/lesson-plans', body: body);

  Future<Map<String, dynamic>> submitLessonPlan(int id) =>
      _client.post('/staff/lesson-plans/$id/submit');

  Future<Map<String, dynamic>> content() => _client.get('/staff/content');

  Future<Map<String, dynamic>> uploadContent({
    required int unitId,
    required String title,
    String? contentText,
    String? filePath,
    String? fileName,
  }) {
    final fields = <String, String>{
      'unit_id': '$unitId',
      'title': title,
      if (contentText != null && contentText.trim().isNotEmpty)
        'content_text': contentText.trim(),
    };
    if (filePath != null && filePath.isNotEmpty) {
      return _client.multipart(
        '/staff/content',
        fields: fields,
        fileField: 'file',
        filePath: filePath,
        fileName: fileName ?? _basename(filePath),
      );
    }
    return _client.multipartFiles(
      '/staff/content',
      fields: fields,
      files: const [],
    );
  }

  Future<Map<String, dynamic>> notifications() =>
      _client.get('/staff/notifications');
}
