<?php
require_once '../config/db.php';
session_name('admin_session');
session_start();

if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
  header('Location: login.php');
  exit;
}

// Check database connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Create transportation_settings table if it doesn't exist
$create_settings_table_sql = "
CREATE TABLE IF NOT EXISTS transportation_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_type VARCHAR(20) NOT NULL,
    service_title VARCHAR(255) NOT NULL,
    year INT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_service_type (service_type)
)";
if (!$conn->query($create_settings_table_sql)) {
  die("Error creating transportation_settings table: " . $conn->error);
}

// Create taxi_routes table if it doesn't exist
$create_taxi_routes_table_sql = "
CREATE TABLE IF NOT EXISTS taxi_routes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_title VARCHAR(255) NOT NULL,
    year INT NOT NULL,
    route_number INT NOT NULL,
    route_name VARCHAR(255) NOT NULL,
    camry_sonata_price DECIMAL(10,2) NOT NULL,
    starex_staria_price DECIMAL(10,2) NOT NULL,
    hiace_price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if (!$conn->query($create_taxi_routes_table_sql)) {
  die("Error creating taxi_routes table: " . $conn->error);
}

// Create rentacar_routes table if it doesn't exist
$create_rentacar_routes_table_sql = "
CREATE TABLE IF NOT EXISTS rentacar_routes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_title VARCHAR(255) NOT NULL,
    year INT NOT NULL,
    route_number INT NOT NULL,
    route_name VARCHAR(255) NOT NULL,
    gmc_16_19_price DECIMAL(10,2) NOT NULL,
    gmc_22_23_price DECIMAL(10,2) NOT NULL,
    coaster_price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if (!$conn->query($create_rentacar_routes_table_sql)) {
  die("Error creating rentacar_routes table: " . $conn->error);
}

// Initialize variables
$success_message = '';
$error_message = '';

// Get the current year dynamically
$current_year = date('Y'); // Will be 2025 as of May 05, 2025

// Handle Taxi Routes Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_taxi_routes'])) {
  error_log("Taxi Routes POST Data: " . print_r($_POST, true));
  $service_title = $conn->real_escape_string($_POST['serviceTitle']);
  $year = (int)$_POST['year'];
  $stmt = $conn->prepare("INSERT INTO transportation_settings (service_type, service_title, year) VALUES ('taxi', ?, ?) 
                          ON DUPLICATE KEY UPDATE service_title = ?, year = ?, updated_at = NOW()");
  $stmt->bind_param("sisi", $service_title, $year, $service_title, $year);
  if (!$stmt->execute()) {
    $error_message = "Error updating taxi settings: " . $stmt->error;
    error_log("Taxi Settings Error: " . $stmt->error);
  } else {
    error_log("Taxi settings updated successfully for service_title: $service_title, year: $year");
  }
  $stmt->close();
  $new_routes = [];
  if (isset($_POST['new_route_name']) && is_array($_POST['new_route_name'])) {
    foreach ($_POST['new_route_name'] as $key => $route_name) {
      if (empty($route_name)) continue;
      $route_name = $conn->real_escape_string($route_name);
      $route_number = (int)$_POST['new_route_number'][$key];
      $camry_price = (float)$_POST['new_camry_price'][$key];
      $starex_price = (float)$_POST['new_starex_price'][$key];
      $hiace_price = (float)$_POST['new_hiace_price'][$key];
      $new_routes[] = "('$service_title', $year, $route_number, '$route_name', $camry_price, $starex_price, $hiace_price, NOW())";
    }
  }
  if (!empty($new_routes)) {
    $insert_sql = "INSERT INTO taxi_routes 
                      (service_title, year, route_number, route_name, camry_sonata_price, starex_staria_price, hiace_price, created_at) 
                      VALUES " . implode(',', $new_routes);
    if (!$conn->query($insert_sql)) {
      $error_message = "Error adding taxi routes: " . $conn->error;
      error_log("Taxi Routes Insert Error: " . $conn->error);
    } else {
      $success_message = "Taxi routes added successfully!";
      error_log("Taxi routes inserted successfully: " . implode(',', $new_routes));
    }
  } else {
    $success_message = "Taxi settings updated, but no new routes were added.";
    error_log("No new taxi routes to insert.");
  }
}

