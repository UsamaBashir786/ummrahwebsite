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
              <div class="mb-4">
                <h2 class="card-title text-primary">
                  <i class="fas fa-plane-departure me-2"></i>Add New Flight
                </h2>
                <p class="text-muted">Enter flight details for Umrah journey</p>
              </div>

              <form action="#" method="POST" class="needs-validation" id="flightForm" novalidate>
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