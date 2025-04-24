<?php
require_once '../config/db.php';
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

// Use MySQLi (matching index.php)
$user_id = $_SESSION['user_id'];

// Fetch user details
$user_query = $conn->prepare("SELECT full_name, email, phone, dob, profile_image FROM users WHERE id = ?");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user = $user_query->get_result()->fetch_assoc();
$user_query->close();

// Fetch bookings
$flight_query = $conn->prepare("
    SELECT fb.id, fb.flight_id, fb.cabin_class, fb.total_price, fb.booking_status, fb.created_at, 
           f.airline_name, f.flight_number, f.departure_city, f.arrival_city, f.departure_date
    FROM flight_bookings fb
    JOIN flights f ON fb.flight_id = f.id
    WHERE fb.user_id = ?
    ORDER BY fb.created_at DESC
");
$flight_query->bind_param("i", $user_id);
$flight_query->execute();
$flights = $flight_query->get_result()->fetch_all(MYSQLI_ASSOC);
$flight_query->close();

$hotel_query = $conn->prepare("
    SELECT hb.id, hb.hotel_id, hb.check_in_date, hb.check_out_date, hb.total_price, hb.booking_status, hb.booking_reference,
           h.hotel_name, h.location
    FROM hotel_bookings hb
    JOIN hotels h ON hb.hotel_id = h.id
    WHERE hb.user_id = ?
    ORDER BY hb.created_at DESC
");
$hotel_query->bind_param("i", $user_id);
$hotel_query->execute();
$hotels = $hotel_query->get_result()->fetch_all(MYSQLI_ASSOC);
$hotel_query->close();

$package_query = $conn->prepare("
    SELECT pb.id, pb.package_id, pb.travel_date, pb.num_travelers, pb.total_price, pb.booking_status, pb.booking_reference,
           up.title, up.package_type
    FROM package_bookings pb
    JOIN umrah_packages up ON pb.package_id = up.id
    WHERE pb.user_id = ?
    ORDER BY pb.created_at DESC
");
$package_query->bind_param("i", $user_id);
$package_query->execute();
$packages = $package_query->get_result()->fetch_all(MYSQLI_ASSOC);
$package_query->close();

$transport_query = $conn->prepare("
    SELECT tb.id, tb.transport_type, tb.route_name, tb.vehicle_type, tb.price, tb.booking_status, tb.pickup_date, tb.pickup_time
    FROM transportation_bookings tb
    WHERE tb.user_id = ?
    ORDER BY tb.created_at DESC
");
$transport_query->bind_param("i", $user_id);
$transport_query->execute();
$transports = $transport_query->get_result()->fetch_all(MYSQLI_ASSOC);
$transport_query->close();

// Handle booking cancellation
if (isset($_POST['cancel_booking'])) {
  $booking_type = $_POST['booking_type'];
  $booking_id = $_POST['booking_id'];

  $table_map = [
    'flight' => 'flight_bookings',
    'hotel' => 'hotel_bookings',
    'package' => 'package_bookings',
    'transport' => 'transportation_bookings'
  ];

  if (isset($table_map[$booking_type])) {
    $stmt = $conn->prepare("UPDATE {$table_map[$booking_type]} SET booking_status = 'cancelled' WHERE id = ? AND user_id = ? AND booking_status = 'pending'");
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: index.php");
    exit();
  }
}

// Handle booking deletion
if (isset($_POST['delete_booking'])) {
  $booking_type = $_POST['booking_type'];
  $booking_id = $_POST['booking_id'];

  $table_map = [
    'flight' => 'flight_bookings',
    'hotel' => 'hotel_bookings',
    'package' => 'package_bookings',
    'transport' => 'transportation_bookings'
  ];

  if (isset($table_map[$booking_type])) {
    $stmt = $conn->prepare("DELETE FROM {$table_map[$booking_type]} WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: index.php");
    exit();
  }
}

// Handle profile update
if (isset($_POST['update_profile'])) {
  $full_name = filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_STRING);
  $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
  $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
  $dob = filter_input(INPUT_POST, 'dob', FILTER_SANITIZE_STRING);

  // Handle profile image upload
  $profile_image = $user['profile_image'];
  if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
    $upload_dir = 'assets/uploads/profile_images/';
    if (!is_dir($upload_dir)) {
      mkdir($upload_dir, 0755, true);
    }
    $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
    $filename = 'profile_' . time() . '.' . $ext;
    $target = $upload_dir . $filename;
    if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target)) {
      $profile_image = $target;
    }
  }

  $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, dob = ?, profile_image = ? WHERE id = ?");
  $stmt->bind_param("sssssi", $full_name, $email, $phone, $dob, $profile_image, $user_id);
  $stmt->execute();
  $stmt->close();
  header("Location: index.php");
  exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Bookings - UmrahFlights</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    .tab-content {
      display: none;
    }

    .tab-content.active {
      display: block;
    }

    .booking-card {
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .booking-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
    }

    .status-badge {
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 600;
      text-transform: capitalize;
    }

    .status-pending {
      background-color: #fef3c7;
      color: #d97706;
    }

    .status-confirmed {
      background-color: #d1fae5;
      color: #059669;
    }

    .status-cancelled {
      background-color: #fee2e2;
      color: #dc2626;
    }

    .status-completed {
      background-color: #e5e7eb;
      color: #4b5563;
    }

    .filter-container {
      background-color: #ffffff;
      padding: 20px;
      border-radius: 12px;
      box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
      margin-bottom: 30px;
    }

    .nav-tabs .nav-link {
      transition: all 0.3s ease;
    }

    .nav-tabs .nav-link:hover {
      background-color: #f1f5f9;
    }

    .nav-tabs .nav-link.active {
      background-color: #06b6d4;
      color: white;
      border-radius: 8px 8px 0 0;
    }

    @media (max-width: 768px) {
      .nav-tabs {
        flex-direction: column;
      }

      .nav-tabs .nav-link {
        width: 100%;
        text-align: center;
        border-radius: 8px;
        margin-bottom: 8px;
      }
    }
  </style>
</head>

<body class="bg-gray-100">
  <!-- Navbar -->
  <nav class="bg-gradient-to-r from-cyan-600 to-teal-500 p-4 shadow-lg">
    <div class="container mx-auto flex justify-between items-center">
      <!-- Left side: Logo and Title -->
      <div class="flex items-center space-x-4">
        <!-- <img src="https://via.placeholder.com/40" alt="Logo" class="h-12 w-12 rounded-full"> -->
        <span class="text-white text-3xl font-extrabold tracking-tight">Ummrah</span>
      </div>
      <!-- Right side: Go Back Button -->
      <div>
        <a href="../index.php" class="bg-white text-cyan-600 px-5 py-2 rounded-full hover:bg-gray-100 transition duration-300 font-semibold shadow-md">Go Back</a>
      </div>
    </div>
  </nav>

  <!-- Main Content -->
  <section class="container mx-auto px-4 py-12">
    <div class="text-center mb-12">
      <div class="text-cyan-500 font-semibold mb-3 tracking-wider">My Bookings</div>
      <h2 class="text-4xl font-extrabold text-gray-900 mb-4">Manage Your Travel Plans</h2>
      <p class="text-gray-600 max-w-2xl mx-auto">View, cancel, or delete your bookings with ease. Keep your travel plans organized in one place.</p>
    </div>

    <!-- Filter Section -->
    <div class="filter-container">
      <div class="flex flex-col md:flex-row gap-4">
        <select id="statusFilter" class="form-select border border-gray-200 rounded-xl p-3 bg-white focus:ring-2 focus:ring-cyan-500">
          <option value="">All Statuses</option>
          <option value="pending">Pending</option>
          <option value="confirmed">Confirmed</option>
          <option value="cancelled">Cancelled</option>
          <option value="completed">Completed</option>
        </select>
        <input type="text" id="searchInput" class="form-control border border-gray-200 rounded-xl p-3 bg-white focus:ring-2 focus:ring-cyan-500" placeholder="Search by booking reference...">
      </div>
    </div>

    <!-- Tabs Navigation -->
    <ul class="nav nav-tabs mb-10 flex flex-wrap border-b-0" id="bookingTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active px-5 py-3 text-gray-700 font-semibold" data-tab="flights">Flights</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link px-5 py-3 text-gray-700 font-semibold" data-tab="hotels">Hotels</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link px-5 py-3 text-gray-700 font-semibold" data-tab="packages">Packages</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link px-5 py-3 text-gray-700 font-semibold" data-tab="transport">Transportation</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link px-5 py-3 text-gray-700 font-semibold" data-tab="profile">Profile</button>
      </li>
    </ul>

    <!-- Tabs Content -->
    <div id="flights" class="tab-content active">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($flights)): ?>
          <div class="col-span-full text-center py-12">
            <p class="text-gray-500 text-lg">No flight bookings found.</p>
          </div>
        <?php else: ?>
          <?php foreach ($flights as $flight): ?>
            <div class="booking-card bg-white p-6" data-status="<?php echo htmlspecialchars($flight['booking_status']); ?>">
              <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($flight['airline_name']); ?> - <?php echo htmlspecialchars($flight['flight_number']); ?></h3>
                <span class="status-badge status-<?php echo htmlspecialchars($flight['booking_status']); ?>">
                  <?php echo ucfirst(htmlspecialchars($flight['booking_status'])); ?>
                </span>
              </div>
              <p class="text-gray-600 mb-2 flex items-center">
                <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <?php echo htmlspecialchars($flight['departure_city']); ?> to <?php echo htmlspecialchars($flight['arrival_city']); ?>
              </p>
              <p class="text-gray-600 mb-2 flex items-center">
                <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <?php echo date('d M Y', strtotime($flight['departure_date'])); ?>
              </p>
              <p class="text-gray-600 mb-2 flex items-center">
                <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                </svg>
                <?php echo ucfirst(htmlspecialchars($flight['cabin_class'])); ?>
              </p>
              <p class="text-cyan-600 font-bold text-lg mb-4">Rs<?php echo number_format($flight['total_price'], 2); ?></p>
              <div class="flex gap-3">
                <a href="flight-details.php?id=<?php echo $flight['id']; ?>" class="bg-cyan-500 hover:bg-cyan-600 text-white py-2 px-4 rounded-xl font-medium">ViewDetails</a>
                <?php if ($flight['booking_status'] == 'pending'): ?>
                  <form method="POST" class="cancel-form">
                    <input type="hidden" name="booking_type" value="flight">
                    <input type="hidden" name="booking_id" value="<?php echo $flight['id']; ?>">
                    <button type="submit" name="cancel_booking" class="bg-yellow-500 hover:bg-yellow-600 text-white py-2 px-4 rounded-xl font-medium">Cancel</button>
                  </form>
                <?php endif; ?>
                <form method="POST" class="delete-form">
                  <input type="hidden" name="booking_type" value="flight">
                  <input type="hidden" name="booking_id" value="<?php echo $flight['id']; ?>">
                  <button type="submit" name="delete_booking" class="bg-red-500 hover:bg-red-600 text-white py-2 px-4 rounded-xl font-medium">Delete</button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <div id="hotels" class="tab-content">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($hotels)): ?>
          <div class="col-span-full text-center py-12">
            <p class="text-gray-500 text-lg">No hotel bookings found.</p>
          </div>
        <?php else: ?>
          <?php foreach ($hotels as $hotel): ?>
            <div class="booking-card bg-white p-6" data-status="<?php echo htmlspecialchars($hotel['booking_status']); ?>" data-reference="<?php echo htmlspecialchars($hotel['booking_reference']); ?>">
              <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($hotel['hotel_name']); ?></h3>
                <span class="status-badge status-<?php echo htmlspecialchars($hotel['booking_status']); ?>">
                  <?php echo ucfirst(htmlspecialchars($hotel['booking_status'])); ?>
                </span>
              </div>
              <p class="text-gray-600 mb-2 flex items-center">
                <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                </svg>
                <?php echo htmlspecialchars($hotel['location']); ?>
              </p>
              <p class="text-gray-600 mb-2 flex items-center">
                <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                Check-in: <?php echo date('d M Y', strtotime($hotel['check_in_date'])); ?>
              </p>
              <p class="text-gray-600 mb-2 flex items-center">
                <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                Check-out: <?php echo date('d M Y', strtotime($hotel['check_out_date'])); ?>
              </p>
              <p class="text-cyan-600 font-bold text-lg mb-4">Rs<?php echo number_format($hotel['total_price'], 2); ?></p>
              <div class="flex gap-3">
                <a href="hotel-details.php?id=<?php echo $hotel['id']; ?>" class="bg-cyan-500 hover:bg-cyan-600 text-white py-2 px-4 rounded-xl font-medium">ViewDetails</a>
                <?php if ($hotel['booking_status'] == 'pending'): ?>
                  <form method="POST" class="cancel-form">
                    <input type="hidden" name="booking_type" value="hotel">
                    <input type="hidden" name="booking_id" value="<?php echo $hotel['id']; ?>">
                    <button type="submit" name="cancel_booking" class="bg-yellow-500 hover:bg-yellow-600 text-white py-2 px-4 rounded-xl font-medium">Cancel</button>
                  </form>
                <?php endif; ?>
                <form method="POST" class="delete-form">
                  <input type="hidden" name="booking_type" value="hotel">
                  <input type="hidden" name="booking_id" value="<?php echo $hotel['id']; ?>">
                  <button type="submit" name="delete_booking" class="bg-red-500 hover:bg-red-600 text-white py-2 px-4 rounded-xl font-medium">Delete</button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <div id="packages" class="tab-content">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($packages)): ?>
          <div class="col-span-full text-center py-12">
            <p class="text-gray-500 text-lg">No package bookings found.</p>
          </div>
        <?php else: ?>
          <?php foreach ($packages as $package): ?>
            <div class="booking-card bg-white p-6" data-status="<?php echo htmlspecialchars($package['booking_status']); ?>" data-reference="<?php echo htmlspecialchars($package['booking_reference']); ?>">
              <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($package['title']); ?></h3>
                <span class="status-badge status-<?php echo htmlspecialchars($package['booking_status']); ?>">
                  <?php echo ucfirst(htmlspecialchars($package['booking_status'])); ?>
                </span>
              </div>
              <p class="text-gray-600 mb-2 flex items-center">
                <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <?php echo ucfirst(htmlspecialchars($package['package_type'])); ?> Package
              </p>
              <p class="text-gray-600 mb-2 flex items-center">
                <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                Travel Date: <?php echo date('d M Y', strtotime($package['travel_date'])); ?>
              </p>
              <p class="text-gray-600 mb-2 flex items-center">
                <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
                <?php echo htmlspecialchars($package['num_travelers']); ?> Traveler(s)
              </p>
              <p class="text-cyan-600 font-bold text-lg mb-4">Rs<?php echo number_format($package['total_price'], 2); ?></p>
              <div class="flex gap-3">
                <a href="package-details.php?id=<?php echo $package['id']; ?>" class="bg-cyan-500 hover:bg-cyan-600 text-white py-2 px-4 rounded-xl font-medium">ViewDetails</a>
                <?php if ($package['booking_status'] == 'pending'): ?>
                  <form method="POST" class="cancel-form">
                    <input type="hidden" name="booking_type" value="package">
                    <input type="hidden" name="booking_id" value="<?php echo $package['id']; ?>">
                    <button type="submit" name="cancel_booking" class="bg-yellow-500 hover:bg-yellow-600 text-white py-2 px-4 rounded-xl font-medium">Cancel</button>
                  </form>
                <?php endif; ?>
                <form method="POST" class="delete-form">
                  <input type="hidden" name="booking_type" value="package">
                  <input type="hidden" name="booking_id" value="<?php echo $package['id']; ?>">
                  <button type="submit" name="delete_booking" class="bg-red-500 hover:bg-red-600 text-white py-2 px-4 rounded-xl font-medium">Delete</button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <div id="transport" class="tab-content">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($transports)): ?>
          <div class="col-span-full text-center py-12">
            <p class="text-gray-500 text-lg">No transportation bookings found.</p>
          </div>
        <?php else: ?>
          <?php foreach ($transports as $transport): ?>
            <div class="booking-card bg-white p-6" data-status="<?php echo htmlspecialchars($transport['booking_status']); ?>">
              <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($transport['route_name']); ?></h3>
                <span class="status-badge status-<?php echo htmlspecialchars($transport['booking_status']); ?>">
                  <?php echo ucfirst(htmlspecialchars($transport['booking_status'])); ?>
                </span>
              </div>
              <p class="text-gray-600 mb-2 flex items-center">
                <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10"></path>
                </svg>
                <?php echo ucfirst(htmlspecialchars($transport['transport_type'])); ?> - <?php echo htmlspecialchars($transport['vehicle_type']); ?>
              </p>
              <p class="text-gray-600 mb-2 flex items-center">
                <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 

 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                Pickup: <?php echo date('d M Y, H:i', strtotime($transport['pickup_date'] . ' ' . $transport['pickup_time'])); ?>
              </p>
              <p class="text-cyan-600 font-bold text-lg mb-4">Rs<?php echo number_format($transport['price'], 2); ?></p>
              <div class="flex gap-3">
                <a href="transport-details.php?id=<?php echo $transport['id']; ?>" class="bg-cyan-500 hover:bg-cyan-600 text-white py-2 px-4 rounded-xl font-medium">ViewDetails</a>
                <?php if ($transport['booking_status'] == 'pending'): ?>
                  <form method="POST" class="cancel-form">
                    <input type="hidden" name="booking_type" value="transport">
                    <input type="hidden" name="booking_id" value="<?php echo $transport['id']; ?>">
                    <button type="submit" name="cancel_booking" class="bg-yellow-500 hover:bg-yellow-600 text-white py-2 px-4 rounded-xl font-medium">Cancel</button>
                  </form>
                <?php endif; ?>
                <form method="POST" class="delete-form">
                  <input type="hidden" name="booking_type" value="transport">
                  <input type="hidden" name="booking_id" value="<?php echo $transport['id']; ?>">
                  <button type="submit" name="delete_booking" class="bg-red-500 hover:bg-red-600 text-white py-2 px-4 rounded-xl font-medium">Delete</button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <div id="profile" class="tab-content">
      <div class="bg-white p-8 rounded-2xl shadow-lg max-w-md mx-auto">
        <h3 class="text-2xl font-bold text-gray-800 mb-6">My Profile</h3>
        <form method="POST" enctype="multipart/form-data">
          <div class="mb-5">
            <label class="block text-gray-700 font-medium mb-2" for="full_name">Full Name</label>
            <input type="text" name="full_name" id="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" class="form-control border border-gray-200 rounded-xl p-3 w-full focus:ring-2 focus:ring-cyan-500" required>
          </div>
          <div class="mb-5">
            <label class="block text-gray-700 font-medium mb-2" for="email">Email</label>
            <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="form-control border border-gray-200 rounded-xl p-3 w-full focus:ring-2 focus:ring-cyan-500" required>
          </div>
          <div class="mb-5">
            <label class="block text-gray-700 font-medium mb-2" for="phone">Phone</label>
            <input type="text" name="phone" id="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" class="form-control border border-gray-200 rounded-xl p-3 w-full focus:ring-2 focus:ring-cyan-500" required>
          </div>
          <div class="mb-5">
            <label class="block text-gray-700 font-medium mb-2" for="dob">Date of Birth</label>
            <input type="date" name="dob" id="dob" value="<?php echo htmlspecialchars($user['dob']); ?>" class="form-control border border-gray-200 rounded-xl p-3 w-full focus:ring-2 focus:ring-cyan-500" required>
          </div>
          <div class="mb-5">
            <label class="block text-gray-700 font-medium mb-2" for="profile_image">Profile Image</label>
            <input type="file" name="profile_image" id="profile_image" accept="image/*" class="form-control border border-gray-200 rounded-xl p-3 w-full">
            <?php if ($user['profile_image']): ?>
              <img src="../<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile Image" class="mt-3 w-28 h-28 object-cover rounded-full border-2 border-cyan-500">
            <?php endif; ?>
          </div>
          <button type="submit" name="update_profile" class="bg-cyan-500 hover:bg-cyan-600 text-white py-3 px-6 rounded-xl font-semibold w-full">Update Profile</button>
        </form>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <?php include '../includes/footer.php'; ?>
  <?php include '../includes/js-links.php'; ?>

  <script>
    // Tab switching
    const tabs = document.querySelectorAll('#bookingTabs .nav-link');
    const tabContents = document.querySelectorAll('.tab-content');

    tabs.forEach(tab => {
      tab.addEventListener('click', () => {
        tabs.forEach(t => t.classList.remove('active'));
        tabContents.forEach(content => content.classList.remove('active'));

        tab.classList.add('active');
        document.getElementById(tab.dataset.tab).classList.add('active');
      });
    });

    // Cancel booking confirmation
    document.querySelectorAll('.cancel-form').forEach(form => {
      form.addEventListener('submit', (e) => {
        e.preventDefault();
        Swal.fire({
          title: 'Are you sure?',
          text: 'This booking will be cancelled.',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#f59e0b',
          cancelButtonColor: '#6b7280',
          confirmButtonText: 'Yes, cancel it!'
        }).then((result) => {
          if (result.isConfirmed) {
            form.submit();
          }
        });
      });
    });

    // Delete booking confirmation
    document.querySelectorAll('.delete-form').forEach(form => {
      form.addEventListener('submit', (e) => {
        e.preventDefault();
        Swal.fire({
          title: 'Are you sure?',
          text: 'This booking will be permanently deleted.',
          icon: 'error',
          showCancelButton: true,
          confirmButtonColor: '#dc2626',
          cancelButtonColor: '#6b7280',
          confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
          if (result.isConfirmed) {
            form.submit();
          }
        });
      });
    });

    // Filter and search
    const statusFilter = document.getElementById('statusFilter');
    const searchInput = document.getElementById('searchInput');

    function filterBookings() {
      const status = statusFilter.value.toLowerCase();
      const search = searchInput.value.toLowerCase();

      document.querySelectorAll('.booking-card').forEach(card => {
        const cardStatus = card.dataset.status.toLowerCase();
        const cardReference = card.dataset.reference ? card.dataset.reference.toLowerCase() : '';

        const statusMatch = !status || cardStatus === status;
        const searchMatch = !search || cardReference.includes(search);

        card.style.display = statusMatch && searchMatch ? 'block' : 'none';
      });
    }

    statusFilter.addEventListener('change', filterBookings);
    searchInput.addEventListener('input', filterBookings);
  </script>
</body>

</html>