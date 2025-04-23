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
$flights = [];
$booking_details = [];

// Store form values to maintain state after submission
$form_flight_id = isset($_POST['flight_id']) ? intval($_POST['flight_id']) : 0;
$form_cabin_class = isset($_POST['cabin_class']) ? $_POST['cabin_class'] : '';
$form_adult_count = isset($_POST['adult_count']) ? intval($_POST['adult_count']) : 1;
$form_children_count = isset($_POST['children_count']) ? intval($_POST['children_count']) : 0;
$form_passenger_name = isset($_POST['passenger_name']) ? $_POST['passenger_name'] : '';
$form_passenger_email = isset($_POST['passenger_email']) ? $_POST['passenger_email'] : '';
$form_passenger_phone = isset($_POST['passenger_phone']) ? $_POST['passenger_phone'] : '';

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

// Check if flight is already assigned to this booking
if ($booking) {
  $stmt = $conn->prepare("SELECT * FROM flight_bookings WHERE user_id = ?");
  $stmt->bind_param("i", $booking['user_id']);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows > 0) {
    $booking_details = $result->fetch_assoc();

    // Use booking details as default form values if not already set
    if (empty($form_flight_id)) {
      $form_flight_id = $booking_details['flight_id'];
    }
    if (empty($form_cabin_class)) {
      $form_cabin_class = $booking_details['cabin_class'];
    }
    if (empty($form_adult_count)) {
      $form_adult_count = $booking_details['adult_count'];
    }
    if (empty($form_children_count)) {
      $form_children_count = $booking_details['children_count'];
    }
    if (empty($form_passenger_name)) {
      $form_passenger_name = $booking_details['passenger_name'];
    }
    if (empty($form_passenger_email)) {
      $form_passenger_email = $booking_details['passenger_email'];
    }
    if (empty($form_passenger_phone)) {
      $form_passenger_phone = $booking_details['passenger_phone'];
    }
  }
  $stmt->close();
}

