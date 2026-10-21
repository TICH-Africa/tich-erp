import 'api_client.dart';

class StudentApi {
  StudentApi(this._client);

  final ApiClient _client;

  Future<Map<String, dynamic>> dashboard() => _client.get('/student/dashboard');

  Future<Map<String, dynamic>> academics() => _client.get('/student/academics');

  Future<List<int>> downloadExamCardPdf({required int semesterId}) =>
      _client.getBytes(
        '/student/academics/exam-card/pdf',
        query: {'semester': '$semesterId'},
      );

  Future<Map<String, dynamic>> timetable() => _client.get('/student/timetable');

  Future<Map<String, dynamic>> finance() => _client.get('/student/finance');

  Future<Map<String, dynamic>> payInvoice(
    int invoiceId, {
    required double amount,
    required String paymentMethod,
    String? phoneNumber,
    String? paymentReference,
  }) =>
      _client.post('/student/finance/invoices/$invoiceId/pay', body: {
        'amount': amount,
        'payment_method': paymentMethod,
        if (phoneNumber != null) 'phone_number': phoneNumber,
        if (paymentReference != null) 'payment_reference': paymentReference,
      });

  Future<Map<String, dynamic>> stkStatus(int stkRequestId) =>
      _client.get('/student/finance/mpesa/$stkRequestId/status');

  Future<Map<String, dynamic>> profile() => _client.get('/student/profile');

  Future<Map<String, dynamic>> updateProfile(Map<String, dynamic> body) =>
      _client.put('/student/profile', body: body);

  Future<Map<String, dynamic>> documents() => _client.get('/student/documents');

  Future<Map<String, dynamic>> requestDocument({
    required String documentType,
    String? notes,
  }) =>
      _client.post('/student/documents/requests', body: {
        'document_type': documentType,
        if (notes != null) 'student_notes': notes,
      });

  Future<Map<String, dynamic>> notifications() =>
      _client.get('/student/notifications');

  Future<Map<String, dynamic>> sidebarNotifications() =>
      _client.get('/student/sidebar-notifications');

  Future<Map<String, dynamic>> markNotificationRead(int id) =>
      _client.post('/student/notifications/$id/read');

  Future<Map<String, dynamic>> clearance() => _client.get('/student/clearance');

  Future<Map<String, dynamic>> ensureClearance() =>
      _client.post('/student/clearance/ensure');

  Future<Map<String, dynamic>> evaluations() =>
      _client.get('/student/evaluations');

  Future<Map<String, dynamic>> submitEvaluation(Map<String, dynamic> body) =>
      _client.post('/student/evaluations', body: body);

  Future<Map<String, dynamic>> lifecycleRequests() =>
      _client.get('/student/requests/lifecycle');

  Future<Map<String, dynamic>> submitSuggestion({
    required String category,
    required String body,
    String? subject,
    bool anonymous = false,
  }) =>
      _client.post('/student/requests/suggestions', body: {
        'category': category,
        'body': body,
        if (subject != null) 'subject': subject,
        'is_anonymous': anonymous,
      });

  Future<Map<String, dynamic>> requestTranscript({
    required String deliveryMethod,
    String? notes,
  }) =>
      _client.post('/student/requests/transcript', body: {
        'delivery_method': deliveryMethod,
        if (notes != null) 'student_notes': notes,
      });

  Future<Map<String, dynamic>> assessment(int id) =>
      _client.get('/student/assessments/$id');

  Future<Map<String, dynamic>> submitAssessment(
    int id, {
    required Map<String, String> responses,
    int? timeTakenSeconds,
  }) =>
      _client.post('/student/assessments/$id/submit', body: {
        'responses': responses,
        if (timeTakenSeconds != null) 'time_taken_seconds': timeTakenSeconds,
      });

  Future<Map<String, dynamic>> submitLifecycle({
    required int defermentMonths,
    required String reason,
    required List<({String path, String name})> attachments,
  }) =>
      _client.multipartFiles(
        '/student/requests/lifecycle',
        fields: {
          'deferment_months': '$defermentMonths',
          'reason': reason,
        },
        files: [
          for (final f in attachments)
            (field: 'attachments[]', path: f.path, name: f.name, mimeType: null),
        ],
      );

  Future<Map<String, dynamic>> submitExamSitting({
    required String requestType,
    required int unitId,
    required int semesterId,
    String? reason,
    List<({String path, String name})> attachments = const [],
  }) =>
      _client.multipartFiles(
        '/student/requests/exam-sitting',
        fields: {
          'request_type': requestType,
          'unit_id': '$unitId',
          'semester_id': '$semesterId',
          if (reason != null) 'reason': reason,
        },
        files: [
          for (final f in attachments)
            (field: 'attachments[]', path: f.path, name: f.name, mimeType: null),
        ],
      );
}
