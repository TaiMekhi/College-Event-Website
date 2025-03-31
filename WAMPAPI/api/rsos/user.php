<?php
// API endpoint for fetching user's RSOs
// Path: /api/rsos/user.php

// Set headers for JSON response
header('Content-Type: application/json');

// Start session if needed
session_start();

// Include database configuration file
require_once '../../dbh.inc.php';

// Check if user_id is provided
if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    echo jsonResponse(false, null, "User ID is required");
    exit();
}

// Get and sanitize input
$user_id = sanitizeInput($_GET['user_id']);

// Verify user is authorized
if (!isset($_SESSION['userID']) || $_SESSION['userID'] != $user_id) {
    echo jsonResponse(false, null, "Unauthorized user");
    exit();
}

try {
    // Create database connection
    $conn = getConnection();
    
    // Get RSOs that the user is a member of
    $query = "SELECT r.rso_id, r.name, r.description, r.is_active, 
             rm.role, 
             (SELECT COUNT(*) FROM rso_members WHERE rso_id = r.rso_id) as member_count,
             CASE 
                WHEN r.is_active = 1 THEN 'Active' 
                ELSE 'Inactive' 
             END as status
             FROM rso r
             JOIN rso_members rm ON r.rso_id = rm.rso_id
             WHERE rm.user_id = ?
             ORDER BY r.name";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $rsos = resultToArray($result);
    
    echo jsonResponse(true, ['rsos' => $rsos]);
} catch (Exception $e) {
    echo jsonResponse(false, null, "Error: " . $e->getMessage());
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>