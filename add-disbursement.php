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
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize the page
        updateDateTime();
        setInterval(updateDateTime, 1000);
        
        // Add row button handler
        document.getElementById('addRow').addEventListener('click', addNewRow);
        
        // Setup initial row
        setupRowEventListeners(document.querySelector('.entry-row'));
        
        // Form validation
        document.getElementById('transactionForm').addEventListener('submit', validateForm);
    });

    function addNewRow() {
        const tbody = document.querySelector('#entriesTable tbody');
        const template = `
            <tr class="entry-row">
                <td>
                    <select class="form-select account-select" name="account_id[]" required>
                        <option value="">Select Account</option>
                        <?php foreach ($accounts as $account): ?>
                        <option value="<?php echo $account['id']; ?>" 
                                data-type="<?php echo $account['account_type']; ?>">
                            <?php echo htmlspecialchars($account['account_code'] . ' - ' . $account['account_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td>
                    <input type="number" class="form-control debit-amount" 
                           name="debit_amount[]" step="0.01" min="0" value="0.00">
                </td>
                <td>
                    <input type="number" class="form-control credit-amount" 
                           name="credit_amount[]" step="0.01" min="0" value="0.00">
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm remove-row">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        
        // Add new row
        tbody.insertAdjacentHTML('beforeend', template);
        
        // Setup event listeners for the new row
        const newRow = tbody.lastElementChild;
        setupRowEventListeners(newRow);
        
        // Enable remove buttons if there's more than one row
        updateRemoveButtons();
    }

    function setupRowEventListeners(row) {
        const debitInput = row.querySelector('.debit-amount');
        const creditInput = row.querySelector('.credit-amount');
        const removeButton = row.querySelector('.remove-row');
        const accountSelect = row.querySelector('.account-select');

        // Handle debit amount changes
        debitInput.addEventListener('input', function() {
            const value = parseFloat(this.value) || 0;
            if (value > 0) {
                creditInput.value = '0.00';
                creditInput.disabled = true;
            } else {
                creditInput.disabled = false;
            }
            updateTotals();
        });

        // Handle credit amount changes
        creditInput.addEventListener('input', function() {
            const value = parseFloat(this.value) || 0;
            if (value > 0) {
                debitInput.value = '0.00';
                debitInput.disabled = true;
            } else {
                debitInput.disabled = false;
            }
            updateTotals();
        });

        // Format numbers on blur
        debitInput.addEventListener('blur', formatNumber);
        creditInput.addEventListener('blur', formatNumber);

        // Remove row handler
        removeButton.addEventListener('click', function() {
            if (document.querySelectorAll('.entry-row').length > 1) {
                row.remove();
                updateTotals();
                updateRemoveButtons();
            }
        });

        // Account select handler
        accountSelect.addEventListener('change', function() {
            row.classList.toggle('table-warning', !this.value);
            updateTotals();
        });
    }

    function updateRemoveButtons() {
        const rows = document.querySelectorAll('.entry-row');
        rows.forEach(row => {
            row.querySelector('.remove-row').disabled = rows.length === 1;
        });
    }

    function formatNumber() {
        this.value = parseFloat(this.value || 0).toFixed(2);
    }

    function updateTotals() {
        let totalDebit = 0;
        let totalCredit = 0;
        let isValid = true;

        // Calculate totals
        document.querySelectorAll('.entry-row').forEach(row => {
            const debit = parseFloat(row.querySelector('.debit-amount').value) || 0;
            const credit = parseFloat(row.querySelector('.credit-amount').value) || 0;
            const account = row.querySelector('.account-select').value;

            totalDebit += debit;
            totalCredit += credit;

            // Validate row
            if (account && (debit <= 0 && credit <= 0)) {
                isValid = false;
                row.classList.add('table-danger');
            } else if (debit > 0 && credit > 0) {
                isValid = false;
                row.classList.add('table-danger');
            } else {
                row.classList.remove('table-danger');
            }
        });

        // Update displays
        document.getElementById('totalDebit').textContent = totalDebit.toFixed(2);
        document.getElementById('totalCredit').textContent = totalCredit.toFixed(2);

        // Check if balanced
        const isBalanced = Math.abs(totalDebit - totalCredit) < 0.01;
        
        // Update total displays
        document.getElementById('totalDebit').className = isBalanced ? 'balanced' : 'unbalanced';
        document.getElementById('totalCredit').className = isBalanced ? 'balanced' : 'unbalanced';

        // Enable/disable submit button
        document.getElementById('submitButton').disabled = !isBalanced || !isValid;
    }

    function validateForm(e) {
        e.preventDefault();

        const rows = document.querySelectorAll('.entry-row');
        let isValid = true;
        let hasEntries = false;

        rows.forEach(row => {
            const account = row.querySelector('.account-select').value;
            const debit = parseFloat(row.querySelector('.debit-amount').value) || 0;
            const credit = parseFloat(row.querySelector('.credit-amount').value) || 0;

            if (account) {
                hasEntries = true;
                if (debit <= 0 && credit <= 0) {
                    isValid = false;
                    row.classList.add('table-danger');
                } else if (debit > 0 && credit > 0) {
                    isValid = false;
                    row.classList.add('table-danger');
                }
            }
        });

        if (!hasEntries) {
            alert('Please add at least one valid entry.');
            return false;
        }

        if (!isValid) {
            alert('Please correct the highlighted entries.');
            return false;
        }

        const totalDebit = parseFloat(document.getElementById('totalDebit').textContent);
        const totalCredit = parseFloat(document.getElementById('totalCredit').textContent);

        if (Math.abs(totalDebit - totalCredit) >= 0.01) {
            alert('Total debits must equal total credits.');
            return false;
        }

        if (confirm('Are you sure you want to save this transaction?')) {
            e.target.submit();
        }
    }

    // Update datetime display
    function updateDateTime() {
        const now = new Date();
        const formatted = now.getUTCFullYear() + '-' + 
                        String(now.getUTCMonth() + 1).padStart(2, '0') + '-' + 
                        String(now.getUTCDate()).padStart(2, '0') + ' ' + 
                        String(now.getUTCHours()).padStart(2, '0') + ':' + 
                        String(now.getUTCMinutes()).padStart(2, '0') + ':' + 
                        String(now.getUTCSeconds()).padStart(2, '0');
        document.getElementById('currentDateTime').textContent = formatted;
    }

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.altKey && e.key === 'n') { // Alt + N
            e.preventDefault();
            addNewRow();
        }
    });
</script>
</body>
</html>
