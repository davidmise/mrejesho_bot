<?php
$host = 'localhost';
$user = 'mrejesho_admin';
$pass = 'P@$$w0rd';
$db   = 'mrejesho';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Insert default admin user (password = admin123)
$username = 'admin';
$password = password_hash('admin123', PASSWORD_BCRYPT);

$conn->query("INSERT IGNORE INTO users (username, password) VALUES ('$username', '$password')");

// Insert test organization
$orgName = 'Pamoja';
$secretCode = 'hello lssc';
$qrCodeUrl = 'http://wa.me/255686768052?text=' . urlencode($secretCode); // WhatsApp URL with code

$conn->query("INSERT IGNORE INTO organizations (name, secret_code, qr_code_url) VALUES ('$orgName', '$secretCode', '$qrCodeUrl')");

echo "✅ Seed complete.\n";
$conn->close();
?>
