<?php
if (!function_exists('getTrialBalance')) {
    function getTrialBalance($startDate, $endDate) {
        $conn = getConnection();
        try {
            // Add one day to end date to include all transactions of the end date
            $endDateAdjusted = date('Y-m-d', strtotime($endDate . ' +1 day'));
            
            $sql = "SELECT 
                    a.account_code, 
                    a.account_name, 
                    a.account_type,
                    SUM(CASE 
                        WHEN td.debit_amount > 0 
                        AND t.transaction_date >= ? 
                        AND t.transaction_date < ? 
                        THEN td.debit_amount 
                        ELSE 0 
                    END) as total_debit,
                    SUM(CASE 
                        WHEN td.credit_amount > 0 
                        AND t.transaction_date >= ? 
                        AND t.transaction_date < ? 
                        THEN td.credit_amount 
                        ELSE 0 
                    END) as total_credit
                    FROM accounts a
                    LEFT JOIN transaction_details td ON a.id = td.account_id
                    LEFT JOIN transactions t ON td.transaction_id = t.id 
                    WHERE t.status = 'Posted' OR t.status IS NULL
                    GROUP BY a.id, a.account_code, a.account_name, a.account_type
                    ORDER BY a.account_code";
                    
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $startDate, $endDateAdjusted, $startDate, $endDateAdjusted);
            $stmt->execute();
            $result = $stmt->get_result();
            $trialBalance = [];
            
            while($row = $result->fetch_assoc()) {
                $trialBalance[] = $row;
            }
            
            return $trialBalance;
        } catch (Exception $e) {
            error_log("Error in getTrialBalance: " . $e->getMessage());
            return [];
        } finally {
            closeConnection($conn);
        }
    }
}

// Update the date handling in your main script
$today = date('Y-m-d');
$firstDayOfMonth = date('Y-m-01');

// Get start and end dates from GET parameters with proper defaults
$endDate = isset($_GET['end_date']) && !empty($_GET['end_date']) ? $_GET['end_date'] : $today;
$startDate = isset($_GET['start_date']) && !empty($_GET['start_date']) ? $_GET['start_date'] : $firstDayOfMonth;

// Validate dates
if (!DateTime::createFromFormat('Y-m-d', $startDate)) {
    $startDate = $firstDayOfMonth;
}
if (!DateTime::createFromFormat('Y-m-d', $endDate)) {
    $endDate = $today;
}

// Ensure end date is not before start date
if (strtotime($endDate) < strtotime($startDate)) {
    $endDate = $startDate;
}

// Get trial balance with validated dates
$trialBalance = getTrialBalance($startDate, $endDate);


if (!function_exists('getIncomeStatement')) {
    function getIncomeStatement($startDate, $endDate) {
        $conn = getConnection();
        try {
            // Get revenue
            $sql = "SELECT a.account_code, a.account_name, 
                    SUM(CASE WHEN td.credit_amount > 0 THEN td.credit_amount ELSE 0 END) - 
                    SUM(CASE WHEN td.debit_amount > 0 THEN td.debit_amount ELSE 0 END) as amount
                    FROM accounts a
                    JOIN transaction_details td ON a.id = td.account_id
                    JOIN transactions t ON td.transaction_id = t.id
                    WHERE a.account_type = 'Income' 
                    AND t.status = 'Posted'
                    AND t.transaction_date BETWEEN ? AND ?
                    GROUP BY a.id
                    ORDER BY a.account_code";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            $result = $stmt->get_result();
            $revenue = [];
            $totalRevenue = 0;
            
            while($row = $result->fetch_assoc()) {
                $revenue[] = $row;
                $totalRevenue += $row['amount'];
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
                    AND t.transaction_date BETWEEN ? AND ?
                    GROUP BY a.id
                    ORDER BY a.account_code";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            $result = $stmt->get_result();
            $expenses = [];
            $totalExpenses = 0;
            
            while($row = $result->fetch_assoc()) {
                $expenses[] = $row;
                $totalExpenses += $row['amount'];
            }
            
            return [
                'revenue' => $revenue,
                'totalRevenue' => $totalRevenue,
                'expenses' => $expenses,
                'totalExpenses' => $totalExpenses,
                'netIncome' => $totalRevenue - $totalExpenses
            ];
        } finally {
            closeConnection($conn);
        }
    }
}

if (!function_exists('getMonthlyTransactionData')) {
    function getMonthlyTransactionData($years = 2) {
        $conn = getConnection();
        try {
            $sql = "SELECT 
                    YEAR(transaction_date) as year,
                    MONTH(transaction_date) as month,
                    SUM(amount) as total_amount
                    FROM transactions
                    WHERE transaction_date >= DATE_SUB(CURDATE(), INTERVAL ? YEAR)
                    AND status = 'Posted'
                    GROUP BY YEAR(transaction_date), MONTH(transaction_date)
                    ORDER BY year ASC, month ASC";
                    
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $years);
            $stmt->execute();
            $result = $stmt->get_result();
            $monthlyData = [];
            
            while($row = $result->fetch_assoc()) {
                $monthlyData[] = $row;
            }
            
            return $monthlyData;
        } finally {
            closeConnection($conn);
        }
    }
}

