<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session and include database connection
session_start();
require_once 'config/db.php';

// Initialize variables
$errors = [];
$debug = []; // For debugging issues

// Check if database connection is successful
if (!$conn) {
  $errors[] = "Database connection failed: " . mysqli_connect_error();
  $debug[] = "Ensure config/db.php is correctly configured and the database server is running.";
}

// Fetch about us content
$about_sections = [];
try {
  $about_query = "SELECT * FROM about_us WHERE status = 'active' ORDER BY display_order, created_at";
  $about_result = $conn->query($about_query);
  if ($about_result === false) {
    throw new Exception("Failed to fetch about us content: " . $conn->error);
  }
  if ($about_result->num_rows > 0) {
    while ($row = $about_result->fetch_assoc()) {
      $about_sections[$row['section_type']] = $row;
    }
  } else {
    $debug[] = "No active about us content found in the database.";
  }
} catch (Exception $e) {
  $errors[] = "Error fetching about us content: " . $e->getMessage();
  $debug[] = "Check if the 'about_us' table exists and has valid data.";
}

// Fetch company values
$values = [];
try {
  $values_query = "SELECT * FROM company_values WHERE status = 'active' ORDER BY display_order, created_at";
  $values_result = $conn->query($values_query);
  if ($values_result === false) {
    throw new Exception("Failed to fetch company values: " . $conn->error);
  }
  if ($values_result->num_rows > 0) {
    while ($row = $values_result->fetch_assoc()) {
      $values[] = $row;
    }
  } else {
    $debug[] = "No active company values found in the database.";
  }
} catch (Exception $e) {
  $errors[] = "Error fetching company values: " . $e->getMessage();
  $debug[] = "Check if the 'company_values' table exists and has valid data.";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>About Us | UmrahFlights</title>
  <link rel="stylesheet" href="src/output.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/about-us.css">
</head>

<body class="flex flex-col min-h-screen">
  <!-- Navbar -->
  <?php
  if (file_exists('includes/navbar.php')) {
    include 'includes/navbar.php';
  } else {
    $errors[] = "Navbar file not found. Please ensure 'includes/navbar.php' exists.";
    $debug[] = "Create 'includes/navbar.php' or provide a fallback navigation.";
    echo '<nav class="bg-white shadow-md p-4"><div class="container mx-auto"><a href="index.php" class="text-2xl font-bold text-emerald-600">UmrahFlights</a></div></nav>';
  }
  ?>

  <!-- Introduction Section -->
  <section class="py-20 bg-gray-50">
    <div class="container mx-auto px-4">
      <!-- Debug Messages -->
      <?php if (!empty($debug) && !empty($errors)): ?>
        <div class="debug-message mb-6">
          <p class="text-sm"><strong>Debug Info:</strong></p>
          <?php foreach ($debug as $msg): ?>
            <p class="text-sm"><?php echo htmlspecialchars($msg); ?></p>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <!-- Error Messages -->
      <?php if (!empty($errors)): ?>
        <div class="alert bg-red-50 border-l-4 border-red-500 text-red-700 mb-6 rounded-lg p-4">
          <?php foreach ($errors as $error): ?>
            <p class="text-sm"><?php echo htmlspecialchars($error); ?></p>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="flex flex-col md:flex-row gap-12 items-center">
        <div class="w-full md:w-1/2 section-card animate-on-scroll">
          <?php if (isset($about_sections['mission'])): ?>
            <h2 class="text-4xl font-extrabold text-gray-800 mb-6"><?php echo htmlspecialchars($about_sections['mission']['title']); ?></h2>
            <div class="text-gray-600 leading-relaxed mb-6">
              <?php echo nl2br(htmlspecialchars($about_sections['mission']['content'])); ?>
            </div>
          <?php else: ?>
            <h2 class="text-4xl font-extrabold text-gray-800 mb-6">Our Mission</h2>
            <p class="text-gray-600 leading-relaxed mb-6">
              To provide exceptional Umrah experiences, blending spiritual fulfillment with premium services.
            </p>
          <?php endif; ?>

          <?php if (isset($about_sections['quote']) && isset($about_sections['quote_author'])): ?>
            <div class="quote-card">
              <p class="italic text-gray-700 relative z-10">
                "<?php echo htmlspecialchars($about_sections['quote']['content']); ?>"
              </p>
              <p class="mt-2 font-medium text-gray-800 relative z-10">
                - <?php echo htmlspecialchars($about_sections['quote_author']['content']); ?>
              </p>
            </div>
          <?php else: ?>
            <div class="quote-card">
              <p class="italic text-gray-700 relative z-10">
                "Embark on a journey of faith with peace and comfort."
              </p>
              <p class="mt-2 font-medium text-gray-800 relative z-10">
                - UmrahFlights Team
              </p>
            </div>
          <?php endif; ?>
        </div>
        <div class="w-full md:w-1/2 animate-on-scroll">
          <?php if (isset($about_sections['mission']) && !empty($about_sections['mission']['image_url'])): ?>
            <img src="<?php echo htmlspecialchars($about_sections['mission']['image_url']); ?>"
              alt="About Us"
              class="w-full h-auto rounded-xl shadow-lg object-cover">
          <?php else: ?>
            <img src="https://via.placeholder.com/600x400?text=Umrah+Journey"
              alt="Umrah Pilgrimage"
              class="w-full h-auto rounded-xl shadow-lg object-cover">
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <!-- Values Section -->
  <section class="py-20 bg-white">
    <div class="container mx-auto px-4">
      <?php if (isset($about_sections['values_intro'])): ?>
        <div class="text-center mb-12 animate-on-scroll">
          <h2 class="text-4xl font-extrabold text-gray-800 mb-4"><?php echo htmlspecialchars($about_sections['values_intro']['title']); ?></h2>
          <p class="text-gray-600 max-w-3xl mx-auto text-lg">
            <?php echo htmlspecialchars($about_sections['values_intro']['subtitle']); ?>
          </p>
        </div>
      <?php else: ?>
        <div class="text-center mb-12 animate-on-scroll">
          <h2 class="text-4xl font-extrabold text-gray-800 mb-4">Our Core Values</h2>
          <p class="text-gray-600 max-w-3xl mx-auto text-lg">
            Guiding principles that shape our commitment to your spiritual journey.
          </p>
        </div>
      <?php endif; ?>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php if (!empty($values)): ?>
          <?php foreach ($values as $value): ?>
            <div class="value-card animate-on-scroll">
              <div class="w-12 h-12 bg-emerald-100 rounded-lg flex items-center justify-center mb-4">
                <?php if ($value['icon_class']): ?>
                  <i class="<?php echo htmlspecialchars($value['icon_class']); ?> text-2xl text-emerald-600"></i>
                <?php else: ?>
                  <i class="fas fa-check-circle text-2xl text-emerald-600"></i>
                <?php endif; ?>
              </div>
              <h3 class="text-xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($value['title']); ?></h3>
              <p class="text-gray-600">
                <?php echo htmlspecialchars($value['description']); ?>
              </p>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="value-card animate-on-scroll">
            <div class="w-12 h-12 bg-emerald-100 rounded-lg flex items-center justify-center mb-4">
              <i class="fas fa-heart text-2xl text-emerald-600"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">Faith</h3>
            <p class="text-gray-600">
              Centering every journey around spiritual devotion and trust.
            </p>
          </div>
          <div class="value-card animate-on-scroll">
            <div class="w-12 h-12 bg-emerald-100 rounded-lg flex items-center justify-center mb-4">
              <i class="fas fa-star text-2xl text-emerald-600"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">Excellence</h3>
            <p class="text-gray-600">
              Delivering premium services with unmatched quality.
            </p>
          </div>
          <div class="value-card animate-on-scroll">
            <div class="w-12 h-12 bg-emerald-100 rounded-lg flex items-center justify-center mb-4">
              <i class="fas fa-hands-helping text-2xl text-emerald-600"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">Community</h3>
            <p class="text-gray-600">
              Fostering unity and support for all pilgrims.
            </p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <?php include 'includes/footer.php' ?>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
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
    });
  </script>
</body>

</html>