<?php
// API endpoint for joining an RSO (direct join without request)
// Path: /api/rsos/join.php

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
    !isset($_POST['rso_id']) || empty($_POST['rso_id'])) {
    
    echo jsonResponse(false, null, "User ID and RSO ID are required");
    exit();
}

// Get and sanitize input
$user_id = sanitizeInput($_POST['user_id']);
$rso_id = sanitizeInput($_POST['rso_id']);

// Verify user is authorized
if (!isset($_SESSION['userID']) || $_SESSION['userID'] != $user_id) {
    echo jsonResponse(false, null, "Unauthorized user");
    exit();
}

try {
    // Create database connection
    $conn = getConnection();
    
    // Check if the user is already a member of this RSO
    $memberQuery = "SELECT 1 FROM rso_members 
                   WHERE user_id = ? AND rso_id = ?";
    
    $stmt = $conn->prepare($memberQuery);
    $stmt->bind_param("ii", $user_id, $rso_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo jsonResponse(false, null, "You are already a member of this RSO");
        exit();
    }
    
    // Add user as member directly
    $memberInsertQuery = "INSERT INTO rso_members (rso_id, user_id, role) 
                         VALUES (?, ?, 'member')";
    
    $stmt = $conn->prepare($memberInsertQuery);
    $stmt->bind_param("ii", $rso_id, $user_id);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        // Check if RSO is now active
        $rsoQuery = "SELECT is_active, 
                      (SELECT COUNT(*) FROM rso_members WHERE rso_id = ?) as member_count 
                     FROM rso WHERE rso_id = ?";
        
        $stmt = $conn->prepare($rsoQuery);
        $stmt->bind_param("ii", $rso_id, $rso_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $rsoData = $result->fetch_assoc();
        
        $isActive = $rsoData['is_active'] == 1;
        $memberCount = $rsoData['member_count'];
        
        echo jsonResponse(true, [
            'message' => 'Successfully joined RSO',
            'is_active' => $isActive,
            'member_count' => $memberCount
        ]);
    } else {
        echo jsonResponse(false, null, "Failed to join RSO");
    }
} catch (Exception $e) {
    echo jsonResponse(false, null, "Error: " . $e->getMessage());
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>