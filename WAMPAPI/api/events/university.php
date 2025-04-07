<?php
// API endpoint for fetching university events
// Path: /api/events/university.php

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
    
    // First, get the user's university
    $userQuery = "SELECT university_id FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($userQuery);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $university_id = $user['university_id'];
        
        // If user has a university
        if (!empty($university_id)) {
            // Check if rso_events table exists
            $tableCheckQuery = "SHOW TABLES LIKE 'rso_events'";
            $tableCheckResult = $conn->query($tableCheckQuery);
            $rsoEventsTableExists = $tableCheckResult && $tableCheckResult->num_rows > 0;
            
            // Modified query to filter out:
            // 1. Public events that aren't approved
            // 2. RSO events (which should only be visible through RSO membership)
            
            $eventsQuery = "SELECT e.*, 
                          (SELECT AVG(r.rating_value) FROM ratings r WHERE r.event_id = e.event_id) as average_rating
                          FROM events e
                          WHERE e.university_id = ?
                          AND (
                              -- Include private events for this university
                              e.event_id IN (SELECT event_id FROM private_events)
                              
                              -- Include approved public events only
                              OR e.event_id IN (SELECT event_id FROM public_events WHERE approved = 1)
                          )";
            
            // Add exclusion for RSO events if the table exists
            if ($rsoEventsTableExists) {
                $eventsQuery .= " AND e.event_id NOT IN (SELECT event_id FROM rso_events)";
            }
            
            $eventsQuery .= " ORDER BY e.date DESC, e.time ASC";
            
            $stmt = $conn->prepare($eventsQuery);
            $stmt->bind_param("i", $university_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $events = resultToArray($result);
            
            echo jsonResponse(true, [
                'university_id' => $university_id,
                'events' => $events
            ]);
        } else {
            // User does not have a university
            echo jsonResponse(true, [
                'university_id' => null,
                'events' => []
            ]);
        }
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