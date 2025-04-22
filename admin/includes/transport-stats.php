<?php
// Initialize stats arrays with default values
$taxi_stats = ['total_routes' => 0, 'avg_price' => '0.00'];
$rentacar_stats = ['total_routes' => 0, 'avg_price' => '0.00'];

// Fetch taxi stats
$result = $conn->query("SELECT COUNT(*) as total, AVG(camry_sonata_price + starex_staria_price + hiace_price)/3 as avg_price FROM taxi_routes");
if ($result) {
  $row = $result->fetch_assoc();
  $taxi_stats['total_routes'] = (int) $row['total'];
  $taxi_stats['avg_price'] = number_format($row['avg_price'] ?? 0, 2);
  $result->free();
} else {
  error_log("Error fetching taxi stats: " . $conn->error);
}

// Fetch rent-a-car stats
$result = $conn->query("SELECT COUNT(*) as total, AVG(gmc_16_19_price + gmc_22_23_price + coaster_price)/3 as avg_price FROM rentacar_routes");
if ($result) {
  $row = $result->fetch_assoc();
  $rentacar_stats['total_routes'] = (int) $row['total'];
  $rentacar_stats['avg_price'] = number_format($row['avg_price'] ?? 0, 2);
  $result->free();
} else {
  error_log("Error fetching rent-a-car stats: " . $conn->error);
}
?>
<section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6" aria-label="Transportation statistics">
  <div class="bg-gray-800 p-6 rounded-xl shadow-lg border border-gray-700 hover:shadow-xl transition-transform hover:scale-105">
    <h3 class="text-sm font-semibold text-gray-300">Total Taxi Routes</h3>
    <p class="text-2xl font-bold text-teal-400"><?php echo htmlspecialchars($taxi_stats['total_routes']); ?></p>
  </div>
  <div class="bg-gray-800 p-6 rounded-xl shadow-lg border border-gray-700 hover:shadow-xl transition-transform hover:scale-105">
    <h3 class="text-sm font-semibold text-gray-300">Avg Taxi Price (₨)</h3>
    <p class="text-2xl font-bold text-teal-400"><?php echo htmlspecialchars($taxi_stats['avg_price']); ?></p>
  </div>
  <div class="bg-gray-800 p-6 rounded-xl shadow-lg border border-gray-700 hover:shadow-xl transition-transform hover:scale-105">
    <h3 class="text-sm font-semibold text-gray-300">Total Rent-a-Car Routes</h3>
    <p class="text-2xl font-bold text-blue-400"><?php echo htmlspecialchars($rentacar_stats['total_routes']); ?></p>
  </div>
  <div class="bg-gray-800 p-6 rounded-xl shadow-lg border border-gray-700 hover:shadow-xl transition-transform hover:scale-105">
    <h3 class="text-sm font-semibold text-gray-300">Avg Rent-a-Car Price (₨)</h3>
    <p class="text-2xl font-bold text-blue-400"><?php echo htmlspecialchars($rentacar_stats['avg_price']); ?></p>
  </div>
</section>