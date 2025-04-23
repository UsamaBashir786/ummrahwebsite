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

// Fetch available flight dates for calendar markers
$flight_dates = [];
if (!empty($departure_city) && !empty($arrival_city)) {
  global $conn;
  // Outbound flights
  $sql = "SELECT departure_date, airline_name, flight_number, departure_city, arrival_city 
            FROM flights 
            WHERE departure_city = ? AND arrival_city = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ss", $departure_city, $arrival_city);
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    $flight_dates[$row['departure_date']][] = [
      'type' => 'outbound',
      'details' => "{$row['airline_name']} {$row['flight_number']}: {$row['departure_city']} to {$row['arrival_city']}"
    ];
  }
  $stmt->close();

  // Return flights (if round-trip)
  if ($has_return) {
    $sql = "SELECT departure_date, airline_name, flight_number, departure_city, arrival_city 
                FROM flights 
                WHERE departure_city = ? AND arrival_city = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $arrival_city, $departure_city);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
      $flight_dates[$row['departure_date']][] = [
        'type' => 'return',
        'details' => "{$row['airline_name']} {$row['flight_number']}: {$row['departure_city']} to {$row['arrival_city']}"
      ];
    }
    $stmt->close();
  }
}

// Perform search if form is submitted
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
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <script src="https://cdn.tailwindcss.com"></script>
  <title>Search Flights | UmrahFlights</title>
  <style>
    .autocomplete-suggestions {
      position: absolute;
      background: white;
      border: 1px solid #e5e7eb;
      border-radius: 0.5rem;
      max-height: 200px;
      overflow-y: auto;
      width: 100%;
      z-index: 10;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .autocomplete-suggestion {
      padding: 0.5rem 1rem;
      cursor: pointer;
    }

    .autocomplete-suggestion:hover {
      background-color: #f3f4f6;
    }

    .flight-card {
      transition: transform 0.2s, box-shadow 0.2s;
    }

    .flight-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
    }

    .flatpickr-day.has-flight {
      position: relative;
      background: #e6fffa !important;
      border-color: #2dd4bf !important;
    }

    .flatpickr-day.has-flight::after {
      content: '';
      position: absolute;
      bottom: 2px;
      left: 50%;
      transform: translateX(-50%);
      width: 4px;
      height: 4px;
      background: #2dd4bf;
      border-radius: 50%;
    }

    .flatpickr-day.has-flight:hover::before {
      content: attr(data-tooltip);
      position: absolute;
      top: -50px;
      left: 50%;
      transform: translateX(-50%);
      background: #1f2937;
      color: white;
      padding: 6px 10px;
      border-radius: 4px;
      font-size: 12px;
      white-space: pre-wrap;
      z-index: 1000;
      display: block;
      pointer-events: none;
    }

    /* Booking.com inspired styles */
    .search-section {
      background: linear-gradient(135deg, #003087 0%, #0059b3 100%);
      padding: 2rem;
      border-radius: 12px;
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
      margin-top: 2rem;
    }

    .search-section h4 {
      color: white;
      font-size: 1.75rem;
      font-weight: 700;
      margin-bottom: 1.5rem;
    }

    .search-form {
      background: white;
      padding: 1rem;
      border-radius: 8px;
      display: flex;
      flex-wrap: wrap;
      gap: 1rem;
      align-items: center;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .search-form input,
    .search-form select {
      border: 2px solid #e0e0e0;
      border-radius: 6px;
      padding: 0.75rem 1rem;
      font-size: 1rem;
      transition: border-color 0.3s ease;
    }

    .search-form input:focus,
    .search-form select:focus {
      border-color: #003087;
      outline: none;
    }

    .search-form button {
      background-color: #ffb700;
      color: #003087;
      font-weight: 600;
      padding: 0.75rem 2rem;
      border-radius: 6px;
      border: none;
      transition: background-color 0.3s ease;
    }

    .search-form button:hover {
      background-color: #e6a500;
    }

    .search-form label {
      color: #333;
      font-weight: 500;
    }

    .journey-type {
      background: #f5f5f5;
      padding: 0.5rem;
      border-radius: 6px;
    }

    .journey-type input[type="radio"] {
      accent-color: #003087;
    }

    .checkbox-filter {
      color: #333;
      font-size: 0.9rem;
    }

    .checkbox-filter input[type="checkbox"] {
      accent-color: #003087;
    }
  </style>
</head>

<body class="pt-24 bg-gray-100">
  <?php include 'includes/navbar.php' ?>

  <!-- Search Section -->
  <section class="search-section">
    <div class="container mx-auto px-4">
      <h4>Find the Best Umrah Flights</h4>

      <form class="search-form" method="GET" action="">
        <!-- Journey Type -->
        <div class="journey-type flex items-center gap-4">
          <div class="flex items-center">
            <input type="radio" name="has_return" id="oneWay" value="0" class="w-4 h-4" <?php echo $has_return ? '' : 'checked'; ?>>
            <label for="oneWay" class="ml-2 text-sm">One-way</label>
          </div>
          <div class="flex items-center">
            <input type="radio" name="has_return" id="roundTrip" value="1" class="w-4 h-4" <?php echo $has_return ? 'checked' : ''; ?>>
            <label for="roundTrip" class="ml-2 text-sm">Round Trip</label>
          </div>
        </div>

        <!-- Departure City -->
        <div class="w-full sm:w-48 relative">
          <input type="text" name="departure_city" id="departure_city" class="w-full" placeholder="From (e.g., Karachi)" value="<?php echo htmlspecialchars($departure_city); ?>" required autocomplete="off">
          <div id="departure_suggestions" class="autocomplete-suggestions hidden"></div>
        </div>

        <!-- Arrival City -->
        <div class="w-full sm:w-48 relative">
          <input type="text" name="arrival_city" id="arrival_city" class="w-full" placeholder="To (e.g., Jeddah)" value="<?php echo htmlspecialchars($arrival_city); ?>" required autocomplete="off">
          <div id="arrival_suggestions" class="autocomplete-suggestions hidden"></div>
        </div>

        <!-- Departure Date -->
        <div class="w-full sm:w-48">
          <input type="text" name="departure_date" id="departure_date" class="w-full" placeholder="Depart" value="<?php echo htmlspecialchars($departure_date); ?>" required>
        </div>

        <!-- Return Date -->
        <div class="w-full sm:w-48">
          <input type="text" name="return_date" id="return_date" class="w-full" placeholder="Return" value="<?php echo htmlspecialchars($return_date); ?>" <?php echo $has_return ? '' : 'disabled'; ?>>
        </div>

        <!-- Travellers and Class -->
        <div class="w-full sm:w-48">
          <select name="travellers" id="travellers" class="w-full">
            <option value="1 Adult, Economy">1 Adult, Economy</option>
            <option value="2 Adults, Economy">2 Adults, Economy</option>
            <option value="1 Adult, Business">1 Adult, Business</option>
            <option value="2 Adults, Business">2 Adults, Business</option>
            <option value="1 Adult, First Class">1 Adult, First Class</option>
          </select>
        </div>

        <!-- Search Button -->
        <button type="submit">Search Flights</button>

        <!-- Hidden Filters -->
        <input type="hidden" name="max_price" id="max_price" value="<?php echo htmlspecialchars($max_price); ?>">
        <input type="hidden" name="airline" id="airline" value="<?php echo htmlspecialchars($airline); ?>">
        <input type="hidden" name="direct_flights" id="direct_flights" value="<?php echo $direct_flights; ?>">
      </form>

      <div class="mt-4 flex flex-wrap gap-4">
        <div class="flex items-center checkbox-filter">
          <input type="checkbox" id="nearbyAirportsFrom" class="w-4 h-4">
          <label for="nearbyAirportsFrom" class="text-white ml-2">Add nearby airports (From)</label>
        </div>
        <div class="flex items-center checkbox-filter">
          <input type="checkbox" id="nearbyAirportsTo" class="w-4 h-4">
          <label for="nearbyAirportsTo" class="ml-2 text-white">Add nearby airports (To)</label>
        </div>
        <div class="flex items-center checkbox-filter">
          <input type="checkbox" id="directFlightsCheck" class="w-4 h-4" <?php echo $direct_flights ? 'checked' : ''; ?>>
          <label for="directFlightsCheck" class="ml-2 text-white">Direct flights</label>
        </div>
      </div>
    </div>
  </section>

  <!-- Flight Listing Section -->
  <section class="py-12">
    <div class="container mx-auto px-4">
      <h2 class="text-3xl font-bold text-gray-800 mb-8">Find Your Umrah Flight</h2>

      <div class="flex flex-col lg:flex-row gap-8">
        <!-- Flight List -->
        <div class="w-full">
          <?php if (!empty($results)) { ?>
            <h3 class="text-xl font-semibold text-gray-800 mb-4">Outbound Flights</h3>
            <?php foreach ($results as $flight) {
              $stops = json_decode($flight['stops'], true);
              $class_type = explode(', ', $_GET['travellers'] ?? '1 Adult, Economy')[1];
              $price = $class_type === 'Economy' ? $flight['economy_price'] : ($class_type === 'Business' ? $flight['business_price'] : $flight['first_class_price']);
              $seats = $class_type === 'Economy' ? $flight['economy_seats'] : ($class_type === 'Business' ? $flight['business_seats'] : $flight['first_class_seats']);
            ?>
              <div class="bg-white p-4 rounded-lg shadow-md mb-4 flex flex-col sm:flex-row justify-between items-center flight-card">
                <div class="flex flex-col sm:flex-row items-center mb-4 sm:mb-0">
                  <div>
                    <h5 class="text-lg font-semibold text-gray-800">
                      <?php echo htmlspecialchars($flight['airline_name']); ?> - <?php echo htmlspecialchars($flight['flight_number']); ?>
                    </h5>
                    <p class="text-gray-600">
                      <?php echo htmlspecialchars($flight['departure_city']); ?> to <?php echo htmlspecialchars($flight['arrival_city']); ?>
                      | <?php echo htmlspecialchars($flight['departure_time']); ?>, <?php echo htmlspecialchars($flight['departure_date']); ?>
                    </p>
                    <p class="text-gray-600">
                      Duration: <?php echo number_format($flight['flight_duration'], 1); ?>h
                      | <?php echo $flight['has_stops'] ? (count($stops) . ' stop(s): ' . implode(', ', array_column($stops, 'city'))) : 'Direct'; ?>
                    </p>
                    <p class="text-gray-600">
                      <?php echo htmlspecialchars($class_type); ?> | <?php echo htmlspecialchars($seats); ?> seats available
                    </p>
                    <?php if (!empty($flight['flight_notes'])) { ?>
                      <p class="text-gray-500 text-sm">Notes: <?php echo htmlspecialchars($flight['flight_notes']); ?></p>
                    <?php } ?>
                  </div>
                </div>
                <div class="text-center sm:text-right">
                  <div class="text-2xl font-bold text-teal-600 mb-2">Rs.<?php echo number_format($price); ?></div>
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
              $class_type = explode(', ', $_GET['travellers'] ?? '1 Adult, Economy')[1];
              $price = $class_type === 'Economy' ? $flight['economy_price'] : ($class_type === 'Business' ? $flight['business_price'] : $flight['first_class_price']);
              $seats = $class_type === 'Economy' ? $flight['economy_seats'] : ($class_type === 'Business' ? $flight['business_seats'] : $flight['first_class_seats']);
            ?>
              <div class="bg-white p-4 rounded-lg shadow-md mb-4 flex flex-col sm:flex-row justify-between items-center flight-card">
                <div class="flex flex-col sm:flex-row items-center mb-4 sm:mb-0">
                  <div>
                    <h5 class="text-lg font-semibold text-gray-800">
                      <?php echo htmlspecialchars($flight['airline_name']); ?> - <?php echo htmlspecialchars($flight['flight_number']); ?>
                    </h5>
                    <p class="text-gray-600">
                      <?php echo htmlspecialchars($flight['departure_city']); ?> to <?php echo htmlspecialchars($flight['arrival_city']); ?>
                      | <?php echo htmlspecialchars($flight['departure_time']); ?>, <?php echo htmlspecialchars($flight['departure_date']); ?>
                    </p>
                    <p class="text-gray-600">
                      Duration: <?php echo number_format($flight['flight_duration'], 1); ?>h
                      | <?php echo $flight['has_stops'] ? (count($stops) . ' stop(s): ' . implode(', ', array_column($stops, 'city'))) : 'Direct'; ?>
                    </p>
                    <p class="text-gray-600">
                      <?php echo htmlspecialchars($class_type); ?> | <?php echo htmlspecialchars($seats); ?> seats available
                    </p>
                    <?php if (!empty($flight['flight_notes'])) { ?>
                      <p class="text-gray-500 text-sm">Notes: <?php echo htmlspecialchars($flight['flight_notes']); ?></p>
                    <?php } ?>
                  </div>
                </div>
                <div class="text-center sm:text-right">
                  <div class="text-2xl font-bold text-teal-600 mb-2">Rs.<?php echo number_format($price); ?></div>
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
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

  <script>
    // City autocomplete data
    const cities = [{
        name: 'Karachi',
        code: 'KHI'
      },
      {
        name: 'Lahore',
        code: 'LHE'
      },
      {
        name: 'Islamabad',
        code: 'ISB'
      },
      {
        name: 'Rawalpindi',
        code: 'RWP'
      },
      {
        name: 'Faisalabad',
        code: 'FSD'
      },
      {
        name: 'Multan',
        code: 'MUX'
      },
      {
        name: 'Hyderabad',
        code: 'HDD'
      },
      {
        name: 'Peshawar',
        code: 'PEW'
      },
      {
        name: 'Quetta',
        code: 'UET'
      },
      {
        name: 'Jeddah',
        code: 'JED'
      },
      {
        name: 'Medina',
        code: 'MED'
      }
    ];

    // Autocomplete function
    function setupAutocomplete(inputId, suggestionsId) {
      const input = document.getElementById(inputId);
      const suggestions = document.getElementById(suggestionsId);

      input.addEventListener('input', () => {
        const query = input.value.toLowerCase();
        suggestions.innerHTML = '';
        if (query.length < 2) {
          suggestions.classList.add('hidden');
          return;
        }

        const matches = cities.filter(city => city.name.toLowerCase().includes(query));
        if (matches.length === 0) {
          suggestions.classList.add('hidden');
          return;
        }

        matches.forEach(city => {
          const div = document.createElement('div');
          div.className = 'autocomplete-suggestion';
          div.textContent = `${city.name} (${city.code})`;
          div.addEventListener('click', () => {
            input.value = city.name;
            suggestions.classList.add('hidden');
            document.forms[0].submit();
          });
          suggestions.appendChild(div);
        });
        suggestions.classList.remove('hidden');
      });

      document.addEventListener('click', (e) => {
        if (!input.contains(e.target) && !suggestions.contains(e.target)) {
          suggestions.classList.add('hidden');
        }
      });
    }

    // Initialize autocomplete
    setupAutocomplete('departure_city', 'departure_suggestions');
    setupAutocomplete('arrival_city', 'arrival_suggestions');

    // Flight dates for calendar markers
    const flightDates = <?php echo json_encode($flight_dates); ?>;

    // Debug flight dates
    console.log('flightDates:', flightDates);

    // Initialize Flatpickr for departure date
    const departurePicker = flatpickr('#departure_date', {
      dateFormat: 'Y-m-d',
      minDate: '2025-04-23',
      maxDate: '2026-12-31',
      onDayCreate: function(dObj, dStr, fp, dayElem) {
        const date = dayElem.dateObj.toISOString().split('T')[0];
        if (flightDates[date] && flightDates[date].length > 0) {
          dayElem.classList.add('has-flight');
          const outboundFlights = flightDates[date].filter(f => f.type === 'outbound');
          const tooltip = outboundFlights.length > 0 ?
            outboundFlights.map(f => f.details).join('\n') :
            'Flights available';
          if (tooltip) {
            dayElem.setAttribute('data-tooltip', tooltip);
          }
        }
      }
    });

    // Initialize Flatpickr for return date
    const returnPicker = flatpickr('#return_date', {
      dateFormat: 'Y-m-d',
      minDate: '2025-04-23',
      maxDate: '2026-12-31',
      onDayCreate: function(dObj, dStr, fp, dayElem) {
        const date = dayElem.dateObj.toISOString().split('T')[0];
        if (flightDates[date] && flightDates[date].length > 0) {
          dayElem.classList.add('has-flight');
          const returnFlights = flightDates[date].filter(f => f.type === 'return');
          const tooltip = returnFlights.length > 0 ?
            returnFlights.map(f => f.details).join('\n') :
            'Flights available';
          if (tooltip) {
            dayElem.setAttribute('data-tooltip', tooltip);
          }
        }
      },
      disableMobile: true
    });

    // Update filters
    const directFlightsCheck = document.getElementById('directFlightsCheck');
    const directFlightsInput = document.getElementById('direct_flights');
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
          returnPicker.clear();
        }
      });
    });

    // Ensure return date is after departure date
    document.getElementById('departure_date').addEventListener('change', function() {
      const departureDate = new Date(this.value);
      returnPicker.set('minDate', departureDate);
    });
  </script>
</body>

</html>