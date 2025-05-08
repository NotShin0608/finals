<?php
session_start();

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

require_once 'config/functions.php';

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        $result = validateLogin($username, $password);
        
        if ($result['success']) {
            // Set session variables
            $_SESSION['user_id'] = $result['user']['id'];
            $_SESSION['username'] = $result['user']['username'];
            $_SESSION['user_role'] = $result['user']['role'];
            $_SESSION['full_name'] = $result['user']['full_name'];
            
            // Log the login
            logUserLogin($result['user']['id']);
            
            // Redirect to dashboard
            header("Location: index.php");
            exit();
        } else {
            $error = $result['message'];
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
                <h4>Login</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form action="" method="POST" novalidate>
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required 
                               value="<?php echo htmlspecialchars($username); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Login</button>
                    </div>
                    
                    <hr class="my-4">
                    <div class="text-center">
                        <p class="mb-0">Don't have an account?</p>
                        <a href="register.php" class="btn btn-link">Register Now</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>