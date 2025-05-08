<?php
/**
 * Get recent payments
 * @param int $limit Number of payments to retrieve
 * @return array Array of recent payments
 */
function getRecentPayments($limit = 5) {
    $conn = getConnection();
    $payments = [];
    
    try {
        $sql = "SELECT 
                    p.payment_date,
                    p.receipt_number,
                    p.payer,
                    p.payment_method,
                    p.amount
                FROM payments p
                WHERE p.status = 'Completed'
                ORDER BY p.payment_date DESC
                LIMIT ?";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $payments[] = $row;
        }
        
        return $payments;
    } catch (Exception $e) {
        error_log("Error in getRecentPayments: " . $e->getMessage());
        return [];
    } finally {
        closeConnection($conn);
    }
}

/**
 * Get payment by ID
 * @param int $paymentId The ID of the payment to retrieve
 * @return array|null Payment details or null if not found
 */
function getPaymentById($paymentId) {
    $conn = getConnection();
    
    try {
        $sql = "SELECT 
                    p.*,
                    u.username as created_by_user
                FROM payments p
                LEFT JOIN users u ON p.created_by = u.id
                WHERE p.id = ?";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $paymentId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    } catch (Exception $e) {
        error_log("Error in getPaymentById: " . $e->getMessage());
        return null;
    } finally {
        closeConnection($conn);
    }
}

/**
 * Create a new payment
 * @param array $paymentData Payment details
 * @return array Response with success status and message
 */
function createPayment($paymentData) {
    $conn = getConnection();
    
    try {
        $conn->begin_transaction();
        
        $sql = "INSERT INTO payments (
                    payment_date,
                    receipt_number,
                    payer,
                    payment_method,
                    amount,
                    description,
                    status,
                    created_by,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, 'Completed', ?, NOW())";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            'ssssdsi',
            $paymentData['payment_date'],
            $paymentData['receipt_number'],
            $paymentData['payer'],
            $paymentData['payment_method'],
            $paymentData['amount'],
            $paymentData['description'],
            $paymentData['created_by']
        );
        
        $stmt->execute();
        
        // Create corresponding transaction entry
        $transactionData = [
            'transaction_date' => $paymentData['payment_date'],
            'reference_number' => $paymentData['receipt_number'],
            'description' => "Payment received from " . $paymentData['payer'],
            'amount' => $paymentData['amount'],
            'type' => 'Payment',
            'status' => 'Completed',
            'created_by' => $paymentData['created_by']
        ];
        
        createTransaction($transactionData);
        
        $conn->commit();
        
        return [
            'success' => true,
            'message' => 'Payment created successfully'
        ];
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error in createPayment: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error creating payment: ' . $e->getMessage()
        ];
    } finally {
        closeConnection($conn);
    }
}

/**
 * Update an existing payment
 * @param int $paymentId Payment ID to update
 * @param array $paymentData Updated payment details
 * @return array Response with success status and message
 */
function updatePayment($paymentId, $paymentData) {
    $conn = getConnection();
    
    try {
        $conn->begin_transaction();
        
        $sql = "UPDATE payments 
                SET payment_date = ?,
                    receipt_number = ?,
                    payer = ?,
                    payment_method = ?,
                    amount = ?,
                    description = ?,
                    updated_by = ?,
                    updated_at = NOW()
                WHERE id = ?";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            'ssssdsi',
            $paymentData['payment_date'],
            $paymentData['receipt_number'],
            $paymentData['payer'],
            $paymentData['payment_method'],
            $paymentData['amount'],
            $paymentData['description'],
            $paymentData['updated_by'],
            $paymentId
        );
        
        $stmt->execute();
        
        // Update corresponding transaction
        $transactionData = [
            'transaction_date' => $paymentData['payment_date'],
            'reference_number' => $paymentData['receipt_number'],
            'description' => "Payment received from " . $paymentData['payer'],
            'amount' => $paymentData['amount'],
            'updated_by' => $paymentData['updated_by']
        ];
        
        updateTransactionByReference($paymentData['receipt_number'], $transactionData);
        
        $conn->commit();
        
        return [
            'success' => true,
            'message' => 'Payment updated successfully'
        ];
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error in updatePayment: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error updating payment: ' . $e->getMessage()
        ];
    } finally {
        closeConnection($conn);
    }
}