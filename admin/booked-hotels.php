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
  'payment' => $_GET['payment'] ?? '',
  'location' => $_GET['location'] ?? '',
  'search' => $_GET['search'] ?? '',
  'check_in_from' => $_GET['check_in_from'] ?? '',
  'check_in_to' => $_GET['check_in_to'] ?? '',
];
$message = '';
$message_type = '';

// Handle status update
if (isset($_GET['action']) && isset($_GET['id'])) {
  $action = $_GET['action'];
  $booking_id = (int)$_GET['id'];

  if ($action === 'confirm') {
    $stmt = $conn->prepare("UPDATE hotel_bookings SET booking_status = 'confirmed' WHERE id = ?");
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
    // Start transaction
    $conn->begin_transaction();

    try {
      // Get hotel and room ID
      $stmt = $conn->prepare("SELECT hotel_id, room_id FROM hotel_bookings WHERE id = ?");
      $stmt->bind_param("i", $booking_id);
      $stmt->execute();
      $result = $stmt->get_result();
      $booking_data = $result->fetch_assoc();
      $stmt->close();

      if ($booking_data) {
        // Update booking status
        $stmt = $conn->prepare("UPDATE hotel_bookings SET booking_status = 'cancelled' WHERE id = ?");
        $stmt->bind_param("i", $booking_id);
        if (!$stmt->execute()) {
          throw new Exception("Error updating booking status: " . $conn->error);
        }
        $stmt->close();

        // Update room status to available
        $stmt = $conn->prepare("UPDATE hotel_rooms SET status = 'available' WHERE hotel_id = ? AND room_id = ?");
        $stmt->bind_param("is", $booking_data['hotel_id'], $booking_data['room_id']);
        if (!$stmt->execute()) {
          throw new Exception("Error updating room status: " . $conn->error);
        }
        $stmt->close();

        $conn->commit();
        $message = "Booking #$booking_id has been cancelled and room has been released.";
        $message_type = "success";
      } else {
        throw new Exception("Booking not found");
      }
    } catch (Exception $e) {
      $conn->rollback();
      $message = "Error: " . $e->getMessage();
      $message_type = "error";
    }
  } elseif ($action === 'complete_payment') {
    $stmt = $conn->prepare("UPDATE hotel_bookings SET payment_status = 'paid' WHERE id = ?");
    $stmt->bind_param("i", $booking_id);
    if ($stmt->execute()) {
      $message = "Payment for booking #$booking_id has been marked as paid.";
      $message_type = "success";
    } else {
      $message = "Error updating payment status: " . $conn->error;
      $message_type = "error";
    }
    $stmt->close();
  }
}

// Build the query with filters
$sql = "SELECT hb.*, h.hotel_name, h.location, h.price as price_per_night, 
               DATEDIFF(hb.check_out_date, hb.check_in_date) as nights_count,
               u.full_name as user_name, u.email as user_email
        FROM hotel_bookings hb
        JOIN hotels h ON hb.hotel_id = h.id
        JOIN users u ON hb.user_id = u.id
        WHERE 1=1";

$params = [];
$types = "";

if (!empty($filters['status'])) {
  $sql .= " AND hb.booking_status = ?";
  $params[] = $filters['status'];
  $types .= "s";
}

if (!empty($filters['payment'])) {
  $sql .= " AND hb.payment_status = ?";
  $params[] = $filters['payment'];
  $types .= "s";
}

if (!empty($filters['location'])) {
  $sql .= " AND h.location = ?";
  $params[] = $filters['location'];
  $types .= "s";
}

if (!empty($filters['search'])) {
  $search = "%" . $filters['search'] . "%";
  $sql .= " AND (h.hotel_name LIKE ? OR hb.booking_reference LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
  $params[] = $search;
  $params[] = $search;
  $params[] = $search;
  $params[] = $search;
  $types .= "ssss";
}

if (!empty($filters['check_in_from'])) {
  $sql .= " AND hb.check_in_date >= ?";
  $params[] = $filters['check_in_from'];
  $types .= "s";
}

