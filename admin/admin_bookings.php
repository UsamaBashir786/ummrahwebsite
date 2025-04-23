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
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : '';
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : '';
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($current_page - 1) * $per_page;
$total_bookings = 0;

// Build query with filters
$query = "SELECT b.id, b.user_id, b.package_id, b.created_at, b.booking_status as status, 
         u.full_name, u.email, u.phone,
         p.title AS package_title, p.package_type, p.price AS package_price
         FROM package_bookings b
         LEFT JOIN users u ON b.user_id = u.id
         LEFT JOIN umrah_packages p ON b.package_id = p.id
         WHERE 1=1";
$count_query = "SELECT COUNT(*) as total FROM package_bookings b
               LEFT JOIN users u ON b.user_id = u.id
               LEFT JOIN umrah_packages p ON b.package_id = p.id
               WHERE 1=1";
$params = [];
$types = "";

// Apply search filter
if (!empty($search_term)) {
  $query .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR CAST(b.id AS CHAR) LIKE ?)";
  $count_query .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR CAST(b.id AS CHAR) LIKE ?)";
  $search_param = "%$search_term%";
  $params[] = $search_param;
  $params[] = $search_param;
  $params[] = $search_param;
  $types .= "sss";
}

// Apply date filter
if (!empty($date_filter)) {
  switch ($date_filter) {
    case 'today':
      $query .= " AND DATE(b.created_at) = CURDATE()";
      $count_query .= " AND DATE(b.created_at) = CURDATE()";
      break;
    case 'last7days':
      $query .= " AND b.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
      $count_query .= " AND b.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
      break;
    case 'last30days':
      $query .= " AND b.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
      $count_query .= " AND b.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
      break;
    case 'thismonth':
      $query .= " AND MONTH(b.created_at) = MONTH(CURDATE()) AND YEAR(b.created_at) = YEAR(CURDATE())";
      $count_query .= " AND MONTH(b.created_at) = MONTH(CURDATE()) AND YEAR(b.created_at) = YEAR(CURDATE())";
      break;
  }
}

// Apply status filter
if (!empty($status_filter)) {
  $query .= " AND b.booking_status = ?";
  $count_query .= " AND b.booking_status = ?";
  $params[] = $status_filter;
  $types .= "s";
}

// Order by most recent
$query .= " ORDER BY b.created_at DESC LIMIT ?, ?";
$params[] = $offset;
$params[] = $per_page;
$types .= "ii";

// Get total count for pagination
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
  // Remove the last two parameters (offset and limit) which are not needed for count
  $count_params = array_slice($params, 0, -2);
  $count_types = substr($types, 0, -2);
  if (!empty($count_params)) {
    $count_stmt->bind_param($count_types, ...$count_params);
  }
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$count_row = $count_result->fetch_assoc();
$total_bookings = $count_row['total'];
$total_pages = ceil($total_bookings / $per_page);
$count_stmt->close();

