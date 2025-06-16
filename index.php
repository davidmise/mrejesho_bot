<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$host = 'localhost';
$user = 'mrejesho_admin';
$pass = 'P@$$w0rd';
$db   = 'mrejesho';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: (" . $conn->connect_errno . ") " . $conn->connect_error);
}

$result = $conn->query("SELECT * FROM feedback ORDER BY created_at DESC");

?>

<!DOCTYPE html>
<html>
<head>
  <title>Feedback List</title>
  <style>
    table { border-collapse: collapse; width: 100%; margin-top: 20px; }
    th, td { border: 1px solid #aaa; padding: 8px; text-align: left; }
    th { background-color: #eee; }
  </style>
</head>
<body>
  <h1>📝 Feedback from Users</h1>
  <table>
    <tr>
      <th>ID</th>
      <th>Sender</th>
      <th>Message</th>
      <th>Rating</th>
      <th>Time</th>
    </tr>
 <?php while ($row = $result->fetch_assoc()): ?>
    <tr>
      <td><?= $row['id'] ?></td>
      <td><?= $row['sender_number'] ?></td>
      <td><?= $row['message'] ?></td>
      <td><?= $row['rating'] ?></td>
      <td><?= $row['created_at'] ?></td>
    </tr>
    <?php endwhile; ?>
  </table>
</body>
</html>
