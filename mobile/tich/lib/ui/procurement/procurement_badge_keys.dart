/// Maps mobile module keys to web procurement sidebar count keys.
const procurementModuleBadgeKey = <String, String>{
  'requisitions': 'requisitions.pending',
  'grns': 'grns.pending',
  'stock_issues': 'stock-issues.pending',
  'stock_alerts': 'stock-alerts.active',
  'suppliers': 'suppliers.pending',
  'rfqs': 'rfqs.actionable',
  'disposals': 'asset-disposals.pending',
  'audits': 'asset-audits.pending',
};

const procurementHomeBadgeKeys = [
  'requisitions.pending',
  'grns.pending',
  'stock-issues.pending',
  'stock-alerts.active',
  'rfqs.actionable',
  'suppliers.pending',
  'asset-disposals.pending',
  'asset-audits.pending',
];
