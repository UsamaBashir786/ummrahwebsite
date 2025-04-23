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
          throw new Exception("Error updating booking status");
        }
        $stmt->close();

        // Update room status to available
        $stmt = $conn->prepare("UPDATE hotel_rooms SET status = 'available' WHERE hotel_id = ? AND room_id = ?");
        $stmt->bind_param("is", $booking_data['hotel_id'], $booking_data['room_id']);
        if (!$stmt->execute()) {
          throw new Exception("Error updating room status");
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

// Prepare and execute query
$stmt = $conn->prepare($sql);
if (!empty($params)) {
  $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Fetch all bookings
if ($result) {
  while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
  }
}
$stmt->close();

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

$result = $conn->query("SELECT 
                          COUNT(*) as total,
                          SUM(CASE WHEN booking_status = 'pending' THEN 1 ELSE 0 END) as pending,
                          SUM(CASE WHEN booking_status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
                          SUM(CASE WHEN booking_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                          SUM(CASE WHEN payment_status = 'unpaid' THEN 1 ELSE 0 END) as unpaid,
                          SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) as paid,
                          SUM(CASE WHEN payment_status = 'paid' THEN total_price ELSE 0 END) as total_revenue
                        FROM hotel_bookings");
if ($result) {
  $stats = $result->fetch_assoc();
}

// Get location breakdown
$result = $conn->query("SELECT 
                          SUM(CASE WHEN h.location = 'makkah' THEN 1 ELSE 0 END) as makkah_bookings,
                          SUM(CASE WHEN h.location = 'madinah' THEN 1 ELSE 0 END) as madinah_bookings
                        FROM hotel_bookings hb
                        JOIN hotels h ON hb.hotel_id = h.id
                        WHERE hb.booking_status != 'cancelled'");
if ($result) {
  $location_stats = $result->fetch_assoc();
  $stats['makkah_bookings'] = $location_stats['makkah_bookings'];
  $stats['madinah_bookings'] = $location_stats['madinah_bookings'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Hotel Bookings | UmrahFlights Admin</title>
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

    .payment-paid {
      background-color: #D1FAE5;
      color: #047857;
    }

    .payment-unpaid {
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
          <i class="fas fa-hotel text-blue-600 mr-2"></i> Hotel Bookings
        </h1>
        <p class="text-gray-600">Manage all hotel bookings</p>
      </div>
      <div class="mt-4 md:mt-0">
        <a href="view-hotels.php" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
          <i class="fas fa-hotel mr-2"></i> View Hotels
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
        <div class="bg-green-100 rounded-lg p-3 mr-4">
          <i class="fas fa-money-bill text-green-600 text-xl"></i>
        </div>
        <div>
          <h3 class="text-gray-500 text-sm font-medium">Revenue</h3>
          <p class="text-2xl font-bold text-gray-800">PKR <?php echo number_format($stats['total_revenue'], 0); ?></p>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-4 flex items-start">
        <div class="bg-yellow-100 rounded-lg p-3 mr-4">
          <i class="fas fa-map-marker-alt text-yellow-600 text-xl"></i>
        </div>
        <div>
          <h3 class="text-gray-500 text-sm font-medium">Locations</h3>
          <p class="text-md font-medium text-gray-800">Makkah: <?php echo $stats['makkah_bookings']; ?>, Madinah: <?php echo $stats['madinah_bookings']; ?></p>
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
      <form action="" method="GET" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
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
            <option value="unpaid" <?php echo $filters['payment'] === 'unpaid' ? 'selected' : ''; ?>>Unpaid</option>
            <option value="paid" <?php echo $filters['payment'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
          </select>
        </div>
        <div>
          <label for="location" class="block text-sm font-medium text-gray-700 mb-1">Location</label>
          <select id="location" name="location" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">All Locations</option>
            <option value="makkah" <?php echo $filters['location'] === 'makkah' ? 'selected' : ''; ?>>Makkah</option>
            <option value="madinah" <?php echo $filters['location'] === 'madinah' ? 'selected' : ''; ?>>Madinah</option>
          </select>
        </div>
        <div>
          <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
          <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($filters['search']); ?>" placeholder="Hotel name, reference, guest" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        </div>
        <div>
          <label for="check_in_from" class="block text-sm font-medium text-gray-700 mb-1">Check-in From</label>
          <input type="date" id="check_in_from" name="check_in_from" value="<?php echo htmlspecialchars($filters['check_in_from']); ?>" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        </div>
        <div>
          <label for="check_in_to" class="block text-sm font-medium text-gray-700 mb-1">Check-in To</label>
          <input type="date" id="check_in_to" name="check_in_to" value="<?php echo htmlspecialchars($filters['check_in_to']); ?>" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        </div>
        <div class="md:col-span-3 lg:col-span-6 flex justify-end">
          <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg mr-2">
            <i class="fas fa-filter mr-2"></i> Apply Filters
          </button>
          <a href="booked-hotels.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg">
            <i class="fas fa-sync-alt mr-2"></i> Reset
          </a>
        </div>
      </form>
    </div>

    <!-- Bookings Table -->
    <div class="bg-white rounded-lg shadow">
      <div class="p-4 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800">Hotel Bookings</h2>
      </div>
      <div class="overflow-x-auto p-4">
        <table id="bookingsTable" class="w-full stripe hover">
          <thead>
            <tr>
              <th>ID</th>
              <th>Guest</th>
              <th>Hotel</th>
              <th>Room</th>
              <th>Check-in / Check-out</th>
              <th>Nights</th>
              <th>Reference</th>
              <th>Total Price</th>
              <th>Booking Status</th>
              <th>Payment Status</th>
              <th>Actions</th>
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
                  <td class="py-3"><?php echo $booking['id']; ?></td>
                  <td>
                    <div class="font-medium"><?php echo htmlspecialchars($booking['user_name']); ?></div>
                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($booking['user_email']); ?></div>
                  </td>
                  <td>
                    <div class="font-medium"><?php echo htmlspecialchars($booking['hotel_name']); ?></div>
                    <div class="text-sm text-gray-500 capitalize"><?php echo htmlspecialchars($booking['location']); ?></div>
                  </td>
                  <td class="text-center"><?php echo htmlspecialchars($booking['room_id']); ?></td>
                  <td>
                    <div>In: <?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?></div>
                    <div>Out: <?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?></div>
                  </td>
                  <td class="text-center"><?php echo $booking['nights_count']; ?></td>
                  <td><?php echo htmlspecialchars($booking['booking_reference']); ?></td>
                  <td class="font-medium">PKR <?php echo number_format($booking['total_price'], 0); ?></td>
                  <td>
                    <span class="status-badge <?php echo 'status-' . $booking['booking_status']; ?>">
                      <?php echo ucfirst($booking['booking_status']); ?>
                    </span>
                  </td>
                  <td>
                    <span class="status-badge <?php echo 'payment-' . $booking['payment_status']; ?>">
                      <?php echo ucfirst($booking['payment_status']); ?>
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
                        <a href="?action=cancel&id=<?php echo $booking['id']; ?>&<?php echo http_build_query($filters); ?>" class="action-btn text-red-600 hover:text-red-800" title="Cancel Booking" onclick="return confirm('Are you sure you want to cancel this booking?')">
                          <i class="fas fa-times"></i>
                        </a>
                      <?php endif; ?>

                      <?php if ($booking['payment_status'] === 'unpaid'): ?>
                        <a href="?action=complete_payment&id=<?php echo $booking['id']; ?>&<?php echo http_build_query($filters); ?>" class="action-btn text-blue-600 hover:text-blue-800" title="Mark Payment as Paid">
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
                <p><span class="font-medium">Reference:</span> ${booking.booking_reference}</p>
                <p><span class="font-medium">Booking Date:</span> ${new Date(booking.created_at).toLocaleDateString()}</p>
                <p><span class="font-medium">Booking Status:</span> <span class="status-badge status-${booking.booking_status}">${booking.booking_status.charAt(0).toUpperCase() + booking.booking_status.slice(1)}</span></p>
                <p><span class="font-medium">Payment Status:</span> <span class="status-badge payment-${booking.payment_status}">${booking.payment_status.charAt(0).toUpperCase() + booking.payment_status.slice(1)}</span></p>
                <p><span class="font-medium">Total Price:</span> PKR ${parseInt(booking.total_price).toLocaleString()}</p>
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
                <p><span class="font-medium">Price per Night:</span> PKR ${parseInt(booking.price_per_night).toLocaleString()}</p>
                <p><span class="font-medium">Check-in Date:</span> ${new Date(booking.check_in_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
                <p><span class="font-medium">Check-out Date:</span> ${new Date(booking.check_out_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
                <p><span class="font-medium">Number of Nights:</span> ${booking.nights_count}</p>
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