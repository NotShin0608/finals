<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include necessary files
require_once 'config/functions.php';

// Get recent disbursements
$disbursements = getRecentDisbursements(20);
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
                    <h1 class="h2">Cash Disbursement</h1>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-12 text-end">
                        <a href="add-disbursement.php" class="btn btn-primary">New Disbursement</a>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <ul class="nav nav-tabs">
                            <li class="nav-item">
                                <a class="nav-link active" href="#">Recent</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="disbursement-reports.php">Reports</a>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Recent Disbursements</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Payee</th>
                                        <th>Date</th>
                                        <th>Voucher #</th>
                                        <th class="text-end">Amount</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($disbursements as $disbursement): ?>
                                    <tr>
                                        <td><?php echo $disbursement['payee']; ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($disbursement['disbursement_date'])); ?></td>
                                        <td><?php echo $disbursement['voucher_number']; ?></td>
                                        <td class="text-end"><?php echo formatCurrency($disbursement['amount']); ?></td>
                                        <td><span class="badge bg-success"><?php echo $disbursement['status']; ?></span></td>
                                        <td>
                                            <a href="view-disbursement.php?id=<?php echo $disbursement['id']; ?>" class="btn btn-sm btn-info">View</a>
                                            <a href="print-voucher.php?id=<?php echo $disbursement['id']; ?>" class="btn btn-sm btn-secondary">Print</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($disbursements)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No disbursements found</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
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
