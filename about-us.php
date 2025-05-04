<?php
require_once 'config/db.php';
session_start();

// Fetch about us content
$about_query = "SELECT * FROM about_us WHERE status = 'active' ORDER BY display_order, created_at";
$about_result = $conn->query($about_query);

$about_sections = [];
if ($about_result && $about_result->num_rows > 0) {
  while ($row = $about_result->fetch_assoc()) {
    $about_sections[$row['section_type']] = $row;
  }
}

// Fetch company values
$values_query = "SELECT * FROM company_values WHERE status = 'active' ORDER BY display_order, created_at";
$values_result = $conn->query($values_query);

// Fetch company statistics
$stats_query = "SELECT * FROM company_statistics WHERE status = 'active' ORDER BY display_order, created_at";
$stats_result = $conn->query($stats_query);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php' ?>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="src/output.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <!-- Add AOS (Animate on Scroll) Library -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" />
  <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
  <style>
    .service-card {
      transition: all 0.3s ease;
    }

    .service-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
    }

    .team-member {
      transition: all 0.3s ease;
    }

    .team-member:hover {
      transform: translateY(-5px);
    }

    .team-member:hover .social-links {
      opacity: 1;
    }

    .social-links {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      background: linear-gradient(to top, rgba(0, 0, 0, 0.7), transparent);
      padding: 20px 0 15px;
      opacity: 0;
      transition: all 0.3s ease;
    }

    .milestone-counter {
      position: relative;
      z-index: 1;
    }

    .milestone-counter::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: rgba(4, 120, 87, 0.05);
      border-radius: 0.5rem;
      z-index: -1;
      transform: rotate(-3deg);
      transition: all 0.3s ease;
    }

    .milestone-counter:hover::before {
      transform: rotate(0deg);
      background-color: rgba(4, 120, 87, 0.1);
    }

    .counter-value {
      color: #047857;
      font-size: 2.5rem;
      font-weight: 700;
      line-height: 1;
    }

    .counter-label {
      color: #4b5563;
      font-size: 1rem;
      margin-top: 0.5rem;
    }

    .timeline-item {
      position: relative;
      padding-left: 30px;
      margin-bottom: 30px;
    }

    .timeline-item::before {
      content: '';
      position: absolute;
      left: 0;
      top: 5px;
      width: 15px;
      height: 15px;
      border-radius: 50%;
      background-color: #047857;
    }

    .timeline-item::after {
      content: '';
      position: absolute;
      left: 7px;
      top: 25px;
      width: 1px;
      height: calc(100% + 10px);
      background-color: #d1d5db;
    }

    .timeline-item:last-child::after {
      display: none;
    }

    .testimonial-card {
      transition: all 0.3s ease;
    }

    .testimonial-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }

    .testimonial-card::before {
      content: '"';
      position: absolute;
      top: 20px;
      left: 20px;
      font-size: 5rem;
      line-height: 1;
      color: rgba(4, 120, 87, 0.1);
      font-family: Georgia, serif;
    }
  </style>
</head>

