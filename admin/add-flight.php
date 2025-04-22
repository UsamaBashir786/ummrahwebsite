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
$errors = [];
$success = '';
$debug_log = [];

// Function to log debug messages
function log_debug($message)
{
  global $debug_log;
  $debug_log[] = $message;
}

// Process form when submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  try {
    // Sanitize and validate inputs
    $airline_name = mysqli_real_escape_string($conn, trim($_POST['airline_name']));
    $flight_number = mysqli_real_escape_string($conn, trim($_POST['flight_number']));
    $departure_city = mysqli_real_escape_string($conn, trim($_POST['departure_city']));
    $arrival_city = mysqli_real_escape_string($conn, trim($_POST['arrival_city']));
    $departure_date = $_POST['departure_date'];
    $departure_time = $_POST['departure_time'];
    $flight_duration = floatval($_POST['flight_duration']);
    $distance = intval($_POST['distance']);
    $has_return = isset($_POST['has_return']) && $_POST['has_return'] == '1' ? 1 : 0;
    $flight_notes = mysqli_real_escape_string($conn, trim($_POST['flight_notes']));

    // Pricing and seats
    $economy_price = floatval($_POST['economy_price']);
    $business_price = floatval($_POST['business_price']);
    $first_class_price = floatval($_POST['first_class_price']);
    $economy_seats = intval($_POST['economy_seats']);
    $business_seats = intval($_POST['business_seats']);
    $first_class_seats = intval($_POST['first_class_seats']);

    log_debug("Received inputs: airline_name=$airline_name, flight_number=$flight_number, departure_city=$departure_city, arrival_city=$arrival_city");

    // Validate required fields
    if (empty($airline_name)) $errors[] = "Airline name is required";
    if (empty($flight_number) || !preg_match('/^[A-Z0-9-]{2,9}$/', $flight_number))
      $errors[] = "Valid flight number is required (2-9 characters)";
    if (empty($departure_city)) $errors[] = "Departure city is required";
    if (empty($arrival_city)) $errors[] = "Arrival city is required";
    if (empty($departure_date) || !strtotime($departure_date))
      $errors[] = "Valid departure date is required";
    if (empty($departure_time) || !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $departure_time))
      $errors[] = "Valid departure time is required (HH:MM)";
    if ($flight_duration <= 0 || $flight_duration > 8)
      $errors[] = "Flight duration must be between 0 and 8 hours";
    if ($distance <= 0 || $distance > 20000)
      $errors[] = "Distance must be between 0 and 20,000 km";
    if ($economy_price <= 0) $errors[] = "Valid economy price is required";
    if ($business_price <= 0) $errors[] = "Valid business price is required";
    if ($first_class_price <= 0) $errors[] = "Valid first class price is required";
    if ($economy_seats < 100 || $economy_seats > 500)
      $errors[] = "Economy seats must be between 100 and 500";
    if ($business_seats < 10 || $business_seats > 100)
      $errors[] = "Business seats must be between 10 and 100";
    if ($first_class_seats < 5 || $first_class_seats > 50)
      $errors[] = "First class seats must be between 5 and 50";

    // Validate outbound stops if present
    $has_stops = isset($_POST['has_stops']) && $_POST['has_stops'] == '1';
    $stop_cities = isset($_POST['stop_city']) ? $_POST['stop_city'] : [];
    $stop_durations = isset($_POST['stop_duration']) ? $_POST['stop_duration'] : [];

    if ($has_stops && !empty($stop_cities)) {
      foreach ($stop_cities as $index => $stop_city) {
        $stop_city = mysqli_real_escape_string($conn, trim($stop_city));
        $stop_duration = floatval($stop_durations[$index]);

        if (empty($stop_city))
          $errors[] = "Stop city is required for stop #" . ($index + 1);
        if ($stop_duration <= 0 || $stop_duration > 24)
          $errors[] = "Stop duration must be between 0 and 24 hours for stop #" . ($index + 1);
      }
    }

    // Validate return flight if round trip
    $return_airline = $return_flight_number = $return_date = $return_time = $return_flight_duration = null;
    if ($has_return) {
      $return_airline = mysqli_real_escape_string($conn, trim($_POST['return_airline']));
      $return_flight_number = mysqli_real_escape_string($conn, trim($_POST['return_flight_number']));
      $return_date = $_POST['return_date'];
      $return_time = $_POST['return_time'];
      $return_flight_duration = floatval($_POST['return_flight_duration']);

      if ($return_airline === 'same') {
        $return_airline = $airline_name;
      }

      if (empty($return_airline)) $errors[] = "Return airline is required";
      if (empty($return_flight_number) || !preg_match('/^[A-Z0-9-]{2,7}$/', $return_flight_number))
        $errors[] = "Valid return flight number is required (2-7 characters)";
      if (empty($return_date) || !strtotime($return_date))
        $errors[] = "Valid return date is required";
      if (strtotime($return_date) < strtotime($departure_date))
        $errors[] = "Return date must be after departure date";
      if (empty($return_time) || !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $return_time))
        $errors[] = "Valid return time is required (HH:MM)";
      if ($return_flight_duration <= 0 || $return_flight_duration > 8)
        $errors[] = "Return flight duration must be between 0 and 8 hours";

      // Validate return stops if present
      $has_return_stops = isset($_POST['has_return_stops']) && $_POST['has_return_stops'] == '1';
      $return_stop_cities = isset($_POST['return_stop_city']) ? $_POST['return_stop_city'] : [];
      $return_stop_durations = isset($_POST['return_stop_duration']) ? $_POST['return_stop_duration'] : [];

      if ($has_return_stops && !empty($return_stop_cities)) {
        foreach ($return_stop_cities as $index => $stop_city) {
          $stop_city = mysqli_real_escape_string($conn, trim($stop_city));
          $stop_duration = floatval($return_stop_durations[$index]);

          if (empty($stop_city))
            $errors[] = "Return stop city is required for stop #" . ($index + 1);
          if ($stop_duration <= 0 || $stop_duration > 24)
            $errors[] = "Return stop duration must be between 0 and 24 hours for stop #" . ($index + 1);
        }
      }
    }

    // If no errors, insert data
    if (empty($errors)) {
      // Insert main flight details
      $query = "INSERT INTO flights (
                airline_name, flight_number, departure_city, arrival_city, 
                departure_date, departure_time, flight_duration, distance,
                is_round_trip, return_airline, return_flight_number,
                return_date, return_time, return_flight_duration, flight_notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

      $stmt = $conn->prepare($query);
      if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
      }

      $stmt->bind_param(
        'ssssssdisssssds',
        $airline_name,
        $flight_number,
        $departure_city,
        $arrival_city,
        $departure_date,
        $departure_time,
        $flight_duration,
        $distance,
        $has_return,
        $return_airline,
        $return_flight_number,
        $return_date,
        $return_time,
        $return_flight_duration,
        $flight_notes
      );

      if (!$stmt->execute()) {
        throw new Exception("Flight insert failed: " . $stmt->error);
      }

      $flight_id = $conn->insert_id;
      log_debug("Flight inserted with ID: $flight_id");

      // Insert outbound stops
      if ($has_stops && !empty($stop_cities)) {
        $stop_query = "INSERT INTO flight_stops (flight_id, stop_city, stop_duration, is_return_stop) VALUES (?, ?, ?, 0)";
        $stop_stmt = $conn->prepare($stop_query);
        if (!$stop_stmt) {
          throw new Exception("Stop prepare failed: " . $conn->error);
        }

        foreach ($stop_cities as $index => $stop_city) {
          $stop_city = mysqli_real_escape_string($conn, trim($stop_city));
          $stop_duration = floatval($stop_durations[$index]);
          $stop_stmt->bind_param('isd', $flight_id, $stop_city, $stop_duration);

          if (!$stop_stmt->execute()) {
            throw new Exception("Stop insert failed: " . $stop_stmt->error);
          }
          log_debug("Inserted stop: $stop_city, duration: $stop_duration");
        }
        $stop_stmt->close();
      }

      // Insert return stops
      if ($has_return && $has_return_stops && !empty($return_stop_cities)) {
        $return_stop_query = "INSERT INTO flight_stops (flight_id, stop_city, stop_duration, is_return_stop) VALUES (?, ?, ?, 1)";
        $return_stop_stmt = $conn->prepare($return_stop_query);
        if (!$return_stop_stmt) {
          throw new Exception("Return stop prepare failed: " . $conn->error);
        }

        foreach ($return_stop_cities as $index => $stop_city) {
          $stop_city = mysqli_real_escape_string($conn, trim($stop_city));
          $stop_duration = floatval($return_stop_durations[$index]);
          $return_stop_stmt->bind_param('isd', $flight_id, $stop_city, $stop_duration);

          if (!$return_stop_stmt->execute()) {
            throw new Exception("Return stop insert failed: " . $return_stop_stmt->error);
          }
          log_debug("Inserted return stop: $stop_city, duration: $stop_duration");
        }
        $return_stop_stmt->close();
      }

      // Insert pricing and seats
      $pricing_query = "INSERT INTO flight_pricing (
                flight_id, economy_price, business_price, first_class_price,
                economy_seats, business_seats, first_class_seats
            ) VALUES (?, ?, ?, ?, ?, ?, ?)";

      $pricing_stmt = $conn->prepare($pricing_query);
      if (!$pricing_stmt) {
        throw new Exception("Pricing prepare failed: " . $conn->error);
      }

      $pricing_stmt->bind_param(
        'idddiii',
        $flight_id,
        $economy_price,
        $business_price,
        $first_class_price,
        $economy_seats,
        $business_seats,
        $first_class_seats
      );

      if (!$pricing_stmt->execute()) {
        throw new Exception("Pricing insert failed: " . $pricing_stmt->error);
      }
      log_debug("Inserted pricing: economy=$economy_price, business=$business_price, first_class=$first_class_price");
      $pricing_stmt->close();

      // Verify insertion
      $verify_query = "SELECT id FROM flights WHERE id = ?";
      $verify_stmt = $conn->prepare($verify_query);
      $verify_stmt->bind_param('i', $flight_id);
      $verify_stmt->execute();
      $result = $verify_stmt->get_result();

      if ($result->num_rows === 0) {
        throw new Exception("Flight not found after insertion");
      }

      $success = "Flight added successfully!";
      log_debug("Flight addition successful, redirecting...");

      // Write debug log to file
      file_put_contents('../logs/flight_add.log', date('Y-m-d H:i:s') . " - " . implode("\n", $debug_log) . "\n", FILE_APPEND);

      // Redirect after success
      header("refresh:2;url=flights.php");
    } else {
      log_debug("Validation errors: " . implode(", ", $errors));
      file_put_contents('../logs/flight_add.log', date('Y-m-d H:i:s') . " - Validation errors: " . implode(", ", $errors) . "\n", FILE_APPEND);
    }
  } catch (Exception $e) {
    $errors[] = "Error: " . $e->getMessage();
    log_debug("Exception: " . $e->getMessage());
    file_put_contents('../logs/flight_add.log', date('Y-m-d H:i:s') . " - Exception: " . $e->getMessage() . "\n", FILE_APPEND);
  }
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
      <?php include 'includes/sidebar.php'; ?>
      <!-- Main Content -->
      <main class="main-content col-md-9">
        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4">
          <div class="container-fluid">
            <button id="sidebarToggle" class="btn d-lg-none me-2">
              <i class="fas fa-bars"></i>
            </button>
            <h1 class="navbar-brand mb-0 d-flex align-items-center">
              <i class="text-primary fas fa-plane me-2"></i> Add New Flight
            </h1>
            <div class="d-flex align-items-center">
              <button onclick="history.back()" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Back
              </button>
            </div>
          </div>
        </nav>

        <!-- Form Container -->
        <div class="container-fluid">
          <div class="card shadow-sm">
            <div class="card-body p-4">
              <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                  <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>

              <?php if ($success): ?>
                <div class="alert alert-success">
                  <p><?php echo htmlspecialchars($success); ?></p>
                </div>
              <?php endif; ?>

              <div class="mb-4">
                <h2 class="card-title text-primary">
                  <i class="fas fa-plane-departure me-2"></i>Add New Flight
                </h2>
                <p class="text-muted">Enter flight details for Umrah journey</p>
              </div>

              <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" class="needs-validation" id="flightForm" novalidate>
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
                    <div class="error-feedback" id="airline_name-error"></div>
                  </div>
                  <div class="col-md-6">
                    <label for="flight_number" class="form-label">Flight Number <span class="text-danger">*</span></label>
                    <input type="text" name="flight_number" id="flight_number" class="form-control" placeholder="e.g., PK-309" required maxlength="9">
                    <div class="error-feedback" id="flight_number-error"></div>
                  </div>
                </div>

                <!-- Route Information -->
                <div class="row mb-4">
                  <!-- cities -->
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
                    <div class="error-feedback" id="departure_city-error"></div>
                  </div>
                  <!-- Arrival City -->
                  <div class="col-md-6">
                    <label for="arrival_city" class="form-label">Arrival City <span class="text-danger">*</span></label>
                    <select name="arrival_city" id="arrival_city" class="form-select" required>
                      <option value="">Select City</option>
                      <option value="Jeddah">Jeddah</option>
                      <option value="Medina">Medina</option>
                    </select>
                    <div class="error-feedback" id="arrival_city-error"></div>
                  </div>
                </div>

                <!-- Flight Stops -->
                <div class="card mb-4 bg-light">
                  <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                      <h4 class="mb-0">Flight Stops</h4>
                      <div class="ms-4">
                        <div class="form-check form-check-inline">
                          <input class="form-check-input" type="radio" name="has_stops" id="directFlight" value="0" checked onchange="toggleStopsSection(false)">
                          <label class="form-check-label" for="directFlight">Direct Flight</label>
                        </div>
                        <div class="form-check form-check-inline">
                          <input class="form-check-input" type="radio" name="has_stops" id="hasStops" value="1" onchange="toggleStopsSection(true)">
                          <label class="form-check-label" for="hasStops">Has Stops</label>
                        </div>
                      </div>
                    </div>

                    <div id="stops-container" class="d-none">
                      <!-- Initial stop row -->
                      <div class="stop-row row mb-3">
                        <div class="col-md-6 mb-3 mb-md-0">
                          <label class="form-label">Stop City <span class="text-danger">*</span></label>
                          <input type="text" name="stop_city[]" class="form-control stop-city" maxlength="12" placeholder="e.g., Dubai">
                        </div>
                        <div class="col-md-6">
                          <label class="form-label">Stop Duration (hours) <span class="text-danger">*</span></label>
                          <input type="text" name="stop_duration[]" class="form-control stop-duration-input" placeholder="e.g., 4">
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
                    <input type="date" name="departure_date" id="departure_date" class="form-control" min="1940-01-01" required onkeydown="return false;">
                    <div class="error-feedback" id="departure_date-error"></div>
                  </div>
                  <div class="col-md-4 mb-3 mb-md-0">
                    <label for="departure_time" class="form-label">Departure Time <span class="text-danger">*</span></label>
                    <input type="text" name="departure_time" id="departure_time" class="form-control" placeholder="HH:MM (24-hour format)" pattern="([01]?[0-9]|2[0-3]):[0-5][0-9]" required>
                    <small class="text-muted">Enter time in 24-hour format (00:00 to 23:59)</small>
                    <div class="error-feedback" id="departure_time-error"></div>
                  </div>
                  <div class="col-md-4">
                    <label for="flight_duration" class="form-label">Flight Duration (hours) <span class="text-danger">*</span></label>
                    <input type="number" name="flight_duration" id="flight_duration" class="form-control" placeholder="e.g., 5.5" step="0.1" min="0" max="8" required>
                    <div class="error-feedback" id="flight_duration-error"></div>
                  </div>
                </div>

                <!-- Distance Field -->
                <div class="mb-4">
                  <label for="distance" class="form-label">Distance (km) <span class="text-danger">*</span></label>
                  <input type="number" name="distance" id="distance" class="form-control" placeholder="e.g., 3500" step="1" min="0" max="20000" required>
                  <div class="error-feedback" id="distance-error"></div>
                </div>

                <!-- Return Flight Section -->
                <div class="border-top pt-4 mt-4">
                  <div class="mb-3">
                    <h3 class="text-primary">
                      <i class="fas fa-plane-arrival me-2"></i>Return Flight Details
                    </h3>
                  </div>

                  <div class="d-flex align-items-center mb-3">
                    <h4 class="mb-0">Journey Type</h4>
                    <div class="ms-4">
                      <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="has_return" id="oneWayFlight" value="0" checked onchange="toggleReturnSection(false)">
                        <label class="form-check-label" for="oneWayFlight">One-way Flight</label>
                      </div>
                      <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="has_return" id="roundTrip" value="1" onchange="toggleReturnSection(true)">
                        <label class="form-check-label" for="roundTrip">Round Trip</label>
                      </div>
                    </div>
                  </div>

                  <div id="return-container" class="card bg-light mb-4 d-none">
                    <div class="card-body">
                      <!-- Return Flight Details -->
                      <div class="row mb-4">
                        <div class="col-md-6 mb-3 mb-md-0">
                          <label for="return_airline" class="form-label">Return Airline <span class="text-danger">*</span></label>
                          <select name="return_airline" id="return_airline" class="form-select return-required">
                            <option value="">Select Airline</option>
                            <!-- Special Option -->
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
                          <div class="error-feedback" id="return_airline-error"></div>
                        </div>
                        <div class="col-md-6">
                          <label for="return_flight_number" class="form-label">Return Flight Number <span class="text-danger">*</span></label>
                          <input type="text" name="return_flight_number" id="return_flight_number" class="form-control return-required" placeholder="e.g., PK-310" maxlength="7">
                          <div class="error-feedback" id="return_flight_number-error"></div>
                        </div>
                      </div>

                      <div class="row mb-4">
                        <div class="col-md-4 mb-3 mb-md-0">
                          <label for="return_date" class="form-label">Return Date <span class="text-danger">*</span></label>
                          <input type="date" name="return_date" id="return_date" class="form-control return-required">
                          <div class="error-feedback" id="return_date-error"></div>
                        </div>
                        <div class="col-md-4 mb-3 mb-md-0">
                          <label for="return_time" class="form-label">Return Time <span class="text-danger">*</span></label>
                          <input type="text" name="return_time" id="return_time" class="form-control return-required" placeholder="HH:MM (24-hour format)">
                          <div class="error-feedback" id="return_time-error"></div>
                        </div>
                        <div class="col-md-4">
                          <label for="return_flight_duration" class="form-label">Return Flight Duration (hours) <span class="text-danger">*</span></label>
                          <input type="text" name="return_flight_duration" id="return_flight_duration" class="form-control return-required return-duration-input" placeholder="e.g., 5.5">
                          <div class="error-feedback" id="return_flight_duration-error"></div>
                        </div>
                      </div>

                      <!-- Return Flight Stops -->
                      <div class="mt-4">
                        <div class="d-flex align-items-center mb-3">
                          <h5 class="mb-0">Return Flight Stops</h5>
                          <div class="ms-4">
                            <div class="form-check form-check-inline">
                              <input class="form-check-input" type="radio" name="has_return_stops" id="directReturnFlight" value="0" checked onchange="toggleReturnStopsSection(false)">
                              <label class="form-check-label" for="directReturnFlight">Direct Return Flight</label>
                            </div>
                            <div class="form-check form-check-inline">
                              <input class="form-check-input" type="radio" name="has_return_stops" id="hasReturnStops" value="1" onchange="toggleReturnStopsSection(true)">
                              <label class="form-check-label" for="hasReturnStops">Has Stops</label>
                            </div>
                          </div>
                        </div>

                        <div id="return-stops-container" class="d-none">
                          <!-- Initial return stop row -->
                          <div class="return-stop-row row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                              <label class="form-label">Return Stop City <span class="text-danger">*</span></label>
                              <input type="text" name="return_stop_city[]" class="form-control return-stop-city" placeholder="e.g., Dubai" maxlength="12">
                            </div>
                            <div class="col-md-6">
                              <label class="form-label">Return Stop Duration (hours) <span class="text-danger">*</span></label>
                              <input type="text" name="return_stop_duration[]" class="form-control return-stop-duration" placeholder="e.g., 2">
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
                      <input type="number" name="economy_price" id="economy_price" class="form-control economy-price" placeholder="242,250" required>
                      <div class="error-feedback" id="economy_price-error"></div>
                    </div>
                    <div class="col-md-4 mb-3 mb-md-0">
                      <label for="business_price" class="form-label">Business Price (PKR) <span class="text-danger">*</span></label>
                      <input type="number" name="business_price" id="business_price" class="form-control business-price" placeholder="427,500" required>
                      <div class="error-feedback" id="business_price-error"></div>
                    </div>
                    <div class="col-md-4">
                      <label for="first_class_price" class="form-label">First Class Price (PKR) <span class="text-danger">*</span></label>
                      <input type="number" name="first_class_price" id="first_class_price" class="form-control first-class-price" placeholder="712,500" required>
                      <div class="error-feedback" id="first_class_price-error"></div>
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
                      <input type="number" name="economy_seats" id="economy_seats" class="form-control" placeholder="200" min="100" max="500" required>
                      <div class="error-feedback" id="economy_seats-error"></div>
                    </div>
                    <div class="col-md-4 mb-3 mb-md-0">
                      <label for="business_seats" class="form-label">Business Seats <span class="text-danger">*</span></label>
                      <input type="number" name="business_seats" id="business_seats" class="form-control" placeholder="30" min="10" max="100" required>
                      <div class="error-feedback" id="business_seats-error"></div>
                    </div>
                    <div class="col-md-4">
                      <label for="first_class_seats" class="form-label">First Class Seats <span class="text-danger">*</span></label>
                      <input type="number" name="first_class_seats" id="first_class_seats" class="form-control" placeholder="10" min="5" max="50" required>
                      <div class="error-feedback" id="first_class_seats-error"></div>
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
  <!-- SweetAlert2 for notifications -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="assets/js/add-flight.js"></script>
</body>

</html>