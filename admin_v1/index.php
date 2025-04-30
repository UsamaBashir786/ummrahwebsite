<?php
require_once '../config/db.php'; // Include db.php with $conn
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

// Function to get database connection if not already defined
if (!function_exists('getConnection')) {
  function getConnection()
  {
    $host = "localhost";
    $username = "root";
    $password = "";
    $database = "latest_ummrah";

    $conn = new mysqli($host, $username, $password, $database);

    if ($conn->connect_error) {
      die("Connection failed: " . $conn->connect_error);
    }

    return $conn;
  }
}

// Function to get dropdown options
function getDropdownOptions()
{
  $conn = getConnection();
  $dropdowns = [];

  // Fetch makes
  $makesSql = "SELECT id, name FROM makes ORDER BY display_order, name";
  $makesResult = $conn->query($makesSql);
  $dropdowns['makes'] = [];
  if ($makesResult && $makesResult->num_rows > 0) {
    while ($row = $makesResult->fetch_assoc()) {
      $dropdowns['makes'][] = $row;
    }
  }

  // Fetch body types
  $bodyTypesSql = "SELECT id, name FROM body_types ORDER BY display_order, name";
  $bodyTypesResult = $conn->query($bodyTypesSql);
  $dropdowns['bodyTypes'] = [];
  if ($bodyTypesResult && $bodyTypesResult->num_rows > 0) {
    while ($row = $bodyTypesResult->fetch_assoc()) {
      $dropdowns['bodyTypes'][] = $row;
    }
  }

  // Fetch fuel types
  $fuelTypesSql = "SELECT id, name FROM fuel_types ORDER BY display_order, name";
  $fuelTypesResult = $conn->query($fuelTypesSql);
  $dropdowns['fuelTypes'] = [];
  if ($fuelTypesResult && $fuelTypesResult->num_rows > 0) {
    while ($row = $fuelTypesResult->fetch_assoc()) {
      $dropdowns['fuelTypes'][] = $row;
    }
  }

  // Fetch transmission types
  $transmissionsSql = "SELECT id, name FROM transmission_types ORDER BY display_order, name";
  $transmissionsResult = $conn->query($transmissionsSql);
  $dropdowns['transmissions'] = [];
  if ($transmissionsResult && $transmissionsResult->num_rows > 0) {
    while ($row = $transmissionsResult->fetch_assoc()) {
      $dropdowns['transmissions'][] = $row;
    }
  }

  // Fetch drive types
  $driveTypesSql = "SELECT id, name FROM drive_types ORDER BY display_order, name";
  $driveTypesResult = $conn->query($driveTypesSql);
  $dropdowns['driveTypes'] = [];
  if ($driveTypesResult && $driveTypesResult->num_rows > 0) {
    while ($row = $driveTypesResult->fetch_assoc()) {
      $dropdowns['driveTypes'][] = $row;
    }
  }

  // Fetch colors
  $colorsSql = "SELECT id, name, hex_code FROM colors ORDER BY display_order, name";
  $colorsResult = $conn->query($colorsSql);
  $dropdowns['colors'] = [];
  if ($colorsResult && $colorsResult->num_rows > 0) {
    while ($row = $colorsResult->fetch_assoc()) {
      $dropdowns['colors'][] = $row;
    }
  }

  // Fetch vehicle statuses
  $statusSql = "SELECT id, name, css_class FROM vehicle_status ORDER BY display_order, name";
  $statusResult = $conn->query($statusSql);
  $dropdowns['statuses'] = [];
  if ($statusResult && $statusResult->num_rows > 0) {
    while ($row = $statusResult->fetch_assoc()) {
      $dropdowns['statuses'][] = $row;
    }
  }

  $conn->close();
  return $dropdowns;
}

// Function to get models by make ID (for AJAX)
function getModelsByMakeId($makeId)
{
  $conn = getConnection();
  $models = [];

  $stmt = $conn->prepare("SELECT id, name FROM models WHERE make_id = ? ORDER BY display_order, name");
  $stmt->bind_param("i", $makeId);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      $models[] = $row;
    }
  }

  $stmt->close();
  $conn->close();
  return $models;
}

// Get all dropdown options
$dropdowns = getDropdownOptions();

