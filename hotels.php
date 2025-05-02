<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
  $error_message = "You must be logged in to book a hotel. <a href='login.php' class='text-blue-600 hover:underline'>Log in here</a>.";
  $is_logged_in = false;
} else {
  $is_logged_in = true;
}

// Initialize variables
$hotels = [];
$available_rooms = [];
$search_performed = false;
$error_message = isset($error_message) ? $error_message : "";
$success_message = "";
$filters = [
  'search' => $_POST['search'] ?? '',
  'location' => $_POST['location'] ?? '',
  'min_price' => $_POST['min_price'] ?? 0,
  'max_price' => $_POST['max_price'] ?? 50000,
  'rating' => $_POST['rating'] ?? '',
  'amenities' => $_POST['amenities'] ?? []
];
$check_in_date = $_POST['check_in_date'] ?? '';
$check_out_date = $_POST['check_out_date'] ?? '';
$hotel_id = $_POST['hotel_id'] ?? null;

// Fetch hotels with filters
$sql = "SELECT h.*, hi.image_path AS primary_image 
        FROM hotels h 
        LEFT JOIN hotel_images hi ON h.id = hi.hotel_id AND hi.is_primary = 1 
        WHERE 1=1";
$params = [];
$types = "";

if (!empty($filters['search'])) {
  $sql .= " AND h.hotel_name LIKE ?";
  $params[] = "%{$filters['search']}%";
  $types .= "s";
}
if (!empty($filters['location'])) {
  $sql .= " AND h.location = ?";
  $params[] = $filters['location'];
  $types .= "s";
}
$sql .= " AND h.price BETWEEN ? AND ?";
$params[] = $filters['min_price'];
$params[] = $filters['max_price'];
$types .= "ii";
if (!empty($filters['rating'])) {
  $sql .= " AND h.rating = ?";
  $params[] = $filters['rating'];
  $types .= "i";
}
if (!empty($filters['amenities'])) {
  foreach ($filters['amenities'] as $amenity) {
    $sql .= " AND FIND_IN_SET(?, h.amenities)";
    $params[] = $amenity;
    $types .= "s";
  }
}

$stmt = $conn->prepare($sql);
if ($types) {
  $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $hotels[] = $row;
}
$stmt->close();

