<?php
require_once '../config/db.php';
// Start admin session
session_name('admin_session');
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
  header('Location: login.php');
  exit;
}

// Initialize variables
$errors = [];
$success = '';
$package = null;

// Get package ID from URL
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
  $_SESSION['error'] = "Invalid package ID.";
  header('Location: view-packages.php');
  exit;
}
$package_id = $_GET['id'];

// Fetch package details
$result = $conn->query("SELECT * FROM umrah_packages WHERE id = " . $conn->real_escape_string($package_id));
if ($result && $result->num_rows > 0) {
  $package = $result->fetch_assoc();
  $package['inclusions'] = json_decode($package['inclusions'], true) ?: [];
} else {
  $_SESSION['error'] = "Package not found.";
  header('Location: view-packages.php');
  exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Validate Star Rating
  $star_rating = filter_input(INPUT_POST, 'star_rating', FILTER_SANITIZE_STRING);
  if (!in_array($star_rating, ['low_budget', '3_star', '4_star', '5_star'])) {
    $errors[] = "Invalid star rating.";
  }

  // Validate Title
  $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
  if (empty($title) || !preg_match('/^[A-Za-z ]{1,35}$/', $title)) {
    $errors[] = "Title is required and must contain only letters and spaces, up to 35 characters.";
  }

  // Validate Description
  $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
  if (empty($description)) {
    $errors[] = "Description is required.";
  } else {
    $words = preg_split('/\s+/', trim($description));
    if (count($words) > 200) {
      $errors[] = "Description cannot exceed 200 words.";
    }
  }

  // Validate Makkah Nights
  $makkah_nights = filter_input(INPUT_POST, 'makkah_nights', FILTER_SANITIZE_NUMBER_INT);
  if (!is_numeric($makkah_nights) || $makkah_nights < 0 || $makkah_nights > 30) {
    $errors[] = "Makkah nights must be a number between 0 and 30.";
  }

  // Validate Madinah Nights
  $madinah_nights = filter_input(INPUT_POST, 'madinah_nights', FILTER_SANITIZE_NUMBER_INT);
  if (!is_numeric($madinah_nights) || $madinah_nights < 0 || $madinah_nights > 30) {
    $errors[] = "Madinah nights must be a number between 0 and 30.";
  }

  // Validate Total Days
  $total_days = filter_input(INPUT_POST, 'total_days', FILTER_SANITIZE_NUMBER_INT);
  if (!is_numeric($total_days) || $total_days < 1 || $total_days > 60) {
    $errors[] = "Total days must be a number between 1 and 60.";
  }

  // Validate Inclusions
  $inclusions = isset($_POST['inclusions']) && is_array($_POST['inclusions']) ? $_POST['inclusions'] : [];
  $valid_inclusions = ['flight', 'hotel', 'transport', 'guide', 'vip_services'];
  $inclusions = array_intersect($inclusions, $valid_inclusions);
  if (empty($inclusions)) {
    $errors[] = "At least one inclusion must be selected.";
  }
  $inclusions_json = json_encode($inclusions);
  if ($inclusions_json === false) {
    $errors[] = "Error processing inclusions.";
  }

  // Validate Price
  $price = filter_input(INPUT_POST, 'price', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
  if (!is_numeric($price) || $price < 0 || $price > 500000) {
    $errors[] = "Price must be a number between 0 and 500,000.";
  }

  // Handle Image Upload
  $package_image = $package['package_image'] ?: 'default-package.jpg';
  if (isset($_FILES['package_image']) && $_FILES['package_image']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['package_image'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 2 * 1024 * 1024; // 2MB

    // Check file size
    if ($file['size'] > $max_size) {
      $errors[] = "Image file size must be 2MB or less.";
    }

    // Check file type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, $allowed_types)) {
      $errors[] = "Only JPEG, PNG, or GIF images are allowed.";
    }

    // Generate unique filename and move file
    if (empty($errors)) {
      $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
      $filename = uniqid('pkg_') . '.' . $ext;
      $upload_dir = '../Uploads/';
      $upload_path = $upload_dir . $filename;

      // Ensure upload directory exists
      if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
          $errors[] = "Failed to create upload directory.";
          error_log("Failed to create directory: $upload_dir");
        }
      }

      // Move uploaded file
      if (empty($errors)) {
        if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
          $errors[] = "Failed to upload image.";
          error_log("Failed to move uploaded file to: $upload_path");
        } else {
          // Delete old image if not default
          if ($package_image !== 'default-package.jpg' && file_exists('../' . $package_image)) {
            unlink('../' . $package_image);
          }
          $package_image = 'Uploads/' . $filename;
          chmod($upload_path, 0644);
        }
      }
    }
  }

  // If no errors, update database
  if (empty($errors)) {
    $stmt = $conn->prepare("
      UPDATE umrah_packages
      SET star_rating = ?, title = ?, description = ?, makkah_nights = ?, madinah_nights = ?, total_days = ?, inclusions = ?, price = ?, package_image = ?
      WHERE id = ?
    ");
    if (!$stmt) {
      $errors[] = "Database query preparation failed: " . $conn->error;
      error_log("Database query preparation failed: " . $conn->error);
    } else {
      $stmt->bind_param(
        "sssiiisdsi",
        $star_rating,
        $title,
        $description,
        $makkah_nights,
        $madinah_nights,
        $total_days,
        $inclusions_json,
        $price,
        $package_image,
        $package_id
      );
      if ($stmt->execute()) {
        $_SESSION['success'] = "Package updated successfully!";
        header('Location: view-packages.php');
        exit;
      } else {
        $errors[] = "Error updating package: " . $stmt->error;
        error_log("Error updating package: " . $stmt->error);
      }
      $stmt->close();
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Umrah Package | UmrahFlights Admin</title>
  <!-- Tailwind CSS -->
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="assets/css/index.css">
</head>

<body class="bg-gray-100">
  <?php include 'includes/sidebar.php'; ?>

  <!-- Main Content -->
  <div class="ml-0 md:ml-64 mt-10 px-4 sm:px-6 lg:px-8 transition-all duration-300">
    <!-- Navbar -->
    <nav class="bg-white shadow-lg rounded-lg p-5 mb-6">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <button id="sidebarToggle" class="text-gray-500 hover:text-gray-700 focus:outline-none md:hidden">
            <i class="fas fa-bars"></i>
          </button>
          <h1 id="dashboardHeader" class="text-lg font-semibold text-gray-800 cursor-pointer hover:text-indigo-600">
            <i class="fas fa-box-open text-indigo-600 mr-2"></i>Edit Umrah Package
          </h1>
        </div>
        <div class="flex items-center space-x-4">
          <!-- User Dropdown -->
          <div class="relative">
            <button id="userDropdownButton" class="flex items-center space-x-2 text-gray-700 hover:bg-indigo-50 rounded-lg px-3 py-2 focus:outline-none">
              <div class="rounded-full overflow-hidden" style="width: 32px; height: 32px;">
                <div class="bg-gray-200 w-full h-full flex items-center justify-center">
                  <i class="fas fa-user text-gray-500"></i>
                </div>
              </div>
              <span class="hidden md:inline text-sm font-medium">Admin User</span>
              <svg class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <ul id="userDropdownMenu" class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 hidden z-50">
              <li>
                <a class="flex items-center px-4 py-2 text-sm text-red-500 hover:bg-red-50" href="logout.php">
                  <i class="fas fa-sign-out-alt mr-2"></i> Logout
                </a>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </nav>

    <!-- Messages -->
    <?php if (!empty($errors)): ?>
      <div id="errorAlert" class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-6 relative" role="alert" aria-live="assertive">
        <ul class="mb-0">
          <?php foreach ($errors as $error): ?>
            <li><?php echo htmlspecialchars($error); ?></li>
          <?php endforeach; ?>
        </ul>
        <button type="button" class="absolute top-2 right-2 text-red-500 hover:text-red-700 focus:outline-none focus:ring-2 focus:ring-red-500" onclick="this.parentElement.classList.add('hidden')" aria-label="Close error alert">
          <i class="fas fa-times"></i>
        </button>
      </div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
      <div id="successAlert" class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-6 relative" role="alert" aria-live="assertive">
        <?php echo htmlspecialchars($success); ?>
        <button type="button" class="absolute top-2 right-2 text-green-500 hover:text-green-700 focus:outline-none focus:ring-2 focus:ring-green-500" onclick="this.parentElement.classList.add('hidden')" aria-label="Close success alert">
          <i class="fas fa-times"></i>
        </button>
      </div>
    <?php endif; ?>

    <!-- Form Section -->
    <div class="bg-white shadow-lg rounded-lg p-6 mx-auto">
      <h2 class="text-xl font-semibold text-indigo-600 mb-6">
        <i class="fas fa-edit mr-2"></i>Edit Umrah Package
      </h2>
      <form action="" method="POST" enctype="multipart/form-data" class="space-y-6" id="editPackageForm">
        <!-- Star Rating -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Package Category <span class="text-red-500">*</span></label>
          <select name="star_rating" class="block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500" required aria-label="Select package category">
            <option value="low_budget" <?php echo $package['star_rating'] === 'low_budget' ? 'selected' : ''; ?>>Low Budget Economy</option>
            <option value="3_star" <?php echo $package['star_rating'] === '3_star' ? 'selected' : ''; ?>>3 Star</option>
            <option value="4_star" <?php echo $package['star_rating'] === '4_star' ? 'selected' : ''; ?>>4 Star</option>
            <option value="5_star" <?php echo $package['star_rating'] === '5_star' ? 'selected' : ''; ?>>5 Star</option>
          </select>
          <small id="starRatingError" class="text-red-500 text-xs mt-1 hidden" aria-live="polite"></small>
        </div>

        <!-- Package Title -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Package Title <span class="text-red-500">*</span></label>
          <input
            type="text"
            id="packageTitle"
            name="title"
            class="block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500"
            required
            value="<?php echo htmlspecialchars($package['title']); ?>"
            oninput="validateTitle()"
            aria-label="Package title"
            aria-describedby="titleError">
          <small id="titleError" class="text-red-500 text-xs mt-1 hidden" aria-live="polite"></small>
        </div>

        <!-- Package Description -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Description <span class="text-red-500">*</span></label>
          <textarea
            id="packageDescription"
            name="description"
            rows="3"
            class="block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500"
            required
            oninput="validateDescription()"
            aria-label="Package description"
            aria-describedby="descError"><?php echo htmlspecialchars($package['description']); ?></textarea>
          <small id="descError" class="text-red-500 text-xs mt-1 hidden" aria-live="polite"></small>
        </div>

        <!-- Makkah Nights -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Makkah Nights <span class="text-red-500">*</span></label>
          <input
            type="number"
            id="makkahNights"
            name="makkah_nights"
            class="block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500"
            required
            min="0"
            max="30"
            value="<?php echo htmlspecialchars($package['makkah_nights']); ?>"
            oninput="validateNights('makkahNights', 'makkahNightsError')"
            aria-label="Makkah nights"
            aria-describedby="makkahNightsError">
          <small id="makkahNightsError" class="text-red-500 text-xs mt-1 hidden" aria-live="polite"></small>
        </div>

        <!-- Madinah Nights -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Madinah Nights <span class="text-red-500">*</span></label>
          <input
            type="number"
            id="madinahNights"
            name="madinah_nights"
            class="block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500"
            required
            min="0"
            max="30"
            value="<?php echo htmlspecialchars($package['madinah_nights']); ?>"
            oninput="validateNights('madinahNights', 'madinahNightsError')"
            aria-label="Madinah nights"
            aria-describedby="madinahNightsError">
          <small id="madinahNightsError" class="text-red-500 text-xs mt-1 hidden" aria-live="polite"></small>
        </div>

        <!-- Total Days -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Total Days <span class="text-red-500">*</span></label>
          <input
            type="number"
            id="totalDays"
            name="total_days"
            class="block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500"
            required
            min="1"
            max="60"
            value="<?php echo htmlspecialchars($package['total_days']); ?>"
            oninput="validateTotalDays()"
            aria-label="Total days"
            aria-describedby="totalDaysError">
          <small id="totalDaysError" class="text-red-500 text-xs mt-1 hidden" aria-live="polite"></small>
        </div>

        <!-- Package Inclusions -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Package Inclusions <span class="text-red-500">*</span></label>
          <div class="space-y-2">
            <label class="flex items-center">
              <input type="checkbox" name="inclusions[]" value="flight" class="text-indigo-600 focus:ring-indigo-500 mr-2" <?php echo in_array('flight', $package['inclusions']) ? 'checked' : ''; ?> aria-label="Include flight">
              <span class="text-sm text-gray-700">Flight</span>
            </label>
            <label class="flex items-center">
              <input type="checkbox" name="inclusions[]" value="hotel" class="text-indigo-600 focus:ring-indigo-500 mr-2" <?php echo in_array('hotel', $package['inclusions']) ? 'checked' : ''; ?> aria-label="Include hotel">
              <span class="text-sm text-gray-700">Hotel</span>
            </label>
            <label class="flex items-center">
              <input type="checkbox" name="inclusions[]" value="transport" class="text-indigo-600 focus:ring-indigo-500 mr-2" <?php echo in_array('transport', $package['inclusions']) ? 'checked' : ''; ?> aria-label="Include transport">
              <span class="text-sm text-gray-700">Transport</span>
            </label>
            <label class="flex items-center">
              <input type="checkbox" name="inclusions[]" value="guide" class="text-indigo-600 focus:ring-indigo-500 mr-2" <?php echo in_array('guide', $package['inclusions']) ? 'checked' : ''; ?> aria-label="Include guide">
              <span class="text-sm text-gray-700">Guide</span>
            </label>
            <label class="flex items-center">
              <input type="checkbox" name="inclusions[]" value="vip_services" class="text-indigo-600 focus:ring-indigo-500 mr-2" <?php echo in_array('vip_services', $package['inclusions']) ? 'checked' : ''; ?> aria-label="Include VIP services">
              <span class="text-sm text-gray-700">VIP Services</span>
            </label>
          </div>
          <small id="inclusionsError" class="text-red-500 text-xs mt-1 hidden" aria-live="polite"></small>
        </div>

        <!-- Price -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Price (PKR) <span class="text-red-500">*</span></label>
          <input
            type="text"
            name="price"
            id="price"
            class="block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500"
            required
            value="<?php echo htmlspecialchars($package['price']); ?>"
            aria-label="Package price in PKR"
            aria-describedby="priceError">
          <small id="priceError" class="text-red-500 text-xs mt-1 hidden" aria-live="polite">Invalid price! Please enter a valid number (e.g., 100.99, max PKR500,000).</small>
        </div>

        <!-- Package Image -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Package Image</label>
          <div class="mb-2">
            <img src="../<?php echo htmlspecialchars($package['package_image'] ?: 'assets/img/default-package.jpg'); ?>" alt="Current package image" class="h-24 w-auto object-cover rounded-lg">
            <p class="text-xs text-gray-500 mt-1">Current image</p>
          </div>
          <input
            type="file"
            name="package_image"
            id="package_image"
            accept="image/jpeg,image/png,image/gif"
            class="block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500"
            aria-label="Upload new package image">
          <small id="imageError" class="text-red-500 text-xs mt-1 hidden" aria-live="polite">File size must be 2MB or less.</small>
          <p class="text-sm text-gray-500 mt-1">Leave blank to keep current image. Accepts JPEG, PNG, or GIF (max 2MB).</p>
        </div>

        <!-- Submit Buttons -->
        <div class="flex flex-wrap gap-4 justify-end">
          <a href="view-packages.php" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" aria-label="Cancel and return to packages">
            <i class="fas fa-times mr-2"></i>Cancel
          </a>
          <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" aria-label="Update package">
            <i class="fas fa-save mr-2"></i>Update Package
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Custom JavaScript -->
  <script src="assets/js/index.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      // Sidebar elements
      const sidebar = document.getElementById('sidebar');
      const sidebarOverlay = document.getElementById('sidebar-overlay');
      const sidebarToggle = document.getElementById('sidebarToggle');
      const sidebarClose = document.getElementById('sidebar-close');
      const dashboardHeader = document.getElementById('dashboardHeader');

      // User dropdown elements
      const userDropdownButton = document.getElementById('userDropdownButton');
      const userDropdownMenu = document.getElementById('userDropdownMenu');

      // Form elements
      const form = document.getElementById('editPackageForm');
      const starRating = document.getElementById('star_rating');
      const starRatingError = document.getElementById('starRatingError');
      const titleInput = document.getElementById('packageTitle');
      const titleError = document.getElementById('titleError');
      const descInput = document.getElementById('packageDescription');
      const descError = document.getElementById('descError');
      const makkahNights = document.getElementById('makkahNights');
      const makkahNightsError = document.getElementById('makkahNightsError');
      const madinahNights = document.getElementById('madinahNights');
      const madinahNightsError = document.getElementById('madinahNightsError');
      const totalDays = document.getElementById('totalDays');
      const totalDaysError = document.getElementById('totalDaysError');
      const inclusions = document.querySelectorAll('input[name="inclusions[]"]');
      const inclusionsError = document.getElementById('inclusionsError');
      const priceInput = document.getElementById('price');
      const priceError = document.getElementById('priceError');
      const imageInput = document.getElementById('package_image');
      const imageError = document.getElementById('imageError');

      // Error handling
      if (!sidebar || !sidebarOverlay || !sidebarToggle || !sidebarClose) {
        console.warn('One or more sidebar elements are missing. Ensure sidebar.php includes #sidebar, #sidebar-overlay, #sidebar-close.');
      }
      if (!userDropdownButton || !userDropdownMenu) {
        console.warn('User dropdown elements are missing.');
      }
      if (!dashboardHeader) {
        console.warn('Dashboard header element is missing.');
      }
      if (!form || !titleInput || !descInput || !makkahNights || !madinahNights || !totalDays || !priceInput || !imageInput) {
        console.warn('Form input elements are missing.');
      }

      // Sidebar toggle
      const toggleSidebar = () => {
        if (sidebar && sidebarOverlay && sidebarToggle) {
          sidebar.classList.toggle('-translate-x-full');
          sidebarOverlay.classList.toggle('hidden');
          sidebarToggle.classList.toggle('hidden');
        }
      };

      if (sidebarToggle) sidebarToggle.addEventListener('click', toggleSidebar);
      if (sidebarClose) sidebarClose.addEventListener('click', toggleSidebar);
      if (sidebarOverlay) sidebarOverlay.addEventListener('click', toggleSidebar);
      if (dashboardHeader) {
        dashboardHeader.addEventListener('click', () => {
          if (sidebar && sidebar.classList.contains('-translate-x-full')) {
            toggleSidebar();
          }
        });
      }

      // User dropdown
      if (userDropdownButton && userDropdownMenu) {
        userDropdownButton.addEventListener('click', () => {
          userDropdownMenu.classList.toggle('hidden');
        });
        document.addEventListener('click', (event) => {
          if (!userDropdownButton.contains(event.target) && !userDropdownMenu.contains(event.target)) {
            userDropdownMenu.classList.add('hidden');
          }
        });
      }

      // Star Rating validation
      if (starRating && starRatingError) {
        starRating.addEventListener('change', () => {
          const validRatings = ['low_budget', '3_star', '4_star', '5_star'];
          if (!validRatings.includes(starRating.value)) {
            starRatingError.textContent = 'Please select a valid package category.';
            starRatingError.classList.remove('hidden');
            starRating.classList.add('border-red-500');
          } else {
            starRatingError.textContent = '';
            starRatingError.classList.add('hidden');
            starRating.classList.remove('border-red-500');
          }
        });
      }

      // Title validation
      function validateTitle() {
        const regex = /^[A-Za-z ]{0,35}$/;
        if (regex.test(titleInput.value)) {
          titleError.textContent = '';
          titleError.classList.add('hidden');
          titleInput.classList.remove('border-red-500');
        } else {
          titleError.textContent = 'Only English letters and spaces are allowed, no numbers or special characters!';
          titleError.classList.remove('hidden');
          titleInput.classList.add('border-red-500');
          titleInput.value = titleInput.value.replace(/[^A-Za-z ]/g, '');
        }
        if (titleInput.value.length > 35) {
          titleInput.value = titleInput.value.slice(0, 35);
        }
      }

      // Description validation
      function validateDescription() {
        const maxWords = 200;
        const words = descInput.value.trim().split(/\s+/).filter(word => word.length > 0);
        if (words.length > maxWords) {
          descError.textContent = 'Description cannot exceed 200 words! Please remove extra words.';
          descError.classList.remove('hidden');
          descInput.classList.add('border-red-500');
          descInput.value = words.slice(0, maxWords).join(' ');
        } else if (!descInput.value.trim()) {
          descError.textContent = 'Description is required.';
          descError.classList.remove('hidden');
          descInput.classList.add('border-red-500');
        } else {
          descError.textContent = '';
          descError.classList.add('hidden');
          descInput.classList.remove('border-red-500');
        }
      }

      // Nights validation
      function validateNights(inputId, errorId) {
        const input = document.getElementById(inputId);
        const errorMessage = document.getElementById(errorId);
        const value = parseInt(input.value);
        if (isNaN(value) || value < 0 || value > 30) {
          errorMessage.textContent = 'Must be a number between 0 and 30.';
          errorMessage.classList.remove('hidden');
          input.classList.add('border-red-500');
        } else {
          errorMessage.textContent = '';
          errorMessage.classList.add('hidden');
          input.classList.remove('border-red-500');
        }
      }

      // Total days validation
      function validateTotalDays() {
        const value = parseInt(totalDays.value);
        if (isNaN(value) || value < 1 || value > 60) {
          totalDaysError.textContent = 'Must be a number between 1 and 60.';
          totalDaysError.classList.remove('hidden');
          totalDays.classList.add('border-red-500');
        } else {
          totalDaysError.textContent = '';
          totalDaysError.classList.add('hidden');
          totalDays.classList.remove('border-red-500');
        }
      }

      // Inclusions validation
      inclusions.forEach(checkbox => {
        checkbox.addEventListener('change', () => {
          const checked = Array.from(inclusions).some(cb => cb.checked);
          if (!checked) {
            inclusionsError.textContent = 'At least one inclusion must be selected.';
            inclusionsError.classList.remove('hidden');
          } else {
            inclusionsError.textContent = '';
            inclusionsError.classList.add('hidden');
          }
        });
      });

      // Price validation
      if (priceInput && priceError) {
        const maxPrice = 500000;
        let isInputValid = true;
        priceInput.addEventListener('input', function(e) {
          let val = e.target.value;
          val = val.replace(/[^0-9.]/g, '');
          const parts = val.split('.');
          if (parts.length > 2) {
            val = parts[0] + '.' + parts[1].slice(0, 2);
          }
          if (parseFloat(val) > maxPrice) {
            val = maxPrice.toString();
          }
          e.target.value = val;
          validatePrice(val);
        });
        priceInput.addEventListener('blur', function(e) {
          validatePrice(e.target.value);
        });

        function validatePrice(value) {
          const isValid = /^([1-9]\d{0,5})(?:\.\d{1,2})?$/.test(value) || /^0(\.\d{1,2})?$/.test(value);
          if (parseFloat(value) > maxPrice) {
            priceError.textContent = `Price cannot exceed PKR${maxPrice}.`;
            priceError.classList.remove('hidden');
            priceInput.classList.add('border-red-500');
            isInputValid = false;
          } else if (value && !isValid) {
            priceError.textContent = 'Invalid price! Please enter a valid number (e.g., 100.99).';
            priceError.classList.remove('hidden');
            priceInput.classList.add('border-red-500');
            isInputValid = false;
          } else {
            priceError.textContent = '';
            priceError.classList.add('hidden');
            priceInput.classList.remove('border-red-500');
            isInputValid = true;
          }
        }
        priceInput.addEventListener('keydown', function(e) {
          if (!isInputValid && e.key !== 'Backspace' && e.key !== 'Delete') {
            e.preventDefault();
          }
        });
      }

      // Image validation
      if (imageInput && imageError) {
        imageInput.addEventListener('change', function(e) {
          const file = e.target.files[0];
          if (file) {
            const fileSize = file.size / 1024 / 1024;
            const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (fileSize > 2) {
              imageError.textContent = 'File size must be 2MB or less.';
              imageError.classList.remove('hidden');
              imageInput.value = '';
            } else if (!validTypes.includes(file.type)) {
              imageError.textContent = 'Only JPEG, PNG, or GIF images are allowed.';
              imageError.classList.remove('hidden');
              imageInput.value = '';
            } else {
              imageError.textContent = '';
              imageError.classList.add('hidden');
            }
          }
        });
      }

      // Form submission validation
      form.addEventListener('submit', (e) => {
        let hasErrors = false;

        // Star Rating
        if (!['low_budget', '3_star', '4_star', '5_star'].includes(starRating.value)) {
          starRatingError.textContent = 'Please select a valid package category.';
          starRatingError.classList.remove('hidden');
          starRating.classList.add('border-red-500');
          hasErrors = true;
        }

        // Title
        if (!/^[A-Za-z ]{1,35}$/.test(titleInput.value)) {
          titleError.textContent = 'Only letters and spaces allowed, up to 35 characters.';
          titleError.classList.remove('hidden');
          titleInput.classList.add('border-red-500');
          hasErrors = true;
        }

        // Description
        const words = descInput.value.trim().split(/\s+/);
        if (!descInput.value.trim()) {
          descError.textContent = 'Description is required.';
          descError.classList.remove('hidden');
          descInput.classList.add('border-red-500');
          hasErrors = true;
        } else if (words.length > 200) {
          descError.textContent = 'Description cannot exceed 200 words.';
          descError.classList.remove('hidden');
          descInput.classList.add('border-red-500');
          hasErrors = true;
        }

        // Makkah Nights
        const makkahValue = parseInt(makkahNights.value);
        if (isNaN(makkahValue) || makkahValue < 0 || makkahValue > 30) {
          makkahNightsError.textContent = 'Must be a number between 0 and 30.';
          makkahNightsError.classList.remove('hidden');
          makkahNights.classList.add('border-red-500');
          hasErrors = true;
        }

        // Madinah Nights
        const madinahValue = parseInt(madinahNights.value);
        if (isNaN(madinahValue) || madinahValue < 0 || madinahValue > 30) {
          madinahNightsError.textContent = 'Must be a number between 0 and 30.';
          madinahNightsError.classList.remove('hidden');
          madinahNights.classList.add('border-red-500');
          hasErrors = true;
        }

        // Total Days
        const totalDaysValue = parseInt(totalDays.value);
        if (isNaN(totalDaysValue) || totalDaysValue < 1 || totalDaysValue > 60) {
          totalDaysError.textContent = 'Must be a number between 1 and 60.';
          totalDaysError.classList.remove('hidden');
          totalDays.classList.add('border-red-500');
          hasErrors = true;
        }

        // Inclusions
        const checkedInclusions = Array.from(inclusions).some(cb => cb.checked);
        if (!checkedInclusions) {
          inclusionsError.textContent = 'At least one inclusion must be selected.';
          inclusionsError.classList.remove('hidden');
          hasErrors = true;
        }

        // Price
        const priceVal = priceInput.value;
        const isPriceValid = /^([1-9]\d{0,5})(?:\.\d{1,2})?$/.test(priceVal) || /^0(\.\d{1,2})?$/.test(priceVal);
        if (!priceVal || !isPriceValid || parseFloat(priceVal) > 500000) {
          priceError.textContent = 'Invalid price! Use format like 100.99, max PKR500,000.';
          priceError.classList.remove('hidden');
          priceInput.classList.add('border-red-500');
          hasErrors = true;
        }

        if (hasErrors) {
          e.preventDefault();
        }
      });
    });
  </script>
</body>

</html>