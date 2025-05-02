<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}

// Check if package ID is provided
if (!isset($_GET['package_id']) || empty($_GET['package_id'])) {
  header('Location: packages.php');
  exit;
}

$package_id = (int)$_GET['package_id'];
$user_id = $_SESSION['user_id'];

// Fetch package details
$stmt = $conn->prepare("SELECT id, title, price FROM umrah_packages WHERE id = ?");
$stmt->bind_param("i", $package_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  header('Location: packages.php');
  exit;
}

$package = $result->fetch_assoc();
$stmt->close();

// Initialize variables
$error_message = "";
$success_message = "";
$travel_date = $_POST['travel_date'] ?? '';
$num_travelers = $_POST['num_travelers'] ?? 1;
$special_requests = $_POST['special_requests'] ?? '';

// Process booking form
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_booking'])) {
  // Validate inputs
  $today = date('Y-m-d');
  if (empty($travel_date)) {
    $error_message = "Please select a travel date.";
  } elseif ($travel_date < $today) {
    $error_message = "Travel date cannot be in the past.";
  } elseif ($num_travelers < 1 || $num_travelers > 10) {
    $error_message = "Number of travelers must be between 1 and 10.";
  } else {
    // Calculate total price
    $total_price = $package['price'] * $num_travelers;

    // Generate booking reference
    $booking_reference = 'PB' . strtoupper(uniqid());

    // Start transaction
    $conn->begin_transaction();
    try {
      // Insert booking
      $sql = "INSERT INTO package_bookings (user_id, package_id, travel_date, num_travelers, total_price, booking_status, payment_status, booking_reference, special_requests) 
                    VALUES (?, ?, ?, ?, ?, 'pending', 'pending', ?, ?)";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("iisidss", $user_id, $package_id, $travel_date, $num_travelers, $total_price, $booking_reference, $special_requests);
      $stmt->execute();
      $stmt->close();

      // Commit transaction
      $conn->commit();
      $success_message = "Booking created successfully! Reference: $booking_reference. Proceed to payment or view your bookings.";
    } catch (Exception $e) {
      $conn->rollback();
      $error_message = "Error creating booking: " . $e->getMessage();
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Book <?php echo htmlspecialchars($package['title']); ?> - UmrahFlights</title>
  <link rel="stylesheet" href="src/output.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <?php include 'includes/css-links.php'; ?>

  <style>
    body {
      margin-top: 65px !important;
    }

    .form-loading .loading-spinner {
      display: inline-block;
    }

    .form-loading button {
      opacity: 0.7;
      cursor: not-allowed;
    }

    .loading-spinner {
      display: none;
    }
  </style>
</head>

<body class="bg-gray-50">
  <?php include 'includes/navbar.php'; ?>

  <!-- Page Header -->
  <section class="bg-primary text-white py-8">
    <div class="container mx-auto px-4">
      <h1 class="text-3xl font-bold mb-2">Book Your Umrah Package</h1>
      <nav class="text-sm">
        <ol class="flex flex-wrap">
          <li class="flex items-center">
            <a href="index.php" class="hover:text-green-200">Home</a>
            <svg class="h-4 w-4 mx-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </li>
          <li class="flex items-center">
            <a href="packages.php" class="hover:text-green-200">Packages</a>
            <svg class="h-4 w-4 mx-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </li>
          <li class="flex items-center">
            <a href="package-details.php?id=<?php echo $package_id; ?>" class="hover:text-green-200"><?php echo htmlspecialchars($package['title']); ?></a>
            <svg class="h-4 w-4 mx-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </li>
          <li class="text-green-200">Book</li>
        </ol>
      </nav>
    </div>
  </section>

  <!-- Booking Form Section -->
  <section class="py-12">
    <div class="container mx-auto px-4 max-w-4xl">
      <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Booking: <?php echo htmlspecialchars($package['title']); ?></h2>

        <!-- Messages -->
        <?php if (!empty($error_message)): ?>
          <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg flex items-center">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <p><?php echo htmlspecialchars($error_message); ?></p>
          </div>
        <?php endif; ?>
        <?php if (!empty($success_message)): ?>
          <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            <p><?php echo htmlspecialchars($success_message); ?></p>
            <a href="user/index.php" class="text-blue-600 hover:underline ml-2">View Bookings</a>
          </div>
        <?php endif; ?>

        <!-- Booking Form -->
        <?php if (empty($success_message)): ?>
          <form method="POST" id="booking-form" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Package Price -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Package Price (Per Person)</label>
                <p class="text-lg font-semibold text-gray-800">Rs <?php echo number_format($package['price'], 2); ?></p>
              </div>
              <!-- Travel Date -->
              <div>
                <label for="travel_date" class="block text-sm font-medium text-gray-700 mb-1">Travel Date</label>
                <input type="text" name="travel_date" id="travel_date" class="w-full px-4 py-2 rounded-lg border-gray-300 focus:ring focus:ring-primary focus:ring-opacity-50"
                  value="<?php echo htmlspecialchars($travel_date); ?>" placeholder="Select travel date" required>
              </div>
              <!-- Number of Travelers -->
              <div>
                <label for="num_travelers" class="block text-sm font-medium text-gray-700 mb-1">Number of Travelers</label>
                <input type="number" name="num_travelers" id="num_travelers" min="1" max="10"
                  value="<?php echo htmlspecialchars($num_travelers); ?>"
                  class="w-full px-4 py-2 rounded-lg border-gray-300 focus:ring focus:ring-primary focus:ring-opacity-50" required>
              </div>
              <!-- Total Price (Display Only) -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Total Price</label>
                <p id="total-price" class="text-lg font-semibold text-gray-800">Rs <?php echo number_format($package['price'] * $num_travelers, 2); ?></p>
              </div>
            </div>
            <!-- Special Requests -->
            <div>
              <label for="special_requests" class="block text-sm font-medium text-gray-700 mb-1">Special Requests (Optional)</label>
              <textarea name="special_requests" id="special_requests" class="w-full px-4 py-2 rounded-lg border-gray-300 focus:ring focus:ring-primary focus:ring-opacity-50"
                rows="4" placeholder="E.g., dietary needs, accessibility requirements"><?php echo htmlspecialchars($special_requests); ?></textarea>
            </div>
            <!-- Submit Button -->
            <div class="flex justify-end gap-4">
              <a href="package-details.php?id=<?php echo $package_id; ?>"
                class="border border-gray-300 hover:bg-gray-100 text-gray-700 font-medium py-2 px-6 rounded-md transition duration-300 ease-in-out">
                Cancel
              </a>
              <button type="submit" name="confirm_booking"
                class="bg-primary hover:bg-secondary text-white font-medium py-2 px-6 rounded-md transition duration-300 ease-in-out flex items-center">
                <i class="fas fa-book mr-2"></i> Confirm Booking
                <i class="fas fa-spinner fa-spin ml-2 loading-spinner"></i>
              </button>
            </div>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <?php include 'includes/footer.php'; ?>
  <?php include 'includes/js-links.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script>
    // Initialize date picker
    flatpickr("#travel_date", {
      dateFormat: "Y-m-d",
      minDate: "today",
      placeholder: "Select travel date"
    });

    // Update total price dynamically
    const numTravelersInput = document.getElementById('num_travelers');
    const totalPriceDisplay = document.getElementById('total-price');
    const packagePrice = <?php echo $package['price']; ?>;

    numTravelersInput.addEventListener('input', function() {
      let travelers = parseInt(this.value) || 1;
      if (travelers < 1) travelers = 1;
      if (travelers > 10) travelers = 10;
      this.value = travelers;
      const totalPrice = (packagePrice * travelers).toFixed(2);
      totalPriceDisplay.textContent = `Rs ${totalPrice.replace(/\B(?=(\d{3})+(?!\d))/g, ",")}`;
    });

    // Form loading state
    const bookingForm = document.getElementById('booking-form');
    if (bookingForm) {
      bookingForm.addEventListener('submit', function() {
        this.classList.add('form-loading');
      });
    }
  </script>
</body>

</html>

<?php
$conn->close();
?>