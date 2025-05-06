<?php
session_start();

// Log user logout if access_log_id exists
if (isset($_SESSION['access_log_id']) && isset($_SESSION['user_id'])) {
    require_once 'config/functions.php';
    
    $conn = getConnection();
    $sql = "UPDATE user_access_logs SET logout_time = CURRENT_TIMESTAMP WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_SESSION['access_log_id']);
    $stmt->execute();
    closeConnection($conn);
}

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: login.php");
exit();
