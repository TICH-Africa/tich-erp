<?php

$pdo = new PDO('mysql:host=127.0.0.1;dbname=tich_erp', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

// Dependency order: parents before children (InnoDB/MariaDB still validates
// referenced tables exist even with FOREIGN_KEY_CHECKS=0 on many hosts).
$tables = [
    'asset_audits',
    'asset_disposals',
    'asset_maintenance',
    'asset_movements',
    'marketing_leads',
    'marketing_lead_activities',
    'marketing_reports',
    'marketing_report_attachments',
    'qa_certification_reminders',
    'qa_training_credits',
    'qa_training_event_enrolments',
    'qa_training_event_registrations',
    'rfqs',
    'rfq_clarifications',
    'rfq_evaluations',
    'rfq_quotations',
    'rfq_suppliers',
    'procurement_invoices',
    'discrepancies',
    'credit_notes',
    'payment_audits',
    'procurement_payments',
    'grn_items',
    'stock_alerts',
    'stock_issues',
];

$out = [];
$out[] = '-- Missing production tables (excluding academic_records + student_financial_records already applied)';
$out[] = '-- Ordered by FK dependency (parents before children).';
$out[] = '-- Generated from local tich_erp '.date('Y-m-d H:i:s');
$out[] = 'SET NAMES utf8mb4;';
$out[] = 'SET FOREIGN_KEY_CHECKS = 0;';
$out[] = '';

foreach ($tables as $table) {
    $row = $pdo->query('SHOW CREATE TABLE `'.$table.'`')->fetch(PDO::FETCH_ASSOC);
    $create = $row['Create Table'] ?? null;
    if (! $create) {
        $create = array_values($row)[1] ?? null;
    }
    if (! $create) {
        fwrite(STDERR, "Missing local table: {$table}\n");
        continue;
    }
    $create = preg_replace('/^CREATE TABLE /', 'CREATE TABLE IF NOT EXISTS ', $create);
    $create = preg_replace('/ AUTO_INCREMENT=\d+/', '', $create);
    $out[] = '-- -----------------------------------------------------------------------------';
    $out[] = '-- Table: `'.$table.'`';
    $out[] = '-- -----------------------------------------------------------------------------';
    $out[] = $create.';';
    $out[] = '';
}

$out[] = 'SET FOREIGN_KEY_CHECKS = 1;';

$path = dirname(__DIR__, 2).'/deploy/missing-tables-prod-patch.sql';
file_put_contents($path, implode("\n", $out)."\n");
echo "Wrote {$path}\n";
echo 'Tables: '.count($tables)."\n";
