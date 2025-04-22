<!-- <?php
$currentMonth = date('m');
$currentYear = date('Y');

$totalFlights = count($flights);
$totalOneWay = 0;
$totalRoundTrip = 0;
$totalDirectFlights = 0;
$totalFlightsWithStops = 0;
$totalJeddahFlights = 0;
$totalMedinaFlights = 0;
$upcomingFlights = 0;
$completedFlights = 0;
$thisMonthFlights = 0;
$totalEconomySeats = 0;
$totalBusinessSeats = 0;
$totalFirstClassSeats = 0;
$totalRevenue = 0;
$averageFlightDuration = 0;
$totalFlightDuration = 0;
$passengerCapacity = 0;
$airlinesCount = [];

foreach ($flights as $flight) {
  if ($flight['has_return'] == 0) {
    $totalOneWay++;
  } else {
    $totalRoundTrip++;
  }

  if ($flight['has_stops'] == 0) {
    $totalDirectFlights++;
  } else {
    $totalFlightsWithStops++;
  }

  if ($flight['arrival_city'] == 'Jeddah') {
    $totalJeddahFlights++;
  } else if ($flight['arrival_city'] == 'Medina') {
    $totalMedinaFlights++;
  }

  $departureDate = strtotime($flight['departure_date']);
  $today = strtotime(date('Y-m-d'));

  if ($departureDate > $today) {
    $upcomingFlights++;
  } else {
    $completedFlights++;
  }

  $flightMonth = date('m', strtotime($flight['departure_date']));
  $flightYear = date('Y', strtotime($flight['departure_date']));

  if ($flightMonth == $currentMonth && $flightYear == $currentYear) {
    $thisMonthFlights++;
  }

  $totalEconomySeats += $flight['economy_seats'];
  $totalBusinessSeats += $flight['business_seats'];
  $totalFirstClassSeats += $flight['first_class_seats'];

  $flightRevenue = ($flight['economy_price'] * $flight['economy_seats']) +
    ($flight['business_price'] * $flight['business_seats']) +
    ($flight['first_class_price'] * $flight['first_class_seats']);

  if ($flight['has_return'] == 1) {
    $flightRevenue *= 2;
  }

  $totalRevenue += $flightRevenue;

  $totalFlightDuration += $flight['flight_duration'];

  if (!isset($airlinesCount[$flight['airline_name']])) {
    $airlinesCount[$flight['airline_name']] = 1;
  } else {
    $airlinesCount[$flight['airline_name']]++;
  }

  $passengerCapacity += $flight['economy_seats'] + $flight['business_seats'] + $flight['first_class_seats'];
}

$averageFlightDuration = $totalFlights > 0 ? round($totalFlightDuration / $totalFlights, 1) : 0;

arsort($airlinesCount);
$topAirlines = array_slice($airlinesCount, 0, 3, true);
?>