<body>
  <!-- Navbar -->
  <?php include 'includes/navbar.php'; ?>

  <!-- Introduction Section -->
  <section class="py-16 bg-gray-50">
    <div class="container mx-auto px-4">
      <div class="flex flex-col md:flex-row gap-12 items-center">
        <div class="w-full md:w-1/2" data-aos="fade-right">
          <?php if (isset($about_sections['mission'])): ?>
            <h2 class="text-3xl font-bold text-gray-800 mb-6"><?php echo htmlspecialchars($about_sections['mission']['title']); ?></h2>
            <div class="text-gray-600 leading-relaxed mb-6">
              <?php echo nl2br(htmlspecialchars($about_sections['mission']['content'])); ?>
            </div>
          <?php endif; ?>

          <?php if (isset($about_sections['quote']) && isset($about_sections['quote_author'])): ?>
            <div class="bg-white p-6 rounded-lg shadow-sm border-l-4 border-green-600">
              <p class="italic text-gray-600">
                "<?php echo htmlspecialchars($about_sections['quote']['content']); ?>"
              </p>
              <p class="mt-2 font-medium text-gray-800">- <?php echo htmlspecialchars($about_sections['quote_author']['content']); ?></p>
            </div>
          <?php endif; ?>
        </div>
        <div class="w-full md:w-1/2" data-aos="fade-left">
          <?php if (isset($about_sections['mission']) && !empty($about_sections['mission']['image_url'])): ?>
            <img src="<?php echo htmlspecialchars($about_sections['mission']['image_url']); ?>"
              alt="About Us"
              class="w-full h-auto rounded-lg shadow-md">
          <?php else: ?>
            <img src="assets/img/hero.jpg"
              alt="Umrah Pilgrimage"
              class="w-full h-auto rounded-lg shadow-md">
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <!-- Values Section -->
  <section class="py-16 bg-white">
    <div class="container mx-auto px-4">
      <?php if (isset($about_sections['values_intro'])): ?>
        <div class="text-center mb-12" data-aos="fade-up">
          <h2 class="text-3xl font-bold text-gray-800 mb-4"><?php echo htmlspecialchars($about_sections['values_intro']['title']); ?></h2>
          <p class="text-gray-600 max-w-3xl mx-auto">
            <?php echo htmlspecialchars($about_sections['values_intro']['subtitle']); ?>
          </p>
        </div>
      <?php endif; ?>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php if ($values_result && $values_result->num_rows > 0): ?>
          <?php $delay = 100; ?>
          <?php while ($value = $values_result->fetch_assoc()): ?>
            <div class="service-card bg-white p-6 rounded-lg shadow-md border-t-4 border-green-600" data-aos="fade-up" data-aos-delay="<?php echo $delay; ?>">
              <div class="w-14 h-14 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                <?php if ($value['icon_class']): ?>
                  <i class="<?php echo htmlspecialchars($value['icon_class']); ?> text-2xl text-green-600"></i>
                <?php else: ?>
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                <?php endif; ?>
              </div>
              <h3 class="text-xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($value['title']); ?></h3>
              <p class="text-gray-600">
                <?php echo htmlspecialchars($value['description']); ?>
              </p>
            </div>
            <?php $delay += 100; ?>
          <?php endwhile; ?>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- Statistics Section -->
  <section class="py-16 bg-gray-50">
    <div class="container mx-auto px-4">
      <?php if (isset($about_sections['statistics_intro'])): ?>
        <div class="text-center mb-12" data-aos="fade-up">
          <h2 class="text-3xl font-bold text-gray-800 mb-4"><?php echo htmlspecialchars($about_sections['statistics_intro']['title']); ?></h2>
          <p class="text-gray-600 max-w-3xl mx-auto">
            <?php echo htmlspecialchars($about_sections['statistics_intro']['subtitle']); ?>
          </p>
        </div>
      <?php endif; ?>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
        <?php if ($stats_result && $stats_result->num_rows > 0): ?>
          <?php $delay = 100; ?>
          <?php while ($stat = $stats_result->fetch_assoc()): ?>
            <div class="milestone-counter text-center p-6" data-aos="fade-up" data-aos-delay="<?php echo $delay; ?>">
              <div class="counter-value">
                <?php echo htmlspecialchars($stat['prefix'] ?? ''); ?><?php echo htmlspecialchars($stat['value']); ?><?php echo htmlspecialchars($stat['suffix'] ?? ''); ?>
              </div>
              <div class="counter-label"><?php echo htmlspecialchars($stat['label']); ?></div>
            </div>
            <?php $delay += 100; ?>
          <?php endwhile; ?>
        <?php endif; ?>
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
    });
  </script>

  <?php include 'includes/js-links.php' ?>
</body>

</html>