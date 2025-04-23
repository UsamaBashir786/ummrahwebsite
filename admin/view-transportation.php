<?php
require_once '../config/db.php';
session_name('admin_session');
session_start();

if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
  header('Location: login.php');
  exit;
}

// Initialize variables
$success_message = '';
$error_message = '';

// Handle Delete Requests
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_route'])) {
  $route_id = (int)$_POST['route_id'];
  $service_type = $conn->real_escape_string($_POST['service_type']);

  if ($service_type == 'taxi') {
    $stmt = $conn->prepare("DELETE FROM taxi_routes WHERE id = ?");
  } else {
    $stmt = $conn->prepare("DELETE FROM rentacar_routes WHERE id = ?");
  }
  $stmt->bind_param("i", $route_id);

  if ($stmt->execute()) {
    $success_message = ucfirst($service_type) . " route deleted successfully!";
  } else {
    $error_message = "Error deleting route: " . $conn->error;
  }
  $stmt->close();
}

// Handle Edit Requests
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_route'])) {
  $route_id = (int)$_POST['route_id'];
  $service_type = $conn->real_escape_string($_POST['service_type']);
  $route_name = $conn->real_escape_string($_POST['route_name']);
  $route_number = (int)$_POST['route_number'];

  if ($service_type == 'taxi') {
    $camry_price = (float)$_POST['camry_price'];
    $starex_price = (float)$_POST['starex_price'];
    $hiace_price = (float)$_POST['hiace_price'];
    $stmt = $conn->prepare("UPDATE taxi_routes SET route_number = ?, route_name = ?, camry_sonata_price = ?, starex_staria_price = ?, hiace_price = ? WHERE id = ?");
    $stmt->bind_param("isdddi", $route_number, $route_name, $camry_price, $starex_price, $hiace_price, $route_id);
  } else {
    $gmc_16_19_price = (float)$_POST['gmc_16_19_price'];
    $gmc_22_23_price = (float)$_POST['gmc_22_23_price'];
    $coaster_price = (float)$_POST['coaster_price'];
    $stmt = $conn->prepare("UPDATE rentacar_routes SET route_number = ?, route_name = ?, gmc_16_19_price = ?, gmc_22_23_price = ?, coaster_price = ? WHERE id = ?");
    $stmt->bind_param("isdddi", $route_number, $route_name, $gmc_16_19_price, $gmc_22_23_price, $coaster_price, $route_id);
  }

  if ($stmt->execute()) {
    $success_message = ucfirst($service_type) . " route updated successfully!";
  } else {
    $error_message = "Error updating route: " . $conn->error;
  }
  $stmt->close();
}

