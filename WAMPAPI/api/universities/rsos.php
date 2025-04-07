<?php
// Include database connection
require_once '../../dbh.inc.php';

// Get PDO connection
$pdo = getPDOConnection();

// Set headers for JSON response
header('Content-Type: application/json');

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get the request method
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get all RSOs or RSOs for a specific university or user
        if (isset($_GET['university_id'])) {
            getRsosByUniversity($pdo, $_GET['university_id']);
        } elseif (isset($_GET['user_id'])) {
            getUserRsos($pdo, $_GET['user_id']);
        } elseif (isset($_GET['rso_id'])) {
            getRsoDetails($pdo, $_GET['rso_id']);
        } else {
            getAllRsos($pdo);
        }
        break;
        
    case 'POST':
        // Create a new RSO
        createRso($pdo);
        break;
        
    case 'PUT':
        // Update an existing RSO
        updateRso($pdo);
        break;
        
    case 'DELETE':
        // Delete an RSO
        if (isset($_GET['id'])) {
            deleteRso($pdo, $_GET['id']);
        } else {
            echo json_encode(['success' => false, 'error_message' => 'RSO ID is required']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'error_message' => 'Invalid request method']);
        break;
}

function getAllRsos($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT r.*, u.name as university_name, 
            (SELECT COUNT(*) FROM rso_members rm WHERE rm.rso_id = r.rso_id) as member_count,
            (SELECT CONCAT(u.first_name, ' ', u.last_name) 
             FROM users u 
             JOIN rso_members rm ON u.user_id = rm.user_id 
             WHERE rm.rso_id = r.rso_id AND rm.role = 'admin' 
             LIMIT 1) as admin_name
            FROM rso r
            JOIN university u ON r.university_id = u.university_id
            ORDER BY r.name
        ");
        $stmt->execute();
        
        $rsos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'rsos' => $rsos]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error_message' => $e->getMessage()]);
    }
}

function getRsosByUniversity($pdo, $university_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT r.*, 
            (SELECT COUNT(*) FROM rso_members rm WHERE rm.rso_id = r.rso_id) as member_count,
            (SELECT CONCAT(u.first_name, ' ', u.last_name) 
             FROM users u 
             JOIN rso_members rm ON u.user_id = rm.user_id 
             WHERE rm.rso_id = r.rso_id AND rm.role = 'admin' 
             LIMIT 1) as admin_name
            FROM rso r
            WHERE r.university_id = :university_id
            ORDER BY r.name
        ");
        $stmt->bindParam(':university_id', $university_id);
        $stmt->execute();
        
        $rsos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format response with status field
        foreach ($rsos as &$rso) {
            $rso['status'] = $rso['is_active'] ? 'Active' : 'Pending';
        }
        
        echo json_encode(['success' => true, 'rsos' => $rsos]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error_message' => $e->getMessage()]);
    }
}

function getUserRsos($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT r.*, 
            (SELECT COUNT(*) FROM rso_members rm2 WHERE rm2.rso_id = r.rso_id) as member_count,
            rm.role as user_role
            FROM rso r
            JOIN rso_members rm ON r.rso_id = rm.rso_id
            WHERE rm.user_id = :user_id
            ORDER BY r.name
        ");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $rsos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'rsos' => $rsos]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error_message' => $e->getMessage()]);
    }
}

function getRsoDetails($pdo, $rso_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT r.*, u.name as university_name,
            (SELECT COUNT(*) FROM rso_members rm WHERE rm.rso_id = r.rso_id) as member_count,
            (SELECT GROUP_CONCAT(CONCAT(u.first_name, ' ', u.last_name) SEPARATOR ', ') 
             FROM users u 
             JOIN rso_members rm ON u.user_id = rm.user_id 
             WHERE rm.rso_id = r.rso_id AND rm.role = 'admin') as admins
            FROM rso r
            JOIN university u ON r.university_id = u.university_id
            WHERE r.rso_id = :rso_id
        ");
        $stmt->bindParam(':rso_id', $rso_id);
        $stmt->execute();
        
        $rso = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($rso) {
            // Add a formatted status field
            $rso['status'] = $rso['is_active'] ? 'Active' : 'Pending';
            
            echo json_encode(['success' => true, 'data' => $rso]);
        } else {
            echo json_encode(['success' => false, 'error_message' => 'RSO not found']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error_message' => $e->getMessage()]);
    }
}

function createRso($pdo) {
    // Get POST data
    $name = isset($_POST['name']) ? $_POST['name'] : null;
    $description = isset($_POST['description']) ? $_POST['description'] : null;
    $university_id = isset($_POST['university_id']) ? $_POST['university_id'] : null;
    $created_by = isset($_POST['user_id']) ? $_POST['user_id'] : null;
    
    // Validate required fields
    if (!$name || !$university_id || !$created_by) {
        echo json_encode(['success' => false, 'error_message' => 'Name, university ID, and user ID are required']);
        return;
    }
    
    try {
        // Begin transaction
        $pdo->beginTransaction();
        
        // Check if RSO with same name already exists at university
        $check_stmt = $pdo->prepare("
            SELECT rso_id FROM rso 
            WHERE name = :name AND university_id = :university_id
        ");
        $check_stmt->bindParam(':name', $name);
        $check_stmt->bindParam(':university_id', $university_id);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'error_message' => 'An RSO with this name already exists at your university']);
            return;
        }
        
        // Verify user is part of the university
        $user_stmt = $pdo->prepare("
            SELECT university_id FROM users 
            WHERE user_id = :user_id
        ");
        $user_stmt->bindParam(':user_id', $created_by);
        $user_stmt->execute();
        
        $user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user_data || $user_data['university_id'] != $university_id) {
            echo json_encode(['success' => false, 'error_message' => 'User must be part of the university to create an RSO']);
            return;
        }
        
        // By default, RSOs are not active until they have 5+ members
        $is_active = 0;
        
        // Insert new RSO
        $stmt = $pdo->prepare("
            INSERT INTO rso (name, description, university_id, is_active, created_by)
            VALUES (:name, :description, :university_id, :is_active, :created_by)
        ");
        
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':university_id', $university_id);
        $stmt->bindParam(':is_active', $is_active);
        $stmt->bindParam(':created_by', $created_by);
        
        $stmt->execute();
        $rso_id = $pdo->lastInsertId();
        
        // RSO creator is automatically added as an admin
        // This might be handled by a trigger in your database
        // But we'll add it here as well for safety
        $member_stmt = $pdo->prepare("
            INSERT INTO rso_members (rso_id, user_id, role)
            VALUES (:rso_id, :user_id, 'admin')
            ON DUPLICATE KEY UPDATE role = 'admin'
        ");
        $member_stmt->bindParam(':rso_id', $rso_id);
        $member_stmt->bindParam(':user_id', $created_by);
        $member_stmt->execute();
        
        // Commit transaction
        $pdo->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'RSO created successfully. Invite at least 4 more members to activate it.',
            'rso_id' => $rso_id
        ]);
    } catch (PDOException $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error_message' => 'Database error: ' . $e->getMessage()]);
    }
}

