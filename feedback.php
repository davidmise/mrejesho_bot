<?php
session_start(); // Start session

// Redirect to login page if not authenticated
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection
$host = 'localhost';
$user = 'mrejesho_admin';
$pass = 'P@$$w0rd';
$db   = 'mrejesho';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: (" . $conn->connect_errno . ") " . $conn->connect_error);
}

// Initialize filter variables
$rating_filter = isset($_GET['rating']) ? (int)$_GET['rating'] : null;
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;
$quick_filter = isset($_GET['quick_filter']) ? $_GET['quick_filter'] : null;

// Build base query
$query = "SELECT * FROM feedback WHERE 1=1";
$count_query = "SELECT COUNT(*) AS total FROM feedback WHERE 1=1";

// Apply filters
if ($rating_filter) {
    $query .= " AND rating = $rating_filter";
    $count_query .= " AND rating = $rating_filter";
}

if ($start_date) {
    $query .= " AND DATE(created_at) >= '$start_date'";
    $count_query .= " AND DATE(created_at) >= '$start_date'";
}

if ($end_date) {
    $query .= " AND DATE(created_at) <= '$end_date'";
    $count_query .= " AND DATE(created_at) <= '$end_date'";
}

// Apply quick filters
if ($quick_filter) {
    $today = date('Y-m-d');
    switch ($quick_filter) {
        case 'today':
            $query .= " AND DATE(created_at) = '$today'";
            $count_query .= " AND DATE(created_at) = '$today'";
            break;
        case 'week':
            $query .= " AND YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)";
            $count_query .= " AND YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)";
            break;
        case 'month':
            $query .= " AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())";
            $count_query .= " AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())";
            break;
    }
}

// Complete queries
$query .= " ORDER BY created_at DESC";
$count_result = $conn->query($count_query);
$total_rows = $count_result->fetch_assoc()['total'];

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$total_pages = ceil($total_rows / $limit);

$query .= " LIMIT $limit OFFSET $offset";
$result = $conn->query($query);

