<?php
session_start();


if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'mrejesho_admin', 'P@$$w0rd', 'mrejesho');

// Initialize filters
$filters = [
    'rating' => $_GET['rating'] ?? null,
    'start_date' => $_GET['start_date'] ?? null,
    'end_date' => $_GET['end_date'] ?? null,
    'quick_filter' => $_GET['quick_filter'] ?? null
];

// Build query with filters
$query = "SELECT * FROM feedback WHERE 1=1";
$count_query = "SELECT COUNT(*) AS total FROM feedback WHERE 1=1";

if ($filters['rating']) {
    $query .= " AND rating = " . (int) $filters['rating'];
    $count_query .= " AND rating = " . (int) $filters['rating'];
}

if ($filters['start_date']) {
    $query .= " AND DATE(created_at) >= '" . $conn->real_escape_string($filters['start_date']) . "'";
    $count_query .= " AND DATE(created_at) >= '" . $conn->real_escape_string($filters['start_date']) . "'";
}

if ($filters['end_date']) {
    $query .= " AND DATE(created_at) <= '" . $conn->real_escape_string($filters['end_date']) . "'";
    $count_query .= " AND DATE(created_at) <= '" . $conn->real_escape_string($filters['end_date']) . "'";
}

// Apply quick filters
if ($filters['quick_filter']) {
    $today = date('Y-m-d');
    switch ($filters['quick_filter']) {
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

// Get total count
$total_rows = $conn->query($count_query)->fetch_assoc()['total'];

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$total_pages = ceil($total_rows / $limit);

// Final query with pagination
$query .= " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$result = $conn->query($query);

// Export functionality
if (isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="feedback_export_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Sender', 'Message', 'Rating', 'Date']);

    $export_query = str_replace("LIMIT $limit OFFSET $offset", "", $query);
    $export_result = $conn->query($export_query);

    while ($row = $export_result->fetch_assoc()) {
        fputcsv($output, [
            $row['id'],
            $row['sender_number'],
            $row['message'],
            $row['rating'],
            $row['created_at']
        ]);
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
    <title>Feedback | Mrejesho Bot</title>
    <!-- Favicon -->
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <!-- Bootstrap 5 CSS (local) -->
    <link href="node_modules/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- <link href="assets/css/bootstrap.min.css" rel="stylesheet"> -->
    <!-- Font Awesome (local) -->
    <!-- <link href="assets/css/fontawesome.min.css" rel="stylesheet"> -->
    <link href="node_modules/@fortawesome/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="assets/css/solid.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/styles.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #f8f9fc;
            --accent-color: #dddfeb;
            --text-color: #858796;
            --dark-color: #5a5c69;
        }

        body {
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: var(--secondary-color);
            color: var(--dark-color);
            overflow-x: hidden;
        }

        .sidebar {
            width: 250px;
            min-height: 100vh;
            background: linear-gradient(180deg, var(--primary-color) 0%, #224abe 100%);
            color: white;
            position: fixed;
            transition: all 0.3s;
            z-index: 1000;
        }

        .sidebar-brand {
            font-size: 1.2rem;
            font-weight: 800;
            padding: 1.5rem 1rem;
            text-align: center;
            letter-spacing: 0.05rem;
            z-index: 1;
        }

        .sidebar-brand i {
            font-size: 2rem;
            display: block;
        }

        .sidebar-divider {
            border-top: 1px solid rgba(255, 255, 255, 0.15);
            margin: 0 1rem 1rem;
        }

        .nav-item {
            position: relative;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 1rem;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05rem;
            transition: all 0.3s;
        }

        .nav-link:hover {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }

        .nav-link i {
            margin-right: 0.5rem;
            font-size: 0.85rem;
        }

        .nav-link.active {
            color: white;
        }

        .nav-link.active:before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background-color: white;
        }

        #content {
            margin-left: 250px;
            width: calc(100% - 250px);
            min-height: 100vh;
            transition: all 0.3s;
        }

        .topbar {
            height: 4.375rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            background-color: white;
        }

        .sidebar.toggled {
            margin-left: -250px;
        }

        #content.toggled {
            width: 100%;
            margin-left: 0;
        }

        .card {
            border: none;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            margin-bottom: 1.5rem;
        }

        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            padding: 1rem 1.35rem;
            font-weight: 600;
            color: var(--dark-color);
        }

        .card-header i {
            color: var(--primary-color);
        }

        .welcome-header {
            font-size: 1.75rem;
            font-weight: 600;
            color: var(--dark-color);
        }

        .table-responsive {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            margin-bottom: 1rem;
            color: #212529;
        }

        .table th {
            background-color: var(--primary-color);
            color: white;
            vertical-align: middle;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.075);
        }

        .pagination {
            margin-top: 1rem;
        }

        .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .page-link {
            color: var(--primary-color);
        }

        .footer {
            position: relative;
            bottom: 0;
            width: 100%;
        }

        .rating-1 {
            color: #dc3545;
        }

        /* Red for 1 */
        .rating-2 {
            color: #fd7e14;
        }

        /* Orange for 2 */
        .rating-3 {
            color: #ffc107;
        }

        /* Yellow for 3 */
        .rating-4 {
            color: #20c997;
        }

        /* Teal for 4 */
        .rating-5 {
            color: #28a745;
        }

        /* Green for 5-10 */

        @media (max-width: 768px) {
            .sidebar {
                margin-left: -250px;
            }

            #content {
                width: 100%;
                margin-left: 0;
            }

            .sidebar.toggled {
                margin-left: 0;
            }

            #content.toggled {
                margin-left: 250px;
                width: calc(100% - 250px);
            }
        }

        /* Loader Styles */
        .loader-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .loader {
            width: 48px;
            height: 48px;
            border: 5px solid var(--primary-color);
            border-bottom-color: transparent;
            border-radius: 50%;
            display: inline-block;
            box-sizing: border-box;
            animation: rotation 1s linear infinite;
        }

        @keyframes rotation {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <!-- Loader Overlay -->
    <div class="loader-overlay" id="loader">
        <span class="loader"></span>
    </div>
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
            <!-- <li class="nav-item">
                <a class="nav-link" href="users.php">
                    <i class="fas fa-fw fa-users"></i>
                    <span>User Management</span>
                </a>
            </li> -->
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
                <span class="text-gray-800 mb-0 fw-bold">Feedback Management</span>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="container-fluid">
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?= $_SESSION['message_type'] ?> alert-dismissible fade show">
                    <?= $_SESSION['message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
                ?>
            <?php endif; ?>

            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="welcome-header">
                    Welcome back, <?= htmlspecialchars($_SESSION['username']) ?>
                </h1>
                <a href="feedback.php?export=1<?= http_build_query($filters) ?>" class="btn btn-success">
                    <i class="fas fa-file-export mr-2"></i> Export to CSV
                </a>
            </div>

            <!-- Filter Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-filter mr-2"></i> <span>Filter Feedback</span>
                </div>
                <div class="card-body">
                    <form method="GET" action="feedback.php">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Rating</label>
                                <select class="form-select" name="rating">
                                    <option value="">All Ratings</option>
                                    <?php for ($i = 1; $i <= 10; $i++): ?>
                                        <option value="<?= $i ?>" <?= $filters['rating'] == $i ? 'selected' : '' ?>>
                                            <?= str_repeat('★', $i) ?> (<?= $i ?>/10)
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">From Date</label>
                                <input type="date" class="form-control" name="start_date"
                                    value="<?= $filters['start_date'] ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">To Date</label>
                                <input type="date" class="form-control" name="end_date"
                                    value="<?= $filters['end_date'] ?>">
                            </div>
                            <div class="col-md-3 d-flex align-items-end gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search mr-2"></i> Apply
                                </button>
                                <a href="feedback.php" class="btn btn-secondary">
                                    <i class="fas fa-times mr-2"></i> Clear
                                </a>
                            </div>
                        </div>
                    </form>

                    <!-- Quick Filters -->
                    <div class="mt-3">
                        <h6 class="mb-2">Quick Filters:</h6>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="feedback.php?quick_filter=today"
                                class="btn btn-sm btn-outline-primary <?= $filters['quick_filter'] == 'today' ? 'active' : '' ?>">
                                <i class="fas fa-calendar-day mr-2"></i> Today
                            </a>
                            <a href="feedback.php?quick_filter=week"
                                class="btn btn-sm btn-outline-primary <?= $filters['quick_filter'] == 'week' ? 'active' : '' ?>">
                                <i class="fas fa-calendar-week mr-2"></i> This Week
                            </a>
                            <a href="feedback.php?quick_filter=month"
                                class="btn btn-sm btn-outline-primary <?= $filters['quick_filter'] == 'month' ? 'active' : '' ?>">
                                <i class="fas fa-calendar-alt mr-2"></i> This Month
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Feedback Table -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-comments mr-2"></i> <span>Recent Feedback</span>
                    <span class="float-end">
                        Showing <?= ($offset + 1) ?>-<?= min($offset + $limit, $total_rows) ?> of <?= $total_rows ?>
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
                                <?php if ($result->num_rows > 0): ?>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= $row['id'] ?></td>
                                            <td><?= htmlspecialchars($row['sender_number']) ?></td>
                                            <td><?= htmlspecialchars($row['message']) ?></td>
                                            <td class="rating-<?= min($row['rating'], 5) ?>">
                                                <?= str_repeat('★', $row['rating']) ?> (<?= $row['rating'] ?>/10)
                                            </td>
                                            <td><?= date('M j, Y g:i A', strtotime($row['created_at'])) ?></td>
                                            <td>
                                                <a href="delete_feedback.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger"
                                                    onclick="return confirm('Are you sure you want to delete this feedback?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4">No feedback found with current filters</td>
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
                                    <a class="page-link" href="?page=<?= $page - 1 ?>&<?= http_build_query($filters) ?>">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&<?= http_build_query($filters) ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page + 1 ?>&<?= http_build_query($filters) ?>">
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

    <!-- Bootstrap 5 JS Bundle with Popper (local) -->
    <!-- <script src="node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script> -->
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <!-- Font Awesome JS (local) -->
    <script src="assets/js/fontawesome.min.js"></script>
    <!-- Custom JS -->
    <script>
        // Hide loader when page is fully loaded
        window.addEventListener('load', function () {
            setTimeout(function () {
                document.getElementById('loader').style.display = 'none';
            }, 500); // Add slight delay for smoother transition
        });

        // Toggle sidebar on mobile
        document.getElementById('sidebarToggle').addEventListener('click', function () {
            document.querySelector('.sidebar').classList.toggle('toggled');
            document.getElementById('content').classList.toggle('toggled');
        });
    </script>
</body>

</html>