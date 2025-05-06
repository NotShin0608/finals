<?php
require_once 'database.php';

// Function to get total assets
function getTotalAssets() {
    $conn = getConnection();
    $sql = "SELECT SUM(balance) as total FROM accounts WHERE account_type = 'Asset'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    closeConnection($conn);
    return $row['total'] ?? 0;
}

// Function to get total liabilities
function getTotalLiabilities() {
    $conn = getConnection();
    $sql = "SELECT SUM(balance) as total FROM accounts WHERE account_type = 'Liability'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    closeConnection($conn);
    return $row['total'] ?? 0;
}

// Function to get total equity
function getTotalEquity() {
    $conn = getConnection();
    $sql = "SELECT SUM(balance) as total FROM accounts WHERE account_type = 'Equity'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    closeConnection($conn);
    return $row['total'] ?? 0;
}

// Function to get net income (Revenue - Expenses)
function getNetIncome() {
    $conn = getConnection();
    
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
    
    closeConnection($conn);
    return $revenue - $expenses;
}

// Function to get recent transactions
function getRecentTransactions($limit = 10) {
    $conn = getConnection();
    $sql = "SELECT * FROM transactions ORDER BY transaction_date DESC, id DESC LIMIT $limit";
    $result = $conn->query($sql);
    $transactions = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $transactions[] = $row;
        }
    }
    
    closeConnection($conn);
    return $transactions;
}

// Function to get recent disbursements
function getRecentDisbursements($limit = 10) {
    $conn = getConnection();
    $sql = "SELECT * FROM disbursements ORDER BY disbursement_date DESC, id DESC LIMIT $limit";
    $result = $conn->query($sql);
    $disbursements = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $disbursements[] = $row;
        }
    }
    
    closeConnection($conn);
    return $disbursements;
}

// Function to get recent payments
function getRecentPayments($limit = 10) {
    $conn = getConnection();
    $sql = "SELECT * FROM payments ORDER BY payment_date DESC, id DESC LIMIT $limit";
    $result = $conn->query($sql);
    $payments = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $payments[] = $row;
        }
    }
    
    closeConnection($conn);
    return $payments;
}

// Function to get all accounts
function getAllAccounts() {
    $conn = getConnection();
    $sql = "SELECT * FROM accounts ORDER BY account_code";
    $result = $conn->query($sql);
    $accounts = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $accounts[] = $row;
        }
    }
    
    closeConnection($conn);
    return $accounts;
}

// Function to get ledger entries
function getLedgerEntries() {
    $conn = getConnection();
    $sql = "SELECT t.id, t.reference_number, t.transaction_date, t.description, 
            a.account_code, a.account_name, td.debit_amount, td.credit_amount
            FROM transactions t
            JOIN transaction_details td ON t.id = td.transaction_id
            JOIN accounts a ON td.account_id = a.id
            ORDER BY t.transaction_date DESC, t.id DESC";
    $result = $conn->query($sql);
    $entries = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $entries[] = $row;
        }
    }
    
    closeConnection($conn);
    return $entries;
}

// Function to get trial balance as of a specific date
function getTrialBalance($date) {
    $conn = getConnection();
    $sql = "SELECT a.account_code, a.account_name, a.account_type,
            SUM(CASE WHEN td.debit_amount > 0 AND t.transaction_date <= '$date' THEN td.debit_amount ELSE 0 END) as total_debit,
            SUM(CASE WHEN td.credit_amount > 0 AND t.transaction_date <= '$date' THEN td.credit_amount ELSE 0 END) as total_credit
            FROM accounts a
            LEFT JOIN transaction_details td ON a.id = td.account_id
            LEFT JOIN transactions t ON td.transaction_id = t.id AND t.status = 'Posted'
            GROUP BY a.id
            ORDER BY a.account_code";
    $result = $conn->query($sql);
    $trialBalance = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $trialBalance[] = $row;
        }
    }
    
    closeConnection($conn);
    return $trialBalance;
}

// Function to format currency
function formatCurrency($amount) {
    return '₱' . number_format($amount, 2);
}

