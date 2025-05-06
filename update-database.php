<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

// Include necessary files
require_once 'config/functions.php';

$success = '';
$error = '';

// Process update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $conn = getConnection();
    
    try {
        // Create user_access_logs table if it doesn't exist
        $sql = "CREATE TABLE IF NOT EXISTS user_access_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            logout_time TIMESTAMP NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        $conn->query($sql);
        
        // Create activity_logs table if it doesn't exist
        $sql = "CREATE TABLE IF NOT EXISTS activity_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            activity_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            activity_type VARCHAR(50) NOT NULL,
            activity_details TEXT,
            ip_address VARCHAR(45) NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        $conn->query($sql);
        
        $success = "Database updated successfully!";
    } catch (Exception $e) {
        $error = "Error updating database: " . $e->getMessage();
    }
    
    closeConnection($conn);
}

// Set page title
$pageTitle = "Update Database";
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
                    <h1 class="h2">Update Database</h1>
                </div>
                
                <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Database Update</h5>
                    </div>
                    <div class="card-body">
                        <p>This will update the database schema to include new tables for user access logs and activity tracking.</p>
                        <form action="" method="POST">
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="submit" name="update" class="btn btn-primary">Update Database</button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
