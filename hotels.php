<?php
require_once 'config/db.php';
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Umrah Hotel Finder</title>
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
      <h2 class="text-3xl font-bold text-center text-gray-800 mb-8">Find Hotels for Your Umrah Journey</h2>

      <!-- Search Form -->
      <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
          <div class="md:col-span-3">
            <label for="location" class="block text-sm font-medium text-gray-700 mb-1">Location</label>
            <select class="w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" id="location" required>
              <option value="" disabled selected>Select Location</option>
              <option value="Makkah">Makkah</option>
              <option value="Madinah">Madinah</option>
            </select>
          </div>
          <div class="md:col-span-2">
            <label for="checkInDate" class="block text-sm font-medium text-gray-700 mb-1">Check-In Date</label>
            <input type="date" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" id="checkInDate" required>
          </div>
          <div class="md:col-span-2">
            <label for="checkOutDate" class="block text-sm font-medium text-gray-700 mb-1">Check-Out Date</label>
            <input type="date" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" id="checkOutDate" required>
          </div>
          <div class="md:col-span-2">
            <label for="guests" class="block text-sm font-medium text-gray-700 mb-1">Guests</label>
            <input type="number" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" id="guests" min="1" value="1" required>
          </div>
          <div class="md:col-span-3 flex items-end">
            <button type="button" class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-md transition duration-300 ease-in-out">Search Hotels</button>
          </div>
        </div>
      </div>

      <!-- Hotel List -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Hotel Card 1 -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition duration-300">
          <div class="h-48 overflow-hidden">
            <img src="assets/img/hero.jpg" alt="Hotel 1" class="w-full h-full object-cover">
          </div>
          <div class="p-5">
            <h5 class="text-xl font-bold text-gray-800 mb-2">The Lenox</h5>
            <div class="text-gray-600 mb-2">Makkah, 500m from Kaaba</div>
            <div class="text-green-600 font-bold mb-3">Rs.25,000/night</div>
            <ul class="mb-4 space-y-1">
              <li class="flex items-center text-sm text-gray-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Free Wi-Fi
              </li>
              <li class="flex items-center text-sm text-gray-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Breakfast Included
              </li>
              <li class="flex items-center text-sm text-gray-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Shuttle Service
              </li>
            </ul>
            <a href="#" class="block text-center bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-md transition duration-300 ease-in-out">Book Now</a>
          </div>
        </div>

        <!-- Hotel Card 2 -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition duration-300">
          <div class="h-48 overflow-hidden">
            <img src="assets/img/hero.jpg" alt="Hotel 2" class="w-full h-full object-cover">
          </div>
          <div class="p-5">
            <h5 class="text-xl font-bold text-gray-800 mb-2">Madinah Oasis</h5>
            <div class="text-gray-600 mb-2">Madinah, 300m from Masjid Nabawi</div>
            <div class="text-green-600 font-bold mb-3">Rs.20,000/night</div>
            <ul class="mb-4 space-y-1">
              <li class="flex items-center text-sm text-gray-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Free Wi-Fi
              </li>
              <li class="flex items-center text-sm text-gray-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                24/7 Room Service
              </li>
              <li class="flex items-center text-sm text-gray-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Guided Tours
              </li>
            </ul>
            <a href="#" class="block text-center bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-md transition duration-300 ease-in-out">Book Now</a>
          </div>
        </div>

        <!-- Hotel Card 3 -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition duration-300">
          <div class="h-48 overflow-hidden">
            <img src="assets/img/hero.jpg" alt="Hotel 3" class="w-full h-full object-cover">
          </div>
          <div class="p-5">
            <h5 class="text-xl font-bold text-gray-800 mb-2">Kaaba View Hotel</h5>
            <div class="text-gray-600 mb-2">Makkah, 200m from Kaaba</div>
            <div class="text-green-600 font-bold mb-3">Rs.35,000/night</div>
            <ul class="mb-4 space-y-1">
              <li class="flex items-center text-sm text-gray-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Free Wi-Fi
              </li>
              <li class="flex items-center text-sm text-gray-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Breakfast Included
              </li>
              <li class="flex items-center text-sm text-gray-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Family Rooms
              </li>
            </ul>
            <a href="#" class="block text-center bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-md transition duration-300 ease-in-out">Book Now</a>
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