// Process availability check or booking
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if (isset($_POST['check_availability']) && !empty($check_in_date) && !empty($check_out_date) && !empty($hotel_id)) {
    $search_performed = true;

    // Validate dates
    $today = date('Y-m-d');
    if ($check_in_date < $today) {
      $error_message = "Check-in date cannot be in the past.";
    } elseif ($check_out_date <= $check_in_date) {
      $error_message = "Check-out date must be after check-in date.";
    } else {
      // Fetch available rooms
      $sql = "SELECT hr.room_id 
                    FROM hotel_rooms hr 
                    WHERE hr.hotel_id = ? 
                    AND hr.status = 'available' 
                    AND NOT EXISTS (
                        SELECT 1 
                        FROM hotel_bookings hb 
                        WHERE hb.hotel_id = hr.hotel_id 
                        AND hb.room_id = hr.room_id 
                        AND (
                            (hb.check_in_date <= ? AND hb.check_out_date >= ?) 
                            OR (hb.check_in_date <= ? AND hb.check_out_date >= ?)
                            OR (hb.check_in_date >= ? AND hb.check_out_date <= ?)
                        )
                        AND hb.booking_status != 'cancelled'
                    )";
      $stmt = $conn->prepare($sql);
      if (!$stmt) {
        $error_message = "Database error: " . $conn->error;
      } else {
        $stmt->bind_param("issssss", $hotel_id, $check_out_date, $check_in_date, $check_out_date, $check_in_date, $check_in_date, $check_out_date);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
          $available_rooms[] = $row['room_id'];
        }
        $stmt->close();
      }
    }
  } elseif ($is_logged_in && isset($_POST['book_room']) && !empty($hotel_id) && !empty($_POST['room_id']) && !empty($check_in_date) && !empty($check_out_date)) {
    // Process booking
    $room_id = $_POST['room_id'];
    $special_requests = $_POST['special_requests'] ?? '';
    $user_id = $_SESSION['user_id'];

    // Calculate total price
    $sql = "SELECT price FROM hotels WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $hotel_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $hotel = $result->fetch_assoc();
    $stmt->close();

    $check_in = new DateTime($check_in_date);
    $check_out = new DateTime($check_out_date);
    $nights = $check_in->diff($check_out)->days;
    $total_price = $hotel['price'] * $nights;

    // Generate booking reference
    $booking_reference = 'HB' . strtoupper(uniqid());

    // Start transaction
    $conn->begin_transaction();
    try {
      // Verify room availability
      $sql = "SELECT 1 
                    FROM hotel_rooms hr 
                    WHERE hr.hotel_id = ? 
                    AND hr.room_id = ? 
                    AND hr.status = 'available' 
                    AND NOT EXISTS (
                        SELECT 1 
                        FROM hotel_bookings hb 
                        WHERE hb.hotel_id = hr.hotel_id 
                        AND hb.room_id = hr.room_id 
                        AND (
                            (hb.check_in_date <= ? AND hb.check_out_date >= ?) 
                            OR (hb.check_in_date <= ? AND hb.check_out_date >= ?)
                            OR (hb.check_in_date >= ? AND hb.check_out_date <= ?)
                        )
                        AND hb.booking_status != 'cancelled'
                    )";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("isssssss", $hotel_id, $room_id, $check_out_date, $check_in_date, $check_out_date, $check_in_date, $check_in_date, $check_out_date);
      $stmt->execute();
      $result = $stmt->get_result();
      if ($result->num_rows == 0) {
        throw new Exception("Selected room is no longer available.");
      }
      $stmt->close();

      // Insert booking
      $sql = "INSERT INTO hotel_bookings (user_id, hotel_id, room_id, check_in_date, check_out_date, total_price, booking_status, payment_status, booking_reference, special_requests) 
                    VALUES (?, ?, ?, ?, ?, ?, 'pending', 'unpaid', ?, ?)";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("iisssdss", $user_id, $hotel_id, $room_id, $check_in_date, $check_out_date, $total_price, $booking_reference, $special_requests);
      $stmt->execute();
      $stmt->close();

      // Update room status
      $sql = "UPDATE hotel_rooms SET status = 'booked' WHERE hotel_id = ? AND room_id = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("is", $hotel_id, $room_id);
      $stmt->execute();
      $stmt->close();

      // Commit transaction
      $conn->commit();
      $success_message = "Booking created successfully! Reference: $booking_reference";
    } catch (Exception $e) {
      $conn->rollback();
      $error_message = "Error: " . $e->getMessage();
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Hotels | UmrahFlights</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

  <style>
    .hotel-card {
      border-radius: 12px;
      transition: transform 0.3s;
    }

    .hotel-card:hover {
      transform: translateY(-4px);
    }

    .filter-section {
      transition: max-height 0.3s ease-in-out;
      overflow: hidden;
    }
  </style>
</head>

<body class="bg-gray-50 min-h-screen">
  <?php include 'includes/navbar.php'; ?>
  <br><br><br>

  <div class="container mx-auto px-4 py-8">
    <div class="text-center mb-8">
      <h1 class="text-3xl md:text-4xl font-bold text-primary mb-2">
        <i class="fas fa-hotel mr-2"></i> Find Your Perfect Hotel
      </h1>
      <p class="text-gray-600 max-w-2xl mx-auto">Search and book hotels for your Umrah journey with ease.</p>
    </div>

    <!-- Search and Filter Form -->
    <div class="bg-primary p-6 mb-10 rounded-lg shadow-lg">
      <form method="POST" id="filter-form">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
          <!-- Search by Name -->
          <div>
            <label for="search" class="block text-sm font-medium text-white mb-1">Hotel Name</label>
            <input type="text" name="search" id="search" value="<?php echo htmlspecialchars($filters['search']); ?>"
              class="w-full px-4 py-2 rounded-lg border-gray-300 focus:ring focus:ring-primary focus:ring-opacity-50"
              placeholder="Enter hotel name">
          </div>
          <!-- Location -->
          <div>
            <label for="location" class="block text-sm font-medium text-white mb-1">Location</label>
            <select name="location" id="location" class="w-full px-4 py-2 rounded-lg border-gray-300 focus:ring focus:ring-primary focus:ring-opacity-50">
              <option value="">All Locations</option>
              <option value="makkah" <?php echo $filters['location'] == 'makkah' ? 'selected' : ''; ?>>Makkah</option>
              <option value="madinah" <?php echo $filters['location'] == 'madinah' ? 'selected' : ''; ?>>Madinah</option>
            </select>
          </div>
          <!-- Price Range -->
          <div>
            <label for="min_price" class="block text-sm font-medium text-white mb-1">Price Range (PKR)</label>
            <div class="flex space-x-2">
              <input type="number" name="min_price" id="min_price" value="<?php echo htmlspecialchars($filters['min_price']); ?>"
                class="w-1/2 px-4 py-2 rounded-lg border-gray-300 focus:ring focus:ring-primary focus:ring-opacity-50"
                placeholder="Min" min="0">
              <input type="number" name="max_price" id="max_price" value="<?php echo htmlspecialchars($filters['max_price']); ?>"
                class="w-1/2 px-4 py-2 rounded-lg border-gray-300 focus:ring focus:ring-primary focus:ring-opacity-50"
                placeholder="Max" max="50000">
            </div>
          </div>
          <!-- Rating -->
          <div>
            <label for="rating" class="block text-sm font-medium text-white mb-1">Rating</label>
            <select name="rating" id="rating" class="w-full px-4 py-2 rounded-lg border-gray-300 focus:ring focus:ring-primary focus:ring-opacity-50">
              <option value="">All Ratings</option>
              <?php for ($i = 1; $i <= 5; $i++): ?>
                <option value="<?php echo $i; ?>" <?php echo $filters['rating'] == $i ? 'selected' : ''; ?>><?php echo $i; ?> Stars</option>
              <?php endfor; ?>
            </select>
          </div>
        </div>
        <!-- Amenities -->
        <div class="filter-section mb-6">
          <button type="button" id="toggle-amenities" class="text-white font-medium mb-2 flex items-center">
            <i class="fas fa-filter mr-2"></i> Filter by Amenities
          </button>
          <div id="amenities-section" class="hidden grid grid-cols-2 md:grid-cols-4 gap-4">
            <?php
            $available_amenities = ['wifi', 'parking', 'restaurant', 'gym', 'pool', 'ac', 'room_service', 'spa'];
            foreach ($available_amenities as $amenity):
            ?>
              <label class="flex items-center space-x-2 text-white">
                <input type="checkbox" name="amenities[]" value="<?php echo $amenity; ?>"
                  <?php echo in_array($amenity, $filters['amenities']) ? 'checked' : ''; ?>
                  class="text-secondary">
                <span><?php echo ucfirst($amenity); ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
        <button type="submit" class="w-full bg-white hover:bg-gray-100 text-primary font-bold py-3 px-4 rounded-lg transition flex items-center justify-center">
          <i class="fas fa-search mr-2"></i> Apply Filters
        </button>
      </form>
    </div>

    <!-- Availability Check Form -->
    <div class="bg-gray-100 p-6 mb-10 rounded-lg shadow-lg">
      <h2 class="text-xl font-bold text-gray-800 mb-4">Check Room Availability</h2>
      <form method="POST" id="availability-form">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div>
            <label for="hotel_id" class="block text-sm font-medium text-gray-700 mb-1">Select Hotel</label>
            <select name="hotel_id" id="hotel_id" class="w-full px-4 py-2 rounded-lg border-gray-300 focus:ring focus:ring-primary focus:ring-opacity-50" required>
              <option value="">Select a hotel</option>
              <?php foreach ($hotels as $hotel): ?>
                <option value="<?php echo $hotel['id']; ?>" <?php echo ($hotel_id == $hotel['id']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($hotel['hotel_name']); ?> (<?php echo htmlspecialchars($hotel['location']); ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label for="check_in_date" class="block text-sm font-medium text-gray-700 mb-1">Check-in Date</label>
            <input type="text" name="check_in_date" id="check_in_date" class="w-full px-4 py-2 rounded-lg border-gray-300 focus:ring focus:ring-primary focus:ring-opacity-50"
              value="<?php echo htmlspecialchars($check_in_date); ?>" required>
          </div>
          <div>
            <label for="check_out_date" class="block text-sm font-medium text-gray-700 mb-1">Check-out Date</label>
            <input type="text" name="check_out_date" id="check_out_date" class="w-full px-4 py-2 rounded-lg border-gray-300 focus:ring focus:ring-primary focus:ring-opacity-50"
              value="<?php echo htmlspecialchars($check_out_date); ?>" required>
          </div>
        </div>
        <div class="mt-6">
          <button type="submit" name="check_availability" class="w-full bg-primary hover:bg-secondary text-white font-bold py-3 px-4 rounded-lg transition flex items-center justify-center">
            <i class="fas fa-search mr-2"></i> Check Availability
          </button>
        </div>
      </form>
    </div>

    <!-- Error/Success Messages -->
    <?php if (!empty($error_message)): ?>
      <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg">
        <p><?php echo $error_message; ?></p>
      </div>
    <?php endif; ?>
    <?php if (!empty($success_message)): ?>
      <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg">
        <p><?php echo htmlspecialchars($success_message); ?></p>
        <a href="user/index.php" class="text-blue-600 hover:underline">View Your Bookings</a>
      </div>
    <?php endif; ?>

    <!-- Available Rooms -->
    <?php if ($search_performed && empty($error_message)): ?>
      <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">
          <?php
          $selected_hotel = array_filter($hotels, fn($h) => $h['id'] == $hotel_id)[array_key_first(array_filter($hotels, fn($h) => $h['id'] == $hotel_id))];
          ?>
          Available Rooms at <?php echo htmlspecialchars($selected_hotel['hotel_name']); ?>
        </h2>
        <?php if (empty($available_rooms)): ?>
          <div class="bg-blue-50 border-l-4 border-blue-500 text-blue-700 p-6 rounded-lg">
            <p class="font-medium text-lg">No rooms available for the selected dates.</p>
            <p class="mt-2">Try adjusting your dates or selecting another hotel.</p>
          </div>
        <?php elseif ($is_logged_in): ?>
          <form method="POST" id="booking-form">
            <input type="hidden" name="hotel_id" value="<?php echo htmlspecialchars($hotel_id); ?>">
            <input type="hidden" name="check_in_date" value="<?php echo htmlspecialchars($check_in_date); ?>">
            <input type="hidden" name="check_out_date" value="<?php echo htmlspecialchars($check_out_date); ?>">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
              <div>
                <label for="room_id" class="block text-sm font-medium text-gray-700 mb-1">Select Room</label>
                <select name="room_id" id="room_id" class="w-full px-4 py-2 rounded-lg border-gray-300 focus:ring focus:ring-primary focus:ring-opacity-50" required>
                  <option value="">Select a room</option>
                  <?php foreach ($available_rooms as $room_id): ?>
                    <option value="<?php echo htmlspecialchars($room_id); ?>"><?php echo htmlspecialchars($room_id); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div>
                <label for="special_requests" class="block text-sm font-medium text-gray-700 mb-1">Special Requests (Optional)</label>
                <textarea name="special_requests" id="special_requests" class="w-full px-4 py-2 rounded-lg border-gray-300 focus:ring focus:ring-primary focus:ring-opacity-50" rows="3" placeholder="Any special requests?"></textarea>
              </div>
            </div>
            <button type="submit" name="book_room" class="bg-primary hover:bg-secondary text-white font-bold py-3 px-4 rounded-lg transition flex items-center">
              <i class="fas fa-book mr-2"></i> Book Room
            </button>
          </form>
        <?php else: ?>
          <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg">
            <p>You must be logged in to book a room. <a href="login.php" class="text-blue-600 hover:underline">Log in here</a>.</p>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <!-- Hotels List -->
    <?php if (empty($hotels)): ?>
      <div class="bg-blue-50 border-l-4 border-blue-500 text-blue-700 p-6 rounded-lg">
        <p class="font-medium text-lg">No hotels found.</p>
        <p class="mt-2">Try adjusting your search filters.</p>
      </div>
    <?php else: ?>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($hotels as $hotel): ?>
          <div class="hotel-card bg-white shadow-sm hover:shadow-md p-6 rounded-lg">
            <img src="<?php echo htmlspecialchars($hotel['primary_image'] ?: 'images/default-hotel.jpg'); ?>"
              alt="<?php echo htmlspecialchars($hotel['hotel_name']); ?>"
              class="w-full h-48 object-cover rounded-lg mb-4">
            <h3 class="text-xl font-bold text-primary"><?php echo htmlspecialchars($hotel['hotel_name']); ?></h3>
            <p class="text-gray-600"><i class="fas fa-map-marker-alt mr-2"></i><?php echo htmlspecialchars($hotel['location']); ?></p>
            <p class="text-secondary font-bold mt-2">PKR <?php echo number_format($hotel['price'], 0); ?>/night</p>
            <div class="flex items-center mt-2">
              <?php for ($i = 0; $i < $hotel['rating']; $i++): ?>
                <i class="fas fa-star text-yellow-400"></i>
              <?php endfor; ?>
              <span class="text-gray-600 ml-2"><?php echo $hotel['rating']; ?> Stars</span>
            </div>
            <p class="text-gray-600 mt-2"><?php echo htmlspecialchars(substr($hotel['description'], 0, 100)); ?>...</p>
            <div class="mt-2">
              <?php if (!empty($hotel['amenities'])): ?>
                <?php foreach (explode(',', $hotel['amenities']) as $amenity): ?>
                  <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700 mr-1 mb-1">
                    <i class="fas fa-check-circle mr-1"></i><?php echo ucfirst($amenity); ?>
                  </span>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script>
    // Initialize date pickers
    flatpickr("#check_in_date", {
      dateFormat: "Y-m-d",
      minDate: "today"
    });
    flatpickr("#check_out_date", {
      dateFormat: "Y-m-d",
      minDate: "today"
    });

    // Update check-out min date
    document.getElementById('check_in_date').addEventListener('change', function() {
      flatpickr("#check_out_date").set("minDate", this.value);
    });

    // Toggle amenities section
    document.getElementById('toggle-amenities').addEventListener('click', function() {
      const section = document.getElementById('amenities-section');
      section.classList.toggle('hidden');
    });

    // Price range validation
    document.getElementById('min_price').addEventListener('input', function() {
      if (this.value < 0) this.value = 0;
      const maxPrice = document.getElementById('max_price');
      if (parseInt(this.value) > parseInt(maxPrice.value)) {
        maxPrice.value = this.value;
      }
    });
    document.getElementById('max_price').addEventListener('input', function() {
      if (this.value > 50000) this.value = 50000;
      const minPrice = document.getElementById('min_price');
      if (parseInt(this.value) < parseInt(minPrice.value)) {
        minPrice.value = this.value;
      }
    });
  </script>
</body>

</html>

<?php
$conn->close();
?>