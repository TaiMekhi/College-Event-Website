<?php
// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to JSON
header('Content-Type: application/json');

// Include database connection
require_once "../../dbh.inc.php";

// Initialize response
$response = [
    'status' => 'error',
    'message' => 'An unknown error occurred',
    'data' => null
];

try {
    // Get PDO connection using the function from dbh.inc.php
    $pdo = getPDOConnection();

    // Check if user is logged in
    session_start();
    if (!isset($_SESSION['userID'])) {
        http_response_code(401);
        $response['message'] = 'Unauthorized access';
        echo json_encode($response);
        exit();
    }

    // Check if RSO ID is provided
    if (!isset($_GET['rso_id']) || empty($_GET['rso_id'])) {
        http_response_code(400);
        $response['message'] = 'RSO ID is required';
        echo json_encode($response);
        exit();
    }

    // Get parameters
    $userID = $_SESSION['userID'];
    $rsoID = $_GET['rso_id'];

    // First, check if the user is an admin of this RSO
    $adminCheckQuery = "SELECT COUNT(*) FROM rso_members 
                        WHERE rso_id = :rso_id AND user_id = :user_id AND role = 'admin'";
    $adminStmt = $pdo->prepare($adminCheckQuery);
    $adminStmt->bindParam(':rso_id', $rsoID, PDO::PARAM_INT);
    $adminStmt->bindParam(':user_id', $userID, PDO::PARAM_INT);
    $adminStmt->execute();

    if ($adminStmt->fetchColumn() == 0) {
        // User is not an admin of this RSO
        http_response_code(403);
        $response['message'] = 'You are not authorized to view this RSO\'s members';
        echo json_encode($response);
        exit();
    }

    // Get RSO details to confirm it exists
    $rsoQuery = "SELECT name FROM rso WHERE rso_id = :rso_id";
    $rsoStmt = $pdo->prepare($rsoQuery);
    $rsoStmt->bindParam(':rso_id', $rsoID, PDO::PARAM_INT);
    $rsoStmt->execute();
    
    if (!$rsoStmt->fetch()) {
        http_response_code(404);
        $response['message'] = 'RSO not found';
        echo json_encode($response);
        exit();
    }

    // Get RSO members with detailed information
    $query = "SELECT 
                u.user_id, 
                u.user_name AS username, 
                u.first_name, 
                u.last_name, 
                rm.role
              FROM rso_members rm
              JOIN users u ON rm.user_id = u.user_id
              WHERE rm.rso_id = :rso_id
              ORDER BY 
                CASE rm.role 
                    WHEN 'admin' THEN 1 
                    WHEN 'member' THEN 2 
                    ELSE 3 
                END, 
                u.first_name, 
                u.last_name";

    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':rso_id', $rsoID, PDO::PARAM_INT);
    $stmt->execute();

    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare successful response
    $response['status'] = 'success';
    $response['message'] = 'RSO members retrieved successfully';
    $response['data'] = $members;

    // Return members as JSON
    echo json_encode($response);

} catch (PDOException $e) {
    // Log the error for server-side debugging
    error_log('PDO Error in get_rso_members: ' . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    $response['message'] = 'Database error occurred';
    $response['debug'] = $e->getMessage(); // Remove in production
    echo json_encode($response);
} catch (Exception $e) {
    // Log unexpected errors
    error_log('Unexpected Error in get_rso_members: ' . $e->getMessage());
    
    // Return unexpected error response
    http_response_code(500);
    $response['message'] = 'An unexpected error occurred';
    $response['debug'] = $e->getMessage(); // Remove in production
    echo json_encode($response);
}