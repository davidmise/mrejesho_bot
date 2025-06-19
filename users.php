<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

include('db.php');

// Initialize variables
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? 0;
$message = '';
$messageType = '';

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $full_name = trim($_POST['full_name']);
    $organization_id = intval($_POST['organization_id']);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($action == 'create') {
        // Validate inputs
        if (empty($username) || empty($full_name) || empty($password)) {
            $message = "All fields are required";
            $messageType = "danger";
        } elseif ($password !== $confirm_password) {
            $message = "Passwords do not match";
            $messageType = "danger";
        } else {
            // Check if username exists
            $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $check->bind_param("s", $username);
            $check->execute();
            $check->store_result();
            
            if ($check->num_rows > 0) {
                $message = "Username already exists";
                $messageType = "danger";
            } else {
                // Create user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (username, full_name, password, organization_id) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("sssi", $username, $full_name, $hashed_password, $organization_id);
                
                if ($stmt->execute()) {
                    $message = "User created successfully";
                    $messageType = "success";
                    $action = 'list'; // Return to list view
                } else {
                    $message = "Error creating user: " . $conn->error;
                    $messageType = "danger";
                }
            }
        }
    } elseif ($action == 'update') {
        // Update user
        $stmt = $conn->prepare("UPDATE users SET username = ?, full_name = ?, organization_id = ? WHERE id = ?");
        $stmt->bind_param("ssii", $username, $full_name, $organization_id, $id);
        
        if ($stmt->execute()) {
            // If password was provided, update it
            if (!empty($password)) {
                if ($password === $confirm_password) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt2 = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt2->bind_param("si", $hashed_password, $id);
                    $stmt2->execute();
                } else {
                    $message = "Passwords do not match";
                    $messageType = "danger";
                }
            }
            
            if (empty($message)) {
                $message = "User updated successfully";
                $messageType = "success";
                $action = 'list'; // Return to list view
            }
        } else {
            $message = "Error updating user: " . $conn->error;
            $messageType = "danger";
        }
    }
} elseif ($action == 'delete') {
    // Delete user (can't delete yourself)
    if ($id != $_SESSION['user_id']) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $message = "User deleted successfully";
            $messageType = "success";
        } else {
            $message = "Error deleting user: " . $conn->error;
            $messageType = "danger";
        }
    } else {
        $message = "You cannot delete your own account";
        $messageType = "danger";
    }
    $action = 'list';
}

// Fetch user data for edit/view
$user = null;
if (($action == 'edit' || $action == 'view') && $id > 0) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user) {
        $action = 'list';
        $message = "User not found";
        $messageType = "danger";
    }
}

// Fetch all users for list view
$users = [];
$organizations = [];
if ($action == 'list') {
    $users = $conn->query("SELECT u.*, o.name as organization_name FROM users u LEFT JOIN organizations o ON u.organization_id = o.id")->fetch_all(MYSQLI_ASSOC);
    $organizations = $conn->query("SELECT * FROM organizations")->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | Mrejesho Bot</title>
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <!-- Bootstrap 5 CSS (local) -->
    <link href="node_modules/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome (local) -->
    <link href="node_modules/@fortawesome/fontawesome-free/css/all.min.css" rel="stylesheet">
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

        .table-responsive {
            overflow-x: auto;
        }

        .table th {
            background-color: var(--primary-color);
            color: white;
        }

        .password-toggle {
            cursor: pointer;
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
             <li class="nav-item">
                <a class="nav-link" href="users.php">
                    <i class="fas fa-fw fa-users"></i>
                    <span>User Management</span>
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
                <span class="text-gray-800 mb-0 fw-bold">User Management</span>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="container-fluid">
            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
                    <?= $message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($action == 'list'): ?>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0">User Management</h1>
                    <a href="users.php?action=create" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Add New User
                    </a>
                </div>

                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-users me-2"></i>User List
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Full Name</th>
                                        <th>Organization</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?= $user['id'] ?></td>
                                            <td><?= htmlspecialchars($user['username']) ?></td>
                                            <td><?= htmlspecialchars($user['full_name']) ?></td>
                                            <td><?= htmlspecialchars($user['organization_name'] ?? 'N/A') ?></td>
                                            <td>
                                                <a href="users.php?action=edit&id=<?= $user['id'] ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                    <a href="users.php?action=delete&id=<?= $user['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this user?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            <?php elseif (in_array($action, ['create', 'edit', 'view'])): ?>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0">
                        <?= ucfirst($action) ?> User
                        <?php if ($action == 'edit' && isset($user)): ?>
                            <small class="text-muted"><?= htmlspecialchars($user['username']) ?></small>
                        <?php endif; ?>
                    </h1>
                    <a href="users.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to List
                    </a>
                </div>

                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-user me-2"></i>User Details
                    </div>
                    <div class="card-body">
                        <form method="POST" action="users.php?action=<?= $action ?><?= isset($user) ? '&id='.$user['id'] : '' ?>">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?= isset($user) ? htmlspecialchars($user['username']) : '' ?>" >
                                </div>
                                <div class="col-md-6">
                                    <label for="full_name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" 
                                           value="<?= isset($user) ? htmlspecialchars($user['full_name']) : '' ?>" >
                                </div>
                            </div>

                            <!-- <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="organization_id" class="form-label">Organization</label>
                                    <select class="form-select" id="organization_id" name="organization_id" required>
                                        <option value="">Select Organization</option>
                                        <?php foreach ($organizations as $org): ?>
                                            <option value="<?= $org['id'] ?>" 
                                                <?= (isset($user) && $user['organization_id'] == $org['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($org['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div> -->

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="password" class="form-label">
                                        <?= $action == 'edit' ? 'New ' : '' ?>Password
                                        <?php if ($action == 'edit'): ?>
                                            <small class="text-muted">(Leave blank to keep current password)</small>
                                        <?php endif; ?>
                                    </label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="password" name="password"
                                               <?= $action == 'create' ? 'required' : '' ?>>
                                        <span class="input-group-text password-toggle" onclick="togglePassword('password')">
                                            <i class="fas fa-eye"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="confirm_password" class="form-label">Confirm Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                               <?= $action == 'create' ? 'required' : '' ?>>
                                        <span class="input-group-text password-toggle" onclick="togglePassword('confirm_password')">
                                            <i class="fas fa-eye"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>
                                    <?= $action == 'edit' ? 'Update' : 'Create' ?> User
                                </button>
                                <a href="users.php" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle with Popper (local) -->
    <script src="node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Font Awesome JS (local) -->
    <script src="node_modules/@fortawesome/fontawesome-free/js/all.min.js"></script>
    <script>
        // Toggle sidebar on mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('toggled');
            document.getElementById('content').classList.toggle('toggled');
        });

        // Toggle password visibility
        function togglePassword(fieldId) {
            const input = document.getElementById(fieldId);
            const icon = input.nextElementSibling.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>
</body>
</html>