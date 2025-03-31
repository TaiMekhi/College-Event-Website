<?php
require_once('../dbh.inc.php'); // Make sure you have a database connection file
session_start();

header("Content-Type: application/json");

// Check if user is logged in using the correct session variable
if (!isset($_SESSION['userID'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized request. Please log in."]);
    exit;
}

// Get input data
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['name']) || empty($data['name']) || !isset($data['description']) || empty($data['description'])) {
    echo json_encode(["success" => false, "message" => "RSO name and description are required."]);
    exit;
}

// Use the correct session variable
$user_id = $_SESSION['userID']; 

// Query the database to get the user's university_id since it's not in the session
try {
    $stmt = $pdo->prepare("SELECT university_id FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $university_id = $stmt->fetchColumn();
    
    // Check if university_id exists
    if (!$university_id) {
        echo json_encode(["success" => false, "message" => "User is not associated with a university."]);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error retrieving university information: " . $e->getMessage()]);
    exit;
}

$rso_name = trim($data['name']);
$rso_description = trim($data['description']);

try {
    $pdo->beginTransaction();

    // Check if RSO name already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM rso WHERE name = ?");
    $stmt->execute([$rso_name]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(["success" => false, "message" => "RSO name already exists."]);
        exit;
    }

    // Insert RSO into the database
    $stmt = $pdo->prepare("INSERT INTO rso (university_id, name, description, is_active, created_by) VALUES (?, ?, ?, 0, ?)");
    $stmt->execute([$university_id, $rso_name, $rso_description, $user_id]);
    $rso_id = $pdo->lastInsertId();

    // Add creator as an admin in rso_members
    $stmt = $pdo->prepare("INSERT INTO rso_members (rso_id, user_id, role) VALUES (?, ?, 'admin')");
    $stmt->execute([$rso_id, $user_id]);

    $pdo->commit();

    echo json_encode(["success" => true, "message" => "RSO created successfully!", "rso_id" => $rso_id]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(["success" => false, "message" => "Error creating RSO: " . $e->getMessage()]);
}
?>