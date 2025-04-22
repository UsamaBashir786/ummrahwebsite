

<body class="bg-gray-50">
  <div class="flex h-screen">

    <!-- Main Content -->
    <div class="main flex-1 flex flex-col">
      <!-- Navbar -->
      <div class="bg-white shadow-md py-4 px-6 flex justify-between items-center">
        <h1 class="text-xl font-semibold flex items-center">
          <i class="text-teal-600 fas fa-plane mx-2"></i> Add New Flight
        </h1>

        <div class="flex items-center gap-4">
          <button onclick="history.back()" class="text-gray-800 hover:text-teal-600">
            <i class="fas fa-arrow-left mr-1"></i> Back
          </button>
          <button class="md:hidden text-gray-800" id="menu-btn">
            <i class="fas fa-bars"></i>
          </button>
        </div>
      </div>

      <!-- Form Container -->
      <div class="overflow-auto container mx-auto px-4 py-8">
        <div class="mx-auto bg-white p-8 rounded-lg shadow-lg">
          <div class="mb-6">
            <h1 class="text-2xl font-bold text-teal-600">
              <i class="fas fa-plane-departure mr-2"></i>Add New Flight
            </h1>
            <p class="text-gray-600 mt-2">Enter flight details for Umrah journey</p>
          </div>

          <form action="#" method="POST" class="space-y-6" id="flightForm">
            <!-- Outbound Flight Section Title -->
            <div class="border-b border-gray-200 pb-2 mb-4">
              <h2 class="text-xl font-bold text-teal-700">
                <i class="fas fa-plane-departure mr-2"></i>Outbound Flight Details
              </h2>
            </div>

            <!-- Airline & Flight Number -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-gray-700 font-semibold mb-2">Airline Name <span class="text-red-500">*</span></label>
                <select name="airline_name" id="airline_name" class="w-full px-4 py-2 border rounded-lg" required>
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
              <div>
                <label class="block text-gray-700 font-semibold mb-2">
                  Flight Number <span class="text-red-500">*</span>
                </label>
                <input
                  type="text"
                  name="flight_number"
                  id="flight_number"
                  class="w-full px-4 py-2 border rounded-lg"
                  placeholder="e.g., PK-309"
                  required
                  maxlength="9">
                <div class="error-feedback" id="flight_number-error"></div>
              </div>
            </div>

            <!-- Route Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- cities -->
              <div>
                <label class="block text-gray-700 font-semibold mb-2">Departure City <span class="text-red-500">*</span></label>
                <select name="departure_city" id="departure_city" class="w-full px-4 py-2 border rounded-lg" required>
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
              <div>
                <label class="block text-gray-700 font-semibold mb-2">Arrival City <span class="text-red-500">*</span></label>
                <select name="arrival_city" id="arrival_city" class="w-full px-4 py-2 border rounded-lg" required>
                  <option value="">Select City</option>
                  <option value="Jeddah">Jeddah</option>
                  <option value="Medina">Medina</option>
                </select>
                <div class="error-feedback" id="arrival_city-error"></div>
              </div>
            </div>

            <!-- Flight Stops -->
            <div class="border p-4 rounded-lg bg-gray-50">
              <div class="flex items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-700">Flight Stops</h3>
                <div class="ml-4">
                  <label class="inline-flex items-center">
                    <input type="radio" name="has_stops" value="0" class="mr-2" checked onchange="toggleStopsSection(false)">
                    <span>Direct Flight</span>
                  </label>
                  <label class="inline-flex items-center ml-4">
                    <input type="radio" name="has_stops" value="1" class="mr-2" onchange="toggleStopsSection(true)">
                    <span>Has Stops</span>
                  </label>
                </div>
              </div>

              <div id="stops-container" class="hidden space-y-4">
                <!-- Initial stop row -->
                <div class="stop-row grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label class="block text-gray-700 font-semibold mb-2">
                      Stop City <span class="text-red-500">*</span>
                    </label>
                    <input
                      type="text"
                      name="stop_city[]"
                      class="stop-city w-full px-4 py-2 border rounded-lg"
                      maxlength="12"
                      placeholder="e.g., Dubai">
                  </div>
                  <div>
                    <label class="block text-gray-700 font-semibold mb-2">
                      Stop Duration (hours) <span class="text-red-500">*</span>
                    </label>
                    <input
                      type="text"
                      name="stop_duration[]"
                      class="stop-duration-input w-full px-4 py-2 border rounded-lg"
                      placeholder="e.g., 4">
                  </div>
                </div>

                <div class="flex justify-end">
                  <button type="button" id="add-stop" class="px-4 py-2 bg-teal-500 text-white rounded-lg hover:bg-teal-600">
                    <i class="fas fa-plus mr-2"></i>Add Another Stop
                  </button>
                </div>
              </div>
            </div>

            <!-- Schedule and Duration -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div>
                <label class="block text-gray-700 font-semibold mb-2">
                  Departure Date <span class="text-red-500">*</span>
                </label>
                <input
                  type="date"
                  name="departure_date"
                  id="departure_date"
                  class="w-full px-4 py-2 border rounded-lg"
                  min="1940-01-01"
                  required
                  onkeydown="return false;">
                <div class="error-feedback" id="departure_date-error"></div>
              </div>
              <div>
                <label class="block text-gray-700 font-semibold mb-2">Departure Time <span class="text-red-500">*</span></label>
                <input
                  type="text"
                  name="departure_time"
                  id="departure_time"
                  class="w-full px-4 py-2 border rounded-lg"
                  placeholder="HH:MM (24-hour format)"
                  pattern="([01]?[0-9]|2[0-3]):[0-5][0-9]"
                  required>
                <small class="text-gray-500">Enter time in 24-hour format (00:00 to 23:59)</small>
                <div class="error-feedback" id="departure_time-error"></div>
              </div>
              <div>
                <label class="block text-gray-700 font-semibold mb-2">
                  Flight Duration (hours) <span class="text-red-500">*</span>
                </label>
                <input
                  type="number"
                  name="flight_duration"
                  id="flight_duration"
                  class="w-full px-4 py-2 border rounded-lg"
                  placeholder="e.g., 5.5"
                  step="0.1"
                  min="0"
                  max="8"
                  required>
                <div class="error-feedback" id="flight_duration-error"></div>
              </div>
            </div>

            <!-- Distance Field -->
            <div>
              <label class="block text-gray-700 font-semibold mb-2">
                Distance (km) <span class="text-red-500">*</span>
              </label>
              <input
                type="number"
                name="distance"
                id="distance"
                class="w-full px-4 py-2 border rounded-lg"
                placeholder="e.g., 3500"
                step="1"
                min="0"
                max="20000"
                required>
              <div class="error-feedback" id="distance-error"></div>
            </div>

            <!-- Return Flight Section -->
            <div class="border-t border-gray-200 pt-6 mt-6">
              <div class="mb-4">
                <h2 class="text-xl font-bold text-purple-700">
                  <i class="fas fa-plane-arrival mr-2"></i>Return Flight Details
                </h2>
              </div>

              <div class="flex items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-700">Journey Type</h3>
                <div class="ml-4">
                  <label class="inline-flex items-center">
                    <input type="radio" name="has_return" value="0" class="mr-2" checked onchange="toggleReturnSection(false)">
                    <span>One-way Flight</span>
                  </label>
                  <label class="inline-flex items-center ml-4">
                    <input type="radio" name="has_return" value="1" class="mr-2" onchange="toggleReturnSection(true)">
                    <span>Round Trip</span>
                  </label>
                </div>
              </div>

              <div id="return-container" class="hidden border p-4 rounded-lg bg-gray-50 space-y-6">
                <!-- Return Flight Details -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                  <div>
                    <label class="block text-gray-700 font-semibold mb-2">Return Airline <span class="text-red-500">*</span></label>
                    <select name="return_airline" id="return_airline" class="w-full px-4 py-2 border rounded-lg return-required">
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
                  <div>
                    <label class="block text-gray-700 font-semibold mb-2">Return Flight Number <span class="text-red-500">*</span></label>
                    <input type="text" name="return_flight_number" id="return_flight_number" class="w-full px-4 py-2 border rounded-lg return-required" placeholder="e.g., PK-310" maxlength="7">
                    <div class="error-feedback" id="return_flight_number-error"></div>
                  </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                  <div>
                    <label class="block text-gray-700 font-semibold mb-2">Return Date <span class="text-red-500">*</span></label>
                    <input type="date" name="return_date" id="return_date" class="w-full px-4 py-2 border rounded-lg return-required">
                    <div class="error-feedback" id="return_date-error"></div>
                  </div>
                  <div>
                    <label class="block text-gray-700 font-semibold mb-2">Return Time <span class="text-red-500">*</span></label>
                    <input type="text" name="return_time" id="return_time" class="w-full px-4 py-2 border rounded-lg return-required" placeholder="HH:MM (24-hour format)">
                    <div class="error-feedback" id="return_time-error"></div>
                  </div>
                  <div>
                    <label class="block text-gray-700 font-semibold mb-2">Return Flight Duration (hours) <span class="text-red-500">*</span></label>
                    <input
                      type="text"
                      name="return_flight_duration"
                      id="return_flight_duration"
                      class="w-full px-4 py-2 border rounded-lg return-required return-duration-input"
                      placeholder="e.g., 5.5">
                    <div class="error-feedback" id="return_flight_duration-error"></div>
                  </div>
                </div>

                <!-- Return Flight Stops -->
                <div class="mt-4">
                  <div class="flex items-center mb-4">
                    <h4 class="text-md font-semibold text-gray-700">Return Flight Stops</h4>
                    <div class="ml-4">
                      <label class="inline-flex items-center">
                        <input type="radio" name="has_return_stops" value="0" class="mr-2" checked onchange="toggleReturnStopsSection(false)">
                        <span>Direct Return Flight</span>
                      </label>
                      <label class="inline-flex items-center ml-4">
                        <input type="radio" name="has_return_stops" value="1" class="mr-2" onchange="toggleReturnStopsSection(true)">
                        <span>Has Stops</span>
                      </label>
                    </div>
                  </div>

                  <div id="return-stops-container" class="hidden space-y-4">
                    <!-- Initial return stop row -->
                    <div class="return-stop-row grid grid-cols-1 md:grid-cols-2 gap-6">
                      <div>
                        <label class="block text-gray-700 font-semibold mb-2">Return Stop City <span class="text-red-500">*</span></label>
                        <input type="text" name="return_stop_city[]" class="w-full px-4 py-2 border rounded-lg return-stop-city" placeholder="e.g., Dubai" maxlength="12">
                      </div>
                      <div>
                        <label class="block text-gray-700 font-semibold mb-2">Return Stop Duration (hours) <span class="text-red-500">*</span></label>
                        <input type="text" name="return_stop_duration[]" class="w-full px-4 py-2 border rounded-lg return-stop-duration" placeholder="e.g., 2">
                      </div>
                    </div>

                    <div class="flex justify-end">
                      <button type="button" id="add-return-stop" class="px-4 py-2 bg-teal-500 text-white rounded-lg hover:bg-teal-600">
                        <i class="fas fa-plus mr-2"></i>Add Another Return Stop
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Pricing Section -->
            <div class="border-t border-gray-200 pt-6 mt-6">
              <div class="mb-4">
                <h2 class="text-xl font-bold text-teal-700">
                  <i class="fas fa-tags mr-2"></i>Pricing Information
                </h2>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                  <label class="block text-gray-700 font-semibold mb-2">Economy Price (PKR) <span class="text-red-500">*</span></label>
                  <input type="number" name="economy_price" id="economy_price" class="w-full px-4 py-2 border rounded-lg economy-price" placeholder="242,250" required>
                  <div class="error-feedback" id="economy_price-error"></div>
                </div>
                <div>
                  <label class="block text-gray-700 font-semibold mb-2">Business Price (PKR) <span class="text-red-500">*</span></label>
                  <input type="number" name="business_price" id="business_price" class="w-full px-4 py-2 border rounded-lg business-price" placeholder="427,500" required>
                  <div class="error-feedback" id="business_price-error"></div>
                </div>
                <div>
                  <label class="block text-gray-700 font-semibold mb-2">First Class Price (PKR) <span class="text-red-500">*</span></label>
                  <input type="number" name="first_class_price" id="first_class_price" class="w-full px-4 py-2 border rounded-lg first-class-price" placeholder="712,500" required>
                  <div class="error-feedback" id="first_class_price-error"></div>
                </div>
              </div>
            </div>

            <!-- Seat Information -->
            <div class="border-t border-gray-200 pt-6 mt-6">
              <div class="mb-4">
                <h2 class="text-xl font-bold text-teal-700">
                  <i class="fas fa-chair mr-2"></i>Seat Information
                </h2>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                  <label class="block text-gray-700 font-semibold mb-2">Economy Seats <span class="text-red-500">*</span></label>
                  <input type="number" name="economy_seats" id="economy_seats" class="w-full px-4 py-2 border rounded-lg" placeholder="200" min="100" max="500" required>
                  <div class="error-feedback" id="economy_seats-error"></div>
                </div>
                <div>
                  <label class="block text-gray-700 font-semibold mb-2">Business Seats <span class="text-red-500">*</span></label>
                  <input type="number" name="business_seats" id="business_seats" class="w-full px-4 py-2 border rounded-lg" placeholder="30" min="10" max="100" required>
                  <div class="error-feedback" id="business_seats-error"></div>
                </div>
                <div>
                  <label class="block text-gray-700 font-semibold mb-2">First Class Seats <span class="text-red-500">*</span></label>
                  <input type="number" name="first_class_seats" id="first_class_seats" class="w-full px-4 py-2 border rounded-lg" placeholder="10" min="5" max="50" required>
                  <div class="error-feedback" id="first_class_seats-error"></div>
                </div>
              </div>
            </div>

            <!-- Flight Notes -->
            <div>
              <label class="block text-gray-700 font-semibold mb-2">Flight Notes (Optional)</label>
              <textarea name="flight_notes" id="flight_notes" class="w-full px-4 py-2 border rounded-lg" rows="3" placeholder="Any additional information about this flight"></textarea>
            </div>

            <!-- Submit Buttons -->
            <div class="flex gap-4">
              <button type="submit" id="submit-btn" class="bg-teal-600 text-white px-6 py-2 rounded-lghover:bg-teal-700">
                <i class="fas fa-save mr-2"></i> Save Flight
              </button>
              <button type="reset" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600">
                <i class="fas fa-times mr-2"></i>Reset
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>


  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Set default date to today
      const dateField = document.querySelector('input[name="departure_date"]');
      if (dateField) {
        const today = new Date();
        const formattedDate = today.toISOString().split('T')[0];
        dateField.value = formattedDate;
      }

      // Function to toggle stops section visibility
      window.toggleStopsSection = function(show) {
        const stopsContainer = document.getElementById('stops-container');
        if (show) {
          stopsContainer.classList.remove('hidden');
          document.querySelectorAll('input[name="stop_city[]"]').forEach(input => {
            input.setAttribute('required', 'required');
          });
          document.querySelectorAll('input[name="stop_duration[]"]').forEach(input => {
            input.setAttribute('required', 'required');
          });
        } else {
          stopsContainer.classList.add('hidden');
          document.querySelectorAll('input[name="stop_city[]"]').forEach(input => {
            input.removeAttribute('required');
          });
          document.querySelectorAll('input[name="stop_duration[]"]').forEach(input => {
            input.removeAttribute('required');
          });
        }
      };

      // Function to toggle return flight section visibility
      window.toggleReturnSection = function(show) {
        const returnContainer = document.getElementById('return-container');
        const returnRequiredFields = document.querySelectorAll('.return-required');

        if (show) {
          returnContainer.classList.remove('hidden');
          returnRequiredFields.forEach(field => {
            field.setAttribute('required', 'required');
          });
        } else {
          returnContainer.classList.add('hidden');
          returnRequiredFields.forEach(field => {
            field.removeAttribute('required');
          });

          // Also uncheck and disable return stops
          document.querySelector('input[name="has_return_stops"][value="0"]').checked = true;
          toggleReturnStopsSection(false);
        }
      };

      // Function to toggle return stops section visibility
      window.toggleReturnStopsSection = function(show) {
        const returnStopsContainer = document.getElementById('return-stops-container');

        if (show) {
          returnStopsContainer.classList.remove('hidden');
          document.querySelectorAll('input[name="return_stop_city[]"]').forEach(input => {
            input.setAttribute('required', 'required');
          });
          document.querySelectorAll('input[name="return_stop_duration[]"]').forEach(input => {
            input.setAttribute('required', 'required');
          });
        } else {
          returnStopsContainer.classList.add('hidden');
          document.querySelectorAll('input[name="return_stop_city[]"]').forEach(input => {
            input.removeAttribute('required');
          });
          document.querySelectorAll('input[name="return_stop_duration[]"]').forEach(input => {
            input.removeAttribute('required');
          });
        }
      };

      // Flight Number formatting
      const flightInput = document.getElementById('flight_number');
      flightInput.addEventListener('input', function() {
        let raw = this.value.toUpperCase().replace(/[^A-Z0-9-]/g, '');
        let formatted = '';

        // Extract letters first
        const letters = raw.match(/^[A-Z]{0,3}/)?.[0] || '';
        const numbers = raw.slice(letters.length).replace(/[^0-9-]/g, ''); // Only digits and dash after letters

        // Auto-insert dash if letters are 2 or 3
        if (letters.length >= 2) {
          const dashIndex = letters.length;
          formatted = letters + '-' + numbers.replace(/-/g, '');
        } else {
          formatted = letters;
        }

        // Apply formatted value
        this.value = formatted;
      });

      // Return flight number formatting
      const returnFlightInput = document.getElementById('return_flight_number');
      if (returnFlightInput) {
        returnFlightInput.addEventListener('input', function() {
          let raw = this.value.toUpperCase().replace(/[^A-Z0-9-]/g, '');
          let formatted = '';

          // Extract letters first
          const letters = raw.match(/^[A-Z]{0,3}/)?.[0] || '';
          const numbers = raw.slice(letters.length).replace(/[^0-9-]/g, ''); // Only digits and dash after letters

          // Auto-insert dash if letters are 2 or 3
          if (letters.length >= 2) {
            formatted = letters + '-' + numbers.replace(/-/g, '');
          } else {
            formatted = letters;
          }

          // Apply formatted value
          this.value = formatted;
        });
      }

      // Stop Duration formatting
      document.addEventListener('input', function(e) {
        if (e.target.classList.contains('stop-duration-input')) {
          let value = e.target.value;
          if (!/^[0-5]$/.test(value)) {
            e.target.value = value.replace(/[^0-5]/g, '');
          }
          if (parseInt(value) > 5) {
            e.target.value = "5";
          }
        }

        // Allow only letters in Stop City (max 12 characters)
        if (e.target.classList.contains('stop-city')) {
          e.target.value = e.target.value.replace(/[^a-zA-Z\s]/g, '').slice(0, 12);
        }
      });

      // Dynamically add more stops
      const addStopBtn = document.getElementById('add-stop');
      addStopBtn.addEventListener('click', function() {
        const stopRow = document.querySelector('.stop-row').cloneNode(true);
        stopRow.querySelectorAll('input').forEach(input => {
          input.value = '';
          if (document.querySelector('input[name="has_stops"]:checked').value === "1") {
            input.setAttribute('required', 'required');
          }
        });
        document.getElementById('stops-container').insertBefore(stopRow, this.parentElement);
      });

      // Add event listener for the "Add Another Return Stop" button
      const addReturnStopBtn = document.getElementById('add-return-stop');
      if (addReturnStopBtn) {
        addReturnStopBtn.addEventListener('click', function() {
          const returnStopRow = document.querySelector('.return-stop-row').cloneNode(true);
          const inputs = returnStopRow.querySelectorAll('input');
          inputs.forEach(input => {
            input.value = '';
            if (document.querySelector('input[name="has_return_stops"]:checked').value === "1" &&
              document.querySelector('input[name="has_return"]:checked').value === "1") {
              input.setAttribute('required', 'required');
            }
          });
          document.getElementById('return-stops-container').insertBefore(returnStopRow, this.parentElement);
        });
      }

      // Return stop city and duration validation
      document.addEventListener('input', function(e) {
        // Validate Return Stop City (letters and spaces only, max length 12)
        if (e.target.classList.contains('return-stop-city')) {
          e.target.value = e.target.value.replace(/[^a-zA-Z\s]/g, '').slice(0, 12);
        }

        // Validate Return Stop Duration (only numbers 1-5)
        if (e.target.classList.contains('return-stop-duration')) {
          let value = e.target.value;
          if (!/^[0-5]$/.test(value)) {
            e.target.value = value.replace(/[^0-5]/g, '');
          }
          if (parseInt(value) > 5) {
            e.target.value = "5";
          }
        }
      });

      // Time format validation
      const timeInputs = document.querySelectorAll('input[name="departure_time"], input[name="return_time"]');
      timeInputs.forEach(input => {
        input.addEventListener('input', function(e) {
          let value = e.target.value;

          // Only allow digits and colon
          value = value.replace(/[^0-9:]/g, '');

          // Auto-add colon after 2 digits if not already there
          if (value.length === 2 && !value.includes(':')) {
            value += ':';
          }

          // Limit to 5 chars (HH:MM)
          if (value.length > 5) {
            value = value.substring(0, 5);
          }

          // Validate hours (00-23)
          if (value.includes(':') && value.split(':')[0].length === 2) {
            const hours = parseInt(value.split(':')[0]);
            if (hours > 23) {
              value = '23' + value.substring(2);
            }
          }

          // Update the input value
          e.target.value = value;
        });
      });

      // Auto-open date pickers
      const dateInputs = document.querySelectorAll('input[type="date"]');
      dateInputs.forEach(input => {
        input.addEventListener('focus', function() {
          this.showPicker && this.showPicker();
        });

        input.addEventListener('click', function() {
          this.showPicker && this.showPicker();
        });
      });

      // Handle return airline dropdown
      const returnAirlineSelect = document.querySelector('select[name="return_airline"]');
      if (returnAirlineSelect) {
        returnAirlineSelect.addEventListener('change', function() {
          if (this.value === 'same') {
            // Get the outbound airline value
            const outboundAirline = document.querySelector('select[name="airline_name"]').value;
            // Just show a message without actually changing the value
            if (outboundAirline) {
              Swal.fire({
                icon: 'info',
                title: 'Return Airline',
                text: 'Return airline will be set to: ' + outboundAirline,
                confirmButtonText: 'OK'
              });
            } else {
              Swal.fire({
                icon: 'warning',
                title: 'Outbound Airline Not Selected',
                text: 'Please select an outbound airline first',
                confirmButtonText: 'OK'
              });
              // Reset to empty option
              this.value = '';
            }
          }
        });
      }

      // Flight duration validation
      document.getElementById('flight_duration').addEventListener('input', function() {
        let inputValue = parseFloat(this.value);
        if (inputValue > 8) {
          this.value = 8;
          Swal.fire({
            icon: 'info',
            title: 'Flight Duration',
            text: 'Maximum flight duration is 8 hours',
            confirmButtonText: 'OK'
          });
        }
      });

      // Distance validation
      document.getElementById('distance').addEventListener('input', function() {
        let inputValue = parseInt(this.value);
        if (inputValue > 20000) {
          this.value = 20000;
          Swal.fire({
            icon: 'info',
            title: 'Flight Distance',
            text: 'Maximum flight distance is 20,000 km',
            confirmButtonText: 'OK'
          });
        }
      });

      // Form submission
      document.getElementById('flightForm').addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent actual form submission

        // Simple client-side validation
        let valid = true;
        let firstInvalidField = null;

        // Check required fields
        this.querySelectorAll('[required]').forEach(field => {
          if (!field.value) {
            valid = false;
            field.classList.add('is-invalid');
            const errorElement = document.getElementById(`${field.id}-error`);
            if (errorElement) {
              errorElement.style.display = 'block';
              errorElement.textContent = 'This field is required';
            }
            if (!firstInvalidField) firstInvalidField = field;
          } else {
            field.classList.remove('is-invalid');
            field.classList.add('is-valid');
            const errorElement = document.getElementById(`${field.id}-error`);
            if (errorElement) {
              errorElement.style.display = 'none';
            }
          }
        });

        // Check return date is after departure date when round trip is selected
        if (document.querySelector('input[name="has_return"]:checked').value === "1") {
          const departureDate = new Date(document.getElementById('departure_date').value);
          const returnDate = new Date(document.getElementById('return_date').value);

          if (returnDate <= departureDate) {
            valid = false;
            const returnDateField = document.getElementById('return_date');
            const returnDateError = document.getElementById('return_date-error');

            returnDateField.classList.add('is-invalid');
            returnDateError.style.display = 'block';
            returnDateError.textContent = 'Return date must be after departure date';

            if (!firstInvalidField) firstInvalidField = returnDateField;
          }
        }

        if (!valid) {
          // Scroll to first invalid field
          if (firstInvalidField) {
            firstInvalidField.scrollIntoView({
              behavior: 'smooth',
              block: 'center'
            });
          }

          Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            text: 'Please fix all errors before submitting',
            confirmButtonText: 'OK'
          });
        } else {
          // Show success message (in a real app, this would submit to server)
          Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: 'Flight added successfully',
            confirmButtonText: 'OK'
          }).then((result) => {
            if (result.isConfirmed) {
              this.reset();
              // In a real application, you might redirect: window.location.href = 'view-flight.html';
            }
          });
        }
      });

      // Mobile menu toggle
      const menuBtn = document.getElementById('menu-btn');
      if (menuBtn) {
        menuBtn.addEventListener('click', function() {
          const sidebar = document.querySelector('.w-64');
          sidebar.classList.toggle('hidden');
          sidebar.classList.toggle('md:block');
        });
      }
    });
  </script>
