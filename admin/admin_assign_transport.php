<?php
require_once '../config/db.php';

session_name('admin_session');
session_start();

if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
  header('Location: login.php');
  exit;
}

$booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : (isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0);

if ($booking_id <= 0) {
  header('Location: index.php');
  exit;
}

$success_message = '';
$error_message = '';
$booking = null;
$transport_types = ['taxi', 'rentacar'];
$taxi_routes = [];
$rentacar_routes = [];
$booking_details = [];

$form_transport_type = isset($_POST['transport_type']) ? $_POST['transport_type'] : '';
$form_route_id = isset($_POST['route_id']) ? intval($_POST['route_id']) : 0;
$form_vehicle_type = isset($_POST['vehicle_type']) ? $_POST['vehicle_type'] : '';
$form_pickup_date = isset($_POST['pickup_date']) ? $_POST['pickup_date'] : '';
$form_pickup_time = isset($_POST['pickup_time']) ? $_POST['pickup_time'] : '';
$form_pickup_location = isset($_POST['pickup_location']) ? $_POST['pickup_location'] : '';
$form_additional_notes = isset($_POST['additional_notes']) ? $_POST['additional_notes'] : '';
$form_full_name = isset($_POST['full_name']) ? $_POST['full_name'] : '';
$form_email = isset($_POST['email']) ? $_POST['email'] : '';
$form_phone = isset($_POST['phone']) ? $_POST['phone'] : '';

$stmt = $conn->prepare("SELECT b.id, b.user_id, b.package_id, b.created_at, u.full_name, u.email, u.phone 
                       FROM package_bookings b 
                       JOIN users u ON b.user_id = u.id 
                       WHERE b.id = ?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
  $booking = $result->fetch_assoc();
} else {
  $error_message = "Booking not found.";
}
$stmt->close();

if ($booking) {
  $stmt = $conn->prepare("SELECT * FROM transportation_bookings WHERE user_id = ?");
  $stmt->bind_param("i", $booking['user_id']);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows > 0) {
    $booking_details = $result->fetch_assoc();
    if (empty($form_transport_type)) {
      $form_transport_type = $booking_details['transport_type'];
    }
    if (empty($form_route_id)) {
      $form_route_id = $booking_details['route_id'];
    }
    if (empty($form_vehicle_type)) {
      $form_vehicle_type = $booking_details['vehicle_type'];
    }
    if (empty($form_pickup_date)) {
      $form_pickup_date = $booking_details['pickup_date'];
    }
    if (empty($form_pickup_time)) {
      $form_pickup_time = date('H:i', strtotime($booking_details['pickup_time']));
    }
    if (empty($form_pickup_location)) {
      $form_pickup_location = $booking_details['pickup_location'];
    }
    if (empty($form_additional_notes)) {
      $form_additional_notes = $booking_details['additional_notes'];
    }
    if (empty($form_full_name)) {
      $form_full_name = $booking_details['full_name'];
    }
    if (empty($form_email)) {
      $form_email = $booking_details['email'];
    }
    if (empty($form_phone)) {
      $form_phone = $booking_details['phone'];
    }
  }
  $stmt->close();
}

$stmt = $conn->prepare("SELECT id, route_name, route_number, camry_sonata_price, starex_staria_price, hiace_price 
                      FROM taxi_routes 
                      ORDER BY route_number");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $taxi_routes[] = $row;
}
$stmt->close();

