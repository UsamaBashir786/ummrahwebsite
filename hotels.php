<?php
// Start session and output buffering
ob_start();
session_start();
require_once 'config/db.php';

// Log the start of the script
error_log("Starting hotel-booking.php, session ID: " . session_id());

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
  $error_message = "You must be logged in to book a hotel. <a href='login.php' class='text-emerald-600 hover:text-emerald-800 transition'>Log in here</a>.";
  $is_logged_in = false;
} else {
  $is_logged_in = true;
  error_log("User logged in, user_id: " . $_SESSION['user_id']);
}

// Initialize variables
$hotels = [];
$available_rooms = [];
$search_performed = false;
$error_message = isset($error_message) ? $error_message : "";
$success_message = "";
$filters = [
  'search' => $_POST['search'] ?? '',
  'location' => $_POST['location'] ?? '',
  'min_price' => $_POST['min_price'] ?? 0,
  'max_price' => $_POST['max_price'] ?? 50000,
  'rating' => $_POST['rating'] ?? '',
  'amenities' => $_POST['amenities'] ?? []
];
$check_in_date = $_POST['check_in_date'] ?? '';
$check_out_date = $_POST['check_out_date'] ?? '';
$hotel_id = $_POST['hotel_id'] ?? null;

// Automatically update room status for deleted or past bookings
$current_date = date('Y-m-d');
$sql = "SELECT hr.hotel_id, hr.room_id 
        FROM hotel_rooms hr 
        LEFT JOIN hotel_bookings hb ON hr.hotel_id = hb.hotel_id AND hr.room_id = hb.room_id 
            AND hb.check_out_date >= ? AND hb.booking_status NOT IN ('cancelled', 'deleted') 
        WHERE hr.status = 'booked' 
        AND hb.hotel_id IS NULL 
        AND EXISTS (
            SELECT 1 FROM hotel_bookings hb2 
            WHERE hb2.hotel_id = hr.hotel_id 
            AND hb2.room_id = hr.room_id 
            AND hb2.check_out_date < ?
        )";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $current_date, $current_date);
$stmt->execute();
$result = $stmt->get_result();
$rooms_to_update = [];
while ($row = $result->fetch_assoc()) {
  $rooms_to_update[] = $row;
}
$stmt->close();

if (!empty($rooms_to_update)) {
  $sql = "UPDATE hotel_rooms SET status = 'available' WHERE (hotel_id, room_id) IN (";
  $placeholders = [];
  $params = [];
  foreach ($rooms_to_update as $index => $room) {
    $placeholders[] = "(?, ?)";
    $params[] = $room['hotel_id'];
    $params[] = $room['room_id'];
  }
  $sql .= implode(', ', $placeholders) . ")";
  $stmt = $conn->prepare($sql);
  if ($stmt) {
    $stmt->bind_param(str_repeat('ii', count($rooms_to_update)), ...$params);
    $stmt->execute();
    $stmt->close();
    error_log("Updated " . count($rooms_to_update) . " rooms to 'available' status due to past bookings.");
  }
}

// Fetch hotels with filters
$sql = "SELECT h.*, hi.image_path AS primary_image 
        FROM hotels h 
        LEFT JOIN hotel_images hi ON h.id = hi.hotel_id AND hi.is_primary = 1 
        WHERE 1=1";
$params = [];
$types = "";

if (!empty($filters['search'])) {
  $sql .= " AND h.hotel_name LIKE ?";
  $params[] = "%{$filters['search']}%";
  $types .= "s";
}
if (!empty($filters['location'])) {
  $sql .= " AND h.location = ?";
  $params[] = $filters['location'];
  $types .= "s";
}
$sql .= " AND h.price BETWEEN ? AND ?";
$params[] = $filters['min_price'];
$params[] = $filters['max_price'];
$types .= "ii";
if (!empty($filters['rating'])) {
  $sql .= " AND h.rating = ?";
  $params[] = $filters['rating'];
  $types .= "i";
}
if (!empty($filters['amenities'])) {
  foreach ($filters['amenities'] as $amenity) {
    $sql .= " AND FIND_IN_SET(?, h.amenities)";
    $params[] = $amenity;
    $types .= "s";
  }
}

$stmt = $conn->prepare($sql);
if ($types) {
  $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $hotels[] = $row;
}
$stmt->close();

