<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

// Database connection
$host = 'localhost';
$user = 'mrejesho_admin';
$pass = 'P@$$w0rd';
$db = 'mrejesho';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: (" . $conn->connect_errno . ") " . $conn->connect_error);
}

// Get feedback stats
$total_feedback = $conn->query("SELECT COUNT(*) as total FROM feedback")->fetch_assoc()['total'];
$avg_rating = $conn->query("SELECT AVG(rating) as avg FROM feedback WHERE rating > 0")->fetch_assoc()['avg'];
$recent_feedback = $conn->query("SELECT * FROM feedback ORDER BY created_at DESC LIMIT 5");

// Get rating distribution
$rating_distribution = [];
for ($i = 1; $i <= 5; $i++) {
    $count = $conn->query("SELECT COUNT(*) as count FROM feedback WHERE rating = $i")->fetch_assoc()['count'];
    $rating_distribution[$i] = $count;
}

// Get feedback by time (last 7 days)
$daily_feedback = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $count = $conn->query("SELECT COUNT(*) as count FROM feedback WHERE DATE(created_at) = '$date'")->fetch_assoc()['count'];
    $daily_feedback[$date] = $count;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Mrejesho Bot</title>
    <!-- Favicon -->
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <!-- Bootstrap 5 CSS (local) -->
    <link href="node_modules/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- <link href="assets/css/bootstrap.min.css" rel="stylesheet"> -->
    <!-- Font Awesome (local) -->
    <link href="node_modules/@fortawesome/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="assets/css/solid.min.css" rel="stylesheet">
    <!-- Chart.js (local) -->
    <script src="assets/js/chart.min.js"></script>
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

        .stats-card {
            transition: transform 0.3s;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .border-start-primary {
            border-left: 4px solid var(--primary-color) !important;
        }

        .border-start-success {
            border-left: 4px solid #1cc88a !important;
        }

        .border-start-info {
            border-left: 4px solid #36b9cc !important;
        }

        .border-start-warning {
            border-left: 4px solid #f6c23e !important;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .table th {
            background-color: var(--primary-color);
            color: white;
            vertical-align: middle;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.075);
        }

        .footer {
            position: relative;
            bottom: 0;
            width: 100%;
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
                <a class="nav-link active" href="index.php">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="feedback.php">
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
                <span class="text-gray-800 mb-0 fw-bold">Dashboard Overview</span>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="container-fluid">
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="welcome-header">
                    Welcome back, <?= htmlspecialchars($_SESSION['username']) ?>
                </h1>
                <a href="feedback.php" class="btn btn-primary">
                    <i class="fas fa-comments mr-2"></i> View All Feedback
                </a>
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <!-- Total Feedback -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-start-primary h-100 stats-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="me-3 text-primary">
                                    <i class="fas fa-comments fa-2x"></i>
                                </div>
                                <div>
                                    <div class="h5 mb-0 fw-bold"><?= $total_feedback ?></div>
                                    <small class="text-muted">Total Feedback</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Average Rating -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-start-success h-100 stats-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="me-3 text-success">
                                    <i class="fas fa-star fa-2x"></i>
                                </div>
                                <div>
                                    <div class="h5 mb-0 fw-bold"><?= number_format($avg_rating, 1) ?>/5</div>
                                    <small class="text-muted">Average Rating</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Positive Feedback -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-start-info h-100 stats-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="me-3 text-info">
                                    <i class="fas fa-thumbs-up fa-2x"></i>
                                </div>
                                <div>
                                    <div class="h5 mb-0 fw-bold">
                                        <?= $rating_distribution[4] + $rating_distribution[5] ?>
                                    </div>
                                    <small class="text-muted">Positive (4-5 stars)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Negative Feedback -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-start-warning h-100 stats-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="me-3 text-warning">
                                    <i class="fas fa-thumbs-down fa-2x"></i>
                                </div>
                                <div>
                                    <div class="h5 mb-0 fw-bold">
                                        <?= $rating_distribution[1] + $rating_distribution[2] ?>
                                    </div>
                                    <small class="text-muted">Negative (1-2 stars)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <!-- <div class="row mb-4"> -->
            <!-- Rating Distribution -->
            <!-- <div class="col-lg-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header bg-primary text-white">
                            <i class="fas fa-chart-bar mr-2"></i> <span>Rating Distribution</span>
                        </div>
                        <div class="card-body">
                            <canvas id="ratingChart" height="300"></canvas>
                        </div>
                    </div>
                </div> -->

            <!-- Daily Feedback -->
            <!-- <div class="col-lg-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header bg-primary text-white">
                            <i class="fas fa-chart-line mr-2"></i> <span>Feedback Last 7 Days</span>
                        </div>
                        <div class="card-body">
                            <canvas id="dailyChart" height="300"></canvas>
                        </div>
                    </div>
                </div> -->
        <!-- </div> -->

        <!-- Recent Feedback -->
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <i class="fas fa-comments mr-2"></i> <span>Recent Feedback</span>
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
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $counter = 1;
                            while ($row = $recent_feedback->fetch_assoc()):
                                $rating_class = [
                                    1 => 'text-danger',
                                    2 => 'text-warning',
                                    3 => 'text-info',
                                    4 => 'text-primary',
                                    5 => 'text-success'
                                ][$row['rating']] ?? 'text-muted';
                                ?>
                                <tr>
                                    <td><?= $counter++ ?></td>
                                    <td><?= htmlspecialchars($row['sender_number']) ?></td>
                                    <td><?= htmlspecialchars(substr($row['message'], 0, 50)) . (strlen($row['message']) > 50 ? '...' : '') ?>
                                    </td>
                                    <td class="<?= $rating_class ?>">
                                        <?= str_repeat('★', (int) $row['rating']) ?>
                                    </td>
                                    <td><?= date('M j, Y g:i A', strtotime($row['created_at'])) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
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
    <script src="node_modules/chart.js/dist/chart.min.js"></script>
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

        // Rating Distribution Chart
        const ratingCtx = document.getElementById('ratingChart').getContext('2d');
        const ratingChart = new Chart(ratingCtx, {
            type: 'bar',
            data: {
                labels: ['1 Star', '2 Stars', '3 Stars', '4 Stars', '5 Stars'],
                datasets: [{
                    label: 'Number of Ratings',
                    data: [
                        <?= $rating_distribution[1] ?>,
                        <?= $rating_distribution[2] ?>,
                        <?= $rating_distribution[3] ?>,
                        <?= $rating_distribution[4] ?>,
                        <?= $rating_distribution[5] ?>
                    ],
                    backgroundColor: [
                        '#dc3545',
                        '#fd7e14',
                        '#ffc107',
                        '#0d6efd',
                        '#198754'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Daily Feedback Chart
        const dailyCtx = document.getElementById('dailyChart').getContext('2d');
        const dailyChart = new Chart(dailyCtx, {
            type: 'line',
            data: {
                labels: [<?= "'" . implode("','", array_keys($daily_feedback)) . "'" ?>],
                datasets: [{
                    label: 'Feedback Count',
                    data: [<?= implode(',', array_values($daily_feedback)) ?>],
                    backgroundColor: 'rgba(13, 110, 253, 0.05)',
                    borderColor: 'rgba(13, 110, 253, 1)',
                    pointBackgroundColor: 'rgba(13, 110, 253, 1)',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgba(13, 110, 253, 1)',
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>

</html>