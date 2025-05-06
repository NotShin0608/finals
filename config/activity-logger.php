<?php
// Function to log user activity
function logActivity($userId, $activityType, $activityDetails) {
    $conn = getConnection();
    $sql = "INSERT INTO activity_logs (user_id, activity_type, activity_details, ip_address) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt->bind_param("isss", $userId, $activityType, $activityDetails, $ip);
    $stmt->execute();
    closeConnection($conn);
}
