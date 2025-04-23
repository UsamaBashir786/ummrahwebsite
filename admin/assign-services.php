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

// Initialize variables
$bookings = [];
$filters = [
  'status' => $_GET['status'] ?? '',
  'package_id' => $_GET['package_id'] ?? '',
  'search' => $_GET['search'] ?? '',
  'date_from' => $_GET['date_from'] ?? '',
  'date_to' => $_GET['date_to'] ?? '',
];
$message = '';
$message_type = '';

// Generate unique booking reference for hotel bookings
function generateBookingReference() {
  return 'HB' . strtoupper(substr(uniqid(), 0, 11));
}

// Handle assignment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_services'])) {
  $booking_id = (int)$_POST['booking_id'];
  $user_id = (int)$_POST['user_id'];
  $full_name = $_POST['full_name'];
  $email = $_POST['email'];
  $phone = $_POST['phone'] ?? '';

  // Assign Hotel
  if (!empty($_POST['hotel_id']) && !empty($_POST['room_id']) && !empty($_POST['check_in_date']) && !empty($_POST['check_out_date'])) {
    $hotel_id = (int)$_POST['hotel_id'];
    $room_id = $_POST['room_id'];
    $check_in_date = $_POST['check_in_date'];
    $check_out_date = $_POST['check_out_date'];
    $total_price = (float)$_POST['hotel_price'];
    $booking_reference = generateBookingReference();
    $special_requests = $_POST['special_requests'] ?? '';

    $stmt = $conn->prepare("INSERT INTO hotel_bookings (user_id, hotel_id, room_id, check_in_date, check_out_date, total_price, booking_status, payment_status, booking_reference, special_requests) VALUES (?, ?, ?, ?, ?, ?, 'pending', 'unpaid', ?, ?)");
    $stmt->bind_param("iisssdss", $user_id, $hotel_id, $room_id, $check_in_date, $check_out_date, $total_price, $booking_reference, $special_requests);
    if (!$stmt->execute()) {
      error_log("Hotel assignment failed: " . $stmt->error);
      $message = "Error assigning hotel: " . $stmt->error;
      $message_type = "error";
    } else {
      // Update room status
      $stmt = $conn->prepare("UPDATE hotel_rooms SET status = 'booked' WHERE hotel_id = ? AND room_id = ?");
      $stmt->bind_param("is", $hotel_id, $room_id);
      $stmt->execute();
      $stmt->close();
    }
    $stmt->close();
  }

  // Assign Transportation
  if (!empty($_POST['transport_type']) && !empty($_POST['route_id']) && !empty($_POST['pickup_date']) && !empty($_POST['pickup_time']) && !empty($_POST['pickup_location'])) {
    $transport_type = $_POST['transport_type'];
    $route_id = (int)$_POST['route_id'];
    $route_name = $_POST['route_name'];
    $vehicle_type = $_POST['vehicle_type'];
    $pickup_date = $_POST['pickup_date'];
    $pickup_time = $_POST['pickup_time'];
    $pickup_location = $_POST['pickup_location'];
    $price = (float)$_POST['transport_price'];
    $additional_notes = $_POST['additional_notes'] ?? '';

    $stmt = $conn->prepare("INSERT INTO transportation_bookings (user_id, transport_type, route_id, route_name, vehicle_type, price, full_name, email, phone, pickup_date, pickup_time, pickup_location, additional_notes, booking_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param("isisssdssssss", $user_id, $transport_type, $route_id, $route_name, $vehicle_type, $price, $full_name, $email, $phone, $pickup_date, $pickup_time, $pickup_location, $additional_notes);
    if (!$stmt->execute()) {
      error_log("Transportation assignment failed: " . $stmt->error);
      $message = "Error assigning transportation: " . $stmt->error;
      $message_type = "error";
    }
    $stmt->close();
  }

  // Assign Flight
  if (!empty($_POST['flight_id']) && !empty($_POST['departure_date']) && !empty($_POST['cabin_class'])) {
    $flight_id = (int)$_POST['flight_id'];
    $cabin_class = $_POST['cabin_class'];
    $adult_count = (int)($_POST['adult_count'] ?? 1);
    $children_count = (int)($_POST['children_count'] ?? 0);
    $total_price = (float)$_POST['flight_price'];

    $stmt = $conn->prepare("INSERT INTO flight_bookings (flight_id, user_id, cabin_class, adult_count, children_count, total_price, passenger_name, passenger_email, passenger_phone, booking_status, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending')");
    $stmt->bind_param("iisiidsss", $flight_id, $user_id, $cabin_class, $adult_count, $children_count, $total_price, $full_name, $email, $phone);
    if (!$stmt->execute()) {
      error_log("Flight assignment failed: " . $stmt->error);
      $message = "Error assigning flight: " . $stmt->error;
      $message_type = "error";
    }
    $stmt->close();
  }

  if (empty($message)) {
    $message = "Services assigned successfully for booking #$booking_id.";
    $message_type = "success";
  }
}

