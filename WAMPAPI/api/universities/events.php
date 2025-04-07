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
        // Get events based on query parameters
        if (isset($_GET['id'])) {
            // Get a specific event by ID
            getEventById($pdo, $_GET['id']);
        } elseif (isset($_GET['status']) && isset($_GET['type'])) {
            // Get events by status and type
            getEventsByStatusAndType($pdo, $_GET['status'], $_GET['type'], $_GET['university_id'] ?? null);
        } elseif (isset($_GET['university_id'])) {
            // Get events for a university
            getEventsByUniversity($pdo, $_GET['university_id']);
        } elseif (isset($_GET['rso_id'])) {
            // Get events for an RSO
            getEventsByRso($pdo, $_GET['rso_id']);
        } else {
            // Get all events (with optional filtering)
            getAllEvents($pdo);
        }
        break;
        
    case 'POST':
        // Create a new event
        createEvent($pdo);
        break;
        
    case 'PUT':
        // Update an existing event (approve/reject)
        updateEventStatus($pdo);
        break;
        
    case 'DELETE':
        // Delete an event
        if (isset($_GET['id'])) {
            deleteEvent($pdo, $_GET['id']);
        } else {
            echo json_encode(['success' => false, 'error_message' => 'Event ID is required']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'error_message' => 'Invalid request method']);
        break;
}

/**
 * Get all events with optional filtering
 */
function getAllEvents($pdo) {
    try {
        // Base query
        $query = "
            SELECT e.*, 
                  (SELECT AVG(r.rating_value) FROM ratings r WHERE r.event_id = e.event_id) as average_rating
            FROM events e
            WHERE 1=1
        ";
        
        $params = [];
        
        // Add filters if provided
        if (isset($_GET['category'])) {
            $query .= " AND e.category = :category";
            $params[':category'] = $_GET['category'];
        }
        
        if (isset($_GET['date_from'])) {
            $query .= " AND e.date >= :date_from";
            $params[':date_from'] = $_GET['date_from'];
        }
        
        if (isset($_GET['date_to'])) {
            $query .= " AND e.date <= :date_to";
            $params[':date_to'] = $_GET['date_to'];
        }
        
        // Order by date and time
        $query .= " ORDER BY e.date DESC, e.time ASC";
        
        // Prepare and execute the query
        $stmt = $pdo->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'events' => $events]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error_message' => $e->getMessage()]);
    }
}

/**
 * Get a specific event by ID
 */
function getEventById($pdo, $event_id) {
    try {
        $query = "
            SELECT e.*, 
                  (SELECT AVG(r.rating_value) FROM ratings r WHERE r.event_id = e.event_id) as average_rating
            FROM events e
            WHERE e.event_id = :event_id
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':event_id', $event_id);
        $stmt->execute();
        
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($event) {
            echo json_encode(['success' => true, 'event' => $event]);
        } else {
            echo json_encode(['success' => false, 'error_message' => 'Event not found']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error_message' => $e->getMessage()]);
    }
}

/**
 * Get events filtered by university
 */
function getEventsByUniversity($pdo, $university_id) {
    try {
        $query = "
            SELECT e.*, 
                  (SELECT AVG(r.rating_value) FROM ratings r WHERE r.event_id = e.event_id) as average_rating
            FROM events e
            WHERE e.university_id = :university_id
            ORDER BY e.date DESC, e.time ASC
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':university_id', $university_id);
        $stmt->execute();
        
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'events' => $events]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error_message' => $e->getMessage()]);
    }
}

/**
 * Get events filtered by RSO
 */
function getEventsByRso($pdo, $rso_id) {
    try {
        $query = "
            SELECT e.*, 
                  (SELECT AVG(r.rating_value) FROM ratings r WHERE r.event_id = e.event_id) as average_rating
            FROM events e
            WHERE e.rso_id = :rso_id
            ORDER BY e.date DESC, e.time ASC
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':rso_id', $rso_id);
        $stmt->execute();
        
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'events' => $events]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error_message' => $e->getMessage()]);
    }
}

/**
 * Get events filtered by status and type
 */
function getEventsByStatusAndType($pdo, $status, $type, $university_id = null) {
    try {
        // Detailed logging
        error_log("Fetching events - Status: $status, Type: $type, University ID: $university_id");
        
        $query = "
            SELECT e.*, pe.approved, u.name as university_name
            FROM events e
            JOIN public_events pe ON e.event_id = pe.event_id
            LEFT JOIN university u ON e.university_id = u.university_id
            WHERE pe.approved = 0
        ";
        
        $params = [];
        
        if ($university_id) {
            $query .= " AND e.university_id = :university_id";
            $params[':university_id'] = $university_id;
        }
        
        $query .= " ORDER BY e.date DESC, e.time ASC";
        
        $stmt = $pdo->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Log found events with more details
        error_log("Pending events found: " . count($events));
        foreach ($events as $event) {
            error_log("Event Details: ID={$event['event_id']}, Name={$event['name']}, University={$event['university_name']}, Approved={$event['approved']}");
        }
        
        echo json_encode([
            'success' => true, 
            'events' => $events
        ]);
    } catch (PDOException $e) {
        error_log("Error in getEventsByStatusAndType: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'error_message' => $e->getMessage()
        ]);
    }
}

