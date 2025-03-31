<?php
// API endpoint for fetching event details
// Path: /api/events/details.php

// Set headers for JSON response
header('Content-Type: application/json');

// Start session if needed
session_start();

// Include database configuration file
require_once '../../dbh.inc.php';

// Check if event ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo jsonResponse(false, null, "Event ID is required");
    exit();
}

// Get and sanitize input
$event_id = sanitizeInput($_GET['id']);
$user_id = isset($_SESSION['userID']) ? $_SESSION['userID'] : null;

try {
    // Create database connection
    $conn = getConnection();
    
    // Query to get event details
    $query = "SELECT e.*, 
              (SELECT AVG(r.rating_value) FROM ratings r WHERE r.event_id = e.event_id) as average_rating
              FROM events e
              WHERE e.event_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $event = $result->fetch_assoc();
        
        // Check if this is a private event and if the user has access
        $isPrivate = false;
        $canAccess = true;
        
        $privateQuery = "SELECT pe.*, rso.name as rso_name 
                       FROM private_events pe
                       JOIN rso ON pe.rso_id = rso.rso_id
                       WHERE pe.event_id = ?";
        
        $stmt = $conn->prepare($privateQuery);
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        $privateResult = $stmt->get_result();
        
        if ($privateResult->num_rows > 0) {
            $privateEvent = $privateResult->fetch_assoc();
            $isPrivate = true;
            $event['rso_id'] = $privateEvent['rso_id'];
            $event['rso_name'] = $privateEvent['rso_name'];
            
            // Check if user is a member of this RSO
            if ($user_id) {
                $memberQuery = "SELECT * FROM rso_members 
                              WHERE rso_id = ? AND user_id = ?";
                
                $stmt = $conn->prepare($memberQuery);
                $stmt->bind_param("ii", $privateEvent['rso_id'], $user_id);
                $stmt->execute();
                $memberResult = $stmt->get_result();
                
                if ($memberResult->num_rows == 0) {
                    $canAccess = false;
                }
            } else {
                $canAccess = false;
            }
        }
        
        // Check if this is a public event and if it's approved
        $isPublic = false;
        $isApproved = true;
        
        $publicQuery = "SELECT * FROM public_events WHERE event_id = ?";
        
        $stmt = $conn->prepare($publicQuery);
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        $publicResult = $stmt->get_result();
        
        if ($publicResult->num_rows > 0) {
            $publicEvent = $publicResult->fetch_assoc();
            $isPublic = true;
            
            if ($publicEvent['approved'] == 0) {
                $isApproved = false;
            }
        }
        
        // Get user's rating for this event if available
        $userRating = null;
        if ($user_id) {
            $ratingQuery = "SELECT rating_value FROM ratings 
                          WHERE event_id = ? AND user_id = ?";
            
            $stmt = $conn->prepare($ratingQuery);
            $stmt->bind_param("ii", $event_id, $user_id);
            $stmt->execute();
            $ratingResult = $stmt->get_result();
            
            if ($ratingResult->num_rows > 0) {
                $ratingRow = $ratingResult->fetch_assoc();
                $userRating = $ratingRow['rating_value'];
            }
        }
        
        // If event is private and user doesn't have access, or if it's a public event that's not approved
        if (($isPrivate && !$canAccess) || ($isPublic && !$isApproved)) {
            echo jsonResponse(false, null, "You do not have permission to view this event");
            exit();
        }
        
        // Return event details with user's rating
        echo jsonResponse(true, [
            'event' => $event,
            'user_rating' => $userRating
        ]);
    } else {
        echo jsonResponse(false, null, "Event not found");
    }
} catch (Exception $e) {
    echo jsonResponse(false, null, "Error: " . $e->getMessage());
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>