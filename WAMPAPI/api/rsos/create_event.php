<?php
session_start();
require_once "../../dbh.inc.php";
$pdo = getPDOConnection();

// Check authentication
if (!isset($_SESSION['userID'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

$userID = $_SESSION['userID'];
$userRole = $_SESSION['userRole'] ?? 'student';

// Parse input based on content type
$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
$data = strpos($contentType, 'application/json') !== false ? 
    json_decode(file_get_contents('php://input'), true) : $_POST;

// Validate required fields
$requiredFields = [
    'name', 'category', 'description', 'date', 'time', 
    'location', 'latitude', 'longitude', 'contact_phone', 
    'contact_email', 'type'
];

foreach ($requiredFields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => "Missing required field: $field"]);
        exit();
    }
}

// Get and sanitize input data
$eventName = htmlspecialchars(trim($data['name']));
$eventCategory = htmlspecialchars(trim($data['category']));
$eventDescription = htmlspecialchars(trim($data['description']));
$eventDate = $data['date'];
$eventTime = $data['time'];
$eventLocationName = htmlspecialchars(trim($data['location']));
$eventRoomNumber = isset($data['room_number']) ? htmlspecialchars(trim($data['room_number'])) : '';
$eventLatitude = floatval($data['latitude']);
$eventLongitude = floatval($data['longitude']);
$eventContactPhone = htmlspecialchars(trim($data['contact_phone']));
$eventContactEmail = htmlspecialchars(trim($data['contact_email']));
$eventType = htmlspecialchars(trim($data['type']));
$eventRsoID = isset($data['rso_id']) ? intval($data['rso_id']) : null;

// Validate event type
$validTypes = ['public', 'private', 'rso'];
if (!in_array($eventType, $validTypes)) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid event type']);
    exit();
}

// Students can only create public events
if ($userRole === 'student' && $eventType !== 'public') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Students can only create public events']);
    exit();
}

// If creating RSO event, RSO ID is required
if ($eventType === 'rso' && empty($eventRsoID)) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'RSO ID is required for RSO events']);
    exit();
}

