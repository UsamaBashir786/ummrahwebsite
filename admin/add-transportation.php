<?php
require_once '../config/db.php';
session_name('admin_session');
session_start();

if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
  header('Location: login.php');
  exit;
}

// Create transportation_settings table if it doesn't exist
$create_settings_table_sql = "
CREATE TABLE IF NOT EXISTS transportation_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_type VARCHAR(20) NOT NULL,
    service_title VARCHAR(255) NOT NULL,
    year INT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
$conn->query($create_settings_table_sql);

// Initialize variables
$success_message = '';
$error_message = '';

// Handle Taxi Routes Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_taxi_routes'])) {
  $service_title = $conn->real_escape_string($_POST['serviceTitle']);
  $year = (int)$_POST['year'];

  // Update or insert into transportation_settings
  $stmt = $conn->prepare("INSERT INTO transportation_settings (service_type, service_title, year) VALUES ('taxi', ?, ?) 
                            ON DUPLICATE KEY UPDATE service_title = ?, year = ?, updated_at = NOW()");
  $stmt->bind_param("sisi", $service_title, $year, $service_title, $year);
  if (!$stmt->execute()) {
    $error_message = "Error updating taxi settings: " . $conn->error;
  }
  $stmt->close();

  // Handle new routes
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
    } else {
      $success_message = "Taxi routes added successfully!";
    }
  }
}

// Handle Rent-a-Car Routes Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_rentacar_routes'])) {
  $service_title = $conn->real_escape_string($_POST['serviceTitle']);
  $year = (int)$_POST['year'];

  // Update or insert into transportation_settings
  $stmt = $conn->prepare("INSERT INTO transportation_settings (service_type, service_title, year) VALUES ('rentacar', ?, ?) 
                            ON DUPLICATE KEY UPDATE service_title = ?, year = ?, updated_at = NOW()");
  $stmt->bind_param("sisi", $service_title, $year, $service_title, $year);
  if (!$stmt->execute()) {
    $error_message = "Error updating rent-a-car settings: " . $conn->error;
  }
  $stmt->close();

  // Handle new routes
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
    } else {
      $success_message = "Rent-a-car routes added successfully!";
    }
  }
}

// Get default service info
$taxi_service_info = ['service_title' => 'Best Taxi Service for Umrah and Hajj', 'year' => 2024];
$rentacar_service_info = ['service_title' => 'Best Umrah and Hajj Rent A Car', 'year' => 2024];

