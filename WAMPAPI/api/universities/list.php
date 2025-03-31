<?php
// API endpoint for listing universities
// Path: /api/universities/list.php

// Set headers for JSON response
header('Content-Type: application/json');

// Include database configuration file
require_once '../../dbh.inc.php';

try {
    // Create database connection
    $conn = getConnection();
    
    // Get all universities
    $query = "SELECT university_id, name, location, description, num_students, email_domain 
              FROM university 
              ORDER BY name";
    
    $result = $conn->query($query);
    
    $universities = resultToArray($result);
    
    echo jsonResponse(true, ['universities' => $universities]);
} catch (Exception $e) {
    echo jsonResponse(false, null, "Error: " . $e->getMessage());
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>