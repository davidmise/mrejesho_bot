<?php
$conn = new mysqli("localhost", "root", "", "mrejesho");

if ($conn->connect_error) {
    die("DB connection failed");
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
