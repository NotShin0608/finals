<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include necessary files
require_once 'config/functions.php';

if (!function_exists('getTrialBalance')) {
    function getTrialBalance($startDate, $endDate) {
        $conn = getConnection();
        try {
            $sql = "SELECT 
                    a.account_code, 
                    a.account_name, 
                    a.account_type,
                    SUM(CASE 
                        WHEN td.debit_amount > 0 
                        AND t.transaction_date BETWEEN ? AND ? 
                        THEN td.debit_amount 
                        ELSE 0 
                    END) as total_debit,
                    SUM(CASE 
                        WHEN td.credit_amount > 0 
                        AND t.transaction_date BETWEEN ? AND ? 
                        THEN td.credit_amount 
                        ELSE 0 
                    END) as total_credit
                    FROM accounts a
                    LEFT JOIN transaction_details td ON a.id = td.account_id
                    LEFT JOIN transactions t ON td.transaction_id = t.id AND t.status = 'Posted'
                    GROUP BY a.id
                    ORDER BY a.account_code";
                    
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $startDate, $endDate, $startDate, $endDate);
            $stmt->execute();
            $result = $stmt->get_result();
            $trialBalance = [];
            
            while($row = $result->fetch_assoc()) {
                $trialBalance[] = $row;
            }
            
            return $trialBalance;
        } finally {
            closeConnection($conn);
        }
    }
}

// Get start and end dates from GET parameters, default to current month
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // First day of current month

// Get trial balance
$trialBalance = getTrialBalance($startDate, $endDate);

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
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            .card {
                border: none !important;
            }
            .card-header {
                background-color: white !important;
            }
        }
    </style>
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
                
                <div class="row mb-4 no-print">
                    <div class="col-md-8">
                        <form action="" method="GET" class="d-flex gap-2">
                            <div class="input-group">
                                <span class="input-group-text">Date Range</span>
                                <input type="date" name="start_date" class="form-control" value="<?php echo $startDate; ?>" required>
                                <span class="input-group-text">to</span>
                                <input type="date" name="end_date" class="form-control" value="<?php echo $endDate; ?>" required>
                                <button type="submit" class="btn btn-primary">Generate</button>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-4 text-end">
                        <button onclick="window.print()" class="btn btn-secondary">
                            <i class="bi bi-printer"></i> Print
                        </button>
                        <a href="export-trial-balance.php?start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" class="btn btn-success">
                            <i class="bi bi-download"></i> Export
                        </a>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            Trial Balance (<?php echo date('F j, Y', strtotime($startDate)); ?> - <?php echo date('F j, Y', strtotime($endDate)); ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Account Code</th>
                                        <th>Account Name</th>
                                        <th>Type</th>
                                        <th class="text-end">Debit</th>
                                        <th class="text-end">Credit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($trialBalance as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['account_code']); ?></td>
                                        <td><?php echo htmlspecialchars($item['account_name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['account_type']); ?></td>
                                        <td class="text-end"><?php echo $item['total_debit'] > 0 ? number_format($item['total_debit'], 2) : ''; ?></td>
                                        <td class="text-end"><?php echo $item['total_credit'] > 0 ? number_format($item['total_credit'], 2) : ''; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($trialBalance)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No data found</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="fw-bold">
                                        <td colspan="3">Total</td>
                                        <td class="text-end"><?php echo number_format($totalDebit, 2); ?></td>
                                        <td class="text-end"><?php echo number_format($totalCredit, 2); ?></td>
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