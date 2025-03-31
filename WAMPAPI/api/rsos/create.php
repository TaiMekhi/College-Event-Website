<?php
// API endpoint for creating a new RSO
// Path: /api/rsos/create.php

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
    !isset($_POST['name']) || empty($_POST['name']) ||
    !isset($_POST['description']) || empty($_POST['description'])) {
    
    echo jsonResponse(false, null, "Name, description, and user ID are required");
    exit();
}

// Get and sanitize input
$user_id = sanitizeInput($_POST['user_id']);
$name = sanitizeInput($_POST['name']);
$description = sanitizeInput($_POST['description']);

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
        
        // Start transaction
        $conn->begin_transaction();
        
        // Check if RSO name already exists
        $checkQuery = "SELECT rso_id FROM rso WHERE name = ?";
        
        $stmt = $conn->prepare($checkQuery);
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo jsonResponse(false, null, "An RSO with this name already exists");
            exit();
        }
        
        // Insert new RSO
        $insertQuery = "INSERT INTO rso (university_id, name, description, created_by) 
                       VALUES (?, ?, ?, ?)";
        
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("issi", $university_id, $name, $description, $user_id);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            $rso_id = $stmt->insert_id;
            
            // The creator is automatically added as admin by the trigger
            
            // Check how many members are needed for activation
            $memberQuery = "SELECT COUNT(*) as member_count FROM rso_members WHERE rso_id = ?";
            
            $stmt = $conn->prepare($memberQuery);
            $stmt->bind_param("i", $rso_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $countData = $result->fetch_assoc();
            
            $memberCount = $countData['member_count'];
            $membersNeeded = 5 - $memberCount;
            
            // Check if RSO is active
            $activeQuery = "SELECT is_active FROM rso WHERE rso_id = ?";
            
            $stmt = $conn->prepare($activeQuery);
            $stmt->bind_param("i", $rso_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $activeData = $result->fetch_assoc();
            
            $isActive = $activeData['is_active'] == 1;
            
            // Commit transaction
            $conn->commit();
            
            echo jsonResponse(true, [
                'message' => 'RSO created successfully',
                'rso_id' => $rso_id,
                'is_active' => $isActive,
                'members_needed' => $membersNeeded
            ]);
        } else {
            $conn->rollback();
            echo jsonResponse(false, null, "Failed to create RSO");
        }
    } else {
        echo jsonResponse(false, null, "User not found");
    }
} catch (Exception $e) {
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }
    echo jsonResponse(false, null, "Error: " . $e->getMessage());
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>