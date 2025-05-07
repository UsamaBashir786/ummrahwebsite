<?php
require_once '../config/db.php';
// Start admin session
session_name('admin_session');
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if admin is logged in
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
  header('Location: login.php');
  exit;
}

// Verify database connection
if (!$conn) {
  die("Database connection failed: " . mysqli_connect_error());
}

// Function to format large numbers into K, M, B suffixes
function formatNumber($number)
{
  if ($number === null || $number == 0 || !is_numeric($number)) {
    return 'N/A';
  }

  $number = (float)$number; // Ensure it's a number
  $suffixes = ['', 'K', 'M', 'B', 'T'];
  $index = 0;

  while ($number >= 1000 && $index < count($suffixes) - 1) {
    $number /= 1000;
    $index++;
  }

  // Round to 1 decimal place if needed, remove decimal if it's .0
  $formattedNumber = round($number, 1);
  if ($formattedNumber == round($formattedNumber)) {
    $formattedNumber = (int)$formattedNumber; // Remove .0
  }

  return $formattedNumber . $suffixes[$index];
}

// Map star_rating to display names
$star_rating_display = [
  'low_budget' => 'Low Budget Economy',
  '3_star' => '3 Star',
  '4_star' => '4 Star',
  '5_star' => '5 Star'
];

// Handle bulk delete
if (isset($_POST['bulk_delete']) && !empty($_POST['package_ids'])) {
  $ids = array_map('intval', $_POST['package_ids']);
  $ids_placeholder = implode(',', array_fill(0, count($ids), '?'));
  $stmt = $conn->prepare("DELETE FROM umrah_packages WHERE id IN ($ids_placeholder)");
  $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
  if ($stmt->execute()) {
    $_SESSION['success'] = "Selected packages deleted successfully!";
  } else {
    $_SESSION['error'] = "Error deleting packages: " . $conn->error;
  }
  $stmt->close();
  header('Location: view-packages.php');
  exit;
}

// Handle single delete
if (isset($_GET['delete'])) {
  $id = filter_var($_GET['delete'], FILTER_VALIDATE_INT);
  if ($id) {
    // Fetch package to delete image
    $stmt = $conn->prepare("SELECT package_image FROM umrah_packages WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
      // Delete package
      $stmt = $conn->prepare("DELETE FROM umrah_packages WHERE id = ?");
      $stmt->bind_param("i", $id);
      if ($stmt->execute()) {
        // Delete image file if not default
        if ($row['package_image'] !== 'default-package.jpg' && file_exists('../' . $row['package_image'])) {
          unlink('../' . $row['package_image']);
        }
        $_SESSION['success'] = "Package deleted successfully!";
      } else {
        $_SESSION['error'] = "Error deleting package: " . $conn->error;
      }
    } else {
      $_SESSION['error'] = "Package not found.";
    }
    $stmt->close();
  } else {
    $_SESSION['error'] = "Invalid package ID.";
  }
  header('Location: view-packages.php');
  exit;
}

// Handle CSV export
if (isset($_GET['export_csv'])) {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="umrah_packages.csv"');
  $output = fopen('php://output', 'w');
  fputcsv($output, ['ID', 'Title', 'Category', 'Makkah Nights', 'Madinah Nights', 'Total Days', 'Inclusions', 'Price (PKR)', 'Created At']);
  $stmt = $conn->prepare("SELECT id, title, star_rating, makkah_nights, madinah_nights, total_days, inclusions, price, created_at FROM umrah_packages");
  $stmt->execute();
  $result = $stmt->get_result();
  while ($package = $result->fetch_assoc()) {
    $inclusions = json_decode($package['inclusions'], true);
    fputcsv($output, [
      $package['id'],
      $package['title'],
      $star_rating_display[$package['star_rating']] ?? $package['star_rating'],
      $package['makkah_nights'],
      $package['madinah_nights'],
      $package['total_days'],
      implode(', ', array_map('ucfirst', is_array($inclusions) ? $inclusions : [])),
      formatNumber($package['price']),
      date('d M Y', strtotime($package['created_at']))
    ]);
  }
  $stmt->close();
  fclose($output);
  exit;
}

