<?php
// Include database connection
require_once 'config/db.php';

// Include contact info functions if they don't exist
if (!function_exists('getContactInfo')) {
  function getContactInfo($conn, $type = null, $primary_only = false)
  {
    $where_conditions = ["status = 'active'"];
    $params = [];
    $types = "";

    if ($type) {
      $where_conditions[] = "type = ?";
      $params[] = $type;
      $types .= "s";
    }

    if ($primary_only) {
      $where_conditions[] = "is_primary = 1";
    }

    $query = "SELECT * FROM contact_info WHERE " . implode(" AND ", $where_conditions);
    $query .= " ORDER BY is_primary DESC, created_at ASC";

    $stmt = $conn->prepare($query);
    if (!empty($params)) {
      $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $contact_info = [];
    while ($row = $result->fetch_assoc()) {
      $contact_info[] = $row;
    }

    return $contact_info;
  }
}

if (!function_exists('getPrimaryContact')) {
  function getPrimaryContact($conn, $type)
  {
    $contacts = getContactInfo($conn, $type, true);
    return !empty($contacts) ? $contacts[0] : null;
  }
}

if (!function_exists('getContactsByType')) {
  function getContactsByType($conn, $type)
  {
    return getContactInfo($conn, $type);
  }
}

// Fetch contact information
$primary_email = getPrimaryContact($conn, 'email');
$primary_phone = getPrimaryContact($conn, 'phone');
$all_emails = getContactsByType($conn, 'email');
$all_phones = getContactsByType($conn, 'phone');
$address = getPrimaryContact($conn, 'address');
$whatsapp = getPrimaryContact($conn, 'whatsapp');
$social_links = getContactsByType($conn, 'social');
?>

<!-- Footer -->
<footer class="footer-bg py-20 text-gray-200">
  <div class="container mx-auto px-4">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-12">
      <!-- About Us -->
      <div class="animate-on-scroll">
        <h3 class="한다는 text-2xl font-bold mb-6 text-white">About Us</h3>
        <p class="text-gray-300 text-sm leading-relaxed">
          We specialize in creating transformative Umrah experiences, blending premium services with spiritual fulfillment.
        </p>
        <div class="flex space-x-6 mt-6">
          <?php foreach ($social_links as $social): ?>
            <a href="<?php echo htmlspecialchars($social['value']); ?>"
              class="social-icon"
              title="<?php echo htmlspecialchars($social['label']); ?>">
              <?php if (strpos(strtolower($social['label']), 'facebook') !== false): ?>
                <i class="fab fa-facebook"></i>
              <?php elseif (strpos(strtolower($social['label']), 'twitter') !== false): ?>
                <i class="fab fa-twitter"></i>
              <?php elseif (strpos(strtolower($social['label']), 'instagram') !== false): ?>
                <i class="fab fa-instagram"></i>
              <?php elseif (strpos(strtolower($social['label']), 'linkedin') !== false): ?>
                <i class="fab fa-linkedin-in"></i>
              <?php else: ?>
                <i class="fas fa-share-alt"></i>
              <?php endif; ?>
            </a>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Quick Links -->
      <div class="animate-on-scroll">
        <h3 class="text-2xl font-bold mb-6 text-white">Quick Links</h3>
        <ul class="space-y-4">
          <li><a href="index.php" class="text-gray-300 hover:text-white transition">Home</a></li>
          <li><a href="about.php" class="text-gray-300 hover:text-white transition">About Us</a></li>
          <li><a href="packages.php" class="text-gray-300 hover:text-white transition">Our Packages</a></li>
          <li><a href="faqs.php" class="text-gray-300 hover:text-white transition">FAQs</a></li>
          <li><a href="contact-us.php" class="text-gray-300 hover:text-white transition">Contact Us</a></li>
        </ul>
      </div>

      <!-- Our Services -->
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

      <!-- Contact Info -->
      <div class="animate-on-scroll">
        <h3 class="text-2xl font-bold mb-6 text-white">Contact Us</h3>
        <ul class="space-y-4 text-gray-300">
          <?php if ($address): ?>
            <li class="flex items-start">
              <i class="fas fa-map-marker-alt mt-1 mr-3 text-emerald-400"></i>
              <span><?php echo nl2br(htmlspecialchars($address['value'])); ?></span>
            </li>
          <?php endif; ?>

          <?php if ($primary_phone): ?>
            <li class="flex items-center">
              <i class="fas fa-phone mr-3 text-emerald-400"></i>
              <a href="tel:<?php echo htmlspecialchars($primary_phone['value']); ?>"
                class="hover:text-white transition">
                <?php echo htmlspecialchars($primary_phone['value']); ?>
              </a>
            </li>
          <?php endif; ?>

          <?php if ($whatsapp): ?>
            <li class="flex items-center">
              <i class="fab fa-whatsapp mr-3 text-emerald-400"></i>
              <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $whatsapp['value']); ?>"
                class="hover:text-white transition">
                <?php echo htmlspecialchars($whatsapp['value']); ?>
              </a>
            </li>
          <?php endif; ?>

          <?php if ($primary_email): ?>
            <li class="flex items-center">
              <i class="fas fa-envelope mr-3 text-emerald-400"></i>
              <a href="mailto:<?php echo htmlspecialchars($primary_email['value']); ?>"
                class="hover:text-white transition">
                <?php echo htmlspecialchars($primary_email['value']); ?>
              </a>
            </li>
          <?php endif; ?>

          <?php if (count($all_emails) > 1): ?>
            <?php foreach ($all_emails as $email): ?>
              <?php if (!$email['is_primary']): ?>
                <li class="flex items-center">
                  <i class="fas fa-envelope mr-3 text-emerald-400"></i>
                  <a href="mailto:<?php echo htmlspecialchars($email['value']); ?>"
                    class="hover:text-white transition text-sm">
                    <?php echo htmlspecialchars($email['label'] . ': ' . $email['value']); ?>
                  </a>
                </li>
              <?php endif; ?>
            <?php endforeach; ?>
          <?php endif; ?>
        </ul>
      </div>
    </div>

    <!-- Footer Bottom -->
    <div class="border-t border-gray-700 mt-12 pt-8 flex flex-col md:flex-row justify-between items-center animate-on-scroll">
      <p class="text-gray-400 text-sm">© <?php echo date('Y'); ?> Umrah Partners. All rights reserved.</p>
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
    // Scroll animation for footer elements
    const elements = document.querySelectorAll('.animate-on-scroll');
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('animate-fade-in');
        }
      });
    }, {
      threshold: 0.1
    });

    elements.forEach(element => observer.observe(element));
  });
</script>

<style>
  @import url('https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700;800&display=swap');

  .footer-bg {
    background: linear-gradient(180deg, #1a202c 0%, #2d3748 100%);
    font-family: 'Manrope', sans-serif;
  }

  .social-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.1);
    color: #e2e8f0;
    transition: background 0.3s ease, color 0.3s ease, transform 0.3s ease;
  }

  .social-icon:hover {
    background: #10b981;
    color: #ffffff;
    transform: scale(1.1);
  }

  .animate-on-scroll {
    opacity: 0;
    transform: translateY(20px);
    transition: opacity 0.6s ease-out, transform 0.6s ease-out;
  }

  .animate-fade-in {
    opacity: 1;
    transform: translateY(0);
  }
</style>