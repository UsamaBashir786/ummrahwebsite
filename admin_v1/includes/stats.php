<?php
// Function to get dashboard statistics
function getDashboardStats()
{
  $conn = getConnection();
  $stats = [
    'total' => 0,
    'available' => 0,
    'sold' => 0,
    'in_transit' => 0,
    'total_change' => 0,
    'available_change' => 0,
    'sold_change' => 0,
    'in_transit_change' => 0,
  ];

  // Get current counts
  $totalQuery = "SELECT COUNT(*) as count FROM vehicles";
  $availableQuery = "SELECT COUNT(*) as count FROM vehicles WHERE status = 'available'";
  $soldQuery = "SELECT COUNT(*) as count FROM vehicles WHERE status = 'sold'";
  $transitQuery = "SELECT COUNT(*) as count FROM vehicles WHERE status = 'in transit'";

  // Get current month start and last month start dates
  $currentMonthStart = date('Y-m-01');
  $lastMonthStart = date('Y-m-01', strtotime('-1 month'));
  $lastMonthEnd = date('Y-m-t', strtotime('-1 month'));

  // Calculate changes from last month
  $totalChangeQuery = "SELECT 
        (SELECT COUNT(*) FROM vehicles WHERE created_at >= '$currentMonthStart') as current_count,
        (SELECT COUNT(*) FROM vehicles WHERE created_at >= '$lastMonthStart' AND created_at <= '$lastMonthEnd') as last_count";

  $availableChangeQuery = "SELECT 
        (SELECT COUNT(*) FROM vehicles WHERE status = 'available' AND created_at >= '$currentMonthStart') as current_count,
        (SELECT COUNT(*) FROM vehicles WHERE status = 'available' AND created_at >= '$lastMonthStart' AND created_at <= '$lastMonthEnd') as last_count";

  $soldChangeQuery = "SELECT 
        (SELECT COUNT(*) FROM vehicles WHERE status = 'sold' AND created_at >= '$currentMonthStart') as current_count,
        (SELECT COUNT(*) FROM vehicles WHERE status = 'sold' AND created_at >= '$lastMonthStart' AND created_at <= '$lastMonthEnd') as last_count";

  $transitChangeQuery = "SELECT 
        (SELECT COUNT(*) FROM vehicles WHERE status = 'in transit' AND created_at >= '$currentMonthStart') as current_count,
        (SELECT COUNT(*) FROM vehicles WHERE status = 'in transit' AND created_at >= '$lastMonthStart' AND created_at <= '$lastMonthEnd') as last_count";

  // Execute queries
  $result = $conn->query($totalQuery);
  if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $stats['total'] = $row['count'];
  }

  $result = $conn->query($availableQuery);
  if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $stats['available'] = $row['count'];
  }

  $result = $conn->query($soldQuery);
  if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $stats['sold'] = $row['count'];
  }

  $result = $conn->query($transitQuery);
  if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $stats['in_transit'] = $row['count'];
  }

  // Calculate percent changes
  $result = $conn->query($totalChangeQuery);
  if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $stats['total_change'] = calculatePercentChange($row['current_count'], $row['last_count']);
  }

  $result = $conn->query($availableChangeQuery);
  if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $stats['available_change'] = calculatePercentChange($row['current_count'], $row['last_count']);
  }

  $result = $conn->query($soldChangeQuery);
  if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $stats['sold_change'] = calculatePercentChange($row['current_count'], $row['last_count']);
  }

  $result = $conn->query($transitChangeQuery);
  if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $stats['in_transit_change'] = calculatePercentChange($row['current_count'], $row['last_count']);
  }

  $conn->close();
  return $stats;
}

// Helper function to calculate percent change
function calculatePercentChange($current, $previous)
{
  if ($previous == 0) {
    return $current > 0 ? 100 : 0;
  }

  return round((($current - $previous) / $previous) * 100);
}
?>

