<?php
session_start();
include('db.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $newUsername = $_POST['username'];
    $name = $_POST['name'];
    $whatsapp = $_POST['whatsapp_number'];
    $secret = $_POST['secret_code'];
    $currentUsername = $_SESSION['username'];

    // Update username
    $stmtUser = $conn->prepare("UPDATE users SET username = ? WHERE username = ?");
    $stmtUser->bind_param("ss", $newUsername, $currentUsername);
    $stmtUser->execute();

    // Update session username
    $_SESSION['username'] = $newUsername;

    // Update organization details
    $stmtOrg = $conn->prepare("UPDATE organizations SET name = ?, whatsapp_number = ?, secret_code = ? WHERE id = 1");
    $stmtOrg->bind_param("sss", $name, $whatsapp, $secret);
    $stmtOrg->execute();

    $_SESSION['message'] = "Details updated successfully.";
    header("Location: profile.php");
    exit();
}
?>
