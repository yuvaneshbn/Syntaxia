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

// Default date range (last 30 days)
$defaultStartDate = date('Y-m-d', strtotime('-30 days'));
$defaultEndDate = date('Y-m-d');

// Get date range from form submission
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : $defaultStartDate;
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : $defaultEndDate;

// Add one day to end date for inclusive results
$endDateQuery = date('Y-m-d', strtotime($endDate . ' +1 day'));

// Get event revenue data with date filter
$query = $db->prepare("SELECT 
    event_name, 
    event_type,
    COUNT(*) as registrations, 
    SUM(event_price) as revenue,
    MIN(registration_date) as first_registration,
    MAX(registration_date) as last_registration
    FROM registrations 
    WHERE registration_date >= :start_date AND registration_date < :end_date
    GROUP BY event_name, event_type
    ORDER BY revenue DESC");

$query->bindParam(':start_date', $startDate);
$query->bindParam(':end_date', $endDateQuery);
$query->execute();
$eventRevenueData = $query->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$totalRegistrations = 0;
$totalRevenue = 0;

foreach ($eventRevenueData as $event) {
    $totalRegistrations += $event['registrations'];
    $totalRevenue += $event['revenue'];
}

// Get daily revenue data for chart
$dailyQuery = $db->prepare("SELECT 
    DATE(registration_date) as date, 
    SUM(event_price) as revenue
    FROM registrations 
    WHERE registration_date >= :start_date AND registration_date < :end_date
    GROUP BY DATE(registration_date)
    ORDER BY date");

$dailyQuery->bindParam(':start_date', $startDate);
$dailyQuery->bindParam(':end_date', $endDateQuery);
$dailyQuery->execute();
$dailyRevenueData = $dailyQuery->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Revenue Report</title>
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
        <h1 class="mb-4">Event Revenue Report</h1>
        
        <!-- Date Range Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="get" class="row g-3">
                    <div class="col-md-4">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $startDate; ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $endDate; ?>">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Apply Filter</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h5 class="card-title">Total Registrations</h5>
                        <h2 class="card-text"><?php echo $totalRegistrations; ?></h2>
                        <p class="card-text">From <?php echo date('M d, Y', strtotime($startDate)); ?> to <?php echo date('M d, Y', strtotime($endDate)); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h5 class="card-title">Total Revenue</h5>
                        <h2 class="card-text">₹<?php echo number_format($totalRevenue, 2); ?></h2>
                        <p class="card-text">From <?php echo date('M d, Y', strtotime($startDate)); ?> to <?php echo date('M d, Y', strtotime($endDate)); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Daily Revenue Chart -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Daily Revenue</h5>
            </div>
            <div class="card-body">
                <canvas id="dailyRevenueChart"></canvas>
            </div>
        </div>
        
        <!-- Event Revenue Table -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Event-wise Revenue</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Event Name</th>
                                <th>Event Type</th>
                                <th>Registrations</th>
                                <th>Revenue (₹)</th>
                                <th>Average Price (₹)</th>
                                <th>% of Total Revenue</th>
                                <th>First Registration</th>
                                <th>Last Registration</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($eventRevenueData) > 0): ?>
                                <?php foreach ($eventRevenueData as $event): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($event['event_name']); ?></td>
                                        <td><?php echo htmlspecialchars($event['event_type']); ?></td>
                                        <td><?php echo $event['registrations']; ?></td>
                                        <td>₹<?php echo number_format($event['revenue'], 2); ?></td>
                                        <td>₹<?php echo number_format($event['revenue'] / $event['registrations'], 2); ?></td>
                                        <td><?php echo number_format(($event['revenue'] / $totalRevenue) * 100, 2); ?>%</td>
                                        <td><?php echo date('M d, Y', strtotime($event['first_registration'])); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($event['last_registration'])); ?></td>
                                        <td>
                                            <a href="event_specific_report.php" class="btn btn-sm btn-info" onclick="document.getElementById('event_<?php echo md5($event['event_name']); ?>').submit(); return false;">View Details</a>
                                            <form id="event_<?php echo md5($event['event_name']); ?>" action="event_specific_report.php" method="post" style="display: none;">
                                                <input type="hidden" name="event" value="<?php echo htmlspecialchars($event['event_name']); ?>">
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center">No data found for the selected date range</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Export Button -->
        <div class="mb-4">
            <a href="export_revenue_report.php?start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>" class="btn btn-success">Export to CSV</a>
        </div>
    </div>

    <script>
        // Daily Revenue Chart
        const dailyRevenueCtx = document.getElementById('dailyRevenueChart').getContext('2d');
        const dailyRevenueChart = new Chart(dailyRevenueCtx, {
            type: 'line',
            data: {
                labels: [<?php echo implode(', ', array_map(function($item) { return '"' . $item['date'] . '"'; }, $dailyRevenueData)); ?>],
                datasets: [{
                    label: 'Revenue (₹)',
                    data: [<?php echo implode(', ', array_map(function($item) { return $item['revenue']; }, $dailyRevenueData)); ?>],
                    borderColor: '#4BC0C0',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1,
                    fill: true
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
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>