// Handle Rent-a-Car Routes Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_rentacar_routes'])) {
  error_log("Rent-a-Car Routes POST Data: " . print_r($_POST, true));
  $service_title = $conn->real_escape_string($_POST['serviceTitle']);
  $year = (int)$_POST['year'];
  $stmt = $conn->prepare("INSERT INTO transportation_settings (service_type, service_title, year) VALUES ('rentacar', ?, ?) 
                          ON DUPLICATE KEY UPDATE service_title = ?, year = ?, updated_at = NOW()");
  $stmt->bind_param("sisi", $service_title, $year, $service_title, $year);
  if (!$stmt->execute()) {
    $error_message = "Error updating rent-a-car settings: " . $stmt->error;
    error_log("Rent-a-Car Settings Error: " . $stmt->error);
  } else {
    error_log("Rent-a-Car settings updated successfully for service_title: $service_title, year: $year");
  }
  $stmt->close();
  $new_routes = [];
  if (isset($_POST['new_route_name']) && is_array($_POST['new_route_name'])) {
    foreach ($_POST['new_route_name'] as $key => $route_name) {
      if (empty($route_name)) continue;
      $route_name = $conn->real_escape_string($route_name);
      $route_number = (int)$_POST['new_route_number'][$key];
      $gmc_16_19_price = (float)$_POST['new_gmc_16_19_price'][$key];
      $gmc_22_23_price = (float)$_POST['new_gmc_22_23_price'][$key];
      $coaster_price = (float)$_POST['new_coaster_price'][$key];
      $new_routes[] = "('$service_title', $year, $route_number, '$route_name', $gmc_16_19_price, $gmc_22_23_price, $coaster_price, NOW())";
    }
  }
  if (!empty($new_routes)) {
    $insert_sql = "INSERT INTO rentacar_routes 
                      (service_title, year, route_number, route_name, gmc_16_19_price, gmc_22_23_price, coaster_price, created_at) 
                      VALUES " . implode(',', $new_routes);
    if (!$conn->query($insert_sql)) {
      $error_message = "Error adding rent-a-car routes: " . $conn->error;
      error_log("Rent-a-Car Routes Insert Error: " . $conn->error);
    } else {
      $success_message = "Rent-a-car routes added successfully!";
      error_log("Rent-a-Car routes inserted successfully: " . implode(',', $new_routes));
    }
  } else {
    $success_message = "Rent-a-car settings updated, but no new routes were added.";
    error_log("No new rent-a-car routes to insert.");
  }
}

// Get service info from the database, set default year to current year
$taxi_service_info = ['service_title' => 'Best Taxi Service for Umrah and Hajj', 'year' => $current_year];
$rentacar_service_info = ['service_title' => 'Best Umrah and Hajj Rent A Car', 'year' => $current_year];

// Fetch taxi service info
$stmt = $conn->prepare("SELECT service_title, year FROM transportation_settings WHERE service_type = ? LIMIT 1");
$stmt->bind_param("s", $service_type);
$service_type = 'taxi';
$stmt->execute();
$result = $stmt->get_result();
if ($data = $result->fetch_assoc()) {
  $taxi_service_info['service_title'] = $data['service_title'];
  $taxi_service_info['year'] = $data['year'] < $current_year ? $current_year : $data['year'];
}

// Fetch rentacar service info
$service_type = 'rentacar';
$stmt->execute();
$result = $stmt->get_result();
if ($data = $result->fetch_assoc()) {
  $rentacar_service_info['service_title'] = $data['service_title'];
  $rentacar_service_info['year'] = $data['year'] < $current_year ? $current_year : $data['year'];
}
$stmt->close();

