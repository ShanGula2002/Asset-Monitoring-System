<?php
require 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $_POST['full_name'];
    $username  = $_POST['username'];
    $role      = $_POST['role'];
    
    // Security: Encrypt the password
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Prepare SQL to prevent SQL Injection
    $stmt = $conn->prepare("INSERT INTO users (username, password, role, full_name) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $password, $role, $full_name);

    if ($stmt->execute()) {
        echo "<script>alert('Registration Successful!'); window.location='index.php';</script>";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>