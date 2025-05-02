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

// Function to format large numbers into K, M, B suffixes
function formatNumber($number)
{
  if ($number === null || $number == 0) {
    return '0';
  }

  $number = (float)$number; // Ensure it's a number
  $suffixes = ['', 'K', 'M', 'B', 'T'];
  $index = 0;

  while ($number >= 1000 && $index < count($suffixes) - 1) {
    $number /= 1000;
    $index++;
  }

  // Round to 1 decimal place if needed, remove decimal if it's .0
  $formattedNumber = round($number, 1);
  if ($formattedNumber == round($formattedNumber)) {
    $formattedNumber = (int)$formattedNumber; // Remove .0
  }

  return $formattedNumber . $suffixes[$index];
}

// Initialize variables
$bookings = [];
$filters = [
  'status' => $_GET['status'] ?? '',
  'payment' => $_GET['payment'] ?? '',
  'search' => $_GET['search'] ?? '',
  'date_from' => $_GET['date_from'] ?? '',
  'date_to' => $_GET['date_to'] ?? '',
];
$message = '';
$message_type = '';

// Handle status update
if (isset($_GET['action']) && isset($_GET['id'])) {
  $action = $_GET['action'];
  $booking_id = (int)$_GET['id'];

  if ($action === 'confirm') {
    $stmt = $conn->prepare("UPDATE flight_bookings SET booking_status = 'confirmed' WHERE id = ?");
    $stmt->bind_param("i", $booking_id);
    if ($stmt->execute()) {
      $message = "Booking #$booking_id has been confirmed successfully.";
      $message_type = "success";
    } else {
      $message = "Error confirming booking: " . $conn->error;
      $message_type = "error";
    }
    $stmt->close();
  } elseif ($action === 'cancel') {
    $stmt = $conn->prepare("UPDATE flight_bookings SET booking_status = 'cancelled' WHERE id = ?");
    $stmt->bind_param("i", $booking_id);
    if ($stmt->execute()) {
      $message = "Booking #$booking_id has been cancelled.";
      $message_type = "success";
    } else {
      $message = "Error cancelling booking: " . $conn->error;
      $message_type = "error";
    }
    $stmt->close();
  } elseif ($action === 'complete_payment') {
    $stmt = $conn->prepare("UPDATE flight_bookings SET payment_status = 'completed' WHERE id = ?");
    $stmt->bind_param("i", $booking_id);
    if ($stmt->execute()) {
      $message = "Payment for booking #$booking_id has been marked as completed.";
      $message_type = "success";
    } else {
      $message = "Error updating payment status: " . $conn->error;
      $message_type = "error";
    }
    $stmt->close();
  }
}

// Build the query with filters
$sql = "SELECT fb.*, f.flight_number, f.airline_name, f.departure_city, f.arrival_city, 
               f.departure_date, f.departure_time, u.full_name as user_name, u.email as user_email
        FROM flight_bookings fb
        JOIN flights f ON fb.flight_id = f.id
        JOIN users u ON fb.user_id = u.id
        WHERE 1=1";

$params = [];
$types = "";

if (!empty($filters['status'])) {
  $sql .= " AND fb.booking_status = ?";
  $params[] = $filters['status'];
  $types .= "s";
}

if (!empty($filters['payment'])) {
  $sql .= " AND fb.payment_status = ?";
  $params[] = $filters['payment'];
  $types .= "s";
}

if (!empty($filters['search'])) {
  $search = "%" . $filters['search'] . "%";
  $sql .= " AND (fb.passenger_name LIKE ? OR f.flight_number LIKE ? OR u.email LIKE ?)";
  $params[] = $search;
  $params[] = $search;
  $params[] = $search;
  $types .= "sss";
}

if (!empty($filters['date_from'])) {
  $sql .= " AND f.departure_date >= ?";
  $params[] = $filters['date_from'];
  $types .= "s";
}

if (!empty($filters['date_to'])) {
  $sql .= " AND f.departure_date <= ?";
  $params[] = $filters['date_to'];
  $types .= "s";
}

$sql .= " ORDER BY fb.created_at DESC";

// Log the query for debugging
error_log("Bookings Query: " . $sql);

// Prepare and execute query
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
  }
  $stmt->close();
}

// Get booking statistics
$stats = [
  'total' => 0,
  'pending' => 0,
  'confirmed' => 0,
  'cancelled' => 0,
  'completed_payments' => 0,
  'pending_payments' => 0,
  'total_revenue' => 0,
  'potential_revenue' => 0
];

