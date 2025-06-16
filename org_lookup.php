<?php
header("Content-Type: application/json");
require_once 'db.php'; // reuse your DB credentials

$code = $_GET['code'] ?? '';
if (!$code) {
    echo json_encode(['error' => 'Code missing']);
    exit;
}

$conn = new mysqli($host, $user, $pass, $db);
$stmt = $conn->prepare("SELECT id, name FROM organizations WHERE secret_code = ?");
$stmt->bind_param("s", $code);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode(['organization' => $row]);
} else {
    echo json_encode(['error' => 'Invalid code']);
}

$stmt->close();
$conn->close();
?>
