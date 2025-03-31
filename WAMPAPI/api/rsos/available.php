<?php
// API endpoint for fetching available RSOs to join
// Path: /api/rsos/available.php

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
    
    // Get user's university
    $userQuery = "SELECT university_id FROM users WHERE user_id = ?";
    
    $stmt = $conn->prepare($userQuery);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $userData = $result->fetch_assoc();
        $university_id = $userData['university_id'];
        
        if (!$university_id) {
            echo jsonResponse(false, null, "User is not associated with any university");
            exit();
        }
        
        // Get RSOs at the user's university that they are not a member of
        $query = "SELECT r.rso_id, r.name, r.description, r.is_active, 
                (SELECT COUNT(*) FROM rso_members WHERE rso_id = r.rso_id) as member_count,
                CASE 
                    WHEN r.is_active = 1 THEN 'Active' 
                    ELSE 'Inactive' 
                END as status
                FROM rso r
                WHERE r.university_id = ?
                AND r.rso_id NOT IN (
                    SELECT rso_id FROM rso_members WHERE user_id = ?
                )
                ORDER BY r.name";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $university_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $rsos = resultToArray($result);
        
        echo jsonResponse(true, ['rsos' => $rsos]);
    } else {
        echo jsonResponse(false, null, "User not found");
    }
} catch (Exception $e) {
    echo jsonResponse(false, null, "Error: " . $e->getMessage());
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>