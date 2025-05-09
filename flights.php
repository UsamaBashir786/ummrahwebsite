<?php
session_start();
include 'config/db.php';

// Initialize variables
$search_results = [];
$search_performed = false;
$error_message = "";
$current_date = date('Y-m-d');
$is_round_trip = false;

// Fetch available flight dates for calendar highlights
$available_dates = ['departure' => [], 'return' => []];
$departure_to_return = [];

// Fetch one-way and round-trip departure dates for the departure calendar
$sql_departure = "SELECT DISTINCT departure_date FROM flights WHERE has_return = 0 
                 UNION 
                 SELECT DISTINCT departure_date FROM flights WHERE has_return = 1";
$result_departure = $conn->query($sql_departure);
if ($result_departure) {
  while ($row = $result_departure->fetch_assoc()) {
    $available_dates['departure'][] = $row['departure_date'];
  }
}

// Fetch round-trip return dates for the return calendar
$sql_return = "SELECT DISTINCT departure_date, return_date FROM flights WHERE has_return = 1 AND return_date IS NOT NULL";
$result_return = $conn->query($sql_return);
if ($result_return) {
  while ($row = $result_return->fetch_assoc()) {
    if (!in_array($row['return_date'], $available_dates['return'])) {
      $available_dates['return'][] = $row['return_date'];
    }
    // Store the mapping of departure to return dates for validation
    $departure_to_return[$row['departure_date']][] = $row['return_date'];
  }
}

// Process search form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $search_performed = true;

  // Get form data
  $departure_city = $_POST['departure_city'] ?? '';
  $arrival_city = $_POST['arrival_city'] ?? '';
  $departure_date = $_POST['departure_date'] ?? '';
  $cabin_class = $_POST['cabin_class'] ?? '';
  $passengers = isset($_POST['passengers']) ? intval($_POST['passengers']) : 1;
  $trip_type = $_POST['trip_type'] ?? 'one_way';
  $return_date = $_POST['return_date'] ?? '';

  // Set round trip flag
  $is_round_trip = ($trip_type === 'round_trip');

  // Validate inputs
  $validation_errors = [];

  if (empty($departure_city)) {
    $validation_errors[] = "Please select a departure city";
  }
  if (empty($arrival_city)) {
    $validation_errors[] = "Please select an arrival city";
  }
  if ($departure_city === $arrival_city && !empty($departure_city)) {
    $validation_errors[] = "Departure and arrival cities cannot be the same";
  }
  if (empty($departure_date)) {
    $validation_errors[] = "Departure date is required";
  }
  if ($is_round_trip && empty($return_date)) {
    $validation_errors[] = "Return date is required for round trips";
  }
  if ($is_round_trip && !empty($departure_date) && !empty($return_date) && strtotime($return_date) < strtotime($departure_date)) {
    $validation_errors[] = "Return date must be after departure date";
  }

  if (empty($validation_errors)) {
    // Build the SQL query
    $sql = "SELECT * FROM flights WHERE departure_city = ? AND arrival_city = ? AND departure_date = ? AND has_return = ?";
    $params = [$departure_city, $arrival_city, $departure_date, $is_round_trip ? 1 : 0];
    $types = "sssi";

    if ($is_round_trip) {
      $sql .= " AND return_date = ?";
      $params[] = $return_date;
      $types .= "s";
    }

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
      $error_message = "Error preparing query: " . $conn->error;
    } else {
      $stmt->bind_param($types, ...$params);
      if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
          $search_results[] = $row;
        }
      } else {
        $error_message = "Error executing query: " . $stmt->error;
      }
      $stmt->close();
    }
  } else {
    $error_message = implode(", ", $validation_errors);
  }
} else {
  // Load all flights by default
  $sql = "SELECT * FROM flights ORDER BY departure_date DESC";
  $result = $conn->query($sql);
  if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      $search_results[] = $row;
    }
  }
}

// Get unique departure and arrival cities for dropdowns
$departure_cities = [];
$arrival_cities = [];
$sql_cities = "SELECT DISTINCT departure_city FROM flights";
$result_departure = $conn->query($sql_cities);
if ($result_departure && $result_departure->num_rows > 0) {
  while ($row = $result_departure->fetch_assoc()) {
    $departure_cities[] = $row['departure_city'];
  }
}
$sql_cities = "SELECT DISTINCT arrival_city FROM flights";
$result_arrival = $conn->query($sql_cities);
if ($result_arrival && $result_arrival->num_rows > 0) {
  while ($row = $result_arrival->fetch_assoc()) {
    $arrival_cities[] = $row['arrival_city'];
  }
}

