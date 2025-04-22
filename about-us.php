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
    .about-container {
      display: flex;
      flex-wrap: wrap;
      gap: 2rem;
      align-items: center;
      margin-bottom: 2rem;
    }

    .about-text {
      flex: 1;
      min-width: 300px;
    }

    .about-image {
      flex: 1;
      min-width: 300px;
    }

    .about-image img {
      width: 100%;
      height: 500px !important;
      border-radius: 8px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    @media (max-width: 768px) {
      .about-container {
        flex-direction: column;
      }

      .about-text,
      .about-image {
        width: 100%;
      }
    }
  </style>
</head>

<body>
  <!-- Navbar -->
  <?php include 'includes/navbar.php'; ?>

  <section class="about-section py-12 bg-gray-100">
    <div class="container mx-auto px-4">
      <!-- Introduction -->
      <div class="about-container">
        <div class="about-text">
          <h2 class="section-title text-3xl font-bold text-gray-800 mb-4">About UmrahFlights</h2>
          <p class="text-gray-600 leading-relaxed">
            UmrahFlights is dedicated to providing seamless travel solutions for pilgrims embarking on their sacred Umrah journey. We understand the importance of this spiritual trip and strive to make it as smooth and comfortable as possible. From flights and hotels to transportation and packages, we offer comprehensive services tailored to your needs. Founded with a passion for serving the Umrah community, we aim to deliver exceptional customer service, competitive pricing, and a hassle-free booking experience. Let us be your trusted partner in this blessed journey.
          </p>
        </div>
        <div class="about-image">
          <img src="assets/img/hero.jpg" alt="Umrah Pilgrimage" class="w-full h-auto">
        </div>
      </div>

    </div>
  </section>

  <!-- Footer -->
  <?php include 'includes/footer.php'; ?>
  <?php include 'includes/js-links.php' ?>
</body>

</html>