// Load all models data for client-side filtering
$allModels = [];
foreach ($dropdowns['makes'] as $make) {
  $makeId = (string)$make['id']; // Convert ID to string for JavaScript
  $allModels[$makeId] = getModelsByMakeId($make['id']);
}
// Fixed function to match your actual database schema
function getRecentInventory($limit = 5)
{
  $conn = getConnection();

  // Direct query based on your actual database structure
  $query = "SELECT 
      id, 
      CONCAT(make, ' ', model) AS vehicle_name,
      year, 
      mileage,
      fuel_type,
      status,
      DATE_FORMAT(created_at, '%Y-%m-%d') AS date_added
  FROM 
      vehicles
  ORDER BY 
      created_at DESC
  LIMIT ?";

  $stmt = $conn->prepare($query);
  $stmt->bind_param("i", $limit);
  $stmt->execute();
  $result = $stmt->get_result();

  $vehicles = [];
  if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      // Map status to CSS class
      $statusClass = 'bg-gray-100 text-gray-800';
      switch (strtolower($row['status'])) {
        case 'available':
          $statusClass = 'bg-green-100 text-green-800';
          break;
        case 'sold':
          $statusClass = 'bg-purple-100 text-purple-800';
          break;
        case 'pending':
          $statusClass = 'bg-amber-100 text-amber-800';
          break;
        case 'reserved':
          $statusClass = 'bg-blue-100 text-blue-800';
          break;
      }

      // Add CSS class to the vehicle data
      $row['css_class'] = $statusClass;

      // Add to vehicles array
      $vehicles[] = $row;
    }
  }

  $stmt->close();
  return $vehicles;
}
// Function to get total vehicle count - add this to your index.php file
function getTotalVehicleCount()
{
  $conn = getConnection();

  $query = "SELECT COUNT(*) as total FROM vehicles";
  $result = $conn->query($query);

  $total = 0;
  if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $total = $row['total'];
  }

  return $total;
}

// Process model deletion if form submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_model'])) {
  $modelId = (int)$_POST['model_id'];

  // Start the deletion process
  $conn = getConnection();
  $success = false;
  $errorMessage = "";

  // Begin transaction
  $conn->begin_transaction();

  try {
    // First, check if there are any vehicles using this model
    $checkVehiclesQuery = "SELECT COUNT(*) as vehicle_count FROM vehicles WHERE model = (SELECT name FROM models WHERE id = ?)";
    $stmt = $conn->prepare($checkVehiclesQuery);
    $stmt->bind_param("i", $modelId);
    $stmt->execute();
    $result = $stmt->get_result();
    $vehicleCount = $result->fetch_assoc()['vehicle_count'];
    $stmt->close();

    if ($vehicleCount > 0) {
      // Can't delete a model that's being used by vehicles
      $errorMessage = "Cannot delete this model because it is being used by $vehicleCount vehicle(s). Please reassign or delete these vehicles first.";
      throw new Exception($errorMessage);
    }

    // Get the model details for logging before deletion
    $modelQuery = "SELECT m.name as model_name, m.make_id, mk.name as make_name 
                  FROM models m
                  JOIN makes mk ON m.make_id = mk.id
                  WHERE m.id = ?";
    $stmt = $conn->prepare($modelQuery);
    $stmt->bind_param("i", $modelId);
    $stmt->execute();
    $modelResult = $stmt->get_result();
    $modelDetails = $modelResult->fetch_assoc();
    $stmt->close();

    if (!$modelDetails) {
      $errorMessage = "Model not found with ID: $modelId";
      throw new Exception($errorMessage);
    }

    // Delete the model
    $deleteModelQuery = "DELETE FROM models WHERE id = ?";
    $stmt = $conn->prepare($deleteModelQuery);
    $stmt->bind_param("i", $modelId);
    $result = $stmt->execute();

    if ($result && $stmt->affected_rows > 0) {
      $success = true;
      $_SESSION['success'] = "Model '" . $modelDetails['model_name'] . "' for " . $modelDetails['make_name'] . " deleted successfully.";
    } else {
      $errorMessage = "Failed to delete model. Error: " . $conn->error;
      throw new Exception($errorMessage);
    }

    $stmt->close();
    $conn->commit();
  } catch (Exception $e) {
    // If there was an exception, roll back the transaction
    $conn->rollback();
    $_SESSION['error'] = $e->getMessage();
  }

  $conn->close();
  header("Location: models.php");
  exit;
}
// Step 1: Query all features from the database (add this to the top of your file where you get other dropdown data)
function getAllFeatures()
{
  $conn = getConnection();
  $features = [];

  $sql = "SELECT id, name, category FROM features ORDER BY category, name";
  $result = $conn->query($sql);

  if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      $features[] = $row;
    }
  }

  $conn->close();
  return $features;
}

