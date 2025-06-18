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
$db   = 'mrejesho';

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
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/styles.css">
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
                <h5 class="text-gray-800 mb-0">Dashboard Overview</h5>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="container-fluid">
            <!-- Page Heading -->
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
                <a href="feedback.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                    <i class="fas fa-comments fa-sm text-white-50"></i> View All Feedback
                </a>
            </div>

            <!-- Content Row -->
            <div class="row">
                <!-- Total Feedback Card -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card dashboard-card h-100">
                        <div class="card-body">
                            <div class="dashboard-card-icon">
                                <i class="fas fa-comments"></i>
                            </div>
                            <div class="dashboard-card-text">
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $total_feedback ?></div>
                                <div class="small">Total Feedback</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Average Rating Card -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card dashboard-card h-100">
                        <div class="card-body">
                            <div class="dashboard-card-icon">
                                <i class="fas fa-star"></i>
                            </div>
                            <div class="dashboard-card-text">
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?= number_format($avg_rating, 1) ?>/5
                                </div>
                                <div class="small">Average Rating</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Positive Feedback Card -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card dashboard-card h-100">
                        <div class="card-body">
                            <div class="dashboard-card-icon">
                                <i class="fas fa-thumbs-up"></i>
                            </div>
                            <div class="dashboard-card-text">
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?= $rating_distribution[4] + $rating_distribution[5] ?>
                                </div>
                                <div class="small">Positive Feedback (4-5 stars)</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Negative Feedback Card -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card dashboard-card h-100">
                        <div class="card-body">
                            <div class="dashboard-card-icon">
                                <i class="fas fa-thumbs-down"></i>
                            </div>
                            <div class="dashboard-card-text">
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?= $rating_distribution[1] + $rating_distribution[2] ?>
                                </div>
                                <div class="small">Negative Feedback (1-2 stars)</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row">
                <!-- Rating Distribution Chart -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-white">Rating Distribution</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="ratingChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Daily Feedback Chart -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-white">Feedback Last 7 Days</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="dailyChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Feedback -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-white">Recent Feedback</h6>
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
                                            $rating_class = 'rating-' . (int)$row['rating'];
                                        ?>
                                            <tr>
                                                <td><?= $counter++ ?></td>
                                                <td><?= htmlspecialchars($row['sender_number']) ?></td>
                                                <td><?= htmlspecialchars(substr($row['message'], 0, 50)) . (strlen($row['message']) > 50 ? '...' : '') ?></td>
                                                <td class="rating-cell <?= $rating_class ?>">
                                                    <?= str_repeat('★', (int)$row['rating']) ?>
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
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script>
        // Toggle sidebar on mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
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
                        '#5cb85c',
                        '#28a745'
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
                    backgroundColor: 'rgba(78, 115, 223, 0.05)',
                    borderColor: 'rgba(78, 115, 223, 1)',
                    pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgba(78, 115, 223, 1)',
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