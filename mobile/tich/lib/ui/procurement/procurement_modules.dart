import 'package:flutter/material.dart';

class ProcurementModuleEntry {
  const ProcurementModuleEntry({
    required this.key,
    required this.label,
    required this.webPath,
    required this.mobile,
    required this.icon,
  });

  final String key;
  final String label;
  final String webPath;
  final bool mobile;
  final IconData icon;
}

/// Mirrors web/mobile procurement module list.
const kProcurementModules = [
  ProcurementModuleEntry(
    key: 'requisitions',
    label: 'Requisitions',
    webPath: '/procurement/requisitions',
    mobile: true,
    icon: Icons.assignment_outlined,
  ),
  ProcurementModuleEntry(
    key: 'grns',
    label: 'GRNs',
    webPath: '/procurement/grns',
    mobile: true,
    icon: Icons.inventory_2_outlined,
  ),
  ProcurementModuleEntry(
    key: 'stock_issues',
    label: 'Stock issues',
    webPath: '/procurement/stock-issues',
    mobile: true,
    icon: Icons.output_outlined,
  ),
  ProcurementModuleEntry(
    key: 'stock_alerts',
    label: 'Stock alerts',
    webPath: '/procurement/stock-alerts',
    mobile: true,
    icon: Icons.notifications_active_outlined,
  ),
  ProcurementModuleEntry(
    key: 'assets',
    label: 'Assets',
    webPath: '/procurement/assets',
    mobile: true,
    icon: Icons.precision_manufacturing_outlined,
  ),
  ProcurementModuleEntry(
    key: 'suppliers',
    label: 'Suppliers',
    webPath: '/procurement/suppliers',
    mobile: false,
    icon: Icons.storefront_outlined,
  ),
  ProcurementModuleEntry(
    key: 'rfqs',
    label: 'RFQs',
    webPath: '/procurement/rfqs',
    mobile: false,
    icon: Icons.request_quote_outlined,
  ),
  ProcurementModuleEntry(
    key: 'inventory',
    label: 'Inventory',
    webPath: '/procurement/inventory-items',
    mobile: false,
    icon: Icons.archive_outlined,
  ),
  ProcurementModuleEntry(
    key: 'disposals',
    label: 'Disposals',
    webPath: '/procurement/asset-disposals',
    mobile: false,
    icon: Icons.delete_outline,
  ),
  ProcurementModuleEntry(
    key: 'audits',
    label: 'Audits',
    webPath: '/procurement/asset-audits',
    mobile: false,
    icon: Icons.fact_check_outlined,
  ),
  ProcurementModuleEntry(
    key: 'payments',
    label: 'Payment & verification',
    webPath: '/procurement/payment-verification',
    mobile: false,
    icon: Icons.payments_outlined,
  ),
];
