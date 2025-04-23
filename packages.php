<?php
require_once 'config/db.php';
session_start();

// Initialize filter variables
$filter_type = isset($_GET['type']) ? $_GET['type'] : '';
$filter_class = isset($_GET['flight_class']) ? $_GET['flight_class'] : '';
$filter_price_min = isset($_GET['price_min']) ? (int)$_GET['price_min'] : 0;
$filter_price_max = isset($_GET['price_max']) ? (int)$_GET['price_max'] : 0;

// Start building the query
$query = "SELECT * FROM umrah_packages WHERE 1=1";
$params = [];
$types = '';

// Apply filters
if (!empty($filter_type)) {
  $query .= " AND package_type = ?";
  $params[] = $filter_type;
  $types .= 's';
}

if (!empty($filter_class)) {
  $query .= " AND flight_class = ?";
  $params[] = $filter_class;
  $types .= 's';
}

if ($filter_price_min > 0) {
  $query .= " AND price >= ?";
  $params[] = $filter_price_min;
  $types .= 'i';
}

if ($filter_price_max > 0) {
  $query .= " AND price <= ?";
  $params[] = $filter_price_max;
  $types .= 'i';
}

// Add ordering
$query .= " ORDER BY created_at DESC";

// Prepare and execute the query
$stmt = $conn->prepare($query);
if (!empty($params)) {
  $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$packages = [];

if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $packages[] = $row;
  }
}
$stmt->close();

// Helper function to get badge colors based on package type
function getPackageTypeColor($type)
{
  switch ($type) {
    case 'vip':
      return 'bg-purple-500';
    case 'group':
      return 'bg-blue-500';
    case 'single':
    default:
      return 'bg-green-500';
  }
}