// Fetch available flights
$stmt = $conn->prepare("SELECT id, airline_name, flight_number, departure_city, arrival_city, 
                      departure_date, departure_time, flight_duration, has_return, 
                      economy_price, business_price, first_class_price,
                      economy_seats, business_seats, first_class_seats
                      FROM flights 
                      ORDER BY departure_date DESC");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $flights[] = $row;
}
$stmt->close();

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['assign_flight'])) {
  $flight_id = $form_flight_id;
  $cabin_class = $form_cabin_class;
  $adult_count = $form_adult_count;
  $children_count = $form_children_count;
  $passenger_name = $form_passenger_name;
  $passenger_email = $form_passenger_email;
  $passenger_phone = $form_passenger_phone;

  // Validate inputs
  if ($flight_id <= 0) {
    $error_message = "Please select a valid flight.";
  } elseif (!in_array($cabin_class, ['economy', 'business', 'first_class'])) {
    $error_message = "Please select a valid cabin class.";
  } elseif (empty($passenger_name) || empty($passenger_email) || empty($passenger_phone)) {
    $error_message = "Passenger details are required.";
  } elseif ($adult_count < 1) {
    $error_message = "At least one adult is required.";
  } else {
    // Get flight details
    $stmt = $conn->prepare("SELECT * FROM flights WHERE id = ?");
    $stmt->bind_param("i", $flight_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
      $error_message = "Selected flight not found.";
    } else {
      $flight = $result->fetch_assoc();

      // Calculate total seats and price
      $total_seats = $adult_count + $children_count;
      $price_per_seat = $flight[$cabin_class . '_price'];
      $total_price = $price_per_seat * $total_seats;

      // Check seat availability
      $available_seats = $flight[$cabin_class . '_seats'];
      $stmt = $conn->prepare("SELECT SUM(adult_count + children_count) as booked_seats 
                            FROM flight_bookings 
                            WHERE flight_id = ? 
                            AND cabin_class = ? 
                            AND booking_status != 'cancelled'");
      $stmt->bind_param("is", $flight_id, $cabin_class);
      $stmt->execute();
      $result = $stmt->get_result();
      $booked_seats = $result->fetch_assoc()['booked_seats'] ?? 0;
      $remaining_seats = $available_seats - $booked_seats;

      // If this is an existing booking, add back the seats from the current booking
      if (!empty($booking_details) && $booking_details['flight_id'] == $flight_id && $booking_details['cabin_class'] == $cabin_class) {
        $remaining_seats += ($booking_details['adult_count'] + $booking_details['children_count']);
      }

      if ($remaining_seats < $total_seats) {
        $error_message = "Not enough seats available. Only $remaining_seats seats left in $cabin_class class.";
      } else {
        // Start transaction
        $conn->begin_transaction();
        try {
          // Check if booking already has a flight assigned
          if (!empty($booking_details)) {
            // Update existing booking
            $stmt = $conn->prepare("UPDATE flight_bookings 
                                  SET flight_id = ?, cabin_class = ?, 
                                      adult_count = ?, children_count = ?,
                                      total_price = ?, passenger_name = ?,
                                      passenger_email = ?, passenger_phone = ?
                                  WHERE user_id = ?");
            $user_id = $booking['user_id'];
            $stmt->bind_param(
              "issidssi",
              $flight_id,
              $cabin_class,
              $adult_count,
              $children_count,
              $total_price,
              $passenger_name,
              $passenger_email,
              $passenger_phone,
              $user_id
            );
            if (!$stmt->execute()) {
              throw new Exception("Error updating flight booking: " . $stmt->error);
            }
          } else {
            // Create new booking
            $user_id = $booking['user_id'];
            $stmt = $conn->prepare("INSERT INTO flight_bookings 
                                  (user_id, flight_id, cabin_class, 
                                   adult_count, children_count, total_price, 
                                   passenger_name, passenger_email, passenger_phone,
                                   booking_status, payment_status) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed', 'pending')");
            $stmt->bind_param(
              "iisiidsss",
              $user_id,
              $flight_id,
              $cabin_class,
              $adult_count,
              $children_count,
              $total_price,
              $passenger_name,
              $passenger_email,
              $passenger_phone
            );
            if (!$stmt->execute()) {
              throw new Exception("Error creating flight booking: " . $stmt->error);
            }
          }

          // Commit the transaction
          $conn->commit();
          $success_message = "Flight assigned successfully to booking #$booking_id.";

          // Refresh booking details
          $stmt = $conn->prepare("SELECT * FROM flight_bookings WHERE user_id = ?");
          $stmt->bind_param("i", $booking['user_id']);
          $stmt->execute();
          $result = $stmt->get_result();
          if ($result->num_rows > 0) {
            $booking_details = $result->fetch_assoc();
          }
        } catch (Exception $e) {
          $conn->rollback();
          $error_message = $e->getMessage();
        }
      }
    }
  }
}

// Get user info for the booking
$user_info = null;
if ($booking) {
  $stmt = $conn->prepare("SELECT full_name, email, phone FROM users WHERE id = ?");
  $stmt->bind_param("i", $booking['user_id']);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows > 0) {
    $user_info = $result->fetch_assoc();
  }
  $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Assign Flight | Admin Panel</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body class="bg-gray-100 min-h-screen">
  <?php include 'includes/sidebar.php'; ?>

  <div class="ml-0 md:ml-64 p-6">
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
          <i class="fas fa-plane text-blue-500 mr-2"></i>Assign Flight
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
            <h3 class="font-bold">Current Flight Assignment</h3>
            <p>Flight ID: <?php echo $booking_details['flight_id']; ?></p>
            <p>Cabin Class: <?php echo ucfirst(str_replace('_', ' ', $booking_details['cabin_class'])); ?></p>
            <p>Passengers: <?php echo $booking_details['adult_count']; ?> Adult(s), <?php echo $booking_details['children_count']; ?> Child(ren)</p>
            <p>Total Price: PKR <?php echo number_format($booking_details['total_price'], 2); ?></p>
            <p class="mt-2">You can update this assignment using the form below.</p>
          </div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-6" id="assignFlightForm">
          <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2">
              <label for="flight_id" class="block text-sm font-medium text-gray-700 mb-1">Select Flight</label>
              <select name="flight_id" id="flight_id" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" required>
                <option value="">-- Select Flight --</option>
                <?php foreach ($flights as $flight): ?>
                  <option value="<?php echo $flight['id']; ?>" <?php echo ($form_flight_id == $flight['id']) ? 'selected' : ''; ?> data-flight='<?php echo json_encode($flight); ?>'>
                    <?php echo htmlspecialchars($flight['airline_name']); ?> (<?php echo htmlspecialchars($flight['flight_number']); ?>) -
                    <?php echo htmlspecialchars($flight['departure_city']); ?> to <?php echo htmlspecialchars($flight['arrival_city']); ?> -
                    <?php echo date('M j, Y', strtotime($flight['departure_date'])); ?> <?php echo date('H:i', strtotime($flight['departure_time'])); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div>
              <label for="cabin_class" class="block text-sm font-medium text-gray-700 mb-1">Cabin Class</label>
              <select name="cabin_class" id="cabin_class" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" required>
                <option value="">-- Select Cabin Class --</option>
                <option value="economy" <?php echo ($form_cabin_class == 'economy') ? 'selected' : ''; ?>>Economy</option>
                <option value="business" <?php echo ($form_cabin_class == 'business') ? 'selected' : ''; ?>>Business</option>
                <option value="first_class" <?php echo ($form_cabin_class == 'first_class') ? 'selected' : ''; ?>>First Class</option>
              </select>
            </div>

            <div>
              <div class="flex space-x-4">
                <div class="flex-1">
                  <label for="adult_count" class="block text-sm font-medium text-gray-700 mb-1">Adults</label>
                  <input type="number" name="adult_count" id="adult_count" min="1" max="10"
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                    value="<?php echo $form_adult_count; ?>"
                    required>
                </div>
                <div class="flex-1">
                  <label for="children_count" class="block text-sm font-medium text-gray-700 mb-1">Children</label>
                  <input type="number" name="children_count" id="children_count" min="0" max="10"
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                    value="<?php echo $form_children_count; ?>">
                </div>
              </div>
            </div>
          </div>

          <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
            <h3 class="text-lg font-medium text-gray-800 mb-4">Passenger Details</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div>
                <label for="passenger_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                <input type="text" name="passenger_name" id="passenger_name"
                  class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                  value="<?php echo !empty($form_passenger_name) ? htmlspecialchars($form_passenger_name) : htmlspecialchars($user_info['full_name'] ?? ''); ?>"
                  required>
              </div>
              <div>
                <label for="passenger_email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="passenger_email" id="passenger_email"
                  class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                  value="<?php echo !empty($form_passenger_email) ? htmlspecialchars($form_passenger_email) : htmlspecialchars($user_info['email'] ?? ''); ?>"
                  required>
              </div>
              <div>
                <label for="passenger_phone" class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                <input type="text" name="passenger_phone" id="passenger_phone"
                  class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                  value="<?php echo !empty($form_passenger_phone) ? htmlspecialchars($form_passenger_phone) : htmlspecialchars($user_info['phone'] ?? ''); ?>"
                  required>
              </div>
            </div>
          </div>

          <div id="flightDetails" class="bg-gray-50 p-4 rounded-lg border border-gray-200 <?php echo empty($form_flight_id) ? 'hidden' : ''; ?>">
            <h3 class="text-lg font-medium text-gray-800 mb-4">Flight Summary</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <p><strong>Airline:</strong> <span id="airlineName"></span></p>
                <p><strong>Flight:</strong> <span id="flightNumber"></span></p>
                <p><strong>Route:</strong> <span id="flightRoute"></span></p>
              </div>
              <div>
                <p><strong>Date:</strong> <span id="flightDate"></span></p>
                <p><strong>Time:</strong> <span id="flightTime"></span></p>
                <p><strong>Duration:</strong> <span id="flightDuration"></span> hours</p>
              </div>
            </div>
            <div class="mt-4 grid grid-cols-3 gap-4">
              <div class="bg-blue-50 p-3 rounded-lg">
                <p class="text-sm text-gray-600">Economy</p>
                <p class="font-semibold"><span id="economyPrice"></span> PKR</p>
                <p class="text-xs text-gray-500"><span id="economySeats"></span> seats available</p>
              </div>
              <div class="bg-purple-50 p-3 rounded-lg">
                <p class="text-sm text-gray-600">Business</p>
                <p class="font-semibold"><span id="businessPrice"></span> PKR</p>
                <p class="text-xs text-gray-500"><span id="businessSeats"></span> seats available</p>
              </div>
              <div class="bg-amber-50 p-3 rounded-lg">
                <p class="text-sm text-gray-600">First Class</p>
                <p class="font-semibold"><span id="firstClassPrice"></span> PKR</p>
                <p class="text-xs text-gray-500"><span id="firstClassSeats"></span> seats available</p>
              </div>
            </div>
          </div>

          <div class="flex justify-end">
            <button type="submit" name="assign_flight" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
              <i class="fas fa-check mr-2"></i><?php echo !empty($booking_details) ? 'Update Flight Assignment' : 'Assign Flight'; ?>
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
    // Show flight details when a flight is selected
    document.getElementById('flight_id').addEventListener('change', function() {
      const flightDetails = document.getElementById('flightDetails');
      const option = this.options[this.selectedIndex];

      if (this.value) {
        const flight = JSON.parse(option.getAttribute('data-flight'));

        document.getElementById('airlineName').textContent = flight.airline_name;
        document.getElementById('flightNumber').textContent = flight.flight_number;
        document.getElementById('flightRoute').textContent = flight.departure_city + ' to ' + flight.arrival_city;

        const departureDate = new Date(flight.departure_date);
        document.getElementById('flightDate').textContent = departureDate.toLocaleDateString('en-US', {
          weekday: 'short',
          year: 'numeric',
          month: 'short',
          day: 'numeric'
        });
        document.getElementById('flightTime').textContent = flight.departure_time;
        document.getElementById('flightDuration').textContent = flight.flight_duration;

        document.getElementById('economyPrice').textContent = new Intl.NumberFormat('en-PK').format(flight.economy_price);
        document.getElementById('businessPrice').textContent = new Intl.NumberFormat('en-PK').format(flight.business_price);
        document.getElementById('firstClassPrice').textContent = new Intl.NumberFormat('en-PK').format(flight.first_class_price);

        document.getElementById('economySeats').textContent = flight.economy_seats;
        document.getElementById('businessSeats').textContent = flight.business_seats;
        document.getElementById('firstClassSeats').textContent = flight.first_class_seats;

        flightDetails.classList.remove('hidden');
      } else {
        flightDetails.classList.add('hidden');
      }
    });

    // Trigger the change event if a flight is already selected
    if (document.getElementById('flight_id').value) {
      document.getElementById('flight_id').dispatchEvent(new Event('change'));
    }

    // Form submission confirmation
    document.getElementById('assignFlightForm').addEventListener('submit', function(e) {
      if (!confirm('Are you sure you want to assign this flight to the booking?')) {
        e.preventDefault();
      }
    });

    // Show pricing based on cabin class and passenger count
    function updatePricing() {
      const flightSelect = document.getElementById('flight_id');
      const cabinClassSelect = document.getElementById('cabin_class');
      const adultCountInput = document.getElementById('adult_count');
      const childrenCountInput = document.getElementById('children_count');

      if (flightSelect.value && cabinClassSelect.value) {
        const option = flightSelect.options[flightSelect.selectedIndex];
        const flight = JSON.parse(option.getAttribute('data-flight'));
        const cabinClass = cabinClassSelect.value;
        const pricePerSeat = flight[cabinClass + '_price'];
        const totalSeats = parseInt(adultCountInput.value) + parseInt(childrenCountInput.value);
        const totalPrice = pricePerSeat * totalSeats;

        // Highlight the selected cabin class
        document.querySelectorAll('.bg-blue-50, .bg-purple-50, .bg-amber-50').forEach(el => {
          el.classList.remove('ring-2', 'ring-blue-500');
        });

        if (cabinClass === 'economy') {
          document.querySelector('.bg-blue-50').classList.add('ring-2', 'ring-blue-500');
        } else if (cabinClass === 'business') {
          document.querySelector('.bg-purple-50').classList.add('ring-2', 'ring-blue-500');
        } else if (cabinClass === 'first_class') {
          document.querySelector('.bg-amber-50').classList.add('ring-2', 'ring-blue-500');
        }
      }
    }

    document.getElementById('cabin_class').addEventListener('change', updatePricing);
    document.getElementById('adult_count').addEventListener('change', updatePricing);
    document.getElementById('children_count').addEventListener('change', updatePricing);

    // Initialize pricing if values are already selected
    if (document.getElementById('flight_id').value && document.getElementById('cabin_class').value) {
      updatePricing();
    }
  </script>
</body>

</html>