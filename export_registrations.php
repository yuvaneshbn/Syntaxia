e<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
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

// Get all registrations
$query = $db->query("SELECT * FROM registrations ORDER BY registration_date DESC");
$registrations = $query->fetchAll(PDO::FETCH_ASSOC);

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="registrations_' . date('Y-m-d') . '.csv"');

// Open output stream
$output = fopen('php://output', 'w');

// Add CSV headers
fputcsv($output, [
    'ID', 'Event Name', 'Event Type', 'Event Price', 'Full Name', 'Email', 
    'Phone', 'College Name', 'Transaction ID', 'Registration Date'
]);

// Add data rows
foreach ($registrations as $reg) {
    fputcsv($output, [
        $reg['id'],
        $reg['event_name'],
        $reg['event_type'],
        $reg['event_price'],
        $reg['full_name'],
        $reg['email'],
        $reg['phone'],
        $reg['college_name'],
        $reg['transaction_id'],
        $reg['registration_date']
    ]);
}

// Close the output stream
fclose($output);
exit;