/**
 * Update event status (approve/reject)
 */
function updateEventStatus($pdo) {
    // Parse the PUT data
    parse_str(file_get_contents("php://input"), $put_data);
    
    $event_id = isset($put_data['id']) ? $put_data['id'] : null;
    $status = isset($put_data['status']) ? $put_data['status'] : null;
    
    if (!$event_id) {
        echo json_encode(['success' => false, 'error_message' => 'Event ID is required']);
        return;
    }
    
    try {
        // Begin transaction
        $pdo->beginTransaction();
        
        // Check if the event exists and is a public event
        $checkQuery = "
            SELECT e.event_id, pe.event_id as public_event_id 
            FROM events e
            LEFT JOIN public_events pe ON e.event_id = pe.event_id
            WHERE e.event_id = :event_id
        ";
        
        $checkStmt = $pdo->prepare($checkQuery);
        $checkStmt->bindParam(':event_id', $event_id);
        $checkStmt->execute();
        
        $eventData = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$eventData) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'error_message' => 'Event not found']);
            return;
        }
        
        // If this is an approval/rejection operation
        if ($status) {
            // Verify this is a public event
            if (!$eventData['public_event_id']) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'error_message' => 'Only public events can be approved/rejected']);
                return;
            }
            
            if ($status === 'approved') {
                // Approve the event
                $approveQuery = "UPDATE public_events SET approved = 1 WHERE event_id = :event_id";
                $approveStmt = $pdo->prepare($approveQuery);
                $approveStmt->bindParam(':event_id', $event_id);
                $approveStmt->execute();
                
                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Event approved successfully']);
            } else if ($status === 'rejected') {
                // For rejected events, we might either:
                // 1. Mark as rejected in the database (if you want to keep them)
                // 2. Delete them (if you want to remove them)
                
                // Here we'll delete the event
                $deleteQuery = "DELETE FROM public_events WHERE event_id = :event_id";
                $deleteStmt = $pdo->prepare($deleteQuery);
                $deleteStmt->bindParam(':event_id', $event_id);
                $deleteStmt->execute();
                
                // Also delete from main events table
                $deleteMainQuery = "DELETE FROM events WHERE event_id = :event_id";
                $deleteMainStmt = $pdo->prepare($deleteMainQuery);
                $deleteMainStmt->bindParam(':event_id', $event_id);
                $deleteMainStmt->execute();
                
                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Event rejected and removed']);
            } else {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'error_message' => 'Invalid status value']);
            }
            
            return;
        }
        
        // For regular updates (not approval/rejection), we'd handle that here
        // But since this isn't currently supported in your use case, we'll just return an error
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error_message' => 'Only approval/rejection operations are supported']);
    } catch (PDOException $e) {
        // Rollback transaction on error
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        echo json_encode(['success' => false, 'error_message' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Delete an event
 */
function deleteEvent($pdo, $event_id) {
    try {
        // Begin transaction
        $pdo->beginTransaction();
        
        // Check if the event exists
        $checkQuery = "SELECT event_id FROM events WHERE event_id = :event_id";
        $checkStmt = $pdo->prepare($checkQuery);
        $checkStmt->bindParam(':event_id', $event_id);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() == 0) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'error_message' => 'Event not found']);
            return;
        }
        
        // Delete from public_events if it exists there
        $deletePublicQuery = "DELETE FROM public_events WHERE event_id = :event_id";
        $deletePublicStmt = $pdo->prepare($deletePublicQuery);
        $deletePublicStmt->bindParam(':event_id', $event_id);
        $deletePublicStmt->execute();
        
        // Delete from private_events if it exists there
        $deletePrivateQuery = "DELETE FROM private_events WHERE event_id = :event_id";
        $deletePrivateStmt = $pdo->prepare($deletePrivateQuery);
        $deletePrivateStmt->bindParam(':event_id', $event_id);
        $deletePrivateStmt->execute();
        
        // Delete ratings
        $deleteRatingsQuery = "DELETE FROM ratings WHERE event_id = :event_id";
        $deleteRatingsStmt = $pdo->prepare($deleteRatingsQuery);
        $deleteRatingsStmt->bindParam(':event_id', $event_id);
        $deleteRatingsStmt->execute();
        
        // Delete comments
        $deleteCommentsQuery = "DELETE FROM comments WHERE event_id = :event_id";
        $deleteCommentsStmt = $pdo->prepare($deleteCommentsQuery);
        $deleteCommentsStmt->bindParam(':event_id', $event_id);
        $deleteCommentsStmt->execute();
        
        // Finally, delete the event itself
        $deleteEventQuery = "DELETE FROM events WHERE event_id = :event_id";
        $deleteEventStmt = $pdo->prepare($deleteEventQuery);
        $deleteEventStmt->bindParam(':event_id', $event_id);
        $deleteEventStmt->execute();
        
        // Commit transaction
        $pdo->commit();
        
        echo json_encode(['success' => true, 'message' => 'Event deleted successfully']);
    } catch (PDOException $e) {
        // Rollback transaction on error
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        echo json_encode(['success' => false, 'error_message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>