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
  <section class="contact-section">
    <div class="container">
      <h2 class="section-title">Contact Us</h2>
      <div class="row">
        <!-- Contact Form -->
        <div class="col-lg-6 mb-4">
          <div class="contact-form">
            <h5>Get in Touch</h5>
            <div>
              <div class="mb-3">
                <label for="name" class="form-label">Full Name</label>
                <input type="text" class="form-control" id="name" placeholder="Enter your name" required>
              </div>
              <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" class="form-control" id="email" placeholder="Enter your email" required>
              </div>
              <div class="mb-3">
                <label for="message" class="form-label">Message</label>
                <textarea class="form-control" id="message" rows="5" placeholder="Enter your message" required></textarea>
              </div>
              <button type="button" class="submit-btn">Send Message</button>
            </div>
          </div>
        </div>
        <!-- Contact Info -->
        <div class="col-lg-6 mb-4">
          <div class="contact-info">
            <h5>Contact Information</h5>
            <p><img src="path/to/location-icon.png" alt="Location"> 123 Business Avenue, Karachi, Pakistan</p>
            <p><img src="path/to/phone-icon.png" alt="Phone"> <a href="tel:+923001234567">+92 300 1234567</a></p>
            <p><img src="path/to/email-icon.png" alt="Email"> <a href="mailto:info@umrahflights.com">info@umrahflights.com</a></p>
          </div>
          <div class="map-placeholder">
            Map Placeholder (Embed Google Map Here)
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