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

// Store form values to maintain state after submission
$form_hotel_id = isset($_POST['hotel_id']) ? intval($_POST['hotel_id']) : 0;
$form_check_in_date = isset($_POST['check_in_date']) ? $_POST['check_in_date'] : '';
$form_check_out_date = isset($_POST['check_out_date']) ? $_POST['check_out_date'] : '';
$form_room_id = isset($_POST['room_id']) ? $_POST['room_id'] : '';
$form_special_requests = isset($_POST['special_requests']) ? $_POST['special_requests'] : '';

// Function to send email notifications
function sendEmailNotification($to, $subject, $message, $action, $booking_id)
{
  $headers = "From: Umrah Partner Team <no-reply@umrahpartner.com>\r\n";
  $headers .= "Reply-To: info@umrahflights.com\r\n";
  $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

  $result = @mail($to, $subject, $message, $headers); // @ suppresses warnings
  if (!$result) {
    error_log("Failed to send email for action '$action' on booking #$booking_id to $to: " . error_get_last()['message']);
  }
  return $result;
}

// Fetch booking details including user email
$stmt = $conn->prepare("SELECT b.id, b.user_id, b.package_id, b.created_at, u.full_name, u.email as user_email 
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

// Process form submission for assigning/updating hotel
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

    // Get hotel price and name
    $stmt = $conn->prepare("SELECT hotel_name, price FROM hotels WHERE id = ?");
    $stmt->bind_param("i", $hotel_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $hotel = $result->fetch_assoc();
    $hotel_name = $hotel['hotel_name'];
    $price_per_night = $hotel['price'];
    $total_price = $price_per_night * $nights;
    $stmt->close();

    // Start transaction
    $conn->begin_transaction();
    try {
      // Check if room exists
      $stmt = $conn->prepare("SELECT status FROM hotel_rooms WHERE hotel_id = ? AND room_id = ?");
      $stmt->bind_param("is", $hotel_id, $room_id);
      $stmt->execute();
      $result = $stmt->get_result();
      if ($result->num_rows == 0) {
        throw new Exception("Selected room does not exist.");
      }
      $room_status = $result->fetch_assoc()['status'];
      $stmt->close();

      // Check if room is marked as booked but might actually be available
      if ($room_status != 'available' && empty($booking_details)) {
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

      // If we have an existing booking, handle room reassignment
      $old_room_id = !empty($booking_details) ? $booking_details['room_id'] : null;
      if (!empty($booking_details) && $booking_details['room_id'] != $room_id) {
        // We're changing to a new room, so check if the new room is booked by someone else
        $stmt = $conn->prepare("SELECT COUNT(*) as booking_count FROM hotel_bookings 
                             WHERE hotel_id = ? AND room_id = ? 
                             AND user_id != ? 
                             AND id != ?  -- Exclude the current booking
                             AND ((check_in_date <= ? AND check_out_date >= ?) 
                                OR (check_in_date <= ? AND check_out_date >= ?) 
                                OR (check_in_date >= ? AND check_out_date <= ?))
                             AND booking_status != 'cancelled'");
        $user_id = $booking['user_id'];
        $current_booking_id = $booking_details['id'];
        $stmt->bind_param(
          "isiiisssss",
          $hotel_id,
          $room_id,
          $user_id,
          $current_booking_id,
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

        // Free up the old room (set status to available if no other bookings exist)
        if ($old_room_id) {
          $stmt = $conn->prepare("SELECT COUNT(*) as booking_count FROM hotel_bookings 
                               WHERE hotel_id = ? AND room_id = ? 
                               AND id != ? 
                               AND booking_status != 'cancelled'");
          $stmt->bind_param("isi", $hotel_id, $old_room_id, $current_booking_id);
          $stmt->execute();
          $result = $stmt->get_result();
          $booking_count = $result->fetch_assoc()['booking_count'];
          $stmt->close();

          if ($booking_count == 0) {
            $stmt = $conn->prepare("UPDATE hotel_rooms SET status = 'available' WHERE hotel_id = ? AND room_id = ?");
            $stmt->bind_param("is", $hotel_id, $old_room_id);
            $stmt->execute();
            $stmt->close();
          }
        }
      }

      // Check if booking already has a hotel assigned
      if (!empty($booking_details)) {
        // Update existing booking
        $stmt = $conn->prepare("UPDATE hotel_bookings 
                              SET hotel_id = ?, room_id = ?, check_in_date = ?, 
                                  check_out_date = ?, total_price = ?, 
                                  special_requests = ?, updated_at = NOW() 
                              WHERE user_id = ? AND id = ?");
        $user_id = $booking['user_id'];
        $current_booking_id = $booking_details['id'];
        $stmt->bind_param(
          "isssdssi",
          $hotel_id,
          $room_id,
          $check_in_date,
          $check_out_date,
          $total_price,
          $special_requests,
          $user_id,
          $current_booking_id
        );
        if (!$stmt->execute()) {
          throw new Exception("Error updating hotel booking: " . $stmt->error);
        }
        $stmt->close();

        $action_type = "update";
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

        $action_type = "assign";
      }

      // Update the new room status to booked
      $stmt = $conn->prepare("UPDATE hotel_rooms SET status = 'booked' WHERE hotel_id = ? AND room_id = ?");
      $stmt->bind_param("is", $hotel_id, $room_id);
      if (!$stmt->execute()) {
        throw new Exception("Error updating room status: " . $stmt->error);
      }
      $stmt->close();

      // Commit the transaction
      $conn->commit();
      $success_message = "Hotel " . ($action_type == "update" ? "updated" : "assigned") . " successfully to booking #$booking_id.";

      // Send email to user
      $user_email = $booking['user_email'];
      $user_subject = 'Your Hotel Booking Has Been ' . ($action_type == "update" ? 'Updated' : 'Assigned') . ' - UmrahFlights';
      $user_message = "Dear " . htmlspecialchars($booking['full_name']) . ",\n\n";
      $user_message .= "Your hotel booking for Booking ID #$booking_id has been " . ($action_type == "update" ? "updated" : "assigned") . ".\n\n";
      $user_message .= "Hotel Details:\n";
      $user_message .= "Hotel Name: " . htmlspecialchars($hotel_name) . "\n";
      $user_message .= "Room ID: " . htmlspecialchars($room_id) . "\n";
      $user_message .= "Check-in Date: " . date('D, M j, Y', strtotime($check_in_date)) . "\n";
      $user_message .= "Check-out Date: " . date('D, M j, Y', strtotime($check_out_date)) . "\n";
      $user_message .= "Total Price: PKR " . number_format($total_price, 0) . "\n";
      $user_message .= "Special Requests: " . (empty($special_requests) ? "None" : htmlspecialchars($special_requests)) . "\n\n";
      $user_message .= "For any queries, contact us at info@umrahflights.com.\n\n";
      $user_message .= "Best regards,\nUmrahFlights Team";
      sendEmailNotification($user_email, $user_subject, $user_message, $action_type, $booking_id);

      // Send email to admin
      $admin_to = 'info@umrahpartner.com';
      $admin_subject = 'Hotel Booking ' . ($action_type == "update" ? 'Updated' : 'Assigned') . ' - Admin Notification';
      $admin_message = "Hotel Booking for Booking ID #$booking_id has been " . ($action_type == "update" ? "updated" : "assigned") . ".\n\n";
      $admin_message .= "Details:\n";
      $admin_message .= "Guest: " . htmlspecialchars($booking['full_name']) . "\n";
      $admin_message .= "Email: " . htmlspecialchars($booking['user_email']) . "\n";
      $admin_message .= "Hotel Name: " . htmlspecialchars($hotel_name) . "\n";
      $admin_message .= "Room ID: " . htmlspecialchars($room_id) . "\n";
      $admin_message .= "Check-in Date: " . date('D, M j, Y', strtotime($check_in_date)) . "\n";
      $admin_message .= "Check-out Date: " . date('D, M j, Y', strtotime($check_out_date)) . "\n";
      $admin_message .= "Total Price: PKR " . number_format($total_price, 0) . "\n";
      $admin_message .= "Special Requests: " . (empty($special_requests) ? "None" : htmlspecialchars($special_requests)) . "\n";
      sendEmailNotification($admin_to, $admin_subject, $admin_message, $action_type, $booking_id);

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
    foreach ($all_rooms as $room) {
      $room_id = $room['room_id'];
      if ($room['status'] == 'booked' && !in_array($room_id, $booked_rooms)) {
        $stmt = $conn->prepare("SELECT COUNT(*) as booking_count FROM hotel_bookings 
                             WHERE hotel_id = ? AND room_id = ? AND booking_status != 'cancelled'");
        $stmt->bind_param("is", $hotel_id, $room_id);
        $stmt->execute();
        $count_result = $stmt->get_result();
        $count_row = $count_result->fetch_assoc();
        $stmt->close();

        if ($count_row['booking_count'] == 0) {
          $available_rooms[] = $room_id;

          $stmt = $conn->prepare("UPDATE hotel_rooms SET status = 'available' WHERE hotel_id = ? AND room_id = ?");
          $stmt->bind_param("is", $hotel_id, $room_id);
          $stmt->execute();
          $stmt->close();
        }
      }
    }

    // Remove rooms that are actually booked from our available list
    $available_rooms = array_diff($available_rooms, $booked_rooms);
    $available_rooms = array_values($available_rooms);

    // If current room is already assigned, add it to available rooms
    if (!empty($booking_details) && $booking_details['hotel_id'] == $hotel_id) {
      if (!in_array($booking_details['room_id'], $available_rooms)) {
        $available_rooms[] = $booking_details['room_id'];
      }
    }

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
  <!-- Tailwind CSS -->
  <link rel="stylesheet" href="../src/output.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-gray-100 font-sans min-h-screen">
  <?php include 'includes/sidebar.php'; ?>
  <main class="ml-0 md:ml-64 mt-10 px-4 sm:px-6 lg:px-8 transition-all duration-300" role="main" aria-label="Main content">
    <!-- Top Navbar -->
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

      <!-- Alerts -->
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

            <div class="room-select-container">
              <label for="room_id" class="block text-sm font-medium text-gray-700 mb-2">
                Room ID <span class="text-gray-500 text-xs">(Select hotel, dates, and check availability first)</span>
              </label>
              <div class="relative">
                <select 
                  name="room_id" 
                  id="room_id" 
                  class="room-select w-full px-4 py-3 bg-white border border-gray-200 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 appearance-none disabled:bg-gray-100 disabled:cursor-not-allowed" 
                  required 
                  <?php echo empty($available_rooms) && empty($booking_details) ? 'disabled' : ''; ?>
                >
                  <?php if (!empty($available_rooms)): ?>
                    <option value="">-- Select Room --</option>
                    <?php foreach ($available_rooms as $room): ?>
                      <option 
                        value="<?php echo htmlspecialchars($room); ?>" 
                        <?php echo ($form_room_id == $room) ? 'selected' : ''; ?>
                      >
                        <?php echo htmlspecialchars($room); ?> 
                        <?php echo (!empty($booking_details) && $booking_details['room_id'] == $room) ? '(Currently Assigned)' : ''; ?>
                      </option>
                    <?php endforeach; ?>
                  <?php elseif (!empty($booking_details)): ?>
                    <option value="<?php echo htmlspecialchars($booking_details['room_id']); ?>" selected>
                      <?php echo htmlspecialchars($booking_details['room_id']); ?> (Currently Assigned)
                    </option>
                  <?php else: ?>
                    <option value="">-- Check availability first --</option>
                  <?php endif; ?>
                </select>
                <!-- Custom arrow icon -->
                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                  <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </div>
              </div>
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

  <style>
    .room-select-container {
      max-width: 100%;
      margin-bottom: 1.5rem;
    }

    .room-select {
      font-size: 0.875rem;
      color: #374151;
      line-height: 1.5;
    }

    .room-select:focus {
      outline: none;
      box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }

    .room-select:disabled {
      opacity: 0.7;
    }

    @media (max-width: 640px) {
      .room-select {
        padding: 0.75rem 1rem;
        font-size: 0.85rem;
      }
    }

    .room-select:not(:disabled):hover {
      border-color: #6366f1;
    }
  </style>

  <script>
    // Validate dates
    document.getElementById('check_in_date').addEventListener('change', function() {
      const checkOutInput = document.getElementById('check_out_date');
      checkOutInput.min = this.value;

      if (checkOutInput.value && new Date(checkOutInput.value) <= new Date(this.value)) {
        const nextDay = new Date(this.value);
        nextDay.setDate(nextDay.getDate() + 1);
        checkOutInput.value = nextDay.toISOString().split('T')[0];
      }
    });

    // Form submission confirmation
    document.getElementById('assignHotelForm').addEventListener('submit', function(e) {
      if (e.submitter && e.submitter.name === 'assign_hotel') {
        if (!confirm('Are you sure you want to ' . (<?php echo !empty($booking_details) ? "'update'" : "'assign'"; ?> + ' this hotel to the booking?'))) {
          e.preventDefault();
        }
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
  </script>
</body>

</html>