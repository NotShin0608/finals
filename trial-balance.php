<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include necessary files
require_once 'config/functions.php';

// Get date for trial balance
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Get trial balance
$trialBalance = getTrialBalance($date);

// Calculate totals
$totalDebit = 0;
$totalCredit = 0;
foreach ($trialBalance as $item) {
    $totalDebit += $item['total_debit'];
    $totalCredit += $item['total_credit'];
}
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
                    <h1 class="h2">Trial Balance</h1>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <form action="" method="GET" class="d-flex">
                            <div class="input-group">
                                <span class="input-group-text">As of Date</span>
                                <input type="date" name="date" class="form-control" value="<?php echo $date; ?>">
                                <button type="submit" class="btn btn-primary">Generate</button>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-6 text-end">
                        <button onclick="window.print()" class="btn btn-secondary">Print</button>
                        <a href="export-trial-balance.php?date=<?php echo $date; ?>" class="btn btn-success">Export</a>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Trial Balance as of <?php echo date('F j, Y', strtotime($date)); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Account</th>
                                        <th class="text-end">Debit</th>
                                        <th class="text-end">Credit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($trialBalance as $item): ?>
                                    <tr>
                                        <td><?php echo $item['account_code'] . ' - ' . $item['account_name']; ?></td>
                                        <td class="text-end"><?php echo $item['total_debit'] > 0 ? formatCurrency($item['total_debit']) : ''; ?></td>
                                        <td class="text-end"><?php echo $item['total_credit'] > 0 ? formatCurrency($item['total_credit']) : ''; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($trialBalance)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center">No data found</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="fw-bold">
                                        <td>Total</td>
                                        <td class="text-end"><?php echo formatCurrency($totalDebit); ?></td>
                                        <td class="text-end"><?php echo formatCurrency($totalCredit); ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
