<?php
// feedback-api.php

// Allow cross-origin and accept JSON
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");

// Get JSON input
$data = json_decode(file_get_contents("php://input"), true);

// Validate
if (!isset($data['sender_number'], $data['message'], $data['rating'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing required fields"]);
    exit;
}

$sender = $data['sender_number'];
$message = $data['message'];
$rating = (int) $data['rating'];

// Connect to DB
$host = "localhost";
$dbname = "mrejesho";
$username = "root";
$password = ""; // default for XAMPP/WAMP

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

// Insert into DB
$stmt = $conn->prepare("INSERT INTO feedback (sender_number, message, rating) VALUES (?, ?, ?)");
$stmt->bind_param("ssi", $sender, $message, $rating);

if ($stmt->execute()) {
    echo json_encode(["message" => "Feedback saved successfully"]);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Failed to insert feedback"]);
}

$stmt->close();
$conn->close();
