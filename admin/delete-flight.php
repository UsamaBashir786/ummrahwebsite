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

// Begin a transaction to ensure data integrity
$conn->begin_transaction();

try {
  // Check if flight exists and get its details
  $check_sql = "SELECT id, flight_number FROM flights WHERE id = ?";
  $check_stmt = $conn->prepare($check_sql);
  $check_stmt->bind_param("i", $flight_id);
  $check_stmt->execute();
  $check_result = $check_stmt->get_result();

  if ($check_result->num_rows === 0) {
    throw new Exception("Flight not found or already deleted");
  }

  // Fetch flight details for the success message
  $flight_details = $check_result->fetch_assoc();
  $check_stmt->close();

  // Delete associated flight bookings
  $delete_bookings_sql = "DELETE FROM flight_bookings WHERE flight_id = ?";
  $delete_bookings_stmt = $conn->prepare($delete_bookings_sql);
  $delete_bookings_stmt->bind_param("i", $flight_id);
  $delete_bookings_stmt->execute();
  $bookings_deleted = $delete_bookings_stmt->affected_rows;
  $delete_bookings_stmt->close();

  // Delete the flight
  $delete_flight_sql = "DELETE FROM flights WHERE id = ?";
  $delete_flight_stmt = $conn->prepare($delete_flight_sql);
  $delete_flight_stmt->bind_param("i", $flight_id);
  $delete_flight_stmt->execute();
  $flight_deleted = $delete_flight_stmt->affected_rows;
  $delete_flight_stmt->close();

  // Commit the transaction
  $conn->commit();

  // Prepare success message
  $success_message = "Flight #{$flight_details['flight_number']} deleted successfully.";
  if ($bookings_deleted > 0) {
    $success_message .= " {$bookings_deleted} associated booking(s) also deleted.";
  }

  // Set success message
  $_SESSION['success'] = $success_message;

} catch (Exception $e) {
  // Rollback the transaction in case of any error
  $conn->rollback();
  
  // Set error message
  $_SESSION['error'] = "Error deleting flight: " . $e->getMessage();
}

// Close the connection
$conn->close();

// Redirect back to view flights page
header('Location: view-flights.php');
exit;