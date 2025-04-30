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

// Initialize variables for form data and errors
$errors = [];
$success = '';

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

  // Validate and Handle Image Upload
  $package_image = 'default-package.jpg'; // Default fallback
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
          $package_image = 'Uploads/' . $filename; // Store relative path in database
          // Set file permissions
          chmod($upload_path, 0644);
        }
      }
    }
  }

  // If no errors, insert into database
  if (empty($errors)) {
    $stmt = $conn->prepare("
            INSERT INTO umrah_packages (package_type, title, description, flight_class, inclusions, price, package_image)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
    if (!$stmt) {
      $errors[] = "Database query preparation failed: " . $conn->error;
      error_log("Database query preparation failed: " . $conn->error);
    } else {
      $stmt->bind_param(
        "sssssds",
        $package_type,
        $title,
        $description,
        $flight_class,
        $inclusions_json,
        $price,
        $package_image
      );
      if (!$stmt->execute()) {
        $errors[] = "Error creating package: " . $stmt->error;
        error_log("Error creating package: " . $stmt->error);
      } else {
        $_SESSION['success'] = "Package created successfully!";
        header('Location: view-packages.php');
        exit;
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
  <title>Add Umrah Package | UmrahFlights</title>
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
            <i class="fas fa-bars text-xl"></i>
          </button>
          <h1 id="dashboardHeader" class="text-lg font-semibold text-gray-800 cursor-pointer hover:text-indigo-600">
            <i class="fas fa-box-open text-indigo-600 mr-2"></i>Add New Package
          </h1>
        </div>
        <div class="flex items-center space-x-4">
          <!-- User Dropdown -->
          <div class="relative">
            <button id="userDropdownButton" class="flex items-center space-x-2 text-gray-700 hover:bg-indigo-50 rounded-lg px-3 py-2 focus:outline-none">
              <div class="rounded-full overflow-hidden" style="width: 32px; height: 32px;">
                <div class="bg-gray-200 w-full h-full"></div>
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
      <div id="errorAlert" class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-6 relative">
        <ul class="mb-0">
          <?php foreach ($errors as $error): ?>
            <li><?php echo htmlspecialchars($error); ?></li>
          <?php endforeach; ?>
        </ul>
        <button type="button" class="absolute top-2 right-2 text-red-500 hover:text-red-700" onclick="this.parentElement.classList.add('hidden')">
          <i class="fas fa-times"></i>
        </button>
      </div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
      <div id="successAlert" class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-6 relative">
        <?php echo htmlspecialchars($success); ?>
        <button type="button" class="absolute top-2 right-2 text-green-500 hover:text-green-700" onclick="this.parentElement.classList.add('hidden')">
          <i class="fas fa-times"></i>
        </button>
      </div>
    <?php endif; ?>

    <!-- Form Section -->
    <div class="bg-white shadow-lg rounded-lg p-6 max-w-2xl mx-auto">
      <h2 class="text-xl font-semibold text-indigo-600 mb-6">
        <i class="fas fa-box-open mr-2"></i>Create New Umrah Package
      </h2>
      <form action="" method="POST" enctype="multipart/form-data" class="space-y-6">
        <!-- Package Type -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Package Type *</label>
          <select name="package_type" class="block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500" required>
            <option value="single" <?php echo (isset($_POST['package_type']) && $_POST['package_type'] === 'single') ? 'selected' : ''; ?>>Single Umrah Package</option>
            <option value="group" <?php echo (isset($_POST['package_type']) && $_POST['package_type'] === 'group') ? 'selected' : ''; ?>>Group Umrah Package</option>
            <option value="vip" <?php echo (isset($_POST['package_type']) && $_POST['package_type'] === 'vip') ? 'selected' : ''; ?>>VIP Umrah Package</option>
          </select>
        </div>

        <!-- Package Title -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Package Title *</label>
          <input
            type="text"
            id="packageTitle"
            name="title"
            class="block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500"
            required
            value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>"
            oninput="validateTitle()">
          <small id="error-message" class="text-red-500 text-xs mt-1"></small>
        </div>

        <!-- Package Description -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Description *</label>
          <textarea
            id="packageDescription"
            name="description"
            rows="3"
            class="block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500"
            required
            oninput="validateDescription()"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
          <small id="desc-error-message" class="text-red-500 text-xs mt-1"></small>
        </div>

        <!-- Flight Class -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Flight Class *</label>
          <select name="flight_class" class="block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500" required>
            <option value="economy" <?php echo (isset($_POST['flight_class']) && $_POST['flight_class'] === 'economy') ? 'selected' : ''; ?>>Economy</option>
            <option value="business" <?php echo (isset($_POST['flight_class']) && $_POST['flight_class'] === 'business') ? 'selected' : ''; ?>>Business</option>
            <option value="first" <?php echo (isset($_POST['flight_class']) && $_POST['flight_class'] === 'first') ? 'selected' : ''; ?>>First Class</option>
          </select>
        </div>

        <!-- Package Inclusions -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Package Inclusions</label>
          <div class="space-y-2">
            <label class="flex items-center">
              <input type="checkbox" name="inclusions[]" value="flight" class="text-indigo-600 focus:ring-indigo-500 mr-2" <?php echo (isset($_POST['inclusions']) && in_array('flight', $_POST['inclusions'])) ? 'checked' : ''; ?>>
              <span class="text-sm text-gray-700">Flight</span>
            </label>
            <label class="flex items-center">
              <input type="checkbox" name="inclusions[]" value="hotel" class="text-indigo-600 focus:ring-indigo-500 mr-2" <?php echo (isset($_POST['inclusions']) && in_array('hotel', $_POST['inclusions'])) ? 'checked' : ''; ?>>
              <span class="text-sm text-gray-700">Hotel</span>
            </label>
            <label class="flex items-center">
              <input type="checkbox" name="inclusions[]" value="transport" class="text-indigo-600 focus:ring-indigo-500 mr-2" <?php echo (isset($_POST['inclusions']) && in_array('transport', $_POST['inclusions'])) ? 'checked' : ''; ?>>
              <span class="text-sm text-gray-700">Transport</span>
            </label>
            <label class="flex items-center">
              <input type="checkbox" name="inclusions[]" value="guide" class="text-indigo-600 focus:ring-indigo-500 mr-2" <?php echo (isset($_POST['inclusions']) && in_array('guide', $_POST['inclusions'])) ? 'checked' : ''; ?>>
              <span class="text-sm text-gray-700">Guide</span>
            </label>
            <label class="flex items-center">
              <input type="checkbox" name="inclusions[]" value="vip_services" class="text-indigo-600 focus:ring-indigo-500 mr-2" <?php echo (isset($_POST['inclusions']) && in_array('vip_services', $_POST['inclusions'])) ? 'checked' : ''; ?>>
              <span class="text-sm text-gray-700">VIP Services</span>
            </label>
          </div>
        </div>

        <!-- Price -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Price (PKR) *</label>
          <input type="text" name="price" id="price" class="block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500" required value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>">
          <p id="priceError" class="text-red-500 text-xs mt-1 hidden">Invalid price! Please enter a valid number (e.g., 100.99, max PKR500,000).</p>
        </div>

        <!-- Package Image -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Package Image (Optional)</label>
          <input type="file" name="package_image" id="package_image" accept="image/*" class="block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500">
          <p id="imageError" class="text-red-500 text-xs mt-1 hidden">File size must be 2MB or less.</p>
          <p class="text-sm text-gray-500 mt-1">If no image is uploaded, a default image will be used.</p>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end">
          <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
            <i class="fas fa-save mr-2"></i> Create Package
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
      if (!priceInput || !imageInput) {
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

      // Title validation
      function validateTitle() {
        const input = document.getElementById('packageTitle');
        const errorMessage = document.getElementById('error-message');
        const regex = /^[A-Za-z ]{0,35}$/;
        if (regex.test(input.value)) {
          errorMessage.textContent = '';
        } else {
          errorMessage.textContent = 'Only English letters and spaces are allowed, no numbers or special characters!';
          input.value = input.value.replace(/[^A-Za-z ]/g, '');
        }
        if (input.value.length > 35) {
          input.value = input.value.slice(0, 35);
        }
      }

      // Description validation
      function validateDescription() {
        const textarea = document.getElementById('packageDescription');
        const errorMessage = document.getElementById('desc-error-message');
        const maxWords = 200;
        const words = textarea.value.trim().split(/\s+/).filter(word => word.length > 0);
        if (words.length > maxWords) {
          errorMessage.textContent = 'Description cannot exceed 200 words! Please remove extra words.';
          textarea.value = words.slice(0, maxWords).join(' ');
        } else {
          errorMessage.textContent = '';
        }
      }

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
      }

      // Image validation
      if (imageInput && imageError) {
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
      }
    });
  </script>
</body>

</html>