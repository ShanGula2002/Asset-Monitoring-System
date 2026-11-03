<?php
$host     = "localhost";      
$db_user  = "root";           
$db_pass  = "";              
$db_name  = "school_assets"; 

$conn = new mysqli($host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}


$conn->set_charset("utf8mb4");
date_default_timezone_set("Asia/Manila");
$conn->query("SET time_zone = '+08:00'");

?>