// Function to generate a new reference number
function generateReferenceNumber($prefix = 'JE', $date = null) {
    if ($date === null) {
        $date = date('Y-m-d');
    }
    
    $year = date('Y', strtotime($date));
    $month = date('m', strtotime($date));
    $day = date('d', strtotime($date));
    
    $conn = getConnection();
    $sql = "SELECT MAX(CAST(SUBSTRING(reference_number, 11) AS UNSIGNED)) as max_num 
            FROM transactions 
            WHERE reference_number LIKE '$prefix$year$month%'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $maxNum = $row['max_num'] ?? 0;
    $nextNum = $maxNum + 1;
    
    closeConnection($conn);
    return $prefix . $year . $month . $day . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
}

// Function to generate a new voucher number
function generateVoucherNumber($prefix = 'CD', $date = null) {
    if ($date === null) {
        $date = date('Y-m-d');
    }
    
    $year = date('Y', strtotime($date));
    $month = date('m', strtotime($date));
    $day = date('d', strtotime($date));
    
    $conn = getConnection();
    $sql = "SELECT MAX(CAST(SUBSTRING(voucher_number, 11) AS UNSIGNED)) as max_num 
            FROM disbursements 
            WHERE voucher_number LIKE '$prefix$year$month%'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $maxNum = $row['max_num'] ?? 0;
    $nextNum = $maxNum + 1;
    
    closeConnection($conn);
    return $prefix . $year . $month . $day . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
}

// Function to authenticate user
function authenticateUser($username, $password) {
    $conn = getConnection();
    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            unset($user['password']); // Don't return the password
            
            // Log the login
            logUserLogin($user['id']);
            
            closeConnection($conn);
            return $user;
        }
    }
    
    closeConnection($conn);
    return false;
}

// Function to log user login
function logUserLogin($userId) {
    $conn = getConnection();
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    // Check if we need to create the user_access_logs table
    $sql = "CREATE TABLE IF NOT EXISTS user_access_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        logout_time TIMESTAMP NULL,
        ip_address VARCHAR(45) NOT NULL,
        user_agent TEXT NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $conn->query($sql);
    
    // Insert login record
    $sql = "INSERT INTO user_access_logs (user_id, ip_address, user_agent) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $userId, $ipAddress, $userAgent);
    $stmt->execute();
    
    // Store the log ID in the session for logout
    $_SESSION['access_log_id'] = $conn->insert_id;
    
    closeConnection($conn);
}

// Function to log user logout
function logUserLogout() {
    if (isset($_SESSION['access_log_id']) && $_SESSION['access_log_id'] > 0) {
        $conn = getConnection();
        $logId = $_SESSION['access_log_id'];
        
        $sql = "UPDATE user_access_logs SET logout_time = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $logId);
        $stmt->execute();
        
        closeConnection($conn);
    }
}

// Function to log user activity
function logActivity($userId, $activityType, $activityDetails) {
    $conn = getConnection();
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    
    // Check if we need to create the activity_logs table
    $sql = "CREATE TABLE IF NOT EXISTS activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        activity_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        activity_type VARCHAR(50) NOT NULL,
        activity_details TEXT NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $conn->query($sql);
    
    // Insert activity record
    $sql = "INSERT INTO activity_logs (user_id, activity_type, activity_details, ip_address) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isss", $userId, $activityType, $activityDetails, $ipAddress);
    $stmt->execute();
    
    closeConnection($conn);
}

// Function to get monthly transaction data for forecasting
function getMonthlyTransactionData($years = 2) {
    $conn = getConnection();
    
    $sql = "SELECT 
                YEAR(transaction_date) as year,
                MONTH(transaction_date) as month,
                SUM(amount) as total_amount
            FROM 
                transactions
            WHERE 
                transaction_date >= DATE_SUB(CURDATE(), INTERVAL $years YEAR)
                AND status = 'Posted'
            GROUP BY 
                YEAR(transaction_date), MONTH(transaction_date)
            ORDER BY 
                year ASC, month ASC";
    
    $result = $conn->query($sql);
    $monthlyData = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $monthlyData[] = $row;
        }
    }
    
    closeConnection($conn);
    return $monthlyData;
}

