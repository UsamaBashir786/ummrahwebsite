<?php
// db.php - MySQLi database connection for ummrah_v1

$host = 'localhost';
$username = 'root';
$password = ''; // No password
$database = 'latestummrah';

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8 (optional but recommended)
$conn->set_charset("utf8");

// You can now use $conn for your database queries
?>