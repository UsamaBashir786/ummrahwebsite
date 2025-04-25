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
  'type' => $_GET['type'] ?? '',
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
    $stmt = $conn->prepare("UPDATE transportation_bookings SET booking_status = 'confirmed' WHERE id = ?");
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
    $stmt = $conn->prepare("UPDATE transportation_bookings SET booking_status = 'cancelled' WHERE id = ?");
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
    $stmt = $conn->prepare("UPDATE transportation_bookings SET payment_status = 'completed' WHERE id = ?");
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
$sql = "SELECT tb.*, u.full_name as user_name, u.email as user_email 
        FROM transportation_bookings tb
        JOIN users u ON tb.user_id = u.id
        WHERE 1=1";

$params = [];
$types = "";

if (!empty($filters['status'])) {
  $sql .= " AND tb.booking_status = ?";
  $params[] = $filters['status'];
  $types .= "s";
}

if (!empty($filters['type'])) {
  $sql .= " AND tb.transport_type = ?";
  $params[] = $filters['type'];
  $types .= "s";
}

if (!empty($filters['search'])) {
  $search = "%" . $filters['search'] . "%";
  $sql .= " AND (tb.route_name LIKE ? OR tb.full_name LIKE ? OR u.email LIKE ?)";
  $params[] = $search;
  $params[] = $search;
  $params[] = $search;
  $types .= "sss";
}

if (!empty($filters['date_from'])) {
  $sql .= " AND tb.pickup_date >= ?";
  $params[] = $filters['date_from'];
  $types .= "s";
}

if (!empty($filters['date_to'])) {
  $sql .= " AND tb.pickup_date <= ?";
  $params[] = $filters['date_to'];
  $types .= "s";
}

$sql .= " ORDER BY tb.created_at DESC";

// Log the query for debugging
error_log("Transportation Bookings Query: " . $sql);

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
    error_log("Number of transportation bookings retrieved: " . count($bookings));
  }
  $stmt->close();
}

// Get booking statistics
$stats = [
  'total' => 0,
  'pending' => 0,
  'confirmed' => 0,
  'cancelled' => 0,
  'total_revenue' => 0,
  'taxi_bookings' => 0,
  'rentacar_bookings' => 0
];

