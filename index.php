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
  <style>
    /* Package Card Styles */
    .package-card {
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
      transition: all 0.3s ease;
      background-color: #fff;
      height: 100%;
      display: flex;
      flex-direction: column;
    }

    .package-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
    }

    .package-card img {
      width: 100%;
      height: 220px;
      object-fit: cover;
    }

    .package-card .card-body {
      padding: 20px;
      flex-grow: 1;
      display: flex;
      flex-direction: column;
    }

    .package-price {
      font-size: 1.6rem;
      font-weight: 700;
      color: #0d6efd;
      margin-bottom: 10px;
    }

    .package-title {
      font-size: 1.3rem;
      font-weight: 600;
      margin-bottom: 5px;
      color: #333;
    }

    .package-location {
      font-size: 0.9rem;
      color: #6c757d;
      margin-bottom: 15px;
    }

    .package-features {
      list-style: none;
      padding: 0;
      margin: 0 0 15px 0;
      flex-grow: 1;
    }

    .package-features li {
      padding: 5px 0;
      display: flex;
      align-items: center;
      font-size: 0.9rem;
      color: #555;
    }

    .package-features i {
      margin-right: 8px;
      color: #0d6efd;
      width: 18px;
      text-align: center;
    }

    .learn-more-btn {
      display: inline-block;
      background-color: #0d6efd;
      color: white;
      padding: 10px 20px;
      border-radius: 5px;
      text-decoration: none;
      font-weight: 500;
      transition: background-color 0.3s;
      text-align: center;
      margin-top: auto;
    }

    .learn-more-btn:hover {
      background-color: #0b5ed7;
      color: white;
      text-decoration: none;
    }

    .limited-offer {
      position: absolute;
      top: 10px;
      right: 10px;
      background-color: #ff6b6b;
      color: white;
      padding: 5px 10px;
      border-radius: 5px;
      font-size: 0.8rem;
      font-weight: 500;
    }

    /* Package List Page Styles */
    .page-header {
      background-color: #f8f9fa;
      padding: 100px 0 50px;
      margin-bottom: 40px;
      text-align: center;
    }

    .page-header h1 {
      font-size: 2.5rem;
      font-weight: 700;
      color: #333;
      margin-bottom: 10px;
    }

    .breadcrumb {
      background-color: transparent;
      justify-content: center;
    }

    .breadcrumb-item+.breadcrumb-item::before {
      content: ">";
    }

    .filter-section {
      margin-bottom: 40px;
    }

    .filter-container {
      background-color: #fff;
      padding: 25px;
      border-radius: 10px;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
    }

    .filter-container h3 {
      margin-bottom: 20px;
      font-size: 1.3rem;
      color: #333;
      font-weight: 600;
    }

    /* Package Details Page Styles */
    .package-image {
      position: relative;
      margin-bottom: 20px;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
    }

    .package-image img {
      width: 100%;
      border-radius: 10px;
    }

    .package-labels {
      position: absolute;
      top: 15px;
      left: 15px;
      display: flex;
      flex-direction: column;
      gap: 5px;
    }

    .package-type,
    .flight-class {
      display: inline-block;
      padding: 5px 10px;
      border-radius: 5px;
      font-size: 0.8rem;
      font-weight: 500;
      color: white;
    }

    .package-type {
      background-color: #0d6efd;
    }

    .flight-class {
      background-color: #198754;
    }

    .package-info-card {
      background-color: #fff;
      border-radius: 10px;
      padding: 25px;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
      height: 100%;
      display: flex;
      flex-direction: column;
    }

    .package-header {
      margin-bottom: 20px;
      border-bottom: 1px solid #eee;
      padding-bottom: 15px;
    }

    .package-header h2 {
      font-size: 1.8rem;
      font-weight: 700;
      color: #333;
      margin-bottom: 10px;
    }

    .package-meta {
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      margin-bottom: 25px;
    }

    .meta-item {
      display: flex;
      align-items: center;
      gap: 8px;
      color: #6c757d;
      font-size: 0.9rem;
    }

    .meta-item i {
      color: #0d6efd;
    }

    .package-inclusions {
      margin-bottom: 25px;
    }

    .package-inclusions h4 {
      font-size: 1.2rem;
      font-weight: 600;
      color: #333;
      margin-bottom: 15px;
    }

    .package-inclusions ul {
      list-style: none;
      padding: 0;
      margin: 0;
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 10px;
    }

    .package-inclusions li {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 8px 0;
      color: #555;
    }

    .package-inclusions i {
      width: 20px;
      height: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      background-color: #e7f0ff;
      color: #0d6efd;
      border-radius: 50%;
      font-size: 0.7rem;
    }

    .booking-action {
      margin-top: auto;
      display: flex;
      gap: 10px;
    }

    .package-description-card {
      background-color: #fff;
      border-radius: 10px;
      padding: 25px;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
      margin-bottom: 30px;
    }

    .package-description-card h3 {
      font-size: 1.4rem;
      font-weight: 600;
      color: #333;
      margin-bottom: 15px;
      padding-bottom: 15px;
      border-bottom: 1px solid #eee;
    }

    .description-content {
      color: #555;
      line-height: 1.7;
    }

    .related-packages h3 {
      font-size: 1.6rem;
      font-weight: 600;
      color: #333;
      margin-bottom: 25px;
      position: relative;
      padding-bottom: 10px;
    }

    .related-packages h3::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      width: 50px;
      height: 3px;
      background-color: #0d6efd;
    }

    /* Package Badges */
    .package-badge {
      position: absolute;
      top: 15px;
      left: 15px;
    }

    .badge-single,
    .badge-group,
    .badge-vip {
      display: inline-block;
      padding: 5px 10px;
      border-radius: 5px;
      font-size: 0.8rem;
      font-weight: 500;
      color: white;
    }

    .badge-single {
      background-color: #0d6efd;
    }

    .badge-group {
      background-color: #6c757d;
    }

    .badge-vip {
      background-color: #dc3545;
    }

    .badge-economy,
    .badge-business,
    .badge-first {
      display: inline-block;
      padding: 5px 10px;
      border-radius: 5px;
      font-size: 0.8rem;
      font-weight: 500;
      color: white;
      margin-right: 5px;
    }

    .badge-economy {
      background-color: #6c757d;
    }

    .badge-business {
      background-color: #198754;
    }

    .badge-first {
      background-color: #dc3545;
    }

    /* Package Info */
    .package-info {
      margin-bottom: 15px;
    }

    .package-description {
      font-size: 0.9rem;
      color: #6c757d;
      margin-bottom: 15px;
      flex-grow: 1;
    }
  </style>