// Step 2: Add this to your existing code where you get dropdown options
$features = getAllFeatures();

// Step 3: Organize features by category
$featuresByCategory = [];
foreach ($features as $feature) {
  if (!isset($featuresByCategory[$feature['category']])) {
    $featuresByCategory[$feature['category']] = [];
  }
  $featuresByCategory[$feature['category']][] = $feature;
}
// Fetch all models for display
$conn = getConnection();
$models = [];

$query = "SELECT m.id, m.name, m.display_order, mk.name as make_name, mk.id as make_id
          FROM models m
          JOIN makes mk ON m.make_id = mk.id
          ORDER BY mk.name, m.display_order, m.name";

$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $models[] = $row;
  }
}
$conn->close();

// Get recent inventory for display
$recentVehicles = getRecentInventory(5);
$totalVehicles = getTotalVehicleCount();
?>
<!doctype html>
<html>

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="../src/output.css" rel="stylesheet">
  <title>CentralAutogy - Car Inventory Management</title>
  <link rel="stylesheet" href="assets/css/index.css">
</head>

<body class="bg-gray-50">
  <?php include 'includes/header.php'; ?>
  <div class="flex h-[calc(100vh-64px)]">
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 p-6 overflow-y-auto bg-gray-50">
      <!-- Mobile menu button -->
      <div class="md:hidden mb-6">
        <button id="mobileMenuBtn" class="flex items-center justify-center bg-white shadow-md rounded-lg p-2 w-full">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
          <span class="ml-2 text-indigo-600 font-medium">Menu</span>
        </button>
      </div>

      <!-- Page Title -->
      <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Vehicle Management Dashboard</h1>
        <p class="text-gray-600">Monitor and manage your car inventory</p>
      </div>

      <?php include 'includes/stats.php'; ?>

      <!-- Recent Inventory & Add New Car -->
      <div class="flex flex-col lg:flex-row gap-6">
        <!-- Car Inventory Table -->
        <div class="dashboard-card bg-white p-6 w-full lg:w-3/4">
          <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
            <h2 class="text-xl font-bold text-gray-800">Recent Inventory</h2>
            <button id="addNewCarBtn" class="bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white px-4 py-2 rounded-lg transition duration-300 flex items-center shadow-md">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
              </svg>
              Add New Vehicle
            </button>
          </div>

          <!-- Updated table to match the output from the fixed getRecentInventory function -->
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead>
                <tr class="bg-gray-50">
                  <th class="px-4 py-3 text-left text-sm font-medium text-gray-600 rounded-tl-lg">Car Name</th>
                  <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">Year</th>
                  <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">Mileage</th>
                  <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">Fuel Type</th>
                  <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">Status</th>
                  <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">Date Added</th>
                  <th class="px-4 py-3 text-left text-sm font-medium text-gray-600 rounded-tr-lg">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($recentVehicles)): ?>
                  <tr class="border-b border-gray-100">
                    <td colspan="7" class="px-4 py-5 text-center text-gray-600">No vehicles found</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($recentVehicles as $vehicle): ?>
                    <tr class="border-b border-gray-100 table-row">
                      <td class="px-4 py-3 text-gray-800"><?php echo htmlspecialchars($vehicle['vehicle_name']); ?></td>
                      <td class="px-4 py-3 text-gray-800"><?php echo htmlspecialchars($vehicle['year']); ?></td>
                      <td class="px-4 py-3 text-gray-800"><?php echo number_format($vehicle['mileage']); ?></td>
                      <td class="px-4 py-3 text-gray-800"><?php echo htmlspecialchars($vehicle['fuel_type']); ?></td>
                      <td class="px-4 py-3">
                        <span class="px-3 py-1 <?php echo htmlspecialchars($vehicle['css_class']); ?> rounded-full text-xs font-medium">
                          <?php echo htmlspecialchars($vehicle['status']); ?>
                        </span>
                      </td>
                      <td class="px-4 py-3 text-gray-800"><?php echo htmlspecialchars($vehicle['date_added']); ?></td>
                      <td class="px-4 py-3">
                        <div class="flex space-x-2">
                          <a href="edit_vehicle_form.php?id=<?php echo $vehicle['id']; ?>" class="p-1.5 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition-all" title="Edit">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                              <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                            </svg>
                          </a>
                          <button onclick="confirmDelete(<?php echo $vehicle['id']; ?>, '<?php echo addslashes($vehicle['year'] . ' ' . $vehicle['vehicle_name']); ?>')" class="p-1.5 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition-all" title="Delete">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                              <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                          </button>
                          <a href="vehicle_details.php?id=<?php echo $vehicle['id']; ?>" class="p-1.5 rounded-lg bg-green-50 text-green-600 hover:bg-green-100 transition-all" title="View Details">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                              <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                              <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                            </svg>
                          </a>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
          <!-- Add this modal HTML code just before the closing </body> tag in your index.php file -->

          <!-- Delete Vehicle Confirmation Modal -->
          <div id="deleteVehicleModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen p-4">
              <div class="bg-white rounded-xl w-full max-w-md mx-auto shadow-2xl p-6">
                <div class="flex items-center justify-center mb-4">
                  <div class="bg-red-100 rounded-full p-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-600" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                  </div>
                </div>
                <h3 class="text-xl font-bold text-center text-gray-800 mb-4">Confirm Vehicle Deletion</h3>
                <p class="text-center text-gray-600 mb-6" id="deleteVehicleMessage">Are you sure you want to delete this vehicle? This action cannot be undone.</p>
                <div class="flex justify-center space-x-3">
                  <button id="cancelVehicleDeleteBtn" class="px-5 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition duration-300 font-medium text-sm">
                    Cancel
                  </button>
                  <form id="deleteVehicleForm" method="post" action="process_delete_vehicle.php">
                    <input type="hidden" id="deleteVehicleId" name="vehicle_id" value="">
                    <button type="submit" class="px-5 py-2.5 bg-red-600 text-white rounded-lg hover:bg-red-700 transition duration-300 font-medium text-sm">
                      Delete Vehicle
                    </button>
                  </form>
                </div>
              </div>
            </div>
          </div>

          <!-- Add this JavaScript right after the modal HTML -->
          <script>
            // Function to show delete confirmation modal
            function confirmDelete(vehicleId, vehicleName) {
              // Update the modal content
              document.getElementById('deleteVehicleMessage').textContent = `Are you sure you want to delete ${vehicleName}? This action cannot be undone.`;
              document.getElementById('deleteVehicleId').value = vehicleId;

              // Show the modal
              document.getElementById('deleteVehicleModal').classList.remove('hidden');
            }

            // Event listeners for modal interactions
            document.addEventListener('DOMContentLoaded', function() {
              // Cancel button closes the modal
              document.getElementById('cancelVehicleDeleteBtn').addEventListener('click', function() {
                document.getElementById('deleteVehicleModal').classList.add('hidden');
              });

              // Close modal when clicking outside
              document.getElementById('deleteVehicleModal').addEventListener('click', function(event) {
                if (event.target === this) {
                  this.classList.add('hidden');
                }
              });
            });

            // Close modal on escape key
            document.addEventListener('keydown', function(event) {
              if (event.key === 'Escape') {
                document.getElementById('deleteVehicleModal').classList.add('hidden');
              }
            });
          </script>
          <div class="mt-6 flex justify-between items-center">
            <div>
              <span class="text-sm text-gray-600">Showing <?php echo count($recentVehicles); ?> of <?php echo $totalVehicles; ?> vehicles</span>
            </div>
            <div class="flex space-x-1">
              <a href="vehicles.php" class="px-4 py-1.5 rounded-md bg-white border border-gray-200 text-gray-600 text-sm hover:bg-gray-50 transition-all flex items-center">
                <span>View All</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
              </a>
            </div>
          </div>

          <div class="mt-6 flex justify-between items-center">
            <div>
              <span class="text-sm text-gray-600">Showing 5 of 152 vehicles</span>
            </div>
            <div class="flex space-x-1">
              <button class="px-3 py-1.5 rounded-md bg-white border border-gray-200 text-gray-600 text-sm hover:bg-gray-50 transition-all">Previous</button>
              <button class="px-3 py-1.5 rounded-md bg-indigo-600 text-white text-sm hover:bg-indigo-700 transition-all">1</button>
              <button class="px-3 py-1.5 rounded-md bg-white border border-gray-200 text-gray-600 text-sm hover:bg-gray-50 transition-all">2</button>
              <button class="px-3 py-1.5 rounded-md bg-white border border-gray-200 text-gray-600 text-sm hover:bg-gray-50 transition-all">3</button>
              <button class="px-3 py-1.5 rounded-md bg-white border border-gray-200 text-gray-600 text-sm hover:bg-gray-50 transition-all">Next</button>
            </div>
          </div>
        </div>

        <!-- Add New Car Form -->
        <div class="dashboard-card bg-white p-6 w-full lg:w-1/4">
          <h2 class="text-xl font-bold text-gray-800 mb-6"></h2>

        </div>
      </div>

    </main>
  </div>

  <!-- Add New Car Modal -->
  <div id="addCarModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4">
      <div class="bg-white rounded-xl w-full max-w-3xl mx-auto shadow-2xl modal-animation">
        <div class="flex justify-between items-center border-b p-6">
          <div class="flex items-center">
            <div class="bg-indigo-100 p-2 rounded-lg mr-3">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-600" viewBox="0 0 20 20" fill="currentColor">
                <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zm7 0a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H14a1 1 0 001-1v-3h-5v-1h9V8h-1a1 1 0 00-1-1h-6a1 1 0 00-1 1v7.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1V5a1 1 0 00-1-1H3z" />
              </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-800">Add New Vehicle</h3>
          </div>
          <button id="closeModalBtn" class="text-gray-400 hover:text-gray-500 focus:outline-none transition-all p-1 hover:bg-gray-100 rounded-full">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <div class="p-6 max-h-[70vh] overflow-y-auto">
          <form id="addCarForm" class="grid grid-cols-1 md:grid-cols-2 gap-6" method="post" action="process_add_vehicle.php" enctype="multipart/form-data">
            <div>
              <label for="modalMake" class="block text-sm font-medium text-gray-700 mb-1">Make</label>
              <select id="modalMake" name="make" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                <option value="">Select Make</option>
                <?php foreach ($dropdowns['makes'] as $make): ?>
                  <option value="<?php echo $make['id']; ?>"><?php echo htmlspecialchars($make['name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label for="modalModel" class="block text-sm font-medium text-gray-700 mb-1">Model</label>
              <select id="modalModel" name="model" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                <option value="">Select Make First</option>
              </select>
            </div>
            <div>
              <label for="modalYear" class="block text-sm font-medium text-gray-700 mb-1">Year</label>
              <input type="number" id="modalYear" name="year" min="1900" max="<?php echo date('Y') + 1; ?>" placeholder="e.g. <?php echo date('Y'); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
            </div>
            <div>
              <label for="modalBodyStyle" class="block text-sm font-medium text-gray-700 mb-1">Body Type</label>
              <select id="modalBodyStyle" name="body_style" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                <option value="">Select Body Type</option>
                <?php foreach ($dropdowns['bodyTypes'] as $bodyType): ?>
                  <option value="<?php echo $bodyType['id']; ?>"><?php echo htmlspecialchars($bodyType['name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label for="modalMileage" class="block text-sm font-medium text-gray-700 mb-1">Mileage</label>
              <input type="text" id="modalMileage" name="mileage" placeholder="e.g. 15000" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
            </div>
            <div>
              <label for="modalPrice" class="block text-sm font-medium text-gray-700 mb-1">Price (Optional)</label>
              <input type="text" id="modalPrice" name="price" placeholder="Leave empty for no price" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
              <p class="text-xs text-gray-500 mt-1">Leave empty if price is not available or negotiable</p>
            </div>
            <div>
              <label for="modalVIN" class="block text-sm font-medium text-gray-700 mb-1">VIN</label>
              <input type="text" id="modalVIN" name="vin" placeholder="Vehicle Identification Number" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
            </div>
            <div>
              <label for="modalFuelType" class="block text-sm font-medium text-gray-700 mb-1">Fuel Type</label>
              <select id="modalFuelType" name="fuel_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                <option value="">Select Fuel Type</option>
                <?php foreach ($dropdowns['fuelTypes'] as $fuelType): ?>
                  <option value="<?php echo $fuelType['id']; ?>"><?php echo htmlspecialchars($fuelType['name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label for="modalTransmission" class="block text-sm font-medium text-gray-700 mb-1">Transmission</label>
              <select id="modalTransmission" name="transmission" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                <option value="">Select Transmission</option>
                <?php foreach ($dropdowns['transmissions'] as $transmission): ?>
                  <option value="<?php echo $transmission['id']; ?>"><?php echo htmlspecialchars($transmission['name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label for="modalDrive" class="block text-sm font-medium text-gray-700 mb-1">Drive Type</label>
              <select id="modalDrive" name="drivetrain" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                <option value="">Select Drive Type</option>
                <?php foreach ($dropdowns['driveTypes'] as $driveType): ?>
                  <option value="<?php echo $driveType['id']; ?>"><?php echo htmlspecialchars($driveType['name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label for="modalEngine" class="block text-sm font-medium text-gray-700 mb-1">Engine</label>
              <input type="text" id="modalEngine" name="engine" placeholder="e.g. 2.0L 4-Cylinder" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
            </div>
            <div>
              <label for="modalExteriorColor" class="block text-sm font-medium text-gray-700 mb-1">Exterior Color</label>
              <select id="modalExteriorColor" name="exterior_color" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                <option value="">Select Exterior Color</option>
                <?php foreach ($dropdowns['colors'] as $color): ?>
                  <option value="<?php echo $color['id']; ?>"><?php echo htmlspecialchars($color['name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label for="modalInteriorColor" class="block text-sm font-medium text-gray-700 mb-1">Interior Color</label>
              <select id="modalInteriorColor" name="interior_color" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                <option value="">Select Interior Color</option>
                <?php foreach ($dropdowns['colors'] as $color): ?>
                  <option value="<?php echo $color['id']; ?>"><?php echo htmlspecialchars($color['name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label for="modalStatus" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
              <select id="modalStatus" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                <option value="">Select Status</option>
                <?php foreach ($dropdowns['statuses'] as $status): ?>
                  <option value="<?php echo $status['id']; ?>"><?php echo htmlspecialchars($status['name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label for="modalNotes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
              <textarea id="modalNotes" name="description" rows="2" placeholder="Additional information about the vehicle" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm resize-none"></textarea>
            </div>
            <div class="col-span-1 md:col-span-2 border-t pt-4 mt-4">
              <label class="block text-sm font-medium text-gray-700 mb-3">Vehicle Features</label>

              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($featuresByCategory as $category => $categoryFeatures): ?>
                  <div class="bg-gray-50 p-3 rounded-lg">
                    <h4 class="font-medium text-gray-700 mb-2"><?php echo htmlspecialchars($category); ?></h4>
                    <div class="space-y-2">
                      <?php foreach ($categoryFeatures as $feature): ?>
                        <div class="flex items-center">
                          <input type="checkbox" id="feature_<?php echo $feature['id']; ?>" name="features[]" value="<?php echo $feature['id']; ?>" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                          <label for="feature_<?php echo $feature['id']; ?>" class="ml-2 block text-sm text-gray-700">
                            <?php echo htmlspecialchars($feature['name']); ?>
                          </label>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>

              <?php if (empty($features)): ?>
                <div class="bg-yellow-50 p-4 rounded-lg">
                  <p class="text-sm text-yellow-700">No features found. You can add features in the "Manage Dropdowns" section.</p>
                </div>
              <?php endif; ?>
            </div>
            <div class="col-span-1 md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-1">Vehicle Images</label>
              <div class="file-drop-area">
                <input type="file" id="modalImages" name="images[]" multiple class="file-input" onChange="updateFileNames()">
                <div class="flex flex-col items-center">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mb-3 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                  <p class="text-sm text-gray-600 mb-1 font-medium">Drag & drop vehicle images here</p>
                  <p class="text-xs text-gray-500">or click to browse files</p>
                </div>
                <div id="fileNames" class="mt-3 text-gray-600 text-xs space-y-1"></div>
              </div>
            </div>
          </form>
        </div>
        <div class="flex justify-end border-t p-6 space-x-3">
          <button id="cancelBtn" class="px-5 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition duration-300 font-medium text-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            Cancel
          </button>
          <button id="saveVehicleBtn" class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg hover:from-indigo-700 hover:to-purple-700 transition duration-300 font-medium text-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-md">
            Save Vehicle
          </button>
        </div>
      </div>
    </div>
  </div>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Store models data globally with explicit object conversion
      window.allModels = JSON.parse('<?php echo json_encode($allModels, JSON_HEX_APOS); ?>');

      console.log('Models data loaded:', window.allModels);
      console.log('Available keys:', Object.keys(window.allModels));

      function updateModelDropdown(makeId, modelDropdown) {
        // Clear current options
        modelDropdown.innerHTML = '<option value="">Select Model</option>';

        if (!makeId) return;

        // Ensure makeId is treated as a string for consistent lookup
        const makeIdStr = makeId.toString();
        console.log('Looking for models with make ID:', makeIdStr);

        // Check if models exist for this make and is an array
        if (window.allModels[makeIdStr] && Array.isArray(window.allModels[makeIdStr]) && window.allModels[makeIdStr].length > 0) {
          console.log('Found models:', window.allModels[makeIdStr].length);

          // Add models to dropdown
          for (let i = 0; i < window.allModels[makeIdStr].length; i++) {
            const model = window.allModels[makeIdStr][i];
            const option = document.createElement('option');
            option.value = model.id;
            option.textContent = model.name;
            modelDropdown.appendChild(option);
            console.log('Added model option:', model.name, 'with ID:', model.id);
          }
        } else {
          console.error('No valid models array found for make ID:', makeIdStr);
          modelDropdown.innerHTML = '<option value="">No models available for this make</option>';
        }
      }

      // Quick add form
      const makeDropdown = document.getElementById('make');
      const modelDropdown = document.getElementById('model');

      if (makeDropdown && modelDropdown) {
        makeDropdown.addEventListener('change', function() {
          updateModelDropdown(this.value, modelDropdown);
        });
      }

      // Modal form
      const modalMakeDropdown = document.getElementById('modalMake');
      const modalModelDropdown = document.getElementById('modalModel');

      if (modalMakeDropdown && modalModelDropdown) {
        modalMakeDropdown.addEventListener('change', function() {
          updateModelDropdown(this.value, modalModelDropdown);
        });
      }

      // Modal controls
      const addNewCarBtn = document.getElementById('addNewCarBtn');
      const addCarModal = document.getElementById('addCarModal');
      const closeModalBtn = document.getElementById('closeModalBtn');
      const cancelBtn = document.getElementById('cancelBtn');
      const saveVehicleBtn = document.getElementById('saveVehicleBtn');
      const addCarForm = document.getElementById('addCarForm');

      if (addNewCarBtn && addCarModal) {
        addNewCarBtn.addEventListener('click', function() {
          addCarModal.classList.remove('hidden');
        });

        if (closeModalBtn) {
          closeModalBtn.addEventListener('click', function() {
            addCarModal.classList.add('hidden');
          });
        }

        if (cancelBtn) {
          cancelBtn.addEventListener('click', function() {
            addCarModal.classList.add('hidden');
          });
        }

        if (saveVehicleBtn && addCarForm) {
          saveVehicleBtn.addEventListener('click', function() {
            addCarForm.submit();
          });
        }
      }

      // File upload preview
      window.updateFileNames = function() {
        const fileInput = document.getElementById('modalImages');
        const fileNamesDiv = document.getElementById('fileNames');

        if (fileInput && fileNamesDiv) {
          fileNamesDiv.innerHTML = '';

          if (fileInput.files.length > 0) {
            for (let i = 0; i < fileInput.files.length; i++) {
              const file = fileInput.files[i];
              const fileItem = document.createElement('div');
              fileItem.className = 'flex items-center';
              fileItem.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-indigo-500" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd" />
            </svg>
            <span class="truncate">${file.name}</span>
          `;
              fileNamesDiv.appendChild(fileItem);
            }
          }
        }
      };

      // Debug helper
      window.debugModels = function(makeId) {
        console.log('=== DEBUG MODELS ===');
        console.log('Requested make ID:', makeId);
        console.log('Type of allModels:', typeof window.allModels);
        console.log('Available keys:', Object.keys(window.allModels));

        const makeIdStr = makeId.toString();
        if (window.allModels[makeIdStr]) {
          console.log('Models for this make:', window.allModels[makeIdStr]);
          console.log('Is Array?', Array.isArray(window.allModels[makeIdStr]));
          console.log('Length:', window.allModels[makeIdStr].length);
        } else {
          console.log('No models found for make ID:', makeIdStr);
        }
        console.log('===================');
      };
    });
  </script>
  <script src="assets/js/index.js"></script>
</body>

</html>