$stats_query = "SELECT 
                   COUNT(*) as total,
                   SUM(CASE WHEN booking_status = 'pending' THEN 1 ELSE 0 END) as pending,
                   SUM(CASE WHEN booking_status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
                   SUM(CASE WHEN booking_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                   COALESCE(SUM(price), 0) as total_revenue,
                   SUM(CASE WHEN transport_type = 'taxi' THEN 1 ELSE 0 END) as taxi_bookings,
                   SUM(CASE WHEN transport_type = 'rentacar' THEN 1 ELSE 0 END) as rentacar_bookings
                FROM transportation_bookings";

// Log the stats query for debugging
error_log("Transportation Stats Query: " . $stats_query);

$result = $conn->query($stats_query);
if ($result) {
  $stats = $result->fetch_assoc();
  // Ensure all stats have default values
  $stats['total_revenue'] = $stats['total_revenue'] ?? 0;
  $stats['taxi_bookings'] = $stats['taxi_bookings'] ?? 0;
  $stats['rentacar_bookings'] = $stats['rentacar_bookings'] ?? 0;
} else {
  error_log("Stats query failed: " . $conn->error);
  $message = "Error fetching statistics: " . $conn->error;
  $message_type = "error";
}

// Get popular vehicle types
$vehicle_query = "SELECT 
                     vehicle_type, 
                     COALESCE(COUNT(*), 0) as count
                  FROM transportation_bookings 
                  WHERE booking_status != 'cancelled'
                  GROUP BY vehicle_type 
                  ORDER BY count DESC 
                  LIMIT 5";

// Log the vehicle query for debugging
error_log("Popular Vehicle Query: " . $vehicle_query);

$popular_vehicles = [];
$result = $conn->query($vehicle_query);
if ($result) {
  while ($row = $result->fetch_assoc()) {
    $popular_vehicles[$row['vehicle_type']] = $row['count'];
  }
} else {
  error_log("Popular vehicle query failed: " . $conn->error);
  $message = "Error fetching vehicle statistics: " . $conn->error;
  $message_type = "error";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Transportation Bookings | UmrahFlights Admin</title>
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Font Awesome -->
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

    .transport-type-badge {
      border-radius: 9999px;
      padding: 0.25rem 0.75rem;
      font-size: 0.75rem;
      font-weight: 600;
    }

    .transport-taxi {
      background-color: #DBEAFE;
      color: #1E40AF;
    }

    .transport-rentacar {
      background-color: #E0E7FF;
      color: #4338CA;
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
          <i class="fas fa-car text-blue-600 mr-2"></i> Transportation Bookings
        </h1>
        <p class="text-gray-600">Manage all transportation bookings</p>
      </div>
      <div class="mt-4 md:mt-0">
        <a href="view-transportation.php" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
          <i class="fas fa-car mr-2"></i> View Transportation
        </a>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <div class="bg-white rounded-lg shadow p-4 flex items-start">
        <div class="bg-blue-100 rounded-lg p-3 mr-4">
          <i class="fas fa-calendar-check text-blue-600 text-xl"></i>
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
          <h3 class="text-gray-500 text-sm font-medium">Total Revenue</h3>
          <p class="text-2xl font-bold text-gray-800">PKR <?php echo formatNumber($stats['total_revenue'] ?? 0); ?></p>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-4 flex items-start">
        <div class="bg-yellow-100 rounded-lg p-3 mr-4">
          <i class="fas fa-car text-yellow-600 text-xl"></i>
        </div>
        <div>
          <h3 class="text-gray-500 text-sm font-medium">Transport Types</h3>
          <p class="text-sm font-medium text-gray-800">
            Taxi: <?php echo $stats['taxi_bookings'] ?? 0; ?>,
            Rent-a-car: <?php echo $stats['rentacar_bookings'] ?? 0; ?>
          </p>
        </div>
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
          </select>
        </div>
        <div>
          <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Transport Type</label>
          <select id="type" name="type" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">All Types</option>
            <option value="taxi" <?php echo $filters['type'] === 'taxi' ? 'selected' : ''; ?>>Taxi</option>
            <option value="rentacar" <?php echo $filters['type'] === 'rentacar' ? 'selected' : ''; ?>>Rent-a-car</option>
          </select>
        </div>
        <div>
          <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
          <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($filters['search']); ?>" placeholder="Route, customer name, email" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
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
          <a href="booked-transportation.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg">
            <i class="fas fa-sync-alt mr-2"></i> Reset
          </a>
        </div>
      </form>
    </div>

    <!-- Bookings Table -->
    <div class="bg-white rounded-lg shadow">
      <div class="p-4 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800">Transportation Bookings</h2>
      </div>
      <div class="overflow-x-auto p-4">
        <table id="bookingsTable" class="w-full stripe hover">
          <thead>
            <tr>
              <th>ID</th>
              <th>Customer</th>
              <th>Type</th>
              <th>Route</th>
              <th>Vehicle</th>
              <th>Pickup Date</th>
              <th>Price</th>
              <th>Status</th>
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
                  <td>
                    <span class="transport-type-badge <?php echo 'transport-' . $booking['transport_type']; ?>">
                      <?php echo ucfirst($booking['transport_type']); ?>
                    </span>
                  </td>
                  <td>
                    <div class="font-medium"><?php echo htmlspecialchars($booking['route_name']); ?></div>
                  </td>
                  <td><?php echo htmlspecialchars($booking['vehicle_type'] ?? ''); ?></td>
                  <td>
                    <div><?php echo date('M d, Y', strtotime($booking['pickup_date'])); ?></div>
                    <div class="text-sm text-gray-500"><?php echo date('h:i A', strtotime($booking['pickup_time'] ?? '00:00:00')); ?></div>
                  </td>
                  <td class="font-medium">PKR <?php echo formatNumber($booking['price'] ?? 0); ?></td>
                  <td>
                    <span class="status-badge <?php echo 'status-' . $booking['booking_status']; ?>">
                      <?php echo ucfirst($booking['booking_status']); ?>
                    </span>
                  </td>
                  <td>
                    <div class="flex space-x-2">
                      <?php if ($booking['booking_status'] === 'pending'): ?>
                        <a href="?action=confirm&id=<?php echo $booking['id']; ?>&<?php echo http_build_query($filters); ?>" class="action-btn text-green-600 hover:text-green-800" title="Confirm Booking">
                          <i class="fas fa-check"></i>
                        </a>
                      <?php endif; ?>

                      <?php if ($booking['booking_status'] !== 'cancelled'): ?>
                        <a href="?action=cancel&id=<?php echo $booking['id']; ?>&<?php echo http_build_query($filters); ?>" class="action-btn text-red-600 hover:text-red-800" title="Cancel Booking" onclick="return confirm('Are you sure you want to cancel this booking?');">
                          <i class="fas fa-times"></i>
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
            "targets": 8
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
                <p><span class="font-medium">Transport Type:</span> <span class="transport-type-badge transport-${booking.transport_type}">${booking.transport_type.charAt(0).toUpperCase() + booking.transport_type.slice(1)}</span></p>
                <p><span class="font-medium">Route:</span> ${booking.route_name}</p>
                <p><span class="font-medium">Vehicle Type:</span> ${booking.vehicle_type || 'N/A'}</p>
                <p><span class="font-medium">Price:</span> PKR ${parseInt(booking.price || 0).toLocaleString()}</p>
                <p><span class="font-medium">Booking Status:</span> <span class="status-badge status-${booking.booking_status}">${booking.booking_status.charAt(0).toUpperCase() + booking.booking_status.slice(1)}</span></p>
              </div>
              <div>
                <h4 class="font-semibold text-gray-700 mb-3">Customer Information</h4>
                <p><span class="font-medium">Name:</span> ${booking.full_name}</p>
                <p><span class="font-medium">Email:</span> ${booking.email}</p>
                <p><span class="font-medium">Phone:</span> ${booking.phone || 'N/A'}</p>
              </div>
              <div class="md:col-span-2">
                <h4 class="font-semibold text-gray-700 mb-3">Trip Details</h4>
                <p><span class="font-medium">Pickup Date:</span> ${new Date(booking.pickup_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
                <p><span class="font-medium">Pickup Time:</span> ${new Date('1970-01-01T' + (booking.pickup_time || '00:00:00')).toLocaleTimeString('en-US', { hour: 'numeric', minute: 'numeric', hour12: true })}</p>
                <p><span class="font-medium">Pickup Location:</span> ${booking.pickup_location || 'N/A'}</p>
                <p><span class="font-medium">Additional Notes:</span> ${booking.additional_notes || 'None'}</p>
                <p><span class="font-medium">Created:</span> ${new Date(booking.created_at).toLocaleString()}</p>
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