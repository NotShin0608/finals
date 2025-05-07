<?php
session_start();
require_once '../config/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

try {
    $conn = getConnection();
    
    // Check if user is admin
    $roleStmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $roleStmt->bind_param("i", $_SESSION['user_id']);
    $roleStmt->execute();
    $userRole = $roleStmt->get_result()->fetch_assoc()['role'];

    if ($userRole !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Only administrators can view approval requests']);
        exit;
    }
    
    // Basic query without account information
    $stmt = $conn->prepare("
        SELECT 
            vr.id,
            vr.transaction_id,
            vr.requested_date,
            vr.reason,
            vr.status,
            t.reference_number,
            t.transaction_date,
            t.description,
            t.amount,
            u.username as requested_by,
            u.role as requester_role
        FROM void_requests vr
        JOIN transactions t ON vr.transaction_id = t.id
        JOIN users u ON vr.requested_by = u.id
        WHERE vr.status = 'Pending'
        ORDER BY vr.requested_date DESC
    ");

    $stmt->execute();
    $result = $stmt->get_result();
    $requests = [];
    
    while ($row = $result->fetch_assoc()) {
        // Format dates
        $row['requested_date'] = date('Y-m-d H:i:s', strtotime($row['requested_date']));
        $row['transaction_date'] = date('Y-m-d', strtotime($row['transaction_date']));
        
        // Format amount
        $row['formatted_amount'] = number_format($row['amount'], 2);
        
        $requests[] = $row;
    }

    echo json_encode([
        'success' => true,
        'data' => $requests
    ]);

} catch (Exception $e) {
    error_log("Get Pending Voids Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error loading void requests: ' . $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>