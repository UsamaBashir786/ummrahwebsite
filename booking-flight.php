<?php
session_start();
include 'config/db.php';

// Initialize variables
$flight = null;
$user = null;
$error_message = '';
$success_message = '';
$available_seats = 0;
$cabin_class = $_GET['cabin_class'] ?? '';
$flight_id = isset($_GET['flight_id']) ? intval($_GET['flight_id']) : 0;

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
  $error_message = "Please log in to book a flight.";
  header("Location: login.php");
  exit();
}

// Fetch user details
$user_id = $_SESSION['user_id'];
$sql = "SELECT full_name, email, phone FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
if ($stmt) {
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
  } else {
    $error_message = "User not found.";
  }
  $stmt->close();
} else {
  $error_message = "Error fetching user details: " . $conn->error;
}

// Fetch flight details
if ($flight_id > 0 && !$error_message) {
  $sql = "SELECT * FROM flights WHERE id = ?";
  $stmt = $conn->prepare($sql);
  if ($stmt) {
    $stmt->bind_param("i", $flight_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
      $flight = $result->fetch_assoc();
    } else {
      $error_message = "Flight not found.";
    }
    $stmt->close();
  } else {
    $error_message = "Error preparing query: " . $conn->error;
  }
} else if (!$error_message) {
  $error_message = "Invalid flight ID.";
}

// Validate cabin class
$valid_classes = ['economy', 'business', 'first_class'];
if (!in_array($cabin_class, $valid_classes)) {
  $error_message = "Invalid cabin class selected.";
}

// Calculate available seats
if ($flight && !$error_message) {
  $total_seats = $flight[$cabin_class . '_seats'];
  $sql = "SELECT SUM(adult_count + children_count) as booked_seats 
          FROM flight_bookings 
          WHERE flight_id = ? 
          AND cabin_class = ? 
          AND payment_status IN ('pending', 'completed')";
  $stmt = $conn->prepare($sql);
  if ($stmt) {
    $stmt->bind_param("is", $flight_id, $cabin_class);
    $stmt->execute();
    $result = $stmt->get_result();
    $booked_seats = $result->fetch_assoc()['booked_seats'] ?? 0;
    $available_seats = max(0, $total_seats - $booked_seats);
    $stmt->close();
  } else {
    $error_message = "Error calculating available seats: " . $conn->error;
  }
}
// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && !$error_message) {
  $passenger_name = $_POST['passenger_name'] ?? '';
  $passenger_email = $_POST['passenger_email'] ?? '';
  $passenger_phone = $_POST['passenger_phone'] ?? '';
  $adult_count = isset($_POST['adult_count']) ? intval($_POST['adult_count']) : 1;
  $children_count = isset($_POST['children_count']) ? intval($_POST['children_count']) : 0;

  // Validate inputs
  $validation_errors = [];
  if ($passenger_name !== $user['full_name']) {
    $validation_errors[] = "Passenger name must match your account name.";
  }
  if ($passenger_email !== $user['email']) {
    $validation_errors[] = "Passenger email must match your account email.";
  }
  if ($passenger_phone !== $user['phone']) {
    $validation_errors[] = "Passenger phone must match your account phone.";
  }
  if ($adult_count < 1) {
    $validation_errors[] = "At least one adult is required.";
  }
  if ($adult_count + $children_count > $available_seats) {
    $validation_errors[] = "Requested seats exceed available seats ($available_seats left).";
  }

  // Check for duplicate booking
  $sql = "SELECT id FROM flight_bookings WHERE flight_id = ? AND user_id = ? AND cabin_class = ? AND booking_status != 'cancelled'";
  $stmt = $conn->prepare($sql);
  if ($stmt) {
    $stmt->bind_param("iis", $flight_id, $user_id, $cabin_class);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
      $validation_errors[] = "You already have an active booking for this flight.";
    }
    $stmt->close();
  }

  if (empty($validation_errors)) {
    // Calculate total price
    $price_per_seat = $flight[$cabin_class . '_price'];
    $total_price = ($adult_count + $children_count) * $price_per_seat;

    // Insert booking with transaction
    $conn->begin_transaction();
    try {
      // Insert into flight_bookings
      $sql = "INSERT INTO flight_bookings (flight_id, user_id, cabin_class, adult_count, children_count, total_price, passenger_name, passenger_email, passenger_phone, booking_status, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending')";
      $stmt = $conn->prepare($sql);
      if ($stmt) {
        $stmt->bind_param("iisiissss", $flight_id, $user_id, $cabin_class, $adult_count, $children_count, $total_price, $passenger_name, $passenger_email, $passenger_phone);
        $stmt->execute();
        $stmt->close();
        $conn->commit();
        $success_message = "Booking created successfully with pending payment status. Please proceed to payment or contact support.";
      } else {
        throw new Exception("Error preparing booking query: " . $conn->error);
      }
    } catch (Exception $e) {
      $conn->rollback();
      $error_message = "Error creating booking: " . $e->getMessage();
    }
  } else {
    $error_message = implode(" ", $validation_errors);
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Book Flight | UmrahFlights</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: '#047857',
            secondary: '#10B981',
            accent: '#F59E0B',
          },
        },
      },
    }
  </script>
  <style>
    .booking-form {
      background-color: #f9fafb;
      border-radius: 12px;
    }

    .summary-card {
      background-color: #ffffff;
      border-radius: 12px;
    }

    .input-icon {
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      color: #6b7280;
    }

    .input-field {
      padding-left: 40px;
    }

    .animate-pulse-once {
      animation: pulse 1s ease-in-out 1;
    }

    .readonly-field {
      background-color: #e5e7eb;
      cursor: not-allowed;
    }
  </style>