// Handle filters
$filters = [
  'star_rating' => filter_input(INPUT_GET, 'star_rating', FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => '/^(low_budget|3_star|4_star|5_star)?$/']]) ?: '',
  'price_min' => filter_input(INPUT_GET, 'price_min', FILTER_VALIDATE_FLOAT) ?: null,
  'price_max' => filter_input(INPUT_GET, 'price_max', FILTER_VALIDATE_FLOAT) ?: null,
  'inclusion' => filter_input(INPUT_GET, 'inclusion', FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => '/^(flight|hotel|transport|guide|vip_services)?$/']]) ?: '',
  'date_from' => filter_input(INPUT_GET, 'date_from', FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => '/^\d{4}-\d{2}-\d{2}$/']]) ?: '',
  'date_to' => filter_input(INPUT_GET, 'date_to', FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => '/^\d{4}-\d{2}-\d{2}$/']]) ?: ''
];

$where_clauses = [];
$params = [];
$types = '';

if ($filters['star_rating']) {
  $where_clauses[] = "star_rating = ?";
  $params[] = $filters['star_rating'];
  $types .= 's';
}

if ($filters['price_min'] !== null && $filters['price_min'] >= 0) {
  $where_clauses[] = "price >= ?";
  $params[] = $filters['price_min'];
  $types .= 'd';
}

if ($filters['price_max'] !== null && $filters['price_max'] <= 500000) {
  $where_clauses[] = "price <= ?";
  $params[] = $filters['price_max'];
  $types .= 'd';
}

if ($filters['inclusion']) {
  $where_clauses[] = "inclusions LIKE ?";
  $params[] = '%"' . $filters['inclusion'] . '"%';
  $types .= 's';
}

if ($filters['date_from']) {
  $where_clauses[] = "DATE(created_at) >= ?";
  $params[] = $filters['date_from'];
  $types .= 's';
}

if ($filters['date_to']) {
  $where_clauses[] = "DATE(created_at) <= ?";
  $params[] = $filters['date_to'];
  $types .= 's';
}

$query = "SELECT id, star_rating, title, makkah_nights, madinah_nights, total_days, inclusions, price, package_image, created_at FROM umrah_packages";
if (!empty($where_clauses)) {
  $query .= " WHERE " . implode(' AND ', $where_clauses);
}
$query .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
  $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$packages = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch statistics
$stats = [
  'total_packages' => 0,
  'by_star_rating' => ['low_budget' => 0, '3_star' => 0, '4_star' => 0, '5_star' => 0],
  'avg_price' => '0.00',
  'recent_packages' => 0
];

$result = $conn->query("SELECT COUNT(*) as total, COALESCE(AVG(price), 0) as avg_price FROM umrah_packages");
if ($result) {
  $row = $result->fetch_assoc();
  $stats['total_packages'] = $row['total'];
  $stats['avg_price'] = formatNumber((float)$row['avg_price']);
} else {
  error_log("Statistics query failed: " . $conn->error);
}

$result = $conn->query("SELECT star_rating, COUNT(*) as count FROM umrah_packages GROUP BY star_rating");
if ($result) {
  while ($row = $result->fetch_assoc()) {
    $stats['by_star_rating'][$row['star_rating']] = $row['count'];
  }
} else {
  error_log("Star rating query failed: " . $conn->error);
}

