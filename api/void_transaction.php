<?php
session_start();
require_once '../config/functions.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $conn = getConnection();
    $conn->begin_transaction();

    $transactionId = intval($_POST['transaction_id']);
    $reason = trim($_POST['reason']);
    $userId = $_SESSION['user_id'];

    // Check user role
    $roleStmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $roleStmt->bind_param("i", $userId);
    $roleStmt->execute();
    $userRole = $roleStmt->get_result()->fetch_assoc()['role'];

    // Check if transaction exists and can be voided
    $checkStmt = $conn->prepare("
        SELECT status 
        FROM transactions 
        WHERE id = ? 
        FOR UPDATE
    ");
    $checkStmt->bind_param("i", $transactionId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Transaction not found");
    }
    
    $transaction = $result->fetch_assoc();
    if ($transaction['status'] !== 'Posted') {
        throw new Exception("Transaction cannot be voided - current status: " . $transaction['status']);
    }

    // Insert void request
    $insertStmt = $conn->prepare("
        INSERT INTO void_requests (
            transaction_id,
            requested_by,
            reason,
            status,
            requested_date
        ) VALUES (?, ?, ?, ?, NOW())
    ");

    // Only admin can directly void, accountant needs approval
    $status = ($userRole === 'admin') ? 'Approved' : 'Pending';
    $insertStmt->bind_param("iiss", $transactionId, $userId, $reason, $status);
    
    if (!$insertStmt->execute()) {
        throw new Exception("Failed to create void request: " . $insertStmt->error);
    }

    // Update transaction status based on user role
    $newStatus = ($userRole === 'admin') ? 'Voided' : 'Pending Void';
    $updateStmt = $conn->prepare("
        UPDATE transactions 
        SET status = ? 
        WHERE id = ? AND status = 'Posted'
    ");
    $updateStmt->bind_param("si", $newStatus, $transactionId);
    
    if (!$updateStmt->execute()) {
        throw new Exception("Failed to update transaction status: " . $updateStmt->error);
    }

    $conn->commit();
    echo json_encode([
        'success' => true,
        'message' => ($userRole === 'admin') ? 
            'Transaction voided successfully' : 
            'Void request submitted for approval'
    ]);

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    error_log("Void Transaction Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>