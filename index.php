<?php
require_once 'config/db.php';
session_start();

// Fetch packages from database
$packages_query = "SELECT * FROM umrah_packages ORDER BY created_at DESC LIMIT 3";
$packages_result = $conn->query($packages_query);
$packages = [];

if ($packages_result && $packages_result->num_rows > 0) {
  while ($row = $packages_result->fetch_assoc()) {
    $packages[] = $row;
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php' ?>
  <link rel="stylesheet" href="assets/css/style.css">
  <!-- <script src="https://cdn.tailwindcss.com"></script> -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- AOS Animation Library -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" />
  <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
</head>

<body>
  <!-- Navbar -->
  <?php include 'includes/navbar.php'; ?>


  <!-- Hero Section -->
  <section class="hero-section relative bg-cover bg-center min-h-screen flex items-center justify-center" style="background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('/assets/img/hero.jpg');">
    <div class="container mx-auto px-4 text-center">
      <div data-aos="fade-up" data-aos-duration="1000" class="max-w-4xl mx-auto text-white">
        <h1 class="text-5xl md:text-6xl font-bold mb-6 leading-tight">Journey to Umrah</h1>
        <p class="text-xl mb-8 opacity-90 max-w-2xl mx-auto">Embark on a transformative spiritual experience with our comprehensive and carefully curated Umrah packages.</p>
        <div class="flex justify-center">
          <a href="packages.php" class="btn-primary bg-teal-600 hover:bg-teal-700 text-white font-medium py-3 px-8 rounded-lg text-lg transition duration-300">
            Explore Packages
          </a>
        </div>
      </div>
      <div class="scroll-down absolute bottom-8 left-1/2 transform -translate-x-1/2 text-white animate-bounce">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
        </svg>
      </div>
    </div>
  </section>

  <!-- Featured Packages Section -->
  <section class="py-16 bg-gray-50">
    <div class="container mx-auto px-4">
      <div class="text-center mb-12" data-aos="fade-up">
        <span class="inline-block px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-semibold mb-2">FEATURED PACKAGES</span>
        <h2 class="text-4xl font-bold text-gray-800 mb-4">Find Your Perfect Umrah Package</h2>
        <p class="text-gray-600 max-w-2xl mx-auto">Choose from our selection of comprehensive Umrah packages designed to provide a seamless and spiritually enriching experience.</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php if (empty($packages)): ?>
          <div class="col-span-full text-center">
            <p class="text-gray-600">No packages available at the moment. Please check back later.</p>
          </div>
        <?php else: ?>
          <?php foreach ($packages as $package): ?>
            <div class="package-card" data-aos="fade-up" data-aos-delay="<?php echo $loop * 100; ?>">
              <div class="relative">
                <img
                  src="<?php echo htmlspecialchars($package['package_image']); ?>"
                  alt="<?php echo htmlspecialchars($package['title']); ?>"
                  class="w-full h-64 object-cover">
                <div class="absolute top-0 right-0 bg-green-600 text-white py-2 px-4 rounded-bl-lg text-sm font-semibold">
                  Limited Offer
                </div>
                <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black to-transparent p-4">
                  <div class="inline-block bg-white/20 backdrop-blur-sm text-white text-xs px-2 py-1 rounded-lg mb-2">
                    <?php echo ucfirst(htmlspecialchars($package['package_type'])); ?> Package
                  </div>
                  <h3 class="text-white text-xl font-bold"><?php echo htmlspecialchars($package['title']); ?></h3>
                </div>
              </div>

              <div class="p-5 flex flex-col flex-grow">
                <div class="flex justify-between items-center mb-4">
                  <div class="text-2xl font-bold text-green-600">
                    Rs<?php echo number_format($package['price'], 2); ?>
                  </div>
                  <div class="text-sm text-gray-500">
                    <?php echo ucfirst(htmlspecialchars($package['flight_class'])); ?> Flight
                  </div>
                </div>

                <div class="mb-4 flex-grow">
                  <ul class="space-y-2">
                    <?php
                    $inclusions = json_decode($package['inclusions'], true);
                    if (is_array($inclusions) && !empty($inclusions)):
                      // Display only first 3 inclusions
                      $count = 0;
                      foreach ($inclusions as $inclusion):
                        if ($count >= 3) break;
                        $icon_class = '';
                        switch ($inclusion) {
                          case 'flight':
                            $icon_class = 'fa-plane';
                            break;
                          case 'hotel':
                            $icon_class = 'fa-hotel';
                            break;
                          case 'transport':
                            $icon_class = 'fa-car';
                            break;
                          case 'guide':
                            $icon_class = 'fa-user';
                            break;
                          case 'vip_services':
                            $icon_class = 'fa-star';
                            break;
                          default:
                            $icon_class = 'fa-check';
                        }
                        $count++;
                    ?>
                        <li class="flex items-center text-gray-700">
                          <i class="fas <?php echo $icon_class; ?> mr-2 text-green-500 w-5"></i>
                          <span><?php echo ucfirst(str_replace('_', ' ', $inclusion)); ?></span>
                        </li>
                      <?php
                      endforeach;
                      if (count($inclusions) > 3):
                      ?>
                        <li class="text-sm text-gray-500 italic">
                          + <?php echo count($inclusions) - 3; ?> more inclusions
                        </li>
                    <?php
                      endif;
                    endif;
                    ?>
                  </ul>
                </div>

                <a href="package-details.php?id=<?php echo $package['id']; ?>"
                  class="block text-center bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-4 rounded-lg transition duration-300 mt-auto">
                  View Details
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="text-center mt-10">
        <a href="packages.php" class="inline-flex items-center bg-white hover:bg-gray-50 text-green-600 border border-green-600 font-medium py-3 px-6 rounded-lg transition duration-300">
          View All Packages
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
          </svg>
        </a>
      </div>
    </div>
  </section>

  <!-- Services Section -->
  <section id="services" class="py-16 bg-white">
    <div class="container mx-auto px-4">
      <div class="text-center mb-12" data-aos="fade-up">
        <span class="inline-block px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-semibold mb-2">OUR SERVICES</span>
        <h2 class="text-4xl font-bold text-gray-800 mb-4">Comprehensive Umrah Services</h2>
        <p class="text-gray-600 max-w-2xl mx-auto">We offer a complete range of services to make your sacred journey comfortable and spiritually fulfilling.</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
        <!-- Flights -->
        <div class="service-block bg-white rounded-xl shadow-md p-6 text-center" data-aos="fade-up" data-aos-delay="100">
          <div class="service-icon w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
            </svg>
          </div>
          <h3 class="text-xl font-bold text-gray-800 mb-2">Flight Bookings</h3>
          <p class="text-gray-600 mb-4">We offer direct and connecting flights from multiple airports with comfortable seating and excellent in-flight services.</p>
          <a href="flights.php" class="text-green-600 hover:text-green-700 font-medium inline-flex items-center">
            Learn More <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </a>
        </div>

        <!-- Hotels -->
        <div class="service-block bg-white rounded-xl shadow-md p-6 text-center" data-aos="fade-up" data-aos-delay="200">
          <div class="service-icon w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
          </div>
          <h3 class="text-xl font-bold text-gray-800 mb-2">Premium Hotels</h3>
          <p class="text-gray-600 mb-4">Stay in carefully selected hotels near the holy sites for maximum convenience during your spiritual journey.</p>
          <a href="hotels.php" class="text-green-600 hover:text-green-700 font-medium inline-flex items-center">
            Learn More <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </a>
        </div>

        <!-- Transportation -->
        <div class="service-block bg-white rounded-xl shadow-md p-6 text-center" data-aos="fade-up" data-aos-delay="300">
          <div class="service-icon w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
            </svg>
          </div>
          <h3 class="text-xl font-bold text-gray-800 mb-2">Transportation</h3>
          <p class="text-gray-600 mb-4">Reliable and comfortable transportation between Makkah, Madinah, and Jeddah with professional drivers.</p>
          <a href="transportation.php" class="text-green-600 hover:text-green-700 font-medium inline-flex items-center">
            Learn More <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </a>
        </div>

        <!-- Guided Tours -->
        <div class="service-block bg-white rounded-xl shadow-md p-6 text-center" data-aos="fade-up" data-aos-delay="400">
          <div class="service-icon w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
          </div>
          <h3 class="text-xl font-bold text-gray-800 mb-2">Guided Assistance</h3>
          <p class="text-gray-600 mb-4">Experienced guides to assist you throughout your journey and enhance your spiritual experience.</p>
          <a href="packages.php" class="text-green-600 hover:text-green-700 font-medium inline-flex items-center">
            Learn More <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- Why Choose Us Section -->
  <section class="py-16 bg-gray-50">
    <div class="container mx-auto px-4">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
        <div data-aos="fade-right">
          <span class="inline-block px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-semibold mb-2">WHY CHOOSE US</span>
          <h2 class="text-4xl font-bold text-gray-800 mb-6">Experience the Difference with Our Umrah Services</h2>
          <p class="text-gray-600 mb-8">We take pride in offering exceptional Umrah services that stand out from the rest. Here's why pilgrims choose us for their sacred journey:</p>

          <div class="space-y-4">
            <div class="flex items-start">
              <div class="flex-shrink-0 mr-4">
                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                </div>
              </div>
              <div>
                <h4 class="text-lg font-semibold text-gray-800 mb-1">Experienced Professionals</h4>
                <p class="text-gray-600">Our team consists of experienced professionals who understand the spiritual significance of Umrah.</p>
              </div>
            </div>

            <div class="flex items-start">
              <div class="flex-shrink-0 mr-4">
                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                </div>
              </div>
              <div>
                <h4 class="text-lg font-semibold text-gray-800 mb-1">Personalized Packages</h4>
                <p class="text-gray-600">We offer customized packages to suit different needs and budgets, ensuring a comfortable journey.</p>
              </div>
            </div>

            <div class="flex items-start">
              <div class="flex-shrink-0 mr-4">
                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                </div>
              </div>
              <div>
                <h4 class="text-lg font-semibold text-gray-800 mb-1">Premium Accommodations</h4>
                <p class="text-gray-600">Stay in carefully selected hotels near the Haram to maximize your time for worship.</p>
              </div>
            </div>

            <div class="flex items-start">
              <div class="flex-shrink-0 mr-4">
                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                </div>
              </div>
              <div>
                <h4 class="text-lg font-semibold text-gray-800 mb-1">24/7 Support</h4>
                <p class="text-gray-600">Our dedicated support team is available round the clock to assist you during your journey.</p>
              </div>
            </div>
          </div>
        </div>

        <div class="relative" data-aos="fade-left">
          <!-- SVG Illustration of Kaaba -->
          <svg width="100%" height="400" viewBox="0 0 800 600" fill="none" xmlns="http://www.w3.org/2000/svg">
            <!-- Background -->
            <rect width="800" height="600" fill="#F9FAFB" />

            <!-- Sky -->
            <rect width="800" height="300" fill="#E0F2FE" />

            <!-- Kaaba -->
            <rect x="300" y="200" width="200" height="200" fill="#111827" />

            <!-- Kaaba Door -->
            <rect x="380" y="250" width="40" height="80" fill="#D4AF37" />

            <!-- Kaaba Corner Stones -->
            <circle cx="300" cy="200" r="10" fill="#D4AF37" />
            <circle cx="500" cy="200" r="10" fill="#D4AF37" />
            <circle cx="300" cy="400" r="10" fill="#D4AF37" />
            <circle cx="500" cy="400" r="10" fill="#D4AF37" />

            <!-- Kaaba Kiswa Border -->
            <rect x="300" y="250" width="200" height="20" fill="#D4AF37" opacity="0.8" />

            <!-- Tawaf Circle -->
            <circle cx="400" cy="300" r="180" stroke="#E5E7EB" stroke-width="20" stroke-dasharray="10 10" fill="none" />

            <!-- People -->
            <circle cx="250" cy="300" r="8" fill="#047857" />
            <circle cx="270" cy="350" r="8" fill="#047857" />
            <circle cx="280" cy="270" r="8" fill="#047857" />
            <circle cx="520" cy="300" r="8" fill="#047857" />
            <circle cx="540" cy="350" r="8" fill="#047857" />
            <circle cx="530" cy="270" r="8" fill="#047857" />
            <circle cx="400" cy="150" r="8" fill="#047857" />
            <circle cx="450" cy="170" r="8" fill="#047857" />
            <circle cx="350" cy="170" r="8" fill="#047857" />
            <circle cx="400" cy="450" r="8" fill="#047857" />
            <circle cx="450" cy="430" r="8" fill="#047857" />
            <circle cx="350" cy="430" r="8" fill="#047857" />

            <!-- Minarets -->
            <rect x="200" y="150" width="20" height="200" fill="#E5E7EB" />
            <polygon points="200,150 220,150 210,120" fill="#E5E7EB" />
            <rect x="580" y="150" width="20" height="200" fill="#E5E7EB" />
            <polygon points="580,150 600,150 590,120" fill="#E5E7EB" />

            <!-- Ground -->
            <rect y="400" width="800" height="200" fill="#E5E7EB" />
          </svg>
        </div>
      </div>
    </div>
  </section>

  <!-- Umrah Process Section -->
  <section class="py-16 bg-white">
    <div class="container mx-auto px-4">
      <div class="text-center mb-12" data-aos="fade-up">
        <span class="inline-block px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-semibold mb-2">THE JOURNEY</span>
        <h2 class="text-4xl font-bold text-gray-800 mb-4">Understanding the Umrah Process</h2>
        <p class="text-gray-600 max-w-2xl mx-auto">Follow these essential steps to complete your Umrah pilgrimage with spiritual fulfillment.</p>
      </div>

      <div class="timeline-container max-w-4xl mx-auto">
        <div class="timeline-item" data-aos="fade-up" data-aos-delay="100">
          <h3 class="text-xl font-bold text-gray-800 mb-2">1. Ihram (Purification & Intention)</h3>
          <p class="text-gray-600 mb-2">Enter the sacred state of Ihram by performing Ghusl (ritual bath), wearing the proper Ihram garments, and making the Niyyah (intention) for Umrah.</p>
          <div class="bg-green-50 p-4 rounded-lg">
            <p class="text-green-700 font-medium">
              <i class="fas fa-info-circle mr-2"></i>
              Men wear two white unstitched cloths, while women wear modest regular clothes.
            </p>
          </div>
        </div>

        <div class="timeline-item" data-aos="fade-up" data-aos-delay="200">
          <h3 class="text-xl font-bold text-gray-800 mb-2">2. Tawaf (Circumambulation)</h3>
          <p class="text-gray-600 mb-2">Perform seven counterclockwise circuits around the Kaaba, starting from the Black Stone (Hajar al-Aswad), reciting prayers throughout.</p>
          <div class="bg-green-50 p-4 rounded-lg">
            <p class="text-green-700 font-medium">
              <i class="fas fa-info-circle mr-2"></i>
              The first three circuits are performed at a faster pace (for men), and the remaining four at a normal walking pace.
            </p>
          </div>
        </div>

        <div class="timeline-item" data-aos="fade-up" data-aos-delay="300">
          <h3 class="text-xl font-bold text-gray-800 mb-2">3. Sa'i (Walking between Safa & Marwa)</h3>
          <p class="text-gray-600 mb-2">Walk seven times between the hills of Safa and Marwa, commemorating Hagar's search for water for her son Ishmael.</p>
          <div class="bg-green-50 p-4 rounded-lg">
            <p class="text-green-700 font-medium">
              <i class="fas fa-info-circle mr-2"></i>
              Men are encouraged to jog lightly in the marked section between the green lights.
            </p>
          </div>
        </div>

        <div class="timeline-item" data-aos="fade-up" data-aos-delay="400">
          <h3 class="text-xl font-bold text-gray-800 mb-2">4. Halq or Taqsir (Cutting of Hair)</h3>
          <p class="text-gray-600 mb-2">Conclude your Umrah by cutting some hair from your head. Men have the option to shave their heads completely (Halq) or trim their hair (Taqsir), while women cut only a small amount of hair.</p>
          <div class="bg-green-50 p-4 rounded-lg">
            <p class="text-green-700 font-medium">
              <i class="fas fa-info-circle mr-2"></i>
              This step signifies the end of the Umrah rites and the state of Ihram.
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Testimonial Section -->
  <section class="py-16 bg-gray-50">
    <div class="container mx-auto px-4">
      <div class="text-center mb-12" data-aos="fade-up">
        <span class="inline-block px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-semibold mb-2">TESTIMONIALS</span>
        <h2 class="text-4xl font-bold text-gray-800 mb-4">What Our Pilgrims Say</h2>
        <p class="text-gray-600 max-w-2xl mx-auto">Hear from those who have experienced our Umrah services firsthand.</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <div class="testimonial-card" data-aos="fade-up" data-aos-delay="100">
          <div class="flex items-center mb-4">
            <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center mr-3">
              <span class="text-green-600 font-bold text-lg">A</span>
            </div>
            <div>
              <h4 class="font-bold text-gray-800">Ahmed Khan</h4>
              <p class="text-sm text-gray-500">Karachi, Pakistan</p>
            </div>
          </div>
          <p class="text-gray-600 mt-6">
            "The team at UmrahFlights made my first Umrah experience truly memorable. From the moment I landed in Saudi Arabia to the time I departed, every detail was taken care of with utmost professionalism."
          </p>
          <div class="mt-4 flex">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
            </svg>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
            </svg>
          </div>
        </div>
      </div>

      <div class="text-center mt-10">
        <a href="about-us.php" class="inline-flex items-center text-green-600 font-medium hover:text-green-700 transition duration-300">
          See More Testimonials
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
          </svg>
        </a>
      </div>
    </div>
  </section>

  <!-- Stats Section -->
  <section class="py-16 animated-bg text-white">
    <div class="container mx-auto px-4">
      <div class="text-center mb-12" data-aos="fade-up">
        <span class="inline-block px-3 py-1 bg-white text-green-700 rounded-full text-sm font-semibold mb-2">OUR ACHIEVEMENTS</span>
        <h2 class="text-4xl font-bold mb-4">Trusted by Thousands of Pilgrims</h2>
        <p class="max-w-2xl mx-auto opacity-90">We have been privileged to serve numerous pilgrims over the years, helping them fulfill their spiritual journey with comfort and peace of mind.</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 text-center">
        <div class="bg-white/10 backdrop-blur-sm p-6 rounded-lg" data-aos="fade-up" data-aos-delay="100">
          <div class="counter-value text-white">5000+</div>
          <div class="counter-label text-white opacity-90">Happy Pilgrims</div>
        </div>

        <div class="bg-white/10 backdrop-blur-sm p-6 rounded-lg" data-aos="fade-up" data-aos-delay="200">
          <div class="counter-value text-white">10+</div>
          <div class="counter-label text-white opacity-90">Years of Experience</div>
        </div>

        <div class="bg-white/10 backdrop-blur-sm p-6 rounded-lg" data-aos="fade-up" data-aos-delay="300">
          <div class="counter-value text-white">20+</div>
          <div class="counter-label text-white opacity-90">Expert Guides</div>
        </div>

        <div class="bg-white/10 backdrop-blur-sm p-6 rounded-lg" data-aos="fade-up" data-aos-delay="400">
          <div class="counter-value text-white">98%</div>
          <div class="counter-label text-white opacity-90">Satisfaction Rate</div>
        </div>
      </div>
    </div>
  </section>

  <!-- FAQ Section -->
  <section class="py-16 bg-white">
    <div class="container mx-auto px-4">
      <div class="text-center mb-12" data-aos="fade-up">
        <span class="inline-block px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-semibold mb-2">FAQ</span>
        <h2 class="text-4xl font-bold text-gray-800 mb-4">Frequently Asked Questions</h2>
        <p class="text-gray-600 max-w-2xl mx-auto">Find answers to common questions about our Umrah packages and services.</p>
      </div>

      <div class="max-w-3xl mx-auto">
        <div class="faq-item" data-aos="fade-up">
          <div class="faq-question">
            <h3 class="text-lg font-semibold text-gray-800">What is the best time to perform Umrah?</h3>
          </div>
          <div class="faq-answer">
            <p class="text-gray-600">Umrah can be performed at any time of the year, but the best times are during Ramadan or the months of Rajab and Sha'ban. These times are considered especially spiritually rewarding. However, if you prefer less crowded periods, consider the months right after Hajj or during winter.</p>
          </div>
        </div>

        <div class="faq-item" data-aos="fade-up">
          <div class="faq-question">
            <h3 class="text-lg font-semibold text-gray-800">What documents are required for Umrah?</h3>
          </div>
          <div class="faq-answer">
            <p class="text-gray-600">You will need a valid passport with at least 6 months validity, completed Umrah visa application form, recent passport-sized photographs with white background, proof of vaccination as per current requirements, and proof of relationship with travel companions (if applicable). Our team will assist you with the complete documentation process.</p>
          </div>
        </div>

        <div class="faq-item" data-aos="fade-up">
          <div class="faq-question">
            <h3 class="text-lg font-semibold text-gray-800">How many days are recommended for an Umrah trip?</h3>
          </div>
          <div class="faq-answer">
            <p class="text-gray-600">We recommend a minimum of 7-10 days for a comprehensive Umrah experience. This allows sufficient time to perform the rituals, visit important religious sites in Makkah and Madinah, and adjust to the local environment. However, we offer packages of varying durations to accommodate different preferences and schedules.</p>
          </div>
        </div>

        <div class="faq-item" data-aos="fade-up">
          <div class="faq-question">
            <h3 class="text-lg font-semibold text-gray-800">Are there any physical requirements for performing Umrah?</h3>
          </div>
          <div class="faq-answer">
            <p class="text-gray-600">While there are no specific physical requirements to perform Umrah, it does involve walking and standing for extended periods. The Tawaf (circumambulation around the Kaaba) is approximately 1.5 km for seven circuits, and the Sa'i between Safa and Marwa is about 3.5 km. Special arrangements can be made for elderly or disabled pilgrims, including wheelchair assistance.</p>
          </div>
        </div>

        <div class="faq-item" data-aos="fade-up">
          <div class="faq-question">
            <h3 class="text-lg font-semibold text-gray-800">Can I customize my Umrah package?</h3>
          </div>
          <div class="faq-answer">
            <p class="text-gray-600">Yes, we offer customizable Umrah packages to cater to your specific needs and preferences. You can choose your preferred hotel accommodations, flight options, duration of stay, and additional services. Contact our customer service team to discuss your requirements and we'll create a personalized itinerary for you.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Call to Action -->
  <section class="py-16 bg-green-700 text-white">
    <div class="container mx-auto px-4 text-center" data-aos="fade-up">
      <h2 class="text-3xl md:text-4xl font-bold mb-6">Ready to Begin Your Sacred Journey?</h2>
      <p class="text-xl opacity-90 max-w-3xl mx-auto mb-8">Let us guide you on this spiritually enriching experience. Book your Umrah package today and take the first step towards fulfilling your religious obligation.</p>
      <div class="flex flex-col sm:flex-row justify-center gap-4">
        <a href="packages.php" class="bg-white text-green-700 hover:bg-gray-100 font-bold py-3 px-8 rounded-lg text-lg transition duration-300">
          Explore Packages
        </a>
        <a href="contact-us.php" class="bg-transparent border-2 border-white text-white hover:bg-white hover:text-green-700 font-bold py-3 px-8 rounded-lg text-lg transition duration-300">
          Contact Us
        </a>
      </div>
    </div>
  </section>

  <!-- Newsletter Section -->
  <section class="py-12 bg-gray-50">
    <div class="container mx-auto px-4">
      <div class="max-w-4xl mx-auto text-center">
        <h2 class="text-2xl font-bold text-gray-800 mb-2">Stay Updated</h2>
        <p class="text-gray-600 mb-6">Subscribe to our newsletter for the latest updates on Umrah packages, travel tips, and special offers.</p>
        <form class="flex flex-col md:flex-row gap-4 max-w-lg mx-auto">
          <input type="email" placeholder="Your email address" class="flex-grow px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
          <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-6 rounded-lg transition duration-300">
            Subscribe
          </button>
        </form>
        <p class="text-sm text-gray-500 mt-4">We respect your privacy and will never share your information.</p>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <?php include 'includes/footer.php'; ?>

  <!-- Initialize AOS (Animate on Scroll) -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Initialize AOS animation
      AOS.init({
        duration: 800,
        easing: 'ease-in-out',
        once: true
      });

      // FAQ Toggle functionality
      const faqQuestions = document.querySelectorAll('.faq-question');
      faqQuestions.forEach(question => {
        question.addEventListener('click', () => {
          const faqItem = question.parentElement;
          faqItem.classList.toggle('active');
        });
      });

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
  </script>

  <?php include 'includes/js-links.php' ?>
</body>

</html>