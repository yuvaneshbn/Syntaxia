<?php
// Set headers for JSON response
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

// Database connection
$host = 'localhost';
$dbname = 'event_registration';
$username = 'root';
$password = '';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['error' => "Connection failed: " . $e->getMessage()]);
    exit;
}

// Fetch all events
try {
    $query = $db->query("SELECT * FROM events ORDER BY id");
    $events = $query->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($events);
} catch (PDOException $e) {
    echo json_encode(['error' => "Query failed: " . $e->getMessage()]);
}
?>