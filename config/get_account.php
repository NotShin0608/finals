<?php
// Make sure we only define the function once
if (!function_exists('getUserData')) {
    /**
     * Get user data from the database
     * @param int $userId The user ID to fetch data for
     * @return array The user data or default values if not found
     */
    function getUserData($userId) {
        // Check if getConnection exists and call it, otherwise return default values
        if (!function_exists('getConnection')) {
            return [
                'id' => $userId,
                'username' => 'NotShin0608',
                'full_name' => '',
                'email' => '',
                'role' => '',
                'profile_picture' => ''
            ];
        }

        $conn = getConnection();
        try {
            $sql = "SELECT 
                    u.id,
                    u.username,
                    COALESCE(u.full_name, '') as full_name,
                    COALESCE(u.email, '') as email,
                    COALESCE(u.role, '') as role,
                    COALESCE(u.profile_picture, '') as profile_picture
                FROM users u 
                WHERE u.id = ?";
                
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                return $row;
            }
            
            // Return default values if no user found
            return [
                'id' => $userId,
                'username' => 'NotShin0608',
                'full_name' => '',
                'email' => '',
                'role' => '',
                'profile_picture' => ''
            ];
        } catch (Exception $e) {
            error_log("Error in getUserData: " . $e->getMessage());
            // Return default values on error
            return [
                'id' => $userId,
                'username' => 'NotShin0608',
                'full_name' => '',
                'email' => '',
                'role' => '',
                'profile_picture' => ''
            ];
        } finally {
            if (isset($conn)) {
                closeConnection($conn);
            }
        }
    }
}