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

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Initialize response variables
  $success = false;
  $message = '';
  $hotel_id = null;

  try {
    // Get form data
    $hotel_name = $_POST['hotel_name'] ?? '';
    $location = $_POST['location'] ?? '';
    $price = intval($_POST['price'] ?? 0);
    $rating = intval($_POST['rating'] ?? 5);
    $description = $_POST['description'] ?? '';
    $room_count = intval($_POST['room_count'] ?? 0);
    $amenities = isset($_POST['amenities']) ? implode(',', $_POST['amenities']) : '';

    // Validate required fields
    if (empty($hotel_name) || empty($description) || $price <= 0 || $room_count <= 0) {
      throw new Exception("All required fields must be filled out properly.");
    }

    // Validate hotel name (letters only)
    if (!preg_match('/^[A-Za-z\s]{1,25}$/', $hotel_name)) {
      throw new Exception("Hotel name must contain only letters and be 25 characters or less.");
    }

    // Validate price
    if ($price > 50000) {
      throw new Exception("Maximum price is $50,000.");
    }

    // Validate room count
    if ($room_count < 1 || $room_count > 10) {
      throw new Exception("Room count must be between 1 and 10.");
    }

    // Start transaction
    $conn->begin_transaction();

    // Insert hotel data
    $sql = "INSERT INTO hotels (hotel_name, location, price, rating, description, room_count, amenities) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
      throw new Exception("Database error: " . $conn->error);
    }

    $stmt->bind_param("ssiisis", $hotel_name, $location, $price, $rating, $description, $room_count, $amenities);

    if (!$stmt->execute()) {
      throw new Exception("Error saving hotel: " . $stmt->error);
    }

    $hotel_id = $conn->insert_id;
    $stmt->close();

    // Create hotel rooms
    for ($i = 1; $i <= $room_count; $i++) {
      $room_id = "r" . $i;
      $roomSql = "INSERT INTO hotel_rooms (hotel_id, room_id, status) VALUES (?, ?, 'available')";
      $roomStmt = $conn->prepare($roomSql);

      if (!$roomStmt) {
        throw new Exception("Database error: " . $conn->error);
      }

      $roomStmt->bind_param("is", $hotel_id, $room_id);

      if (!$roomStmt->execute()) {
        throw new Exception("Error creating room: " . $roomStmt->error);
      }

      $roomStmt->close();
    }

    // Handle image uploads
    if (isset($_FILES['hotel_images']) && !empty($_FILES['hotel_images']['name'][0])) {
      $upload_dir = '../uploads/hotels/';

      // Create directory if it doesn't exist
      if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
      }

      // Create hotel-specific directory
      $hotel_dir = $upload_dir . $hotel_id . '/';
      if (!file_exists($hotel_dir)) {
        mkdir($hotel_dir, 0777, true);
      }

      $allowed_types = ['image/jpeg', 'image/png'];
      $max_size = 10 * 1024 * 1024; // 10MB
      $uploaded_images = [];

      // Process each uploaded image
      for ($i = 0; $i < count($_FILES['hotel_images']['name']); $i++) {
        $file_name = $_FILES['hotel_images']['name'][$i];
        $file_tmp = $_FILES['hotel_images']['tmp_name'][$i];
        $file_size = $_FILES['hotel_images']['size'][$i];
        $file_type = $_FILES['hotel_images']['type'][$i];

        // Skip if file is empty
        if (empty($file_name)) continue;

        // Validate file type
        if (!in_array($file_type, $allowed_types)) {
          throw new Exception("Invalid file type: {$file_name}. Only JPG/PNG allowed.");
        }

        // Validate file size
        if ($file_size > $max_size) {
          throw new Exception("File too large: {$file_name}. Max 10MB allowed.");
        }

        // Generate unique filename
        $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
        $new_filename = uniqid('hotel_') . '.' . $file_ext;
        $destination = $hotel_dir . $new_filename;

        // Move uploaded file
        if (move_uploaded_file($file_tmp, $destination)) {
          $uploaded_images[] = $new_filename;

          // Insert image record into database
          $imgSql = "INSERT INTO hotel_images (hotel_id, image_path) VALUES (?, ?)";
          $relative_path = 'uploads/hotels/' . $hotel_id . '/' . $new_filename;

          $imgStmt = $conn->prepare($imgSql);
          if (!$imgStmt) {
            throw new Exception("Database error: " . $conn->error);
          }

          $imgStmt->bind_param("is", $hotel_id, $relative_path);

          if (!$imgStmt->execute()) {
            throw new Exception("Error saving image: " . $imgStmt->error);
          }

          $imgStmt->close();
        } else {
          throw new Exception("Failed to upload {$file_name}");
        }
      }
    }

    // Commit transaction
    $conn->commit();

    $success = true;
    $message = "Hotel added successfully!";

    // Redirect after success
    echo "<script>
            alert('{$message}');
            window.location.href = 'view-hotels.php';
          </script>";
    exit;
  } catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    $message = $e->getMessage();

    echo "<script>alert('Error: {$message}');</script>";
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Hotel | UmrahFlights Admin</title>
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body class="bg-gray-100 min-h-screen flex">
  <!-- Sidebar -->
  <?php include 'includes/sidebar.php'; ?>

  <!-- Main Content -->
  <div class="main flex-1 flex flex-col">
    <!-- Navbar -->
    <div class="bg-white shadow-md py-4 px-6 flex justify-between items-center">
      <button class="md:hidden text-gray-800" id="menu-btn">
        <i class="fas fa-bars"></i>
      </button>
      <h1 class="text-xl font-semibold">
        <i class="text-blue-600 fas fa-hotel mx-2"></i> Add New Hotel
      </h1>
    </div>

    <!-- Form Container -->
    <div class="overflow-auto container mx-auto px-4 py-8">
      <div class="max-w-3xl mx-auto bg-white p-8 rounded-lg shadow-lg">
        <div class="mb-6">
          <h2 class="text-2xl font-bold text-blue-600">
            <i class="fas fa-plus-circle mr-2"></i>New Hotel Information
          </h2>
          <p class="text-gray-600 mt-2">Add a new hotel to your Umrah package inventory</p>
        </div>

        <form action="" method="POST" enctype="multipart/form-data" class="space-y-6">
          <!-- Hotel Image Upload -->
          <div class="mb-6">
            <label class="block text-gray-700 font-semibold mb-2">Hotel Images *</label>
            <div class="flex items-center justify-center w-full">
              <label class="flex flex-col w-full h-32 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 relative">
                <div id="upload-area" class="flex flex-col items-center justify-center pt-7">
                  <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                  <p class="text-sm text-gray-500">Click to upload or drag and drop</p>
                  <p class="text-xs text-gray-500">PNG, JPG up to 10MB</p>
                </div>
                <input type="file" id="hotel_images" class="hidden" multiple accept="image/jpeg,image/png" name="hotel_images[]">
              </label>
            </div>
            <!-- File validation error -->
            <div id="file-error" class="text-red-500 text-xs mt-1 hidden"></div>
            <!-- Selected files list -->
            <div id="file-list" class="mt-3 space-y-2 hidden">
              <p class="text-sm font-medium text-gray-700">Selected Files:</p>
              <ul id="selected-files" class="text-xs text-gray-500 space-y-1"></ul>
              <p id="size-warning" class="text-xs text-red-500 hidden"></p>
            </div>
          </div>

          <!-- Hotel Basic Information -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="mb-4">
              <label class="block text-gray-700 font-semibold mb-2">Hotel Name *</label>
              <input type="text" name="hotel_name" id="hotel_name"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Enter hotel name (letters only)"
                maxlength="25"
                required>
              <div id="hotel_name_error" class="text-red-500 text-xs mt-1 hidden">
                Hotel name must contain only letters (A-Z) and be 25 characters or less
              </div>
              <div class="text-xs text-gray-500 mt-1">
                <span id="hotel_name_counter">0</span>/25 characters
              </div>
            </div>

            <div>
              <label class="block text-gray-700 font-semibold mb-2">Location *</label>
              <select name="location"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                required>
                <option value="">Select Location</option>
                <option value="makkah">Makkah</option>
                <option value="madinah">Madinah</option>
              </select>
            </div>
          </div>

          <!-- Room Count Field -->
          <div class="mb-4">
            <label class="block text-gray-700 font-semibold mb-2">Number of Rooms *</label>
            <input type="number" name="room_count" id="room_count" min="1" max="10"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="Enter number of rooms (1-10)"
              required>
            <div id="room_count_error" class="text-red-500 text-xs mt-1 hidden">
              Please enter a number between 1 and 10
            </div>
            <p class="text-sm text-gray-500 mt-1">Room IDs (r1, r2, etc.) will be automatically generated based on this count</p>
          </div>

          <!-- Price and Rating -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="mb-4">
              <label class="block text-gray-700 font-semibold mb-2">Price per Night (PKR) *</label>
              <input type="number" name="price" id="price"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Enter price (PKR1-PJR50,000)"
                required>
              <div id="price_error" class="text-red-500 text-xs mt-1 hidden">
                Maximum price is RS50,000
              </div>
            </div>

            <div>
              <label class="block text-gray-700 font-semibold mb-2">Hotel Rating</label>
              <div class="flex items-center space-x-2">
                <select name="rating"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                  <option value="5">5 Stars</option>
                  <option value="4">4 Stars</option>
                  <option value="3">3 Stars</option>
                  <option value="2">2 Stars</option>
                  <option value="1">1 Star</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Hotel Description -->
          <div class="mb-4">
            <label class="block text-gray-700 font-semibold mb-2">Hotel Description *</label>
            <textarea name="description" id="description" rows="6"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="Enter hotel description (200 words maximum)"
              required></textarea>
            <div id="desc_error" class="text-red-500 text-xs mt-1 hidden">
              Maximum 200 words reached (backspace to edit)
            </div>
            <div class="text-xs text-gray-500 mt-1">
              <span id="word_count">0</span>/200 words
              <span id="limit_reached" class="text-red-500 font-semibold hidden"> (Limit reached)</span>
            </div>
          </div>

          <!-- Amenities -->
          <div>
            <label class="block text-gray-700 font-semibold mb-2">Hotel Amenities</label>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
              <label class="flex items-center space-x-2">
                <input type="checkbox" name="amenities[]" value="wifi" class="text-blue-600">
                <span>Free WiFi</span>
              </label>
              <label class="flex items-center space-x-2">
                <input type="checkbox" name="amenities[]" value="parking" class="text-blue-600">
                <span>Parking</span>
              </label>
              <label class="flex items-center space-x-2">
                <input type="checkbox" name="amenities[]" value="restaurant" class="text-blue-600">
                <span>Restaurant</span>
              </label>
              <label class="flex items-center space-x-2">
                <input type="checkbox" name="amenities[]" value="gym" class="text-blue-600">
                <span>Gym</span>
              </label>
              <label class="flex items-center space-x-2">
                <input type="checkbox" name="amenities[]" value="pool" class="text-blue-600">
                <span>Swimming Pool</span>
              </label>
              <label class="flex items-center space-x-2">
                <input type="checkbox" name="amenities[]" value="ac" class="text-blue-600">
                <span>Air Conditioning</span>
              </label>
              <label class="flex items-center space-x-2">
                <input type="checkbox" name="amenities[]" value="room_service" class="text-blue-600">
                <span>Room Service</span>
              </label>
              <label class="flex items-center space-x-2">
                <input type="checkbox" name="amenities[]" value="spa" class="text-blue-600">
                <span>Spa</span>
              </label>
            </div>
          </div>

          <!-- Submit Buttons -->
          <div class="flex gap-4 pt-4 border-t border-gray-200">
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition duration-200">
              <i class="fas fa-save mr-2"></i>Save Hotel
            </button>
            <button type="button" onclick="window.location.href='view-hotels.php'" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition duration-200">
              <i class="fas fa-times mr-2"></i>Cancel
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script>
    // File Upload Handling
    document.getElementById('hotel_images').addEventListener('change', function(e) {
      const MAX_SIZE = 10 * 1024 * 1024; // 10MB
      const fileList = document.getElementById('selected-files');
      const uploadArea = document.getElementById('upload-area');
      const fileError = document.getElementById('file-error');
      const sizeWarning = document.getElementById('size-warning');
      const filesContainer = document.getElementById('file-list');

      fileList.innerHTML = '';
      fileError.classList.add('hidden');
      sizeWarning.classList.add('hidden');

      if (this.files.length > 0) {
        filesContainer.classList.remove('hidden');
        uploadArea.classList.add('hidden');

        let totalSize = 0;
        let hasInvalidFiles = false;

        Array.from(this.files).forEach((file, index) => {
          // Validate file type
          if (!['image/jpeg', 'image/png'].includes(file.type)) {
            fileError.textContent = `Invalid file type: ${file.name}. Only JPG/PNG allowed.`;
            fileError.classList.remove('hidden');
            hasInvalidFiles = true;
            return;
          }

          // Validate file size
          if (file.size > MAX_SIZE) {
            fileError.textContent = `File too large: ${file.name} (${(file.size/1024/1024).toFixed(1)}MB). Max 10MB allowed.`;
            fileError.classList.remove('hidden');
            hasInvalidFiles = true;
            return;
          }

          totalSize += file.size;

          // Add to file list
          const listItem = document.createElement('li');
          listItem.className = 'flex items-center justify-between';
          listItem.innerHTML = `
            <span class="truncate w-40">${index + 1}. ${file.name}</span>
            <span class="text-gray-400">${(file.size/1024/1024).toFixed(1)}MB</span>
            <button type="button" onclick="removeFile(${index})" class="text-red-400 hover:text-red-600">
              <i class="fas fa-times"></i>
            </button>
          `;
          fileList.appendChild(listItem);
        });

        // Show total size warning if over 30MB combined
        if (totalSize > 30 * 1024 * 1024) {
          sizeWarning.textContent = `Total size: ${(totalSize/1024/1024).toFixed(1)}MB (recommended under 30MB)`;
          sizeWarning.classList.remove('hidden');
        }

        if (hasInvalidFiles) {
          this.value = ''; // Clear invalid files
        }
      } else {
        filesContainer.classList.add('hidden');
        uploadArea.classList.remove('hidden');
      }
    });

    function removeFile(index) {
      const input = document.getElementById('hotel_images');
      const files = Array.from(input.files);
      files.splice(index, 1);

      // Create new DataTransfer to update files
      const dataTransfer = new DataTransfer();
      files.forEach(file => dataTransfer.items.add(file));
      input.files = dataTransfer.files;

      // Trigger change event to update UI
      const event = new Event('change');
      input.dispatchEvent(event);
    }

    // Drag and drop functionality
    const dropArea = document.querySelector('label[for="hotel_images"]');
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
      dropArea.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
      e.preventDefault();
      e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
      dropArea.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
      dropArea.addEventListener(eventName, unhighlight, false);
    });

    function highlight() {
      dropArea.classList.add('border-blue-500', 'bg-blue-50');
    }

    function unhighlight() {
      dropArea.classList.remove('border-blue-500', 'bg-blue-50');
    }

    dropArea.addEventListener('drop', handleDrop, false);

    function handleDrop(e) {
      const dt = e.dataTransfer;
      const input = document.getElementById('hotel_images');
      input.files = dt.files;
      const event = new Event('change');
      input.dispatchEvent(event);
    }

    // Hotel Name Validation
    document.getElementById('hotel_name').addEventListener('input', function() {
      const errorElement = document.getElementById('hotel_name_error');
      const counterElement = document.getElementById('hotel_name_counter');

      // Remove any numbers or special characters (keep only letters A-Z and spaces)
      this.value = this.value.replace(/[^A-Za-z\s]/g, '');

      // Update character counter
      const currentLength = this.value.length;
      counterElement.textContent = currentLength;

      // Enforce 25 character limit
      if (currentLength > 25) {
        this.value = this.value.substring(0, 25);
        counterElement.textContent = 25;
      }

      // Show error if invalid characters were attempted
      if (/[^A-Za-z\s]/.test(this.value)) {
        errorElement.classList.remove('hidden');
        this.setCustomValidity('Only letters allowed');
      } else {
        errorElement.classList.add('hidden');
        this.setCustomValidity('');
      }
    });

    // Room Count Validation
    document.getElementById('room_count').addEventListener('input', function() {
      const errorElement = document.getElementById('room_count_error');
      const value = parseInt(this.value) || 0;

      // Validate range
      if (value < 1 || value > 10) {
        errorElement.classList.remove('hidden');
        this.setCustomValidity('Number must be between 1-10');

        // Auto-correct out-of-range values
        if (value < 1) this.value = 1;
        if (value > 10) this.value = 10;
      } else {
        errorElement.classList.add('hidden');
        this.setCustomValidity('');
      }
    });

    // Price Validation
    document.getElementById('price').addEventListener('input', function() {
      const errorElement = document.getElementById('price_error');
      let value = parseInt(this.value) || 0;

      // Enforce maximum limit
      if (value > 50000) {
        value = 50000;
        this.value = value;
        errorElement.classList.remove('hidden');
      } else {
        errorElement.classList.add('hidden');
      }

      // Enforce minimum $1
      if (value < 1 && this.value !== '') {
        this.value = 1;
        errorElement.textContent = "Price must be at least $1";
        errorElement.classList.remove('hidden');
      }
    });

    // Description Word Count
    document.getElementById('description').addEventListener('input', function() {
      const errorElement = document.getElementById('desc_error');
      const wordCountElement = document.getElementById('word_count');
      const limitReachedElement = document.getElementById('limit_reached');

      // Count words (including hyphenated words and contractions)
      const words = this.value.match(/\b[\w'-]+\b/g) || [];
      const wordCount = words.length;
      wordCountElement.textContent = wordCount;

      // Check word limit
      if (wordCount >= 200) {
        // Trim to exactly 200 words
        if (wordCount > 200) {
          const trimmedText = words.slice(0, 200).join(' ');
          this.value = trimmedText;
          wordCountElement.textContent = 200;
        }
        errorElement.classList.remove('hidden');
        limitReachedElement.classList.remove('hidden');
        this.classList.add('border-red-300');
      } else {
        errorElement.classList.add('hidden');
        limitReachedElement.classList.add('hidden');
        this.classList.remove('border-red-300');
      }
    });
  </script>
</body>

</html>