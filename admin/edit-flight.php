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

// Check if flight ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  echo "<script>alert('Invalid flight ID.'); window.location.href = 'view-flights.php';</script>";
  exit;
}

$flight_id = intval($_GET['id']);

// Fetch flight details
global $conn;
$sql = "SELECT * FROM flights WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $flight_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  echo "<script>alert('Flight not found.'); window.location.href = 'view-flights.php';</script>";
  exit;
}

$flight = $result->fetch_assoc();
$stmt->close();

// Decode JSON stops
$stops = json_decode($flight['stops'], true) ?: [];
$return_stops = json_decode($flight['return_stops'], true) ?: [];

// Process form submission for update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

  // Prepare and execute MySQLi update query
  $sql = "UPDATE flights SET 
        airline_name = ?, flight_number = ?, departure_city = ?, arrival_city = ?, 
        has_stops = ?, stops = ?, departure_date = ?, departure_time = ?, 
        flight_duration = ?, distance = ?, has_return = ?, return_airline = ?, 
        return_flight_number = ?, return_date = ?, return_time = ?, 
        return_flight_duration = ?, has_return_stops = ?, return_stops = ?, 
        economy_price = ?, business_price = ?, first_class_price = ?, 
        economy_seats = ?, business_seats = ?, first_class_seats = ?, 
        flight_notes = ?
        WHERE id = ?";

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

  // Bind parameters
  $stmt->bind_param(
    "ssssisssdiissssdissiiiissi", // Type string matches the 25 parameters + 1 for id
    $airline_name,
    $flight_number,
    $departure_city,
    $arrival_city,
    $has_stops,
    $stops_json,
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
    $has_return_stops,
    $return_stops_json,
    $economy_price,
    $business_price,
    $first_class_price,
    $economy_seats,
    $business_seats,
    $first_class_seats,
    $flight_notes,
    $flight_id
  );

  // Execute the statement
  if ($stmt->execute()) {
    echo "<script>
            alert('Flight updated successfully!');
            window.location.href = 'view-flights.php';
        </script>";
  } else {
    echo "<script>alert('Error updating flight: " . addslashes($stmt->error) . "');</script>";
  }

  $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Flight | UmrahFlights Admin</title>
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
      <div class="col-md-3"></div>
      <main class="col-md-8 px-0">
        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
          <div class="container-fluid">
            <h1 class="navbar-brand mb-0 d-flex align-items-center">
              <i class="text-primary fas fa-plane me-2"></i> Edit Flight
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
                  <i class="fas fa-plane-departure me-2"></i>Edit Flight
                </h2>
                <p class="text-muted">Update flight details for Umrah journey</p>
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
                        <option value="PIA" <?php echo $flight['airline_name'] === 'PIA' ? 'selected' : ''; ?>>Pakistan International Airlines (PIA)</option>
                        <option value="AirBlue" <?php echo $flight['airline_name'] === 'AirBlue' ? 'selected' : ''; ?>>AirBlue</option>
                        <option value="SereneAir" <?php echo $flight['airline_name'] === 'SereneAir' ? 'selected' : ''; ?>>Serene Air</option>
                        <option value="AirSial" <?php echo $flight['airline_name'] === 'AirSial' ? 'selected' : ''; ?>>AirSial</option>
                        <option value="FlyJinnah" <?php echo $flight['airline_name'] === 'FlyJinnah' ? 'selected' : ''; ?>>Fly Jinnah</option>
                      </optgroup>
                      <!-- Middle Eastern Airlines -->
                      <optgroup label="Middle Eastern Airlines">
                        <option value="Emirates" <?php echo $flight['airline_name'] === 'Emirates' ? 'selected' : ''; ?>>Emirates</option>
                        <option value="Qatar" <?php echo $flight['airline_name'] === 'Qatar' ? 'selected' : ''; ?>>Qatar Airways</option>
                        <option value="Etihad" <?php echo $flight['airline_name'] === 'Etihad' ? 'selected' : ''; ?>>Etihad Airways</option>
                        <option value="Saudi" <?php echo $flight['airline_name'] === 'Saudi' ? 'selected' : ''; ?>>Saudia (Saudi Airlines)</option>
                        <option value="Flynas" <?php echo $flight['airline_name'] === 'Flynas' ? 'selected' : ''; ?>>Flynas</option>
                        <option value="Flydubai" <?php echo $flight['airline_name'] === 'Flydubai' ? 'selected' : ''; ?>>Flydubai</option>
                        <option value="OmanAir" <?php echo $flight['airline_name'] === 'OmanAir' ? 'selected' : ''; ?>>Oman Air</option>
                        <option value="GulfAir" <?php echo $flight['airline_name'] === 'GulfAir' ? 'selected' : ''; ?>>Gulf Air</option>
                        <option value="KuwaitAirways" <?php echo $flight['airline_name'] === 'KuwaitAirways' ? 'selected' : ''; ?>>Kuwait Airways</option>
                      </optgroup>
                      <!-- Asian Airlines -->
                      <optgroup label="Asian Airlines">
                        <option value="Thai" <?php echo $flight['airline_name'] === 'Thai' ? 'selected' : ''; ?>>Thai Airways</option>
                        <option value="Malaysia" <?php echo $flight['airline_name'] === 'Malaysia' ? 'selected' : ''; ?>>Malaysia Airlines</option>
                        <option value="Singapore" <?php echo $flight['airline_name'] === 'Singapore' ? 'selected' : ''; ?>>Singapore Airlines</option>
                        <option value="Cathay" <?php echo $flight['airline_name'] === 'Cathay' ? 'selected' : ''; ?>>Cathay Pacific</option>
                        <option value="ChinaSouthern" <?php echo $flight['airline_name'] === 'ChinaSouthern' ? 'selected' : ''; ?>>China Southern</option>
                        <option value="Turkish" <?php echo $flight['airline_name'] === 'Turkish' ? 'selected' : ''; ?>>Turkish Airlines</option>
                      </optgroup>
                      <!-- European & American Airlines -->
                      <optgroup label="European & American Airlines">
                        <option value="British" <?php echo $flight['airline_name'] === 'British' ? 'selected' : ''; ?>>British Airways</option>
                        <option value="Lufthansa" <?php echo $flight['airline_name'] === 'Lufthansa' ? 'selected' : ''; ?>>Lufthansa</option>
                        <option value="AirFrance" <?php echo $flight['airline_name'] === 'AirFrance' ? 'selected' : ''; ?>>Air France</option>
                        <option value="KLM" <?php echo $flight['airline_name'] === 'KLM' ? 'selected' : ''; ?>>KLM Royal Dutch Airlines</option>
                        <option value="Virgin" <?php echo $flight['airline_name'] === 'Virgin' ? 'selected' : ''; ?>>Virgin Atlantic</option>
                      </optgroup>
                      <!-- Budget Airlines -->
                      <optgroup label="Budget Airlines">
                        <option value="AirArabia" <?php echo $flight['airline_name'] === 'AirArabia' ? 'selected' : ''; ?>>Air Arabia</option>
                        <option value="Indigo" <?php echo $flight['airline_name'] === 'Indigo' ? 'selected' : ''; ?>>IndiGo</option>
                        <option value="SpiceJet" <?php echo $flight['airline_name'] === 'SpiceJet' ? 'selected' : ''; ?>>SpiceJet</option>
                      </optgroup>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label for="flight_number" class="form-label">Flight Number <span class="text-danger">*</span></label>
                    <input type="text" name="flight_number" id="flight_number" class="form-control" value="<?php echo htmlspecialchars($flight['flight_number']); ?>" placeholder="e.g., PK-309" required maxlength="9">
                  </div>
                </div>

                <!-- Route Information -->
                <div class="row mb-4">
                  <div class="col-md-6 mb-3 mb-md-0">
                    <label for="departure_city" class="form-label">Departure City <span class="text-danger">*</span></label>
                    <select name="departure_city" id="departure_city" class="form-select" required>
                      <option value="">Select City</option>
                      <!-- Major Cities -->
                      <option value="Karachi" <?php echo $flight['departure_city'] === 'Karachi' ? 'selected' : ''; ?>>Karachi</option>
                      <option value="Lahore" <?php echo $flight['departure_city'] === 'Lahore' ? 'selected' : ''; ?>>Lahore</option>
                      <option value="Islamabad" <?php echo $flight['departure_city'] === 'Islamabad' ? 'selected' : ''; ?>>Islamabad</option>
                      <option value="Rawalpindi" <?php echo $flight['departure_city'] === 'Rawalpindi' ? 'selected' : ''; ?>>Rawalpindi</option>
                      <option value="Faisalabad" <?php echo $flight['departure_city'] === 'Faisalabad' ? 'selected' : ''; ?>>Faisalabad</option>
                      <option value="Multan" <?php echo $flight['departure_city'] === 'Multan' ? 'selected' : ''; ?>>Multan</option>
                      <option value="Hyderabad" <?php echo $flight['departure_city'] === 'Hyderabad' ? 'selected' : ''; ?>>Hyderabad</option>
                      <option value="Peshawar" <?php echo $flight['departure_city'] === 'Peshawar' ? 'selected' : ''; ?>>Peshawar</option>
                      <option value="Quetta" <?php echo $flight['departure_city'] === 'Quetta' ? 'selected' : ''; ?>>Quetta</option>
                      <!-- Punjab Cities -->
                      <optgroup label="Punjab">
                        <option value="Gujranwala" <?php echo $flight['departure_city'] === 'Gujranwala' ? 'selected' : ''; ?>>Gujranwala</option>
                        <option value="Sialkot" <?php echo $flight['departure_city'] === 'Sialkot' ? 'selected' : ''; ?>>Sialkot</option>
                        <option value="Bahawalpur" <?php echo $flight['departure_city'] === 'Bahawalpur' ? 'selected' : ''; ?>>Bahawalpur</option>
                        <option value="Sargodha" <?php echo $flight['departure_city'] === 'Sargodha' ? 'selected' : ''; ?>>Sargodha</option>
                        <option value="Jhang" <?php echo $flight['departure_city'] === 'Jhang' ? 'selected' : ''; ?>>Jhang</option>
                        <option value="Gujrat" <?php echo $flight['departure_city'] === 'Gujrat' ? 'selected' : ''; ?>>Gujrat</option>
                        <option value="Kasur" <?php echo $flight['departure_city'] === 'Kasur' ? 'selected' : ''; ?>>Kasur</option>
                        <option value="Okara" <?php echo $flight['departure_city'] === 'Okara' ? 'selected' : ''; ?>>Okara</option>
                        <option value="Sahiwal" <?php echo $flight['departure_city'] === 'Sahiwal' ? 'selected' : ''; ?>>Sahiwal</option>
                        <option value="Sheikhupura" <?php echo $flight['departure_city'] === 'Sheikhupura' ? 'selected' : ''; ?>>Sheikhupura</option>
                      </optgroup>
                      <!-- Sindh Cities -->
                      <optgroup label="Sindh">
                        <option value="Sukkur" <?php echo $flight['departure_city'] === 'Sukkur' ? 'selected' : ''; ?>>Sukkur</option>
                        <option value="Larkana" <?php echo $flight['departure_city'] === 'Larkana' ? 'selected' : ''; ?>>Larkana</option>
                        <option value="Nawabshah" <?php echo $flight['departure_city'] === 'Nawabshah' ? 'selected' : ''; ?>>Nawabshah</option>
                        <option value="Mirpur Khas" <?php echo $flight['departure_city'] === 'Mirpur Khas' ? 'selected' : ''; ?>>Mirpur Khas</option>
                        <option value="Thatta" <?php echo $flight['departure_city'] === 'Thatta' ? 'selected' : ''; ?>>Thatta</option>
                        <option value="Jacobabad" <?php echo $flight['departure_city'] === 'Jacobabad' ? 'selected' : ''; ?>>Jacobabad</option>
                      </optgroup>
                      <!-- KPK Cities -->
                      <optgroup label="Khyber Pakhtunkhwa">
                        <option value="Mardan" <?php echo $flight['departure_city'] === 'Mardan' ? 'selected' : ''; ?>>Mardan</option>
                        <option value="Abbottabad" <?php echo $flight['departure_city'] === 'Abbottabad' ? 'selected' : ''; ?>>Abbottabad</option>
                        <option value="Swat" <?php echo $flight['departure_city'] === 'Swat' ? 'selected' : ''; ?>>Swat</option>
                        <option value="Nowshera" <?php echo $flight['departure_city'] === 'Nowshera' ? 'selected' : ''; ?>>Nowshera</option>
                        <option value="Charsadda" <?php echo $flight['departure_city'] === 'Charsadda' ? 'selected' : ''; ?>>Charsadda</option>
                        <option value="Mansehra" <?php echo $flight['departure_city'] === 'Mansehra' ? 'selected' : ''; ?>>Mansehra</option>
                      </optgroup>
                      <!-- Balochistan Cities -->
                      <optgroup label="Balochistan">
                        <option value="Gwadar" <?php echo $flight['departure_city'] === 'Gwadar' ? 'selected' : ''; ?>>Gwadar</option>
                        <option value="Khuzdar" <?php echo $flight['departure_city'] === 'Khuzdar' ? 'selected' : ''; ?>>Khuzdar</option>
                        <option value="Chaman" <?php echo $flight['departure_city'] === 'Chaman' ? 'selected' : ''; ?>>Chaman</option>
                        <option value="Zhob" <?php echo $flight['departure_city'] === 'Zhob' ? 'selected' : ''; ?>>Zhob</option>
                      </optgroup>
                      <!-- AJK & Gilgit-Baltistan -->
                      <optgroup label="Azad Kashmir & Gilgit-Baltistan">
                        <option value="Muzaffarabad" <?php echo $flight['departure_city'] === 'Muzaffarabad' ? 'selected' : ''; ?>>Muzaffarabad</option>
                        <option value="Mirpur" <?php echo $flight['departure_city'] === 'Mirpur' ? 'selected' : ''; ?>>Mirpur</option>
                        <option value="Gilgit" <?php echo $flight['departure_city'] === 'Gilgit' ? 'selected' : ''; ?>>Gilgit</option>
                        <option value="Skardu" <?php echo $flight['departure_city'] === 'Skardu' ? 'selected' : ''; ?>>Skardu</option>
                      </optgroup>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label for="arrival_city" class="form-label">Arrival City <span class="text-danger">*</span></label>
                    <select name="arrival_city" id="arrival_city" class="form-select" required>
                      <option value="">Select City</option>
                      <option value="Jeddah" <?php echo $flight['arrival_city'] === 'Jeddah' ? 'selected' : ''; ?>>Jeddah</option>
                      <option value="Medina" <?php echo $flight['arrival_city'] === 'Medina' ? 'selected' : ''; ?>>Medina</option>
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
                          <input class="form-check-input" type="radio" name="has_stops" id="directFlight" value="0" <?php echo !$flight['has_stops'] ? 'checked' : ''; ?>>
                          <label class="form-check-label" for="directFlight">Direct Flight</label>
                        </div>
                        <div class="form-check form-check-inline">
                          <input class="form-check-input" type="radio" name="has_stops" id="hasStops" value="1" <?php echo $flight['has_stops'] ? 'checked' : ''; ?>>
                          <label class="form-check-label" for="hasStops">Has Stops</label>
                        </div>
                      </div>
                    </div>

                    <div id="stops-container" class="<?php echo $flight['has_stops'] ? '' : 'd-none'; ?>">
                      <?php if (!empty($stops)) : ?>
                        <?php foreach ($stops as $stop) : ?>
                          <div class="stop-row row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                              <label class="form-label">Stop City</label>
                              <input type="text" name="stop_city[]" class="form-control" maxlength="12" value="<?php echo htmlspecialchars($stop['city']); ?>" placeholder="e.g., Dubai">
                            </div>
                            <div class="col-md-6">
                              <label class="form-label">Stop Duration (hours)</label>
                              <input type="text" name="stop_duration[]" class="form-control" value="<?php echo htmlspecialchars($stop['duration']); ?>" placeholder="e.g., 4">
                            </div>
                          </div>
                        <?php endforeach; ?>
                      <?php else : ?>
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
                      <?php endif; ?>
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
                    <input type="date" name="departure_date" id="departure_date" class="form-control" value="<?php echo htmlspecialchars($flight['departure_date']); ?>" required>
                  </div>
                  <div class="col-md-4 mb-3 mb-md-0">
                    <label for="departure_time" class="form-label">Departure Time <span class="text-danger">*</span></label>
                    <input type="text" name="departure_time" id="departure_time" class="form-control" value="<?php echo htmlspecialchars($flight['departure_time']); ?>" placeholder="HH:MM (24-hour format)" required>
                  </div>
                  <div class="col-md-4">
                    <label for="flight_duration" class="form-label">Flight Duration (hours) <span class="text-danger">*</span></label>
                    <input type="number" name="flight_duration" id="flight_duration" class="form-control" value="<?php echo htmlspecialchars($flight['flight_duration']); ?>" placeholder="e.g., 5.5" step="0.1" required>
                  </div>
                </div>

                <!-- Distance Field -->
                <div class="mb-4">
                  <label for="distance" class="form-label">Distance (km) <span class="text-danger">*</span></label>
                  <input type="number" name="distance" id="distance" class="form-control" value="<?php echo htmlspecialchars($flight['distance']); ?>" placeholder="e.g., 3500" step="1" required>
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
                        <input class="form-check-input" type="radio" name="has_return" id="oneWayFlight" value="0" <?php echo !$flight['has_return'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="oneWayFlight">One-way Flight</label>
                      </div>
                      <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="has_return" id="roundTrip" value="1" <?php echo $flight['has_return'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="roundTrip">Round Trip</label>
                      </div>
                    </div>
                  </div>

                  <div id="return-container" class="card bg-light mb-4 <?php echo $flight['has_return'] ? '' : 'd-none'; ?>">
                    <div class="card-body">
                      <div class="row mb-4">
                        <div class="col-md-6 mb-3 mb-md-0">
                          <label for="return_airline" class="form-label">Return Airline</label>
                          <select name="return_airline" id="return_airline" class="form-select">
                            <option value="">Select Airline</option>
                            <option value="same" <?php echo $flight['return_airline'] === $flight['airline_name'] ? 'selected' : ''; ?>>Same as Outbound</option>
                            <!-- Pakistani Airlines -->
                            <optgroup label="Pakistani Airlines">
                              <option value="PIA" <?php echo $flight['return_airline'] === 'PIA' ? 'selected' : ''; ?>>Pakistan International Airlines (PIA)</option>
                              <option value="AirBlue" <?php echo $flight['return_airline'] === 'AirBlue' ? 'selected' : ''; ?>>AirBlue</option>
                              <option value="SereneAir" <?php echo $flight['return_airline'] === 'SereneAir' ? 'selected' : ''; ?>>Serene Air</option>
                              <option value="AirSial" <?php echo $flight['return_airline'] === 'AirSial' ? 'selected' : ''; ?>>AirSial</option>
                              <option value="FlyJinnah" <?php echo $flight['return_airline'] === 'FlyJinnah' ? 'selected' : ''; ?>>Fly Jinnah</option>
                            </optgroup>
                            <!-- Middle Eastern Airlines -->
                            <optgroup label="Middle Eastern Airlines">
                              <option value="Emirates" <?php echo $flight['return_airline'] === 'Emirates' ? 'selected' : ''; ?>>Emirates</option>
                              <option value="Qatar" <?php echo $flight['return_airline'] === 'Qatar' ? 'selected' : ''; ?>>Qatar Airways</option>
                              <option value="Etihad" <?php echo $flight['return_airline'] === 'Etihad' ? 'selected' : ''; ?>>Etihad Airways</option>
                              <option value="Saudi" <?php echo $flight['return_airline'] === 'Saudi' ? 'selected' : ''; ?>>Saudia (Saudi Airlines)</option>
                              <option value="Flynas" <?php echo $flight['return_airline'] === 'Flynas' ? 'selected' : ''; ?>>Flynas</option>
                              <option value="Flydubai" <?php echo $flight['return_airline'] === 'Flydubai' ? 'selected' : ''; ?>>Flydubai</option>
                              <option value="OmanAir" <?php echo $flight['return_airline'] === 'OmanAir' ? 'selected' : ''; ?>>Oman Air</option>
                            </optgroup>
                            <!-- Asian Airlines -->
                            <optgroup label="Asian Airlines">
                              <option value="Thai" <?php echo $flight['return_airline'] === 'Thai' ? 'selected' : ''; ?>>Thai Airways</option>
                              <option value="Singapore" <?php echo $flight['return_airline'] === 'Singapore' ? 'selected' : ''; ?>>Singapore Airlines</option>
                              <option value="Turkish" <?php echo $flight['return_airline'] === 'Turkish' ? 'selected' : ''; ?>>Turkish Airlines</option>
                              <option value="Malaysia" <?php echo $flight['return_airline'] === 'Malaysia' ? 'selected' : ''; ?>>Malaysia Airlines</option>
                            </optgroup>
                            <!-- European & American Airlines -->
                            <optgroup label="European & American Airlines">
                              <option value="British" <?php echo $flight['return_airline'] === 'British' ? 'selected' : ''; ?>>British Airways</option>
                              <option value="Lufthansa" <?php echo $flight['return_airline'] === 'Lufthansa' ? 'selected' : ''; ?>>Lufthansa</option>
                              <option value="AirFrance" <?php echo $flight['return_airline'] === 'AirFrance' ? 'selected' : ''; ?>>Air France</option>
                            </optgroup>
                            <!-- Budget Airlines -->
                            <optgroup label="Budget Airlines">
                              <option value="AirArabia" <?php echo $flight['return_airline'] === 'AirArabia' ? 'selected' : ''; ?>>Air Arabia</option>
                              <option value="Indigo" <?php echo $flight['return_airline'] === 'Indigo' ? 'selected' : ''; ?>>IndiGo</option>
                            </optgroup>
                          </select>
                        </div>
                        <div class="col-md-6">
                          <label for="return_flight_number" class="form-label">Return Flight Number</label>
                          <input type="text" name="return_flight_number" id="return_flight_number" class="form-control" value="<?php echo htmlspecialchars($flight['return_flight_number'] ?? ''); ?>" placeholder="e.g., PK-310" maxlength="7">
                        </div>
                      </div>

                      <div class="row mb-4">
                        <div class="col-md-4 mb-3 mb-md-0">
                          <label for="return_date" class="form-label">Return Date</label>
                          <input type="date" name="return_date" id="return_date" class="form-control" value="<?php echo htmlspecialchars($flight['return_date'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4 mb-3 mb-md-0">
                          <label for="return_time" class="form-label">Return Time</label>
                          <input type="text" name="return_time" id="return_time" class="form-control" value="<?php echo htmlspecialchars($flight['return_time'] ?? ''); ?>" placeholder="HH:MM (24-hour format)">
                        </div>
                        <div class="col-md-4">
                          <label for="return_flight_duration" class="form-label">Return Flight Duration (hours)</label>
                          <input type="text" name="return_flight_duration" id="return_flight_duration" class="form-control" value="<?php echo htmlspecialchars($flight['return_flight_duration'] ?? ''); ?>" placeholder="e.g., 5.5">
                        </div>
                      </div>

                      <div class="mt-4">
                        <div class="d-flex align-items-center mb-3">
                          <h5 class="mb-0">Return Flight Stops</h5>
                          <div class="ms-4">
                            <div class="form-check form-check-inline">
                              <input class="form-check-input" type="radio" name="has_return_stops" id="directReturnFlight" value="0" <?php echo !$flight['has_return_stops'] ? 'checked' : ''; ?>>
                              <label class="form-check-label" for="directReturnFlight">Direct Return Flight</label>
                            </div>
                            <div class="form-check form-check-inline">
                              <input class="form-check-input" type="radio" name="has_return_stops" id="hasReturnStops" value="1" <?php echo $flight['has_return_stops'] ? 'checked' : ''; ?>>
                              <label class="form-check-label" for="hasReturnStops">Has Stops</label>
                            </div>
                          </div>
                        </div>

                        <div id="return-stops-container" class="<?php echo $flight['has_return_stops'] ? '' : 'd-none'; ?>">
                          <?php if (!empty($return_stops)) : ?>
                            <?php foreach ($return_stops as $stop) : ?>
                              <div class="return-stop-row row mb-3">
                                <div class="col-md-6 mb-3 mb-md-0">
                                  <label class="form-label">Return Stop City</label>
                                  <input type="text" name="return_stop_city[]" class="form-control" value="<?php echo htmlspecialchars($stop['city']); ?>" placeholder="e.g., Dubai" maxlength="12">
                                </div>
                                <div class="col-md-6">
                                  <label class="form-label">Return Stop Duration (hours)</label>
                                  <input type="text" name="return_stop_duration[]" class="form-control" value="<?php echo htmlspecialchars($stop['duration']); ?>" placeholder="e.g., 2">
                                </div>
                              </div>
                            <?php endforeach; ?>
                          <?php else : ?>
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
                          <?php endif; ?>
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
                      <input type="number" name="economy_price" id="economy_price" class="form-control" value="<?php echo htmlspecialchars($flight['economy_price']); ?>" placeholder="242,250" required>
                    </div>
                    <div class="col-md-4 mb-3 mb-md-0">
                      <label for="business_price" class="form-label">Business Price (PKR) <span class="text-danger">*</span></label>
                      <input type="number" name="business_price" id="business_price" class="form-control" value="<?php echo htmlspecialchars($flight['business_price']); ?>" placeholder="427,500" required>
                    </div>
                    <div class="col-md-4">
                      <label for="first_class_price" class="form-label">First Class Price (PKR) <span class="text-danger">*</span></label>
                      <input type="number" name="first_class_price" id="first_class_price" class="form-control" value="<?php echo htmlspecialchars($flight['first_class_price']); ?>" placeholder="712,500" required>
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
                      <input type="number" name="economy_seats" id="economy_seats" class="form-control" value="<?php echo htmlspecialchars($flight['economy_seats']); ?>" placeholder="200" required>
                    </div>
                    <div class="col-md-4 mb-3 mb-md-0">
                      <label for="business_seats" class="form-label">Business Seats <span class="text-danger">*</span></label>
                      <input type="number" name="business_seats" id="business_seats" class="form-control" value="<?php echo htmlspecialchars($flight['business_seats']); ?>" placeholder="30" required>
                    </div>
                    <div class="col-md-4">
                      <label for="first_class_seats" class="form-label">First Class Seats <span class="text-danger">*</span></label>
                      <input type="number" name="first_class_seats" id="first_class_seats" class="form-control" value="<?php echo htmlspecialchars($flight['first_class_seats']); ?>" placeholder="10" required>
                    </div>
                  </div>
                </div>

                <!-- Flight Notes -->
                <div class="mb-4">
                  <label for="flight_notes" class="form-label">Flight Notes (Optional)</label>
                  <textarea name="flight_notes" id="flight_notes" class="form-control" rows="3" placeholder="Any additional information about this flight"><?php echo htmlspecialchars($flight['flight_notes'] ?? ''); ?></textarea>
                </div>

                <!-- Submit Buttons -->
                <div class="d-flex gap-2">
                  <button type="submit" id="submit-btn" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i> Update Flight
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