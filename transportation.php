<?php
require_once 'config/db.php';
session_start();

// Fetch taxi routes from database
$taxi_routes = [];
$taxi_service_info = ['service_title' => 'Best Taxi Service for Umrah and Hajj', 'year' => date('Y')];

// Get taxi service info
$stmt = $conn->prepare("SELECT service_title, year FROM transportation_settings WHERE service_type = 'taxi' LIMIT 1");
if ($stmt) {
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result && $data = $result->fetch_assoc()) {
    $taxi_service_info = $data;
  }
  $stmt->close();
}

// Get taxi routes
$stmt = $conn->prepare("SELECT id, route_number, route_name, camry_sonata_price, starex_staria_price, hiace_price 
                      FROM taxi_routes 
                      WHERE service_title = ? AND year = ?
                      ORDER BY route_number");
if ($stmt) {
  $stmt->bind_param("si", $taxi_service_info['service_title'], $taxi_service_info['year']);
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    $taxi_routes[] = $row;
  }
  $stmt->close();
}

// Fetch rent-a-car routes
$rentacar_routes = [];
$rentacar_service_info = ['service_title' => 'Best Umrah and Hajj Rent A Car', 'year' => date('Y')];

// Get rent-a-car service info
$stmt = $conn->prepare("SELECT service_title, year FROM transportation_settings WHERE service_type = 'rentacar' LIMIT 1");
if ($stmt) {
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result && $data = $result->fetch_assoc()) {
    $rentacar_service_info = $data;
  }
  $stmt->close();
}

// Get rent-a-car routes
$stmt = $conn->prepare("SELECT id, route_number, route_name, gmc_16_19_price, gmc_22_23_price, coaster_price 
                      FROM rentacar_routes 
                      WHERE service_title = ? AND year = ?
                      ORDER BY route_number");
if ($stmt) {
  $stmt->bind_param("si", $rentacar_service_info['service_title'], $rentacar_service_info['year']);
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    $rentacar_routes[] = $row;
  }
  $stmt->close();
}

// Handle filters
$filter_type = isset($_GET['transportType']) ? $_GET['transportType'] : '';
$filter_route = isset($_GET['route']) ? $_GET['route'] : '';
$filter_city = isset($_GET['city']) ? $_GET['city'] : '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Transportation - UmrahFlights</title>
  <!-- Include Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <?php include 'includes/css-links.php' ?>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <style>
    body {
      margin-top: 65px !important;
    }

    .transport-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }

    .transport-card {
      transition: all 0.3s ease;
    }

    .tab-button.active {
      background-color: #17a2b8;
      color: white;
    }

    .text-blue-800 {
      color: white !important;
    }
  </style>
</head>

