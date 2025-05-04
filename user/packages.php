<?php
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

$user_id = $_SESSION['user_id'];

// Fetch package bookings
$packages_query = $conn->prepare("
    SELECT pb.id, pb.package_id, pb.travel_date, pb.num_travelers, pb.total_price, pb.booking_status, pb.booking_reference,
           up.title, up.package_type, up.duration, up.description, up.package_image
    FROM package_bookings pb
    JOIN umrah_packages up ON pb.package_id = up.id
    WHERE pb.user_id = ?
    ORDER BY pb.created_at DESC
");
$packages_query->bind_param("i", $user_id);
$packages_query->execute();
$packages = $packages_query->get_result()->fetch_all(MYSQLI_ASSOC);
$packages_query->close();

// Handle cancellation
if (isset($_POST['cancel_booking'])) {
  $booking_id = $_POST['booking_id'] ?? 0;

  $check_stmt = $conn->prepare("SELECT id FROM package_bookings WHERE id = ? AND user_id = ? AND booking_status = 'pending'");
  $check_stmt->bind_param("ii", $booking_id, $user_id);
  $check_stmt->execute();
  $check_result = $check_stmt->get_result();

  if ($check_result->num_rows > 0) {
    $cancel_stmt = $conn->prepare("UPDATE package_bookings SET booking_status = 'cancelled' WHERE id = ? AND user_id = ?");
    $cancel_stmt->bind_param("ii", $booking_id, $user_id);

    if ($cancel_stmt->execute()) {
      $_SESSION['booking_message'] = "Package booking successfully cancelled.";
      $_SESSION['booking_message_type'] = "success";
    } else {
      $_SESSION['booking_message'] = "Error cancelling booking. Please try again.";
      $_SESSION['booking_message_type'] = "error";
    }
    $cancel_stmt->close();
  }
  $check_stmt->close();

  header("Location: packages.php");
  exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Package Bookings - UmrahFlights</title>
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

    .type-single {
      background-color: #e0f2fe;
      color: #0369a1;
    }

    .type-group {
      background-color: #f3e8ff;
      color: #7c3aed;
    }

    .type-vip {
      background-color: #fef3c7;
      color: #d97706;
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
          <h1 class="text-2xl font-bold text-gray-800">My Package Bookings</h1>
          <p class="text-gray-600">Manage and track your Umrah package reservations</p>
        </div>
        <a href="../packages.php" class="bg-cyan-600 text-white px-4 py-2 rounded-lg hover:bg-cyan-700 transition">
          <i class="fas fa-plus mr-2"></i>Book New Package
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
          <option value="single">Single</option>
          <option value="group">Group</option>
          <option value="vip">VIP</option>
        </select>
        <input type="text" id="searchInput" class="form-control rounded-lg border-gray-300" placeholder="Search by package name or reference...">
      </div>
    </div>

    <!-- Package Bookings Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php if (empty($packages)): ?>
        <div class="col-span-full text-center py-12">
          <img src="assets/images/no-packages.svg" alt="No packages" class="w-48 h-48 mx-auto mb-4">
          <p class="text-gray-500 text-lg">No package bookings found.</p>
          <a href="../packages.php" class="mt-4 inline-block bg-cyan-600 text-white px-6 py-2 rounded-lg hover:bg-cyan-700 transition">
            Book Your First Package
          </a>
        </div>
      <?php else: ?>
        <?php foreach ($packages as $package): ?>
          <div class="booking-card bg-white overflow-hidden"
            data-status="<?php echo htmlspecialchars($package['booking_status']); ?>"
            data-type="<?php echo htmlspecialchars($package['package_type']); ?>"
            data-reference="<?php echo htmlspecialchars($package['booking_reference']); ?>">
            <?php if ($package['package_image']): ?>
              <img src="../admin/<?php echo htmlspecialchars($package['package_image']); ?>" alt="<?php echo htmlspecialchars($package['title']); ?>" class="package-image w-full">
            <?php else: ?>
              <div class="package-image w-full bg-gray-200 flex items-center justify-center">
                <i class="fas fa-box text-4xl text-gray-400"></i>
              </div>
            <?php endif; ?>

            <div class="p-6">
              <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($package['title']); ?></h3>
                <span class="status-badge status-<?php echo htmlspecialchars($package['booking_status']); ?>">
                  <?php echo ucfirst(htmlspecialchars($package['booking_status'])); ?>
                </span>
              </div>
              <div class="mb-3">
                <span class="package-type-badge type-<?php echo htmlspecialchars($package['package_type']); ?>">
                  <?php echo ucfirst(htmlspecialchars($package['package_type'])); ?> Package
                </span>
              </div>
              <p class="text-gray-600 mb-2 flex items-center">
                <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                Travel Date: <?php echo date('d M Y', strtotime($package['travel_date'])); ?>
              </p>
              <p class="text-gray-600 mb-2 flex items-center">
                <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Duration: <?php echo htmlspecialchars($package['duration']); ?> Days
              </p>
              <p class="text-gray-600 mb-2 flex items-center">
                <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
                <?php echo htmlspecialchars($package['num_travelers']); ?> Traveler(s)
              </p>
              <p class="text-cyan-600 font-bold text-lg mb-4">Rs<?php echo number_format($package['total_price'], 2); ?></p>
              <div class="flex gap-3">
                <button class="bg-cyan-500 hover:bg-cyan-600 text-white py-2 px-4 rounded-xl font-medium"
                  data-bs-toggle="modal" data-bs-target="#detailsModal"
                  onclick="showPackageDetails(<?php echo htmlspecialchars(json_encode($package), ENT_QUOTES, 'UTF-8'); ?>)">
                  View Details
                </button>
                <?php if ($package['booking_status'] == 'pending'): ?>
                  <form method="POST" class="cancel-form">
                    <input type="hidden" name="booking_id" value="<?php echo $package['id']; ?>">
                    <button type="submit" name="cancel_booking" class="bg-yellow-500 hover:bg-yellow-600 text-white py-2 px-4 rounded-xl font-medium">
                      Cancel
                    </button>
                  </form>
                <?php endif; ?>
              </div>
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
          <h5 class="modal-title text-xl font-bold" id="detailsModalLabel">Package Details</h5>
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
        const cardReference = card.dataset.reference ? card.dataset.reference.toLowerCase() : '';
        const cardText = card.textContent.toLowerCase();

        const statusMatch = !status || cardStatus === status;
        const typeMatch = !type || cardType === type;
        const searchMatch = !search || cardText.includes(search) || cardReference.includes(search);

        card.style.display = statusMatch && typeMatch && searchMatch ? 'block' : 'none';
      });
    }

    statusFilter.addEventListener('change', filterBookings);
    typeFilter.addEventListener('change', filterBookings);
    searchInput.addEventListener('input', filterBookings);

    // Show package details
    function showPackageDetails(package) {
      document.getElementById('detailsModalLabel').textContent = `${package.title} Details`;
      document.getElementById('modalContent').innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="font-bold mb-3">Package Information</h6>
                        <p><strong>Package Name:</strong> ${package.title}</p>
                        <p><strong>Package Type:</strong> ${package.package_type.charAt(0).toUpperCase() + package.package_type.slice(1)}</p>
                        <p><strong>Duration:</strong> ${package.duration} Days</p>
                        <p><strong>Status:</strong> <span class="status-badge status-${package.booking_status}">${package.booking_status.charAt(0).toUpperCase() + package.booking_status.slice(1)}</span></p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="font-bold mb-3">Travel Details</h6>
                        <p><strong>Travel Date:</strong> ${new Date(package.travel_date).toLocaleDateString()}</p>
                        <p><strong>Number of Travelers:</strong> ${package.num_travelers}</p>
                        <p><strong>Booking Reference:</strong> ${package.booking_reference}</p>
                    </div>
                </div>
                <hr class="my-4">
                <div class="row">
                    <div class="col-12">
                        <h6 class="font-bold mb-3">Package Description</h6>
                        <p>${package.description}</p>
                    </div>
                </div>
                <hr class="my-4">
                <div class="row">
                    <div class="col-12">
                        <h6 class="font-bold mb-3">Payment Details</h6>
                        <p><strong>Total Price:</strong> Rs${parseFloat(package.total_price).toFixed(2)}</p>
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
          text: 'This package booking will be cancelled.',
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