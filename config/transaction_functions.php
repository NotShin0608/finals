<?php
if (!function_exists('getRecentTransactions')) {
    function getRecentTransactions($limit = 10) {
        $conn = getConnection();
        try {
            $sql = "SELECT * FROM transactions ORDER BY transaction_date DESC, id DESC LIMIT ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            $transactions = [];
            
            while($row = $result->fetch_assoc()) {
                $transactions[] = $row;
            }
            
            return $transactions;
        } finally {
            closeConnection($conn);
        }
    }
}

if (!function_exists('getNetIncome')) {
    function getNetIncome() {
        $conn = getConnection();
        try {
            // Get total revenue
            $sql = "SELECT SUM(balance) as total FROM accounts WHERE account_type = 'Income'";
            $result = $conn->query($sql);
            $row = $result->fetch_assoc();
            $revenue = $row['total'] ?? 0;
            
            // Get total expenses
            $sql = "SELECT SUM(balance) as total FROM accounts WHERE account_type = 'Expense'";
            $result = $conn->query($sql);
            $row = $result->fetch_assoc();
            $expenses = $row['total'] ?? 0;
            
            return $revenue - $expenses;
        } finally {
            closeConnection($conn);
        }
    }
}

if (!function_exists('getLedgerEntries')) {
    function getLedgerEntries() {
        $conn = getConnection();
        try {
            $sql = "SELECT t.id, t.reference_number, t.transaction_date, t.description, 
                    a.account_code, a.account_name, td.debit_amount, td.credit_amount
                    FROM transactions t
                    JOIN transaction_details td ON t.id = td.transaction_id
                    JOIN accounts a ON td.account_id = a.id
                    ORDER BY t.transaction_date DESC, t.id DESC";
            $result = $conn->query($sql);
            $entries = [];
            
            while($row = $result->fetch_assoc()) {
                $entries[] = $row;
            }
            
            return $entries;
        } finally {
            closeConnection($conn);
        }
    }
}

if (!function_exists('updateAccountBalance')) {
    function updateAccountBalance($conn, $accountId, $debitAmount, $creditAmount) {
        try {
            $stmt = $conn->prepare("SELECT account_type FROM accounts WHERE id = ?");
            $stmt->bind_param("i", $accountId);
            $stmt->execute();
            $result = $stmt->get_result();
            $account = $result->fetch_assoc();
            
            if (!$account) {
                throw new Exception("Account not found");
            }
            
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
                    throw new Exception("Invalid account type");
            }
            
            $stmt = $conn->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ?");
            $stmt->bind_param("di", $balanceChange, $accountId);
            $stmt->execute();
            
            if ($stmt->affected_rows === 0) {
                throw new Exception("Failed to update account balance");
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error in updateAccountBalance: " . $e->getMessage());
            throw $e;
        }
    }
}