// Fetch Taxi Routes
$taxi_routes = [];
$taxi_service_info = ['service_title' => 'Best Taxi Service for Umrah and Hajj', 'year' => 2024];
$stmt = $conn->prepare("SELECT service_title, year FROM transportation_settings WHERE service_type = 'taxi' LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();
if ($data = $result->fetch_assoc()) {
  $taxi_service_info = $data;
}
$stmt->close();

$stmt = $conn->prepare("SELECT id, route_number, route_name, camry_sonata_price, starex_staria_price, hiace_price FROM taxi_routes WHERE service_title = ? AND year = ?");
$stmt->bind_param("si", $taxi_service_info['service_title'], $taxi_service_info['year']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $taxi_routes[] = $row;
}
$stmt->close();

// Fetch Rent-a-Car Routes
$rentacar_routes = [];
$rentacar_service_info = ['service_title' => 'Best Umrah and Hajj Rent A Car', 'year' => 2024];
$stmt = $conn->prepare("SELECT service_title, year FROM transportation_settings WHERE service_type = 'rentacar' LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();
if ($data = $result->fetch_assoc()) {
  $rentacar_service_info = $data;
}
$stmt->close();

$stmt = $conn->prepare("SELECT id, route_number, route_name, gmc_16_19_price, gmc_22_23_price, coaster_price FROM rentacar_routes WHERE service_title = ? AND year = ?");
$stmt->bind_param("si", $rentacar_service_info['service_title'], $rentacar_service_info['year']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $rentacar_routes[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>View Transportation | UmrahFlights</title>
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

    .edit-form {
      display: none;
    }

    .edit-form.active {
      display: table-row;
    }

    .view-row {
      display: table-row;
    }

    .view-row.hidden {
      display: none;
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
        <h1 class="text-xl font-semibold text-gray-800 ml-4">View Transportation</h1>
      </div>
      <div class="flex items-center space-x-4">
        <div class="relative">
          <button id="userDropdown" class="flex items-center text-gray-600 hover:text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500" aria-label="User menu" aria-expanded="false">
            <!-- <img src="../assets/img/admin.jpg" alt="Admin User" class="w-8 h-8 rounded-full mr-2"> -->
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
          <h2 class="text-2xl font-bold">Taxi Routes</h2>
          <p class="text-gray-600 mt-2">View and manage taxi service routes and prices</p>
        </div>
        <div class="mb-6 overflow-x-auto">
          <h3 class="font-semibold text-lg mb-3">Existing Routes</h3>
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-blue-600 text-white">
                <th class="p-3 w-16 text-center">#</th>
                <th class="p-3 text-left">Route</th>
                <th class="p-3 text-center">Camry / Sonata (PKR)</th>
                <th class="p-3 text-center">Starex / Staria (PKR)</th>
                <th class="p-3 text-center">Hiace (PKR)</th>
                <th class="p-3 w-24 text-center">Actions</th>
              </tr>
            </thead>
            <tbody id="taxi-routes-body">
              <?php if (empty($taxi_routes)): ?>
                <tr>
                  <td colspan="6" class="p-3 text-center text-gray-500">No taxi routes found.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($taxi_routes as $route): ?>
                  <!-- View Row -->
                  <tr class="view-row" data-route-id="<?php echo $route['id']; ?>">
                    <td class="p-3 text-center"><?php echo htmlspecialchars($route['route_number']); ?></td>
                    <td class="p-3"><?php echo htmlspecialchars($route['route_name']); ?></td>
                    <td class="p-3 text-center"><?php echo number_format($route['camry_sonata_price'], 2); ?></td>
                    <td class="p-3 text-center"><?php echo number_format($route['starex_staria_price'], 2); ?></td>
                    <td class="p-3 text-center"><?php echo number_format($route['hiace_price'], 2); ?></td>
                    <td class="p-3 text-center">
                      <button type="button" class="text-blue-500 hover:text-blue-700 edit-btn" title="Edit"><i class="fas fa-edit"></i></button>
                      <button type="button" class="text-red-500 hover:text-red-700 delete-btn" title="Delete"><i class="fas fa-trash"></i></button>
                    </td>
                  </tr>
                  <!-- Edit Form Row -->
                  <tr class="edit-form price-validation-row" data-route-id="<?php echo $route['id']; ?>">
                    <td class="p-3 text-center">
                      <input type="number" name="route_number" value="<?php echo htmlspecialchars($route['route_number']); ?>" class="price-input w-16 text-center" required>
                    </td>
                    <td class="p-3">
                      <input type="text" name="route_name" value="<?php echo htmlspecialchars($route['route_name']); ?>" class="price-input w-full route-name" pattern="[A-Za-z\s]+" title="Only letters are allowed" maxlength="15" oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '')" required>
                      <div class="text-red-500 text-xs error-msg-name hidden">Only letters allowed (max 15 chars)</div>
                    </td>
                    <td class="p-3">
                      <input type="number" name="camry_price" value="<?php echo htmlspecialchars($route['camry_sonata_price']); ?>" min="0" step="0.01" class="price-input w-full text-center base-price" oninput="validateEditCarPrices(this)" required>
                      <div class="text-red-500 text-xs error-msg-base hidden">Must be greater than 0</div>
                    </td>
                    <td class="p-3">
                      <input type="number" name="starex_price" value="<?php echo htmlspecialchars($route['starex_staria_price']); ?>" min="0" step="0.01" class="price-input w-full text-center mid-price" oninput="validateEditCarPrices(this)" required>
                      <div class="text-red-500 text-xs error-msg-mid hidden">Must be higher than base price</div>
                    </td>
                    <td class="p-3">
                      <input type="number" name="hiace_price" value="<?php echo htmlspecialchars($route['hiace_price']); ?>" min="0" step="0.01" class="price-input w-full text-center premium-price" oninput="validateEditCarPrices(this)" required>
                      <div class="text-red-500 text-xs error-msg-premium hidden">Must be higher than mid price</div>
                    </td>
                    <td class="p-3 text-center">
                      <button type="button" class="text-green-500 hover:text-green-700 save-btn" title="Save"><i class="fas fa-save"></i></button>
                      <button type="button" class="text-gray-500 hover:text-gray-700 cancel-btn" title="Cancel"><i class="fas fa-times"></i></button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Rent A Car Routes Tab -->
      <div id="rentacar-tab" class="tab-content">
        <div class="mb-6">
          <h2 class="text-2xl font-bold">Rent A Car Routes</h2>
          <p class="text-gray-600 mt-2">View and manage rent a car service routes and prices</p>
        </div>
        <div class="mb-6 overflow-x-auto">
          <h3 class="font-semibold text-lg mb-3">Existing Routes</h3>
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-blue-600 text-white">
                <th class="p-3 w-16 text-center">#</th>
                <th class="p-3 text-left">Route</th>
                <th class="p-3 text-center">GMC 16-19 (PKR)</th>
                <th class="p-3 text-center">GMC 22-23 (PKR)</th>
                <th class="p-3 text-center">COASTER (PKR)</th>
                <th class="p-3 w-24 text-center">Actions</th>
              </tr>
            </thead>
            <tbody id="rentacar-routes-body">
              <?php if (empty($rentacar_routes)): ?>
                <tr>
                  <td colspan="6" class="p-3 text-center text-gray-500">No rent-a-car routes found.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($rentacar_routes as $route): ?>
                  <!-- View Row -->
                  <tr class="view-row" data-route-id="<?php echo $route['id']; ?>">
                    <td class="p-3 text-center"><?php echo htmlspecialchars($route['route_number']); ?></td>
                    <td class="p-3"><?php echo htmlspecialchars($route['route_name']); ?></td>
                    <td class="p-3 text-center"><?php echo number_format($route['gmc_16_19_price'], 2); ?></td>
                    <td class="p-3 text-center"><?php echo number_format($route['gmc_22_23_price'], 2); ?></td>
                    <td class="p-3 text-center"><?php echo number_format($route['coaster_price'], 2); ?></td>
                    <td class="p-3 text-center">
                      <button type="button" class="text-blue-500 hover:text-blue-700 edit-btn" title="Edit"><i class="fas fa-edit"></i></button>
                      <button type="button" class="text-red-500 hover:text-red-700 delete-btn" title="Delete"><i class="fas fa-trash"></i></button>
                    </td>
                  </tr>
                  <!-- Edit Form Row -->
                  <tr class="edit-form price-validation-row" data-route-id="<?php echo $route['id']; ?>">
                    <td class="p-3 text-center">
                      <input type="number" name="route_number" value="<?php echo htmlspecialchars($route['route_number']); ?>" class="price-input rentacar-input w-16 text-center" required>
                    </td>
                    <td class="p-3">
                      <input type="text" name="route_name" value="<?php echo htmlspecialchars($route['route_name']); ?>" class="price-input rentacar-input w-full route-name" pattern="[A-Za-z\s]+" title="Only letters are allowed" maxlength="15" oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '')" required>
                      <div class="text-red-500 text-xs error-msg-name hidden">Only letters allowed (max 15 chars)</div>
                    </td>
                    <td class="p-3">
                      <input type="number" name="gmc_16_19_price" value="<?php echo htmlspecialchars($route['gmc_16_19_price']); ?>" min="0" step="0.01" class="price-input rentacar-input w-full text-center base-price" oninput="validateEditCarPrices(this)" required>
                      <div class="text-red-500 text-xs error-msg-base hidden">Must be greater than 0</div>
                    </td>
                    <td class="p-3">
                      <input type="number" name="gmc_22_23_price" value="<?php echo htmlspecialchars($route['gmc_22_23_price']); ?>" min="0" step="0.01" class="price-input rentacar-input w-full text-center mid-price" oninput="validateEditCarPrices(this)" required>
                      <div class="text-red-500 text-xs error-msg-mid hidden">Must be higher than base price</div>
                    </td>
                    <td class="p-3">
                      <input type="number" name="coaster_price" value="<?php echo htmlspecialchars($route['coaster_price']); ?>" min="0" step="0.01" class="price-input rentacar-input w-full text-center premium-price" oninput="validateEditCarPrices(this)" required>
                      <div class="text-red-500 text-xs error-msg-premium hidden">Must be higher than mid price</div>
                    </td>
                    <td class="p-3 text-center">
                      <button type="button" class="text-green-500 hover:text-green-700 save-btn" title="Save"><i class="fas fa-save"></i></button>
                      <button type="button" class="text-gray-500 hover:text-gray-700 cancel-btn" title="Cancel"><i class="fas fa-times"></i></button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
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

    function validateEditCarPrices(input) {
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
        }
      }

      if (input.classList.contains('mid-price')) {
        if (midPrice <= basePrice) {
          row.querySelector('.error-msg-mid').classList.remove('hidden');
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

      // Route Name Validation
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

      // Edit Button Handler
      document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', function() {
          const row = this.closest('.view-row');
          const routeId = row.dataset.routeId;
          row.classList.add('hidden');
          document.querySelector(`.edit-form[data-route-id="${routeId}"]`).classList.add('active');
        });
      });

      // Cancel Button Handler
      document.querySelectorAll('.cancel-btn').forEach(button => {
        button.addEventListener('click', function() {
          const row = this.closest('.edit-form');
          const routeId = row.dataset.routeId;
          row.classList.remove('active');
          document.querySelector(`.view-row[data-route-id="${routeId}"]`).classList.remove('hidden');
        });
      });

      // Save Button Handler
      document.querySelectorAll('.save-btn').forEach(button => {
        button.addEventListener('click', function() {
          const row = this.closest('.edit-form');
          const routeId = row.dataset.routeId;
          const routeNumber = row.querySelector('input[name="route_number"]').value;
          const routeName = row.querySelector('input[name="route_name"]').value;
          const serviceType = row.closest('.tab-content').id === 'taxi-tab' ? 'taxi' : 'rentacar';
          let isValid = true;

          // Validate inputs
          if (!/^[A-Za-z\s]{1,15}$/.test(routeName)) {
            row.querySelector('.error-msg-name').classList.remove('hidden');
            isValid = false;
          }

          let formData;
          if (serviceType === 'taxi') {
            const camryPrice = parseFloat(row.querySelector('input[name="camry_price"]').value) || 0;
            const starexPrice = parseFloat(row.querySelector('input[name="starex_price"]').value) || 0;
            const hiacePrice = parseFloat(row.querySelector('input[name="hiace_price"]').value) || 0;

            if (camryPrice <= 0) {
              row.querySelector('.error-msg-base').classList.remove('hidden');
              isValid = false;
            }
            if (starexPrice <= camryPrice) {
              row.querySelector('.error-msg-mid').classList.remove('hidden');
              isValid = false;
            }
            if (hiacePrice <= starexPrice) {
              row.querySelector('.error-msg-premium').classList.remove('hidden');
              isValid = false;
            }

            formData = new FormData();
            formData.append('edit_route', '1');
            formData.append('route_id', routeId);
            formData.append('service_type', serviceType);
            formData.append('route_number', routeNumber);
            formData.append('route_name', routeName);
            formData.append('camry_price', camryPrice);
            formData.append('starex_price', starexPrice);
            formData.append('hiace_price', hiacePrice);
          } else {
            const gmc1619Price = parseFloat(row.querySelector('input[name="gmc_16_19_price"]').value) || 0;
            const gmc2223Price = parseFloat(row.querySelector('input[name="gmc_22_23_price"]').value) || 0;
            const coasterPrice = parseFloat(row.querySelector('input[name="coaster_price"]').value) || 0;

            if (gmc1619Price <= 0) {
              row.querySelector('.error-msg-base').classList.remove('hidden');
              isValid = false;
            }
            if (gmc2223Price <= gmc1619Price) {
              row.querySelector('.error-msg-mid').classList.remove('hidden');
              isValid = false;
            }
            if (coasterPrice <= gmc2223Price) {
              row.querySelector('.error-msg-premium').classList.remove('hidden');
              isValid = false;
            }

            formData = new FormData();
            formData.append('edit_route', '1');
            formData.append('route_id', routeId);
            formData.append('service_type', serviceType);
            formData.append('route_number', routeNumber);
            formData.append('route_name', routeName);
            formData.append('gmc_16_19_price', gmc1619Price);
            formData.append('gmc_22_23_price', gmc2223Price);
            formData.append('coaster_price', coasterPrice);
          }

          if (!isValid) {
            Swal.fire({
              icon: 'error',
              title: 'Validation Error',
              text: 'Please fix all validation errors before saving.'
            });
            return;
          }

          // Submit form via AJAX
          fetch('', {
              method: 'POST',
              body: formData
            }).then(response => response.text())
            .then(() => {
              Swal.fire({
                icon: 'success',
                title: 'Success',
                text: `${serviceType.charAt(0).toUpperCase() + serviceType.slice(1)} route updated successfully!`,
                showConfirmButton: false,
                timer: 1500
              }).then(() => {
                location.reload();
              });
            }).catch(error => {
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'An error occurred while saving the route.'
              });
            });
        });
      });

      // Delete Button Handler
      document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', function() {
          const row = this.closest('.view-row');
          const routeId = row.dataset.routeId;
          const serviceType = row.closest('.tab-content').id === 'taxi-tab' ? 'taxi' : 'rentacar';

          Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
          }).then((result) => {
            if (result.isConfirmed) {
              const formData = new FormData();
              formData.append('delete_route', '1');
              formData.append('route_id', routeId);
              formData.append('service_type', serviceType);

              fetch('', {
                  method: 'POST',
                  body: formData
                }).then(response => response.text())
                .then(() => {
                  Swal.fire({
                    icon: 'success',
                    title: 'Deleted!',
                    text: `${serviceType.charAt(0).toUpperCase() + serviceType.slice(1)} route has been deleted.`,
                    showConfirmButton: false,
                    timer: 1500
                  }).then(() => {
                    location.reload();
                  });
                }).catch(error => {
                  Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while deleting the route.'
                  });
                });
            }
          });
        });
      });

      // Sidebar and Dropdown Handlers
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
    });
  </script>
</body>

</html>