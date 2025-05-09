<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}

// Check if package ID is provided - handle both 'id' and 'package_id' for compatibility
$package_id = 0;
if (isset($_GET['id']) && !empty($_GET['id'])) {
  $package_id = (int)$_GET['id'];
} elseif (isset($_GET['package_id']) && !empty($_GET['package_id'])) {
  $package_id = (int)$_GET['package_id'];
} else {
  header('Location: packages.php');
  exit;
}

$user_id = $_SESSION['user_id'];

// Fetch package details with all fields from add-packages.php
// Removing package_type from the query since it doesn't exist in your table
$stmt = $conn->prepare("SELECT id, star_rating, title, description, makkah_nights, 
                       madinah_nights, total_days, inclusions, price, package_image 
                       FROM umrah_packages WHERE id = ?");
$stmt->bind_param("i", $package_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  header('Location: packages.php');
  exit;
}

$package = $result->fetch_assoc();
$stmt->close();

// Parse inclusions JSON - add safety checks to prevent errors
$inclusions = [];
if (!empty($package['inclusions'])) {
  $inclusions = json_decode($package['inclusions'], true) ?: [];
}

// Fetch user details
$stmt = $conn->prepare("SELECT full_name, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();
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

    // Default status values
    $booking_status = 'pending';
    $payment_status = 'pending';

    // Start transaction
    $conn->begin_transaction();
    try {
      // Insert booking according to the package_bookings table structure
      $sql = "INSERT INTO package_bookings 
              (user_id, package_id, travel_date, num_travelers, total_price, 
               booking_status, payment_status, booking_reference, special_requests) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
      $stmt = $conn->prepare($sql);

      // Bind parameters according to the table structure
      $stmt->bind_param(
        "iisidssss",
        $user_id,
        $package_id,
        $travel_date,
        $num_travelers,
        $total_price,
        $booking_status,
        $payment_status,
        $booking_reference,
        $special_requests
      );

      $stmt->execute();
      $booking_id = $conn->insert_id;
      $stmt->close();

      // Try to send email to User, but don't fail the transaction if it doesn't work
      try {
        $to = $user['email'];
        $email_subject = 'Thank You for Your Booking with Umrah Partner';

        // Format star rating for display
        $star_rating_display = '';
        switch ($package['star_rating']) {
          case 'low_budget':
            $star_rating_display = 'Low Budget Economy';
            break;
          case '3_star':
            $star_rating_display = '3 Star';
            break;
          case '4_star':
            $star_rating_display = '4 Star';
            break;
          case '5_star':
            $star_rating_display = '5 Star';
            break;
        }

        // Format inclusions for display
        $inclusions_text = '';
        foreach ($inclusions as $inclusion) {
          switch ($inclusion) {
            case 'flight':
              $inclusions_text .= "- Flight\n";
              break;
            case 'hotel':
              $inclusions_text .= "- Hotel\n";
              break;
            case 'transport':
              $inclusions_text .= "- Transport\n";
              break;
            case 'guide':
              $inclusions_text .= "- Guide\n";
              break;
            case 'vip_services':
              $inclusions_text .= "- VIP Services\n";
              break;
          }
        }

        $email_message = "Dear {$user['full_name']},\n\n";
        $email_message .= "Thank you for booking with Umrah Partner! Your booking has been successfully created, and we will process it shortly.\n\n";
        $email_message .= "Booking Details:\n";
        $email_message .= "Booking Reference: $booking_reference\n";
        $email_message .= "Package: {$package['title']}\n";
        $email_message .= "Category: $star_rating_display\n";
        $email_message .= "Makkah Nights: {$package['makkah_nights']}\n";
        $email_message .= "Madinah Nights: {$package['madinah_nights']}\n";
        $email_message .= "Total Days: {$package['total_days']}\n";
        $email_message .= "Travel Date: $travel_date\n";
        $email_message .= "Number of Travelers: $num_travelers\n";
        $email_message .= "Total Price: Rs " . number_format($total_price, 2) . "\n";
        $email_message .= "Payment Status: Pending\n\n";
        $email_message .= "Package Inclusions:\n$inclusions_text\n";
        $email_message .= "Special Requests: " . ($special_requests ?: 'None') . "\n\n";
        $email_message .= "You can view your booking details in your account under 'My Bookings'.\n";
        $email_message .= "For any queries, contact us at info@umrahpartner.com.\n\n";
        $email_message .= "Best regards,\nUmrah Partner Team";

        $headers = "From: no-reply@umrahpartner.com\r\n";
        $headers .= "Reply-To: info@umrahpartner.com\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        mail($to, $email_subject, $email_message, $headers);

        // Send email to Admin
        $admin_to = 'info@umrahpartner.com';
        $admin_subject = 'New Booking Submission';
        $admin_message = "New Booking Submission\n\n";
        $admin_message .= "A new booking has been created.\n\n";
        $admin_message .= "Details:\n";
        $admin_message .= "Booking Reference: $booking_reference\n";
        $admin_message .= "User: {$user['full_name']} ({$user['email']})\n";
        $admin_message .= "Package: {$package['title']}\n";
        $admin_message .= "Category: $star_rating_display\n";
        $admin_message .= "Makkah Nights: {$package['makkah_nights']}\n";
        $admin_message .= "Madinah Nights: {$package['madinah_nights']}\n";
        $admin_message .= "Total Days: {$package['total_days']}\n";
        $admin_message .= "Travel Date: $travel_date\n";
        $admin_message .= "Number of Travelers: $num_travelers\n";
        $admin_message .= "Total Price: Rs " . number_format($total_price, 2) . "\n";
        $admin_message .= "Payment Status: Pending\n";
        $admin_message .= "Package Inclusions:\n$inclusions_text\n";
        $admin_message .= "Special Requests: " . ($special_requests ?: 'None') . "\n";
        $admin_message .= "Submitted At: " . date('Y-m-d H:i:s') . "\n";

        mail($admin_to, $admin_subject, $admin_message, $headers);
      } catch (Exception $e) {
        // Just log the error but continue with the transaction
        error_log("Email Error: " . $e->getMessage());
      }

      // Commit transaction
      $conn->commit();
      $success_message = "Booking created successfully! Reference: $booking_reference. Proceed to payment or view your bookings.";
    } catch (Exception $e) {
      $conn->rollback();
      $error_message = "Error creating booking: " . $e->getMessage();
      error_log("Booking Error: " . $e->getMessage());
    }
  }
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
      return 'Unknown';
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Book <?php echo htmlspecialchars($package['title'] ?? 'Umrah Package'); ?> - UmrahFlights</title>
  <link rel="stylesheet" href="src/output.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <?php include 'includes/css-links.php'; ?>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: '#047857',
            secondary: '#10B981',
            accent: '#F59E0B',
          },
        },
      },
    }
  </script>
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
            <a href="package-details.php?id=<?php echo $package_id; ?>" class="hover:text-green-200"><?php echo htmlspecialchars($package['title'] ?? 'Package Details'); ?></a>
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
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Booking: <?php echo htmlspecialchars($package['title'] ?? 'Umrah Package'); ?></h2>

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

        <!-- Package Summary -->
        <div class="mb-6 bg-gray-50 p-4 rounded-lg">
          <div class="flex flex-col md:flex-row gap-4">
            <!-- Package Image -->
            <div class="md:w-1/3">
              <img
                src="<?php echo !empty($package['package_image']) ? htmlspecialchars($package['package_image']) : 'assets/images/default-package.jpg'; ?>"
                alt="<?php echo htmlspecialchars($package['title'] ?? 'Umrah Package'); ?>"
                class="w-full h-auto rounded-md object-cover"
                style="max-height: 200px;">
            </div>

            <!-- Package Details -->
            <div class="md:w-2/3">
              <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($package['title'] ?? 'Umrah Package'); ?></h3>
              <p class="text-sm text-gray-600 mb-2">Category: <?php echo displayStarRating($package['star_rating'] ?? ''); ?></p>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-2 mb-3">
                <div class="flex items-center">
                  <i class="fas fa-mosque text-primary mr-2"></i>
                  <span class="text-sm"><?php echo isset($package['makkah_nights']) ? $package['makkah_nights'] : 0; ?> Nights in Makkah</span>
                </div>
                <div class="flex items-center">
                  <i class="fas fa-mosque text-primary mr-2"></i>
                  <span class="text-sm"><?php echo isset($package['madinah_nights']) ? $package['madinah_nights'] : 0; ?> Nights in Madinah</span>
                </div>
                <div class="flex items-center">
                  <i class="fas fa-calendar-alt text-primary mr-2"></i>
                  <span class="text-sm"><?php echo isset($package['total_days']) ? $package['total_days'] : 0; ?> Days Total</span>
                </div>
              </div>

              <div class="mb-3">
                <p class="text-sm font-medium text-gray-700 mb-1">Inclusions:</p>
                <div>
                  <?php if (!empty($inclusions)): ?>
                    <?php foreach ($inclusions as $inclusion): ?>
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
                    <?php endforeach; ?>
                  <?php else: ?>
                    <p class="text-sm text-gray-500">No inclusions specified</p>
                  <?php endif; ?>
                </div>
              </div>

              <p class="text-lg font-bold text-primary">
                Rs <?php echo isset($package['price']) ? number_format($package['price'], 2) : '0.00'; ?> <span class="text-sm font-normal text-gray-600">per person</span>
              </p>
            </div>
          </div>
        </div>

        <!-- Booking Form -->
        <?php if (empty($success_message)): ?>
          <form method="POST" id="booking-form" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
            </div>

            <!-- Price Summary -->
            <div class="bg-gray-50 p-4 rounded-lg mb-4">
              <h3 class="text-lg font-semibold text-gray-800 mb-2">Price Summary</h3>
              <div class="flex justify-between mb-2">
                <span>Package Price (per person):</span>
                <span>Rs <?php echo isset($package['price']) ? number_format($package['price'], 2) : '0.00'; ?></span>
              </div>
              <div class="flex justify-between mb-2">
                <span>Number of Travelers:</span>
                <span id="travelers-count">1</span>
              </div>
              <div class="flex justify-between font-bold text-lg text-primary border-t border-gray-200 pt-2 mt-2">
                <span>Total Price:</span>
                <span id="total-price">Rs <?php echo isset($package['price']) ? number_format($package['price'], 2) : '0.00'; ?></span>
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
  <style>
    .bg-primary {
      background: #0d6efd;
    }

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

    .inclusion-badge {
      display: inline-block;
      padding: 0.3rem 0.7rem;
      border-radius: 9999px;
      font-size: 0.75rem;
      margin-right: 0.5rem;
      margin-bottom: 0.5rem;
      background-color: #E5F2F2;
      color: #047857;
      border: 1px solid #047857;
    }
  </style>
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
    const travelersCount = document.getElementById('travelers-count');
    const totalPriceDisplay = document.getElementById('total-price');
    const packagePrice = <?php echo isset($package['price']) ? $package['price'] : 0; ?>;

    numTravelersInput.addEventListener('input', function() {
      let travelers = parseInt(this.value) || 1;
      if (travelers < 1) travelers = 1;
      if (travelers > 10) travelers = 10;
      this.value = travelers;
      travelersCount.textContent = travelers;
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