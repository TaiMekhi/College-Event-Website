<?php
// API endpoint for joining a university
// Path: /api/users/join_university.php

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
    !isset($_POST['university_id']) || empty($_POST['university_id'])) {
    
    echo jsonResponse(false, null, "User ID and university ID are required");
    exit();
}

// Get and sanitize input
$user_id = sanitizeInput($_POST['user_id']);
$university_id = sanitizeInput($_POST['university_id']);

// Verify user is authorized
if (!isset($_SESSION['userID']) || $_SESSION['userID'] != $user_id) {
    echo jsonResponse(false, null, "Unauthorized user");
    exit();
}

try {
    // Create database connection
    $conn = getConnection();
    
    // First, check if the university exists
    $checkQuery = "SELECT university_id, name FROM university WHERE university_id = ?";
    
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("i", $university_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo jsonResponse(false, null, "University not found");
        exit();
    }
    
    $universityData = $result->fetch_assoc();
    $universityName = $universityData['name'];
    
    // Update user's university
    $updateQuery = "UPDATE users SET university_id = ? WHERE user_id = ?";
    
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("ii", $university_id, $user_id);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        echo jsonResponse(true, [
            'message' => 'Successfully joined university',
            'university_id' => $university_id,
            'university_name' => $universityName
        ]);
    } else {
        echo jsonResponse(false, null, "Failed to join university");
    }
} catch (Exception $e) {
    echo jsonResponse(false, null, "Error: " . $e->getMessage());
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>