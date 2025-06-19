<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

include('db.php');

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: feedback.php");
    exit();
}

$id = (int) $_GET['id'];

// Delete the feedback
$stmt = $conn->prepare("DELETE FROM feedback WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $_SESSION['message'] = "Feedback deleted successfully";
    $_SESSION['message_type'] = "success";
} else {
    $_SESSION['message'] = "Error deleting feedback: " . $conn->error;
    $_SESSION['message_type'] = "danger";
}

$stmt->close();
$conn->close();

// Redirect back to feedback page
header("Location: feedback.php");
exit();
?>