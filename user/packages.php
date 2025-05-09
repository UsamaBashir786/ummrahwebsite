<?php
require_once '../config/db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
  header("Location: ../login.php");
  exit();
}

$user_id = $_SESSION['user_id'];

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$search_term = $_GET['search'] ?? '';

// Build SQL query with filters
$sql_filters = "WHERE pb.user_id = ?";
$params = [$user_id];
$param_types = "i";

if (!empty($status_filter)) {
  $sql_filters .= " AND pb.booking_status = ?";
  $params[] = $status_filter;
  $param_types .= "s";
}

if (!empty($search_term)) {
  $sql_filters .= " AND (up.title LIKE ? OR pb.booking_reference LIKE ?)";
  $search_param = "%$search_term%";
  $params[] = $search_param;
  $params[] = $search_param;
  $param_types .= "ss";
}

// Fetch package bookings with filters - REMOVED package_type from the selection
$packages_query = $conn->prepare("
    SELECT 
        pb.id, 
        pb.package_id, 
        pb.travel_date, 
        pb.num_travelers, 
        pb.total_price, 
        pb.booking_status, 
        pb.payment_status,
        pb.booking_reference,
        pb.special_requests,
        pb.created_at,
        up.title, 
        up.star_rating,
        up.makkah_nights,
        up.madinah_nights,
        up.total_days,
        up.description, 
        up.package_image,
        up.inclusions
    FROM package_bookings pb
    JOIN umrah_packages up ON pb.package_id = up.id
    $sql_filters
    ORDER BY pb.created_at DESC
");

$packages_query->bind_param($param_types, ...$params);
$packages_query->execute();
$packages = $packages_query->get_result()->fetch_all(MYSQLI_ASSOC);
$packages_query->close();

// Get booking count by status
$status_count_query = $conn->prepare("
    SELECT booking_status, COUNT(*) as count
    FROM package_bookings
    WHERE user_id = ?
    GROUP BY booking_status
");
$status_count_query->bind_param("i", $user_id);
$status_count_query->execute();
$status_counts = $status_count_query->get_result()->fetch_all(MYSQLI_ASSOC);
$status_count_query->close();

// Format status counts for easier access
$booking_counts = [
  'total' => 0,
  'pending' => 0,
  'confirmed' => 0,
  'cancelled' => 0,
  'completed' => 0
];

foreach ($status_counts as $count) {
  $booking_counts[$count['booking_status']] = $count['count'];
  $booking_counts['total'] += $count['count'];
}

// Handle booking cancellation
if (isset($_POST['cancel_booking'])) {
  $booking_id = $_POST['booking_id'] ?? 0;

  // Check if booking is eligible for cancellation (pending status)
  $check_stmt = $conn->prepare("SELECT id, booking_reference FROM package_bookings WHERE id = ? AND user_id = ? AND booking_status = 'pending'");
  $check_stmt->bind_param("ii", $booking_id, $user_id);
  $check_stmt->execute();
  $check_result = $check_stmt->get_result();

  if ($check_result->num_rows > 0) {
    $booking_data = $check_result->fetch_assoc();
    $booking_reference = $booking_data['booking_reference'];

    // Update booking status
    $cancel_stmt = $conn->prepare("UPDATE package_bookings SET booking_status = 'cancelled', updated_at = NOW() WHERE id = ? AND user_id = ?");
    $cancel_stmt->bind_param("ii", $booking_id, $user_id);

    if ($cancel_stmt->execute()) {
      $_SESSION['booking_message'] = "Booking #$booking_reference has been cancelled successfully.";
      $_SESSION['booking_message_type'] = "success";
    } else {
      $_SESSION['booking_message'] = "Error cancelling your booking. Please try again.";
      $_SESSION['booking_message_type'] = "error";
    }
    $cancel_stmt->close();
  } else {
    $_SESSION['booking_message'] = "This booking cannot be cancelled. It may have already been processed.";
    $_SESSION['booking_message_type'] = "error";
  }
  $check_stmt->close();

  // Redirect to remove POST data
  header("Location: packages.php" . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
  exit();
}

// Helper function to display star rating
function displayStarRating($rating)
{
  switch ($rating) {
    case 'low_budget':
      return 'Low Budget Economy';
    case '3_star':
      return '3 Star';
    case '4_star':
      return '4 Star';
    case '5_star':
      return '5 Star';
    default:
      return 'Standard';
  }
}

// Helper function to format date
function formatDate($date_string)
{
  $date = new DateTime($date_string);
  return $date->format('d M Y');
}

// Helper function to get status badge class
function getStatusBadgeClass($status)
{
  switch ($status) {
    case 'pending':
      return 'status-pending';
    case 'confirmed':
      return 'status-confirmed';
    case 'cancelled':
      return 'status-cancelled';
    case 'completed':
      return 'status-completed';
    default:
      return 'status-pending';
  }
}

// Helper function to parse inclusions
function parseInclusions($inclusions_json)
{
  if (empty($inclusions_json)) {
    return [];
  }

  $inclusions = json_decode($inclusions_json, true);
  return is_array($inclusions) ? $inclusions : [];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Package Bookings - Umrah Partner</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <style>
    .booking-card {
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      margin-bottom: 20px;
    }

    .booking-card:hover {
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

    .package-image {
      height: 200px;
      object-fit: cover;
      border-radius: 12px 12px 0 0;
    }

    .package-type-badge {
      padding: 4px 8px;
      border-radius: 12px;
      font-size: 0.8rem;
      font-weight: 600;
      text-transform: capitalize;
    }

    .rating-badge {
      background-color: #e0f2fe;
      color: #0369a1;
    }

    .status-card {
      transition: transform 0.3s ease;
      cursor: pointer;
    }

    .status-card:hover {
      transform: translateY(-5px);
    }

    .status-card.active {
      border-color: #0d6efd;
      background-color: #f0f9ff;
    }

    .counter-badge {
      position: absolute;
      top: -8px;
      right: -8px;
      font-size: 12px;
      font-weight: bold;
      border-radius: 50%;
      width: 24px;
      height: 24px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .inclusion-badge {
      display: inline-block;
      padding: 0.25rem 0.5rem;
      border-radius: 50px;
      font-size: 0.7rem;
      margin-right: 0.3rem;
      margin-bottom: 0.3rem;
      background-color: #E5F2F2;
      color: #047857;
      border: 1px solid #047857;
    }

    /* Timeline styles */
    .timeline {
      position: relative;
      padding-left: 30px;
    }

    .timeline::before {
      content: '';
      position: absolute;
      left: 0;
      top: 0;
      bottom: 0;
      width: 2px;
      background-color: #e5e7eb;
    }

    .timeline-item {
      position: relative;
      padding-bottom: 20px;
    }

    .timeline-marker {
      position: absolute;
      left: -30px;
      width: 16px;
      height: 16px;
      border-radius: 50%;
      background-color: #0d6efd;
      top: 3px;
    }

    .timeline-date {
      font-size: 0.8rem;
      color: #6b7280;
      margin-bottom: 4px;
    }

    .timeline-content {
      background-color: #f3f4f6;
      padding: 12px;
      border-radius: 8px;
    }
  </style>
</head>

<body class="bg-gray-100">
  <?php include 'includes/sidebar.php'; ?>

  <!-- Main Content -->
  <div class="content-area">
    <!-- Top Header -->
    <div class="bg-white shadow-lg rounded-lg p-5 mb-6">
      <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">My Package Bookings</h1>
          <p class="text-gray-600">Track and manage your Umrah package reservations</p>
        </div>
        <a href="../packages.php" class="bg-cyan-600 text-white px-4 py-2 rounded-lg hover:bg-cyan-700 transition">
          <i class="fas fa-plus mr-2"></i>Book New Package
        </a>
      </div>
    </div>

    <!-- Booking Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
      <!-- All Bookings -->
      <a href="packages.php" class="status-card block bg-white rounded-lg shadow-md p-4 border-2 border-transparent relative <?php echo empty($status_filter) ? 'active' : ''; ?>">
        <span class="counter-badge bg-cyan-600 text-white"><?php echo $booking_counts['total']; ?></span>
        <div class="flex items-center">
          <div class="p-3 bg-cyan-100 rounded-full mr-4">
            <i class="fas fa-list text-cyan-600"></i>
          </div>
          <div>
            <h3 class="text-lg font-semibold text-gray-800">All</h3>
            <p class="text-sm text-gray-500">Bookings</p>
          </div>
        </div>
      </a>

      <!-- Pending Bookings -->
      <a href="packages.php?status=pending" class="status-card block bg-white rounded-lg shadow-md p-4 border-2 border-transparent relative <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">
        <span class="counter-badge bg-yellow-500 text-white"><?php echo $booking_counts['pending']; ?></span>
        <div class="flex items-center">
          <div class="p-3 bg-yellow-100 rounded-full mr-4">
            <i class="fas fa-clock text-yellow-500"></i>
          </div>
          <div>
            <h3 class="text-lg font-semibold text-gray-800">Pending</h3>
            <p class="text-sm text-gray-500">Bookings</p>
          </div>
        </div>
      </a>

      <!-- Confirmed Bookings -->
      <a href="packages.php?status=confirmed" class="status-card block bg-white rounded-lg shadow-md p-4 border-2 border-transparent relative <?php echo $status_filter === 'confirmed' ? 'active' : ''; ?>">
        <span class="counter-badge bg-green-600 text-white"><?php echo $booking_counts['confirmed']; ?></span>
        <div class="flex items-center">
          <div class="p-3 bg-green-100 rounded-full mr-4">
            <i class="fas fa-check-circle text-green-600"></i>
          </div>
          <div>
            <h3 class="text-lg font-semibold text-gray-800">Confirmed</h3>
            <p class="text-sm text-gray-500">Bookings</p>
          </div>
        </div>
      </a>

      <!-- Cancelled Bookings -->
      <a href="packages.php?status=cancelled" class="status-card block bg-white rounded-lg shadow-md p-4 border-2 border-transparent relative <?php echo $status_filter === 'cancelled' ? 'active' : ''; ?>">
        <span class="counter-badge bg-red-600 text-white"><?php echo $booking_counts['cancelled']; ?></span>
        <div class="flex items-center">
          <div class="p-3 bg-red-100 rounded-full mr-4">
            <i class="fas fa-times-circle text-red-600"></i>
          </div>
          <div>
            <h3 class="text-lg font-semibold text-gray-800">Cancelled</h3>
            <p class="text-sm text-gray-500">Bookings</p>
          </div>
        </div>
      </a>

      <!-- Completed Bookings -->
      <a href="packages.php?status=completed" class="status-card block bg-white rounded-lg shadow-md p-4 border-2 border-transparent relative <?php echo $status_filter === 'completed' ? 'active' : ''; ?>">
        <span class="counter-badge bg-gray-600 text-white"><?php echo $booking_counts['completed']; ?></span>
        <div class="flex items-center">
          <div class="p-3 bg-gray-100 rounded-full mr-4">
            <i class="fas fa-flag-checkered text-gray-600"></i>
          </div>
          <div>
            <h3 class="text-lg font-semibold text-gray-800">Completed</h3>
            <p class="text-sm text-gray-500">Bookings</p>
          </div>
        </div>
      </a>
    </div>

    <!-- Filter Section -->
    <div class="bg-white shadow-lg rounded-lg p-5 mb-6">
      <form action="packages.php" method="GET" class="flex flex-col md:flex-row gap-4">
        <!-- Keep status filter if already selected -->
        <?php if (!empty($status_filter)): ?>
          <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
        <?php endif; ?>

        <input type="text" name="search" value="<?php echo htmlspecialchars($search_term); ?>" class="form-control rounded-lg border-gray-300" placeholder="Search by package name or reference...">

        <div class="flex gap-2">
          <button type="submit" class="bg-cyan-600 text-white px-4 py-2 rounded-lg hover:bg-cyan-700 transition">
            <i class="fas fa-search mr-2"></i>Search
          </button>
          <?php if (!empty($search_term)): ?>
            <a href="packages.php<?php echo !empty($status_filter) ? '?status=' . urlencode($status_filter) : ''; ?>" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition">
              <i class="fas fa-times mr-2"></i>Clear
            </a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <!-- Package Bookings Grid/List -->
    <div class="mb-6">
      <?php if (empty($packages)): ?>
        <div class="bg-white rounded-lg shadow-lg p-12 text-center">
          <div class="mb-4">
            <i class="fas fa-search text-5xl text-gray-300"></i>
          </div>
          <h3 class="text-xl font-bold text-gray-700 mb-2">No bookings found</h3>
          <?php if (!empty($status_filter) || !empty($search_term)): ?>
            <p class="text-gray-500 mb-4">No bookings match your current filter criteria.</p>
            <a href="packages.php" class="bg-cyan-600 text-white px-6 py-2 rounded-lg hover:bg-cyan-700 transition inline-block">
              View All Bookings
            </a>
          <?php else: ?>
            <p class="text-gray-500 mb-4">You haven't made any package bookings yet.</p>
            <a href="../packages.php" class="bg-cyan-600 text-white px-6 py-2 rounded-lg hover:bg-cyan-700 transition inline-block">
              Explore Available Packages
            </a>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <!-- Showing results text -->
        <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
          <p class="text-gray-600">
            Showing <?php echo count($packages); ?>
            <?php echo count($packages) === 1 ? 'booking' : 'bookings'; ?>
            <?php if (!empty($status_filter)): ?>
              with status "<?php echo ucfirst(htmlspecialchars($status_filter)); ?>"
            <?php endif; ?>
            <?php if (!empty($search_term)): ?>
              matching "<?php echo htmlspecialchars($search_term); ?>"
            <?php endif; ?>
          </p>
        </div>

        <!-- Bookings List View -->
        <?php foreach ($packages as $package): ?>
          <div class="booking-card bg-white overflow-hidden">
            <div class="p-0">
              <div class="flex flex-col md:flex-row">
                <!-- Package Image -->
                <?php if (!empty($package['package_image'])): ?>
                  <div class="md:w-1/4">
                    <img src="../<?php echo htmlspecialchars($package['package_image']); ?>"
                      alt="<?php echo htmlspecialchars($package['title']); ?>"
                      class="w-full h-full object-cover md:h-56"
                      style="max-height: 200px;">
                  </div>
                <?php else: ?>
                  <div class="md:w-1/4 bg-gray-200 flex items-center justify-center text-gray-500">
                    <i class="fas fa-image fa-3x"></i>
                  </div>
                <?php endif; ?>

                <!-- Booking Details -->
                <div class="md:w-3/4 p-6">
                  <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4">
                    <div>
                      <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($package['title']); ?></h3>
                      <p class="text-sm text-gray-600">Booking #<?php echo htmlspecialchars($package['booking_reference']); ?></p>
                    </div>
                    <div class="mt-2 sm:mt-0">
                      <span class="status-badge <?php echo getStatusBadgeClass($package['booking_status']); ?>">
                        <?php echo ucfirst(htmlspecialchars($package['booking_status'])); ?>
                      </span>
                    </div>
                  </div>

                  <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
                    <div>
                      <p class="text-gray-600 text-sm font-semibold">Travel Date</p>
                      <p class="flex items-center">
                        <i class="fas fa-calendar-alt text-cyan-500 mr-2"></i>
                        <?php echo formatDate($package['travel_date']); ?>
                      </p>
                    </div>
                    <div>
                      <p class="text-gray-600 text-sm font-semibold">Travelers</p>
                      <p class="flex items-center">
                        <i class="fas fa-users text-cyan-500 mr-2"></i>
                        <?php echo htmlspecialchars($package['num_travelers']); ?> Person(s)
                      </p>
                    </div>
                    <div>
                      <p class="text-gray-600 text-sm font-semibold">Total Price</p>
                      <p class="text-cyan-600 font-bold">
                        <i class="fas fa-tag text-cyan-500 mr-2"></i>
                        Rs<?php echo number_format($package['total_price'], 2); ?>
                      </p>
                    </div>
                  </div>

                  <div class="flex flex-wrap mb-4">
                    <span class="package-type-badge mr-2 rating-badge">
                      <?php echo displayStarRating($package['star_rating']); ?>
                    </span>
                  </div>

                  <div class="flex flex-wrap gap-2 mt-4">
                    <button class="bg-cyan-500 hover:bg-cyan-600 text-white py-2 px-4 rounded-xl text-sm font-medium"
                      data-bs-toggle="modal" data-bs-target="#detailsModal<?php echo $package['id']; ?>">
                      <i class="fas fa-info-circle mr-1"></i> View Details
                    </button>

                    <!-- Show these buttons only for pending bookings -->
                    <?php if ($package['booking_status'] == 'pending'): ?>
                      <form method="POST" class="inline cancel-form">
                        <input type="hidden" name="booking_id" value="<?php echo $package['id']; ?>">
                        <button type="submit" name="cancel_booking" class="bg-yellow-500 hover:bg-yellow-600 text-white py-2 px-4 rounded-xl text-sm font-medium">
                          <i class="fas fa-times-circle mr-1"></i> Cancel
                        </button>
                      </form>

                      <!-- <a href="../payment.php?booking_id=<?php echo $package['id']; ?>" class="bg-green-500 hover:bg-green-600 text-white py-2 px-4 rounded-xl text-sm font-medium">
                        <i class="fas fa-credit-card mr-1"></i> Make Payment
                      </a> -->
                    <?php endif; ?>

                    <!-- For confirmed bookings, show download voucher button -->
                    <?php if ($package['booking_status'] == 'confirmed'): ?>
                      <a href="download-voucher.php?booking_id=<?php echo $package['id']; ?>" class="bg-indigo-500 hover:bg-indigo-600 text-white py-2 px-4 rounded-xl text-sm font-medium">
                        <i class="fas fa-download mr-1"></i> Download Voucher
                      </a>
                    <?php endif; ?>

                    <!-- For completed bookings, show feedback option -->
                    <?php if ($package['booking_status'] == 'completed' && empty($package['feedback_submitted'])): ?>
                      <a href="feedback.php?booking_id=<?php echo $package['id']; ?>" class="bg-purple-500 hover:bg-purple-600 text-white py-2 px-4 rounded-xl text-sm font-medium">
                        <i class="fas fa-star mr-1"></i> Leave Feedback
                      </a>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Details Modal for each booking -->
          <div class="modal fade" id="detailsModal<?php echo $package['id']; ?>" tabindex="-1" aria-labelledby="detailsModalLabel<?php echo $package['id']; ?>" aria-hidden="true">
            <div class="modal-dialog modal-lg">
              <div class="modal-content">
                <div class="modal-header bg-gradient-to-r from-cyan-600 to-teal-500 text-white">
                  <h5 class="modal-title text-xl font-bold" id="detailsModalLabel<?php echo $package['id']; ?>">
                    <?php echo htmlspecialchars($package['title']); ?> - Details
                  </h5>
                  <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                  <div class="mb-4">
                    <div class="flex justify-between items-center mb-2">
                      <h6 class="font-bold text-gray-700">Booking Information</h6>
                      <span class="status-badge <?php echo getStatusBadgeClass($package['booking_status']); ?>">
                        <?php echo ucfirst(htmlspecialchars($package['booking_status'])); ?>
                      </span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 bg-gray-50 p-4 rounded-lg">
                      <div>
                        <p class="text-sm"><strong>Booking Reference:</strong> <?php echo htmlspecialchars($package['booking_reference']); ?></p>
                        <p class="text-sm"><strong>Booking Date:</strong> <?php echo formatDate($package['created_at']); ?></p>
                        <p class="text-sm"><strong>Travel Date:</strong> <?php echo formatDate($package['travel_date']); ?></p>
                      </div>
                      <div>
                        <p class="text-sm"><strong>Number of Travelers:</strong> <?php echo htmlspecialchars($package['num_travelers']); ?></p>
                        <p class="text-sm"><strong>Payment Status:</strong>
                          <span class="inline-block px-2 py-1 text-xs rounded-full 
                          <?php echo $package['payment_status'] == 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                            <?php echo ucfirst($package['payment_status']); ?>
                          </span>
                        </p>
                        <p class="text-sm"><strong>Total Amount:</strong> <span class="font-semibold text-cyan-600">Rs<?php echo number_format($package['total_price'], 2); ?></span></p>
                      </div>
                    </div>
                  </div>

                  <hr class="my-4">

                  <div class="mb-4">
                    <h6 class="font-bold text-gray-700 mb-2">Package Details</h6>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div>
                        <p class="text-sm mb-2"><strong>Category:</strong> <?php echo displayStarRating($package['star_rating']); ?></p>
                        <p class="text-sm"><strong>Duration:</strong> <?php echo htmlspecialchars($package['total_days']); ?> Days</p>
                      </div>
                      <div>
                        <p class="text-sm mb-2"><strong>Makkah Stay:</strong> <?php echo htmlspecialchars($package['makkah_nights']); ?> Nights</p>
                        <p class="text-sm mb-2"><strong>Madinah Stay:</strong> <?php echo htmlspecialchars($package['madinah_nights']); ?> Nights</p>
                      </div>
                    </div>

                    <div class="mt-3">
                      <p class="text-sm font-bold mb-1">Package Inclusions:</p>
                      <div class="flex flex-wrap">
                        <?php
                        $package_inclusions = parseInclusions($package['inclusions']);
                        if (!empty($package_inclusions)):
                          foreach ($package_inclusions as $inclusion):
                        ?>
                            <span class="inclusion-badge">
                              <?php
                              switch ($inclusion) {
                                case 'flight':
                                  echo '<i class="fas fa-plane-departure mr-1"></i> Flight';
                                  break;
                                case 'hotel':
                                  echo '<i class="fas fa-hotel mr-1"></i> Hotel';
                                  break;
                                case 'transport':
                                  echo '<i class="fas fa-bus mr-1"></i> Transport';
                                  break;
                                case 'guide':
                                  echo '<i class="fas fa-user-tie mr-1"></i> Guide';
                                  break;
                                case 'vip_services':
                                  echo '<i class="fas fa-star mr-1"></i> VIP Services';
                                  break;
                              }
                              ?>
                            </span>
                          <?php
                          endforeach;
                        else:
                          ?>
                          <p class="text-sm text-gray-500">No inclusions specified</p>
                        <?php endif; ?>
                      </div>
                    </div>

                    <?php if (!empty($package['description'])): ?>
                      <div class="mt-3">
                        <p class="text-sm font-bold mb-1">Package Description:</p>
                        <p class="text-sm text-gray-600"><?php echo nl2br(htmlspecialchars($package['description'])); ?></p>
                      </div>
                    <?php endif; ?>
                  </div>

                  <?php if (!empty($package['special_requests'])): ?>
                    <hr class="my-4">
                    <div class="mb-4">
                      <h6 class="font-bold text-gray-700 mb-2">Special Requests</h6>
                      <p class="text-sm text-gray-600 bg-gray-50 p-3 rounded"><?php echo nl2br(htmlspecialchars($package['special_requests'])); ?></p>
                    </div>
                  <?php endif; ?>

                  <hr class="my-4">

                  <!-- Booking Timeline -->
                  <div>
                    <h6 class="font-bold text-gray-700 mb-3">Booking Timeline</h6>
                    <div class="timeline">
                      <div class="timeline-item">
                        <div class="timeline-marker"></div>
                        <div class="timeline-date"><?php echo formatDate($package['created_at']); ?></div>
                        <div class="timeline-content">
                          <p class="font-medium">Booking Created</p>
                          <p class="text-sm text-gray-600">Your booking was successfully created.</p>
                        </div>
                      </div>

                      <?php if ($package['booking_status'] != 'pending'): ?>
                        <div class="timeline-item">
                          <div class="timeline-marker"></div>
                          <div class="timeline-date">
                            <!-- This would be from an actual status update timestamp in a real application -->
                            <?php echo date('d M Y', strtotime($package['created_at'] . ' +1 day')); ?>
                          </div>
                          <div class="timeline-content">
                            <p class="font-medium">Status Updated</p>
                            <p class="text-sm text-gray-600">Your booking status was updated to: <strong><?php echo ucfirst($package['booking_status']); ?></strong></p>
                          </div>
                        </div>
                      <?php endif; ?>

                      <?php if ($package['payment_status'] == 'paid'): ?>
                        <div class="timeline-item">
                          <div class="timeline-marker"></div>
                          <div class="timeline-date">
                            <!-- This would be from an actual payment timestamp in a real application -->
                            <?php echo date('d M Y', strtotime($package['created_at'] . ' +2 day')); ?>
                          </div>
                          <div class="timeline-content">
                            <p class="font-medium">Payment Received</p>
                            <p class="text-sm text-gray-600">Payment of Rs<?php echo number_format($package['total_price'], 2); ?> was received.</p>
                          </div>
                        </div>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg text-sm font-medium" data-bs-dismiss="modal">Close</button>

                  <?php if ($package['booking_status'] == 'pending'): ?>
                    <!-- <a href="../payment.php?booking_id=<?php echo $package['id']; ?>" class="bg-green-500 hover:bg-green-600 text-white py-2 px-4 rounded-lg text-sm font-medium">
                      <i class="fas fa-credit-card mr-1"></i> Make Payment
                    </a> -->
                  <?php endif; ?>

                  <?php if ($package['booking_status'] == 'confirmed'): ?>
                    <a href="download-voucher.php?booking_id=<?php echo $package['id']; ?>" class="bg-indigo-500 hover:bg-indigo-600 text-white py-2 px-4 rounded-lg text-sm font-medium">
                      <i class="fas fa-download mr-1"></i> Download Voucher
                    </a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <script>
    // Cancel booking confirmation
    document.querySelectorAll('.cancel-form').forEach(form => {
      form.addEventListener('submit', (e) => {
        e.preventDefault();
        Swal.fire({
          title: 'Cancel this booking?',
          text: 'Are you sure you want to cancel this booking? This action cannot be undone.',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#f59e0b',
          cancelButtonColor: '#6b7280',
          confirmButtonText: 'Yes, cancel it!',
          cancelButtonText: 'No, keep it'
        }).then((result) => {
          if (result.isConfirmed) {
            form.submit();
          }
        });
      });
    });

    // Show booking message (from session)
    <?php if (isset($_SESSION['booking_message'])): ?>
      Swal.fire({
        icon: '<?php echo $_SESSION['booking_message_type'] == 'success' ? 'success' : 'error'; ?>',
        title: '<?php echo $_SESSION['booking_message_type'] == 'success' ? 'Success!' : 'Error!'; ?>',
        text: '<?php echo addslashes($_SESSION['booking_message']); ?>',
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