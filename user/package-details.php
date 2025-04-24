<?php
require_once '../config/db.php';
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

// Ensure booking ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  header("Location: index.php");
  exit();
}

$user_id = $_SESSION['user_id'];
$booking_id = $_GET['id'];

// Fetch package booking details
$package_query = $conn->prepare("
    SELECT pb.id, pb.user_id, pb.package_id, pb.travel_date, pb.num_travelers, pb.total_price, 
           pb.booking_status, pb.payment_status, pb.booking_reference, pb.special_requests, 
           pb.created_at, pb.updated_at,
           up.title, up.package_type, up.description, up.flight_class, up.inclusions, up.price, up.package_image,
           u.full_name, u.email, u.phone
    FROM package_bookings pb
    JOIN umrah_packages up ON pb.package_id = up.id
    JOIN users u ON pb.user_id = u.id
    WHERE pb.id = ? AND pb.user_id = ?
");
$package_query->bind_param("ii", $booking_id, $user_id);
$package_query->execute();
$package = $package_query->get_result()->fetch_assoc();
$package_query->close();

// Redirect if booking not found or doesn't belong to user
if (!$package) {
  header("Location: index.php");
  exit();
}

// Parse inclusions
$inclusions = json_decode($package['inclusions'], true) ?: [];

// Handle booking cancellation
if (isset($_POST['cancel_booking'])) {
  $stmt = $conn->prepare("UPDATE package_bookings SET booking_status = 'cancelled' WHERE id = ? AND user_id = ? AND booking_status = 'pending'");
  $stmt->bind_param("ii", $booking_id, $user_id);
  $stmt->execute();
  $stmt->close();
  header("Location: index.php");
  exit();
}

// Handle booking deletion
if (isset($_POST['delete_booking'])) {
  $stmt = $conn->prepare("DELETE FROM package_bookings WHERE id = ? AND user_id = ?");
  $stmt->bind_param("ii", $booking_id, $user_id);
  $stmt->execute();
  $stmt->close();
  header("Location: index.php");
  exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Package Booking Details - UmrahFlights</title>
  <script src="https://cdn.tailwindcss.com"></script>
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
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }
  </style>
</head>

<body class="bg-gray-100">
  <!-- Navbar -->
  <nav class="bg-gradient-to-r from-cyan-600 to-teal-500 p-4 shadow-lg">
    <div class="container mx-auto flex justify-between items-center">
      <!-- Left side: Logo and Title -->
      <div class="flex items-center space-x-4">
        <!-- <img src="https://via.placeholder.com/40" alt="Logo" class="h-12 w-12 rounded-full"> -->
        <span class="text-white text-3xl font-extrabold tracking-tight">Ummrah</span>
      </div>
      <!-- Right side: Back Button -->
      <div>
        <a href="index.php" class="bg-white text-cyan-600 px-5 py-2 rounded-full hover:bg-gray-100 transition duration-300 font-semibold shadow-md">Back to Bookings</a>
      </div>
    </div>
  </nav>

  <!-- Main Content -->
  <section class="container mx-auto px-4 py-12">
    <div class="text-center mb-12">
      <div class="text-cyan-500 font-semibold mb-3 tracking-wider">Package Booking Details</div>
      <h2 class="text-4xl font-extrabold text-gray-900 mb-4"><?php echo htmlspecialchars($package['title']); ?></h2>
      <p class="text-gray-600 max-w-2xl mx-auto">View all details of your package booking below.</p>
    </div>

    <!-- Package Details Card -->
    <div class="detail-card bg-white p-8 max-w-4xl mx-auto">
      <div class="flex justify-between items-center mb-6">
        <h3 class="text-2xl font-bold text-gray-800">Booking Information</h3>
        <span class="status-badge status-<?php echo htmlspecialchars($package['booking_status']); ?>">
          <?php echo ucfirst(htmlspecialchars($package['booking_status'])); ?>
        </span>
      </div>

      <!-- Package Overview -->
      <div class="mb-8">
        <div class="flex flex-col md:flex-row gap-6">
          <?php if ($package['package_image']): ?>
            <div class="md:w-1/3">
              <img src="../<?php echo htmlspecialchars($package['package_image']); ?>" alt="<?php echo htmlspecialchars($package['title']); ?>" class="w-full h-48 object-cover rounded-xl border-2 border-cyan-500">
            </div>
          <?php endif; ?>
          <div class="md:w-<?php echo $package['package_image'] ? '2/3' : 'full'; ?>">
            <h4 class="text-xl font-semibold text-gray-800 mb-4">Package Overview</h4>
            <p class="text-gray-600 mb-3 flex items-center">
              <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
              </svg>
              <span class="font-semibold">Type:</span> <?php echo ucfirst(htmlspecialchars($package['package_type'])); ?>
            </p>
            <p class="text-gray-600 mb-3 flex items-center">
              <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
              </svg>
              <span class="font-semibold">Description:</span> <?php echo htmlspecialchars($package['description']); ?>
            </p>
            <p class="text-gray-600 mb-3 flex items-center">
              <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
              </svg>
              <span class="font-semibold">Flight Class:</span> <?php echo ucfirst(htmlspecialchars($package['flight_class'])); ?>
            </p>
            <?php if (!empty($inclusions)): ?>
              <p class="text-gray-600 mb-3 flex items-center">
                <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01m-.01 4h.01"></path>
                </svg>
                <span class="font-semibold">Inclusions:</span> <?php echo htmlspecialchars(implode(', ', $inclusions)); ?>
              </p>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Booking Details -->
      <div class="mb-8">
        <h4 class="text-xl font-semibold text-gray-800 mb-4">Booking Details</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <p class="text-gray-600 mb-3 flex items-center">
              <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
              </svg>
              <span class="font-semibold">Travel Date:</span> <?php echo date('d M Y', strtotime($package['travel_date'])); ?>
            </p>
            <p class="text-gray-600 mb-3 flex items-center">
              <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
              </svg>
              <span class="font-semibold">Travelers:</span> <?php echo htmlspecialchars($package['num_travelers']); ?>
            </p>
            <p class="text-gray-600 mb-3 flex items-center">
              <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              <span class="font-semibold">Total Price:</span> Rs<?php echo number_format($package['total_price'], 2); ?>
            </p>
          </div>
          <div>
            <p class="text-gray-600 mb-3 flex items-center">
              <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h10m-10 4h10m-10 4h10"></path>
              </svg>
              <span class="font-semibold">Booking Reference:</span> <?php echo htmlspecialchars($package['booking_reference']); ?>
            </p>
            <p class="text-gray-600 mb-3 flex items-center">
              <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              <span class="font-semibold">Payment Status:</span> <?php echo ucfirst(htmlspecialchars($package['payment_status'])); ?>
            </p>
          </div>
        </div>
      </div>

      <!-- Passenger Details -->
      <div class="mb-8">
        <h4 class="text-xl font-semibold text-gray-800 mb-4">Passenger Details</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <p class="text-gray-600 mb-3 flex items-center">
              <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
              </svg>
              <span class="font-semibold">Name:</span> <?php echo htmlspecialchars($package['full_name']); ?>
            </p>
            <p class="text-gray-600 mb-3 flex items-center">
              <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
              </svg>
              <span class="font-semibold">Email:</span> <?php echo htmlspecialchars($package['email']); ?>
            </p>
          </div>
          <div>
            <p class="text-gray-600 mb-3 flex items-center">
              <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
              </svg>
              <span class="font-semibold">Phone:</span> <?php echo htmlspecialchars($package['phone']); ?>
            </p>
          </div>
        </div>
      </div>

      <!-- Additional Information -->
      <div class="mb-8">
        <h4 class="text-xl font-semibold text-gray-800 mb-4">Additional Information</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <p class="text-gray-600 mb-3 flex items-center">
              <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
              </svg>
              <span class="font-semibold">Booked On:</span> <?php echo date('d M Y, H:i', strtotime($package['created_at'])); ?>
            </p>
            <p class="text-gray-600 mb-3 flex items-center">
              <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
              </svg>
              <span class="font-semibold">Last Updated:</span> <?php echo date('d M Y, H:i', strtotime($package['updated_at'])); ?>
            </p>
          </div>
          <div>
            <?php if ($package['special_requests']): ?>
              <p class="text-gray-600 mb-3 flex items-center">
                <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <span class="font-semibold">Special Requests:</span> <?php echo htmlspecialchars($package['special_requests']); ?>
              </p>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="mt-8 flex gap-3 justify-end">
        <?php if ($package['booking_status'] == 'pending'): ?>
          <form method="POST" class="cancel-form">
            <input type="hidden" name="booking_type" value="package">
            <input type="hidden" name="booking_id" value="<?php echo $package['id']; ?>">
            <!-- <button type="submit" name="cancel_booking" class="bg-yellow-500 hover:bg-yellow-600 text-white py-2 px-4 rounded-xl font-medium">Cancel Booking</button> -->
          </form>
        <?php endif; ?>
        <form method="POST" class="delete-form">
          <input type="hidden" name="booking_type" value="package">
          <input type="hidden" name="booking_id" value="<?php echo $package['id']; ?>">
          <!-- <button type="submit" name="delete_booking" class="bg-red-500 hover:bg-red-600 text-white py-2 px-4 rounded-xl font-medium">Delete Booking</button> -->
        </form>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <?php include '../includes/footer.php'; ?>
  <?php include '../includes/js-links.php'; ?>

  <script>
    // Cancel booking confirmation
    document.querySelectorAll('.cancel-form').forEach(form => {
      form.addEventListener('submit', (e) => {
        e.preventDefault();
        Swal.fire({
          title: 'Are you sure?',
          text: 'This booking will be cancelled.',
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

    // Delete booking confirmation
    document.querySelectorAll('.delete-form').forEach(form => {
      form.addEventListener('submit', (e) => {
        e.preventDefault();
        Swal.fire({
          title: 'Are you sure?',
          text: 'This booking will be permanently deleted.',
          icon: 'error',
          showCancelButton: true,
          confirmButtonColor: '#dc2626',
          cancelButtonColor: '#6b7280',
          confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
          if (result.isConfirmed) {
            form.submit();
          }
        });
      });
    });
  </script>
</body>

</html>