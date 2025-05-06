<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include necessary files
require_once 'config/functions.php';

// Get financial summary data
$totalAssets = getTotalAssets();
$totalLiabilities = getTotalLiabilities();
$totalEquity = getTotalEquity();
$netIncome = getNetIncome();

// Get recent transactions
$recentTransactions = getRecentTransactions(10);

// Get recent disbursements
$recentDisbursements = getRecentDisbursements(5);

// Get recent payments
$recentPayments = getRecentPayments(5);

// Set page title
$pageTitle = "Dashboard";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'includes/head.php'; ?>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo formatCurrency($totalAssets); ?></h5>
                                <p class="card-text text-muted">Total Assets</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo formatCurrency($totalLiabilities); ?></h5>
                                <p class="card-text text-muted">Total Liabilities</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo formatCurrency($totalEquity); ?></h5>
                                <p class="card-text text-muted">Total Equity</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo formatCurrency($netIncome); ?></h5>
                                <p class="card-text text-muted">Net Income</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Recent Transactions</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Reference</th>
                                                <th>Description</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                                <th>Created By</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentTransactions as $transaction): ?>
                                            <tr>
                                                <td><?php echo date('Y-m-d', strtotime($transaction['transaction_date'])); ?></td>
                                                <td><?php echo $transaction['reference_number']; ?></td>
                                                <td><?php echo $transaction['description']; ?></td>
                                                <td><?php echo formatCurrency($transaction['amount']); ?></td>
                                                <td><span class="badge bg-success"><?php echo $transaction['status']; ?></span></td>
                                                <td><?php echo $transaction['created_by']; ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($recentTransactions)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center">No transactions found</td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Recent Payments</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Receipt #</th>
                                                <th>Payer</th>
                                                <th>Method</th>
                                                <th>Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentPayments as $payment): ?>
                                            <tr>
                                                <td><?php echo date('Y-m-d', strtotime($payment['payment_date'])); ?></td>
                                                <td><?php echo $payment['receipt_number']; ?></td>
                                                <td><?php echo $payment['payer']; ?></td>
                                                <td><?php echo $payment['payment_method']; ?></td>
                                                <td><?php echo formatCurrency($payment['amount']); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($recentPayments)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center">No payments found</td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Recent Disbursements</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Voucher #</th>
                                                <th>Payee</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentDisbursements as $disbursement): ?>
                                            <tr>
                                                <td><?php echo date('Y-m-d', strtotime($disbursement['disbursement_date'])); ?></td>
                                                <td><?php echo $disbursement['voucher_number']; ?></td>
                                                <td><?php echo $disbursement['payee']; ?></td>
                                                <td><?php echo formatCurrency($disbursement['amount']); ?></td>
                                                <td><span class="badge bg-success"><?php echo $disbursement['status']; ?></span></td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($recentDisbursements)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center">No disbursements found</td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