// Function to get expense categories for forecasting
function getExpenseCategories() {
    $conn = getConnection();
    
    $sql = "SELECT 
                a.id,
                a.account_name,
                SUM(td.debit_amount) as total_amount
            FROM 
                transaction_details td
                JOIN accounts a ON td.account_id = a.id
                JOIN transactions t ON td.transaction_id = t.id
            WHERE 
                a.account_type = 'Expense'
                AND t.transaction_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
                AND t.status = 'Posted'
            GROUP BY 
                a.id
            ORDER BY 
                total_amount DESC
            LIMIT 5";
    
    $result = $conn->query($sql);
    $categories = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    }
    
    closeConnection($conn);
    return $categories;
}

// Function to get forecast data
function getForecastData() {
    $conn = getConnection();
    
    // Get yearly expense data
    $sql = "SELECT 
                YEAR(transaction_date) as year, 
                SUM(amount) as total_amount 
            FROM transactions 
            WHERE status = 'Posted' 
            GROUP BY YEAR(transaction_date) 
            ORDER BY YEAR(transaction_date)";
    
    $result = $conn->query($sql);
    $yearlyData = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $yearlyData[$row['year']] = $row['total_amount'];
        }
    }
    
    // Get monthly data for the current year
    $currentYear = date('Y');
    $sql = "SELECT 
                MONTH(transaction_date) as month, 
                SUM(amount) as total_amount 
            FROM transactions 
            WHERE status = 'Posted' AND YEAR(transaction_date) = '$currentYear'
            GROUP BY MONTH(transaction_date) 
            ORDER BY MONTH(transaction_date)";
    
    $result = $conn->query($sql);
    $monthlyData = array_fill(1, 12, 0); // Initialize with zeros
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $monthlyData[$row['month']] = $row['total_amount'];
        }
    }
    
    // Get category data
    $sql = "SELECT 
                a.account_type,
                SUM(td.debit_amount) as total_amount 
            FROM transaction_details td
            JOIN accounts a ON td.account_id = a.id
            JOIN transactions t ON td.transaction_id = t.id
            WHERE t.status = 'Posted'
            GROUP BY a.account_type";
    
    $result = $conn->query($sql);
    $categoryData = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $categoryData[$row['account_type']] = $row['total_amount'];
        }
    }
    
    closeConnection($conn);
    
    return [
        'yearly' => $yearlyData,
        'monthly' => $monthlyData,
        'category' => $categoryData
    ];
}

// Function to get income statement data
function getIncomeStatement($startDate, $endDate) {
    $conn = getConnection();
    
    // Get revenue
    $sql = "SELECT a.account_code, a.account_name, 
            SUM(CASE WHEN td.credit_amount > 0 THEN td.credit_amount ELSE 0 END) - 
            SUM(CASE WHEN td.debit_amount > 0 THEN td.debit_amount ELSE 0 END) as amount
            FROM accounts a
            JOIN transaction_details td ON a.id = td.account_id
            JOIN transactions t ON td.transaction_id = t.id
            WHERE a.account_type = 'Income' 
            AND t.status = 'Posted'
            AND t.transaction_date BETWEEN '$startDate' AND '$endDate'
            GROUP BY a.id
            ORDER BY a.account_code";
    
    $result = $conn->query($sql);
    $revenue = [];
    $totalRevenue = 0;
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $revenue[] = $row;
            $totalRevenue += $row['amount'];
        }
    }
    
    // Get expenses
    $sql = "SELECT a.account_code, a.account_name, 
            SUM(CASE WHEN td.debit_amount > 0 THEN td.debit_amount ELSE 0 END) - 
            SUM(CASE WHEN td.credit_amount > 0 THEN td.credit_amount ELSE 0 END) as amount
            FROM accounts a
            JOIN transaction_details td ON a.id = td.account_id
            JOIN transactions t ON td.transaction_id = t.id
            WHERE a.account_type = 'Expense' 
            AND t.status = 'Posted'
            AND t.transaction_date BETWEEN '$startDate' AND '$endDate'
            GROUP BY a.id
            ORDER BY a.account_code";
    
    $result = $conn->query($sql);
    $expenses = [];
    $totalExpenses = 0;
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $expenses[] = $row;
            $totalExpenses += $row['amount'];
        }
    }
    
    closeConnection($conn);
    
    return [
        'revenue' => $revenue,
        'totalRevenue' => $totalRevenue,
        'expenses' => $expenses,
        'totalExpenses' => $totalExpenses,
        'netIncome' => $totalRevenue - $totalExpenses
    ];
}