</head>

<body>
  <!-- Navbar -->
  <?php include 'includes/navbar.php'; ?>
  <!-- Hero Section -->
  <br><br><br>
  <section class="hero-section">
    <div class="hero-content">
      <h1>Experience the Sacred Journey of Umrah</h1>
      <p>Embark on a transformative spiritual journey with our comprehensive Umrah packages. Let us help you make the most of your pilgrimage experience with our tailored services.</p>
      <a href="packages.php" class="explore-btn text-decoration-none">Explore Packages</a>
    </div>
  </section>
  <!-- packages Section -->
  <div class="container mx-auto px-4 py-12">
    <div class="text-center mb-10">
      <div class="text-teal-500 font-medium mb-2">- Packages</div>
      <h2 class="text-4xl font-bold text-gray-800 mb-4">Choose Your Umrah Package</h2>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
      <?php if (empty($packages)): ?>
        <div class="col-span-full text-center">
          <p class="text-gray-600">No packages available at the moment. Please check back later.</p>
        </div>
      <?php else: ?>
        <?php foreach ($packages as $package): ?>
          <div class="bg-white rounded-xl shadow-lg overflow-hidden transition-all duration-300 hover:-translate-y-2 hover:shadow-xl flex flex-col h-full">
            <div class="relative">
              <img
                src="<?php echo htmlspecialchars($package['package_image']); ?>"
                alt="<?php echo htmlspecialchars($package['title']); ?>"
                class="w-full h-48 object-cover">
              <span class="absolute top-3 right-3 bg-teal-500 text-white text-xs px-2 py-1 rounded-lg font-medium">
                Limited Offer
              </span>
              <div class="absolute top-3 left-3">
                <?php
                $badge_color = '';
                switch ($package['package_type']) {
                  case 'single':
                    $badge_color = 'bg-teal-500';
                    break;
                  case 'group':
                    $badge_color = 'bg-gray-600';
                    break;
                  case 'vip':
                    $badge_color = 'bg-red-600';
                    break;
                }
                ?>
                <span class="<?php echo $badge_color; ?> text-white text-xs px-2 py-1 rounded-lg font-medium">
                  <?php echo ucfirst(htmlspecialchars($package['package_type'])); ?>
                </span>
              </div>
            </div>

            <div class="p-5 flex flex-col flex-grow">
              <div class="text-2xl font-bold text-gray-800 mb-2">
                Rs<?php echo number_format($package['price'], 2); ?>
              </div>

              <div class="text-xl font-semibold text-teal-600 mb-1">
                <?php echo htmlspecialchars($package['title']); ?>
              </div>

              <div class="text-sm text-gray-600 mb-4">
                <?php echo ucfirst(htmlspecialchars($package['package_type'])) . ' Package'; ?> |
                <?php echo ucfirst(htmlspecialchars($package['flight_class'])); ?> Flight
              </div>

              <ul class="space-y-2 flex-grow mb-4">
                <?php
                $inclusions = json_decode($package['inclusions'], true);
                if (is_array($inclusions) && !empty($inclusions)):
                  foreach ($inclusions as $inclusion):
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
                ?>
                    <li class="flex items-center text-gray-700">
                      <i class="fas <?php echo $icon_class; ?> mr-2 text-teal-500 w-5"></i>
                      <span><?php echo ucfirst(str_replace('_', ' ', $inclusion)); ?></span>
                    </li>
                <?php
                  endforeach;
                endif;
                ?>
              </ul>

              <a
                href="package-details.php?id=<?php echo $package['id']; ?>"
                class="block text-center bg-teal-500 hover:bg-teal-600 text-white font-medium py-2 px-4 rounded-lg transition duration-300 mt-auto">
                Learn More
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="text-center mt-8">
      <a href="packages.php" class="inline-flex items-center border border-teal-500 text-teal-500 hover:bg-teal-500 hover:text-white px-6 py-2 rounded-lg transition-colors duration-300 font-medium">
        View All Packages
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M10.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L12.586 11H5a1 1 0 110-2h7.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
      </a>
    </div>
  </div>
  <!-- Elevate Section -->
  <section class="py-16 bg-gray-50">
    <div class="container mx-auto px-4">
      <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-10">
        <div>
          <div class="text-teal-500 font-medium mb-2">- Features</div>
          <h2 class="text-4xl font-bold text-gray-800">Elevate Your Faith</h2>
        </div>
        <a href="packages.php" class="text-teal-500 hover:text-teal-700 font-medium text-lg flex items-center mt-4 md:mt-0">
          View Packages <span class="ml-1">→</span>
        </a>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
        <!-- Image -->
        <div class="mb-8 lg:mb-0">
          <div class="rounded-xl overflow-hidden shadow-lg">
            <img src="assets/img/hero.jpg" alt="Feature Image" class="w-full h-auto">
          </div>
        </div>

        <!-- Features in 3x2 Grid -->
        <div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <!-- Feature 1 -->
            <div class="flex items-start">
              <div class="flex-shrink-0 mr-4">
                <img src="assets/img/hero.jpg" alt="Tawaf Icon" class="w-12 h-12 rounded-full object-cover">
              </div>
              <div>
                <h5 class="font-semibold text-lg text-gray-800 mb-1">Tawaf</h5>
                <p class="text-gray-600">Circumambulating the Kaaba in unity.</p>
              </div>
            </div>

            <!-- Feature 2 -->
            <div class="flex items-start">
              <div class="flex-shrink-0 mr-4">
                <img src="assets/img/hero.jpg" alt="Ihram Icon" class="w-12 h-12 rounded-full object-cover">
              </div>
              <div>
                <h5 class="font-semibold text-lg text-gray-800 mb-1">Ihram</h5>
                <p class="text-gray-600">Sacred attire signifying purity.</p>
              </div>
            </div>

            <!-- Feature 3 -->
            <div class="flex items-start">
              <div class="flex-shrink-0 mr-4">
                <img src="assets/img/hero.jpg" alt="Mina Icon" class="w-12 h-12 rounded-full object-cover">
              </div>
              <div>
                <h5 class="font-semibold text-lg text-gray-800 mb-1">Mina</h5>
                <p class="text-gray-600">Sacred desert valley for pilgrims.</p>
              </div>
            </div>

            <!-- Feature 4 -->
            <div class="flex items-start">
              <div class="flex-shrink-0 mr-4">
                <img src="assets/img/hero.jpg" alt="Jamarat Icon" class="w-12 h-12 rounded-full object-cover">
              </div>
              <div>
                <h5 class="font-semibold text-lg text-gray-800 mb-1">Jamarat</h5>
                <p class="text-gray-600">Symbolic act of rejecting Satan.</p>
              </div>
            </div>

            <!-- Feature 5 -->
            <div class="flex items-start">
              <div class="flex-shrink-0 mr-4">
                <img src="assets/img/hero.jpg" alt="Zam-Zam Icon" class="w-12 h-12 rounded-full object-cover">
              </div>
              <div>
                <h5 class="font-semibold text-lg text-gray-800 mb-1">Zam-Zam</h5>
                <p class="text-gray-600">Holy water with miraculous origins.</p>
              </div>
            </div>

            <!-- Feature 6 -->
            <div class="flex items-start">
              <div class="flex-shrink-0 mr-4">
                <img src="assets/img/hero.jpg" alt="Prayer Mat Icon" class="w-12 h-12 rounded-full object-cover">
              </div>
              <div>
                <h5 class="font-semibold text-lg text-gray-800 mb-1">Prayer Mat</h5>
                <p class="text-gray-600">Sacred space for performing Salah.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <!-- Footer -->
  <?php include 'includes/footer.php'; ?>
  <?php include 'includes/js-links.php' ?>
</body>

</html>