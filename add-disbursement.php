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

$success = '';
$error = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'] ?? date('Y-m-d');
    $voucher = $_POST['voucher'] ?? generateVoucherNumber('CD', $date);
    $payee = $_POST['payee'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    $description = $_POST['description'] ?? '';
    $expenseAccountId = $_POST['expense_account_id'] ?? '';
    
    if (empty($date) || empty($voucher) || empty($payee) || empty($amount) || empty($expenseAccountId)) {
        $error = 'Please fill in all required fields';
    } else {
        $conn = getConnection();
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Insert disbursement
            $sql = "INSERT INTO disbursements (voucher_number, disbursement_date, payee, amount, status) VALUES (?, ?, ?, ?, 'Posted')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssd", $voucher, $date, $payee, $amount);
            $stmt->execute();
            $disbursementId = $conn->insert_id;
            
            // Create journal entry
            $reference = generateReferenceNumber('JE', $date);
            
            // Insert transaction
            $sql = "INSERT INTO transactions (reference_number, transaction_date, description, amount, status, created_by) VALUES (?, ?, ?, ?, 'Posted', ?)";
            $stmt = $conn->prepare($sql);
            $createdBy = $_SESSION['full_name'];
            $stmt->bind_param("sssds", $reference, $date, $description, $amount, $createdBy);
            $stmt->execute();
            $transactionId = $conn->insert_id;
            
            // Get cash account ID
            $sql = "SELECT id FROM accounts WHERE account_code = '1000'"; // Cash account
            $result = $conn->query($sql);
            $row = $result->fetch_assoc();
            $cashAccountId = $row['id'];
            
            // Insert transaction details
            // Debit expense account
            $sql = "INSERT INTO transaction_details (transaction_id, account_id, debit_amount, credit_amount) VALUES (?, ?, ?, 0)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iid", $transactionId, $expenseAccountId, $amount);
            $stmt->execute();
            
            // Credit cash account
            $sql = "INSERT INTO transaction_details (transaction_id, account_id, debit_amount, credit_amount) VALUES (?, ?, 0, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iid", $transactionId, $cashAccountId, $amount);
            $stmt->execute();
            
            // Update account balances
            // Increase expense account balance
            $sql = "UPDATE accounts SET balance = balance + ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("di", $amount, $expenseAccountId);
            $stmt->execute();
            
            // Decrease cash account balance
            $sql = "UPDATE accounts SET balance = balance - ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("di", $amount, $cashAccountId);
            $stmt->execute();
            
            // Commit transaction
            $conn->commit();
            $success = 'Disbursement recorded successfully';
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $error = 'Error recording disbursement: ' . $e->getMessage();
        }
        
        closeConnection($conn);
    }
}

// Generate new voucher number
$newVoucher = generateVoucherNumber('CD');
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
                    <h1 class="h2">Add New Disbursement</h1>
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
                                <div class="col-md-4">
                                    <label for="date" class="form-label">Date *</label>
                                    <input type="date" class="form-control" id="date" name="date" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="voucher" class="form-label">Voucher Number *</label>
                                    <input type="text" class="form-control" id="voucher" name="voucher" value="<?php echo $newVoucher; ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="amount" class="form-label">Amount *</label>
                                    <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0.01" required>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="payee" class="form-label">Payee *</label>
                                    <input type="text" class="form-control" id="payee" name="payee" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="expense_account_id" class="form-label">Expense Account *</label>
                                    <select class="form-select" id="expense_account_id" name="expense_account_id" required>
                                        <option value="">Select Expense Account</option>
                                        <?php foreach ($accounts as $account): ?>
                                            <?php if ($account['account_type'] == 'Expense'): ?>
                                            <option value="<?php echo $account['id']; ?>"><?php echo $account['account_code'] . ' - ' . $account['account_name']; ?></option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description *</label>
                                <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="cash-disbursement.php" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">Save Disbursement</button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