if (!empty($filters['check_in_to'])) {
  $sql .= " AND hb.check_in_date <= ?";
  $params[] = $filters['check_in_to'];
  $types .= "s";
}

$sql .= " ORDER BY hb.created_at DESC";

// Log the query for debugging
error_log("Hotel Bookings Query: " . $sql);

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
    error_log("Number of hotel bookings retrieved: " . count($bookings));
  }
  $stmt->close();
}

// Get booking statistics
$stats = [
  'total' => 0,
  'pending' => 0,
  'confirmed' => 0,
  'cancelled' => 0,
  'unpaid' => 0,
  'paid' => 0,
  'total_revenue' => 0,
  'makkah_bookings' => 0,
  'madinah_bookings' => 0
];

$stats_query = "SELECT 
                   COUNT(*) as total,
                   SUM(CASE WHEN booking_status = 'pending' THEN 1 ELSE 0 END) as pending,
                   SUM(CASE WHEN booking_status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
                   SUM(CASE WHEN booking_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                   SUM(CASE WHEN payment_status = 'unpaid' THEN 1 ELSE 0 END) as unpaid,
                   SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) as paid,
                   COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN total_price ELSE 0 END), 0) as total_revenue
                FROM hotel_bookings";

// Log the stats query for debugging
error_log("Hotel Stats Query: " . $stats_query);

$result = $conn->query($stats_query);
if ($result) {
  $stats = $result->fetch_assoc();
} else {
  error_log("Stats query failed: " . $conn->error);
  $message = "Error fetching statistics: " . $conn->error;
  $message_type = "error";
}

// Get location breakdown
$location_query = "SELECT 
                      COALESCE(SUM(CASE WHEN h.location = 'makkah' THEN 1 ELSE 0 END), 0) as makkah_bookings,
                      COALESCE(SUM(CASE WHEN h.location = 'madinah' THEN 1 ELSE 0 END), 0) as madinah_bookings
                   FROM hotel_bookings hb
                   JOIN hotels h ON hb.hotel_id = h.id
                   WHERE hb.booking_status != 'cancelled'";

// Log the location query for debugging
error_log("Location Query: " . $location_query);

$result = $conn->query($location_query);
if ($result) {
  $location_stats = $result->fetch_assoc();
  $stats['makkah_bookings'] = $location_stats['makkah_bookings'] ?? 0;
  $stats['madinah_bookings'] = $location_stats['madinah_bookings'] ?? 0;
} else {
  error_log("Location query failed: " . $conn->error);
  $message = "Error fetching location statistics: " . $conn->error;
  $message_type = "error";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Hotel Bookings | UmrahFlights</title>
  <!-- Tailwind CSS -->
  <link rel="stylesheet" href="../src/output.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    .status-badge {
      @apply inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold;
    }

    .status-pending {
      @apply bg-yellow-100 text-yellow-800;
    }

    .status-confirmed {
      @apply bg-green-100 text-green-800;
    }

    .status-cancelled {
      @apply bg-red-100 text-red-800;
    }

    .payment-paid {
      @apply bg-green-100 text-green-800;
    }

    .payment-unpaid {
      @apply bg-yellow-100 text-yellow-800;
    }

    .action-btn {
      @apply transition-transform hover:scale-105;
    }
  </style>
</head>

