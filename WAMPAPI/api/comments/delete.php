<?php
// API endpoint for deleting event comments
// Path: /api/comments/delete.php

// Set headers for JSON response
header('Content-Type: application/json');

// Start session if needed
session_start();

// Include database configuration file
require_once '../../dbh.inc.php';

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo jsonResponse(false, null, "Invalid request method");
    exit();
}

// Check if comment ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo jsonResponse(false, null, "Comment ID is required");
    exit();
}

// Get and sanitize input
$comment_id = sanitizeInput($_GET['id']);
$user_id = isset($_SESSION['userID']) ? $_SESSION['userID'] : null;

// Verify user is logged in
if (!$user_id) {
    echo jsonResponse(false, null, "User must be logged in");
    exit();
}

try {
    // Create database connection
    $conn = getConnection();
    
    // Check if the comment exists and belongs to the user
    $checkQuery = "SELECT c.* FROM comments c
                  WHERE c.comment_id = ? AND c.user_id = ?";
    
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("ii", $comment_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        // Check if user is an admin or superadmin
        if (isset($_SESSION['userRole']) && 
            ($_SESSION['userRole'] == 'admin' || $_SESSION['userRole'] == 'superadmin')) {
            
            // Admins can delete any comment, so check if the comment exists
            $adminCheckQuery = "SELECT c.* FROM comments c WHERE c.comment_id = ?";
            
            $stmt = $conn->prepare($adminCheckQuery);
            $stmt->bind_param("i", $comment_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows == 0) {
                echo jsonResponse(false, null, "Comment not found");
                exit();
            }
        } else {
            echo jsonResponse(false, null, "Comment not found or you don't have permission to delete it");
            exit();
        }
    }
    
    // Delete the comment
    $deleteQuery = "DELETE FROM comments WHERE comment_id = ?";
    
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("i", $comment_id);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        echo jsonResponse(true, ['message' => 'Comment deleted successfully']);
    } else {
        echo jsonResponse(false, null, "Failed to delete comment");
    }
} catch (Exception $e) {
    echo jsonResponse(false, null, "Error: " . $e->getMessage());
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>