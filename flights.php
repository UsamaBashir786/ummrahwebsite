<?php
require_once 'config/db.php';
session_start();

// Initialize variables for search
$departure_city = isset($_GET['departure_city']) ? $_GET['departure_city'] : '';
$arrival_city = isset($_GET['arrival_city']) ? $_GET['arrival_city'] : '';
$departure_date = isset($_GET['departure_date']) ? $_GET['departure_date'] : '';
$has_return = isset($_GET['has_return']) && $_GET['has_return'] == '1' ? 1 : 0;
$return_date = $has_return && isset($_GET['return_date']) ? $_GET['return_date'] : '';
$max_price = isset($_GET['max_price']) ? intval($_GET['max_price']) : 500000;
$airline = isset($_GET['airline']) ? $_GET['airline'] : '';
$direct_flights = isset($_GET['direct_flights']) && $_GET['direct_flights'] == '1' ? 1 : 0;
$results = [];
$return_results = [];

if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($departure_city) && !empty($arrival_city) && !empty($departure_date)) {
  global $conn;

  // Build query for outbound flights
  $sql = "SELECT * FROM flights WHERE departure_city = ? AND arrival_city = ? AND departure_date = ? 
            AND (economy_price <= ? OR business_price <= ? OR first_class_price <= ?)";
  $params = [$departure_city, $arrival_city, $departure_date, $max_price, $max_price, $max_price];
  $types = "ssssss";

  if (!empty($airline)) {
    $sql .= " AND airline_name = ?";
    $params[] = $airline;
    $types .= "s";
  }
  if ($direct_flights) {
    $sql .= " AND has_stops = 0";
  }

  $stmt = $conn->prepare($sql);
  if ($stmt === false) {
    echo "<script>alert('Error preparing query: " . addslashes($conn->error) . "');</script>";
    exit;
  }
  $stmt->bind_param($types, ...$params);
  $stmt->execute();
  $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  $stmt->close();

  // If round-trip, query return flights
  if ($has_return && !empty($return_date)) {
    $sql = "SELECT * FROM flights WHERE departure_city = ? AND arrival_city = ? AND departure_date = ? 
                AND (economy_price <= ? OR business_price <= ? OR first_class_price <= ?)";
    $params = [$arrival_city, $departure_city, $return_date, $max_price, $max_price, $max_price];
    $types = "ssssss";

    if (!empty($airline)) {
      $sql .= " AND airline_name = ?";
      $params[] = $airline;
      $types .= "s";
    }
    if ($direct_flights) {
      $sql .= " AND has_stops = 0";
    }

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
      echo "<script>alert('Error preparing query: " . addslashes($conn->error) . "');</script>";
      exit;
    }
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $return_results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php' ?>
  <link rel="stylesheet" href="assets/css/style.css">
  <script src="https://cdn.tailwindcss.com"></script>
  <title>Search Flights | UmrahFlights</title>
</head>

<body class="pt-24 bg-gray-50">
  <?php include 'includes/navbar.php' ?>

  <!-- Search Section -->
  <section class="py-6 bg-white shadow-sm">
    <div class="container mx-auto px-4">
      <h4 class="text-xl font-semibold text-gray-800 mb-4">Millions of cheap flights. One simple search.</h4>

      <form class="flex flex-wrap gap-3 items-center" method="GET" action="">
        <!-- Journey Type -->
        <div class="w-full sm:w-auto flex items-center gap-4">
          <div class="flex items-center">
            <input type="radio" name="has_return" id="oneWay" value="0" class="w-4 h-4 text-teal-600 border-gray-300 rounded focus:ring-teal-500" <?php echo $has_return ? '' : 'checked'; ?>>
            <label for="oneWay" class="ml-2 text-sm text-gray-700">One-way</label>
          </div>
          <div class="flex items-center">
            <input type="radio" name="has_return" id="roundTrip" value="1" class="w-4 h-4 text-teal-600 border-gray-300 rounded focus:ring-teal-500" <?php echo $has_return ? 'checked' : ''; ?>>
            <label for="roundTrip" class="ml-2 text-sm text-gray-700">Round Trip</label>
          </div>
        </div>

        <!-- Departure City -->
        <div class="w-full sm:w-auto">
          <select name="departure_city" id="departure_city" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent" required>
            <option value="">From</option>
            <option value="Karachi" <?php echo $departure_city === 'Karachi' ? 'selected' : ''; ?>>Karachi (KHI)</option>
            <option value="Lahore" <?php echo $departure_city === 'Lahore' ? 'selected' : ''; ?>>Lahore (LHE)</option>
            <option value="Islamabad" <?php echo $departure_city === 'Islamabad' ? 'selected' : ''; ?>>Islamabad (ISB)</option>
            <option value="Rawalpindi" <?php echo $departure_city === 'Rawalpindi' ? 'selected' : ''; ?>>Rawalpindi (RWP)</option>
            <option value="Faisalabad" <?php echo $departure_city === 'Faisalabad' ? 'selected' : ''; ?>>Faisalabad (FSD)</option>
            <option value="Multan" <?php echo $departure_city === 'Multan' ? 'selected' : ''; ?>>Multan (MUX)</option>
            <option value="Hyderabad" <?php echo $departure_city === 'Hyderabad' ? 'selected' : ''; ?>>Hyderabad (HDD)</option>
            <option value="Peshawar" <?php echo $departure_city === 'Peshawar' ? 'selected' : ''; ?>>Peshawar (PEW)</option>
            <option value="Quetta" <?php echo $departure_city === 'Quetta' ? 'selected' : ''; ?>>Quetta (UET)</option>
          </select>
        </div>

        <!-- Arrival City -->
        <div class="w-full sm:w-auto">
          <select name="arrival_city" id="arrival_city" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent" required>
            <option value="">To</option>
            <option value="Jeddah" <?php echo $arrival_city === 'Jeddah' ? 'selected' : ''; ?>>Jeddah (JED)</option>
            <option value="Medina" <?php echo $arrival_city === 'Medina' ? 'selected' : ''; ?>>Medina (MED)</option>
          </select>
        </div>

        <!-- Departure Date -->
        <div class="w-full sm:w-auto">
          <input type="date" name="departure_date" id="departure_date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent" value="<?php echo htmlspecialchars($departure_date); ?>" required>
        </div>

        <!-- Return Date -->
        <div class="w-full sm:w-auto">
          <input type="date" name="return_date" id="return_date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent" value="<?php echo htmlspecialchars($return_date); ?>" <?php echo $has_return ? '' : 'disabled'; ?>>
        </div>

        <!-- Travellers and Class -->
        <div class="w-full sm:w-auto">
          <select name="travellers" id="travellers" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent">
            <option value="1 Adult, Economy">1 Adult, Economy</option>
            <option value="2 Adults, Economy">2 Adults, Economy</option>
            <option value="1 Adult, Business">1 Adult, Business</option>
            <option value="2 Adults, Business">2 Adults, Business</option>
            <option value="1 Adult, First Class">1 Adult, First Class</option>
          </select>
        </div>

        <!-- Search Button -->
        <button type="submit" class="w-full sm:w-auto bg-teal-500 hover:bg-teal-600 text-white font-medium py-2 px-6 rounded-lg transition duration-300">
          Search
        </button>

        <!-- Hidden Filters -->
        <input type="hidden" name="max_price" id="max_price" value="<?php echo htmlspecialchars($max_price); ?>">
        <input type="hidden" name="airline" id="airline" value="<?php echo htmlspecialchars($airline); ?>">
        <input type="hidden" name="direct_flights" id="direct_flights" value="<?php echo $direct_flights; ?>">
      </form>

      <div class="mt-3 flex flex-wrap gap-4">
        <div class="flex items-center">
          <input type="checkbox" id="nearbyAirportsFrom" class="w-4 h-4 text-teal-600 border-gray-300 rounded focus:ring-teal-500">
          <label for="nearbyAirportsFrom" class="ml-2 text-sm text-gray-700">Add nearby airports (From)</label>
        </div>
        <div class="flex items-center">
          <input type="checkbox" id="nearbyAirportsTo" class="w-4 h-4 text-teal-600 border-gray-300 rounded focus:ring-teal-500">
          <label for="nearbyAirportsTo" class="ml-2 text-sm text-gray-700">Add nearby airports (To)</label>
        </div>
        <div class="flex items-center">
          <input type="checkbox" id="directFlightsCheck" class="w-4 h-4 text-teal-600 border-gray-300 rounded focus:ring-teal-500" <?php echo $direct_flights ? 'checked' : ''; ?>>
          <label for="directFlightsCheck" class="ml-2 text-sm text-gray-700">Direct flights</label>
        </div>
      </div>
    </div>
  </section>

  <!-- Flight Listing Section -->
  <section class="py-12">
    <div class="container mx-auto px-4">
      <h2 class="text-3xl font-bold text-gray-800 mb-8">Find Your Umrah Flight</h2>

      <div class="flex flex-col lg:flex-row gap-8">
        <!-- Filter Sidebar -->
        <div class="w-full lg:w-1/4">
          <div class="bg-white p-6 rounded-lg shadow-md">
            <h5 class="text-lg font-semibold text-gray-800 mb-4">Filter Flights</h5>

            <div class="mb-4">
              <label for="priceRange" class="block text-gray-700 font-medium mb-2">Price Range</label>
              <input type="range" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer" id="priceRange" min="50000" max="500000" step="10000" value="<?php echo htmlspecialchars($max_price); ?>">
              <p class="text-sm text-gray-500 mt-1">Up to Rs.<span id="priceValue"><?php echo number_format($max_price); ?></span></p>
            </div>

            <div class="mb-4">
              <label for="departureCitySide" class="block text-gray-700 font-medium mb-2">Departure City</label>
              <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent" id="departureCitySide">
                <option value="">Select City</option>
                <option value="Karachi" <?php echo $departure_city === 'Karachi' ? 'selected' : ''; ?>>Karachi</option>
                <option value="Lahore" <?php echo $departure_city === 'Lahore' ? 'selected' : ''; ?>>Lahore</option>
                <option value="Islamabad" <?php echo $departure_city === 'Islamabad' ? 'selected' : ''; ?>>Islamabad</option>
                <option value="Rawalpindi" <?php echo $departure_city === 'Rawalpindi' ? 'selected' : ''; ?>>Rawalpindi</option>
                <option value="Faisalabad" <?php echo $departure_city === 'Faisalabad' ? 'selected' : ''; ?>>Faisalabad</option>
              </select>
            </div>

            <div class="mb-4">
              <label for="airlineSide" class="block text-gray-700 font-medium mb-2">Airline</label>
              <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent" id="airlineSide">
                <option value="">Select Airline</option>
                <option value="PIA" <?php echo $airline === 'PIA' ? 'selected' : ''; ?>>Pakistan International Airlines</option>
                <option value="Saudi" <?php echo $airline === 'Saudi' ? 'selected' : ''; ?>>Saudia</option>
                <option value="Emirates" <?php echo $airline === 'Emirates' ? 'selected' : ''; ?>>Emirates</option>
                <option value="Qatar" <?php echo $airline === 'Qatar' ? 'selected' : ''; ?>>Qatar Airways</option>
                <option value="Etihad" <?php echo $airline === 'Etihad' ? 'selected' : ''; ?>>Etihad Airways</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Flight List -->
        <div class="w-full lg:w-3/4">
          <?php if (!empty($results)) { ?>
            <h3 class="text-xl font-semibold text-gray-800 mb-4">Outbound Flights</h3>
            <?php foreach ($results as $flight) {
              $stops = json_decode($flight['stops'], true);
            ?>
              <div class="bg-white p-4 rounded-lg shadow-md mb-4 flex flex-col sm:flex-row justify-between items-center">
                <div class="flex flex-col sm:flex-row items-center mb-4 sm:mb-0">
                  <div>
                    <h5 class="text-lg font-semibold text-gray-800">
                      <?php echo htmlspecialchars($flight['airline_name']); ?> - <?php echo htmlspecialchars($flight['flight_number']); ?>
                    </h5>
                    <p class="text-gray-600">
                      <?php echo htmlspecialchars($flight['departure_city']); ?> to <?php echo htmlspecialchars($flight['arrival_city']); ?>
                      | Departure: <?php echo htmlspecialchars($flight['departure_time']); ?>
                    </p>
                    <p class="text-gray-600">
                      Duration: <?php echo number_format($flight['flight_duration'], 1); ?>h
                      | <?php echo $flight['has_stops'] ? (count($stops) . ' stop(s): ' . implode(', ', array_column($stops, 'city'))) : 'Direct'; ?>
                    </p>
                    <p class="text-gray-600">
                      Economy: <?php echo htmlspecialchars($flight['economy_seats']); ?> seats
                      | Business: <?php echo htmlspecialchars($flight['business_seats']); ?> seats
                      | First: <?php echo htmlspecialchars($flight['first_class_seats']); ?> seats
                    </p>
                    <?php if (!empty($flight['flight_notes'])) { ?>
                      <p class="text-gray-500 text-sm">Notes: <?php echo htmlspecialchars($flight['flight_notes']); ?></p>
                    <?php } ?>
                  </div>
                </div>
                <div class="text-center sm:text-right">
                  <div class="text-2xl font-bold text-teal-600 mb-2">Rs.<?php echo number_format($flight['economy_price']); ?></div>
                  <a href="#" class="inline-block bg-teal-500 hover:bg-teal-600 text-white font-medium py-2 px-6 rounded-lg transition duration-300">Book Now</a>
                </div>
              </div>
            <?php } ?>
          <?php } elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($departure_city)) { ?>
            <p class="text-gray-600">No outbound flights found for the selected criteria.</p>
          <?php } ?>

          <?php if ($has_return && !empty($return_results)) { ?>
            <h3 class="text-xl font-semibold text-gray-800 mb-4 mt-8">Return Flights</h3>
            <?php foreach ($return_results as $flight) {
              $stops = json_decode($flight['stops'], true);
            ?>
              <div class="bg-white p-4 rounded-lg shadow-md mb-4 flex flex-col sm:flex-row justify-between items-center">
                <div class="flex flex-col sm:flex-row items-center mb-4 sm:mb-0">
                  <div>
                    <h5 class="text-lg font-semibold text-gray-800">
                      <?php echo htmlspecialchars($flight['airline_name']); ?> - <?php echo htmlspecialchars($flight['flight_number']); ?>
                    </h5>
                    <p class="text-gray-600">
                      <?php echo htmlspecialchars($flight['departure_city']); ?> to <?php echo htmlspecialchars($flight['arrival_city']); ?>
                      | Departure: <?php echo htmlspecialchars($flight['departure_time']); ?>
                    </p>
                    <p class="text-gray-600">
                      Duration: <?php echo number_format($flight['flight_duration'], 1); ?>h
                      | <?php echo $flight['has_stops'] ? (count($stops) . ' stop(s): ' . implode(', ', array_column($stops, 'city'))) : 'Direct'; ?>
                    </p>
                    <p class="text-gray-600">
                      Economy: <?php echo htmlspecialchars($flight['economy_seats']); ?> seats
                      | Business: <?php echo htmlspecialchars($flight['business_seats']); ?> seats
                      | First: <?php echo htmlspecialchars($flight['first_class_seats']); ?> seats
                    </p>
                    <?php if (!empty($flight['flight_notes'])) { ?>
                      <p class="text-gray-500 text-sm">Notes: <?php echo htmlspecialchars($flight['flight_notes']); ?></p>
                    <?php } ?>
                  </div>
                </div>
                <div class="text-center sm:text-right">
                  <div class="text-2xl font-bold text-teal-600 mb-2">Rs.<?php echo number_format($flight['economy_price']); ?></div>
                  <a href="#" class="inline-block bg-teal-500 hover:bg-teal-600 text-white font-medium py-2 px-6 rounded-lg transition duration-300">Book Now</a>
                </div>
              </div>
            <?php } ?>
          <?php } elseif ($has_return && !empty($return_date)) { ?>
            <p class="text-gray-600">No return flights found for the selected criteria.</p>
          <?php } ?>
        </div>
      </div>
    </div>
  </section>

  <?php include "includes/footer.php" ?>
  <?php include "includes/js-links.php" ?>

  <script>
    // Update price range display
    const priceRange = document.getElementById('priceRange');
    const priceValue = document.getElementById('priceValue');
    const maxPriceInput = document.getElementById('max_price');
    priceRange.addEventListener('input', () => {
      priceValue.textContent = parseInt(priceRange.value).toLocaleString();
      maxPriceInput.value = priceRange.value;
    });

    // Update filters
    const departureCitySide = document.getElementById('departureCitySide');
    const airlineSide = document.getElementById('airlineSide');
    const directFlightsCheck = document.getElementById('directFlightsCheck');
    const directFlightsInput = document.getElementById('direct_flights');
    departureCitySide.addEventListener('change', () => {
      document.getElementById('departure_city').value = departureCitySide.value;
      document.forms[0].submit();
    });
    airlineSide.addEventListener('change', () => {
      document.getElementById('airline').value = airlineSide.value;
      document.forms[0].submit();
    });
    directFlightsCheck.addEventListener('change', () => {
      directFlightsInput.value = directFlightsCheck.checked ? '1' : '0';
      document.forms[0].submit();
    });

    // Toggle return date field
    document.querySelectorAll('input[name="has_return"]').forEach(input => {
      input.addEventListener('change', () => {
        const returnDateInput = document.getElementById('return_date');
        returnDateInput.disabled = input.value == '0';
        if (input.value == '0') {
          returnDateInput.value = '';
        }
      });
    });

    // Set minimum date for departure and return
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('departure_date').setAttribute('min', today);
    document.getElementById('return_date').setAttribute('min', today);

    // Ensure return date is after departure date
    document.getElementById('departure_date').addEventListener('change', function() {
      document.getElementById('return_date').setAttribute('min', this.value);
    });
  </script>
</body>

</html>