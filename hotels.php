<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php' ?>
  <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
  <!-- Navbar -->
  <?php include 'includes/navbar.php'; ?>
  <section class="hotels-section">
    <div class="container">
      <h2 class="section-title">Find Hotels for Your Umrah Journey</h2>
      <!-- Search Form -->
      <div class="search-form">
        <div class="row">
          <div class="col-md-3 mb-3">
            <label for="location" class="form-label">Location</label>
            <select class="form-select" id="location" required>
              <option value="" disabled selected>Select Location</option>
              <option value="Makkah">Makkah</option>
              <option value="Madinah">Madinah</option>
            </select>
          </div>
          <div class="col-md-2 mb-3">
            <label for="checkInDate" class="form-label">Check-In Date</label>
            <input type="date" class="form-control" id="checkInDate" required>
          </div>
          <div class="col-md-2 mb-3">
            <label for="checkOutDate" class="form-label">Check-Out Date</label>
            <input type="date" class="form-control" id="checkOutDate" required>
          </div>
          <div class="col-md-2 mb-3">
            <label for="guests" class="form-label">Guests</label>
            <input type="number" class="form-control" id="guests" min="1" value="1" required>
          </div>
          <div class="col-md-3 mb-3 d-flex align-items-end">
            <button type="button" class="search-btn">Search Hotels</button>
          </div>
        </div>
      </div>
      <!-- Hotel List -->
      <div class="row">
        <!-- Hotel Card 1 -->
        <div class="col-lg-4 col-md-6 mb-4">
          <div class="hotel-card">
            <div class="hotel-image">
              <img src="path/to/hotel-image1.jpg" alt="Hotel 1">
            </div>
            <div class="hotel-body">
              <h5 class="hotel-title">The Lenox</h5>
              <div class="hotel-location">Makkah, 500m from Kaaba</div>
              <div class="hotel-price">Rs.25,000/night</div>
              <ul class="hotel-amenities">
                <li>Free Wi-Fi</li>
                <li>Breakfast Included</li>
                <li>Shuttle Service</li>
              </ul>
              <a href="#" class="book-btn">Book Now</a>
            </div>
          </div>
        </div>
        <!-- Hotel Card 2 -->
        <div class="col-lg-4 col-md-6 mb-4">
          <div class="hotel-card">
            <div class="hotel-image">
              <img src="path/to/hotel-image2.jpg" alt="Hotel 2">
            </div>
            <div class="hotel-body">
              <h5 class="hotel-title">Madinah Oasis</h5>
              <div class="hotel-location">Madinah, 300m from Masjid Nabawi</div>
              <div class="hotel-price">Rs.20,000/night</div>
              <ul class="hotel-amenities">
                <li>Free Wi-Fi</li>
                <li>24/7 Room Service</li>
                <li>Guided Tours</li>
              </ul>
              <a href="#" class="book-btn">Book Now</a>
            </div>
          </div>
        </div>
        <!-- Hotel Card 3 -->
        <div class="col-lg-4 col-md-6 mb-4">
          <div class="hotel-card">
            <div class="hotel-image">
              <img src="path/to/hotel-image3.jpg" alt="Hotel 3">
            </div>
            <div class="hotel-body">
              <h5 class="hotel-title">Kaaba View Hotel</h5>
              <div class="hotel-location">Makkah, 200m from Kaaba</div>
              <div class="hotel-price">Rs.35,000/night</div>
              <ul class="hotel-amenities">
                <li>Free Wi-Fi</li>
                <li>Breakfast Included</li>
                <li>Family Rooms</li>
              </ul>
              <a href="#" class="book-btn">Book Now</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <?php include 'includes/footer.php'; ?>
  <?php include 'includes/js-links.php' ?>
</body>

</html>