// Fetch package bookings
$sql = "SELECT pb.*, u.full_name, u.email, u.phone, up.title as package_title 
        FROM package_bookings pb
        JOIN users u ON pb.user_id = u.id
        JOIN umrah_packages up ON pb.package_id = up.id
        WHERE 1=1";

$params = [];
$types = "";

if (!empty($filters['status'])) {
  $sql .= " AND pb.booking_status = ?";
  $params[] = $filters['status'];
  $types .= "s";
}

if (!empty($filters['package_id'])) {
  $sql .= " AND pb.package_id = ?";
  $params[] = $filters['package_id'];
  $types .= "i";
}

if (!empty($filters['search'])) {
  $search = "%" . $filters['search'] . "%";
  $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR up.title LIKE ?)";
  $params[] = $search;
  $params[] = $search;
  $params[] = $search;
  $types .= "sss";
}

if (!empty($filters['date_from'])) {
  $sql .= " AND pb.created_at >= ?";
  $params[] = $filters['date_from'];
  $types .= "s";
}

if (!empty($filters['date_to'])) {
  $sql .= " AND pb.created_at <= ?";
  $params[] = $filters['date_to'];
  $types .= "s";
}

$sql .= " ORDER BY pb.created_at DESC";

// Log the query for debugging
error_log("Package Bookings Query: " . $sql);

$stmt = $conn->prepare($sql);
if ($stmt === false) {
  error_log("Prepare failed: " . $conn->error);
  $message = "Database error occurred. Please try again later.";
  $message_type = "error";
} else {
  if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
  }
  if (!$stmt->execute()) {
    error_log("Execute failed: " . $stmt->error);
    $message = "Database error occurred. Please try again later.";
    $message_type = "error";
  } else {
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
      $bookings[] = $row;
    }
    error_log("Number of package bookings retrieved: " . count($bookings));
  }
  $stmt->close();
}

// Fetch packages for filter
$packages = [];
$result = $conn->query("SELECT id, title FROM umrah_packages ORDER BY title");
if ($result) {
  while ($row = $result->fetch_assoc()) {
    $packages[] = $row;
  }
}