try {
    $pdo->beginTransaction();
    
    // Check for time conflicts at this location
    $conflictQuery = "SELECT COUNT(*) FROM events e
                      WHERE e.location_name = :location_name " .
                      (!empty($eventRoomNumber) ? "AND e.room_number = :room_number " : "") .
                      "AND date = :date 
                      AND (
                          (time BETWEEN :start_time AND ADDTIME(:start_time, '01:00')) OR
                          (ADDTIME(time, '01:00') > :start_time AND time < :start_time)
                      )";
    
    $conflictStmt = $pdo->prepare($conflictQuery);
    $conflictParams = [
        ':location_name' => $eventLocationName,
        ':date' => $eventDate,
        ':start_time' => $eventTime
    ];
    
    if (!empty($eventRoomNumber)) {
        $conflictParams[':room_number'] = $eventRoomNumber;
    }
    
    $conflictStmt->execute($conflictParams);
    
    if ($conflictStmt->fetchColumn() > 0) {
        $pdo->rollBack();
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error', 
            'message' => 'An event already exists at this location and time'
        ]);
        exit();
    }
    
    // Determine university ID based on event type
    $universityID = null;
    
    // For public and private events, ensure a university is associated
    if ($eventType === 'public' || $eventType === 'private') {
        $universityStmt = $pdo->prepare("SELECT university_id FROM users WHERE user_id = :user_id");
        $universityStmt->bindParam(':user_id', $userID, PDO::PARAM_INT);
        $universityStmt->execute();
        
        $universityID = $universityStmt->fetchColumn();
        
        if (!$universityID) {
            $pdo->rollBack();
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error', 
                'message' => 'You must be associated with a university to create public or private events'
            ]);
            exit();
        }
    } 
    
    // For RSO events, get the university from the RSO
    if ($eventType === 'rso') {
        // Verify the user is an admin of the RSO
        $adminStmt = $pdo->prepare("SELECT COUNT(*) FROM rso_members 
                                    WHERE rso_id = :rso_id AND user_id = :user_id AND role = 'admin'");
        $adminStmt->bindParam(':rso_id', $eventRsoID, PDO::PARAM_INT);
        $adminStmt->bindParam(':user_id', $userID, PDO::PARAM_INT);
        $adminStmt->execute();
        
        if ($adminStmt->fetchColumn() == 0) {
            $pdo->rollBack();
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'You are not authorized to create events for this RSO']);
            exit();
        }
        
        // Check if RSO is active
        $statusStmt = $pdo->prepare("SELECT is_active, university_id FROM rso WHERE rso_id = :rso_id");
        $statusStmt->bindParam(':rso_id', $eventRsoID, PDO::PARAM_INT);
        $statusStmt->execute();
        
        $rsoData = $statusStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($rsoData['is_active'] != 1) {
            $pdo->rollBack();
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Events can only be created for active RSOs (5+ members)']);
            exit();
        }
        
        $universityID = $rsoData['university_id'];
    }
    
    // Create the event
    $insertStmt = $pdo->prepare("INSERT INTO events (
                name, category, description, date, time, 
                location_name, room_number, latitude, longitude, contact_phone, 
                contact_email, admin_id, university_id
            ) VALUES (
                :name, :category, :description, :date, :time, 
                :location_name, :room_number, :latitude, :longitude, :contact_phone, 
                :contact_email, :admin_id, :university_id
            )");
    
    $insertStmt->bindParam(':name', $eventName);
    $insertStmt->bindParam(':category', $eventCategory);
    $insertStmt->bindParam(':description', $eventDescription);
    $insertStmt->bindParam(':date', $eventDate);
    $insertStmt->bindParam(':time', $eventTime);
    $insertStmt->bindParam(':location_name', $eventLocationName);
    $insertStmt->bindParam(':room_number', $eventRoomNumber);
    $insertStmt->bindParam(':latitude', $eventLatitude);
    $insertStmt->bindParam(':longitude', $eventLongitude);
    $insertStmt->bindParam(':contact_phone', $eventContactPhone);
    $insertStmt->bindParam(':contact_email', $eventContactEmail);
    $insertStmt->bindParam(':admin_id', $userID);
    $insertStmt->bindParam(':university_id', $universityID);
    $insertStmt->execute();
    
    $eventID = $pdo->lastInsertId();
    
    // Insert into event_locations
    $locationStmt = $pdo->prepare("INSERT INTO event_locations (
                  event_id, location_name, latitude, longitude
                ) VALUES (
                  :event_id, :location_name, :latitude, :longitude
                )");
    $locationStmt->execute([
        ':event_id' => $eventID,
        ':location_name' => $eventLocationName,
        ':latitude' => $eventLatitude,
        ':longitude' => $eventLongitude
    ]);
    
    $locationId = $pdo->lastInsertId();
    
    // Update event with location_id
    $updateEventStmt = $pdo->prepare("UPDATE events SET location_id = :location_id WHERE event_id = :event_id");
    $updateEventStmt->bindParam(':location_id', $locationId);
    $updateEventStmt->bindParam(':event_id', $eventID);
    $updateEventStmt->execute();
    
    // Add to the appropriate event type table
    if ($eventType == 'public') {
        $approved = ($_SESSION['userRole'] === 'superadmin') ? 1 : 0;
        $publicStmt = $pdo->prepare("INSERT INTO public_events (event_id, approved) VALUES (:event_id, :approved)");
        $publicStmt->bindParam(':event_id', $eventID);
        $publicStmt->bindParam(':approved', $approved);
        $publicStmt->execute();
    } elseif ($eventType == 'private') {
        $pdo->prepare("INSERT INTO private_events (event_id) VALUES (:event_id)")
            ->execute([':event_id' => $eventID]);
    } elseif ($eventType == 'rso') {
        $tableStmt = $pdo->query("SHOW TABLES LIKE 'rso_events'");
        
        if ($tableStmt->rowCount() > 0) {
            $pdo->prepare("INSERT INTO rso_events (event_id, rso_id) VALUES (:event_id, :rso_id)")
                ->execute([':event_id' => $eventID, ':rso_id' => $eventRsoID]);
        } else {
            $pdo->prepare("INSERT INTO private_events (event_id) VALUES (:event_id)")
                ->execute([':event_id' => $eventID]);
            error_log("Warning: rso_events table not found. Using private_events as fallback for RSO event: " . $eventID);
        }
    }
    
    $pdo->commit();
    
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success', 
        'message' => $eventType === 'public' && $userRole !== 'superadmin' ? 
            'Event submitted for approval successfully' : 'Event created successfully',
        'event_id' => $eventID
    ]);
} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>