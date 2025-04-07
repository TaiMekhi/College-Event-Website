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
        
        // Check if this is a private event
        $isPrivate = false;
        $canAccess = true;
        
        $privateQuery = "SELECT * FROM private_events WHERE event_id = ?";
        
        $stmt = $conn->prepare($privateQuery);
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        $privateResult = $stmt->get_result();
        
        if ($privateResult->num_rows > 0) {
            $isPrivate = true;
            
            // Check if user has permission to view this private event
            // This requires checking if user is from the same university
            if ($user_id && isset($event['university_id'])) {
                $userUniversityQuery = "SELECT university_id FROM users WHERE user_id = ?";
                $stmt = $conn->prepare($userUniversityQuery);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $userUniversityResult = $stmt->get_result();
                
                if ($userUniversityResult->num_rows > 0) {
                    $userUniversity = $userUniversityResult->fetch_assoc();
                    
                    // If user is not from the same university
                    if ($userUniversity['university_id'] != $event['university_id']) {
                        $canAccess = false;
                    }
                } else {
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
        
        // Check if user is a superadmin
        $isSuperAdmin = isset($_SESSION['userRole']) && $_SESSION['userRole'] === 'superadmin';
        
        // If event is private and user doesn't have access, or if it's a public event that's not approved
        // But always allow superadmins to view events
        if (($isPrivate && !$canAccess && !$isSuperAdmin) || ($isPublic && !$isApproved && $user_id != $event['admin_id'] && !$isSuperAdmin)) {
            echo jsonResponse(false, null, "You do not have permission to view this event");
            exit();
        }
        
        // Get RSO details if applicable
        if (isset($event['rso_id']) && $event['rso_id']) {
            $rsoQuery = "SELECT name FROM rso WHERE rso_id = ?";
            $stmt = $conn->prepare($rsoQuery);
            $stmt->bind_param("i", $event['rso_id']);
            $stmt->execute();
            $rsoResult = $stmt->get_result();
            
            if ($rsoResult->num_rows > 0) {
                $rso = $rsoResult->fetch_assoc();
                $event['rso_name'] = $rso['name'];
            }
        }
        
        // Ensure room_number is properly included in the response
        if (!isset($event['room_number'])) {
            $event['room_number'] = '';
        }
        
        // Add a formatted location that includes room number if available
        $formattedLocation = $event['location_name'];
        if (!empty($event['room_number'])) {
            $formattedLocation .= ", Room " . $event['room_number'];
        }
        $event['formatted_location'] = $formattedLocation;
        
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