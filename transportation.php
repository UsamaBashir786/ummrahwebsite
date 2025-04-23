<?php
require_once 'config/db.php';
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Transportation - UmrahFlights</title>
  <!-- Include Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <?php include 'includes/css-links.php' ?>
  <style>
    body {
      margin-top: 65px !important;
    }
  </style>
</head>

<body class="bg-gray-50">
  <!-- Navbar -->
  <?php include 'includes/navbar.php'; ?>

  <section class="py-12 px-4">
    <div class="container mx-auto max-w-6xl">
      <h2 class="text-3xl font-bold text-center text-gray-800 mb-8">Transportation for Your Umrah Journey</h2>

      <!-- Filter Form -->
      <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label for="transportType" class="block text-sm font-medium text-gray-700 mb-1">Transport Type</label>
            <select class="w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" id="transportType">
              <option value="">All Types</option>
              <option value="Bus">Bus</option>
              <option value="Private Car">Private Car</option>
              <option value="Taxi">Taxi</option>
            </select>
          </div>

          <div>
            <label for="city" class="block text-sm font-medium text-gray-700 mb-1">City</label>
            <select class="w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" id="city">
              <option value="">Select City</option>
              <option value="Jeddah">Jeddah</option>
              <option value="Madinah">Madinah</option>
              <option value="Mecca">Mecca</option>
            </select>
          </div>

          <div>
            <label for="travelDate" class="block text-sm font-medium text-gray-700 mb-1">Travel Date</label>
            <input type="date" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" id="travelDate">
          </div>
        </div>
      </div>

      <!-- Transportation List -->
      <div class="space-y-4">
        <!-- Transport Card 1 -->
        <div class="bg-white rounded-lg shadow-md p-4 hover:shadow-lg transition duration-300">
          <div class="flex flex-col md:flex-row justify-between items-center">
            <div class="flex flex-col md:flex-row items-center mb-4 md:mb-0">
              <img src="assets/img/hero.jpg" alt="Bus" class="w-24 h-24 rounded-lg object-cover mr-0 md:mr-4 mb-4 md:mb-0">
              <div>
                <h5 class="text-xl font-bold text-gray-800 mb-1">Luxury Bus - Jeddah to Mecca</h5>
                <p class="text-gray-600 text-sm">Capacity: 40 passengers | Air Conditioned</p>
                <p class="text-gray-600 text-sm">Travel Date: Available Daily</p>
              </div>
            </div>
            <div class="flex flex-col items-center md:items-end">
              <div class="text-green-600 font-bold text-xl mb-2">Rs.5,000</div>
              <a href="#" class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-md transition duration-300 ease-in-out">Book Now</a>
            </div>
          </div>
        </div>

        <!-- Transport Card 2 -->
        <div class="bg-white rounded-lg shadow-md p-4 hover:shadow-lg transition duration-300">
          <div class="flex flex-col md:flex-row justify-between items-center">
            <div class="flex flex-col md:flex-row items-center mb-4 md:mb-0">
              <img src="assets/img/hero.jpg" alt="Private Car" class="w-24 h-24 rounded-lg object-cover mr-0 md:mr-4 mb-4 md:mb-0">
              <div>
                <h5 class="text-xl font-bold text-gray-800 mb-1">Private Car - Madinah to Mecca</h5>
                <p class="text-gray-600 text-sm">Capacity: 4 passengers | Sedan</p>
                <p class="text-gray-600 text-sm">Travel Date: Available Daily</p>
              </div>
            </div>
            <div class="flex flex-col items-center md:items-end">
              <div class="text-green-600 font-bold text-xl mb-2">Rs.15,000</div>
              <a href="#" class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-md transition duration-300 ease-in-out">Book Now</a>
            </div>
          </div>
        </div>

        <!-- Transport Card 3 -->
        <div class="bg-white rounded-lg shadow-md p-4 hover:shadow-lg transition duration-300">
          <div class="flex flex-col md:flex-row justify-between items-center">
            <div class="flex flex-col md:flex-row items-center mb-4 md:mb-0">
              <img src="assets/img/hero.jpg" alt="Taxi" class="w-24 h-24 rounded-lg object-cover mr-0 md:mr-4 mb-4 md:mb-0">
              <div>
                <h5 class="text-xl font-bold text-gray-800 mb-1">Taxi - Jeddah to Madinah</h5>
                <p class="text-gray-600 text-sm">Capacity: 4 passengers | Standard Taxi</p>
                <p class="text-gray-600 text-sm">Travel Date: Available Daily</p>
              </div>
            </div>
            <div class="flex flex-col items-center md:items-end">
              <div class="text-green-600 font-bold text-xl mb-2">Rs.10,000</div>
              <a href="#" class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-md transition duration-300 ease-in-out">Book Now</a>
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