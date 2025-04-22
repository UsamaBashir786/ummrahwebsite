<?php
require_once '../config/db.php';
session_name('admin_session');
session_start();

// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if admin is logged in
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
  header('Location: login.php');
  exit;
}

$success_message = '';
$error_message = '';

// Initialize default service info
$taxi_service_info = ['service_title' => 'Taxi Service', 'year' => date('Y')];
$rentacar_service_info = ['service_title' => 'Rent A Car Service', 'year' => date('Y')];
$taxi_routes = [];
$rentacar_routes = [];

// Fetch existing taxi service info
$stmt = $conn->prepare("SELECT service_title, year FROM taxi_routes ORDER BY year DESC LIMIT 1");
if ($stmt->execute()) {
  $result = $stmt->get_result();
  if ($row = $result->fetch_assoc()) {
    $taxi_service_info = $row;
  }
  $stmt->close();
} else {
  error_log("Error fetching taxi service info: " . $stmt->error);
}

// Fetch existing rent-a-car service info
$stmt = $conn->prepare("SELECT service_title, year FROM rentacar_routes ORDER BY year DESC LIMIT 1");
if ($stmt->execute()) {
  $result = $stmt->get_result();
  if ($row = $result->fetch_assoc()) {
    $rentacar_service_info = $row;
  }
  $stmt->close();
} else {
  error_log("Error fetching rent-a-car service info: " . $stmt->error);
}

// Fetch existing taxi routes
$stmt = $conn->prepare("SELECT id, route_number, route_name, camry_sonata_price, starex_staria_price, hiace_price FROM taxi_routes ORDER BY route_number");
if ($stmt->execute()) {
  $result = $stmt->get_result();
  $taxi_routes = $result->fetch_all(MYSQLI_ASSOC);
  $stmt->close();
} else {
  error_log("Error fetching taxi routes: " . $stmt->error);
}

// Fetch existing rent-a-car routes
$stmt = $conn->prepare("SELECT id, route_number, route_name, gmc_16_19_price, gmc_22_23_price, coaster_price FROM rentacar_routes ORDER BY route_number");
if ($stmt->execute()) {
  $result = $stmt->get_result();
  $rentacar_routes = $result->fetch_all(MYSQLI_ASSOC);
  $stmt->close();
} else {
  error_log("Error fetching rent-a-car routes: " . $stmt->error);
}

// Handle taxi routes form submission
if (isset($_POST['update_taxi_routes'])) {
  error_log("Taxi routes form submitted: " . print_r($_POST, true)); // Debug log
  $service_title = filter_input(INPUT_POST, 'serviceTitle', FILTER_SANITIZE_STRING) ?: 'Taxi Service';
  $year = filter_input(INPUT_POST, 'year', FILTER_VALIDATE_INT) ?: date('Y');

  $conn->begin_transaction();
  try {
    // Update existing routes
    if (!empty($_POST['route_id'])) {
      foreach ($_POST['route_id'] as $index => $id) {
        $route_number = filter_input(INPUT_POST, "route_number][$index", FILTER_VALIDATE_INT) ?: 1;
        $route_name = filter_input(INPUT_POST, "route_name][$index", FILTER_SANITIZE_STRING) ?: 'Unknown Route';
        $camry_price = filter_input(INPUT_POST, "camry_price][$index", FILTER_VALIDATE_FLOAT) ?: 0.00;
        $starex_price = filter_input(INPUT_POST, "starex_price][$index", FILTER_VALIDATE_FLOAT) ?: 0.00;
        $hiace_price = filter_input(INPUT_POST, "hiace_price][$index", FILTER_VALIDATE_FLOAT) ?: 0.00;

        $stmt = $conn->prepare("UPDATE taxi_routes SET service_title = ?, year = ?, route_number = ?, route_name = ?, camry_sonata_price = ?, starex_staria_price = ?, hiace_price = ? WHERE id = ?");
        $stmt->bind_param("sisidddi", $service_title, $year, $route_number, $route_name, $camry_price, $starex_price, $hiace_price, $id);
        if (!$stmt->execute()) {
          throw new Exception("Error updating taxi route ID $id: " . $stmt->error);
        }
        $stmt->close();
      }
    }

    // Add new routes
    if (!empty($_POST['new_route_name'])) {
      foreach ($_POST['new_route_name'] as $index => $route_name) {
        $route_number = filter_input(INPUT_POST, "new_route_number][$index", FILTER_VALIDATE_INT) ?: 1;
        $route_name = filter_input(INPUT_POST, "new_route_name][$index", FILTER_SANITIZE_STRING) ?: 'Unknown Route';
        $camry_price = filter_input(INPUT_POST, "new_camry_price][$index", FILTER_VALIDATE_FLOAT) ?: 0.00;
        $starex_price = filter_input(INPUT_POST, "new_starex_price][$index", FILTER_VALIDATE_FLOAT) ?: 0.00;
        $hiace_price = filter_input(INPUT_POST, "new_hiace_price][$index", FILTER_VALIDATE_FLOAT) ?: 0.00;

        // Only insert if route_name is not empty
        if ($route_name !== '') {
          $stmt = $conn->prepare("INSERT INTO taxi_routes (service_title, year, route_number, route_name, camry_sonata_price, starex_staria_price, hiace_price) VALUES (?, ?, ?, ?, ?, ?, ?)");
          $stmt->bind_param("sisiddd", $service_title, $year, $route_number, $route_name, $camry_price, $starex_price, $hiace_price);
          if (!$stmt->execute()) {
            throw new Exception("Error adding new taxi route: " . $stmt->error);
          }
          $stmt->close();
        }
      }
    }

    $conn->commit();
    $success_message = 'Taxi routes saved successfully!';
    error_log("Taxi routes saved successfully");
    header('Location: add-transportations.php');
    exit;
  } catch (Exception $e) {
    $conn->rollback();
    $error_message = 'Error saving taxi routes: ' . $e->getMessage();
    error_log("Taxi routes error: " . $error_message);
  }
}