// Define cabin classes based on table structure
$cabin_classes = ['economy', 'business', 'first_class'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Search Flights | UmrahFlights</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700;800&display=swap');

    body {
      font-family: 'Manrope', sans-serif;
      background: #f9fafb;
      color: #1f2937;
      overflow-x: hidden;
    }

    .flight-card {
      background: linear-gradient(145deg, #ffffff, #f1f5f9);
      border-radius: 24px;
      overflow: hidden;
      transition: transform 0.4s ease, box-shadow 0.4s ease;
      border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .flight-card:hover {
      transform: translateY(-12px);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    }

    .gradient-button {
      background: linear-gradient(90deg, #10b981, #059669);
      color: white;
      border-radius: 16px;
      padding: 12px 32px;
      font-weight: 600;
      transition: transform 0.3s ease, background 0.3s ease;
    }

    .gradient-button:hover {
      background: linear-gradient(90deg, #059669, #10b981);
      transform: scale(1.05);
    }

    .header-bg {
      background: linear-gradient(135deg, #059669 0%, #10b981 100%);
      position: relative;
      overflow: hidden;
      clip-path: polygon(0 0, 100% 0, 100% 85%, 0 100%);
    }

    .header-bg::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: url('https://source.unsplash.com/random/1600x400?mosque,kaaba') no-repeat center center/cover;
      opacity: 0.2;
      z-index: 0;
    }

    .search-form {
      background: rgba(255, 255, 255, 0.95);
      border-radius: 24px;
      padding: 40px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      backdrop-filter: blur(10px);
      transition: transform 0.3s ease;
    }

    .search-form:hover {
      transform: translateY(-8px);
    }

    .input-field {
      border: 1px solid #e5e7eb;
      border-radius: 16px;
      padding: 14px;
      background: #ffffff;
      transition: border-color 0.3s ease, box-shadow 0.3s ease;
    }

    .input-field:focus {
      border-color: #10b981;
      box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
      outline: none;
    }

    .trip-type-option {
      padding: 10px 20px;
      border-radius: 9999px;
      font-weight: 600;
      transition: background 0.3s ease, color 0.3s ease;
      background: #e5e7eb;
      color: #1f2937;
    }

    .trip-type-option.active {
      background: linear-gradient(90deg, #10b981, #059669);
      color: white;
    }

    .section-title {
      position: relative;
      font-size: 2.25rem;
      font-weight: 800;
      color: #1f2937;
      padding-bottom: 16px;
      margin-bottom: 32px;
    }

    .section-title::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      width: 120px;
      height: 5px;
      background: linear-gradient(to right, #10b981, #059669);
      border-radius: 3px;
    }

    .chip {
      display: inline-flex;
      align-items: center;
      padding: 8px 16px;
      background: #ecfdf5;
      color: #059669;
      border-radius: 9999px;
      font-size: 0.85rem;
      font-weight: 600;
      transition: background 0.3s ease;
    }

    .chip:hover {
      background: #d1fae5;
    }

    .flatpickr-day.available-flight {
      background: #10b981 !important;
      border-color: #10b981 !important;
      color: white !important;
    }

    .flatpickr-day.available-flight:hover {
      background: #059669 !important;
    }

    .tooltip .tooltip-text {
      visibility: hidden;
      width: 140px;
      background: #1f2937;
      color: #fff;
      text-align: center;
      border-radius: 8px;
      padding: 8px;
      position: absolute;
      z-index: 10;
      bottom: 125%;
      left: 50%;
      transform: translateX(-50%);
      opacity: 0;
      transition: opacity 0.3s;
    }

    .tooltip:hover .tooltip-text {
      visibility: visible;
      opacity: 1;
    }

    .animate-on-scroll {
      opacity: 0;
      transform: translateY(20px);
      transition: opacity 0.6s ease, transform 0.6s ease;
    }

    .animate-on-scroll.visible {
      opacity: 1;
      transform: translateY(0);
    }

    .tab {
      padding: 10px 20px;
      border-radius: 12px;
      font-weight: 600;
      transition: background 0.3s ease, color 0.3s ease;
      background: #e5e7eb;
      color: #1f2937;
    }

    .tab.active {
      background: linear-gradient(90deg, #10b981, #059669);
      color: white;
    }

    .footer-bg {
      background: linear-gradient(to bottom, #1f2937, #111827);
      clip-path: polygon(0 10%, 100% 0, 100% 100%, 0 100%);
    }

    .social-icon {
      transition: transform 0.3s ease, color 0.3s ease;
      font-size: 1.5rem;
    }

    .social-icon:hover {
      transform: scale(1.4);
      color: #10b981;
    }

    @media (max-width: 768px) {
      .section-title {
        font-size: 1.75rem;
      }

      .search-form {
        padding: 24px;
      }

      .trip-type-option {
        padding: 8px 16px;
        font-size: 0.875rem;
      }
    }
  </style>
</head>

<body>
  <!-- Navbar -->
  <?php include 'includes/navbar.php'; ?>

  <!-- Page Header -->
  <section class="header-bg text-white py-20 relative">
    <div class="container mx-auto px-4 relative z-10">
      <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight mb-4">Find Your Perfect Flight</h1>
      <p class="text-lg md:text-xl text-gray-100 max-w-2xl">Book seamless flights for your Umrah journey with ease and confidence.</p>
      <div class="mt-6 text-sm md:text-base">
        <a href="index.php" class="text-gray-200 hover:text-white transition">Home</a>
        <span class="mx-2">></span>
        <span class="text-gray-200">Flight Booking</span>
      </div>
    </div>
  </section>

  <!-- Search Form -->
  <section class="py-16">
    <div class="container mx-auto px-4">
      <div class="search-form animate-on-scroll">
        <h2 class="section-title">Search Flights</h2>
        <div class="flex justify-center mb-8">
          <div class="flex space-x-4">
            <label class="trip-type-option <?php echo !$is_round_trip ? 'active' : ''; ?>" data-trip-type="one_way">
              <input type="radio" name="trip_type" value="one_way" class="hidden" <?php echo !$is_round_trip ? 'checked' : ''; ?>>
              <i class="fas fa-long-arrow-alt-right mr-2"></i> One Way
            </label>
            <label class="trip-type-option <?php echo $is_round_trip ? 'active' : ''; ?>" data-trip-type="round_trip">
              <input type="radio" name="trip_type" value="round_trip" class="hidden" <?php echo $is_round_trip ? 'checked' : ''; ?>>
              <i class="fas fa-exchange-alt mr-2"></i> Round Trip
            </label>
          </div>
        </div>

        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="flight-search-form">
          <input type="hidden" name="trip_type" id="trip_type" value="<?php echo $is_round_trip ? 'round_trip' : 'one_way'; ?>">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div>
              <label for="departure_city" class="block text-sm font-medium text-gray-700 mb-3">From</label>
              <div class="relative">
                <i class="fas fa-plane-departure absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                <select class="w-full input-field pl-12" id="departure_city" name="departure_city" required>
                  <option value="">Select departure city</option>
                  <?php foreach ($departure_cities as $city): ?>
                    <option value="<?php echo htmlspecialchars($city); ?>" <?php echo (isset($_POST['departure_city']) && $_POST['departure_city'] == $city) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($city); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div>
              <label for="arrival_city" class="block text-sm font-medium text-gray-700 mb-3">To</label>
              <div class="relative">
                <i class="fas fa-plane-arrival absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                <select class="w-full input-field pl-12" id="arrival_city" name="arrival_city" required>
                  <option value="">Select arrival city</option>
                  <?php foreach ($arrival_cities as $city): ?>
                    <option value="<?php echo htmlspecialchars($city); ?>" <?php echo (isset($_POST['arrival_city']) && $_POST['arrival_city'] == $city) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($city); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div>
              <label for="departure_date" class="block text-sm font-medium text-gray-700 mb-3">Departure Date</label>
              <div class="relative">
                <i class="fas fa-calendar-alt absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                <input type="text" class="w-full input-field pl-12" id="departure_date" name="departure_date" required value="<?php echo isset($_POST['departure_date']) ? htmlspecialchars($_POST['departure_date']) : ''; ?>">
              </div>
            </div>
            <div class="<?php echo !$is_round_trip ? 'hidden' : ''; ?>" id="return-date-container">
              <label for="return_date" class="block text-sm font-medium text-gray-700 mb-3">Return Date</label>
              <div class="relative">
                <i class="fas fa-calendar-check absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                <input type="text" class="w-full input-field pl-12" id="return_date" name="return_date" <?php echo $is_round_trip ? 'required' : 'disabled'; ?> value="<?php echo isset($_POST['return_date']) ? htmlspecialchars($_POST['return_date']) : ''; ?>">
              </div>
            </div>
          </div>
          <div class="flex items-center justify-center space-x-4 mt-6">
            <div class="flex items-center">
              <span class="w-4 h-4 bg-emerald-200 rounded-full mr-2"></span>
              <span class="text-sm text-gray-600">Flights Available</span>
            </div>
          </div>
          <div class="mt-8 text-center">
            <button type="submit" id="search-button" class="gradient-button w-full md:w-auto"><i class="fas fa-search mr-2"></i>Search Flights</button>
          </div>
        </form>
      </div>
    </div>
  </section>

  <!-- Search Results -->
  <section class="py-16">
    <div class="container mx-auto px-4">
      <div class="flex justify-end mb-6">
        <button id="refresh-button" class="gradient-button"><i class="fas fa-sync-alt mr-2"></i>Refresh Results</button>
      </div>

      <div id="search-results" class="animate-on-scroll">
        <?php if (!empty($error_message)): ?>
          <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-6 rounded-2xl shadow-sm mb-8" role="alert">
            <div class="flex items-center">
              <i class="fas fa-exclamation-circle text-red-500 text-xl mr-3"></i>
              <p class="font-medium"><?php echo $error_message; ?></p>
            </div>
          </div>
        <?php endif; ?>

        <?php if (empty($search_results) && $search_performed && empty($error_message)): ?>
          <div class="bg-blue-50 border-l-4 border-blue-500 text-blue-700 p-6 rounded-2xl shadow-sm mb-8" role="alert">
            <div class="flex">
              <i class="fas fa-info-circle text-blue-500 text-xl mr-3"></i>
              <div>
                <p class="font-medium text-lg">No flights found</p>
                <p class="mt-2">Try adjusting your search parameters or selecting different dates.</p>
                <div class="mt-4">
                  <button onclick="document.getElementById('flight-search-form').scrollIntoView({behavior: 'smooth'})" class="gradient-button"><i class="fas fa-sync-alt mr-2"></i>Modify Search</button>
                </div>
              </div>
            </div>
          </div>
        <?php elseif (!empty($search_results)): ?>
          <div class="mb-8">
            <h2 class="section-title"><?php echo count($search_results); ?> Flight<?php echo count($search_results) > 1 ? 's' : ''; ?> Found</h2>
            <?php if ($search_performed): ?>
              <p class="text-gray-600 text-sm">
                <?php echo htmlspecialchars($departure_city); ?> to <?php echo htmlspecialchars($arrival_city); ?>
                on <?php echo date('D, M j, Y', strtotime($departure_date)); ?>
                <?php if ($is_round_trip): ?>
                  (Return on <?php echo date('D, M j, Y', strtotime($return_date)); ?>)
                <?php endif; ?>
              </p>
            <?php else: ?>
              <p class="text-gray-600 text-sm">Showing all available flights</p>
            <?php endif; ?>
          </div>

          <div class="space-y-8">
            <?php foreach ($search_results as $index => $flight):
              $min_price = min($flight['economy_price'], $flight['business_price'], $flight['first_class_price']);
              $is_cheapest = $index === 0;
            ?>
              <div class="flight-card animate-on-scroll">
                <?php if ($is_cheapest): ?>
                  <div class="chip absolute top-4 right-4 bg-emerald-100 text-emerald-800">Best Deal</div>
                <?php endif; ?>
                <div class="p-8">
                  <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                    <div class="md:col-span-3">
                      <div class="flex items-center">
                        <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mr-3">
                          <i class="fas fa-plane text-emerald-600 text-xl"></i>
                        </div>
                        <div>
                          <h5 class="font-bold text-lg text-gray-800"><?php echo htmlspecialchars($flight['airline_name']); ?></h5>
                          <p class="text-gray-500 text-sm">Flight: <?php echo htmlspecialchars($flight['flight_number']); ?></p>
                        </div>
                      </div>
                      <div class="md:hidden mt-4 text-right">
                        <div class="font-bold text-2xl text-emerald-600">PKR <?php echo number_format($min_price, 0); ?></div>
                        <p class="text-sm text-gray-500">Lowest fare</p>
                      </div>
                    </div>
                    <div class="md:col-span-6">
                      <div class="flex items-center justify-between mb-4">
                        <div class="text-center">
                          <p class="text-2xl font-bold text-gray-800"><?php echo date('H:i', strtotime($flight['departure_time'])); ?></p>
                          <p class="text-sm text-gray-600"><?php echo htmlspecialchars($flight['departure_city']); ?></p>
                        </div>
                        <div class="flex-1 mx-4 flex items-center justify-between">
                          <i class="fas fa-circle text-emerald-600 text-xs"></i>
                          <div class="flex-1 mx-2 h-0.5 bg-gray-300 relative overflow-hidden">
                            <div class="absolute top-1/2 transform -translate-y-1/2"><i class="fas fa-plane text-emerald-600"></i></div>
                          </div>
                          <i class="fas fa-circle text-emerald-600 text-xs"></i>
                        </div>
                        <div class="text-center">
                          <p class="text-sm text-gray-600"><?php echo htmlspecialchars($flight['arrival_city']); ?></p>
                        </div>
                      </div>
                      <div class="flex items-center justify-between border-t pt-3">
                        <p class="text-sm text-gray-600"><i class="far fa-calendar-alt text-emerald-600 mr-1"></i><?php echo date('D, M j, Y', strtotime($flight['departure_date'])); ?></p>
                        <p class="text-sm text-gray-600"><i class="far fa-clock text-emerald-600 mr-1"></i>Duration: <?php echo htmlspecialchars($flight['flight_duration']); ?> hours</p>
                        <span class="chip <?php echo $flight['has_stops'] ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800'; ?>">
                          <i class="fas <?php echo $flight['has_stops'] ? 'fa-map-marker-alt' : 'fa-check-circle'; ?> mr-1"></i>
                          <?php echo $flight['has_stops'] ? 'Stops' : 'Direct'; ?>
                        </span>
                      </div>
                    </div>
                    <div class="md:col-span-3 flex flex-col justify-between items-end">
                      <div class="hidden md:block">
                        <div class="font-bold text-2xl text-emerald-600">PKR <?php echo number_format($min_price, 0); ?></div>
                        <p class="text-sm text-gray-500">Lowest fare</p>
                      </div>
                      <button type="button" class="view-details-btn gradient-button w-full md:w-auto" data-flight-id="<?php echo $flight['id']; ?>">
                        <i class="fas fa-chevron-down mr-1 details-icon-<?php echo $flight['id']; ?>"></i> View Details
                      </button>
                    </div>
                  </div>

                  <!-- Expandable Details -->
                  <div id="details-<?php echo $flight['id']; ?>" class="mt-6 pt-6 border-t border-gray-200 hidden">
                    <div class="flex space-x-4 mb-6">
                      <div class="tab active" data-tab="pricing-<?php echo $flight['id']; ?>"><i class="fas fa-tag mr-1"></i>Pricing & Classes</div>
                      <div class="tab" data-tab="route-<?php echo $flight['id']; ?>"><i class="fas fa-route mr-1"></i>Flight Route</div>
                      <div class="tab" data-tab="info-<?php echo $flight['id']; ?>"><i class="fas fa-info-circle mr-1"></i>Flight Information</div>
                      <?php if ($flight['has_return']): ?>
                        <div class="tab" data-tab="return-<?php echo $flight['id']; ?>"><i class="fas fa-undo-alt mr-1"></i>Return Flight</div>
                      <?php endif; ?>
                    </div>

                    <?php
                    $remaining_seats = [];
                    foreach (['economy', 'business', 'first_class'] as $class_key) {
                      $total_seats = $flight[$class_key . '_seats'];
                      $sql = "SELECT SUM(adult_count + children_count) as booked_seats 
                              FROM flight_bookings 
                              WHERE flight_id = ? 
                              AND cabin_class = ? 
                              AND payment_status IN ('pending', 'completed')";
                      $stmt = $conn->prepare($sql);
                      if ($stmt) {
                        $stmt->bind_param("is", $flight['id'], $class_key);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $booked_seats = $result->fetch_assoc()['booked_seats'] ?? 0;
                        $remaining_seats[$class_key] = max(0, $total_seats - $booked_seats);
                        $stmt->close();
                      } else {
                        $remaining_seats[$class_key] = 0;
                      }
                    }
                    ?>

                    <div class="tab-content">
                      <div id="pricing-<?php echo $flight['id']; ?>" class="tab-pane">
                        <h6 class="font-semibold text-gray-700 mb-4">Available Classes and Pricing</h6>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                          <?php foreach (['economy' => 'Economy', 'business' => 'Business', 'first_class' => 'First Class'] as $class_key => $class_name):
                            $price_field = $class_key . '_price';
                            $class_icon = $class_key === 'business' ? 'fa-briefcase' : ($class_key === 'first_class' ? 'fa-crown' : 'fa-chair');
                            $bg_color = $class_key === 'business' ? 'bg-purple-50' : ($class_key === 'first_class' ? 'bg-blue-50' : 'bg-emerald-50');
                            $text_color = $class_key === 'business' ? 'text-purple-700' : ($class_key === 'first_class' ? 'text-blue-700' : 'text-emerald-700');
                          ?>
                            <div class="<?php echo $bg_color; ?> rounded-xl p-4 border border-gray-200 hover:shadow-md transition-shadow">
                              <div class="flex items-center mb-3">
                                <div class="w-8 h-8 rounded-full bg-white border border-gray-200 flex items-center justify-center mr-2">
                                  <i class="fas <?php echo $class_icon; ?> <?php echo $text_color; ?>"></i>
                                </div>
                                <span class="font-medium <?php echo $text_color; ?>"><?php echo $class_name; ?></span>
                              </div>
                              <div class="space-y-2">
                                <div class="flex justify-between">
                                  <span class="text-sm text-gray-600">Price:</span>
                                  <span class="font-bold <?php echo $text_color; ?>">PKR <?php echo number_format($flight[$price_field], 0); ?></span>
                                </div>
                                <div class="flex justify-between">
                                  <span class="text-sm text-gray-600">Seats Left:</span>
                                  <span class="font-medium"><?php echo $remaining_seats[$class_key]; ?></span>
                                </div>
                              </div>
                              <div class="mt-4">
                                <a href="booking-flight.php?flight_id=<?php echo $flight['id']; ?>&cabin_class=<?php echo $class_key; ?>" class="block w-full text-center py-2 px-4 gradient-button text-sm font-medium <?php echo $remaining_seats[$class_key] == 0 ? 'opacity-50 cursor-not-allowed' : ''; ?>" <?php echo $remaining_seats[$class_key] == 0 ? 'onclick="return false;"' : ''; ?>>Select</a>
                              </div>
                            </div>
                          <?php endforeach; ?>
                        </div>
                      </div>

                      <div id="route-<?php echo $flight['id']; ?>" class="tab-pane hidden">
                        <div class="bg-gray-50 rounded-xl p-6">
                          <div class="relative">
                            <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-300"></div>
                            <div class="space-y-8">
                              <div class="flex">
                                <div class="flex-shrink-0 w-8 h-8 rounded-full bg-emerald-500 flex items-center justify-center z-10">
                                  <i class="fas fa-plane-departure text-white text-sm"></i>
                                </div>
                                <div class="ml-6">
                                  <p class="font-bold text-gray-800"><?php echo htmlspecialchars($flight['departure_city']); ?></p>
                                  <p class="text-gray-600"><?php echo date('h:i A', strtotime($flight['departure_time'])); ?></p>
                                  <p class="text-sm text-gray-500"><?php echo date('l, F j, Y', strtotime($flight['departure_date'])); ?></p>
                                </div>
                              </div>
                              <?php if ($flight['has_stops'] && $flight['stops']):
                                $stops = json_decode($flight['stops'], true);
                                if (is_array($stops)):
                                  foreach ($stops as $stop):
                              ?>
                                    <div class="flex">
                                      <div class="flex-shrink-0 w-8 h-8 rounded-full bg-amber-500 flex items-center justify-center z-10">
                                        <i class="fas fa-map-marker-alt text-white text-sm"></i>
                                      </div>
                                      <div class="ml-6">
                                        <p class="font-bold text-gray-800"><?php echo htmlspecialchars($stop['city']); ?></p>
                                        <p class="text-gray-600">Layover: <?php echo htmlspecialchars($stop['duration']); ?> hours</p>
                                      </div>
                                    </div>
                              <?php
                                  endforeach;
                                endif;
                              endif; ?>
                              <div class="flex">
                                <div class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center z-10">
                                  <i class="fas fa-plane-arrival text-white text-sm"></i>
                                </div>
                                <div class="ml-6">
                                  <p class="font-bold text-gray-800"><?php echo htmlspecialchars($flight['arrival_city']); ?></p>
                                  <p class="text-sm text-gray-500"><?php echo date('l, F j, Y', strtotime($flight['departure_date'])); ?></p>
                                </div>
                              </div>
                            </div>
                          </div>
                          <div class="mt-8 p-4 bg-white rounded-lg border border-gray-200">
                            <h6 class="font-semibold text-gray-700 mb-3">Flight Summary</h6>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                              <div>
                                <p class="text-sm text-gray-500">Total Duration</p>
                                <p class="font-medium"><?php echo htmlspecialchars($flight['flight_duration']); ?> hours</p>
                              </div>
                              <div>
                                <p class="text-sm text-gray-500">Distance</p>
                                <p class="font-medium"><?php echo htmlspecialchars($flight['distance']); ?> km</p>
                              </div>
                              <div>
                                <p class="text-sm text-gray-500">Flight Type</p>
                                <p class="font-medium"><?php echo $flight['has_stops'] ? 'Stops' : 'Direct'; ?></p>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>

                      <div id="info-<?php echo $flight['id']; ?>" class="tab-pane hidden">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                          <div class="bg-gray-50 rounded-xl p-4">
                            <h6 class="font-semibold text-gray-700 mb-3">Airline Information</h6>
                            <div class="space-y-3">
                              <div class="flex items-center">
                                <div class="w-8 text-emerald-600"><i class="fas fa-plane"></i></div>
                                <div>
                                  <p class="text-sm text-gray-500">Airline</p>
                                  <p class="font-medium"><?php echo htmlspecialchars($flight['airline_name']); ?></p>
                                </div>
                              </div>
                              <div class="flex items-center">
                                <div class="w-8 text-emerald-600"><i class="fas fa-id-card"></i></div>
                                <div>
                                  <p class="text-sm text-gray-500">Flight Number</p>
                                  <p class="font-medium"><?php echo htmlspecialchars($flight['flight_number']); ?></p>
                                </div>
                              </div>
                            </div>
                          </div>
                          <div class="bg-gray-50 rounded-xl p-4">
                            <h6 class="font-semibold text-gray-700 mb-3">Additional Information</h6>
                            <?php if ($flight['flight_notes']): ?>
                              <div class="mb-4">
                                <p class="text-sm text-gray-500 mb-1">Flight Notes</p>
                                <p class="bg-white p-3 rounded-lg border border-gray-200 text-sm"><?php echo htmlspecialchars($flight['flight_notes']); ?></p>
                              </div>
                            <?php endif; ?>
                            <div>
                              <p class="text-sm text-gray-500 mb-2">Flight Features</p>
                              <div class="flex flex-wrap gap-2">
                                <span class="chip bg-blue-50 text-blue-700"><i class="fas fa-wifi mr-1"></i>Wi-Fi</span>
                                <span class="chip bg-purple-50 text-purple-700"><i class="fas fa-utensils mr-1"></i>Meals</span>
                                <span class="chip bg-pink-50 text-pink-700"><i class="fas fa-tv mr-1"></i>Entertainment</span>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>

                      <?php if ($flight['has_return']): ?>
                        <div id="return-<?php echo $flight['id']; ?>" class="tab-pane hidden">
                          <div class="bg-purple-50 rounded-xl p-6">
                            <h6 class="font-bold text-purple-700 mb-4"><i class="fas fa-plane-arrival mr-2"></i>Return Flight Details</h6>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                              <div class="bg-white rounded-lg shadow-sm p-4">
                                <h6 class="font-semibold text-gray-700 mb-3">Return Journey</h6>
                                <div class="space-y-3">
                                  <div class="flex items-center">
                                    <div class="w-8 text-purple-600"><i class="fas fa-calendar-day"></i></div>
                                    <div>
                                      <p class="text-sm text-gray-500">Return Date</p>
                                      <p class="font-medium"><?php echo date('D, M j, Y', strtotime($flight['return_date'])); ?></p>
                                    </div>
                                  </div>
                                  <div class="flex items-center">
                                    <div class="w-8 text-purple-600"><i class="fas fa-clock"></i></div>
                                    <div>
                                      <p class="text-sm text-gray-500">Return Time</p>
                                      <p class="font-medium"><?php echo date('h:i A', strtotime($flight['return_time'])); ?></p>
                                    </div>
                                  </div>
                                  <div class="flex items-center">
                                    <div class="w-8 text-purple-600"><i class="fas fa-plane"></i></div>
                                    <div>
                                      <p class="text-sm text-gray-500">Flight Number</p>
                                      <p class="font-medium"><?php echo htmlspecialchars($flight['return_flight_number']); ?></p>
                                    </div>
                                  </div>
                                  <div class="flex items-center">
                                    <div class="w-8 text-purple-600"><i class="fas fa-hourglass-half"></i></div>
                                    <div>
                                      <p class="text-sm text-gray-500">Flight Duration</p>
                                      <p class="font-medium"><?php echo htmlspecialchars($flight['return_flight_duration']); ?> hours</p>
                                    </div>
                                  </div>
                                  <div class="flex items-center">
                                    <div class="w-8 text-purple-600"><i class="fas fa-building"></i></div>
                                    <div>
                                      <p class="text-sm text-gray-500">Airline</p>
                                      <p class="font-medium"><?php echo htmlspecialchars($flight['return_airline']); ?></p>
                                    </div>
                                  </div>
                                </div>
                              </div>
                              <div class="bg-white rounded-lg shadow-sm p-4">
                                <h6 class="font-semibold text-gray-700 mb-3">Return Flight Route</h6>
                                <div class="relative pb-6">
                                  <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-300"></div>
                                  <div class="space-y-8">
                                    <div class="flex">
                                      <div class="flex-shrink-0 w-8 h-8 rounded-full bg-purple-500 flex items-center justify-center z-10">
                                        <i class="fas fa-plane-departure text-white text-sm"></i>
                                      </div>
                                      <div class="ml-6">
                                        <p class="font-bold text-gray-800"><?php echo htmlspecialchars($flight['arrival_city']); ?></p>
                                        <p class="text-gray-600"><?php echo date('h:i A', strtotime($flight['return_time'])); ?></p>
                                        <p class="text-sm text-gray-500"><?php echo date('l, F j, Y', strtotime($flight['return_date'])); ?></p>
                                      </div>
                                    </div>
                                    <?php if ($flight['has_return_stops'] && $flight['return_stops']):
                                      $return_stops = json_decode($flight['return_stops'], true);
                                      if (is_array($return_stops)):
                                        foreach ($return_stops as $stop):
                                    ?>
                                          <div class="flex">
                                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-amber-500 flex items-center justify-center z-10">
                                              <i class="fas fa-map-marker-alt text-white text-sm"></i>
                                            </div>
                                            <div class="ml-6">
                                              <p class="font-bold text-gray-800"><?php echo htmlspecialchars($stop['city']); ?></p>
                                              <p class="text-gray-600">Layover: <?php echo htmlspecialchars($stop['duration']); ?> hours</p>
                                            </div>
                                          </div>
                                    <?php
                                        endforeach;
                                      endif;
                                    endif; ?>
                                    <div class="flex">
                                      <div class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center z-10">
                                        <i class="fas fa-plane-arrival text-white text-sm"></i>
                                      </div>
                                      <div class="ml-6">
                                        <p class="font-bold text-gray-800"><?php echo htmlspecialchars($flight['departure_city']); ?></p>
                                        <p class="text-sm text-gray-500"><?php echo date('l, F j, Y', strtotime($flight['return_date'])); ?></p>
                                      </div>
                                    </div>
                                  </div>
                                </div>
                                <div class="mt-4 text-center">
                                  <span class="chip <?php echo $flight['has_return_stops'] ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800'; ?>">
                                    <i class="fas <?php echo $flight['has_return_stops'] ? 'fa-map-marker-alt' : 'fa-check-circle'; ?> mr-1"></i>
                                    <?php echo $flight['has_return_stops'] ? 'Stops' : 'Direct Return Flight'; ?>
                                  </span>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- Call to Action -->
  <section class="py-20 text-center bg-gradient-to-r from-emerald-50 to-teal-50">
    <div class="container mx-auto px-4">
      <h2 class="text-3xl font-bold text-gray-800 mb-6 animate-on-scroll">Book Your Umrah Flight Today</h2>
      <p class="text-lg text-gray-600 mb-8 max-w-3xl mx-auto animate-on-scroll">Let us help you plan a seamless journey to Makkah and Madinah.</p>
      <a href="contact.php" class="gradient-button inline-block text-lg animate-on-scroll">Get in Touch</a>
    </div>
  </section>

  <!-- Footer -->
  <footer class="footer-bg py-20 text-gray-200">
    <div class="container mx-auto px-4">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-12">
        <div class="animate-on-scroll">
          <h3 class="text-2xl font-bold mb-6 text-white">About Us</h3>
          <p class="text-gray-300 text-sm leading-relaxed">
            We specialize in creating transformative Umrah experiences, blending premium services with spiritual fulfillment.
          </p>
          <div class="flex space-x-6 mt-6">
            <a href="#" class="social-icon"><i class="fab fa-facebook"></i></a>
            <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
            <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
          </div>
        </div>
        <div class="animate-on-scroll">
          <h3 class="text-2xl font-bold mb-6 text-white">Quick Links</h3>
          <ul class="space-y-4">
            <li><a href="index.php" class="text-gray-300 hover:text-white transition">Home</a></li>
            <li><a href="about.php" class="text-gray-300 hover:text-white transition">About Us</a></li>
            <li><a href="packages.php" class="text-gray-300 hover:text-white transition">Our Packages</a></li>
            <li><a href="faqs.php" class="text-gray-300 hover:text-white transition">FAQs</a></li>
            <li><a href="contact.php" class="text-gray-300 hover:text-white transition">Contact Us</a></li>
          </ul>
        </div>
        <div class="animate-on-scroll">
          <h3 class="text-2xl font-bold mb-6 text-white">Our Services</h3>
          <ul class="space-y-4">
            <li><a href="packages.php" class="text-gray-300 hover:text-white transition">Umrah Packages</a></li>
            <li><a href="flight-booking.php" class="text-gray-300 hover:text-white transition">Flight Booking</a></li>
            <li><a href="hotel.php" class="text-gray-300 hover:text-white transition">Hotel Reservation</a></li>
            <li><a href="visa.php" class="text-gray-300 hover:text-white transition">Visa Processing</a></li>
            <li><a href="transport.php" class="text-gray-300 hover:text-white transition">Transportation</a></li>
          </ul>
        </div>
        <div class="animate-on-scroll">
          <h3 class="text-2xl font-bold mb-6 text-white">Contact Us</h3>
          <ul class="space-y-4 text-gray-300">
            <li class="flex items-start">
              <i class="fas fa-map-marker-alt mt-1 mr-3 text-emerald-400"></i>
              <span>123 Main Street, City, Country</span>
            </li>
            <li class="flex items-center">
              <i class="fas fa-phone mr-3 text-emerald-400"></i>
              <span>+44 775 983691</span>
            </li>
            <li class="flex items-center">
              <i class="fas fa-envelope mr-3 text-emerald-400"></i>
              <span>info@umrahpartner.com</span>
            </li>
          </ul>
        </div>
      </div>
      <div class="border-t border-gray-700 mt-12 pt-8 flex flex-col md:flex-row justify-between items-center animate-on-scroll">
        <p class="text-gray-400 text-sm">© 2025 Umrah Partners. All rights reserved.</p>
        <div class="flex space-x-8 mt-4 md:mt-0">
          <a href="privacy.php" class="text-gray-400 hover:text-white text-sm transition">Privacy Policy</a>
          <a href="terms.php" class="text-gray-400 hover:text-white text-sm transition">Terms of Service</a>
          <a href="cookies.php" class="text-gray-400 hover:text-white text-sm transition">Cookie Policy</a>
        </div>
      </div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script>
    const availableDates = <?php echo json_encode($available_dates); ?>;
    const departureToReturn = <?php echo json_encode($departure_to_return); ?>;

    function initializeDepartureCalendar() {
      return flatpickr("#departure_date", {
        dateFormat: "Y-m-d",
        minDate: "today",
        allowInput: false,
        onDayCreate: function(dObj, dStr, fp, dayElem) {
          const date = dayElem.dateObj.toISOString().split('T')[0];
          if (availableDates.departure.includes(date)) {
            dayElem.classList.add("available-flight", "tooltip");
            dayElem.innerHTML += `<span class="tooltip-text">Flights available</span>`;
          }
        },
        onChange: function(selectedDates, dateStr, instance) {
          const isOneWay = tripTypeInput.value === 'one_way';
          if (!isOneWay) {
            returnPicker.set("minDate", dateStr);
            const validReturnDates = departureToReturn[dateStr] || [];
            returnPicker.set("enable", validReturnDates);
          }
        }
      });
    }

    function initializeReturnCalendar() {
      return flatpickr("#return_date", {
        dateFormat: "Y-m-d",
        minDate: "today",
        allowInput: false,
        enable: [],
        onDayCreate: function(dObj, dStr, fp, dayElem) {
          const date = dayElem.dateObj.toISOString().split('T')[0];
          if (availableDates.return.includes(date)) {
            dayElem.classList.add("available-flight", "tooltip");
            dayElem.innerHTML += `<span class="tooltip-text">Return flights available</span>`;
          }
        }
      });
    }

    let departurePicker = initializeDepartureCalendar();
    let returnPicker = initializeReturnCalendar();

    const tripTypeOptions = document.querySelectorAll('.trip-type-option');
    const tripTypeInput = document.getElementById('trip_type');
    const returnDateContainer = document.getElementById('return-date-container');
    const returnDateInput = document.getElementById('return_date');

    tripTypeOptions.forEach(option => {
      option.addEventListener('click', function() {
        tripTypeOptions.forEach(opt => opt.classList.remove('active'));
        this.classList.add('active');
        const tripType = this.getAttribute('data-trip-type');
        tripTypeInput.value = tripType;
        const isOneWay = tripType === 'one_way';
        returnDateContainer.classList.toggle('hidden', isOneWay);
        returnDateInput.disabled = isOneWay;
        returnDateInput.required = !isOneWay;
        if (isOneWay) {
          returnDateInput.value = '';
          returnPicker.set("enable", []);
        } else {
          const departureDate = document.getElementById('departure_date').value;
          if (departureDate) {
            returnPicker.set("minDate", departureDate);
            const validReturnDates = departureToReturn[departureDate] || [];
            returnPicker.set("enable", validReturnDates);
          }
        }
        departurePicker.destroy();
        departurePicker = initializeDepartureCalendar();
        validateForm();
        const radioInput = this.querySelector('input[type="radio"]');
        if (radioInput) radioInput.checked = true;
      });
    });

    document.getElementById('departure_date').addEventListener('change', function() {
      const isOneWay = tripTypeInput.value === 'one_way';
      if (!isOneWay) {
        returnPicker.set("minDate", this.value);
        const validReturnDates = departureToReturn[this.value] || [];
        returnPicker.set("enable", validReturnDates);
      }
      validateForm();
    });

    function validateForm() {
      const departureCity = document.getElementById('departure_city').value;
      const arrivalCity = document.getElementById('arrival_city').value;
      const departureDate = document.getElementById('departure_date').value;
      const isRoundTrip = tripTypeInput.value === 'round_trip';
      const returnDate = document.getElementById('return_date').value;
      const searchBtn = document.getElementById('search-button');
      const isValid = departureCity && arrivalCity && departureDate && (!isRoundTrip || returnDate);
      searchBtn.disabled = !isValid;
    }

    ['departure_city', 'arrival_city', 'departure_date', 'return_date'].forEach(id => {
      const element = document.getElementById(id);
      if (element) element.addEventListener('change', validateForm);
    });

    document.getElementById('search-results').addEventListener('click', function(e) {
      const button = e.target.closest('.view-details-btn');
      if (button) {
        const flightId = button.getAttribute('data-flight-id');
        const detailsDiv = document.getElementById('details-' + flightId);
        const icon = document.querySelector('.details-icon-' + flightId);
        if (detailsDiv && icon) {
          detailsDiv.classList.toggle('hidden');
          icon.classList.toggle('fa-chevron-down', detailsDiv.classList.contains('hidden'));
          icon.classList.toggle('fa-chevron-up', !detailsDiv.classList.contains('hidden'));
        }
      }
    });

    const tabs = document.querySelectorAll('.tab');
    tabs.forEach(tab => {
      tab.addEventListener('click', function() {
        const tabId = this.getAttribute('data-tab');
        const flightId = tabId.split('-')[1];
        const flightTabs = document.querySelectorAll(`[data-tab$="-${flightId}"]`);
        const tabPanes = document.querySelectorAll(`[id$="-${flightId}"].tab-pane`);
        flightTabs.forEach(t => t.classList.remove('active'));
        tabPanes.forEach(p => p.classList.add('hidden'));
        this.classList.add('active');
        document.getElementById(tabId).classList.remove('hidden');
      });
    });

    document.getElementById('refresh-button').addEventListener('click', function() {
      const refreshIcon = this.querySelector('.fa-sync-alt');
      refreshIcon.classList.add('animate-spin');
      setTimeout(() => window.location.reload(true), 300);
    });

    document.getElementById('flight-search-form').addEventListener('submit', function() {
      const searchBtn = document.getElementById('search-button');
      searchBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Searching...';
      searchBtn.disabled = true;
    });

    const elements = document.querySelectorAll('.animate-on-scroll');
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add('visible');
          }
        });
      }, {
        threshold: 0.1
      }
    );
    elements.forEach((el) => observer.observe(el));
  </script>
</body>

</html>
<?php
$conn->close();
?>