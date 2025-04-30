<?php
require_once '../config/db.php';

// Start admin session
session_name('admin_session');
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
  header('Location: login.php');
  exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Ensure $conn is the MySQLi connection from db.php
  global $conn;

  // Prepare data for outbound flight
  $airline_name = $_POST['airline_name'] ?? '';
  $flight_number = $_POST['flight_number'] ?? '';
  $departure_city = $_POST['departure_city'] ?? '';
  $arrival_city = $_POST['arrival_city'] ?? '';
  $has_stops = isset($_POST['has_stops']) && $_POST['has_stops'] == 1 ? 1 : 0;
  $departure_date = $_POST['departure_date'] ?? '';
  $departure_time = $_POST['departure_time'] ?? '';
  $flight_duration = floatval($_POST['flight_duration'] ?? 0);
  $distance = intval($_POST['distance'] ?? 0);

  // Process stops
  $stops = [];
  if ($has_stops && !empty($_POST['stop_city']) && !empty($_POST['stop_duration'])) {
    foreach ($_POST['stop_city'] as $index => $city) {
      if (!empty($city) && isset($_POST['stop_duration'][$index])) {
        $stops[] = [
          'city' => $city,
          'duration' => floatval($_POST['stop_duration'][$index])
        ];
      }
    }
  }
  $stops_json = json_encode($stops);

  // Prepare data for return flight
  $has_return = isset($_POST['has_return']) && $_POST['has_return'] == 1 ? 1 : 0;
  $return_airline = $has_return ? ($_POST['return_airline'] === 'same' ? $airline_name : ($_POST['return_airline'] ?? '')) : null;
  $return_flight_number = $has_return ? ($_POST['return_flight_number'] ?? '') : null;
  $return_date = $has_return ? ($_POST['return_date'] ?? '') : null;
  $return_time = $has_return ? ($_POST['return_time'] ?? '') : null;
  $return_flight_duration = $has_return ? floatval($_POST['return_flight_duration'] ?? 0) : null;
  $has_return_stops = $has_return && isset($_POST['has_return_stops']) && $_POST['has_return_stops'] == 1 ? 1 : 0;

  // Process return stops
  $return_stops = [];
  if ($has_return && $has_return_stops && !empty($_POST['return_stop_city']) && !empty($_POST['return_stop_duration'])) {
    foreach ($_POST['return_stop_city'] as $index => $city) {
      if (!empty($city) && isset($_POST['return_stop_duration'][$index])) {
        $return_stops[] = [
          'city' => $city,
          'duration' => floatval($_POST['return_stop_duration'][$index])
        ];
      }
    }
  }
  $return_stops_json = json_encode($return_stops);

  // Pricing and seats
  $economy_price = intval($_POST['economy_price'] ?? 0);
  $business_price = intval($_POST['business_price'] ?? 0);
  $first_class_price = intval($_POST['first_class_price'] ?? 0);
  $economy_seats = intval($_POST['economy_seats'] ?? 0);
  $business_seats = intval($_POST['business_seats'] ?? 0);
  $first_class_seats = intval($_POST['first_class_seats'] ?? 0);
  $flight_notes = $_POST['flight_notes'] ?? null;

  // Prepare and execute MySQLi query
  $sql = "INSERT INTO flights (
        airline_name, flight_number, departure_city, arrival_city, has_stops, stops,
        departure_date, departure_time, flight_duration, distance,
        has_return, return_airline, return_flight_number, return_date, return_time,
        return_flight_duration, has_return_stops, return_stops,
        economy_price, business_price, first_class_price,
        economy_seats, business_seats, first_class_seats, flight_notes
    ) VALUES (
        ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?,
        ?, ?, ?, ?, ?,
        ?, ?, ?,
        ?, ?, ?,
        ?, ?, ?, ?
    )";

  $stmt = $conn->prepare($sql);
  if ($stmt === false) {
    echo "<script>alert('Error preparing query: " . addslashes($conn->error) . "');</script>";
    exit;
  }

  // Handle NULL values explicitly
  $return_airline = $return_airline ?? null;
  $return_flight_number = $return_flight_number ?? null;
  $return_date = $return_date ?? null;
  $return_time = $return_time ?? null;
  $return_flight_duration = $return_flight_duration ?? null;
  $flight_notes = $flight_notes ?? null;

  // Correct the type definition string to match the number of variables
  $stmt->bind_param(
    "ssssisssdiissssdissiiiiss", // Adjusted type string to include the missing placeholder
    $airline_name,              // s
    $flight_number,             // s
    $departure_city,            // s
    $arrival_city,              // s
    $has_stops,                 // i
    $stops_json,                // s
    $departure_date,            // s
    $departure_time,            // s
    $flight_duration,           // d
    $distance,                  // i
    $has_return,                // i
    $return_airline,            // s
    $return_flight_number,      // s
    $return_date,               // s
    $return_time,               // s
    $return_flight_duration,    // d
    $has_return_stops,          // i
    $return_stops_json,         // s
    $economy_price,             // i
    $business_price,            // i
    $first_class_price,         // i
    $economy_seats,             // i
    $business_seats,            // i
    $first_class_seats,         // i
    $flight_notes               // s
  );
  // Execute the statement
  if ($stmt->execute()) {
    echo "<script>
            alert('Flight added successfully!');
            window.location.href = 'view-flights.php';
        </script>";
  } else {
    echo "<script>alert('Error adding flight: " . addslashes($stmt->error) . "');</script>";
  }

  $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add New Flight | UmrahFlights Admin</title>
  <!-- Tailwind CSS -->
  <!-- <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet"> -->
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="assets/css/add-flight.css">
  <link rel="stylesheet" href="assets/css/sidebar.css">
