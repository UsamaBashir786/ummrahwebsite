<?php
require_once '../config/db.php'; // Include db.php with $conn
// Start admin session
session_name('admin_session');
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if admin is logged in
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
  header('Location: login.php');
  exit;
}

// Verify database connection
if (!$conn) {
  die("Database connection failed: " . mysqli_connect_error());
}

// Function to format numbers to k format
function format_number($number)
{
  if ($number >= 1000) {
    $number = $number / 1000;
    return number_format($number, $number >= 10 ? 0 : 1) . 'k';
  }
  return number_format($number, 2);
}

// Fetch comprehensive dashboard statistics using $conn
// 1. Total Bookings (flight_bookings + hotel_bookings + package_bookings + transportation_bookings)
$stmt = $conn->prepare("
    SELECT 
        (SELECT COUNT(*) FROM flight_bookings) AS flight_bookings,
        (SELECT COUNT(*) FROM hotel_bookings) AS hotel_bookings,
        (SELECT COUNT(*) FROM package_bookings) AS package_bookings,
        (SELECT COUNT(*) FROM transportation_bookings) AS transportation_bookings,
        (SELECT COUNT(*) FROM flight_bookings) +
        (SELECT COUNT(*) FROM hotel_bookings) +
        (SELECT COUNT(*) FROM package_bookings) +
        (SELECT COUNT(*) FROM transportation_bookings) AS total_bookings
");
if (!$stmt) {
  error_log("Query preparation failed for total bookings: " . $conn->error);
}
$stmt->execute();
$bookings = $stmt->get_result()->fetch_assoc();
$total_bookings = $bookings['total_bookings'] ?? 0;
$flight_bookings = $bookings['flight_bookings'] ?? 0;
$hotel_bookings = $bookings['hotel_bookings'] ?? 0;
$package_bookings = $bookings['package_bookings'] ?? 0;
$transportation_bookings = $bookings['transportation_bookings'] ?? 0;
$stmt->close();

// 2. Total Revenue (sum of total_price from booking tables)
$stmt = $conn->prepare("
    SELECT 
        (SELECT COALESCE(SUM(total_price), 0) FROM flight_bookings) AS flight_revenue,
        (SELECT COALESCE(SUM(total_price), 0) FROM hotel_bookings) AS hotel_revenue,
        (SELECT COALESCE(SUM(total_price), 0) FROM package_bookings) AS package_revenue,
        (SELECT COALESCE(SUM(price), 0) FROM transportation_bookings) AS transportation_revenue,
        (SELECT COALESCE(SUM(total_price), 0) FROM flight_bookings) +
        (SELECT COALESCE(SUM(total_price), 0) FROM hotel_bookings) +
        (SELECT COALESCE(SUM(total_price), 0) FROM package_bookings) +
        (SELECT COALESCE(SUM(price), 0) FROM transportation_bookings) AS total_revenue
");
if (!$stmt) {
  error_log("Query preparation failed for total revenue: " . $conn->error);
}
$stmt->execute();
$revenue = $stmt->get_result()->fetch_assoc();
$total_revenue = $revenue['total_revenue'] ?? 0;
$flight_revenue = $revenue['flight_revenue'] ?? 0;
$hotel_revenue = $revenue['hotel_revenue'] ?? 0;
$package_revenue = $revenue['package_revenue'] ?? 0;
$transportation_revenue = $revenue['transportation_revenue'] ?? 0;
$stmt->close();

// 3. Total Users
$stmt = $conn->prepare("SELECT COUNT(*) AS total_users FROM users");
if (!$stmt) {
  error_log("Query preparation failed for total users: " . $conn->error);
}
$stmt->execute();
$total_users = $stmt->get_result()->fetch_assoc()['total_users'] ?? 0;
$stmt->close();

// 4. Active Packages (by star_rating)
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) AS total_packages,
        SUM(CASE WHEN star_rating = 'low_budget' THEN 1 ELSE 0 END) AS low_budget_packages,
        SUM(CASE WHEN star_rating = '3_star' THEN 1 ELSE 0 END) AS three_star_packages,
        SUM(CASE WHEN star_rating = '4_star' THEN 1 ELSE 0 END) AS four_star_packages,
        SUM(CASE WHEN star_rating = '5_star' THEN 1 ELSE 0 END) AS five_star_packages
    FROM umrah_packages
");
if (!$stmt) {
  error_log("Query preparation failed for active packages: " . $conn->error);
}
$stmt->execute();
$packages = $stmt->get_result()->fetch_assoc();
$total_packages = $packages['total_packages'] ?? 0;
$low_budget_packages = $packages['low_budget_packages'] ?? 0;
$three_star_packages = $packages['three_star_packages'] ?? 0;
$four_star_packages = $packages['four_star_packages'] ?? 0;
$five_star_packages = $packages['five_star_packages'] ?? 0;
$stmt->close();

// 5. Booking Status Breakdown
$stmt = $conn->prepare("
    SELECT 
        (SELECT COUNT(*) FROM flight_bookings WHERE booking_status = 'pending') +
        (SELECT COUNT(*) FROM hotel_bookings WHERE booking_status = 'pending') +
        (SELECT COUNT(*) FROM package_bookings WHERE booking_status = 'pending') +
        (SELECT COUNT(*) FROM transportation_bookings WHERE booking_status = 'pending') AS pending_bookings,
        (SELECT COUNT(*) FROM flight_bookings WHERE booking_status = 'confirmed') +
        (SELECT COUNT(*) FROM hotel_bookings WHERE booking_status = 'confirmed') +
        (SELECT COUNT(*) FROM package_bookings WHERE booking_status = 'confirmed') +
        (SELECT COUNT(*) FROM transportation_bookings WHERE booking_status = 'confirmed') AS confirmed_bookings,
        (SELECT COUNT(*) FROM flight_bookings WHERE booking_status = 'cancelled') +
        (SELECT COUNT(*) FROM hotel_bookings WHERE booking_status = 'cancelled') +
        (SELECT COUNT(*) FROM package_bookings WHERE booking_status = 'cancelled') +
        (SELECT COUNT(*) FROM transportation_bookings WHERE booking_status = 'cancelled') AS cancelled_bookings
");
if (!$stmt) {
  error_log("Query preparation failed for booking status: " . $conn->error);
}
$stmt->execute();
$booking_status = $stmt->get_result()->fetch_assoc();
$pending_bookings = $booking_status['pending_bookings'] ?? 0;
$confirmed_bookings = $booking_status['confirmed_bookings'] ?? 0;
$cancelled_bookings = $booking_status['cancelled_bookings'] ?? 0;
$stmt->close();

// 6. Hotels and Rooms
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) AS total_hotels,
        SUM(CASE WHEN location = 'makkah' THEN 1 ELSE 0 END) AS makkah_hotels,
        SUM(CASE WHEN location = 'madinah' THEN 1 ELSE 0 END) AS madinah_hotels,
        COALESCE(AVG(rating), 0) AS avg_hotel_rating
    FROM hotels
");
if (!$stmt) {
  error_log("Query preparation failed for hotels: " . $conn->error);
}
$stmt->execute();
$hotels = $stmt->get_result()->fetch_assoc();
$total_hotels = $hotels['total_hotels'] ?? 0;
$makkah_hotels = $hotels['makkah_hotels'] ?? 0;
$madinah_hotels = $hotels['madinah_hotels'] ?? 0;
$avg_hotel_rating = round((float)$hotels['avg_hotel_rating'], 1);
$stmt->close();

// 7. Hotel Rooms Availability
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) AS total_rooms,
        SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) AS available_rooms,
        SUM(CASE WHEN status = 'booked' THEN 1 ELSE 0 END) AS booked_rooms,
        SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) AS maintenance_rooms
    FROM hotel_rooms
