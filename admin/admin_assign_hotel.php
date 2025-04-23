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

// Fetch booking details
$stmt = $conn->prepare("SELECT b.id, b.user_id, b.package_id, b.created_at, u.full_name 
                       FROM booking b 
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
  $stmt = $conn->prepare("SELECT * FROM hotel_bookings WHERE booking_id = ?");
  $stmt->bind_param("i", $booking_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows > 0) {
    $booking_details = $result->fetch_assoc();
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
  $hotel_id = isset($_POST['hotel_id']) ? intval($_POST['hotel_id']) : 0;
  $check_in_date = isset($_POST['check_in_date']) ? $_POST['check_in_date'] : '';
  $check_out_date = isset($_POST['check_out_date']) ? $_POST['check_out_date'] : '';
  $room_id = isset($_POST['room_id']) ? $_POST['room_id'] : '';
  $special_requests = isset($_POST['special_requests']) ? $_POST['special_requests'] : '';

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
      if ($room_status != 'available') {
        throw new Exception("Selected room is not available.");
      }
      $stmt->close();

      // Check if booking already has a hotel assigned
      if (!empty($booking_details)) {
        // Update existing booking
        $stmt = $conn->prepare("UPDATE hotel_bookings 
                              SET hotel_id = ?, room_id = ?, check_in_date = ?, 
                                  check_out_date = ?, total_price = ?, 
                                  special_requests = ?, updated_at = NOW() 
                              WHERE booking_id = ?");
        $stmt->bind_param(
          "isssdsi",
          $hotel_id,
          $room_id,
          $check_in_date,
          $check_out_date,
          $total_price,
          $special_requests,
          $booking_id
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
                              (booking_id, user_id, hotel_id, room_id, check_in_date, 
                               check_out_date, total_price, booking_status, payment_status, 
                               booking_reference, special_requests) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, 'confirmed', 'paid', ?, ?)");
        $stmt->bind_param(
          "iiisssss",
          $booking_id,
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

      // Refresh booking details
      $stmt = $conn->prepare("SELECT * FROM hotel_bookings WHERE booking_id = ?");
      $stmt->bind_param("i", $booking_id);
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
  $hotel_id = isset($_POST['hotel_id']) ? intval($_POST['hotel_id']) : 0;
  $check_in_date = isset($_POST['check_in_date']) ? $_POST['check_in_date'] : '';
  $check_out_date = isset($_POST['check_out_date']) ? $_POST['check_out_date'] : '';

  if ($hotel_id <= 0 || empty($check_in_date) || empty($check_out_date)) {
    $error_message = "Please provide hotel, check-in and check-out dates.";
  } elseif (strtotime($check_out_date) <= strtotime($check_in_date)) {
    $error_message = "Check-out date must be after check-in date.";
  } else {
    // Query available rooms
    $stmt = $conn->prepare("SELECT room_id FROM hotel_rooms 
                          WHERE hotel_id = ? AND status = 'available' 
                          AND room_id NOT IN (
                              SELECT room_id FROM hotel_bookings 
                              WHERE hotel_id = ? 
                              AND ((check_in_date <= ? AND check_out_date >= ?) 
                                   OR (check_in_date <= ? AND check_out_date >= ?) 
                                   OR (check_in_date >= ? AND check_out_date <= ?))
                              AND booking_status != 'cancelled'
                          )");
    $stmt->bind_param(
      "iissssss",
      $hotel_id,
      $hotel_id,
      $check_out_date,
      $check_in_date,
      $check_in_date,
      $check_in_date,
      $check_in_date,
      $check_out_date
    );
    $stmt->execute();
    $available_rooms_result = $stmt->get_result();
    $available_rooms = [];
    while ($row = $available_rooms_result->fetch_assoc()) {
      $available_rooms[] = $row['room_id'];
    }
    $stmt->close();
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Assign Hotel | Admin Panel</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body class="bg-gray-100 min-h-screen">
  <?php include 'includes/sidebar.php'; ?>

  <div class="ml-0 md:ml-64 p-6">
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
          <i class="fas fa-hotel text-blue-500 mr-2"></i>Assign Hotel
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
            <h3 class="font-bold">Current Hotel Assignment</h3>
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
              <select name="hotel_id" id="hotel_id" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" required>
                <option value="">-- Select Hotel --</option>
                <?php foreach ($hotels as $hotel): ?>
                  <option value="<?php echo $hotel['id']; ?>" <?php echo (!empty($booking_details) && $booking_details['hotel_id'] == $hotel['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($hotel['hotel_name']); ?> (<?php echo ucfirst($hotel['location']); ?>) - PKR <?php echo number_format($hotel['price'], 2); ?>/night
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div>
              <label for="room_id" class="block text-sm font-medium text-gray-700 mb-1">Room ID</label>
              <select name="room_id" id="room_id" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" required <?php echo empty($available_rooms) ? 'disabled' : ''; ?>>
                <?php if (!empty($available_rooms)): ?>
                  <option value="">-- Select Room --</option>
                  <?php foreach ($available_rooms as $room): ?>
                    <option value="<?php echo $room; ?>" <?php echo (!empty($booking_details) && $booking_details['room_id'] == $room) ? 'selected' : ''; ?>>
                      <?php echo $room; ?>
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
                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                value="<?php echo !empty($booking_details) ? $booking_details['check_in_date'] : ''; ?>"
                required>
            </div>

            <div>
              <label for="check_out_date" class="block text-sm font-medium text-gray-700 mb-1">Check-out Date</label>
              <input type="date" name="check_out_date" id="check_out_date"
                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                value="<?php echo !empty($booking_details) ? $booking_details['check_out_date'] : ''; ?>"
                required>
            </div>
          </div>

          <div>
            <label for="special_requests" class="block text-sm font-medium text-gray-700 mb-1">Special Requests</label>
            <textarea name="special_requests" id="special_requests" rows="3"
              class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"><?php echo !empty($booking_details) ? htmlspecialchars($booking_details['special_requests']) : ''; ?></textarea>
          </div>

          <div class="flex justify-between">
            <button type="submit" name="check_availability" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
              <i class="fas fa-search mr-2"></i>Check Room Availability
            </button>

            <button type="submit" name="assign_hotel" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
              <i class="fas fa-check mr-2"></i>Assign Hotel
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

    // Form submission confirmation
    document.getElementById('assignHotelForm').addEventListener('submit', function(e) {
      if (e.submitter && e.submitter.name === 'assign_hotel') {
        if (!confirm('Are you sure you want to assign this hotel to the booking?')) {
          e.preventDefault();
        }
      }
    });
  </script>
</body>

</html>