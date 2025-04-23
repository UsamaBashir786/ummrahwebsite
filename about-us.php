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

      <!-- Our Mission -->
      <div class="bg-white p-8 rounded-lg shadow-md my-8">
        <h3 class="text-2xl font-bold text-gray-800 mb-4">Our Mission</h3>
        <p class="text-gray-600 leading-relaxed">
          Our mission is to simplify the Umrah journey for Muslims worldwide by providing reliable, affordable, and comprehensive travel services. We are committed to ensuring that every pilgrim has a spiritually fulfilling experience without the stress of travel logistics. Through continuous improvement and customer feedback, we strive to be the leading Umrah travel service provider.
        </p>
      </div>

      <!-- Our Values -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 my-8">
        <div class="bg-white p-6 rounded-lg shadow-md">
          <div class="flex items-center justify-center w-12 h-12 bg-teal-100 text-teal-600 rounded-full mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
          </div>
          <h4 class="text-xl font-semibold text-gray-800 mb-2">Trust</h4>
          <p class="text-gray-600">We build lasting relationships through transparent practices and reliable services.</p>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-md">
          <div class="flex items-center justify-center w-12 h-12 bg-teal-100 text-teal-600 rounded-full mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
          </div>
          <h4 class="text-xl font-semibold text-gray-800 mb-2">Integrity</h4>
          <p class="text-gray-600">We operate with the highest ethical standards and respect for our clients' spiritual journey.</p>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-md">
          <div class="flex items-center justify-center w-12 h-12 bg-teal-100 text-teal-600 rounded-full mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
          </div>
          <h4 class="text-xl font-semibold text-gray-800 mb-2">Excellence</h4>
          <p class="text-gray-600">We strive for excellence in every aspect of our service to exceed our clients' expectations.</p>
        </div>
      </div>

      <!-- Team Section (Optional) -->
      <div class="mt-12">
        <h3 class="text-2xl font-bold text-gray-800 mb-6">Our Team</h3>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
          <div class="bg-white rounded-lg shadow-md overflow-hidden text-center">
            <img src="assets/img/hero.jpg" alt="Team Member" class="w-full h-48 object-cover">
            <div class="p-4">
              <h5 class="text-lg font-semibold text-gray-800">Ahmed Khan</h5>
              <p class="text-gray-600 text-sm">Founder & CEO</p>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow-md overflow-hidden text-center">
            <img src="assets/img/hero.jpg" alt="Team Member" class="w-full h-48 object-cover">
            <div class="p-4">
              <h5 class="text-lg font-semibold text-gray-800">Sarah Ahmed</h5>
              <p class="text-gray-600 text-sm">Travel Specialist</p>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow-md overflow-hidden text-center">
            <img src="assets/img/hero.jpg" alt="Team Member" class="w-full h-48 object-cover">
            <div class="p-4">
              <h5 class="text-lg font-semibold text-gray-800">Muhammad Ali</h5>
              <p class="text-gray-600 text-sm">Customer Relations</p>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow-md overflow-hidden text-center">
            <img src="assets/img/hero.jpg" alt="Team Member" class="w-full h-48 object-cover">
            <div class="p-4">
              <h5 class="text-lg font-semibold text-gray-800">Fatima Hassan</h5>
              <p class="text-gray-600 text-sm">Accommodation Specialist</p>
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