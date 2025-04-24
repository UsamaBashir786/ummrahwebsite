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

// Fetch hotel booking details
$hotel_query = $conn->prepare("
    SELECT hb.id, hb.user_id, hb.hotel_id, hb.room_id, hb.check_in_date, hb.check_out_date, 
           hb.total_price, hb.booking_status, hb.payment_status, hb.booking_reference, 
           hb.special_requests, hb.created_at, hb.updated_at,
           h.hotel_name, h.location, h.price, h.rating, h.description, h.amenities,
           hi.image_path, hr.status AS room_status
    FROM hotel_bookings hb
    JOIN hotels h ON hb.hotel_id = h.id
    LEFT JOIN hotel_images hi ON h.id = hi.hotel_id AND hi.is_primary = 1
    LEFT JOIN hotel_rooms hr ON hb.hotel_id = hr.hotel_id AND hb.room_id = hr.room_id
    WHERE hb.id = ? AND hb.user_id = ?
");
$hotel_query->bind_param("ii", $booking_id, $user_id);
$hotel_query->execute();
$hotel = $hotel_query->get_result()->fetch_assoc();
$hotel_query->close();

// Redirect if booking not found or doesn't belong to user
if (!$hotel) {
  header("Location: index.php");
  exit();
}

// Calculate number of nights
$check_in = new DateTime($hotel['check_in_date']);
$check_out = new DateTime($hotel['check_out_date']);
$nights = $check_in->diff($check_out)->days;

// Parse amenities
$amenities = !empty($hotel['amenities']) ? explode(',', $hotel['amenities']) : [];

// Handle booking cancellation
if (isset($_POST['cancel_booking'])) {
  $stmt = $conn->prepare("UPDATE hotel_bookings SET booking_status = 'cancelled' WHERE id = ? AND user_id = ? AND booking_status = 'pending'");
  $stmt->bind_param("ii", $booking_id, $user_id);
  $stmt->execute();
  $stmt->close();
  header("Location: index.php");
  exit();
}

// Handle booking deletion
if (isset($_POST['delete_booking'])) {
  $stmt = $conn->prepare("DELETE FROM hotel_bookings WHERE id = ? AND user_id = ?");
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
  <title>Hotel Booking Details - UmrahFlights</title>
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
      <div class="text-cyan-500 font-semibold mb-3 tracking-wider">Hotel Booking Details</div>
      <h2 class="text-4xl font-extrabold text-gray-900 mb-4"><?php echo htmlspecialchars($hotel['hotel_name']); ?></h2>
      <p class="text-gray-600 max-w-2xl mx-auto">View all details of your hotel booking below.</p>
    </div>

    <!-- Hotel Details Card -->
    <div class="detail-card bg-white p-8 max-w-4xl mx-auto">
      <div class="flex justify-between items-center mb-6">
        <h3 class="text-2xl font-bold text-gray-800">Booking Information</h3>
        <span class="status-badge status-<?php echo htmlspecialchars($hotel['booking_status']); ?>">
          <?php echo ucfirst(htmlspecialchars($hotel['booking_status'])); ?>
        </span>
      </div>

      <!-- Hotel Image and Overview -->
      <div class="mb-8">
        <div class="flex flex-col md:flex-row gap-6">
          <?php if ($hotel['image_path']): ?>
            <div class="md:w-1/3">
              <img src="../<?php echo htmlspecialchars($hotel['image_path']); ?>" alt="<?php echo htmlspecialchars($hotel['hotel_name']); ?>" class="w-full h-48 object-cover rounded-xl border-2 border-cyan-500">
            </div>
          <?php endif; ?>
          <div class="md:w-<?php echo $hotel['image_path'] ? '2/3' : 'full'; ?>">
            <h4 class="text-xl font-semibold text-gray-800 mb-4">Hotel Overview</h4>
            <p class="text-gray-600 mb-3 flex items-center">
              <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
              </svg>
              <span class="font-semibold">Location:</span> <?php echo htmlspecialchars($hotel['location']); ?>
            </p>
            <p class="text-gray-600 mb-3 flex items-center">
              <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.783-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
              </svg>
              <span class="font-semibold">Rating:</span> <?php echo htmlspecialchars($hotel['rating']); ?> / 5
            </p>
            <p class="text-gray-600 mb-3 flex items-center">
              <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
              </svg>
              <span class="font-semibold">Description:</span> <?php echo htmlspecialchars($hotel['description']); ?>
            </p>
            <?php if (!empty($amenities)): ?>
              <p class="text-gray-600 mb-3 flex items-center">
                <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01m-.01 4h.01"></path>
                </svg>
                <span class="font-semibold">Amenities:</span> <?php echo htmlspecialchars(implode(', ', $amenities)); ?>
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
              <span class="font-semibold">Check-in:</span> <?php echo date('d M Y', strtotime($hotel['check_in_date'])); ?>
            </p>
            <p class="text-gray-600 mb-3 flex items-center">
              <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
              </svg>
              <span class="font-semibold">Check-out:</span> <?php echo date('d M Y', strtotime($hotel['check_out_date'])); ?>
            </p>
            <p class="text-gray-600 mb-3 flex items-center">
              <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              <span class="font-semibold">Nights:</span> <?php echo $nights; ?>
            </p>
            <p class="text-gray-600 mb-3 flex items-center">
              <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
              </svg>
              <span class="font-semibold">Room ID:</span> <?php echo htmlspecialchars($hotel['room_id']); ?> (<?php echo ucfirst(htmlspecialchars($hotel['room_status'])); ?>)
            </p>
          </div>
          <div>
            <p class="text-gray-600 mb-3 flex items-center">
              <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              <span class="font-semibold">Total Price:</span> Rs<?php echo number_format($hotel['total_price'], 2); ?>
            </p>
            <p class="text-gray-600 mb-3 flex items-center">
              <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h10m-10 4h10m-10 4h10"></path>
              </svg>
              <span class="font-semibold">Booking Reference:</span> <?php echo htmlspecialchars($hotel['booking_reference']); ?>
            </p>
            <p class="text-gray-600 mb-3 flex items-center">
              <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              <span class="font-semibold">Payment Status:</span> <?php echo ucfirst(htmlspecialchars($hotel['payment_status'])); ?>
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
              <span class="font-semibold">Booked On:</span> <?php echo date('d M Y, H:i', strtotime($hotel['created_at'])); ?>
            </p>
            <p class="text-gray-600 mb-3 flex items-center">
              <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
              </svg>
              <span class="font-semibold">Last Updated:</span> <?php echo date('d M Y, H:i', strtotime($hotel['updated_at'])); ?>
            </p>
          </div>
          <div>
            <?php if ($hotel['special_requests']): ?>
              <p class="text-gray-600 mb-3 flex items-center">
                <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <span class="font-semibold">Special Requests:</span> <?php echo htmlspecialchars($hotel['special_requests']); ?>
              </p>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="mt-8 flex gap-3 justify-end">
        <?php if ($hotel['booking_status'] == 'pending'): ?>
          <form method="POST" class="cancel-form">
            <input type="hidden" name="booking_type" value="hotel">
            <input type="hidden" name="booking_id" value="<?php echo $hotel['id']; ?>">
            <!-- <button type="submit" name="cancel_booking" class="bg-yellow-500 hover:bg-yellow-600 text-white py-2 px-4 rounded-xl font-medium">Cancel Booking</button> -->
          </form>
        <?php endif; ?>
        <form method="POST" class="delete-form">
          <input type="hidden" name="booking_type" value="hotel">
          <input type="hidden" name="booking_id" value="<?php echo $hotel['id']; ?>">
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