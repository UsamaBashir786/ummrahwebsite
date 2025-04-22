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
  <section class="about-section">
    <div class="container">
      <h2 class="section-title">About UmrahFlights</h2>
      <div class="section-subtitle">Making your journey to the Holy Land easier and more comfortable.</div>
      <!-- Introduction -->
      <div class="about-content">
        <p>UmrahFlights is dedicated to providing seamless travel solutions for pilgrims embarking on their sacred Umrah journey. We understand the importance of this spiritual trip and strive to make it as smooth and comfortable as possible. From flights and hotels to transportation and packages, we offer comprehensive services tailored to your needs.</p>
        <p>Founded with a passion for serving the Umrah community, we aim to deliver exceptional customer service, competitive pricing, and a hassle-free booking experience. Let us be your trusted partner in this blessed journey.</p>
      </div>
      <!-- Mission -->
      <div class="mission-section">
        <h3>Our Mission</h3>
        <p>Our mission is to simplify the pilgrimage process by offering reliable, affordable, and high-quality travel services. We aim to support pilgrims at every step, ensuring they can focus on their spiritual journey while we take care of the logistics.</p>
      </div>
      <!-- Team -->
      <h3 class="section-title">Meet Our Team</h3>
      <div class="row">
        <div class="col-lg-4 col-md-6 mb-4">
          <div class="team-card">
            <img src="path/to/team-member1.jpg" alt="Team Member">
            <h5>Ahmed Khan</h5>
            <p>Founder & CEO</p>
          </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-4">
          <div class="team-card">
            <img src="path/to/team-member2.jpg" alt="Team Member">
            <h5>Fatima Ali</h5>
            <p>Travel Consultant</p>
          </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-4">
          <div class="team-card">
            <img src="path/to/team-member3.jpg" alt="Team Member">
            <h5>Omar Hassan</h5>
            <p>Customer Support Lead</p>
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