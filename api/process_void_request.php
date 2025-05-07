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

    // Verify user is admin
    $userRole = getUserRole($_SESSION['user_id']);
    if ($userRole !== 'admin') {
        throw new Exception('Only administrators can process void requests');
    }

    $requestId = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if (!$requestId || !in_array($action, ['approve', 'reject'])) {
        throw new Exception('Invalid request parameters');
    }

    // Get void request details
    $stmt = $conn->prepare("
        SELECT vr.*, t.id as transaction_id 
        FROM void_requests vr
        JOIN transactions t ON vr.transaction_id = t.id
        WHERE vr.id = ? AND vr.status = 'Pending'
        FOR UPDATE
    ");
    $stmt->bind_param("i", $requestId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Void request not found or already processed');
    }

    $voidRequest = $result->fetch_assoc();

    // Update void request status
    $newStatus = ($action === 'approve') ? 'Approved' : 'Rejected';
    $stmt = $conn->prepare("
        UPDATE void_requests 
        SET status = ?,
            processed_by = ?,
            processed_date = NOW()
        WHERE id = ?
    ");
    $stmt->bind_param("sii", $newStatus, $_SESSION['user_id'], $requestId);
    $stmt->execute();

    // If approved, update transaction status
    if ($action === 'approve') {
        $stmt = $conn->prepare("
            UPDATE transactions 
            SET status = 'Voided'
            WHERE id = ?
        ");
        $stmt->bind_param("i", $voidRequest['transaction_id']);
        $stmt->execute();
    } else {
        // If rejected, revert transaction status to Posted
        $stmt = $conn->prepare("
            UPDATE transactions 
            SET status = 'Posted'
            WHERE id = ?
        ");
        $stmt->bind_param("i", $voidRequest['transaction_id']);
        $stmt->execute();
    }

    $conn->commit();
    echo json_encode([
        'success' => true,
        'message' => 'Void request ' . ($action === 'approve' ? 'approved' : 'rejected') . ' successfully'
    ]);

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    error_log("Process Void Request Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error ' . ($action ?? 'processing') . ' void request: ' . $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>