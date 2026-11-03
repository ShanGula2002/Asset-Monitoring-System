<?php
session_start();
require 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $selected_role = $_POST['role'];

    // 1. Prepare statement to find the user
    $stmt = $conn->prepare("SELECT user_id, username, password, role, full_name FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // 2. Verify password and check if the role matches the selection
        if (password_verify($password, $user['password'])) {
            if ($user['role'] === $selected_role) {
                
                // 3. Set Session Variables
                $_SESSION['user_id']   = $user['user_id'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role']      = $user['role'];

                // 4. Redirect based on role
                if ($user['role'] === 'admin') {
                    header("Location: admin/dashboard.php");
                } else {
                    header("Location: employee/dashboard.php");
                }
                exit();

            } else {
                echo "<script>alert('Invalid role selected for this account.'); window.location='index.php';</script>";
            }
        } else {
            echo "<script>alert('Incorrect password.'); window.location='index.php';</script>";
        }
    } else {
        echo "<script>alert('Username not found.'); window.location='index.php';</script>";
    }

    $stmt->close();
    $conn->close();
}
?>