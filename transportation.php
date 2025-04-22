<?php
require_once 'config/db.php';
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php' ?>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    body {
      background-color: #f8f9fa;
    }
  </style>
</head>

<body>
  <!-- Navbar -->
  <?php include 'includes/navbar.php'; ?>
  <section class="transportation-section">
    <div class="container">
      <h2 class="section-title">Transportation for Your Umrah Journey</h2>
      <!-- Filter Form -->
      <div class="filter-form">
        <div class="row">
          <div class="col-md-4 mb-3">
            <label for="transportType" class="form-label">Transport Type</label>
            <select class="form-select" id="transportType">
              <option value="">All Types</option>
              <option value="Bus">Bus</option>
              <option value="Private Car">Private Car</option>
              <option value="Taxi">Taxi</option>
            </select>
          </div>
          <div class="col-md-4 mb-3">
            <label for="city" class="form-label">City</label>
            <select class="form-select" id="city">
              <option value="">Select City</option>
              <option value="Jeddah">Jeddah</option>
              <option value="Madinah">Madinah</option>
              <option value="Mecca">Mecca</option>
            </select>
          </div>
          <div class="col-md-4 mb-3">
            <label for="travelDate" class="form-label">Travel Date</label>
            <input type="date" class="form-control" id="travelDate">
          </div>
        </div>
      </div>
      <!-- Transportation List -->
      <div class="row">
        <div class="col-12">
          <!-- Transport Card 1 -->
          <div class="transport-card">
            <div class="transport-info">
              <img src="path/to/bus-image.jpg" alt="Bus">
              <div class="transport-details">
                <h5>Luxury Bus - Jeddah to Mecca</h5>
                <p>Capacity: 40 passengers | Air Conditioned</p>
                <p>Travel Date: Available Daily</p>
              </div>
            </div>
            <div class="text-end">
              <div class="transport-price">Rs.5,000</div>
              <a href="#" class="book-btn">Book Now</a>
            </div>
          </div>
          <!-- Transport Card 2 -->
          <div class="transport-card">
            <div class="transport-info">
              <img src="path/to/private-car-image.jpg" alt="Private Car">
              <div class="transport-details">
                <h5>Private Car - Madinah to Mecca</h5>
                <p>Capacity: 4 passengers | Sedan</p>
                <p>Travel Date: Available Daily</p>
              </div>
            </div>
            <div class="text-end">
              <div class="transport-price">Rs.15,000</div>
              <a href="#" class="book-btn">Book Now</a>
            </div>
          </div>
          <!-- Transport Card 3 -->
          <div class="transport-card">
            <div class="transport-info">
              <img src="path/to/taxi-image.jpg" alt="Taxi">
              <div class="transport-details">
                <h5>Taxi - Jeddah to Madinah</h5>
                <p>Capacity: 4 passengers | Standard Taxi</p>
                <p>Travel Date: Available Daily</p>
              </div>
            </div>
            <div class="text-end">
              <div class="transport-price">Rs.10,000</div>
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