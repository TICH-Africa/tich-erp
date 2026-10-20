import 'package:flutter/material.dart';

import '../../data/procurement_api.dart';
import 'procurement_extra_pages.dart';
import 'procurement_pages.dart';

void openProcurementModule(
  BuildContext context, {
  required ProcurementApi api,
  required String key,
  required String label,
  required String webPath,
  required bool mobile,
}) {
  Widget page;
  switch (key) {
    case 'requisitions':
      page = ProcurementRequisitionsPage(api: api);
    case 'grns':
      page = ProcurementGrnsPage(api: api);
    case 'stock_issues':
      page = ProcurementStockIssuesPage(api: api);
    case 'stock_alerts':
      page = ProcurementStockAlertsPage(api: api);
    case 'assets':
      page = ProcurementAssetsPage(api: api);
    case 'suppliers':
      page = ProcurementSuppliersPage(api: api);
    case 'rfqs':
      page = ProcurementRfqsPage(api: api);
    case 'inventory':
      page = ProcurementInventoryPage(api: api);
    case 'disposals':
      page = ProcurementDisposalsPage(api: api);
    case 'audits':
      page = ProcurementAuditsPage(api: api);
    case 'payments':
      page = ProcurementPaymentsPage(api: api);
    default:
      return;
  }

  Navigator.of(context).push(MaterialPageRoute(builder: (_) => page));
}

void openProcurementInboxItem(
  BuildContext context, {
  required ProcurementApi api,
  required Map<String, dynamic> item,
}) {
  final kind = (item['kind'] ?? '').toString();
  final id = (item['id'] as num?)?.toInt();
  if (id == null) return;

  Widget page;
  switch (kind) {
    case 'requisition':
      page = ProcurementRequisitionDetailPage(api: api, id: id);
    case 'grn':
      page = ProcurementGrnDetailPage(api: api, id: id);
    case 'stock_issue':
      page = ProcurementStockIssueDetailPage(api: api, id: id);
    case 'stock_alert':
      page = ProcurementStockAlertDetailPage(api: api, id: id);
    case 'supplier':
      page = ProcurementSupplierDetailPage(api: api, id: id);
    case 'rfq':
      page = ProcurementRfqDetailPage(api: api, id: id);
    case 'asset_disposal':
      page = ProcurementDisposalDetailPage(api: api, id: id);
    case 'asset_audit':
      page = ProcurementAuditDetailPage(api: api, id: id);
    default:
      return;
  }

  Navigator.of(context).push(MaterialPageRoute(builder: (_) => page));
}