// Function to export to CSV
if (isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="feedback_export_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Header row
    fputcsv($output, array('ID', 'Sender Number', 'Message', 'Rating', 'Created At'));
    
    // Data rows
    $export_query = str_replace("LIMIT $limit OFFSET $offset", "", $query);
    $export_result = $conn->query($export_query);
    while ($row = $export_result->fetch_assoc()) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback List | Mrejesho Bot</title>
    <!-- Bootstrap 5 CSS -->
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"> -->
    <link href="node_modules/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/styles.css">
    <style>
        
/* Star Rating Colors (1-10 scale) */
.rating-1 { color: #ff0000; } /* Red */
.rating-2 { color: #ff3300; }
.rating-3 { color: #ff6600; }
.rating-4 { color: #ff9900; }
.rating-5 { color: #ffcc00; } /* Yellow */
.rating-6 { color: #ccff00; }
.rating-7 { color: #99ff00; }
.rating-8 { color: #66ff00; }
.rating-9 { color: #33ff00; }
.rating-10 { color: #ffd700; } /* Gold */

/* Filter Cards */
.filter-card {
    border-left: 0.25rem solid var(--primary-color);
    margin-bottom: 1rem;
    transition: var(--transition);
}

.filter-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-sm);
}

.filter-card .card-body {
    padding: 1rem;
}

.filter-card .form-control, 
.filter-card .form-select {
    margin-bottom: 0.5rem;
}

/* Quick Filter Buttons */
.quick-filter-btn {
    margin-right: 0.5rem;
    margin-bottom: 0.5rem;
}

/* Export Button */
.btn-export {
    background-color: #28a745;
    color: white;
    border: none;
}

.btn-export:hover {
    background-color: #218838;
    color: white;
}

/* Clear Filters Button */
.btn-clear {
    background-color: #6c757d;
    color: white;
    border: none;
}

.btn-clear:hover {
    background-color: #5a6268;
    color: white;
}
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="sidebar-brand text-center py-4">
            <i class="fas fa-robot mb-2"></i> <span>Mrejesho Bot</span>
        </div>
        <div class="sidebar-divider"></div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="index.php">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="feedback.php">
                    <i class="fas fa-fw fa-comments"></i>
                    <span>Feedback</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="profile.php">
                    <i class="fas fa-fw fa-user"></i>
                    <span>Profile</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-fw fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Content Wrapper -->
    <div id="content">
        <!-- Topbar -->
        <nav class="navbar topbar mb-4 static-top shadow">
            <div class="container-fluid">
                <button class="btn btn-link d-md-none rounded-circle mr-3" id="sidebarToggle">
                    <i class="fa fa-bars"></i>
                </button>
                <span class="text-gray-800 mb-0 fw-bold"> Feedback Management</span>
                <h5 class=""></h5>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="container-fluid">
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 text-gray-800">User Feedback</h1>
                <div>
                    <a href="feedback.php?export=1<?= isset($_GET['rating']) ? '&rating='.$_GET['rating'] : '' ?><?= isset($_GET['start_date']) ? '&start_date='.$_GET['start_date'] : '' ?><?= isset($_GET['end_date']) ? '&end_date='.$_GET['end_date'] : '' ?><?= isset($_GET['quick_filter']) ? '&quick_filter='.$_GET['quick_filter'] : '' ?>" class="btn btn-export">
                        <i class="fas fa-file-export"></i> Export to CSV
                    </a>
                </div>
            </div>

            <!-- Filter Cards -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card filter-card">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-filter"></i> Filter Feedback</h5>
                            <form method="GET" action="feedback.php">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label for="rating" class="form-label">Rating</label>
                                        <select class="form-select" id="rating" name="rating">
                                            <option value="">All Ratings</option>
                                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                                <option value="<?= $i ?>" <?= isset($_GET['rating']) && $_GET['rating'] == $i ? 'selected' : '' ?>>
                                                    <?= str_repeat('★', $i) ?> (<?= $i ?>/10)
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="start_date" class="form-label">From Date</label>
                                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?= $start_date ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="end_date" class="form-label">To Date</label>
                                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?= $end_date ?>">
                                    </div>
                                    <div class="col-md-3 d-flex align-items-end">
                                        <button type="submit" class="btn btn-primary me-2">
                                            <i class="fas fa-search"></i> Apply Filters
                                        </button>
                                        <a href="feedback.php" class="btn btn-clear">
                                            <i class="fas fa-times"></i> Clear
                                        </a>
                                    </div>
                                </div>
                            </form>
                            
                            <!-- Quick Filters -->
                            <div class="mt-3">
                                <h6 class="mb-2">Quick Filters:</h6>
                                <a href="feedback.php?quick_filter=today" class="btn btn-sm btn-outline-primary quick-filter-btn <?= $quick_filter == 'today' ? 'active' : '' ?>">
                                    <i class="fas fa-calendar-day"></i> Today
                                </a>
                                <a href="feedback.php?quick_filter=week" class="btn btn-sm btn-outline-primary quick-filter-btn <?= $quick_filter == 'week' ? 'active' : '' ?>">
                                    <i class="fas fa-calendar-week"></i> This Week
                                </a>
                                <a href="feedback.php?quick_filter=month" class="btn btn-sm btn-outline-primary quick-filter-btn <?= $quick_filter == 'month' ? 'active' : '' ?>">
                                    <i class="fas fa-calendar-alt"></i> This Month
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <i class="fas fa-comments mr-2"></i> <span> Recent Feedback </span>
                    <span class="float-end">
                        Showing <?= ($offset + 1) ?> to <?= min($offset + $limit, $total_rows) ?> of <?= $total_rows ?> records
                    </span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Sender</th>
                                    <th>Message</th>
                                    <th>Rating</th>
                                    <th>Time Received</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $counter = ($page - 1) * $limit + 1;
                                while ($row = $result->fetch_assoc()): 
                                    $rating_class = 'rating-' . (int)$row['rating'];
                                ?>
                                    <tr>
                                        <td><?= $counter++ ?></td>
                                        <td><?= htmlspecialchars($row['sender_number']) ?></td>
                                        <td><?= htmlspecialchars($row['message']) ?></td>
                                        <td class="rating-cell <?= $rating_class ?>">
                                            <?= str_repeat('★', (int)$row['rating']) ?> (<?= $row['rating'] ?>/10)
                                        </td>
                                        <td><?= date('M j, Y g:i A', strtotime($row['created_at'])) ?></td>
                                        <td>
                                            <a href="delete.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this feedback?');">
                                                <i class="fas fa-trash"></i> 
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                                <?php if ($result->num_rows == 0): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No feedback found with the current filters</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page - 1 ?><?= $rating_filter ? '&rating='.$rating_filter : '' ?><?= $start_date ? '&start_date='.$start_date : '' ?><?= $end_date ? '&end_date='.$end_date : '' ?><?= $quick_filter ? '&quick_filter='.$quick_filter : '' ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?><?= $rating_filter ? '&rating='.$rating_filter : '' ?><?= $start_date ? '&start_date='.$start_date : '' ?><?= $end_date ? '&end_date='.$end_date : '' ?><?= $quick_filter ? '&quick_filter='.$quick_filter : '' ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page + 1 ?><?= $rating_filter ? '&rating='.$rating_filter : '' ?><?= $start_date ? '&start_date='.$start_date : '' ?><?= $end_date ? '&end_date='.$end_date : '' ?><?= $quick_filter ? '&quick_filter='.$quick_filter : '' ?>" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- Footer -->
<footer class="footer mt-5 py-3 bg-light text-center border-top">
    <div class="container">
        <span class="text-muted">
            &copy; <?= date('Y') ?> Mrejesho Bot. All rights reserved.
        </span>
        <br>
        <small class="text-muted">
            Developed by <a href="#" target="_blank" class="text-decoration-none">David M. Mushi</a>
        </small>
    </div>
</footer>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script> -->
    <!-- Custom JS -->
     <script src="node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Toggle sidebar on mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('toggled');
            document.getElementById('content').classList.toggle('toggled');
        });
    </script>
</body>
</html>