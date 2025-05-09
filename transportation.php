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
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700;800&display=swap');

    body {
      font-family: 'Manrope', sans-serif;
      background: #f9fafb;
      color: #1f2937;
      overflow-x: hidden;
    }

    .transport-card {
      background: linear-gradient(145deg, #ffffff, #f1f5f9);
      border-radius: 24px;
      overflow: hidden;
      transition: transform 0.4s ease, box-shadow 0.4s ease;
      position: relative;
      border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .transport-card:hover {
      transform: translateY(-12px);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    }

    .gradient-button {
      background: linear-gradient(90deg, #10b981, #059669);
      color: white;
      border-radius: 16px;
      padding: 12px 32px;
      font-weight: 600;
      transition: transform 0.3s ease, background 0.3s ease;
    }

    .gradient-button:hover {
      background: linear-gradient(90deg, #059669, #10b981);
      transform: scale(1.05);
    }

    .header-bg {
      background: linear-gradient(135deg, #059669 0%, #10b981 100%);
      position: relative;
      overflow: hidden;
      clip-path: polygon(0 0, 100% 0, 100% 85%, 0 100%);
    }

    .header-bg::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: url('https://source.unsplash.com/random/1600x400?mosque,kaaba') no-repeat center center/cover;
      opacity: 0.2;
      z-index: 0;
    }

    .tab-button {
      padding: 12px 24px;
      font-weight: 600;
      border-radius: 12px 12px 0 0;
      transition: background 0.3s ease, color 0.3s ease;
      background: #e5e7eb;
      color: #1f2937;
    }

    .tab-button.active {
      background: linear-gradient(90deg, #10b981, #059669);
      color: white;
    }

    .tab-button:hover {
      background: #d1d5db;
    }

    .filter-card {
      background: rgba(255, 255, 255, 0.95);
      border-radius: 24px;
      padding: 40px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      backdrop-filter: blur(10px);
      transition: transform 0.3s ease;
    }

    .filter-card:hover {
      transform: translateY(-8px);
    }

    .input-field {
      border: 1px solid #e5e7eb;
      border-radius: 16px;
      padding: 14px;
      background: #ffffff;
      transition: border-color 0.3s ease, box-shadow 0.3s ease;
    }

    .input-field:focus {
      border-color: #10b981;
      box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
      outline: none;
    }

    .section-title {
      position: relative;
      font-size: 2.25rem;
      font-weight: 800;
      color: #1f2937;
      padding-bottom: 16px;
      margin-bottom: 32px;
    }

    .section-title::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      width: 120px;
      height: 5px;
      background: linear-gradient(to right, #10b981, #059669);
      border-radius: 3px;
    }

    .faq-card {
      background: white;
      border-radius: 16px;
      padding: 20px;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .faq-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }

    .faq-icon {
      transition: transform 0.3s ease;
    }

    .faq-answer.active .faq-icon {
      transform: rotate(180deg);
    }

    .chip {
      display: inline-flex;
      align-items: center;
      padding: 10px 20px;
      background: #ecfdf5;
      color: #059669;
      border-radius: 9999px;
      font-size: 0.9rem;
      font-weight: 600;
      margin: 6px;
      transition: background 0.3s ease;
    }

    .chip:hover {
      background: #d1fae5;
    }

    .animate-on-scroll {
      opacity: 0;
      transform: translateY(20px);
      transition: opacity 0.6s ease, transform 0.6s ease;
    }

    .animate-on-scroll.visible {
      opacity: 1;
      transform: translateY(0);
    }

    .footer-bg {
      background: linear-gradient(to bottom, #1f2937, #111827);
      clip-path: polygon(0 10%, 100% 0, 100% 100%, 0 100%);
    }

    .social-icon {
      transition: transform 0.3s ease, color 0.3s ease;
      font-size: 1.5rem;
    }

    .social-icon:hover {
      transform: scale(1.4);
      color: #10b981;
    }

    @media (max-width: 768px) {
      .section-title {
        font-size: 1.75rem;
      }

      .filter-card {
        padding: 24px;
      }

      .tab-button {
        padding: 10px 16px;
        font-size: 0.875rem;
      }
    }
  </style>
</head>

<body>
  <!-- Navbar -->
  <?php include 'includes/navbar.php'; ?>

  <!-- Page Header -->
  <section class="header-bg text-white py-20 relative">
    <div class="container mx-auto px-4 relative z-10">
      <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight mb-4">Transportation for Umrah & Hajj</h1>
      <p class="text-lg md:text-xl text-gray-100 max-w-2xl">Travel seamlessly between Makkah, Madinah, and Jeddah with our premium taxi and rent-a-car services.</p>
      <div class="mt-6 text-sm md:text-base">
        <a href="index.php" class="text-gray-200 hover:text-white transition">Home</a>
        <span class="mx-2">></span>
        <span class="text-gray-200">Transportation</span>
      </div>
    </div>
  </section>

  <!-- Filter Section -->
  <section class="">
    <div class="container mx-auto px-4">
      <div class="filter-card animate-on-scroll">
        <div class="flex justify-between items-center mb-8">
          <h2 class="text-2xl font-bold text-gray-800"><i class="fas fa-filter mr-2 text-emerald-600"></i>Find Your Transport</h2>
          <a href="?" class="text-emerald-600 hover:text-emerald-700 text-sm font-medium transition"><i class="fas fa-times mr-1"></i>Clear Filters</a>
        </div>
        <form action="" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div>
            <label for="transportType" class="block text-sm font-medium text-gray-700 mb-3">Transport Type</label>
            <select name="transportType" class="w-full input-field" id="transportType">
              <option value="">All Types</option>
              <option value="taxi" <?php echo $filter_type === 'taxi' ? 'selected' : ''; ?>>Taxi</option>
              <option value="rentacar" <?php echo $filter_type === 'rentacar' ? 'selected' : ''; ?>>Rent A Car</option>
            </select>
          </div>
          <div>
            <label for="route" class="block text-sm font-medium text-gray-700 mb-3">Route</label>
            <select name="route" class="w-full input-field" id="route">
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
          <div class="flex items-end">
            <button type="submit" class="w-full gradient-button"><i class="fas fa-search mr-2"></i>Search</button>
          </div>
        </form>
      </div>
    </div>
  </section>

  <!-- Tabs -->
  <section class="">
    <div class="container mx-auto px-4">
      <div class="flex mb-12 animate-on-scroll">
        <button class="tab-button active" data-target="all-tab">All Transportation</button>
        <button class="tab-button" data-target="taxi-tab">Taxi Services</button>
        <button class="tab-button" data-target="rentacar-tab">Rent A Car</button>
      </div>

      <!-- All Transportation Tab -->
      <div id="all-tab" class="tab-content">
        <!-- Taxi Section -->
        <div id="taxi-section" class="mb-16">
          <h2 class="section-title animate-on-scroll"><?php echo htmlspecialchars($taxi_service_info['service_title']); ?></h2>
          <?php if (empty($taxi_routes)): ?>
            <div class="bg-white rounded-2xl shadow-lg p-8 text-center animate-on-scroll">
              <i class="fas fa-exclamation-circle text-amber-500 text-4xl mb-4"></i>
              <p class="text-gray-600">No taxi routes available at the moment.</p>
            </div>
          <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
              <?php
              foreach ($taxi_routes as $route):
                if (($filter_type && $filter_type !== 'taxi') || ($filter_route && $filter_route !== $route['route_name'])) {
                  continue;
                }
              ?>
                <div class="transport-card animate-on-scroll">
                  <div class="p-8">
                    <div class="flex justify-between items-start mb-6">
                      <div>
                        <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($route['route_name']); ?></h3>
                        <p class="text-gray-600 text-sm">Route #<?php echo $route['route_number']; ?></p>
                      </div>
                      <span class="chip"><i class="fas fa-taxi mr-2"></i>Taxi</span>
                    </div>
                    <div class="space-y-4 mb-6">
                      <div class="flex justify-between">
                        <span class="text-gray-600"><i class="fas fa-car mr-2"></i>Camry / Sonata</span>
                        <span class="font-semibold">PKR <?php echo number_format($route['camry_sonata_price'], 2); ?></span>
                      </div>
                      <div class="flex justify-between">
                        <span class="text-gray-600"><i class="fas fa-car-side mr-2"></i>Starex / Staria</span>
                        <span class="font-semibold">PKR <?php echo number_format($route['starex_staria_price'], 2); ?></span>
                      </div>
                      <div class="flex justify-between">
                        <span class="text-gray-600"><i class="fas fa-shuttle-van mr-2"></i>Hiace</span>
                        <span class="font-semibold">PKR <?php echo number_format($route['hiace_price'], 2); ?></span>
                      </div>
                    </div>
                    <a href="transportation-booking.php?type=taxi&route=<?php echo $route['id']; ?>" class="block gradient-button text-center">Book Now</a>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- Rent A Car Section -->
        <div id="rentacar-section">
          <h2 class="section-title animate-on-scroll"><?php echo htmlspecialchars($rentacar_service_info['service_title']); ?></h2>
          <?php if (empty($rentacar_routes)): ?>
            <div class="bg-white rounded-2xl shadow-lg p-8 text-center animate-on-scroll">
              <i class="fas fa-exclamation-circle text-blue-500 text-4xl mb-4"></i>
              <p class="text-gray-600">No rent-a-car routes available at the moment.</p>
            </div>
          <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
              <?php
              foreach ($rentacar_routes as $route):
                if (($filter_type && $filter_type !== 'rentacar') || ($filter_route && $filter_route !== $route['route_name'])) {
                  continue;
                }
              ?>
                <div class="transport-card animate-on-scroll">
                  <div class="p-8">
                    <div class="flex justify-between items-start mb-6">
                      <div>
                        <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($route['route_name']); ?></h3>
                        <p class="text-gray-600 text-sm">Route #<?php echo $route['route_number']; ?></p>
                      </div>
                      <span class="chip"><i class="fas fa-car mr-2"></i>Rent A Car</span>
                    </div>
                    <div class="space-y-4 mb-6">
                      <div class="flex justify-between">
                        <span class="text-gray-600"><i class="fas fa-bus-alt mr-2"></i>GMC 16-19 Seats</span>
                        <span class="font-semibold">PKR <?php echo number_format($route['gmc_16_19_price'], 2); ?></span>
                      </div>
                      <div class="flex justify-between">
                        <span class="text-gray-600"><i class="fas fa-bus mr-2"></i>GMC 22-23 Seats</span>
                        <span class="font-semibold">PKR <?php echo number_format($route['gmc_22_23_price'], 2); ?></span>
                      </div>
                      <div class="flex justify-between">
                        <span class="text-gray-600"><i class="fas fa-bus mr-2"></i>Coaster</span>
                        <span class="font-semibold">PKR <?php echo number_format($route['coaster_price'], 2); ?></span>
                      </div>
                    </div>
                    <a href="transportation-booking.php?type=rentacar&route=<?php echo $route['id']; ?>" class="block gradient-button text-center">Book Now</a>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Taxi Tab -->
      <div id="taxi-tab" class="tab-content hidden">
        <h2 class="section-title animate-on-scroll"><?php echo htmlspecialchars($taxi_service_info['service_title']); ?></h2>
        <?php if (empty($taxi_routes)): ?>
          <div class="bg-white rounded-2xl shadow-lg p-8 text-center animate-on-scroll">
            <i class="fas fa-exclamation-circle text-amber-500 text-4xl mb-4"></i>
            <p class="text-gray-600">No taxi routes available at the moment.</p>
          </div>
        <?php else: ?>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php
            foreach ($taxi_routes as $route):
              if ($filter_route && $filter_route !== $route['route_name']) {
                continue;
              }
            ?>
              <div class="transport-card animate-on-scroll">
                <div class="p-8">
                  <div class="flex justify-between items-start mb-6">
                    <div>
                      <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($route['route_name']); ?></h3>
                      <p class="text-gray-600 text-sm">Route #<?php echo $route['route_number']; ?></p>
                    </div>
                    <span class="chip"><i class="fas fa-taxi mr-2"></i>Taxi</span>
                  </div>
                  <div class="space-y-4 mb-6">
                    <div class="flex justify-between">
                      <span class="text-gray-600"><i class="fas fa-car mr-2"></i>Camry / Sonata</span>
                      <span class="font-semibold">PKR <?php echo number_format($route['camry_sonata_price'], 2); ?></span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-gray-600"><i class="fas fa-car-side mr-2"></i>Starex / Staria</span>
                      <span class="font-semibold">PKR <?php echo number_format($route['starex_staria_price'], 2); ?></span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-gray-600"><i class="fas fa-shuttle-van mr-2"></i>Hiace</span>
                      <span class="font-semibold">PKR <?php echo number_format($route['hiace_price'], 2); ?></span>
                    </div>
                  </div>
                  <a href="transportation-booking.php?type=taxi&route=<?php echo $route['id']; ?>" class="block gradient-button text-center">Book Now</a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Rent A Car Tab -->
      <div id="rentacar-tab" class="tab-content hidden">
        <h2 class="section-title animate-on-scroll"><?php echo htmlspecialchars($rentacar_service_info['service_title']); ?></h2>
        <?php if (empty($rentacar_routes)): ?>
          <div class="bg-white rounded-2xl shadow-lg p-8 text-center animate-on-scroll">
            <i class="fas fa-exclamation-circle text-blue-500 text-4xl mb-4"></i>
            <p class="text-gray-600">No rent-a-car routes available at the moment.</p>
          </div>
        <?php else: ?>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php
            foreach ($rentacar_routes as $route):
              if ($filter_route && $filter_route !== $route['route_name']) {
                continue;
              }
            ?>
              <div class="transport-card animate-on-scroll">
                <div class="p-8">
                  <div class="flex justify-between items-start mb-6">
                    <div>
                      <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($route['route_name']); ?></h3>
                      <p class="text-gray-600 text-sm">Route #<?php echo $route['route_number']; ?></p>
                    </div>
                    <span class="chip"><i class="fas fa-car mr-2"></i>Rent A Car</span>
                  </div>
                  <div class="space-y-4 mb-6">
                    <div class="flex justify-between">
                      <span class="text-gray-600"><i class="fas fa-bus-alt mr-2"></i>GMC 16-19 Seats</span>
                      <span class="font-semibold">PKR <?php echo number_format($route['gmc_16_19_price'], 2); ?></span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-gray-600"><i class="fas fa-bus mr-2"></i>GMC 22-23 Seats</span>
                      <span class="font-semibold">PKR <?php echo number_format($route['gmc_22_23_price'], 2); ?></span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-gray-600"><i class="fas fa-bus mr-2"></i>Coaster</span>
                      <span class="font-semibold">PKR <?php echo number_format($route['coaster_price'], 2); ?></span>
                    </div>
                  </div>
                  <a href="transportation-booking.php?type=rentacar&route=<?php echo $route['id']; ?>" class="block gradient-button text-center">Book Now</a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Information Section -->
      <section class="">
        <div class="container mx-auto px-4">
          <div class="bg-white rounded-2xl shadow-lg p-8 animate-on-scroll">
            <h3 class="section-title">Transportation Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
              <div>
                <h4 class="font-semibold text-lg mb-3">About Our Services</h4>
                <p class="text-gray-600 mb-4">Experience seamless travel with our premium fleet, designed for your comfort during Umrah and Hajj. Our vehicles connect Makkah, Madinah, and Jeddah with reliability and ease.</p>
                <p class="text-gray-600">All vehicles are air-conditioned, regularly sanitized, and driven by experienced professionals familiar with the routes.</p>
              </div>
              <div>
                <h4 class="font-semibold text-lg mb-3">Important Notes</h4>
                <ul class="list-disc list-inside text-gray-600 space-y-2">
                  <li>Book at least 24 hours in advance for guaranteed availability.</li>
                  <li>Prices may vary during peak seasons.</li>
                  <li>Extra luggage may incur additional charges.</li>
                  <li>Vehicles are sanitized for your safety.</li>
                  <li>Arrive at the pickup point 15 minutes early.</li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- FAQ Section -->
      <section class="">
        <div class="container mx-auto px-4">
          <h3 class="section-title animate-on-scroll">Frequently Asked Questions</h3>
          <div class="space-y-4">
            <div class="faq-card animate-on-scroll">
              <button class="flex justify-between items-center w-full text-left focus:outline-none" onclick="toggleFAQ(this)">
                <span class="font-semibold text-lg">What’s the difference between Taxi and Rent A Car services?</span>
                <svg class="w-5 h-5 text-gray-500 faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
              </button>
              <div class="mt-3 hidden faq-answer text-gray-600">
                Taxi services include a driver for specific routes, while Rent A Car offers vehicles like GMC vans and Coasters for larger groups with flexible itineraries.
              </div>
            </div>
            <div class="faq-card animate-on-scroll">
              <button class="flex justify-between items-center w-full text-left focus:outline-none" onclick="toggleFAQ(this)">
                <span class="font-semibold text-lg">How do I book a transportation service?</span>
                <svg class="w-5 h-5 text-gray-500 faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
              </button>
              <div class="mt-3 hidden faq-answer text-gray-600">
                Browse routes, select your vehicle, click "Book Now," and follow the booking process. You’ll receive a confirmation upon completion.
              </div>
            </div>
            <div class="faq-card animate-on-scroll">
              <button class="flex justify-between items-center w-full text-left focus:outline-none" onclick="toggleFAQ(this)">
                <span class="font-semibold text-lg">Can I cancel my booking?</span>
                <svg class="w-5 h-5 text-gray-500 faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
              </button>
              <div class="mt-3 hidden faq-answer text-gray-600">
                Yes, cancel up to 24 hours before departure for a full refund. Late cancellations may incur a fee.
              </div>
            </div>
            <div class="faq-card animate-on-scroll">
              <button class="flex justify-between items-center w-full text-left focus:outline-none" onclick="toggleFAQ(this)">
                <span class="font-semibold text-lg">Are vehicles air-conditioned?</span>
                <svg class="w-5 h-5 text-gray-500 faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
              </button>
              <div class="mt-3 hidden faq-answer text-gray-600">
                Yes, all vehicles are fully air-conditioned for your comfort in Saudi Arabia’s climate.
              </div>
            </div>
            <div class="faq-card animate-on-scroll">
              <button class="flex justify-between items-center w-full text-left focus:outline-none" onclick="toggleFAQ(this)">
                <span class="font-semibold text-lg">Do you offer customized routes?</span>
                <svg class="w-5 h-5 text-gray-500 faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
              </button>
              <div class="mt-3 hidden faq-answer text-gray-600">
                We offer fixed routes between major cities. For custom routes, contact our customer service team.
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Call to Action -->
      <section class="py-20 text-center bg-gradient-to-r from-emerald-50 to-teal-50">
        <div class="container mx-auto px-4">
          <h2 class="text-3xl font-bold text-gray-800 mb-6 animate-on-scroll">Plan Your Journey Today</h2>
          <p class="text-lg text-gray-600 mb-8 max-w-3xl mx-auto animate-on-scroll">Let us help you arrange seamless transportation for your Umrah or Hajj pilgrimage.</p>
          <a href="contact.php" class="gradient-button inline-block text-lg animate-on-scroll">Get in Touch</a>
        </div>
      </section>
    </div>

    <!-- Footer -->
     <?php include 'includes/footer.php' ?>

    <script>
      document.addEventListener('DOMContentLoaded', function() {
        // Tab switching
        const tabButtons = document.querySelectorAll('.tab-button');
        const tabContents = document.querySelectorAll('.tab-content');

        tabButtons.forEach(button => {
          button.addEventListener('click', function() {
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.add('hidden'));

            this.classList.add('active');
            document.getElementById(this.dataset.target).classList.remove('hidden');
          });
        });

        // URL hash for direct tab access
        const hash = window.location.hash;
        if (hash === '#taxi-section') {
          document.querySelector('[data-target="taxi-tab"]').click();
        } else if (hash === '#rentacar-section') {
          document.querySelector('[data-target="rentacar-tab"]').click();
        }

        // Apply filter based on URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        const transportType = urlParams.get('transportType');
        if (transportType === 'taxi') {
          document.querySelector('[data-target="taxi-tab"]').click();
        } else if (transportType === 'rentacar') {
          document.querySelector('[data-target="rentacar-tab"]').click();
        }

        // Scroll animations
        const elements = document.querySelectorAll('.animate-on-scroll');
        const observer = new IntersectionObserver(
          (entries) => {
            entries.forEach((entry) => {
              if (entry.isIntersecting) {
                entry.target.classList.add('visible');
              }
            });
          }, {
            threshold: 0.1
          }
        );

        elements.forEach((el) => observer.observe(el));

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
          anchor.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelector(this.getAttribute('href')).scrollIntoView({
              behavior: 'smooth'
            });
          });
        });
      });

      // FAQ toggle
      function toggleFAQ(element) {
        const answer = element.nextElementSibling;
        const icon = element.querySelector('.faq-icon');
        answer.classList.toggle('hidden');
        icon.style.transform = answer.classList.contains('hidden') ? 'rotate(0)' : 'rotate(180deg)';
      }
    </script>
</body>

</html>