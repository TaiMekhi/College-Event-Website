<?php
// API endpoint for adding or updating event ratings
// Path: /api/ratings/add.php

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
    !isset($_POST['rating']) || $_POST['rating'] === '') {
    
    echo jsonResponse(false, null, "All fields are required");
    exit();
}

// Get and sanitize input
$event_id = sanitizeInput($_POST['event_id']);
$user_id = sanitizeInput($_POST['user_id']);
$rating = sanitizeInput($_POST['rating']);

// Verify user is authorized
if (!isset($_SESSION['userID']) || $_SESSION['userID'] != $user_id) {
    echo jsonResponse(false, null, "Unauthorized user");
    exit();
}

// Verify rating is between 1 and 5
if ($rating < 1 || $rating > 5) {
    echo jsonResponse(false, null, "Rating must be between 1 and 5");
    exit();
}

try {
    // Create database connection
    $conn = getConnection();
    
    // First, check if the event exists
    $eventExistsQuery = "SELECT event_id, university_id FROM events WHERE event_id = ?";
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
                echo jsonResponse(false, null, "You can only rate events from your university");
                exit();
            }
        } else {
            echo jsonResponse(false, null, "You must be associated with a university to rate this event");
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
        
        // If event is not approved and user is not the admin
        if (!$isApproved && $eventData['admin_id'] != $user_id) {
            echo jsonResponse(false, null, "This event has not been approved yet");
            exit();
        }
    }
    
    // Check if user has already rated this event
    $checkQuery = "SELECT rating_id, rating_value FROM ratings 
                  WHERE event_id = ? AND user_id = ?";
    
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("ii", $event_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // User has already rated, update the rating
        $ratingData = $result->fetch_assoc();
        $rating_id = $ratingData['rating_id'];
        
        $updateQuery = "UPDATE ratings SET rating_value = ? WHERE rating_id = ?";
        
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("ii", $rating, $rating_id);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0 || $stmt->affected_rows === 0) {
            // Get updated average rating
            $avgQuery = "SELECT AVG(rating_value) as average_rating FROM ratings WHERE event_id = ?";
            
            $stmt = $conn->prepare($avgQuery);
            $stmt->bind_param("i", $event_id);
            $stmt->execute();
            $avgResult = $stmt->get_result();
            
            if ($avgResult->num_rows > 0) {
                $avgData = $avgResult->fetch_assoc();
                $average_rating = $avgData['average_rating'];
                
                echo jsonResponse(true, [
                    'message' => 'Rating updated successfully',
                    'rating' => $rating,
                    'average_rating' => round($average_rating, 1)
                ]);
            } else {
                echo jsonResponse(true, [
                    'message' => 'Rating updated successfully',
                    'rating' => $rating
                ]);
            }
        } else {
            echo jsonResponse(false, null, "Failed to update rating");
        }
    } else {
        // Insert new rating
        $insertQuery = "INSERT INTO ratings (event_id, user_id, rating_value) 
                       VALUES (?, ?, ?)";
        
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("iii", $event_id, $user_id, $rating);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            // Get updated average rating
            $avgQuery = "SELECT AVG(rating_value) as average_rating FROM ratings WHERE event_id = ?";
            
            $stmt = $conn->prepare($avgQuery);
            $stmt->bind_param("i", $event_id);
            $stmt->execute();
            $avgResult = $stmt->get_result();
            
            if ($avgResult->num_rows > 0) {
                $avgData = $avgResult->fetch_assoc();
                $average_rating = $avgData['average_rating'];
                
                echo jsonResponse(true, [
                    'message' => 'Rating added successfully',
                    'rating' => $rating,
                    'average_rating' => round($average_rating, 1)
                ]);
            } else {
                echo jsonResponse(true, [
                    'message' => 'Rating added successfully',
                    'rating' => $rating
                ]);
            }
        } else {
            echo jsonResponse(false, null, "Failed to add rating");
        }
    }
} catch (Exception $e) {
    echo jsonResponse(false, null, "Error: " . $e->getMessage());
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>