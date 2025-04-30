<?php
require_once '../config/db.php';
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

$user_id = $_SESSION['user_id'];
$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Debug information - can be removed after fixing
error_log("Transport details page accessed. ID: " . $booking_id);

if ($booking_id <= 0) {
  header("Location: index.php");
  exit();
}

// Fetch transportation booking details
$transport_query = $conn->prepare("
    SELECT tb.id, tb.transport_type, tb.route_name, tb.vehicle_type, tb.price, 
           tb.booking_status, tb.pickup_date, tb.pickup_time, tb.dropoff_location,
           tb.pickup_location, tb.booking_reference, tb.num_passengers, tb.created_at,
           tb.special_instructions, tb.payment_status, tb.driver_name, tb.driver_contact
    FROM transportation_bookings tb
    WHERE tb.id = ? AND tb.user_id = ?
");

$transport_query->bind_param("ii", $booking_id, $user_id);
$transport_query->execute();
$result = $transport_query->get_result();

if ($result->num_rows === 0) {
  // No booking found or doesn't belong to this user
  header("Location: index.php");
  exit();
}

$transport = $result->fetch_assoc();
$transport_query->close();

// Handle booking cancellation
if (isset($_POST['cancel_booking'])) {
  // Check if booking is in pending status
  if ($transport['booking_status'] === 'pending') {
    $cancel_stmt = $conn->prepare("UPDATE transportation_bookings SET booking_status = 'cancelled' WHERE id = ? AND user_id = ?");
    $cancel_stmt->bind_param("ii", $booking_id, $user_id);
    
    if ($cancel_stmt->execute()) {
      $_SESSION['booking_message'] = "Transportation booking successfully cancelled.";
      $_SESSION['booking_message_type'] = "success";
    } else {
      $_SESSION['booking_message'] = "Error cancelling booking. Please try again.";
      $_SESSION['booking_message_type'] = "error";
    }
    
    $cancel_stmt->close();
    header("Location: index.php");
    exit();
  } else {
    $_SESSION['booking_message'] = "This booking cannot be cancelled.";
    $_SESSION['booking_message_type'] = "error";
    header("Location: transportation-details.php?id=" . $booking_id);
    exit();
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Transportation Details - UmrahFlights</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
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

    .detail-card {
      background-color: #ffffff;
      border-radius: 16px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
      overflow: hidden;
    }

    .detail-header {
      background: linear-gradient(to right, #0891b2, #14b8a6);
      color: white;
      padding: 30px;
    }

    .detail-body {
      padding: 30px;
    }

    .info-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      gap: 20px;
    }

    .info-item {
      margin-bottom: 16px;
    }

    .info-label {
      color: #6b7280;
      font-size: 0.875rem;
      margin-bottom: 4px;
    }

    .info-value {
      font-weight: 500;
      color: #111827;
    }

    @media (max-width: 768px) {
      .info-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>

<body class="bg-gray-100 min-h-screen">
  <!-- Navbar -->
  <nav class="bg-gradient-to-r from-cyan-600 to-teal-500 p-4 shadow-lg">
    <div class="container mx-auto flex justify-between items-center">
      <!-- Left side: Logo and Title -->
      <div class="flex items-center space-x-4">
        <span class="text-white text-3xl font-extrabold tracking-tight">Ummrah</span>
      </div>
      <!-- Right side: Go Back Button -->
      <div>
        <a href="index.php" class="bg-white text-cyan-600 px-5 py-2 rounded-full hover:bg-gray-100 transition duration-300 font-semibold shadow-md">My Bookings</a>
      </div>
    </div>
  </nav>

  <!-- Main Content -->
  <div class="container mx-auto px-4 py-12">
    <div class="text-center mb-8">
      <h2 class="text-3xl font-bold text-gray-900">Transportation Booking Details</h2>
      <p class="text-gray-600 mt-2">View the details of your transportation booking</p>
    </div>

    <?php if (isset($_SESSION['booking_message'])): ?>
      <div class="alert alert-<?php echo $_SESSION['booking_message_type'] === 'success' ? 'success' : 'danger'; ?> mb-4">
        <?php echo $_SESSION['booking_message']; ?>
      </div>
      <?php unset($_SESSION['booking_message']); unset($_SESSION['booking_message_type']); ?>
    <?php endif; ?>

    <div class="detail-card max-w-4xl mx-auto mb-8">
      <div class="detail-header">
        <div class="flex justify-between items-center">
          <h3 class="text-2xl font-bold"><?php echo htmlspecialchars($transport['route_name']); ?></h3>
          <span class="status-badge status-<?php echo htmlspecialchars($transport['booking_status']); ?>">
            <?php echo ucfirst(htmlspecialchars($transport['booking_status'])); ?>
          </span>
        </div>
        <p class="mt-2 opacity-90">Booking Reference: <?php echo htmlspecialchars($transport['booking_reference'] ?? 'Not assigned yet'); ?></p>
      </div>
      
      <div class="detail-body">
        <div class="info-grid">
          <div class="info-item">
            <div class="info-label">Transport Type</div>
            <div class="info-value"><?php echo ucfirst(htmlspecialchars($transport['transport_type'])); ?></div>
          </div>
          
          <div class="info-item">
            <div class="info-label">Vehicle Type</div>
            <div class="info-value"><?php echo htmlspecialchars($transport['vehicle_type']); ?></div>
          </div>
          
          <div class="info-item">
            <div class="info-label">Pickup Date</div>
            <div class="info-value"><?php echo date('d M Y', strtotime($transport['pickup_date'])); ?></div>
          </div>
          
          <div class="info-item">
            <div class="info-label">Pickup Time</div>
            <div class="info-value"><?php echo date('h:i A', strtotime($transport['pickup_time'])); ?></div>
          </div>
          
          <div class="info-item">
            <div class="info-label">Pickup Location</div>
            <div class="info-value"><?php echo htmlspecialchars($transport['pickup_location']); ?></div>
          </div>
          
          <div class="info-item">
            <div class="info-label">Dropoff Location</div>
            <div class="info-value"><?php echo htmlspecialchars($transport['dropoff_location']); ?></div>
          </div>
          
          <div class="info-item">
            <div class="info-label">Number of Passengers</div>
            <div class="info-value"><?php echo htmlspecialchars($transport['num_passengers']); ?></div>
          </div>
          
          <div class="info-item">
            <div class="info-label">Price</div>
            <div class="info-value text-cyan-600 font-bold">Rs<?php echo number_format($transport['price'], 2); ?></div>
          </div>
          
          <div class="info-item">
            <div class="info-label">Payment Status</div>
            <div class="info-value">
              <?php if ($transport['payment_status'] === 'paid'): ?>
                <span class="text-green-600 font-medium">Paid</span>
              <?php else: ?>
                <span class="text-yellow-600 font-medium">Pending</span>
              <?php endif; ?>
            </div>
          </div>
          
          <div class="info-item">
            <div class="info-label">Booking Date</div>
            <div class="info-value"><?php echo date('d M Y, h:i A', strtotime($transport['created_at'])); ?></div>
          </div>
        </div>
        
        <?php if (!empty($transport['driver_name']) || !empty($transport['driver_contact'])): ?>
          <div class="mt-8 p-4 bg-gray-50 rounded-lg">
            <h4 class="text-lg font-semibold mb-3 text-gray-800">Driver Information</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <div class="info-label">Driver Name</div>
                <div class="info-value"><?php echo htmlspecialchars($transport['driver_name'] ?? 'Not assigned yet'); ?></div>
              </div>
              <div>
                <div class="info-label">Driver Contact</div>
                <div class="info-value"><?php echo htmlspecialchars($transport['driver_contact'] ?? 'Not assigned yet'); ?></div>
              </div>
            </div>
          </div>
        <?php endif; ?>
        
        <?php if (!empty($transport['special_instructions'])): ?>
          <div class="mt-6">
            <h4 class="text-lg font-semibold mb-2 text-gray-800">Special Instructions</h4>
            <p class="p-4 bg-gray-50 rounded-lg text-gray-700"><?php echo nl2br(htmlspecialchars($transport['special_instructions'])); ?></p>
          </div>
        <?php endif; ?>
        
        <div class="mt-8 flex flex-wrap gap-4">
          <a href="index.php" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-6 rounded-lg font-medium">Back to My Bookings</a>
          
          <?php if ($transport['booking_status'] === 'pending'): ?>
            <form method="POST" id="cancelForm">
              <input type="hidden" name="cancel_booking" value="1">
              <button type="button" onclick="confirmCancel()" class="bg-yellow-500 hover:bg-yellow-600 text-white py-2 px-6 rounded-lg font-medium">Cancel Booking</button>
            </form>
          <?php endif; ?>
          
          <?php if ($transport['booking_status'] === 'confirmed'): ?>
            <a href="#" class="bg-cyan-500 hover:bg-cyan-600 text-white py-2 px-6 rounded-lg font-medium">Download Voucher</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
    
    <!-- Trip Information -->
    <div class="detail-card max-w-4xl mx-auto">
      <div class="detail-header">
        <h3 class="text-xl font-bold">Trip Information</h3>
      </div>
      <div class="detail-body">
        <div class="flex flex-col md:flex-row items-start gap-8">
          <div class="flex-1">
            <div class="relative pb-8 pl-8 border-l-2 border-cyan-500">
              <div class="absolute top-0 left-0 w-4 h-4 bg-cyan-500 rounded-full transform -translate-x-1/2"></div>
              <h4 class="text-lg font-medium text-gray-900 mb-1">Pickup</h4>
              <p class="text-gray-600"><?php echo htmlspecialchars($transport['pickup_location']); ?></p>
              <p class="text-sm text-gray-500 mt-1">
                <?php echo date('d M Y, h:i A', strtotime($transport['pickup_date'] . ' ' . $transport['pickup_time'])); ?>
              </p>
            </div>
            <div class="relative pl-8">
              <div class="absolute top-0 left-0 w-4 h-4 bg-cyan-500 rounded-full transform -translate-x-1/2"></div>
              <h4 class="text-lg font-medium text-gray-900 mb-1">Dropoff</h4>
              <p class="text-gray-600"><?php echo htmlspecialchars($transport['dropoff_location']); ?></p>
            </div>
          </div>
          
          <div class="bg-gray-50 p-5 rounded-lg w-full md:w-64 mt-8 md:mt-0">
            <h4 class="text-lg font-semibold mb-3 text-gray-800">Vehicle Details</h4>
            <p class="text-gray-600 mb-3">
              <span class="font-medium text-gray-700">Type:</span> <?php echo htmlspecialchars($transport['vehicle_type']); ?>
            </p>
            <p class="text-gray-600 mb-3">
              <span class="font-medium text-gray-700">Passengers:</span> <?php echo htmlspecialchars($transport['num_passengers']); ?>
            </p>
            <div class="border-t border-gray-200 pt-3 mt-3">
              <p class="text-sm text-gray-500">
                All transportation services are subject to availability and traffic conditions.
              </p>
            </div>
          </div>
        </div>
        
        <div class="mt-8 bg-gray-50 p-5 rounded-lg">
          <h4 class="text-lg font-semibold mb-3 text-gray-800">Transportation Policy</h4>
          <ul class="text-sm text-gray-600 space-y-2">
            <li>• Please be ready at least 15 minutes before your scheduled pickup time.</li>
            <li>• Driver will wait for a maximum of 30 minutes after the scheduled pickup time.</li>
            <li>• In case of any changes or delays, please contact customer support immediately.</li>
            <li>• Free cancellation is available up to 24 hours before the scheduled pickup time.</li>
            <li>• Additional waiting time may incur extra charges.</li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <?php include '../includes/footer.php'; ?>
  <?php include '../includes/js-links.php'; ?>

  <script>
    function confirmCancel() {
      Swal.fire({
        title: 'Are you sure?',
        text: 'Do you want to cancel this transportation booking?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f59e0b',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, cancel it!'
      }).then((result) => {
        if (result.isConfirmed) {
          document.getElementById('cancelForm').submit();
        }
      });
    }
  </script>
</body>

</html>