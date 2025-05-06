<?php
// Include header
include 'includes/header.php';

// Require accounting or admin role
requireRole(['admin']);

// Include sidebar
include 'includes/sidebar.php';

// Process form submission
$message = '';
$error = '';

// Get filter parameters
$startDate = $_GET['start_date'] ?? date('Y-m-01'); // Default to first day of current month
$endDate = $_GET['end_date'] ?? date('Y-m-t'); // Default to last day of current month
$accountId = $_GET['account_id'] ?? '';

// Get accounts for filter dropdown
$accounts = getAccounts();

// Get general ledger data
$ledgerData = [];
if (!empty($accountId)) {
    $ledgerData = getGeneralLedgerByAccount($accountId, $startDate, $endDate);
} else {
    $ledgerData = getGeneralLedger($startDate, $endDate);
}

// Log the action
logAction($_SESSION['user_id'], 'View General Ledger', 'User viewed general ledger');
?>

<!-- Main Content -->
<main class="main-content">
    <h2 class="page-title">General Ledger</h2>

    <?php if (!empty($message)): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="filter-form">
        <form method="get" action="">
            <div class="form-row">
                <div class="form-group">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($startDate); ?>">
                </div>
                
                <div class="form-group">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($endDate); ?>">
                </div>
                
                <div class="form-group" style="flex-grow: 2;">
                    <label for="account_id" class="form-label">Account</label>
                    <select id="account_id" name="account_id" class="form-control">
                        <option value="">All Accounts</option>
                        <?php foreach ($accounts as $account): ?>
                            <option value="<?php echo $account['id']; ?>" <?php echo $accountId == $account['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($account['account_code'] . ' - ' . $account['account_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group" style="align-self: flex-end;">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="general_ledger.php" class="btn">Reset</a>
                </div>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>General Ledger</h3>
            <div>
                <a href="export.php?type=general_ledger&start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>&account_id=<?php echo urlencode($accountId); ?>" class="btn btn-sm">Export to CSV</a>
                <button class="btn btn-sm print-btn">Print</button>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($ledgerData)): ?>
                <div class="empty-state">No ledger data available for the selected filters.</div>
            <?php else: ?>
                <?php foreach ($ledgerData as $accountCode => $accountData): ?>
                    <div class="ledger-account">
                        <h4><?php echo htmlspecialchars($accountCode . ' - ' . $accountData['account_name']); ?></h4>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Reference</th>
                                        <th>Description</th>
                                        <th>Debit</th>
                                        <th>Credit</th>
                                        <th>Balance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Opening Balance -->
                                    <tr class="opening-balance">
                                        <td colspan="3"><strong>Opening Balance</strong></td>
                                        <td></td>
                                        <td></td>
                                        <td class="currency"><strong><?php echo formatCurrency($accountData['opening_balance']); ?></strong></td>
                                    </tr>
                                    
                                    <!-- Transactions -->
                                    <?php foreach ($accountData['transactions'] as $transaction): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars(formatDate($transaction['transaction_date'])); ?></td>
                                            <td>
                                                <a href="view_transaction.php?id=<?php echo $transaction['transaction_id']; ?>">
                                                    <?php echo htmlspecialchars($transaction['reference']); ?>
                                                </a>
                                            </td>
                                            <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                            <td class="currency"><?php echo $transaction['debit'] > 0 ? formatCurrency($transaction['debit']) : ''; ?></td>
                                            <td class="currency"><?php echo $transaction['credit'] > 0 ? formatCurrency($transaction['credit']) : ''; ?></td>
                                            <td class="currency"><?php echo formatCurrency($transaction['running_balance']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    
                                    <!-- Closing Balance -->
                                    <tr class="closing-balance">
                                        <td colspan="3"><strong>Closing Balance</strong></td>
                                        <td class="currency"><strong><?php echo formatCurrency($accountData['total_debit']); ?></strong></td>
                                        <td class="currency"><strong><?php echo formatCurrency($accountData['total_credit']); ?></strong></td>
                                        <td class="currency"><strong><?php echo formatCurrency($accountData['closing_balance']); ?></strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<style>
.ledger-account {
    margin-bottom: 30px;
}

.ledger-account h4 {
    margin-bottom: 10px;
    padding-bottom: 5px;
    border-bottom: 1px solid var(--primary-color);
}

.opening-balance, .closing-balance {
    background-color: var(--background-color);
}

@media print {
    .header, .sidebar, .footer, .filter-form, .card-header {
        display: none !important;
    }
    
    .main-content {
        margin-left: 0 !important;
        padding: 0 !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    
    .card-body {
        padding: 0 !important;
    }
}
</style>

<?php
// Include footer
include 'includes/footer.php';
?>
