<?php
session_start();
require_once 'config/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$response = ['success' => false, 'message' => ''];

try {
    $userId = $_SESSION['user_id'];
    $fullName = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    
    // Handle file upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $fileInfo = $_FILES['profile_picture'];
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileType = mime_content_type($fileInfo['tmp_name']);
        
        if (!in_array($fileType, $allowedTypes)) {
            throw new Exception('Invalid file type. Only JPEG, PNG, and GIF files are allowed.');
        }
        
        // Generate unique filename
        $extension = pathinfo($fileInfo['name'], PATHINFO_EXTENSION);
        $filename = uniqid('profile_') . '.' . $extension;
        $uploadPath = 'uploads/profiles/' . $filename;
        
        // Create directory if it doesn't exist
        if (!file_exists('uploads/profiles')) {
            mkdir('uploads/profiles', 0777, true);
        }
        
        // Move uploaded file
        if (!move_uploaded_file($fileInfo['tmp_name'], $uploadPath)) {
            throw new Exception('Failed to upload file.');
        }
        
        // Update profile picture in database
        $conn = getConnection();
        $sql = "UPDATE users SET profile_picture = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $uploadPath, $userId);
        $stmt->execute();
    }
    
    // Update user information
    $conn = getConnection();
    $sql = "UPDATE users SET full_name = ?, email = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $fullName, $email, $userId);
    
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Profile updated successfully.';
    } else {
        throw new Exception('Failed to update profile.');
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);