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
$hotels = [];
$booking_details = [];
$available_rooms = [];

// Retrieve available rooms from session if they exist
if (isset($_SESSION['available_rooms_' . $booking_id])) {
  $available_rooms = $_SESSION['available_rooms_' . $booking_id];
}

// Store form values to maintain state after submission
$form_hotel_id = isset($_POST['hotel_id']) ? intval($_POST['hotel_id']) : 0;
$form_check_in_date = isset($_POST['check_in_date']) ? $_POST['check_in_date'] : '';
$form_check_out_date = isset($_POST['check_out_date']) ? $_POST['check_out_date'] : '';
$form_room_id = isset($_POST['room_id']) ? $_POST['room_id'] : '';
$form_special_requests = isset($_POST['special_requests']) ? $_POST['special_requests'] : '';

// Fetch booking details
$stmt = $conn->prepare("SELECT b.id, b.user_id, b.package_id, b.created_at, u.full_name 
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

// Check if hotel is already assigned to this booking
if ($booking) {
  $stmt = $conn->prepare("SELECT * FROM hotel_bookings WHERE user_id = ?");
  $stmt->bind_param("i", $booking['user_id']);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows > 0) {
    $booking_details = $result->fetch_assoc();

    // Use booking details as default form values if not already set
    if (empty($form_hotel_id)) {
      $form_hotel_id = $booking_details['hotel_id'];
    }
    if (empty($form_check_in_date)) {
      $form_check_in_date = $booking_details['check_in_date'];
    }
    if (empty($form_check_out_date)) {
      $form_check_out_date = $booking_details['check_out_date'];
    }
    if (empty($form_room_id)) {
      $form_room_id = $booking_details['room_id'];
    }
    if (empty($form_special_requests)) {
      $form_special_requests = $booking_details['special_requests'];
    }
  }
  $stmt->close();
}

