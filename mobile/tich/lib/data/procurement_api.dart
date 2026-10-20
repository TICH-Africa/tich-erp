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

  Future<Map<String, dynamic>> requisitionFormOptions() =>
      _client.get('/procurement/requisitions/form-options');

  Future<Map<String, dynamic>> requisitionBudgetLines({
    required String source,
    required int id,
  }) =>
      _client.get('/procurement/requisitions/budget-lines', query: {
        'source': source,
        'id': '$id',
      });

  Future<Map<String, dynamic>> createRequisition({
    required int requestingDepartmentId,
    required String requestDate,
    required String justification,
    String? budgetCode,
    required List<Map<String, dynamic>> lines,
    List<({String path, String name, String? mimeType})> attachments = const [],
  }) {
    if (attachments.isEmpty) {
      return _client.post('/procurement/requisitions', body: {
        'requesting_department_id': requestingDepartmentId,
        'request_date': requestDate,
        'justification': justification,
        if (budgetCode != null && budgetCode.isNotEmpty) 'budget_code': budgetCode,
        'lines': lines,
      });
    }

    final fields = <String, String>{
      'requesting_department_id': '$requestingDepartmentId',
      'request_date': requestDate,
      'justification': justification,
      if (budgetCode != null && budgetCode.isNotEmpty) 'budget_code': budgetCode,
    };
    for (var i = 0; i < lines.length; i++) {
      final line = lines[i];
      fields['lines[$i][item]'] = (line['item'] ?? '').toString();
      fields['lines[$i][quantity]'] = (line['quantity'] ?? '').toString();
      fields['lines[$i][description]'] = (line['description'] ?? '').toString();
      fields['lines[$i][unit_price]'] = (line['unit_price'] ?? '').toString();
      final unit = line['unit_of_measure'];
      if (unit != null && unit.toString().isNotEmpty) {
        fields['lines[$i][unit_of_measure]'] = unit.toString();
      }
    }

    return _client.multipartFiles(
      '/procurement/requisitions',
      fields: fields,
      files: [
        for (final file in attachments)
          (
            field: 'attachments[]',
            path: file.path,
            name: file.name,
            mimeType: file.mimeType,
          ),
      ],
    );
  }

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

  Future<Map<String, dynamic>> stockIssueFormOptions({String? staffSearch}) =>
      _client.get('/procurement/stock-issues/form-options', query: {
        if (staffSearch != null && staffSearch.isNotEmpty) 'staff_search': staffSearch,
      });

  Future<Map<String, dynamic>> createStockIssue({
    required int inventoryItemId,
    required int departmentId,
    required int requestedBy,
    required double quantity,
    required double unitCost,
    required String reason,
  }) =>
      _client.post('/procurement/stock-issues', body: {
        'inventory_item_id': inventoryItemId,
        'department_id': departmentId,
        'requested_by': requestedBy,
        'quantity': quantity,
        'unit_cost': unitCost,
        'reason': reason,
      });

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

  Future<Map<String, dynamic>> createSupplier({
    required Map<String, String> fields,
    List<({String field, String path, String name, String? mimeType})> files =
        const [],
  }) {
    if (files.isEmpty) {
      return _client.post('/procurement/suppliers', body: fields);
    }
    return _client.multipartFiles(
      '/procurement/suppliers',
      fields: fields,
      files: files,
    );
  }

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

  Future<Map<String, dynamic>> assetDisposalFormOptions() =>
      _client.get('/procurement/asset-disposals/form-options');

  Future<Map<String, dynamic>> createAssetDisposal({
    required int assetId,
    required String disposalType,
    double? disposalValue,
    String? disposalDate,
    required String reason,
  }) =>
      _client.post('/procurement/asset-disposals', body: {
        'asset_id': assetId,
        'disposal_type': disposalType,
        if (disposalValue != null) 'disposal_value': disposalValue,
        if (disposalDate != null && disposalDate.isNotEmpty)
          'disposal_date': disposalDate,
        'reason': reason,
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
