<?php
session_start();
header('Content-Type: application/json');

// Setup error logging
error_log("DEBUG: formhandler.php started");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Direct access to raw POST data
    $raw_post = file_get_contents('php://input');
    error_log("DEBUG: Raw POST data: " . $raw_post);
    
    // Use a different variable name to avoid potential conflicts
    $user_pwd = isset($_POST['password']) ? $_POST['password'] : '';
    error_log("DEBUG: Raw password value from POST: '" . $user_pwd . "'");
    
    // Get other form values
    $user_name = isset($_POST['user_name']) ? $_POST['user_name'] : '';
    $first_name = isset($_POST['first_name']) ? $_POST['first_name'] : '';
    $last_name = isset($_POST['last_name']) ? $_POST['last_name'] : '';
    $userLevel = isset($_POST['userLevel']) ? $_POST['userLevel'] : '';
    
    // Log all values
    error_log("DEBUG: All values retrieved - username: $user_name, pwd length: " . strlen($user_pwd) . 
              ", first: $first_name, last: $last_name, level: $userLevel");
    
    // Check if any required fields are missing
    if (empty($user_name) || empty($user_pwd) || empty($first_name) || empty($userLevel)) {
        error_log("DEBUG: Missing required fields");
        echo json_encode(['success' => false, 'error_message' => "Please fill in all required fields."]);
        exit;
    }

    try {
        require_once 'dbh.inc.php';

        $pdo = getPDOConnection();
        
        // Check if the user_name already exists
        $query_check = "SELECT user_name FROM users WHERE user_name = ?";
        $stmt_check = $pdo->prepare($query_check);
        $stmt_check->execute([$user_name]);
        
        if ($stmt_check->rowCount() > 0) {
            echo json_encode(['success' => false, 'error_message' => "Username already exists. Please use a different username."]);
        } else {
            // Immediately create insert parameters with the password value
            $params = array();
            $params[0] = $user_name;
            $params[1] = $user_pwd;  // Using our renamed variable
            $params[2] = $first_name;
            $params[3] = $last_name;
            $params[4] = $userLevel;
            
            error_log("DEBUG: Password value for insert: '" . $params[1] . "' length: " . strlen($params[1]));
            
            // Insert user into database with renamed parameters
            $query_insert = "INSERT INTO users (`user_name`, `user_password`, `first_name`, `last_name`, `user_level`) VALUES (?, ?, ?, ?, ?)";
            
            $stmt_insert = $pdo->prepare($query_insert);
            $result = $stmt_insert->execute($params);
            
            // Verify the inserted record
            $last_id = $pdo->lastInsertId();
            error_log("DEBUG: Last inserted ID: " . $last_id);
            
            // Check what was actually stored
            $verify_query = "SELECT * FROM users WHERE user_id = ?";
            $verify_stmt = $pdo->prepare($verify_query);
            $verify_stmt->execute([$last_id]);
            $new_user = $verify_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($new_user) {
                error_log("DEBUG: Password column in DB: '" . $new_user['user_password'] . "'");
            }
            
            echo json_encode(['success' => true, 'message' => "Registration successful."]);
        }
    } catch (PDOException $e) {
        error_log("DEBUG: Database error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error_message' => "Database error: " . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error_message' => "Invalid request method."]);
}
?>