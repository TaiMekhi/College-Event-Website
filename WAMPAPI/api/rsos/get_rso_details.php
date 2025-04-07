<?php
// Enable error reporting for debugging
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

    // Verify user's admin status for this RSO
    $adminCheckQuery = "SELECT 1 FROM rso_members WHERE rso_id = :rso_id AND user_id = :user_id AND role = 'admin'";
    $adminStmt = $pdo->prepare($adminCheckQuery);
    $adminStmt->bindParam(':rso_id', $rsoID, PDO::PARAM_INT);
    $adminStmt->bindParam(':user_id', $userID, PDO::PARAM_INT);
    $adminStmt->execute();

    if (!$adminStmt->fetch()) {
        http_response_code(403);
        $response['message'] = 'You do not have admin permissions for this RSO';
        echo json_encode($response);
        exit();
    }

    // Get RSO information
    $query = "SELECT rso.*, university.name AS university_name 
              FROM rso 
              LEFT JOIN university ON rso.university_id = university.university_id 
              WHERE rso.rso_id = :rso_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':rso_id', $rsoID, PDO::PARAM_INT);
    $stmt->execute();

    $rso = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$rso) {
        http_response_code(404);
        $response['message'] = 'RSO not found';
        echo json_encode($response);
        exit();
    }

    // Get member count
    $countQuery = "SELECT COUNT(*) as member_count FROM rso_members WHERE rso_id = :rso_id";
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->bindParam(':rso_id', $rsoID, PDO::PARAM_INT);
    $countStmt->execute();
    $memberCount = $countStmt->fetch(PDO::FETCH_ASSOC);

    // Prepare response data
    $rsoData = [
        'id' => $rso['rso_id'],
        'name' => $rso['name'],
        'description' => $rso['description'],
        'status' => $rso['is_active'] == 1 ? 'Active' : 'Inactive',
        'university_name' => $rso['university_name'] ?? 'None',
        'member_count' => $memberCount['member_count']
    ];

    // Success response
    $response['status'] = 'success';
    $response['message'] = 'RSO details retrieved successfully';
    $response['data'] = $rsoData;

    echo json_encode($response);

} catch (PDOException $e) {
    // Log the full error for server-side debugging
    error_log('PDO Error: ' . $e->getMessage());
    
    // Return a generic error to the client
    http_response_code(500);
    $response['message'] = 'Internal server error';
    $response['debug'] = $e->getMessage(); // Only for development
    echo json_encode($response);
} catch (Exception $e) {
    // Catch any other unexpected errors
    error_log('Unexpected Error: ' . $e->getMessage());
    
    http_response_code(500);
    $response['message'] = 'Unexpected server error';
    $response['debug'] = $e->getMessage(); // Only for development
    echo json_encode($response);
}