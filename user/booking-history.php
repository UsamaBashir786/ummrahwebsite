<?php
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

$user_id = $_SESSION['user_id'];

// Fetch all bookings
$all_bookings_query = $conn->prepare("
    (SELECT 'Flight' AS type, fb.id, f.airline_name AS title, f.departure_city AS location, 
            f.departure_date AS date, fb.total_price AS price, fb.booking_status, fb.created_at, 'flights' AS link
     FROM flight_bookings fb 
     JOIN flights f ON fb.flight_id = f.id 
     WHERE fb.user_id = ?)
    UNION
    (SELECT 'Hotel' AS type, hb.id, h.hotel_name AS title, h.location, 
            hb.check_in_date AS date, hb.total_price AS price, hb.booking_status, hb.created_at, 'hotels' AS link
     FROM hotel_bookings hb 
     JOIN hotels h ON hb.hotel_id = h.id 
     WHERE hb.user_id = ?)
    UNION
    (SELECT 'Package' AS type, pb.id, up.title, 'Saudi Arabia' AS location, 
            pb.travel_date AS date, pb.total_price AS price, pb.booking_status, pb.created_at, 'packages' AS link
     FROM package_bookings pb 
     JOIN umrah_packages up ON pb.package_id = up.id 
     WHERE pb.user_id = ?)
    UNION
    (SELECT 'Transport' AS type, tb.id, tb.route_name AS title, tb.transport_type AS location, 
            tb.pickup_date AS date, tb.price, tb.booking_status, tb.created_at, 'transportation' AS link
     FROM transportation_bookings tb
     WHERE tb.user_id = ?)
    ORDER BY created_at DESC
");
$all_bookings_query->bind_param("iiii", $user_id, $user_id, $user_id, $user_id);
$all_bookings_query->execute();
$all_bookings = $all_bookings_query->get_result()->fetch_all(MYSQLI_ASSOC);
$all_bookings_query->close();

// Get booking statistics
$stats_query = $conn->prepare("
    SELECT 
        (SELECT COUNT(*) FROM flight_bookings WHERE user_id = ?) +
        (SELECT COUNT(*) FROM hotel_bookings WHERE user_id = ?) +
        (SELECT COUNT(*) FROM package_bookings WHERE user_id = ?) +
        (SELECT COUNT(*) FROM transportation_bookings WHERE user_id = ?) AS total_bookings,
        
        (SELECT COUNT(*) FROM flight_bookings WHERE user_id = ? AND booking_status = 'pending') +
        (SELECT COUNT(*) FROM hotel_bookings WHERE user_id = ? AND booking_status = 'pending') +
        (SELECT COUNT(*) FROM package_bookings WHERE user_id = ? AND booking_status = 'pending') +
        (SELECT COUNT(*) FROM transportation_bookings WHERE user_id = ? AND booking_status = 'pending') AS pending_bookings,
        
        (SELECT COUNT(*) FROM flight_bookings WHERE user_id = ? AND booking_status = 'confirmed') +
        (SELECT COUNT(*) FROM hotel_bookings WHERE user_id = ? AND booking_status = 'confirmed') +
        (SELECT COUNT(*) FROM package_bookings WHERE user_id = ? AND booking_status = 'confirmed') +
        (SELECT COUNT(*) FROM transportation_bookings WHERE user_id = ? AND booking_status = 'confirmed') AS confirmed_bookings,
        
        (SELECT COUNT(*) FROM flight_bookings WHERE user_id = ? AND booking_status = 'completed') +
        (SELECT COUNT(*) FROM hotel_bookings WHERE user_id = ? AND booking_status = 'completed') +
        (SELECT COUNT(*) FROM package_bookings WHERE user_id = ? AND booking_status = 'completed') +
        (SELECT COUNT(*) FROM transportation_bookings WHERE user_id = ? AND booking_status = 'completed') AS completed_bookings
");
$stats_query->bind_param(
  "iiiiiiiiiiiiiiii",
  $user_id,
  $user_id,
  $user_id,
  $user_id,
  $user_id,
  $user_id,
  $user_id,
  $user_id,
  $user_id,
  $user_id,
  $user_id,
  $user_id,
  $user_id,
  $user_id,
  $user_id,
  $user_id
);
$stats_query->execute();
$stats = $stats_query->get_result()->fetch_assoc();
$stats_query->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Booking History - UmrahFlights</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <style>
    .stat-card {
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .stat-card:hover {
      transform: translateY(-5px);
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

    .booking-row:hover {
      background-color: #f8fafc;
    }

    .type-badge {
      padding: 4px 8px;
      border-radius: 12px;
      font-size: 0.8rem;
      font-weight: 600;
    }

    .type-flight {
      background-color: #dbeafe;
      color: #1d4ed8;
    }

    .type-hotel {
      background-color: #dcfce7;
      color: #166534;
    }

    .type-package {
      background-color: #fef3c7;
      color: #d97706;
    }

    .type-transport {
      background-color: #f3e8ff;
      color: #7c3aed;
    }
  </style>
</head>

<body class="bg-gray-100">
  <?php include 'includes/sidebar.php'; ?>

  <!-- Main Content -->
  <div class="content-area">
    <!-- Top Header -->
    <div class="bg-white shadow-lg rounded-lg p-5 mb-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">Booking History</h1>
          <p class="text-gray-600">View all your past and upcoming bookings in one place</p>
        </div>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
      <div class="stat-card bg-white p-6 border-l-4 border-cyan-500">
        <div class="flex justify-between items-center">
          <div>
            <h3 class="text-2xl font-bold text-gray-800"><?php echo $stats['total_bookings']; ?></h3>
            <p class="text-sm text-gray-500">Total Bookings</p>
          </div>
          <div class="flex items-center justify-center w-12 h-12 rounded-full bg-cyan-100 text-cyan-500">
            <i class="fas fa-calendar-check text-xl"></i>
          </div>
        </div>
      </div>

      <div class="stat-card bg-white p-6 border-l-4 border-yellow-500">
        <div class="flex justify-between items-center">
          <div>
            <h3 class="text-2xl font-bold text-gray-800"><?php echo $stats['pending_bookings']; ?></h3>
            <p class="text-sm text-gray-500">Pending</p>
          </div>
          <div class="flex items-center justify-center w-12 h-12 rounded-full bg-yellow-100 text-yellow-500">
            <i class="fas fa-clock text-xl"></i>
          </div>
        </div>
      </div>

      <div class="stat-card bg-white p-6 border-l-4 border-green-500">
        <div class="flex justify-between items-center">
          <div>
            <h3 class="text-2xl font-bold text-gray-800"><?php echo $stats['confirmed_bookings']; ?></h3>
            <p class="text-sm text-gray-500">Confirmed</p>
          </div>
          <div class="flex items-center justify-center w-12 h-12 rounded-full bg-green-100 text-green-500">
            <i class="fas fa-check-circle text-xl"></i>
          </div>
        </div>
      </div>

      <div class="stat-card bg-white p-6 border-l-4 border-gray-500">
        <div class="flex justify-between items-center">
          <div>
            <h3 class="text-2xl font-bold text-gray-800"><?php echo $stats['completed_bookings']; ?></h3>
            <p class="text-sm text-gray-500">Completed</p>
          </div>
          <div class="flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 text-gray-500">
            <i class="fas fa-flag-checkered text-xl"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow-lg rounded-lg p-5 mb-6">
      <div class="flex flex-col md:flex-row gap-4">
        <select id="typeFilter" class="form-select rounded-lg border-gray-300">
          <option value="">All Types</option>
          <option value="Flight">Flights</option>
          <option value="Hotel">Hotels</option>
          <option value="Package">Packages</option>
          <option value="Transport">Transportation</option>
        </select>
        <select id="statusFilter" class="form-select rounded-lg border-gray-300">
          <option value="">All Statuses</option>
          <option value="pending">Pending</option>
          <option value="confirmed">Confirmed</option>
          <option value="cancelled">Cancelled</option>
          <option value="completed">Completed</option>
        </select>
        <input type="date" id="dateFilter" class="form-control rounded-lg border-gray-300" placeholder="Filter by date">
        <input type="text" id="searchInput" class="form-control rounded-lg border-gray-300" placeholder="Search bookings...">
      </div>
    </div>

    <!-- Bookings Table -->
    <div class="bg-white shadow-lg rounded-lg overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
              <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
              <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
              <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
              <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
              <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
              <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <?php if (empty($all_bookings)): ?>
              <tr>
                <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                  No bookings found.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($all_bookings as $booking): ?>
                <tr class="booking-row hover:bg-gray-50"
                  data-type="<?php echo htmlspecialchars($booking['type']); ?>"
                  data-status="<?php echo htmlspecialchars($booking['booking_status']); ?>"
                  data-date="<?php echo date('Y-m-d', strtotime($booking['date'])); ?>">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="type-badge type-<?php echo strtolower($booking['type']); ?>">
                      <?php echo $booking['type']; ?>
                    </span>
                  </td>
                  <td class="px-6 py-4">
                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($booking['title']); ?></div>
                    <div class="text-sm text-gray-500">Booking #<?php echo $booking['id']; ?></div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <?php echo htmlspecialchars($booking['location']); ?>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <?php echo date('d M Y', strtotime($booking['date'])); ?>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    Rs<?php echo number_format($booking['price'], 2); ?>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="status-badge status-<?php echo $booking['booking_status']; ?>">
                      <?php echo ucfirst($booking['booking_status']); ?>
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm">
                    <a href="<?php echo $booking['link']; ?>.php"
                      class="text-cyan-600 hover:text-cyan-900">View Details</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <script>
    // Filter functionality
    const typeFilter = document.getElementById('typeFilter');
    const statusFilter = document.getElementById('statusFilter');
    const dateFilter = document.getElementById('dateFilter');
    const searchInput = document.getElementById('searchInput');
    const bookingRows = document.querySelectorAll('.booking-row');

    function filterBookings() {
      const type = typeFilter.value;
      const status = statusFilter.value.toLowerCase();
      const date = dateFilter.value;
      const search = searchInput.value.toLowerCase();

      bookingRows.forEach(row => {
        const rowType = row.dataset.type;
        const rowStatus = row.dataset.status.toLowerCase();
        const rowDate = row.dataset.date;
        const rowText = row.textContent.toLowerCase();

        const typeMatch = !type || rowType === type;
        const statusMatch = !status || rowStatus === status;
        const dateMatch = !date || rowDate === date;
        const searchMatch = !search || rowText.includes(search);

        row.style.display = typeMatch && statusMatch && dateMatch && searchMatch ? '' : 'none';
      });
    }

    typeFilter.addEventListener('change', filterBookings);
    statusFilter.addEventListener('change', filterBookings);
    dateFilter.addEventListener('change', filterBookings);
    searchInput.addEventListener('input', filterBookings);
  </script>
</body>

</html>