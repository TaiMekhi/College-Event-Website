<?php
// API endpoint for fetching public events
// Path: /api/events/public.php

// Set headers for JSON response
header('Content-Type: application/json');

// Include database configuration file
require_once '../../dbh.inc.php';

try {
    // Create database connection
    $conn = getConnection();
    
    // Query to get public events with average ratings
    $query = "SELECT e.*, pe.approved, 
              (SELECT AVG(r.rating_value) FROM ratings r WHERE r.event_id = e.event_id) as average_rating
              FROM events e
              JOIN public_events pe ON e.event_id = pe.event_id
              WHERE pe.approved = 1
              ORDER BY e.date DESC, e.time ASC";
    
    $result = $conn->query($query);
    
    if ($result) {
        $events = resultToArray($result);
        
        echo jsonResponse(true, ['events' => $events]);
    } else {
        echo jsonResponse(false, null, "Failed to retrieve public events");
    }
} catch (Exception $e) {
    echo jsonResponse(false, null, "Error: " . $e->getMessage());
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>