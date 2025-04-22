<?php
require_once 'config/db.php';
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php' ?>
  <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
  <!-- Navbar -->
  <?php include 'includes/navbar.php'; ?>
  <!-- Hero Section -->
  <br><br><br>
  <section class="hero-section">
    <div class="hero-content">
      <h1>Experience the Sacred Journey of Umrah</h1>
      <p>Embark on a transformative spiritual journey with our comprehensive Umrah packages. Let us help you make the most of your pilgrimage experience with our tailored services.</p>
      <a href="#" class="explore-btn text-decoration-none">Explore Packages</a>
    </div>
  </section>
  <!-- Packages Section -->
  <section class="packages-section">
    <div class="container">
      <div class="text-center">
        <div class="section-subtitle">- Packages</div>
        <h2 class="section-title">Choose Your Umrah Package</h2>
      </div>
      <div class="row mt-4">
        <!-- Package 1 -->
        <div class="col-lg-4 col-md-6 mb-4">
          <div class="package-card">
            <div class="position-relative">
              <img src="assets/img/hero.jpg" alt="Budget Umrah Bliss">
              <span class="limited-offer">Limited Offer</span>
            </div>
            <div class="card-body">
              <div class="package-price">Rs245,000.00</div>
              <div class="package-title">Budget Umrah Bliss</div>
              <div class="package-location">Lahore - Jeddah</div>
              <ul class="package-features">
                <!-- <li><img src="path/to/icon1.png" alt="icon">Document Guide</li> -->
                <!-- <li><img src="path/to/icon2.png" alt="icon">Economy Class Flight</li> -->
                <!-- <li><img src="path/to/icon3.png" alt="icon">Local Meals</li> -->
                <!-- <li><img src="path/to/icon4.png" alt="icon">Visa Included</li> -->
              </ul>
              <a href="#" class="learn-more-btn">Learn More</a>
            </div>
          </div>
        </div>
        <!-- Package 2 -->
        <div class="col-lg-4 col-md-6 mb-4">
          <div class="package-card">
            <div class="position-relative">
              <img src="assets/img/hero.jpg" alt="Premium Spiritual Retreat">
              <span class="limited-offer">Limited Offer</span>
            </div>
            <div class="card-body">
              <div class="package-price">Rs375,000.00</div>
              <div class="package-title">Premium Spiritual Retreat</div>
              <div class="package-location">Islamabad - Madinah</div>
              <ul class="package-features">
                <!-- <li><img src="path/to/icon1.png" alt="icon">Document Guide</li> -->
                <!-- <li><img src="path/to/icon2.png" alt="icon">Business Class Flight</li> -->
                <!-- <li><img src="path/to/icon3.png" alt="icon">Local Meals</li> -->
                <!-- <li><img src="path/to/icon4.png" alt="icon">Visa Included</li> -->
              </ul>
              <a href="#" class="learn-more-btn">Learn More</a>
            </div>
          </div>
        </div>
        <!-- Package 3 -->
        <div class="col-lg-4 col-md-6 mb-4">
          <div class="package-card">
            <div class="position-relative">
              <img src="assets/img/hero.jpg" alt="Executive Umrah Experience">
              <span class="limited-offer">Limited Offer</span>
            </div>
            <div class="card-body">
              <div class="package-price">Rs500,000.00</div>
              <div class="package-title">Executive Umrah Experience</div>
              <div class="package-location">Karachi - Jeddah</div>
              <ul class="package-features">
                <!-- <li><img src="path/to/icon1.png" alt="icon">Document Guide</li>
                <li><img src="path/to/icon2.png" alt="icon">First Class Flight</li>
                <li><img src="path/to/icon3.png" alt="icon">Local Meals</li>
                <li><img src="path/to/icon4.png" alt="icon">Visa Included</li> -->
              </ul>
              <a href="#" class="learn-more-btn">Learn More</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <!-- Elevate Section -->
  <section class="features-section">
    <div class="container">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <div class="section-subtitle">- Features</div>
          <h2 class="section-title">Elevate Your Faith</h2>
        </div>
        <a href="#" class="view-packages">View Packages <span>→</span></a>
      </div>
      <div class="row align-items-center">
        <!-- Image -->
        <div class="col-lg-6 mb-4">
          <div class="feature-image">
            <img src="assets/img/hero.jpg" alt="Feature Image">
          </div>
        </div>
        <!-- Features in 3x2 Grid -->
        <div class="col-lg-6">
          <div class="row">
            <!-- Feature 1 -->
            <div class="col-6">
              <div class="feature-item">
                <img src="assets/img/hero.jpg" alt="Tawaf Icon">
                <div>
                  <h5>Tawaf</h5>
                  <p>Circumambulating the Kaaba in unity.</p>
                </div>
              </div>
            </div>
            <!-- Feature 2 -->
            <div class="col-6">
              <div class="feature-item">
                <img src="assets/img/hero.jpg" alt="Ihram Icon">
                <div>
                  <h5>Ihram</h5>
                  <p>Sacred attire signifying purity.</p>
                </div>
              </div>
            </div>
            <!-- Feature 3 -->
            <div class="col-6">
              <div class="feature-item">
                <img src="assets/img/hero.jpg" alt="Mina Icon">
                <div>
                  <h5>Mina</h5>
                  <p>Sacred desert valley for pilgrims.</p>
                </div>
              </div>
            </div>
            <!-- Feature 4 -->
            <div class="col-6">
              <div class="feature-item">
                <img src="assets/img/hero.jpg" alt="Jamarat Icon">
                <div>
                  <h5>Jamarat</h5>
                  <p>Symbolic act of rejecting Satan.</p>
                </div>
              </div>
            </div>
            <!-- Feature 5 -->
            <div class="col-6">
              <div class="feature-item">
                <img src="assets/img/hero.jpg" alt="Zam-Zam Icon">
                <div>
                  <h5>Zam-Zam</h5>
                  <p>Holy water with miraculous origins.</p>
                </div>
              </div>
            </div>
            <!-- Feature 6 -->
            <div class="col-6">
              <div class="feature-item">
                <img src="assets/img/hero.jpg" alt="Prayer Mat Icon">
                <div>
                  <h5>Prayer Mat</h5>
                  <p>Sacred space for performing Salah.</p>
                </div>
              </div>
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