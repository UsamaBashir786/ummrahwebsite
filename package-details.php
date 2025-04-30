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
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($package['title']); ?> - UmrahFlights</title>
  <!-- Include Tailwind CSS -->
  <link rel="stylesheet" href="src/output.css">
  <!-- Include Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <?php include 'includes/css-links.php' ?>
  <style>
    body {
      margin-top: 65px !important;
    }
  </style>
</head>

<body class="bg-gray-50">
  <!-- Navbar -->
  <?php include 'includes/navbar.php'; ?>

  <!-- Page Header -->
  <section class="bg-green-600 text-white py-8">
    <div class="container mx-auto px-4">
      <h1 class="text-3xl font-bold mb-2"><?php echo htmlspecialchars($package['title']); ?></h1>
      <nav class="text-sm">
        <ol class="flex flex-wrap">
          <li class="flex items-center">
            <a href="index.php" class="hover:text-green-200">Home</a>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mx-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </li>
          <li class="flex items-center">
            <a href="packages.php" class="hover:text-green-200">Packages</a>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mx-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </li>
          <li class="text-green-200"><?php echo htmlspecialchars($package['title']); ?></li>
        </ol>
      </nav>
    </div>
  </section>

  <!-- Package Details Section -->
  <section class="py-12">
    <div class="container mx-auto px-4 max-w-6xl">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Package Image -->
        <div class="relative">
          <img src="<?php echo htmlspecialchars($package['package_image']); ?>" alt="<?php echo htmlspecialchars($package['title']); ?>" class="w-full h-auto rounded-lg shadow-md">
          <div class="absolute top-4 left-4 flex flex-col gap-2">
            <span class="bg-green-600 text-white px-3 py-1 rounded-full text-sm font-medium">
              <?php echo ucfirst($package['package_type']); ?> Package
            </span>
            <span class="bg-blue-600 text-white px-3 py-1 rounded-full text-sm font-medium">
              <?php echo ucfirst($package['flight_class']); ?> Flight
            </span>
          </div>
        </div>

        <!-- Package Info -->
        <div class="bg-white rounded-lg shadow-md p-6">
          <div class="border-b pb-4 mb-4">
            <div class="flex justify-between items-start">
              <h2 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($package['title']); ?></h2>
              <div class="text-2xl font-bold text-green-600">Rs<?php echo number_format($package['price'], 2); ?></div>
            </div>
          </div>

          <div class="flex flex-wrap gap-4 mb-6">
            <div class="flex items-center text-gray-600">
              <i class="fas fa-tag text-green-500 mr-2"></i>
              <span><?php echo ucfirst($package['package_type']); ?> Package</span>
            </div>
            <div class="flex items-center text-gray-600">
              <i class="fas fa-plane text-green-500 mr-2"></i>
              <span><?php echo ucfirst($package['flight_class']); ?> Flight</span>
            </div>
            <div class="flex items-center text-gray-600">
              <i class="fas fa-calendar-alt text-green-500 mr-2"></i>
              <span>Added: <?php echo date('F j, Y', strtotime($package['created_at'])); ?></span>
            </div>
          </div>

          <div class="mb-6">
            <h4 class="text-lg font-semibold text-gray-800 mb-3">Package Inclusions</h4>
            <ul class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <?php foreach ($inclusions as $inclusion): ?>
                <?php
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
                <li class="flex items-center">
                  <i class="fas <?php echo $icon_class; ?> text-green-500 mr-2"></i>
                  <span class="text-gray-700"><?php echo ucfirst(str_replace('_', ' ', $inclusion)); ?></span>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>

          <div class="flex flex-col sm:flex-row gap-4">
            <a href="package-booking.php?package_id=<?php echo $package['id']; ?>" class="bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-6 rounded-md transition duration-300 ease-in-out text-center">
              Book Now
            </a>
            <a href="packages.php" class="border border-gray-300 hover:bg-gray-100 text-gray-700 font-medium py-3 px-6 rounded-md transition duration-300 ease-in-out text-center">
              Back to Packages
            </a>
          </div>
        </div>
      </div>

      <!-- Package Description -->
      <div class="mt-8">
        <div class="bg-white rounded-lg shadow-md p-6">
          <h3 class="text-xl font-bold text-gray-800 mb-4">Package Description</h3>
          <div class="text-gray-700 leading-relaxed">
            <?php echo nl2br(htmlspecialchars($package['description'])); ?>
          </div>
        </div>
      </div>

      <!-- Related Packages -->
      <div class="mt-12">
        <h3 class="text-2xl font-bold text-gray-800 mb-6">You May Also Like</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <?php
          // Fetch related packages of the same type
          $stmt = $conn->prepare("SELECT * FROM umrah_packages WHERE package_type = ? AND id != ? ORDER BY RAND() LIMIT 3");
          $stmt->bind_param("si", $package['package_type'], $package_id);
          $stmt->execute();
          $related_result = $stmt->get_result();

          if ($related_result->num_rows > 0):
            while ($related = $related_result->fetch_assoc()):
          ?>
              <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition duration-300">
                <div class="relative">
                  <img src="<?php echo htmlspecialchars($related['package_image']); ?>" alt="<?php echo htmlspecialchars($related['title']); ?>" class="w-full h-48 object-cover">
                </div>
                <div class="p-5">
                  <div class="text-green-600 font-bold text-lg mb-2">Rs<?php echo number_format($related['price'], 2); ?></div>
                  <h5 class="text-xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($related['title']); ?></h5>
                  <div class="mb-4">
                    <span class="inline-block bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded">
                      <?php echo ucfirst($related['flight_class']); ?> Flight
                    </span>
                  </div>
                  <a href="package-details.php?id=<?php echo $related['id']; ?>" class="block text-center bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-md transition duration-300 ease-in-out">
                    View Details
                  </a>
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
  </section>

  <!-- Footer -->
  <?php include 'includes/footer.php'; ?>
  <?php include 'includes/js-links.php' ?>
</body>

</html>