// Handle rent-a-car routes form submission
if (isset($_POST['update_rentacar_routes'])) {
  error_log("Rent-a-car routes form submitted: " . print_r($_POST, true)); // Debug log
  $service_title = filter_input(INPUT_POST, 'serviceTitle', FILTER_SANITIZE_STRING) ?: 'Rent A Car Service';
  $year = filter_input(INPUT_POST, 'year', FILTER_VALIDATE_INT) ?: date('Y');

  $conn->begin_transaction();
  try {
    // Update existing routes
    if (!empty($_POST['route_id'])) {
      foreach ($_POST['route_id'] as $index => $id) {
        $route_number = filter_input(INPUT_POST, "route_number][$index", FILTER_VALIDATE_INT) ?: 1;
        $route_name = filter_input(INPUT_POST, "route_name][$index", FILTER_SANITIZE_STRING) ?: 'Unknown Route';
        $gmc_16_19_price = filter_input(INPUT_POST, "gmc_16_19_price][$index", FILTER_VALIDATE_FLOAT) ?: 0.00;
        $gmc_22_23_price = filter_input(INPUT_POST, "gmc_22_23_price][$index", FILTER_VALIDATE_FLOAT) ?: 0.00;
        $coaster_price = filter_input(INPUT_POST, "coaster_price][$index", FILTER_VALIDATE_FLOAT) ?: 0.00;

        $stmt = $conn->prepare("UPDATE rentacar_routes SET service_title = ?, year = ?, route_number = ?, route_name = ?, gmc_16_19_price = ?, gmc_22_23_price = ?, coaster_price = ? WHERE id = ?");
        $stmt->bind_param("sisidddi", $service_title, $year, $route_number, $route_name, $gmc_16_19_price, $gmc_22_23_price, $coaster_price, $id);
        if (!$stmt->execute()) {
          throw new Exception("Error updating rent-a-car route ID $id: " . $stmt->error);
        }
        $stmt->close();
      }
    }

    // Add new routes
    if (!empty($_POST['new_route_name'])) {
      foreach ($_POST['new_route_name'] as $index => $route_name) {
        $route_number = filter_input(INPUT_POST, "new_route_number][$index", FILTER_VALIDATE_INT) ?: 1;
        $route_name = filter_input(INPUT_POST, "new_route_name][$index", FILTER_SANITIZE_STRING) ?: 'Unknown Route';
        $gmc_16_19_price = filter_input(INPUT_POST, "new_gmc_16_19_price][$index", FILTER_VALIDATE_FLOAT) ?: 0.00;
        $gmc_22_23_price = filter_input(INPUT_POST, "new_gmc_22_23_price][$index", FILTER_VALIDATE_FLOAT) ?: 0.00;
        $coaster_price = filter_input(INPUT_POST, "new_coaster_price][$index", FILTER_VALIDATE_FLOAT) ?: 0.00;

        // Only insert if route_name is not empty
        if ($route_name !== '') {
          $stmt = $conn->prepare("INSERT INTO rentacar_routes (service_title, year, route_number, route_name, gmc_16_19_price, gmc_22_23_price, coaster_price) VALUES (?, ?, ?, ?, ?, ?, ?)");
          $stmt->bind_param("sisiddd", $service_title, $year, $route_number, $route_name, $gmc_16_19_price, $gmc_22_23_price, $coaster_price);
          if (!$stmt->execute()) {
            throw new Exception("Error adding new rent-a-car route: " . $stmt->error);
          }
          $stmt->close();
        }
      }
    }

    $conn->commit();
    $success_message = 'Rent-a-car routes saved successfully!';
    error_log("Rent-a-car routes saved successfully");
    header('Location: add-transportations.php');
    exit;
  } catch (Exception $e) {
    $conn->rollback();
    $error_message = 'Error saving rent-a-car routes: ' . $e->getMessage();
    error_log("Rent-a-car routes error: " . $error_message);
  }
}