// Fetch available hotels
$stmt = $conn->prepare("SELECT * FROM hotels");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $hotels[] = $row;
}
$stmt->close();

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['assign_hotel'])) {
  $hotel_id = $form_hotel_id;
  $check_in_date = $form_check_in_date;
  $check_out_date = $form_check_out_date;
  $room_id = $form_room_id;
  $special_requests = $form_special_requests;

  // Validate inputs
  if ($hotel_id <= 0) {
    $error_message = "Please select a valid hotel.";
  } elseif (empty($check_in_date) || empty($check_out_date)) {
    $error_message = "Check-in and check-out dates are required.";
  } elseif (strtotime($check_out_date) <= strtotime($check_in_date)) {
    $error_message = "Check-out date must be after check-in date.";
  } elseif (empty($room_id)) {
    $error_message = "Please select a room.";
  } else {
    // Calculate total price based on number of days
    $check_in = new DateTime($check_in_date);
    $check_out = new DateTime($check_out_date);
    $nights = $check_in->diff($check_out)->days;

    // Get hotel price
    $stmt = $conn->prepare("SELECT price FROM hotels WHERE id = ?");
    $stmt->bind_param("i", $hotel_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $hotel = $result->fetch_assoc();
    $price_per_night = $hotel['price'];
    $total_price = $price_per_night * $nights;
    $stmt->close();

    // Start transaction
    $conn->begin_transaction();
    try {
      // Check if room is available
      $stmt = $conn->prepare("SELECT status FROM hotel_rooms WHERE hotel_id = ? AND room_id = ?");
      $stmt->bind_param("is", $hotel_id, $room_id);
      $stmt->execute();
      $result = $stmt->get_result();
      if ($result->num_rows == 0) {
        throw new Exception("Selected room does not exist.");
      }
      $room_status = $result->fetch_assoc()['status'];

      // Check if room is marked as booked but might actually be available
      if ($room_status != 'available' && empty($booking_details)) {
        // Verify if the room is actually booked for the selected dates
        $stmt->close();
        $stmt = $conn->prepare("SELECT COUNT(*) as booking_count FROM hotel_bookings 
                             WHERE hotel_id = ? AND room_id = ? 
                             AND ((check_in_date <= ? AND check_out_date >= ?) 
                                OR (check_in_date <= ? AND check_out_date >= ?) 
                                OR (check_in_date >= ? AND check_out_date <= ?))
                             AND booking_status != 'cancelled'");
        $stmt->bind_param(
          "isssssss",
          $hotel_id,
          $room_id,
          $check_out_date,
          $check_in_date,
          $check_in_date,
          $check_in_date,
          $check_in_date,
          $check_out_date
        );
        $stmt->execute();
        $result = $stmt->get_result();
        $booking_count = $result->fetch_assoc()['booking_count'];
        $stmt->close();

        if ($booking_count > 0) {
          throw new Exception("Selected room is booked for the selected dates.");
        } else {
          // Update room status since it's incorrectly marked as booked
          $stmt = $conn->prepare("UPDATE hotel_rooms SET status = 'available' WHERE hotel_id = ? AND room_id = ?");
          $stmt->bind_param("is", $hotel_id, $room_id);
          $stmt->execute();
          $stmt->close();
        }
      }

      // If we have an existing booking using this room, make sure we can update it
      if (!empty($booking_details) && $booking_details['room_id'] != $room_id) {
        // We're changing to a new room, so check if the new room is booked by someone else
        $stmt = $conn->prepare("SELECT COUNT(*) as booking_count FROM hotel_bookings 
                             WHERE hotel_id = ? AND room_id = ? 
                             AND user_id != ? 
                             AND ((check_in_date <= ? AND check_out_date >= ?) 
                                OR (check_in_date <= ? AND check_out_date >= ?) 
                                OR (check_in_date >= ? AND check_out_date <= ?))
                             AND booking_status != 'cancelled'");
        $user_id = $booking['user_id'];
        $stmt->bind_param(
          "isisssss",
          $hotel_id,
          $room_id,
          $user_id,
          $check_out_date,
          $check_in_date,
          $check_in_date,
          $check_in_date,
          $check_in_date,
          $check_out_date
        );
        $stmt->execute();
        $result = $stmt->get_result();
        $booking_count = $result->fetch_assoc()['booking_count'];
        $stmt->close();

        if ($booking_count > 0) {
          throw new Exception("The new room you selected is already booked for these dates by another user.");
        }
      }

      // Check if booking already has a hotel assigned
      if (!empty($booking_details)) {
        // Update existing booking
        $stmt = $conn->prepare("UPDATE hotel_bookings 
                              SET hotel_id = ?, room_id = ?, check_in_date = ?, 
                                  check_out_date = ?, total_price = ?, 
                                  special_requests = ?, updated_at = NOW() 
                              WHERE user_id = ?");
        $user_id = $booking['user_id'];
        $stmt->bind_param(
          "isssdsi",
          $hotel_id,
          $room_id,
          $check_in_date,
          $check_out_date,
          $total_price,
          $special_requests,
          $user_id
        );
        if (!$stmt->execute()) {
          throw new Exception("Error updating hotel booking: " . $stmt->error);
        }
        $stmt->close();
      } else {
        // Create new booking
        $booking_reference = 'HB' . strtoupper(uniqid());
        $user_id = $booking['user_id'];
        $stmt = $conn->prepare("INSERT INTO hotel_bookings 
                              (user_id, hotel_id, room_id, check_in_date, 
                               check_out_date, total_price, booking_status, payment_status, 
                               booking_reference, special_requests) 
                              VALUES (?, ?, ?, ?, ?, ?, 'confirmed', 'paid', ?, ?)");
        $stmt->bind_param(
          "iisssdss",
          $user_id,
          $hotel_id,
          $room_id,
          $check_in_date,
          $check_out_date,
          $total_price,
          $booking_reference,
          $special_requests
        );
        if (!$stmt->execute()) {
          throw new Exception("Error creating hotel booking: " . $stmt->error);
        }
        $stmt->close();
      }

      // Update room status to booked
      $stmt = $conn->prepare("UPDATE hotel_rooms SET status = 'booked' WHERE hotel_id = ? AND room_id = ?");
      $stmt->bind_param("is", $hotel_id, $room_id);
      if (!$stmt->execute()) {
        throw new Exception("Error updating room status: " . $stmt->error);
      }
      $stmt->close();

      // Commit the transaction
      $conn->commit();
      $success_message = "Hotel assigned successfully to booking #$booking_id.";

      // Clear the session variable after successful assignment
      if (isset($_SESSION['available_rooms_' . $booking_id])) {
        unset($_SESSION['available_rooms_' . $booking_id]);
      }

      // Refresh booking details
      $stmt = $conn->prepare("SELECT * FROM hotel_bookings WHERE user_id = ?");
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

// Check for room availability based on selected hotel and dates
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['check_availability'])) {
  $hotel_id = $form_hotel_id;
  $check_in_date = $form_check_in_date;
  $check_out_date = $form_check_out_date;

  if ($hotel_id <= 0 || empty($check_in_date) || empty($check_out_date)) {
    $error_message = "Please provide hotel, check-in and check-out dates.";
  } elseif (strtotime($check_out_date) <= strtotime($check_in_date)) {
    $error_message = "Check-out date must be after check-in date.";
  } else {
    // First get all rooms for the hotel
    $stmt = $conn->prepare("SELECT room_id, status FROM hotel_rooms WHERE hotel_id = ?");
    $stmt->bind_param("i", $hotel_id);
    $stmt->execute();
    $all_rooms_result = $stmt->get_result();
    $all_rooms = [];
    $available_rooms = [];

    while ($row = $all_rooms_result->fetch_assoc()) {
      $all_rooms[] = $row;
      // Add rooms that are marked as available
      if ($row['status'] == 'available') {
        $available_rooms[] = $row['room_id'];
      }
    }
    $stmt->close();

    // Then find rooms that are actually booked for the requested dates
    $stmt = $conn->prepare("SELECT DISTINCT room_id FROM hotel_bookings 
                         WHERE hotel_id = ? 
                         AND ((check_in_date <= ? AND check_out_date >= ?) 
                              OR (check_in_date <= ? AND check_out_date >= ?) 
                              OR (check_in_date >= ? AND check_out_date <= ?))
                         AND booking_status != 'cancelled'");
    $stmt->bind_param(
      "issssss",
      $hotel_id,
      $check_out_date,
      $check_in_date,
      $check_in_date,
      $check_in_date,
      $check_in_date,
      $check_out_date
    );
    $stmt->execute();
    $booked_rooms_result = $stmt->get_result();
    $booked_rooms = [];

    while ($row = $booked_rooms_result->fetch_assoc()) {
      $booked_rooms[] = $row['room_id'];
    }
    $stmt->close();

    // Find rooms that are marked as booked but don't have active bookings
    // These might be rooms where bookings were deleted or canceled
    foreach ($all_rooms as $room) {
      $room_id = $room['room_id'];
      // If room is marked as booked but not in our booked list for these dates
      if ($room['status'] == 'booked' && !in_array($room_id, $booked_rooms)) {
        // Check if this room has any active bookings at all
        $stmt = $conn->prepare("SELECT COUNT(*) as booking_count FROM hotel_bookings 
                             WHERE hotel_id = ? AND room_id = ? AND booking_status != 'cancelled'");
        $stmt->bind_param("is", $hotel_id, $room_id);
        $stmt->execute();
        $count_result = $stmt->get_result();
        $count_row = $count_result->fetch_assoc();
        $stmt->close();

        // If no active bookings exist, this room should be available
        if ($count_row['booking_count'] == 0) {
          // Add to available rooms list
          $available_rooms[] = $room_id;

          // Fix the room status in the database
          $stmt = $conn->prepare("UPDATE hotel_rooms SET status = 'available' WHERE hotel_id = ? AND room_id = ?");
          $stmt->bind_param("is", $hotel_id, $room_id);
          $stmt->execute();
          $stmt->close();
        }
      }
    }

    // Remove rooms that are actually booked from our available list
    $available_rooms = array_diff($available_rooms, $booked_rooms);

    // Convert back to array format (using array_values to reindex)
    $available_rooms = array_values($available_rooms);

    // If current room is already assigned, add it to available rooms
    if (!empty($booking_details) && $booking_details['hotel_id'] == $hotel_id) {
      if (!in_array($booking_details['room_id'], $available_rooms)) {
        $available_rooms[] = $booking_details['room_id'];
      }
    }

    // Store available rooms in session
    $_SESSION['available_rooms_' . $booking_id] = $available_rooms;

    if (empty($available_rooms)) {
      $error_message = "No rooms available for the selected dates. Please choose different dates.";
    } else {
      $success_message = count($available_rooms) . " room(s) available for the selected dates.";
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Assign Hotel | UmrahFlights</title>
  <!-- Tailwind CSS (same as index.php, add-transportation.php, and assign-flight.php) -->
  <link rel="stylesheet" href="../src/output.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <!-- SweetAlert2 (for consistency with add-transportation.php and assign-flight.php) -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-gray-100 font-sans min-h-screen">
  <?php include 'includes/sidebar.php'; ?>
  <main class="ml-0 md:ml-64 mt-10 px-4 sm:px-6 lg:px-8 transition-all duration-300" role="main" aria-label="Main content">
    <!-- Top Navbar (aligned with index.php, add-transportation.php, and assign-flight.php) -->
    <nav class="bg-white shadow-lg rounded-lg p-5 mb-6">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <button id="sidebarToggle" class="text-gray-500 hover:text-gray-700 focus:outline-none md:hidden" aria-label="Toggle sidebar">
            <i class="fas fa-bars text-xl"></i>
          </button>
          <h4 id="dashboardHeader" class="text-lg font-semibold text-gray-800 cursor-pointer hover:text-indigo-600">Assign Hotel</h4>
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
    <section class="bg-white shadow-lg rounded-lg p-6" aria-label="Hotel assignment">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">
          <i class="fas fa-hotel text-indigo-600 mr-2"></i>Assign Hotel
        </h2>
        <a href="index.php" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">
          <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
        </a>
      </div>

      <!-- Alerts (aligned with index.php, add-transportation.php, and assign-flight.php) -->
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
            <h3 class="font-bold text-lg text-gray-800">Current Hotel Assignment</h3>
            <p>Hotel ID: <?php echo $booking_details['hotel_id']; ?></p>
            <p>Room: <?php echo $booking_details['room_id']; ?></p>
            <p>Check-in: <?php echo date('F j, Y', strtotime($booking_details['check_in_date'])); ?></p>
            <p>Check-out: <?php echo date('F j, Y', strtotime($booking_details['check_out_date'])); ?></p>
            <p>Total Price: PKR <?php echo number_format($booking_details['total_price'], 2); ?></p>
            <p class="mt-2">You can update this assignment using the form below.</p>
          </div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-6" id="assignHotelForm">
          <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label for="hotel_id" class="block text-sm font-medium text-gray-700 mb-1">Select Hotel</label>
              <select name="hotel_id" id="hotel_id" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500" required>
                <option value="">-- Select Hotel --</option>
                <?php foreach ($hotels as $hotel): ?>
                  <option value="<?php echo $hotel['id']; ?>" <?php echo ($form_hotel_id == $hotel['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($hotel['hotel_name']); ?> (<?php echo ucfirst($hotel['location']); ?>) - PKR <?php echo number_format($hotel['price'], 2); ?>/night
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div>
              <label for="room_id" class="block text-sm font-medium text-gray-700 mb-1">Room ID</label>
              <select name="room_id" id="room_id" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500" required <?php echo empty($available_rooms) && empty($booking_details) ? 'disabled' : ''; ?>>
                <?php if (!empty($available_rooms)): ?>
                  <option value="">-- Select Room --</option>
                  <?php foreach ($available_rooms as $room): ?>
                    <option value="<?php echo $room; ?>" <?php echo ($form_room_id == $room) ? 'selected' : ''; ?>>
                      <?php echo $room; ?> <?php echo (!empty($booking_details) && $booking_details['room_id'] == $room) ? '(Currently Assigned)' : ''; ?>
                    </option>
                  <?php endforeach; ?>
                <?php elseif (!empty($booking_details)): ?>
                  <option value="<?php echo $booking_details['room_id']; ?>" selected>
                    <?php echo $booking_details['room_id']; ?> (Currently Assigned)
                  </option>
                <?php else: ?>
                  <option value="">-- Check availability first --</option>
                <?php endif; ?>
              </select>
            </div>

            <div>
              <label for="check_in_date" class="block text-sm font-medium text-gray-700 mb-1">Check-in Date</label>
              <input type="date" name="check_in_date" id="check_in_date"
                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                value="<?php echo $form_check_in_date; ?>"
                min="<?php echo date('Y-m-d'); ?>"
                required>
            </div>

            <div>
              <label for="check_out_date" class="block text-sm font-medium text-gray-700 mb-1">Check-out Date</label>
              <input type="date" name="check_out_date" id="check_out_date"
                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                value="<?php echo $form_check_out_date; ?>"
                min="<?php echo !empty($form_check_in_date) ? $form_check_in_date : date('Y-m-d', strtotime('+1 day')); ?>"
                required>
            </div>
          </div>

          <div>
            <label for="special_requests" class="block text-sm font-medium text-gray-700 mb-1">Special Requests</label>
            <textarea name="special_requests" id="special_requests" rows="3"
              class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"><?php echo htmlspecialchars($form_special_requests); ?></textarea>
          </div>

          <div class="flex justify-between">
            <button type="submit" name="check_availability" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
              <i class="fas fa-search mr-2"></i>Check Room Availability
            </button>

            <button type="submit" name="assign_hotel" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700" <?php echo empty($available_rooms) && empty($booking_details) ? 'disabled' : ''; ?>>
              <i class="fas fa-check mr-2"></i><?php echo !empty($booking_details) ? 'Update Hotel Assignment' : 'Assign Hotel'; ?>
            </button>
          </div>

          <?php if (empty($available_rooms) && empty($booking_details)): ?>
            <div class="mt-4 text-red-500 text-center">
              <p><i class="fas fa-exclamation-circle mr-2"></i>Please check room availability before assigning a hotel</p>
            </div>
          <?php endif; ?>
        </form>
      <?php else: ?>
        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded-lg">
          <p>Booking not found or invalid booking ID.</p>
        </div>
      <?php endif; ?>
    </section>
  </main>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Sidebar elements (aligned with index.php, add-transportation.php, and assign-flight.php)
      const sidebar = document.getElementById('sidebar');
      const sidebarOverlay = document.getElementById('sidebar-overlay');
      const sidebarToggle = document.getElementById('sidebarToggle');
      const sidebarClose = document.getElementById('sidebar-close');
      const dashboardHeader = document.getElementById('dashboardHeader');

      // User dropdown elements
      const userDropdownButton = document.getElementById('userDropdownButton');
      const userDropdownMenu = document.getElementById('userDropdownMenu');

      // Error handling for missing elements
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

      // Validate dates
      document.getElementById('check_in_date').addEventListener('change', function() {
        const checkOutInput = document.getElementById('check_out_date');
        checkOutInput.min = this.value;

        if (checkOutInput.value && new Date(checkOutInput.value) <= new Date(this.value)) {
          // Set checkout to day after checkin
          const nextDay = new Date(this.value);
          nextDay.setDate(nextDay.getDate() + 1);
          checkOutInput.value = nextDay.toISOString().split('T')[0];
        }
      });

      // Form submission confirmation with SweetAlert2
      document.getElementById('assignHotelForm').addEventListener('submit', function(e) {
        if (e.submitter && e.submitter.name === 'assign_hotel') {
          e.preventDefault();
          Swal.fire({
            title: 'Are you sure?',
            text: 'Do you want to assign this hotel to the booking?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#4f46e5',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, assign it!',
            cancelButtonText: 'Cancel'
          }).then((result) => {
            if (result.isConfirmed) {
              this.submit();
            }
          });
        }
      });

      // Disable assign button if no room is selected
      const roomSelect = document.getElementById('room_id');
      const assignButton = document.querySelector('button[name="assign_hotel"]');

      roomSelect.addEventListener('change', function() {
        assignButton.disabled = !this.value;
      });

      // Enable room selection dropdown when hotel_id changes and check availability button is clicked
      document.getElementById('hotel_id').addEventListener('change', function() {
        if (this.value) {
          document.getElementById('check_in_date').focus();
        }
      });
    });
  </script>
</body>

</html>