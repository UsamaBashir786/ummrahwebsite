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
  'package_type' => $_GET['package_type'] ?? '',
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
    if (!$conn) {
      error_log("Database connection failed in confirm action.");
      $message = "Database connection error. Please try again later.";
      $message_type = "error";
    } else {
      $stmt = $conn->prepare("UPDATE package_bookings SET booking_status = 'confirmed' WHERE id = ?");
      if ($stmt === false) {
        error_log("Prepare failed in confirm: " . $conn->error);
        $message = "Database error occurred. Please try again later.";
        $message_type = "error";
      } else {
        $stmt->bind_param("i", $booking_id);
        if ($stmt->execute()) {
          $message = "Booking #$booking_id has been confirmed successfully.";
          $message_type = "success";
        } else {
          error_log("Execute failed in confirm: " . $stmt->error);
          $message = "Error confirming booking: " . $stmt->error;
          $message_type = "error";
        }
        $stmt->close();
      }
    }
  } elseif ($action === 'cancel') {
    if (!$conn) {
      error_log("Database connection failed in cancel action.");
      $message = "Database connection error. Please try again later.";
      $message_type = "error";
    } else {
      $stmt = $conn->prepare("UPDATE package_bookings SET booking_status = 'cancelled' WHERE id = ?");
      if ($stmt === false) {
        error_log("Prepare failed in cancel: " . $conn->error);
        $message = "Database error occurred. Please try again later.";
        $message_type = "error";
      } else {
        $stmt->bind_param("i", $booking_id);
        if ($stmt->execute()) {
          $message = "Booking #$booking_id has been cancelled.";
          $message_type = "success";
        } else {
          error_log("Execute failed in cancel: " . $stmt->error);
          $message = "Error cancelling booking: " . $stmt->error;
          $message_type = "error";
        }
        $stmt->close();
      }
    }
  } elseif ($action === 'complete_payment') {
    if (!$conn) {
      error_log("Database connection failed in complete_payment action.");
      $message = "Database connection error. Please try again later.";
      $message_type = "error";
    } else {
      $stmt = $conn->prepare("UPDATE package_bookings SET payment_status = 'paid' WHERE id = ?");
      if ($stmt === false) {
        error_log("Prepare failed in complete_payment: " . $conn->error);
        $message = "Database error occurred. Please try again later.";
        $message_type = "error";
      } else {
        $stmt->bind_param("i", $booking_id);
        if ($stmt->execute()) {
          $message = "Payment for booking #$booking_id has been marked as paid.";
          $message_type = "success";
        } else {
          error_log("Execute failed in complete_payment: " . $stmt->error);
          $message = "Error updating payment status: " . $stmt->error;
          $message_type = "error";
        }
        $stmt->close();
      }
    }
  }
}

// Build the query with filters
$sql = "SELECT pb.*, up.title as package_title, up.package_type, up.price as package_price, 
               u.full_name as user_name, u.email as user_email 
        FROM package_bookings pb
        JOIN umrah_packages up ON pb.package_id = up.id
        JOIN users u ON pb.user_id = u.id
        WHERE 1=1";

$params = [];
$types = "";

if (!empty($filters['status'])) {
  $sql .= " AND pb.booking_status = ?";
  $params[] = $filters['status'];
  $types .= "s";
}

if (!empty($filters['payment'])) {
  $sql .= " AND pb.payment_status = ?";
  $params[] = $filters['payment'];
  $types .= "s";
}

if (!empty($filters['package_type'])) {
  $sql .= " AND up.package_type = ?";
  $params[] = $filters['package_type'];
  $types .= "s";
}

if (!empty($filters['search'])) {
  $search = "%" . $filters['search'] . "%";
  $sql .= " AND (up.title LIKE ? OR pb.booking_reference LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
  $params[] = $search;
  $params[] = $search;
  $params[] = $search;
  $params[] = $search;
  $types .= "ssss";
}

if (!empty($filters['date_from'])) {
  $sql .= " AND pb.travel_date >= ?";
  $params[] = $filters['date_from'];
  $types .= "s";
}

