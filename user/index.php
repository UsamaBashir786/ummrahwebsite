<?php
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

// Function to format numbers in shortened form
function formatNumber($number)
{
  if ($number < 1000) {
    return number_format($number);
  } elseif ($number < 1000000) {
    $formatted = $number / 1000;
    // Check if it's a whole number
    return ($formatted == floor($formatted)) ? number_format($formatted) . 'k' : number_format($formatted, 1) . 'k';
  } else {
    $formatted = $number / 1000000;
    // Check if it's a whole number
    return ($formatted == floor($formatted)) ? number_format($formatted) . 'M' : number_format($formatted, 1) . 'M';
  }
}

$user_id = $_SESSION['user_id'];

// Fetch user data
$user_query = $conn->prepare("SELECT full_name, email, phone, dob, profile_image FROM users WHERE id = ?");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user = $user_query->get_result()->fetch_assoc();
$user_query->close();

// Fetch booking statistics
$stats_query = $conn->prepare("
    SELECT 
        (SELECT COUNT(*) FROM flight_bookings WHERE user_id = ?) AS total_flights,
        (SELECT COUNT(*) FROM hotel_bookings WHERE user_id = ?) AS total_hotels,
        (SELECT COUNT(*) FROM package_bookings WHERE user_id = ?) AS total_packages,
        (SELECT COUNT(*) FROM transportation_bookings WHERE user_id = ?) AS total_transport,
        (SELECT COALESCE(SUM(total_price), 0) FROM flight_bookings WHERE user_id = ?) +
        (SELECT COALESCE(SUM(total_price), 0) FROM hotel_bookings WHERE user_id = ?) +
        (SELECT COALESCE(SUM(total_price), 0) FROM package_bookings WHERE user_id = ?) +
        (SELECT COALESCE(SUM(price), 0) FROM transportation_bookings WHERE user_id = ?) AS total_spent
");
$stats_query->bind_param("iiiiiiii", $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
$stats_query->execute();
$stats = $stats_query->get_result()->fetch_assoc();
$stats_query->close();

// Fetch recent bookings
$recent_bookings_query = $conn->prepare("
    (SELECT 'Flight' AS type, airline_name AS title, departure_city AS location, 
            departure_date AS date, fb.total_price AS price, fb.booking_status, fb.created_at, fb.id, 'flight' AS booking_type
     FROM flight_bookings fb 
     JOIN flights f ON fb.flight_id = f.id 
     WHERE fb.user_id = ?)
    UNION
    (SELECT 'Hotel' AS type, hotel_name AS title, h.location, 
            check_in_date AS date, hb.total_price AS price, hb.booking_status, hb.created_at, hb.id, 'hotel' AS booking_type
     FROM hotel_bookings hb 
     JOIN hotels h ON hb.hotel_id = h.id 
     WHERE hb.user_id = ?)
    UNION
    (SELECT 'Package' AS type, up.title, 'Saudi Arabia' AS location, 
            travel_date AS date, pb.total_price AS price, pb.booking_status, pb.created_at, pb.id, 'package' AS booking_type
     FROM package_bookings pb 
     JOIN umrah_packages up ON pb.package_id = up.id 
     WHERE pb.user_id = ?)
    UNION
    (SELECT 'Transport' AS type, route_name AS title, transport_type AS location, 
            pickup_date AS date, price, booking_status, created_at, id, 'transport' AS booking_type
     FROM transportation_bookings 
     WHERE user_id = ?)
    ORDER BY created_at DESC
    LIMIT 5
");
$recent_bookings_query->bind_param("iiii", $user_id, $user_id, $user_id, $user_id);
$recent_bookings_query->execute();
$recent_bookings = $recent_bookings_query->get_result()->fetch_all(MYSQLI_ASSOC);
$recent_bookings_query->close();

// Fetch upcoming trips
$upcoming_trips_query = $conn->prepare("
    (SELECT 'Flight' AS type, airline_name AS title, f.departure_date AS date, fb.booking_status
     FROM flight_bookings fb 
     JOIN flights f ON fb.flight_id = f.id 
     WHERE fb.user_id = ? AND f.departure_date > NOW() AND fb.booking_status != 'cancelled')
    UNION
    (SELECT 'Hotel' AS type, hotel_name AS title, hb.check_in_date AS date, hb.booking_status
     FROM hotel_bookings hb 
     JOIN hotels h ON hb.hotel_id = h.id 
     WHERE hb.user_id = ? AND hb.check_in_date > NOW() AND hb.booking_status != 'cancelled')
    UNION
    (SELECT 'Package' AS type, up.title, pb.travel_date AS date, pb.booking_status
     FROM package_bookings pb 
     JOIN umrah_packages up ON pb.package_id = up.id 
     WHERE pb.user_id = ? AND pb.travel_date > NOW() AND pb.booking_status != 'cancelled')
    ORDER BY date ASC
    LIMIT 3
");
$upcoming_trips_query->bind_param("iii", $user_id, $user_id, $user_id);
$upcoming_trips_query->execute();
$upcoming_trips = $upcoming_trips_query->get_result()->fetch_all(MYSQLI_ASSOC);
$upcoming_trips_query->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - UmrahFlights</title>
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

    .recent-booking-card {
      border-radius: 12px;
      transition: all 0.3s ease;
    }

    .recent-booking-card:hover {
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
      transform: translateY(-2px);
    }
  </style>
</head>

<body class="bg-gray-100">
  <?php include 'includes/sidebar.php'; ?>

  <!-- Main Content -->
  <div class="content-area">
    <!-- Top Navbar -->
    <nav class="bg-white shadow-lg rounded-lg p-5 mb-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>!</h1>
          <p class="text-gray-600">Here's an overview of your bookings and activities</p>
        </div>
        <div class="flex items-center space-x-4">
          <button class="bg-cyan-600 text-white px-4 py-2 rounded-lg hover:bg-cyan-700 transition">
            <i class="fas fa-plus mr-2"></i>New Booking
          </button>
          <?php if ($user['profile_image']): ?>
            <img src="../<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile" class="w-10 h-10 rounded-full object-cover">
          <?php else: ?>
            <div class="w-10 h-10 rounded-full bg-cyan-500 text-white flex items-center justify-center">
              <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </nav>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
      <!-- Total Bookings -->
      <div class="stat-card bg-white p-6 border-l-4 border-cyan-500">
        <div class="flex justify-between items-center">
          <div>
            <h3 class="text-2xl font-bold text-gray-800">
              <?php echo $stats['total_flights'] + $stats['total_hotels'] + $stats['total_packages'] + $stats['total_transport']; ?>
            </h3>
            <p class="text-sm text-gray-500">Total Bookings</p>
          </div>
          <div class="flex items-center justify-center w-12 h-12 rounded-full bg-cyan-100 text-cyan-500">
            <i class="fas fa-calendar-check text-xl"></i>
          </div>
        </div>
      </div>

      <!-- Flight Bookings -->
      <div class="stat-card bg-white p-6 border-l-4 border-blue-500">
        <div class="flex justify-between items-center">
          <div>
            <h3 class="text-2xl font-bold text-gray-800"><?php echo $stats['total_flights']; ?></h3>
            <p class="text-sm text-gray-500">Flight Bookings</p>
          </div>
          <div class="flex items-center justify-center w-12 h-12 rounded-full bg-blue-100 text-blue-500">
            <i class="fas fa-plane text-xl"></i>
          </div>
        </div>
      </div>

      <!-- Hotel Bookings -->
      <div class="stat-card bg-white p-6 border-l-4 border-green-500">
        <div class="flex justify-between items-center">
          <div>
            <h3 class="text-2xl font-bold text-gray-800"><?php echo $stats['total_hotels']; ?></h3>
            <p class="text-sm text-gray-500">Hotel Bookings</p>
          </div>
          <div class="flex items-center justify-center w-12 h-12 rounded-full bg-green-100 text-green-500">
            <i class="fas fa-hotel text-xl"></i>
          </div>
        </div>
      </div>

      <!-- Total Spent -->
      <div class="stat-card bg-white p-6 border-l-4 border-yellow-500">
        <div class="flex justify-between items-center">
          <div>
            <h3 class="text-2xl font-bold text-gray-800">Rs<?php echo formatNumber($stats['total_spent']); ?></h3>
            <p class="text-sm text-gray-500">Total Spent</p>
          </div>
          <div class="flex items-center justify-center w-12 h-12 rounded-full bg-yellow-100 text-yellow-500">
            <i class="fas fa-wallet text-xl"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Content Sections -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Recent Bookings -->
      <div class="lg:col-span-2">
        <div class="bg-white shadow-lg rounded-lg p-6">
          <div class="flex justify-between items-center mb-4">
            <h5 class="text-lg font-semibold text-gray-800">Recent Bookings</h5>
            <a href="booking-history.php" class="text-cyan-600 hover:text-cyan-700">View All</a>
          </div>
          <?php if (empty($recent_bookings)): ?>
            <p class="text-gray-500 text-center py-4">No bookings found.</p>
          <?php else: ?>
            <div class="space-y-4">
              <?php foreach ($recent_bookings as $booking): ?>
                <div class="recent-booking-card bg-gray-50 p-4 rounded-lg">
                  <div class="flex justify-between items-start">
                    <div>
                      <h6 class="font-semibold text-gray-800"><?php echo htmlspecialchars($booking['title']); ?></h6>
                      <p class="text-sm text-gray-600">
                        <i class="fas fa-<?php
                                          echo $booking['type'] == 'Flight' ? 'plane' : ($booking['type'] == 'Hotel' ? 'hotel' : ($booking['type'] == 'Package' ? 'box' : 'car'));
                                          ?> mr-2"></i>
                        <?php echo $booking['type']; ?> • <?php echo htmlspecialchars($booking['location']); ?>
                      </p>
                      <p class="text-sm text-gray-600">
                        <i class="far fa-calendar mr-2"></i>
                        <?php echo date('d M Y', strtotime($booking['date'])); ?>
                      </p>
                    </div>
                    <div class="text-right">
                      <span class="status-badge status-<?php echo $booking['booking_status']; ?>">
                        <?php echo ucfirst($booking['booking_status']); ?>
                      </span>
                      <p class="text-lg font-bold text-cyan-600 mt-2">Rs<?php echo formatNumber($booking['price']); ?></p>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Upcoming Trips -->
      <div class="lg:col-span-1">
        <div class="bg-white shadow-lg rounded-lg p-6">
          <h5 class="text-lg font-semibold text-gray-800 mb-4">Upcoming Trips</h5>
          <?php if (empty($upcoming_trips)): ?>
            <p class="text-gray-500 text-center py-4">No upcoming trips.</p>
          <?php else: ?>
            <div class="space-y-4">
              <?php foreach ($upcoming_trips as $trip): ?>
                <div class="border-l-4 border-cyan-500 pl-4">
                  <h6 class="font-semibold text-gray-800"><?php echo htmlspecialchars($trip['title']); ?></h6>
                  <p class="text-sm text-gray-600"><?php echo $trip['type']; ?></p>
                  <p class="text-sm text-cyan-600">
                    <i class="far fa-calendar mr-2"></i>
                    <?php echo date('d M Y', strtotime($trip['date'])); ?>
                  </p>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white shadow-lg rounded-lg p-6 mt-6">
          <h5 class="text-lg font-semibold text-gray-800 mb-4">Quick Actions</h5>
          <div class="space-y-3">
            <a href="../flights.php" class="block w-full text-center bg-cyan-600 text-white py-2 rounded-lg hover:bg-cyan-700 transition">
              <i class="fas fa-plane mr-2"></i>Book Flight
            </a>
            <a href="../hotels.php" class="block w-full text-center bg-green-600 text-white py-2 rounded-lg hover:bg-green-700 transition">
              <i class="fas fa-hotel mr-2"></i>Book Hotel
            </a>
            <a href="../packages.php" class="block w-full text-center bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition">
              <i class="fas fa-box mr-2"></i>Book Package
            </a>
            <a href="../transportation.php" class="block w-full text-center bg-yellow-600 text-white py-2 rounded-lg hover:bg-yellow-700 transition">
              <i class="fas fa-car mr-2"></i>Book Transport
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    <?php if (isset($_SESSION['booking_message'])): ?>
      Swal.fire({
        icon: '<?php echo $_SESSION['booking_message_type']; ?>',
        title: '<?php echo $_SESSION['booking_message_type'] == 'success' ? 'Success!' : 'Error!'; ?>',
        text: '<?php echo $_SESSION['booking_message']; ?>',
        confirmButtonColor: '#06b6d4'
      });
      <?php
      unset($_SESSION['booking_message']);
      unset($_SESSION['booking_message_type']);
      ?>
    <?php endif; ?>
  </script>
</body>

</html>