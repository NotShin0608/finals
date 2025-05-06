<?php
session_start();

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Include necessary files
require_once 'config/functions.php';

$error = '';

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        $user = authenticateUser($username, $password);
        
        if ($user) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            
            // Log user access
            $conn = getConnection();
            $sql = "INSERT INTO user_access_logs (user_id, ip_address, user_agent) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $ip = $_SERVER['REMOTE_ADDR'];
            $userAgent = $_SERVER['HTTP_USER_AGENT'];
            $stmt->bind_param("iss", $user['id'], $ip, $userAgent);
            $stmt->execute();
            $accessLogId = $conn->insert_id;
            $_SESSION['access_log_id'] = $accessLogId;
            closeConnection($conn);
            
            // Redirect to dashboard
            header("Location: index.php");
            exit();
        } else {
            $error = 'Invalid username or password';
        }
    }
}

// Set page title
$pageTitle = "Login";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'includes/head.php'; ?>
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 40px;
        }
        .login-container {
            max-width: 400px;
            margin: 0 auto;
        }
        .logo-container {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo-container img {
            max-width: 100px;
            height: auto;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container login-container">
        <div class="logo-container">
            <img src="assets/img/logo.png" alt="Financial Management System Logo">
        </div>
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white text-center">
                <h4>Financial Management System</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form action="" method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Login</button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center">
                <small class="text-muted">© <?php echo date('Y'); ?> Financial Management System</small>
            </div>
        </div>
    </div>
</body>
</html>
