<?php
require_once 'database.php';
require_once __DIR__ . '/common_functions.php';

// Account-related functions
if (!function_exists('getAccountNamesByType')) {
    function getAccountNamesByType($type) {
        $conn = getConnection();
        try {
            $sql = "SELECT * FROM account_types WHERE type = ? ORDER BY name";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $type);
            $stmt->execute();
            $result = $stmt->get_result();
            $accounts = [];
            
            while($row = $result->fetch_assoc()) {
                $accounts[] = $row;
            }
            
            return $accounts;
        } finally {
            closeConnection($conn);
        }
    }
}

// Remove the other declarations of getAccountNamesByType from lines 1222 and beyond