</head>

<body class="bg-gray-50 min-h-screen">
  <?php include 'includes/navbar.php'; ?>
  <br><br><br>

  <div class="container mx-auto px-4 py-8">
    <div class="text-center mb-8 animate__animated animate__fadeIn">
      <h1 class="text-3xl md:text-4xl font-bold text-primary mb-2">
        <i class="fas fa-ticket-alt mr-2"></i> Book Your Flight
      </h1>
      <p class="text-gray-600 max-w-2xl mx-auto">Complete your booking details to secure your seats for a seamless Umrah journey.</p>
    </div>

    <!-- Error/Success Messages -->
    <?php if ($error_message): ?>
      <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg shadow-sm animate__animated animate__fadeIn" role="alert">
        <div class="flex">
          <div class="flex-shrink-0">
            <i class="fas fa-exclamation-circle text-red-500 mr-2"></i>
          </div>
          <div>
            <p class="font-medium"><?php echo htmlspecialchars($error_message); ?></p>
          </div>
        </div>
      </div>
    <?php endif; ?>
    <?php if ($success_message): ?>
      <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg shadow-sm animate__animated animate__fadeIn" role="alert">
        <div class="flex">
          <div class="flex-shrink-0">
            <i class="fas fa-check-circle text-green-500 mr-2"></i>
          </div>
          <div>
            <p class="font-medium"><?php echo htmlspecialchars($success_message); ?></p>
            <p class="mt-2">You will receive a confirmation email soon.</p>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- Booking Form and Summary -->
    <?php if ($flight && $user && !$success_message && $available_seats > 0): ?>
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Booking Form -->
        <div class="lg:col-span-2">
          <div class="booking-form p-6 shadow-lg animate__animated animate__fadeIn animate__delay-1s">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Passenger Details</h2>
            <form method="post" id="booking-form">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Passenger Name -->
                <div class="relative">
                  <label for="passenger_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                  <i class="fas fa-user input-icon"></i>
                  <input type="text" id="passenger_name" name="passenger_name" class="w-full input-field rounded-lg border-gray-300 shadow-sm readonly-field py-3" value="<?php echo htmlspecialchars($user['full_name']); ?>" readonly required>
                </div>
                <!-- Passenger Email -->
                <div class="relative">
                  <label for="passenger_email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                  <i class="fas fa-envelope input-icon"></i>
                  <input type="email" id="passenger_email" name="passenger_email" class="w-full input-field rounded-lg border-gray-300 shadow-sm readonly-field py-3" value="<?php echo htmlspecialchars($user['email']); ?>" readonly required>
                </div>
                <!-- Passenger Phone -->
                <div class="relative">
                  <label for="passenger_phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                  <i class="fas fa-phone input-icon"></i>
                  <input type="tel" id="passenger_phone" name="passenger_phone" class="w-full input-field rounded-lg border-gray-300 shadow-sm readonly-field py-3" value="<?php echo htmlspecialchars($user['phone']); ?>" readonly required>
                </div>
                <!-- Adult Count -->
                <div>
                  <label for="adult_count" class="block text-sm font-medium text-gray-700 mb-1">Adults (18+)</label>
                  <select id="adult_count" name="adult_count" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 py-3" required>
                    <?php for ($i = 1; $i <= $available_seats; $i++): ?>
                      <option value="<?php echo $i; ?>" <?php echo (isset($_POST['adult_count']) && $_POST['adult_count'] == $i) ? 'selected' : ''; ?>><?php echo $i; ?></option>
                    <?php endfor; ?>
                  </select>
                </div>
                <!-- Children Count -->
                <div>
                  <label for="children_count" class="block text-sm font-medium text-gray-700 mb-1">Children (2-11)</label>
                  <select id="children_count" name="children_count" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 py-3">
                    <?php for ($i = 0; $i <= $available_seats; $i++): ?>
                      <option value="<?php echo $i; ?>" <?php echo (isset($_POST['children_count']) && $_POST['children_count'] == $i) ? 'selected' : ''; ?>><?php echo $i; ?></option>
                    <?php endfor; ?>
                  </select>
                </div>
              </div>
              <div class="mt-6">
                <button type="submit" id="confirm-booking" class="w-full bg-primary hover:bg-green-700 text-white font-bold py-3 px-4 rounded-lg transition duration-300 ease-in-out flex items-center justify-center shadow-md">
                  <i class="fas fa-check-circle mr-2"></i> Confirm Booking
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Flight Summary -->
        <div class="lg:col-span-1">
          <div class="summary-card p-6 shadow-lg animate__animated animate__fadeIn animate__delay-1s">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Flight Summary</h2>
            <!-- Seat Availability -->
            <div class="mb-6">
              <h3 class="text-lg font-semibold text-primary"><i class="fas fa-chair mr-2"></i> Seat Availability</h3>
              <div class="mt-2">
                <p><span class="font-medium">Available Seats:</span> <?php echo $available_seats; ?> in <?php echo ucfirst($cabin_class); ?> Class</p>
              </div>
            </div>
            <!-- Outbound Flight -->
            <div class="mb-6">
              <h3 class="text-lg font-semibold text-primary"><i class="fas fa-plane-departure mr-2"></i> Outbound Flight</h3>
              <div class="mt-2 space-y-2">
                <p><span class="font-medium">Airline:</span> <?php echo htmlspecialchars($flight['airline_name']); ?></p>
                <p><span class="font-medium">Flight:</span> <?php echo htmlspecialchars($flight['flight_number']); ?></p>
                <p><span class="font-medium">Route:</span> <?php echo htmlspecialchars($flight['departure_city']); ?> to <?php echo htmlspecialchars($flight['arrival_city']); ?></p>
                <p><span class="font-medium">Date:</span> <?php echo date('D, M j, Y', strtotime($flight['departure_date'])); ?></p>
                <p><span class="font-medium">Time:</span> <?php echo date('H:i', strtotime($flight['departure_time'])); ?></p>
                <p><span class="font-medium">Duration:</span> <?php echo htmlspecialchars($flight['flight_duration']); ?> hours</p>
                <p><span class="font-medium">Class:</span> <?php echo ucfirst($cabin_class); ?></p>
                <p><span class="font-medium">Price per Seat:</span> PKR <?php echo number_format($flight[$cabin_class . '_price'], 0); ?></p>
              </div>
            </div>
            <!-- Return Flight (if applicable) -->
            <?php if ($flight['has_return']): ?>
              <div class="mb-6">
                <h3 class="text-lg font-semibold text-purple-600"><i class="fas fa-plane-arrival mr-2"></i> Return Flight</h3>
                <div class="mt-2 space-y-2">
                  <p><span class="font-medium">Airline:</span> <?php echo htmlspecialchars($flight['return_airline']); ?></p>
                  <p><span class="font-medium">Flight:</span> <?php echo htmlspecialchars($flight['return_flight_number']); ?></p>
                  <p><span class="font-medium">Route:</span> <?php echo htmlspecialchars($flight['arrival_city']); ?> to <?php echo htmlspecialchars($flight['departure_city']); ?></p>
                  <p><span class="font-medium">Date:</span> <?php echo date('D, M j, Y', strtotime($flight['return_date'])); ?></p>
                  <p><span class="font-medium">Time:</span> <?php echo date('H:i', strtotime($flight['return_time'])); ?></p>
                  <p><span class="font-medium">Duration:</span> <?php echo htmlspecialchars($flight['return_flight_duration']); ?> hours</p>
                  <p><span class="font-medium">Class:</span> <?php echo ucfirst($cabin_class); ?></p>
                </div>
              </div>
            <?php endif; ?>
            <!-- Price Breakdown -->
            <div class="border-t pt-4">
              <h3 class="text-lg font-semibold text-gray-800">Price Breakdown</h3>
              <div class="mt-2 space-y-2">
                <p><span class="font-medium">Price per Seat:</span> PKR <?php echo number_format($flight[$cabin_class . '_price'], 0); ?></p>
                <p><span class="font-medium">Passengers:</span> <span id="passenger-count">1 Adult</span></p>
                <p class="text-lg font-bold text-secondary"><span class="font-medium">Total:</span> PKR <span id="total-price"><?php echo number_format($flight[$cabin_class . '_price'], 0); ?></span></p>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php elseif ($flight && $available_seats == 0): ?>
      <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg shadow-sm animate__animated animate__fadeIn" role="alert">
        <div class="flex">
          <div class="flex-shrink-0">
            <i class="fas fa-exclamation-circle text-red-500 mr-2"></i>
          </div>
          <div>
            <p class="font-medium">No seats available in <?php echo ucfirst($cabin_class); ?> class for this flight.</p>
            <p class="mt-2">Please select a different flight or cabin class.</p>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Update price and passenger count dynamically
      const adultCountSelect = document.getElementById('adult_count');
      const childrenCountSelect = document.getElementById('children_count');
      const totalPriceSpan = document.getElementById('total-price');
      const passengerCountSpan = document.getElementById('passenger-count');
      const pricePerSeat = <?php echo $flight[$cabin_class . '_price'] ?? 0; ?>;
      const maxSeats = <?php echo $available_seats; ?>;

      function updatePriceAndCount() {
        const adults = parseInt(adultCountSelect.value) || 0;
        const children = parseInt(childrenCountSelect.value) || 0;
        const totalPassengers = adults + children;

        // Update children options based on available seats
        const remainingSeats = maxSeats - adults;
        childrenCountSelect.innerHTML = '';
        for (let i = 0; i <= remainingSeats; i++) {
          const option = document.createElement('option');
          option.value = i;
          option.text = i;
          if (i === (parseInt(childrenCountSelect.dataset.value) || 0) && i <= remainingSeats) {
            option.selected = true;
          }
          childrenCountSelect.appendChild(option);
        }

        // Update price and passenger count
        const totalPrice = totalPassengers * pricePerSeat;
        totalPriceSpan.textContent = totalPrice.toLocaleString('en-PK');
        let passengerText = '';
        if (adults > 0) passengerText += `${adults} Adult${adults > 1 ? 's' : ''}`;
        if (children > 0) passengerText += `${passengerText ? ', ' : ''}${children} Child${children > 1 ? 'ren' : ''}`;
        passengerCountSpan.textContent = passengerText || '1 Adult';

        // Animate price update
        totalPriceSpan.classList.add('animate-pulse-once');
        setTimeout(() => totalPriceSpan.classList.remove('animate-pulse-once'), 1000);
      }

      // Store initial children count
      if (childrenCountSelect) {
        childrenCountSelect.dataset.value = childrenCountSelect.value;
      }

      // Add event listeners
      if (adultCountSelect && childrenCountSelect) {
        adultCountSelect.addEventListener('change', updatePriceAndCount);
        childrenCountSelect.addEventListener('change', function() {
          this.dataset.value = this.value;
          updatePriceAndCount();
        });
      }

      // Initial update
      updatePriceAndCount();

      // Form submission handling
      const bookingForm = document.getElementById('booking-form');
      const confirmButton = document.getElementById('confirm-booking');
      if (bookingForm && confirmButton) {
        bookingForm.addEventListener('submit', function() {
          confirmButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processing...';
          confirmButton.disabled = true;
        });
      }
    });
  </script>
</body>

</html>

<?php
$conn->close();
?>