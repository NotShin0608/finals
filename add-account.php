<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include necessary files
require_once 'config/functions.php';

$success = '';
$error = '';

// Get initial account names for Asset type
$accountNames = getAccountNamesByType('Asset');

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accountCode = $_POST['account_code'] ?? '';
    $accountName = $_POST['account_name'] ?? '';
    $accountType = $_POST['account_type'] ?? '';
    $initialBalance = $_POST['initial_balance'] ?? 0;
    $description = $_POST['description'] ?? '';
    
    if (empty($accountCode) || empty($accountName) || empty($accountType)) {
        $error = 'Please fill in all required fields';
    } else {
        $conn = getConnection();
        
        // Check if account code already exists
        $sql = "SELECT id FROM accounts WHERE account_code = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $accountCode);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Account code already exists';
        } else {
            // Insert new account
            $sql = "INSERT INTO accounts (account_code, account_name, account_type, balance, description) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssds", $accountCode, $accountName, $accountType, $initialBalance, $description);
            
            if ($stmt->execute()) {
                $success = 'Account created successfully';
                // ... rest of your existing code for initial balance ...
            } else {
                $error = 'Error creating account: ' . $conn->error;
            }
        }
        closeConnection($conn);
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
                    <h1 class="h2">Add New Account</h1>
                </div>
                
                <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-body">
                        <form action="" method="POST">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="account_code" class="form-label">Account Code *</label>
                                    <input type="text" class="form-control" id="account_code" name="account_code" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="account_type" class="form-label">Account Type *</label>
                                    <select class="form-select" id="account_type" name="account_type" required>
                                        <option value="">Select Account Type</option>
                                        <option value="Asset">Asset</option>
                                        <option value="Liability">Liability</option>
                                        <option value="Equity">Equity</option>
                                        <option value="Income">Income</option>
                                        <option value="Expense">Expense</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="account_name" class="form-label">Account Name *</label>
                                <select class="form-select" id="account_name" name="account_name" required>
                                    <option value="">Select Account Name</option>
                                    <?php foreach ($accountNames as $account): ?>
                                    <option value="<?php echo htmlspecialchars($account['name']); ?>">
                                        <?php echo htmlspecialchars($account['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="initial_balance" class="form-label">Initial Balance</label>
                                <input type="number" class="form-control" id="initial_balance" name="initial_balance" step="0.01" value="0.00">
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            </div>
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="chart-of-accounts.php" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">Save Account</button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
    // Update account names when account type changes
    document.getElementById('account_type').addEventListener('change', function() {
        const accountType = this.value;
        const accountNameSelect = document.getElementById('account_name');
        
        fetch(`get_account_names.php?type=${accountType}`)
            .then(response => response.json())
            .then(data => {
                accountNameSelect.innerHTML = '<option value="">Select Account Name</option>';
                data.forEach(account => {
                    const option = new Option(account.name, account.name);
                    accountNameSelect.add(option);
                });
            });
    });
    </script>
</body>
</html>