// Function to format large numbers into K, M, B suffixes
function formatNumber($number)
{
  if ($number === null || $number == 0 || !is_numeric($number)) {
    return 'N/A';
  }
  $number = (float)$number;
  $suffixes = ['', 'K', 'M', 'B', 'T'];
  $index = 0;
  while ($number >= 1000 && $index < count($suffixes) - 1) {
    $number /= 1000;
    $index++;
  }
  $formattedNumber = round($number, 1);
  if ($formattedNumber == round($formattedNumber)) {
    $formattedNumber = (int)$formattedNumber;
  }
  return $formattedNumber . $suffixes[$index];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Transportation | UmrahFlights</title>
  <link rel="stylesheet" href="../src/output.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    .tab-buttons {
      display: flex;
      flex-direction: column;
      gap: 8px;
      margin-bottom: 16px;
    }
    @media (min-width: 640px) {
      .tab-buttons {
        flex-direction: row;
        gap: 10px;
        justify-content: center;
      }
    }
    .tab-btn {
      padding: 12px 16px;
      border-radius: 6px;
      font-weight: 600;
      font-size: 0.875rem;
      cursor: pointer;
      transition: all 0.3s ease;
      border: none;
      width: 100%;
      text-align: center;
    }
    @media (min-width: 640px) {
      .tab-btn {
        padding: 10px 20px;
        width: auto;
      }
    }
    .tab-btn.active {
      background-color: #3b82f6;
      color: white;
    }
    .tab-btn:not(.active) {
      background-color: #e2e8f0;
      color: #1e293b;
    }
    .tab-btn:hover:not(.active) {
      background-color: #cbd5e1;
    }
    .tab-content {
      display: none;
    }
    .tab-content.active {
      display: block;
      animation: fadeIn 0.5s ease-in-out;
    }
    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    .form-container {
      display: flex;
      flex-direction: column;
      gap: 16px;
      margin-bottom: 24px;
    }
    .form-field {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }
    .form-field label {
      font-size: 0.875rem;
      font-weight: 500;
      color: #374151;
    }
    .form-field input {
      width: 100%;
      padding: 10px 12px;
      border: 1px solid #d1d5db;
      border-radius: 0.375rem;
      font-size: 0.875rem;
      line-height: 1.25rem;
    }
    .form-field input:focus {
      outline: 2px solid #3b82f6;
      border-color: #3b82f6;
    }
    .price-input {
      padding: 8px 10px;
      border: 1px solid #d1d5db;
      border-radius: 0.375rem;
      font-size: 0.75rem;
      line-height: 1.25rem;
      width: 100%;
    }
    @media (min-width: 640px) {
      .price-input {
        font-size: 0.875rem;
        padding: 8px 12px;
      }
    }
    .price-input:focus {
      outline: 2px solid #3b82f6;
      border-color: #3b82f6;
    }
    .rentacar-input:focus {
      outline: 2px solid #1d4ed8;
      border-color: #1d4ed8;
    }
    table {
      min-width: 100%;
      width: max-content;
    }
    th, td {
      padding: 6px;
      font-size: 0.75rem;
      white-space: nowrap;
    }
    @media (min-width: 640px) {
      th, td {
        padding: 8px;
        font-size: 0.875rem;
      }
    }
    th {
      position: sticky;
      top: 0;
      z-index: 10;
    }
    .action-btn {
      padding: 6px;
      font-size: 0.75rem;
      line-height: 1;
    }
    @media (min-width: 640px) {
      .action-btn {
        padding: 8px;
        font-size: 0.875rem;
      }
    }
    .error-msg-name, .error-msg-base, .error-msg-mid, .error-msg-premium {
      font-size: 0.6875rem;
      line-height: 1.25rem;
      margin-top: 4px;
    }
    @media (min-width: 640px) {
      .error-msg-name, .error-msg-base, .error-msg-mid, .error-msg-premium {
        font-size: 0.75rem;
      }
    }
    .action-buttons {
      display: flex;
      flex-direction: column;
      gap: 8px;
      width: 100%;
    }
    @media (min-width: 640px) {
      .action-buttons {
        flex-direction: row;
        gap: 12px;
        width: auto;
        justify-content: flex-end;
      }
    }
    .action-buttons button {
      width: 100%;
      padding: 12px 16px;
      font-size: 0.875rem;
      border-radius: 0.375rem;
      text-align: center;
    }
    @media (min-width: 640px) {
      .action-buttons button {
        width: auto;
        padding: 8px 24px;
      }
    }
    .table-container {
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
      max-width: 100%;
    }
  </style>
</head>

