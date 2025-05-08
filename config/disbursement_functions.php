<?php

function getDisbursements($status, $startDate, $endDate, $search) {
    $conn = getConnection();
    try {
        $sql = "SELECT 
                d.id,
                d.voucher_number,
                d.disbursement_date,
                d.payee,
                d.amount,
                d.status,
                d.created_at,
                d.void_reason,
                d.approved_by,
                d.approved_at,
                u.username as approver_name
            FROM disbursements d
            LEFT JOIN users u ON d.approved_by = u.id
            WHERE 1=1";
        
        // Rest of your function remains the same
        
        $params = [];
        $types = '';

        // Print the initial parameters
        error_log("Initial parameters: Status=$status, StartDate=$startDate, EndDate=$endDate, Search=$search");

        if ($status && $status !== 'All') {
            $sql .= " AND d.status = ?";
            $params[] = $status;
            $types .= 's';
        }

        if ($startDate) {
            $sql .= " AND DATE(d.disbursement_date) >= ?";
            $params[] = $startDate;
            $types .= 's';
        }

        if ($endDate) {
            $sql .= " AND DATE(d.disbursement_date) <= ?";
            $params[] = $endDate;
            $types .= 's';
        }

        if ($search) {
            $sql .= " AND (d.voucher_number LIKE ? OR d.payee LIKE ? OR d.description LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= 'sss';
        }

        $sql .= " ORDER BY d.disbursement_date DESC";

        // Debug: Print the final SQL query and parameters
        error_log("Final SQL Query: " . $sql);
        error_log("Parameters: " . print_r($params, true));
        
        $stmt = $conn->prepare($sql);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        
        $disbursements = [];
        while ($row = $result->fetch_assoc()) {
            $disbursements[] = $row;
        }

        // Debug: Print the number of results
        error_log("Number of disbursements found: " . count($disbursements));
        
        return $disbursements;

    } catch (Exception $e) {
        error_log("Error in getDisbursements: " . $e->getMessage());
        throw $e; // Re-throw the exception for debugging
    } finally {
        if ($conn) {
            $conn->close();
        }
    }
}

function createDisbursement($data) {
    $conn = getConnection();
    
    try {
        $sql = "INSERT INTO disbursements (
                    reference_number,
                    amount,
                    description,
                    disbursement_date,
                    recipient,
                    category,
                    created_by,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            'sdsssssi',
            $data['reference_number'],
            $data['amount'],
            $data['description'],
            $data['disbursement_date'],
            $data['recipient'],
            $data['category'],
            $data['created_by']
        );
        
        $stmt->execute();
        
        return [
            'success' => true,
            'message' => 'Disbursement created successfully'
        ];
    } catch (Exception $e) {
        error_log("Error in createDisbursement: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error creating disbursement: ' . $e->getMessage()
        ];
    } finally {
        closeConnection($conn);
    }
}

/**
 * Approve disbursement
 * @param int $id Disbursement ID
 * @param int $approver_id User ID of approver
 * @return array Response with success status and message
 */
function approveDisbursement($id, $approver_id) {
    $conn = getConnection();
    
    try {
        // Check current status
        $check_sql = "SELECT status FROM disbursements WHERE id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param('i', $id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $disbursement = $result->fetch_assoc();
        
        if (!$disbursement || $disbursement['status'] !== 'waiting_for_approval') {
            return [
                'success' => false,
                'message' => 'Disbursement cannot be approved. Invalid status or not found.'
            ];
        }
        
        // Update status
        $sql = "UPDATE disbursements 
                SET status = 'completed',
                    approved_by = ?,
                    approved_at = NOW()
                WHERE id = ?";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $approver_id, $id);
        $stmt->execute();
        
        return [
            'success' => true,
            'message' => 'Disbursement approved successfully'
        ];
    } catch (Exception $e) {
        error_log("Error in approveDisbursement: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error approving disbursement: ' . $e->getMessage()
        ];
    } finally {
        closeConnection($conn);
    }
}

/**
 * Reject disbursement
 * @param int $id Disbursement ID
 * @param int $rejecter_id User ID of rejecter
 * @param string $reason Rejection reason
 * @return array Response with success status and message
 */
