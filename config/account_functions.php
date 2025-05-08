<?php
if (!function_exists('getAccountNamesByType')) {
    function getAccountNamesByType($type) {
        $conn = getConnection();
        try {
            $sql = "SELECT * FROM account_types WHERE type = ? ORDER BY name";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $type);
            $stmt->execute();
            $result = $stmt->get_result();
            $accounts = [];
            
            while($row = $result->fetch_assoc()) {
                $accounts[] = $row;
            }
            
            return $accounts;
        } finally {
            closeConnection($conn);
        }
    }
}

if (!function_exists('getAccountById')) {
    function getAccountById($id) {
        $conn = getConnection();
        try {
            $sql = "SELECT * FROM accounts WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_assoc();
        } finally {
            closeConnection($conn);
        }
    }
}

if (!function_exists('updateAccount')) {
    function updateAccount($id, $accountCode, $accountName, $accountType, $description) {
        $conn = getConnection();
        try {
            $sql = "UPDATE accounts SET account_code = ?, account_name = ?, account_type = ?, description = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssi", $accountCode, $accountName, $accountType, $description, $id);
            return $stmt->execute();
        } finally {
            closeConnection($conn);
        }
    }
}

if (!function_exists('getAllAccounts')) {
    function getAllAccounts() {
        $conn = getConnection();
        try {
            $sql = "SELECT * FROM accounts ORDER BY account_code";
            $result = $conn->query($sql);
            $accounts = [];
            
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $accounts[] = $row;
                }
            }
            
            return $accounts;
        } finally {
            closeConnection($conn);
        }
    }
}

if (!function_exists('getAccountBalance')) {
    function getAccountBalance($accountId) {
        $conn = getConnection();
        try {
            $sql = "SELECT balance FROM accounts WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $accountId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            return $row['balance'] ?? 0;
        } finally {
            closeConnection($conn);
        }
    }
}

if (!function_exists('getTotalAssets')) {
    function getTotalAssets() {
        $conn = getConnection();
        try {
            $sql = "SELECT SUM(balance) as total FROM accounts WHERE account_type = 'Asset'";
            $result = $conn->query($sql);
            $row = $result->fetch_assoc();
            return $row['total'] ?? 0;
        } finally {
            closeConnection($conn);
        }
    }
}

if (!function_exists('getTotalLiabilities')) {
    function getTotalLiabilities() {
        $conn = getConnection();
        try {
            $sql = "SELECT SUM(balance) as total FROM accounts WHERE account_type = 'Liability'";
            $result = $conn->query($sql);
            $row = $result->fetch_assoc();
            return $row['total'] ?? 0;
        } finally {
            closeConnection($conn);
        }
    }
}

if (!function_exists('getTotalEquity')) {
    function getTotalEquity() {
        $conn = getConnection();
        try {
            $sql = "SELECT SUM(balance) as total FROM accounts WHERE account_type = 'Equity'";
            $result = $conn->query($sql);
            $row = $result->fetch_assoc();
            return $row['total'] ?? 0;
        } finally {
            closeConnection($conn);
        }
    }
}