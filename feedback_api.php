<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");

require_once 'db.php'; // reuse your DB credentials

try {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['sender_number'], $data['message'], $data['rating'])) {
        http_response_code(400);
        echo json_encode(["error" => "Missing required fields"]);
        exit;
    }

    $senderFull = $data['sender_number'];
    $senderParts = explode('@', $senderFull);
    $sender = $senderParts[0];  // Extract phone number part only

    $message = $data['message'];
    $rating = (int) $data['rating'];

 
    $stmt = $conn->prepare("INSERT INTO feedback (sender_number, message, rating) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $sender, $message, $rating);

    $stmt->execute();

    echo json_encode(["message" => "Feedback saved successfully"]);

    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Server error", "details" => $e->getMessage()]);
}
