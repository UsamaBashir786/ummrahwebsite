<?php
require_once 'config/db.php';
session_start();

// Initialize filter variables
$filter_category = isset($_GET['category']) ? $_GET['category'] : '';
$filter_days = isset($_GET['days']) ? (int)$_GET['days'] : 0;
$filter_price_min = isset($_GET['price_min']) ? (int)$_GET['price_min'] : 0;
$filter_price_max = isset($_GET['price_max']) ? (int)$_GET['price_max'] : 0;

// Start building the query
$query = "SELECT * FROM umrah_packages WHERE 1=1";
$params = [];
$types = '';

// Apply filters
if (!empty($filter_category)) {
  $query .= " AND star_rating = ?";
  $params[] = $filter_category;
  $types .= 's';
}

if ($filter_days > 0) {
  $query .= " AND total_days = ?";
  $params[] = $filter_days;
  $types .= 'i';
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

// Helper function to get star display
function getStarRating($rating, $display_type = 'icons')
{
  if ($display_type === 'icons') {
    switch ($rating) {
      case 'low_budget':
        return '<i class="fas fa-star text-yellow-400"></i>';
      case '3_star':
        return '<i class="fas fa-star text-yellow-400"></i><i class="fas fa-star text-yellow-400"></i><i class="fas fa-star text-yellow-400"></i>';
      case '4_star':
        return '<i class="fas fa-star text-yellow-400"></i><i class="fas fa-star text-yellow-400"></i><i class="fas fa-star text-yellow-400"></i><i class="fas fa-star text-yellow-400"></i>';
      case '5_star':
        return '<i class="fas fa-star text-yellow-400"></i><i class="fas fa-star text-yellow-400"></i><i class="fas fa-star text-yellow-400"></i><i class="fas fa-star text-yellow-400"></i><i class="fas fa-star text-yellow-400"></i>';
      default:
        return '';
    }
  } else {
    switch ($rating) {
      case 'low_budget':
        return 'Low Budget Economy';
      case '3_star':
        return '3 Star';
      case '4_star':
        return '4 Star';
      case '5_star':
        return '5 Star';
      default:
        return '';
    }
  }
}

// Function to get title based on star rating
function getPackageTypeTitle($star_rating)
{
  switch ($star_rating) {
    case 'low_budget':
      return 'Umrah Packages 2025 from Pakistan | Low Budget Economy Umrah Packages';
    case '3_star':
      return 'All Inclusive Umrah Packages 2025 | 3 Star Umrah Packages';
    case '4_star':
      return 'All Inclusive Umrah Packages 2025 | 4 Star Umrah Packages';
    case '5_star':
      return 'All Inclusive Umrah Packages 2025 | 5 Star Umrah Packages';
    default:
      return 'All Umrah Packages 2025';
  }
}

// Group packages by star_rating
$grouped_packages = [];
foreach ($packages as $package) {
  if (!isset($grouped_packages[$package['star_rating']])) {
    $grouped_packages[$package['star_rating']] = [];
  }
  $grouped_packages[$package['star_rating']][] = $package;
}

// Sort by priority (low_budget, 3_star, 4_star, 5_star)
$priority_order = ['low_budget', '3_star', '4_star', '5_star'];
if (empty($filter_category)) {
  uksort($grouped_packages, function ($a, $b) use ($priority_order) {
    return array_search($a, $priority_order) - array_search($b, $priority_order);
  });
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Umrah Packages 2025 from Pakistan - UmrahFlights</title>
  <!-- Include Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Include Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body {
      font-family: 'Arial', sans-serif;
    }

    .package-card {
      transition: all 0.3s ease;
    }

    .package-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }

    .blue-button {
      background-color: #0277bd;
    }

    .blue-button:hover {
      background-color: #0288d1;
    }

    .teal-bg {
      background-color: #009688;
    }

    .package-title {
      background: rgba(0, 0, 0, 0.7);
      position: absolute;
      bottom: 0;
      left: 0;
      width: 100%;
      padding: 10px;
      color: white;
    }

    .star-rating {
      display: flex;
      margin-top: 5px;
    }

    .star-rating .fas {
      margin-right: 2px;
    }

    .hotel-name {
      min-height: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .section-title {
      border-bottom: 2px solid #0277bd;
      display: inline-block;
      padding-bottom: 5px;
    }
  </style>
</head>

<body class="bg-gray-50">
  <!-- Navbar -->
  <header class="bg-white shadow-sm">
    <div class="container mx-auto px-4 py-3">
      <div class="flex justify-between items-center">
        <div>
          <a href="index.php" class="text-xl font-bold text-gray-800">UMRAH</a>
        </div>
        <nav>
          <ul class="flex space-x-6">
            <li><a href="index.php" class="text-gray-600 hover:text-teal-600">Home</a></li>
            <li><a href="about.php" class="text-gray-600 hover:text-teal-600">About Us</a></li>
            <li><a href="contact.php" class="text-gray-600 hover:text-teal-600">Contact Us</a></li>
            <li><a href="packages.php" class="text-gray-600 hover:text-teal-600">Packages</a></li>
            <li class="relative">
              <a href="#" class="text-gray-600 hover:text-teal-600">More <i class="fas fa-chevron-down text-xs ml-1"></i></a>
            </li>
            <li><a href="login.php" class="text-gray-600 hover:text-teal-600">Login</a></li>
            <li><a href="register.php" class="text-teal-600 hover:text-teal-700">Register</a></li>
          </ul>
        </nav>
      </div>
    </div>
  </header>

  <!-- Page Header -->
  <section class="teal-bg text-white py-6">
    <div class="container mx-auto px-4">
      <h1 class="text-2xl font-bold">Umrah Packages 2025 from Pakistan</h1>
      <div class="mt-2">
        <a href="index.php" class="text-white hover:text-gray-200">Home</a>
        <span class="mx-2">&gt;</span>
        <span class="text-gray-200">Umrah Packages</span>
      </div>
    </div>
  </section>

  <!-- Filter Section -->
  <section class="py-8">
    <div class="container mx-auto px-4">
      <div class="bg-white rounded-lg shadow p-6 max-w-4xl mx-auto">
        <h2 class="text-lg font-medium text-gray-800 mb-4">Filter Packages</h2>
        <form action="" method="GET">
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
              <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Package Category</label>
              <select name="category" id="category" class="w-full border border-gray-300 rounded p-2 focus:outline-none focus:ring-2 focus:ring-teal-500">
                <option value="">All Categories</option>
                <option value="low_budget" <?php echo $filter_category === 'low_budget' ? 'selected' : ''; ?>>Low Budget Economy</option>
                <option value="3_star" <?php echo $filter_category === '3_star' ? 'selected' : ''; ?>>3 Star</option>
                <option value="4_star" <?php echo $filter_category === '4_star' ? 'selected' : ''; ?>>4 Star</option>
                <option value="5_star" <?php echo $filter_category === '5_star' ? 'selected' : ''; ?>>5 Star</option>
              </select>
            </div>
            <div>
              <label for="days" class="block text-sm font-medium text-gray-700 mb-1">Total Days</label>
              <select name="days" id="days" class="w-full border border-gray-300 rounded p-2 focus:outline-none focus:ring-2 focus:ring-teal-500">
                <option value="">Any Duration</option>
                <option value="7" <?php echo $filter_days === 7 ? 'selected' : ''; ?>>7 Days</option>
                <option value="10" <?php echo $filter_days === 10 ? 'selected' : ''; ?>>10 Days</option>
                <option value="12" <?php echo $filter_days === 12 ? 'selected' : ''; ?>>12 Days</option>
                <option value="14" <?php echo $filter_days === 14 ? 'selected' : ''; ?>>14 Days</option>
                <option value="21" <?php echo $filter_days === 21 ? 'selected' : ''; ?>>21 Days</option>
              </select>
            </div>
            <div>
              <label for="price_min" class="block text-sm font-medium text-gray-700 mb-1">Min Price (PKR)</label>
              <input type="number" name="price_min" id="price_min" class="w-full border border-gray-300 rounded p-2 focus:outline-none focus:ring-2 focus:ring-teal-500" placeholder="0" value="<?php echo $filter_price_min; ?>">
            </div>
            <div>
              <label for="price_max" class="block text-sm font-medium text-gray-700 mb-1">Max Price (PKR)</label>
              <input type="number" name="price_max" id="price_max" class="w-full border border-gray-300 rounded p-2 focus:outline-none focus:ring-2 focus:ring-teal-500" placeholder="0" value="<?php echo $filter_price_max; ?>">
            </div>
          </div>
          <div class="mt-4 text-right">
            <button type="submit" class="blue-button text-white py-2 px-6 rounded">Filter</button>
          </div>
        </form>
      </div>
    </div>
  </section>

  <?php if (empty($packages)): ?>
    <!-- No Packages Found -->
    <section class="py-8">
      <div class="container mx-auto px-4">
        <div class="bg-blue-50 border border-blue-200 text-blue-700 p-4 rounded">
          <h3 class="font-medium mb-2">No packages found</h3>
          <p>No packages match your selected criteria. Please try different filters or check back later.</p>
        </div>
      </div>
    </section>
  <?php else: ?>
    <?php
    // If specific category is selected, show only that category
    if (!empty($filter_category)) {
      if (isset($grouped_packages[$filter_category])) {
        $filtered_packages = [$filter_category => $grouped_packages[$filter_category]];
        $grouped_packages = $filtered_packages;
      }
    }
    ?>

    <!-- Package Categories -->
    <?php foreach ($grouped_packages as $category => $category_packages): ?>
      <section class="py-8">
        <div class="container mx-auto px-4">
          <h2 class="text-xl font-bold text-gray-800 mb-6">
            <span class="section-title"><?php echo getPackageTypeTitle($category); ?></span>
          </h2>

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($category_packages as $package): ?>
              <div class="package-card bg-white rounded-lg shadow overflow-hidden">
                <div class="relative">
                  <img src="<?php echo htmlspecialchars($package['package_image']); ?>" alt="<?php echo htmlspecialchars($package['title']); ?>" class="w-full h-48 object-cover">
                  <div class="package-title text-center">
                    <?php echo $package['total_days']; ?> Nights <?php echo getStarRating($package['star_rating'], 'text'); ?> Umrah Package 2025
                  </div>
                </div>

                <div class="p-0">
                  <div class="grid grid-cols-2">
                    <div class="p-4 text-center border-r border-gray-200">
                      <img src="assets/images/makkah-icon.jpg" alt="Makkah" class="w-8 h-8 mx-auto mb-2">
                      <div class="font-medium">Makkah <?php echo $package['makkah_nights']; ?> Nights</div>
                      <div class="hotel-name text-sm text-gray-600 mt-1">Al Aseel Ajyad</div>
                      <div class="text-xs text-gray-500 mt-1">(similar)</div>
                      <div class="star-rating flex justify-center mt-1">
                        <?php echo getStarRating($category); ?>
                      </div>
                    </div>
                    <div class="p-4 text-center">
                      <img src="assets/images/madinah-icon.jpg" alt="Madinah" class="w-8 h-8 mx-auto mb-2">
                      <div class="font-medium">Madinah <?php echo $package['madinah_nights']; ?> Nights</div>
                      <div class="hotel-name text-sm text-gray-600 mt-1">Al Shourfah Hotel</div>
                      <div class="text-xs text-gray-500 mt-1">(similar)</div>
                      <div class="star-rating flex justify-center mt-1">
                        <?php echo getStarRating($category); ?>
                      </div>
                    </div>
                  </div>

                  <div class="mt-0">
                    <a href="package-details.php?id=<?php echo $package['id']; ?>" class="block w-full blue-button text-white text-center py-3 font-medium">VIEW DETAILS</a>
                  </div>

                  <div class="bg-gray-100 p-2 text-center font-bold text-blue-600">
                    PKR <?php echo number_format($package['price'], 0); ?> /PP
                    <span class="text-xs text-gray-500 ml-1">| <?php echo $package['total_days']; ?> Nights</span>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </section>
    <?php endforeach; ?>
  <?php endif; ?>

  <!-- Call to Action -->
  <section class="py-12 text-center">
    <div class="container mx-auto px-4">
      <h2 class="text-2xl font-bold text-gray-800 mb-4">Can't Find What You're Looking For?</h2>
      <p class="text-gray-600 mb-6">Contact our Umrah specialists to customize a package that fits your needs and budget.</p>
      <a href="contact.php" class="inline-block blue-button text-white font-medium py-2 px-6 rounded-md transition duration-300">
        Contact Us
      </a>
    </div>
  </section>

  <!-- Footer -->
  <footer class="bg-gray-900 text-white py-12">
    <div class="container mx-auto px-4">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
        <div>
          <h3 class="text-lg font-semibold mb-4">About Us</h3>
          <p class="text-gray-400 text-sm leading-relaxed">
            We specialize in providing comprehensive Umrah packages with premium services, ensuring a comfortable and spiritual journey for all our clients.
          </p>
          <div class="flex space-x-4 mt-4">
            <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-facebook"></i></a>
            <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-twitter"></i></a>
            <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-instagram"></i></a>
          </div>
        </div>

        <div>
          <h3 class="text-lg font-semibold mb-4">Quick Links</h3>
          <ul class="space-y-2">
            <li><a href="index.php" class="text-gray-400 hover:text-white">Home</a></li>
            <li><a href="about.php" class="text-gray-400 hover:text-white">About Us</a></li>
            <li><a href="packages.php" class="text-gray-400 hover:text-white">Our Packages</a></li>
            <li><a href="faqs.php" class="text-gray-400 hover:text-white">FAQs</a></li>
            <li><a href="contact.php" class="text-gray-400 hover:text-white">Contact Us</a></li>
          </ul>
        </div>

        <div>
          <h3 class="text-lg font-semibold mb-4">Our Services</h3>
          <ul class="space-y-2">
            <li><a href="packages.php" class="text-gray-400 hover:text-white">Umrah Packages</a></li>
            <li><a href="flight-booking.php" class="text-gray-400 hover:text-white">Flight Booking</a></li>
            <li><a href="hotel.php" class="text-gray-400 hover:text-white">Hotel Reservation</a></li>
            <li><a href="visa.php" class="text-gray-400 hover:text-white">Visa Processing</a></li>
            <li><a href="transport.php" class="text-gray-400 hover:text-white">Transportation</a></li>
          </ul>
        </div>

        <div>
          <h3 class="text-lg font-semibold mb-4">Contact Us</h3>
          <ul class="space-y-2 text-gray-400">
            <li class="flex items-start">
              <i class="fas fa-map-marker-alt mt-1 mr-3 text-teal-500"></i>
              <span>123 Main Street, City, Country</span>
            </li>
            <li class="flex items-center">
              <i class="fas fa-phone mr-3 text-teal-500"></i>
              <span>+44 775 983691</span>
            </li>
            <li class="flex items-center">
              <i class="fas fa-envelope mr-3 text-teal-500"></i>
              <span>info@umrahpartner.com</span>
            </li>
          </ul>
        </div>
      </div>

      <div class="border-t border-gray-800 mt-10 pt-6 flex flex-col md:flex-row justify-between items-center">
        <p class="text-gray-400 text-sm">© 2025 Umrah Partners. All rights reserved.</p>
        <div class="flex space-x-6 mt-4 md:mt-0">
          <a href="privacy.php" class="text-gray-400 hover:text-white text-sm">Privacy Policy</a>
          <a href="terms.php" class="text-gray-400 hover:text-white text-sm">Terms of Service</a>
          <a href="cookies.php" class="text-gray-400 hover:text-white text-sm">Cookie Policy</a>
        </div>
      </div>
    </div>
  </footer>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Add any JavaScript functionality here
    });
  </script>
</body>

</html>