<body class="bg-gray-100 font-sans">
  <?php include 'includes/sidebar.php'; ?>
  <main class="ml-0 md:ml-64 mt-10 px-4 sm:px-6 lg:px-8 transition-all duration-300" role="main" aria-label="Main content">
    <nav class="bg-white shadow-lg rounded-lg p-5 mb-6">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <button id="sidebarToggle" class="text-gray-500 hover:text-gray-700 focus:outline-none md:hidden" aria-label="Toggle sidebar">
            <i class="fas fa-bars text-xl"></i>
          </button>
          <h4 id="dashboardHeader" class="text-lg font-semibold text-gray-800 cursor-pointer hover:text-indigo-600">Add Transportation</h4>
        </div>
        <div class="flex items-center space-x-4">
          <div class="relative">
            <button id="userDropdownButton" class="flex items-center space-x-2 text-gray-700 hover:bg-indigo-50 rounded-lg px-3 py-2 focus:outline-none" aria-label="User menu" aria-expanded="false">
              <div class="rounded-full overflow-hidden" style="width: 32px; height: 32px;">
                <div class="bg-gray-200 w-full h-full"></div>
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
    <?php if ($success_message): ?>
      <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-6 flex justify-between items-center" role="alert">
        <span><?php echo htmlspecialchars($success_message); ?></span>
        <button class="text-green-700 hover:text-green-900 focus:outline-none focus:ring-2 focus:ring-green-500" onclick="this.parentElement.remove()" aria-label="Close alert">
          <i class="fas fa-times"></i>
        </button>
      </div>
    <?php endif; ?>
    <?php if ($error_message): ?>
      <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-6 flex justify-between items-center" role="alert">
        <span><?php echo htmlspecialchars($error_message); ?></span>
        <button class="text-red-700 hover:text-red-900 focus:outline-none focus:ring-2 focus:ring-red-500" onclick="this.parentElement.remove()" aria-label="Close alert">
          <i class="fas fa-times"></i>
        </button>
      </div>
    <?php endif; ?>
    <section class="bg-white shadow-lg rounded-lg p-4 sm:p-6" aria-label="Transportation management">
      <div class="tab-buttons">
        <button class="tab-btn active" onclick="switchTab('taxi')">Taxi Routes</button>
        <button class="tab-btn" onclick="switchTab('rentacar')">Rent A Car Routes</button>
      </div>
      <div id="taxi-tab" class="tab-content active">
        <div class="mb-6">
          <h2 class="text-xl sm:text-2xl font-bold text-gray-800">Add Taxi Routes</h2>
          <p class="text-gray-600 mt-2 text-sm sm:text-base">Add new taxi service routes and prices</p>
        </div>
        <form action="" method="POST" id="taxi-routes-form">
          <input type="hidden" name="update_taxi_routes" value="1">
          <div class="form-container">
            <div class="form-field">
              <label for="taxi-service-title">Service Title</label>
              <input type="text" id="taxi-service-title" name="serviceTitle" value="<?php echo htmlspecialchars($taxi_service_info['service_title']); ?>" required>
            </div>
            <div class="form-field">
              <label for="taxi-year">Year</label>
              <input type="number" id="taxi-year" name="year" min="<?php echo $current_year; ?>" max="2030" value="<?php echo $taxi_service_info['year']; ?>" required>
            </div>
          </div>
          <div class="table-container mb-6">
            <h3 class="font-semibold text-lg text-gray-800 mb-3">New Routes</h3>
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="bg-indigo-600 text-white">
                  <th class="w-12 text-center">#</th>
                  <th class="min-w-[120px] text-left">Route</th>
                  <th class="w-24 text-center">Camry / Sonata (PKR)</th>
                  <th class="w-24 text-center">Starex / Staria (PKR)</th>
                  <th class="w-24 text-center">Hiace (PKR)</th>
                  <th class="w-12 text-center">Action</th>
                </tr>
              </thead>
              <tbody id="new-taxi-routes-body">
                <tr class="price-validation-row border-b hover:bg-indigo-50">
                  <td class="text-center">
                    <input type="number" name="new_route_number[0]" value="1" class="price-input w-12 text-center" required>
                  </td>
                  <td>
                    <input type="text" name="new_route_name[0]" placeholder="Enter route name" class="price-input w-full route-name" pattern="[A-Za-z\s]+" title="Only letters are allowed" maxlength="15" oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '')" required>
                    <div class="text-red-500 error-msg-name hidden">Only letters allowed (max 15 chars)</div>
                  </td>
                  <td>
                    <input type="number" name="new_camry_price[0]" placeholder="Price" min="0" step="0.01" class="price-input w-full text-center base-price" oninput="validateNewCarPrices(this)" required>
                    <!--<span class="text-xs text-gray-500 block mt-1">PKR</span>-->
                    <div class="text-red-500 error-msg-base hidden">Must be greater than 0</div>
                  </td>
                  <td>
                    <input type="number" name="new_starex_price[0]" placeholder="Price" min="0" step="0.01" class="price-input w-full text-center mid-price" oninput="validateNewCarPrices(this)" disabled required>
                    <!--<span class="text-xs text-gray-500 block mt-1">PKR</span>-->
                    <div class="text-red-500 error-msg-mid hidden">Must be higher than base price</div>
                  </td>
                  <td>
                    <input type="number" name="new_hiace_price[0]" placeholder="Price" min="0" step="0.01" class="price-input w-full text-center premium-price" oninput="validateNewCarPrices(this)" disabled required>
                    <!--<span class="text-xs text-gray-500 block mt-1">PKR</span>-->
                    <div class="text-red-500 error-msg-premium hidden">Must be higher than mid price</div>
                  </td>
                  <td class="text-center">
                    <button type="button" class="text-red-500 hover:text-red-700 delete-new-row action-btn" disabled><i class="fas fa-trash"></i></button>
                  </td>
                </tr>
              </tbody>
            </table>
            <div class="action-buttons mt-4">
              <button type="button" id="add-taxi-row" class="bg-indigo-600 text-white hover:bg-indigo-700"><i class="fas fa-plus-circle mr-2"></i>Add Another Route</button>
            </div>
          </div>
          <div class="action-buttons">
            <button type="submit" class="bg-indigo-600 text-white hover:bg-indigo-700"><i class="fas fa-save mr-2"></i>Save Routes</button>
          </div>
        </form>
      </div>
      <div id="rentacar-tab" class="tab-content">
        <div class="mb-6">
          <h2 class="text-xl sm:text-2xl font-bold text-gray-800">Add Rent A Car Routes</h2>
          <p class="text-gray-600 mt-2 text-sm sm:text-base">Add new rent a car service routes and prices</p>
        </div>
        <form action="" method="POST" id="rentacar-routes-form">
          <input type="hidden" name="update_rentacar_routes" value="1">
          <div class="form-container">
            <div class="form-field">
              <label for="rentacar-service-title">Service Title</label>
              <input type="text" id="rentacar-service-title" name="serviceTitle" value="<?php echo htmlspecialchars($rentacar_service_info['service_title']); ?>" required>
            </div>
            <div class="form-field">
              <label for="rentacar-year">Year</label>
              <input type="number" id="rentacar-year" name="year" min="<?php echo $current_year; ?>" max="2030" value="<?php echo $rentacar_service_info['year']; ?>" required>
            </div>
          </div>
          <div class="table-container mb-6">
            <h3 class="font-semibold text-lg text-gray-800 mb-3">New Routes</h3>
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="bg-indigo-600 text-white">
                  <th class="w-12 text-center">#</th>
                  <th class="min-w-[120px] text-left">Route</th>
                  <th class="w-24 text-center">GMC 16-19 (PKR)</th>
                  <th class="w-24 text-center">GMC 22-23 (PKR)</th>
                  <th class="w-24 text-center">COASTER (PKR)</th>
                  <th class="w-12 text-center">Action</th>
                </tr>
              </thead>
              <tbody id="new-rentacar-routes-body">
                <tr class="price-validation-row border-b hover:bg-indigo-50">
                  <td class="text-center">
                    <input type="number" name="new_route_number[0]" value="1" class="price-input rentacar-input w-12 text-center" required>
                  </td>
                  <td>
                    <input type="text" name="new_route_name[0]" placeholder="Enter route name" class="price-input rentacar-input w-full route-name" pattern="[A-Za-z\s]+" title="Only letters are allowed" maxlength="15" oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '')" required>
                    <div class="text-red-500 error-msg-name hidden">Only letters allowed (max 15 chars)</div>
                  </td>
                  <td>
                    <input type="number" name="new_gmc_16_19_price[0]" placeholder="Price" min="0" step="0.01" class="price-input rentacar-input w-full text-center base-price" oninput="validateNewCarPrices(this)" required>
                    <!--<span class="text-xs text-gray-500 block mt-1">PKR</span>-->
                    <div class="text-red-500 error-msg-base hidden">Must be greater than 0</div>
                  </td>
                  <td>
                    <input type="number" name="new_gmc_22_23_price[0]" placeholder="Price" min="0" step="0.01" class="price-input rentacar-input w-full text-center mid-price" oninput="validateNewCarPrices(this)" disabled required>
                    <!--<span class="text-xs text-gray-500 block mt-1">PKR</span>-->
                    <div class="text-red-500 error-msg-mid hidden">Must be higher than base price</div>
                  </td>
                  <td>
                    <input type="number" name="new_coaster_price[0]" placeholder="Price" min="0" step="0.01" class="price-input rentacar-input w-full text-center premium-price" oninput="validateNewCarPrices(this)" disabled required>
                    <!--<span class="text-xs text-gray-500 block mt-1">PKR</span>-->
                    <div class="text-red-500 error-msg-premium hidden">Must be higher than mid price</div>
                  </td>
                  <td class="text-center">
                    <button type="button" class="text-red-500 hover:text-red-700 delete-new-row action-btn" disabled><i class="fas fa-trash"></i></button>
                  </td>
                </tr>
              </tbody>
            </table>
            <div class="action-buttons mt-4">
              <button type="button" id="add-rentacar-row" class="bg-indigo-600 text-white hover:bg-indigo-700"><i class="fas fa-plus-circle mr-2"></i>Add Another Route</button>
            </div>
          </div>
          <div class="action-buttons">
            <button type="submit" class="bg-indigo-600 text-white hover:bg-indigo-700"><i class="fas fa-save mr-2"></i>Save Routes</button>
          </div>
        </form>
      </div>
    </section>
  </main>
  <script>
function switchTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.getElementById(tabName + '-tab').classList.add('active');
    document.querySelector(`.tab-btn[onclick="switchTab('${tabName}')"]`).classList.add('active');
    localStorage.setItem('activeTransportTab', tabName);
}

function validateNewCarPrices(input) {
    const row = input.closest('.price-validation-row');
    const basePriceInput = row.querySelector('.base-price');
    const midPriceInput = row.querySelector('.mid-price');
    const premiumPriceInput = row.querySelector('.premium-price');
    const basePrice = parseFloat(basePriceInput.value) || 0;
    const midPrice = parseFloat(midPriceInput.value) || 0;
    const premiumPrice = parseFloat(premiumPriceInput.value) || 0;
    row.querySelector('.error-msg-base').classList.add('hidden');
    row.querySelector('.error-msg-mid').classList.add('hidden');
    row.querySelector('.error-msg-premium').classList.add('hidden');
    if (input.classList.contains('base-price')) {
        if (basePrice <= 0) {
            row.querySelector('.error-msg-base').classList.remove('hidden');
            midPriceInput.disabled = true;
            premiumPriceInput.disabled = true;
            midPriceInput.value = '';
            premiumPriceInput.value = '';
        } else {
            midPriceInput.disabled = false;
        }
    }
    if (input.classList.contains('mid-price')) {
        if (midPrice <= basePrice) {
            row.querySelector('.error-msg-mid').classList.remove('hidden');
            premiumPriceInput.disabled = true;
            premiumPriceInput.value = '';
        } else {
            premiumPriceInput.disabled = false;
        }
    }
    if (input.classList.contains('premium-price')) {
        if (premiumPrice <= midPrice) {
            row.querySelector('.error-msg-premium').classList.remove('hidden');
        }
    }
}