");
if (!$stmt) {
  error_log("Query preparation failed for hotel rooms: " . $conn->error);
}
$stmt->execute();
$rooms = $stmt->get_result()->fetch_assoc();
$total_rooms = $rooms['total_rooms'] ?? 0;
$available_rooms = $rooms['available_rooms'] ?? 0;
$booked_rooms = $rooms['booked_rooms'] ?? 0;
$maintenance_rooms = $rooms['maintenance_rooms'] ?? 0;
$stmt->close();

// 8. Total Flights and Seat Availability
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) AS total_flights,
        SUM(economy_seats) AS economy_seats,
        SUM(business_seats) AS business_seats,
        SUM(first_class_seats) AS first_class_seats
    FROM flights
");
if (!$stmt) {
  error_log("Query preparation failed for flights: " . $conn->error);
}
$stmt->execute();
$flights = $stmt->get_result()->fetch_assoc();
$total_flights = $flights['total_flights'] ?? 0;
$economy_seats = $flights['economy_seats'] ?? 0;
$business_seats = $flights['business_seats'] ?? 0;
$first_class_seats = $flights['first_class_seats'] ?? 0;
$stmt->close();

// 9. Transportation Routes
$stmt = $conn->prepare("
    SELECT 
        (SELECT COUNT(*) FROM taxi_routes) AS taxi_routes,
        (SELECT COUNT(*) FROM rentacar_routes) AS rentacar_routes
");
if (!$stmt) {
  error_log("Query preparation failed for transportation routes: " . $conn->error);
}
$stmt->execute();
$transportation = $stmt->get_result()->fetch_assoc();
$taxi_routes = $transportation['taxi_routes'] ?? 0;
$rentacar_routes = $transportation['rentacar_routes'] ?? 0;
$stmt->close();

// 10. Payment Status
$stmt = $conn->prepare("
    SELECT 
        (SELECT COUNT(*) FROM flight_bookings WHERE payment_status = 'pending') +
        (SELECT COUNT(*) FROM hotel_bookings WHERE payment_status = 'unpaid') +
        (SELECT COUNT(*) FROM package_bookings WHERE payment_status = 'pending') AS pending_payments,
        (SELECT COUNT(*) FROM flight_bookings WHERE payment_status = 'completed') +
        (SELECT COUNT(*) FROM hotel_bookings WHERE payment_status = 'paid') +
        (SELECT COUNT(*) FROM package_bookings WHERE payment_status = 'paid') AS completed_payments,
        (SELECT COUNT(*) FROM flight_bookings WHERE payment_status = 'failed') +
        (SELECT COUNT(*) FROM hotel_bookings WHERE payment_status = 'refunded') +
        (SELECT COUNT(*) FROM package_bookings WHERE payment_status = 'refunded') AS failed_payments
");
if (!$stmt) {
  error_log("Query preparation failed for payment status: " . $conn->error);
}
$stmt->execute();
$payment_status = $stmt->get_result()->fetch_assoc();
$pending_payments = $payment_status['pending_payments'] ?? 0;
$completed_payments = $payment_status['completed_payments'] ?? 0;
$failed_payments = $payment_status['failed_payments'] ?? 0;
$stmt->close();

// 11. Average Booking Value
$stmt = $conn->prepare("
    SELECT 
        COALESCE((SELECT AVG(total_price) FROM flight_bookings WHERE total_price > 0), 0) AS avg_flight_booking,
        COALESCE((SELECT AVG(total_price) FROM hotel_bookings WHERE total_price > 0), 0) AS avg_hotel_booking,
        COALESCE((SELECT AVG(total_price) FROM package_bookings WHERE total_price > 0), 0) AS avg_package_booking,
        COALESCE((SELECT AVG(price) FROM transportation_bookings WHERE price > 0), 0) AS avg_transportation_booking
");
if (!$stmt) {
  error_log("Query preparation failed for average booking value: " . $conn->error);
}
$stmt->execute();
$avg_bookings = $stmt->get_result()->fetch_assoc();
$avg_flight_booking = round((float)$avg_bookings['avg_flight_booking'], 2);
$avg_hotel_booking = round((float)$avg_bookings['avg_hotel_booking'], 2);
$avg_package_booking = round((float)$avg_bookings['avg_package_booking'], 2);
$avg_transportation_booking = round((float)$avg_bookings['avg_transportation_booking'], 2);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard | UmrahFlights</title>
  <!-- Tailwind CSS CDN -->
  <link rel="stylesheet" href="../src/output.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="assets/css/index.css">
</head>

<body class="bg-gray-100">
  <?php include 'includes/sidebar.php'; ?>

  <!-- Main Content -->
  <div class="ml-0 md:ml-64 mt-10 px-4 sm:px-6 lg:px-8 transition-all duration-300">
    <!-- Top Navbar -->
    <nav class="bg-white shadow-lg rounded-lg p-5 mb-6">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <h4 id="dashboardHeader" class="text-lg font-semibold text-gray-800 cursor-pointer hover:text-indigo-600">Dashboard</h4>
        </div>

        <div class="flex items-center space-x-4">
          <!-- User Dropdown -->
          <div class="relative">
            <button id="userDropdownButton" class="flex items-center space-x-2 text-gray-700 hover:bg-indigo-50 rounded-lg px-3 py-2 focus:outline-none">
              <div class="rounded-full overflow-hidden" style="width: 32px; height: 32px;">
                <!-- Placeholder for user image -->
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
    <script>
      document.addEventListener("DOMContentLoaded", function() {
        const dropdownBtn = document.getElementById("userDropdownButton");
        const dropdownMenu = document.getElementById("userDropdownMenu");

        // Toggle menu on button click
        dropdownBtn.addEventListener("click", function(e) {
          e.stopPropagation(); // Stop event from bubbling to document
          dropdownMenu.classList.toggle("hidden");
        });

        // Hide the dropdown when clicking outside
        document.addEventListener("click", function(e) {
          // If click target is not the button or inside the menu, hide it
          if (!dropdownBtn.contains(e.target) && !dropdownMenu.contains(e.target)) {
            dropdownMenu.classList.add("hidden");
          }
        });
      });
    </script>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
      <!-- Total Revenue -->
      <div>
        <div class="bg-white shadow-lg rounded-lg p-6 border-l-4 border-green-500">
          <div class="flex justify-between items-center">
            <div>
              <h3 class="text-2xl font-bold text-gray-800">Rs.<?php echo format_number($total_revenue); ?></h3>
              <p class="text-sm text-gray-500">Total Revenue</p>
            </div>
            <div class="flex items-center justify-center w-12 h-12 rounded-full bg-green-100 text-green-500">
              <i class="fas fa-wallet text-xl"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Average Package Booking Value -->
      <div>
        <div class="bg-white shadow-lg rounded-lg p-6 border-l-4 border-yellow-500">
          <div class="flex justify-between items-center">
            <div>
              <h3 class="text-2xl font-bold text-gray-800">Rs.<?php echo format_number($avg_package_booking); ?></h3>
              <p class="text-sm text-gray-500">Avg Package Booking</p>
            </div>
            <div class="flex items-center justify-center w-12 h-12 rounded-full bg-yellow-100 text-yellow-500">
              <i class="fas fa-box-open text-xl"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Average Flight Booking Value -->
      <div>
        <div class="bg-white shadow-lg rounded-lg p-6 border-l-4 border-blue-500">
          <div class="flex justify-between items-center">
            <div>
              <h3 class="text-2xl font-bold text-gray-800">Rs.<?php echo format_number($avg_flight_booking); ?></h3>
              <p class="text-sm text-gray-500">Avg Flight Booking</p>
            </div>
            <div class="flex items-center justify-center w-12 h-12 rounded-full bg-blue-100 text-blue-500">
              <i class="fas fa-plane-departure text-xl"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Total Bookings -->
      <div>
        <div class="bg-white shadow-lg rounded-lg p-6 border-l-4 border-blue-500">
          <div class="flex justify-between items-center">
            <div>
              <h3 class="text-2xl font-bold text-gray-800"><?php echo $total_bookings; ?></h3>
              <p class="text-sm text-gray-500">Total Bookings</p>
            </div>
            <div class="flex items-center justify-center w-12 h-12 rounded-full bg-blue-100 text-blue-500">
              <i class="fas fa-calendar-check text-xl"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Total Users -->
      <div>
        <div class="bg-white shadow-lg rounded-lg p-6 border-l-4 border-cyan-500">
          <div class="flex justify-between items-center">
            <div>
              <h3 class="text-2xl font-bold text-gray-800"><?php echo $total_users; ?></h3>
              <p class="text-sm text-gray-500">Total Users</p>
            </div>
            <div class="flex items-center justify-center w-12 h-12 rounded-full bg-cyan-100 text-cyan-500">
              <i class="fas fa-users text-xl"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Active Packages -->
      <div>
        <div class="bg-white shadow-lg rounded-lg p-6 border-l-4 border-yellow-500">
          <div class="flex justify-between items-center">
            <div>
              <h3 class="text-2xl font-bold text-gray-800"><?php echo $total_packages; ?></h3>
              <p class="text-sm text-gray-500">Active Packages</p>
              <p class="text-xs text-gray-500 mt-1">
                Low Budget: <?php echo $low_budget_packages; ?> |
                3-Star: <?php echo $three_star_packages; ?> |
                4-Star: <?php echo $four_star_packages; ?> |
                5-Star: <?php echo $five_star_packages; ?>
              </p>
            </div>
            <div class="flex items-center justify-center w-12 h-12 rounded-full bg-yellow-100 text-yellow-500">
              <i class="fas fa-box text-xl"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Pending Bookings -->
      <div>
        <div class="bg-white shadow-lg rounded-lg p-6 border-l-4 border-red-500">
          <div class="flex justify-between items-center">
            <div>
              <h3 class="text-2xl font-bold text-gray-800"><?php echo $pending_bookings; ?></h3>
              <p class="text-sm text-gray-500">Pending Bookings</p>
            </div>
            <div class="flex items-center justify-center w-12 h-12 rounded-full bg-red-100 text-red-500">
              <i class="fas fa-hourglass-half text-xl"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Confirmed Bookings -->
      <div>
        <div class="bg-white shadow-lg rounded-lg p-6 border-l-4 border-green-500">
          <div class="flex justify-between items-center">
            <div>
              <h3 class="text-2xl font-bold text-gray-800"><?php echo $confirmed_bookings; ?></h3>
              <p class="text-sm text-gray-500">Confirmed Bookings</p>
            </div>
            <div class="flex items-center justify-center w-12 h-12 rounded-full bg-green-100 text-green-500">
              <i class="fas fa-check-circle text-xl"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Cancelled Bookings -->
      <div>
        <div class="bg-white shadow-lg rounded-lg p-6 border-l-4 border-gray-500">
          <div class="flex justify-between items-center">
            <div>
              <h3 class="text-2xl font-bold text-gray-800"><?php echo $cancelled_bookings; ?></h3>
              <p class="text-sm text-gray-500">Cancelled Bookings</p>
            </div>
            <div class="flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 text-gray-500">
              <i class="fas fa-times-circle text-xl"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Total Hotels -->
      <div>
        <div class="bg-white shadow-lg rounded-lg p-6 border-l-4 border-blue-500">
          <div class="flex justify-between items-center">
            <div>
              <h3 class="text-2xl font-bold text-gray-800"><?php echo $total_hotels; ?></h3>
              <p class="text-sm text-gray-500">Total Hotels</p>
            </div>
            <div class="flex items-center justify-center w-12 h-12 rounded-full bg-blue-100 text-blue-500">
              <i class="fas fa-hotel text-xl"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Makkah Hotels -->
      <div>
        <div class="bg-white shadow-lg rounded-lg p-6 border-l-4 border-cyan-500">
          <div class="flex justify-between items-center">
            <div>
              <h3 class="text-2xl font-bold text-gray-800"><?php echo $makkah_hotels; ?></h3>
              <p class="text-sm text-gray-500">Makkah Hotels</p>
            </div>
            <div class="flex items-center justify-center w-12 h-12 rounded-full bg-cyan-100 text-cyan-500">
              <i class="fas fa-mosque text-xl"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Madinah Hotels -->
      <div>
        <div class="bg-white shadow-lg rounded-lg p-6 border-l-4 border-cyan-500">
          <div class="flex justify-between items-center">
            <div>
              <h3 class="text-2xl font-bold text-gray-800"><?php echo $madinah_hotels; ?></h3>
              <p class="text-sm text-gray-500">Madinah Hotels</p>
            </div>
            <div class="flex items-center justify-center w-12 h-12 rounded-full bg-cyan-100 text-cyan-500">
              <i class="fas fa-mosque text-xl"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Average Hotel Rating -->
      <div>
        <div class="bg-white shadow-lg rounded-lg p-6 border-l-4 border-yellow-500">
          <div class="flex justify-between items-center">
            <div>
              <h3 class="text-2xl font-bold text-gray-800"><?php echo $avg_hotel_rating; ?>/5</h3>
              <p class="text-sm text-gray-500">Avg Hotel Rating</p>
            </div>
            <div class="flex items-center justify-center w-12 h-12 rounded-full bg-yellow-100 text-yellow-500">
              <i class="fas fa-star text-xl"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Available Rooms -->
      <div>
        <div class="bg-white shadow-lg rounded-lg p-6 border-l-4 border-green-500">
          <div class="flex justify-between items-center">
            <div>
              <h3 class="text-2xl font-bold text-gray-800"><?php echo $available_rooms; ?>/<?php echo $total_rooms; ?></h3>
              <p class="text-sm text-gray-500">Available Rooms</p>
            </div>
            <div class="flex items-center justify-center w-12 h-12 rounded-full bg-green-100 text-green-500">
              <i class="fas fa-bed text-xl"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Total Flights -->
      <div>
        <div class="bg-white shadow-lg rounded-lg p-6 border-l-4 border-blue-500">
          <div class="flex justify-between items-center">
            <div>
              <h3 class="text-2xl font-bold text-gray-800"><?php echo $total_flights; ?></h3>
              <p class="text-sm text-gray-500">Total Flights</p>
            </div>
            <div class="flex items-center justify-center w-12 h-12 rounded-full bg-blue-100 text-blue-500">
              <i class="fas fa-plane text-xl"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Flight Seats -->
      <div>
        <div class="bg-white shadow-lg rounded-lg p-6 border-l-4 border-cyan-500">
          <div class="flex justify-between items-center">
            <div>
              <h3 class="text-2xl font-bold text-gray-800"><?php echo $economy_seats + $business_seats + $first_class_seats; ?></h3>
              <p class="text-sm text-gray-500">Total Flight Seats</p>
            </div>
            <div class="flex items-center justify-center w-12 h-12 rounded-full bg-cyan-100 text-cyan-500">
              <i class="fas fa-chair text-xl"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Taxi Routes -->
      <div>
        <div class="bg-white shadow-lg rounded-lg p-6 border-l-4 border-gray-500">
          <div class="flex justify-between items-center">
            <div>
              <h3 class="text-2xl font-bold text-gray-800"><?php echo $taxi_routes; ?></h3>
              <p class="text-sm text-gray-500">Taxi Routes</p>
            </div>
            <div class="flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 text-gray-500">
              <i class="fas fa-taxi text-xl"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Rent-a-Car Routes -->
      <div>
        <div class="bg-white shadow-lg rounded-lg p-6 border-l-4 border-gray-500">
          <div class="flex justify-between items-center">
            <div>
              <h3 class="text-2xl font-bold text-gray-800"><?php echo $rentacar_routes; ?></h3>
              <p class="text-sm text-gray-500">Rent-a-Car Routes</p>
            </div>
            <div class="flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 text-gray-500">
              <i class="fas fa-car text-xl"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Pending Payments -->
      <div>
        <div class="bg-white shadow-lg rounded-lg p-6 border-l-4 border-red-500">
          <div class="flex justify-between items-center">
            <div>
              <h3 class="text-2xl font-bold text-gray-800"><?php echo $pending_payments; ?></h3>
              <p class="text-sm text-gray-500">Pending Payments</p>
            </div>
            <div class="flex items-center justify-center w-12 h-12 rounded-full bg-red-100 text-red-500">
              <i class="fas fa-money-check-alt text-xl"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Completed Payments -->
      <div>
        <div class="bg-white shadow-lg rounded-lg p-6 border-l-4 border-green-500">
          <div class="flex justify-between items-center">
            <div>
              <h3 class="text-2xl font-bold text-gray-800"><?php echo $completed_payments; ?></h3>
              <p class="text-sm text-gray-500">Completed Payments</p>
            </div>
            <div class="flex items-center justify-center w-12 h-12 rounded-full bg-green-100 text-green-500">
              <i class="fas fa-money-check text-xl"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Activity Section -->
    <div class="bg-white shadow-lg rounded-lg p-6">
      <div class="mb-4">
        <h5 class="text-lg font-semibold text-gray-800">Recent Activity</h5>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-left">
          <thead>
            <tr class="border-b">
              <th class="py-3 px-4 text-sm font-semibold text-gray-600">Type</th>
              <th class="py-3 px-4 text-sm font-semibold text-gray-600">Reference/ID</th>
              <th class="py-3 px-4 text-sm font-semibold text-gray-600">User</th>
              <th class="py-3 px-4 text-sm font-semibold text-gray-600">Status</th>
              <th class="py-3 px-4 text-sm font-semibold text-gray-600">Date</th>
            </tr>
          </thead>
          <tbody>
            <?php
            // Fetch recent bookings (limit to 5)
            $stmt = $conn->prepare("
              SELECT 'Flight' AS type, id, passenger_name AS user, booking_status, created_at
              FROM flight_bookings
              UNION
              SELECT 'Hotel' AS type, booking_reference AS id, '' AS user, booking_status, created_at
              FROM hotel_bookings
              UNION
              SELECT 'Package' AS type, booking_reference AS id, '' AS user, booking_status, created_at
              FROM package_bookings
              UNION
              SELECT 'Transportation' AS type, id, full_name AS user, booking_status, created_at
              FROM transportation_bookings
              ORDER BY created_at DESC
              LIMIT 5
            ");
            if (!$stmt) {
              error_log("Query preparation failed for recent activity: " . $conn->error);
            }
            $stmt->execute();
            $recent_activities = $stmt->get_result();
            if ($recent_activities->num_rows === 0) {
              echo "<tr><td colspan='5' class='py-3 px-4 text-sm text-gray-500'>No recent activities found.</td></tr>";
            } else {
              while ($activity = $recent_activities->fetch_assoc()) {
                echo "<tr class='border-b hover:bg-indigo-50'>";
                echo "<td class='py-3 px-4 text-sm text-gray-700'>" . htmlspecialchars($activity['type']) . "</td>";
                echo "<td class='py-3 px-4 text-sm text-gray-700'>" . htmlspecialchars($activity['id']) . "</td>";
                echo "<td class='py-3 px-4 text-sm text-gray-700'>" . htmlspecialchars($activity['user'] ?: 'N/A') . "</td>";
                echo "<td class='py-3 px-4 text-sm text-gray-700'>" . htmlspecialchars($activity['booking_status']) . "</td>";
                echo "<td class='py-3 px-4 text-sm text-gray-700'>" . date('Y-m-d H:i', strtotime($activity['created_at'])) . "</td>";
                echo "</tr>";
              }
            }
            $stmt->close();
            ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- jQuery -->
  <script src="assets/js/jquery-3.7.1.min.js"></script>
  <!-- Chart.js (retained for potential use in assets/js/index.js) -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@3.5.1/dist/chart.min.js"></script>
  <!-- Custom JavaScript -->
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      // Sidebar elements (assumed from sidebar.php)
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
        console.warn('One or more sidebar elements are missing. Ensure sidebar.php includes #sidebar, #sidebar-overlay, #sidebar-close.');
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
    });
  </script>
</body>

</html>