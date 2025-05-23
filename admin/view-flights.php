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

// Initialize variables
$flights = [];
$filteredFlights = [];
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$totalFlights = 0;

// Get all flights
$query = "SELECT * FROM flights ORDER BY departure_date DESC";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $flights[] = $row;
  }
  $totalFlights = count($flights);
}
// Function to format large numbers into K, M, B suffixes
function formatNumber($number)
{
  if ($number === null || $number == 0) {
    return 'N/A';
  }

  $number = (float)$number; // Ensure it's a number
  $suffixes = ['', 'K', 'M', 'B', 'T'];
  $index = 0;

  while ($number >= 1000 && $index < count($suffixes) - 1) {
    $number /= 1000;
    $index++;
  }

  // Round to 1 decimal place if needed, remove decimal if it's .0
  $formattedNumber = round($number, 1);
  if ($formattedNumber == round($formattedNumber)) {
    $formattedNumber = (int)$formattedNumber; // Remove .0
  }

  return $formattedNumber . $suffixes[$index];
}
// Calculate additional statistics
$totalEconomySeats = 0;
$totalBusinessSeats = 0;
$totalFirstClassSeats = 0;
$totalEconomyRevenue = 0;
$totalBusinessRevenue = 0;
$totalFirstClassRevenue = 0;
$avgEconomyPrice = 0;
$avgBusinessPrice = 0;
$avgFirstClassPrice = 0;
$totalStops = 0;
$totalDistance = 0;

if (!empty($flights)) {
  foreach ($flights as $flight) {
    // Seat counts
    $totalEconomySeats += $flight['economy_seats'];
    $totalBusinessSeats += $flight['business_seats'];
    $totalFirstClassSeats += $flight['first_class_seats'];

    // Revenue potential (price * seats)
    $totalEconomyRevenue += $flight['economy_price'] * $flight['economy_seats'];
    $totalBusinessRevenue += $flight['business_price'] * $flight['business_seats'];
    $totalFirstClassRevenue += $flight['first_class_price'] * $flight['first_class_seats'];

    // Stops
    if ($flight['has_stops']) {
      $stops = json_decode($flight['stops'], true);
      $totalStops += is_array($stops) ? count($stops) : 0;
    }
    if ($flight['has_return'] && $flight['has_return_stops']) {
      $returnStops = json_decode($flight['return_stops'], true);
      $totalStops += is_array($returnStops) ? count($returnStops) : 0;
    }

    // Distance
    $totalDistance += $flight['distance'];
  }

  // Calculate averages (avoid division by zero)
  $totalFlightsNonZero = $totalFlights > 0 ? $totalFlights : 1;
  $avgEconomyPrice = $totalFlights ? array_sum(array_column($flights, 'economy_price')) / $totalFlightsNonZero : 0;
  $avgBusinessPrice = $totalFlights ? array_sum(array_column($flights, 'business_price')) / $totalFlightsNonZero : 0;
  $avgFirstClassPrice = $totalFlights ? array_sum(array_column($flights, 'first_class_price')) / $totalFlightsNonZero : 0;
  $avgDistance = $totalFlights ? $totalDistance / $totalFlightsNonZero : 0;
}

// Total revenue potential
$totalRevenuePotential = $totalEconomyRevenue + $totalBusinessRevenue + $totalFirstClassRevenue;

// Total seats
$totalSeats = $totalEconomySeats + $totalBusinessSeats + $totalFirstClassSeats;

// Handle success/error messages
$message = '';
$messageType = '';

if (isset($_SESSION['success'])) {
  $message = $_SESSION['success'];
  $messageType = 'success';
  unset($_SESSION['success']);
} elseif (isset($_SESSION['error'])) {
  $message = $_SESSION['error'];
  $messageType = 'error';
  unset($_SESSION['error']);
}

// Apply filters
$filteredFlights = $flights;