// Process availability check or booking
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if (isset($_POST['check_availability']) && !empty($check_in_date) && !empty($check_out_date) && !empty($hotel_id)) {
    $search_performed = true;

    // Validate dates
    $today = date('Y-m-d');
    if ($check_in_date < $today) {
      $error_message = "Check-in date cannot be in the past.";
    } elseif ($check_out_date <= $check_in_date) {
      $error_message = "Check-out date must be after check-in date.";
    } else {
      // Fetch available rooms
      $sql = "SELECT hr.room_id 
                    FROM hotel_rooms hr 
                    WHERE hr.hotel_id = ? 
                    AND hr.status = 'available' 
                    AND NOT EXISTS (
                        SELECT 1 
                        FROM hotel_bookings hb 
                        WHERE hb.hotel_id = hr.hotel_id 
                        AND hb.room_id = hr.room_id 
                        AND (
                            (hb.check_in_date <= ? AND hb.check_out_date >= ?) 
                            OR (hb.check_in_date <= ? AND hb.check_out_date >= ?)
                            OR (hb.check_in_date >= ? AND hb.check_out_date <= ?)
                        )
                        AND hb.booking_status NOT IN ('cancelled', 'deleted')
                    )";
      $stmt = $conn->prepare($sql);
      if (!$stmt) {
        $error_message = "Database error: " . $conn->error;
      } else {
        $stmt->bind_param("issssss", $hotel_id, $check_out_date, $check_in_date, $check_out_date, $check_in_date, $check_in_date, $check_out_date);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
          $available_rooms[] = $row['room_id'];
        }
        $stmt->close();
      }
    }
  } elseif ($is_logged_in && isset($_POST['book_room']) && !empty($hotel_id) && !empty($_POST['room_id']) && !empty($check_in_date) && !empty($check_out_date)) {
    // Process booking
    $room_id = $_POST['room_id'];
    $special_requests = $_POST['special_requests'] ?? '';
    $user_id = $_SESSION['user_id'];

    // Calculate total price
    $sql = "SELECT price, hotel_name FROM hotels WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $hotel_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $hotel = $result->fetch_assoc();
    $stmt->close();

    $check_in = new DateTime($check_in_date);
    $check_out = new DateTime($check_out_date);
    $nights = $check_in->diff($check_out)->days;
    $total_price = $hotel['price'] * $nights;

    // Generate booking reference
    $booking_reference = 'HB' . strtoupper(uniqid());

    // Start transaction
    $conn->begin_transaction();
    try {
      // Verify room availability
      $sql = "SELECT 1 
                    FROM hotel_rooms hr 
                    WHERE hr.hotel_id = ? 
                    AND hr.room_id = ? 
                    AND hr.status = 'available' 
                    AND NOT EXISTS (
                        SELECT 1 
                        FROM hotel_bookings hb 
                        WHERE hb.hotel_id = hr.hotel_id 
                        AND hb.room_id = hr.room_id 
                        AND (
                            (hb.check_in_date <= ? AND hb.check_out_date >= ?) 
                            OR (hb.check_in_date <= ? AND hb.check_out_date >= ?)
                            OR (hb.check_in_date >= ? AND hb.check_out_date <= ?)
                        )
                        AND hb.booking_status NOT IN ('cancelled', 'deleted')
                    )";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("isssssss", $hotel_id, $room_id, $check_out_date, $check_in_date, $check_out_date, $check_in_date, $check_in_date, $check_out_date);
      $stmt->execute();
      $result = $stmt->get_result();
      if ($result->num_rows == 0) {
        throw new Exception("Selected room is no longer available.");
      }
      $stmt->close();

      // Insert booking
      $sql = "INSERT INTO hotel_bookings (user_id, hotel_id, room_id, check_in_date, check_out_date, total_price, booking_status, payment_status, booking_reference, special_requests) 
                    VALUES (?, ?, ?, ?, ?, ?, 'pending', 'unpaid', ?, ?)";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("iisssdss", $user_id, $hotel_id, $room_id, $check_in_date, $check_out_date, $total_price, $booking_reference, $special_requests);
      $stmt->execute();
      $stmt->close();

      // Update room status
      $sql = "UPDATE hotel_rooms SET status = 'booked' WHERE hotel_id = ? AND room_id = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("is", $hotel_id, $room_id);
      $stmt->execute();
      $stmt->close();

      // Send email to User
      $user_email = $_SESSION['email'] ?? ''; // Assuming email is stored in session or fetch from users table
      $email_subject = 'Thank You for Your Hotel Booking with Umrah Partner';
      $email_message = "Dear Valued Customer,\n\n";
      $email_message .= "Booking created successfully! Reference: $booking_reference\n\n";
      $email_message .= "Booking Details:\n";
      $email_message .= "Hotel: " . htmlspecialchars($hotel['hotel_name']) . "\n";
      $email_message .= "Check-in Date: " . date('D, M j, Y', strtotime($check_in_date)) . "\n";
      $email_message .= "Check-out Date: " . date('D, M j, Y', strtotime($check_out_date)) . "\n";
      $email_message .= "Nights: $nights\n";
      $email_message .= "Total Price: PKR " . number_format($total_price, 0) . "\n";
      $email_message .= "Payment Status: Unpaid\n\n";
      $email_message .= "You can view your booking details in your account under 'My Bookings'.\n";
      $email_message .= "For any queries, contact us at info@umrahpartner.com.\n\n";
      $email_message .= "Best regards,\nUmrah Partner Team";

      $headers = "From: no-reply@umrahpartner.com\r\n";
      $headers .= "Reply-To: info@umrahpartner.com\r\n";
      $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

      if (!mail($user_email, $email_subject, $email_message, $headers)) {
        throw new Exception('Failed to send user email.');
      }

      // Send email to Admin
      $admin_to = 'info@umrahpartner.com';
      $admin_subject = 'New Hotel Booking Submission';
      $admin_message = "New Hotel Booking Submission\n\n";
      $admin_message .= "A new hotel booking has been created.\n\n";
      $admin_message .= "Details:\n";
      $admin_message .= "Booking Reference: $booking_reference\n";
      $admin_message .= "User ID: $user_id\n";
      $admin_message .= "Hotel: " . htmlspecialchars($hotel['hotel_name']) . "\n";
      $admin_message .= "Check-in Date: " . date('D, M j, Y', strtotime($check_in_date)) . "\n";
      $admin_message .= "Check-out Date: " . date('D, M j, Y', strtotime($check_out_date)) . "\n";
      $admin_message .= "Nights: $nights\n";
      $admin_message .= "Total Price: PKR " . number_format($total_price, 0) . "\n";
      $admin_message .= "Payment Status: Unpaid\n";
      $admin_message .= "Special Requests: " . htmlspecialchars($special_requests) . "\n";
      $admin_message .= "Submitted At: " . date('Y-m-d H:i:s') . "\n";

      if (!mail($admin_to, $admin_subject, $admin_message, $headers)) {
        throw new Exception('Failed to send admin email.');
      }

      // Commit transaction
      $conn->commit();
      error_log("Hotel booking successful for user_id=$user_id, hotel_id=$hotel_id, booking_reference=$booking_reference");
      $success_message = "Booking created successfully! Reference: $booking_reference";
    } catch (Exception $e) {
      $conn->rollback();
      $error_message = "Error: " . $e->getMessage();
      error_log("Hotel Booking Error: " . $e->getMessage());
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Find Your Perfect Hotel | UmrahFlights</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700;800&display=swap');

    body {
      font-family: 'Manrope', sans-serif;
      background: #f9fafb;
      color: #1f2937;
      overflow-x: hidden;
    }

    .search-form,
    .availability-form {
      background: rgba(255, 255, 255, 0.95);
      border-radius: 24px;
      padding: 40px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      backdrop-filter: blur(10px);
      transition: transform 0.3s ease;
    }

    .search-form:hover,
    .availability-form:hover {
      transform: translateY(-8px);
    }

    .hotel-card {
      background: linear-gradient(145deg, #ffffff, #f1f5f9);
      border-radius: 24px;
      padding: 24px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      transition: transform 0.4s ease;
    }

    .hotel-card:hover {
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

    .input-field,
    .flatpickr-input {
      border: 1px solid #e5e7eb;
      border-radius: 16px;
      padding: 14px 14px 14px 40px;
      background: #ffffff;
      transition: border-color 0.3s ease, box-shadow 0.3s ease;
    }

    .input-field:focus,
    .flatpickr-input:focus {
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

    .filter-section {
      transition: max-height 0.3s ease-in-out;
      overflow: hidden;
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

      .search-form,
      .availability-form,
      .hotel-card {
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
      <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight mb-4">Find Your Perfect Hotel</h1>
      <p class="text-lg md:text-xl text-gray-100 max-w-2xl">Book a comfortable stay for your Umrah journey with ease.</p>
      <div class="mt-6 text-sm md:text-base">
        <a href="index.php" class="text-gray-200 hover:text-white transition">Home</a>
        <span class="mx-2">></span>
        <span class="text-gray-200">Hotel Booking</span>
      </div>
    </div>
  </section>

  <!-- Main Content -->
  <section class="py-16">
    <div class="container mx-auto px-4">
      <!-- Search and Filter Form -->
      <div class="search-form mb-12 animate-on-scroll">
        <h2 class="section-title">Search Hotels</h2>
        <form method="POST" id="filter-form">
          <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <!-- Search by Name -->
            <div class="relative">
              <label for="search" class="block text-sm font-medium text-gray-700 mb-3">Hotel Name</label>
              <i class="fas fa-search input-icon"></i>
              <input type="text" name="search" id="search" value="<?php echo htmlspecialchars($filters['search']); ?>"
                class="w-full input-field" placeholder="Enter hotel name">
            </div>
            <!-- Location -->
            <div class="relative">
              <label for="location" class="block text-sm font-medium text-gray-700 mb-3">Location</label>
              <i class="fas fa-map-marker-alt input-icon"></i>
              <select name="location" id="location" class="w-full input-field">
                <option value="">All Locations</option>
                <option value="makkah" <?php echo $filters['location'] == 'makkah' ? 'selected' : ''; ?>>Makkah</option>
                <option value="madinah" <?php echo $filters['location'] == 'madinah' ? 'selected' : ''; ?>>Madinah</option>
              </select>
            </div>
            <!-- Price Range -->
            <div class="relative">
              <label for="min_price" class="block text-sm font-medium text-gray-700 mb-3">Price Range (PKR)</label>
              <div class="flex space-x-2">
                <div class="relative flex-1">
                  <i class="fas fa-dollar-sign input-icon"></i>
                  <input type="number" name="min_price" id="min_price" value="<?php echo htmlspecialchars($filters['min_price']); ?>"
                    class="w-full input-field" placeholder="Min" min="0">
                </div>
                <div class="relative flex-1">
                  <i class="fas fa-dollar-sign input-icon"></i>
                  <input type="number" name="max_price" id="max_price" value="<?php echo htmlspecialchars($filters['max_price']); ?>"
                    class="w-full input-field" placeholder="Max" max="50000">
                </div>
              </div>
            </div>
            <!-- Rating -->
            <div class="relative">
              <label for="rating" class="block text-sm font-medium text-gray-700 mb-3">Rating</label>
              <i class="fas fa-star input-icon"></i>
              <select name="rating" id="rating" class="w-full input-field">
                <option value="">All Ratings</option>
                <?php for ($i = 1; $i <= 5; $i++): ?>
                  <option value="<?php echo $i; ?>" <?php echo $filters['rating'] == $i ? 'selected' : ''; ?>><?php echo $i; ?> Stars</option>
                <?php endfor; ?>
              </select>
            </div>
          </div>
          <!-- Amenities -->
          <div class="filter-section mb-6">
            <button type="button" id="toggle-amenities" class="text-emerald-600 font-medium mb-2 flex items-center hover:text-emerald-800 transition">
              <i class="fas fa-filter mr-2"></i> Filter by Amenities
            </button>
            <div id="amenities-section" class="hidden grid grid-cols-2 md:grid-cols-4 gap-4">
              <?php
              $available_amenities = ['wifi', 'parking', 'restaurant', 'gym', 'pool', 'ac', 'room_service', 'spa'];
              foreach ($available_amenities as $amenity):
              ?>
                <label class="flex items-center space-x-2 text-gray-700">
                  <input type="checkbox" name="amenities[]" value="<?php echo $amenity; ?>"
                    <?php echo in_array($amenity, $filters['amenities']) ? 'checked' : ''; ?>
                    class="text-emerald-600 focus:ring-emerald-500">
                  <span class="text-sm"><?php echo ucfirst($amenity); ?></span>
                </label>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="text-center">
            <button type="submit" class="gradient-button w-full md:w-auto"><i class="fas fa-search mr-2"></i>Apply Filters</button>
          </div>
        </form>
      </div>

      <!-- Availability Check Form -->
      <div class="availability-form mb-12 animate-on-scroll">
        <h2 class="section-title">Check Room Availability</h2>
        <form method="POST" id="availability-form">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Select Hotel -->
            <div class="relative">
              <label for="hotel_id" class="block text-sm font-medium text-gray-700 mb-3">Select Hotel</label>
              <i class="fas fa-hotel input-icon"></i>
              <select name="hotel_id" id="hotel_id" class="w-full input-field" required>
                <option value="">Select a hotel</option>
                <?php foreach ($hotels as $hotel): ?>
                  <option value="<?php echo $hotel['id']; ?>" <?php echo ($hotel_id == $hotel['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($hotel['hotel_name']); ?> (<?php echo htmlspecialchars($hotel['location']); ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <!-- Check-in Date -->
            <div class="relative">
              <label for="check_in_date" class="block text-sm font-medium text-gray-700 mb-3">Check-in Date</label>
              <i class="fas fa-calendar-alt input-icon"></i>
              <input type="text" name="check_in_date" id="check_in_date" class="w-full input-field flatpickr-input"
                value="<?php echo htmlspecialchars($check_in_date); ?>" required>
            </div>
            <!-- Check-out Date -->
            <div class="relative">
              <label for="check_out_date" class="block text-sm font-medium text-gray-700 mb-3">Check-out Date</label>
              <i class="fas fa-calendar-alt input-icon"></i>
              <input type="text" name="check_out_date" id="check_out_date" class="w-full input-field flatpickr-input"
                value="<?php echo htmlspecialchars($check_out_date); ?>" required>
            </div>
          </div>
          <div class="mt-8 text-center">
            <button type="submit" name="check_availability" class="gradient-button w-full md:w-auto"><i class="fas fa-search mr-2"></i>Check Availability</button>
          </div>
        </form>
      </div>

      <!-- Error/Success Messages -->
      <?php if (!empty($error_message)): ?>
        <div class="alert bg-red-50 border-l-4 border-red-500 text-red-700 p-6 mb-8 animate-on-scroll">
          <div class="flex items-center">
            <i class="fas fa-exclamation-circle text-red-500 text-xl mr-3"></i>
            <p class="text-sm"><?php echo $error_message; ?></p>
          </div>
        </div>
      <?php endif; ?>
      <?php if (!empty($success_message)): ?>
        <div class="alert bg-green-50 border-l-4 border-green-500 text-green-700 p-6 mb-8 animate-on-scroll">
          <div class="flex">
            <i class="fas fa-check-circle text-green-500 text-xl mr-3"></i>
            <div>
              <p class="font-medium text-sm"><?php echo htmlspecialchars($success_message); ?></p>
              <p class="mt-2 text-sm">You will receive a confirmation email soon.</p>
              <div class="mt-4 space-y-2">
                <p><a href="user/index.php" class="text-emerald-600 hover:text-emerald-800 transition text-sm">View Your Bookings</a></p>
                <p><a href="hotel-booking.php" class="text-emerald-600 hover:text-emerald-800 transition text-sm">Check Another Hotel</a></p>
                <p><a href="index.php" class="text-emerald-600 hover:text-emerald-800 transition text-sm">Back to Home</a></p>
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <!-- Available Rooms -->
      <?php if ($search_performed && empty($error_message)): ?>
        <div class="mb-12 animate-on-scroll">
          <h2 class="section-title">
            <?php
            $selected_hotel = array_filter($hotels, fn($h) => $h['id'] == $hotel_id)[array_key_first(array_filter($hotels, fn($h) => $h['id'] == $hotel_id))];
            ?>
            Available Rooms at <?php echo htmlspecialchars($selected_hotel['hotel_name']); ?>
          </h2>
          <?php if (empty($available_rooms)): ?>
            <div class="alert bg-blue-50 border-l-4 border-blue-500 text-blue-700 p-6 rounded-lg animate-on-scroll">
              <p class="font-medium text-lg">No rooms available for the selected dates.</p>
              <p class="mt-2 text-sm">Try adjusting your dates or selecting another hotel.</p>
              <a href="hotel-booking.php" class="text-emerald-600 hover:text-emerald-800 transition mt-2 inline-block text-sm">Search Again</a>
            </div>
          <?php elseif ($is_logged_in): ?>
            <form method="POST" id="booking-form" class="bg-white p-8 rounded-2xl shadow-lg animate-on-scroll">
              <input type="hidden" name="hotel_id" value="<?php echo htmlspecialchars($hotel_id); ?>">
              <input type="hidden" name="check_in_date" value="<?php echo htmlspecialchars($check_in_date); ?>">
              <input type="hidden" name="check_out_date" value="<?php echo htmlspecialchars($check_out_date); ?>">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="relative">
                  <label for="room_id" class="block text-sm font-medium text-gray-700 mb-3">Select Room</label>
                  <i class="fas fa-door-open input-icon"></i>
                  <select name="room_id" id="room_id" class="w-full input-field" required>
                    <option value="">Select a room</option>
                    <?php foreach ($available_rooms as $room_id): ?>
                      <option value="<?php echo htmlspecialchars($room_id); ?>"><?php echo htmlspecialchars($room_id); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="relative">
                  <label for="special_requests" class="block text-sm font-medium text-gray-700 mb-3">Special Requests (Optional)</label>
                  <i class="fas fa-comment input-icon"></i>
                  <textarea name="special_requests" id="special_requests" class="w-full input-field" rows="3" placeholder="Any special requests?"></textarea>
                </div>
              </div>
              <div class="text-center">
                <button type="submit" name="book_room" class="gradient-button"><i class="fas fa-book mr-2"></i>Book Room</button>
              </div>
            </form>
          <?php else: ?>
            <div class="alert bg-red-50 border-l-4 border-red-500 text-red-700 p-6 rounded-lg animate-on-scroll">
              <p class="text-sm">You must be logged in to book a room. <a href="login.php" class="text-emerald-600 hover:text-emerald-800 transition">Log in here</a>.</p>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <!-- Hotels List -->
      <?php if (empty($hotels)): ?>
        <div class="alert bg-blue-50 border-l-4 border-blue-500 text-blue-700 p-6 rounded-lg animate-on-scroll">
          <p class="font-medium text-lg">No hotels found.</p>
          <p class="mt-2 text-sm">Try adjusting your search filters.</p>
          <a href="hotel-booking.php" class="text-emerald-600 hover:text-emerald-800 transition mt-2 inline-block text-sm">Reset Filters</a>
        </div>
      <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          <?php foreach ($hotels as $hotel): ?>
            <div class="hotel-card animate-on-scroll">
              <img src="<?php echo htmlspecialchars($hotel['primary_image'] ?: 'images/default-hotel.jpg'); ?>"
                alt="<?php echo htmlspecialchars($hotel['hotel_name']); ?>"
                class="w-full h-48 object-cover rounded-2xl mb-4">
              <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($hotel['hotel_name']); ?></h3>
              <p class="text-sm text-gray-600"><i class="fas fa-map-marker-alt mr-2 text-emerald-600"></i><?php echo htmlspecialchars($hotel['location']); ?></p>
              <p class="text-emerald-600 font-bold mt-2 text-lg">PKR <?php echo number_format($hotel['price'], 0); ?>/night</p>
              <div class="flex items-center mt-2">
                <?php for ($i = 0; $i < $hotel['rating']; $i++): ?>
                  <i class="fas fa-star text-yellow-400"></i>
                <?php endfor; ?>
                <span class="text-gray-600 text-sm ml-2"><?php echo $hotel['rating']; ?> Stars</span>
              </div>
              <p class="text-gray-600 text-sm mt-2 line-clamp-2"><?php echo htmlspecialchars($hotel['description']); ?></p>
              <div class="mt-3 flex flex-wrap gap-2">
                <?php if (!empty($hotel['amenities'])): ?>
                  <?php foreach (explode(',', $hotel['amenities']) as $amenity): ?>
                    <span class="chip"><i class="fas fa-check-circle mr-1"></i><?php echo ucfirst($amenity); ?></span>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
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
  <footer class="footer-bg py-20 text-gray-200">
    <div class="container mx-auto px-4">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-12">
        <div class="animate-on-scroll">
          <h3 class="text-2xl font-bold mb-6 text-white">About Us</h3>
          <p class="text-gray-300 text-sm leading-relaxed">
            We specialize in creating transformative Umrah experiences, blending premium services with spiritual fulfillment.
          </p>
          <div class="flex space-x-6 mt-6">
            <a href="#" class="social-icon"><i class="fab fa-facebook"></i></a>
            <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
            <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
          </div>
        </div>
        <div class="animate-on-scroll">
          <h3 class="text-2xl font-bold mb-6 text-white">Quick Links</h3>
          <ul class="space-y-4">
            <li><a href="index.php" class="text-gray-300 hover:text-white transition">Home</a></li>
            <li><a href="about.php" class="text-gray-300 hover:text-white transition">About Us</a></li>
            <li><a href="packages.php" class="text-gray-300 hover:text-white transition">Our Packages</a></li>
            <li><a href="faqs.php" class="text-gray-300 hover:text-white transition">FAQs</a></li>
            <li><a href="contact.php" class="text-gray-300 hover:text-white transition">Contact Us</a></li>
          </ul>
        </div>
        <div class="animate-on-scroll">
          <h3 class="text-2xl font-bold mb-6 text-white">Our Services</h3>
          <ul class="space-y-4">
            <li><a href="packages.php" class="text-gray-300 hover:text-white transition">Umrah Packages</a></li>
            <li><a href="flight-booking.php" class="text-gray-300 hover:text-white transition">Flight Booking</a></li>
            <li><a href="hotel-booking.php" class="text-gray-300 hover:text-white transition">Hotel Reservation</a></li>
            <li><a href="visa.php" class="text-gray-300 hover:text-white transition">Visa Processing</a></li>
            <li><a href="transport.php" class="text-gray-300 hover:text-white transition">Transportation</a></li>
          </ul>
        </div>
        <div class="animate-on-scroll">
          <h3 class="text-2xl font-bold mb-6 text-white">Contact Us</h3>
          <ul class="space-y-4 text-gray-300">
            <li class="flex items-start">
              <i class="fas fa-map-marker-alt mt-1 mr-3 text-emerald-400"></i>
              <span>123 Main Street, City, Country</span>
            </li>
            <li class="flex items-center">
              <i class="fas fa-phone mr-3 text-emerald-400"></i>
              <span>+44 775 983691</span>
            </li>
            <li class="flex items-center">
              <i class="fas fa-envelope mr-3 text-emerald-400"></i>
              <span>info@umrahpartner.com</span>
            </li>
          </ul>
        </div>
      </div>
      <div class="border-t border-gray-700 mt-12 pt-8 flex flex-col md:flex-row justify-between items-center animate-on-scroll">
        <p class="text-gray-400 text-sm">© 2025 Umrah Partners. All rights reserved.</p>
        <div class="flex space-x-8 mt-4 md:mt-0">
          <a href="privacy.php" class="text-gray-400 hover:text-white text-sm transition">Privacy Policy</a>
          <a href="terms.php" class="text-gray-400 hover:text-white text-sm transition">Terms of Service</a>
          <a href="cookies.php" class="text-gray-400 hover:text-white text-sm transition">Cookie Policy</a>
        </div>
      </div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Initialize date pickers
      flatpickr("#check_in_date", {
        dateFormat: "Y-m-d",
        minDate: "today"
      });
      flatpickr("#check_out_date", {
        dateFormat: "Y-m-d",
        minDate: "today"
      });

      // Update check-out min date
      document.getElementById('check_in_date').addEventListener('change', function() {
        flatpickr("#check_out_date").set("minDate", this.value);
      });

      // Toggle amenities section
      document.getElementById('toggle-amenities').addEventListener('click', function() {
        const section = document.getElementById('amenities-section');
        section.classList.toggle('hidden');
      });

      // Price range validation
      document.getElementById('min_price').addEventListener('input', function() {
        if (this.value < 0) this.value = 0;
        const maxPrice = document.getElementById('max_price');
        if (parseInt(this.value) > parseInt(maxPrice.value)) {
          maxPrice.value = this.value;
        }
      });
      document.getElementById('max_price').addEventListener('input', function() {
        if (this.value > 50000) this.value = 50000;
        const minPrice = document.getElementById('min_price');
        if (parseInt(this.value) < parseInt(minPrice.value)) {
          minPrice.value = this.value;
        }
      });

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