$result = $conn->query("SELECT COUNT(*) as recent FROM umrah_packages WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
if ($result) {
  $stats['recent_packages'] = $result->fetch_assoc()['recent'];
} else {
  error_log("Recent packages query failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>View Umrah Packages | UmrahFlights Admin</title>
  <!-- Tailwind CSS -->
  <link rel="stylesheet" href="../src/output.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <!-- DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.tailwindcss.min.css">
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
  <style>
    .modal {
      transition: opacity 0.3s ease-in-out;
    }

    .modal-hidden {
      opacity: 0;
      pointer-events: none;
    }

    .modal-visible {
      opacity: 1;
      pointer-events: auto;
    }

    .stats-card {
      transition: transform 0.2s;
    }

    .stats-card:hover {
      transform: scale(1.05);
    }

    .table-img {
      transition: transform 0.2s;
    }

    .table-img:hover {
      transform: scale(1.1);
    }

    [data-tooltip] {
      position: relative;
    }

    [data-tooltip]:hover:after {
      content: attr(data-tooltip);
      @apply absolute bg-gray-800 text-white text-xs rounded px-2 py-1 -top-8 left-1/2 transform -translate-x-1/2 z-10;
    }
  </style>
</head>

<body class="bg-gray-100">
  <?php include 'includes/sidebar.php'; ?>

  <!-- Main Content -->
  <div class="ml-0 md:ml-64 mt-10 px-4 sm:px-6 lg:px-8 transition-all duration-300">
    <!-- Top Navbar -->
    <nav class="bg-white shadow-lg rounded-lg p-5 mb-6">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <button id="sidebarToggle" class="text-gray-500 hover:text-gray-700 focus:outline-none md:hidden">
            <i class="fas fa-bars"></i>
          </button>
          <h4 class="text-lg font-semibold text-gray-800">
            <i class="fas fa-box text-indigo-600 mr-2"></i> Umrah Packages
          </h4>
        </div>

        <div class="flex items-center space-x-4">
          <!-- Notification -->
          <div class="relative">
            <button class="flex items-center text-gray-500 hover:text-gray-700 focus:outline-none">
              <i class="fas fa-bell text-xl"></i>
              <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                3
              </span>
            </button>
          </div>

          <!-- User Dropdown -->
          <div class="relative">
            <button id="userDropdownButton" class="flex items-center space-x-2 text-gray-700 hover:bg-indigo-50 rounded-lg px-3 py-2 focus:outline-none">
              <div class="rounded-full overflow-hidden" style="width: 32px; height: 32px;">
                <div class="bg-gray-200 w-full h-full flex items-center justify-center">
                  <i class="fas fa-user text-gray-500"></i>
                </div>
              </div>
              <span class="hidden md:inline text-sm font-medium">Admin User</span>
              <svg class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <ul id="userDropdownMenu" class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 hidden z-50">
              <li>
                <a class="flex items-center px-4 py-2 text-sm text-red-500 hover:bg-red-50" href="logout.php">
                  <i class="fas fa-sign-out-alt mr-2"></i> Logout
                </a>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </nav>

    <!-- Messages -->
    <?php if (isset($_SESSION['success'])): ?>
      <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-6 flex justify-between items-center" role="alert">
        <span><?php echo htmlspecialchars($_SESSION['success']); ?></span>
        <button class="text-green-700 hover:text-green-900 focus:outline-none focus:ring-2 focus:ring-green-500" onclick="this.parentElement.remove()" aria-label="Close alert">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
      <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-6 flex justify-between items-center" role="alert">
        <span><?php echo htmlspecialchars($_SESSION['error']); ?></span>
        <button class="text-red-700 hover:text-red-900 focus:outline-none focus:ring-2 focus:ring-red-500" onclick="this.parentElement.remove()" aria-label="Close alert">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Statistics Section -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-6 mb-6" aria-label="Package statistics">
      <div class="stats-card bg-white shadow-lg rounded-lg p-6 border-l-4 border-indigo-500">
        <div class="flex justify-between items-center">
          <div>
            <h3 class="text-lg font-semibold text-gray-800">Total Packages</h3>
            <p class="text-2xl font-bold text-indigo-600"><?php echo $stats['total_packages'] ?: 'N/A'; ?></p>
          </div>
          <div class="flex items-center justify-center w-12 h-12 rounded-full bg-indigo-100 text-indigo-500">
            <i class="fas fa-box text-xl"></i>
          </div>
        </div>
      </div>
      <div class="stats-card bg-white shadow-lg rounded-lg p-6 border-l-4 border-green-500">
        <div class="flex justify-between items-center">
          <div>
            <h3 class="text-lg font-semibold text-gray-800">Average Price</h3>
            <p class="text-2xl font-bold text-green-600">₨<?php echo $stats['avg_price'] ?: 'N/A'; ?></p>
          </div>
          <div class="flex items-center justify-center w-12 h-12 rounded-full bg-green-100 text-green-500">
            <i class="fas fa-tag text-xl"></i>
          </div>
        </div>
      </div>
      <div class="stats-card bg-white shadow-lg rounded-lg p-6 border-l-4 border-purple-500">
        <div class="flex justify-between items-center">
          <div>
            <h3 class="text-lg font-semibold text-gray-800">Recent Packages</h3>
            <p class="text-2xl font-bold text-purple-600"><?php echo $stats['recent_packages'] ?: 'N/A'; ?></p>
          </div>
          <div class="flex items-center justify-center w-12 h-12 rounded-full bg-purple-100 text-purple-500">
            <i class="fas fa-clock text-xl"></i>
          </div>
        </div>
      </div>
      <!-- <div class="stats-card bg-white shadow-lg rounded-lg p-6 border-l-4 border-yellow-500">
        <div class="flex justify-between items-center">
          <div>
            <h3 class="text-lg font-semibold text-gray-800">By Category</h3>
            <p class="text-sm text-gray-600">
              Low Budget: <?php echo $stats['by_star_rating']['low_budget'] ?: '0'; ?><br>
              3 Star: <?php echo $stats['by_star_rating']['3_star'] ?: '0'; ?><br>
              4 Star: <?php echo $stats['by_star_rating']['4_star'] ?: '0'; ?><br>
              5 Star: <?php echo $stats['by_star_rating']['5_star'] ?: '0'; ?>
            </p>
          </div>
          <div class="flex items-center justify-center w-12 h-12 rounded-full bg-yellow-100 text-yellow-500">
            <i class="fas fa-th-list text-xl"></i>
          </div>
        </div>
      </div> -->
    </div>

    <!-- Filters Section -->
    <div class="bg-white shadow-lg rounded-lg p-6 mb-6" aria-label="Package filters">
      <h3 class="text-lg font-semibold text-gray-800 mb-4">Filter Packages</h3>
      <form class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4" method="GET">
        <div>
          <label for="star_rating" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
          <select id="star_rating" name="star_rating" class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            <option value="">All Categories</option>
            <option value="low_budget" <?php echo $filters['star_rating'] === 'low_budget' ? 'selected' : ''; ?>>Low Budget Economy</option>
            <option value="3_star" <?php echo $filters['star_rating'] === '3_star' ? 'selected' : ''; ?>>3 Star</option>
            <option value="4_star" <?php echo $filters['star_rating'] === '4_star' ? 'selected' : ''; ?>>4 Star</option>
            <option value="5_star" <?php echo $filters['star_rating'] === '5_star' ? 'selected' : ''; ?>>5 Star</option>
          </select>
        </div>
        <div>
          <label for="inclusion" class="block text-sm font-medium text-gray-700 mb-1">Inclusion</label>
          <select id="inclusion" name="inclusion" class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            <option value="">Any Inclusion</option>
            <option value="flight" <?php echo $filters['inclusion'] === 'flight' ? 'selected' : ''; ?>>Flight</option>
            <option value="hotel" <?php echo $filters['inclusion'] === 'hotel' ? 'selected' : ''; ?>>Hotel</option>
            <option value="transport" <?php echo $filters['inclusion'] === 'transport' ? 'selected' : ''; ?>>Transport</option>
            <option value="guide" <?php echo $filters['inclusion'] === 'guide' ? 'selected' : ''; ?>>Guide</option>
            <option value="vip_services" <?php echo $filters['inclusion'] === 'vip_services' ? 'selected' : ''; ?>>VIP Services</option>
          </select>
        </div>
        <div>
          <label for="price_min" class="block text-sm font-medium text-gray-700 mb-1">Min Price (PKR)</label>
          <input type="number" id="price_min" name="price_min" value="<?php echo htmlspecialchars($filters['price_min'] !== null ? $filters['price_min'] : ''); ?>" min="0" step="0.01" class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
        </div>
        <div>
          <label for="price_max" class="block text-sm font-medium text-gray-700 mb-1">Max Price (PKR)</label>
          <input type="number" id="price_max" name="price_max" value="<?php echo htmlspecialchars($filters['price_max'] !== null ? $filters['price_max'] : ''); ?>" max="500000" step="0.01" class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
        </div>
        <div>
          <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">Created From</label>
          <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($filters['date_from']); ?>" class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
        </div>
        <div>
          <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">Created To</label>
          <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($filters['date_to']); ?>" class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
        </div>
        <div class="flex items-end space-x-2">
          <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            <i class="fas fa-search mr-2"></i>Apply Filters
          </button>
          <a href="view-packages.php" class="text-indigo-600 hover:text-indigo-900">Clear</a>
        </div>
      </form>
    </div>

    <!-- Packages Table -->
    <div class="bg-white shadow-lg rounded-lg p-6" aria-label="Umrah packages table">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-800">Umrah Packages</h3>
        <div class="flex flex-col sm:flex-row sm:space-x-2 space-y-2 sm:space-y-0">
          <a href="add-package.php" class="inline-flex items-center justify-center px-4 py-2 sm:px-3 sm:py-1.5 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 w-full sm:w-auto">
            <i class="fas fa-plus mr-2"></i>Add New Package
          </a>
          <a href="?export_csv=1" class="inline-flex items-center justify-center px-4 py-2 sm:px-3 sm:py-1.5 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-gray-600 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 w-full sm:w-auto">
            <i class="fas fa-download mr-2"></i>Export CSV
          </a>
        </div>
      </div>
      <form id="bulkDeleteForm" method="POST">
        <div class="flex justify-end mb-4">
          <button type="submit" name="bulk_delete" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 hidden" id="bulkDeleteBtn" onclick="return confirm('Are you sure you want to delete selected packages?');" aria-label="Delete selected packages">
            <i class="fas fa-trash mr-2"></i>Delete Selected
          </button>
        </div>
        <div class="overflow-x-auto">
          <table id="packagesTable" class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-gray-50">
                <th class="p-3"><input type="checkbox" id="selectAll" class="rounded text-indigo-600 focus:ring-indigo-500" aria-label="Select all packages"></th>
                <th class="p-3">#</th>
                <th class="p-3">Image</th>
                <th class="p-3">Title</th>
                <th class="p-3">Category</th>
                <th class="p-3">Makkah Nights</th>
                <th class="p-3">Madinah Nights</th>
                <th class="p-3">Total Days</th>
                <th class="p-3">Inclusions</th>
                <th class="p-3">Price (PKR)</th>
                <th class="p-3">Created At</th>
                <th class="p-3">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($packages)): ?>
                <tr>
                  <td colspan="12" class="p-3 text-center text-gray-600">No packages found.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($packages as $index => $package): ?>
                  <tr class="border-b hover:bg-gray-50">
                    <td class="p-3">
                      <input type="checkbox" name="package_ids[]" value="<?php echo $package['id']; ?>" class="package-checkbox rounded text-indigo-600 focus:ring-indigo-500" aria-label="Select package <?php echo htmlspecialchars($package['title']); ?>">
                    </td>
                    <td class="p-3"><?php echo $index + 1; ?></td>
                    <td class="p-3">
                      <img src="../<?php echo htmlspecialchars($package['package_image'] ?: 'assets/img/default-package.jpg'); ?>" alt="Package Image for <?php echo htmlspecialchars($package['title']); ?>" class="table-img w-12 h-12 object-cover rounded" aria-label="Package image">
                    </td>
                    <td class="p-3"><?php echo htmlspecialchars($package['title']); ?></td>
                    <td class="p-3"><?php echo htmlspecialchars($star_rating_display[$package['star_rating']] ?? $package['star_rating']); ?></td>
                    <td class="p-3"><?php echo htmlspecialchars($package['makkah_nights']); ?></td>
                    <td class="p-3"><?php echo htmlspecialchars($package['madinah_nights']); ?></td>
                    <td class="p-3"><?php echo htmlspecialchars($package['total_days']); ?></td>
                    <td class="p-3">
                      <?php
                      $inclusions = json_decode($package['inclusions'], true);
                      echo implode(', ', array_map('ucfirst', is_array($inclusions) ? $inclusions : []));
                      ?>
                    </td>
                    <td class="p-3"><?php echo formatNumber($package['price']); ?></td>
                    <td class="p-3"><?php echo date('d M Y', strtotime($package['created_at'])); ?></td>
                    <td class="p-3 flex space-x-2">
                      <a href="edit-package.php?id=<?php echo $package['id']; ?>" class="text-yellow-600 hover:text-yellow-800" data-tooltip="Edit Package" aria-label="Edit <?php echo htmlspecialchars($package['title']); ?>">
                        <i class="fas fa-edit"></i>
                      </a>
                      <a href="?delete=<?php echo $package['id']; ?>" class="text-red-600 hover:text-red-800" data-tooltip="Delete Package" onclick="return confirm('Are you sure you want to delete this package?');" aria-label="Delete <?php echo htmlspecialchars($package['title']); ?>">
                        <i class="fas fa-trash"></i>
                      </a>
                      <button class="view-details text-indigo-600 hover:text-indigo-800" data-id="<?php echo $package['id']; ?>" data-tooltip="View Details" aria-label="View details for <?php echo htmlspecialchars($package['title']); ?>">
                        <i class="fas fa-eye"></i>
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </form>
    </div>

    <!-- Details Modal -->
    <div id="detailsModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center modal modal-hidden" role="dialog" aria-labelledby="modalTitle" aria-hidden="true">
      <div class="bg-white rounded-lg p-6 w-full max-w-2xl max-h-[80vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
          <h3 id="modalTitle" class="text-lg font-semibold text-gray-800">Package Details</h3>
          <button id="closeModal" class="text-gray-600 hover:text-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-500" aria-label="Close modal">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div id="modalContent" class="text-gray-700">
          <!-- Package details will be loaded here via JavaScript -->
        </div>
      </div>
    </div>
  </div>

  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- DataTables JS -->
  <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/dataTables.tailwindcss.min.js"></script>

  <script>
    // User Dropdown Toggle
    const userDropdownButton = document.getElementById('userDropdownButton');
    const userDropdownMenu = document.getElementById('userDropdownMenu');

    if (userDropdownButton && userDropdownMenu) {
      userDropdownButton.addEventListener('click', function() {
        userDropdownMenu.classList.toggle('hidden');
      });

      // Close dropdown when clicking outside
      document.addEventListener('click', function(event) {
        if (!userDropdownButton.contains(event.target) && !userDropdownMenu.contains(event.target)) {
          userDropdownMenu.classList.add('hidden');
        }
      });
    }

    // Sidebar Toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebar-overlay');

    if (sidebarToggle && sidebar && sidebarOverlay) {
      sidebarToggle.addEventListener('click', function() {
        sidebar.classList.toggle('-translate-x-full');
        sidebarOverlay.classList.toggle('hidden');
      });
    }

    // Suppress DataTables alerts
    $.fn.dataTable.ext.errMode = 'none';

    $('#packagesTable').on('error.dt', function(e, settings, techNote, message) {
      console.error('DataTables Error:', message);
    });

    // DataTables Initialization
    $(document).ready(function() {
      const hasDataRows = $('#packagesTable tbody tr').not(':has(td[colspan="12"])').length > 0;

      $('#packagesTable').DataTable({
        pageLength: 10,
        order: [
          [10, 'desc']
        ],
        destroy: true,
        columns: hasDataRows ? [{
            data: 'checkbox',
            orderable: false,
            searchable: false
          },
          {
            data: 'index',
            searchable: false
          },
          {
            data: 'image',
            orderable: false,
            searchable: false
          },
          {
            data: 'title'
          },
          {
            data: 'category'
          },
          {
            data: 'makkah_nights'
          },
          {
            data: 'madinah_nights'
          },
          {
            data: 'total_days'
          },
          {
            data: 'inclusions'
          },
          {
            data: 'price'
          },
          {
            data: 'created_at'
          },
          {
            data: 'actions',
            orderable: false,
            searchable: false
          }
        ] : null,
        language: {
          search: 'Search packages:',
          searchPlaceholder: 'Enter title or category...',
          emptyTable: 'No packages available.'
        },
        deferRender: true
      });
    });

    // Modal Handling
    const modal = document.getElementById('detailsModal');
    const closeModal = document.getElementById('closeModal');
    const modalContent = document.getElementById('modalContent');

    // Use event delegation for view-details buttons
    document.querySelector('#packagesTable').addEventListener('click', function(e) {
      const button = e.target.closest('.view-details');
      if (button) {
        const id = button.getAttribute('data-id');
        const packages = <?php echo json_encode($packages); ?>;
        if (!packages || !Array.isArray(packages)) {
          console.error('Packages array is invalid:', packages);
          return;
        }
        const package = packages.find(p => p.id == parseInt(id));
        if (package) {
          let inclusions = [];
          try {
            inclusions = JSON.parse(package.inclusions || '[]').map(i => i.charAt(0).toUpperCase() + i.slice(1));
          } catch (error) {
            console.error('Error parsing inclusions:', error);
            inclusions = [];
          }
          modalContent.innerHTML = `
            <img src="../${package.package_image || 'assets/img/default-package.jpg'}" alt="${package.title || 'Package'}" class="w-full h-48 object-cover rounded-md mb-4">
            <p><strong>Title:</strong> ${package.title || 'N/A'}</p>
            <p><strong>Category:</strong> ${<?php echo json_encode($star_rating_display); ?>[package.star_rating] || package.star_rating || 'N/A'}</p>
            <p><strong>Makkah Nights:</strong> ${package.makkah_nights ?? 'N/A'}</p>
            <p><strong>Madinah Nights:</strong> ${package.madinah_nights ?? 'N/A'}</p>
            <p><strong>Total Days:</strong> ${package.total_days ?? 'N/A'}</p>
            <p><strong>Inclusions:</strong> ${inclusions.length ? inclusions.join(', ') : 'None'}</p>
            <p><strong>Price (PKR):</strong> ${formatNumber(package.price || 0)}</p>
            <p><strong>Created At:</strong> ${package.created_at ? new Date(package.created_at).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : 'N/A'}</p>
          `;
          modal.classList.remove('modal-hidden');
          modal.classList.add('modal-visible');
          modal.focus();
        } else {
          console.error('Package not found for ID:', id);
        }
      }
    });

    closeModal.addEventListener('click', function() {
      modal.classList.add('modal-hidden');
      modal.classList.remove('modal-visible');
    });

    modal.addEventListener('click', function(e) {
      if (e.target === modal) {
        modal.classList.add('modal-hidden');
        modal.classList.remove('modal-visible');
      }
    });

    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && modal.classList.contains('modal-visible')) {
        modal.classList.add('modal-hidden');
        modal.classList.remove('modal-visible');
      }
    });

    // Bulk Delete Checkbox Handling
    document.getElementById('selectAll').addEventListener('change', function() {
      document.querySelectorAll('.package-checkbox').forEach(cb => cb.checked = this.checked);
      toggleBulkDeleteButton();
    });

    document.querySelectorAll('.package-checkbox').forEach(cb => {
      cb.addEventListener('change', toggleBulkDeleteButton);
    });

    function toggleBulkDeleteButton() {
      const checked = document.querySelectorAll('.package-checkbox:checked').length;
      document.getElementById('bulkDeleteBtn').classList.toggle('hidden', checked === 0);
    }

    // Chart.js Initialization
    const typeChart = new Chart(document.createElement('canvas'), {
      type: 'pie',
      data: {
        labels: ['Low Budget', '3 Star', '4 Star', '5 Star'],
        datasets: [{
          data: [
            <?php echo $stats['by_star_rating']['low_budget'] ?: 0; ?>,
            <?php echo $stats['by_star_rating']['3_star'] ?: 0; ?>,
            <?php echo $stats['by_star_rating']['4_star'] ?: 0; ?>,
            <?php echo $stats['by_star_rating']['5_star'] ?: 0; ?>
          ],
          backgroundColor: ['#6366F1', '#10B981', '#F59E0B', '#EF4444']
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'bottom'
          }
        }
      }
    });
    document.querySelector('.stats-card:nth-child(4) .flex').appendChild(typeChart.canvas);
  </script>
</body>

</html>