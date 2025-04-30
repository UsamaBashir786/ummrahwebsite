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

// Use the $conn from config/db.php (MySQLi connection)

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
  $package['inclusions'] = json_decode($package['inclusions'], true);
} else {
  $_SESSION['error'] = "Package not found.";
  header('Location: view-packages.php');
  exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Validate Package Type
  $package_type = filter_input(INPUT_POST, 'package_type', FILTER_SANITIZE_STRING);
  if (!in_array($package_type, ['single', 'group', 'vip'])) {
    $errors[] = "Invalid package type.";
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

  // Validate Flight Class
  $flight_class = filter_input(INPUT_POST, 'flight_class', FILTER_SANITIZE_STRING);
  if (!in_array($flight_class, ['economy', 'business', 'first'])) {
    $errors[] = "Invalid flight class.";
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

  // Handle Image Upload (optional update)
  $package_image = $package['package_image'];
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
        mkdir($upload_dir, 0755, true);
      }

      if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        $errors[] = "Failed to upload image.";
      } else {
        // Delete old image if it exists
        if (file_exists($package_image)) {
          unlink($package_image);
        }
        $package_image = 'Uploads/' . $filename; // Relative path for database
      }
    }
  }

  // If no errors, update database
  if (empty($errors)) {
    $stmt = $conn->prepare("
      UPDATE umrah_packages
      SET package_type = ?, title = ?, description = ?, flight_class = ?, inclusions = ?, price = ?, package_image = ?
      WHERE id = ?
    ");
    $stmt->bind_param(
      "sssssdsi",
      $package_type,
      $title,
      $description,
      $flight_class,
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
      $errors[] = "Error updating package: " . $conn->error;
    }
    $stmt->close();
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
  <link rel="stylesheet" href="../src/output.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body class="bg-gray-100">
  <?php include 'includes/sidebar.php'; ?>

  <!-- Main Content -->
  <div class="ml-0 md:ml-64 mt-10 px-4 sm:px-6 lg:px-8 transition-all duration-300">
    <!-- Top Navbar -->
    <nav class="bg-white shadow-lg rounded-lg p-5 mb-6">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <button id="sidebarToggle" class="text-gray-500 hover:text-gray-700 focus:outline-none md:hidden">
            <i class="fas fa-bars text-xl"></i>
          </button>
          <h4 class="text-lg font-semibold text-gray-800">
            <i class="fas fa-box text-indigo-600 mr-2"></i> Edit Umrah Package
          </h4>
        </div>

        <div class="flex items-center space-x-4">
          <!-- Notification -->
          <div class="relative">
            <button class="flex items-center text-gray-500 hover:text-gray-700 focus:outline-none">
              <i class="fas fa-bell text-xl"></i>
              <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                3
              </span>
            </button>
          </div>

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
      <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow" role="alert">
        <div class="flex">
          <div class="py-1"><i class="fas fa-exclamation-circle text-red-500 mr-2"></i></div>
          <div>
            <ul class="list-disc ml-5">
              <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
          <button type="button" class="ml-auto -mx-1.5 -my-1.5 text-red-500 hover:text-red-900 focus:outline-none p-1.5" onclick="this.parentElement.parentElement.remove()">
            <span class="sr-only">Close</span>
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
      <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow" role="alert">
        <div class="flex">
          <div class="py-1"><i class="fas fa-check-circle text-green-500 mr-2"></i></div>
          <div><?php echo htmlspecialchars($success); ?></div>
          <button type="button" class="ml-auto -mx-1.5 -my-1.5 text-green-500 hover:text-green-900 focus:outline-none p-1.5" onclick="this.parentElement.parentElement.remove()">
            <span class="sr-only">Close</span>
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>
    <?php endif; ?>

    <!-- Form Section -->
    <div class="bg-white shadow-lg rounded-lg overflow-hidden mb-6">
      <div class="p-6">
        <div class="mb-6">
          <h2 class="text-xl font-semibold text-indigo-600">
            <i class="fas fa-edit mr-2"></i>Edit Umrah Package
          </h2>
          <p class="text-gray-500 mt-1">Update package details and offerings</p>
        </div>

        <form action="" method="POST" enctype="multipart/form-data" class="space-y-6">
          <!-- Package Type -->
          <div>
            <label for="package_type" class="block text-sm font-medium text-gray-700 mb-1">Package Type <span class="text-red-500">*</span></label>
            <select name="package_type" id="package_type" class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required>
              <option value="single" <?php echo $package['package_type'] === 'single' ? 'selected' : ''; ?>>Single Umrah Package</option>
              <option value="group" <?php echo $package['package_type'] === 'group' ? 'selected' : ''; ?>>Group Umrah Package</option>
              <option value="vip" <?php echo $package['package_type'] === 'vip' ? 'selected' : ''; ?>>VIP Umrah Package</option>
            </select>
          </div>

          <!-- Package Title -->
          <div>
            <label for="packageTitle" class="block text-sm font-medium text-gray-700 mb-1">Package Title <span class="text-red-500">*</span></label>
            <input
              type="text"
              id="packageTitle"
              name="title"
              class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
              required
              value="<?php echo htmlspecialchars($package['title']); ?>"
              oninput="validateTitle()">
            <div id="error-message" class="text-red-500 text-xs mt-1 hidden"></div>
          </div>

          <!-- Package Description -->
          <div>
            <label for="packageDescription" class="block text-sm font-medium text-gray-700 mb-1">Description <span class="text-red-500">*</span></label>
            <textarea
              id="packageDescription"
              name="description"
              rows="3"
              class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
              required
              oninput="validateDescription()"><?php echo htmlspecialchars($package['description']); ?></textarea>
            <div id="desc-error-message" class="text-red-500 text-xs mt-1 hidden"></div>
          </div>

          <!-- Flight Class -->
          <div>
            <label for="flight_class" class="block text-sm font-medium text-gray-700 mb-1">Flight Class <span class="text-red-500">*</span></label>
            <select name="flight_class" id="flight_class" class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required>
              <option value="economy" <?php echo $package['flight_class'] === 'economy' ? 'selected' : ''; ?>>Economy</option>
              <option value="business" <?php echo $package['flight_class'] === 'business' ? 'selected' : ''; ?>>Business</option>
              <option value="first" <?php echo $package['flight_class'] === 'first' ? 'selected' : ''; ?>>First Class</option>
            </select>
          </div>

          <!-- Package Inclusions -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Package Inclusions</label>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
              <label class="inline-flex items-center">
                <input type="checkbox" name="inclusions[]" value="flight" class="rounded text-indigo-600 focus:ring-indigo-500" <?php echo in_array('flight', $package['inclusions']) ? 'checked' : ''; ?>>
                <span class="ml-2">Flight</span>
              </label>
              <label class="inline-flex items-center">
                <input type="checkbox" name="inclusions[]" value="hotel" class="rounded text-indigo-600 focus:ring-indigo-500" <?php echo in_array('hotel', $package['inclusions']) ? 'checked' : ''; ?>>
                <span class="ml-2">Hotel</span>
              </label>
              <label class="inline-flex items-center">
                <input type="checkbox" name="inclusions[]" value="transport" class="rounded text-indigo-600 focus:ring-indigo-500" <?php echo in_array('transport', $package['inclusions']) ? 'checked' : ''; ?>>
                <span class="ml-2">Transport</span>
              </label>
              <label class="inline-flex items-center">
                <input type="checkbox" name="inclusions[]" value="guide" class="rounded text-indigo-600 focus:ring-indigo-500" <?php echo in_array('guide', $package['inclusions']) ? 'checked' : ''; ?>>
                <span class="ml-2">Guide</span>
              </label>
              <label class="inline-flex items-center">
                <input type="checkbox" name="inclusions[]" value="vip_services" class="rounded text-indigo-600 focus:ring-indigo-500" <?php echo in_array('vip_services', $package['inclusions']) ? 'checked' : ''; ?>>
                <span class="ml-2">VIP Services</span>
              </label>
            </div>
          </div>

          <!-- Price -->
          <div>
            <label for="price" class="block text-sm font-medium text-gray-700 mb-1">Price (PKR) <span class="text-red-500">*</span></label>
            <input
              type="text"
              name="price"
              id="price"
              class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
              required
              value="<?php echo htmlspecialchars($package['price']); ?>">
            <div id="priceError" class="text-red-500 text-xs mt-1 hidden">Invalid price! Please enter a valid number (e.g., 100.99).</div>
          </div>

          <!-- Package Image -->
          <div>
            <label for="package_image" class="block text-sm font-medium text-gray-700 mb-1">Package Image</label>
            <div class="mb-2">
              <img src="../<?php echo htmlspecialchars($package['package_image']); ?>" alt="Current Image" class="h-24 w-auto object-cover rounded-lg">
              <p class="text-xs text-gray-500 mt-1">Current image</p>
            </div>
            <div class="mt-2">
              <input
                type="file"
                name="package_image"
                id="package_image"
                accept="image/*"
                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
              <div id="imageError" class="text-red-500 text-xs mt-1 hidden">File size must be 2MB or less.</div>
              <p class="text-xs text-gray-500 mt-1">Leave blank to keep current image.</p>
            </div>
          </div>

          <!-- Submit Buttons -->
          <div class="flex flex-wrap gap-4 pt-6 border-t border-gray-200">
            <a href="view-packages.php" class="inline-flex items-center px-5 py-2.5 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
              <i class="fas fa-times mr-2"></i>Cancel
            </a>
            <button type="submit" class="inline-flex items-center px-5 py-2.5 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
              <i class="fas fa-save mr-2"></i>Update Package
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script>
    // Form validation scripts
    function validateTitle() {
      const input = document.getElementById("packageTitle");
      const errorMessage = document.getElementById("error-message");
      const regex = /^[A-Za-z ]{0,35}$/;

      if (regex.test(input.value)) {
        errorMessage.textContent = "";
        errorMessage.classList.add("hidden");
      } else {
        errorMessage.textContent = "Only English letters and spaces are allowed, no numbers or special characters!";
        errorMessage.classList.remove("hidden");
        input.value = input.value.replace(/[^A-Za-z ]/g, "");
      }

      if (input.value.length > 35) {
        input.value = input.value.slice(0, 35);
      }
    }

    function validateDescription() {
      const textarea = document.getElementById("packageDescription");
      const errorMessage = document.getElementById("desc-error-message");
      const maxWords = 200;

      const words = textarea.value.trim().split(/\s+/);

      if (words.length > maxWords) {
        errorMessage.textContent = "Description cannot exceed 200 words! Please remove extra words.";
        errorMessage.classList.remove("hidden");
        textarea.value = words.slice(0, maxWords).join(" ");
      } else {
        errorMessage.textContent = "";
        errorMessage.classList.add("hidden");
      }
    }

    // Price validation
    const priceInput = document.getElementById('price');
    const priceError = document.getElementById('priceError');
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
      const value = e.target.value;
      validatePrice(value);
    });

    function validatePrice(value) {
      const isValid = /^([1-9]\d{0,5})(?:\.\d{1,2})?$/.test(value) || /^0(\.\d{1,2})?$/.test(value);

      if (parseFloat(value) > maxPrice) {
        priceError.classList.remove('hidden');
        priceInput.classList.add('border-red-500');
        isInputValid = false;
      } else if (value && !isValid) {
        priceError.classList.remove('hidden');
        priceInput.classList.add('border-red-500');
        isInputValid = false;
      } else {
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

    // Image validation
    const imageInput = document.getElementById('package_image');
    const imageError = document.getElementById('imageError');

    imageInput.addEventListener('change', function(e) {
      const file = e.target.files[0];
      if (file) {
        const fileSize = file.size / 1024 / 1024;

        if (fileSize > 2) {
          imageError.classList.remove('hidden');
          imageInput.value = '';
        } else {
          imageError.classList.add('hidden');
        }
      }
    });

    // User Dropdown Toggle
    const userDropdownButton = document.getElementById('userDropdownButton');
    const userDropdownMenu = document.getElementById('userDropdownMenu');

    if (userDropdownButton && userDropdownMenu) {
      userDropdownButton.addEventListener('click', function() {
        userDropdownMenu.classList.toggle('hidden');
      });

      // Close dropdown when clicking outside
      document.addEventListener('click', function(event) {
        if (!userDropdownButton.contains(event.target) && !userDropdownMenu.contains(event.target)) {
          userDropdownMenu.classList.add('hidden');
        }
      });
    }

    // Sidebar Toggle (assuming sidebar toggle functionality from sidebar.php)
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebar-overlay');

    if (sidebarToggle && sidebar && sidebarOverlay) {
      sidebarToggle.addEventListener('click', function() {
        sidebar.classList.remove('-translate-x-full');
        sidebarOverlay.classList.remove('hidden');
      });
    }
  </script>
</body>

</html>