if (!empty($filters['date_to'])) {
  $sql .= " AND pb.travel_date <= ?";
  $params[] = $filters['date_to'];
  $types .= "s";
}

$sql .= " ORDER BY pb.created_at DESC";

// Log the query for debugging
error_log("Package Bookings Query: " . $sql);

// Prepare and execute query
if (!$conn) {
  error_log("Database connection failed when fetching bookings.");
  $message = "Database connection error. Please try again later.";
  $message_type = "error";
} else {
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
}

// Get booking statistics
$stats = [
  'total' => 0,
  'pending' => 0,
  'confirmed' => 0,
  'cancelled' => 0,
  'payment_pending' => 0,
  'payment_paid' => 0,
  'payment_refunded' => 0,
  'total_revenue' => 0,
  'total_travelers' => 0,
];

$stats_query = "SELECT 
                   COUNT(*) as total,
                   SUM(CASE WHEN booking_status = 'pending' THEN 1 ELSE 0 END) as pending,
                   SUM(CASE WHEN booking_status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
                   SUM(CASE WHEN booking_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                   SUM(CASE WHEN payment_status = 'pending' THEN 1 ELSE 0 END) as payment_pending,
                   SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) as payment_paid,
                   SUM(CASE WHEN payment_status = 'refunded' THEN 1 ELSE 0 END) as payment_refunded,
                   COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN total_price ELSE 0 END), 0) as total_revenue,
                   COALESCE(SUM(num_travelers), 0) as total_travelers
                FROM package_bookings";

// Log the stats query for debugging
error_log("Package Stats Query: " . $stats_query);

if (!$conn) {
  error_log("Database connection failed when fetching stats.");
  $message = "Database connection error. Please try again later.";
  $message_type = "error";
} else {
  $result = $conn->query($stats_query);
  if ($result) {
    $stats = $result->fetch_assoc();
  } else {
    error_log("Stats query failed: " . $conn->error);
    $message = "Error fetching statistics: " . $conn->error;
    $message_type = "error";
  }
}

// Get package type breakdown
$package_query = "SELECT 
                     up.package_type,
                     COALESCE(COUNT(*), 0) as count
                  FROM package_bookings pb
                  JOIN umrah_packages up ON pb.package_id = up.id
                  WHERE pb.booking_status != 'cancelled'
                  GROUP BY up.package_type";

// Log the package query for debugging
error_log("Package Type Query: " . $package_query);

