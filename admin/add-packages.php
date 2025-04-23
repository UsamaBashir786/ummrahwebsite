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
  $package_image = '';
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
      $upload_dir = '../uploads/';
      $upload_path = $upload_dir . $filename;

      // Ensure upload directory exists
      if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
      }

      if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        $errors[] = "Failed to upload image.";
      } else {
        $package_image = 'Uploads/' . $filename; // Relative path for database
      }
    }
  } else {
    $errors[] = "Package image is required.";
  }

  // If no errors, insert into database
  if (empty($errors)) {
    $stmt = $conn->prepare("
      INSERT INTO umrah_packages (package_type, title, description, flight_class, inclusions, price, package_image)
      VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
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

    if ($stmt->execute()) {
      $_SESSION['success'] = "Package created successfully!";
      header('Location: view-packages.php');
      exit;
    } else {
      $errors[] = "Error creating package: " . $conn->error;
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
  <title>Add Umrah Package | UmrahFlights</title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="assets/css/index.css">
</head>

<body>
  <?php include 'includes/sidebar.php'; ?>
  <!-- Main Content -->
  <div class="main-content col-md-12">
    <!-- Top Navbar -->
    <nav class="navbar navbar-expand-lg top-navbar mb-4">
      <div class="container-fluid">
        <button id="sidebarToggle" class="btn d-lg-none">
          <i class="fas fa-bars"></i>
        </button>
        <h4 class="mb-0 ms-2">Add New Package</h4>

        <div class="d-flex align-items-center">
          <div class="position-relative me-3">
            <button class="btn position-relative" id="notificationBtn">
              <i class="fas fa-bell fs-5"></i>
              <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                3
              </span>
            </button>
          </div>

          <div class="dropdown">
            <button class="btn dropdown-toggle d-flex align-items-center" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
              <div class="rounded-circle overflow-hidden me-2" style="width: 32px; height: 32px;">
                <img src="../assets/img/admin.jpg" alt="Admin User" class="img-fluid">
              </div>
              <span class="d-none d-md-inline">Admin User</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
              <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
            </ul>
          </div>
        </div>
      </div>
    </nav>

    <!-- Messages -->
    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
          <?php foreach ($errors as $error): ?>
            <li><?php echo htmlspecialchars($error); ?></li>
          <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($success); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <!-- Form Section -->
    <div class="container mx-auto px-4 py-8">
      <div class="max-w-2xl mx-auto bg-white p-8 rounded-lg shadow-lg">
        <h2 class="text-2xl font-bold text-primary mb-6">Create New Umrah Package</h2>
        <form action="" method="POST" enctype="multipart/form-data">
          <!-- Package Type -->
          <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">Package Type</label>
            <select name="package_type" class="form-select w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-primary" required>
              <option value="single">Single Umrah Package</option>
              <option value="group">Group Umrah Package</option>
              <option value="vip">VIP Umrah Package</option>
            </select>
          </div>

          <!-- Package Title -->
          <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">Package Title</label>
            <input
              type="text"
              id="packageTitle"
              name="title"
              class="form-control w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-primary"
              required
              value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>"
              oninput="validateTitle()">
            <small id="error-message" class="text-danger"></small>
          </div>

          <script>
            function validateTitle() {
              const input = document.getElementById("packageTitle");
              const errorMessage = document.getElementById("error-message");
              const regex = /^[A-Za-z ]{0,35}$/;

              if (regex.test(input.value)) {
                errorMessage.textContent = "";
              } else {
                errorMessage.textContent = "Only English letters and spaces are allowed, no numbers or special characters!";
                input.value = input.value.replace(/[^A-Za-z ]/g, "");
              }

              if (input.value.length > 35) {
                input.value = input.value.slice(0, 35);
              }
            }
          </script>

          <!-- Package Description -->
          <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">Description</label>
            <textarea
              id="packageDescription"
              name="description"
              rows="3"
              class="form-control w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-primary"
              required
              oninput="validateDescription()"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
            <small id="desc-error-message" class="text-danger"></small>
          </div>

          <script>
            function validateDescription() {
              const textarea = document.getElementById("packageDescription");
              const errorMessage = document.getElementById("desc-error-message");
              const maxWords = 200;

              const words = textarea.value.trim().split(/\s+/);

              if (words.length > maxWords) {
                errorMessage.textContent = "Description cannot exceed 200 words! Please remove extra words.";
                textarea.value = words.slice(0, maxWords).join(" ");
              } else {
                errorMessage.textContent = "";
              }
            }
          </script>

          <!-- Flight Class -->
          <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">Flight Class</label>
            <select name="flight_class" class="form-select w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-primary" required>
              <option value="economy" <?php echo (isset($_POST['flight_class']) && $_POST['flight_class'] === 'economy') ? 'selected' : ''; ?>>Economy</option>
              <option value="business" <?php echo (isset($_POST['flight_class']) && $_POST['flight_class'] === 'business') ? 'selected' : ''; ?>>Business</option>
              <option value="first" <?php echo (isset($_POST['flight_class']) && $_POST['flight_class'] === 'first') ? 'selected' : ''; ?>>First Class</option>
            </select>
          </div>

          <!-- Package Inclusions -->
          <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">Package Inclusions</label>
            <div class="space-y-2">
              <label class="flex items-center">
                <input type="checkbox" name="inclusions[]" value="flight" class="form-check-input mr-2" <?php echo (isset($_POST['inclusions']) && in_array('flight', $_POST['inclusions'])) ? 'checked' : ''; ?>>
                Flight
              </label>
              <label class="flex items-center">
                <input type="checkbox" name="inclusions[]" value="hotel" class="form-check-input mr-2" <?php echo (isset($_POST['inclusions']) && in_array('hotel', $_POST['inclusions'])) ? 'checked' : ''; ?>>
                Hotel
              </label>
              <label class="flex items-center">
                <input type="checkbox" name="inclusions[]" value="transport" class="form-check-input mr-2" <?php echo (isset($_POST['inclusions']) && in_array('transport', $_POST['inclusions'])) ? 'checked' : ''; ?>>
                Transport
              </label>
              <label class="flex items-center">
                <input type="checkbox" name="inclusions[]" value="guide" class="form-check-input mr-2" <?php echo (isset($_POST['inclusions']) && in_array('guide', $_POST['inclusions'])) ? 'checked' : ''; ?>>
                Guide
              </label>
              <label class="flex items-center">
                <input type="checkbox" name="inclusions[]" value="vip_services" class="form-check-input mr-2" <?php echo (isset($_POST['inclusions']) && in_array('vip_services', $_POST['inclusions'])) ? 'checked' : ''; ?>>
                VIP Services
              </label>
            </div>
          </div>

          <!-- Price -->
          <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">Price (PKR)</label>
            <input type="text" name="price" id="price" class="form-control w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-primary" required value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>">
            <p id="priceError" class="text-danger text-xs mt-1 hidden">Invalid price! Please enter a valid number (e.g., 100.99).</p>
          </div>

          <script>
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
                priceInput.classList.add('border-danger');
                isInputValid = false;
              } else if (value && !isValid) {
                priceError.classList.remove('hidden');
                priceInput.classList.add('border-danger');
                isInputValid = false;
              } else {
                priceError.classList.add('hidden');
                priceInput.classList.remove('border-danger');
                isInputValid = true;
              }
            }

            priceInput.addEventListener('keydown', function(e) {
              if (!isInputValid && e.key !== 'Backspace' && e.key !== 'Delete') {
                e.preventDefault();
              }
            });
          </script>

          <!-- Package Image -->
          <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">Package Image</label>
            <input type="file" name="package_image" id="package_image" accept="image/*" class="form-control w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-primary" required>
            <p id="imageError" class="text-danger text-xs mt-1 hidden">File size must be 2MB or less.</p>
          </div>

          <script>
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
          </script>

          <!-- Submit Button -->
          <div class="flex justify-end">
            <button type="submit" class="btn btn-primary px-6 py-3 rounded-lg hover:bg-primary-dark transition-all duration-300">
              Create Package
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Bootstrap 5 JS Bundle with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Custom JavaScript -->
  <script src="assets/js/index.js"></script>
</body>

</html>