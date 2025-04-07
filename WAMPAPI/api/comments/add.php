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
    
    // First, check if the event exists
    $eventExistsQuery = "SELECT e.event_id, e.university_id FROM events e WHERE e.event_id = ?";
    $stmt = $conn->prepare($eventExistsQuery);
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $eventResult = $stmt->get_result();
    
    if ($eventResult->num_rows == 0) {
        echo jsonResponse(false, null, "Event not found");
        exit();
    }
    
    $eventData = $eventResult->fetch_assoc();
    
    // Check if this is a private university event
    $isPrivateEvent = false;
    $privateQuery = "SELECT event_id FROM private_events WHERE event_id = ?";
    $stmt = $conn->prepare($privateQuery);
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $privateResult = $stmt->get_result();
    
    if ($privateResult->num_rows > 0) {
        $isPrivateEvent = true;
        
        // Check if user is from the same university
        $userUniversityQuery = "SELECT university_id FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($userUniversityQuery);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $userUnivResult = $stmt->get_result();
        
        if ($userUnivResult->num_rows > 0) {
            $userUnivData = $userUnivResult->fetch_assoc();
            
            // If user is not from the same university as the event
            if ($userUnivData['university_id'] != $eventData['university_id']) {
                echo jsonResponse(false, null, "You can only comment on events from your university");
                exit();
            }
        } else {
            echo jsonResponse(false, null, "You must be associated with a university to comment on this event");
            exit();
        }
    }
    
    // Check if this is a public event that's approved
    $isPublicEvent = false;
    $isApproved = false;
    
    $publicQuery = "SELECT approved FROM public_events WHERE event_id = ?";
    $stmt = $conn->prepare($publicQuery);
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $publicResult = $stmt->get_result();
    
    if ($publicResult->num_rows > 0) {
        $isPublicEvent = true;
        $publicData = $publicResult->fetch_assoc();
        $isApproved = ($publicData['approved'] == 1);
        
        // If event is not approved
        if (!$isApproved) {
            echo jsonResponse(false, null, "This event has not been approved yet");
            exit();
        }
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
        $newCommentQuery = "SELECT c.*, u.first_name, u.last_name, u.user_name
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