<?php
require_once 'config/db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php?redirect=transportation-booking.php");
  exit;
}

// Initialize variables
$errors = [];
$success_message = '';
$route = null;
$vehicle_types = [];
$selected_vehicle = '';
$price = 0.0;
$user_data = [];

// Fetch user data and verify user exists
$user_id = (int)$_SESSION['user_id'];
$stmt = $conn->prepare("SELECT full_name, email, phone FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $user_data = $result->fetch_assoc()) {
  // User data found
} else {
  $errors[] = "User profile not found. Please update your profile or register.";
  error_log("Invalid user_id: $user_id");
}
$stmt->close();

// Get transport type and route ID from URL
$transport_type = isset($_GET['type']) ? trim($_GET['type']) : '';
$route_id = isset($_GET['route']) ? (int)$_GET['route'] : 0;

// Validate transport type
if (!in_array($transport_type, ['taxi', 'rentacar'])) {
  $errors[] = "Invalid transportation type.";
}

// Fetch route details
if (empty($errors)) {
  if ($transport_type === 'taxi') {
    $stmt = $conn->prepare("SELECT id, route_name, route_number, camry_sonata_price, starex_staria_price, hiace_price 
                                FROM taxi_routes 
                                WHERE id = ?");
    $stmt->bind_param("i", $route_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $route = $result->fetch_assoc()) {
      $vehicle_types = array_filter([
        'Camry/Sonata' => $route['camry_sonata_price'],
        'Starex/Staria' => $route['starex_staria_price'],
        'Hiace' => $route['hiace_price']
      ], function ($price) {
        return $price !== null && $price > 0;
      });
    } else {
      $errors[] = "Invalid taxi route selected.";
    }
    $stmt->close();
  } elseif ($transport_type === 'rentacar') {
    $stmt = $conn->prepare("SELECT id, route_name, route_number, gmc_16_19_price, gmc_22_23_price, coaster_price 
                                FROM rentacar_routes 
                                WHERE id = ?");
    $stmt->bind_param("i", $route_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $route = $result->fetch_assoc()) {
      $vehicle_types = array_filter([
        'GMC 16-19 Seats' => $route['gmc_16_19_price'],
        'GMC 22-23 Seats' => $route['gmc_22_23_price'],
        'Coaster' => $route['coaster_price']
      ], function ($price) {
        return $price !== null && $price > 0;
      });
    } else {
      $errors[] = "Invalid rent-a-car route selected.";
    }
    $stmt->close();
  }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
  $full_name = trim($_POST['full_name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $pickup_date = $_POST['pickup_date'] ?? '';
  $pickup_time = $_POST['pickup_time'] ? $_POST['pickup_time'] . ':00' : ''; // Append seconds
  $pickup_location = trim($_POST['pickup_location'] ?? '');
  $additional_notes = trim($_POST['additional_notes'] ?? '');
  $selected_vehicle = $_POST['vehicle_type'] ?? '';

  // Server-side validation
  if (empty($full_name)) {
    $errors[] = "Full name is required.";
  }
  if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Valid email is required.";
  }
  if (empty($phone) || !preg_match("/^[0-9+\-\(\) ]{7,20}$/", $phone)) {
    $errors[] = "Valid phone number is required (7-20 characters, numbers, +, -, (), spaces allowed).";
  }
  if (empty($pickup_date) || !preg_match("/^\d{4}-\d{2}-\d{2}$/", $pickup_date)) {
    $errors[] = "Valid pickup date is required (YYYY-MM-DD).";
  } elseif (strtotime($pickup_date) < strtotime(date('Y-m-d'))) {
    $errors[] = "Pickup date must be in the future.";
  }
  if (empty($pickup_time) || !preg_match("/^\d{2}:\d{2}:\d{2}$/", $pickup_time)) {
    $errors[] = "Valid pickup time is required (HH:MM:SS).";
  }
  if (empty($pickup_location)) {
    $errors[] = "Pickup location is required.";
  }
  if (empty($selected_vehicle) || !isset($vehicle_types[$selected_vehicle])) {
    $errors[] = "Please select a valid vehicle type.";
  }

  // Check for duplicate booking with detailed logging
  if (empty($errors)) {
    error_log("Checking for duplicate booking: user_id=$user_id, transport_type=$transport_type, route_id=$route_id, pickup_date=$pickup_date");
    $stmt = $conn->prepare("SELECT COUNT(*) as booking_count 
                                FROM transportation_bookings 
                                WHERE user_id = ? 
                                AND transport_type = ? 
                                AND route_id = ? 
                                AND pickup_date = ? 
                                AND booking_status IN ('pending', 'confirmed')");
    if (!$stmt) {
      $errors[] = "Failed to prepare duplicate check query: " . $conn->error;
      error_log("Duplicate check prepare error: " . $conn->error);
    } else {
      $stmt->bind_param("isis", $user_id, $transport_type, $route_id, $pickup_date);
      if (!$stmt->execute()) {
        $errors[] = "Failed to execute duplicate check query: " . $stmt->error;
        error_log("Duplicate check execute error: " . $stmt->error);
      } else {
        $result = $stmt->get_result();
        if (!$result) {
          $errors[] = "Failed to fetch duplicate check result: " . $conn->error;
          error_log("Duplicate check result error: " . $conn->error);
        } else {
          $row = $result->fetch_assoc();
          error_log("Duplicate check result: booking_count=" . $row['booking_count']);
          if ($row['booking_count'] > 0) {
            $errors[] = "You have already booked transportation for this route on this date.";
            error_log("Duplicate booking detected for user_id=$user_id, transport_type=$transport_type, route_id=$route_id, pickup_date=$pickup_date");
          }
        }
      }
      $stmt->close();
    }
  }

  // If no errors, process the booking
  if (empty($errors)) {
    $price = (float)$vehicle_types[$selected_vehicle];
    $route_name = $route['route_name'];

    // Debug: Log variables before binding
    error_log("Booking variables: " . json_encode([
      'user_id' => $user_id,
      'transport_type' => $transport_type,
      'route_id' => $route_id,
      'route_name' => $route_name,
      'vehicle_type' => $selected_vehicle,
      'price' => $price,
      'full_name' => $full_name,
      'email' => $email,
      'phone' => $phone,
      'pickup_date' => $pickup_date,
      'pickup_time' => $pickup_time,
      'pickup_location' => $pickup_location,
      'additional_notes' => $additional_notes
    ], JSON_PRETTY_PRINT));

    // Insert booking into transportation_bookings table
    $stmt = $conn->prepare("INSERT INTO transportation_bookings (user_id, transport_type, route_id, route_name, vehicle_type, price, full_name, email, phone, pickup_date, pickup_time, pickup_location, additional_notes, booking_status) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
    if (!$stmt) {
      $errors[] = "Failed to prepare insert statement: " . $conn->error;
      error_log("Insert prepare error: " . $conn->error);
    } else {
      $stmt->bind_param("isisssdssssss", $user_id, $transport_type, $route_id, $route_name, $selected_vehicle, $price, $full_name, $email, $phone, $pickup_date, $pickup_time, $pickup_location, $additional_notes);
      if ($stmt->execute()) {
        $success_message = "Your booking has been successfully submitted! You will receive a confirmation soon.";
        error_log("Booking successful for user_id=$user_id, transport_type=$transport_type, route_id=$route_id, pickup_date=$pickup_date");
      } else {
        $errors[] = "An error occurred while processing your booking: " . $stmt->error;
        error_log("Booking insert error: " . $stmt->error);
      }
      $stmt->close();
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Book Transportation - UmrahFlights</title>
  <!-- Include Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Include Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <?php include 'includes/css-links.php'; ?>
  <style>
    body {
      margin-top: 65px !important;
    }

    .form-input {
      transition: all 0.3s ease;
    }

    .form-input:focus {
      border-color: #22c55e;
      box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
    }
  </style>
</head>

<body class="bg-gray-50">
  <!-- Navbar -->
  <?php include 'includes/navbar.php'; ?>

  <!-- Main Content -->
  <section class="py-12 px-4">
    <div class="container mx-auto max-w-6xl">
      <h1 class="text-3xl font-bold text-gray-800 mb-8">
        <i class="fas fa-book mr-2 text-green-600"></i>Book Your Transportation
      </h1>

      <?php if (!empty($errors)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
          <ul class="list-disc list-inside">
            <?php foreach ($errors as $error): ?>
              <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <?php if ($success_message): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
          <p><?php echo htmlspecialchars($success_message); ?></p>
          <a href="transportation.php" class="text-green-600 hover:underline mt-2 inline-block">
            Return to Transportation
          </a>
        </div>
      <?php elseif (empty($errors) || !empty($_POST)): ?>
        <div class="bg-white rounded-lg shadow-md p-6">
          <h2 class="text-2xl font-semibold text-gray-800 mb-6">Booking Details</h2>
          <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Route and Vehicle Information -->
            <div class="md:col-span-2">
              <h3 class="text-lg font-medium text-gray-700 mb-4">Selected Route</h3>
              <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                <p class="text-gray-600">
                  <strong>Transport Type:</strong>
                  <?php echo ucfirst(htmlspecialchars($transport_type)); ?>
                </p>
                <p class="text-gray-600">
                  <strong>Route:</strong>
                  <?php echo htmlspecialchars($route['route_name'] ?? 'N/A'); ?>
                  (Route #<?php echo htmlspecialchars($route['route_number'] ?? 'N/A'); ?>)
                </p>
                <div class="mt-4">
                  <label for="vehicle_type" class="block text-sm font-medium text-gray-700 mb-2">
                    Select Vehicle Type
                  </label>
                  <select name="vehicle_type" id="vehicle_type" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 form-input" required>
                    <option value="">Choose a vehicle</option>
                    <?php foreach ($vehicle_types as $vehicle => $price): ?>
                      <option value="<?php echo htmlspecialchars($vehicle); ?>" <?php echo $selected_vehicle === $vehicle ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($vehicle); ?> (PKR <?php echo number_format($price, 2); ?>)
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
            </div>

            <!-- User Information -->
            <div>
              <label for="full_name" class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
              <input type="text" name="full_name" id="full_name" value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : htmlspecialchars($user_data['full_name'] ?? ''); ?>" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 form-input" required>
            </div>
            <div>
              <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
              <input type="email" name="email" id="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : htmlspecialchars($user_data['email'] ?? ''); ?>" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 form-input" required>
            </div>
            <div>
              <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
              <input type="tel" name="phone" id="phone" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : htmlspecialchars($user_data['phone'] ?? ''); ?>" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 form-input" required>
            </div>
            <div>
              <label for="pickup_date" class="block text-sm font-medium text-gray-700 mb-2">Pickup Date</label>
              <input type="date" name="pickup_date" id="pickup_date" value="<?php echo isset($_POST['pickup_date']) ? htmlspecialchars($_POST['pickup_date']) : ''; ?>" min="<?php echo date('Y-m-d'); ?>" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 form-input" required>
            </div>
            <div>
              <label for="pickup_time" class="block text-sm font-medium text-gray-700 mb-2">Pickup Time</label>
              <input type="time" name="pickup_time" id="pickup_time" value="<?php echo isset($_POST['pickup_time']) ? htmlspecialchars($_POST['pickup_time']) : ''; ?>" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 form-input" required>
            </div>
            <div>
              <label for="pickup_location" class="block text-sm font-medium text-gray-700 mb-2">Pickup Location</label>
              <input type="text" name="pickup_location" id="pickup_location" value="<?php echo isset($_POST['pickup_location']) ? htmlspecialchars($_POST['pickup_location']) : ''; ?>" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 form-input" required>
            </div>
            <div class="md:col-span-2">
              <label for="additional_notes" class="block text-sm font-medium text-gray-700 mb-2">Additional Notes</label>
              <textarea name="additional_notes" id="additional_notes" rows="4" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 form-input"><?php echo isset($_POST['additional_notes']) ? htmlspecialchars($_POST['additional_notes']) : ''; ?></textarea>
            </div>
            <div class="md:col-span-2 flex justify-end">
              <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-6 rounded-md transition duration-300 ease-in-out flex items-center gap-2">
                <i class="fas fa-check"></i> Confirm Booking
              </button>
            </div>
          </form>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- Footer -->
  <?php include 'includes/footer.php'; ?>
  <?php include 'includes/js-links.php'; ?>

  <script>
    // Client-side validation for pickup date
    document.getElementById('pickup_date').addEventListener('change', function() {
      const selectedDate = new Date(this.value);
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      if (selectedDate < today) {
        alert('Please select a future date for pickup.');
        this.value = '';
      }
    });
  </script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const pickupTimeInput = document.getElementById('pickup_time');

      // Add event listener for form submission or input validation
      pickupTimeInput.addEventListener('change', validateTime);
      pickupTimeInput.addEventListener('input', validateTime);

      function validateTime() {
        const timeValue = pickupTimeInput.value;

        if (!timeValue) {
          pickupTimeInput.setCustomValidity('Please enter a pickup time');
          return;
        }

        // Split the time into hours and minutes
        const [hours, minutes] = timeValue.split(':').map(Number);

        // Validate hours (0-23) and minutes (0-59)
        if (isNaN(hours) || hours < 0 || hours > 23) {
          pickupTimeInput.setCustomValidity('Please enter a valid hour (0-23)');
        } else if (isNaN(minutes) || minutes < 0 || minutes > 59) {
          pickupTimeInput.setCustomValidity('Please enter valid minutes (0-59)');
        } else {
          pickupTimeInput.setCustomValidity('');
        }

        // For immediate feedback
        pickupTimeInput.reportValidity();
      }

      // You can also add this to your form submission handler
      const form = pickupTimeInput.closest('form');
      if (form) {
        form.addEventListener('submit', function(event) {
          validateTime();
          if (!pickupTimeInput.checkValidity()) {
            event.preventDefault();
          }
        });
      }
    });
  </script>
</body>

</html>