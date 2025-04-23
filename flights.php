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

<body class="pt-24 bg-gray-50">
  <?php include 'includes/navbar.php' ?>

  <section class="py-6 bg-white shadow-sm">
    <div class="container mx-auto px-4">
      <h4 class="text-xl font-semibold text-gray-800 mb-4">Millions of cheap flights. One simple search.</h4>

      <form class="flex flex-wrap gap-3 items-center">
        <div class="w-full sm:w-auto">
          <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent"
            id="departureCity" name="departureCity" placeholder="From" value="Karachi (KHI)">
        </div>

        <div class="w-full sm:w-auto">
          <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent"
            id="arrivalCity" name="arrivalCity" placeholder="To" value="Jeddah (JED)">
        </div>

        <div class="w-full sm:w-auto">
          <input type="date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent"
            id="departureDate" name="departureDate" placeholder="Depart">
        </div>

        <div class="w-full sm:w-auto">
          <input type="date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent"
            id="returnDate" name="returnDate" placeholder="Return">
        </div>

        <div class="w-full sm:w-auto">
          <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent"
            id="travellers" name="travellers">
            <option value="1 Adult, Economy">1 Adult, Economy</option>
            <option value="2 Adults, Economy">2 Adults, Economy</option>
            <option value="1 Adult, Business">1 Adult, Business</option>
          </select>
        </div>

        <button type="submit" class="w-full sm:w-auto bg-teal-500 hover:bg-teal-600 text-white font-medium py-2 px-6 rounded-lg transition duration-300">
          Search
        </button>
      </form>

      <div class="mt-3 flex flex-wrap gap-4">
        <div class="flex items-center">
          <input type="checkbox" id="nearbyAirportsFrom" class="w-4 h-4 text-teal-600 border-gray-300 rounded focus:ring-teal-500">
          <label for="nearbyAirportsFrom" class="ml-2 text-sm text-gray-700">Add nearby airports</label>
        </div>

        <div class="flex items-center">
          <input type="checkbox" id="nearbyAirportsTo" class="w-4 h-4 text-teal-600 border-gray-300 rounded focus:ring-teal-500">
          <label for="nearbyAirportsTo" class="ml-2 text-sm text-gray-700">Add nearby airports</label>
        </div>

        <div class="flex items-center">
          <input type="checkbox" id="directFlights" class="w-4 h-4 text-teal-600 border-gray-300 rounded focus:ring-teal-500">
          <label for="directFlights" class="ml-2 text-sm text-gray-700">Direct flights</label>
        </div>
      </div>
    </div>
  </section>

  <!-- ✈️ Flight Listing -->
  <section class="py-12">
    <div class="container mx-auto px-4">
      <h2 class="text-3xl font-bold text-gray-800 mb-8">Find Your Umrah Flight</h2>

      <div class="flex flex-col lg:flex-row gap-8">
        <!-- Filter Sidebar -->
        <div class="w-full lg:w-1/4">
          <div class="bg-white p-6 rounded-lg shadow-md">
            <h5 class="text-lg font-semibold text-gray-800 mb-4">Filter Flights</h5>

            <div class="mb-4">
              <label for="priceRange" class="block text-gray-700 font-medium mb-2">Price Range</label>
              <input type="range" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                id="priceRange" min="50000" max="500000" step="10000" value="500000">
              <p class="text-sm text-gray-500 mt-1">Up to Rs.<span id="priceValue">500,000</span></p>
            </div>

            <div class="mb-4">
              <label for="departureCitySide" class="block text-gray-700 font-medium mb-2">Departure City</label>
              <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                id="departureCitySide">
                <option value="">Select City</option>
                <option value="Karachi">Karachi</option>
                <option value="Lahore">Lahore</option>
                <option value="Islamabad">Islamabad</option>
              </select>
            </div>

            <div class="mb-4">
              <label for="airlineSide" class="block text-gray-700 font-medium mb-2">Airline</label>
              <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                id="airlineSide">
                <option value="">Select Airline</option>
                <option value="PIA">Pakistan International Airlines</option>
                <option value="Saudi">Saudia</option>
                <option value="Emirates">Emirates</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Flight List -->
        <div class="w-full lg:w-3/4">
          <!-- Flight Card 1 -->
          <div class="bg-white p-4 rounded-lg shadow-md mb-4 flex flex-col sm:flex-row justify-between items-center">
            <div class="flex flex-col sm:flex-row items-center mb-4 sm:mb-0">
              <img src="assets/img/hero.jpg" alt="Airline Logo" class="w-16 h-16 object-cover rounded-full mr-0 sm:mr-4 mb-4 sm:mb-0">
              <div>
                <h5 class="text-lg font-semibold text-gray-800">PIA - Karachi to Jeddah</h5>
                <p class="text-gray-600">Departure: 10:00 AM | Duration: 4h 30m</p>
                <p class="text-gray-600">Economy Class</p>
              </div>
            </div>

            <div class="text-center sm:text-right">
              <div class="text-2xl font-bold text-teal-600 mb-2">Rs.125,000</div>
              <a href="#" class="inline-block bg-teal-500 hover:bg-teal-600 text-white font-medium py-2 px-6 rounded-lg transition duration-300">
                Book Now
              </a>
            </div>
          </div>

          <!-- Flight Card 2 -->
          <div class="bg-white p-4 rounded-lg shadow-md mb-4 flex flex-col sm:flex-row justify-between items-center">
            <div class="flex flex-col sm:flex-row items-center mb-4 sm:mb-0">
              <img src="assets/img/hero.jpg" alt="Airline Logo" class="w-16 h-16 object-cover rounded-full mr-0 sm:mr-4 mb-4 sm:mb-0">
              <div>
                <h5 class="text-lg font-semibold text-gray-800">Saudia - Lahore to Madinah</h5>
                <p class="text-gray-600">Departure: 2:00 PM | Duration: 5h 10m</p>
                <p class="text-gray-600">Business Class</p>
              </div>
            </div>

            <div class="text-center sm:text-right">
              <div class="text-2xl font-bold text-teal-600 mb-2">Rs.250,000</div>
              <a href="#" class="inline-block bg-teal-500 hover:bg-teal-600 text-white font-medium py-2 px-6 rounded-lg transition duration-300">
                Book Now
              </a>
            </div>
          </div>

          <!-- Flight Card 3 -->
          <div class="bg-white p-4 rounded-lg shadow-md mb-4 flex flex-col sm:flex-row justify-between items-center">
            <div class="flex flex-col sm:flex-row items-center mb-4 sm:mb-0">
              <img src="assets/img/hero.jpg" alt="Airline Logo" class="w-16 h-16 object-cover rounded-full mr-0 sm:mr-4 mb-4 sm:mb-0">
              <div>
                <h5 class="text-lg font-semibold text-gray-800">Emirates - Islamabad to Jeddah</h5>
                <p class="text-gray-600">Departure: 8:00 PM | Duration: 6h 00m</p>
                <p class="text-gray-600">First Class</p>
              </div>
            </div>

            <div class="text-center sm:text-right">
              <div class="text-2xl font-bold text-teal-600 mb-2">Rs.400,000</div>
              <a href="#" class="inline-block bg-teal-500 hover:bg-teal-600 text-white font-medium py-2 px-6 rounded-lg transition duration-300">
                Book Now
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <?php include "includes/footer.php" ?>
  <?php include "includes/js-links.php" ?>

  <script>
    // Update price range display
    const priceRange = document.getElementById('priceRange');
    const priceValue = document.getElementById('priceValue');
    priceRange.addEventListener('input', () => {
      priceValue.textContent = parseInt(priceRange.value).toLocaleString();
    });
  </script>
</body>

</html>