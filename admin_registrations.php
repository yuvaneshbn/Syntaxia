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

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$searchCondition = '';
$params = [];

if (!empty($search)) {
    $searchCondition = " WHERE full_name LIKE :search OR email LIKE :search OR college_name LIKE :search OR event_name LIKE :search";
    $params[':search'] = "%$search%";
}

// Get total count for pagination
$countQuery = $db->prepare("SELECT COUNT(*) FROM registrations" . $searchCondition);
if (!empty($params)) {
    foreach ($params as $key => $value) {
        $countQuery->bindValue($key, $value);
    }
}
$countQuery->execute();
$totalCount = $countQuery->fetchColumn();
$totalPages = ceil($totalCount / $perPage);

// Get registrations with pagination
$query = $db->prepare("SELECT * FROM registrations" . $searchCondition . " ORDER BY registration_date DESC LIMIT :offset, :perPage");
$query->bindValue(':offset', $offset, PDO::PARAM_INT);
$query->bindValue(':perPage', $perPage, PDO::PARAM_INT);

if (!empty($params)) {
    foreach ($params as $key => $value) {
        $query->bindValue($key, $value);
    }
}

$query->execute();
$registrations = $query->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Registrations</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
                        <a class="nav-link active" href="admin_registrations.php">All Registrations</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1 class="mb-4">All Registrations</h1>
        
        <!-- Search Form -->
        <div class="row mb-4">
            <div class="col-md-6">
                <form method="get" class="d-flex">
                    <input type="text" name="search" class="form-control me-2" placeholder="Search by name, email, college, event..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <?php if (!empty($search)): ?>
                        <a href="admin_registrations.php" class="btn btn-secondary ms-2">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
            <div class="col-md-6 text-end">
                <a href="export_registrations.php" class="btn btn-success">Export to CSV</a>
            </div>
        </div>
        
        <!-- Registrations Table -->
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>College</th>
                        <th>Event</th>
                        <th>Type</th>
                        <th>Price</th>
                        <th>Transaction ID</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($registrations) > 0): ?>
                        <?php foreach ($registrations as $reg): ?>
                            <tr>
                                <td><?php echo $reg['id']; ?></td>
                                <td><?php echo htmlspecialchars($reg['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($reg['email']); ?></td>
                                <td><?php echo htmlspecialchars($reg['phone']); ?></td>
                                <td><?php echo htmlspecialchars($reg['college_name']); ?></td>
                                <td><?php echo htmlspecialchars($reg['event_name']); ?></td>
                                <td><?php echo htmlspecialchars($reg['event_type']); ?></td>
                                <td>₹<?php echo $reg['event_price']; ?></td>
                                <td><?php echo htmlspecialchars($reg['transaction_id']); ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($reg['registration_date'])); ?></td>
                                <td>
                                    <?php if (!empty($reg['screenshot_path'])): ?>
                                        <a href="<?php echo htmlspecialchars($reg['screenshot_path']); ?>" class="btn btn-sm btn-info" target="_blank">View Receipt</a>
                                    <?php else: ?>
                                        <span class="badge bg-warning">No Receipt</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="11" class="text-center">No registrations found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Previous</a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Next</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>