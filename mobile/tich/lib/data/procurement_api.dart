import 'api_client.dart';

class ProcurementApi {
  ProcurementApi(this._client);

  final ApiClient _client;

  Future<Map<String, dynamic>> dashboard() =>
      _client.get('/procurement/dashboard');

  Future<Map<String, dynamic>> sidebarNotifications() =>
      _client.get('/procurement/sidebar-notifications');

  Future<Map<String, dynamic>> requisitions({String? search, String? status}) =>
      _client.get('/procurement/requisitions', query: {
        if (search != null && search.isNotEmpty) 'search': search,
        if (status != null && status.isNotEmpty) 'status': status,
      });

  Future<Map<String, dynamic>> requisition(int id) =>
      _client.get('/procurement/requisitions/$id');

  Future<Map<String, dynamic>> approveRequisition(int id, {String? comments}) =>
      _client.post('/procurement/requisitions/$id/approve', body: {
        if (comments != null && comments.isNotEmpty) 'comments': comments,
      });

  Future<Map<String, dynamic>> rejectRequisition(int id, {required String comments}) =>
      _client.post('/procurement/requisitions/$id/reject', body: {
        'comments': comments,
      });

  Future<Map<String, dynamic>> submitRequisition(int id) =>
      _client.post('/procurement/requisitions/$id/submit');

  Future<Map<String, dynamic>> stockIssues() =>
      _client.get('/procurement/stock-issues');

  Future<Map<String, dynamic>> stockIssue(int id) =>
      _client.get('/procurement/stock-issues/$id');

  Future<Map<String, dynamic>> approveStockIssue(int id) =>
      _client.post('/procurement/stock-issues/$id/approve');

  Future<Map<String, dynamic>> grns({String? status}) =>
      _client.get('/procurement/grns', query: {
        if (status != null && status.isNotEmpty) 'status': status,
      });

  Future<Map<String, dynamic>> grn(int id) =>
      _client.get('/procurement/grns/$id');

  Future<Map<String, dynamic>> completeGrnInspection(
    int id, {
    String? inspectionNotes,
  }) =>
      _client.post('/procurement/grns/$id/complete-inspection', body: {
        if (inspectionNotes != null && inspectionNotes.isNotEmpty)
          'inspection_notes': inspectionNotes,
      });

  Future<Map<String, dynamic>> stockAlerts({String? status}) =>
      _client.get('/procurement/stock-alerts', query: {
        if (status != null && status.isNotEmpty) 'status': status,
      });

  Future<Map<String, dynamic>> stockAlert(int id) =>
      _client.get('/procurement/stock-alerts/$id');

  Future<Map<String, dynamic>> assets({String? search}) =>
      _client.get('/procurement/assets', query: {
        if (search != null && search.isNotEmpty) 'search': search,
      });

  Future<Map<String, dynamic>> asset(int id) =>
      _client.get('/procurement/assets/$id');

  Future<Map<String, dynamic>> autoReorderStockAlert(int id) =>
      _client.post('/procurement/stock-alerts/$id/auto-reorder');

  Future<Map<String, dynamic>> suppliers({String? search}) =>
      _client.get('/procurement/suppliers', query: {
        if (search != null && search.isNotEmpty) 'search': search,
      });

  Future<Map<String, dynamic>> supplier(int id) =>
      _client.get('/procurement/suppliers/$id');

  Future<Map<String, dynamic>> verifySupplier(
    int id, {
    required String complianceStatus,
    String? riskRating,
    String? supplierCategory,
  }) =>
      _client.post('/procurement/suppliers/$id/verify', body: {
        'compliance_status': complianceStatus,
        if (riskRating != null) 'risk_rating': riskRating,
        if (supplierCategory != null) 'supplier_category': supplierCategory,
      });

  Future<Map<String, dynamic>> rfqs({String? search, String? status}) =>
      _client.get('/procurement/rfqs', query: {
        if (search != null && search.isNotEmpty) 'search': search,
        if (status != null && status.isNotEmpty) 'status': status,
      });

  Future<Map<String, dynamic>> rfq(int id) => _client.get('/procurement/rfqs/$id');

  Future<Map<String, dynamic>> approveRfqAward(int id, {String? notes}) =>
      _client.post('/procurement/rfqs/$id/approve-award', body: {
        if (notes != null && notes.isNotEmpty) 'notes': notes,
      });

  Future<Map<String, dynamic>> rejectRfqAward(int id, {required String notes}) =>
      _client.post('/procurement/rfqs/$id/reject-award', body: {'notes': notes});

  Future<Map<String, dynamic>> inventoryItems({String? search}) =>
      _client.get('/procurement/inventory-items', query: {
        if (search != null && search.isNotEmpty) 'search': search,
      });

  Future<Map<String, dynamic>> inventoryItem(int id) =>
      _client.get('/procurement/inventory-items/$id');

  Future<Map<String, dynamic>> assetDisposals({String? status}) =>
      _client.get('/procurement/asset-disposals', query: {
        if (status != null && status.isNotEmpty) 'status': status,
      });

  Future<Map<String, dynamic>> assetDisposal(int id) =>
      _client.get('/procurement/asset-disposals/$id');

  Future<Map<String, dynamic>> approveAssetDisposal(int id) =>
      _client.post('/procurement/asset-disposals/$id/approve');

  Future<Map<String, dynamic>> assetAudits({String? status}) =>
      _client.get('/procurement/asset-audits', query: {
        if (status != null && status.isNotEmpty) 'status': status,
      });

  Future<Map<String, dynamic>> assetAudit(int id) =>
      _client.get('/procurement/asset-audits/$id');

  Future<Map<String, dynamic>> reviewAssetAudit(
    int id, {
    required String verificationStatus,
    required String condition,
    String? reviewNotes,
  }) =>
      _client.post('/procurement/asset-audits/$id/review', body: {
        'verification_status': verificationStatus,
        'condition': condition,
        if (reviewNotes != null && reviewNotes.isNotEmpty)
          'review_notes': reviewNotes,
      });

  Future<Map<String, dynamic>> paymentVerification() =>
      _client.get('/procurement/payment-verification');
}
