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

// Handle form submission for adding/editing events
$message = '';
$editEvent = null;

// Delete event
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $stmt = $db->prepare("DELETE FROM events WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Event deleted successfully!";
    } catch (PDOException $e) {
        $message = "Error deleting event: " . $e->getMessage();
    }
}

// Edit event - fetch data
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    try {
        $stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
        $stmt->execute([$id]);
        $editEvent = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $message = "Error fetching event: " . $e->getMessage();
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data
    $title = $_POST['title'] ?? '';
    $type = $_POST['type'] ?? '';
    $typeName = $_POST['type_name'] ?? '';
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $location = $_POST['location'] ?? '';
    $description = $_POST['description'] ?? '';
    $imageUrl = $_POST['image_url'] ?? '';
    $attendees = $_POST['attendees'] ?? '';
    $refreshments = $_POST['refreshments'] ?? '';
    $prizes = $_POST['prizes'] ?? '';
    $teamSize = $_POST['team_size'] ?? '';
    $eligibility = $_POST['eligibility'] ?? '';
    $requirements = $_POST['requirements'] ?? '';
    $price = $_POST['price'] ?? '';
    $seatsLeft = (int)($_POST['seats_left'] ?? 0);
    
    // Validate required fields
    if (empty($title) || empty($type) || empty($typeName) || empty($date)) {
        $message = "Please fill in all required fields.";
    } else {
        try {
            // Check if we're updating or inserting
            if (isset($_POST['event_id']) && !empty($_POST['event_id'])) {
                // Update existing event
                $stmt = $db->prepare("UPDATE events SET 
                    title = ?, type = ?, type_name = ?, date = ?, time = ?, 
                    location = ?, description = ?, image_url = ?, attendees = ?, 
                    refreshments = ?, prizes = ?, team_size = ?, eligibility = ?, 
                    requirements = ?, price = ?, seats_left = ? 
                    WHERE id = ?");
                
                $stmt->execute([
                    $title, $type, $typeName, $date, $time, $location, $description, 
                    $imageUrl, $attendees, $refreshments, $prizes, $teamSize, 
                    $eligibility, $requirements, $price, $seatsLeft, $_POST['event_id']
                ]);
                
                $message = "Event updated successfully!";
            } else {
                // Insert new event
                $stmt = $db->prepare("INSERT INTO events (
                    title, type, type_name, date, time, location, description, 
                    image_url, attendees, refreshments, prizes, team_size, 
                    eligibility, requirements, price, seats_left
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->execute([
                    $title, $type, $typeName, $date, $time, $location, $description, 
                    $imageUrl, $attendees, $refreshments, $prizes, $teamSize, 
                    $eligibility, $requirements, $price, $seatsLeft
                ]);
                
                $message = "Event added successfully!";
            }
            
            // Clear edit event after successful submission
            $editEvent = null;
        } catch (PDOException $e) {
            $message = "Error saving event: " . $e->getMessage();
        }
    }
}

// Fetch all events
$events = $db->query("SELECT * FROM events ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .form-section {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .event-card {
            transition: transform 0.3s;
        }
        .event-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
    </style>
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
                        <a class="nav-link" href="admin_registrations.php">Registrations</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="admin_events.php">Events</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1 class="mb-4">Manage Events</h1>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-info"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <!-- Event Form -->
        <div class="form-section">
            <h2><?php echo $editEvent ? 'Edit Event' : 'Add New Event'; ?></h2>
            <form method="post" class="row g-3">
                <?php if ($editEvent): ?>
                    <input type="hidden" name="event_id" value="<?php echo $editEvent['id']; ?>">
                <?php endif; ?>
                
                <div class="col-md-6">
                    <label for="title" class="form-label">Event Title*</label>
                    <input type="text" class="form-control" id="title" name="title" required 
                           value="<?php echo $editEvent ? htmlspecialchars($editEvent['title']) : ''; ?>">
                </div>
                
                <div class="col-md-3">
                    <label for="type" class="form-label">Event Type*</label>
                    <select class="form-select" id="type" name="type" required>
                        <option value="">Select Type</option>
                        <option value="type1" <?php echo ($editEvent && $editEvent['type'] == 'type1') ? 'selected' : ''; ?>>Syntaxia (type1)</option>
                        <option value="type2" <?php echo ($editEvent && $editEvent['type'] == 'type2') ? 'selected' : ''; ?>>Technophite (type2)</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="type_name" class="form-label">Type Name*</label>
                    <input type="text" class="form-control" id="type_name" name="type_name" required
                           value="<?php echo $editEvent ? htmlspecialchars($editEvent['type_name']) : ''; ?>">
                    <small class="text-muted">E.g., Syntaxia or Technophite</small>
                </div>
                
                <div class="col-md-6">
                    <label for="date" class="form-label">Event Date*</label>
                    <input type="text" class="form-control" id="date" name="date" required
                           value="<?php echo $editEvent ? htmlspecialchars($editEvent['date']) : ''; ?>">
                    <small class="text-muted">E.g., "2025-04-25" (yyyy-mm-dd)</small>
                </div>
                
                <div class="col-md-6">
                    <label for="time" class="form-label">Event Time</label>
                    <input type="text" class="form-control" id="time" name="time"
                           value="<?php echo $editEvent ? htmlspecialchars($editEvent['time']) : ''; ?>">
                    <small class="text-muted">E.g., 10:00 AM - 5:00 PM</small>
                </div>
                
                <div class="col-md-12">
                    <label for="location" class="form-label">Location</label>
                    <input type="text" class="form-control" id="location" name="location"
                           value="<?php echo $editEvent ? htmlspecialchars($editEvent['location']) : ''; ?>">
                </div>
                
                <div class="col-md-12">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="4"><?php echo $editEvent ? htmlspecialchars($editEvent['description']) : ''; ?></textarea>
                </div>
                
                <div class="col-md-12">
                    <label for="image_url" class="form-label">Image URL</label>
                    <input type="text" class="form-control" id="image_url" name="image_url"
                           value="<?php echo $editEvent ? htmlspecialchars($editEvent['image_url']) : ''; ?>">
                </div>
                
                <div class="col-md-6">
                    <label for="attendees" class="form-label">Expected Attendees</label>
                    <input type="text" class="form-control" id="attendees" name="attendees"
                           value="<?php echo $editEvent ? htmlspecialchars($editEvent['attendees']) : ''; ?>">
                </div>
                
                <div class="col-md-6">
                    <label for="refreshments" class="form-label">Refreshments</label>
                    <input type="text" class="form-control" id="refreshments" name="refreshments"
                           value="<?php echo $editEvent ? htmlspecialchars($editEvent['refreshments']) : ''; ?>">
                </div>
                
                <div class="col-md-6">
                    <label for="prizes" class="form-label">Prizes</label>
                    <input type="text" class="form-control" id="prizes" name="prizes"
                           value="<?php echo $editEvent ? htmlspecialchars($editEvent['prizes']) : ''; ?>">
                </div>
                
                <div class="col-md-6">
                    <label for="team_size" class="form-label">Team Size</label>
                    <input type="text" class="form-control" id="team_size" name="team_size"
                           value="<?php echo $editEvent ? htmlspecialchars($editEvent['team_size']) : ''; ?>">
                </div>
                
                <div class="col-md-6">
                    <label for="eligibility" class="form-label">Eligibility</label>
                    <input type="text" class="form-control" id="eligibility" name="eligibility"
                           value="<?php echo $editEvent ? htmlspecialchars($editEvent['eligibility']) : ''; ?>">
                </div>
                
                <div class="col-md-6">
                    <label for="requirements" class="form-label">Requirements</label>
                    <input type="text" class="form-control" id="requirements" name="requirements"
                           value="<?php echo $editEvent ? htmlspecialchars($editEvent['requirements']) : ''; ?>">
                </div>
                
                <div class="col-md-6">
                    <label for="price" class="form-label">Price</label>
                    <input type="text" class="form-control" id="price" name="price"
                           value="<?php echo $editEvent ? htmlspecialchars($editEvent['price']) : ''; ?>">
                    <small class="text-muted">E.g., ₹1000 per team</small>
                </div>
                
                <div class="col-md-6">
                    <label for="seats_left" class="form-label">Seats Left</label>
                    <input type="number" class="form-control" id="seats_left" name="seats_left" min="0"
                           value="<?php echo $editEvent ? htmlspecialchars($editEvent['seats_left']) : '0'; ?>">
                </div>
                
                <div class="col-12">
                    <button type="submit" class="btn btn-primary"><?php echo $editEvent ? 'Update Event' : 'Add Event'; ?></button>
                    <?php if ($editEvent): ?>
                        <a href="admin_events.php" class="btn btn-secondary">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <!-- Events List -->
        <h2 class="mb-3">Current Events</h2>
        <div class="row">
            <?php if (empty($events)): ?>
                <div class="col-12">
                    <div class="alert alert-info">No events found. Add your first event using the form above.</div>
                </div>
            <?php else: ?>
                <?php foreach ($events as $event): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card event-card h-100">
                            <div class="card-header bg-<?php echo $event['type'] == 'type1' ? 'success' : 'primary'; ?> text-white">
                                <?php echo htmlspecialchars($event['type_name']); ?>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($event['title']); ?></h5>
                                <p class="card-text">
                                    <strong>Date:</strong> <?php echo htmlspecialchars($event['date']); ?><br>
                                    <strong>Location:</strong> <?php echo htmlspecialchars($event['location']); ?><br>
                                    <strong>Seats Left:</strong> <?php echo htmlspecialchars($event['seats_left']); ?>
                                </p>
                                <div class="d-flex justify-content-between mt-3">
                                    <a href="admin_events.php?edit=<?php echo $event['id']; ?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="admin_events.php?delete=<?php echo $event['id']; ?>" class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Are you sure you want to delete this event?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>