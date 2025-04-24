<?php
require_once '../config/db.php'; // Include your database connection

// Set JSON response header
// header('Content-Type: application/json');

$response = ['success' => false, 'error' => '', 'bookings_deleted' => 0];

try {
  // Validate POST data
  if (!isset($_POST['delete_route'], $_POST['route_id'], $_POST['service_type'])) {
    throw new Exception('Invalid request parameters.');
  }

  $route_id = (int)$_POST['route_id'];
  $service_type = $conn->real_escape_string($_POST['service_type']);

  // Validate service type
  if (!in_array($service_type, ['taxi', 'rentacar'])) {
    throw new Exception('Invalid service type.');
  }

  // Start transaction to ensure atomicity
  $conn->begin_transaction();

  // Count associated bookings
  $stmt = $conn->prepare("SELECT COUNT(*) as booking_count FROM transportation_bookings WHERE route_id = ? AND transport_type = ?");
  $stmt->bind_param("is", $route_id, $service_type);
  $stmt->execute();
  $result = $stmt->get_result();
  $booking_count = $result->fetch_assoc()['booking_count'];
  $stmt->close();

  // Delete associated bookings
  $stmt = $conn->prepare("DELETE FROM transportation_bookings WHERE route_id = ? AND transport_type = ?");
  $stmt->bind_param("is", $route_id, $service_type);
  $stmt->execute();
  $stmt->close();

  // Delete the route
  $table = ($service_type === 'taxi') ? 'taxi_routes' : 'rentacar_routes';
  $stmt = $conn->prepare("DELETE FROM $table WHERE id = ?");
  $stmt->bind_param("i", $route_id);
  $stmt->execute();
  $route_deleted = $stmt->affected_rows > 0;
  $stmt->close();

  if ($route_deleted) {
    // Commit transaction
    $conn->commit();
    $response['success'] = true;
    $response['bookings_deleted'] = $booking_count;
  } else {
    throw new Exception('Route not found or already deleted.');
  }
} catch (Exception $e) {
  // Rollback transaction on error
  $conn->rollback();
  $response['error'] = $e->getMessage();
}

// Output JSON response
echo json_encode($response);
exit;