$package_stats = ['single' => 0, 'group' => 0, 'vip' => 0];
if (!$conn) {
  error_log("Database connection failed when fetching package stats.");
  $message = "Database connection error. Please try again later.";
  $message_type = "error";
} else {
  $result = $conn->query($package_query);
  if ($result) {
    while ($row = $result->fetch_assoc()) {
      $package_stats[$row['package_type']] = $row['count'];
    }
  } else {
    error_log("Package type query failed: " . $conn->error);
    $message = "Error fetching package type statistics: " . $conn->error;
    $message_type = "error";
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Package Bookings | UmrahFlights</title>
  <!-- Tailwind CSS -->
  <link rel="stylesheet" href="../src/output.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    .status-badge,
    .package-type-badge {
      @apply inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold;
    }

    .status-pending,
    .payment-pending {
      @apply bg-yellow-100 text-yellow-800;
    }

    .status-confirmed,
    .status-completed,
    .payment-paid {
      @apply bg-green-100 text-green-800;
    }

    .status-cancelled,
    .payment-refunded {
      @apply bg-red-100 text-red-800;
    }

    .package-single {
      @apply bg-indigo-100 text-indigo-800;
    }

    .package-group {
      @apply bg-purple-100 text-purple-800;
    }

    .package-vip {
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
          <h4 id="dashboardHeader" class="text-lg font-semibold text-gray-800 cursor-pointer hover:text-indigo-600">Package Bookings</h4>
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
    <section class="bg-white shadow-lg rounded-lg p-6" aria-label="Package bookings">
      <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-6">
        <div>
          <h2 class="text-2xl font-bold text-gray-800 flex items-center">
            <i class="fas fa-box text-indigo-600 mr-2"></i> Package Bookings
          </h2>
          <p class="text-gray-600">Manage all Umrah package bookings</p>
        </div>
        <div class="mt-4 md:mt-0">
          <a href="view-packages.php" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition-colors">
            <i class="fas fa-box mr-2"></i> View Packages
          </a>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
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
            <i class="fas fa-users text-green-600 text-xl"></i>
          </div>
          <div>
            <h3 class="text-gray-500 text-sm font-medium">Total Travelers</h3>
            <p class="text-2xl font-bold text-gray-800"><?php echo $stats['total_travelers'] ?? 0; ?></p>
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
        <div class="bg-white rounded-lg shadow p-4 flex items-start">
          <div class="bg-yellow-100 rounded-lg p-3 mr-4">
            <i class="fas fa-tag text-yellow-600 text-xl"></i>
          </div>
          <div>
            <h3 class="text-gray-500 text-sm font-medium">Package Types</h3>
            <p class="text-sm font-medium text-gray-800">
              Single: <?php echo $package_stats['single'] ?? 0; ?>,
              Group: <?php echo $package_stats['group'] ?? 0; ?>,
              VIP: <?php echo $package_stats['vip'] ?? 0; ?>
            </p>
          </div>
        </div>
      </div>

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
              <option value="completed" <?php echo $filters['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
            </select>
          </div>
          <div>
            <label for="payment" class="block text-sm font-medium text-gray-700 mb-1">Payment Status</label>
            <select id="payment" name="payment" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
              <option value="">All Payments</option>
              <option value="pending" <?php echo $filters['payment'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
              <option value="paid" <?php echo $filters['payment'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
              <option value="refunded" <?php echo $filters['payment'] === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
            </select>
          </div>
          <div>
            <label for="package_type" class="block text-sm font-medium text-gray-700 mb-1">Package Type</label>
            <select id="package_type" name="package_type" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
              <option value="">All Types</option>
              <option value="single" <?php echo $filters['package_type'] === 'single' ? 'selected' : ''; ?>>Single</option>
              <option value="group" <?php echo $filters['package_type'] === 'group' ? 'selected' : ''; ?>>Group</option>
              <option value="vip" <?php echo $filters['package_type'] === 'vip' ? 'selected' : ''; ?>>VIP</option>
            </select>
          </div>
          <div>
            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
            <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($filters['search']); ?>" placeholder="Package title, guest, reference" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
          </div>
          <div>
            <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">Travel Date From</label>
            <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($filters['date_from']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
          </div>
          <div>
            <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">Travel Date To</label>
            <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($filters['date_to']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
          </div>
          <div class="md:col-span-3 lg:col-span-6 flex justify-end">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md mr-2">
              <i class="fas fa-filter mr-2"></i> Apply Filters
            </button>
            <a href="booked-packages.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-md">
              <i class="fas fa-sync-alt mr-2"></i> Reset
            </a>
          </div>
        </form>
      </div>

      <!-- Bookings Table -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-4 border-b border-gray-200">
          <h3 class="text-lg font-semibold text-gray-800">Package Bookings</h3>
        </div>
        <div class="w-full overflow-x-auto">
          <table class="min-w-full table-auto whitespace-nowrap">
            <thead>
              <tr class="bg-gray-100 text-xs font-semibold text-gray-600 uppercase tracking-wider">
                <th class="px-6 py-3 text-left">ID</th>
                <th class="px-6 py-3 text-left">Guest</th>
                <th class="px-6 py-3 text-left">Package</th>
                <th class="px-6 py-3 text-left">Type</th>
                <th class="px-6 py-3 text-left">Travel Date</th>
                <th class="px-6 py-3 text-center">Travelers</th>
                <th class="px-6 py-3 text-left">Reference</th>
                <th class="px-6 py-3 text-left">Total Price</th>
                <th class="px-6 py-3 text-left">Booking Status</th>
                <th class="px-6 py-3 text-left">Payment Status</th>
                <th class="px-6 py-3 text-center">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 text-sm">
              <?php if (empty($bookings)): ?>
                <tr>
                  <td colspan="11" class="text-center py-4 text-gray-500">No bookings found</td>
                </tr>
              <?php else: ?>
                <?php foreach ($bookings as $booking): ?>
                  <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3"><?php echo $booking['id']; ?></td>
                    <td class="px-6 py-3">
                      <div class="font-medium"><?php echo htmlspecialchars($booking['user_name']); ?></div>
                      <div class="text-gray-500 text-xs"><?php echo htmlspecialchars($booking['user_email']); ?></div>
                    </td>
                    <td class="px-6 py-3"><?php echo htmlspecialchars($booking['package_title']); ?></td>
                    <td class="px-6 py-3">
                      <span class="inline-block px-2 py-1 rounded bg-blue-100 text-blue-700 text-xs font-medium">
                        <?php echo ucfirst($booking['package_type']); ?>
                      </span>
                    </td>
                    <td class="px-6 py-3"><?php echo date('M d, Y', strtotime($booking['travel_date'])); ?></td>
                    <td class="px-6 py-3 text-center"><?php echo $booking['num_travelers'] ?? 0; ?></td>
                    <td class="px-6 py-3"><?php echo htmlspecialchars($booking['booking_reference']); ?></td>
                    <td class="px-6 py-3 font-medium">PKR <?php echo number_format($booking['total_price'] ?? 0, 0); ?></td>
                    <td class="px-6 py-3">
                      <span class="inline-block px-2 py-1 rounded bg-yellow-100 text-yellow-700 text-xs font-medium">
                        <?php echo ucfirst($booking['booking_status']); ?>
                      </span>
                    </td>
                    <td class="px-6 py-3">
                      <span class="inline-block px-2 py-1 rounded bg-green-100 text-green-700 text-xs font-medium">
                        <?php echo ucfirst($booking['payment_status']); ?>
                      </span>
                    </td>
                    <td class="px-6 py-3 text-center">
                      <div class="flex justify-center space-x-2">
                        <?php if ($booking['booking_status'] === 'pending'): ?>
                          <a href="?action=confirm&id=<?php echo $booking['id']; ?>&<?php echo http_build_query($filters); ?>" title="Confirm Booking" class="text-green-600 hover:text-green-800">
                            <i class="fas fa-check"></i>
                          </a>
                        <?php endif; ?>
                        <?php if ($booking['booking_status'] !== 'cancelled'): ?>
                          <a href="?action=cancel&id=<?php echo $booking['id']; ?>&<?php echo http_build_query($filters); ?>" title="Cancel Booking" class="text-red-600 hover:text-red-800">
                            <i class="fas fa-times"></i>
                          </a>
                        <?php endif; ?>
                        <?php if ($booking['payment_status'] === 'pending'): ?>
                          <a href="?action=complete_payment&id=<?php echo $booking['id']; ?>&<?php echo http_build_query($filters); ?>" title="Mark Paid" class="text-indigo-600 hover:text-indigo-800">
                            <i class="fas fa-dollar-sign"></i>
                          </a>
                        <?php endif; ?>
                        <button type="button" title="View Details" class="text-gray-600 hover:text-gray-800 view-details" data-id="<?php echo $booking['id']; ?>">
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
                  <p><span class="font-medium">Number of Travelers:</span> ${booking.num_travelers || 0}</p>
                  <p><span class="font-medium">Travel Date:</span> ${new Date(booking.travel_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
                  <p><span class="font-medium">Special Requests:</span> ${booking.special_requests || 'None'}</p>
                </div>
                <div class="md:col-span-2">
                  <h4 class="font-semibold text-gray-700 mb-3">Package Details</h4>
                  <p><span class="font-medium">Package Title:</span> ${booking.package_title}</p>
                  <p><span class="font-medium">Package Type:</span> <span class="package-type-badge package-${booking.package_type}">${booking.package_type.charAt(0).toUpperCase() + booking.package_type.slice(1)}</span></p>
                  <p><span class="font-medium">Price Per Person:</span> PKR ${parseInt(booking.package_price || 0).toLocaleString()}</p>
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