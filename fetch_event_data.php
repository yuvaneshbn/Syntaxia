<?php
// Database connection
$host = 'localhost';
$dbname = 'event_registration'; // Replace with your database name
$username = 'root'; // Default XAMPP username
$password = '';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Query to get the number of registrations and total money for each event
$query = "
    SELECT 
        event_name, 
        COUNT(*) AS total_registrations, 
        SUM(event_price) AS total_money 
    FROM 
        registrations 
    WHERE 
        event_name IN ('Syntaxia', 'Technophite') 
    GROUP BY 
        event_name
";

$stmt = $db->prepare($query);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Return JSON response
header('Content-Type: application/json');
echo json_encode($results);
?>
