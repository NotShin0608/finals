<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

// Include necessary files
require_once 'config/functions.php';

// Get user ID from URL
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($userId <= 0) {
    header("Location: user-control.php");
    exit();
}

// Function to get user details
function getUserDetails($userId) {
    $conn = getConnection();
    
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        closeConnection($conn);
        return $user;
    }
    
    closeConnection($conn);
    return null;
}

// Function to get user access logs
function getUserAccessLogs($userId) {
    $conn = getConnection();
    
    $sql = "SELECT * FROM user_access_logs WHERE user_id = ? ORDER BY login_time DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $logs = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
    }
    
    closeConnection($conn);
    return $logs;
}

// Function to get user activity logs
function getUserActivityLogs($userId) {
    $conn = getConnection();
    
    $sql = "SELECT * FROM activity_logs WHERE user_id = ? ORDER BY activity_time DESC LIMIT 100";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $logs = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
    }
    
    closeConnection($conn);
    return $logs;
}

// Get user details
$user = getUserDetails($userId);

if (!$user) {
    header("Location: user-control.php");
    exit();
}

// Get user access logs
$accessLogs = getUserAccessLogs($userId);

// Get user activity logs
$activityLogs = getUserActivityLogs($userId);

// Set page title
$pageTitle = "User Logs - " . $user['username'];
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
                    <h1 class="h2">User Logs: <?php echo $user['username']; ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="user-control.php" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Back to User Control
                        </a>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">User Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Username:</strong> <?php echo $user['username']; ?></p>
                                        <p><strong>Full Name:</strong> <?php echo $user['full_name']; ?></p>
                                        <p><strong>Email:</strong> <?php echo $user['email']; ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Role:</strong> <span class="badge <?php echo $user['role'] === 'Admin' ? 'bg-danger' : ($user['role'] === 'Accountant' ? 'bg-warning' : 'bg-info'); ?>"><?php echo $user['role']; ?></span></p>
                                        <p><strong>Created:</strong> <?php echo date('Y-m-d H:i:s', strtotime($user['created_at'])); ?></p>
                                        <p><strong>Status:</strong> <span class="badge bg-success">Active</span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Access Logs</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>Login Time</th>
                                                <th>Logout Time</th>
                                                <th>Duration</th>
                                                <th>IP Address</th>
                                                <th>Browser</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($accessLogs as $log): ?>
                                            <tr>
                                                <td><?php echo date('Y-m-d H:i:s', strtotime($log['login_time'])); ?></td>
                                                <td><?php echo $log['logout_time'] ? date('Y-m-d H:i:s', strtotime($log['logout_time'])) : 'Active/No Logout'; ?></td>
                                                <td>
                                                    <?php 
                                                    if ($log['logout_time']) {
                                                        $login = new DateTime($log['login_time']);
                                                        $logout = new DateTime($log['logout_time']);
                                                        $diff = $login->diff($logout);
                                                        echo $diff->format('%H:%I:%S');
                                                    } else {
                                                        echo 'N/A';
                                                    }
                                                    ?>
                                                </td>
                                                <td><?php echo $log['ip_address']; ?></td>
                                                <td><?php echo substr($log['user_agent'], 0, 50) . (strlen($log['user_agent']) > 50 ? '...' : ''); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($accessLogs)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center">No access logs found</td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Activity Logs</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>Time</th>
                                                <th>Activity</th>
                                                <th>Details</th>
                                                <th>IP Address</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($activityLogs)): ?>
                                                <?php foreach ($activityLogs as $log): ?>
                                                <tr>
                                                    <td><?php echo date('Y-m-d H:i:s', strtotime($log['activity_time'])); ?></td>
                                                    <td><?php echo $log['activity_type']; ?></td>
                                                    <td><?php echo $log['activity_details']; ?></td>
                                                    <td><?php echo $log['ip_address']; ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="4" class="text-center">No activity logs found</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
