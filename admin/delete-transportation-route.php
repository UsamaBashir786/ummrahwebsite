<?php
require_once '../config/db.php';
session_name('admin_session');
session_start();

// Set JSON header to ensure proper content type
header('Content-Type: application/json');

// Function to send error response
function sendErrorResponse($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false, 
        'error' => $message
    ]);
    exit;
}

// Check admin login
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    sendErrorResponse('Unauthorized access', 403);
}

function deleteTransportationRoute($conn, $route_id, $service_type) {
    // Start a transaction to ensure data integrity
    $conn->begin_transaction();

    try {
        // Variables to track deletion
        $bookings_deleted = 0;
        $route_deleted = false;

        // Determine vehicle types and deletion queries based on service type
        if ($service_type == 'taxi') {
            // Vehicle types for taxi routes
            $vehicle_types = "'Camry/Sonata', 'Starex/Staria', 'Hiace'";
            $delete_route_query = "DELETE FROM taxi_routes WHERE id = ?";
            $transport_type = 'taxi';
        } elseif ($service_type == 'rentacar') {
            // Vehicle types for rent-a-car routes
            $vehicle_types = "'GMC 16-19', 'GMC 22-23', 'Coaster'";
            $delete_route_query = "DELETE FROM rentacar_routes WHERE id = ?";
            $transport_type = 'rentacar';
        } else {
            throw new Exception("Invalid service type");
        }

        // Delete associated transportation bookings
        $delete_bookings_query = "
            DELETE FROM transportation_bookings 
            WHERE route_id = ? 
            AND transport_type = ?
            AND vehicle_type IN ($vehicle_types)
        ";
        $delete_bookings_stmt = $conn->prepare($delete_bookings_query);
        $delete_bookings_stmt->bind_param("is", $route_id, $transport_type);
        $delete_bookings_stmt->execute();
        $bookings_deleted = $delete_bookings_stmt->affected_rows;
        $delete_bookings_stmt->close();

        // Delete the specific route
        $delete_route_stmt = $conn->prepare($delete_route_query);
        $delete_route_stmt->bind_param("i", $route_id);
        $delete_route_stmt->execute();
        $route_deleted = $delete_route_stmt->affected_rows > 0;
        $delete_route_stmt->close();

        // Commit the transaction
        $conn->commit();

        // Return detailed deletion information
        return [
            'success' => true,
            'bookings_deleted' => $bookings_deleted,
            'route_deleted' => $route_deleted
        ];

    } catch (Exception $e) {
        // Rollback the transaction in case of any error
        $conn->rollback();

        // Log the error
        error_log("Transportation Route Deletion Error: " . $e->getMessage());

        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Handle Delete Requests
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_route'])) {
    // Validate and sanitize input
    $route_id = filter_input(INPUT_POST, 'route_id', FILTER_VALIDATE_INT);
    $service_type = filter_input(INPUT_POST, 'service_type', FILTER_SANITIZE_STRING);

    // Validate inputs
    if ($route_id === false || $route_id === null) {
        sendErrorResponse('Invalid route ID');
    }

    if (!in_array($service_type, ['taxi', 'rentacar'])) {
        sendErrorResponse('Invalid service type');
    }

    // Call the deletion function
    $result = deleteTransportationRoute($conn, $route_id, $service_type);

    // Send JSON response
    echo json_encode($result);
    exit;
}

// Prevent direct access
sendErrorResponse('Access denied', 403);
?>