<div class="mb-8">
  <h2 class="text-xl font-bold text-gray-800 mb-4">Flight Statistics Overview</h2>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="px-6 py-5 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
        <h3 class="font-semibold text-gray-800 flex items-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-600" viewBox="0 0 20 20" fill="currentColor">
            <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z" />
          </svg>
          Flight Status
        </h3>
      </div>
      <div class="p-6">
        <div class="flex flex-col gap-4">
          <div class="flex justify-between items-center">
            <span class="text-gray-600">Total Flights</span>
            <span class="font-bold text-blue-600"><?php echo $totalFlights; ?></span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-gray-600">Upcoming Flights</span>
            <span class="font-bold text-green-600"><?php echo $upcomingFlights; ?></span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-gray-600">Completed Flights</span>
            <span class="font-bold text-gray-600"><?php echo $completedFlights; ?></span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-gray-600">This Month</span>
            <span class="font-bold text-purple-600"><?php echo $thisMonthFlights; ?></span>
          </div>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="px-6 py-5 border-b border-gray-200 bg-gradient-to-r from-purple-50 to-pink-50">
        <h3 class="font-semibold text-gray-800 flex items-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-purple-600" viewBox="0 0 20 20" fill="currentColor">
            <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
          </svg>
          Flight Types
        </h3>
      </div>
      <div class="p-6">
        <div class="space-y-6">
          <div>
            <div class="flex justify-between mb-2">
              <span class="text-gray-600">One-way vs Round Trip</span>
            </div>
            <div class="h-2.5 w-full bg-gray-200 rounded-full">
              <?php $oneWayPercent = ($totalFlights > 0) ? ($totalOneWay / $totalFlights) * 100 : 0; ?>
              <div class="h-2.5 rounded-full bg-gradient-to-r from-blue-500 to-purple-500" style="width: <?php echo $oneWayPercent; ?>%"></div>
            </div>
            <div class="flex justify-between mt-1 text-xs font-medium">
              <span class="text-blue-600"><?php echo $totalOneWay; ?> One-way (<?php echo round($oneWayPercent); ?>%)</span>
              <span class="text-purple-600"><?php echo $totalRoundTrip; ?> Round Trip (<?php echo round(100 - $oneWayPercent); ?>%)</span>
            </div>
          </div>

          <div>
            <div class="flex justify-between mb-2">
              <span class="text-gray-600">Direct vs With Stops</span>
            </div>
            <div class="h-2.5 w-full bg-gray-200 rounded-full">
              <?php $directPercent = ($totalFlights > 0) ? ($totalDirectFlights / $totalFlights) * 100 : 0; ?>
              <div class="h-2.5 rounded-full bg-gradient-to-r from-green-500 to-yellow-500" style="width: <?php echo $directPercent; ?>%"></div>
            </div>
            <div class="flex justify-between mt-1 text-xs font-medium">
              <span class="text-green-600"><?php echo $totalDirectFlights; ?> Direct (<?php echo round($directPercent); ?>%)</span>
              <span class="text-yellow-600"><?php echo $totalFlightsWithStops; ?> With Stops (<?php echo round(100 - $directPercent); ?>%)</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="px-6 py-5 border-b border-gray-200 bg-gradient-to-r from-amber-50 to-orange-50">
        <h3 class="font-semibold text-gray-800 flex items-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-amber-600" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
          </svg>
          Destinations
        </h3>
      </div>
      <div class="p-6">
        <div class="space-y-4">
          <div class="flex justify-between items-center">
            <div class="flex items-center">
              <div class="w-3 h-3 rounded-full bg-green-500 mr-2"></div>
              <span class="text-gray-600">Jeddah</span>
            </div>
            <span class="font-semibold"><?php echo $totalJeddahFlights; ?> flights</span>
          </div>
          <div class="flex justify-between items-center">
            <div class="flex items-center">
              <div class="w-3 h-3 rounded-full bg-blue-500 mr-2"></div>
              <span class="text-gray-600">Medina</span>
            </div>
            <span class="font-semibold"><?php echo $totalMedinaFlights; ?> flights</span>
          </div>

          <div class="mt-4">
            <div class="relative pt-1">
              <div class="flex h-6 overflow-hidden text-xs bg-gray-200 rounded-full">
                <?php if ($totalFlights > 0): ?>
                  <div class="flex flex-col justify-center text-center text-white bg-green-500 shadow-none" style="width: <?php echo ($totalJeddahFlights / $totalFlights) * 100; ?>%">
                    <?php echo round(($totalJeddahFlights / $totalFlights) * 100); ?>%
                  </div>
                  <div class="flex flex-col justify-center text-center text-white bg-blue-500 shadow-none" style="width: <?php echo ($totalMedinaFlights / $totalFlights) * 100; ?>%">
                    <?php echo round(($totalMedinaFlights / $totalFlights) * 100); ?>%
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="px-6 py-5 border-b border-gray-200 bg-gradient-to-r from-green-50 to-emerald-50">
        <h3 class="font-semibold text-gray-800 flex items-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-green-600" viewBox="0 0 20 20" fill="currentColor">
            <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z" />
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd" />
          </svg>
          Revenue & Capacity
        </h3>
      </div>
      <div class="p-6">
        <div class="flex flex-col gap-4">
          <div class="flex justify-between items-center">
            <span class="text-gray-600">Potential Revenue</span>
            <span class="font-bold text-green-600">Rs. <?php echo number_format($totalRevenue); ?></span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-gray-600">Passenger Capacity</span>
            <span class="font-bold text-blue-600"><?php echo number_format($passengerCapacity); ?> seats</span>
          </div>
          <div class="pt-2 border-t border-gray-200">
            <div class="grid grid-cols-3 gap-2 text-center">
              <div class="bg-blue-50 rounded-lg p-2">
                <div class="text-xs text-gray-500">Economy</div>
                <div class="font-semibold text-blue-600"><?php echo number_format($totalEconomySeats); ?></div>
              </div>
              <div class="bg-purple-50 rounded-lg p-2">
                <div class="text-xs text-gray-500">Business</div>
                <div class="font-semibold text-purple-600"><?php echo number_format($totalBusinessSeats); ?></div>
              </div>
              <div class="bg-amber-50 rounded-lg p-2">
                <div class="text-xs text-gray-500">First Class</div>
                <div class="font-semibold text-amber-600"><?php echo number_format($totalFirstClassSeats); ?></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="px-6 py-5 border-b border-gray-200 bg-gradient-to-r from-cyan-50 to-blue-50">
        <h3 class="font-semibold text-gray-800 flex items-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-cyan-600" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M3 3a1 1 0 000 2h10a1 1 0 100-2H3zm0 4a1 1 0 000 2h6a1 1 0 100-2H3zm0 4a1 1 0 100 2h10a1 1 0 100-2H3z" clip-rule="evenodd" />
            <path d="M17 6a1 1 0 100-2h-1a1 1 0 100 2h1zm0 4a1 1 0 100-2h-1a1 1 0 100 2h1zm0 4a1 1 0 100-2h-1a1 1 0 100 2h1z" />
          </svg>
          Top Airlines
        </h3>
      </div>
      <div class="p-6">
        <div class="space-y-4">
          <?php $i = 0; ?>
          <?php foreach ($topAirlines as $airline => $count): ?>
            <?php
            $colors = [
              'bg-blue-500',
              'bg-purple-500',
              'bg-green-500',
              'bg-amber-500',
              'bg-red-500',
              'bg-emerald-500'
            ];
            $color = $colors[$i % count($colors)];
            ?>
            <div class="flex justify-between items-center">
              <div class="flex items-center">
                <div class="w-3 h-3 rounded-full <?php echo $color; ?> mr-2"></div>
                <span class="text-gray-600"><?php echo htmlspecialchars($airline); ?></span>
              </div>
              <span class="font-semibold"><?php echo $count; ?> flights</span>
            </div>
            <?php if ($i < count($topAirlines) - 1): ?>
              <div class="border-b border-gray-100"></div>
            <?php endif; ?>
            <?php $i++; ?>
          <?php endforeach; ?>

          <?php if (count($airlinesCount) > count($topAirlines)): ?>
            <div class="pt-2 text-center">
              <span class="text-sm text-gray-500">+<?php echo count($airlinesCount) - count($topAirlines); ?> more airlines</span>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="px-6 py-5 border-b border-gray-200 bg-gradient-to-r from-violet-50 to-purple-50">
        <h3 class="font-semibold text-gray-800 flex items-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-violet-600" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
          </svg>
          Flight Duration
        </h3>
      </div>
      <div class="p-6">
        <div class="text-center mb-6">
          <div class="text-gray-500 mb-2">Average Flight Duration</div>
          <div class="text-3xl font-bold text-violet-600"><?php echo $averageFlightDuration; ?></div>
          <div class="text-gray-500 text-sm">hours</div>
        </div>

        <div class="pt-4 border-t border-dashed border-gray-200">
          <div class="text-sm text-gray-600 mb-2">Flight Duration Range</div>
          <div class="flex justify-between items-center text-xs text-gray-500">
            <span>Shortest</span>
            <span>Longest</span>
          </div>
          <div class="h-2 w-full bg-gray-200 rounded-full mt-1 mb-1">
            <div class="h-2 rounded-full bg-gradient-to-r from-green-400 via-blue-500 to-purple-600" style="width: 100%"></div>
          </div>
          <?php
          $minDuration = PHP_INT_MAX;
          $maxDuration = 0;

          foreach ($flights as $flight) {
            $minDuration = min($minDuration, $flight['flight_duration']);
            $maxDuration = max($maxDuration, $flight['flight_duration']);
          }

          // Set defaults if no flights
          if ($totalFlights === 0) {
            $minDuration = 0;
            $maxDuration = 0;
          }
          ?>
          <div class="flex justify-between items-center text-xs font-semibold">
            <span class="text-green-600"><?php echo $minDuration; ?> hours</span>
            <span class="text-purple-600"><?php echo $maxDuration; ?> hours</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div> -->