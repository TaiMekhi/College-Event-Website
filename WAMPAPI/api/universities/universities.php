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
        // Get all universities or a specific university
        if (isset($_GET['university_id'])) {
            getUniversity($pdo, $_GET['university_id']);
        } else {
            getAllUniversities($pdo);
        }
        break;
        
    case 'POST':
        // Create a new university
        createUniversity($pdo);
        break;
        
    case 'PUT':
        // Update an existing university
        updateUniversity($pdo);
        break;
        
    case 'DELETE':
        // Delete a university
        if (isset($_GET['id'])) {
            deleteUniversity($pdo, $_GET['id']);
        } else {
            echo json_encode(['success' => false, 'error_message' => 'University ID is required']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'error_message' => 'Invalid request method']);
        break;
}

function getAllUniversities($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM university ORDER BY name");
        $stmt->execute();
        
        $universities = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'universities' => $universities]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error_message' => $e->getMessage()]);
    }
}

function getUniversity($pdo, $university_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM university WHERE university_id = :university_id");
        $stmt->bindParam(':university_id', $university_id);
        $stmt->execute();
        
        $university = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($university) {
            echo json_encode(['success' => true, 'university' => $university]);
        } else {
            echo json_encode(['success' => false, 'error_message' => 'University not found']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error_message' => $e->getMessage()]);
    }
}

function createUniversity($pdo) {
    // Get POST data
    $name = isset($_POST['name']) ? $_POST['name'] : null;
    $location = isset($_POST['location']) ? $_POST['location'] : null;
    $description = isset($_POST['description']) ? $_POST['description'] : null;
    $num_students = isset($_POST['num_students']) ? $_POST['num_students'] : null;
    $email_domain = isset($_POST['email_domain']) ? $_POST['email_domain'] : null;
    $pictures = isset($_POST['pictures']) ? $_POST['pictures'] : null;
    $created_by = isset($_POST['created_by']) ? $_POST['created_by'] : null;
    
    // Validate required fields
    if (!$name || !$location || !$description || !$num_students || !$email_domain || !$created_by) {
        echo json_encode([
            'success' => false, 
            'error_message' => 'All fields are required',
            'received' => [
                'name' => $name ? 'yes' : 'no',
                'location' => $location ? 'yes' : 'no',
                'description' => $description ? 'yes' : 'no',
                'num_students' => $num_students ? 'yes' : 'no',
                'email_domain' => $email_domain ? 'yes' : 'no',
                'created_by' => $created_by ? 'yes' : 'no'
            ]
        ]);
        return;
    }
    
    try {
        // Check if university with same name already exists
        $check_stmt = $pdo->prepare("SELECT university_id FROM university WHERE name = :name");
        $check_stmt->bindParam(':name', $name);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'error_message' => 'A university with this name already exists']);
            return;
        }
        
        // Insert new university
        $stmt = $pdo->prepare("
            INSERT INTO university (name, location, description, num_students, email_domain, pictures, created_by)
            VALUES (:name, :location, :description, :num_students, :email_domain, :pictures, :created_by)
        ");
        
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':location', $location);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':num_students', $num_students);
        $stmt->bindParam(':email_domain', $email_domain);
        $stmt->bindParam(':pictures', $pictures);
        $stmt->bindParam(':created_by', $created_by);
        
        $stmt->execute();
        
        $university_id = $pdo->lastInsertId();
        
        // Also update the user's university_id
        $update_user = $pdo->prepare("
            UPDATE users 
            SET university_id = :university_id 
            WHERE user_id = :user_id
        ");
        
        $update_user->bindParam(':university_id', $university_id);
        $update_user->bindParam(':user_id', $created_by);
        $update_user->execute();
        
        echo json_encode([
            'success' => true, 
            'message' => 'University created successfully',
            'university_id' => $university_id
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error_message' => 'Database error: ' . $e->getMessage()]);
    }
}

function updateUniversity($pdo) {
    // Parse the PUT data
    parse_str(file_get_contents("php://input"), $put_data);
    
    $university_id = isset($put_data['university_id']) ? $put_data['university_id'] : null;
    $name = isset($put_data['name']) ? $put_data['name'] : null;
    $location = isset($put_data['location']) ? $put_data['location'] : null;
    $description = isset($put_data['description']) ? $put_data['description'] : null;
    $num_students = isset($put_data['num_students']) ? $put_data['num_students'] : null;
    $email_domain = isset($put_data['email_domain']) ? $put_data['email_domain'] : null;
    $pictures = isset($put_data['pictures']) ? $put_data['pictures'] : null;
    
    // Validate required fields
    if (!$university_id || !$name || !$location || !$description || !$num_students || !$email_domain) {
        echo json_encode(['success' => false, 'error_message' => 'All fields are required']);
        return;
    }
    
    try {
        // Check if university exists
        $check_stmt = $pdo->prepare("SELECT university_id FROM university WHERE university_id = :university_id");
        $check_stmt->bindParam(':university_id', $university_id);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() == 0) {
            echo json_encode(['success' => false, 'error_message' => 'University not found']);
            return;
        }
        
        // Update university
        $stmt = $pdo->prepare("
            UPDATE university
            SET name = :name, location = :location, description = :description, 
                num_students = :num_students, email_domain = :email_domain, pictures = :pictures
            WHERE university_id = :university_id
        ");
        
        $stmt->bindParam(':university_id', $university_id);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':location', $location);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':num_students', $num_students);
        $stmt->bindParam(':email_domain', $email_domain);
        $stmt->bindParam(':pictures', $pictures);
        
        $stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'University updated successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error_message' => $e->getMessage()]);
    }
}

function deleteUniversity($pdo, $university_id) {
    try {
        // First check if there are any dependent records
        $check_students = $pdo->prepare("SELECT COUNT(*) FROM users WHERE university_id = :university_id");
        $check_students->bindParam(':university_id', $university_id);
        $check_students->execute();
        $num_students = $check_students->fetchColumn();
        
        $check_events = $pdo->prepare("SELECT COUNT(*) FROM events WHERE university_id = :university_id");
        $check_events->bindParam(':university_id', $university_id);
        $check_events->execute();
        $event_count = $check_events->fetchColumn();
        
        $check_rsos = $pdo->prepare("SELECT COUNT(*) FROM rsos WHERE university_id = :university_id");
        $check_rsos->bindParam(':university_id', $university_id);
        $check_rsos->execute();
        $rso_count = $check_rsos->fetchColumn();
        
        if ($num_students > 0 || $event_count > 0 || $rso_count > 0) {
            echo json_encode([
                'success' => false, 
                'error_message' => 'Cannot delete university. It has associated students, events, or RSOs.',
                'details' => [
                    'students' => $num_students,
                    'events' => $event_count,
                    'rsos' => $rso_count
                ]
            ]);
            return;
        }
        
        // Delete the university if no dependencies
        $stmt = $pdo->prepare("DELETE FROM university WHERE university_id = :university_id");
        $stmt->bindParam(':university_id', $university_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'University deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'error_message' => 'University not found']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error_message' => $e->getMessage()]);
    }
}
?>