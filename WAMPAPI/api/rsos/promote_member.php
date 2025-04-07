<?php
// Start session for authentication
session_start();

// Set content type to JSON
header('Content-Type: application/json');

// Include database connection
require_once "../../dbh.inc.php";

// Initialize response
$response = [
    'status' => 'error',
    'message' => 'An unknown error occurred'
];

try {
    // Get PDO connection
    $pdo = getPDOConnection();

    // Check if user is logged in
    if (!isset($_SESSION['userID'])) {
        http_response_code(401);
        $response['message'] = 'Unauthorized access';
        echo json_encode($response);
        exit();
    }

    // Determine input method (POST or JSON)
    $input = [];
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    if (strpos($contentType, 'application/json') !== false) {
        // Read raw JSON input
        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true);
    } else {
        // Use $_POST
        $input = $_POST;
    }

    // Check if required parameters are provided
    if (!isset($input['rso_id']) || !isset($input['user_id'])) {
        http_response_code(400);
        $response['message'] = 'Missing required parameters: rso_id and user_id are needed';
        echo json_encode($response);
        exit();
    }

    // Get parameters
    $currentUserID = $_SESSION['userID'];
    $rsoID = $input['rso_id'];
    $targetUserID = $input['user_id'];

    // Begin transaction
    $pdo->beginTransaction();

    // First, check if the current user is an admin of this RSO
    $adminCheck = "SELECT COUNT(*) FROM rso_members 
                   WHERE rso_id = :rso_id AND user_id = :user_id AND role = 'admin'";
    $adminStmt = $pdo->prepare($adminCheck);
    $adminStmt->bindParam(':rso_id', $rsoID, PDO::PARAM_INT);
    $adminStmt->bindParam(':user_id', $currentUserID, PDO::PARAM_INT);
    $adminStmt->execute();

    if ($adminStmt->fetchColumn() == 0) {
        // Current user is not an admin of this RSO
        $pdo->rollBack();
        http_response_code(403);
        $response['message'] = 'You are not authorized to promote members in this RSO';
        echo json_encode($response);
        exit();
    }

    // Check if target user is a member of this RSO
    $memberCheck = "SELECT role FROM rso_members 
                    WHERE rso_id = :rso_id AND user_id = :user_id";
    $memberStmt = $pdo->prepare($memberCheck);
    $memberStmt->bindParam(':rso_id', $rsoID, PDO::PARAM_INT);
    $memberStmt->bindParam(':user_id', $targetUserID, PDO::PARAM_INT);
    $memberStmt->execute();

    $currentRole = $memberStmt->fetchColumn();

    if (!$currentRole) {
        // Target user is not a member of this RSO
        $pdo->rollBack();
        http_response_code(404);
        $response['message'] = 'The selected user is not a member of this RSO';
        echo json_encode($response);
        exit();
    }

    // Check if user is already an admin
    if ($currentRole === 'admin') {
        $pdo->rollBack();
        http_response_code(400);
        $response['message'] = 'User is already an admin of this RSO';
        echo json_encode($response);
        exit();
    }

    // Update the target user's role to admin in rso_members
    $updateRsoQuery = "UPDATE rso_members 
                    SET role = 'admin' 
                    WHERE rso_id = :rso_id AND user_id = :user_id";
    $updateRsoStmt = $pdo->prepare($updateRsoQuery);
    $updateRsoStmt->bindParam(':rso_id', $rsoID, PDO::PARAM_INT);
    $updateRsoStmt->bindParam(':user_id', $targetUserID, PDO::PARAM_INT);
    $updateRsoStmt->execute();

    // Update the user's level in users table
    $updateUserQuery = "UPDATE users 
                        SET user_level = 'admin' 
                        WHERE user_id = :user_id";
    $updateUserStmt = $pdo->prepare($updateUserQuery);
    $updateUserStmt->bindParam(':user_id', $targetUserID, PDO::PARAM_INT);
    $updateUserStmt->execute();

    // Commit the transaction
    $pdo->commit();

    // Return success response
    $response = [
        'status' => 'success', 
        'message' => 'Member promoted to admin successfully'
    ];
    echo json_encode($response);

} catch (PDOException $e) {
    // Rollback the transaction in case of error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log the error
    error_log('Promote member error: ' . $e->getMessage());
    
    // Return error message
    http_response_code(500);
    $response['message'] = 'Database error occurred';
    $response['debug'] = $e->getMessage(); // Remove in production
    echo json_encode($response);
} catch (Exception $e) {
    // Log unexpected errors
    error_log('Unexpected promote member error: ' . $e->getMessage());
    
    // Return unexpected error response
    http_response_code(500);
    $response['message'] = 'An unexpected error occurred';
    $response['debug'] = $e->getMessage(); // Remove in production
    echo json_encode($response);
}