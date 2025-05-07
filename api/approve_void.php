// api/approve_void.php
<?php
require_once '../config/functions.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $conn = getConnection();
    $conn->begin_transaction();

    $requestId = $_POST['id'];
    $action = $_POST['action'];

    if ($action === 'approve') {
        // Update void request status
        $stmt = $conn->prepare("
            UPDATE void_requests 
            SET status = 'Approved', 
                approved_by = ?, 
                approved_at = NOW() 
            WHERE id = ?
        ");
        $stmt->bind_param("ii", $_SESSION['user_id'], $requestId);
        $stmt->execute();

        // Get transaction ID
        $stmt = $conn->prepare("
            SELECT transaction_id 
            FROM void_requests 
            WHERE id = ?
        ");
        $stmt->bind_param("i", $requestId);
        $stmt->execute();
        $result = $stmt->get_result();
        $transactionId = $result->fetch_assoc()['transaction_id'];

        // Update transaction status
        $stmt = $conn->prepare("
            UPDATE transactions 
            SET status = 'Voided' 
            WHERE id = ?
        ");
        $stmt->bind_param("i", $transactionId);
        $stmt->execute();
    } else {
        // Reject void request
        $stmt = $conn->prepare("
            UPDATE void_requests 
            SET status = 'Rejected', 
                approved_by = ?, 
                approved_at = NOW() 
            WHERE id = ?
        ");
        $stmt->bind_param("ii", $_SESSION['user_id'], $requestId);
        $stmt->execute();
    }

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>