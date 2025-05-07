<?php
require_once 'config/db.php';
session_start();

// Check if package ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
  header('Location: packages.php');
  exit;
}

$package_id = (int)$_GET['id'];

// Fetch package details
$stmt = $conn->prepare("SELECT * FROM umrah_packages WHERE id = ?");
$stmt->bind_param("i", $package_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  // Package not found, redirect to packages page
  header('Location: packages.php');
  exit;
}

$package = $result->fetch_assoc();
$stmt->close();

// Parse inclusions
$inclusions = json_decode($package['inclusions'], true) ?: [];

// Get star rating text
function getStarRatingText($rating)
{
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
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $package['total_days']; ?> Nights <?php echo getStarRatingText($package['star_rating']); ?> Umrah Package - UmrahFlights</title>
  <!-- Include Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Include Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body {
      font-family: 'Arial', sans-serif;
    }

    .blue-bg {
      background-color: #1976d2;
    }

    .blue-text {
      color: #1976d2;
    }

    .room-option {
      transition: all 0.3s ease;
    }

    .room-option:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }
  </style>
</head>

<body class="bg-gray-50">
  <!-- Navbar -->
  <?php include 'includes/navbar.php'; ?>

  <div class="container mx-auto px-4 py-8 max-w-6xl">
    <br>
    <br>
    <br>
    <!-- Package Title -->
    <h1 class="text-3xl font-bold text-gray-800 mb-6 border-b pb-4">
      <?php echo $package['total_days']; ?> Nights <?php echo getStarRatingText($package['star_rating']); ?> Umrah Package
    </h1>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
      <!-- Package Image (3 columns) -->
      <div class="lg:col-span-3">
        <img src="<?php echo htmlspecialchars($package['package_image']); ?>" alt="<?php echo htmlspecialchars($package['title']); ?>" class="w-full h-auto rounded-lg">
      </div>

      <!-- Package Details (2 columns) -->
      <div class="lg:col-span-2">
        <!-- Umrah Package Includes -->
        <div class="mb-4">
          <div class="blue-bg text-white py-3 px-4 rounded-t-lg">
            <h2 class="text-xl font-bold">Umrah Packages Includes</h2>
          </div>

          <div class="border border-gray-200 rounded-b-lg overflow-hidden">
            <div class="grid grid-cols-2">
              <!-- Makkah Details -->
              <div class="p-6 text-center border-r border-b border-gray-200">
                <h3 class="font-medium text-gray-700">Makkah</h3>
                <div class="text-4xl font-bold blue-text mt-2"><?php echo $package['makkah_nights']; ?></div>
                <div class="text-gray-600">Nights</div>
              </div>

              <!-- Madinah Details -->
              <div class="p-6 text-center border-b border-gray-200">
                <h3 class="font-medium text-gray-700">Madina</h3>
                <div class="text-4xl font-bold blue-text mt-2"><?php echo $package['madinah_nights']; ?></div>
                <div class="text-gray-600">Nights</div>
              </div>
            </div>

            <!-- Inclusions -->
            <div class="p-4">
              <?php if (in_array('transport', $inclusions)): ?>
                <div class="flex items-center py-2">
                  <i class="fas fa-car text-gray-700 mr-3"></i>
                  <span>Transfers Included</span>
                </div>
              <?php endif; ?>

              <?php if (in_array('visa', $inclusions)): ?>
                <div class="flex items-center py-2">
                  <i class="fas fa-passport text-gray-700 mr-3"></i>
                  <span>Visa Included</span>
                </div>
              <?php endif; ?>

              <?php if (in_array('flight', $inclusions)): ?>
                <div class="flex items-center py-2">
                  <i class="fas fa-plane text-gray-700 mr-3"></i>
                  <span>Flight Included</span>
                </div>
              <?php endif; ?>
            </div>

            <!-- Price Display -->
            <div class="blue-bg text-white p-4 text-center">
              <div class="text-sm">from</div>
              <div class="text-4xl font-bold">PKR <?php echo number_format($package['price']); ?><span class="text-sm">pp</span></div>
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="grid grid-cols-3 gap-3 mb-6">
          <a href="tel:+0304-111-5530" class="blue-bg text-white text-center py-3 px-4 rounded font-medium hover:bg-blue-700 transition duration-300">
            <i class="fas fa-phone mr-2"></i> Call Now
          </a>
          <a href="https://wa.me/03041115530" class="bg-green-500 text-white text-center py-3 px-4 rounded font-medium hover:bg-green-600 transition duration-300">
            <i class="fab fa-whatsapp mr-2"></i> Whatsapp
          </a>
          <a href="package-booking.php?id=<?php echo $package['id']; ?>" class="bg-red-600 text-white text-center py-3 px-4 rounded font-medium hover:bg-red-700 transition duration-300">
            <i class="fas fa-calendar-check mr-2"></i> Book Now
          </a>
        </div>

        <!-- Expert Contact -->
        <div class="text-center mb-4">
          <div class="font-medium text-gray-700">Speak to our Hajj & Umrah Expert</div>
          <a href="tel:+0304-111-5530" class="text-2xl font-bold blue-text hover:underline">0304 111 5530</a>
        </div>
      </div>
    </div>

    <!-- Room Options -->
    <!-- <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-8">
      <a href="#" class="blue-bg text-white text-center py-4 rounded room-option">
        <div class="font-medium">Sharing Room</div>
        <div class="text-2xl font-bold mt-1">PKR 235,500</div>
      </a>
      <a href="#" class="blue-bg text-white text-center py-4 rounded room-option">
        <div class="font-medium">Quad Room</div>
        <div class="text-2xl font-bold mt-1">PKR 245,500</div>
      </a>
      <a href="#" class="blue-bg text-white text-center py-4 rounded room-option">
        <div class="font-medium">Triple Room</div>
        <div class="text-2xl font-bold mt-1">PKR 264,500</div>
      </a>
      <a href="#" class="blue-bg text-white text-center py-4 rounded room-option">
        <div class="font-medium">Double Room</div>
        <div class="text-2xl font-bold mt-1">PKR 303,500</div>
      </a>
    </div> -->

    <!-- Package Description -->
    <div class="mt-8 bg-white rounded-lg shadow-md p-6">
      <h3 class="text-xl font-bold text-gray-800 mb-4">Package Description</h3>
      <div class="text-gray-700 leading-relaxed">
        <?php echo nl2br(htmlspecialchars($package['description'])); ?>
      </div>
    </div>

    <!-- Related Packages -->
    <div class="mt-12">
      <h3 class="text-2xl font-bold text-gray-800 mb-6">You May Also Like</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php
        // Fetch related packages of the same type
        $stmt = $conn->prepare("SELECT * FROM umrah_packages WHERE star_rating = ? AND id != ? ORDER BY RAND() LIMIT 3");
        $stmt->bind_param("si", $package['star_rating'], $package_id);
        $stmt->execute();
        $related_result = $stmt->get_result();

        if ($related_result->num_rows > 0):
          while ($related = $related_result->fetch_assoc()):
        ?>
            <div class="package-card bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition duration-300">
              <div class="relative">
                <img src="<?php echo htmlspecialchars($related['package_image']); ?>" alt="<?php echo htmlspecialchars($related['title']); ?>" class="w-full h-48 object-cover">
                <div class="absolute bottom-0 left-0 w-full bg-black bg-opacity-60 text-white text-center py-2">
                  <?php echo $related['total_days']; ?> Nights <?php echo getStarRatingText($related['star_rating']); ?> Umrah Package
                </div>
              </div>
              <div class="p-0">
                <div class="grid grid-cols-2">
                  <div class="p-4 text-center border-r border-gray-200">
                    <img src="images/kaaba-icon.png" alt="Makkah" class="w-8 h-8 mx-auto mb-2">
                    <div class="font-medium">Makkah <?php echo $related['makkah_nights']; ?> Nights</div>
                    <div class="text-xs text-gray-500 mt-1">(similar)</div>
                    <div class="flex justify-center mt-1">
                      <?php
                      switch ($related['star_rating']) {
                        case 'low_budget':
                          echo '<i class="fas fa-star text-yellow-400"></i>';
                          break;
                        case '3_star':
                          echo '<i class="fas fa-star text-yellow-400 mx-0.5"></i><i class="fas fa-star text-yellow-400 mx-0.5"></i><i class="fas fa-star text-yellow-400 mx-0.5"></i>';
                          break;
                        case '4_star':
                          echo '<i class="fas fa-star text-yellow-400 mx-0.5"></i><i class="fas fa-star text-yellow-400 mx-0.5"></i><i class="fas fa-star text-yellow-400 mx-0.5"></i><i class="fas fa-star text-yellow-400 mx-0.5"></i>';
                          break;
                        case '5_star':
                          echo '<i class="fas fa-star text-yellow-400 mx-0.5"></i><i class="fas fa-star text-yellow-400 mx-0.5"></i><i class="fas fa-star text-yellow-400 mx-0.5"></i><i class="fas fa-star text-yellow-400 mx-0.5"></i><i class="fas fa-star text-yellow-400 mx-0.5"></i>';
                          break;
                      }
                      ?>
                    </div>
                  </div>
                  <div class="p-4 text-center">
                    <img src="images/madinah-icon.png" alt="Madinah" class="w-8 h-8 mx-auto mb-2">
                    <div class="font-medium">Madinah <?php echo $related['madinah_nights']; ?> Nights</div>
                    <div class="text-xs text-gray-500 mt-1">(similar)</div>
                    <div class="flex justify-center mt-1">
                      <?php
                      switch ($related['star_rating']) {
                        case 'low_budget':
                          echo '<i class="fas fa-star text-yellow-400"></i>';
                          break;
                        case '3_star':
                          echo '<i class="fas fa-star text-yellow-400 mx-0.5"></i><i class="fas fa-star text-yellow-400 mx-0.5"></i><i class="fas fa-star text-yellow-400 mx-0.5"></i>';
                          break;
                        case '4_star':
                          echo '<i class="fas fa-star text-yellow-400 mx-0.5"></i><i class="fas fa-star text-yellow-400 mx-0.5"></i><i class="fas fa-star text-yellow-400 mx-0.5"></i><i class="fas fa-star text-yellow-400 mx-0.5"></i>';
                          break;
                        case '5_star':
                          echo '<i class="fas fa-star text-yellow-400 mx-0.5"></i><i class="fas fa-star text-yellow-400 mx-0.5"></i><i class="fas fa-star text-yellow-400 mx-0.5"></i><i class="fas fa-star text-yellow-400 mx-0.5"></i><i class="fas fa-star text-yellow-400 mx-0.5"></i>';
                          break;
                      }
                      ?>
                    </div>
                  </div>
                </div>

                <div class="mt-0">
                  <a href="package-details.php?id=<?php echo $related['id']; ?>" class="block w-full blue-bg text-white text-center py-3 font-medium">VIEW DETAILS</a>
                </div>

                <div class="bg-gray-100 p-2 text-center font-bold blue-text">
                  PKR <?php echo number_format($related['price']); ?> /PP
                  <span class="text-xs text-gray-500 ml-1">| <?php echo $related['total_days']; ?> Nights</span>
                </div>
              </div>
            </div>
          <?php
          endwhile;
        else:
          ?>
          <div class="col-span-3">
            <p class="text-center text-gray-600">No related packages found.</p>
          </div>
        <?php
        endif;
        $stmt->close();
        ?>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <?php include 'includes/footer.php'; ?>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Add any JavaScript functionality here
    });
  </script>
</body>

</html>