$stmt = $conn->prepare("SELECT id, route_name, route_number, gmc_16_19_price, gmc_22_23_price, coaster_price 
                      FROM rentacar_routes 
                      ORDER BY route_number");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $rentacar_routes[] = $row;
}
$stmt->close();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['assign_transport'])) {
  // Add debugging for form submission
  error_log("Transport assignment form submitted for booking #$booking_id: " . json_encode($_POST));

  $transport_type = $form_transport_type;
  $route_id = $form_route_id;
  $vehicle_type = $form_vehicle_type;
  $pickup_date = $form_pickup_date;
  $pickup_time = $form_pickup_time;
  $pickup_location = $form_pickup_location;
  $additional_notes = $form_additional_notes;
  $full_name = $form_full_name;
  $email = $form_email;
  $phone = $form_phone;

  // Enhanced validation with debugging
  $has_errors = false;

  if (!in_array($transport_type, $transport_types)) {
    $error_message = "Please select a valid transport type.";
    error_log("Invalid transport type: $transport_type");
    $has_errors = true;
  } elseif ($route_id <= 0) {
    $error_message = "Please select a valid route.";
    error_log("Invalid route ID: $route_id");
    $has_errors = true;
  } elseif (empty($vehicle_type)) {
    $error_message = "Please select a vehicle type.";
    error_log("Vehicle type is empty");
    $has_errors = true;
  } elseif (empty($pickup_date)) {
    $error_message = "Pickup date is required.";
    error_log("Pickup date is empty");
    $has_errors = true;
  } elseif (empty($pickup_time)) {
    $error_message = "Pickup time is required.";
    error_log("Pickup time is empty");
    $has_errors = true;
  } elseif (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $pickup_time)) {
    $error_message = "Invalid pickup time. Please use 24-hour format (e.g., 13:55 or 22:45).";
    error_log("Invalid pickup time format: $pickup_time");
    $has_errors = true;
  } elseif (empty($pickup_location)) {
    $error_message = "Pickup location is required.";
    error_log("Pickup location is empty");
    $has_errors = true;
  } elseif (empty($full_name)) {
    $error_message = "Contact name is required.";
    error_log("Full name is empty");
    $has_errors = true;
  } elseif (empty($email)) {
    $error_message = "Contact email is required.";
    error_log("Email is empty");
    $has_errors = true;
  } elseif (empty($phone)) {
    $error_message = "Contact phone is required.";
    error_log("Phone is empty");
    $has_errors = true;
  }

  if (!$has_errors) {
    // Find the price and route name
    $price = 0;
    $route_name = '';
    $price_found = false;

    if ($transport_type === 'taxi') {
      foreach ($taxi_routes as $route) {
        if ($route['id'] == $route_id) {
          $route_name = $route['route_name'];
          switch ($vehicle_type) {
            case 'Camry/Sonata':
              $price = $route['camry_sonata_price'];
              $price_found = true;
              break;
            case 'Starex/Staria':
              $price = $route['starex_staria_price'];
              $price_found = true;
              break;
            case 'Hiace':
              $price = $route['hiace_price'];
              $price_found = true;
              break;
          }
          break;
        }
      }
    } else {
      foreach ($rentacar_routes as $route) {
        if ($route['id'] == $route_id) {
          $route_name = $route['route_name'];
          switch ($vehicle_type) {
            case 'GMC 16-19 Seats':
              $price = $route['gmc_16_19_price'];
              $price_found = true;
              break;
            case 'GMC 22-23 Seats':
              $price = $route['gmc_22_23_price'];
              $price_found = true;
              break;
            case 'Coaster':
              $price = $route['coaster_price'];
              $price_found = true;
              break;
          }
          break;
        }
      }
    }

    if (!$price_found || $price <= 0) {
      $error_message = "Could not determine price for selected options. Please try different selections.";
      error_log("Price determination failed for booking #$booking_id. Transport type: $transport_type, Route ID: $route_id, Vehicle type: $vehicle_type");
    } else {
      // Format pickup time for database
      $pickup_time = $pickup_time . ':00';

      // Database transaction
      $conn->begin_transaction();
      try {
        $payment_status = 'paid';
        $booking_status = 'confirmed';
        $user_id = $booking['user_id'];

        if (!empty($booking_details)) {
          // Update existing booking
          $stmt = $conn->prepare("UPDATE transportation_bookings 
                                SET transport_type = ?, route_id = ?, route_name = ?,
                                    vehicle_type = ?, price = ?, pickup_date = ?,
                                    pickup_time = ?, pickup_location = ?, 
                                    additional_notes = ?, full_name = ?,
                                    email = ?, phone = ?, payment_status = ?,
                                    booking_status = ?
                                WHERE user_id = ?");

          $stmt->bind_param(
            "sissdsssssssssi",
            $transport_type,
            $route_id,
            $route_name,
            $vehicle_type,
            $price,
            $pickup_date,
            $pickup_time,
            $pickup_location,
            $additional_notes,
            $full_name,
            $email,
            $phone,
            $payment_status,
            $booking_status,
            $user_id
          );

          if (!$stmt->execute()) {
            throw new Exception("Error updating transportation booking: " . $stmt->error);
          }
          error_log("Successfully updated transportation booking for user ID: $user_id, booking #$booking_id");
          $stmt->close();
        } else {
          // Create new booking
          $stmt = $conn->prepare("INSERT INTO transportation_bookings 
                                (user_id, transport_type, route_id, route_name,
                                 vehicle_type, price, full_name, email, phone,
                                 pickup_date, pickup_time, pickup_location, 
                                 additional_notes, payment_status, booking_status) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

          $stmt->bind_param(
            "isissdsssssssss",
            $user_id,
            $transport_type,
            $route_id,
            $route_name,
            $vehicle_type,
            $price,
            $full_name,
            $email,
            $phone,
            $pickup_date,
            $pickup_time,
            $pickup_location,
            $additional_notes,
            $payment_status,
            $booking_status
          );

          if (!$stmt->execute()) {
            throw new Exception("Error creating transportation booking: " . $stmt->error);
          }
          error_log("Successfully created transportation booking for user ID: $user_id, booking #$booking_id");
          $stmt->close();
        }

        // Commit the transaction
        $conn->commit();
        $success_message = "Transportation assigned successfully to booking #$booking_id. Payment status set to 'paid' and booking status set to 'confirmed'.";

        // Refresh booking details
        $stmt = $conn->prepare("SELECT * FROM transportation_bookings WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
          $booking_details = $result->fetch_assoc();
        }
        $stmt->close();
      } catch (Exception $e) {
        $conn->rollback();
        $error_message = $e->getMessage();
        error_log("Transportation assignment transaction failed: " . $e->getMessage());
      }
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Assign Transportation | UmrahFlights</title>
  <!-- Tailwind CSS (same as other pages) -->
  <link rel="stylesheet" href="../src/output.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <!-- SweetAlert2 (for consistency with other pages) -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-gray-100 font-sans min-h-screen">
  <?php include 'includes/sidebar.php'; ?>
  <main class="ml-0 md:ml-64 mt-10 px-4 sm:px-6 lg:px-8 transition-all duration-300" role="main" aria-label="Main content">
    <!-- Top Navbar (aligned with other pages) -->
    <nav class="bg-white shadow-lg rounded-lg p-5 mb-6">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <button id="sidebarToggle" class="text-gray-500 hover:text-gray-700 focus:outline-none md:hidden" aria-label="Toggle sidebar">
            <i class="fas fa-bars text-xl"></i>
          </button>
          <h4 id="dashboardHeader" class="text-lg font-semibold text-gray-800 cursor-pointer hover:text-indigo-600">Assign Transportation</h4>
        </div>
        <div class="flex items-center space-x-4">
          <!-- User Dropdown -->
          <div class="relative">
            <button id="userDropdownButton" class="flex items-center space-x-2 text-gray-700 hover:bg-indigo-50 rounded-lg px-3 py-2 focus:outline-none" aria-label="User menu" aria-expanded="false">
              <div class="rounded-full overflow-hidden" style="width: 32px; height: 32px;">
                <div class="bg-gray-200 w-full h-full"></div>
              </div>
              <span class="hidden md:inline text-sm font-medium">Admin User</span>
              <svg class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <ul id="userDropdownMenu" class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 hidden z-50">
              <li>
                <a class="flex items-center px-4 py-2 text-sm text-red-500 hover:bg-red-50" href="logout.php">
                  <i class="fas fa-sign-out-alt mr-2"></i> Logout
                </a>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </nav>

    <!-- Main Content Section -->
    <section class="bg-white shadow-lg rounded-lg p-6" aria-label="Transportation assignment">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">
          <i class="fas fa-car text-indigo-600 mr-2"></i>Assign Transportation
        </h2>
        <a href="index.php" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">
          <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
        </a>
      </div>

      <!-- Alerts (aligned with other pages) -->
      <?php if ($error_message): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-6 flex justify-between items-center" role="alert">
          <span><?php echo htmlspecialchars($error_message); ?></span>
          <button class="text-red-700 hover:text-red-900 focus:outline-none focus:ring-2 focus:ring-red-500" onclick="this.parentElement.remove()" aria-label="Close alert">
            <i class="fas fa-times"></i>
          </button>
        </div>
      <?php endif; ?>

      <?php if ($success_message): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-6 flex justify-between items-center" role="alert">
          <span><?php echo htmlspecialchars($success_message); ?></span>
          <button class="text-green-700 hover:text-green-900 focus:outline-none focus:ring-2 focus:ring-green-500" onclick="this.parentElement.remove()" aria-label="Close alert">
            <i class="fas fa-times"></i>
          </button>
        </div>
      <?php endif; ?>

      <?php if ($booking): ?>
        <div class="bg-indigo-50 border-l-4 border-indigo-500 text-indigo-700 p-4 rounded-lg mb-6">
          <p><strong>Booking #<?php echo $booking['id']; ?></strong></p>
          <p>User: <?php echo htmlspecialchars($booking['full_name']); ?> (ID: <?php echo $booking['user_id']; ?>)</p>
          <p>Package: <?php echo $booking['package_id']; ?></p>
          <p>Date: <?php echo date('F j, Y', strtotime($booking['created_at'])); ?></p>
        </div>

        <?php if (!empty($booking_details)): ?>
          <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-6">
            <h3 class="font-bold text-lg text-gray-800">Current Transportation Assignment</h3>
            <p>Type: <?php echo ucfirst($booking_details['transport_type']); ?></p>
            <p>Route: <?php echo htmlspecialchars($booking_details['route_name']); ?></p>
            <p>Vehicle: <?php echo htmlspecialchars($booking_details['vehicle_type']); ?></p>
            <p>Pickup: <?php echo date('F j, Y', strtotime($booking_details['pickup_date'])); ?> at <?php echo date('H:i', strtotime($booking_details['pickup_time'])); ?></p>
            <p>Price: PKR <?php echo number_format($booking_details['price'], 2); ?></p>
            <p>Payment Status: <?php echo ucfirst($booking_details['payment_status'] ?? 'Pending'); ?></p>
            <p>Booking Status: <?php echo ucfirst($booking_details['booking_status'] ?? 'Pending'); ?></p>
            <p class="mt-2">You can update this assignment using the form below.</p>
          </div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-6" id="assignTransportForm">
          <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label for="transport_type" class="block text-sm font-medium text-gray-700 mb-1">Transport Type</label>
              <select name="transport_type" id="transport_type" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500" required>
                <option value="">-- Select Transport Type --</option>
                <option value="taxi" <?php echo ($form_transport_type == 'taxi') ? 'selected' : ''; ?>>Taxi</option>
                <option value="rentacar" <?php echo ($form_transport_type == 'rentacar') ? 'selected' : ''; ?>>Rent A Car</option>
              </select>
            </div>
            <div id="routeContainer">
              <label for="route_id" class="block text-sm font-medium text-gray-700 mb-1">Route</label>
              <select name="route_id" id="route_id" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500" required <?php echo empty($form_transport_type) ? 'disabled' : ''; ?>>
                <option value="">-- Select Route --</option>
                <?php if ($form_transport_type == 'taxi'): ?>
                  <?php foreach ($taxi_routes as $route): ?>
                    <option value="<?php echo $route['id']; ?>" <?php echo ($form_route_id == $route['id']) ? 'selected' : ''; ?> data-route='<?php echo json_encode($route); ?>'>
                      <?php echo htmlspecialchars($route['route_name']); ?> (Route #<?php echo $route['route_number']; ?>)
                    </option>
                  <?php endforeach; ?>
                <?php elseif ($form_transport_type == 'rentacar'): ?>
                  <?php foreach ($rentacar_routes as $route): ?>
                    <option value="<?php echo $route['id']; ?>" <?php echo ($form_route_id == $route['id']) ? 'selected' : ''; ?> data-route='<?php echo json_encode($route); ?>'>
                      <?php echo htmlspecialchars($route['route_name']); ?> (Route #<?php echo $route['route_number']; ?>)
                    </option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
            </div>
            <div id="vehicleContainer">
              <label for="vehicle_type" class="block text-sm font-medium text-gray-700 mb-1">Vehicle Type</label>
              <select name="vehicle_type" id="vehicle_type" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500" required <?php echo empty($form_route_id) ? 'disabled' : ''; ?>>
                <option value="">-- Select Vehicle Type --</option>
                <?php if (!empty($form_transport_type) && !empty($form_route_id)): ?>
                  <?php if ($form_transport_type == 'taxi'): ?>
                    <?php
                    $selected_route = null;
                    foreach ($taxi_routes as $route) {
                      if ($route['id'] == $form_route_id) {
                        $selected_route = $route;
                        break;
                      }
                    }
                    if ($selected_route):
                    ?>
                      <?php if (floatval($selected_route['camry_sonata_price']) > 0): ?>
                        <option value="Camry/Sonata" <?php echo ($form_vehicle_type == 'Camry/Sonata') ? 'selected' : ''; ?>>
                          Camry/Sonata - PKR <?php echo number_format($selected_route['camry_sonata_price'], 2); ?>
                        </option>
                      <?php endif; ?>
                      <?php if (floatval($selected_route['starex_staria_price']) > 0): ?>
                        <option value="Starex/Staria" <?php echo ($form_vehicle_type == 'Starex/Staria') ? 'selected' : ''; ?>>
                          Starex/Staria - PKR <?php echo number_format($selected_route['starex_staria_price'], 2); ?>
                        </option>
                      <?php endif; ?>
                      <?php if (floatval($selected_route['hiace_price']) > 0): ?>
                        <option value="Hiace" <?php echo ($form_vehicle_type == 'Hiace') ? 'selected' : ''; ?>>
                          Hiace - PKR <?php echo number_format($selected_route['hiace_price'], 2); ?>
                        </option>
                      <?php endif; ?>
                    <?php endif; ?>
                  <?php elseif ($form_transport_type == 'rentacar'): ?>
                    <?php
                    $selected_route = null;
                    foreach ($rentacar_routes as $route) {
                      if ($route['id'] == $form_route_id) {
                        $selected_route = $route;
                        break;
                      }
                    }
                    if ($selected_route):
                    ?>
                      <?php if (floatval($selected_route['gmc_16_19_price']) > 0): ?>
                        <option value="GMC 16-19 Seats" <?php echo ($form_vehicle_type == 'GMC 16-19 Seats') ? 'selected' : ''; ?>>
                          GMC 16-19 Seats - PKR <?php echo number_format($selected_route['gmc_16_19_price'], 2); ?>
                        </option>
                      <?php endif; ?>
                      <?php if (floatval($selected_route['gmc_22_23_price']) > 0): ?>
                        <option value="GMC 22-23 Seats" <?php echo ($form_vehicle_type == 'GMC 22-23 Seats') ? 'selected' : ''; ?>>
                          GMC 22-23 Seats - PKR <?php echo number_format($selected_route['gmc_22_23_price'], 2); ?>
                        </option>
                      <?php endif; ?>
                      <?php if (floatval($selected_route['coaster_price']) > 0): ?>
                        <option value="Coaster" <?php echo ($form_vehicle_type == 'Coaster') ? 'selected' : ''; ?>>
                          Coaster - PKR <?php echo number_format($selected_route['coaster_price'], 2); ?>
                        </option>
                      <?php endif; ?>
                    <?php endif; ?>
                  <?php endif; ?>
                <?php endif; ?>
              </select>
            </div>
            <div>
              <label for="pickup_date" class="block text-sm font-medium text-gray-700 mb-1">Pickup Date</label>
              <input type="date" name="pickup_date" id="pickup_date"
                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                value="<?php echo htmlspecialchars($form_pickup_date); ?>"
                min="<?php echo date('Y-m-d'); ?>"
                required>
            </div>
            <div>
              <label for="pickup_time" class="block text-sm font-medium text-gray-700 mb-1">Pickup Time</label>
              <input type="text"
                name="pickup_time"
                id="pickup_time"
                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                value="<?php echo htmlspecialchars($form_pickup_time); ?>"
                placeholder="HH:MM"
                maxlength="5"
                required>
              <span id="pickup_time_error" class="text-red-500 text-xs hidden">Please enter a valid time in 24-hour format (e.g., 13:55 or 22:45).</span>
            </div>
            <div>
              <label for="pickup_location" class="block text-sm font-medium text-gray-700 mb-1">Pickup Location</label>
              <input type="text" name="pickup_location" id="pickup_location"
                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                value="<?php echo htmlspecialchars($form_pickup_location); ?>"
                required>
            </div>
          </div>
          <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
            <h3 class="text-lg font-medium text-gray-800 mb-4">Contact Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div>
                <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                <input type="text" name="full_name" id="full_name"
                  class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                  value="<?php echo !empty($form_full_name) ? htmlspecialchars($form_full_name) : htmlspecialchars($booking['full_name']); ?>"
                  required>
              </div>
              <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" id="email"
                  class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                  value="<?php echo !empty($form_email) ? htmlspecialchars($form_email) : htmlspecialchars($booking['email']); ?>"
                  required>
              </div>
              <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                <input type="text" name="phone" id="phone"
                  class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                  value="<?php echo !empty($form_phone) ? htmlspecialchars($form_phone) : htmlspecialchars($booking['phone']); ?>"
                  required>
              </div>
            </div>
          </div>
          <div>
            <label for="additional_notes" class="block text-sm font-medium text-gray-700 mb-1">Additional Notes</label>
            <textarea name="additional_notes" id="additional_notes" rows="3"
              class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"><?php echo htmlspecialchars($form_additional_notes); ?></textarea>
          </div>
          <div id="pricePreview" class="bg-indigo-50 p-4 rounded-lg border border-indigo-200 <?php echo (empty($form_transport_type) || empty($form_route_id) || empty($form_vehicle_type)) ? 'hidden' : ''; ?>">
            <div class="flex justify-between items-center">
              <h3 class="text-lg font-medium text-gray-800">Price</h3>
              <span class="text-xl font-bold text-indigo-600">PKR <span id="priceDisplay"><?php
                                                                                          if (!empty($booking_details)) {
                                                                                            echo number_format($booking_details['price'], 2);
                                                                                          } else {
                                                                                            $calculated_price = 0;
                                                                                            if (!empty($form_transport_type) && !empty($form_route_id) && !empty($form_vehicle_type)) {
                                                                                              if ($form_transport_type === 'taxi') {
                                                                                                foreach ($taxi_routes as $route) {
                                                                                                  if ($route['id'] == $form_route_id) {
                                                                                                    switch ($form_vehicle_type) {
                                                                                                      case 'Camry/Sonata':
                                                                                                        $calculated_price = $route['camry_sonata_price'];
                                                                                                        break;
                                                                                                      case 'Starex/Staria':
                                                                                                        $calculated_price = $route['starex_staria_price'];
                                                                                                        break;
                                                                                                      case 'Hiace':
                                                                                                        $calculated_price = $route['hiace_price'];
                                                                                                        break;
                                                                                                    }
                                                                                                    break;
                                                                                                  }
                                                                                                }
                                                                                              } else {
                                                                                                foreach ($rentacar_routes as $route) {
                                                                                                  if ($route['id'] == $form_route_id) {
                                                                                                    switch ($form_vehicle_type) {
                                                                                                      case 'GMC 16-19 Seats':
                                                                                                        $calculated_price = $route['gmc_16_19_price'];
                                                                                                        break;
                                                                                                      case 'GMC 22-23 Seats':
                                                                                                        $calculated_price = $route['gmc_22_23_price'];
                                                                                                        break;
                                                                                                      case 'Coaster':
                                                                                                        $calculated_price = $route['coaster_price'];
                                                                                                        break;
                                                                                                    }
                                                                                                    break;
                                                                                                  }
                                                                                                }
                                                                                              }
                                                                                            }
                                                                                            echo number_format($calculated_price, 2);
                                                                                          }
                                                                                          ?></span></span>
            </div>
          </div>
          <div class="flex justify-end">
            <button type="submit" name="assign_transport" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
              <i class="fas fa-check mr-2"></i><?php echo !empty($booking_details) ? 'Update Transportation' : 'Assign Transportation'; ?>
            </button>
          </div>
        </form>
      <?php else: ?>
        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded-lg">
          <p>Booking not found or invalid booking ID.</p>
        </div>
      <?php endif; ?>
    </section>
  </main>
  <script>
    const taxiRoutes = <?php echo json_encode($taxi_routes); ?>;
    const rentacarRoutes = <?php echo json_encode($rentacar_routes); ?>;

    document.addEventListener('DOMContentLoaded', function() {
      // Sidebar elements (aligned with other pages)
      const sidebar = document.getElementById('sidebar');
      const sidebarOverlay = document.getElementById('sidebar-overlay');
      const sidebarToggle = document.getElementById('sidebarToggle');
      const sidebarClose = document.getElementById('sidebar-close');
      const dashboardHeader = document.getElementById('dashboardHeader');

      // User dropdown elements
      const userDropdownButton = document.getElementById('userDropdownButton');
      const userDropdownMenu = document.getElementById('userDropdownMenu');

      // Error handling for missing elements
      if (!sidebar || !sidebarOverlay || !sidebarToggle || !sidebarClose) {
        console.warn('One or more sidebar elements are missing.');
        return;
      }
      if (!userDropdownButton || !userDropdownMenu) {
        console.warn('User dropdown elements are missing.');
        return;
      }
      if (!dashboardHeader) {
        console.warn('Dashboard header element is missing.');
        return;
      }

      // Sidebar toggle function
      const toggleSidebar = () => {
        sidebar.classList.toggle('-translate-x-full');
        sidebarOverlay.classList.toggle('hidden');
        sidebarToggle.classList.toggle('hidden');
      };

      // Open sidebar
      sidebarToggle.addEventListener('click', toggleSidebar);

      // Close sidebar
      sidebarClose.addEventListener('click', toggleSidebar);

      // Close sidebar via overlay
      sidebarOverlay.addEventListener('click', toggleSidebar);

      // Open sidebar on Dashboard header click
      dashboardHeader.addEventListener('click', () => {
        if (sidebar.classList.contains('-translate-x-full')) {
          toggleSidebar();
        }
      });

      // User dropdown toggle
      userDropdownButton.addEventListener('click', () => {
        userDropdownMenu.classList.toggle('hidden');
      });

      // Close dropdown when clicking outside
      document.addEventListener('click', (event) => {
        if (!userDropdownButton.contains(event.target) && !userDropdownMenu.contains(event.target)) {
          userDropdownMenu.classList.add('hidden');
        }
      });

      // Pickup time validation
      const timeInput = document.getElementById('pickup_time');
      const errorSpan = document.getElementById('pickup_time_error');
      const form = document.getElementById('assignTransportForm');

      timeInput.addEventListener('input', function(e) {
        let value = e.target.value;
        value = value.replace(/[^0-9:]/g, '');
        if (value.length === 2 && !value.includes(':')) {
          value += ':';
        }
        if (value.length > 5) {
          value = value.substring(0, 5);
        }
        if (value.includes(':') && value.split(':')[0].length === 2) {
          const hours = parseInt(value.split(':')[0], 10);
          if (hours > 23) {
            value = '23' + value.substring(2);
          }
        }
        if (value.length === 5 && value.includes(':')) {
          const minutes = parseInt(value.split(':')[1], 10);
          if (minutes > 59) {
            value = value.substring(0, 3) + '59';
          }
        }
        e.target.value = value;
        const timePattern = /^([01]?[0-9]|2[0-3]):[0-5][0-9]$/;
        if (value.length === 5 && !timePattern.test(value)) {
          timeInput.classList.remove('border-gray-300');
          timeInput.classList.add('border-red-500');
          errorSpan.classList.remove('hidden');
        } else {
          timeInput.classList.remove('border-red-500');
          timeInput.classList.add('border-gray-300');
          errorSpan.classList.add('hidden');
        }
      });

      form.addEventListener('submit', function(e) {
        const timePattern = /^([01]?[0-9]|2[0-3]):[0-5][0-9]$/;

        // First validate the time format
        if (timeInput.value && !timePattern.test(timeInput.value)) {
          e.preventDefault();
          timeInput.classList.remove('border-gray-300');
          timeInput.classList.add('border-red-500');
          errorSpan.classList.remove('hidden');
          timeInput.focus();
          Swal.fire({
            icon: 'error',
            title: 'Invalid Time',
            text: 'Please enter a valid time in 24-hour format (e.g., 13:55 or 22:45).'
          });
          return; // Stop execution here
        }

        // Handle form submission with confirmation
        if (e.submitter && e.submitter.name === 'assign_transport') {
          e.preventDefault(); // Prevent default form submission initially

          // Store a reference to the form
          const formElement = this;

          Swal.fire({
            title: 'Are you sure?',
            text: 'Do you want to assign this transportation to the booking?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#4f46e5',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, assign it!',
            cancelButtonText: 'Cancel'
          }).then((result) => {
            if (result.isConfirmed) {
              // Critical fix: Use a hidden input to indicate the form should be submitted
              const hiddenInput = document.createElement('input');
              hiddenInput.type = 'hidden';
              hiddenInput.name = 'assign_transport';
              hiddenInput.value = '1';
              formElement.appendChild(hiddenInput);

              // Submit the form programmatically
              formElement.submit();
            }
          });
        }
      });

      // Populate routes and vehicles
      function populateRoutes(transportType) {
        const routeSelect = document.getElementById('route_id');
        routeSelect.innerHTML = '<option value="">-- Select Route --</option>';
        routeSelect.disabled = !transportType;
        if (!transportType) return;
        const routes = transportType === 'taxi' ? taxiRoutes : rentacarRoutes;
        routes.forEach(route => {
          const option = document.createElement('option');
          option.value = route.id;
          option.textContent = `${route.route_name} (Route #${route.route_number})`;
          option.setAttribute('data-route', JSON.stringify(route));
          routeSelect.appendChild(option);
        });
        const savedRouteId = <?php echo $form_route_id ? $form_route_id : '0'; ?>;
        if (savedRouteId > 0) {
          for (let i = 0; i < routeSelect.options.length; i++) {
            if (routeSelect.options[i].value == savedRouteId) {
              routeSelect.selectedIndex = i;
              routeSelect.dispatchEvent(new Event('change'));
              break;
            }
          }
        }
      }

      function populateVehicles(routeId, transportType) {
        const vehicleSelect = document.getElementById('vehicle_type');
        vehicleSelect.innerHTML = '<option value="">-- Select Vehicle Type --</option>';
        if (!routeId || !transportType) {
          vehicleSelect.disabled = true;
          return;
        }
        const routes = transportType === 'taxi' ? taxiRoutes : rentacarRoutes;
        let selectedRoute = null;
        for (const route of routes) {
          if (route.id == routeId) {
            selectedRoute = route;
            break;
          }
        }
        if (!selectedRoute) {
          vehicleSelect.disabled = true;
          return;
        }
        vehicleSelect.disabled = false;
        if (transportType === 'taxi') {
          if (parseFloat(selectedRoute.camry_sonata_price) > 0) {
            addVehicleOption(vehicleSelect, 'Camry/Sonata', selectedRoute.camry_sonata_price);
          }
          if (parseFloat(selectedRoute.starex_staria_price) > 0) {
            addVehicleOption(vehicleSelect, 'Starex/Staria', selectedRoute.starex_staria_price);
          }
          if (parseFloat(selectedRoute.hiace_price) > 0) {
            addVehicleOption(vehicleSelect, 'Hiace', selectedRoute.hiace_price);
          }
        } else {
          if (parseFloat(selectedRoute.gmc_16_19_price) > 0) {
            addVehicleOption(vehicleSelect, 'GMC 16-19 Seats', selectedRoute.gmc_16_19_price);
          }
          if (parseFloat(selectedRoute.gmc_22_23_price) > 0) {
            addVehicleOption(vehicleSelect, 'GMC 22-23 Seats', selectedRoute.gmc_22_23_price);
          }
          if (parseFloat(selectedRoute.coaster_price) > 0) {
            addVehicleOption(vehicleSelect, 'Coaster', selectedRoute.coaster_price);
          }
        }
        const savedVehicleType = "<?php echo $form_vehicle_type ? addslashes($form_vehicle_type) : ''; ?>";
        if (savedVehicleType) {
          for (let i = 0; i < vehicleSelect.options.length; i++) {
            if (vehicleSelect.options[i].value === savedVehicleType) {
              vehicleSelect.selectedIndex = i;
              vehicleSelect.dispatchEvent(new Event('change'));
              break;
            }
          }
        }
      }

      function addVehicleOption(select, vehicleName, price) {
        const option = document.createElement('option');
        option.value = vehicleName;
        option.textContent = `${vehicleName} - PKR ${parseFloat(price).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        option.setAttribute('data-price', price);
        select.appendChild(option);
      }

      function updatePriceDisplay() {
        const vehicleSelect = document.getElementById('vehicle_type');
        const pricePreview = document.getElementById('pricePreview');
        const priceDisplay = document.getElementById('priceDisplay');
        if (vehicleSelect.value) {
          const selectedOption = vehicleSelect.options[vehicleSelect.selectedIndex];
          const price = parseFloat(selectedOption.getAttribute('data-price'));
          priceDisplay.textContent = price.toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
          });
          pricePreview.classList.remove('hidden');
        } else {
          pricePreview.classList.add('hidden');
        }
      }

      document.getElementById('transport_type').addEventListener('change', function() {
        populateRoutes(this.value);
        document.getElementById('vehicle_type').innerHTML = '<option value="">-- Select Route First --</option>';
        document.getElementById('vehicle_type').disabled = true;
        document.getElementById('pricePreview').classList.add('hidden');
      });

      document.getElementById('route_id').addEventListener('change', function() {
        const transportType = document.getElementById('transport_type').value;
        populateVehicles(this.value, transportType);
        document.getElementById('pricePreview').classList.add('hidden');
      });

      document.getElementById('vehicle_type').addEventListener('change', updatePriceDisplay);

      document.getElementById('pickup_date').min = new Date().toISOString().split('T')[0];

      // Initialize form state
      const transportType = document.getElementById('transport_type').value;
      const routeId = document.getElementById('route_id').value;
      if (transportType) {
        populateRoutes(transportType);
        if (routeId) {
          populateVehicles(routeId, transportType);
          if (document.getElementById('vehicle_type').value) {
            updatePriceDisplay();
          }
        }
      }
    });
  </script>
</body>

</html>