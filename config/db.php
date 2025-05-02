<?php
$host = 'localhost';
$username = 'root';
$password = ''; 
$database = 'latestummrah';
// $host = 'localhost';
// $username = 'trending_ummrahuser';
// $password = 'tL~9+0~U#0,^'; 
// $database = 'trending_ummrah';
$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8");
?>