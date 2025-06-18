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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Dashboard | Mrejesho Bot</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 CSS -->
    <link href="node_modules/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                <span class="text-gray-800 mb-0 fw-bold">Organization Profile </span>
                <h5 class=""></h5>
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
                                <div class="bg-light  text-muted text-center">
                                <p> Type the command `node index.js` to start the bot. </p>

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


    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script>
        // Toggle sidebar on mobile
        document.getElementById('sidebarToggle').addEventListener('click', function () {
            document.querySelector('.sidebar').classList.toggle('toggled');
            document.getElementById('content').classList.toggle('toggled');
        });
    </script>
</body>

</html>