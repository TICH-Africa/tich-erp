import 'package:flutter/material.dart';

import '../../data/procurement_api.dart';
import 'procurement_pages.dart';
import 'procurement_web_handoff.dart';

void openProcurementModule(
  BuildContext context, {
  required ProcurementApi api,
  required String key,
  required String label,
  required String webPath,
  required bool mobile,
}) {
  if (!mobile) {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => ProcurementWebPlaceholderPage(
          title: label,
          description:
              'Manage $label in the procurement web module with the full desktop experience.',
          webPath: webPath,
        ),
      ),
    );
    return;
  }

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
    default:
      page = ProcurementWebPlaceholderPage(
        title: label,
        description: 'Open $label on the web portal.',
        webPath: webPath,
      );
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
    default:
      return;
  }

  Navigator.of(context).push(MaterialPageRoute(builder: (_) => page));
}
