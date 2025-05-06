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
    $reference = $_POST['reference'] ?? generateReferenceNumber('JE', $date);
    $description = $_POST['description'] ?? '';
    $accountIds = $_POST['account_id'] ?? [];
    $debitAmounts = $_POST['debit_amount'] ?? [];
    $creditAmounts = $_POST['credit_amount'] ?? [];
    
    if (empty($date) || empty($reference) || empty($description) || empty($accountIds)) {
        $error = 'Please fill in all required fields';
    } else {
        // Calculate total debits and credits
        $totalDebit = 0;
        $totalCredit = 0;
        foreach ($accountIds as $key => $accountId) {
            $totalDebit += floatval($debitAmounts[$key] ?? 0);
            $totalCredit += floatval($creditAmounts[$key] ?? 0);
        }
        
        // Check if debits equal credits
        if (abs($totalDebit - $totalCredit) > 0.001) {
            $error = 'Total debits must equal total credits';
        } else {
            $conn = getConnection();
            
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Insert transaction
                $sql = "INSERT INTO transactions (reference_number, transaction_date, description, amount, status, created_by) VALUES (?, ?, ?, ?, 'Posted', ?)";
                $stmt = $conn->prepare($sql);
                $createdBy = $_SESSION['full_name'];
                $stmt->bind_param("sssds", $reference, $date, $description, $totalDebit, $createdBy);
                $stmt->execute();
                $transactionId = $conn->insert_id;
                
                // Insert transaction details and update account balances
                foreach ($accountIds as $key => $accountId) {
                    $debitAmount = floatval($debitAmounts[$key] ?? 0);
                    $creditAmount = floatval($creditAmounts[$key] ?? 0);
                    
                    if ($debitAmount > 0 || $creditAmount > 0) {
                        // Insert transaction detail
                        $sql = "INSERT INTO transaction_details (transaction_id, account_id, debit_amount, credit_amount) VALUES (?, ?, ?, ?)";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("iidd", $transactionId, $accountId, $debitAmount, $creditAmount);
                        $stmt->execute();
                        
                        // Get account type
                        $sql = "SELECT account_type FROM accounts WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $accountId);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $account = $result->fetch_assoc();
                        
                        // Update account balance based on account type
                        $balanceChange = 0;
                        
                        if ($account['account_type'] == 'Asset' || $account['account_type'] == 'Expense') {
                            $balanceChange = $debitAmount - $creditAmount;
                        } else {
                            $balanceChange = $creditAmount - $debitAmount;
                        }
                        
                        $sql = "UPDATE accounts SET balance = balance + ? WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("di", $balanceChange, $accountId);
                        $stmt->execute();
                    }
                }
                
                // Commit transaction
                $conn->commit();
                $success = 'Transaction recorded successfully';
            } catch (Exception $e) {
                // Rollback transaction on error
                $conn->rollback();
                $error = 'Error recording transaction: ' . $e->getMessage();
            }
            
            closeConnection($conn);
        }
    }
}

// Generate new reference number
$newReference = generateReferenceNumber('JE');
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
                    <h1 class="h2">Add New Transaction</h1>
                </div>
                
                <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-body">
                        <form action="" method="POST" id="transactionForm">
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="date" class="form-label">Date *</label>
                                    <input type="date" class="form-control" id="date" name="date" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="reference" class="form-label">Reference Number *</label>
                                    <input type="text" class="form-control" id="reference" name="reference" value="<?php echo $newReference; ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="description" class="form-label">Description *</label>
                                    <input type="text" class="form-control" id="description" name="description" required>
                                </div>
                            </div>
                            
                            <div class="table-responsive mb-3">
                                <table class="table table-bordered" id="transactionTable">
                                    <thead>
                                        <tr>
                                            <th width="40%">Account</th>
                                            <th width="25%">Debit</th>
                                            <th width="25%">Credit</th>
                                            <th width="10%">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                <select class="form-select" name="account_id[]" required>
                                                    <option value="">Select Account</option>
                                                    <?php foreach ($accounts as $account): ?>
                                                    <option value="<?php echo $account['id']; ?>"><?php echo $account['account_code'] . ' - ' . $account['account_name']; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control debit-amount" name="debit_amount[]" step="0.01" value="0.00" min="0">
                                            </td>
                                            <td>
                                                <input type="number" class="form-control credit-amount" name="credit_amount[]" step="0.01" value="0.00" min="0">
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-danger btn-sm remove-row" disabled>Remove</button>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td>
                                                <button type="button" class="btn btn-success btn-sm" id="addRow">Add Row</button>
                                            </td>
                                            <td>
                                                <strong>Total Debit: <span id="totalDebit">0.00</span></strong>
                                            </td>
                                            <td>
                                                <strong>Total Credit: <span id="totalCredit">0.00</span></strong>
                                            </td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="general-ledger.php" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">Save Transaction</button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        $(document).ready(function() {
            // Add row
            $('#addRow').click(function() {
                var newRow = `
                    <tr>
                        <td>
                            <select class="form-select" name="account_id[]" required>
                                <option value="">Select Account</option>
                                <?php foreach ($accounts as $account): ?>
                                <option value="<?php echo $account['id']; ?>"><?php echo $account['account_code'] . ' - ' . $account['account_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <input type="number" class="form-control debit-amount" name="debit_amount[]" step="0.01" value="0.00" min="0">
                        </td>
                        <td>
                            <input type="number" class="form-control credit-amount" name="credit_amount[]" step="0.01" value="0.00" min="0">
                        </td>
                        <td>
                            <button type="button" class="btn btn-danger btn-sm remove-row">Remove</button>
                        </td>
                    </tr>
                `;
                $('#transactionTable tbody').append(newRow);
                updateTotals();
            });
            
            // Remove row
            $(document).on('click', '.remove-row', function() {
                $(this).closest('tr').remove();
                updateTotals();
            });
            
            // Update totals when amounts change
            $(document).on('input', '.debit-amount, .credit-amount', function() {
                updateTotals();
            });
            
            // Ensure only debit or credit is entered
            $(document).on('input', '.debit-amount', function() {
                if ($(this).val() > 0) {
                    $(this).closest('tr').find('.credit-amount').val('0.00');
                }
                updateTotals();
            });
            
            $(document).on('input', '.credit-amount', function() {
                if ($(this).val() > 0) {
                    $(this).closest('tr').find('.debit-amount').val('0.00');
                }
                updateTotals();
            });
            
            // Calculate totals
            function updateTotals() {
                var totalDebit = 0;
                var totalCredit = 0;
                
                $('.debit-amount').each(function() {
                    totalDebit += parseFloat($(this).val() || 0);
                });
                
                $('.credit-amount').each(function() {
                    totalCredit += parseFloat($(this).val() || 0);
                });
                
                $('#totalDebit').text(totalDebit.toFixed(2));
                $('#totalCredit').text(totalCredit.toFixed(2));
                
                // Highlight totals if they don't match
                if (Math.abs(totalDebit - totalCredit) > 0.001) {
                    $('#totalDebit, #totalCredit').addClass('text-danger');
                } else {
                    $('#totalDebit, #totalCredit').removeClass('text-danger');
                }
            }
            
            // Form validation
            $('#transactionForm').submit(function(e) {
                var totalDebit = parseFloat($('#totalDebit').text());
                var totalCredit = parseFloat($('#totalCredit').text());
                
                if (Math.abs(totalDebit - totalCredit) > 0.001) {
                    e.preventDefault();
                    alert('Total debits must equal total credits');
                }
            });
        });
    </script>
</body>
</html>
