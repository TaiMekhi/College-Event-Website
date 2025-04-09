<?php 
session_start(); 
header('Content-Type: application/json');  

// Database connection 
$servername = "localhost"; 
$username = "root"; // Change if needed 
$password = ""; // Change if needed 
$dbname = "college_event_website";  

// Create connection 
$conn = new mysqli($servername, $username, $password, $dbname);  

// Check connection 
if ($conn->connect_error) {     
    die(json_encode(['success' => false, 'error_message' => "Connection failed: " . $conn->connect_error])); 
}  

// Ensure POST request 
if ($_SERVER["REQUEST_METHOD"] !== "POST") {     
    echo json_encode(['success' => false, 'error_message' => "Invalid request method"]);     
    exit(); 
}  

// Check if user_name and user_password are set 
if (!isset($_POST['user_name']) || !isset($_POST['user_password'])) {     
    echo json_encode(['success' => false, 'error_message' => "Username and password are required"]);     
    exit(); 
}  

// Get input values 
$user_name = trim($_POST['user_name']); 
$password = trim($_POST['user_password']);  

// Validate empty fields 
if (empty($user_name) || empty($password)) {     
    echo json_encode(['success' => false, 'error_message' => "Username and password cannot be empty"]);     
    exit(); 
}  

// Prepare statement to fetch user data - now also get university_id 
$stmt = $conn->prepare("SELECT user_id, user_name, user_password, user_level, university_id FROM users WHERE user_name = ?"); 
$stmt->bind_param("s", $user_name); 
$stmt->execute(); 
$stmt->store_result();  

// Check if user exists 
if ($stmt->num_rows > 0) {     
    $stmt->bind_result($user_id, $db_user_name, $db_user_password, $user_level, $university_id);     
    $stmt->fetch();          
    
    // Compare the passwords directly (no hashing)     
    if ($password === $db_user_password) {         
        // Store in BOTH session variables for compatibility
        $_SESSION['userID'] = $user_id;         
        $_SESSION['userLevel'] = $user_level;
        // ALSO set userRole which is what student_dashboard.php checks for
        $_SESSION['userRole'] = $user_level;
                  
        // Prepare response         
        $response = [             
            'success' => true,              
            'userID' => $user_id,              
            'userRole' => $user_level         
        ];                  
        
        // Add university_id to response if user is a superadmin and has a university         
        if ($user_level === 'superadmin' && !empty($university_id)) {             
            $response['universityID'] = $university_id;         
        }                  
        
        echo json_encode($response);     
    } else {         
        echo json_encode(['success' => false, 'error_message' => "Invalid password"]);     
    } 
} else {     
    echo json_encode(['success' => false, 'error_message' => "User not found"]); 
}  

// Close connections 
$stmt->close(); 
$conn->close(); 
?>