function formatNumber(number) {
    if (number === null || number == 0 || isNaN(number)) {
        return 'N/A';
    }
    const suffixes = ['', 'K', 'M', 'B', 'T'];
    let index = 0;
    while (number >= 1000 && index < suffixes.length - 1) {
        number /= 1000;
        index++;
    }
    let formattedNumber = Math.round(number * 10) / 10;
    if (formattedNumber % 1 === 0) {
        formattedNumber = Math.round(formattedNumber);
    }
    return formattedNumber + suffixes[index];
}

function setupDeleteHandlers() {
    console.log('Setting up delete handlers');
    document.querySelectorAll('.delete-new-row').forEach(button => {
        if (!button.hasAttribute('disabled')) {
            button.addEventListener('click', function() {
                console.log('Delete button clicked');
                const row = this.closest('tr');
                const tbody = row.parentNode;
                tbody.removeChild(row);
                const rows = tbody.querySelectorAll('tr');
                rows.forEach((row, index) => {
                    const input = row.querySelector('input[type="number"]');
                    if (input && input.name.includes('new_route_number')) {
                        input.value = index + 1;
                    }
                });
            });
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const storedTab = localStorage.getItem('activeTransportTab');
    if (storedTab) {
        switchTab(storedTab);
    }

    const addTaxiRowButton = document.getElementById('add-taxi-row');
    const addRentacarRowButton = document.getElementById('add-rentacar-row');
    const taxiTbody = document.getElementById('new-taxi-routes-body');
    const rentacarTbody = document.getElementById('new-rentacar-routes-body');

    if (!addTaxiRowButton || !addRentacarRowButton || !taxiTbody || !rentacarTbody) {
        console.error('One or more required DOM elements are missing:', {
            addTaxiRowButton: !!addTaxiRowButton,
            addRentacarRowButton: !!addRentacarRowButton,
            taxiTbody: !!taxiTbody,
            rentacarTbody: !!rentacarTbody
        });
        return;
    }

    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebar-overlay');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarClose = document.getElementById('sidebar-close');
    const dashboardHeader = document.getElementById('dashboardHeader');
    const userDropdownButton = document.getElementById('userDropdownButton');
    const userDropdownMenu = document.getElementById('userDropdownMenu');

    if (!sidebar || !sidebarOverlay || !sidebarToggle || !sidebarClose) {
        console.warn('One or more sidebar elements are missing.');
    }
    if (!userDropdownButton || !userDropdownMenu) {
        console.warn('User dropdown elements are missing.');
    }
    if (!dashboardHeader) {
        console.warn('Dashboard header element is missing.');
    }

    if (sidebar && sidebarOverlay && sidebarToggle && sidebarClose) {
        const toggleSidebar = () => {
            sidebar.classList.toggle('-translate-x-full');
            sidebarOverlay.classList.toggle('hidden');
            sidebarToggle.classList.toggle('hidden');
        };
        sidebarToggle.addEventListener('click', toggleSidebar);
        sidebarClose.addEventListener('click', toggleSidebar);
        sidebarOverlay.addEventListener('click', toggleSidebar);
        dashboardHeader.addEventListener('click', () => {
            if (sidebar.classList.contains('-translate-x-full')) {
                toggleSidebar();
            }
        });
    }

    if (userDropdownButton && userDropdownMenu) {
        userDropdownButton.addEventListener('click', () => {
            userDropdownMenu.classList.toggle('hidden');
        });
        document.addEventListener('click', (event) => {
            if (!userDropdownButton.contains(event.target) && !userDropdownMenu.contains(event.target)) {
                userDropdownMenu.classList.add('hidden');
            }
        });
    }

    document.querySelectorAll('.route-name').forEach(input => {
        input.addEventListener('input', function() {
            const errorMsg = this.closest('td').querySelector('.error-msg-name');
            if (!/^[A-Za-z\s]{0,15}$/.test(this.value)) {
                errorMsg.classList.remove('hidden');
            } else {
                errorMsg.classList.add('hidden');
            }
        });
    });

    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            form.querySelectorAll('.price-validation-row').forEach(row => {
                const routeName = row.querySelector('.route-name').value;
                if (!/^[A-Za-z\s]{1,15}$/.test(routeName)) {
                    row.querySelector('.error-msg-name').classList.remove('hidden');
                    isValid = false;
                }
                const basePrice = parseFloat(row.querySelector('.base-price').value) || 0;
                const midPrice = parseFloat(row.querySelector('.mid-price').value) || 0;
                const premiumPrice = parseFloat(row.querySelector('.premium-price').value) || 0;
                if (basePrice <= 0) {
                    row.querySelector('.error-msg-base').classList.remove('hidden');
                    isValid = false;
                }
                if (midPrice <= basePrice) {
                    row.querySelector('.error-msg-mid').classList.remove('hidden');
                    isValid = false;
                }
                if (premiumPrice <= midPrice) {
                    row.querySelector('.error-msg-premium').classList.remove('hidden');
                    isValid = false;
                }
            });
            if (!isValid) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: 'Please fix all validation errors before submitting.'
                });
            }
        });
    });

    addTaxiRowButton.addEventListener('click', function() {
        console.log('Add Taxi Row button clicked');
        const rowCount = taxiTbody.rows.length;
        const newIndex = rowCount;
        const newRowNumber = rowCount + 1;
        const newRow = document.createElement('tr');
        newRow.classList.add('price-validation-row', 'border-b', 'hover:bg-indigo-50');
        newRow.innerHTML = `
            <td class="text-center">
                <input type="number" name="new_route_number[${newIndex}]" value="${newRowNumber}" class="price-input w-12 text-center" required>
            </td>
            <td>
                <input type="text" name="new_route_name[${newIndex}]" placeholder="Enter route name" class="price-input w-full route-name" pattern="[A-Za-z\s]+" title="Only letters are allowed" maxlength="15" oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '')" required>
                <div class="text-red-500 error-msg-name hidden">Only letters allowed (max 15 chars)</div>
            </td>
            <td>
                <input type="number" name="new_camry_price[${newIndex}]" placeholder="Price" min="0" step="0.01" class="price-input w-full text-center base-price" oninput="validateNewCarPrices(this)" required>
                <div class="text-red-500 error-msg-base hidden">Must be greater than 0</div>
            </td>
            <td>
                <input type="number" name="new_starex_price[${newIndex}]" placeholder="Price" min="0" step="0.01" class="price-input w-full text-center mid-price" oninput="validateNewCarPrices(this)" disabled required>
                <div class="text-red-500 error-msg-mid hidden">Must be higher than base price</div>
            </td>
            <td>
                <input type="number" name="new_hiace_price[${newIndex}]" placeholder="Price" min="0" step="0.01" class="price-input w-full text-center premium-price" oninput="validateNewCarPrices(this)" disabled required>
                <div class="text-red-500 error-msg-premium hidden">Must be higher than mid price</div>
            </td>
            <td class="text-center">
                <button type="button" class="text-red-500 hover:text-red-700 delete-new-row action-btn"><i class="fas fa-trash"></i></button>
            </td>
        `;
        taxiTbody.appendChild(newRow);
        setupDeleteHandlers();
    });

    addRentacarRowButton.addEventListener('click', function() {
        console.log('Add Rentacar Row button clicked');
        const rowCount = rentacarTbody.rows.length;
        const newIndex = rowCount;
        const newRowNumber = rowCount + 1;
        const newRow = document.createElement('tr');
        newRow.classList.add('price-validation-row', 'border-b', 'hover:bg-indigo-50');
        newRow.innerHTML = `
            <td class="text-center">
                <input type="number" name="new_route_number[${newIndex}]" value="${newRowNumber}" class="price-input rentacar-input w-12 text-center" required>
            </td>
            <td>
                <input type="text" name="new_route_name[${newIndex}]" placeholder="Enter route name" class="price-input rentacar-input w-full route-name" pattern="[A-Za-z\s]+" title="Only letters are allowed" maxlength="15" oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '')" required>
                <div class="text-red-500 error-msg-name hidden">Only letters allowed (max 15 chars)</div>
            </td>
            <td>
                <input type="number" name="new_gmc_16_19_price[${newIndex}]" placeholder="Price" min="0" step="0.01" class="price-input rentacar-input w-full text-center base-price" oninput="validateNewCarPrices(this)" required>
                <div class="text-red-500 error-msg-base hidden">Must be greater than 0</div>
            </td>
            <td>
                <input type="number" name="new_gmc_22_23_price[${newIndex}]" placeholder="Price" min="0" step="0.01" class="price-input rentacar-input w-full text-center mid-price" oninput="validateNewCarPrices(this)" disabled required>
                <div class="text-red-500 error-msg-mid hidden">Must be higher than base price</div>
            </td>
            <td>
                <input type="number" name="new_coaster_price[${newIndex}]" placeholder="Price" min="0" step="0.01" class="price-input rentacar-input w-full text-center premium-price" oninput="validateNewCarPrices(this)" disabled required>
                <div class="text-red-500 error-msg-premium hidden">Must be higher than mid price</div>
            </td>
            <td class="text-center">
                <button type="button" class="text-red-500 hover:text-red-700 delete-new-row action-btn"><i class="fas fa-trash"></i></button>
            </td>
        `;
        rentacarTbody.appendChild(newRow);
        setupDeleteHandlers();
    });

    setupDeleteHandlers();
});
</script>
  
</body>
</html>