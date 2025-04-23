<?php
require_once 'config/db.php';
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php' ?>
  <link rel="stylesheet" href="assets/css/style.css">
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body>
  <!-- Navbar -->
  <?php include 'includes/navbar.php'; ?>

  <section class="py-16 bg-gray-100 mt-16">
    <div class="container mx-auto px-4">
      <!-- Introduction -->
      <div class="flex flex-col md:flex-row gap-8 items-center mb-8">
        <div class="w-full md:w-1/2">
          <h2 class="text-3xl font-bold text-gray-800 mb-4">About UmrahFlights</h2>
          <p class="text-gray-600 leading-relaxed">
            UmrahFlights is dedicated to providing seamless travel solutions for pilgrims embarking on their sacred Umrah journey. We understand the importance of this spiritual trip and strive to make it as smooth and comfortable as possible. From flights and hotels to transportation and packages, we offer comprehensive services tailored to your needs. Founded with a passion for serving the Umrah community, we aim to deliver exceptional customer service, competitive pricing, and a hassle-free booking experience. Let us be your trusted partner in this blessed journey.
          </p>
        </div>
        <div class="w-full md:w-1/2">
          <img src="assets/img/hero.jpg" alt="Umrah Pilgrimage" class="w-full h-[500px] object-cover rounded-lg shadow-md">
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <?php include 'includes/footer.php'; ?>
  <?php include 'includes/js-links.php' ?>
</body>

</html>