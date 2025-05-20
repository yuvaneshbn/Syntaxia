<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

// Check if event parameter exists
if (!isset($_GET['event'])) {
    die('No event specified');
}

// Database connection
$host = 'localhost';
$dbname = 'event_registration';
$username = 'root';
$password = '';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$event = $_GET['event'];

// Fetch registrations for the selected event
$stmt = $db->prepare("SELECT full_name, email, phone, college_name, event_type, event_price, transaction_id, registration_date 
                     FROM registrations 
                     WHERE event_name = :event 
                     ORDER BY registration_date DESC");
$stmt->bindParam(':event', $event);
$stmt->execute();
$registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $event . '_registrations.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add CSV headers
fputcsv($output, array('Full Name', 'Email', 'Phone', 'College', 'Registration Type', 'Amount Paid', 'Transaction ID', 'Registration Date'));

// Add data rows
foreach ($registrations as $row) {
    fputcsv($output, array(
        $row['full_name'],
        $row['email'],
        $row['phone'],
        $row['college_name'],
        $row['event_type'],
        '₹' . $row['event_price'],
        $row['transaction_id'],
        date('Y-m-d H:i:s', strtotime($row['registration_date']))
    ));
}

fclose($output);
exit;
?>