// Handle delete taxi route
if (isset($_GET['delete_taxi_route'])) {
  $id = filter_input(INPUT_GET, 'delete_taxi_route', FILTER_VALIDATE_INT);
  if ($id) {
    $stmt = $conn->prepare("DELETE FROM taxi_routes WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
      $success_message = 'Taxi route deleted successfully!';
      error_log("Taxi route ID $id deleted successfully");
    } else {
      $error_message = 'Error deleting taxi route: ' . $stmt->error;
      error_log("Error deleting taxi route: " . $stmt->error);
    }
    $stmt->close();
    header('Location: add-transportations.php');
    exit;
  } else {
    $error_message = 'Invalid taxi route ID for deletion';
    error_log("Invalid taxi route ID for deletion: " . $_GET['delete_taxi_route']);
  }
}

// Handle delete rent-a-car route
if (isset($_GET['delete_rentacar_route'])) {
  $id = filter_input(INPUT_GET, 'delete_rentacar_route', FILTER_VALIDATE_INT);
  if ($id) {
    $stmt = $conn->prepare("DELETE FROM rentacar_routes WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
      $success_message = 'Rent-a-car route deleted successfully!';
      error_log("Rent-a-car route ID $id deleted successfully");
    } else {
      $error_message = 'Error deleting rent-a-car route: ' . $stmt->error;
      error_log("Error deleting rent-a-car route: " . $stmt->error);
    }
    $stmt->close();
    header('Location: add-transportations.php');
    exit;
  } else {
    $error_message = 'Invalid rent-a-car route ID for deletion';
    error_log("Invalid rent-a-car route ID for deletion: " . $_GET['delete_rentacar_route']);
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Transportation | UmrahFlights</title>
  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="assets/css/index.css">
  <style>
    .tab-btn {
      @apply px-4 py-2 font-semibold text-gray-600 rounded-t-lg;
    }

    .tab-btn.active {
      @apply bg-teal-600 text-white;
    }

    .tab-content {
      @apply hidden;
    }

    .tab-content.active {
      @apply block;
    }

    .price-input {
      @apply border border-gray-300 rounded-md px-2 py-1 focus:outline-none focus:ring-2 focus:ring-teal-500;
    }
  </style>
</head>

<body class="bg-gray-100 font-sans">
  <?php include 'includes/sidebar.php'; ?>
  <!-- Main Content -->
  <main class="ml-0 md:ml-64 p-6 min-h-screen flex flex-col" role="main" aria-label="Main content">
    <div class="bg-white shadow-md py-4 px-6 flex justify-between items-center">
      <h1 class="text-xl font-semibold">
        <i class="text-teal-600 fas fa-car mr-2"></i> Transportation Management
      </h1>
      <div class="flex items-center space-x-4">
        <button class="md:hidden text-gray-800 focus:outline-none focus:ring-2 focus:ring-teal-500" id="menu-btn" aria-label="Toggle sidebar">
          <i class="fas fa-bars text-xl"></i>
        </button>
      </div>
    </div>
    <div class="w-full p-5">
      <?php include 'includes/transport-stats.php'; ?>
    </div>
    <div class="container mx-auto px-4 py-8">
      <?php if ($success_message): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 flex justify-between items-center" id="success-alert" role="alert">
          <p><?php echo htmlspecialchars($success_message); ?></p>
          <button class="text-green-700 hover:text-green-900 focus:outline-none focus:ring-2 focus:ring-green-500" onclick="this.parentElement.remove()" aria-label="Close success alert">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <script>
          setTimeout(() => {
            const alert = document.getElementById('success-alert');
            if (alert) alert.style.display = 'none';
          }, 5000);
        </script>
      <?php endif; ?>
      <?php if ($error_message): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 flex justify-between items-center" id="error-alert" role="alert">
          <p><?php echo htmlspecialchars($error_message); ?></p>
          <button class="text-red-700 hover:text-red-900 focus:outline-none focus:ring-2 focus:ring-red-500" onclick="this.parentElement.remove()" aria-label="Close error alert">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <script>
          setTimeout(() => {
            const alert = document.getElementById('error-alert');
            if (alert) alert.style.display = 'none';
          }, 5000);
        </script>
      <?php endif; ?>
      <div class="bg-white p-6 rounded-lg shadow-lg">
        <div class="tab-buttons flex justify-center mb-4">
          <button class="tab-btn active" onclick="switchTab('taxi')" aria-label="Show Taxi Routes">Taxi Routes</button>
          <button class="tab-btn" onclick="switchTab('rentacar')" aria-label="Show Rent A Car Routes">Rent A Car Routes</button>
        </div>
        <!-- Taxi Routes Tab -->
        <div id="taxi-tab" class="tab-content active">
          <div class="mb-6">
            <h2 class="text-2xl font-bold">Taxi Routes Management</h2>
            <p class="text-gray-600 mt-2">Manage your taxi service routes and prices</p>
          </div>
          <form action="" method="POST" id="taxi-routes-form">
            <input type="hidden" name="update_taxi_routes" value="1">
            <!-- Service Title and Year -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
              <div>
                <label for="taxi-service-title" class="block text-sm font-medium text-gray-700">Service Title</label>
                <input type="text" id="taxi-service-title" name="serviceTitle" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500" value="<?php echo htmlspecialchars($taxi_service_info['service_title']); ?>" aria-label="Taxi service title">
              </div>
              <div>
                <label for="taxi-year" class="block text-sm font-medium text-gray-700">Year</label>
                <input type="number" id="taxi-year" name="year" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500" value="<?php echo $taxi_service_info['year']; ?>" aria-label="Taxi service year">
              </div>
            </div>
            <!-- Existing Routes Table -->
            <div class="mb-6 overflow-x-auto">
              <h3 class="font-semibold text-lg mb-3">Existing Routes</h3>
              <table class="min-w-full bg-white border border-gray-300 mb-4">
                <thead>
                  <tr class="bg-teal-600 text-white">
                    <th class="py-2 px-4 border-b w-16 text-center" scope="col">#</th>
                    <th class="py-2 px-4 border-b text-left" scope="col">Route</th>
                    <th class="py-2 px-4 border-b text-center" scope="col">Camry / Sonata (PKR)</th>
                    <th class="py-2 px-4 border-b text-center" scope="col">Starex / Staria (PKR)</th>
                    <th class="py-2 px-4 border-b text-center" scope="col">Hiace (PKR)</th>
                    <th class="py-2 px-4 border-b w-16 text-center" scope="col">Action</th>
                  </tr>
                </thead>
                <tbody id="taxi-routes-body">
                  <?php if (empty($taxi_routes)): ?>
                    <tr>
                      <td colspan="6" class="py-4 text-center text-gray-500">No taxi routes found</td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($taxi_routes as $index => $route): ?>
                      <tr>
                        <td class="py-2 px-4 border-b text-center">
                          <input type="hidden" name="route_id[<?php echo $index; ?>]" value="<?php echo $route['id']; ?>">
                          <input type="number" name="route_number[<?php echo $index; ?>]" value="<?php echo $route['route_number']; ?>" class="price-input w-16 text-center" aria-label="Route number <?php echo $index + 1; ?>">
                        </td>
                        <td class="py-2 px-4 border-b">
                          <input type="text" name="route_name[<?php echo $index; ?>]" value="<?php echo htmlspecialchars($route['route_name']); ?>" class="price-input w-full" aria-label="Route name <?php echo $index + 1; ?>">
                        </td>
                        <td class="py-2 px-4 border-b">
                          <input type="number" name="camry_price[<?php echo $index; ?>]" value="<?php echo $route['camry_sonata_price']; ?>" step="0.01" class="price-input w-full text-center" aria-label="Camry/Sonata price <?php echo $index + 1; ?>">
                          <span class="text-xs text-gray-500">PKR</span>
                        </td>
                        <td class="py-2 px-4 border-b">
                          <input type="number" name="starex_price[<?php echo $index; ?>]" value="<?php echo $route['starex_staria_price']; ?>" step="0.01" class="price-input w-full text-center" aria-label="Starex/Staria price <?php echo $index + 1; ?>">
                          <span class="text-xs text-gray-500">PKR</span>
                        </td>
                        <td class="py-2 px-4 border-b">
                          <input type="number" name="hiace_price[<?php echo $index; ?>]" value="<?php echo $route['hiace_price']; ?>" step="0.01" class="price-input w-full text-center" aria-label="Hiace price <?php echo $index + 1; ?>">
                          <span class="text-xs text-gray-500">PKR</span>
                        </td>
                        <td class="py-2 px-4 border-b text-center">
                          <button type="button" class="text-red-500 hover:text-red-700 focus:outline-none focus:ring-2 focus:ring-red-500" onclick="confirmDeleteTaxiRoute(<?php echo $route['id']; ?>)" aria-label="Delete route <?php echo $index + 1; ?>">
                            <i class="fas fa-trash"></i>
                          </button>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
            <!-- Add New Routes Section -->
            <div class="mb-6">
              <h3 class="font-semibold text-lg mb-3">Add New Routes</h3>
              <table class="min-w-full bg-white border border-gray-300 mb-4">
                <thead>
                  <tr class="bg-teal-600 text-white">
                    <th class="py-2 px-4 border-b w-16 text-center" scope="col">#</th>
                    <th class="py-2 px-4 border-b text-left" scope="col">Route</th>
                    <th class="py-2 px-4 border-b text-center" scope="col">Camry / Sonata (PKR)</th>
                    <th class="py-2 px-4 border-b text-center" scope="col">Starex / Staria (PKR)</th>
                    <th class="py-2 px-4 border-b text-center" scope="col">Hiace (PKR)</th>
                    <th class="py-2 px-4 border-b w-16 text-center" scope="col">Action</th>
                  </tr>
                </thead>
                <tbody id="new-taxi-routes-body">
                  <tr>
                    <td class="py-2 px-4 border-b text-center">
                      <input type="number" name="new_route_number[0]" value="<?php echo count($taxi_routes) + 1; ?>" class="price-input w-16 text-center" aria-label="New route number 1">
                    </td>
                    <td class="py-2 px-4 border-b">
                      <input type="text" name="new_route_name[0]" placeholder="Enter route name" class="price-input w-full" aria-label="New route name 1">
                    </td>
                    <td class="py-2 px-4 border-b">
                      <input type="number" name="new_camry_price[0]" placeholder="Price" step="0.01" class="price-input w-full text-center" aria-label="New Camry/Sonata price 1">
                      <span class="text-xs text-gray-500">PKR</span>
                    </td>
                    <td class="py-2 px-4 border-b">
                      <input type="number" name="new_starex_price[0]" placeholder="Price" step="0.01" class="price-input w-full text-center" aria-label="New Starex/Staria price 1">
                      <span class="text-xs text-gray-500">PKR</span>
                    </td>
                    <td class="py-2 px-4 border-b">
                      <input type="number" name="new_hiace_price[0]" placeholder="Price" step="0.01" class="price-input w-full text-center" aria-label="New Hiace price 1">
                      <span class="text-xs text-gray-500">PKR</span>
                    </td>
                    <td class="py-2 px-4 border-b text-center">
                      <button type="button" class="text-red-500 hover:text-red-700 delete-new-row focus:outline-none focus:ring-2 focus:ring-red-500" disabled aria-label="Delete new route 1">
                        <i class="fas fa-trash"></i>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
              <button type="button" id="add-taxi-row" class="px-4 py-2 bg-teal-600 text-white rounded-md hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2" aria-label="Add another taxi route">
                <i class="fas fa-plus-circle mr-2"></i> Add Another Route
              </button>
            </div>
            <!-- Submit Button -->
            <div class="flex flex-wrap gap-4">
              <button type="submit" class="bg-teal-600 text-white px-6 py-2 rounded-lg hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500" aria-label="Save taxi routes">
                <i class="fas fa-save mr-2"></i> Save All Changes
              </button>
            </div>
          </form>
        </div>
        <!-- Rent A Car Routes Tab -->
        <div id="rentacar-tab" class="tab-content">
          <div class="mb-6">
            <h2 class="text-2xl font-bold">Rent A Car Routes Management</h2>
            <p class="text-gray-600 mt-2">Manage your rent a car service routes and prices</p>
          </div>
          <form action="" method="POST" id="rentacar-routes-form">
            <input type="hidden" name="update_rentacar_routes" value="1">
            <!-- Service Title and Year -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
              <div>
                <label for="rentacar-service-title" class="block text-sm font-medium text-gray-700">Service Title</label>
                <input type="text" id="rentacar-service-title" name="serviceTitle" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($rentacar_service_info['service_title']); ?>" aria-label="Rent-a-car service title">
              </div>
              <div>
                <label for="rentacar-year" class="block text-sm font-medium text-gray-700">Year</label>
                <input type="number" id="rentacar-year" name="year" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo $rentacar_service_info['year']; ?>" aria-label="Rent-a-car service year">
              </div>
            </div>
            <!-- Existing Routes Table -->
            <div class="mb-6 overflow-x-auto">
              <h3 class="font-semibold text-lg mb-3">Existing Routes</h3>
              <table class="min-w-full bg-white border border-gray-300 mb-4">
                <thead>
                  <tr class="bg-blue-600 text-white">
                    <th class="py-2 px-4 border-b w-16 text-center" scope="col">#</th>
                    <th class="py-2 px-4 border-b text-left" scope="col">Route</th>
                    <th class="py-2 px-4 border-b text-center" scope="col">GMC 16-19 (PKR)</th>
                    <th class="py-2 px-4 border-b text-center" scope="col">GMC 22-23 (PKR)</th>
                    <th class="py-2 px-4 border-b text-center" scope="col">Coaster (PKR)</th>
                    <th class="py-2 px-4 border-b w-16 text-center" scope="col">Action</th>
                  </tr>
                </thead>
                <tbody id="rentacar-routes-body">
                  <?php if (empty($rentacar_routes)): ?>
                    <tr>
                      <td colspan="6" class="py-4 text-center text-gray-500">No rent a car routes found</td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($rentacar_routes as $index => $route): ?>
                      <tr>
                        <td class="py-2 px-4 border-b text-center">
                          <input type="hidden" name="route_id[<?php echo $index; ?>]" value="<?php echo $route['id']; ?>">
                          <input type="number" name="route_number[<?php echo $index; ?>]" value="<?php echo $route['route_number']; ?>" class="price-input w-16 text-center" aria-label="Route number <?php echo $index + 1; ?>">
                        </td>
                        <td class="py-2 px-4 border-b">
                          <input type="text" name="route_name[<?php echo $index; ?>]" value="<?php echo htmlspecialchars($route['route_name']); ?>" class="price-input w-full" aria-label="Route name <?php echo $index + 1; ?>">
                        </td>
                        <td class="py-2 px-4 border-b">
                          <input type="number" name="gmc_16_19_price[<?php echo $index; ?>]" value="<?php echo $route['gmc_16_19_price']; ?>" step="0.01" class="price-input w-full text-center" aria-label="GMC 16-19 price <?php echo $index + 1; ?>">
                          <span class="text-xs text-gray-500">PKR</span>
                        </td>
                        <td class="py-2 px-4 border-b">
                          <input type="number" name="gmc_22_23_price[<?php echo $index; ?>]" value="<?php echo $route['gmc_22_23_price']; ?>" step="0.01" class="price-input w-full text-center" aria-label="GMC 22-23 price <?php echo $index + 1; ?>">
                          <span class="text-xs text-gray-500">PKR</span>
                        </td>
                        <td class="py-2 px-4 border-b">
                          <input type="number" name="coaster_price[<?php echo $index; ?>]" value="<?php echo $route['coaster_price']; ?>" step="0.01" class="price-input w-full text-center" aria-label="Coaster price <?php echo $index + 1; ?>">
                          <span class="text-xs text-gray-500">PKR</span>
                        </td>
                        <td class="py-2 px-4 border-b text-center">
                          <button type="button" class="text-red-500 hover:text-red-700 focus:outline-none focus:ring-2 focus:ring-red-500" onclick="confirmDeleteRentacarRoute(<?php echo $route['id']; ?>)" aria-label="Delete route <?php echo $index + 1; ?>">
                            <i class="fas fa-trash"></i>
                          </button>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
            <!-- Add New Routes Section -->
            <div class="mb-6">
              <h3 class="font-semibold text-lg mb-3">Add New Routes</h3>
              <table class="min-w-full bg-white border border-gray-300 mb-4">
                <thead>
                  <tr class="bg-blue-600 text-white">
                    <th class="py-2 px-4 border-b w-16 text-center" scope="col">#</th>
                    <th class="py-2 px-4 border-b text-left" scope="col">Route</th>
                    <th class="py-2 px-4 border-b text-center" scope="col">GMC 16-19 (PKR)</th>
                    <th class="py-2 px-4 border-b text-center" scope="col">GMC 22-23 (PKR)</th>
                    <th class="py-2 px-4 border-b text-center" scope="col">Coaster (PKR)</th>
                    <th class="py-2 px-4 border-b w-16 text-center" scope="col">Action</th>
                  </tr>
                </thead>
                <tbody id="new-rentacar-routes-body">
                  <tr>
                    <td class="py-2 px-4 border-b text-center">
                      <input type="number" name="new_route_number[0]" value="<?php echo count($rentacar_routes) + 1; ?>" class="price-input w-16 text-center" aria-label="New route number 1">
                    </td>
                    <td class="py-2 px-4 border-b">
                      <input type="text" name="new_route_name[0]" placeholder="Enter route name" class="price-input w-full" aria-label="New route name 1">
                    </td>
                    <td class="py-2 px-4 border-b">
                      <input type="number" name="new_gmc_16_19_price[0]" placeholder="Price" step="0.01" class="price-input w-full text-center" aria-label="New GMC 16-19 price 1">
                      <span class="text-xs text-gray-500">PKR</span>
                    </td>
                    <td class="py-2 px-4 border-b">
                      <input type="number" name="new_gmc_22_23_price[0]" placeholder="Price" step="0.01" class="price-input w-full text-center" aria-label="New GMC 22-23 price 1">
                      <span class="text-xs text-gray-500">PKR</span>
                    </td>
                    <td class="py-2 px-4 border-b">
                      <input type="number" name="new_coaster_price[0]" placeholder="Price" step="0.01" class="price-input w-full text-center" aria-label="New Coaster price 1">
                      <span class="text-xs text-gray-500">PKR</span>
                    </td>
                    <td class="py-2 px-4 border-b text-center">
                      <button type="button" class="text-red-500 hover:text-red-700 delete-new-row focus:outline-none focus:ring-2 focus:ring-red-500" disabled aria-label="Delete new route 1">
                        <i class="fas fa-trash"></i>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
              <button type="button" id="add-rentacar-row" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2" aria-label="Add another rent-a-car route">
                <i class="fas fa-plus-circle mr-2"></i> Add Another Route
              </button>
            </div>
            <!-- Submit Button -->
            <div class="flex flex-wrap gap-4">
              <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500" aria-label="Save rent-a-car routes">
                <i class="fas fa-save mr-2"></i> Save All Changes
              </button>
            </div>
          </form>
        </div>
      </div>
  </main>
  <script>
    // Tab Switching
    function switchTab(tab) {
      document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
      document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
      document.querySelector(`button[onclick="switchTab('${tab}')"]`).classList.add('active');
      document.getElementById(`${tab}-tab`).classList.add('active');
    }

    // Delete Confirmation
    function confirmDeleteTaxiRoute(id) {
      if (confirm('Are you sure you want to delete this taxi route?')) {
        window.location.href = `add-transportations.php?delete_taxi_route=${id}`;
      }
    }

    function confirmDeleteRentacarRoute(id) {
      if (confirm('Are you sure you want to delete this rent-a-car route?')) {
        window.location.href = `add-transportations.php?delete_rentacar_route=${id}`;
      }
    }

    // Add New Row (Taxi)
    document.getElementById('add-taxi-row').addEventListener('click', function() {
      const tbody = document.getElementById('new-taxi-routes-body');
      const rowCount = tbody.querySelectorAll('tr').length;
      const newRow = document.createElement('tr');
      newRow.innerHTML = `
                    <td class="py-2 px-4 border-b text-center">
                        <input type="number" name="new_route_number[${rowCount}]" value="${parseInt(tbody.lastElementChild.querySelector('input[name^="new_route_number"]').value) + 1}" class="price-input w-16 text-center" aria-label="New route number ${rowCount + 1}">
                    </td>
                    <td class="py-2 px-4 border-b">
                        <input type="text" name="new_route_name[${rowCount}]" placeholder="Enter route name" class="price-input w-full" aria-label="New route name ${rowCount + 1}">
                    </td>
                    <td class="py-2 px-4 border-b">
                        <input type="number" name="new_camry_price[${rowCount}]" placeholder="Price" step="0.01" class="price-input w-full text-center" aria-label="New Camry/Sonata price ${rowCount + 1}">
                        <span class="text-xs text-gray-500">PKR</span>
                    </td>
                    <td class="py-2 px-4 border-b">
                        <input type="number" name="new_starex_price[${rowCount}]" placeholder="Price" step="0.01" class="price-input w-full text-center" aria-label="New Starex/Staria price ${rowCount + 1}">
                        <span class="text-xs text-gray-500">PKR</span>
                    </td>
                    <td class="py-2 px-4 border-b">
                        <input type="number" name="new_hiace_price[${rowCount}]" placeholder="Price" step="0.01" class="price-input w-full text-center" aria-label="New Hiace price ${rowCount + 1}">
                        <span class="text-xs text-gray-500">PKR</span>
                    </td>
                    <td class="py-2 px-4 border-b text-center">
                        <button type="button" class="text-red-500 hover:text-red-700 delete-new-row focus:outline-none focus:ring-2 focus:ring-red-500" aria-label="Delete new route ${rowCount + 1}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;
      tbody.appendChild(newRow);
      updateDeleteButtons(tbody);
    });

    // Add New Row (Rent-a-car)
    document.getElementById('add-rentacar-row').addEventListener('click', function() {
      const tbody = document.getElementById('new-rentacar-routes-body');
      const rowCount = tbody.querySelectorAll('tr').length;
      const newRow = document.createElement('tr');
      newRow.innerHTML = `
                    <td class="py-2 px-4 border-b text-center">
                        <input type="number" name="new_route_number[${rowCount}]" value="${parseInt(tbody.lastElementChild.querySelector('input[name^="new_route_number"]').value) + 1}" class="price-input w-16 text-center" aria-label="New route number ${rowCount + 1}">
                    </td>
                    <td class="py-2 px-4 border-b">
                        <input type="text" name="new_route_name[${rowCount}]" placeholder="Enter route name" class="price-input w-full" aria-label="New route name ${rowCount + 1}">
                    </td>
                    <td class="py-2 px-4 border-b">
                        <input type="number" name="new_gmc_16_19_price[${rowCount}]" placeholder="Price" step="0.01" class="price-input w-full text-center" aria-label="New GMC 16-19 price ${rowCount + 1}">
                        <span class="text-xs text-gray-500">PKR</span>
                    </td>
                    <td class="py-2 px-4 border-b">
                        <input type="number" name="new_gmc_22_23_price[${rowCount}]" placeholder="Price" step="0.01" class="price-input w-full text-center" aria-label="New GMC 22-23 price ${rowCount + 1}">
                        <span class="text-xs text-gray-500">PKR</span>
                    </td>
                    <td class="py-2 px-4 border-b">
                        <input type="number" name="new_coaster_price[${rowCount}]" placeholder="Price" step="0.01" class="price-input w-full text-center" aria-label="New Coaster price ${rowCount + 1}">
                        <span class="text-xs text-gray-500">PKR</span>
                    </td>
                    <td class="py-2 px-4 border-b text-center">
                        <button type="button" class="text-red-500 hover:text-red-700 delete-new-row focus:outline-none focus:ring-2 focus:ring-red-500" aria-label="Delete new route ${rowCount + 1}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;
      tbody.appendChild(newRow);
      updateDeleteButtons(tbody);
    });

    // Update Delete Buttons
    function updateDeleteButtons(tbody) {
      const rows = tbody.querySelectorAll('tr');
      rows.forEach((row, index) => {
        const deleteBtn = row.querySelector('.delete-new-row');
        deleteBtn.disabled = rows.length === 1;
        deleteBtn.onclick = () => {
          if (rows.length > 1) {
            row.remove();
            updateRowIndices(tbody);
          }
        };
      });
    }

    // Update Row Indices
    function updateRowIndices(tbody) {
      tbody.querySelectorAll('tr').forEach((row, index) => {
        row.querySelector('input[name^="new_route_number"]').name = `new_route_number[${index}]`;
        row.querySelector('input[name^="new_route_name"]').name = `new_route_name[${index}]`;
        if (tbody.id === 'new-taxi-routes-body') {
          row.querySelector('input[name^="new_camry_price"]').name = `new_camry_price[${index}]`;
          row.querySelector('input[name^="new_starex_price"]').name = `new_starex_price[${index}]`;
          row.querySelector('input[name^="new_hiace_price"]').name = `new_hiace_price[${index}]`;
        } else {
          row.querySelector('input[name^="new_gmc_16_19_price"]').name = `new_gmc_16_19_price[${index}]`;
          row.querySelector('input[name^="new_gmc_22_23_price"]').name = `new_gmc_22_23_price[${index}]`;
          row.querySelector('input[name^="new_coaster_price"]').name = `new_coaster_price[${index}]`;
        }
        row.querySelector('input[name^="new_route_number"]').setAttribute('aria-label', `New route number ${index + 1}`);
        row.querySelector('input[name^="new_route_name"]').setAttribute('aria-label', `New route name ${index + 1}`);
        row.querySelector('input[name$="_price[0]"]').setAttribute('aria-label', `New ${tbody.id === 'new-taxi-routes-body' ? 'Camry/Sonata' : 'GMC 16-19'} price ${index + 1}`);
        row.querySelector('input[name$="_price[0]"]:nth-of-type(2)').setAttribute('aria-label', `New ${tbody.id === 'new-taxi-routes-body' ? 'Starex/Staria' : 'GMC 22-23'} price ${index + 1}`);
        row.querySelector('input[name$="_price[0]"]:nth-of-type(3)').setAttribute('aria-label', `New ${tbody.id === 'new-taxi-routes-body' ? 'Hiace' : 'Coaster'} price ${index + 1}`);
        row.querySelector('.delete-new-row').setAttribute('aria-label', `Delete new route ${index + 1}`);
      });
    }

    // Sidebar Toggle
    document.addEventListener('DOMContentLoaded', function() {
      document.getElementById('menu-btn').addEventListener('click', function() {
        document.querySelector('aside').classList.toggle('hidden');
      });
    });
  </script>
</body>

</html>