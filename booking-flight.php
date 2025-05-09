<?php
// Start session and ensure no output before headers
ob_start();
session_start();
include 'config/db.php';

// Log the start of the script
error_log("Starting booking-flight.php, session ID: " . session_id());

// Initialize variables
$flight = null;
$user = null;
$error_message = '';
$success_message = '';
$available_seats = 0;
$cabin_class = $_GET['cabin_class'] ?? '';
$flight_id = isset($_GET['flight_id']) ? intval($_GET['flight_id']) : 0;

// Log current URL and request method
error_log("Current URL: " . $_SERVER['REQUEST_URI']);
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Session user_id: " . ($_SESSION['user_id'] ?? 'not set'));

// Check for success query parameter
if (isset($_GET['success']) && $_GET['success'] == '1') {
  $success_message = 'Booking created successfully with pending payment status. Please proceed to payment or contact support.';
  error_log("Success parameter detected, success_message set");
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
  $error_message = "Please log in to book a flight.";
  error_log("User not logged in, redirecting to login.php");
  header("Location: login.php?redirect=booking-flight.php");
  ob_end_flush();
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
    error_log("User fetched: " . $user['full_name']);
  } else {
    $error_message = "User not found.";
    error_log("User ID $user_id not found");
  }
  $stmt->close();
} else {
  $error_message = "Error fetching user details: " . $conn->error;
  error_log("Error fetching user details: " . $conn->error);
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
      error_log("Flight fetched: ID $flight_id");
    } else {
      $error_message = "Flight not found.";
      error_log("Flight ID $flight_id not found");
    }
    $stmt->close();
  } else {
    $error_message = "Error preparing query: " . $conn->error;
    error_log("Error preparing flight query: " . $conn->error);
  }
} else if (!$error_message) {
  $error_message = "Invalid flight ID.";
  error_log("Invalid flight ID: $flight_id");
}

