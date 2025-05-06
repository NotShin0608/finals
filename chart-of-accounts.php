<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include necessary files
require_once 'config/functions.php';

// Get all accounts
$accounts = getAllAccounts();
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
                    <h1 class="h2">Chart of Accounts</h1>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-12 text-end">
                        <a href="add-account.php" class="btn btn-primary">New Account</a>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Account List</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Account Code</th>
                                        <th>Account Name</th>
                                        <th>Type</th>
                                        <th class="text-end">Balance</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($accounts as $account): ?>
                                    <tr>
                                        <td><?php echo $account['account_code']; ?></td>
                                        <td><?php echo $account['account_name']; ?></td>
                                        <td><?php echo $account['account_type']; ?></td>
                                        <td class="text-end"><?php echo formatCurrency($account['balance']); ?></td>
                                        <td>
                                            <a href="edit-account.php?id=<?php echo $account['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                            <a href="view-account.php?id=<?php echo $account['id']; ?>" class="btn btn-sm btn-info">View</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($accounts)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No accounts found</td>
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
