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

// Fetch active FAQs from database, grouped by category
$faqs_query = "SELECT * FROM faqs WHERE status = 'active' ORDER BY category, created_at DESC";
$faqs_result = $conn->query($faqs_query);

// Group FAQs by category
$faqs_by_category = [];
$all_faqs = [];

if ($faqs_result && $faqs_result->num_rows > 0) {
  while ($faq = $faqs_result->fetch_assoc()) {
    $category = $faq['category'] ?: 'General';
    if (!isset($faqs_by_category[$category])) {
      $faqs_by_category[$category] = [];
    }
    $faqs_by_category[$category][] = $faq;
    $all_faqs[] = $faq;
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Home | UmrahFlights</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css" />
  <link rel="stylesheet" href="assets/css/index.css">
  <link rel="stylesheet" href="src/output.css">
  <style>

  </style>
</head>

<body class="flex flex-col min-h-screen">
  <!-- Navbar -->
  <?php include 'includes/navbar.php'; ?>

  <!-- Hero Section -->
  <section class="hero-section">
    <div class="hero-content animate-on-scroll">
      <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight mb-4">Embark on a Sacred Journey</h1>
      <p class="text-lg md:text-xl text-gray-100 max-w-2xl mx-auto mb-8">Experience spiritual transformation with our premium Umrah packages tailored for comfort and serenity.</p>
      <div class="flex flex-wrap justify-center gap-4">
        <a href="packages.php" class="gradient-button"><i class="fas fa-arrow-right mr-2"></i>Explore Packages</a>
        <a href="contact-us.php" class="outline-button"><i class="fas fa-phone mr-2"></i>Contact Us</a>
      </div>
    </div>
  </section>

  <!-- Featured Destinations Section -->
  <section class="py-20 bg-white">
    <div class="container mx-auto px-4">
      <div class="text-center mb-12 animate-on-scroll">
        <h2 class="section-title">Discover Holy Destinations</h2>
        <p class="text-gray-600 max-w-2xl mx-auto">Explore significant places in your spiritual journey with our guided tours and comprehensive packages.</p>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <div class="destination-card animate-on-scroll">
          <img src="assets/images/mecca.jpg" alt="Mecca" class="destination-img">
          <div class="destination-overlay">
            <h3 class="text-white text-xl font-bold">Mecca</h3>
            <p class="text-gray-200">The holiest city in Islam and the birthplace of Prophet Muhammad</p>
          </div>
        </div>
        <div class="destination-card animate-on-scroll">
          <img src="assets/images/Madinah.jpg" alt="Madinah" class="destination-img">
          <div class="destination-overlay">
            <h3 class="text-white text-xl font-bold">Madinah</h3>
            <p class="text-gray-200">The second holiest city in Islam and the burial place of Prophet Muhammad</p>
          </div>
        </div>
        <div class="destination-card animate-on-scroll">
          <img src="assets/images/Jeddah.jpg" alt="Jeddah" class="destination-img">
          <div class="destination-overlay">
            <h3 class="text-white text-xl font-bold">Jeddah</h3>
            <p class="text-gray-200">The gateway to Mecca and a major urban center in western Saudi Arabia</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Packages Section -->
  <section class="py-20 bg-gray-50">
    <div class="container mx-auto px-4">
      <div class="text-center mb-12 animate-on-scroll">
        <span class="chip mb-3">Premium Selections</span>
        <h2 class="section-title">Discover Our Umrah Packages</h2>
        <p class="text-gray-600 max-w-2xl mx-auto">Choose from our carefully designed packages tailored for comfort and spiritual enrichment.</p>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php if (empty($packages)): ?>
          <div class="col-span-full text-center">
            <p class="text-gray-500">No packages available at the moment. Please check back later.</p>
          </div>
        <?php else: ?>
          <?php foreach ($packages as $index => $package): ?>
            <div class="card package-card animate-on-scroll">
              <!-- Package Header -->
              <div class="package-img">
                <img src="<?php echo htmlspecialchars($package['package_image']); ?>" alt="<?php echo htmlspecialchars($package['title']); ?>">
                <div class="absolute top-4 right-4 chip"><?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($package['star_rating']))); ?></div>
                <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 to-transparent p-6">
                  <h3 class="text-white text-xl font-bold"><?php echo htmlspecialchars($package['title']); ?></h3>
                  <div class="flex flex-wrap gap-2 mt-2">
                    <span class="chip"><?php echo (int)$package['total_days']; ?> Days</span>
                    <span class="chip">Makkah: <?php echo (int)$package['makkah_nights']; ?> Nights</span>
                    <span class="chip">Madinah: <?php echo (int)$package['madinah_nights']; ?> Nights</span>
                  </div>
                </div>
              </div>
              <!-- Package Content -->
              <div class="p-6 flex flex-col">
                <div class="flex justify-between items-center mb-4">
                  <span class="text-2xl font-bold text-emerald-600">Rs <?php echo number_format($package['price'], 0); ?></span>
                  <div class="flex">
                    <?php
                    $starCount = 0;
                    switch ($package['star_rating']) {
                      case '5_star':
                        $starCount = 5;
                        break;
                      case '4_star':
                        $starCount = 4;
                        break;
                      case '3_star':
                        $starCount = 3;
                        break;
                      case 'low_budget':
                        $starCount = 2;
                        break;
                    }
                    for ($i = 0; $i < $starCount; $i++):
                    ?>
                      <i class="fas fa-star text-yellow-400"></i>
                    <?php endfor; ?>
                  </div>
                </div>
                <p class="text-gray-600 mb-6 line-clamp-2"><?php echo htmlspecialchars(substr($package['description'], 0, 120)) . '...'; ?></p>
                <div class="mb-6">
                  <h4 class="text-sm font-semibold text-gray-500 uppercase mb-3">Package Includes</h4>
                  <div class="grid grid-cols-2 gap-2">
                    <?php
                    $inclusions = json_decode($package['inclusions'], true);
                    if (is_array($inclusions) && !empty($inclusions)):
                      $count = 0;
                      foreach ($inclusions as $inclusion):
                        if ($count >= 4) break;
                        $icon_class = match ($inclusion) {
                          'flight' => 'fa-plane',
                          'hotel' => 'fa-hotel',
                          'transport' => 'fa-car',
                          'guide' => 'fa-user',
                          'vip_services' => 'fa-star',
                          'visa' => 'fa-passport',
                          'meals' => 'fa-utensils',
                          'ziyarat' => 'fa-map-marked-alt',
                          default => 'fa-check'
                        };
                        $count++;
                    ?>
                        <div class="flex items-center gap-2 text-gray-700">
                          <div class="feature-icon"><i class="fas <?php echo $icon_class; ?>"></i></div>
                          <span class="text-sm"><?php echo ucfirst(str_replace('_', ' ', $inclusion)); ?></span>
                        </div>
                      <?php
                      endforeach;
                      if (count($inclusions) > 4):
                      ?>
                        <div class="col-span-2 text-xs text-emerald-600 font-medium mt-1">
                          + <?php echo count($inclusions) - 4; ?> more inclusions
                        </div>
                    <?php endif;
                    endif; ?>
                  </div>
                </div>
                <div class="flex flex-col gap-2 mt-auto">
                  <a href="package-details.php?id=<?php echo $package['id']; ?>" class="gradient-button w-full text-center"><i class="fas fa-eye mr-2"></i>View Details</a>
                  <a href="package-booking.php?package_id=<?php echo $package['id']; ?>" class="outline-button w-full text-center"><i class="fas fa-book mr-2"></i>Book Now</a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
      <div class="text-center mt-12 animate-on-scroll">
        <a href="all-packages.php" class="gradient-button"><i class="fas fa-arrow-right mr-2"></i>View All Packages</a>
      </div>
    </div>
  </section>

  <!-- Key Features Section -->
  <section class="py-20 bg-white">
    <div class="container mx-auto px-4">
      <div class="text-center mb-12 animate-on-scroll">
        <h2 class="section-title">Our Exceptional Services</h2>
        <p class="text-gray-600 max-w-2xl mx-auto">Top-quality Umrah services ensuring comfort, convenience, and spiritual fulfillment.</p>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
        <div class="card animate-on-scroll">
          <div class="feature-icon mb-4"><i class="fas fa-map-marked-alt"></i></div>
          <h3 class="text-xl font-bold mb-2">Strategic Locations</h3>
          <p class="text-gray-600 text-sm">Premium hotels within walking distance from Haram to maximize worship time.</p>
        </div>
        <div class="card animate-on-scroll">
          <div class="feature-icon mb-4"><i class="fas fa-user-tie"></i></div>
          <h3 class="text-xl font-bold mb-2">Expert Guidance</h3>
          <p class="text-gray-600 text-sm">Knowledgeable guides assist throughout your journey, enhancing your spiritual experience.</p>
        </div>
        <div class="card animate-on-scroll">
          <div class="feature-icon mb-4"><i class="fas fa-plane"></i></div>
          <h3 class="text-xl font-bold mb-2">Comfortable Travel</h3>
          <p class="text-gray-600 text-sm">Direct flights with premium airlines and hassle-free transport between destinations.</p>
        </div>
        <div class="card animate-on-scroll">
          <div class="feature-icon mb-4"><i class="fas fa-headset"></i></div>
          <h3 class="text-xl font-bold mb-2">24/7 Support</h3>
          <p class="text-gray-600 text-sm">Dedicated support team available round the clock during your sacred journey.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Testimonials Section -->
  <section class="py-20 bg-gray-50">
    <div class="container mx-auto px-4">
      <div class="text-center mb-12 animate-on-scroll">
        <h2 class="section-title">Pilgrim Testimonials</h2>
        <p class="text-gray-600 max-w-2xl mx-auto">Hear from those who have experienced our Umrah services firsthand.</p>
      </div>
      <div class="swiper testimonial-slider animate-on-scroll">
        <div class="swiper-wrapper">
          <div class="swiper-slide">
            <div class="testimonial-card">
              <div class="flex mb-4">
                <i class="fas fa-star text-yellow-400"></i>
                <i class="fas fa-star text-yellow-400"></i>
                <i class="fas fa-star text-yellow-400"></i>
                <i class="fas fa-star text-yellow-400"></i>
                <i class="fas fa-star text-yellow-400"></i>
              </div>
              <p class="text-gray-600 italic mb-4">"The team made my first Umrah truly memorable. Every detail was handled with professionalism."</p>
              <div class="flex items-center">
                <div class="w-12 h-12 bg-emerald-100 rounded-full flex items-center justify-center text-emerald-600 font-bold">A</div>
                <div class="ml-3">
                  <p class="font-bold">Ahmed Khan</p>
                  <p class="text-sm text-gray-500">Karachi, Pakistan</p>
                </div>
              </div>
            </div>
          </div>
          <div class="swiper-slide">
            <div class="testimonial-card">
              <div class="flex mb-4">
                <i class="fas fa-star text-yellow-400"></i>
                <i class="fas fa-star text-yellow-400"></i>
                <i class="fas fa-star text-yellow-400"></i>
                <i class="fas fa-star text-yellow-400"></i>
                <i class="fas fa-star text-yellow-400"></i>
              </div>
              <p class="text-gray-600 italic mb-4">"As a woman traveling with family, I appreciated the care and excellent accommodations."</p>
              <div class="flex items-center">
                <div class="w-12 h-12 bg-emerald-100 rounded-full flex items-center justify-center text-emerald-600 font-bold">F</div>
                <div class="ml-3">
                  <p class="font-bold">Fatima Ali</p>
                  <p class="text-sm text-gray-500">Lahore, Pakistan</p>
                </div>
              </div>
            </div>
          </div>
          <div class="swiper-slide">
            <div class="testimonial-card">
              <div class="flex mb-4">
                <i class="fas fa-star text-yellow-400"></i>
                <i class="fas fa-star text-yellow-400"></i>
                <i class="fas fa-star text-yellow-400"></i>
                <i class="fas fa-star text-yellow-400"></i>
                <i class="fas fa-star-half-alt text-yellow-400"></i>
              </div>
              <p class="text-gray-600 italic mb-4">"The most organized Umrah trip I’ve experienced. Will book again."</p>
              <div class="flex items-center">
                <div class="w-12 h-12 bg-emerald-100 rounded-full flex items-center justify-center text-emerald-600 font-bold">M</div>
                <div class="ml-3">
                  <p class="font-bold">Muhammad Usman</p>
                  <p class="text-sm text-gray-500">Islamabad, Pakistan</p>
                </div>
              </div>
            </div>
          </div>
          <div class="swiper-slide">
            <div class="testimonial-card">
              <div class="flex mb-4">
                <i class="fas fa-star text-yellow-400"></i>
                <i class="fas fa-star text-yellow-400"></i>
                <i class="fas fa-star text-yellow-400"></i>
                <i class="fas fa-star text-yellow-400"></i>
                <i class="fas fa-star text-yellow-400"></i>
              </div>
              <p class="text-gray-600 italic mb-4">"The premium package exceeded expectations with excellent hotels and punctual transport."</p>
              <div class="flex items-center">
                <div class="w-12 h-12 bg-emerald-100 rounded-full flex items-center justify-center text-emerald-600 font-bold">S</div>
                <div class="ml-3">
                  <p class="font-bold">Saad Rahman</p>
                  <p class="text-sm text-gray-500">Peshawar, Pakistan</p>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="swiper-pagination"></div>
        <div class="swiper-button-next"></div>
        <div class="swiper-button-prev"></div>
      </div>
    </div>
  </section>

  <!-- Stats Section -->
  <section class="stats-section">
    <div class="container mx-auto px-4">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
        <div class="stat-card animate-on-scroll">
          <div class="text-4xl font-extrabold mb-2" data-count="5000">0</div>
          <div class="text-lg font-medium">Happy Pilgrims</div>
        </div>
        <div class="stat-card animate-on-scroll">
          <div class="text-4xl font-extrabold mb-2" data-count="10">0</div>
          <div class="text-lg font-medium">Years Experience</div>
        </div>
        <div class="stat-card animate-on-scroll">
          <div class="text-4xl font-extrabold mb-2" data-count="20">0</div>
          <div class="text-lg font-medium">Expert Guides</div>
        </div>
        <div class="stat-card animate-on-scroll">
          <div class="text-4xl font-extrabold mb-2" data-count="98">0</div>
          <div class="text-lg font-medium">Satisfaction Rate</div>
        </div>
      </div>
    </div>
  </section>

  <!-- Umrah Process Timeline -->
  <section class="py-20 bg-white">
    <div class="container mx-auto px-4">
      <div class="text-center mb-12 animate-on-scroll">
        <h2 class="section-title">The Umrah Journey</h2>
        <p class="text-gray-600 max-w-2xl mx-auto">Understand the essential steps of Umrah to prepare spiritually and physically.</p>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div class="card relative animate-on-scroll">
          <div class="absolute -top-6 left-6 bg-emerald-600 text-white w-12 h-12 rounded-full flex items-center justify-center text-xl font-bold">1</div>
          <div class="pt-6">
            <h3 class="text-xl font-bold mb-2">Ihram (Purification & Intention)</h3>
            <p class="text-gray-600 text-sm">Enter Ihram by performing Ghusl, wearing Ihram garments, and making Niyyah for Umrah.</p>
            <div class="mt-4 bg-emerald-50 p-4 rounded-lg border-l-4 border-emerald-500">
              <p class="text-sm text-emerald-700"><i class="fas fa-info-circle mr-2"></i>Men wear two white unstitched cloths, women wear modest clothes.</p>
            </div>
          </div>
        </div>
        <div class="card relative animate-on-scroll">
          <div class="absolute -top-6 left-6 bg-emerald-600 text-white w-12 h-12 rounded-full flex items-center justify-center text-xl font-bold">2</div>
          <div class="pt-6">
            <h3 class="text-xl font-bold mb-2">Tawaf (Circumambulation)</h3>
            <p class="text-gray-600 text-sm">Perform seven counterclockwise circuits around the Kaaba, reciting prayers.</p>
            <div class="mt-4 bg-emerald-50 p-4 rounded-lg border-l-4 border-emerald-500">
              <p class="text-sm text-emerald-700"><i class="fas fa-info-circle mr-2"></i>First three circuits at a faster pace (men), then normal pace.</p>
            </div>
          </div>
        </div>
        <div class="card relative animate-on-scroll">
          <div class="absolute -top-6 left-6 bg-emerald-600 text-white w-12 h-12 rounded-full flex items-center justify-center text-xl font-bold">3</div>
          <div class="pt-6">
            <h3 class="text-xl font-bold mb-2">Sa'i (Walking between Safa & Marwa)</h3>
            <p class="text-gray-600 text-sm">Walk seven times between Safa and Marwa, commemorating Hagar’s search for water.</p>
            <div class="mt-4 bg-emerald-50 p-4 rounded-lg border-l-4 border-emerald-500">
              <p class="text-sm text-emerald-700"><i class="fas fa-info-circle mr-2"></i>Men jog lightly in the marked section between green lights.</p>
            </div>
          </div>
        </div>
        <div class="card relative animate-on-scroll">
          <div class="absolute -top-6 left-6 bg-emerald-600 text-white w-12 h-12 rounded-full flex items-center justify-center text-xl font-bold">4</div>
          <div class="pt-6">
            <h3 class="text-xl font-bold mb-2">Halq or Taqsir (Cutting of Hair)</h3>
            <p class="text-gray-600 text-sm">Conclude Umrah by cutting hair; men may shave (Halq) or trim (Taqsir).</p>
            <div class="mt-4 bg-emerald-50 p-4 rounded-lg border-l-4 border-emerald-500">
              <p class="text-sm text-emerald-700"><i class="fas fa-info-circle mr-2"></i>Signifies the end of Umrah rites and Ihram state.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA Section -->
  <section class="cta-section">
    <div class="cta-overlay"></div>
    <div class="container mx-auto px-4 text-center animate-on-scroll">
      <h2 class="text-3xl font-bold text-white mb-4">Ready to Begin Your Sacred Journey?</h2>
      <p class="text-lg text-gray-200 mb-8 max-w-3xl mx-auto">Take the first step towards fulfilling your religious obligation with our comprehensive Umrah packages.</p>
      <div class="flex flex-wrap justify-center gap-4">
        <a href="packages.php" class="gradient-button"><i class="fas fa-arrow-right mr-2"></i>Explore Packages</a>
        <a href="contact-us.php" class="outline-button"><i class="fas fa-phone mr-2"></i>Contact Us</a>
      </div>
    </div>
  </section>

  <!-- Newsletter Section -->
  <section class="py-20 bg-white">
    <div class="container mx-auto px-4">
      <div class="text-center animate-on-scroll">
        <h2 class="section-title">Join Our Newsletter</h2>
        <p class="text-gray-600 max-w-2xl mx-auto mb-6">Subscribe for updates on Umrah packages, travel guidelines, and exclusive offers.</p>
        <form class="newsletter-form">
          <input type="email" class="newsletter-input" placeholder="Your email address">
          <button type="submit" class="newsletter-btn"><i class="fas fa-envelope mr-2"></i>Subscribe</button>
        </form>
        <p class="text-sm text-gray-500 mt-4">We respect your privacy and will never share your information.</p>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <!-- <footer class="footer-bg py-20 text-gray-200">
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
            <li><a href="about.php" class="text-gray-300 hover:text-white transition">About Us</a></li>
            <li><a href="packages.php" class="text-gray-300 hover:text-white transition">Our Packages</a></li>
            <li><a href="faqs.php" class="text-gray-300 hover:text-white transition">FAQs</a></li>
            <li><a href="contact.php" class="text-gray-300 hover:text-white transition">Contact Us</a></li>
          </ul>
        </div>
        <div class="animate-on-scroll">
          <h3 class="text-2xl font-bold mb-6 text-white">Our Services</h3>
          <ul class="space-y-4">
            <li><a href="packages.php" class="text-gray-300 hover:text-white transition">Umrah Packages</a></li>
            <li><a href="flight-booking.php" class="text-gray-300 hover:text-white transition">Flight Booking</a></li>
            <li><a href="hotel-booking.php" class="text-gray-300 hover:text-white transition">Hotel Reservation</a></li>
            <li><a href="visa.php" class="text-gray-300 hover:text-white transition">Visa Processing</a></li>
            <li><a href="transport.php" class="text-gray-300 hover:text-white transition">Transportation</a></li>
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
  </footer> -->
  <?php include 'includes/footer.php' ?>                  
  <script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Initialize Swiper
      new Swiper('.testimonial-slider', {
        slidesPerView: 1,
        spaceBetween: 30,
        loop: true,
        pagination: {
          el: '.swiper-pagination',
          clickable: true,
        },
        navigation: {
          nextEl: '.swiper-button-next',
          prevEl: '.swiper-button-prev',
        },
        breakpoints: {
          640: {
            slidesPerView: 1
          },
          768: {
            slidesPerView: 2
          },
          1024: {
            slidesPerView: 3
          },
        },
        autoplay: {
          delay: 5000,
          disableOnInteraction: false,
        },
      });

      // Stats counter animation
      const statNumbers = document.querySelectorAll('[data-count]');
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            const element = entry.target;
            const target = parseInt(element.getAttribute('data-count'));
            const duration = 2000;
            const step = target / duration * 10;
            let current = 0;
            const timer = setInterval(() => {
              current += step;
              if (current >= target) {
                element.textContent = target;
                clearInterval(timer);
              } else {
                element.textContent = Math.floor(current);
              }
            }, 10);
            observer.unobserve(element);
          }
        });
      }, {
        threshold: 0.5
      });
      statNumbers.forEach(number => observer.observe(number));

      // Scroll animations
      const elements = document.querySelectorAll('.animate-on-scroll');
      const scrollObserver = new IntersectionObserver(
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
      elements.forEach((el) => scrollObserver.observe(el));

      // Smooth scroll for anchor links
      document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
          e.preventDefault();
          const target = document.querySelector(this.getAttribute('href'));
          if (target) {
            target.scrollIntoView({
              behavior: 'smooth',
              block: 'start'
            });
          }
        });
      });
    });
  </script>
</body>

</html>