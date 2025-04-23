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

// Get booking ID from POST or GET
$booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : (isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0);

if ($booking_id <= 0) {
  header('Location: index.php');
  exit;
}

// Initialize variables
$success_message = '';
$error_message = '';
$booking = null;
$transport_types = ['taxi', 'rentacar'];
$taxi_routes = [];
$rentacar_routes = [];
$booking_details = [];

// Store form values to maintain state after submission
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

// Fetch booking details
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

// Check if transportation is already assigned to this booking
if ($booking) {
  $stmt = $conn->prepare("SELECT * FROM transportation_bookings WHERE user_id = ?");
  $stmt->bind_param("i", $booking['user_id']);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows > 0) {
    $booking_details = $result->fetch_assoc();

    // Use booking details as default form values if not already set
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

// Fetch taxi routes
$stmt = $conn->prepare("SELECT id, route_name, route_number, camry_sonata_price, starex_staria_price, hiace_price 
                      FROM taxi_routes 
                      ORDER BY route_number");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $taxi_routes[] = $row;
}
$stmt->close();

// Fetch rent-a-car routes
$stmt = $conn->prepare("SELECT id, route_name, route_number, gmc_16_19_price, gmc_22_23_price, coaster_price 
                      FROM rentacar_routes 
                      ORDER BY route_number");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $rentacar_routes[] = $row;
}
$stmt->close();

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['assign_transport'])) {
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

  // Validate inputs
  if (!in_array($transport_type, $transport_types)) {
    $error_message = "Please select a valid transport type.";
  } elseif ($route_id <= 0) {
    $error_message = "Please select a valid route.";
  } elseif (empty($vehicle_type)) {
    $error_message = "Please select a vehicle type.";
  } elseif (empty($pickup_date) || empty($pickup_time)) {
    $error_message = "Pickup date and time are required.";
  } elseif (empty($pickup_location)) {
    $error_message = "Pickup location is required.";
  } elseif (empty($full_name) || empty($email) || empty($phone)) {
    $error_message = "Contact information is required.";
  } else {
    // Get price based on vehicle type and route
    $price = 0;
    $route_name = '';

    if ($transport_type === 'taxi') {
      // Find route in taxi routes
      foreach ($taxi_routes as $route) {
        if ($route['id'] == $route_id) {
          $route_name = $route['route_name'];
          switch ($vehicle_type) {
            case 'Camry/Sonata':
              $price = $route['camry_sonata_price'];
              break;
            case 'Starex/Staria':
              $price = $route['starex_staria_price'];
              break;
            case 'Hiace':
              $price = $route['hiace_price'];
              break;
          }
          break;
        }
      }
    } else { // rentacar
      // Find route in rentacar routes
      foreach ($rentacar_routes as $route) {
        if ($route['id'] == $route_id) {
          $route_name = $route['route_name'];
          switch ($vehicle_type) {
            case 'GMC 16-19 Seats':
              $price = $route['gmc_16_19_price'];
              break;
            case 'GMC 22-23 Seats':
              $price = $route['gmc_22_23_price'];
              break;
            case 'Coaster':
              $price = $route['coaster_price'];
              break;
          }
          break;
        }
      }
    }

    if ($price <= 0) {
      $error_message = "Could not determine price for selected options.";
    } else {
      // Format pickup time with seconds
      $pickup_time = $pickup_time . ':00';

      // Start transaction
      $conn->begin_transaction();
      try {
        // Check if booking already has transportation assigned
        if (!empty($booking_details)) {
          // Update existing booking
          $stmt = $conn->prepare("UPDATE transportation_bookings 
                                SET transport_type = ?, route_id = ?, route_name = ?,
                                    vehicle_type = ?, price = ?, pickup_date = ?,
                                    pickup_time = ?, pickup_location = ?, 
                                    additional_notes = ?, full_name = ?,
                                    email = ?, phone = ?
                                WHERE user_id = ?");
          $user_id = $booking['user_id'];
          $stmt->bind_param(
            "sissdsssssssi",
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
            $user_id
          );
          if (!$stmt->execute()) {
            throw new Exception("Error updating transportation booking: " . $stmt->error);
          }
          $stmt->close();
        } else {
          // Create new booking
          $user_id = $booking['user_id'];
          $stmt = $conn->prepare("INSERT INTO transportation_bookings 
                                (user_id, transport_type, route_id, route_name,
                                 vehicle_type, price, full_name, email, phone,
                                 pickup_date, pickup_time, pickup_location, 
                                 additional_notes, booking_status) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed')");
          $stmt->bind_param(
            "isissdsssssss",
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
            $additional_notes
          );
          if (!$stmt->execute()) {
            throw new Exception("Error creating transportation booking: " . $stmt->error);
          }
          $stmt->close();
        }

        // Commit the transaction
        $conn->commit();
        $success_message = "Transportation assigned successfully to booking #$booking_id.";

        // Refresh booking details
        $stmt = $conn->prepare("SELECT * FROM transportation_bookings WHERE user_id = ?");
        $stmt->bind_param("i", $booking['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
          $booking_details = $result->fetch_assoc();
        }
        $stmt->close();
      } catch (Exception $e) {
        $conn->rollback();
        $error_message = $e->getMessage();
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
  <title>Assign Transportation | Admin Panel</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body class="bg-gray-100 min-h-screen">
  <?php include 'includes/sidebar.php'; ?>

  <div class="ml-0 md:ml-64 p-6">
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
          <i class="fas fa-car text-blue-500 mr-2"></i>Assign Transportation
        </h1>
        <a href="index.php" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
          <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
        </a>
      </div>

      <?php if ($error_message): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
          <p><?php echo $error_message; ?></p>
        </div>
      <?php endif; ?>

      <?php if ($success_message): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
          <p><?php echo $success_message; ?></p>
        </div>
      <?php endif; ?>

      <?php if ($booking): ?>
        <div class="bg-blue-50 border-l-4 border-blue-500 text-blue-700 p-4 mb-6">
          <p><strong>Booking #<?php echo $booking['id']; ?></strong></p>
          <p>User: <?php echo htmlspecialchars($booking['full_name']); ?> (ID: <?php echo $booking['user_id']; ?>)</p>
          <p>Package: <?php echo $booking['package_id']; ?></p>
          <p>Date: <?php echo date('F j, Y', strtotime($booking['created_at'])); ?></p>
        </div>

        <?php if (!empty($booking_details)): ?>
          <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6">
            <h3 class="font-bold">Current Transportation Assignment</h3>
            <p>Type: <?php echo ucfirst($booking_details['transport_type']); ?></p>
            <p>Route: <?php echo htmlspecialchars($booking_details['route_name']); ?></p>
            <p>Vehicle: <?php echo htmlspecialchars($booking_details['vehicle_type']); ?></p>
            <p>Pickup: <?php echo date('F j, Y', strtotime($booking_details['pickup_date'])); ?> at <?php echo date('H:i', strtotime($booking_details['pickup_time'])); ?></p>
            <p>Price: PKR <?php echo number_format($booking_details['price'], 2); ?></p>
            <p class="mt-2">You can update this assignment using the form below.</p>
          </div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-6" id="assignTransportForm">
          <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label for="transport_type" class="block text-sm font-medium text-gray-700 mb-1">Transport Type</label>
              <select name="transport_type" id="transport_type" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" required>
                <option value="">-- Select Transport Type --</option>
                <option value="taxi" <?php echo ($form_transport_type == 'taxi') ? 'selected' : ''; ?>>Taxi</option>
                <option value="rentacar" <?php echo ($form_transport_type == 'rentacar') ? 'selected' : ''; ?>>Rent A Car</option>
              </select>
            </div>

            <div id="routeContainer">
              <label for="route_id" class="block text-sm font-medium text-gray-700 mb-1">Route</label>
              <select name="route_id" id="route_id" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" required <?php echo empty($form_transport_type) ? 'disabled' : ''; ?>>
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
              <select name="vehicle_type" id="vehicle_type" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" required <?php echo empty($form_route_id) ? 'disabled' : ''; ?>>
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
                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                value="<?php echo $form_pickup_date; ?>"
                min="<?php echo date('Y-m-d'); ?>"
                required>
            </div>

            <div>
              <label for="pickup_time" class="block text-sm font-medium text-gray-700 mb-1">Pickup Time</label>
              <input type="time" name="pickup_time" id="pickup_time"
                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                value="<?php echo $form_pickup_time; ?>"
                required>
            </div>

            <div>
              <label for="pickup_location" class="block text-sm font-medium text-gray-700 mb-1">Pickup Location</label>
              <input type="text" name="pickup_location" id="pickup_location"
                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
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
                  class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                  value="<?php echo !empty($form_full_name) ? htmlspecialchars($form_full_name) : htmlspecialchars($booking['full_name']); ?>"
                  required>
              </div>
              <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" id="email"
                  class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                  value="<?php echo !empty($form_email) ? htmlspecialchars($form_email) : htmlspecialchars($booking['email']); ?>"
                  required>
              </div>
              <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                <input type="text" name="phone" id="phone"
                  class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                  value="<?php echo !empty($form_phone) ? htmlspecialchars($form_phone) : htmlspecialchars($booking['phone']); ?>"
                  required>
              </div>
            </div>
          </div>

          <div>
            <label for="additional_notes" class="block text-sm font-medium text-gray-700 mb-1">Additional Notes</label>
            <textarea name="additional_notes" id="additional_notes" rows="3"
              class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"><?php echo htmlspecialchars($form_additional_notes); ?></textarea>
          </div>

          <div id="pricePreview" class="bg-green-50 p-4 rounded-lg border border-green-200 <?php echo (empty($form_transport_type) || empty($form_route_id) || empty($form_vehicle_type)) ? 'hidden' : ''; ?>">
            <div class="flex justify-between items-center">
              <h3 class="text-lg font-medium text-gray-800">Price</h3>
              <span class="text-xl font-bold text-green-600">PKR <span id="priceDisplay"><?php
                                                                                          if (!empty($booking_details)) {
                                                                                            echo number_format($booking_details['price'], 2);
                                                                                          } else {
                                                                                            // Calculate price based on selections
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
                                                                                              } else { // rentacar
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
            <button type="submit" name="assign_transport" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
              <i class="fas fa-check mr-2"></i><?php echo !empty($booking_details) ? 'Update Transportation' : 'Assign Transportation'; ?>
            </button>
          </div>
        </form>
      <?php else: ?>
        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4">
          <p>Booking not found or invalid booking ID.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script>
    // Define routes data for dynamic loading
    const taxiRoutes = <?php echo json_encode($taxi_routes); ?>;
    const rentacarRoutes = <?php echo json_encode($rentacar_routes); ?>;

    // Function to populate route dropdown based on transport type
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

      // If a route was previously selected, try to re-select it
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

    // Function to populate vehicle types based on selected route
    function populateVehicles(routeId, transportType) {
      const vehicleSelect = document.getElementById('vehicle_type');
      vehicleSelect.innerHTML = '<option value="">-- Select Vehicle Type --</option>';

      if (!routeId || !transportType) {
        vehicleSelect.disabled = true;
        return;
      }

      const routes = transportType === 'taxi' ? taxiRoutes : rentacarRoutes;
      let selectedRoute = null;

      // Find the selected route
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

      // Add vehicle options based on transport type
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
      } else { // rentacar
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

      // If a vehicle type was previously selected, try to re-select it
      const savedVehicleType = "<?php echo $form_vehicle_type ? $form_vehicle_type : ''; ?>";
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
      option.textContent = `${vehicleName} - PKR ${parseFloat(price).toLocaleString()}`;
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

    // Event listeners
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

    // Initialize form state on page load
    document.addEventListener('DOMContentLoaded', function() {
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

    // Set minimum date for pickup date
    document.getElementById('pickup_date').min = new Date().toISOString().split('T')[0];

    // Form submission confirmation
    document.getElementById('assignTransportForm').addEventListener('submit', function(e) {
      if (e.submitter && e.submitter.name === 'assign_transport') {
        if (!confirm('Are you sure you want to assign this transportation to the booking?')) {
          e.preventDefault();
        }
      }
    });
  </script>
</body>

</html>