$stmt = $conn->prepare("SELECT service_title, year FROM transportation_settings WHERE service_type = ? LIMIT 1");
$stmt->bind_param("s", $service_type);
$service_type = 'taxi';
$stmt->execute();
$result = $stmt->get_result();
if ($data = $result->fetch_assoc()) {
  $taxi_service_info = $data;
}
$service_type = 'rentacar';
$stmt->execute();
$result = $stmt->get_result();
if ($data = $result->fetch_assoc()) {
  $rentacar_service_info = $data;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Transportation | UmrahFlights</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="stylesheet" href="assets/css/index.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    .tab-buttons {
      display: flex;
      gap: 10px;
      margin-bottom: 20px;
    }

    .tab-btn {
      padding: 10px 20px;
      border-radius: 6px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      border: none;
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

    .price-input {
      width: 100%;
      padding: 0.375rem 0.75rem;
      border: 1px solid #d1d5db;
      border-radius: 0.375rem;
      font-size: 0.875rem;
    }

    .price-input:focus {
      outline: 2px solid #3b82f6;
      border-color: #3b82f6;
    }

    .rentacar-input:focus {
      outline: 2px solid #1d4ed8;
      border-color: #1d4ed8;
    }
  </style>
</head>

<body class="bg-gray-100 font-sans">
  <?php include 'includes/sidebar.php'; ?>
  <main class="ml-0 md:ml-64 p-6 min-h-screen" role="main" aria-label="Main content">
    <nav class="flex items-center justify-between bg-white shadow-md p-4 rounded-lg mb-6">
      <div class="flex items-center">
        <button id="sidebarToggle" class="md:hidden text-gray-600 hover:text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500" aria-label="Toggle sidebar">
          <i class="fas fa-bars text-xl"></i>
        </button>
        <h1 class="text-xl font-semibold text-gray-800 ml-4">Add Transportation</h1>
      </div>
      <div class="flex items-center space-x-4">
        <div class="relative">
          <button id="userDropdown" class="flex items-center text-gray-600 hover:text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500" aria-label="User menu" aria-expanded="false">
            <img src="../assets/img/admin.jpg" alt="Admin User" class="w-8 h-8 rounded-full mr-2">
            <span class="hidden md:inline text-gray-800">Admin User</span>
            <i class="fas fa-chevron-down ml-1"></i>
          </button>
          <div id="userDropdownMenu" class="hidden absolute right-0 mt-2 w-48 bg-white shadow-lg rounded-lg py-2 z-10">
            <a href="logout.php" class="block px-4 py-2 text-red-600 hover:bg-gray-100"><i class="fas fa-sign-out-alt mr-2"></i> Logout</a>
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

    <section class="bg-white p-6 rounded-lg shadow-md" aria-label="Transportation management">
      <div class="tab-buttons flex justify-center">
        <button class="tab-btn active" onclick="switchTab('taxi')">Taxi Routes</button>
        <button class="tab-btn" onclick="switchTab('rentacar')">Rent A Car Routes</button>
      </div>

      <!-- Taxi Routes Tab -->
      <div id="taxi-tab" class="tab-content active">
        <div class="mb-6">
          <h2 class="text-2xl font-bold">Add Taxi Routes</h2>
          <p class="text-gray-600 mt-2">Add new taxi service routes and prices</p>
        </div>
        <form action="" method="POST" id="taxi-routes-form">
          <input type="hidden" name="update_taxi_routes" value="1">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
              <label for="taxi-service-title" class="block text-sm font-medium text-gray-700">Service Title</label>
              <input type="text" id="taxi-service-title" name="serviceTitle" class="mt-1 block w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" value="<?php echo htmlspecialchars($taxi_service_info['service_title']); ?>" required>
            </div>
            <div>
              <label for="taxi-year" class="block text-sm font-medium text-gray-700">Year</label>
              <input type="number" id="taxi-year" name="year" min="2024" max="2030" class="mt-1 block w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" value="<?php echo $taxi_service_info['year']; ?>" required>
            </div>
          </div>
          <div class="mb-6 overflow-x-auto">
            <h3 class="font-semibold text-lg mb-3">New Routes</h3>
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="bg-blue-600 text-white">
                  <th class="p-3 w-16 text-center">#</th>
                  <th class="p-3 text-left">Route</th>
                  <th class="p-3 text-center">Camry / Sonata (PKR)</th>
                  <th class="p-3 text-center">Starex / Staria (PKR)</th>
                  <th class="p-3 text-center">Hiace (PKR)</th>
                  <th class="p-3 w-16 text-center">Action</th>
                </tr>
              </thead>
              <tbody id="new-taxi-routes-body">
                <tr class="price-validation-row">
                  <td class="p-3 text-center">
                    <input type="number" name="new_route_number[0]" value="1" class="price-input w-16 text-center" required>
                  </td>
                  <td class="p-3">
                    <input type="text" name="new_route_name[0]" placeholder="Enter route name" class="price-input w-full route-name" pattern="[A-Za-z\s]+" title="Only letters are allowed" maxlength="15" oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '')" required>
                    <div class="text-red-500 text-xs error-msg-name hidden">Only letters allowed (max 15 chars)</div>
                  </td>
                  <td class="p-3">
                    <input type="number" name="new_camry_price[0]" placeholder="Price" min="0" step="0.01" class="price-input w-full text-center base-price" oninput="validateNewCarPrices(this)" required>
                    <span class="text-xs text-gray-500">PKR</span>
                    <div class="text-red-500 text-xs error-msg-base hidden">Must be greater than 0</div>
                  </td>
                  <td class="p-3">
                    <input type="number" name="new_starex_price[0]" placeholder="Price" min="0" step="0.01" class="price-input w-full text-center mid-price" oninput="validateNewCarPrices(this)" disabled required>
                    <span class="text-xs text-gray-500">PKR</span>
                    <div class="text-red-500 text-xs error-msg-mid hidden">Must be higher than base price</div>
                  </td>
                  <td class="p-3">
                    <input type="number" name="new_hiace_price[0]" placeholder="Price" min="0" step="0.01" class="price-input w-full text-center premium-price" oninput="validateNewCarPrices(this)" disabled required>
                    <span class="text-xs text-gray-500">PKR</span>
                    <div class="text-red-500 text-xs error-msg-premium hidden">Must be higher than mid price</div>
                  </td>
                  <td class="p-3 text-center">
                    <button type="button" class="text-red-500 hover:text-red-700 delete-new-row" disabled><i class="fas fa-trash"></i></button>
                  </td>
                </tr>
              </tbody>
            </table>
            <button type="button" id="add-taxi-row" class="mt-4 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700"><i class="fas fa-plus-circle mr-2"></i>Add Another Route</button>
          </div>
          <div class="flex justify-end">
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700"><i class="fas fa-save mr-2"></i>Save Routes</button>
          </div>
        </form>
      </div>

      <!-- Rent A Car Routes Tab -->
      <div id="rentacar-tab" class="tab-content">
        <div class="mb-6">
          <h2 class="text-2xl font-bold">Add Rent A Car Routes</h2>
          <p class="text-gray-600 mt-2">Add new rent a car service routes and prices</p>
        </div>
        <form action="" method="POST" id="rentacar-routes-form">
          <input type="hidden" name="update_rentacar_routes" value="1">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
              <label for="rentacar-service-title" class="block text-sm font-medium text-gray-700">Service Title</label>
              <input type="text" id="rentacar-service-title" name="serviceTitle" class="mt-1 block w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" value="<?php echo htmlspecialchars($rentacar_service_info['service_title']); ?>" required>
            </div>
            <div>
              <label for="rentacar-year" class="block text-sm font-medium text-gray-700">Year</label>
              <input type="number" id="rentacar-year" name="year" min="2024" max="2030" class="mt-1 block w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" value="<?php echo $rentacar_service_info['year']; ?>" required>
            </div>
          </div>
          <div class="mb-6 overflow-x-auto">
            <h3 class="font-semibold text-lg mb-3">New Routes</h3>
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="bg-blue-600 text-white">
                  <th class="p-3 w-16 text-center">#</th>
                  <th class="p-3 text-left">Route</th>
                  <th class="p-3 text-center">GMC 16-19 (PKR)</th>
                  <th class="p-3 text-center">GMC 22-23 (PKR)</th>
                  <th class="p-3 text-center">COASTER (PKR)</th>
                  <th class="p-3 w-16 text-center">Action</th>
                </tr>
              </thead>
              <tbody id="new-rentacar-routes-body">
                <tr class="price-validation-row">
                  <td class="p-3 text-center">
                    <input type="number" name="new_route_number[0]" value="1" class="price-input rentacar-input w-16 text-center" required>
                  </td>
                  <td class="p-3">
                    <input type="text" name="new_route_name[0]" placeholder="Enter route name" class="price-input rentacar-input w-full route-name" pattern="[A-Za-z\s]+" title="Only letters are allowed" maxlength="15" oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '')" required>
                    <div class="text-red-500 text-xs error-msg-name hidden">Only letters allowed (max 15 chars)</div>
                  </td>
                  <td class="p-3">
                    <input type="number" name="new_gmc_16_19_price[0]" placeholder="Price" min="0" step="0.01" class="price-input rentacar-input w-full text-center base-price" oninput="validateNewCarPrices(this)" required>
                    <span class="text-xs text-gray-500">PKR</span>
                    <div class="text-red-500 text-xs error-msg-base hidden">Must be greater than 0</div>
                  </td>
                  <td class="p-3">
                    <input type="number" name="new_gmc_22_23_price[0]" placeholder="Price" min="0" step="0.01" class="price-input rentacar-input w-full text-center mid-price" oninput="validateNewCarPrices(this)" disabled required>
                    <span class="text-xs text-gray-500">PKR</span>
                    <div class="text-red-500 text-xs error-msg-mid hidden">Must be higher than base price</div>
                  </td>
                  <td class="p-3">
                    <input type="number" name="new_coaster_price[0]" placeholder="Price" min="0" step="0.01" class="price-input rentacar-input w-full text-center premium-price" oninput="validateNewCarPrices(this)" disabled required>
                    <span class="text-xs text-gray-500">PKR</span>
                    <div class="text-red-500 text-xs error-msg-premium hidden">Must be higher than mid price</div>
                  </td>
                  <td class="p-3 text-center">
                    <button type="button" class="text-red-500 hover:text-red-700 delete-new-row" disabled><i class="fas fa-trash"></i></button>
                  </td>
                </tr>
              </tbody>
            </table>
            <button type="button" id="add-rentacar-row" class="mt-4 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700"><i class="fas fa-plus-circle mr-2"></i>Add Another Route</button>
          </div>
          <div class="flex justify-end">
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700"><i class="fas fa-save mr-2"></i>Save Routes</button>
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

    document.addEventListener('DOMContentLoaded', function() {
      const storedTab = localStorage.getItem('activeTransportTab');
      if (storedTab) {
        switchTab(storedTab);
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

      document.getElementById('add-taxi-row').addEventListener('click', function() {
        const tbody = document.getElementById('new-taxi-routes-body');
        const rowCount = tbody.rows.length;
        const newIndex = rowCount;
        const newRowNumber = rowCount + 1;

        const newRow = document.createElement('tr');
        newRow.classList.add('price-validation-row');
        newRow.innerHTML = `
                    <td class="p-3 text-center">
                        <input type="number" name="new_route_number[${newIndex}]" value="${newRowNumber}" class="price-input w-16 text-center" required>
                    </td>
                    <td class="p-3">
                        <input type="text" name="new_route_name[${newIndex}]" placeholder="Enter route name" class="price-input w-full route-name" pattern="[A-Za-z\s]+" title="Only letters are allowed" maxlength="15" oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '')" required>
                        <div class="text-red-500 text-xs error-msg-name hidden">Only letters allowed (max 15 chars)</div>
                    </td>
                    <td class="p-3">
                        <input type="number" name="new_camry_price[${newIndex}]" placeholder="Price" min="0" step="0.01" class="price-input w-full text-center base-price" oninput="validateNewCarPrices(this)" required>
                        <span class="text-xs text-gray-500">PKR</span>
                        <div class="text-red-500 text-xs error-msg-base hidden">Must be greater than 0</div>
                    </td>
                    <td class="p-3">
                        <input type="number" name="new_starex_price[${newIndex}]" placeholder="Price" min="0" step="0.01" class="price-input w-full text-center mid-price" oninput="validateNewCarPrices(this)" disabled required>
                        <span class="text-xs text-gray-500">PKR</span>
                        <div class="text-red-500 text-xs error-msg-mid hidden">Must be higher than base price</div>
                    </td>
                    <td class="p-3">
                        <input type="number" name="new_hiace_price[${newIndex}]" placeholder="Price" min="0" step="0.01" class="price-input w-full text-center premium-price" oninput="validateNewCarPrices(this)" disabled required>
                        <span class="text-xs text-gray-500">PKR</span>
                        <div class="text-red-500 text-xs error-msg-premium hidden">Must be higher than mid price</div>
                    </td>
                    <td class="p-3 text-center">
                        <button type="button" class="text-red-500 hover:text-red-700 delete-new-row"><i class="fas fa-trash"></i></button>
                    </td>
                `;
        tbody.appendChild(newRow);
        setupDeleteHandlers();
      });

      document.getElementById('add-rentacar-row').addEventListener('click', function() {
        const tbody = document.getElementById('new-rentacar-routes-body');
        const rowCount = tbody.rows.length;
        const newIndex = rowCount;
        const newRowNumber = rowCount + 1;

        const newRow = document.createElement('tr');
        newRow.classList.add('price-validation-row');
        newRow.innerHTML = `
                    <td class="p-3 text-center">
                        <input type="number" name="new_route_number[${newIndex}]" value="${newRowNumber}" class="price-input rentacar-input w-16 text-center" required>
                    </td>
                    <td class="p-3">
                        <input type="text" name="new_route_name[${newIndex}]" placeholder="Enter route name" class="price-input rentacar-input w-full route-name" pattern="[A-Za-z\s]+" title="Only letters are allowed" maxlength="15" oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '')" required>
                        <div class="text-red-500 text-xs error-msg-name hidden">Only letters allowed (max 15 chars)</div>
                    </td>
                    <td class="p-3">
                        <input type="number" name="new_gmc_16_19_price[${newIndex}]" placeholder="Price" min="0" step="0.01" class="price-input rentacar-input w-full text-center base-price" oninput="validateNewCarPrices(this)" required>
                        <span class="text-xs text-gray-500">PKR</span>
                        <div class="text-red-500 text-xs error-msg-base hidden">Must be greater than 0</div>
                    </td>
                    <td class="p-3">
                        <input type="number" name="new_gmc_22_23_price[${newIndex}]" placeholder="Price" min="0" step="0.01" class="price-input rentacar-input w-full text-center mid-price" oninput="validateNewCarPrices(this)" disabled required>
                        <span class="text-xs text-gray-500">PKR</span>
                        <div class="text-red-500 text-xs error-msg-mid hidden">Must be higher than base price</div>
                    </td>
                    <td class="p-3">
                        <input type="number" name="new_coaster_price[${newIndex}]" placeholder="Price" min="0" step="0.01" class="price-input rentacar-input w-full text-center premium-price" oninput="validateNewCarPrices(this)" disabled required>
                        <span class="text-xs text-gray-500">PKR</span>
                        <div class="text-red-500 text-xs error-msg-premium hidden">Must be higher than mid price</div>
                    </td>
                    <td class="p-3 text-center">
                        <button type="button" class="text-red-500 hover:text-red-700 delete-new-row"><i class="fas fa-trash"></i></button>
                    </td>
                `;
        tbody.appendChild(newRow);
        setupDeleteHandlers();
      });

      document.getElementById('sidebarToggle').addEventListener('click', function() {
        document.querySelector('aside').classList.toggle('hidden');
      });

      document.getElementById('userDropdown').addEventListener('click', function() {
        document.getElementById('userDropdownMenu').classList.toggle('hidden');
      });

      document.addEventListener('click', function(e) {
        const dropdown = document.getElementById('userDropdownMenu');
        const button = document.getElementById('userDropdown');
        if (!dropdown.contains(e.target) && !button.contains(e.target)) {
          dropdown.classList.add('hidden');
        }
      });

      function setupDeleteHandlers() {
        document.querySelectorAll('.delete-new-row').forEach(button => {
          if (!button.hasAttribute('disabled')) {
            button.addEventListener('click', function() {
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

      setupDeleteHandlers();
    });
  </script>
</body>

</html>