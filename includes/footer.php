<?php
// Include database connection
require_once 'config/db.php';

// Include contact info functions or define them here
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

function getPrimaryContact($conn, $type)
{
  $contacts = getContactInfo($conn, $type, true);
  return !empty($contacts) ? $contacts[0] : null;
}

function getContactsByType($conn, $type)
{
  return getContactInfo($conn, $type);
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

<!-- Footer Section -->
<footer class="bg-gray-900 text-white py-12">
  <div class="container mx-auto px-4">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
      <!-- About Us -->
      <div>
        <h4 class="text-xl font-semibold mb-4">About Us</h4>
        <p class="text-gray-400 mb-4">
          We specialize in providing comprehensive Umrah packages with premium services,
          ensuring a comfortable and spiritual journey for all our clients.
        </p>
        <div class="flex space-x-4">
          <?php foreach ($social_links as $social): ?>
            <a href="<?php echo htmlspecialchars($social['value']); ?>"
              class="text-gray-400 hover:text-white transition-colors"
              title="<?php echo htmlspecialchars($social['label']); ?>">
              <?php if (strpos(strtolower($social['label']), 'facebook') !== false): ?>
                <i class="fab fa-facebook-f"></i>
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
      <div>
        <h4 class="text-xl font-semibold mb-4">Quick Links</h4>
        <ul class="space-y-2">
          <li><a href="index.php" class="text-gray-400 hover:text-white transition-colors">Home</a></li>
          <li><a href="about.php" class="text-gray-400 hover:text-white transition-colors">About Us</a></li>
          <li><a href="packages.php" class="text-gray-400 hover:text-white transition-colors">Our Packages</a></li>
          <li><a href="faqs.php" class="text-gray-400 hover:text-white transition-colors">FAQs</a></li>
          <li><a href="contact.php" class="text-gray-400 hover:text-white transition-colors">Contact Us</a></li>
        </ul>
      </div>

      <!-- Our Services -->
      <div>
        <h4 class="text-xl font-semibold mb-4">Our Services</h4>
        <ul class="space-y-2">
          <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Umrah Packages</a></li>
          <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Flight Booking</a></li>
          <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Hotel Reservation</a></li>
          <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Visa Processing</a></li>
          <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Transportation</a></li>
        </ul>
      </div>

      <!-- Contact Info -->
      <div>
        <h4 class="text-xl font-semibold mb-4">Contact Us</h4>
        <?php if ($address): ?>
          <p class="text-gray-400 mb-3 flex items-start">
            <i class="fas fa-map-marker-alt mr-3 mt-1 text-amber-500"></i>
            <?php echo nl2br(htmlspecialchars($address['value'])); ?>
          </p>
        <?php endif; ?>

        <?php if ($primary_phone): ?>
          <p class="text-gray-400 mb-3 flex items-center">
            <i class="fas fa-phone-alt mr-3 text-amber-500"></i>
            <a href="tel:<?php echo htmlspecialchars($primary_phone['value']); ?>"
              class="hover:text-white transition-colors">
              <?php echo htmlspecialchars($primary_phone['value']); ?>
            </a>
          </p>
        <?php endif; ?>

        <?php if ($whatsapp): ?>
          <p class="text-gray-400 mb-3 flex items-center">
            <i class="fab fa-whatsapp mr-3 text-amber-500"></i>
            <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $whatsapp['value']); ?>"
              class="hover:text-white transition-colors">
              <?php echo htmlspecialchars($whatsapp['value']); ?>
            </a>
          </p>
        <?php endif; ?>

        <?php if ($primary_email): ?>
          <p class="text-gray-400 mb-3 flex items-center">
            <i class="fas fa-envelope mr-3 text-amber-500"></i>
            <a href="mailto:<?php echo htmlspecialchars($primary_email['value']); ?>"
              class="hover:text-white transition-colors">
              <?php echo htmlspecialchars($primary_email['value']); ?>
            </a>
          </p>
        <?php endif; ?>

        <?php if (count($all_emails) > 1): ?>
          <?php foreach ($all_emails as $email): ?>
            <?php if (!$email['is_primary']): ?>
              <p class="text-gray-400 mb-3 flex items-center">
                <i class="fas fa-envelope mr-3 text-amber-500"></i>
                <a href="mailto:<?php echo htmlspecialchars($email['value']); ?>"
                  class="hover:text-white transition-colors text-sm">
                  <?php echo htmlspecialchars($email['label'] . ': ' . $email['value']); ?>
                </a>
              </p>
            <?php endif; ?>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Footer Bottom -->
    <div class="border-t border-gray-800 mt-12 pt-8">
      <div class="flex flex-col md:flex-row justify-between items-center">
        <p class="text-gray-400 text-sm mb-4 md:mb-0">
          © <?php echo date('Y'); ?> Umrah Partners. All rights reserved.
        </p>
        <div class="flex space-x-6">
          <a href="#" class="text-gray-400 hover:text-white text-sm transition-colors">Privacy Policy</a>
          <a href="#" class="text-gray-400 hover:text-white text-sm transition-colors">Terms of Service</a>
          <a href="#" class="text-gray-400 hover:text-white text-sm transition-colors">Cookie Policy</a>
        </div>
      </div>
    </div>
  </div>
</footer>