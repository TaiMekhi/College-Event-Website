<?php
// API endpoint for updating user profile
// Path: /api/users/update.php

// Set headers for JSON response
header('Content-Type: application/json');

// Start session
session_start();

// Include database configuration file
require_once '../../dbh.inc.php';

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo jsonResponse(false, null, "Invalid request method");
    exit();
}

// Check if required fields are provided
if (!isset($_POST['user_id']) || empty($_POST['user_id']) ||
    !isset($_POST['first_name']) || empty($_POST['first_name'])) {
    
    echo jsonResponse(false, null, "User ID and first name are required");
    exit();
}

// Get and sanitize input
$user_id = sanitizeInput($_POST['user_id']);
$first_name = sanitizeInput($_POST['first_name']);
$last_name = sanitizeInput($_POST['last_name'] ?? '');
$password = isset($_POST['password']) && !empty($_POST['password']) ? $_POST['password'] : null;

// Verify user is authorized
if (!isset($_SESSION['userID']) || $_SESSION['userID'] != $user_id) {
    echo jsonResponse(false, null, "Unauthorized user");
    exit();
}

try {
    // Create database connection
    $conn = getConnection();
    
    // Start preparing the query
    $updateFields = ["first_name = ?", "last_name = ?"];
    $params = [$first_name, $last_name];
    $types = "ss";
    
    // Add password if provided
    if ($password !== null) {
        $updateFields[] = "user_password = ?";
        $params[] = $password;  // In a real app, you would hash this password
        $types .= "s";
    }
    
    // Build the query
    $query = "UPDATE users SET " . implode(", ", $updateFields) . " WHERE user_id = ?";
    $params[] = $user_id;
    $types .= "i";
    
    // Prepare and execute the statement
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0 || $stmt->affected_rows === 0) {
        // Get updated user data
        $selectQuery = "SELECT user_id, user_name, first_name, last_name, user_level, university_id 
                       FROM users WHERE user_id = ?";
        
        $stmt = $conn->prepare($selectQuery);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $userData = $result->fetch_assoc();
            
            // Remove sensitive information
            unset($userData['user_password']);
            
            echo jsonResponse(true, ['message' => 'Profile updated successfully', 'user' => $userData]);
        } else {
            echo jsonResponse(true, ['message' => 'Profile updated successfully']);
        }
    } else {
        echo jsonResponse(false, null, "Failed to update profile");
    }
} catch (Exception $e) {
    echo jsonResponse(false, null, "Error: " . $e->getMessage());
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>