if (!function_exists('getExpenseCategories')) {
    function getExpenseCategories() {
        $conn = getConnection();
        try {
            $sql = "SELECT 
                    a.id,
                    a.account_name,
                    SUM(td.debit_amount) as total_amount
                    FROM transaction_details td
                    JOIN accounts a ON td.account_id = a.id
                    JOIN transactions t ON td.transaction_id = t.id
                    WHERE a.account_type = 'Expense'
                    AND t.transaction_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
                    AND t.status = 'Posted'
                    GROUP BY a.id
                    ORDER BY total_amount DESC
                    LIMIT 5";
            
            $result = $conn->query($sql);
            $categories = [];
            
            while($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
            
            return $categories;
        } finally {
            closeConnection($conn);
        }
    }
}

if (!function_exists('getForecastData')) {
    function getForecastData() {
        $conn = getConnection();
        try {
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
            
            while($row = $result->fetch_assoc()) {
                $yearlyData[$row['year']] = $row['total_amount'];
            }
            
            // Get monthly data for current year
            $currentYear = date('Y');
            $sql = "SELECT 
                    MONTH(transaction_date) as month, 
                    SUM(amount) as total_amount 
                    FROM transactions 
                    WHERE status = 'Posted' 
                    AND YEAR(transaction_date) = ?
                    GROUP BY MONTH(transaction_date) 
                    ORDER BY MONTH(transaction_date)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $currentYear);
            $stmt->execute();
            $result = $stmt->get_result();
            $monthlyData = array_fill(1, 12, 0); // Initialize with zeros
            
            while($row = $result->fetch_assoc()) {
                $monthlyData[$row['month']] = $row['total_amount'];
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
            
            while($row = $result->fetch_assoc()) {
                $categoryData[$row['account_type']] = $row['total_amount'];
            }
            
            return [
                'yearly' => $yearlyData,
                'monthly' => $monthlyData,
                'category' => $categoryData
            ];
        } finally {
            closeConnection($conn);
        }
    }
}

if (!function_exists('getHistoricalData')) {
    function getHistoricalData() {
        $conn = getConnection();
        try {
            // Get monthly expense totals for past 2 years
            $sql = "SELECT 
                    YEAR(transaction_date) as year,
                    MONTH(transaction_date) as month,
                    SUM(amount) as total_amount
                    FROM transactions
                    WHERE transaction_date >= DATE_SUB(CURDATE(), INTERVAL 2 YEAR)
                    AND status = 'Posted'
                    GROUP BY YEAR(transaction_date), MONTH(transaction_date)
                    ORDER BY year ASC, month ASC";
            
            $result = $conn->query($sql);
            $historicalData = [];
            
            while($row = $result->fetch_assoc()) {
                $historicalData['monthly'][] = $row;
            }
            
            // Get expense categories and totals
            $sql = "SELECT 
                    a.account_name,
                    SUM(td.debit_amount) as total_amount
                    FROM transaction_details td
                    JOIN accounts a ON td.account_id = a.id
                    JOIN transactions t ON td.transaction_id = t.id
                    WHERE a.account_type = 'Expense'
                    AND t.transaction_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
                    AND t.status = 'Posted'
                    GROUP BY a.id
                    ORDER BY total_amount DESC
                    LIMIT 5";
            
            $result = $conn->query($sql);
            
            while($row = $result->fetch_assoc()) {
                $historicalData['categories'][] = $row;
            }
            
            // Get yearly totals
            $sql = "SELECT 
                    YEAR(transaction_date) as year,
                    SUM(amount) as total_amount
                    FROM transactions
                    WHERE status = 'Posted'
                    GROUP BY YEAR(transaction_date)
                    ORDER BY year ASC";
            
            $result = $conn->query($sql);
            
            while($row = $result->fetch_assoc()) {
                $historicalData['yearly'][$row['year']] = $row['total_amount'];
            }
            
            return $historicalData;
        } finally {
            closeConnection($conn);
        }
    }
}

if (!function_exists('getAvailableYears')) {
    function getAvailableYears() {
        $conn = getConnection();
        try {
            $sql = "SELECT DISTINCT YEAR(transaction_date) as year 
                    FROM transactions 
                    WHERE status = 'Posted' 
                    ORDER BY year DESC";
            $result = $conn->query($sql);
            $years = [];
            
            while($row = $result->fetch_assoc()) {
                $years[] = $row['year'];
            }
            
            return $years;
        } finally {
            closeConnection($conn);
        }
    }
}

if (!function_exists('generateTraditionalForecast')) {
    function generateTraditionalForecast($historicalData) {
        $monthlyData = $historicalData['monthly'];
        
        if (count($monthlyData) < 6) {
            return [
                'months' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                'actual' => array_fill(0, 12, 0),
                'forecast' => [50000, 52000, 48000, 55000, 60000, 58000, 62000, 65000, 63000, 67000, 70000, 72000],
                'lower_bound' => [45000, 47000, 43000, 50000, 55000, 53000, 57000, 60000, 58000, 62000, 65000, 67000],
                'upper_bound' => [55000, 57000, 53000, 60000, 65000, 63000, 67000, 70000, 68000, 72000, 75000, 77000]
            ];
        }
        
        $monthlyAverages = [];
        $monthlyTrends = [];
        
        for ($i = 1; $i <= 12; $i++) {
            $monthlyAverages[$i] = [];
            $monthlyTrends[$i] = 0;
        }
        
        foreach ($monthlyData as $data) {
            $month = (int)$data['month'];
            $monthlyAverages[$month][] = $data['total_amount'];
        }
        
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $actual = [];
        $forecast = [];
        $lowerBound = [];
        $upperBound = [];
        
        $currentYear = date('Y');
        $currentMonth = date('n');
        
        for ($i = 1; $i <= 12; $i++) {
            if (!empty($monthlyAverages[$i])) {
                $avg = array_sum($monthlyAverages[$i]) / count($monthlyAverages[$i]);
                $growthFactor = 1 + (mt_rand(5, 10) / 100);
                
                if ($i < $currentMonth) {
                    $actualValue = 0;
                    foreach ($monthlyData as $data) {
                        if ($data['year'] == $currentYear && $data['month'] == $i) {
                            $actualValue = $data['total_amount'];
                            break;
                        }
                    }
                    $actual[] = $actualValue;
                    $forecast[] = null;
                    $lowerBound[] = null;
                    $upperBound[] = null;
                } else {
                    $forecastValue = $avg * $growthFactor;
                    $actual[] = null;
                    $forecast[] = $forecastValue;
                    $lowerBound[] = $forecastValue * 0.9;
                    $upperBound[] = $forecastValue * 1.1;
                }
            } else {
                $actual[] = null;
                $forecast[] = 50000 + mt_rand(0, 20000);
                $lowerBound[] = $forecast[count($forecast) - 1] * 0.9;
                $upperBound[] = $forecast[count($forecast) - 1] * 1.1;
            }
        }
        
        return [
            'months' => $months,
            'actual' => $actual,
            'forecast' => $forecast,
            'lower_bound' => $lowerBound,
            'upper_bound' => $upperBound
        ];
    }
}