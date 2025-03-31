<?php
// API endpoint for fetching RSO events
// Path: /api/events/rso.php

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

try {
    // Create database connection
    $conn = getConnection();
    
    // Get RSOs that the user is a member of
    $rsoQuery = "SELECT r.rso_id, r.name 
                 FROM rso r
                 JOIN rso_members rm ON r.rso_id = rm.rso_id
                 WHERE rm.user_id = ? AND r.is_active = 1";
    
    $stmt = $conn->prepare($rsoQuery);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $rsos = resultToArray($result);
    
    if (count($rsos) > 0) {
        // Get a comma-separated list of RSO IDs
        $rsoIds = array_column($rsos, 'rso_id');
        $rsoIdsList = implode(',', $rsoIds);
        
        // Get events for these RSOs with average ratings
        $eventsQuery = "SELECT e.*, pe.rso_id,
                      (SELECT AVG(r.rating_value) FROM ratings r WHERE r.event_id = e.event_id) as average_rating,
                      rso.name as rso_name
                      FROM events e
                      JOIN private_events pe ON e.event_id = pe.event_id
                      JOIN rso ON pe.rso_id = rso.rso_id
                      WHERE pe.rso_id IN ($rsoIdsList)
                      ORDER BY e.date DESC, e.time ASC";
        
        $result = $conn->query($eventsQuery);
        $events = resultToArray($result);
        
        echo jsonResponse(true, [
            'rsos' => $rsos,
            'events' => $events
        ]);
    } else {
        echo jsonResponse(true, [
            'rsos' => [],
            'events' => []
        ]);
    }
} catch (Exception $e) {
    echo jsonResponse(false, null, "Error: " . $e->getMessage());
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>