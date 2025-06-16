<?php
    $host = 'localhost';
    $user = 'mrejesho_admin';
    $pass = 'P@$$w0rd';
    $db   = 'mrejesho';

    $conn = new mysqli($host, $user, $pass, $db);
    
    if ($conn->connect_error) {
        die("Connection failed: (" . $conn->connect_errno . ") " . $conn->connect_error);
    }


?>