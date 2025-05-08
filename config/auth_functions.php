<?php
if (!function_exists('validateLogin')) {
    function validateLogin($username, $password) {
        $conn = getConnection();
        try {
            $sql = "SELECT id, username, password, role, full_name FROM users WHERE username = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    // Check if user is unverified
                    if ($user['role'] === '') {
                        return ['success' => false, 'message' => 'Your account is pending verification.'];
                    }
                    
                    return [
                        'success' => true,
                        'user' => [
                            'id' => $user['id'],
                            'username' => $user['username'],
                            'role' => $user['role'],
                            'full_name' => $user['full_name']
                        ]
                    ];
                }
            }
            
            return ['success' => false, 'message' => 'Invalid username or password'];
        } finally {
            closeConnection($conn);
        }
    }
}

if (!function_exists('getUserRole')) {
    function getUserRole($userId) {
        $conn = getConnection();
        try {
            $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            return $user['role'] ?? 'user';
        } finally {
            closeConnection($conn);
        }
    }
}

if (!function_exists('isAdmin')) {
    function isAdmin($userId) {
        return getUserRole($userId) === 'admin';
    }
}

if (!function_exists('isAccountant')) {
    function isAccountant($userId) {
        return getUserRole($userId) === 'accountant';
    }
}

if (!function_exists('logUserLogin')) {
    function logUserLogin($userId) {
        $conn = getConnection();
        try {
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            
            // Create table if not exists
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
            
            $sql = "INSERT INTO user_access_logs (user_id, ip_address, user_agent) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iss", $userId, $ipAddress, $userAgent);
            $stmt->execute();
            
            $_SESSION['access_log_id'] = $conn->insert_id;
        } finally {
            closeConnection($conn);
        }
    }
}

if (!function_exists('logUserLogout')) {
    function logUserLogout() {
        if (isset($_SESSION['access_log_id']) && $_SESSION['access_log_id'] > 0) {
            $conn = getConnection();
            try {
                $logId = $_SESSION['access_log_id'];
                $sql = "UPDATE user_access_logs SET logout_time = CURRENT_TIMESTAMP WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $logId);
                $stmt->execute();
            } finally {
                closeConnection($conn);
            }
        }
    }
}

if (!function_exists('logActivity')) {
    function logActivity($userId, $activityType, $activityDetails) {
        $conn = getConnection();
        try {
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            
            // Create table if not exists
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
            
            $sql = "INSERT INTO activity_logs (user_id, activity_type, activity_details, ip_address) 
                   VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isss", $userId, $activityType, $activityDetails, $ipAddress);
            $stmt->execute();
        } finally {
            closeConnection($conn);
        }
    }
}