$stats_query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN booking_status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN booking_status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
                    SUM(CASE WHEN booking_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                    SUM(CASE WHEN payment_status = 'completed' THEN 1 ELSE 0 END) as completed_payments,
                    SUM(CASE WHEN payment_status = 'pending' THEN 1 ELSE 0 END) as pending_payments,
                    COALESCE(SUM(CASE WHEN payment_status = 'completed' THEN total_price ELSE 0 END), 0) as total_revenue,
                    COALESCE(SUM(CASE WHEN booking_status != 'cancelled' THEN total_price ELSE 0 END), 0) as potential_revenue
                FROM flight_bookings";

// Log the stats query for debugging
error_log("Stats Query: " . $stats_query);

$result = $conn->query($stats_query);
if ($result) {
  $stats = $result->fetch_assoc();
} else {
  error_log("Stats query failed: " . $conn->error);
  $message = "Error fetching statistics: " . $conn->error;
  $message_type = "error";
}

// Initialize variables
$flights = [];
$filteredFlights = [];
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$totalFlights = 0;

// Get all flights
$query = "SELECT * FROM flights ORDER BY departure_date DESC";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $flights[] = $row;
  }
  $totalFlights = count($flights);
}

// Calculate additional statistics
$totalEconomySeats = 0;
$totalBusinessSeats = 0;
$totalFirstClassSeats = 0;
$totalEconomyRevenue = 0;
$totalBusinessRevenue = 0;
$totalFirstClassRevenue = 0;
$avgEconomyPrice = 0;
$avgBusinessPrice = 0;
$avgFirstClassPrice = 0;
$totalStops = 0;
$totalDistance = 0;

if (!empty($flights)) {
  foreach ($flights as $flight) {
    // Seat counts
    $totalEconomySeats += $flight['economy_seats'];
    $totalBusinessSeats += $flight['business_seats'];
    $totalFirstClassSeats += $flight['first_class_seats'];

    // Revenue potential (price * seats)
    $totalEconomyRevenue += $flight['economy_price'] * $flight['economy_seats'];
    $totalBusinessRevenue += $flight['business_price'] * $flight['business_seats'];
    $totalFirstClassRevenue += $flight['first_class_price'] * $flight['first_class_seats'];

    // Stops
    if ($flight['has_stops']) {
      $stops = json_decode($flight['stops'], true);
      $totalStops += is_array($stops) ? count($stops) : 0;
    }
    if ($flight['has_return'] && $flight['has_return_stops']) {
      $returnStops = json_decode($flight['return_stops'], true);
      $totalStops += is_array($returnStops) ? count($returnStops) : 0;
    }

    // Distance
    $totalDistance += $flight['distance'];
  }

  // Calculate averages (avoid division by zero)
  $totalFlightsNonZero = $totalFlights > 0 ? $totalFlights : 1;
  $avgEconomyPrice = $totalFlights ? array_sum(array_column($flights, 'economy_price')) / $totalFlightsNonZero : 0;
  $avgBusinessPrice = $totalFlights ? array_sum(array_column($flights, 'business_price')) / $totalFlightsNonZero : 0;
  $avgFirstClassPrice = $totalFlights ? array_sum(array_column($flights, 'first_class_price')) / $totalFlightsNonZero : 0;
  $avgDistance = $totalFlights ? $totalDistance / $totalFlightsNonZero : 0;
}

// Total revenue potential
$totalRevenuePotential = $totalEconomyRevenue + $totalBusinessRevenue + $totalFirstClassRevenue;

// Total seats
$totalSeats = $totalEconomySeats + $totalBusinessSeats + $totalFirstClassSeats;

// Handle success/error messages
$message = '';
$messageType = '';

if (isset($_SESSION['success'])) {
  $message = $_SESSION['success'];
  $messageType = 'success';
  unset($_SESSION['success']);
} elseif (isset($_SESSION['error'])) {
  $message = $_SESSION['error'];
  $messageType = 'error';
  unset($_SESSION['error']);
}

// Apply filters
$filteredFlights = $flights;

if (!empty($search)) {
  $filteredFlights = array_filter($flights, function ($flight) use ($search) {
    $search = strtolower($search);
    return (
      stripos($flight['airline_name'], $search) !== false ||
      stripos($flight['flight_number'], $search) !== false ||
      stripos($flight['departure_city'], $search) !== false ||
      stripos($flight['arrival_city'], $search) !== false
    );
  });
}