function rejectDisbursement($id, $rejecter_id, $reason) {
    $conn = getConnection();
    
    try {
        // Check current status
        $check_sql = "SELECT status FROM disbursements WHERE id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param('i', $id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $disbursement = $result->fetch_assoc();
        
        if (!$disbursement || $disbursement['status'] !== 'waiting_for_approval') {
            return [
                'success' => false,
                'message' => 'Disbursement cannot be rejected. Invalid status or not found.'
            ];
        }
        
        // Update status
        $sql = "UPDATE disbursements 
                SET status = 'rejected',
                    rejected_by = ?,
                    rejected_at = NOW(),
                    description = CONCAT(description, '\nRejection reason: ', ?)
                WHERE id = ?";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('isi', $rejecter_id, $reason, $id);
        $stmt->execute();
        
        return [
            'success' => true,
            'message' => 'Disbursement rejected successfully'
        ];
    } catch (Exception $e) {
        error_log("Error in rejectDisbursement: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error rejecting disbursement: ' . $e->getMessage()
        ];
    } finally {
        closeConnection($conn);
    }
}

/**
 * Request void for disbursement
 * @param int $id Disbursement ID
 * @param int $requester_id User ID of void requester
 * @param string $reason Void reason
 * @return array Response with success status and message
 */
function requestVoidDisbursement($id, $requester_id, $reason) {
    $conn = getConnection();
    
    try {
        // Check current status
        $check_sql = "SELECT status FROM disbursements WHERE id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param('i', $id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $disbursement = $result->fetch_assoc();
        
        if (!$disbursement || $disbursement['status'] !== 'waiting_for_approval') {
            return [
                'success' => false,
                'message' => 'Disbursement cannot be voided. Only entries in waiting for approval status can be voided.'
            ];
        }
        
        // Update status
        $sql = "UPDATE disbursements 
                SET status = 'void_requested',
                    void_requested_by = ?,
                    void_requested_at = NOW(),
                    void_reason = ?
                WHERE id = ?";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('isi', $requester_id, $reason, $id);
        $stmt->execute();
        
        return [
            'success' => true,
            'message' => 'Void request submitted successfully'
        ];
    } catch (Exception $e) {
        error_log("Error in requestVoidDisbursement: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error requesting void: ' . $e->getMessage()
        ];
    } finally {
        closeConnection($conn);
    }
}

/**
 * Approve void request for disbursement
 * @param int $id Disbursement ID
 * @param int $approver_id User ID of void approver
 * @return array Response with success status and message
 */
function approveVoidDisbursement($id, $approver_id) {
    $conn = getConnection();
    
    try {
        // Check current status
        $check_sql = "SELECT status FROM disbursements WHERE id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param('i', $id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $disbursement = $result->fetch_assoc();
        
        if (!$disbursement || $disbursement['status'] !== 'void_requested') {
            return [
                'success' => false,
                'message' => 'Void request cannot be approved. Invalid status or not found.'
            ];
        }
        
        // Update status
        $sql = "UPDATE disbursements 
                SET status = 'voided',
                    void_approved_by = ?,
                    void_approved_at = NOW()
                WHERE id = ?";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $approver_id, $id);
        $stmt->execute();
        
        return [
            'success' => true,
            'message' => 'Void request approved successfully'
        ];
    } catch (Exception $e) {
        error_log("Error in approveVoidDisbursement: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error approving void request: ' . $e->getMessage()
        ];
    } finally {
        closeConnection($conn);
    }
}

/**
 * Get recent disbursements for dashboard display
 * @param int $limit Number of recent disbursements to return
 * @return array Array of recent disbursements
 */
function getRecentDisbursements($limit = 5) {
    $conn = getConnection();
    try {
        $sql = "SELECT 
                d.id,
                d.voucher_number,
                d.disbursement_date,
                d.payee,
                d.amount,
                d.status,
                d.description,
                u.username as created_by
            FROM disbursements d
            LEFT JOIN users u ON d.created_by = u.id
            WHERE d.status != 'Voided'
            ORDER BY d.disbursement_date DESC, d.id DESC
            LIMIT ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $disbursements = [];
        
        while ($row = $result->fetch_assoc()) {
            // Format the date
            $row['formatted_date'] = date('Y-m-d', strtotime($row['disbursement_date']));
            // Format the amount
            $row['formatted_amount'] = number_format($row['amount'], 2);
            $disbursements[] = $row;
        }
        
        return $disbursements;

    } catch (Exception $e) {
        error_log("Error in getRecentDisbursements: " . $e->getMessage());
        return [];
    } finally {
        $conn->close();
    }
}