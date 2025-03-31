<?php
// API endpoint for adding event comments
// Path: /api/comments/add.php

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
if (!isset($_POST['event_id']) || empty($_POST['event_id']) ||
    !isset($_POST['user_id']) || empty($_POST['user_id']) ||
    !isset($_POST['comment']) || empty($_POST['comment'])) {
    
    echo jsonResponse(false, null, "All fields are required");
    exit();
}

// Get and sanitize input
$event_id = sanitizeInput($_POST['event_id']);
$user_id = sanitizeInput($_POST['user_id']);
$comment = sanitizeInput($_POST['comment']);

// Verify user is authorized
if (!isset($_SESSION['userID']) || $_SESSION['userID'] != $user_id) {
    echo jsonResponse(false, null, "Unauthorized user");
    exit();
}

try {
    // Create database connection
    $conn = getConnection();
    
    // Check if event exists and user has access
    $eventQuery = "SELECT e.event_id 
                  FROM events e
                  LEFT JOIN private_events pe ON e.event_id = pe.event_id
                  LEFT JOIN rso_members rm ON pe.rso_id = rm.rso_id AND rm.user_id = ?
                  LEFT JOIN public_events pub ON e.event_id = pub.event_id
                  WHERE e.event_id = ? 
                  AND (pe.event_id IS NULL OR rm.user_id IS NOT NULL OR pub.approved = 1)";
    
    $stmt = $conn->prepare($eventQuery);
    $stmt->bind_param("ii", $user_id, $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        echo jsonResponse(false, null, "Event not found or access denied");
        exit();
    }
    
    // Insert comment
    $insertQuery = "INSERT INTO comments (event_id, user_id, comment) 
                   VALUES (?, ?, ?)";
    
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param("iis", $event_id, $user_id, $comment);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        $comment_id = $stmt->insert_id;
        
        // Get the newly created comment with user info
        $newCommentQuery = "SELECT c.*, u.first_name, u.last_name 
                           FROM comments c
                           JOIN users u ON c.user_id = u.user_id
                           WHERE c.comment_id = ?";
        
        $stmt = $conn->prepare($newCommentQuery);
        $stmt->bind_param("i", $comment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $commentData = $result->fetch_assoc();
            
            echo jsonResponse(true, ['comment' => $commentData]);
        } else {
            echo jsonResponse(true, ['comment_id' => $comment_id]);
        }
    } else {
        echo jsonResponse(false, null, "Failed to add comment");
    }
} catch (Exception $e) {
    echo jsonResponse(false, null, "Error: " . $e->getMessage());
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>