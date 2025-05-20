<?php
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

// Get all event names for dropdown
$eventsQuery = $db->query("SELECT DISTINCT event_name FROM registrations ORDER BY event_name");
$events = $eventsQuery->fetchAll(PDO::FETCH_COLUMN);

// Initialize variables
$selectedEvent = '';
$eventData = null;
$registrations = [];
$dailyData = [];
$collegeData = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['event'])) {
    $selectedEvent = $_POST['event'];
    
    // Get event summary data
    $eventQuery = $db->prepare("SELECT 
        COUNT(*) as total_registrations,
        SUM(event_price) as total_revenue,
        AVG(event_price) as avg_price,
        MIN(registration_date) as first_registration,
        MAX(registration_date) as last_registration
        FROM registrations WHERE event_name = :event");
    $eventQuery->bindParam(':event', $selectedEvent);
    $eventQuery->execute();
    $eventData = $eventQuery->fetch(PDO::FETCH_ASSOC);
    
    // Get registrations for this event
    $registrationsQuery = $db->prepare("SELECT * FROM registrations WHERE event_name = :event ORDER BY registration_date DESC");
    $registrationsQuery->bindParam(':event', $selectedEvent);
    $registrationsQuery->execute();
    $registrations = $registrationsQuery->fetchAll(PDO::FETCH_ASSOC);
    
    // Get daily registrations for this event
    $dailyQuery = $db->prepare("SELECT DATE(registration_date) as date, COUNT(*) as count 
                              FROM registrations 
                              WHERE event_name = :event
                              GROUP BY DATE(registration_date) 
                              ORDER BY date");
    $dailyQuery->bindParam(':event', $selectedEvent);
    $dailyQuery->execute();
    $dailyData = $dailyQuery->fetchAll(PDO::FETCH_ASSOC);
    
    // Get college breakdown for this event
    $collegeQuery = $db->prepare("SELECT college_name, COUNT(*) as count 
                                FROM registrations 
                                WHERE event_name = :event
                                GROUP BY college_name 
                                ORDER BY count DESC 
                                LIMIT 10");
    $collegeQuery->bindParam(':event', $selectedEvent);
    $collegeQuery->execute();
    $collegeData = $collegeQuery->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event-Specific Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="admin_dashboard.php">Registration Analytics</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="admin_dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_registrations.php">All Registrations</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1 class="mb-4">Event-Specific Report</h1>
        
        <!-- Event Selection Form -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="post" class="row g-3">
                    <div class="col-md-8">
                        <select name="event" class="form-select" required>
                            <option value="">Select an Event</option>
                            <?php foreach ($events as $event): ?>
                                <option value="<?php echo htmlspecialchars($event); ?>" <?php echo $selectedEvent === $event ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($event); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100">Generate Report</button>
                    </div>
                </form>
            </div>
        </div>
        
        <?php if ($eventData): ?>
        <!-- Event Summary -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Summary for: <?php echo htmlspecialchars($selectedEvent); ?></h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h6 class="card-title">Total Registrations</h6>
                                <h3><?php echo $eventData['total_registrations']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h6 class="card-title">Total Revenue</h6>
                                <h3>₹<?php echo number_format($eventData['total_revenue'], 2); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h6 class="card-title">Average Price</h6>
                                <h3>₹<?php echo number_format($eventData['avg_price'], 2); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h6 class="card-title">Registration Period</h6>
                                <p><?php echo date('M d, Y', strtotime($eventData['first_registration'])); ?> to <?php echo date('M d, Y', strtotime($eventData['last_registration'])); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Charts Row -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        Daily Registrations
                    </div>
                    <div class="card-body">
                        <canvas id="dailyChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        Top Colleges
                    </div>
                    <div class="card-body">
                        <canvas id="collegeChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Registrations Table -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">All Registrations for: <?php echo htmlspecialchars($selectedEvent); ?></h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>College</th>
                                <th>Price</th>
                                <th>Transaction ID</th>
                                <th>Date</th>
                                <th>Receipt</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($registrations) > 0): ?>
                                <?php foreach ($registrations as $reg): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($reg['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($reg['email']); ?></td>
                                        <td><?php echo htmlspecialchars($reg['phone']); ?></td>
                                        <td><?php echo htmlspecialchars($reg['college_name']); ?></td>
                                        <td>₹<?php echo $reg['event_price']; ?></td>
                                        <td><?php echo htmlspecialchars($reg['transaction_id']); ?></td>
                                        <td><?php echo date('M d, Y H:i', strtotime($reg['registration_date'])); ?></td>
                                        <td>
                                            <?php if (!empty($reg['screenshot_path'])): ?>
                                                <a href="<?php echo htmlspecialchars($reg['screenshot_path']); ?>" class="btn btn-sm btn-info" target="_blank">View</a>
                                            <?php else: ?>
                                                <span class="badge bg-warning">None</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">No registrations found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Export Button -->
        <div class="mb-4">
            <a href="export_event_registrations.php?event=<?php echo urlencode($selectedEvent); ?>" class="btn btn-success">Export to CSV</a>
        </div>
        
        <script>
            // Daily Registrations Chart
            const dailyCtx = document.getElementById('dailyChart').getContext('2d');
            const dailyChart = new Chart(dailyCtx, {
                type: 'line',
                data: {
                    labels: [<?php echo implode(', ', array_map(function($item) { return '"' . $item['date'] . '"'; }, $dailyData)); ?>],
                    datasets: [{
                        label: 'Registrations',
                        data: [<?php echo implode(', ', array_map(function($item) { return $item['count']; }, $dailyData)); ?>],
                        borderColor: '#4BC0C0',
                        tension: 0.1,
                        fill: false
                    }]
                }
            });
            
            // College Chart
            const collegeCtx = document.getElementById('collegeChart').getContext('2d');
            const collegeChart = new Chart(collegeCtx, {
                type: 'bar',
                data: {
                    labels: [<?php echo implode(', ', array_map(function($item) { return '"' . $item['college_name'] . '"'; }, $collegeData)); ?>],
                    datasets: [{
                        label: 'Registrations',
                        data: [<?php echo implode(', ', array_map(function($item) { return $item['count']; }, $collegeData)); ?>],
                        backgroundColor: '#9966FF'
                    }]
                },
                options: {
                    indexAxis: 'y',
                    scales: {
                        x: {
                            beginAtZero: true
                        }
                    }
                }
            });
        </script>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>