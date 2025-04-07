<?php
// Turn off error reporting for production
error_reporting(0);
ini_set('display_errors', 0);

// Start session
session_start();

// Set headers for JSON response
header('Content-Type: application/json');

// Initialize response
$response = [
    'success' => false,
    'message' => 'No action specified'
];

// Check if user is logged in
if (!isset($_SESSION['userID'])) {
    $response['message'] = 'User not logged in';
    echo json_encode($response);
    exit();
}

// Get JSON data from request
$request_body = file_get_contents('php://input');
$data = json_decode($request_body, true);

// Update user role in session if requested
if (isset($data['update_role']) && $data['update_role']) {
    try {
        // Get user ID from session
        $userID = $_SESSION['userID'];
        
        // Database connection details - adjust these to match your setup
        $db_host = "localhost"; // or your database host
        $db_user = "root";      // or your database username
        $db_pass = "";          // or your database password
        $db_name = "college_event_website";   // or your database name - ADJUST THIS
        
        // Connect to database
        $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
        
        // Check connection
        if ($conn->connect_error) {
            throw new Exception('Database connection failed: ' . $conn->connect_error);
        }
        
        // Use the correct column name "user_level"
        $roleColumn = "user_level";
        
        // Get user's current role from database using the correct column name
        $sql = "SELECT " . $roleColumn . " FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('Prepare statement failed: ' . $conn->error);
        }
        
        $stmt->bind_param("i", $userID);
        if (!$stmt->execute()) {
            throw new Exception('Execute failed: ' . $stmt->error);
        }
        
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Update session with current role from database
            $_SESSION['userRole'] = $user[$roleColumn];
            
            $response['success'] = true;
            $response['message'] = 'Session updated successfully';
            $response['role'] = $user[$roleColumn];
        } else {
            $response['message'] = 'User not found';
        }
        
        // Close database connection
        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        $response['message'] = 'Error: ' . $e->getMessage();
    }
} else {
    // If no update_role parameter, just return current session info
    $response['success'] = true;
    $response['message'] = 'Session info retrieved';
    $response['current_role'] = $_SESSION['userRole'] ?? 'none';
}

// Return response
echo json_encode($response);