<?php

$host = "localhost";
$username = "root";
$password = "password";
$dbname = "college_event_website";


$global_pdo = null;


function getPDOConnection() {
    global $host, $username, $password, $dbname, $global_pdo;
    
    // Reuse existing connection if available
    if ($global_pdo !== null) {
        return $global_pdo;
    }
    
    $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
    
    try {
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Store for reuse
        $global_pdo = $pdo;
        
        return $pdo;
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}


function getConnection() {
    global $host, $username, $password, $dbname;
    
    $conn = new mysqli($host, $username, $password, $dbname);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}


function resultToArray($result) {
    $rows = array();
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    return $rows;
}


function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function jsonResponse($success, $data = null, $error_message = null) {
    $response = ['success' => $success];
    
    if ($data !== null) {
        $response = array_merge($response, $data);
    }
    
    if (!$success && $error_message !== null) {
        $response['error_message'] = $error_message;
    }
    
    return json_encode($response);
}
?>