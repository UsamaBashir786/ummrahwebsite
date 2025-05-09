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
        return '<i class="fas fa-star text-amber-400"></i>';
      case '3_star':
        return '<i class="fas fa-star text-amber-400"></i><i class="fas fa-star text-amber-400"></i><i class="fas fa-star text-amber-400"></i>';
      case '4_star':
        return '<i class="fas fa-star text-amber-400"></i><i class="fas fa-star text-amber-400"></i><i class="fas fa-star text-amber-400"></i><i class="fas fa-star text-amber-400"></i>';
      case '5_star':
        return '<i class="fas fa-star text-amber-400"></i><i class="fas fa-star text-amber-400"></i><i class="fas fa-star text-amber-400"></i><i class="fas fa-star text-amber-400"></i><i class="fas fa-star text-amber-400"></i>';
      default:
        return '';
    }
  } else {
    switch ($rating) {
      case 'low_budget':
        return 'Economy';
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
      return 'Economy Umrah Packages 2025';
    case '3_star':
      return '3 Star Umrah Packages 2025';
    case '4_star':
      return '4 Star Umrah Packages 2025';
    case '5_star':
      return '5 Star Umrah Packages 2025';
    default:
      return 'Umrah Packages 2025';
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
  <title>Umrah Packages 2025 - UmrahFlights</title>
  <!-- <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet"> -->
   <link rel="stylesheet" href="src/output.css">
   <link rel="stylesheet" href="assets/css/packages.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>

  </style>
</head>

<body>
  <!-- Navbar -->
  <?php include 'includes/navbar.php' ?>

  <!-- Page Header -->
  <section class="header-bg text-white py-20 relative">
    <div class="container mx-auto px-4 relative z-10">
      <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight mb-4">Umrah Packages 2025</h1>
      <p class="text-lg md:text-xl text-gray-100 max-w-2xl">Embark on a spiritual journey with our curated Umrah packages from Pakistan, designed for comfort and devotion.</p>
      <div class="mt-6 text-sm md:text-base">
        <a href="index.php" class="text-gray-200 hover:text-white transition">Home</a>
        <span class="mx-2">></span>
        <span class="text-gray-200">Umrah Packages</span>
      </div>
    </div>
  </section>

  <!-- Filter Section -->
  <section class="py-16">
    <div class="container mx-auto px-4">
      <div class="filter-card animate-on-scroll">
        <h2 class="text-2xl font-bold text-gray-800 mb-8">Discover Your Ideal Package</h2>
        <form action="" method="GET">
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
            <div>
              <label for="category" class="block text-sm font-medium text-gray-700 mb-3">Package Category</label>
              <select name="category" id="category" class="w-full input-field">
                <option value="">All Categories</option>
                <option value="low_budget" <?php echo $filter_category === 'low_budget' ? 'selected' : ''; ?>>Economy</option>
                <option value="3_star" <?php echo $filter_category === '3_star' ? 'selected' : ''; ?>>3 Star</option>
                <option value="4_star" <?php echo $filter_category === '4_star' ? 'selected' : ''; ?>>4 Star</option>
                <option value="5_star" <?php echo $filter_category === '5_star' ? 'selected' : ''; ?>>5 Star</option>
              </select>
            </div>
            <div>
              <label for="days" class="block text-sm font-medium text-gray-700 mb-3">Total Days</label>
              <select name="days" id="days" class="w-full input-field">
                <option value="">Any Duration</option>
                <option value="7" <?php echo $filter_days === 7 ? 'selected' : ''; ?>>7 Days</option>
                <option value="10" <?php echo $filter_days === 10 ? 'selected' : ''; ?>>10 Days</option>
                <option value="12" <?php echo $filter_days === 12 ? 'selected' : ''; ?>>12 Days</option>
                <option value="14" <?php echo $filter_days === 14 ? 'selected' : ''; ?>>14 Days</option>
                <option value="21" <?php echo $filter_days === 21 ? 'selected' : ''; ?>>21 Days</option>
              </select>
            </div>
            <div>
              <label for="price_min" class="block text-sm font-medium text-gray-700 mb-3">Min Price (PKR)</label>
              <input type="number" name="price_min" id="price_min" class="w-full input-field" placeholder="0" value="<?php echo $filter_price_min; ?>">
            </div>
            <div>
              <label for="price_max" class="block text-sm font-medium text-gray-700 mb-3">Max Price (PKR)</label>
              <input type="number" name="price_max" id="price_max" class="w-full input-field" placeholder="0" value="<?php echo $filter_price_max; ?>">
            </div>
          </div>
          <div class="mt-8 text-right">
            <button type="submit" class="gradient-button">Search Packages</button>
          </div>
        </form>
      </div>
    </div>
  </section>

  <?php if (empty($packages)): ?>
    <!-- No Packages Found -->
    <section class="py-16">
      <div class="container mx-auto px-4">
        <div class="bg-white border border-gray-100 text-gray-700 p-10 rounded-3xl text-center shadow-lg animate-on-scroll">
          <h3 class="font-bold text-2xl mb-4">No Packages Found</h3>
          <p class="text-lg">We couldn't find any packages matching your criteria. Try different filters or check back later.</p>
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
      <section class="py-16">
        <div class="container mx-auto px-4">
          <h2 class="section-title animate-on-scroll"><?php echo getPackageTypeTitle($category); ?></h2>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($category_packages as $package): ?>
              <div class="package-card animate-on-scroll">
                <div class="package-image">
                  <img src="<?php echo htmlspecialchars($package['package_image']); ?>" alt="<?php echo htmlspecialchars($package['title']); ?>" loading="lazy">
                  <div class="package-overlay">
                    <?php echo $package['total_days']; ?> Nights <?php echo getStarRating($package['star_rating'], 'text'); ?>
                  </div>
                </div>
                <div class="p-8">
                  <div class="flex flex-wrap justify-center mb-6">
                    <span class="chip">
                      <i class="fas fa-mosque mr-2"></i> Makkah <?php echo $package['makkah_nights']; ?> Nights
                    </span>
                    <span class="chip">
                      <i class="fas fa-kaaba mr-2"></i> Madinah <?php echo $package['madinah_nights']; ?> Nights
                    </span>
                  </div>
                  <div class="flex justify-center mb-6"><?php echo getStarRating($category); ?></div>
                  <div class="text-center text-3xl font-bold text-gray-800 mb-6">
                    PKR <?php echo number_format($package['price'], 0); ?> /PP
                  </div>
                  <a href="package-details.php?id=<?php echo $package['id']; ?>" class="block gradient-button text-center">Explore Package</a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </section>
    <?php endforeach; ?>
  <?php endif; ?>

  <!-- Call to Action -->
  <section class="py-20 text-center bg-gradient-to-r from-emerald-50 to-teal-50">
    <div class="container mx-auto px-4">
      <h2 class="text-3xl font-bold text-gray-800 mb-6 animate-on-scroll">Craft Your Perfect Umrah</h2>
      <p class="text-lg text-gray-600 mb-8 max-w-3xl mx-auto animate-on-scroll">Let our experts tailor a bespoke Umrah package that aligns with your spiritual and budgetary needs.</p>
      <a href="contact.php" class="gradient-button inline-block text-lg animate-on-scroll">Get in Touch</a>
    </div>
  </section>

  <!-- Footer -->
  <?php include 'includes/footer.php' ?>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Smooth scroll for anchor links
      document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
          e.preventDefault();
          document.querySelector(this.getAttribute('href')).scrollIntoView({
            behavior: 'smooth'
          });
        });
      });

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

      // Lazy load images
      const images = document.querySelectorAll('img[loading="lazy"]');
      images.forEach((img) => {
        img.addEventListener('load', () => {
          img.classList.add('opacity-100');
          img.classList.remove('opacity-0');
        });
      });
    });
  </script>
</body>

</html>