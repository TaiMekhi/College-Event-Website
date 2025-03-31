<?php
// API endpoint for fetching user details
// Path: /api/users/details.php

// Set headers for JSON response
header('Content-Type: application/json');

// Start session if needed
session_start();

// Database connection
require_once '../../dbh.inc.php';

// Function to sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Check if user ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'error_message' => 'User ID is required'
    ]);
    exit();
}

// Get and sanitize input
$user_id = sanitize_input($_GET['id']);

// Check if user is authorized to view this information
// For now, we'll allow it if the requested user_id matches the session user_id
// In a more secure implementation, you might want additional checks
if (!isset($_SESSION['userID']) || $_SESSION['userID'] != $user_id) {
    // You could implement different permissions here based on user roles
    // For now, we're just checking if the logged-in user is requesting their own info
    
    // Optional: Allow admin or superadmin to view any user details
    if (!isset($_SESSION['userRole']) || ($_SESSION['userRole'] != 'admin' && $_SESSION['userRole'] != 'superadmin')) {
        echo json_encode([
            'success' => false,
            'error_message' => 'Unauthorized access'
        ]);
        exit();
    }
}

try {
    // Create database connection
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Prepare SQL statement
    $stmt = $conn->prepare("SELECT user_id, user_name, first_name, last_name, user_level, university_id 
                            FROM users 
                            WHERE user_id = :user_id");
    
    // Bind parameters
    $stmt->bindParam(':user_id', $user_id);
    
    // Execute query
    $stmt->execute();
    
    // Check if user exists
    if ($stmt->rowCount() > 0) {
        // Fetch user data
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get university name if user has a university
        $university_name = null;
        if (!empty($user['university_id'])) {
            $stmt_uni = $conn->prepare("SELECT name FROM university WHERE university_id = :university_id");
            $stmt_uni->bindParam(':university_id', $user['university_id']);
            $stmt_uni->execute();
            
            if ($stmt_uni->rowCount() > 0) {
                $university = $stmt_uni->fetch(PDO::FETCH_ASSOC);
                $university_name = $university['name'];
            }
        }
        
        // Add university name to user data
        $user['university_name'] = $university_name;
        
        // Remove sensitive information
        unset($user['user_password']);
        
        // Return success with user data
        echo json_encode([
            'success' => true,
            'user' => $user
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error_message' => 'User not found'
        ]);
    }
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'error_message' => 'Database error: ' . $e->getMessage()
    ]);
}

// Close connection
$conn = null;
?>