// Fetch hotels, rooms, flights, and routes for assignment options
$hotels = $conn->query("SELECT id, hotel_name AS name FROM hotels ORDER BY hotel_name")->fetch_all(MYSQLI_ASSOC);
$rooms = $conn->query("SELECT id, hotel_id, room_id, status FROM hotel_rooms WHERE status = 'available' ORDER BY room_id")->fetch_all(MYSQLI_ASSOC);
$flights = $conn->query("SELECT id, flight_number, departure_city, arrival_city FROM flights ORDER BY flight_number")->fetch_all(MYSQLI_ASSOC);
$taxi_routes = $conn->query("SELECT id, route_name FROM taxi_routes ORDER BY route_name")->fetch_all(MYSQLI_ASSOC);
$rentacar_routes = $conn->query("SELECT id, route_name FROM rentacar_routes ORDER BY route_name")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Assign Services | UmrahFlights Admin</title>
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <!-- DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    .status-badge {
      border-radius: 9999px;
      padding: 0.25rem 0.75rem;
      font-size: 0.75rem;
      font-weight: 600;
    }

    .status-pending {
      background-color: #FEF3C7;
      color: #D97706;
    }

    .status-confirmed {
      background-color: #D1FAE5;
      color: #047857;
    }

    .status-cancelled {
      background-color: #FEE2E2;
      color: #DC2626;
    }

    .status-completed {
      background-color: #BFDBFE;
      color: #1E40AF;
    }

    .action-btn {
      transition: all 0.2s ease;
    }

    .action-btn:hover {
      transform: scale(1.05);
    }

    /* DataTables customization */
    table.dataTable thead th {
      border-bottom: 2px solid #E5E7EB;
      padding: 10px 18px;
      background-color: #F9FAFB;
    }

    table.dataTable tbody tr {
      background-color: #FFFFFF;
    }

    table.dataTable tbody tr:hover {
      background-color: #F3F4F6;
    }

    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter,
    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_processing,
    .dataTables_wrapper .dataTables_paginate {
      color: #4B5563;
    }

    .dataTables_wrapper .dataTables_length select {
      border: 1px solid #D1D5DB;
      border-radius: 0.375rem;
      padding: 0.25rem 1rem 0.25rem 0.5rem;
    }

    .dataTables_wrapper .dataTables_filter input {
      border: 1px solid #D1D5DB;
      border-radius: 0.375rem;
      padding: 0.25rem 0.5rem;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button {
      padding: 0.25rem 0.75rem;
      border-radius: 0.375rem;
      border: 1px solid #D1D5DB;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
      background: #3B82F6;
      color: white !important;
      border: 1px solid #3B82F6;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
      background: #93C5FD;
      color: #1E40AF !important;
      border: 1px solid #93C5FD;
    }
  </style>
</head>

<body class="bg-gray-50">
  <?php include 'includes/sidebar.php'; ?>

  <div class="ml-0 md:ml-64 min-h-screen p-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-800 flex items-center">
          <i class="fas fa-concierge-bell text-blue-600 mr-2"></i> Assign Services
        </h1>
        <p class="text-gray-600">Assign hotels, transportation, and flights to package bookings</p>
      </div>
    </div>

    <!-- Messages -->
    <?php if ($message): ?>
      <div class="mb-6">
        <div class="rounded-lg p-4 <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?> flex items-center">
          <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-3"></i>
          <?php echo $message; ?>
        </div>
      </div>
    <?php endif; ?>

    <!-- Filter Section -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
      <h2 class="text-lg font-semibold text-gray-800 mb-4">Filter Bookings</h2>
      <form action="" method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div>
          <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Booking Status</label>
          <select id="status" name="status" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">All Statuses</option>
            <option value="pending" <?php echo $filters['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
            <option value="confirmed" <?php echo $filters['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
            <option value="cancelled" <?php echo $filters['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            <option value="completed" <?php echo $filters['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
          </select>
        </div>
        <div>
          <label for="package_id" class="block text-sm font-medium text-gray-700 mb-1">Package</label>
          <select id="package_id" name="package_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">All Packages</option>
            <?php foreach ($packages as $package): ?>
              <option value="<?php echo $package['id']; ?>" <?php echo $filters['package_id'] == $package['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($package['title']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
          <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($filters['search']); ?>" placeholder="Customer name, email, package" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        </div>
        <div>
          <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
          <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($filters['date_from']); ?>" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        </div>
        <div>
          <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
          <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($filters['date_to']); ?>" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        </div>
        <div class="md:col-span-5 flex justify-end">
          <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg mr-2">
            <i class="fas fa-filter mr-2"></i> Apply Filters
          </button>
          <a href="assign-services.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg">
            <i class="fas fa-sync-alt mr-2"></i> Reset
          </a>
        </div>
      </form>
    </div>

    <!-- Bookings Table -->
    <div class="bg-white rounded-lg shadow">
      <div class="p-4 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800">Package Bookings</h2>
      </div>
      <div class="overflow-x-auto p-4">
        <table id="bookingsTable" class="w-full stripe hover">
          <thead>
            <tr>
              <th>ID</th>
              <th>Customer</th>
              <th>Package</th>
              <th>Travel Date</th>
              <th>Travelers</th>
              <th>Total Price</th>
              <th>Status</th>
              <th>Created At</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($bookings)): ?>
              <tr>
                <td colspan="9" class="p-3 text-center text-gray-500">No bookings found</td>
              </tr>
            <?php else: ?>
              <?php foreach ($bookings as $booking): ?>
                <tr>
                  <td class="p-3"><?php echo $booking['id']; ?></td>
                  <td>
                    <div class="font-medium"><?php echo htmlspecialchars($booking['full_name']); ?></div>
                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($booking['email']); ?></div>
                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($booking['phone'] ?? ''); ?></div>
                  </td>
                  <td><?php echo htmlspecialchars($booking['package_title']); ?></td>
                  <td><?php echo date('M d, Y', strtotime($booking['travel_date'])); ?></td>
                  <td><?php echo $booking['num_travelers']; ?></td>
                  <td class="font-medium">PKR <?php echo number_format($booking['total_price'] ?? 0, 0); ?></td>
                  <td>
                    <span class="status-badge <?php echo 'status-' . $booking['booking_status']; ?>">
                      <?php echo ucfirst($booking['booking_status']); ?>
                    </span>
                  </td>
                  <td><?php echo date('M d, Y H:i', strtotime($booking['created_at'])); ?></td>
                  <td>
                    <button type="button" class="action-btn text-blue-600 hover:text-blue-800 assign-services" 
                            data-id="<?php echo $booking['id']; ?>" 
                            data-user-id="<?php echo $booking['user_id']; ?>" 
                            data-full-name="<?php echo htmlspecialchars($booking['full_name']); ?>" 
                            data-email="<?php echo htmlspecialchars($booking['email']); ?>" 
                            data-phone="<?php echo htmlspecialchars($booking['phone'] ?? ''); ?>"
                            data-travel-date="<?php echo $booking['travel_date']; ?>"
                            data-num-travelers="<?php echo $booking['num_travelers']; ?>"
                            title="Assign Services">
                      <i class="fas fa-plus-circle"></i> Assign
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Assign Services Modal -->
  <div id="assignModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg max-w-3xl w-full mx-4 max-h-[90vh] overflow-y-auto">
      <div class="flex justify-between items-center border-b border-gray-200 px-6 py-4">
        <h3 class="text-lg font-semibold text-gray-800">Assign Services</h3>
        <button id="closeModal" class="text-gray-400 hover:text-gray-500">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <form id="assignForm" action="" method="POST" class="p-6">
        <input type="hidden" name="booking_id" id="booking_id">
        <input type="hidden" name="user_id" id="user_id">
        <input type="hidden" name="full_name" id="full_name">
        <input type="hidden" name="email" id="email">
        <input type="hidden" name="phone" id="phone">

        <!-- Hotel Assignment -->
        <div class="mb-6">
          <h4 class="text-md font-semibold text-gray-700 mb-3">Assign Hotel</h4>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label for="hotel_id" class="block text-sm font-medium text-gray-700 mb-1">Hotel</label>
              <select id="hotel_id" name="hotel_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">Select Hotel</option>
                <?php foreach ($hotels as $hotel): ?>
                  <option value="<?php echo $hotel['id']; ?>"><?php echo htmlspecialchars($hotel['name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label for="room_id" class="block text-sm font-medium text-gray-700 mb-1">Room</label>
              <select id="room_id" name="room_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">Select Room</option>
                <?php foreach ($rooms as $room): ?>
                  <option value="<?php echo $room['room_id']; ?>" data-hotel-id="<?php echo $room['hotel_id']; ?>" class="hidden">
                    <?php echo htmlspecialchars($room['room_id']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label for="check_in_date" class="block text-sm font-medium text-gray-700 mb-1">Check-in Date</label>
              <input type="date" id="check_in_date" name="check_in_date" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
              <label for="check_out_date" class="block text-sm font-medium text-gray-700 mb-1">Check-out Date</label>
              <input type="date" id="check_out_date" name="check_out_date" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
              <label for="hotel_price" class="block text-sm font-medium text-gray-700 mb-1">Price (PKR)</label>
              <input type="number" id="hotel_price" name="hotel_price" step="0.01" min="0" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Enter price">
            </div>
            <div>
              <label for="special_requests" class="block text-sm font-medium text-gray-700 mb-1">Special Requests</label>
              <textarea id="special_requests" name="special_requests" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Any special requests"></textarea>
            </div>
          </div>
        </div>

        <!-- Transportation Assignment -->
        <div class="mb-6">
          <h4 class="text-md font-semibold text-gray-700 mb-3">Assign Transportation</h4>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label for="transport_type" class="block text-sm font-medium text-gray-700 mb-1">Transport Type</label>
              <select id="transport_type" name="transport_type" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">Select Type</option>
                <option value="taxi">Taxi</option>
                <option value="rentacar">Rent-a-car</option>
              </select>
            </div>
            <div>
              <label for="route_id" class="block text-sm font-medium text-gray-700 mb-1">Route</label>
              <select id="route_id" name="route_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">Select Route</option>
              </select>
              <input type="hidden" id="route_name" name="route_name">
            </div>
            <div>
              <label for="vehicle_type" class="block text-sm font-medium text-gray-700 mb-1">Vehicle Type</label>
              <select id="vehicle_type" name="vehicle_type" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">Select Vehicle</option>
              </select>
            </div>
            <div>
              <label for="pickup_date" class="block text-sm font-medium text-gray-700 mb-1">Pickup Date</label>
              <input type="date" id="pickup_date" name="pickup_date" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
              <label for="pickup_time" class="block text-sm font-medium text-gray-700 mb-1">Pickup Time</label>
              <input type="time" id="pickup_time" name="pickup_time" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
              <label for="pickup_location" class="block text-sm font-medium text-gray-700 mb-1">Pickup Location</label>
              <input type="text" id="pickup_location" name="pickup_location" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="e.g., Jeddah Airport">
            </div>
            <div>
              <label for="transport_price" class="block text-sm font-medium text-gray-700 mb-1">Price (PKR)</label>
              <input type="number" id="transport_price" name="transport_price" step="0.01" min="0" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Enter price">
            </div>
            <div>
              <label for="additional_notes" class="block text-sm font-medium text-gray-700 mb-1">Additional Notes</label>
              <textarea id="additional_notes" name="additional_notes" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Any additional notes"></textarea>
            </div>
          </div>
        </div>

        <!-- Flight Assignment -->
        <div class="mb-6">
          <h4 class="text-md font-semibold text-gray-700 mb-3">Assign Flight</h4>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label for="flight_id" class="block text-sm font-medium text-gray-700 mb-1">Flight</label>
              <select id="flight_id" name="flight_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">Select Flight</option>
                <?php foreach ($flights as $flight): ?>
                  <option value="<?php echo $flight['id']; ?>">
                    <?php echo htmlspecialchars($flight['flight_number'] . ' (' . $flight['departure_city'] . ' to ' . $flight['arrival_city'] . ')'); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label for="cabin_class" class="block text-sm font-medium text-gray-700 mb-1">Cabin Class</label>
              <select id="cabin_class" name="cabin_class" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">Select Class</option>
                <option value="economy">Economy</option>
                <option value="business">Business</option>
                <option value="first">First Class</option>
              </select>
            </div>
            <div>
              <label for="adult_count" class="block text-sm font-medium text-gray-700 mb-1">Adults</label>
              <input type="number" id="adult_count" name="adult_count" min="1" value="1" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
              <label for="children_count" class="block text-sm font-medium text-gray-700 mb-1">Children</label>
              <input type="number" id="children_count" name="children_count" min="0" value="0" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
              <label for="departure_date" class="block text-sm font-medium text-gray-700 mb-1">Departure Date</label>
              <input type="date" id="departure_date" name="departure_date" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
              <label for="flight_price" class="block text-sm font-medium text-gray-700 mb-1">Price (PKR)</label>
              <input type="number" id="flight_price" name="flight_price" step="0.01" min="0" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Enter price">
            </div>
          </div>
        </div>

        <div class="flex justify-end">
          <button type="submit" name="assign_services" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
            <i class="fas fa-save mr-2"></i> Assign Services
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- jQuery and DataTables -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>

  <script>
    $(document).ready(function() {
      // Initialize DataTable
      $('#bookingsTable').DataTable({
        "order": [[0, "desc"]],
        "pageLength": 10,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
        "columnDefs": [{ "orderable": false, "targets": 8 }],
        "responsive": true
      });

      // Assign Services Modal
      $('.assign-services').click(function() {
        const bookingId = $(this).data('id');
        const userId = $(this).data('user-id');
        const fullName = $(this).data('full-name');
        const email = $(this).data('email');
        const phone = $(this).data('phone');
        const travelDate = $(this).data('travel-date');
        const numTravelers = $(this).data('num-travelers');

        $('#booking_id').val(bookingId);
        $('#user_id').val(userId);
        $('#full_name').val(fullName);
        $('#email').val(email);
        $('#phone').val(phone);
        $('#check_in_date').val(travelDate);
        $('#pickup_date').val(travelDate);
        $('#departure_date').val(travelDate);
        $('#adult_count').val(numTravelers);

        $('#assignModal').removeClass('hidden').addClass('flex');
      });

      // Filter rooms based on selected hotel
      $('#hotel_id').change(function() {
        const hotelId = $(this).val();
        $('#room_id option').addClass('hidden').prop('disabled', true);
        $(`#room_id option[data-hotel-id="${hotelId}"]`).removeClass('hidden').prop('disabled', false);
        $('#room_id').val('');
      });

      // Update routes and vehicles based on transport type
      const taxiRoutes = <?php echo json_encode($taxi_routes); ?>;
      const rentacarRoutes = <?php echo json_encode($rentacar_routes); ?>;
      const taxiVehicles = ['Camry/Sonata', 'Starex/Staria', 'HiAce'];
      const rentacarVehicles = ['GMC 16-19', 'GMC 22-23', 'Coaster'];

      $('#transport_type').change(function() {
        const type = $(this).val();
        const $routeSelect = $('#route_id');
        const $vehicleSelect = $('#vehicle_type');
        $routeSelect.empty().append('<option value="">Select Route</option>');
        $vehicleSelect.empty().append('<option value="">Select Vehicle</option>');

        if (type === 'taxi') {
          taxiRoutes.forEach(route => {
            $routeSelect.append(`<option value="${route.id}" data-name="${route.route_name}">${route.route_name}</option>`);
          });
          taxiVehicles.forEach(vehicle => {
            $vehicleSelect.append(`<option value="${vehicle}">${vehicle}</option>`);
          });
        } else if (type === 'rentacar') {
          rentacarRoutes.forEach(route => {
            $routeSelect.append(`<option value="${route.id}" data-name="${route.route_name}">${route.route_name}</option>`);
          });
          rentacarVehicles.forEach(vehicle => {
            $vehicleSelect.append(`<option value="${vehicle}">${vehicle}</option>`);
          });
        }
      });

      // Update hidden route_name when route is selected
      $('#route_id').change(function() {
        const routeName = $(this).find(':selected').data('name') || '';
        $('#route_name').val(routeName);
      });

      // Close Modal
      $('#closeModal').click(function() {
        $('#assignModal').removeClass('flex').addClass('hidden');
        $('#assignForm')[0].reset();
        $('#route_id').empty().append('<option value="">Select Route</option>');
        $('#vehicle_type').empty().append('<option value="">Select Vehicle</option>');
      });

      // Close modal when clicking outside
      $('#assignModal').click(function(e) {
        if ($(e.target).is('#assignModal')) {
          $('#assignModal').removeClass('flex').addClass('hidden');
          $('#assignForm')[0].reset();
          $('#route_id').empty().append('<option value="">Select Route</option>');
          $('#vehicle_type').empty().append('<option value="">Select Vehicle</option>');
        }
      });

      // SweetAlert for form submission
      $('#assignForm').submit(function(e) {
        e.preventDefault();
        Swal.fire({
          title: 'Are you sure?',
          text: 'Do you want to assign these services?',
          icon: 'question',
          showCancelButton: true,
          confirmButtonColor: '#2563EB',
          cancelButtonColor: '#6B7280',
          confirmButtonText: 'Yes, assign!'
        }).then((result) => {
          if (result.isConfirmed) {
            this.submit();
          }
        });
      });
    });
  </script>
</body>

</html>