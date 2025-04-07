<?php
// API endpoint for editing event comments
// Path: /api/comments/edit.php

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

// Check if required fields are provided
if (!isset($_POST['comment_id']) || empty($_POST['comment_id']) ||
    !isset($_POST['comment']) || empty($_POST['comment'])) {
    
    echo jsonResponse(false, null, "All fields are required");
    exit();
}

// Get and sanitize input
$comment_id = sanitizeInput($_POST['comment_id']);
$comment = sanitizeInput($_POST['comment']);
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
    $checkQuery = "SELECT c.*, e.event_id FROM comments c
                  JOIN events e ON c.event_id = e.event_id
                  WHERE c.comment_id = ? AND c.user_id = ?";
    
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("ii", $comment_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        // Check if user is an admin or superadmin
        if (isset($_SESSION['userRole']) && 
            ($_SESSION['userRole'] == 'admin' || $_SESSION['userRole'] == 'superadmin')) {
            
            // Admins can edit any comment, so check if the comment exists
            $adminCheckQuery = "SELECT c.*, e.event_id FROM comments c
                               JOIN events e ON c.event_id = e.event_id
                               WHERE c.comment_id = ?";
            
            $stmt = $conn->prepare($adminCheckQuery);
            $stmt->bind_param("i", $comment_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows == 0) {
                echo jsonResponse(false, null, "Comment not found");
                exit();
            }
        } else {
            echo jsonResponse(false, null, "Comment not found or you don't have permission to edit it");
            exit();
        }
    }
    
    // Get the event_id from the result for returning in the response
    $commentData = $result->fetch_assoc();
    $event_id = $commentData['event_id'];
    
    // Update the comment (without tracking edit status since we don't have those columns)
    $updateQuery = "UPDATE comments SET comment = ? WHERE comment_id = ?";
    
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("si", $comment, $comment_id);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0 || $stmt->affected_rows === 0) {
        // Get the updated comment with user info
        $updatedCommentQuery = "SELECT c.*, u.first_name, u.last_name, u.user_name
                               FROM comments c
                               JOIN users u ON c.user_id = u.user_id
                               WHERE c.comment_id = ?";
        
        $stmt = $conn->prepare($updatedCommentQuery);
        $stmt->bind_param("i", $comment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $updatedData = $result->fetch_assoc();
            
            echo jsonResponse(true, [
                'comment' => $updatedData,
                'event_id' => $event_id
            ]);
        } else {
            echo jsonResponse(true, [
                'message' => 'Comment updated successfully',
                'event_id' => $event_id
            ]);
        }
    } else {
        echo jsonResponse(false, null, "Failed to update comment");
    }
} catch (Exception $e) {
    echo jsonResponse(false, null, "Error: " . $e->getMessage());
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>