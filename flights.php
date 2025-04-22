<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php' ?>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>

  </style>
</head>

<body>
  <?php include 'includes/navbar.php' ?>
  <style>

  </style>
  <section class="search-filter py-5">
    <div class="container">
      <h4 class="mb-4">Millions of cheap flights. One simple search.</h4>
      <form class="search-form d-flex flex-wrap align-items-center gap-3">
        <div class="form-group">
          <input type="text" class="form-control" id="departureCity" name="departureCity" placeholder="From" value="Karachi (KHI)">
        </div>
        <div class="form-group">
          <input type="text" class="form-control" id="arrivalCity" name="arrivalCity" placeholder="To" value="Jeddah (JED)">
        </div>
        <div class="form-group">
          <input type="date" class="form-control" id="departureDate" name="departureDate" placeholder="Depart">
        </div>
        <div class="form-group">
          <input type="date" class="form-control" id="returnDate" name="returnDate" placeholder="Return">
        </div>
        <div class="form-group">
          <select class="form-select" id="travellers" name="travellers">
            <option value="1 Adult, Economy">1 Adult, Economy</option>
            <option value="2 Adults, Economy">2 Adults, Economy</option>
            <option value="1 Adult, Business">1 Adult, Business</option>
          </select>
        </div>
        <button type="submit" class="btn btn-primary">Search</button>
      </form>
      <div class="options mt-3 d-flex gap-3">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="nearbyAirportsFrom">
          <label class="form-check-label" for="nearbyAirportsFrom">Add nearby airports</label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="nearbyAirportsTo">
          <label class="form-check-label" for="nearbyAirportsTo">Add nearby airports</label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="directFlights">
          <label class="form-check-label" for="directFlights">Direct flights</label>
        </div>
      </div>
    </div>
  </section>
  <!-- ✈️ Flight Listing -->
  <section class="flights-section py-5">
    <div class="container">
      <h2 class="section-title">Find Your Umrah Flight</h2>
      <div class="row">
        <!-- Filter Sidebar -->
        <div class="col-lg-3 col-md-4 mb-4">
          <div class="filter-sidebar">
            <h5>Filter Flights</h5>
            <div>
              <label for="priceRange" class="form-label">Price Range</label>
              <input type="range" class="form-range" id="priceRange" min="50000" max="500000" step="10000" value="500000">
              <p class="text-muted">Up to Rs.<span id="priceValue">500,000</span></p>
            </div>
            <div>
              <label for="departureCitySide" class="form-label">Departure City</label>
              <select class="form-select" id="departureCitySide">
                <option value="">Select City</option>
                <option value="Karachi">Karachi</option>
                <option value="Lahore">Lahore</option>
                <option value="Islamabad">Islamabad</option>
              </select>
            </div>
            <div>
              <label for="airlineSide" class="form-label">Airline</label>
              <select class="form-select" id="airlineSide">
                <option value="">Select Airline</option>
                <option value="PIA">Pakistan International Airlines</option>
                <option value="Saudi">Saudia</option>
                <option value="Emirates">Emirates</option>
              </select>
            </div>
          </div>
        </div>
        <!-- Flight List -->
        <div class="col-lg-9 col-md-8">
          <!-- Flight Card 1 -->
          <div class="flight-card">
            <div class="flight-info">
              <img src="path/to/airline-logo1.png" alt="Airline Logo">
              <div class="flight-details">
                <h5>PIA - Karachi to Jeddah</h5>
                <p>Departure: 10:00 AM | Duration: 4h 30m</p>
                <p>Economy Class</p>
              </div>
            </div>
            <div class="text-end">
              <div class="flight-price">Rs.125,000</div>
              <a href="#" class="book-btn">Book Now</a>
            </div>
          </div>

          <!-- Flight Card 2 -->
          <div class="flight-card">
            <div class="flight-info">
              <img src="path/to/airline-logo2.png" alt="Airline Logo">
              <div class="flight-details">
                <h5>Saudia - Lahore to Madinah</h5>
                <p>Departure: 2:00 PM | Duration: 5h 10m</p>
                <p>Business Class</p>
              </div>
            </div>
            <div class="text-end">
              <div class="flight-price">Rs.250,000</div>
              <a href="#" class="book-btn">Book Now</a>
            </div>
          </div>

          <!-- Flight Card 3 -->
          <div class="flight-card">
            <div class="flight-info">
              <img src="path/to/airline-logo3.png" alt="Airline Logo">
              <div class="flight-details">
                <h5>Emirates - Islamabad to Jeddah</h5>
                <p>Departure: 8:00 PM | Duration: 6h 00m</p>
                <p>First Class</p>
              </div>
            </div>
            <div class="text-end">
              <div class="flight-price">Rs.400,000</div>
              <a href="#" class="book-btn">Book Now</a>
            </div>
          </div>
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
    priceRange.addEventListener('input', () => {
      priceValue.textContent = priceRange.value.toLocaleString();
    });
  </script>
</body>

</html>