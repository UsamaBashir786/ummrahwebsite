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
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="assets/css/add-flight.css">
  <link rel="stylesheet" href="assets/css/sidebar.css">
</head>

<body>
  <?php include 'includes/sidebar.php'; ?>
  <div class="container-fluid">
    <div class="row">
      <!-- Main Content -->
      <div class="col-md-"></div>
      <main class="col-md-12 px-0">
        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
          <div class="container-fluid">
            <h1 class="navbar-brand mb-0 d-flex align-items-center">
              <i class="text-primary fas fa-plane me-2"></i> Add New Flight
            </h1>
            <div class="d-flex align-items-center">
              <button onclick="history.back()" class="btn btn-outline-secondary btn-sm me-2">
                <i class="fas fa-arrow-left me-1"></i> Back
              </button>
            </div>
          </div>
        </nav>

        <!-- Form Container -->
        <div class="container py-4">
          <div class="card shadow-sm">
            <div class="card-body p-4">
              <div class="mb-4">
                <h2 class="card-title text-primary">
                  <i class="fas fa-plane-departure me-2"></i>Add New Flight
                </h2>
                <p class="text-muted">Enter flight details for Umrah journey</p>
              </div>

              <form action="" method="POST" id="flightForm">
                <!-- Outbound Flight Section Title -->
                <div class="section-heading mb-4">
                  <h3 class="text-primary">
                    <i class="fas fa-plane-departure me-2"></i>Outbound Flight Details
                  </h3>
                </div>

                <!-- Airline & Flight Number -->
                <div class="row mb-4">
                  <div class="col-md-6 mb-3 mb-md-0">
                    <label for="airline_name" class="form-label">Airline Name <span class="text-danger">*</span></label>
                    <select name="airline_name" id="airline_name" class="form-select" required>
                      <option value="">Select Airline</option>
                      <!-- Pakistani Airlines -->
                      <optgroup label="Pakistani Airlines">
                        <option value="PIA">Pakistan International Airlines (PIA)</option>
                        <option value="AirBlue">AirBlue</option>
                        <option value="SereneAir">Serene Air</option>
                        <option value="AirSial">AirSial</option>
                        <option value="FlyJinnah">Fly Jinnah</option>
                      </optgroup>
                      <!-- Middle Eastern Airlines -->
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
                      <!-- Asian Airlines -->
                      <optgroup label="Asian Airlines">
                        <option value="Thai">Thai Airways</option>
                        <option value="Malaysia">Malaysia Airlines</option>
                        <option value="Singapore">Singapore Airlines</option>
                        <option value="Cathay">Cathay Pacific</option>
                        <option value="ChinaSouthern">China Southern</option>
                        <option value="Turkish">Turkish Airlines</option>
                      </optgroup>
                      <!-- European & American Airlines -->
                      <optgroup label="European & American Airlines">
                        <option value="British">British Airways</option>
                        <option value="Lufthansa">Lufthansa</option>
                        <option value="AirFrance">Air France</option>
                        <option value="KLM">KLM Royal Dutch Airlines</option>
                        <option value="Virgin">Virgin Atlantic</option>
                      </optgroup>
                      <!-- Budget Airlines -->
                      <optgroup label="Budget Airlines">
                        <option value="AirArabia">Air Arabia</option>
                        <option value="Indigo">IndiGo</option>
                        <option value="SpiceJet">SpiceJet</option>
                      </optgroup>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label for="flight_number" class="form-label">Flight Number <span class="text-danger">*</span></label>
                    <input type="text" name="flight_number" id="flight_number" class="form-control" placeholder="e.g., PK-309" required maxlength="9">
                  </div>
                </div>

                <!-- Route Information -->
                <div class="row mb-4">
                  <div class="col-md-6 mb-3 mb-md-0">
                    <label for="departure_city" class="form-label">Departure City <span class="text-danger">*</span></label>
                    <select name="departure_city" id="departure_city" class="form-select" required>
                      <option value="">Select City</option>
                      <!-- Major Cities -->
                      <option value="Karachi">Karachi</option>
                      <option value="Lahore">Lahore</option>
                      <option value="Islamabad">Islamabad</option>
                      <option value="Rawalpindi">Rawalpindi</option>
                      <option value="Faisalabad">Faisalabad</option>
                      <option value="Multan">Multan</option>
                      <option value="Hyderabad">Hyderabad</option>
                      <option value="Peshawar">Peshawar</option>
                      <option value="Quetta">Quetta</option>
                      <!-- Punjab Cities -->
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
                      <!-- Sindh Cities -->
                      <optgroup label="Sindh">
                        <option value="Sukkur">Sukkur</option>
                        <option value="Larkana">Larkana</option>
                        <option value="Nawabshah">Nawabshah</option>
                        <option value="Mirpur Khas">Mirpur Khas</option>
                        <option value="Thatta">Thatta</option>
                        <option value="Jacobabad">Jacobabad</option>
                      </optgroup>
                      <!-- KPK Cities -->
                      <optgroup label="Khyber Pakhtunkhwa">
                        <option value="Mardan">Mardan</option>
                        <option value="Abbottabad">Abbottabad</option>
                        <option value="Swat">Swat</option>
                        <option value="Nowshera">Nowshera</option>
                        <option value="Charsadda">Charsadda</option>
                        <option value="Mansehra">Mansehra</option>
                      </optgroup>
                      <!-- Balochistan Cities -->
                      <optgroup label="Balochistan">
                        <option value="Gwadar">Gwadar</option>
                        <option value="Khuzdar">Khuzdar</option>
                        <option value="Chaman">Chaman</option>
                        <option value="Zhob">Zhob</option>
                      </optgroup>
                      <!-- AJK & Gilgit-Baltistan -->
                      <optgroup label="Azad Kashmir & Gilgit-Baltistan">
                        <option value="Muzaffarabad">Muzaffarabad</option>
                        <option value="Mirpur">Mirpur</option>
                        <option value="Gilgit">Gilgit</option>
                        <option value="Skardu">Skardu</option>
                      </optgroup>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label for="arrival_city" class="form-label">Arrival City <span class="text-danger">*</span></label>
                    <select name="arrival_city" id="arrival_city" class="form-select" required>
                      <option value="">Select City</option>
                      <option value="Jeddah">Jeddah</option>
                      <option value="Medina">Medina</option>
                    </select>
                  </div>
                </div>

                <!-- Flight Stops -->
                <div class="card mb-4 bg-light">
                  <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                      <h4 class="mb-0">Flight Stops</h4>
                      <div class="ms-4">
                        <div class="form-check form-check-inline">
                          <input class="form-check-input" type="radio" name="has_stops" id="directFlight" value="0" checked>
                          <label class="form-check-label" for="directFlight">Direct Flight</label>
                        </div>
                        <div class="form-check form-check-inline">
                          <input class="form-check-input" type="radio" name="has_stops" id="hasStops" value="1">
                          <label class="form-check-label" for="hasStops">Has Stops</label>
                        </div>
                      </div>
                    </div>

                    <div id="stops-container" class="d-none">
                      <div class="stop-row row mb-3">
                        <div class="col-md-6 mb-3 mb-md-0">
                          <label class="form-label">Stop City</label>
                          <input type="text" name="stop_city[]" class="form-control" maxlength="12" placeholder="e.g., Dubai">
                        </div>
                        <div class="col-md-6">
                          <label class="form-label">Stop Duration (hours)</label>
                          <input type="text" name="stop_duration[]" class="form-control" placeholder="e.g., 4">
                        </div>
                      </div>
                      <div class="text-end">
                        <button type="button" id="add-stop" class="btn btn-primary">
                          <i class="fas fa-plus me-2"></i>Add Another Stop
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Schedule and Duration -->
                <div class="row mb-4">
                  <div class="col-md-4 mb-3 mb-md-0">
                    <label for="departure_date" class="form-label">Departure Date <span class="text-danger">*</span></label>
                    <input type="date" name="departure_date" id="departure_date" class="form-control" required>
                  </div>
                  <div class="col-md-4 mb-3 mb-md-0">
                    <label for="departure_time" class="form-label">Departure Time <span class="text-danger">*</span></label>
                    <input type="text" name="departure_time" id="departure_time" class="form-control" placeholder="HH:MM (24-hour format)" required>
                  </div>
                  <div class="col-md-4">
                    <label for="flight_duration" class="form-label">Flight Duration (hours) <span class="text-danger">*</span></label>
                    <input type="number" name="flight_duration" id="flight_duration" class="form-control" placeholder="e.g., 5.5" step="0.1" required>
                  </div>
                </div>

                <!-- Distance Field -->
                <div class="mb-4">
                  <label for="distance" class="form-label">Distance (km) <span class="text-danger">*</span></label>
                  <input type="number" name="distance" id="distance" class="form-control" placeholder="e.g., 3500" step="1" required>
                </div>

                <!-- Return Flight Section -->
                <div class="border-top pt-4 mt-4">
                  <div class="mb-3">
                    <h3 class="text-primary">
                      <i class="fas fa-plane-arrival me-2"></i>Return Flight Details
                    </h3>
                  </div>

                  <div class="d-flex align-items-center mb-3">
                    <header>
                      <h4 class="mb-0">Journey Type</h4>
                    </header>
                    <div class="ms-4">
                      <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="has_return" id="oneWayFlight" value="0" checked>
                        <label class="form-check-label" for="oneWayFlight">One-way Flight</label>
                      </div>
                      <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="has_return" id="roundTrip" value="1">
                        <label class="form-check-label" for="roundTrip">Round Trip</label>
                      </div>
                    </div>
                  </div>

                  <div id="return-container" class="card bg-light mb-4 d-none">
                    <div class="card-body">
                      <div class="row mb-4">
                        <div class="col-md-6 mb-3 mb-md-0">
                          <label for="return_airline" class="form-label">Return Airline</label>
                          <select name="return_airline" id="return_airline" class="form-select">
                            <option value="">Select Airline</option>
                            <option value="same">Same as Outbound</option>
                            <!-- Pakistani Airlines -->
                            <optgroup label="Pakistani Airlines">
                              <option value="PIA">Pakistan International Airlines (PIA)</option>
                              <option value="AirBlue">AirBlue</option>
                              <option value="SereneAir">Serene Air</option>
                              <option value="AirSial">AirSial</option>
                              <option value="FlyJinnah">Fly Jinnah</option>
                            </optgroup>
                            <!-- Middle Eastern Airlines -->
                            <optgroup label="Middle Eastern Airlines">
                              <option value="Emirates">Emirates</option>
                              <option value="Qatar">Qatar Airways</option>
                              <option value="Etihad">Etihad Airways</option>
                              <option value="Saudi">Saudia (Saudi Airlines)</option>
                              <option value="Flynas">Flynas</option>
                              <option value="Flydubai">Flydubai</option>
                              <option value="OmanAir">Oman Air</option>
                            </optgroup>
                            <!-- Asian Airlines -->
                            <optgroup label="Asian Airlines">
                              <option value="Thai">Thai Airways</option>
                              <option value="Singapore">Singapore Airlines</option>
                              <option value="Turkish">Turkish Airlines</option>
                              <option value="Malaysia">Malaysia Airlines</option>
                            </optgroup>
                            <!-- European & American Airlines -->
                            <optgroup label="European & American Airlines">
                              <option value="British">British Airways</option>
                              <option value="Lufthansa">Lufthansa</option>
                              <option value="AirFrance">Air France</option>
                            </optgroup>
                            <!-- Budget Airlines -->
                            <optgroup label="Budget Airlines">
                              <option value="AirArabia">Air Arabia</option>
                              <option value="Indigo">IndiGo</option>
                            </optgroup>
                          </select>
                        </div>
                        <div class="col-md-6">
                          <label for="return_flight_number" class="form-label">Return Flight Number</label>
                          <input type="text" name="return_flight_number" id="return_flight_number" class="form-control" placeholder="e.g., PK-310" maxlength="7">
                        </div>
                      </div>

                      <div class="row mb-4">
                        <div class="col-md-4 mb-3 mb-md-0">
                          <label for="return_date" class="form-label">Return Date</label>
                          <input type="date" name="return_date" id="return_date" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3 mb-md-0">
                          <label for="return_time" class="form-label">Return Time</label>
                          <input type="text" name="return_time" id="return_time" class="form-control" placeholder="HH:MM (24-hour format)">
                        </div>
                        <div class="col-md-4">
                          <label for="return_flight_duration" class="form-label">Return Flight Duration (hours)</label>
                          <input type="text" name="return_flight_duration" id="return_flight_duration" class="form-control" placeholder="e.g., 5.5">
                        </div>
                      </div>

                      <div class="mt-4">
                        <div class="d-flex align-items-center mb-3">
                          <h5 class="mb-0">Return Flight Stops</h5>
                          <div class="ms-4">
                            <div class="form-check form-check-inline">
                              <input class="form-check-input" type="radio" name="has_return_stops" id="directReturnFlight" value="0" checked>
                              <label class="form-check-label" for="directReturnFlight">Direct Return Flight</label>
                            </div>
                            <div class="form-check form-check-inline">
                              <input class="form-check-input" type="radio" name="has_return_stops" id="hasReturnStops" value="1">
                              <label class="form-check-label" for="hasReturnStops">Has Stops</label>
                            </div>
                          </div>
                        </div>

                        <div id="return-stops-container" class="d-none">
                          <div class="return-stop-row row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                              <label class="form-label">Return Stop City</label>
                              <input type="text" name="return_stop_city[]" class="form-control" placeholder="e.g., Dubai" maxlength="12">
                            </div>
                            <div class="col-md-6">
                              <label class="form-label">Return Stop Duration (hours)</label>
                              <input type="text" name="return_stop_duration[]" class="form-control" placeholder="e.g., 2">
                            </div>
                          </div>
                          <div class="text-end">
                            <button type="button" id="add-return-stop" class="btn btn-primary">
                              <i class="fas fa-plus me-2"></i>Add Another Return Stop
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Pricing Section -->
                <div class="border-top pt-4 mt-4">
                  <div class="mb-3">
                    <h3 class="text-primary">
                      <i class="fas fa-tags me-2"></i>Pricing Information
                    </h3>
                  </div>

                  <div class="row mb-4">
                    <div class="col-md-4 mb-3 mb-md-0">
                      <label for="economy_price" class="form-label">Economy Price (PKR) <span class="text-danger">*</span></label>
                      <input type="number" name="economy_price" id="economy_price" class="form-control" placeholder="242,250" required>
                    </div>
                    <div class="col-md-4 mb-3 mb-md-0">
                      <label for="business_price" class="form-label">Business Price (PKR) <span class="text-danger">*</span></label>
                      <input type="number" name="business_price" id="business_price" class="form-control" placeholder="427,500" required>
                    </div>
                    <div class="col-md-4">
                      <label for="first_class_price" class="form-label">First Class Price (PKR) <span class="text-danger">*</span></label>
                      <input type="number" name="first_class_price" id="first_class_price" class="form-control" placeholder="712,500" required>
                    </div>
                  </div>
                </div>

                <!-- Seat Information -->
                <div class="border-top pt-4 mt-4">
                  <div class="mb-3">
                    <h3 class="text-primary">
                      <i class="fas fa-chair me-2"></i>Seat Information
                    </h3>
                  </div>

                  <div class="row mb-4">
                    <div class="col-md-4 mb-3 mb-md-0">
                      <label for="economy_seats" class="form-label">Economy Seats <span class="text-danger">*</span></label>
                      <input type="number" name="economy_seats" id="economy_seats" class="form-control" placeholder="200" required>
                    </div>
                    <div class="col-md-4 mb-3 mb-md-0">
                      <label for="business_seats" class="form-label">Business Seats <span class="text-danger">*</span></label>
                      <input type="number" name="business_seats" id="business_seats" class="form-control" placeholder="30" required>
                    </div>
                    <div class="col-md-4">
                      <label for="first_class_seats" class="form-label">First Class Seats <span class="text-danger">*</span></label>
                      <input type="number" name="first_class_seats" id="first_class_seats" class="form-control" placeholder="10" required>
                    </div>
                  </div>
                </div>

                <!-- Flight Notes -->
                <div class="mb-4">
                  <label for="flight_notes" class="form-label">Flight Notes (Optional)</label>
                  <textarea name="flight_notes" id="flight_notes" class="form-control" rows="3" placeholder="Any additional information about this flight"></textarea>
                </div>

                <!-- Submit Buttons -->
                <div class="d-flex gap-2">
                  <button type="submit" id="submit-btn" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i> Save Flight
                  </button>
                  <button type="reset" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i>Reset
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>

  <!-- Bootstrap Bundle with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Simple script for toggling sections and adding stops -->
  <script>
    // Toggle stops section
    function toggleStopsSection(show) {
      document.getElementById('stops-container').classList.toggle('d-none', !show);
    }

    // Toggle return section
    function toggleReturnSection(show) {
      document.getElementById('return-container').classList.toggle('d-none', !show);
    }

    // Toggle return stops section
    function toggleReturnStopsSection(show) {
      document.getElementById('return-stops-container').classList.toggle('d-none', !show);
    }

    // Add stop row
    document.getElementById('add-stop').addEventListener('click', function() {
      const stopRow = document.querySelector('.stop-row').cloneNode(true);
      stopRow.querySelectorAll('input').forEach(input => input.value = '');
      this.closest('.text-end').before(stopRow);
    });

    // Add return stop row
    document.getElementById('add-return-stop').addEventListener('click', function() {
      const returnStopRow = document.querySelector('.return-stop-row').cloneNode(true);
      returnStopRow.querySelectorAll('input').forEach(input => input.value = '');
      this.closest('.text-end').before(returnStopRow);
    });

    // Bind radio buttons
    document.querySelectorAll('input[name="has_stops"]').forEach(input => {
      input.addEventListener('change', function() {
        toggleStopsSection(this.value == '1');
      });
    });

    document.querySelectorAll('input[name="has_return"]').forEach(input => {
      input.addEventListener('change', function() {
        toggleReturnSection(this.value == '1');
      });
    });

    document.querySelectorAll('input[name="has_return_stops"]').forEach(input => {
      input.addEventListener('change', function() {
        toggleReturnStopsSection(this.value == '1');
      });
    });
  </script>
</body>

</html>