<?php
// Set headers to allow POST method
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Origin: *");

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo "Error: Only POST method is allowed";
    exit;
}

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

// Create table if it doesn't exist
$query = "CREATE TABLE IF NOT EXISTS registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_name VARCHAR(255),
    event_type VARCHAR(255),
    event_price INT,
    full_name VARCHAR(255),
    email VARCHAR(255),
    phone VARCHAR(50),
    college_name VARCHAR(255),
    transaction_id VARCHAR(255),
    screenshot_path VARCHAR(255),
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$db->exec($query);

// Process form submission
$eventName = $_POST['eventName'] ?? '';
$eventType = $_POST['eventType'] ?? '';
$eventPrice = $_POST['eventPrice'] ?? 0;
$fullName = $_POST['fullName'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$collegeName = $_POST['collegeName'] ?? '';
$transactionId = $_POST['transactionId'] ?? '';

// Debug information - comment out in production
error_log("POST data received: " . print_r($_POST, true));
error_log("FILES data received: " . print_r($_FILES, true));

// Handle file upload
$screenshotPath = '';
if (isset($_FILES['transactionScreenshot']) && $_FILES['transactionScreenshot']['error'] === 0) {
    $uploadDir = 'uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    $fileName = time() . '_' . basename($_FILES['transactionScreenshot']['name']);
    $targetPath = $uploadDir . $fileName;
    if (move_uploaded_file($_FILES['transactionScreenshot']['tmp_name'], $targetPath)) {
        $screenshotPath = $targetPath;
    } else {
        error_log("Failed to move uploaded file: " . $_FILES['transactionScreenshot']['error']);
    }
}

try {
    $stmt = $db->prepare("INSERT INTO registrations 
        (event_name, event_type, event_price, full_name, email, phone, college_name, transaction_id, screenshot_path) 
        VALUES (:event_name, :event_type, :event_price, :full_name, :email, :phone, :college_name, :transaction_id, :screenshot_path)");
    
    $stmt->bindParam(':event_name', $eventName);
    $stmt->bindParam(':event_type', $eventType);
    $stmt->bindParam(':event_price', $eventPrice, PDO::PARAM_INT);
    $stmt->bindParam(':full_name', $fullName);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':phone', $phone);
    $stmt->bindParam(':college_name', $collegeName);
    $stmt->bindParam(':transaction_id', $transactionId);
    $stmt->bindParam(':screenshot_path', $screenshotPath);
    
    $stmt->execute();
    
    // Redirect with success message
    header('Location: register.html?status=success');
    exit;
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    header('Location: register.html?status=error&message=' . urlencode($e->getMessage()));
    exit;
}
