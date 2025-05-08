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

// Process edit form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = $_POST['id'];
    $accountCode = $_POST['account_code'];
    $accountName = $_POST['account_name'];
    $accountType = $_POST['account_type'];
    $description = $_POST['description'];
    
    if (updateAccount($id, $accountCode, $accountName, $accountType, $description)) {
        $success = "Account updated successfully";
        // Refresh accounts list
        $accounts = getAllAccounts();
    } else {
        $error = "Error updating account";
    }
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
                    <h1 class="h2">Chart of Accounts</h1>
                </div>
                
                <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
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
                                        <td><?php echo htmlspecialchars($account['account_code']); ?></td>
                                        <td><?php echo htmlspecialchars($account['account_name']); ?></td>
                                        <td><?php echo htmlspecialchars($account['account_type']); ?></td>
                                        <td class="text-end"><?php echo formatCurrency($account['balance']); ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editModal<?php echo $account['id']; ?>">
                                                Edit
                                            </button>
                                            <button type="button" class="btn btn-sm btn-info"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#viewModal<?php echo $account['id']; ?>">
                                                View
                                            </button>
                                        </td>
                                    </tr>
                                    
                                    <!-- Edit Modal -->
                                    <div class="modal fade" id="editModal<?php echo $account['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Account</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form action="" method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="action" value="edit">
                                                        <input type="hidden" name="id" value="<?php echo $account['id']; ?>">
                                                        
                                                        <div class="mb-3">
                                                            <label for="account_code" class="form-label">Account Code</label>
                                                            <input type="text" class="form-control" name="account_code" 
                                                                   value="<?php echo htmlspecialchars($account['account_code']); ?>" required>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label for="account_type" class="form-label">Account Type</label>
                                                            <select class="form-select" name="account_type" required>
                                                                <?php
                                                                $types = ['Asset', 'Liability', 'Equity', 'Income', 'Expense'];
                                                                foreach ($types as $type):
                                                                    $selected = ($type === $account['account_type']) ? 'selected' : '';
                                                                ?>
                                                                <option value="<?php echo $type; ?>" <?php echo $selected; ?>>
                                                                    <?php echo $type; ?>
                                                                </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label for="account_name" class="form-label">Account Name</label>
                                                            <select class="form-select account-name-select" name="account_name" required>
                                                                <?php
                                                                $accountNames = getAccountNamesByType($account['account_type']);
                                                                foreach ($accountNames as $name):
                                                                    $selected = ($name['name'] === $account['account_name']) ? 'selected' : '';
                                                                ?>
                                                                <option value="<?php echo htmlspecialchars($name['name']); ?>" <?php echo $selected; ?>>
                                                                    <?php echo htmlspecialchars($name['name']); ?>
                                                                </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label for="description" class="form-label">Description</label>
                                                            <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($account['description']); ?></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- View Modal -->
                                    <div class="modal fade" id="viewModal<?php echo $account['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Account Details</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <dl class="row">
                                                        <dt class="col-sm-4">Account Code</dt>
                                                        <dd class="col-sm-8"><?php echo htmlspecialchars($account['account_code']); ?></dd>
                                                        
                                                        <dt class="col-sm-4">Account Name</dt>
                                                        <dd class="col-sm-8"><?php echo htmlspecialchars($account['account_name']); ?></dd>
                                                        
                                                        <dt class="col-sm-4">Account Type</dt>
                                                        <dd class="col-sm-8"><?php echo htmlspecialchars($account['account_type']); ?></dd>
                                                        
                                                        <dt class="col-sm-4">Current Balance</dt>
                                                        <dd class="col-sm-8"><?php echo formatCurrency($account['balance']); ?></dd>
                                                        
                                                        <dt class="col-sm-4">Description</dt>
                                                        <dd class="col-sm-8"><?php echo htmlspecialchars($account['description']); ?></dd>
                                                    </dl>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
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
    
    <script>
    // Update account names when account type changes
    document.querySelectorAll('select[name="account_type"]').forEach(select => {
        select.addEventListener('change', function() {
            const accountType = this.value;
            const modal = this.closest('.modal');
            const accountNameSelect = modal.querySelector('.account-name-select');
            
            fetch(`get_account_names.php?type=${accountType}`)
                .then(response => response.json())
                .then(data => {
                    accountNameSelect.innerHTML = '';
                    data.forEach(account => {
                        const option = new Option(account.name, account.name);
                        accountNameSelect.add(option);
                    });
                });
        });
    });
    </script>
</body>
</html>