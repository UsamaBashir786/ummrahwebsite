<?php
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

$user_id = $_SESSION['user_id'];

// Fetch transportation bookings
$transport_query = $conn->prepare("
    SELECT tb.id, tb.transport_type, tb.route_name, tb.vehicle_type, tb.price, tb.booking_status, 
           tb.pickup_date, tb.pickup_time, tb.pickup_location, tb.additional_notes
    FROM transportation_bookings tb
    WHERE tb.user_id = ?
    ORDER BY tb.created_at DESC
");
$transport_query->bind_param("i", $user_id);
$transport_query->execute();
$transports = $transport_query->get_result()->fetch_all(MYSQLI_ASSOC);
$transport_query->close();

// Handle cancellation
if (isset($_POST['cancel_booking'])) {
  $booking_id = $_POST['booking_id'] ?? 0;

  $check_stmt = $conn->prepare("SELECT id FROM transportation_bookings WHERE id = ? AND user_id = ? AND booking_status = 'pending'");
  $check_stmt->bind_param("ii", $booking_id, $user_id);
  $check_stmt->execute();
  $check_result = $check_stmt->get_result();

  if ($check_result->num_rows > 0) {
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
  }
  $check_stmt->close();

  header("Location: transportation.php");
  exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Transportation Bookings - UmrahFlights</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <style>
    .booking-card {
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .booking-card:hover {
      transform: translateY(-8px);
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

    .transport-icon {
      width: 60px;
      height: 60px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .transport-taxi {
      background-color: #fef3c7;
      color: #d97706;
    }

    .transport-rentacar {
      background-color: #dbeafe;
      color: #1d4ed8;
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
          <h1 class="text-2xl font-bold text-gray-800">My Transportation Bookings</h1>
          <p class="text-gray-600">Manage and track your transportation reservations</p>
        </div>
        <a href="../transportation.php" class="bg-cyan-600 text-white px-4 py-2 rounded-lg hover:bg-cyan-700 transition">
          <i class="fas fa-plus mr-2"></i>Book Transportation
        </a>
      </div>
    </div>

    <!-- Filter Section -->
    <div class="bg-white shadow-lg rounded-lg p-5 mb-6">
      <div class="flex flex-col md:flex-row gap-4">
        <select id="statusFilter" class="form-select rounded-lg border-gray-300">
          <option value="">All Statuses</option>
          <option value="pending">Pending</option>
          <option value="confirmed">Confirmed</option>
          <option value="cancelled">Cancelled</option>
          <option value="completed">Completed</option>
        </select>
        <select id="typeFilter" class="form-select rounded-lg border-gray-300">
          <option value="">All Types</option>
          <option value="taxi">Taxi</option>
          <option value="rentacar">Rent a Car</option>
        </select>
        <input type="text" id="searchInput" class="form-control rounded-lg border-gray-300" placeholder="Search by route or location...">
      </div>
    </div>

    <!-- Transportation Bookings Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php if (empty($transports)): ?>
        <div class="col-span-full text-center py-12">
          <img src="assets/images/no-transport.svg" alt="No transportation" class="w-48 h-48 mx-auto mb-4">
          <p class="text-gray-500 text-lg">No transportation bookings found.</p>
          <a href="../transportation.php" class="mt-4 inline-block bg-cyan-600 text-white px-6 py-2 rounded-lg hover:bg-cyan-700 transition">
            Book Transportation
          </a>
        </div>
      <?php else: ?>
        <?php foreach ($transports as $transport): ?>
          <div class="booking-card bg-white p-6"
            data-status="<?php echo htmlspecialchars($transport['booking_status']); ?>"
            data-type="<?php echo htmlspecialchars($transport['transport_type']); ?>">
            <div class="flex justify-between items-center mb-4">
              <div class="flex items-center">
                <div class="transport-icon transport-<?php echo htmlspecialchars($transport['transport_type']); ?> mr-4">
                  <i class="fas fa-<?php echo $transport['transport_type'] == 'taxi' ? 'taxi' : 'car'; ?> text-2xl"></i>
                </div>
                <div>
                  <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($transport['route_name']); ?></h3>
                  <p class="text-sm text-gray-600"><?php echo ucfirst(htmlspecialchars($transport['transport_type'])); ?></p>
                </div>
              </div>
              <span class="status-badge status-<?php echo htmlspecialchars($transport['booking_status']); ?>">
                <?php echo ucfirst(htmlspecialchars($transport['booking_status'])); ?>
              </span>
            </div>
            <p class="text-gray-600 mb-2 flex items-center">
              <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10"></path>
              </svg>
              Vehicle: <?php echo htmlspecialchars($transport['vehicle_type']); ?>
            </p>
            <p class="text-gray-600 mb-2 flex items-center">
              <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
              </svg>
              Pickup: <?php echo date('d M Y, H:i', strtotime($transport['pickup_date'] . ' ' . $transport['pickup_time'])); ?>
            </p>
            <p class="text-gray-600 mb-2 flex items-center">
              <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
              </svg>
              From: <?php echo htmlspecialchars($transport['pickup_location']); ?>
            </p>

            <p class="text-cyan-600 font-bold text-lg mb-4">Rs<?php echo number_format($transport['price'], 2); ?></p>
            <div class="flex gap-3">
              <button class="bg-cyan-500 hover:bg-cyan-600 text-white py-2 px-4 rounded-xl font-medium"
                data-bs-toggle="modal" data-bs-target="#detailsModal"
                onclick="showTransportDetails(<?php echo htmlspecialchars(json_encode($transport), ENT_QUOTES, 'UTF-8'); ?>)">
                View Details
              </button>
              <?php if ($transport['booking_status'] == 'pending'): ?>
                <form method="POST" class="cancel-form">
                  <input type="hidden" name="booking_id" value="<?php echo $transport['id']; ?>">
                  <button type="submit" name="cancel_booking" class="bg-yellow-500 hover:bg-yellow-600 text-white py-2 px-4 rounded-xl font-medium">
                    Cancel
                  </button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Details Modal -->
  <div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header bg-gradient-to-r from-cyan-600 to-teal-500 text-white">
          <h5 class="modal-title text-xl font-bold" id="detailsModalLabel">Transportation Details</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="modalContent"></div>
        <div class="modal-footer">
          <button type="button" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-xl font-medium" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Filter functionality
    const statusFilter = document.getElementById('statusFilter');
    const typeFilter = document.getElementById('typeFilter');
    const searchInput = document.getElementById('searchInput');
    const bookingCards = document.querySelectorAll('.booking-card');

    function filterBookings() {
      const status = statusFilter.value.toLowerCase();
      const type = typeFilter.value.toLowerCase();
      const search = searchInput.value.toLowerCase();

      bookingCards.forEach(card => {
        const cardStatus = card.dataset.status.toLowerCase();
        const cardType = card.dataset.type.toLowerCase();
        const cardText = card.textContent.toLowerCase();

        const statusMatch = !status || cardStatus === status;
        const typeMatch = !type || cardType === type;
        const searchMatch = !search || cardText.includes(search);

        card.style.display = statusMatch && typeMatch && searchMatch ? 'block' : 'none';
      });
    }

    statusFilter.addEventListener('change', filterBookings);
    typeFilter.addEventListener('change', filterBookings);
    searchInput.addEventListener('input', filterBookings);

    // Show transport details
    function showTransportDetails(transport) {
      document.getElementById('detailsModalLabel').textContent = `${transport.route_name} Details`;
      document.getElementById('modalContent').innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="font-bold mb-3">Transport Information</h6>
                        <p><strong>Route:</strong> ${transport.route_name}</p>
                        <p><strong>Transport Type:</strong> ${transport.transport_type.charAt(0).toUpperCase() + transport.transport_type.slice(1)}</p>
                        <p><strong>Vehicle Type:</strong> ${transport.vehicle_type}</p>
                        <p><strong>Status:</strong> <span class="status-badge status-${transport.booking_status}">${transport.booking_status.charAt(0).toUpperCase() + transport.booking_status.slice(1)}</span></p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="font-bold mb-3">Pickup & Drop-off</h6>
                        <p><strong>Pickup Date:</strong> ${new Date(transport.pickup_date).toLocaleDateString()}</p>
                        <p><strong>Pickup Time:</strong> ${transport.pickup_time}</p>
                        <p><strong>Pickup Location:</strong> ${transport.pickup_location}</p>
                    </div>
                </div>
                ${transport.additional_notes ? `
                <hr class="my-4">
                <div class="row">
                    <div class="col-12">
                        <h6 class="font-bold mb-3">Additional Notes</h6>
                        <p>${transport.additional_notes}</p>
                    </div>
                </div>
                ` : ''}
                <hr class="my-4">
                <div class="row">
                    <div class="col-12">
                        <h6 class="font-bold mb-3">Payment Details</h6>
                        <p><strong>Total Price:</strong> Rs${parseFloat(transport.price).toFixed(2)}</p>
                    </div>
                </div>
            `;
    }

    // Cancel booking confirmation
    document.querySelectorAll('.cancel-form').forEach(form => {
      form.addEventListener('submit', (e) => {
        e.preventDefault();
        Swal.fire({
          title: 'Are you sure?',
          text: 'This transportation booking will be cancelled.',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#f59e0b',
          cancelButtonColor: '#6b7280',
          confirmButtonText: 'Yes, cancel it!'
        }).then((result) => {
          if (result.isConfirmed) {
            form.submit();
          }
        });
      });
    });

    // Show booking message
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