<body class="bg-gray-100 font-sans min-h-screen">
  <?php include 'includes/sidebar.php'; ?>
  <main class="ml-0 md:ml-64 mt-10 px-4 sm:px-6 lg:px-8 transition-all duration-300" role="main" aria-label="Main content">
    <!-- Top Navbar -->
    <nav class="bg-white shadow-lg rounded-lg p-5 mb-6">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <button id="sidebarToggle" class="text-gray-500 hover:text-gray-700 focus:outline-none md:hidden" aria-label="Toggle sidebar">
            <i class="fas fa-bars text-xl"></i>
          </button>
          <h4 id="dashboardHeader" class="text-lg font-semibold text-gray-800 cursor-pointer hover:text-indigo-600">Hotel Bookings</h4>
        </div>
        <div class="flex items-center space-x-4">
          <!-- User Dropdown -->
          <div class="relative">
            <button id="userDropdownButton" class="flex items-center space-x-2 text-gray-700 hover:bg-indigo-50 rounded-lg px-3 py-2 focus:outline-none" aria-label="User menu" aria-expanded="false">
              <div class="rounded-full overflow-hidden" style="width: 32px; height: 32px;">
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

    <!-- Main Content Section -->
    <section class="bg-white shadow-lg rounded-lg p-6" aria-label="Hotel bookings">
      <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-6">
        <div>
          <h2 class="text-2xl font-bold text-gray-800 flex items-center">
            <i class="fas fa-hotel text-indigo-600 mr-2"></i> Hotel Bookings
          </h2>
          <p class="text-gray-600">Manage all hotel bookings</p>
        </div>
        <div class="mt-4 md:mt-0">
          <a href="view-hotels.php" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition-colors">
            <i class="fas fa-hotel mr-2"></i> View Hotels
          </a>
        </div>
      </div>

      <!-- Stats Cards (Uncomment if needed) -->
      <?php include 'includes/hotel-stats.php'; ?>
      <!-- <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4 flex items-start">
          <div class="bg-indigo-100 rounded-lg p-3 mr-4">
            <i class="fas fa-calendar-check text-indigo-600 text-xl"></i>
          </div>
          <div>
            <h3 class="text-gray-500 text-sm font-medium">Total Bookings</h3>
            <p class="text-2xl font-bold text-gray-800"><?php echo $stats['total'] ?? 0; ?></p>
          </div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 flex items-start">
          <div class="bg-green-100 rounded-lg p-3 mr-4">
            <i class="fas fa-check-circle text-green-600 text-xl"></i>
          </div>
          <div>
            <h3 class="text-gray-500 text-sm font-medium">Confirmed Bookings</h3>
            <p class="text-2xl font-bold text-gray-800"><?php echo $stats['confirmed'] ?? 0; ?></p>
          </div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 flex items-start">
          <div class="bg-green-100 rounded-lg p-3 mr-4">
            <i class="fas fa-money-bill text-green-600 text-xl"></i>
          </div>
          <div>
            <h3 class="text-gray-500 text-sm font-medium">Revenue</h3>
            <p class="text-2xl font-bold text-gray-800">PKR <?php echo number_format($stats['total_revenue'] ?? 0, 0); ?></p>
          </div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 flex items-start">
          <div class="bg-yellow-100 rounded-lg p-3 mr-4">
            <i class="fas fa-map-marker-alt text-yellow-600 text-xl"></i>
          </div>
          <div>
            <h3 class="text-gray-500 text-sm font-medium">Locations</h3>
            <p class="text-md font-medium text-gray-800">Makkah: <?php echo $stats['makkah_bookings'] ?? 0; ?>, Madinah: <?php echo $stats['madinah_bookings'] ?? 0; ?></p>
          </div>
        </div>
      </div> -->

      <!-- Messages -->
      <?php if ($message): ?>
        <div class="mb-6">
          <div class="bg-<?php echo $message_type === 'success' ? 'green' : 'red'; ?>-100 border-l-4 border-<?php echo $message_type === 'success' ? 'green' : 'red'; ?>-500 text-<?php echo $message_type === 'success' ? 'green' : 'red'; ?>-700 p-4 rounded-lg flex justify-between items-center" role="alert">
            <span><i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-3"></i><?php echo htmlspecialchars($message); ?></span>
            <button class="text-<?php echo $message_type === 'success' ? 'green' : 'red'; ?>-700 hover:text-<?php echo $message_type === 'success' ? 'green' : 'red'; ?>-900 focus:outline-none focus:ring-2 focus:ring-<?php echo $message_type === 'success' ? 'green' : 'red'; ?>-500" onclick="this.parentElement.remove()" aria-label="Close alert">
              <i class="fas fa-times"></i>
            </button>
          </div>
        </div>
      <?php endif; ?>

      <!-- Filter Section -->
      <div class="bg-white rounded-lg shadow p-4 mb-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Filter Bookings</h3>
        <form action="" method="GET" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
          <div>
            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Booking Status</label>
            <select id="status" name="status" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
              <option value="">All Statuses</option>
              <option value="pending" <?php echo $filters['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
              <option value="confirmed" <?php echo $filters['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
              <option value="cancelled" <?php echo $filters['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
          </div>
          <div>
            <label for="payment" class="block text-sm font-medium text-gray-700 mb-1">Payment Status</label>
            <select id="payment" name="payment" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
              <option value="">All Payments</option>
              <option value="unpaid" <?php echo $filters['payment'] === 'unpaid' ? 'selected' : ''; ?>>Unpaid</option>
              <option value="paid" <?php echo $filters['payment'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
            </select>
          </div>
          <div>
            <label for="location" class="block text-sm font-medium text-gray-700 mb-1">Location</label>
            <select id="location" name="location" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
              <option value="">All Locations</option>
              <option value="makkah" <?php echo $filters['location'] === 'makkah' ? 'selected' : ''; ?>>Makkah</option>
              <option value="madinah" <?php echo $filters['location'] === 'madinah' ? 'selected' : ''; ?>>Madinah</option>
            </select>
          </div>
          <div>
            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
            <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($filters['search']); ?>" placeholder="Hotel name, reference, guest" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
          </div>
          <div>
            <label for="check_in_from" class="block text-sm font-medium text-gray-700 mb-1">Check-in From</label>
            <input type="date" id="check_in_from" name="check_in_from" value="<?php echo htmlspecialchars($filters['check_in_from']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
          </div>
          <div>
            <label for="check_in_to" class="block text-sm font-medium text-gray-700 mb-1">Check-in To</label>
            <input type="date" id="check_in_to" name="check_in_to" value="<?php echo htmlspecialchars($filters['check_in_to']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
          </div>
          <div class="md:col-span-3 lg:col-span-6 flex justify-end">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md mr-2">
              <i class="fas fa-filter mr-2"></i> Apply Filters
            </button>
            <a href="booked-hotels.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-md">
              <i class="fas fa-sync-alt mr-2"></i> Reset
            </a>
          </div>
        </form>
      </div>

      <!-- Bookings Table -->
      <div class="bg-white rounded-lg shadow">
        <div class="p-4 border-b border-gray-200">
          <h3 class="text-lg font-semibold text-gray-800">Hotel Bookings</h3>
        </div>
        <div class="overflow-x-auto p-4">
          <table class="min-w-full bg-white border border-gray-200 rounded-lg">
            <thead>
              <tr class="bg-gray-100">
                <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">ID</th>
                <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Guest</th>
                <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Hotel</th>
                <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Room</th>
                <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Check-in / Check-out</th>
                <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Nights</th>
                <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Reference</th>
                <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Total Price</th>
                <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Booking Status</th>
                <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Payment Status</th>
                <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <?php if (empty($bookings)): ?>
                <tr>
                  <td colspan="11" class="py-4 px-4 text-center text-gray-500">No bookings found</td>
                </tr>
              <?php else: ?>
                <?php foreach ($bookings as $booking): ?>
                  <tr class="hover:bg-gray-50">
                    <td class="py-2 px-4"><?php echo $booking['id']; ?></td>
                    <td class="py-2 px-4">
                      <div class="font-medium"><?php echo htmlspecialchars($booking['user_name']); ?></div>
                      <div class="text-sm text-gray-500"><?php echo htmlspecialchars($booking['user_email']); ?></div>
                    </td>
                    <td class="py-2 px-4">
                      <div class="font-medium"><?php echo htmlspecialchars($booking['hotel_name']); ?></div>
                      <div class="text-sm text-gray-500 capitalize"><?php echo htmlspecialchars($booking['location']); ?></div>
                    </td>
                    <td class="py-2 px-4 text-center"><?php echo htmlspecialchars($booking['room_id']); ?></td>
                    <td class="py-2 px-4">
                      <div>In: <?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?></div>
                      <div>Out: <?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?></div>
                    </td>
                    <td class="py-2 px-4 text-center"><?php echo $booking['nights_count']; ?></td>
                    <td class="py-2 px-4"><?php echo htmlspecialchars($booking['booking_reference']); ?></td>
                    <td class="py-2 px-4 font-medium">PKR <?php echo number_format($booking['total_price'] ?? 0, 0); ?></td>
                    <td class="py-2 px-4">
                      <span class="status-badge <?php echo 'status-' . $booking['booking_status']; ?>">
                        <?php echo ucfirst($booking['booking_status']); ?>
                      </span>
                    </td>
                    <td class="py-2 px-4">
                      <span class="status-badge <?php echo 'payment-' . $booking['payment_status']; ?>">
                        <?php echo ucfirst($booking['payment_status']); ?>
                      </span>
                    </td>
                    <td class="py-2 px-4">
                      <div class="flex space-x-2">
                        <?php if ($booking['booking_status'] === 'pending'): ?>
                          <a href="?action=confirm&id=<?php echo $booking['id']; ?>&<?php echo http_build_query($filters); ?>" class="action-btn text-green-600 hover:text-green-800" title="Confirm Booking">
                            <i class="fas fa-check"></i>
                          </a>
                        <?php endif; ?>
                        <?php if ($booking['booking_status'] !== 'cancelled'): ?>
                          <a href="?action=cancel&id=<?php echo $booking['id']; ?>&<?php echo http_build_query($filters); ?>" class="action-btn text-red-600 hover:text-red-800 cancel-booking" title="Cancel Booking" data-id="<?php echo $booking['id']; ?>">
                            <i class="fas fa-times"></i>
                          </a>
                        <?php endif; ?>
                        <?php if ($booking['payment_status'] === 'unpaid'): ?>
                          <a href="?action=complete_payment&id=<?php echo $booking['id']; ?>&<?php echo http_build_query($filters); ?>" class="action-btn text-indigo-600 hover:text-indigo-800" title="Mark Payment as Paid">
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
    </section>
  </main>

  <!-- View Details Modal -->
  <div id="detailsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg max-w-3xl w-full mx-4 max-h-[90vh] overflow-y-auto">
      <div class="flex justify-between items-center border-b border-gray-200 px-6 py-4">
        <h3 class="text-lg font-semibold text-gray-800">Booking Details</h3>
        <button id="closeModal" class="text-gray-400 hover:text-gray-500" aria-label="Close modal">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <div id="modalContent" class="p-6">
        <div class="animate-pulse">
          <div class="h-4 bg-gray-200 rounded w-3/4 mb-4"></div>
          <div class="h-4 bg-gray-200 rounded w-1/2 mb-4"></div>
          <div class="h-4 bg-gray-200 rounded w-5/6 mb-4"></div>
          <div class="h-4 bg-gray-200 rounded w-2/3 mb-4"></div>
        </div>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Sidebar elements
      const sidebar = document.getElementById('sidebar');
      const sidebarOverlay = document.getElementById('sidebar-overlay');
      const sidebarToggle = document.getElementById('sidebarToggle');
      const sidebarClose = document.getElementById('sidebar-close');
      const dashboardHeader = document.getElementById('dashboardHeader');

      // User dropdown elements
      const userDropdownButton = document.getElementById('userDropdownButton');
      const userDropdownMenu = document.getElementById('userDropdownMenu');

      // Modal elements
      const detailsModal = document.getElementById('detailsModal');
      const closeModal = document.getElementById('closeModal');
      const modalContent = document.getElementById('modalContent');

      // Error handling for missing elements
      if (!sidebar || !sidebarOverlay || !sidebarToggle || !sidebarClose) {
        console.warn('One or more sidebar elements are missing.');
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
      if (!detailsModal || !closeModal || !modalContent) {
        console.warn('One or more modal elements are missing.');
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

      // View Details Modal
      const bookings = <?php echo json_encode($bookings); ?>;
      document.querySelectorAll('.view-details').forEach(button => {
        button.addEventListener('click', () => {
          const bookingId = button.getAttribute('data-id');
          const booking = bookings.find(b => b.id == bookingId);
          detailsModal.classList.remove('hidden');
          detailsModal.classList.add('flex');

          if (booking) {
            const html = `
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <h4 class="font-semibold text-gray-700 mb-3">Booking Information</h4>
                  <p><span class="font-medium">Booking ID:</span> ${booking.id}</p>
                  <p><span class="font-medium">Reference:</span> ${booking.booking_reference}</p>
                  <p><span class="font-medium">Booking Date:</span> ${new Date(booking.created_at).toLocaleDateString()}</p>
                  <p><span class="font-medium">Booking Status:</span> <span class="status-badge status-${booking.booking_status}">${booking.booking_status.charAt(0).toUpperCase() + booking.booking_status.slice(1)}</span></p>
                  <p><span class="font-medium">Payment Status:</span> <span class="status-badge payment-${booking.payment_status}">${booking.payment_status.charAt(0).toUpperCase() + booking.payment_status.slice(1)}</span></p>
                  <p><span class="font-medium">Total Price:</span> PKR ${parseInt(booking.total_price || 0).toLocaleString()}</p>
                </div>
                <div>
                  <h4 class="font-semibold text-gray-700 mb-3">Guest Information</h4>
                  <p><span class="font-medium">Name:</span> ${booking.user_name}</p>
                  <p><span class="font-medium">Email:</span> ${booking.user_email}</p>
                  <p><span class="font-medium">Special Requests:</span> ${booking.special_requests || 'None'}</p>
                </div>
                <div class="md:col-span-2">
                  <h4 class="font-semibold text-gray-700 mb-3">Hotel Information</h4>
                  <p><span class="font-medium">Hotel Name:</span> ${booking.hotel_name}</p>
                  <p><span class="font-medium">Location:</span> ${booking.location.charAt(0).toUpperCase() + booking.location.slice(1)}</p>
                  <p><span class="font-medium">Room ID:</span> ${booking.room_id}</p>
                  <p><span class="font-medium">Price per Night:</span> PKR ${parseInt(booking.price_per_night || 0).toLocaleString()}</p>
                  <p><span class="font-medium">Check-in Date:</span> ${new Date(booking.check_in_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
                  <p><span class="font-medium">Check-out Date:</span> ${new Date(booking.check_out_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
                  <p><span class="font-medium">Number of Nights:</span> ${booking.nights_count}</p>
                </div>
              </div>
            `;
            modalContent.innerHTML = html;
          } else {
            modalContent.innerHTML = '<p class="text-red-500">Error loading booking details.</p>';
          }
        });
      });

      // Close Modal
      closeModal.addEventListener('click', () => {
        detailsModal.classList.remove('flex');
        detailsModal.classList.add('hidden');
      });

      // Close modal when clicking outside
      detailsModal.addEventListener('click', (e) => {
        if (e.target === detailsModal) {
          detailsModal.classList.remove('flex');
          detailsModal.classList.add('hidden');
        }
      });

      // Cancel Booking Confirmation
      document.querySelectorAll('.cancel-booking').forEach(button => {
        button.addEventListener('click', (e) => {
          e.preventDefault();
          const bookingId = button.getAttribute('data-id');
          const href = button.getAttribute('href');
          Swal.fire({
            title: 'Are you sure?',
            text: 'Do you want to cancel this booking?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Cancel Booking',
            cancelButtonText: 'No'
          }).then((result) => {
            if (result.isConfirmed) {
              window.location.href = href;
            }
          });
        });
      });
    });
  </script>
</body>

</html>