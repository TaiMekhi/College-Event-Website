<?php
// API endpoint for fetching event comments
// Path: /api/comments/event.php

// Set headers for JSON response
header('Content-Type: application/json');

// Include database configuration file
require_once '../../dbh.inc.php';

// Check if event ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo jsonResponse(false, null, "Event ID is required");
    exit();
}

// Get and sanitize input
$event_id = sanitizeInput($_GET['id']);

try {
    // Create database connection
    $conn = getConnection();
    
    // First verify event exists
    $eventCheckQuery = "SELECT event_id FROM events WHERE event_id = ?";
    $stmt = $conn->prepare($eventCheckQuery);
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $eventResult = $stmt->get_result();
    
    if ($eventResult->num_rows == 0) {
        echo jsonResponse(false, null, "Event not found");
        exit();
    }
    
    // Query to get comments for this event along with user information
    $query = "SELECT c.*, u.first_name, u.last_name, u.user_name
              FROM comments c
              JOIN users u ON c.user_id = u.user_id
              WHERE c.event_id = ?
              ORDER BY c.timestamp DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $comments = resultToArray($result);
    
    echo jsonResponse(true, ['comments' => $comments]);
} catch (Exception $e) {
    echo jsonResponse(false, null, "Error: " . $e->getMessage());
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>