if (!empty($filter)) {
  switch ($filter) {
    case 'one-way':
      $filteredFlights = array_filter($filteredFlights, function ($flight) {
        return $flight['has_return'] == 0;
      });
      break;
    case 'round-trip':
      $filteredFlights = array_filter($filteredFlights, function ($flight) {
        return $flight['has_return'] == 1;
      });
      break;
    case 'direct':
      $filteredFlights = array_filter($filteredFlights, function ($flight) {
        return $flight['has_stops'] == 0;
      });
      break;
    case 'with-stops':
      $filteredFlights = array_filter($filteredFlights, function ($flight) {
        return $flight['has_stops'] == 1;
      });
      break;
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Flight Bookings | UmrahFlights Admin</title>
  <!-- Tailwind CSS -->
  <!-- <script src="https://cdn.tailwindcss.com"></script> -->
  <!-- Font Awesome -->
  <link rel="stylesheet" href="../src/output.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <!-- DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
  <style>
    .booking-card:hover {
      transform: translateY(-4px);
      transition: transform 0.3s ease;
    }

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

    .payment-completed {
      background-color: #D1FAE5;
      color: #047857;
    }

    .payment-pending {
      background-color: #FEF3C7;
      color: #D97706;
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
          <i class="fas fa-ticket-alt text-blue-600 mr-2"></i> Flight Bookings
        </h1>
        <p class="text-gray-600">Manage all flight bookings</p>
      </div>
      <div class="mt-4 md:mt-0">
        <a href="view-flights.php" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
          <i class="fas fa-plane mr-2"></i> View Flights
        </a>
      </div>
    </div>

    <!-- Stats Cards -->
    <!-- <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <div class="bg-white rounded-lg shadow p-4 flex items-start">
        <div class="bg-blue-100 rounded-lg p-3 mr-4">
          <i class="fas fa-calendar-check text-blue-600 text-xl"></i>
        </div>
        <div>
          <h3 class="text-gray-500 text-sm font-medium">Total Bookings</h3>
          <p class="text-2xl font-bold text-gray-800"><?php echo $stats['total']; ?></p>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-4 flex items-start">
        <div class="bg-green-100 rounded-lg p-3 mr-4">
          <i class="fas fa-check-circle text-green-600 text-xl"></i>
        </div>
        <div>
          <h3 class="text-gray-500 text-sm font-medium">Confirmed Bookings</h3>
          <p class="text-2xl font-bold text-gray-800"><?php echo $stats['confirmed']; ?></p>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-4 flex items-start">
        <div class="bg-yellow-100 rounded-lg p-3 mr-4">
          <i class="fas fa-clock text-yellow-600 text-xl"></i>
        </div>
        <div>
          <h3 class="text-gray-500 text-sm font-medium">Pending Bookings</h3>
          <p class="text-2xl font-bold text-gray-800"><?php echo $stats['pending']; ?></p>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-4 flex items-start">
        <div class="bg-green-100 rounded-lg p-3 mr-4">
          <i class="fas fa-money-bill text-green-600 text-xl"></i>
        </div>
        <div>
          <h3 class="text-gray-500 text-sm font-medium">Total Revenue</h3>
          <p class="text-2xl font-bold text-gray-800">PKR <?php echo number_format($stats['total_revenue'] ?? 0, 0); ?></p>
        </div>
      </div>
    </div> -->
    <?php include 'includes/sums-flight.php'; ?>
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
          </select>
        </div>
        <div>
          <label for="payment" class="block text-sm font-medium text-gray-700 mb-1">Payment Status</label>
          <select id="payment" name="payment" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">All Payments</option>
            <option value="pending" <?php echo $filters['payment'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
            <option value="completed" <?php echo $filters['payment'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
          </select>
        </div>
        <div>
          <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
          <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($filters['search']); ?>" placeholder="Passenger name, flight #, email" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
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
          <a href="booked-flights.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg">
            <i class="fas fa-sync-alt mr-2"></i> Reset
          </a>
        </div>
      </form>
    </div>

    <!-- Bookings Table -->
    <!-- Bookings Table -->
    <div class="bg-white rounded-lg shadow">
      <div class="p-4 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800">Flight Bookings</h2>
      </div>
      <div class="overflow-x-auto p-4">
        <table id="bookingsTable" class="min-w-max table-auto w-full whitespace-nowrap">
          <thead>
            <tr>
              <th class="px-4 py-2 text-left">ID</th>
              <th class="px-4 py-2 text-left">Passenger</th>
              <th class="px-4 py-2 text-left">Flight</th>
              <th class="px-4 py-2 text-left">Route</th>
              <th class="px-4 py-2 text-left">Date</th>
              <th class="px-4 py-2 text-left">Cabin Class</th>
              <th class="px-4 py-2 text-left">Passengers</th>
              <th class="px-4 py-2 text-left">Total Price</th>
              <th class="px-4 py-2 text-left">Booking Status</th>
              <th class="px-4 py-2 text-left">Payment Status</th>
              <th class="px-4 py-2 text-left">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($bookings)): ?>
              <tr>
                <td colspan="11" class="text-center py-4 text-gray-500">No bookings found</td>
              </tr>
            <?php else: ?>
              <?php foreach ($bookings as $booking): ?>
                <tr>
                  <td class="px-4 py-2"><?php echo $booking['id']; ?></td>
                  <td class="px-4 py-2">
                    <div class="font-medium"><?php echo htmlspecialchars($booking['passenger_name']); ?></div>
                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($booking['passenger_email']); ?></div>
                  </td>
                  <td class="px-4 py-2">
                    <div class="font-medium"><?php echo htmlspecialchars($booking['flight_number']); ?></div>
                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($booking['airline_name']); ?></div>
                  </td>
                  <td class="px-4 py-2"><?php echo htmlspecialchars($booking['departure_city']); ?> → <?php echo htmlspecialchars($booking['arrival_city']); ?></td>
                  <td class="px-4 py-2">
                    <div><?php echo date('M d, Y', strtotime($booking['departure_date'])); ?></div>
                    <div class="text-sm text-gray-500"><?php echo date('h:i A', strtotime($booking['departure_time'])); ?></div>
                  </td>
                  <td class="px-4 py-2"><?php echo ucfirst(htmlspecialchars(str_replace('_', ' ', $booking['cabin_class']))); ?></td>
                  <td class="px-4 py-2">
                    <div>Adults: <?php echo $booking['adult_count']; ?></div>
                    <div>Children: <?php echo $booking['children_count']; ?></div>
                  </td>
                  <td class="px-4 py-2 font-medium">PKR <?php echo number_format($booking['total_price'] ?? 0, 0); ?></td>
                  <td class="px-4 py-2">
                    <span class="status-badge <?php echo 'status-' . $booking['booking_status']; ?>">
                      <?php echo ucfirst($booking['booking_status']); ?>
                    </span>
                  </td>
                  <td class="px-4 py-2">
                    <span class="status-badge <?php echo 'payment-' . $booking['payment_status']; ?>">
                      <?php echo ucfirst($booking['payment_status']); ?>
                    </span>
                  </td>
                  <td class="px-4 py-2">
                    <div class="flex space-x-2">
                      <?php if ($booking['booking_status'] === 'pending'): ?>
                        <a href="?action=confirm&id=<?php echo $booking['id']; ?>&<?php echo http_build_query($filters); ?>" class="action-btn text-green-600 hover:text-green-800" title="Confirm Booking">
                          <i class="fas fa-check"></i>
                        </a>
                      <?php endif; ?>
                      <?php if ($booking['booking_status'] !== 'cancelled'): ?>
                        <a href="?action=cancel&id=<?php echo $booking['id']; ?>&<?php echo http_build_query($filters); ?>" class="action-btn text-red-600 hover:text-red-800" title="Cancel Booking" onclick="return confirm('Are you sure you want to cancel this booking?')">
                          <i class="fas fa-times"></i>
                        </a>
                      <?php endif; ?>
                      <?php if ($booking['payment_status'] === 'pending'): ?>
                        <a href="?action=complete_payment&id=<?php echo $booking['id']; ?>&<?php echo http_build_query($filters); ?>" class="action-btn text-blue-600 hover:text-blue-800" title="Mark Payment as Completed">
                          <i class="fas fa-dollar-sign"></i>
                        </a>
                      <?php endif; ?>
                      <button type="button" class="action-btn text-gray-600 hover:text-gray-800 view-details" data-id="<?php echo $booking['id']; ?>" title="View Details">
                        <i class="fas fa-eye"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>

  <!-- View Details Modal -->
  <div id="detailsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg max-w-3xl w-full mx-4 max-h-[90vh] overflow-y-auto">
      <div class="flex justify-between items-center border-b border-gray-200 px-6 py-4">
        <h3 class="text-lg font-semibold text-gray-800">Booking Details</h3>
        <button id="closeModal" class="text-gray-400 hover:text-gray-500">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <div id="modalContent" class="p-6">
        <!-- Content will be loaded dynamically -->
        <div class="animate-pulse">
          <div class="h-4 bg-gray-200 rounded w-3/4 mb-4"></div>
          <div class="h-4 bg-gray-200 rounded w-1/2 mb-4"></div>
          <div class="h-4 bg-gray-200 rounded w-5/6 mb-4"></div>
          <div class="h-4 bg-gray-200 rounded w-2/3 mb-4"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- jQuery and DataTables -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>

  <script>
    $(document).ready(function() {
      // Initialize DataTable
      $('#bookingsTable').DataTable({
        "order": [
          [0, "desc"]
        ],
        "pageLength": 10,
        "lengthMenu": [
          [10, 25, 50, -1],
          [10, 25, 50, "All"]
        ],
        "columnDefs": [{
            "orderable": false,
            "targets": 10
          } // Disable sorting on actions column
        ],
        "responsive": true
      });

      // View Details Modal
      $('.view-details').click(function() {
        const bookingId = $(this).data('id');
        $('#detailsModal').removeClass('hidden').addClass('flex');

        // In a real application, you would fetch the details via AJAX
        // For now, we'll just fill it with example content
        const booking = <?php echo json_encode($bookings); ?>.find(b => b.id == bookingId);
        if (booking) {
          let html = `
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <h4 class="font-semibold text-gray-700 mb-3">Booking Information</h4>
                <p><span class="font-medium">Booking ID:</span> ${booking.id}</p>
                <p><span class="font-medium">Booking Date:</span> ${new Date(booking.created_at).toLocaleDateString()}</p>
                <p><span class="font-medium">Booking Status:</span> <span class="status-badge status-${booking.booking_status}">${booking.booking_status.charAt(0).toUpperCase() + booking.booking_status.slice(1)}</span></p>
                <p><span class="font-medium">Payment Status:</span> <span class="status-badge payment-${booking.payment_status}">${booking.payment_status.charAt(0).toUpperCase() + booking.payment_status.slice(1)}</span></p>
                <p><span class="font-medium">Total Price:</span> PKR ${parseInt(booking.total_price || 0).toLocaleString()}</p>
              </div>
              <div>
                <h4 class="font-semibold text-gray-700 mb-3">Passenger Information</h4>
                <p><span class="font-medium">Name:</span> ${booking.passenger_name}</p>
                <p><span class="font-medium">Email:</span> ${booking.passenger_email}</p>
                <p><span class="font-medium">Phone:</span> ${booking.passenger_phone || 'N/A'}</p>
                <p><span class="font-medium">Adults:</span> ${booking.adult_count}</p>
                <p><span class="font-medium">Children:</span> ${booking.children_count}</p>
              </div>
              <div class="md:col-span-2">
                <h4 class="font-semibold text-gray-700 mb-3">Flight Information</h4>
                <p><span class="font-medium">Flight Number:</span> ${booking.flight_number}</p>
                <p><span class="font-medium">Airline:</span> ${booking.airline_name}</p>
                <p><span class="font-medium">Route:</span> ${booking.departure_city} → ${booking.arrival_city}</p>
                <p><span class="font-medium">Departure Date:</span> ${new Date(booking.departure_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
                <p><span class="font-medium">Departure Time:</span> ${new Date('1970-01-01T' + booking.departure_time).toLocaleTimeString('en-US', { hour: 'numeric', minute: 'numeric', hour12: true })}</p>
                <p><span class="font-medium">Cabin Class:</span> ${booking.cabin_class.charAt(0).toUpperCase() + booking.cabin_class.slice(1).replace('_', ' ')}</p>
              </div>
            </div>
          `;
          $('#modalContent').html(html);
        } else {
          $('#modalContent').html('<p class="text-red-500">Error loading booking details.</p>');
        }
      });

      // Close Modal
      $('#closeModal').click(function() {
        $('#detailsModal').removeClass('flex').addClass('hidden');
      });

      // Close modal when clicking outside
      $('#detailsModal').click(function(e) {
        if ($(e.target).is('#detailsModal')) {
          $('#detailsModal').removeClass('flex').addClass('hidden');
        }
      });
    });
  </script>
</body>

</html>