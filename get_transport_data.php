<?php
// get_transport_data.php - Handles AJAX requests for transport dropdown data

require_once '../config/db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set header to return JSON response
header('Content-Type: application/json');

// Function to handle database errors
function handleDBError($message, $error)
{
  error_log($message . ": " . $error);
  echo json_encode([
    'success' => false,
    'message' => $message,
    'error' => $error
  ]);
  exit;
}

// Check connection
if (!$conn) {
  handleDBError("Database connection failed", mysqli_connect_error());
}

// Get request type
$request_type = isset($_GET['request']) ? $_GET['request'] : '';

// Handle different request types
switch ($request_type) {
  case 'routes':
    // Get transport type
    $transport_type = isset($_GET['transport_type']) ? $_GET['transport_type'] : '';

    if (!in_array($transport_type, ['taxi', 'rentacar'])) {
      echo json_encode([
        'success' => false,
        'message' => 'Invalid transport type'
      ]);
      exit;
    }

    try {
      // Get routes based on transport type
      if ($transport_type === 'taxi') {
        $stmt = $conn->prepare("SELECT id, route_name, route_number, camry_sonata_price, starex_staria_price, hiace_price 
                                        FROM taxi_routes 
                                        ORDER BY route_number");
      } else {
        $stmt = $conn->prepare("SELECT id, route_name, route_number, gmc_16_19_price, gmc_22_23_price, coaster_price 
                                        FROM rentacar_routes 
                                        ORDER BY route_number");
      }

      if (!$stmt) {
        handleDBError("Failed to prepare statement", $conn->error);
      }

      if (!$stmt->execute()) {
        handleDBError("Failed to execute query", $stmt->error);
      }

      $result = $stmt->get_result();
      $routes = [];

      while ($row = $result->fetch_assoc()) {
        $routes[] = $row;
      }

      $stmt->close();

      echo json_encode([
        'success' => true,
        'data' => $routes
      ]);
    } catch (Exception $e) {
      handleDBError("Failed to fetch routes", $e->getMessage());
    }
    break;

  case 'vehicles':
    // Get route ID and transport type
    $route_id = isset($_GET['route_id']) ? intval($_GET['route_id']) : 0;
    $transport_type = isset($_GET['transport_type']) ? $_GET['transport_type'] : '';

    if ($route_id <= 0 || !in_array($transport_type, ['taxi', 'rentacar'])) {
      echo json_encode([
        'success' => false,
        'message' => 'Invalid route ID or transport type'
      ]);
      exit;
    }

    try {
      // Get route details based on route ID and transport type
      if ($transport_type === 'taxi') {
        $stmt = $conn->prepare("SELECT id, route_name, camry_sonata_price, starex_staria_price, hiace_price 
                                        FROM taxi_routes 
                                        WHERE id = ?");
      } else {
        $stmt = $conn->prepare("SELECT id, route_name, gmc_16_19_price, gmc_22_23_price, coaster_price 
                                        FROM rentacar_routes 
                                        WHERE id = ?");
      }

      if (!$stmt) {
        handleDBError("Failed to prepare statement", $conn->error);
      }

      $stmt->bind_param("i", $route_id);

      if (!$stmt->execute()) {
        handleDBError("Failed to execute query", $stmt->error);
      }

      $result = $stmt->get_result();

      if ($result->num_rows === 0) {
        echo json_encode([
          'success' => false,
          'message' => 'Route not found'
        ]);
        exit;
      }

      $route = $result->fetch_assoc();
      $stmt->close();

      // Format vehicle options
      $vehicles = [];

      if ($transport_type === 'taxi') {
        if (floatval($route['camry_sonata_price']) > 0) {
          $vehicles[] = [
            'name' => 'Camry/Sonata',
            'price' => floatval($route['camry_sonata_price']),
            'display' => 'Camry/Sonata - PKR ' . number_format($route['camry_sonata_price'], 2)
          ];
        }

        if (floatval($route['starex_staria_price']) > 0) {
          $vehicles[] = [
            'name' => 'Starex/Staria',
            'price' => floatval($route['starex_staria_price']),
            'display' => 'Starex/Staria - PKR ' . number_format($route['starex_staria_price'], 2)
          ];
        }

        if (floatval($route['hiace_price']) > 0) {
          $vehicles[] = [
            'name' => 'Hiace',
            'price' => floatval($route['hiace_price']),
            'display' => 'Hiace - PKR ' . number_format($route['hiace_price'], 2)
          ];
        }
      } else {
        if (floatval($route['gmc_16_19_price']) > 0) {
          $vehicles[] = [
            'name' => 'GMC 16-19 Seats',
            'price' => floatval($route['gmc_16_19_price']),
            'display' => 'GMC 16-19 Seats - PKR ' . number_format($route['gmc_16_19_price'], 2)
          ];
        }

        if (floatval($route['gmc_22_23_price']) > 0) {
          $vehicles[] = [
            'name' => 'GMC 22-23 Seats',
            'price' => floatval($route['gmc_22_23_price']),
            'display' => 'GMC 22-23 Seats - PKR ' . number_format($route['gmc_22_23_price'], 2)
          ];
        }

        if (floatval($route['coaster_price']) > 0) {
          $vehicles[] = [
            'name' => 'Coaster',
            'price' => floatval($route['coaster_price']),
            'display' => 'Coaster - PKR ' . number_format($route['coaster_price'], 2)
          ];
        }
      }

      echo json_encode([
        'success' => true,
        'data' => $vehicles,
        'route_name' => $route['route_name']
      ]);
    } catch (Exception $e) {
      handleDBError("Failed to fetch vehicles", $e->getMessage());
    }
    break;

  default:
    echo json_encode([
      'success' => false,
      'message' => 'Invalid request type'
    ]);
    break;
}