<body class="bg-gray-50">
  <!-- Navbar -->
  <?php include 'includes/navbar.php'; ?>
  <!-- Main Content -->
  <section class="py-12 px-4">
    <div class="container mx-auto max-w-6xl">
      <!-- Filter Form -->
      <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-xl font-semibold text-gray-800"><i class="fas fa-filter mr-2 text-green-600"></i>Find Your Perfect Transport</h3>
          <a href="?" class="text-green-600 hover:text-green-700 text-sm font-medium transition duration-300 ease-in-out">
            <i class="fas fa-times mr-1"></i>Clear Filters
          </a>
        </div>
        <form action="" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label for="transportType" class="block text-sm font-medium text-gray-700 mb-1">Transport Type</label>
            <select name="transportType" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" id="transportType">
              <option value="">All Types</option>
              <option value="taxi" <?php echo $filter_type === 'taxi' ? 'selected' : ''; ?>>Taxi</option>
              <option value="rentacar" <?php echo $filter_type === 'rentacar' ? 'selected' : ''; ?>>Rent A Car</option>
            </select>
          </div>

          <div>
            <label for="route" class="block text-sm font-medium text-gray-700 mb-1">Route</label>
            <select name="route" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" id="route">
              <option value="">All Routes</option>
              <?php
              $all_routes = [];
              foreach ($taxi_routes as $route) {
                if (!in_array($route['route_name'], $all_routes)) {
                  $all_routes[] = $route['route_name'];
                }
              }
              foreach ($rentacar_routes as $route) {
                if (!in_array($route['route_name'], $all_routes)) {
                  $all_routes[] = $route['route_name'];
                }
              }
              sort($all_routes);
              foreach ($all_routes as $route_name) {
                echo '<option value="' . htmlspecialchars($route_name) . '" ' .
                  ($filter_route === $route_name ? 'selected' : '') . '>' .
                  htmlspecialchars($route_name) . '</option>';
              }
              ?>
            </select>
          </div>

          <div class="flex items-end gap-4">
            <button type="submit" class="flex-1 bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-md transition duration-300 ease-in-out">
              <i class="fas fa-search mr-2"></i>Search
            </button>
          </div>
        </form>
      </div>

      <!-- Tabs -->
      <div class="flex border-b border-gray-200 mb-8">
        <button class="tab-button py-3 px-6 font-medium rounded-t-lg border-t border-r border-l border-gray-200 active" data-target="all-tab">
          All Transportation
        </button>
        <button class="tab-button py-3 px-6 font-medium rounded-t-lg border-t border-r border-l border-gray-200" data-target="taxi-tab">
          Taxi Services
        </button>
        <button class="tab-button py-3 px-6 font-medium rounded-t-lg border-t border-r border-l border-gray-200" data-target="rentacar-tab">
          Rent A Car
        </button>
      </div>

      <!-- All Transportation Tab -->
      <div id="all-tab" class="tab-content">
        <!-- Taxi Section -->
        <div id="taxi-section" class="mb-12">
          <div class="flex items-center mb-6">
            <div class="w-10 h-10 rounded-full bg-yellow-400 flex items-center justify-center mr-3">
              <i class="fas fa-taxi text-white"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($taxi_service_info['service_title']); ?></h2>
          </div>

          <?php if (empty($taxi_routes)): ?>
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
              <i class="fas fa-exclamation-circle text-yellow-500 text-4xl mb-4"></i>
              <p class="text-gray-600">No taxi routes available at the moment.</p>
            </div>
          <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <?php
              foreach ($taxi_routes as $route):
                // Apply filters
                if (($filter_type && $filter_type !== 'taxi') ||
                  ($filter_route && $filter_route !== $route['route_name'])
                ) {
                  continue;
                }
              ?>
                <div class="bg-white rounded-lg shadow-md p-6 transport-card">
                  <div class="flex justify-between items-start mb-4">
                    <div>
                      <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($route['route_name']); ?></h3>
                      <p class="text-gray-600 text-sm">Route #<?php echo $route['route_number']; ?></p>
                    </div>
                    <div class="bg-yellow-100 text-yellow-800 text-xs font-medium px-2 py-1 rounded-full">
                      <i class="fas fa-taxi mr-1"></i> Taxi
                    </div>
                  </div>

                  <div class="mb-4">
                    <div class="border-b border-gray-200 py-2">
                      <div class="flex justify-between">
                        <span class="text-gray-600"><i class="fas fa-car mr-1"></i> Camry / Sonata</span>
                        <span class="font-semibold">PKR <?php echo number_format($route['camry_sonata_price'], 2); ?></span>
                      </div>
                    </div>
                    <div class="border-b border-gray-200 py-2">
                      <div class="flex justify-between">
                        <span class="text-gray-600"><i class="fas fa-car-side mr-1"></i> Starex / Staria</span>
                        <span class="font-semibold">PKR <?php echo number_format($route['starex_staria_price'], 2); ?></span>
                      </div>
                    </div>
                    <div class="py-2">
                      <div class="flex justify-between">
                        <span class="text-gray-600"><i class="fas fa-shuttle-van mr-1"></i> Hiace</span>
                        <span class="font-semibold">PKR <?php echo number_format($route['hiace_price'], 2); ?></span>
                      </div>
                    </div>
                  </div>

                  <a href="transportation-booking.php?type=taxi&route=<?php echo $route['id']; ?>" class="block w-full text-center bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-md transition duration-300 ease-in-out">
                    Book Now
                  </a>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- Rent A Car Section -->
        <div id="rentacar-section">
          <div class="flex items-center mb-6">
            <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center mr-3">
              <i class="fas fa-car text-white"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($rentacar_service_info['service_title']); ?></h2>
          </div>

          <?php if (empty($rentacar_routes)): ?>
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
              <i class="fas fa-exclamation-circle text-blue-500 text-4xl mb-4"></i>
              <p class="text-gray-600">No rent-a-car routes available at the moment.</p>
            </div>
          <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <?php
              foreach ($rentacar_routes as $route):
                // Apply filters
                if (($filter_type && $filter_type !== 'rentacar') ||
                  ($filter_route && $filter_route !== $route['route_name'])
                ) {
                  continue;
                }
              ?>
                <div class="bg-white rounded-lg shadow-md p-6 transport-card">
                  <div class="flex justify-between items-start mb-4">
                    <div>
                      <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($route['route_name']); ?></h3>
                      <p class="text-gray-600 text-sm">Route #<?php echo $route['route_number']; ?></p>
                    </div>
                    <div class="bg-blue-100 text-blue-800 text-xs font-medium px-2 py-1 rounded-full">
                      <i class="fas fa-car mr-1"></i> Rent A Car
                    </div>
                  </div>

                  <div class="mb-4">
                    <div class="border-b border-gray-200 py-2">
                      <div class="flex justify-between">
                        <span class="text-gray-600"><i class="fas fa-bus-alt mr-1"></i> GMC 16-19 Seats</span>
                        <span class="font-semibold">PKR <?php echo number_format($route['gmc_16_19_price'], 2); ?></span>
                      </div>
                    </div>
                    <div class="border-b border-gray-200 py-2">
                      <div class="flex justify-between">
                        <span class="text-gray-600"><i class="fas fa-bus mr-1"></i> GMC 22-23 Seats</span>
                        <span class="font-semibold">PKR <?php echo number_format($route['gmc_22_23_price'], 2); ?></span>
                      </div>
                    </div>
                    <div class="py-2">
                      <div class="flex justify-between">
                        <span class="text-gray-600"><i class="fas fa-bus mr-1"></i> Coaster</span>
                        <span class="font-semibold">PKR <?php echo number_format($route['coaster_price'], 2); ?></span>
                      </div>
                    </div>
                  </div>

                  <a href="transportation-booking.php?type=rentacar&route=<?php echo $route['id']; ?>" class="block w-full text-center bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition duration-300 ease-in-out">
                    Book Now
                  </a>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Taxi Tab -->
      <div id="taxi-tab" class="tab-content hidden">
        <div class="flex items-center mb-6">
          <div class="w-10 h-10 rounded-full bg-yellow-400 flex items-center justify-center mr-3">
            <i class="fas fa-taxi text-white"></i>
          </div>
          <h2 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($taxi_service_info['service_title']); ?></h2>
        </div>

        <?php if (empty($taxi_routes)): ?>
          <div class="bg-white rounded-lg shadow-md p-6 text-center">
            <i class="fas fa-exclamation-circle text-yellow-500 text-4xl mb-4"></i>
            <p class="text-gray-600">No taxi routes available at the moment.</p>
          </div>
        <?php else: ?>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php
            foreach ($taxi_routes as $route):
              if ($filter_route && $filter_route !== $route['route_name']) {
                continue;
              }
            ?>
              <div class="bg-white rounded-lg shadow-md p-6 transport-card">
                <div class="flex justify-between items-start mb-4">
                  <div>
                    <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($route['route_name']); ?></h3>
                    <p class="text-gray-600 text-sm">Route #<?php echo $route['route_number']; ?></p>
                  </div>
                  <div class="bg-yellow-100 text-yellow-800 text-xs font-medium px-2 py-1 rounded-full">
                    <i class="fas fa-taxi mr-1"></i> Taxi
                  </div>
                </div>

                <div class="mb-4">
                  <div class="border-b border-gray-200 py-2">
                    <div class="flex justify-between">
                      <span class="text-gray-600"><i class="fas fa-car mr-1"></i> Camry / Sonata</span>
                      <span class="font-semibold">PKR <?php echo number_format($route['camry_sonata_price'], 2); ?></span>
                    </div>
                  </div>
                  <div class="border-b border-gray-200 py-2">
                    <div class="flex justify-between">
                      <span class="text-gray-600"><i class="fas fa-car-side mr-1"></i> Starex / Staria</span>
                      <span class="font-semibold">PKR <?php echo number_format($route['starex_staria_price'], 2); ?></span>
                    </div>
                  </div>
                  <div class="py-2">
                    <div class="flex justify-between">
                      <span class="text-gray-600"><i class="fas fa-shuttle-van mr-1"></i> Hiace</span>
                      <span class="font-semibold">PKR <?php echo number_format($route['hiace_price'], 2); ?></span>
                    </div>
                  </div>
                </div>

                <a href="transportation-booking.php?type=taxi&route=<?php echo $route['id']; ?>" class="block w-full text-center bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-md transition duration-300 ease-in-out">
                  Book Now
                </a>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Rent A Car Tab -->
      <div id="rentacar-tab" class="tab-content hidden">
        <div class="flex items-center mb-6">
          <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center mr-3">
            <i class="fas fa-car text-white"></i>
          </div>
          <h2 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($rentacar_service_info['service_title']); ?></h2>
        </div>

        <?php if (empty($rentacar_routes)): ?>
          <div class="bg-white rounded-lg shadow-md p-6 text-center">
            <i class="fas fa-exclamation-circle text-blue-500 text-4xl mb-4"></i>
            <p class="text-gray-600">No rent-a-car routes available at the moment.</p>
          </div>
        <?php else: ?>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php
            foreach ($rentacar_routes as $route):
              if ($filter_route && $filter_route !== $route['route_name']) {
                continue;
              }
            ?>
              <div class="bg-white rounded-lg shadow-md p-6 transport-card">
                <div class="flex justify-between items-start mb-4">
                  <div>
                    <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($route['route_name']); ?></h3>
                    <p class="text-gray-600 text-sm">Route #<?php echo $route['route_number']; ?></p>
                  </div>
                  <div class="bg-blue-100 text-blue-800 text-xs font-medium px-2 py-1 rounded-full">
                    <i class="fas fa-car mr-1"></i> Rent A Car
                  </div>
                </div>

                <div class="mb-4">
                  <div class="border-b border-gray-200 py-2">
                    <div class="flex justify-between">
                      <span class="text-gray-600"><i class="fas fa-bus-alt mr-1"></i> GMC 16-19 Seats</span>
                      <span class="font-semibold">PKR <?php echo number_format($route['gmc_16_19_price'], 2); ?></span>
                    </div>
                  </div>
                  <div class="border-b border-gray-200 py-2">
                    <div class="flex justify-between">
                      <span class="text-gray-600"><i class="fas fa-bus mr-1"></i> GMC 22-23 Seats</span>
                      <span class="font-semibold">PKR <?php echo number_format($route['gmc_22_23_price'], 2); ?></span>
                    </div>
                  </div>
                  <div class="py-2">
                    <div class="flex justify-between">
                      <span class="text-gray-600"><i class="fas fa-bus mr-1"></i> Coaster</span>
                      <span class="font-semibold">PKR <?php echo number_format($route['coaster_price'], 2); ?></span>
                    </div>
                  </div>
                </div>

                <a href="transportation-booking.php?type=rentacar&route=<?php echo $route['id']; ?>" class="block w-full text-center bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition duration-300 ease-in-out">
                  Book Now
                </a>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Information Section -->
      <div class="bg-white rounded-lg shadow-md p-6 mt-12">
        <h3 class="text-xl font-bold text-gray-800 mb-4">Transportation Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <h4 class="font-semibold text-lg mb-2">About Our Transport Services</h4>
            <p class="text-gray-600 mb-3">We offer a variety of transportation options to make your Umrah journey comfortable and convenient. Whether you're traveling between Makkah, Madinah, or Jeddah, we have you covered with our premium fleet of vehicles.</p>
            <p class="text-gray-600">All our vehicles are well-maintained, air-conditioned, and operated by experienced drivers who know the routes well.</p>
          </div>
          <div>
            <h4 class="font-semibold text-lg mb-2">Important Notes</h4>
            <ul class="list-disc list-inside text-gray-600 space-y-2">
              <li>Booking should be made at least 24 hours in advance</li>
              <li>Prices are subject to change during peak seasons</li>
              <li>Additional charges may apply for luggage exceeding the standard allowance</li>
              <li>All vehicles are regularly sanitized for your safety</li>
              <li>Please be ready at the pickup location 15 minutes before the scheduled time</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- FAQ Section -->
      <div class="mt-12">
        <h3 class="text-2xl font-bold text-gray-800 mb-6">Frequently Asked Questions</h3>
        <div class="space-y-4">
          <div class="bg-white rounded-lg shadow-md p-4">
            <button class="flex justify-between items-center w-full text-left focus:outline-none" onclick="toggleFAQ(this)">
              <span class="font-semibold text-lg">What is the difference between Taxi and Rent A Car services?</span>
              <svg class="w-5 h-5 text-gray-500 faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
              </svg>
            </button>
            <div class="mt-2 hidden faq-answer">
              <p class="text-gray-600">Taxi services provide a driver and vehicle for a specific route, while Rent A Car services offer vehicles like GMC vans and Coasters for larger groups with more flexibility for your itinerary.</p>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow-md p-4">
            <button class="flex justify-between items-center w-full text-left focus:outline-none" onclick="toggleFAQ(this)">
              <span class="font-semibold text-lg">How do I book a transportation service?</span>
              <svg class="w-5 h-5 text-gray-500 faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
              </svg>
            </button>
            <div class="mt-2 hidden faq-answer">
              <p class="text-gray-600">Simply browse our available routes, select the type of vehicle you need, click on "Book Now," and follow the simple booking process. You'll receive a confirmation once your booking is complete.</p>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow-md p-4">
            <button class="flex justify-between items-center w-full text-left focus:outline-none" onclick="toggleFAQ(this)">
              <span class="font-semibold text-lg">Can I cancel my transportation booking?</span>
              <svg class="w-5 h-5 text-gray-500 faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
              </svg>
            </button>
            <div class="mt-2 hidden faq-answer">
              <p class="text-gray-600">Yes, you can cancel your booking up to 24 hours before the scheduled departure for a full refund. Cancellations made less than 24 hours in advance may be subject to a cancellation fee.</p>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow-md p-4">
            <button class="flex justify-between items-center w-full text-left focus:outline-none" onclick="toggleFAQ(this)">
              <span class="font-semibold text-lg">Are your vehicles air-conditioned?</span>
              <svg class="w-5 h-5 text-gray-500 faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
              </svg>
            </button>
            <div class="mt-2 hidden faq-answer">
              <p class="text-gray-600">Yes, all our vehicles are well-maintained and fully air-conditioned for your comfort, especially important given the climate in Saudi Arabia.</p>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow-md p-4">
            <button class="flex justify-between items-center w-full text-left focus:outline-none" onclick="toggleFAQ(this)">
              <span class="font-semibold text-lg">Do you offer customized routes or only fixed routes?</span>
              <svg class="w-5 h-5 text-gray-500 faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
              </svg>
            </button>
            <div class="mt-2 hidden faq-answer">
              <p class="text-gray-600">We primarily offer fixed routes between major cities like Makkah, Madinah, and Jeddah. For customized routes or special requirements, please contact our customer service team for assistance.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>


  <!-- Footer -->
  <?php include 'includes/footer.php'; ?>
  <?php include 'includes/js-links.php' ?>

  <script>
    // Tab switching functionality
    document.addEventListener('DOMContentLoaded', function() {
      const tabButtons = document.querySelectorAll('.tab-button');
      const tabContents = document.querySelectorAll('.tab-content');

      tabButtons.forEach(button => {
        button.addEventListener('click', function() {
          // Remove active class from all buttons and hide all contents
          tabButtons.forEach(btn => btn.classList.remove('active'));
          tabContents.forEach(content => content.classList.add('hidden'));

          // Add active class to clicked button and show corresponding content
          this.classList.add('active');
          document.getElementById(this.dataset.target).classList.remove('hidden');
        });
      });

      // Check URL hash for direct tab access
      const hash = window.location.hash;
      if (hash === '#taxi-section') {
        document.querySelector('[data-target="taxi-tab"]').click();
      } else if (hash === '#rentacar-section') {
        document.querySelector('[data-target="rentacar-tab"]').click();
      }

      // Apply initial filter based on URL parameters
      const urlParams = new URLSearchParams(window.location.search);
      const transportType = urlParams.get('transportType');
      if (transportType === 'taxi') {
        document.querySelector('[data-target="taxi-tab"]').click();
      } else if (transportType === 'rentacar') {
        document.querySelector('[data-target="rentacar-tab"]').click();
      }
    });

    // FAQ toggle functionality
    function toggleFAQ(element) {
      const answer = element.nextElementSibling;
      const icon = element.querySelector('.faq-icon');

      // Toggle visibility
      if (answer.classList.contains('hidden')) {
        answer.classList.remove('hidden');
        icon.style.transform = 'rotate(180deg)';
      } else {
        answer.classList.add('hidden');
        icon.style.transform = 'rotate(0)';
      }
    }
  </script>
</body>

</html>