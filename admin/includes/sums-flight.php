<!-- Stats Section -->
<div class="grid grid-cols-1 sm:grid-cols-1 lg:grid-cols-2 xl:grid-cols-2 gap-6 mb-6">
  <!-- Total Flights -->
  <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-6 border border-blue-200 shadow-sm">
    <div class="flex items-center">
      <div class="rounded-full bg-blue-500 p-3 mr-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M17.8 19.2L16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z"></path>
        </svg>
      </div>
      <div>
        <p class="text-sm text-gray-600 font-medium">Total Flights</p>
        <h3 class="text-2xl font-bold text-gray-800"><?php echo $totalFlights; ?></h3>
      </div>
    </div>
  </div>

  <!-- One-way Flights -->
  <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-6 border border-green-200 shadow-sm">
    <div class="flex items-center">
      <div class="rounded-full bg-green-500 p-3 mr-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M17 8h2a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2h-2v4l-4-4H9a2 2 0 0 1-2-2v-1"></path>
          <path d="M7 16V8a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v2"></path>
        </svg>
      </div>
      <div>
        <p class="text-sm text-gray-600 font-medium">One-way Flights</p>
        <h3 class="text-2xl font-bold text-gray-800">
          <?php echo count(array_filter($flights, function ($flight) {
            return $flight['has_return'] == 0;
          })); ?>
        </h3>
      </div>
    </div>
  </div>

  <!-- Round Trip Flights -->
  <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-6 border border-purple-200 shadow-sm">
    <div class="flex items-center">
      <div class="rounded-full bg-purple-500 p-3 mr-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M7 16l-4-4m0 0l4-4m-4 4h18"></path>
          <path d="M21 10l-4-4m0 0l-4 4m4-4v14"></path>
        </svg>
      </div>
      <div>
        <p class="text-sm text-gray-600 font-medium">Round Trip Flights</p>
        <h3 class="text-2xl font-bold text-gray-800">
          <?php echo count(array_filter($flights, function ($flight) {
            return $flight['has_return'] == 1;
          })); ?>
        </h3>
      </div>
    </div>
  </div>

  <!-- Direct Flights -->
  <div class="bg-gradient-to-br from-amber-50 to-amber-100 rounded-xl p-6 border border-amber-200 shadow-sm">
    <div class="flex items-center">
      <div class="rounded-full bg-amber-500 p-3 mr-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 8c-2.8 0-5 2.2-5 5s2.2 5 5 5 5-2.2 5-5-2.2-5-5-5z"></path>
          <path d="M12 3V1"></path>
          <path d="M12 23v-2"></path>
          <path d="M3.64 5.64l1.42-1.42"></path>
          <path d="M18.94 19.94l1.42-1.42"></path>
          <path d="M1 13h2"></path>
          <path d="M21 13h2"></path>
          <path d="M4.6 19.4l1.4-1.4"></path>
          <path d="M18 6l-1.4 1.4"></path>
        </svg>
      </div>
      <div>
        <p class="text-sm text-gray-600 font-medium">Direct Flights</p>
        <h3 class="text-2xl font-bold text-gray-800">
          <?php echo count(array_filter($flights, function ($flight) {
            return $flight['has_stops'] == 0;
          })); ?>
        </h3>
      </div>
    </div>
  </div>

  <!-- Total Seats -->
  <div class="bg-gradient-to-br from-teal-50 to-teal-100 rounded-xl p-6 border border-teal-200 shadow-sm">
    <div class="flex items-center">
      <div class="rounded-full bg-teal-500 p-3 mr-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M18 8h1a4 4 0 0 1 0 8h-1"></path>
          <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path>
          <line x1="6" y1="1" x2="6" y2="4"></line>
          <line x1="10" y1="1" x2="10" y2="4"></line>
          <line x1="14" y1="1" x2="14" y2="4"></line>
        </svg>
      </div>
      <div>
        <p class="text-sm text-gray-600 font-medium">Total Seats</p>
        <h3 class="text-2xl font-bold text-gray-800"><?php echo number_format($totalSeats); ?></h3>
      </div>
    </div>
  </div>

  <!-- Total Revenue Potential -->
  <div class="bg-gradient-to-br from-pink-50 to-pink-100 rounded-xl p-6 border border-pink-200 shadow-sm">
    <div class="flex items-center">
      <div class="rounded-full bg-pink-500 p-3 mr-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"></circle>
          <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
          <line x1="12" y1="17" x2="12" y2="17"></line>
        </svg>
      </div>
      <div>
        <p class="text-sm text-gray-600 font-medium">Revenue Potential</p>
        <h3 class="text-2xl font-bold text-gray-800">Rs.<?php echo number_format($totalRevenuePotential); ?></h3>
      </div>
    </div>
  </div>

  <!-- Average Economy Price -->
  <div class="bg-gradient-to-br from-cyan-50 to-cyan-100 rounded-xl p-6 border border-cyan-200 shadow-sm">
    <div class="flex items-center">
      <div class="rounded-full bg-cyan-500 p-3 mr-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
        </svg>
      </div>
      <div>
        <p class="text-sm text-gray-600 font-medium">Avg. Economy Price</p>
        <h3 class="text-2xl font-bold text-gray-800">Rs.<?php echo number_format($avgEconomyPrice); ?></h3>
      </div>
    </div>
  </div>

  <!-- Average Business Price -->
  <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 rounded-xl p-6 border border-indigo-200 shadow-sm">
    <div class="flex items-center">
      <div class="rounded-full bg-indigo-500 p-3 mr-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
        </svg>
      </div>
      <div>
        <p class="text-sm text-gray-600 font-medium">Avg. Business Price</p>
        <h3 class="text-2xl font-bold text-gray-800">Rs.<?php echo number_format($avgBusinessPrice); ?></h3>
      </div>
    </div>
  </div>

  <!-- Average First Class Price -->
  <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-xl p-6 border border-orange-200 shadow-sm">
    <div class="flex items-center">
      <div class="rounded-full bg-orange-500 p-3 mr-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
        </svg>
      </div>
      <div>
        <p class="text-sm text-gray-600 font-medium">Avg. First Class Price</p>
        <h3 class="text-2xl font-bold text-gray-800">Rs.<?php echo number_format($avgFirstClassPrice); ?></h3>
      </div>
    </div>
  </div>

  <!-- Total Stops -->
  <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-xl p-6 border border-red-200 shadow-sm">
    <div class="flex items-center">
      <div class="rounded-full bg-red-500 p-3 mr-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 2v4m0 12v4M2 12h4m12 0h4"></path>
        </svg>
      </div>
      <div>
        <p class="text-sm text-gray-600 font-medium">Total Stops</p>
        <h3 class="text-2xl font-bold text-gray-800"><?php echo $totalStops; ?></h3>
      </div>
    </div>
  </div>


  <!-- Seat Distribution -->
  <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-xl p-6 border border-yellow-200 shadow-sm">
    <div class="flex items-center">
      <div class="rounded-full bg-yellow-500 p-3 mr-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 2v20M2 12h20"></path>
        </svg>
      </div>
      <div>
        <p class="text-sm text-gray-600 font-medium">Seat Distribution</p>
        <h3 class="text-2xl font-bold text-gray-800">
          <?php echo round($totalEconomySeats / max($totalSeats, 1) * 100); ?>% Econ
        </h3>
        <p class="text-xs text-gray-500">
          Bus: <?php echo round($totalBusinessSeats / max($totalSeats, 1) * 100); ?>%,
          First: <?php echo round($totalFirstClassSeats / max($totalSeats, 1) * 100); ?>%
        </p>
      </div>
    </div>
  </div>
</div>