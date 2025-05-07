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
    $entryDates = $_POST['entry_date'] ?? [];
    $entryTypes = $_POST['entry_type'] ?? [];
    $entryParticulars = $_POST['entry_particulars'] ?? [];
    $entryDescriptions = $_POST['entry_description'] ?? [];
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
            
            try {
                // Start transaction
                $conn->begin_transaction();

                // Insert main transaction
                $stmt = $conn->prepare("
                    INSERT INTO transactions (
                        reference_number, 
                        transaction_date, 
                        description, 
                        amount, 
                        status, 
                        created_by
                    ) VALUES (?, ?, ?, ?, 'Posted', ?)
                ");

                $stmt->bind_param("sssdi", 
                    $reference,
                    $date,
                    $description,
                    $totalDebit,
                    $_SESSION['user_id']
                );
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to create transaction: " . $stmt->error);
                }
                
                $transactionId = $conn->insert_id;

                // Prepare statement for transaction details
                $detailStmt = $conn->prepare("
                    INSERT INTO transaction_details (
                        transaction_id, 
                        entry_date,
                        account_id,
                        entry_type,
                        particulars,
                        description,
                        debit_amount, 
                        credit_amount
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");

                // Insert each transaction detail with its own date and details
                foreach ($accountIds as $key => $accountId) {
                    if (empty($accountId)) continue;
                    
                    $debitAmount = floatval($debitAmounts[$key] ?? 0);
                    $creditAmount = floatval($creditAmounts[$key] ?? 0);
                    $entryDate = $entryDates[$key] ?? $date;
                    
                    $detailStmt->bind_param("isisssdd", 
                        $transactionId,
                        $entryDate,
                        $accountId,
                        $entryTypes[$key],
                        $entryParticulars[$key],
                        $entryDescriptions[$key],
                        $debitAmount,
                        $creditAmount
                    );
                    
                    if (!$detailStmt->execute()) {
                        throw new Exception("Failed to insert transaction detail: " . $detailStmt->error);
                    }

                    // Get account type
                    $accountStmt = $conn->prepare("
                        SELECT account_type, balance 
                        FROM accounts 
                        WHERE id = ? 
                        AND status = 'Active'
                        FOR UPDATE
                    ");
                    
                    $accountStmt->bind_param("i", $accountId);
                    
                    if (!$accountStmt->execute()) {
                        throw new Exception("Failed to get account info: " . $accountStmt->error);
                    }
                    
                    $accountResult = $accountStmt->get_result();
                    $account = $accountResult->fetch_assoc();
                    
                    if (!$account) {
                        throw new Exception("Account not found or inactive: ID " . $accountId);
                    }

                    // Calculate balance change based on account type
                    $balanceChange = 0;
                    switch ($account['account_type']) {
                        case 'Asset':
                        case 'Expense':
                            $balanceChange = $debitAmount - $creditAmount;
                            break;
                        case 'Liability':
                        case 'Equity':
                        case 'Revenue':
                            $balanceChange = $creditAmount - $debitAmount;
                            break;
                        default:
                            throw new Exception("Invalid account type for account ID " . $accountId);
                    }

                    // Update account balance
                    $updateStmt = $conn->prepare("
                        UPDATE accounts 
                        SET balance = balance + ? 
                        WHERE id = ?
                    ");
                    
                    $updateStmt->bind_param("di", $balanceChange, $accountId);
                    
                    if (!$updateStmt->execute()) {
                        throw new Exception("Failed to update account balance: " . $updateStmt->error);
                    }
                }

                $conn->commit();
                $success = 'Transaction recorded successfully';
                header("Location: add-transaction.php?success=1");
                exit();
                
            } catch (Exception $e) {
                $conn->rollback();
                $error = 'Error recording transaction: ' . $e->getMessage();
            } finally {
                closeConnection($conn);
            }
        }
    }
}

// Generate new reference number
$newReference = generateReferenceNumber('JE');

// Check for success message in URL
if (isset($_GET['success'])) {
    $success = 'Transaction recorded successfully';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'includes/head.php'; ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .text-danger { color: #dc3545 !important; }
        .form-control:disabled { background-color: #e9ecef; }
        input[type="number"] { text-align: right; }
        input[type="number"]::-webkit-outer-spin-button,
        input[type="number"]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type="number"] { -moz-appearance: textfield; }
        .table td { vertical-align: middle; }
        .current-info-bar {
            font-family: 'Courier New', Courier, monospace;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
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
                                            <th width="10%">Date</th>
                                            <th width="10%">Type</th>
                                            <th width="15%">Particulars</th>
                                            <th width="20%">Account</th>
                                            <th width="15%">Description</th>
                                            <th width="10%">Debit</th>
                                            <th width="10%">Credit</th>
                                            <th width="10%">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                <input type="date" class="form-control entry-date" name="entry_date[]" value="<?php echo date('Y-m-d'); ?>" required>
                                            </td>
                                            <td>
                                                <select class="form-select entry-type" name="entry_type[]" required>
                                                    <option value="">Select Type</option>
                                                    <option value="INV">INV</option>
                                                    <option value="PAY">PAY</option>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control entry-particulars" name="entry_particulars[]" required>
                                            </td>
                                            <td>
                                                <select class="form-select" name="account_id[]" required>
                                                    <option value="">Select Account</option>
                                                    <?php foreach ($accounts as $account): ?>
                                                    <option value="<?php echo $account['id']; ?>">
                                                        <?php echo htmlspecialchars($account['account_code'] . ' - ' . $account['account_name']); ?>
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control entry-description" name="entry_description[]">
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
                                            <td colspan="5">
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
                                <a href="general-ledger.php" class="btn btn-secondary me-2">Cancel</a>
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
            // Update datetime
            function updateDateTime() {
                const now = new Date();
                const formatted = now.getUTCFullYear() + '-' + 
                                String(now.getUTCMonth() + 1).padStart(2, '0') + '-' + 
                                String(now.getUTCDate()).padStart(2, '0') + ' ' + 
                                String(now.getUTCHours()).padStart(2, '0') + ':' + 
                                String(now.getUTCMinutes()).padStart(2, '0') + ':' + 
                                String(now.getUTCSeconds()).padStart(2, '0');
                $('#currentDateTime').text(formatted);
            }
            
            setInterval(updateDateTime, 1000);
            updateDateTime();

            // Sync main date with entry dates
            $('#date').on('change', function() {
                $('.entry-date').val($(this).val());
            });

            // Add row
            $('#addRow').click(function() {
                var accountOptions = '';
                <?php foreach ($accounts as $account): ?>
                accountOptions += '<option value="<?php echo $account['id']; ?>"><?php echo addslashes($account['account_code'] . ' - ' . $account['account_name']); ?></option>';
                <?php endforeach; ?>

                var newRow = $(`
                    <tr>
                        <td>
                            <input type="date" class="form-control entry-date" name="entry_date[]" value="${$('#date').val()}" required>
                        </td>
                        <td>
                            <select class="form-select entry-type" name="entry_type[]" required>
                                <option value="">Select Type</option>
                                <option value="INV">INV</option>
                                <option value="PAY">PAY</option>
                            </select>
                        </td>
                        <td>
                            <input type="text" class="form-control entry-particulars" name="entry_particulars[]" required>
                        </td>
                        <td>
                            <select class="form-select" name="account_id[]" required>
                                <option value="">Select Account</option>
                                ${accountOptions}
                            </select>
                        </td>
                        <td>
                            <input type="text" class="form-control entry-description" name="entry_description[]">
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
                `);

                $('#transactionTable tbody').append(newRow);
                updateRemoveButtons();
                updateTotals();
            });

            // Remove row
            $(document).on('click', '.remove-row', function() {
                if ($('#transactionTable tbody tr').length > 1) {
                    $(this).closest('tr').remove();
                    updateRemoveButtons();
                    updateTotals();
                }
            });


$(document).on('input', '.debit-amount, .credit-amount', function() {
    var $row = $(this).closest('tr');
    var $debit = $row.find('.debit-amount');
    var $credit = $row.find('.credit-amount');
    
    // Format to 2 decimal places
    $(this).val(parseFloat($(this).val() || 0).toFixed(2));
    
    // Don't disable the other field, just clear it if this field has a value
    if ($(this).hasClass('debit-amount')) {
        if (parseFloat($(this).val()) > 0) {
            $credit.val('0.00');
        }
    } else if ($(this).hasClass('credit-amount')) {
        if (parseFloat($(this).val()) > 0) {
            $debit.val('0.00');
        }
    }
    
    updateTotals();
});

            function updateRemoveButtons() {
                var rowCount = $('#transactionTable tbody tr').length;
                $('.remove-row').prop('disabled', rowCount <= 1);
            }

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
                    return false;
                }
                
                return confirm('Are you sure you want to save this transaction?');
            });

            // Initialize
            updateRemoveButtons();
            updateTotals();
        });

        // Keyboard shortcuts
        $(document).keydown(function(e) {
            if (e.altKey && e.key === 'n') { // Alt + N
                e.preventDefault();
                $('#addRow').click();
            }
        });
    </script>
</body>
</html>