</head>

<body class="bg-gray-100">
  <?php include 'includes/sidebar.php'; ?>

  <!-- Main Content -->
  <div class="ml-0 md:ml-64 mt-10 px-4 sm:px-6 lg:px-8 transition-all duration-300">
    <!-- Navbar -->
    <nav class="bg-white shadow-lg rounded-lg p-5 mb-6">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <button id="sidebarToggle" class="text-gray-500 hover:text-gray-700 focus:outline-none md:hidden">
            <i class="fas fa-bars text-xl"></i>
          </button>
          <h1 id="dashboardHeader" class="text-lg font-semibold text-gray-800 cursor-pointer hover:text-indigo-600">
            <i class="fas fa-plane text-indigo-600 mr-2"></i>Add New Flight
          </h1>
        </div>
        <div class="flex items-center space-x-4">
          <button onclick="history.back()" class="flex items-center px-3 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-indigo-50">
            <i class="fas fa-arrow-left mr-2"></i> Back
          </button>
          <!-- User Dropdown -->
          <div class="relative">
            <button id="userDropdownButton" class="flex items-center space-x-2 text-gray-700 hover:bg-indigo-50 rounded-lg px-3 py-2 focus:outline-none">
              <div class="rounded-full overflow-hidden" style="width: 32px; height: 32px;">
                <div class="bg-gray-200 w-full h-full"></div>
              </div>
              <span class="hidden md:inline text-sm font-medium">Admin User</span>
              <svg class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <ul id="userDropdownMenu" class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 hidden z-50">
              <li>
                <a class="flex items-center px-4 py-2 text-sm text-red-500 hover:bg-red-50" href="logout.php">
                  <i class="fas fa-sign-out-alt mr-2"></i> Logout
                </a>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </nav>

    <!-- Form Container -->
    <div class="bg-white shadow-lg rounded-lg p-6">
      <div class="mb-6">
        <h2 class="text-xl font-semibold text-indigo-600">
          <i class="fas fa-plane-departure mr-2"></i>Add New Flight
        </h2>
        <p class="text-sm text-gray-500">Enter flight details for Umrah journey</p>
      </div>

      <form action="" method="POST" id="flightForm">
        <!-- Outbound Flight Section Title -->
        <div class="mb-4">
          <h3 class="text-lg font-semibold text-indigo-600">
            <i class="fas fa-plane-departure mr-2"></i>Outbound Flight Details
          </h3>
        </div>

        <!-- Airline & Flight Number -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
          <div>
            <label for="airline_name" class="block text-sm font-medium text-gray-700">Airline Name <span class="text-red-500">*</span></label>
            <select name="airline_name" id="airline_name" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500" required>
              <option value="">Select Airline</option>
              <optgroup label="Pakistani Airlines">
                <option value="PIA">Pakistan International Airlines (PIA)</option>
                <option value="AirBlue">AirBlue</option>
                <option value="SereneAir">Serene Air</option>
                <option value="AirSial">AirSial</option>
                <option value="FlyJinnah">Fly Jinnah</option>
              </optgroup>
              <optgroup label="Middle Eastern Airlines">
                <option value="Emirates">Emirates</option>
                <option value="Qatar">Qatar Airways</option>
                <option value="Etihad">Etihad Airways</option>
                <option value="Saudi">Saudia (Saudi Airlines)</option>
                <option value="Flynas">Flynas</option>
                <option value="Flydubai">Flydubai</option>
                <option value="OmanAir">Oman Air</option>
                <option value="GulfAir">Gulf Air</option>
                <option value="KuwaitAirways">Kuwait Airways</option>
              </optgroup>
              <optgroup label="Asian Airlines">
                <option value="Thai">Thai Airways</option>
                <option value="Malaysia">Malaysia Airlines</option>
                <option value="Singapore">Singapore Airlines</option>
                <option value="Cathay">Cathay Pacific</option>
                <option value="ChinaSouthern">China Southern</option>
                <option value="Turkish">Turkish Airlines</option>
              </optgroup>
              <optgroup label="European & American Airlines">
                <option value="British">British Airways</option>
                <option value="Lufthansa">Lufthansa</option>
                <option value="AirFrance">Air France</option>
                <option value="KLM">KLM Royal Dutch Airlines</option>
                <option value="Virgin">Virgin Atlantic</option>
              </optgroup>
              <optgroup label="Budget Airlines">
                <option value="AirArabia">Air Arabia</option>
                <option value="Indigo">IndiGo</option>
                <option value="SpiceJet">SpiceJet</option>
              </optgroup>
            </select>
          </div>
          <div>
            <label for="flight_number" class="block text-sm font-medium text-gray-700">Flight Number <span class="text-red-500">*</span></label>
            <input type="text" name="flight_number" id="flight_number" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="e.g., PK-309" required maxlength="9">
          </div>
        </div>

        <!-- Route Information -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
          <div>
            <label for="departure_city" class="block text-sm font-medium text-gray-700">Departure City <span class="text-red-500">*</span></label>
            <select name="departure_city" id="departure_city" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500" required>
              <option value="">Select City</option>
              <option value="Karachi">Karachi</option>
              <option value="Lahore">Lahore</option>
              <option value="Islamabad">Islamabad</option>
              <option value="Rawalpindi">Rawalpindi</option>
              <option value="Faisalabad">Faisalabad</option>
              <option value="Multan">Multan</option>
              <option value="Hyderabad">Hyderabad</option>
              <option value="Peshawar">Peshawar</option>
              <option value="Quetta">Quetta</option>
              <optgroup label="Punjab">
                <option value="Gujranwala">Gujranwala</option>
                <option value="Sialkot">Sialkot</option>
                <option value="Bahawalpur">Bahawalpur</option>
                <option value="Sargodha">Sargodha</option>
                <option value="Jhang">Jhang</option>
                <option value="Gujrat">Gujrat</option>
                <option value="Kasur">Kasur</option>
                <option value="Okara">Okara</option>
                <option value="Sahiwal">Sahiwal</option>
                <option value="Sheikhupura">Sheikhupura</option>
              </optgroup>
              <optgroup label="Sindh">
                <option value="Sukkur">Sukkur</option>
                <option value="Larkana">Larkana</option>
                <option value="Nawabshah">Nawabshah</option>
                <option value="Mirpur Khas">Mirpur Khas</option>
                <option value="Thatta">Thatta</option>
                <option value="Jacobabad">Jacobabad</option>
              </optgroup>
              <optgroup label="Khyber Pakhtunkhwa">
                <option value="Mardan">Mardan</option>
                <option value="Abbottabad">Abbottabad</option>
                <option value="Swat">Swat</option>
                <option value="Nowshera">Nowshera</option>
                <option value="Charsadda">Charsadda</option>
                <option value="Mansehra">Mansehra</option>
              </optgroup>
              <optgroup label="Balochistan">
                <option value="Gwadar">Gwadar</option>
                <option value="Khuzdar">Khuzdar</option>
                <option value="Chaman">Chaman</option>
                <option value="Zhob">Zhob</option>
              </optgroup>
              <optgroup label="Azad Kashmir & Gilgit-Baltistan">
                <option value="Muzaffarabad">Muzaffarabad</option>
                <option value="Mirpur">Mirpur</option>
                <option value="Gilgit">Gilgit</option>
                <option value="Skardu">Skardu</option>
              </optgroup>
            </select>
          </div>
          <div>
            <label for="arrival_city" class="block text-sm font-medium text-gray-700">Arrival City <span class="text-red-500">*</span></label>
            <select name="arrival_city" id="arrival_city" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500" required>
              <option value="">Select City</option>
              <option value="Jeddah">Jeddah</option>
              <option value="Medina">Medina</option>
            </select>
          </div>
        </div>

        <!-- Flight Stops -->
        <div class="bg-gray-50 p-4 rounded-lg mb-4">
          <div class="flex items-center justify-between mb-4">
            <h4 class="text-base font-semibold text-gray-800">Flight Stops</h4>
            <div class="flex space-x-4">
              <label class="flex items-center">
                <input type="radio" name="has_stops" id="directFlight" value="0" class="mr-2" checked>
                <span class="text-sm text-gray-700">Direct Flight</span>
              </label>
              <label class="flex items-center">
                <input type="radio" name="has_stops" id="hasStops" value="1" class="mr-2">
                <span class="text-sm text-gray-700">Has Stops</span>
              </label>
            </div>
          </div>
          <div id="stops-container" class="hidden">
            <div class="stop-row grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Stop City</label>
                <input type="text" name="stop_city[]" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500" maxlength="12" placeholder="e.g., Dubai">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Stop Duration (hours)</label>
                <input type="text" name="stop_duration[]" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="e.g., 4">
              </div>
            </div>
            <div class="text-right">
              <button type="button" id="add-stop" class="inline-flex items-center px-3 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                <i class="fas fa-plus mr-2"></i>Add Another Stop
              </button>
            </div>
          </div>
        </div>

        <!-- Schedule and Duration -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
          <div>
            <label for="departure_date" class="block text-sm font-medium text-gray-700">Departure Date <span class="text-red-500">*</span></label>
            <input type="date" name="departure_date" id="departure_date" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500" required>
          </div>
          <div>
            <label for="departure_time" class="block text-sm font-medium text-gray-700">Departure Time <span class="text-red-500">*</span></label>
            <input type="text" name="departure_time" id="departure_time" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="HH:MM (24-hour format)" required>
          </div>
          <div>
            <label for="flight_duration" class="block text-sm font-medium text-gray-700">Flight Duration (hours) <span class="text-red-500">*</span></label>
            <input type="number" name="flight_duration" id="flight_duration" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="e.g., 5.5" step="0.1" required>
          </div>
        </div>

        <!-- Distance Field -->
        <div class="mb-4">
          <label for="distance" class="block text-sm font-medium text-gray-700">Distance (km) <span class="text-red-500">*</span></label>
          <input type="number" name="distance" id="distance" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="e.g., 3500" step="1" required>
        </div>

        <!-- Return Flight Section -->
        <div class="border-t pt-4 mt-4">
          <div class="mb-4">
            <h3 class="text-lg font-semibold text-indigo-600">
              <i class="fas fa-plane-arrival mr-2"></i>Return Flight Details
            </h3>
          </div>
          <div class="flex items-center justify-between mb-4">
            <h4 class="text-base font-semibold text-gray-800">Journey Type</h4>
            <div class="flex space-x-4">
              <label class="flex items-center">
                <input type="radio" name="has_return" id="oneWayFlight" value="0" class="mr-2" checked>
                <span class="text-sm text-gray-700">One-way Flight</span>
              </label>
              <label class="flex items-center">
                <input type="radio" name="has_return" id="roundTrip" value="1" class="mr-2">
                <span class="text-sm text-gray-700">Round Trip</span>
              </label>
            </div>
          </div>
          <div id="return-container" class="bg-gray-50 p-4 rounded-lg hidden">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
              <div>
                <label for="return_airline" class="block text-sm font-medium text-gray-700">Return Airline</label>
                <select name="return_airline" id="return_airline" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500">
                  <option value="">Select Airline</option>
                  <option value="same">Same as Outbound</option>
                  <optgroup label="Pakistani Airlines">
                    <option value="PIA">Pakistan International Airlines (PIA)</option>
                    <option value="AirBlue">AirBlue</option>
                    <option value="SereneAir">Serene Air</option>
                    <option value="AirSial">AirSial</option>
                    <option value="FlyJinnah">Fly Jinnah</option>
                  </optgroup>
                  <optgroup label="Middle Eastern Airlines">
                    <option value="Emirates">Emirates</option>
                    <option value="Qatar">Qatar Airways</option>
                    <option value="Etihad">Etihad Airways</option>
                    <option value="Saudi">Saudia (Saudi Airlines)</option>
                    <option value="Flynas">Flynas</option>
                    <option value="Flydubai">Flydubai</option>
                    <option value="OmanAir">Oman Air</option>
                  </optgroup>
                  <optgroup label="Asian Airlines">
                    <option value="Thai">Thai Airways</option>
                    <option value="Singapore">Singapore Airlines</option>
                    <option value="Turkish">Turkish Airlines</option>
                    <option value="Malaysia">Malaysia Airlines</option>
                  </optgroup>
                  <optgroup label="European & American Airlines">
                    <option value="British">British Airways</option>
                    <option value="Lufthansa">Lufthansa</option>
                    <option value="AirFrance">Air France</option>
                  </optgroup>
                  <optgroup label="Budget Airlines">
                    <option value="AirArabia">Air Arabia</option>
                    <option value="Indigo">IndiGo</option>
                  </optgroup>
                </select>
              </div>
              <div>
                <label for="return_flight_number" class="block text-sm font-medium text-gray-700">Return Flight Number</label>
                <input type="text" name="return_flight_number" id="return_flight_number" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="e.g., PK-310" maxlength="7">
              </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
              <div>
                <label for="return_date" class="block text-sm font-medium text-gray-700">Return Date</label>
                <input type="date" name="return_date" id="return_date" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500">
              </div>
              <div>
                <label for="return_time" class="block text-sm font-medium text-gray-700">Return Time</label>
                <input type="text" name="return_time" id="return_time" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="HH:MM (24-hour format)">
              </div>
              <div>
                <label for="return_flight_duration" class="block text-sm font-medium text-gray-700">Return Flight Duration (hours)</label>
                <input type="text" name="return_flight_duration" id="return_flight_duration" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="e.g., 5.5">
              </div>
            </div>
            <div class="mt-4">
              <div class="flex items-center justify-between mb-4">
                <h5 class="text-base font-semibold text-gray-800">Return Flight Stops</h5>
                <div class="flex space-x-4">
                  <label class="flex items-center">
                    <input type="radio" name="has_return_stops" id="directReturnFlight" value="0" class="mr-2" checked>
                    <span class="text-sm text-gray-700">Direct Return Flight</span>
                  </label>
                  <label class="flex items-center">
                    <input type="radio" name="has_return_stops" id="hasReturnStops" value="1" class="mr-27D;
                    <span class=" text-sm text-gray-700">Has Stops</span>
                  </label>
                </div>
              </div>
              <div id="return-stops-container" class="hidden">
                <div class="return-stop-row grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Return Stop City</label>
                    <input type="text" name="return_stop_city[]" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="e.g., Dubai" maxlength="50">
                    <div class="invalid-feedback"></div>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Return Stop Duration (hours)</label>
                    <input type="text" name="return_stop_duration[]" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="e.g., 2">
                    <div class="invalid-feedback"></div>
                  </div>
                </div>
                <div class="text-right">
                  <button type="button" id="add-return-stop" class="inline-flex items-center px-3 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    <i class="fas fa-plus mr-2"></i>Add Another Return Stop
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Pricing Section -->
        <div class="border-t pt-4 mt-4">
          <div class="mb-4">
            <h3 class="text-lg font-semibold text-indigo-600">
              <i class="fas fa-tags mr-2"></i>Pricing Information
            </h3>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div>
              <label for="economy_price" class="block text-sm font-medium text-gray-700">Economy Price (PKR) <span class="text-red-500">*</span></label>
              <input type="number" name="economy_price" id="economy_price" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="242,250" required>
            </div>
            <div>
              <label for="business_price" class="block text-sm font-medium text-gray-700">Business Price (PKR) <span class="text-red-500">*</span></label>
              <input type="number" name="business_price" id="business_price" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="427,500" required>
            </div>
            <div>
              <label for="first_class_price" class="block text-sm font-medium text-gray-700">First Class Price (PKR) <span class="text-red-500">*</span></label>
              <input type="number" name="first_class_price" id="first_class_price" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="712,500" required>
            </div>
          </div>
        </div>

        <!-- Seat Information -->
        <div class="border-t pt-4 mt-4">
          <div class="mb-4">
            <h3 class="text-lg font-semibold text-indigo-600">
              <i class="fas fa-chair mr-2"></i>Seat Information
            </h3>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div>
              <label for="economy_seats" class="block text-sm font-medium text-gray-700">Economy Seats <span class="text-red-500">*</span></label>
              <input type="number" name="economy_seats" id="economy_seats" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="200" required>
            </div>
            <div>
              <label for="business_seats" class="block text-sm font-medium text-gray-700">Business Seats <span class="text-red-500">*</span></label>
              <input type="number" name="business_seats" id="business_seats" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="30" required>
            </div>
            <div>
              <label for="first_class_seats" class="block text-sm font-medium text-gray-700">First Class Seats <span class="text-red-500">*</span></label>
              <input type="number" name="first_class_seats" id="first_class_seats" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="10" required>
            </div>
          </div>
        </div>

        <!-- Flight Notes -->
        <div class="mb-4">
          <label for="flight_notes" class="block text-sm font-medium text-gray-700">Flight Notes (Optional)</label>
          <textarea name="flight_notes" id="flight_notes" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500" rows="3" placeholder="Any additional information about this flight"></textarea>
        </div>

        <!-- Submit Buttons -->
        <div class="flex space-x-3">
          <button type="submit" id="submit-btn" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
            <i class="fas fa-save mr-2"></i> Save Flight
          </button>
          <button type="reset" class="inline-flex items-center px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
            <i class="fas fa-times mr-2"></i>Reset
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Custom JavaScript -->
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      // Sidebar elements (assumed from sidebar.php)
      const sidebar = document.getElementById('sidebar');
      const sidebarOverlay = document.getElementById('sidebar-overlay');
      const sidebarToggle = document.getElementById('sidebarToggle');
      const sidebarClose = document.getElementById('sidebar-close');
      const dashboardHeader = document.getElementById('dashboardHeader');

      // User dropdown elements
      const userDropdownButton = document.getElementById('userDropdownButton');
      const userDropdownMenu = document.getElementById('userDropdownMenu');

      // Form elements
      const addStopButton = document.getElementById('add-stop');
      const addReturnStopButton = document.getElementById('add-return-stop');

      // Error handling for missing elements
      if (!sidebar || !sidebarOverlay || !sidebarToggle || !sidebarClose) {
        console.warn('One or more sidebar elements are missing. Ensure sidebar.php includes #sidebar, #sidebar-overlay, #sidebar-close.');
      }
      if (!userDropdownButton || !userDropdownMenu) {
        console.warn('User dropdown elements are missing.');
      }
      if (!dashboardHeader) {
        console.warn('Dashboard header element is missing.');
      }
      if (!addStopButton || !addReturnStopButton) {
        console.warn('Form stop buttons are missing.');
      }

      // Sidebar toggle function
      const toggleSidebar = () => {
        if (sidebar && sidebarOverlay && sidebarToggle) {
          sidebar.classList.toggle('-translate-x-full');
          sidebarOverlay.classList.toggle('hidden');
          sidebarToggle.classList.toggle('hidden');
        }
      };

      // Sidebar event listeners
      if (sidebarToggle) sidebarToggle.addEventListener('click', toggleSidebar);
      if (sidebarClose) sidebarClose.addEventListener('click', toggleSidebar);
      if (sidebarOverlay) sidebarOverlay.addEventListener('click', toggleSidebar);
      if (dashboardHeader) {
        dashboardHeader.addEventListener('click', () => {
          if (sidebar && sidebar.classList.contains('-translate-x-full')) {
            toggleSidebar();
          }
        });
      }

      // User dropdown toggle
      if (userDropdownButton && userDropdownMenu) {
        userDropdownButton.addEventListener('click', () => {
          userDropdownMenu.classList.toggle('hidden');
        });
        document.addEventListener('click', (event) => {
          if (!userDropdownButton.contains(event.target) && !userDropdownMenu.contains(event.target)) {
            userDropdownMenu.classList.add('hidden');
          }
        });
      }

      // Form interactions
      const toggleStopsSection = (show) => {
        document.getElementById('stops-container').classList.toggle('hidden', !show);
      };
      const toggleReturnSection = (show) => {
        document.getElementById('return-container').classList.toggle('hidden', !show);
      };
      const toggleReturnStopsSection = (show) => {
        document.getElementById('return-stops-container').classList.toggle('hidden', !show);
      };

      // Bind radio buttons
      document.querySelectorAll('input[name="has_stops"]').forEach(input => {
        input.addEventListener('change', () => toggleStopsSection(input.value === '1'));
      });
      document.querySelectorAll('input[name="has_return"]').forEach(input => {
        input.addEventListener('change', () => toggleReturnSection(input.value === '1'));
      });
      document.querySelectorAll('input[name="has_return_stops"]').forEach(input => {
        input.addEventListener('change', () => toggleReturnStopsSection(input.value === '1'));
      });

      // Add stop row (outbound)
      if (addStopButton) {
        addStopButton.addEventListener('click', () => {
          const stopRow = document.querySelector('.stop-row').cloneNode(true);
          stopRow.querySelectorAll('input').forEach(input => input.value = '');
          addStopButton.closest('.text-right').before(stopRow);
          // Re-initialize validation for new inputs
          const newCityInput = stopRow.querySelector('input[name="stop_city[]"]');
          const newDurationInput = stopRow.querySelector('input[name="stop_duration[]"]');
          if (window.addNewStopCity) window.addNewStopCity(newCityInput);
          if (window.initStopDurationValidation) window.initStopDurationValidation(newDurationInput);
        });
      }

      // Add return stop row
      if (addReturnStopButton) {
        addReturnStopButton.addEventListener('click', () => {
          const returnStopRow = document.querySelector('.return-stop-row').cloneNode(true);
          returnStopRow.querySelectorAll('input').forEach(input => input.value = '');
          addReturnStopButton.closest('.text-right').before(returnStopRow);
          // Re-initialize validation for new inputs
          const newCityInput = returnStopRow.querySelector('input[name="return_stop_city[]"]');
          const newDurationInput = returnStopRow.querySelector('input[name="return_stop_duration[]"]');
          if (window.addNewStopCity) window.addNewStopCity(newCityInput);
          if (window.initStopDurationValidation) window.initStopDurationValidation(newDurationInput);
        });
      }
    });
  </script>
  <!-- Validation Script -->
  <script src="assets/js/validate.js"></script>
</body>

</html>