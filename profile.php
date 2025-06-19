<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'vendor/autoload.php';
include('db.php');

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;

$username = $_SESSION['username'];

// Fetch user and organization
$userStmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$userStmt->bind_param("s", $username);
$userStmt->execute();
$userResult = $userStmt->get_result();
$user = $userResult->fetch_assoc();

$orgStmt = $conn->prepare("SELECT * FROM organizations WHERE id = ?");
$orgStmt->bind_param("i", $user['organization_id']);
$orgStmt->execute();
$orgResult = $orgStmt->get_result();
$org = $orgResult->fetch_assoc();

// Generate QR code
$qrCodeUrl = '';
$qrCodeSvg = '';

if ($org && !empty($org['whatsapp_number']) && !empty($org['secret_code'])) {
    $qrCodeUrl = "https://wa.me/" . $org['whatsapp_number'] . "?text=" . urlencode($org['secret_code']);

    $qrResult = Builder::create()
        ->writer(new SvgWriter())
        ->data($qrCodeUrl)
        ->size(200)
        ->margin(10)
        ->build();

    $qrCodeSvg = $qrResult->getString();
    $encodedQr = base64_encode($qrCodeSvg);
}

// Fetch user data
$userStmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$userStmt->bind_param("s", $username);
$userStmt->execute();
$userResult = $userStmt->get_result();
$user = $userResult->fetch_assoc();

// Handle password change
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    if (password_verify($currentPassword, $user['password'])) {
        if ($newPassword === $confirmPassword) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
            $updateStmt->bind_param("ss", $hashedPassword, $username);

            if ($updateStmt->execute()) {
                $message = 'Password changed successfully!';
                $messageType = 'success';
            } else {
                $message = 'Error updating password. Please try again.';
                $messageType = 'danger';
            }
        } else {
            $message = 'New passwords do not match.';
            $messageType = 'danger';
        }
    } else {
        $message = 'Current password is incorrect.';
        $messageType = 'danger';
    }
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
    <!-- Custom CSS -->
    <!-- <link href="assets/css/styles.css" rel="stylesheet"> -->
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

        .qr-container {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 1rem;
            background-color: white;
            border-radius: 0.35rem;
            margin: 1rem 0;
        }

        .footer {
            position: relative;
            bottom: 0;
            width: 100%;
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
                <a class="nav-link " href="index.php">
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
                <a class="nav-link active" href="profile.php">
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
                <span class="text-gray-800 mb-0 fw-bold">Organization Profile</span>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="container-fluid">
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="welcome-header">
                    Welcome back, <?= htmlspecialchars($username) ?>
                </h1>
            </div>

            <?php if ($user && $org): ?>
                <div class="row">
                    <!-- Organization Info Card -->
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-building mr-2"></i> <span>Organization Information</span>
                            </div>
                            <div class="card-body">
                                <form action="update_organization.php" method="POST">
                                    <div class="mb-3">
                                        <label class="form-label">Username</label>
                                        <input type="text" class="form-control" name="username"
                                            value="<?= htmlspecialchars($user['username']) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Organization Name</label>
                                        <input type="text" class="form-control" name="name"
                                            value="<?= htmlspecialchars($org['name']) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">WhatsApp Number</label>
                                        <div class="input-group">
                                            <span class="input-group-text">+</span>
                                            <input type="text" class="form-control" name="whatsapp_number"
                                                value="<?= htmlspecialchars($org['whatsapp_number']) ?>">
                                        </div>
                                        <small class="text-muted">Include country code without '+' (e.g.,
                                            254712345678)</small>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Secret Code</label>
                                        <input type="text" class="form-control" name="secret_code"
                                            value="<?= htmlspecialchars($org['secret_code']) ?>" required>
                                        <small class="text-muted">This code will trigger your bot when sent via
                                            WhatsApp</small>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save mr-2"></i> Update
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-building mr-2"></i> <span>Organization Information</span>
                            </div>
                            <div class="card-body">
                                <form action="profile.php" method="POST">
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Current Password</label>
                                        <input type="password" class="form-control" id="current_password"
                                            name="current_password" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">New Password</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password"
                                            required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                                        <input type="password" class="form-control" id="confirm_password"
                                            name="confirm_password" required>
                                    </div>
                                    <button type="submit" name="change_password" class="btn btn-primary">
                                        <i class="fas fa-save mr-2"></i> Change Password
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- QR Code Cards -->
                    <div class="col-lg-6">
                        <?php if ($qrCodeSvg): ?>
                            <div class="card mb-4">
                                <div class="card-header bg-success text-white">
                                    <i class="fas fa-qrcode mr-2"></i> <span>Feedback QR Code</span>
                                </div>
                                <div class="card-body text-center">
                                    <p class="card-text">Scan this code to start a WhatsApp conversation that automatically
                                        sends your secret code.</p>
                                    <div class="qr-container">
                                        <?= $qrCodeSvg ?>
                                    </div>
                                    <div class="d-flex justify-content-center gap-2 mt-3">
                                        <a href="data:image/svg+xml;base64,<?= $encodedQr ?>" download="feedback_qr.svg"
                                            class="btn btn-success">
                                            <i class="fas fa-download mr-2"></i>Download SVG
                                        </a>
                                        <a href="<?= $qrCodeUrl ?>" target="_blank" class="btn btn-outline-success">
                                            <i class="fas fa-external-link-alt mr-2"></i>Open Link
                                        </a>
                                    </div>
                                    <div class="mt-3 p-3 bg-light rounded">
                                        <small class="text-muted">Scan URL:</small>
                                        <div class="text-truncate">
                                            <a href="<?= $qrCodeUrl ?>" target="_blank"><?= $qrCodeUrl ?></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php
                        $botQrPath = __DIR__ . '/auth_info/auth_info/latest_qr.svg';
                        $botQrSvg = file_exists($botQrPath) ? file_get_contents($botQrPath) : '';
                        ?>

                        <?php if (!empty($botQrSvg)): ?>
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <i class="fas fa-robot mr-2"></i> <span>Bot Connection QR Code</span>
                                </div>
                                <div class="card-body text-center">
                                    <p class="card-text">Scan this code with your WhatsApp mobile app to connect your bot
                                        account.</p>
                                    <div class="qr-containe" style="text-align:center;">
                                        <img src="auth_info/auth_info/latest_qr.svg" alt="QR Code Preview"
                                            style="max-width: 300px;" />
                                    </div>
                                    <a href="auth_info/auth_info/latest_qr.svg" download="bot_qr.svg" class="btn btn-info mt-3">
                                        <i class="fas fa-download mr-2"></i>Download SVG
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="card p-2">
                                <div class="bg-light text-muted text-center">
                                    <p>Type the command `node index.js` to start the bot.</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>


            <?php else: ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle mr-2"></i>Organization or user information not found.
                </div>
            <?php endif; ?>
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