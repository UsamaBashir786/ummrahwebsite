<?php
require_once '../config/db.php';

// Start admin session
session_name('admin_session');
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
  header('Location: login.php');
  exit;
}

// Check if flight ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
  $_SESSION['error'] = "No flight ID provided for deletion";
  header('Location: view-flights.php');
  exit;
}

$flight_id = intval($_GET['id']);

// Verify the flight exists before deletion
$check_sql = "SELECT id FROM flights WHERE id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i", $flight_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
  $_SESSION['error'] = "Flight not found or already deleted";
  header('Location: view-flights.php');
  exit;
}

// Prepare and execute the delete query
$delete_sql = "DELETE FROM flights WHERE id = ?";
$delete_stmt = $conn->prepare($delete_sql);
$delete_stmt->bind_param("i", $flight_id);

if ($delete_stmt->execute()) {
  if ($delete_stmt->affected_rows > 0) {
    $_SESSION['success'] = "Flight successfully deleted";
  } else {
    $_SESSION['error'] = "No changes made - flight may have already been deleted";
  }
} else {
  $_SESSION['error'] = "Error deleting flight: " . $conn->error;
}

$delete_stmt->close();
$conn->close();

// Redirect back to view flights page
header('Location: view-flights.php');
exit;
