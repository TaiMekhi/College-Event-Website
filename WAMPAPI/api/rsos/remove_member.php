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
    'message' => 'An unknown error occurred',
    'reload_session' => false
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

    // Check the current role of the target user
    $roleQuery = "SELECT role FROM rso_members 
                  WHERE rso_id = :rso_id AND user_id = :user_id";
    $roleStmt = $pdo->prepare($roleQuery);
    $roleStmt->bindParam(':rso_id', $rsoID, PDO::PARAM_INT);
    $roleStmt->bindParam(':user_id', $targetUserID, PDO::PARAM_INT);
    $roleStmt->execute();
    $targetUserRole = $roleStmt->fetchColumn();

    if (!$targetUserRole) {
        // Target user is not a member of this RSO
        $pdo->rollBack();
        http_response_code(404);
        $response['message'] = 'The selected user is not a member of this RSO';
        echo json_encode($response);
        exit();
    }

    // Check admin count
    $adminCountQuery = "SELECT COUNT(*) FROM rso_members 
                        WHERE rso_id = :rso_id AND role = 'admin'";
    $adminCountStmt = $pdo->prepare($adminCountQuery);
    $adminCountStmt->bindParam(':rso_id', $rsoID, PDO::PARAM_INT);
    $adminCountStmt->execute();
    $adminCount = $adminCountStmt->fetchColumn();

    // Check if the current user is an admin of this RSO
    $currentUserAdminQuery = "SELECT role FROM rso_members 
                              WHERE rso_id = :rso_id AND user_id = :user_id";
    $currentUserAdminStmt = $pdo->prepare($currentUserAdminQuery);
    $currentUserAdminStmt->bindParam(':rso_id', $rsoID, PDO::PARAM_INT);
    $currentUserAdminStmt->bindParam(':user_id', $currentUserID, PDO::PARAM_INT);
    $currentUserAdminStmt->execute();
    $currentUserRole = $currentUserAdminStmt->fetchColumn();

    // Special case: If removing self, check if there's another admin
    if ($targetUserID == $currentUserID) {
        if ($targetUserRole !== 'admin') {
            $pdo->rollBack();
            http_response_code(400);
            $response['message'] = 'You can only remove yourself if you are an admin';
            echo json_encode($response);
            exit();
        }

        if ($adminCount <= 1) {
            $pdo->rollBack();
            http_response_code(400);
            $response['message'] = 'Cannot remove the last admin. Promote another member first.';
            echo json_encode($response);
            exit();
        }
    } else {
        // If removing another user, check if current user has admin privileges
        if ($currentUserRole !== 'admin') {
            $pdo->rollBack();
            http_response_code(403);
            $response['message'] = 'You are not authorized to remove members from this RSO';
            echo json_encode($response);
            exit();
        }

        // If removing an admin, ensure there are other admins
        if ($targetUserRole === 'admin' && $adminCount <= 1) {
            $pdo->rollBack();
            http_response_code(400);
            $response['message'] = 'Cannot remove the last admin. Promote another member first.';
            echo json_encode($response);
            exit();
        }
    }

    // Remove the member
    $deleteQuery = "DELETE FROM rso_members 
                    WHERE rso_id = :rso_id AND user_id = :user_id";
    $deleteStmt = $pdo->prepare($deleteQuery);
    $deleteStmt->bindParam(':rso_id', $rsoID, PDO::PARAM_INT);
    $deleteStmt->bindParam(':user_id', $targetUserID, PDO::PARAM_INT);
    $deleteStmt->execute();

    // Update RSO activity status if member count drops below 5
    $memberCountQuery = "SELECT COUNT(*) FROM rso_members WHERE rso_id = :rso_id";
    $memberCountStmt = $pdo->prepare($memberCountQuery);
    $memberCountStmt->bindParam(':rso_id', $rsoID, PDO::PARAM_INT);
    $memberCountStmt->execute();
    $memberCount = $memberCountStmt->fetchColumn();

    // Potentially update RSO status
    if ($memberCount < 5) {
        $updateStatusQuery = "UPDATE rso SET is_active = 0 WHERE rso_id = :rso_id";
        $updateStatusStmt = $pdo->prepare($updateStatusQuery);
        $updateStatusStmt->bindParam(':rso_id', $rsoID, PDO::PARAM_INT);
        $updateStatusStmt->execute();
    }

    // If removing self, update user level back to student
    if ($targetUserID == $currentUserID) {
        $updateUserLevelQuery = "UPDATE users SET user_level = 'student' WHERE user_id = :user_id";
        $updateUserLevelStmt = $pdo->prepare($updateUserLevelQuery);
        $updateUserLevelStmt->bindParam(':user_id', $targetUserID, PDO::PARAM_INT);
        $updateUserLevelStmt->execute();

        // Set flag to reload session
        $response['reload_session'] = true;
    }

    // Commit the transaction
    $pdo->commit();

    // Return success response
    $response['status'] = 'success';
    $response['message'] = 'Member removed successfully';
    $response['member_count'] = $memberCount;
    $response['status_changed'] = ($memberCount < 5);
    echo json_encode($response);

} catch (PDOException $e) {
    // Rollback the transaction in case of error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log the error
    error_log('Remove member error: ' . $e->getMessage());
    
    // Return error message
    http_response_code(500);
    $response['message'] = 'Database error occurred';
    $response['debug'] = $e->getMessage(); // Remove in production
    echo json_encode($response);
} catch (Exception $e) {
    // Log unexpected errors
    error_log('Unexpected remove member error: ' . $e->getMessage());
    
    // Return unexpected error response
    http_response_code(500);
    $response['message'] = 'An unexpected error occurred';
    $response['debug'] = $e->getMessage(); // Remove in production
    echo json_encode($response);
}