// Validate cabin class
$valid_classes = ['economy', 'business', 'first_class'];
if (!in_array($cabin_class, $valid_classes)) {
  $error_message = "Invalid cabin class selected: $cabin_class";
  error_log("Invalid cabin class: $cabin_class");
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
    error_log("Available seats calculated: $available_seats");
    $stmt->close();
  } else {
    $error_message = "Error calculating available seats: " . $conn->error;
    error_log("Error calculating available seats: " . $conn->error);
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
      if (!$stmt) {
        throw new Exception("Error preparing booking query: " . $conn->error);
      }
      $stmt->bind_param("iisiissss", $flight_id, $user_id, $cabin_class, $adult_count, $children_count, $total_price, $passenger_name, $passenger_email, $passenger_phone);
      if (!$stmt->execute()) {
        throw new Exception("Error executing booking query: " . $stmt->error);
      }
      $booking_id = $conn->insert_id;
      $stmt->close();

      // Generate booking reference
      $booking_reference = 'FB' . strtoupper(substr(md5($booking_id), 0, 8));

      // Send email to User
      $to = $passenger_email;
      $email_subject = 'Thank You for Your Flight Booking with Umrah Partner';
      $email_message = "Dear $passenger_name,\n\n";
      $email_message .= "Booking created successfully with pending payment status. Please proceed to payment or contact support.\n\n";
      $email_message .= "Booking Details:\n";
      $email_message .= "Booking Reference: $booking_reference\n";
      $email_message .= "Airline: " . htmlspecialchars($flight['airline_name']) . "\n";
      $email_message .= "Flight Number: " . htmlspecialchars($flight['flight_number']) . "\n";
      $email_message .= "Route: " . htmlspecialchars($flight['departure_city']) . " to " . htmlspecialchars($flight['arrival_city']) . "\n";
      $email_message .= "Departure Date: " . date('D, M j, Y', strtotime($flight['departure_date'])) . "\n";
      $email_message .= "Departure Time: " . date('H:i', strtotime($flight['departure_time'])) . "\n";
      $email_message .= "Cabin Class: " . ucfirst($cabin_class) . "\n";
      $email_message .= "Adults: $adult_count\n";
      $email_message .= "Children: $children_count\n";
      $email_message .= "Total Price: PKR " . number_format($total_price, 0) . "\n";
      $email_message .= "Payment Status: Pending\n\n";
      if ($flight['has_return']) {
        $email_message .= "Return Flight Details:\n";
        $email_message .= "Airline: " . htmlspecialchars($flight['return_airline']) . "\n";
        $email_message .= "Flight Number: " . htmlspecialchars($flight['return_flight_number']) . "\n";
        $email_message .= "Route: " . htmlspecialchars($flight['arrival_city']) . " to " . htmlspecialchars($flight['departure_city']) . "\n";
        $email_message .= "Return Date: " . date('D, M j, Y', strtotime($flight['return_date'])) . "\n";
        $email_message .= "Return Time: " . date('H:i', strtotime($flight['return_time'])) . "\n\n";
      }
      $email_message .= "You can view your booking details in your account under 'My Bookings'.\n";
      $email_message .= "For any queries, contact us at info@umrahpartner.com.\n\n";
      $email_message .= "Best regards,\nUmrah Partner Team";

      $headers = "From: no-reply@umrahpartner.com\r\n";
      $headers .= "Reply-To: info@umrahpartner.com\r\n";
      $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

      if (!mail($to, $email_subject, $email_message, $headers)) {
        throw new Exception('Failed to send user email.');
      }

      // Send email to Admin
      $admin_to = 'info@umrahpartner.com';
      $admin_subject = 'New Flight Booking Submission';
      $admin_message = "New Flight Booking Submission\n\n";
      $admin_message .= "A new flight booking has been created.\n\n";
      $admin_message .= "Details:\n";
      $admin_message .= "Booking Reference: $booking_reference\n";
      $admin_message .= "User: $passenger_name ($passenger_email)\n";
      $admin_message .= "Airline: " . htmlspecialchars($flight['airline_name']) . "\n";
      $admin_message .= "Flight Number: " . htmlspecialchars($flight['flight_number']) . "\n";
      $admin_message .= "Route: " . htmlspecialchars($flight['departure_city']) . " to " . htmlspecialchars($flight['arrival_city']) . "\n";
      $admin_message .= "Departure Date: " . date('D, M j, Y', strtotime($flight['departure_date'])) . "\n";
      $admin_message .= "Departure Time: " . date('H:i', strtotime($flight['departure_time'])) . "\n";
      $admin_message .= "Cabin Class: " . ucfirst($cabin_class) . "\n";
      $admin_message .= "Adults: $adult_count\n";
      $admin_message .= "Children: $children_count\n";
      $admin_message .= "Total Price: PKR " . number_format($total_price, 0) . "\n";
      $admin_message .= "Payment Status: Pending\n";
      if ($flight['has_return']) {
        $admin_message .= "Return Flight Details:\n";
        $admin_message .= "Airline: " . htmlspecialchars($flight['return_airline']) . "\n";
        $admin_message .= "Flight Number: " . htmlspecialchars($flight['return_flight_number']) . "\n";
        $admin_message .= "Route: " . htmlspecialchars($flight['arrival_city']) . " to " . htmlspecialchars($flight['departure_city']) . "\n";
        $admin_message .= "Return Date: " . date('D, M j, Y', strtotime($flight['return_date'])) . "\n";
        $admin_message .= "Return Time: " . date('H:i', strtotime($flight['return_time'])) . "\n";
      }
      $admin_message .= "Submitted At: " . date('Y-m-d H:i:s') . "\n";

      if (!mail($admin_to, $admin_subject, $admin_message, $headers)) {
        throw new Exception('Failed to send admin email.');
      }

      // Commit transaction
      $conn->commit();
      error_log("Flight booking successful for user_id=$user_id, flight_id=$flight_id, booking_reference=$booking_reference");

      // Construct redirect URL
      $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
      $host = $_SERVER['HTTP_HOST'];
      $script_path = dirname($_SERVER['PHP_SELF']);
      $script_name = basename($_SERVER['PHP_SELF']);
      $path_prefix = ($script_path == '/' ? '' : $script_path) . '/';
      $redirect_url = "$protocol://$host$path_prefix$script_name?flight_id=$flight_id&cabin_class=" . urlencode($cabin_class) . "&success=1";

      error_log("Redirecting to: $redirect_url");
      header("Location: $redirect_url", true, 303);
      ob_end_flush();
      exit();
    } catch (Exception $e) {
      $conn->rollback();
      $error_message = "Booking failed. Please contact us directly. Error: " . $e->getMessage();
      error_log("Flight Booking Error: " . $e->getMessage());
    }
  } else {
    $error_message = implode(" ", $validation_errors);
    error_log("Validation errors: " . $error_message);
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Book Your Flight | UmrahFlights</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700;800&display=swap');

    body {
      font-family: 'Manrope', sans-serif;
      background: #f9fafb;
      color: #1f2937;
      overflow-x: hidden;
    }

    .booking-form {
      background: rgba(255, 255, 255, 0.95);
      border-radius: 24px;
      padding: 40px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      backdrop-filter: blur(10px);
      transition: transform 0.3s ease;
    }

    .booking-form:hover {
      transform: translateY(-8px);
    }

    .summary-card {
      background: linear-gradient(145deg, #ffffff, #f1f5f9);
      border-radius: 24px;
      padding: 32px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      transition: transform 0.4s ease;
    }

    .summary-card:hover {
      transform: translateY(-8px);
    }

    .gradient-button {
      background: linear-gradient(90deg, #10b981, #059669);
      color: white;
      border-radius: 16px;
      padding: 12px 32px;
      font-weight: 600;
      transition: transform 0.3s ease, background 0.3s ease;
    }

    .gradient-button:hover {
      background: linear-gradient(90deg, #059669, #10b981);
      transform: scale(1.05);
    }

    .input-field {
      border: 1px solid #e5e7eb;
      border-radius: 16px;
      padding: 14px 14px 14px 40px;
      background: #ffffff;
      transition: border-color 0.3s ease, box-shadow 0.3s ease;
    }

    .input-field:focus {
      border-color: #10b981;
      box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
      outline: none;
    }

    .input-icon {
      position: absolute;
      left: 14px;
      top: 50%;
      transform: translateY(-50%);
      color: #6b7280;
    }

    .readonly-field {
      background: #f3f4f6;
      cursor: not-allowed;
      opacity: 0.9;
    }

    .header-bg {
      background: linear-gradient(135deg, #059669 0%, #10b981 100%);
      position: relative;
      overflow: hidden;
      clip-path: polygon(0 0, 100% 0, 100% 85%, 0 100%);
    }

    .header-bg::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: url('https://source.unsplash.com/random/1600x400?mosque,kaaba') no-repeat center center/cover;
      opacity: 0.2;
      z-index: 0;
    }

    .section-title {
      position: relative;
      font-size: 2.25rem;
      font-weight: 800;
      color: #1f2937;
      padding-bottom: 16px;
      margin-bottom: 32px;
    }

    .section-title::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      width: 120px;
      height: 5px;
      background: linear-gradient(to right, #10b981, #059669);
      border-radius: 3px;
    }

    .chip {
      display: inline-flex;
      align-items: center;
      padding: 8px 16px;
      background: #ecfdf5;
      color: #059669;
      border-radius: 9999px;
      font-size: 0.85rem;
      font-weight: 600;
      transition: background 0.3s ease;
    }

    .chip:hover {
      background: #d1fae5;
    }

    .animate-on-scroll {
      opacity: 0;
      transform: translateY(20px);
      transition: opacity 0.6s ease, transform 0.6s ease;
    }

    .animate-on-scroll.visible {
      opacity: 1;
      transform: translateY(0);
    }

    .alert {
      border-radius: 16px;
      padding: 24px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }

    .footer-bg {
      background: linear-gradient(to bottom, #1f2937, #111827);
      clip-path: polygon(0 10%, 100% 0, 100% 100%, 0 100%);
    }

    .social-icon {
      transition: transform 0.3s ease, color 0.3s ease;
      font-size: 1.5rem;
    }

    .social-icon:hover {
      transform: scale(1.4);
      color: #10b981;
    }

    @media (max-width: 768px) {
      .section-title {
        font-size: 1.75rem;
      }

      .booking-form,
      .summary-card {
        padding: 24px;
      }

      .gradient-button {
        padding: 10px 24px;
      }
    }
  </style>
</head>

<body>
  <!-- Navbar -->
  <?php include 'includes/navbar.php'; ?>

  <!-- Page Header -->
  <section class="header-bg text-white py-20 relative">
    <div class="container mx-auto px-4 relative z-10">
      <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight mb-4">Book Your Flight</h1>
      <p class="text-lg md:text-xl text-gray-100 max-w-2xl">Secure your seats for a seamless Umrah journey with ease.</p>
      <div class="mt-6 text-sm md:text-base">
        <a href="index.php" class="text-gray-200 hover:text-white transition">Home</a>
        <span class="mx-2">></span>
        <a href="flight-booking.php" class="text-gray-200 hover:text-white transition">Flight Booking</a>
        <span class="mx-2">></span>
        <span class="text-gray-200">Book Flight</span>
      </div>
    </div>
  </section>

  <!-- Main Content -->
  <section class="py-16">
    <div class="container mx-auto px-4">
      <!-- Error/Success Messages -->
      <?php if ($error_message): ?>
        <div class="alert bg-red-50 border-l-4 border-red-500 text-red-700 p-6 mb-8 animate-on-scroll" role="alert">
          <div class="flex items-center">
            <i class="fas fa-exclamation-circle text-red-500 text-xl mr-3"></i>
            <p class="font-medium"><?php echo htmlspecialchars($error_message); ?></p>
          </div>
        </div>
      <?php endif; ?>
      <?php if ($success_message): ?>
        <div class="alert bg-green-50 border-l-4 border-green-500 text-green-700 p-6 mb-8 animate-on-scroll" role="alert">
          <div class="flex">
            <i class="fas fa-check-circle text-green-500 text-xl mr-3"></i>
            <div>
              <p class="font-medium"><?php echo htmlspecialchars($success_message); ?></p>
              <p class="mt-2 text-sm">You will receive a confirmation email soon.</p>
              <div class="mt-4 space-y-2">
                <p><a href="user/index.php" class="text-emerald-600 hover:text-emerald-800 transition">View My Bookings</a></p>
                <p><a href="flight-booking.php?flight_id=<?php echo $flight_id; ?>&cabin_class=<?php echo urlencode($cabin_class); ?>" class="text-emerald-600 hover:text-emerald-800 transition">Book Another Flight</a></p>
                <p><a href="index.php" class="text-emerald-600 hover:text-emerald-800 transition">Back to Home</a></p>
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <!-- Booking Form and Summary -->
      <?php if (!$success_message && $flight && $user && $available_seats > 0): ?>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <!-- Booking Form -->
          <div class="lg:col-span-2">
            <div class="booking-form animate-on-scroll">
              <h2 class="section-title">Passenger Details</h2>
              <form method="post" id="booking-form">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <!-- Passenger Name -->
                  <div class="relative">
                    <label for="passenger_name" class="block text-sm font-medium text-gray-700 mb-3">Full Name</label>
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" id="passenger_name" name="passenger_name" class="w-full input-field readonly-field" value="<?php echo htmlspecialchars($user['full_name']); ?>" readonly required>
                  </div>
                  <!-- Passenger Email -->
                  <div class="relative">
                    <label for="passenger_email" class="block text-sm font-medium text-gray-700 mb-3">Email Address</label>
                    <i class="fas fa-envelope input-icon"></i>
                    <input type="email" id="passenger_email" name="passenger_email" class="w-full input-field readonly-field" value="<?php echo htmlspecialchars($user['email']); ?>" readonly required>
                  </div>
                  <!-- Passenger Phone -->
                  <div class="relative">
                    <label for="passenger_phone" class="block text-sm font-medium text-gray-700 mb-3">Phone Number</label>
                    <i class="fas fa-phone input-icon"></i>
                    <input type="tel" id="passenger_phone" name="passenger_phone" class="w-full input-field readonly-field" value="<?php echo htmlspecialchars($user['phone']); ?>" readonly required>
                  </div>
                  <!-- Adult Count -->
                  <div class="relative">
                    <label for="adult_count" class="block text-sm font-medium text-gray-700 mb-3">Adults (18+)</label>
                    <i class="fas fa-users input-icon"></i>
                    <select id="adult_count" name="adult_count" class="w-full input-field" required>
                      <?php for ($i = 1; $i <= $available_seats; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo (isset($_POST['adult_count']) && $_POST['adult_count'] == $i) ? 'selected' : ''; ?>><?php echo $i; ?></option>
                      <?php endfor; ?>
                    </select>
                  </div>
                  <!-- Children Count -->
                  <div class="relative">
                    <label for="children_count" class="block text-sm font-medium text-gray-700 mb-3">Children (2-11)</label>
                    <i class="fas fa-child input-icon"></i>
                    <select id="children_count" name="children_count" class="w-full input-field">
                      <?php for ($i = 0; $i <= $available_seats; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo (isset($_POST['children_count']) && $_POST['children_count'] == $i) ? 'selected' : ''; ?>><?php echo $i; ?></option>
                      <?php endfor; ?>
                    </select>
                  </div>
                </div>
                <div class="mt-8 text-center">
                  <button type="submit" id="confirm-booking" class="gradient-button w-full md:w-auto"><i class="fas fa-check-circle mr-2"></i>Confirm Booking</button>
                </div>
              </form>
            </div>
          </div>

          <!-- Flight Summary -->
          <div class="lg:col-span-1">
            <div class="summary-card animate-on-scroll">
              <h2 class="section-title">Flight Summary</h2>
              <!-- Seat Availability -->
              <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800"><i class="fas fa-chair mr-2 text-emerald-600"></i>Seat Availability</h3>
                <p class="mt-2 text-sm text-gray-600">
                  <span class="font-medium"><?php echo $available_seats; ?></span> seats available in <span class="font-medium"><?php echo ucfirst($cabin_class); ?></span> Class
                </p>
                <span class="chip mt-2"><i class="fas fa-ticket-alt mr-1"></i><?php echo ucfirst($cabin_class); ?></span>
              </div>
              <!-- Outbound Flight -->
              <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800"><i class="fas fa-plane-departure mr-2 text-emerald-600"></i>Outbound Flight</h3>
                <div class="mt-2 space-y-2 text-sm text-gray-600">
                  <p><span class="font-medium">Airline:</span> <?php echo htmlspecialchars($flight['airline_name']); ?></p>
                  <p><span class="font-medium">Flight:</span> <?php echo htmlspecialchars($flight['flight_number']); ?></p>
                  <p><span class="font-medium">Route:</span> <?php echo htmlspecialchars($flight['departure_city']); ?> to <?php echo htmlspecialchars($flight['arrival_city']); ?></p>
                  <p><span class="font-medium">Date:</span> <?php echo date('D, M j, Y', strtotime($flight['departure_date'])); ?></p>
                  <p><span class="font-medium">Time:</span> <?php echo date('H:i', strtotime($flight['departure_time'])); ?></p>
                  <p><span class="font-medium">Duration:</span> <?php echo htmlspecialchars($flight['flight_duration']); ?> hours</p>
                  <p><span class="font-medium">Price per Seat:</span> PKR <?php echo number_format($flight[$cabin_class . '_price'], 0); ?></p>
                </div>
              </div>
              <!-- Return Flight (if applicable) -->
              <?php if ($flight['has_return']): ?>
                <div class="mb-6">
                  <h3 class="text-lg font-semibold text-gray-800"><i class="fas fa-plane-arrival mr-2 text-purple-600"></i>Return Flight</h3>
                  <div class="mt-2 space-y-2 text-sm text-gray-600">
                    <p><span class="font-medium">Airline:</span> <?php echo htmlspecialchars($flight['return_airline']); ?></p>
                    <p><span class="font-medium">Flight:</span> <?php echo htmlspecialchars($flight['return_flight_number']); ?></p>
                    <p><span class="font-medium">Route:</span> <?php echo htmlspecialchars($flight['arrival_city']); ?> to <?php echo htmlspecialchars($flight['departure_city']); ?></p>
                    <p><span class="font-medium">Date:</span> <?php echo date('D, M j, Y', strtotime($flight['return_date'])); ?></p>
                    <p><span class="font-medium">Time:</span> <?php echo date('H:i', strtotime($flight['return_time'])); ?></p>
                    <p><span class="font-medium">Duration:</span> <?php echo htmlspecialchars($flight['return_flight_duration']); ?> hours</p>
                  </div>
                </div>
              <?php endif; ?>
              <!-- Price Breakdown -->
              <div class="border-t pt-4">
                <h3 class="text-lg font-semibold text-gray-800">Price Breakdown</h3>
                <div class="mt-2 space-y-2 text-sm text-gray-600">
                  <p><span class="font-medium">Price per Seat:</span> PKR <?php echo number_format($flight[$cabin_class . '_price'], 0); ?></p>
                  <p><span class="font-medium">Passengers:</span> <span id="passenger-count">1 Adult</span></p>
                  <p class="text-lg font-bold text-emerald-600"><span class="font-medium">Total:</span> PKR <span id="total-price"><?php echo number_format($flight[$cabin_class . '_price'], 0); ?></span></p>
                </div>
              </div>
            </div>
          </div>
        </div>
      <?php elseif (!$success_message && $flight && $available_seats == 0): ?>
        <div class="alert bg-red-50 border-l-4 border-red-500 text-red-700 p-6 mb-8 animate-on-scroll" role="alert">
          <div class="flex items-center">
            <i class="fas fa-exclamation-circle text-red-500 text-xl mr-3"></i>
            <div>
              <p class="font-medium">No seats available in <?php echo ucfirst($cabin_class); ?> class for this flight.</p>
              <p class="mt-2 text-sm">Please select a different flight or cabin class.</p>
              <a href="flight-booking.php" class="text-emerald-600 hover:text-emerald-800 transition mt-2 inline-block">Search Flights</a>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- Call to Action -->
  <section class="py-20 text-center bg-gradient-to-r from-emerald-50 to-teal-50">
    <div class="container mx-auto px-4">
      <h2 class="text-3xl font-bold text-gray-800 mb-6 animate-on-scroll">Plan Your Umrah Journey</h2>
      <p class="text-lg text-gray-600 mb-8 max-w-3xl mx-auto animate-on-scroll">Explore our packages and services for a complete spiritual experience.</p>
      <a href="packages.php" class="gradient-button inline-block text-lg animate-on-scroll">View Packages</a>
    </div>
  </section>

  <!-- Footer -->
   <?php include 'includes/footer.php'; ?>

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
          confirmButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
          confirmButton.disabled = true;
        });
      }

      // Scroll animations
      const elements = document.querySelectorAll('.animate-on-scroll');
      const observer = new IntersectionObserver(
        (entries) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              entry.target.classList.add('visible');
            }
          });
        }, {
          threshold: 0.1
        }
      );
      elements.forEach((el) => observer.observe(el));
    });
  </script>
</body>

</html>
<?php
$conn->close();
ob_end_flush();
?>