// Get bookings with pagination
$stmt = $conn->prepare($query);
if (!empty($params)) {
  $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $booking_id = $row['id'];

  // Check if hotel is assigned
  $hotel_assigned = false;
  $hotel_stmt = $conn->prepare("SELECT id FROM hotel_bookings WHERE user_id = ?");
  $hotel_stmt->bind_param("i", $row['user_id']);
  $hotel_stmt->execute();
  if ($hotel_stmt->get_result()->num_rows > 0) {
    $hotel_assigned = true;
  }
  $hotel_stmt->close();

  // Check if flight is assigned
  $flight_assigned = false;
  $flight_stmt = $conn->prepare("SELECT id FROM flight_bookings WHERE user_id = ?");
  $flight_stmt->bind_param("i", $row['user_id']);
  $flight_stmt->execute();
  if ($flight_stmt->get_result()->num_rows > 0) {
    $flight_assigned = true;
  }
  $flight_stmt->close();

  // Check if transportation is assigned
  $transport_assigned = false;
  $transport_stmt = $conn->prepare("SELECT id FROM transportation_bookings WHERE user_id = ?");
  $transport_stmt->bind_param("i", $row['user_id']);
  $transport_stmt->execute();
  if ($transport_stmt->get_result()->num_rows > 0) {
    $transport_assigned = true;
  }
  $transport_stmt->close();

  // Add assignment status to the row data
  $row['hotel_assigned'] = $hotel_assigned;
  $row['flight_assigned'] = $flight_assigned;
  $row['transport_assigned'] = $transport_assigned;

  $bookings[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Bookings | Admin Panel</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <style>
    .badge {
      @apply inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium;
    }

    .badge-success {
      @apply bg-green-100 text-green-800;
    }

    .badge-pending {
      @apply bg-yellow-100 text-yellow-800;
    }

    .badge-danger {
      @apply bg-red-100 text-red-800;
    }

    .btn-primary {
      @apply bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded transition-colors;
    }

    .btn-success {
      @apply bg-green-500 hover:bg-green-600 text-white font-medium py-2 px-4 rounded transition-colors;
    }

    .btn-secondary {
      @apply bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2 px-4 rounded transition-colors;
    }
  </style>
</head>

<body class="bg-gray-100 min-h-screen">
  <?php include 'includes/sidebar.php'; ?>

  <div class="ml-0 md:ml-64 p-6">
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
          <i class="fas fa-clipboard-list text-blue-500 mr-2"></i>Manage Bookings
        </h1>
        <a href="index.php" class="btn-secondary">
          <i class="fas fa-home mr-2"></i>Dashboard
        </a>
      </div>

      <!-- Filters and Search -->
      <div class="mb-6">
        <form action="" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
            <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="ID, Name or Email" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
          </div>
          <div>
            <label for="date_filter" class="block text-sm font-medium text-gray-700 mb-1">Date Filter</label>
            <select id="date_filter" name="date_filter" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
              <option value="">All Time</option>
              <option value="today" <?php echo $date_filter === 'today' ? 'selected' : ''; ?>>Today</option>
              <option value="last7days" <?php echo $date_filter === 'last7days' ? 'selected' : ''; ?>>Last 7 Days</option>
              <option value="last30days" <?php echo $date_filter === 'last30days' ? 'selected' : ''; ?>>Last 30 Days</option>
              <option value="thismonth" <?php echo $date_filter === 'thismonth' ? 'selected' : ''; ?>>This Month</option>
            </select>
          </div>
          <div>
            <label for="status_filter" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select id="status_filter" name="status_filter" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
              <option value="">All Statuses</option>
              <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
              <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
              <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
          </div>
          <div class="flex items-end">
            <button type="submit" class="btn-primary mr-2">
              <i class="fas fa-search mr-2"></i>Filter
            </button>
            <a href="admin_bookings.php" class="btn-secondary">
              <i class="fas fa-times mr-2"></i>Clear
            </a>
          </div>
        </form>
      </div>

      <!-- Bookings Count -->
      <div class="mb-6">
        <div class="bg-blue-50 border-l-4 border-blue-500 text-blue-700 p-4">
          <div class="flex justify-between items-center">
            <p class="font-bold">Total Bookings: <?php echo $total_bookings; ?></p>
            <p class="text-sm">Showing <?php echo min($per_page, count($bookings)); ?> of <?php echo $total_bookings; ?> bookings</p>
          </div>
        </div>
      </div>

      <!-- Bookings Table -->
      <div class="overflow-x-auto">
        <table class="min-w-full bg-white border border-gray-200">
          <thead>
            <tr class="bg-gray-100">
              <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">ID</th>
              <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">User</th>
              <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Package</th>
              <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Date</th>
              <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Status</th>
              <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Assignments</th>
              <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <?php if (empty($bookings)): ?>
              <tr>
                <td colspan="7" class="py-4 px-4 text-center text-gray-500">No bookings found</td>
              </tr>
            <?php else: ?>
              <?php foreach ($bookings as $booking): ?>
                <tr class="hover:bg-gray-50">
                  <td class="py-2 px-4"><?php echo $booking['id']; ?></td>
                  <td class="py-2 px-4">
                    <div>
                      <div class="font-medium text-gray-900"><?php echo htmlspecialchars($booking['full_name']); ?></div>
                      <div class="text-gray-500 text-sm"><?php echo htmlspecialchars($booking['email']); ?></div>
                    </div>
                  </td>
                  <td class="py-2 px-4">
                    <div>
                      <div class="font-medium text-gray-900"><?php echo htmlspecialchars($booking['package_title']); ?></div>
                      <div class="text-gray-500 text-sm">
                        <?php echo ucfirst($booking['package_type']); ?> -
                        PKR <?php echo number_format($booking['package_price'], 2); ?>
                      </div>
                    </div>
                  </td>
                  <td class="py-2 px-4 text-sm text-gray-500">
                    <?php echo date('M j, Y', strtotime($booking['created_at'])); ?><br>
                    <span class="text-xs"><?php echo date('h:i A', strtotime($booking['created_at'])); ?></span>
                  </td>
                  <td class="py-2 px-4">
                    <?php if ($booking['status'] === 'confirmed'): ?>
                      <span class="badge badge-success">Confirmed</span>
                    <?php elseif ($booking['status'] === 'pending'): ?>
                      <span class="badge badge-pending">Pending</span>
                    <?php elseif ($booking['status'] === 'cancelled'): ?>
                      <span class="badge badge-danger">Cancelled</span>
                    <?php else: ?>
                      <span class="badge badge-secondary"><?php echo ucfirst($booking['status']); ?></span>
                    <?php endif; ?>
                  </td>
                  <td class="py-2 px-4">
                    <div class="flex flex-col space-y-2">
                      <div>
                        <span class="text-sm font-medium text-gray-700">Hotel:</span>
                        <?php if ($booking['hotel_assigned']): ?>
                          <span class="text-green-500 text-sm"><i class="fas fa-check"></i> Assigned</span>
                        <?php else: ?>
                          <span class="text-red-500 text-sm"><i class="fas fa-times"></i> Not Assigned</span>
                        <?php endif; ?>
                      </div>
                      <div>
                        <span class="text-sm font-medium text-gray-700">Flight:</span>
                        <?php if ($booking['flight_assigned']): ?>
                          <span class="text-green-500 text-sm"><i class="fas fa-check"></i> Assigned</span>
                        <?php else: ?>
                          <span class="text-red-500 text-sm"><i class="fas fa-times"></i> Not Assigned</span>
                        <?php endif; ?>
                      </div>
                      <div>
                        <span class="text-sm font-medium text-gray-700">Transport:</span>
                        <?php if ($booking['transport_assigned']): ?>
                          <span class="text-green-500 text-sm"><i class="fas fa-check"></i> Assigned</span>
                        <?php else: ?>
                          <span class="text-red-500 text-sm"><i class="fas fa-times"></i> Not Assigned</span>
                        <?php endif; ?>
                      </div>
                    </div>
                  </td>
                  <td class="py-2 px-4">
                    <div class="flex flex-col space-y-2">
                      <a href="admin_assign_hotel.php?booking_id=<?php echo $booking['id']; ?>" class="<?php echo $booking['hotel_assigned'] ? 'btn-success' : 'btn-primary'; ?> text-xs py-1">
                        <?php if ($booking['hotel_assigned']): ?>
                          <i class="fas fa-edit mr-1"></i> Update Hotel
                        <?php else: ?>
                          <i class="fas fa-hotel mr-1"></i> Assign Hotel
                        <?php endif; ?>
                      </a>
                      <a href="admin_assign_flight.php?booking_id=<?php echo $booking['id']; ?>" class="<?php echo $booking['flight_assigned'] ? 'btn-success' : 'btn-primary'; ?> text-xs py-1">
                        <?php if ($booking['flight_assigned']): ?>
                          <i class="fas fa-edit mr-1"></i> Update Flight
                        <?php else: ?>
                          <i class="fas fa-plane mr-1"></i> Assign Flight
                        <?php endif; ?>
                      </a>
                      <a href="admin_assign_transport.php?booking_id=<?php echo $booking['id']; ?>" class="<?php echo $booking['transport_assigned'] ? 'btn-success' : 'btn-primary'; ?> text-xs py-1">
                        <?php if ($booking['transport_assigned']): ?>
                          <i class="fas fa-edit mr-1"></i> Update Transport
                        <?php else: ?>
                          <i class="fas fa-car mr-1"></i> Assign Transport
                        <?php endif; ?>
                      </a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <?php if ($total_pages > 1): ?>
        <div class="mt-6 flex justify-between items-center">
          <div>
            <p class="text-sm text-gray-700">
              Showing <span class="font-medium"><?php echo ($current_page - 1) * $per_page + 1; ?></span> to
              <span class="font-medium"><?php echo min($current_page * $per_page, $total_bookings); ?></span> of
              <span class="font-medium"><?php echo $total_bookings; ?></span> bookings
            </p>
          </div>
          <div class="flex space-x-2">
            <?php if ($current_page > 1): ?>
              <a href="?page=<?php echo $current_page - 1; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?><?php echo !empty($date_filter) ? '&date_filter=' . urlencode($date_filter) : ''; ?><?php echo !empty($status_filter) ? '&status_filter=' . urlencode($status_filter) : ''; ?>" class="btn-secondary py-1 px-3">
                <i class="fas fa-chevron-left mr-1"></i> Previous
              </a>
            <?php endif; ?>

            <?php
            $start_page = max(1, $current_page - 2);
            $end_page = min($total_pages, $current_page + 2);

            if ($start_page > 1) {
              echo '<a href="?page=1' . (!empty($search_term) ? '&search=' . urlencode($search_term) : '') . (!empty($date_filter) ? '&date_filter=' . urlencode($date_filter) : '') . (!empty($status_filter) ? '&status_filter=' . urlencode($status_filter) : '') . '" class="btn-secondary py-1 px-3">1</a>';
              if ($start_page > 2) {
                echo '<span class="px-2">...</span>';
              }
            }

            for ($i = $start_page; $i <= $end_page; $i++) {
              $active_class = $i === $current_page ? 'bg-blue-500 text-white' : '';
              echo '<a href="?page=' . $i . (!empty($search_term) ? '&search=' . urlencode($search_term) : '') . (!empty($date_filter) ? '&date_filter=' . urlencode($date_filter) : '') . (!empty($status_filter) ? '&status_filter=' . urlencode($status_filter) : '') . '" class="btn-secondary py-1 px-3 ' . $active_class . '">' . $i . '</a>';
            }

            if ($end_page < $total_pages) {
              if ($end_page < $total_pages - 1) {
                echo '<span class="px-2">...</span>';
              }
              echo '<a href="?page=' . $total_pages . (!empty($search_term) ? '&search=' . urlencode($search_term) : '') . (!empty($date_filter) ? '&date_filter=' . urlencode($date_filter) : '') . (!empty($status_filter) ? '&status_filter=' . urlencode($status_filter) : '') . '" class="btn-secondary py-1 px-3">' . $total_pages . '</a>';
            }
            ?>

            <?php if ($current_page < $total_pages): ?>
              <a href="?page=<?php echo $current_page + 1; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?><?php echo !empty($date_filter) ? '&date_filter=' . urlencode($date_filter) : ''; ?><?php echo !empty($status_filter) ? '&status_filter=' . urlencode($status_filter) : ''; ?>" class="btn-secondary py-1 px-3">
                Next <i class="fas fa-chevron-right ml-1"></i>
              </a>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script>
    // Toggle mobile sidebar
    document.addEventListener('DOMContentLoaded', function() {
      const sidebarToggle = document.getElementById('sidebar-toggle');
      const sidebar = document.getElementById('sidebar');

      if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
          sidebar.classList.toggle('hidden');
        });
      }
    });
  </script>
</body>

</html>