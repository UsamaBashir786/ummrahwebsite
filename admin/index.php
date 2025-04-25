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

// 4. Active Packages (by type)
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) AS total_packages,
        SUM(CASE WHEN package_type = 'single' THEN 1 ELSE 0 END) AS single_packages,
        SUM(CASE WHEN package_type = 'group' THEN 1 ELSE 0 END) AS group_packages,
        SUM(CASE WHEN package_type = 'vip' THEN 1 ELSE 0 END) AS vip_packages
    FROM umrah_packages
");
if (!$stmt) {
  error_log("Query preparation failed for active packages: " . $conn->error);
}
$stmt->execute();
$packages = $stmt->get_result()->fetch_assoc();
$total_packages = $packages['total_packages'] ?? 0;
$single_packages = $packages['single_packages'] ?? 0;
$group_packages = $packages['group_packages'] ?? 0;
$vip_packages = $packages['vip_packages'] ?? 0;
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
$avg_hotel_rating = round((float)$hotels['avg_hotel_rating'], 1); // Line 116
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
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="assets/css/index.css">
</head>

<body>
  <?php include 'includes/sidebar.php'; ?>

  <!-- Main Content -->
  <div class="mt-10 main-content col-md-12">
    <!-- Top Navbar -->
    <nav class="p-5 navbar navbar-expand-lg top-navbar mb-4">
      <div class="container-fluid">
        <button id="sidebarToggle" class="btn d-lg-none">
          <i class="fas fa-bars"></i>
        </button>
        <h4 class="mb-0 ms-2">Dashboard</h4>

        <div class="d-flex align-items-center">
          <!-- <div class="position-relative me-3">
            <button class="btn position-relative" id="notificationBtn">
              <i class="fas fa-bell fs-5"></i>
              <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                <?php echo $pending_bookings; ?>
              </span>
            </button>
          </div> -->

          <div class="dropdown">
            <button class="btn dropdown-toggle d-flex align-items-center" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
              <div class="rounded-circle overflow-hidden me-2" style="width: 32px; height: 32px;">
                <!-- <img src="../assets/img/admin.jpg" alt="Admin User" class="img-fluid"> -->
              </div>
              <span class="d-none d-md-inline">Admin User</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
              <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
            </ul>
          </div>
        </div>
      </div>
    </nav>

    <!-- Stats Cards -->
    <div class="row g-4 mb-4 p-5">


      <!-- Total Revenue -->
      <div class="col-12 col-md-6 col-lg-12">
        <div class="card stat-card border-start border-success border-4">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h3 class="fw-bold mb-1">Rs.<?php echo number_format($total_revenue, 2); ?></h3>
                <div class="text-muted">Total Revenue</div>
              </div>
              <div class="stat-card-icon bg-success bg-opacity-10 text-success">
                <i class="fas fa-wallet"></i>
              </div>
            </div>
          </div>
        </div>
      </div>


      <!-- Average Package Booking Value -->
      <div class="col-12 col-md-6 col-lg-12">
        <div class="card stat-card border-start border-warning border-4">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h3 class="fw-bold mb-1">Rs.<?php echo number_format($avg_package_booking, 2); ?></h3>
                <div class="text-muted">Avg Package Booking</div>
              </div>
              <div class="stat-card-icon bg-warning bg-opacity-10 text-warning">
                <i class="fas fa-box-open"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Average Flight Booking Value -->
      <div class="col-12 col-md-6 col-lg-12">
        <div class="card stat-card border-start border-primary border-4">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h3 class="fw-bold mb-1">Rs.<?php echo number_format($avg_flight_booking, 2); ?></h3>
                <div class="text-muted">Avg Flight Booking</div>
              </div>
              <div class="stat-card-icon bg-primary bg-opacity-10 text-primary">
                <i class="fas fa-plane-departure"></i>
              </div>
            </div>
          </div>
        </div>
      </div>


      <!-- Total Bookings -->
      <div class="col-12 col-md-6 col-lg-3">
        <div class="card stat-card border-start border-primary border-4">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h3 class="fw-bold mb-1"><?php echo $total_bookings; ?></h3>
                <div class="text-muted">Total Bookings</div>
              </div>
              <div class="stat-card-icon bg-primary bg-opacity-10 text-primary">
                <i class="fas fa-calendar-check"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Total Users -->
      <div class="col-12 col-md-6 col-lg-3">
        <div class="card stat-card border-start border-info border-4">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h3 class="fw-bold mb-1"><?php echo $total_users; ?></h3>
                <div class="text-muted">Total Users</div>
              </div>
              <div class="stat-card-icon bg-info bg-opacity-10 text-info">
                <i class="fas fa-users"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Active Packages -->
      <div class="col-12 col-md-6 col-lg-3">
        <div class="card stat-card border-start border-warning border-4">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h3 class="fw-bold mb-1"><?php echo $total_packages; ?></h3>
                <div class="text-muted">Active Packages</div>
              </div>
              <div class="stat-card-icon bg-warning bg-opacity-10 text-warning">
                <i class="fas fa-box"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Pending Bookings -->
      <div class="col-12 col-md-6 col-lg-3">
        <div class="card stat-card border-start border-danger border-4">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h3 class="fw-bold mb-1"><?php echo $pending_bookings; ?></h3>
                <div class="text-muted">Pending Bookings</div>
              </div>
              <div class="stat-card-icon bg-danger bg-opacity-10 text-danger">
                <i class="fas fa-hourglass-half"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Confirmed Bookings -->
      <div class="col-12 col-md-6 col-lg-3">
        <div class="card stat-card border-start border-success border-4">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h3 class="fw-bold mb-1"><?php echo $confirmed_bookings; ?></h3>
                <div class="text-muted">Confirmed Bookings</div>
              </div>
              <div class="stat-card-icon bg-success bg-opacity-10 text-success">
                <i class="fas fa-check-circle"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Cancelled Bookings -->
      <div class="col-12 col-md-6 col-lg-3">
        <div class="card stat-card border-start border-secondary border-4">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h3 class="fw-bold mb-1"><?php echo $cancelled_bookings; ?></h3>
                <div class="text-muted">Cancelled Bookings</div>
              </div>
              <div class="stat-card-icon bg-secondary bg-opacity-10 text-secondary">
                <i class="fas fa-times-circle"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Total Hotels -->
      <div class="col-12 col-md-6 col-lg-3">
        <div class="card stat-card border-start border-primary border-4">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h3 class="fw-bold mb-1"><?php echo $total_hotels; ?></h3>
                <div class="text-muted">Total Hotels</div>
              </div>
              <div class="stat-card-icon bg-primary bg-opacity-10 text-primary">
                <i class="fas fa-hotel"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Makkah Hotels -->
      <div class="col-12 col-md-6 col-lg-3">
        <div class="card stat-card border-start border-info border-4">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h3 class="fw-bold mb-1"><?php echo $makkah_hotels; ?></h3>
                <div class="text-muted">Makkah Hotels</div>
              </div>
              <div class="stat-card-icon bg-info bg-opacity-10 text-info">
                <i class="fas fa-mosque"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Madinah Hotels -->
      <div class="col-12 col-md-6 col-lg-3">
        <div class="card stat-card border-start border-info border-4">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h3 class="fw-bold mb-1"><?php echo $madinah_hotels; ?></h3>
                <div class="text-muted">Madinah Hotels</div>
              </div>
              <div class="stat-card-icon bg-info bg-opacity-10 text-info">
                <i class="fas fa-mosque"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Average Hotel Rating -->
      <div class="col-12 col-md-6 col-lg-3">
        <div class="card stat-card border-start border-warning border-4">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h3 class="fw-bold mb-1"><?php echo $avg_hotel_rating; ?>/5</h3>
                <div class="text-muted">Avg Hotel Rating</div>
              </div>
              <div class="stat-card-icon bg-warning bg-opacity-10 text-warning">
                <i class="fas fa-star"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Available Rooms -->
      <div class="col-12 col-md-6 col-lg-3">
        <div class="card stat-card border-start border-success border-4">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h3 class="fw-bold mb-1"><?php echo $available_rooms; ?>/<?php echo $total_rooms; ?></h3>
                <div class="text-muted">Available Rooms</div>
              </div>
              <div class="stat-card-icon bg-success bg-opacity-10 text-success">
                <i class="fas fa-bed"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Total Flights -->
      <div class="col-12 col-md-6 col-lg-3">
        <div class="card stat-card border-start border-primary border-4">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h3 class="fw-bold mb-1"><?php echo $total_flights; ?></h3>
                <div class="text-muted">Total Flights</div>
              </div>
              <div class="stat-card-icon bg-primary bg-opacity-10 text-primary">
                <i class="fas fa-plane"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Flight Seats -->
      <div class="col-12 col-md-6 col-lg-3">
        <div class="card stat-card border-start border-info border-4">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h3 class="fw-bold mb-1"><?php echo $economy_seats + $business_seats + $first_class_seats; ?></h3>
                <div class="text-muted">Total Flight Seats</div>
              </div>
              <div class="stat-card-icon bg-info bg-opacity-10 text-info">
                <i class="fas fa-chair"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Taxi Routes -->
      <div class="col-12 col-md-6 col-lg-3">
        <div class="card stat-card border-start border-secondary border-4">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h3 class="fw-bold mb-1"><?php echo $taxi_routes; ?></h3>
                <div class="text-muted">Taxi Routes</div>
              </div>
              <div class="stat-card-icon bg-secondary bg-opacity-10 text-secondary">
                <i class="fas fa-taxi"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Rent-a-Car Routes -->
      <div class="col-12 col-md-6 col-lg-3">
        <div class="card stat-card border-start border-secondary border-4">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h3 class="fw-bold mb-1"><?php echo $rentacar_routes; ?></h3>
                <div class="text-muted">Rent-a-Car Routes</div>
              </div>
              <div class="stat-card-icon bg-secondary bg-opacity-10 text-secondary">
                <i class="fas fa-car"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Pending Payments -->
      <div class="col-12 col-md-6 col-lg-3">
        <div class="card stat-card border-start border-danger border-4">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h3 class="fw-bold mb-1"><?php echo $pending_payments; ?></h3>
                <div class="text-muted">Pending Payments</div>
              </div>
              <div class="stat-card-icon bg-danger bg-opacity-10 text-danger">
                <i class="fas fa-money-check-alt"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Completed Payments -->
      <div class="col-12 col-md-6 col-lg-3">
        <div class="card stat-card border-start border-success border-4">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h3 class="fw-bold mb-1"><?php echo $completed_payments; ?></h3>
                <div class="text-muted">Completed Payments</div>
              </div>
              <div class="stat-card-icon bg-success bg-opacity-10 text-success">
                <i class="fas fa-money-check"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- Recent Activity Section -->
    <div class="card mt-4 p-5">
      <div class="card-header">
        <h5 class="mb-0">Recent Activity</h5>
      </div>
      <div class="card-body">
        <table class="table table-striped">
          <thead>
            <tr>
              <th>Type</th>
              <th>Reference/ID</th>
              <th>User</th>
              <th>Status</th>
              <th>Date</th>
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
              echo "<tr><td colspan='5'>No recent activities found.</td></tr>";
            } else {
              while ($activity = $recent_activities->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($activity['type']) . "</td>";
                echo "<td>" . htmlspecialchars($activity['id']) . "</td>";
                echo "<td>" . htmlspecialchars($activity['user'] ?: 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($activity['booking_status']) . "</td>";
                echo "<td>" . date('Y-m-d H:i', strtotime($activity['created_at'])) . "</td>";
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

  <!-- Bootstrap 5 JS Bundle with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@3.5.1/dist/chart.min.js"></script>
  <!-- Custom JavaScript -->
  <script src="assets/js/index.js"></script>
</body>

</html>