if (!empty($search)) {
  $filteredFlights = array_filter($flights, function ($flight) use ($search) {
    $search = strtolower($search);
    return (
      stripos($flight['airline_name'], $search) !== false ||
      stripos($flight['flight_number'], $search) !== false ||
      stripos($flight['departure_city'], $search) !== false ||
      stripos($flight['arrival_city'], $search) !== false
    );
  });
}

if (!empty($filter)) {
  switch ($filter) {
    case 'one-way':
      $filteredFlights = array_filter($filteredFlights, function ($flight) {
        return $flight['has_return'] == 0;
      });
      break;
    case 'round-trip':
      $filteredFlights = array_filter($filteredFlights, function ($flight) {
        return $flight['has_return'] == 1;
      });
      break;
    case 'direct':
      $filteredFlights = array_filter($filteredFlights, function ($flight) {
        return $flight['has_stops'] == 0;
      });
      break;
    case 'with-stops':
      $filteredFlights = array_filter($filteredFlights, function ($flight) {
        return $flight['has_stops'] == 1;
      });
      break;
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>View Flights | UmrahFlights Admin</title>
  <!-- Tailwind CSS -->
  <link rel="stylesheet" href="../src/output.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <!-- Custom styling -->
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

    body {
      font-family: 'Inter', sans-serif;
    }

    .flight-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
    }

    .tooltip {
      position: relative;
      display: inline-block;
    }

    .tooltip .tooltip-text {
      visibility: hidden;
      width: 120px;
      background-color: #333;
      color: #fff;
      text-align: center;
      border-radius: 6px;
      padding: 5px;
      position: absolute;
      z-index: 1;
      bottom: 125%;
      left: 50%;
      margin-left: -60px;
      opacity: 0;
      transition: opacity 0.3s;
    }

    .tooltip:hover .tooltip-text {
      visibility: visible;
      opacity: 1;
    }
  </style>
</head>

<body class="bg-gray-100">
  <?php include 'includes/sidebar.php'; ?>

  <!-- Main Content -->
  <div class="ml-0 md:ml-64 mt-10 px-4 sm:px-6 lg:px-8 transition-all duration-300">
    <!-- Top Navbar -->
    <nav class="bg-white shadow-lg rounded-lg p-5 mb-6">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <button id="sidebarToggle" class="text-gray-500 hover:text-gray-700 focus:outline-none md:hidden">
            <i class="fas fa-bars text-xl"></i>
          </button>
          <h4 class="text-lg font-semibold text-gray-800">
            <i class="fas fa-plane-departure text-indigo-600 mr-2"></i> Flight Management
          </h4>
        </div>

        <div>
          <a href="add-flight.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            <i class="fas fa-plus mr-2"></i> Add New Flight
          </a>
        </div>
      </div>
    </nav>

    <!-- Stats section -->
    <?php include 'includes/sums-flight.php'; ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
      <div class="bg-white shadow-lg rounded-lg p-6 border-l-4 border-indigo-500">
        <div class="flex justify-between items-center">
          <div>
            <h3 class="text-2xl font-bold text-gray-800"><?php echo $totalFlights; ?></h3>
            <p class="text-sm text-gray-500">Total Flights</p>
          </div>
          <div class="flex items-center justify-center w-12 h-12 rounded-full bg-indigo-100 text-indigo-500">
            <i class="fas fa-plane text-xl"></i>
          </div>
        </div>
      </div>

      <div class="bg-white shadow-lg rounded-lg p-6 border-l-4 border-green-500">
        <div class="flex justify-between items-center">
          <div>
            <h3 class="text-2xl font-bold text-gray-800">
              <?php
              echo count(array_filter($flights, function ($flight) {
                return $flight['has_return'] == 0;
              }));
              ?>
            </h3>
            <p class="text-sm text-gray-500">One-way Flights</p>
          </div>
          <div class="flex items-center justify-center w-12 h-12 rounded-full bg-green-100 text-green-500">
            <i class="fas fa-arrow-right text-xl"></i>
          </div>
        </div>
      </div>

      <div class="bg-white shadow-lg rounded-lg p-6 border-l-4 border-purple-500">
        <div class="flex justify-between items-center">
          <div>
            <h3 class="text-2xl font-bold text-gray-800">
              <?php
              echo count(array_filter($flights, function ($flight) {
                return $flight['has_return'] == 1;
              }));
              ?>
            </h3>
            <p class="text-sm text-gray-500">Round Trip Flights</p>
          </div>
          <div class="flex items-center justify-center w-12 h-12 rounded-full bg-purple-100 text-purple-500">
            <i class="fas fa-exchange-alt text-xl"></i>
          </div>
        </div>
      </div>

      <div class="bg-white shadow-lg rounded-lg p-6 border-l-4 border-yellow-500">
        <div class="flex justify-between items-center">
          <div>
            <h3 class="text-2xl font-bold text-gray-800">
              <?php
              echo count(array_filter($flights, function ($flight) {
                return $flight['has_stops'] == 0;
              }));
              ?>
            </h3>
            <p class="text-sm text-gray-500">Direct Flights</p>
          </div>
          <div class="flex items-center justify-center w-12 h-12 rounded-full bg-yellow-100 text-yellow-500">
            <i class="fas fa-paper-plane text-xl"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Notification Message -->
    <?php if (!empty($message)): ?>
      <div class="mb-6">
        <div class="<?php echo $messageType === 'success' ? 'bg-green-100 border-l-4 border-green-500 text-green-700' : 'bg-red-100 border-l-4 border-red-500 text-red-700'; ?> p-4 rounded shadow" role="alert">
          <div class="flex">
            <div class="py-1">
              <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle text-green-500' : 'fa-exclamation-circle text-red-500'; ?> mr-2"></i>
            </div>
            <div><?php echo $message; ?></div>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- Filter and Search Section -->
    <div class="bg-white shadow-lg rounded-lg p-6 mb-6">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <!-- Search Bar -->
        <form action="" method="GET" class="flex-1">
          <div class="relative">
            <input type="text" name="search" placeholder="Search by airline, flight number, or city"
              value="<?php echo htmlspecialchars($search); ?>"
              class="w-full pl-10 pr-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
              </svg>
            </div>
            <button type="submit" class="absolute inset-y-0 right-0 px-4 flex items-center bg-indigo-600 hover:bg-indigo-700 text-white rounded-r-lg transition duration-300">
              Search
            </button>
          </div>
        </form>

        <!-- Filter Buttons -->
        <div class="flex flex-wrap gap-2 md:gap-3">
          <a href="?filter=" class="<?php echo empty($filter) ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?> px-4 py-2 rounded-lg text-sm font-medium transition duration-300">
            All Flights
          </a>
          <a href="?filter=one-way" class="<?php echo $filter === 'one-way' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?> px-4 py-2 rounded-lg text-sm font-medium transition duration-300 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
            One-way
          </a>
          <a href="?filter=round-trip" class="<?php echo $filter === 'round-trip' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?> px-4 py-2 rounded-lg text-sm font-medium transition duration-300 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10.293 15.707a1 1 0 010-1.414L12.586 12H5a1 1 0 110-2h7.586l-2.293-2.293a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
              <path fill-rule="evenodd" d="M9.707 4.293a1 1 0 010 1.414L7.414 8H15a1 1 0 110 2H7.414l2.293 2.293a1 1 0 11-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
            Round Trip
          </a>
          <a href="?filter=direct" class="<?php echo $filter === 'direct' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?> px-4 py-2 rounded-lg text-sm font-medium transition duration-300 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
              <path d="M17.92 8.18L11 10.59V6.5a2 2 0 00-2-2 2 2 0 00-2 2v10a2 2 0 002 2 2 2 0 001.45-.64l7.24-7a2 2 0 00-.77-3.68z" />
            </svg>
            Direct
          </a>
          <a href="?filter=with-stops" class="<?php echo $filter === 'with-stops' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?> px-4 py-2 rounded-lg text-sm font-medium transition duration-300 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
              <path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" />
            </svg>
            With Stops
          </a>
        </div>
      </div>
    </div>

    <!-- Flights List -->
    <div class="grid grid-cols-1 xl:grid-cols-1 gap-6 mb-6">
      <?php if (empty($filteredFlights)): ?>
        <div class="col-span-1 xl:col-span-2 bg-white rounded-lg shadow-sm p-10 text-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-400 mb-4" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
          </svg>
          <h3 class="text-xl font-semibold text-gray-800 mb-2">No flights found</h3>
          <p class="text-gray-600 mb-6">Try adjusting your search or filter criteria</p>
          <a href="view-flights.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
            </svg>
            Reset Filters
          </a>
        </div>
      <?php else: ?>
        <?php foreach ($filteredFlights as $flight): ?>
          <div class="flight-card bg-white rounded-lg shadow-sm hover:shadow-lg border border-gray-200 overflow-hidden transition-all duration-300">
            <!-- Card Header -->
            <div class="p-4 <?php echo $flight['has_return'] ? 'bg-gradient-to-r from-indigo-50 to-purple-50 border-b border-purple-100' : 'bg-gradient-to-r from-indigo-50 to-cyan-50 border-b border-indigo-100'; ?>">
              <div class="flex justify-between items-center">
                <div class="flex items-center">
                  <div class="rounded-full <?php echo $flight['has_return'] ? 'bg-purple-100 text-purple-600' : 'bg-indigo-100 text-indigo-600'; ?> p-2 mr-3">
                    <?php if ($flight['has_return']): ?>
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10.293 15.707a1 1 0 010-1.414L12.586 12H5a1 1 0 110-2h7.586l-2.293-2.293a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        <path fill-rule="evenodd" d="M9.707 4.293a1 1 0 010 1.414L7.414 8H15a1 1 0 110 2H7.414l2.293 2.293a1 1 0 11-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                      </svg>
                    <?php else: ?>
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                      </svg>
                    <?php endif; ?>
                  </div>
                  <div>
                    <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($flight['flight_number']); ?></h3>
                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($flight['airline_name']); ?></p>
                  </div>
                </div>
                <div class="flex items-center space-x-2">
                  <div class="flex items-center">
                    <span class="px-2 py-1 rounded-lg text-xs font-medium <?php echo $flight['has_return'] ? 'bg-purple-100 text-purple-800' : 'bg-indigo-100 text-indigo-800'; ?>">
                      <?php echo $flight['has_return'] ? 'Round Trip' : 'One Way'; ?>
                    </span>
                  </div>
                  <div class="flex items-center">
                    <span class="px-2 py-1 rounded-lg text-xs font-medium <?php echo $flight['has_stops'] ? 'bg-amber-100 text-amber-800' : 'bg-green-100 text-green-800'; ?>">
                      <?php echo $flight['has_stops'] ? 'With Stops' : 'Direct'; ?>
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Card Body -->
            <div class="p-4">
              <!-- Flight Route -->
              <div class="mb-4 relative">
                <div class="flex justify-between items-center">
                  <div class="text-center">
                    <span class="block text-sm text-gray-500"><?php echo date('h:i A', strtotime($flight['departure_time'])); ?></span>
                    <span class="block text-lg font-bold"><?php echo htmlspecialchars($flight['departure_city']); ?></span>
                    <span class="block text-xs text-gray-500"><?php echo date('M d, Y', strtotime($flight['departure_date'])); ?></span>
                  </div>

                  <div class="flex-1 mx-4 relative">
                    <div class="h-0.5 bg-gray-300 absolute w-full top-1/2 transform -translate-y-1/2"></div>
                    <?php if ($flight['has_stops']): ?>
                      <?php
                      $stops = json_decode($flight['stops'], true);
                      $stopsCount = count($stops);
                      ?>
                      <div class="flex justify-center items-center">
                        <div class="bg-amber-100 text-amber-800 text-xs rounded-full px-2 py-1 font-medium flex items-center">
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd" />
                          </svg>
                          <?php echo $stopsCount; ?> Stop<?php echo $stopsCount > 1 ? 's' : ''; ?>
                        </div>
                      </div>
                    <?php else: ?>
                      <div class="flex justify-center items-center">
                        <div class="bg-green-100 text-green-800 text-xs rounded-full px-2 py-1 font-medium">Direct</div>
                      </div>
                    <?php endif; ?>
                  </div>

                  <div class="text-center">
                    <span class="block text-sm text-gray-500">
                      <?php
                      $arrival_time = strtotime($flight['departure_time']) + ($flight['flight_duration'] * 3600);
                      echo date('h:i A', $arrival_time);
                      ?>
                    </span>
                    <span class="block text-lg font-bold"><?php echo htmlspecialchars($flight['arrival_city']); ?></span>
                    <span class="block text-xs text-gray-500">
                      <?php
                      // Check if flight crosses into next day
                      $departureDay = date('Y-m-d', strtotime($flight['departure_date']));
                      $arrivalDay = date('Y-m-d', strtotime($flight['departure_date']) + ($flight['flight_duration'] * 3600));
                      echo date('M d, Y', strtotime($arrivalDay));
                      if ($departureDay !== $arrivalDay) {
                        echo ' <span class="text-xs text-indigo-600">(+' . round((strtotime($arrivalDay) - strtotime($departureDay)) / 86400) . ')</span>';
                      }
                      ?>
                    </span>
                  </div>
                </div>

                <?php if ($flight['has_stops'] && !empty($stops)): ?>
                  <div class="mt-3 text-sm text-gray-600">
                    <div class="flex items-center mb-1">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-amber-500" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" />
                      </svg>
                      <span>Stops:</span>
                    </div>
                    <div class="ml-5 flex flex-wrap gap-2">
                      <?php foreach ($stops as $stop): ?>
                        <span class="inline-flex items-center px-2 py-1 bg-gray-100 rounded-md">
                          <?php echo htmlspecialchars($stop['city']); ?> (<?php echo $stop['duration']; ?>h)
                        </span>
                      <?php endforeach; ?>
                    </div>
                  </div>
                <?php endif; ?>
              </div>

              <!-- Return Flight (if applicable) -->
              <?php if ($flight['has_return']): ?>
                <div class="mt-4 pt-4 border-t border-dashed border-gray-300">
                  <div class="text-sm text-purple-600 font-medium mb-3 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M10.293 15.707a1 1 0 010-1.414L12.586 12H5a1 1 0 110-2h7.586l-2.293-2.293a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                      <path fill-rule="evenodd" d="M9.707 4.293a1 1 0 010 1.414L7.414 8H15a1 1 0 110 2H7.414l2.293 2.293a1 1 0 11-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    Return Flight: <?php echo htmlspecialchars($flight['return_flight_number']); ?> (<?php echo htmlspecialchars($flight['return_airline']); ?>)
                  </div>

                  <div class="flex justify-between items-center">
                    <div class="text-center">
                      <span class="block text-sm text-gray-500"><?php echo date('h:i A', strtotime($flight['return_time'])); ?></span>
                      <span class="block text-lg font-bold"><?php echo htmlspecialchars($flight['arrival_city']); ?></span>
                      <span class="block text-xs text-gray-500"><?php echo date('M d, Y', strtotime($flight['return_date'])); ?></span>
                    </div>

                    <div class="flex-1 mx-4 relative">
                      <div class="h-0.5 bg-gray-300 absolute w-full top-1/2 transform -translate-y-1/2"></div>
                      <?php if ($flight['has_return_stops']): ?>
                        <?php
                        $return_stops = json_decode($flight['return_stops'], true);
                        $returnStopsCount = count($return_stops);
                        ?>
                        <div class="flex justify-center items-center">
                          <div class="bg-amber-100 text-amber-800 text-xs rounded-full px-2 py-1 font-medium flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd" />
                            </svg>
                            <?php echo $returnStopsCount; ?> Stop<?php echo $returnStopsCount > 1 ? 's' : ''; ?>
                          </div>
                        </div>
                      <?php else: ?>
                        <div class="flex justify-center items-center">
                          <div class="bg-green-100 text-green-800 text-xs rounded-full px-2 py-1 font-medium">Direct</div>
                        </div>
                      <?php endif; ?>
                    </div>

                    <div class="text-center">
                      <span class="block text-sm text-gray-500">
                        <?php
                        $return_arrival_time = strtotime($flight['return_time']) + ($flight['return_flight_duration'] * 3600);
                        echo date('h:i A', $return_arrival_time);
                        ?>
                      </span>
                      <span class="block text-lg font-bold"><?php echo htmlspecialchars($flight['departure_city']); ?></span>
                      <span class="block text-xs text-gray-500">
                        <?php
                        // Check if return flight crosses into next day
                        $returnDepartureDay = date('Y-m-d', strtotime($flight['return_date']));
                        $returnArrivalDay = date('Y-m-d', strtotime($flight['return_date']) + ($flight['return_flight_duration'] * 3600));
                        echo date('M d, Y', strtotime($returnArrivalDay));
                        if ($returnDepartureDay !== $returnArrivalDay) {
                          echo ' <span class="text-xs text-indigo-600">(+' . round((strtotime($returnArrivalDay) - strtotime($returnDepartureDay)) / 86400) . ')</span>';
                        }
                        ?>
                      </span>
                    </div>
                  </div>

                  <?php if ($flight['has_return_stops'] && !empty($return_stops)): ?>
                    <div class="mt-3 text-sm text-gray-600">
                      <div class="flex items-center mb-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-amber-500" viewBox="0 0 20 20" fill="currentColor">
                          <path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" />
                        </svg>
                        <span>Return Stops:</span>
                      </div>
                      <div class="ml-5">
                        <?php foreach ($return_stops as $stop): ?>
                          <span class="inline-flex items-center px-2 py-1 bg-gray-100 rounded-md mr-2 mb-1">
                            <?php echo htmlspecialchars($stop['city']); ?> (<?php echo $stop['duration']; ?>h)
                          </span>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  <?php endif; ?>
                </div>
              <?php endif; ?>

              <!-- Flight Info & Action Buttons -->
              <div class="mt-4 grid grid-cols-3 gap-4">
                <div class="col-span-2">
                  <!-- Pricing -->
                  <!--<div class="grid grid-cols-3 gap-2">-->
                  <!--  <div class="px-3 py-2 bg-gray-50 rounded-lg border border-gray-200 flex flex-col items-center">-->
                  <!--    <span class="text-xs text-gray-500">Economy</span>-->
                  <!--    <span class="font-bold text-gray-800">Rs.<?php echo number_format($flight['economy_price']); ?></span>-->
                  <!--    <span class="text-xs text-gray-500"><?php echo $flight['economy_seats']; ?> seats</span>-->
                  <!--  </div>-->
                  <!--  <div class="px-3 py-2 bg-gray-50 rounded-lg border border-gray-200 flex flex-col items-center">-->
                  <!--    <span class="text-xs text-gray-500">Business</span>-->
                  <!--    <span class="font-bold text-gray-800">Rs.<?php echo number_format($flight['business_price']); ?></span>-->
                  <!--    <span class="text-xs text-gray-500"><?php echo $flight['business_seats']; ?> seats</span>-->
                  <!--  </div>-->
                  <!--  <div class="px-3 py-2 bg-gray-50 rounded-lg border border-gray-200 flex flex-col items-center">-->
                  <!--    <span class="text-xs text-gray-500">First Class</span>-->
                  <!--    <span class="font-bold text-gray-800">Rs.<?php echo number_format($flight['first_class_price']); ?></span>-->
                  <!--    <span class="text-xs text-gray-500"><?php echo $flight['first_class_seats']; ?> seats</span>-->
                  <!--  </div>-->
                  <!--</div>-->
                  <!-- Pricing -->
<div class="grid grid-cols-2 gap-2">
  <div class="px-3 py-2 bg-gray-50 rounded-lg border border-gray-200 flex flex-col items-center">
    <span class="text-xs text-gray-500">Economy</span>
    <span class="font-bold text-gray-800">Rs.<?php echo formatNumber($flight['economy_price']); ?></span>
    <span class="text-xs text-gray-500"><?php echo $flight['economy_seats']; ?> seats</span>
  </div>
  <div class="px-3 py-2 bg-gray-50 rounded-lg border border-gray-200 flex flex-col items-center">
    <span class="text-xs text-gray-500">Business</span>
    <span class="font-bold text-gray-800">Rs.<?php echo formatNumber($flight['business_price']); ?></span>
    <span class="text-xs text-gray-500"><?php echo $flight['business_seats']; ?> seats</span>
  </div>
  <div class="px-3 py-2 bg-gray-50 rounded-lg border border-gray-200 flex flex-col items-center">
    <span class="text-xs text-gray-500">First Class</span>
    <span class="font-bold text-gray-800">Rs.<?php echo formatNumber($flight['first_class_price']); ?></span>
    <span class="text-xs text-gray-500"><?php echo $flight['first_class_seats']; ?> seats</span>
  </div>
</div>

                  <!-- Duration & Distance -->
                  <div class="mt-3 flex items-center gap-4 text-sm text-gray-600">
                    <div class="flex items-center">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                      </svg>
                      <span>Duration: <?php echo $flight['flight_duration']; ?> hours</span>
                    </div>
                    <div class="flex items-center">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                      </svg>
                      <span>Distance: <?php echo number_format($flight['distance']); ?> km</span>
                    </div>
                  </div>
                </div>

                <div class="col-span-1 flex flex-col gap-2 justify-center">
                  <a href="edit-flight.php?id=<?php echo $flight['id']; ?>" class="w-full flex items-center justify-center px-3 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                      <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                    </svg>
                    Edit
                  </a>
                  <button type="button" onclick="confirmDelete(<?php echo $flight['id']; ?>, '<?php echo htmlspecialchars($flight['flight_number']); ?>')" class="w-full flex items-center justify-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-red-600 bg-red-100 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    Delete
                  </button>
                </div>
              </div>

              <!-- Flight Notes (if any) -->
              <?php if (!empty($flight['flight_notes'])): ?>
                <div class="mt-4 pt-4 border-t border-dashed border-gray-300">
                  <div class="flex items-start text-sm text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-gray-500 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2h2a1 1 0 100-2H9z" clip-rule="evenodd" />
                    </svg>
                    <div>
                      <strong class="text-gray-700">Notes:</strong>
                      <p><?php echo nl2br(htmlspecialchars($flight['flight_notes'])); ?></p>
                    </div>
                  </div>
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Delete Confirmation Modal -->
  <div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-lg shadow-lg p-6 max-w-md w-full mx-4">
      <div class="text-center mb-6">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-red-500 mx-auto mb-4" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
        </svg>
        <h3 class="text-xl font-bold text-gray-900 mb-2">Confirm Deletion</h3>
        <p class="text-gray-600">Are you sure you want to delete flight <span id="deleteFlightNumber" class="font-semibold"></span>? This action cannot be undone.</p>
      </div>
      <div class="flex justify-center gap-4">
        <button type="button" id="cancelDelete" class="px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
          Cancel
        </button>
        <a href="#" id="confirmDeleteBtn" class="px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
          Yes, Delete
        </a>
      </div>
    </div>
  </div>

  <!-- Sidebar Toggle Script (assuming sidebar.php has the necessary elements) -->
  <script>
    // User Dropdown Toggle
    const userDropdownButton = document.getElementById('userDropdownButton');
    const userDropdownMenu = document.getElementById('userDropdownMenu');

    if (userDropdownButton && userDropdownMenu) {
      userDropdownButton.addEventListener('click', function() {
        userDropdownMenu.classList.toggle('hidden');
      });

      // Close dropdown when clicking outside
      document.addEventListener('click', function(event) {
        if (!userDropdownButton.contains(event.target) && !userDropdownMenu.contains(event.target)) {
          userDropdownMenu.classList.add('hidden');
        }
      });
    }

    // Sidebar Toggle (assuming sidebar toggle functionality from sidebar.php)
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebar-overlay');

    if (sidebarToggle && sidebar && sidebarOverlay) {
      sidebarToggle.addEventListener('click', function() {
        sidebar.classList.remove('-translate-x-full');
        sidebarOverlay.classList.remove('hidden');
      });
    }

    // Delete confirmation function
    function confirmDelete(flightId, flightNumber) {
      const modal = document.getElementById('deleteModal');
      const confirmBtn = document.getElementById('confirmDeleteBtn');
      const flightNumberSpan = document.getElementById('deleteFlightNumber');

      flightNumberSpan.textContent = flightNumber;
      confirmBtn.href = 'delete-flight.php?id=' + flightId;
      modal.classList.remove('hidden');
    }

    // Close modal when clicking cancel
    document.getElementById('cancelDelete').addEventListener('click', function() {
      document.getElementById('deleteModal').classList.add('hidden');
    });

    // Close modal when clicking outside
    document.getElementById('deleteModal').addEventListener('click', function(e) {
      if (e.target === this) {
        this.classList.add('hidden');
      }
    });
  </script>
  <script>
    /**
     * Fixed Delete Flight Button Functionality
     * 
     * The issue is that the confirmDelete function might not be defined in the global scope
     * or the modal elements might not be properly accessed.
     */

    // Fix for Delete Flight Modal
    document.addEventListener('DOMContentLoaded', function() {
      // Explicitly define the confirmDelete function in the global scope
      window.confirmDelete = function(flightId, flightNumber) {
        console.log("Deleting flight:", flightId, flightNumber); // Debug log

        const modal = document.getElementById('deleteModal');
        const confirmBtn = document.getElementById('confirmDeleteBtn');
        const flightNumberSpan = document.getElementById('deleteFlightNumber');

        if (!modal || !confirmBtn || !flightNumberSpan) {
          console.error("Missing modal elements:", {
            modal: !!modal,
            confirmBtn: !!confirmBtn,
            flightNumberSpan: !!flightNumberSpan
          });
          alert("System error: Could not open delete confirmation dialog");
          return;
        }

        // Set the flight number in the modal
        flightNumberSpan.textContent = flightNumber;

        // Set the href for the confirmation button
        confirmBtn.href = 'delete-flight.php?id=' + flightId;

        // Show the modal by removing the hidden class
        modal.classList.remove('hidden');

        // Prevent body scrolling
        document.body.style.overflow = 'hidden';
      };

      // Cancel delete button
      const cancelDeleteBtn = document.getElementById('cancelDelete');
      if (cancelDeleteBtn) {
        cancelDeleteBtn.addEventListener('click', function() {
          const modal = document.getElementById('deleteModal');
          if (modal) {
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
          }
        });
      }

      // Close modal when clicking outside
      const deleteModal = document.getElementById('deleteModal');
      if (deleteModal) {
        deleteModal.addEventListener('click', function(e) {
          if (e.target === this) {
            this.classList.add('hidden');
            document.body.style.overflow = 'auto';
          }
        });
      }

      // Make sure the confirm button also closes the modal after clicking
      const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
      if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', function() {
          // The link will navigate away, but we'll close the modal anyway
          // in case something prevents navigation
          const modal = document.getElementById('deleteModal');
          if (modal) {
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
          }
        });
      }
    });
  </script>
</body>

</html>