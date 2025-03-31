<?php
// API endpoint for user logout
// Path: /api/auth/logout.php

// Set headers for JSON response
header('Content-Type: application/json');

// Start session
session_start();

// Destroy the session
session_destroy();

// Return success message
echo json_encode([
    'success' => true,
    'message' => 'You have been successfully logged out'
]);
?>