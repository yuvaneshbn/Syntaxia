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

// Get total registrations
$totalQuery = $db->query("SELECT COUNT(*) as total FROM registrations");
$totalRegistrations = $totalQuery->fetch(PDO::FETCH_ASSOC)['total'];

// Get registrations by event type
$eventTypeQuery = $db->query("SELECT event_type, COUNT(*) as count FROM registrations GROUP BY event_type");
$eventTypeData = $eventTypeQuery->fetchAll(PDO::FETCH_ASSOC);

// Get registrations by event name
$eventNameQuery = $db->query("SELECT event_name, COUNT(*) as count FROM registrations GROUP BY event_name");
$eventNameData = $eventNameQuery->fetchAll(PDO::FETCH_ASSOC);

// Get registrations by college
$collegeQuery = $db->query("SELECT college_name, COUNT(*) as count FROM registrations GROUP BY college_name ORDER BY count DESC LIMIT 10");
$collegeData = $collegeQuery->fetchAll(PDO::FETCH_ASSOC);

// Get total revenue
$revenueQuery = $db->query("SELECT SUM(event_price) as total_revenue FROM registrations");
$totalRevenue = $revenueQuery->fetch(PDO::FETCH_ASSOC)['total_revenue'];

// Get daily registrations for the last 30 days
$dailyQuery = $db->query("SELECT DATE(registration_date) as date, COUNT(*) as count 
                          FROM registrations 
                          WHERE registration_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
                          GROUP BY DATE(registration_date) 
                          ORDER BY date");
$dailyData = $dailyQuery->fetchAll(PDO::FETCH_ASSOC);

// Get recent registrations
$recentQuery = $db->query("SELECT * FROM registrations ORDER BY registration_date DESC LIMIT 10");
$recentRegistrations = $recentQuery->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Registration Analytics</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="admin_logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1 class="mb-4">Registration Analytics Dashboard</h1>
        
        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h5 class="card-title">Total Registrations</h5>
                        <h2 class="card-text"><?php echo $totalRegistrations; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h5 class="card-title">Total Revenue</h5>
                        <h2 class="card-text">₹<?php echo number_format($totalRevenue, 2); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <h5 class="card-title">Event Types</h5>
                        <h2 class="card-text"><?php echo count($eventTypeData); ?></h2>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        Registrations by Event Type
                    </div>
                    <div class="card-body">
                        <canvas id="eventTypeChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        Registrations by Event Name
                    </div>
                    <div class="card-body">
                        <canvas id="eventNameChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        Daily Registrations (Last 30 Days)
                    </div>
                    <div class="card-body">
                        <canvas id="dailyRegistrationsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mb-4">
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        Recent Registrations
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Event</th>
                                        <th>College</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentRegistrations as $reg): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($reg['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($reg['event_name']); ?></td>
                                        <td><?php echo htmlspecialchars($reg['college_name']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($reg['registration_date'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- View All Registrations Button -->
        <!-- View All Registrations Button -->
<div class="row mb-4">
    <div class="col-12">
        <a href="admin_registrations.php" class="btn btn-primary">View All Registrations</a>
        <a href="event_specific_report.php" class="btn btn-success">Event-Specific Report</a>
        <a href="event_revenue_report.php" class="btn btn-info">Event Revenue Report</a>
        <a href="admin_events.php" class="btn btn-warning">Manage Events</a>

    </div>
</div>
    </div>

    <script>
        // Event Type Chart
        const eventTypeCtx = document.getElementById('eventTypeChart').getContext('2d');
        const eventTypeChart = new Chart(eventTypeCtx, {
            type: 'pie',
            data: {
                labels: [<?php echo implode(', ', array_map(function($item) { return '"' . $item['event_type'] . '"'; }, $eventTypeData)); ?>],
                datasets: [{
                    data: [<?php echo implode(', ', array_map(function($item) { return $item['count']; }, $eventTypeData)); ?>],
                    backgroundColor: [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40'
                    ]
                }]
            }
        });
        
        // Event Name Chart
        const eventNameCtx = document.getElementById('eventNameChart').getContext('2d');
        const eventNameChart = new Chart(eventNameCtx, {
            type: 'bar',
            data: {
                labels: [<?php echo implode(', ', array_map(function($item) { return '"' . $item['event_name'] . '"'; }, $eventNameData)); ?>],
                datasets: [{
                    label: 'Registrations',
                    data: [<?php echo implode(', ', array_map(function($item) { return $item['count']; }, $eventNameData)); ?>],
                    backgroundColor: '#36A2EB'
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        
        // Daily Registrations Chart
        const dailyCtx = document.getElementById('dailyRegistrationsChart').getContext('2d');
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
            type: 'horizontalBar',
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>