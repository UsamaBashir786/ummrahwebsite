<?php
require_once '../config/db.php';
// Start admin session
session_name('admin_session');
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
  header('Location: login.php');
  exit;
}

// Use the $conn from config/db.php (MySQLi connection)

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
        // Delete image file
        if (file_exists('../' . $row['package_image'])) {
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
  fputcsv($output, ['ID', 'Title', 'Type', 'Flight Class', 'Inclusions', 'Price (PKR)', 'Created At']);
  $stmt = $conn->prepare("SELECT id, title, package_type, flight_class, inclusions, price, created_at FROM umrah_packages");
  $stmt->execute();
  $result = $stmt->get_result();
  while ($package = $result->fetch_assoc()) {
    $inclusions = json_decode($package['inclusions'], true);
    fputcsv($output, [
      $package['id'],
      $package['title'],
      ucfirst($package['package_type']),
      ucfirst($package['flight_class']),
      implode(', ', array_map('ucfirst', is_array($inclusions) ? $inclusions : [])),
      number_format($package['price'], 2),
      date('d M Y', strtotime($package['created_at']))
    ]);
  }
  $stmt->close();
  fclose($output);
  exit;
}

// Handle filters
$filters = [
  'package_type' => filter_input(INPUT_GET, 'package_type', FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => '/^(single|group|vip)?$/']]) ?: '',
  'flight_class' => filter_input(INPUT_GET, 'flight_class', FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => '/^(economy|business|first)?$/']]) ?: '',
  'price_min' => filter_input(INPUT_GET, 'price_min', FILTER_VALIDATE_FLOAT) ?: null,
  'price_max' => filter_input(INPUT_GET, 'price_max', FILTER_VALIDATE_FLOAT) ?: null,
  'inclusion' => filter_input(INPUT_GET, 'inclusion', FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => '/^(flight|hotel|transport|guide|vip_services)?$/']]) ?: '',
  'date_from' => filter_input(INPUT_GET, 'date_from', FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => '/^\d{4}-\d{2}-\d{2}$/']]) ?: '',
  'date_to' => filter_input(INPUT_GET, 'date_to', FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => '/^\d{4}-\d{2}-\d{2}$/']]) ?: ''
];

$where_clauses = [];
$params = [];
$types = '';

if ($filters['package_type']) {
  $where_clauses[] = "package_type = ?";
  $params[] = $filters['package_type'];
  $types .= 's';
}

if ($filters['flight_class']) {
  $where_clauses[] = "flight_class = ?";
  $params[] = $filters['flight_class'];
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

$query = "SELECT id, package_type, title, flight_class, inclusions, price, package_image, created_at FROM umrah_packages";
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
  'by_type' => ['single' => 0, 'group' => 0, 'vip' => 0],
  'by_flight_class' => ['economy' => 0, 'business' => 0, 'first' => 0],
  'avg_price' => 0,
  'recent_packages' => 0
];

$result = $conn->query("SELECT COUNT(*) as total, AVG(price) as avg_price FROM umrah_packages");
if ($result) {
  $row = $result->fetch_assoc();
  $stats['total_packages'] = $row['total'];
  $stats['avg_price'] = number_format($row['avg_price'], 2);
}

$result = $conn->query("SELECT package_type, COUNT(*) as count FROM umrah_packages GROUP BY package_type");
if ($result) {
  while ($row = $result->fetch_assoc()) {
    $stats['by_type'][$row['package_type']] = $row['count'];
  }
}

$result = $conn->query("SELECT flight_class, COUNT(*) as count FROM umrah_packages GROUP BY flight_class");
if ($result) {
  while ($row = $result->fetch_assoc()) {
    $stats['by_flight_class'][$row['flight_class']] = $row['count'];
  }
}