// Helper function to get badge colors based on flight class
function getFlightClassColor($class)
{
  switch ($class) {
    case 'first':
      return 'bg-yellow-100 text-yellow-800';
    case 'business':
      return 'bg-blue-100 text-blue-800';
    case 'economy':
    default:
      return 'bg-green-100 text-green-800';
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Umrah Packages - UmrahFlights</title>
  <!-- Include Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Include Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

  <!-- Page Header -->
  <section class="bg-green-600 text-white py-8">
    <div class="container mx-auto px-4">
      <h1 class="text-3xl font-bold mb-2">Umrah Packages</h1>
      <nav class="text-sm">
        <ol class="flex flex-wrap">
          <li class="flex items-center">
            <a href="index.php" class="hover:text-green-200">Home</a>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mx-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </li>
          <li class="text-green-200">Packages</li>
        </ol>
      </nav>
    </div>
  </section>

  <?php
  // Form section with clear filters button added - this replaces the existing filter form section
  ?>
  <!-- Packages Filter Section -->
  <section class="py-8">
    <div class="container mx-auto px-4 max-w-6xl">
      <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-xl font-bold text-gray-800">Filter Packages</h3>
          <?php if ($filter_type || $filter_class || $filter_price_min > 0 || $filter_price_max > 0): ?>
            <a href="packages.php" class="text-gray-600 hover:text-green-600 flex items-center text-sm transition duration-300">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
              Clear Filters
            </a>
          <?php endif; ?>
        </div>
        <form action="" method="GET" class="filter-form">
          <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
            <div class="md:col-span-3">
              <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Package Type</label>
              <select name="type" id="type" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                <option value="">All Types</option>
                <option value="single" <?php echo $filter_type === 'single' ? 'selected' : ''; ?>>Single</option>
                <option value="group" <?php echo $filter_type === 'group' ? 'selected' : ''; ?>>Group</option>
                <option value="vip" <?php echo $filter_type === 'vip' ? 'selected' : ''; ?>>VIP</option>
              </select>
            </div>
            <div class="md:col-span-3">
              <label for="flight_class" class="block text-sm font-medium text-gray-700 mb-1">Flight Class</label>
              <select name="flight_class" id="flight_class" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                <option value="">All Classes</option>
                <option value="economy" <?php echo $filter_class === 'economy' ? 'selected' : ''; ?>>Economy</option>
                <option value="business" <?php echo $filter_class === 'business' ? 'selected' : ''; ?>>Business</option>
                <option value="first" <?php echo $filter_class === 'first' ? 'selected' : ''; ?>>First Class</option>
              </select>
            </div>
            <div class="md:col-span-2">
              <label for="price_min" class="block text-sm font-medium text-gray-700 mb-1">Min Price (PKR)</label>
              <input type="number" name="price_min" id="price_min" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" value="<?php echo $filter_price_min; ?>" min="0">
            </div>
            <div class="md:col-span-2">
              <label for="price_max" class="block text-sm font-medium text-gray-700 mb-1">Max Price (PKR)</label>
              <input type="number" name="price_max" id="price_max" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" value="<?php echo $filter_price_max; ?>" min="0">
            </div>
            <div class="md:col-span-2 flex items-end">
              <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-md transition duration-300 ease-in-out">
                Filter
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </section>
  <!-- Packages List Section -->
  <section class="pb-12">
    <div class="container mx-auto px-4 max-w-6xl">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($packages)): ?>
          <div class="col-span-3">
            <div class="bg-blue-50 border border-blue-200 text-blue-700 px-6 py-5 rounded-lg">
              <h4 class="text-lg font-semibold mb-2">No packages found</h4>
              <p>No packages match your selected criteria. Please try different filters or check back later.</p>
            </div>
          </div>
        <?php else: ?>
          <?php foreach ($packages as $package): ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition duration-300">
              <div class="relative">
                <img src="<?php echo htmlspecialchars($package['package_image']); ?>" alt="<?php echo htmlspecialchars($package['title']); ?>" class="w-full h-48 object-cover">
                <div class="absolute top-3 right-3">
                  <span class="<?php echo getPackageTypeColor($package['package_type']); ?> text-white text-xs font-semibold px-2.5 py-1 rounded-full">
                    <?php echo ucfirst($package['package_type']); ?>
                  </span>
                </div>
              </div>
              <div class="p-5">
                <div class="text-green-600 font-bold text-lg mb-2">Rs<?php echo number_format($package['price'], 2); ?></div>
                <h3 class="text-xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($package['title']); ?></h3>
                <div class="mb-4">
                  <span class="inline-block <?php echo getFlightClassColor($package['flight_class']); ?> text-xs font-medium px-2.5 py-0.5 rounded">
                    <?php echo ucfirst($package['flight_class']); ?> Flight
                  </span>
                </div>
                <ul class="mb-4 space-y-1">
                  <?php
                  // Display inclusions if available
                  $inclusions = json_decode($package['inclusions'], true);
                  if (is_array($inclusions) && !empty($inclusions)):
                    // Only show up to 3 inclusions in the card
                    $displayed_inclusions = array_slice($inclusions, 0, 3);
                    foreach ($displayed_inclusions as $inclusion):
                      $icon_class = '';
                      switch ($inclusion) {
                        case 'flight':
                          $icon_class = 'fa-plane';
                          break;
                        case 'hotel':
                          $icon_class = 'fa-hotel';
                          break;
                        case 'transport':
                          $icon_class = 'fa-car';
                          break;
                        case 'guide':
                          $icon_class = 'fa-user';
                          break;
                        case 'vip_services':
                          $icon_class = 'fa-star';
                          break;
                        default:
                          $icon_class = 'fa-check';
                      }
                  ?>
                      <li class="flex items-center text-sm text-gray-600">
                        <i class="fas <?php echo $icon_class; ?> text-green-500 mr-2"></i>
                        <?php echo ucfirst(str_replace('_', ' ', $inclusion)); ?>
                      </li>
                    <?php
                    endforeach;
                    // Show a "more" indicator if there are more inclusions
                    if (count($inclusions) > 3):
                    ?>
                      <li class="text-sm text-gray-500 italic">
                        + <?php echo count($inclusions) - 3; ?> more inclusions
                      </li>
                  <?php
                    endif;
                  endif;
                  ?>
                </ul>
                <div class="text-gray-600 text-sm mb-4">
                  <?php echo substr(htmlspecialchars($package['description']), 0, 100) . '...'; ?>
                </div>
                <a href="package-details.php?id=<?php echo $package['id']; ?>" class="block text-center bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-md transition duration-300 ease-in-out">
                  View Details
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <?php include 'includes/footer.php'; ?>
  <?php include 'includes/js-links.php' ?>
</body>

</html>