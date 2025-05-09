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

    .section-card {
      background: rgba(255, 255, 255, 0.95);
      border-radius: 24px;
      padding: 32px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      backdrop-filter: blur(10px);
      transition: transform 0.3s ease;
    }

    .section-card:hover {
      transform: translateY(-8px);
    }

    .value-card {
      background: white;
      border-radius: 16px;
      padding: 24px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .value-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }

    .quote-card {
      background: linear-gradient(135deg, #ecfdf5, #d1fae5);
      border-radius: 16px;
      padding: 24px;
      position: relative;
      overflow: hidden;
    }

    .quote-card::before {
      content: '"';
      position: absolute;
      top: 20px;
      left: 20px;
      font-size: 5rem;
      color: rgba(16, 185, 129, 0.1);
      font-family: Georgia, serif;
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

    .animate-on-scroll {
      opacity: 0;
      transform: translateY(20px);
      transition: opacity 0.6s ease, transform 0.6s ease;
    }

    .animate-on-scroll.visible {
      opacity: 1;
      transform: translateY(0);
    }

    .debug-message {
      background: #fff3cd;
      border: 1px solid #ffeeba;
      color: #856404;
      padding: 16px;
      border-radius: 12px;
      margin-bottom: 16px;
      font-size: 0.85rem;
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
      .section-card {
        padding: 24px;
      }
    }
  </style>
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
  <footer class="footer-bg py-20 text-gray-200">
    <div class="container mx-auto px-4">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-12">
        <div class="animate-on-scroll">
          <h3 class="text-2xl font-bold mb-6 text-white">About Us</h3>
          <p class="text-gray-300 text-sm leading-relaxed">
            We specialize in creating transformative Umrah experiences, blending premium services with spiritual fulfillment.
          </p>
          <div class="flex space-x-6 mt-6">
            <a href="#" class="social-icon"><i class="fab fa-facebook"></i></a>
            <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
            <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
          </div>
        </div>
        <div class="animate-on-scroll">
          <h3 class="text-2xl font-bold mb-6 text-white">Quick Links</h3>
          <ul class="space-y-4">
            <li><a href="index.php" class="text-gray-300 hover:text-white transition">Home</a></li>
            <li><a href="about-us.php" class="text-gray-300 hover:text-white transition">About Us</a></li>
            <li><a href="packages.php" class="text-gray-300 hover:text-white transition">Our Packages</a></li>
            <li><a href="faqs.php" class="text-gray-300 hover:text-white transition">FAQs</a></li>
            <li><a href="contact-us.php" class="text-gray-300 hover:text-white transition">Contact Us</a></li>
          </ul>
        </div>
        <div class="animate-on-scroll">
          <h3 class="text-2xl font-bold mb-6 text-white">Our Services</h3>
          <ul class="space-y-4">
            <li><a href="packages.php" class="text-gray-300 hover:text-white transition">Umrah Packages</a></li>
            <li><a href="flights.php" class="text-gray-300 hover:text-white transition">Flight Booking</a></li>
            <li><a href="hotels.php" class="text-gray-300 hover:text-white transition">Hotel Reservation</a></li>
            <li><a href="visa.php" class="text-gray-300 hover:text-white transition">Visa Processing</a></li>
            <li><a href="transportation.php" class="text-gray-300 hover:text-white transition">Transportation</a></li>
          </ul>
        </div>
        <div class="animate-on-scroll">
          <h3 class="text-2xl font-bold mb-6 text-white">Contact Us</h3>
          <ul class="space-y-4 text-gray-300">
            <li class="flex items-start">
              <i class="fas fa-map-marker-alt mt-1 mr-3 text-emerald-400"></i>
              <span>123 Main Street, City, Country</span>
            </li>
            <li class="flex items-center">
              <i class="fas fa-phone mr-3 text-emerald-400"></i>
              <span>+44 775 983691</span>
            </li>
            <li class="flex items-center">
              <i class="fas fa-envelope mr-3 text-emerald-400"></i>
              <span>info@umrahpartner.com</span>
            </li>
          </ul>
        </div>
      </div>
      <div class="border-t border-gray-700 mt-12 pt-8 flex flex-col md:flex-row justify-between items-center animate-on-scroll">
        <p class="text-gray-400 text-sm">© 2025 Umrah Partners. All rights reserved.</p>
        <div class="flex space-x-8 mt-4 md:mt-0">
          <a href="privacy.php" class="text-gray-400 hover:text-white text-sm transition">Privacy Policy</a>
          <a href="terms.php" class="text-gray-400 hover:text-white text-sm transition">Terms of Service</a>
          <a href="cookies.php" class="text-gray-400 hover:text-white text-sm transition">Cookie Policy</a>
        </div>
      </div>
    </div>
  </footer>

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