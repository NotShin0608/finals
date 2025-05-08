<?php
if (!function_exists('formatCurrency')) {
    function formatCurrency($amount) {
        // First, remove any existing formatting (commas)
        $cleanAmount = str_replace(',', '', $amount);
        // Convert to float
        $numericAmount = floatval($cleanAmount);
        // Format the number and add the peso sign
        return '₱' . number_format($numericAmount, 2);
    }
}


if (!function_exists('generateReferenceNumber')) {
    function generateReferenceNumber($prefix = 'JE', $date = null) {
        $date = $date ?? date('Y-m-d');
        $conn = getConnection();
        
        try {
            $dateObj = new DateTime($date);
            $year = $dateObj->format('y');
            $month = $dateObj->format('m');
            $day = $dateObj->format('d');
            
            $sql = "SELECT MAX(reference_number) as max_ref 
                    FROM transactions 
                    WHERE reference_number LIKE ?";
            $pattern = $prefix . $year . $month . $day . '%';
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('s', $pattern);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            if ($result['max_ref']) {
                $sequence = intval(substr($result['max_ref'], -4)) + 1;
            } else {
                $sequence = 1;
            }
            
            return $prefix . $year . $month . $day . str_pad($sequence, 4, '0', STR_PAD_LEFT);
        } finally {
            closeConnection($conn);
        }
    }
}

if (!function_exists('generateVoucherNumber')) {
    function generateVoucherNumber($prefix = 'CD', $date = null) {
        $date = $date ?? date('Y-m-d');
        $conn = getConnection();
        
        try {
            $dateObj = new DateTime($date);
            $year = $dateObj->format('y');
            $month = $dateObj->format('m');
            $day = $dateObj->format('d');
            
            $sql = "SELECT MAX(voucher_number) as max_ref 
                    FROM disbursements 
                    WHERE voucher_number LIKE ?";
            $pattern = $prefix . $year . $month . $day . '%';
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('s', $pattern);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            if ($result['max_ref']) {
                $sequence = intval(substr($result['max_ref'], -4)) + 1;
            } else {
                $sequence = 1;
            }
            
            return $prefix . $year . $month . $day . str_pad($sequence, 4, '0', STR_PAD_LEFT);
        } finally {
            closeConnection($conn);
        }
    }
}