$result = $conn->query("SELECT COUNT(*) as recent FROM umrah_packages WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
if ($result) {
  $stats['recent_packages'] = $result->fetch_assoc()['recent'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>View Umrah Packages | UmrahFlights</title>
  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <!-- DataTables CSS (Custom Tailwind Styling) -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.tailwindcss.min.css">
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
  <!-- Custom CSS -->
  <link rel="stylesheet" href="assets/css/index.css">
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

<body class="bg-gray-100 font-sans">
  <?php include 'includes/sidebar.php'; ?>
  <!-- Main Content -->
  <main class="ml-0 md:ml-64 p-6 min-h-screen" role="main" aria-label="Main content">
    <!-- Top Navbar -->
    <nav class="flex items-center justify-between bg-white shadow-md p-4 rounded-lg mb-6">
      <div class="flex items-center">
        <button id="sidebarToggle" class="md:hidden text-gray-600 hover:text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500" aria-label="Toggle sidebar">
          <i class="fas fa-bars text-xl"></i>
        </button>
        <h1 class="text-xl font-semibold text-gray-800 ml-4">View Packages</h1>
      </div>
      <div class="flex items-center space-x-4">
        <div class="relative">
          <button id="notificationBtn" class="text-gray-600 hover:text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500" aria-label="Notifications">
            <i class="fas fa-bell text-xl"></i>
            <span class="absolute top-0 right-0 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">3</span>
          </button>
        </div>
        <div class="relative">
          <button id="userDropdown" class="flex items-center text-gray-600 hover:text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500" aria-label="User menu" aria-expanded="false">
            <img src="../assets/img/admin.jpg" alt="Admin User" class="w-8 h-8 rounded-full mr-2">
            <span class="hidden md:inline text-gray-800">Admin User</span>
            <i class="fas fa-chevron-down ml-1"></i>
          </button>
          <div id="userDropdownMenu" class="hidden absolute right-0 mt-2 w-48 bg-white shadow-lg rounded-lg py-2 z-10">
            <a href="../logout.php" class="block px-4 py-2 text-red-600 hover:bg-gray-100"><i class="fas fa-sign-out-alt mr-2"></i> Logout</a>
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
    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6" aria-label="Package statistics">
      <div class="stats-card bg-white p-6 rounded-lg shadow-md hover:shadow-lg">
        <h3 class="text-lg font-semibold text-gray-800">Total Packages</h3>
        <p class="text-2xl font-bold text-blue-600"><?php echo $stats['total_packages']; ?></p>
      </div>
      <div class="stats-card bg-white p-6 rounded-lg shadow-md hover:shadow-lg">
        <h3 class="text-lg font-semibold text-gray-800">Average Price (PKR)</h3>
        <p class="text-2xl font-bold text-blue-600"><?php echo $stats['avg_price']; ?></p>
      </div>
      <div class="stats-card bg-white p-6 rounded-lg shadow-md hover:shadow-lg">
        <h3 class="text-lg font-semibold text-gray-800">Recent Packages (30 Days)</h3>
        <p class="text-2xl font-bold text-blue-600"><?php echo $stats['recent_packages']; ?></p>
      </div>
      <div class="stats-card bg-white p-6 rounded-lg shadow-md hover:shadow-lg">
        <h3 class="text-lg font-semibold text-gray-800">By Package Type</h3>
        <p class="text-sm text-gray-600">
          Single: <?php echo $stats['by_type']['single']; ?><br>
          Group: <?php echo $stats['by_type']['group']; ?><br>
          VIP: <?php echo $stats['by_type']['vip']; ?>
        </p>
      </div>
    </section>

    <!-- Chart Section -->
    <!-- <section class="bg-white p-6 rounded-lg shadow-md mb-6" aria-label="Package distribution charts">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <h3 class="text-lg font-semibold text-gray-800 mb-4">Packages by Type</h3>
          <canvas id="typeChart" class="w-full h-64"></canvas>
        </div>
        <div>
          <h3 class="text-lg font-semibold text-gray-800 mb-4">Packages by Flight Class</h3>
          <canvas id="flightChart" class="w-full h-64"></canvas>
        </div>
      </div>
    </section> -->

    <!-- Filters Section -->
    <section class="bg-white p-6 rounded-lg shadow-md mb-6" aria-label="Package filters">
      <h3 class="text-lg font-semibold text-gray-800 mb-4">Filter Packages</h3>
      <form class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4" method="GET">
        <div>
          <label for="package_type" class="block text-sm font-medium text-gray-700">Package Type</label>
          <select id="package_type" name="package_type" class="mt-1 block w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
            <option value="">All Types</option>
            <option value="single" <?php echo $filters['package_type'] === 'single' ? 'selected' : ''; ?>>Single</option>
            <option value="group" <?php echo $filters['package_type'] === 'group' ? 'selected' : ''; ?>>Group</option>
            <option value="vip" <?php echo $filters['package_type'] === 'vip' ? 'selected' : ''; ?>>VIP</option>
          </select>
        </div>
        <div>
          <label for="flight_class" class="block text-sm font-medium text-gray-700">Flight Class</label>
          <select id="flight_class" name="flight_class" class="mt-1 block w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
            <option value="">All Classes</option>
            <option value="economy" <?php echo $filters['flight_class'] === 'economy' ? 'selected' : ''; ?>>Economy</option>
            <option value="business" <?php echo $filters['flight_class'] === 'business' ? 'selected' : ''; ?>>Business</option>
            <option value="first" <?php echo $filters['flight_class'] === 'first' ? 'selected' : ''; ?>>First</option>
          </select>
        </div>
        <div>
          <label for="inclusion" class="block text-sm font-medium text-gray-700">Inclusion</label>
          <select id="inclusion" name="inclusion" class="mt-1 block w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
            <option value="">Any Inclusion</option>
            <option value="flight" <?php echo $filters['inclusion'] === 'flight' ? 'selected' : ''; ?>>Flight</option>
            <option value="hotel" <?php echo $filters['inclusion'] === 'hotel' ? 'selected' : ''; ?>>Hotel</option>
            <option value="transport" <?php echo $filters['inclusion'] === 'transport' ? 'selected' : ''; ?>>Transport</option>
            <option value="guide" <?php echo $filters['inclusion'] === 'guide' ? 'selected' : ''; ?>>Guide</option>
            <option value="vip_services" <?php echo $filters['inclusion'] === 'vip_services' ? 'selected' : ''; ?>>VIP Services</option>
          </select>
        </div>
        <div>
          <label for="price_min" class="block text-sm font-medium text-gray-700">Min Price (PKR)</label>
          <input type="number" id="price_min" name="price_min" value="<?php echo htmlspecialchars($filters['price_min'] !== null ? $filters['price_min'] : ''); ?>" min="0" step="0.01" class="mt-1 block w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div>
          <label for="price_max" class="block text-sm font-medium text-gray-700">Max Price (PKR)</label>
          <input type="number" id="price_max" name="price_max" value="<?php echo htmlspecialchars($filters['price_max'] !== null ? $filters['price_max'] : ''); ?>" max="500000" step="0.01" class="mt-1 block w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div>
          <label for="date_from" class="block text-sm font-medium text-gray-700">Created From</label>
          <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($filters['date_from']); ?>" class="mt-1 block w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div>
          <label for="date_to" class="block text-sm font-medium text-gray-700">Created To</label>
          <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($filters['date_to']); ?>" class="mt-1 block w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div class="flex items-end space-x-2">
          <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">Apply Filters</button>
          <a href="view-packages.php" class="text-blue-600 hover:underline">Clear</a>
        </div>
      </form>
    </section>

    <!-- Packages Table -->
    <section class="bg-white p-6 rounded-lg shadow-md" aria-label="Umrah packages table">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-800">Umrah Packages</h3>
        <div class="flex space-x-2">
          <a href="add-packages.php" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
            <i class="fas fa-plus mr-2"></i>Add New Package
          </a>
          <a href="?export_csv=1" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500">
            <i class="fas fa-download mr-2"></i>Export CSV
          </a>
        </div>
      </div>
      <form id="bulkDeleteForm" method="POST">
        <div class="flex justify-end mb-4">
          <button type="submit" name="bulk_delete" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 hidden" id="bulkDeleteBtn" onclick="return confirm('Are you sure you want to delete selected packages?');" aria-label="Delete selected packages">
            <i class="fas fa-trash mr-2"></i>Delete Selected
          </button>
        </div>
        <div class="overflow-x-auto">
          <table id="packagesTable" class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-gray-200">
                <th class="p-3"><input type="checkbox" id="selectAll" aria-label="Select all packages"></th>
                <th class="p-3">#</th>
                <th class="p-3">Image</th>
                <th class="p-3">Title</th>
                <th class="p-3">Type</th>
                <th class="p-3">Flight Class</th>
                <th class="p-3">Inclusions</th>
                <th class="p-3">Price (PKR)</th>
                <th class="p-3">Created At</th>
                <th class="p-3">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($packages)): ?>
                <tr>
                  <td colspan="10" class="p-3 text-center text-gray-600">No packages found.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($packages as $index => $package): ?>
                  <tr class="border-b hover:bg-gray-50">
                    <td class="p-3">
                      <input type="checkbox" name="package_ids[]" value="<?php echo $package['id']; ?>" class="package-checkbox" aria-label="Select package <?php echo htmlspecialchars($package['title']); ?>">
                    </td>
                    <td class="p-3"><?php echo $index + 1; ?></td>
                    <td class="p-3">
                      <img src="../<?php echo htmlspecialchars($package['package_image']); ?>" alt="Package Image for <?php echo htmlspecialchars($package['title']); ?>" class="table-img w-12 h-12 object-cover rounded" aria-label="Package image">
                    </td>
                    <td class="p-3"><?php echo htmlspecialchars($package['title']); ?></td>
                    <td class="p-3"><?php echo ucfirst(htmlspecialchars($package['package_type'])); ?></td>
                    <td class="p-3"><?php echo ucfirst(htmlspecialchars($package['flight_class'])); ?></td>
                    <td class="p-3">
                      <?php
                      $inclusions = json_decode($package['inclusions'], true);
                      echo implode(', ', array_map('ucfirst', is_array($inclusions) ? $inclusions : []));
                      ?>
                    </td>
                    <td class="p-3"><?php echo number_format($package['price'], 2); ?></td>
                    <td class="p-3"><?php echo date('d M Y', strtotime($package['created_at'])); ?></td>
                    <td class="p-3 flex space-x-2">
                      <button type="button" class="text-blue-600 hover:text-blue-800 view-details" data-id="<?php echo $package['id']; ?>" data-tooltip="View Details" aria-label="View details for <?php echo htmlspecialchars($package['title']); ?>">
                        <i class="fas fa-eye"></i>
                      </button>
                      <a href="edit-package.php?id=<?php echo $package['id']; ?>" class="text-yellow-600 hover:text-yellow-800" data-tooltip="Edit Package" aria-label="Edit <?php echo htmlspecialchars($package['title']); ?>">
                        <i class="fas fa-edit"></i>
                      </a>
                      <a href="?delete=<?php echo $package['id']; ?>" class="text-red-600 hover:text-red-800" data-tooltip="Delete Package" onclick="return confirm('Are you sure you want to delete this package?');" aria-label="Delete <?php echo htmlspecialchars($package['title']); ?>">
                        <i class="fas fa-trash"></i>
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </form>
    </section>

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
  </main>

  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- DataTables JS -->
  <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/dataTables.tailwindcss.min.js"></script>
  <!-- Custom JavaScript -->
  <script src="assets/js/index.js"></script>
  <script>
    // DataTables Initialization
    $(document).ready(function() {
      $('#packagesTable').DataTable({
        pageLength: 10,
        order: [
          [8, 'desc']
        ],
        columnDefs: [{
            orderable: false,
            targets: [0, 2, 9]
          }, // Disable sorting for checkbox, image, actions
          {
            searchable: false,
            targets: [0, 2, 9]
          } // Disable search for checkbox, image, actions
        ],
        language: {
          search: 'Search packages:',
          searchPlaceholder: 'Enter title or type...',
          emptyTable: 'No packages available.'
        }
      });
    });

    // User Dropdown Toggle
    document.getElementById('userDropdown').addEventListener('click', function() {
      const menu = document.getElementById('userDropdownMenu');
      menu.classList.toggle('hidden');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
      const dropdown = document.getElementById('userDropdownMenu');
      const button = document.getElementById('userDropdown');
      if (!dropdown.contains(e.target) && !button.contains(e.target)) {
        dropdown.classList.add('hidden');
      }
    });

    // Sidebar Toggle
    document.getElementById('sidebarToggle').addEventListener('click', function() {
      // Assuming sidebar has a toggle mechanism in includes/sidebar.php
      document.querySelector('aside').classList.toggle('hidden');
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

    // Modal Handling
    const modal = document.getElementById('detailsModal');
    const closeModal = document.getElementById('closeModal');
    const modalContent = document.getElementById('modalContent');

    document.querySelectorAll('.view-details').forEach(button => {
      button.addEventListener('click', function() {
        const id = this.getAttribute('data-id');
        const package = <?php echo json_encode($packages); ?>.find(p => p.id == id);
        if (package) {
          const inclusions = JSON.parse(package.inclusions || '[]').map(i => i.charAt(0).toUpperCase() + i.slice(1)).join(', ');
          modalContent.innerHTML = `
            <img src="../${package.package_image}" alt="${package.title}" class="w-full h-48 object-cover rounded-md mb-4">
            <p><strong>Title:</strong> ${package.title}</p>
            <p><strong>Type:</strong> ${package.package_type.charAt(0).toUpperCase() + package.package_type.slice(1)}</p>
            <p><strong>Flight Class:</strong> ${package.flight_class.charAt(0).toUpperCase() + package.flight_class.slice(1)}</p>
            <p><strong>Inclusions:</strong> ${inclusions || 'None'}</p>
            <p><strong>Price (PKR):</strong> ${Number(package.price).toFixed(2)}</p>
            <p><strong>Created At:</strong> ${new Date(package.created_at).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })}</p>
          `;
          modal.classList.remove('modal-hidden');
          modal.classList.add('modal-visible');
          modal.focus();
        }
      });
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

    // Chart.js Initialization
    const typeChart = new Chart(document.getElementById('typeChart'), {
      type: 'pie',
      data: {
        labels: ['Single', 'Group', 'VIP'],
        datasets: [{
          data: [
            <?php echo $stats['by_type']['single']; ?>,
            <?php echo $stats['by_type']['group']; ?>,
            <?php echo $stats['by_type']['vip']; ?>
          ],
          backgroundColor: ['#3B82F6', '#10B981', '#F59E0B']
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

    const flightChart = new Chart(document.getElementById('flightChart'), {
      type: 'bar',
      data: {
        labels: ['Economy', 'Business', 'First'],
        datasets: [{
          label: 'Packages',
          data: [
            <?php echo $stats['by_flight_class']['economy']; ?>,
            <?php echo $stats['by_flight_class']['business']; ?>,
            <?php echo $stats['by_flight_class']['first']; ?>
          ],
          backgroundColor: '#3B82F6'
        }]
      },
      options: {
        responsive: true,
        scales: {
          y: {
            beginAtZero: true
          }
        }
      }
    });
  </script>
</body>

</html>