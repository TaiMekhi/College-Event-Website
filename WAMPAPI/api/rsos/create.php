<?php
// API endpoint for creating a new RSO
// Path: /api/rsos/create.php

header('Content-Type: application/json');
session_start();
require_once '../../dbh.inc.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo jsonResponse(false, null, "Invalid request method");
    exit();
}

if (empty($_POST['user_id']) || empty($_POST['name']) || empty($_POST['description'])) {
    echo jsonResponse(false, null, "Name, description, and user ID are required");
    exit();
}

$user_id = sanitizeInput($_POST['user_id']);
$name = sanitizeInput($_POST['name']);
$description = sanitizeInput($_POST['description']);

if (!isset($_SESSION['userID']) || $_SESSION['userID'] != $user_id) {
    echo jsonResponse(false, null, "Unauthorized user");
    exit();
}

try {
    $conn = getConnection();

    // Step 1: Check user's university
    $stmt = $conn->prepare("SELECT university_id FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo jsonResponse(false, null, "User not found");
        exit();
    }

    $userData = $result->fetch_assoc();
    $university_id = $userData['university_id'];

    if (!$university_id) {
        echo jsonResponse(false, null, "User is not associated with any university");
        exit();
    }

    // Step 2: Check if RSO name already exists
    $stmt = $conn->prepare("SELECT rso_id FROM rso WHERE name = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo jsonResponse(false, null, "An RSO with this name already exists");
        exit();
    }

    // Step 3: Insert new RSO
    $conn->begin_transaction();

    $stmt = $conn->prepare("INSERT INTO rso (university_id, name, description, created_by) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("issi", $university_id, $name, $description, $user_id);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        $conn->rollback();
        echo jsonResponse(false, null, "Failed to create RSO");
        exit();
    }

    $rso_id = $stmt->insert_id;

    // Step 4: Check number of members (after trigger)
    $stmt = $conn->prepare("SELECT COUNT(*) as member_count FROM rso_members WHERE rso_id = ?");
    $stmt->bind_param("i", $rso_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $memberCount = $result->fetch_assoc()['member_count'];

    // Step 5: Check if active
    $stmt = $conn->prepare("SELECT is_active FROM rso WHERE rso_id = ?");
    $stmt->bind_param("i", $rso_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $isActive = $result->fetch_assoc()['is_active'] == 1;

    $conn->commit();

    echo jsonResponse(true, [
        'message' => 'RSO created successfully',
        'rso_id' => $rso_id,
        'is_active' => $isActive,
        'members_needed' => max(0, 5 - $memberCount)
    ]);

} catch (Exception $e) {
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }
    echo jsonResponse(false, null, "Error: " . $e->getMessage());
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
