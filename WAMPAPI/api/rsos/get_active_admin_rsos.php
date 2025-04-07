<?php
// Disable error reporting for production
error_reporting(0);
ini_set('display_errors', 0);

// Set content type to JSON
header('Content-Type: application/json');

// Start session
session_start();

// Initialize response
$response = [
    'status' => 'error',
    'message' => 'An error occurred',
    'data' => []
];

try {
    // Check if user is logged in
    if (!isset($_SESSION['userID'])) {
        $response['message'] = 'User not logged in';
        echo json_encode($response);
        exit();
    }
    
    // Get user ID from session
    $userID = $_SESSION['userID'];
    
    // Connect to database
    $db_host = "localhost"; 
    $db_user = "root";      
    $db_pass = "";          
    $db_name = "college_event_website";  
    
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }
    
    // Use is_active column instead of status
    $stmt = $conn->prepare("
        SELECT r.* 
        FROM rso r
        JOIN rso_members rm ON r.rso_id = rm.rso_id
        WHERE rm.user_id = ? AND rm.role = 'admin' AND r.is_active = 1
    ");
    
    if (!$stmt) {
        throw new Exception('Prepare statement failed: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $userID);
    
    if (!$stmt->execute()) {
        throw new Exception('Execute failed: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $rsos = [];
    
    while ($row = $result->fetch_assoc()) {
        $rsos[] = [
            'id' => $row['rso_id'],
            'name' => $row['name'],
            'description' => $row['description'],
            'is_active' => $row['is_active']
        ];
    }
    
    // Success response
    $response['status'] = 'success';
    $response['message'] = 'Active RSOs retrieved successfully';
    $response['data'] = $rsos;
    
    // Close database connection
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

// Return JSON response
echo json_encode($response);