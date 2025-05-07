<?php
// db.php - MySQLi database connection for ummrah_v1
$host = 'localhost';
$username = 'root';
// $password = 'tL~9+0~U#0,^';
$password = '';
$database = 'trending_ummrah';

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
  error_log("Database connection failed: " . $conn->connect_error);
  throw new Exception("Database connection failed.");
}

// Set charset to utf8
$conn->set_charset("utf8");

return $conn;