<!-- Dashboard Stats -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
  <?php
  // Get dashboard statistics
  $stats = getDashboardStats();
  ?>

  <div class="dashboard-card bg-white p-6">
    <div class="flex justify-between items-center">
      <div>
        <p class="text-gray-500 text-sm font-medium">Total Vehicles</p>
        <h3 class="text-3xl font-bold text-gray-800 mt-1"><?php echo number_format($stats['total']); ?></h3>
      </div>
      <div class="bg-indigo-100 rounded-full p-3">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </div>
    </div>
    <div class="mt-4 flex items-center">
      <?php if ($stats['total_change'] >= 0): ?>
        <span class="text-green-500 text-sm font-medium flex items-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
          </svg>
          <?php echo abs($stats['total_change']); ?>%
        </span>
      <?php else: ?>
        <span class="text-red-500 text-sm font-medium flex items-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
          </svg>
          <?php echo abs($stats['total_change']); ?>%
        </span>
      <?php endif; ?>
      <span class="text-gray-500 text-sm ml-1">from last month</span>
    </div>
  </div>

  <div class="dashboard-card bg-white p-6">
    <div class="flex justify-between items-center">
      <div>
        <p class="text-gray-500 text-sm font-medium">Available</p>
        <h3 class="text-3xl font-bold text-gray-800 mt-1"><?php echo number_format($stats['available']); ?></h3>
      </div>
      <div class="bg-green-100 rounded-full p-3">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </div>
    </div>
    <div class="mt-4 flex items-center">
      <?php if ($stats['available_change'] >= 0): ?>
        <span class="text-green-500 text-sm font-medium flex items-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
          </svg>
          <?php echo abs($stats['available_change']); ?>%
        </span>
      <?php else: ?>
        <span class="text-red-500 text-sm font-medium flex items-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
          </svg>
          <?php echo abs($stats['available_change']); ?>%
        </span>
      <?php endif; ?>
      <span class="text-gray-500 text-sm ml-1">from last month</span>
    </div>
  </div>

  <div class="dashboard-card bg-white p-6">
    <div class="flex justify-between items-center">
      <div>
        <p class="text-gray-500 text-sm font-medium">Sold</p>
        <h3 class="text-3xl font-bold text-gray-800 mt-1"><?php echo number_format($stats['sold']); ?></h3>
      </div>
      <div class="bg-purple-100 rounded-full p-3">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </div>
    </div>
    <div class="mt-4 flex items-center">
      <?php if ($stats['sold_change'] >= 0): ?>
        <span class="text-green-500 text-sm font-medium flex items-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
          </svg>
          <?php echo abs($stats['sold_change']); ?>%
        </span>
      <?php else: ?>
        <span class="text-red-500 text-sm font-medium flex items-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
          </svg>
          <?php echo abs($stats['sold_change']); ?>%
        </span>
      <?php endif; ?>
      <span class="text-gray-500 text-sm ml-1">from last month</span>
    </div>
  </div>

  <div class="dashboard-card bg-white p-6">
    <div class="flex justify-between items-center">
      <div>
        <p class="text-gray-500 text-sm font-medium">In Transit</p>
        <h3 class="text-3xl font-bold text-gray-800 mt-1"><?php echo number_format($stats['in_transit']); ?></h3>
      </div>
      <div class="bg-amber-100 rounded-full p-3">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </div>
    </div>
    <div class="mt-4 flex items-center">
      <?php if ($stats['in_transit_change'] >= 0): ?>
        <span class="text-green-500 text-sm font-medium flex items-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
          </svg>
          <?php echo abs($stats['in_transit_change']); ?>%
        </span>
      <?php else: ?>
        <span class="text-red-500 text-sm font-medium flex items-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
          </svg>
          <?php echo abs($stats['in_transit_change']); ?>%
        </span>
      <?php endif; ?>
      <span class="text-gray-500 text-sm ml-1">from last month</span>
    </div>
  </div>
</div>