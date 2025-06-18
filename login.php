<?php
session_start(); // Start the session

// Include database connection
include('db.php');

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the login credentials from the form
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Prepare SQL query to fetch user data based on username
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if user exists
    if ($result->num_rows > 0) {
        // Fetch user data
        $user = $result->fetch_assoc();

        // Verify the password
        if (password_verify($password, $user['password'])) {
            // Store user information in session
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['loggedin'] = true; // Mark as logged in

            // Redirect to profile page
            header("Location: profile.php");
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "No user found with that username.";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Mrejesho Bot</title>
  <link rel="icon" href="favicon.ico" type="image/x-icon">

    <!-- Bootstrap 5 CSS -->
    <link href="node_modules/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">

    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"> -->

</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="login-header">
                <h1>Mrejesho Bot</h1>
                <p class="text-muted">Admin Login</p>
            </div>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" name="username" id="username" required>
                </div>
                
                <div class="mb-3 position-relative">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" name="password" id="password" required>
                    <!-- <span class="password-toggle" onclick="togglePassword()">
                        👁️
                    </span> -->
                </div>
                
                <button type="submit" class="btn btn-primary btn-login">Login</button>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger mt-3">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle with Popper -->
     <script src="node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Password toggle script -->
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById("password");
            const toggleIcon = document.querySelector(".password-toggle");
            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                toggleIcon.textContent = "🙈";
            } else {
                passwordInput.type = "password";
                toggleIcon.textContent = "👁️";
            }
        }
    </script>
</body>
</html>
