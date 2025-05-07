<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/functions.php';

// Get filters from URL parameters
$filters = [];
if (!empty($_GET['start_date'])) $filters['start_date'] = $_GET['start_date'];
if (!empty($_GET['end_date'])) $filters['end_date'] = $_GET['end_date'];
if (!empty($_GET['reference'])) $filters['reference'] = $_GET['reference'];
if (!empty($_GET['account'])) $filters['account'] = $_GET['account'];

// Get all entries without pagination
$entries = getLedgerEntries(null, 0, $filters);

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="general_ledger_export_' . date('Y-m-d_His') . '.csv"');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for proper Excel encoding
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add CSV headers
fputcsv($output, [
    'Date',
    'Reference Number',
    'Account Code',
    'Account Name',
    'Description',
    'Debit Amount',
    'Credit Amount'
]);

// Add data rows
foreach ($entries as $entry) {
    fputcsv($output, [
        date('Y-m-d', strtotime($entry['transaction_date'])),
        $entry['reference_number'],
        $entry['account_code'],
        $entry['account_name'],
        $entry['description'],
        number_format($entry['debit_amount'], 2, '.', ''),
        number_format($entry['credit_amount'], 2, '.', '')
    ]);
}

// Close the file pointer
fclose($output);
exit();