function updateRso($pdo) {
    // Parse the PUT data
    parse_str(file_get_contents("php://input"), $put_data);
    
    $rso_id = isset($put_data['rso_id']) ? $put_data['rso_id'] : null;
    $name = isset($put_data['name']) ? $put_data['name'] : null;
    $description = isset($put_data['description']) ? $put_data['description'] : null;
    $is_active = isset($put_data['is_active']) ? $put_data['is_active'] : null;
    
    // RSO ID and at least one updateable field are required
    if (!$rso_id || (!$name && !$description && $is_active === null)) {
        echo json_encode(['success' => false, 'error_message' => 'RSO ID and at least one field to update are required']);
        return;
    }
    
    try {
        // Start building the update query
        $updateFields = [];
        $params = [':rso_id' => $rso_id];
        
        if ($name) {
            $updateFields[] = "name = :name";
            $params[':name'] = $name;
        }
        
        if ($description) {
            $updateFields[] = "description = :description";
            $params[':description'] = $description;
        }
        
        if ($is_active !== null) {
            $updateFields[] = "is_active = :is_active";
            $params[':is_active'] = $is_active;
        }
        
        // If no fields to update, return error
        if (empty($updateFields)) {
            echo json_encode(['success' => false, 'error_message' => 'No fields to update']);
            return;
        }
        
        $query = "UPDATE rso SET " . implode(", ", $updateFields) . " WHERE rso_id = :rso_id";
        $stmt = $pdo->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'RSO updated successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error_message' => $e->getMessage()]);
    }
}

function deleteRso($pdo, $rso_id) {
    try {
        // Check if RSO exists
        $check_stmt = $pdo->prepare("SELECT rso_id FROM rso WHERE rso_id = :rso_id");
        $check_stmt->bindParam(':rso_id', $rso_id);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() == 0) {
            echo json_encode(['success' => false, 'error_message' => 'RSO not found']);
            return;
        }
        
        // Begin transaction
        $pdo->beginTransaction();
        
        // Delete RSO members first (due to foreign key constraints)
        $member_stmt = $pdo->prepare("DELETE FROM rso_members WHERE rso_id = :rso_id");
        $member_stmt->bindParam(':rso_id', $rso_id);
        $member_stmt->execute();
        
        // Delete events associated with this RSO
        // This might need additional logic based on your exact schema
        $events_stmt = $pdo->prepare("
            SELECT event_id FROM events WHERE rso_id = :rso_id
        ");
        $events_stmt->bindParam(':rso_id', $rso_id);
        $events_stmt->execute();
        
        while ($event = $events_stmt->fetch(PDO::FETCH_ASSOC)) {
            $event_id = $event['event_id'];
            
            // Delete from type-specific tables first
            $pdo->prepare("DELETE FROM public_events WHERE event_id = :event_id")->execute([':event_id' => $event_id]);
            $pdo->prepare("DELETE FROM private_events WHERE event_id = :event_id")->execute([':event_id' => $event_id]);
            
            // Delete from events table
            $pdo->prepare("DELETE FROM events WHERE event_id = :event_id")->execute([':event_id' => $event_id]);
        }
        
        // Delete the RSO itself
        $stmt = $pdo->prepare("DELETE FROM rso WHERE rso_id = :rso_id");
        $stmt->bindParam(':rso_id', $rso_id);
        $stmt->execute();
        
        // Commit transaction
        $pdo->commit();
        
        echo json_encode(['success' => true, 'message' => 'RSO deleted successfully']);
    } catch (PDOException $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error_message' => $e->getMessage()]);
    }
}
?>