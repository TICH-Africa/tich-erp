import '../../data/procurement_api.dart';
import 'procurement_helpers.dart';

/// Starts detail/create payloads before route transition (stale-while-revalidate).
abstract final class ProcurementPrefetch {
  static const requisitionFormOptionsKey = 'procurement.requisition_form_options';
  static const stockIssueFormOptionsKey = 'procurement.stock_issue_form_options';
  static const assetDisposalFormOptionsKey =
      'procurement.asset_disposal_form_options';

  static void requisitionFormOptions(ProcurementApi api) {
    procurementWarm(requisitionFormOptionsKey, () => api.requisitionFormOptions());
  }

  static void stockIssueFormOptions(ProcurementApi api) {
    procurementWarm(stockIssueFormOptionsKey, () => api.stockIssueFormOptions());
  }

  static void assetDisposalFormOptions(ProcurementApi api) {
    procurementWarm(
      assetDisposalFormOptionsKey,
      () => api.assetDisposalFormOptions(),
    );
  }

  static void requisition(ProcurementApi api, int id) {
    procurementWarm(
      procurementDetailCacheKey('procurement.requisition', id),
      () => api.requisition(id),
    );
  }

  static void stockIssue(ProcurementApi api, int id) {
    procurementWarm(
      procurementDetailCacheKey('procurement.stockIssue', id),
      () => api.stockIssue(id),
    );
  }

  static void grn(ProcurementApi api, int id) {
    procurementWarm(
      procurementDetailCacheKey('procurement.grn', id),
      () => api.grn(id),
    );
  }

  static void stockAlert(ProcurementApi api, int id) {
    procurementWarm(
      procurementDetailCacheKey('procurement.stockAlert', id),
      () => api.stockAlert(id),
    );
  }

  static void asset(ProcurementApi api, int id) {
    procurementWarm(
      procurementDetailCacheKey('procurement.asset', id),
      () => api.asset(id),
    );
  }

  static void supplier(ProcurementApi api, int id) {
    procurementWarm(
      procurementDetailCacheKey('procurement.supplier', id),
      () => api.supplier(id),
    );
  }

  static void rfq(ProcurementApi api, int id) {
    procurementWarm(
      procurementDetailCacheKey('procurement.rfq', id),
      () => api.rfq(id),
    );
  }

  static void inventoryItem(ProcurementApi api, int id) {
    procurementWarm(
      procurementDetailCacheKey('procurement.inventory_item', id),
      () => api.inventoryItem(id),
    );
  }

  static void assetDisposal(ProcurementApi api, int id) {
    procurementWarm(
      procurementDetailCacheKey('procurement.assetDisposal', id),
      () => api.assetDisposal(id),
    );
  }

  static void assetAudit(ProcurementApi api, int id) {
    procurementWarm(
      procurementDetailCacheKey('procurement.assetAudit', id),
      () => api.assetAudit(id),
    );
  }
}
