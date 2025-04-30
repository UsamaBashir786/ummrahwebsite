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

// Check if hotel ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  header('Location: view-hotels.php');
  exit;
}

$hotel_id = intval($_GET['id']);

// Fetch hotel data
$hotel = [];
$images = [];
$amenities = [];

try {
  // Get hotel basic info
  $stmt = $conn->prepare("SELECT * FROM hotels WHERE id = ?");
  $stmt->bind_param("i", $hotel_id);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows === 0) {
    throw new Exception("Hotel not found");
  }

  $hotel = $result->fetch_assoc();
  $stmt->close();

  // Get hotel images
  $stmt = $conn->prepare("SELECT * FROM hotel_images WHERE hotel_id = ? ORDER BY is_primary DESC");
  $stmt->bind_param("i", $hotel_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $images = $result->fetch_all(MYSQLI_ASSOC);
  $stmt->close();

  // Get current amenities (if any)
  if (!empty($hotel['amenities'])) {
    $amenities = explode(',', $hotel['amenities']);
  }
} catch (Exception $e) {
  echo "<script>alert('Error: {$e->getMessage()}'); window.location.href='view-hotels.php';</script>";
  exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $success = false;
  $message = '';

  try {
    // Get form data
    $hotel_name = $_POST['hotel_name'] ?? '';
    $location = $_POST['location'] ?? '';
    $price = intval($_POST['price'] ?? 0);
    $rating = intval($_POST['rating'] ?? 5);
    $description = $_POST['description'] ?? '';
    $room_count = intval($_POST['room_count'] ?? 0);
    $amenities = isset($_POST['amenities']) ? implode(',', $_POST['amenities']) : '';
    $delete_images = isset($_POST['delete_images']) ? $_POST['delete_images'] : [];

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
      throw new Exception("Maximum price is PKR50,000.");
    }

    // Validate room count
    if ($room_count < 1 || $room_count > 10) {
      throw new Exception("Room count must be between 1 and 10.");
    }

    // Start transaction
    $conn->begin_transaction();

    // Update hotel data
    $sql = "UPDATE hotels SET 
            hotel_name = ?, 
            location = ?, 
            price = ?, 
            rating = ?, 
            description = ?, 
            room_count = ?, 
            amenities = ?,
            updated_at = NOW()
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
      throw new Exception("Database error: " . $conn->error);
    }

    $stmt->bind_param("ssiisisi", $hotel_name, $location, $price, $rating, $description, $room_count, $amenities, $hotel_id);

    if (!$stmt->execute()) {
      throw new Exception("Error updating hotel: " . $stmt->error);
    }

    $stmt->close();

    // Handle image deletions
    if (!empty($delete_images)) {
      foreach ($delete_images as $image_id) {
        $image_id = intval($image_id);

        // Get image path first
        $stmt = $conn->prepare("SELECT image_path FROM hotel_images WHERE id = ? AND hotel_id = ?");
        $stmt->bind_param("ii", $image_id, $hotel_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
          $img = $result->fetch_assoc();
          $file_path = '../' . $img['image_path'];

          // Delete from database
          $stmt = $conn->prepare("DELETE FROM hotel_images WHERE id = ?");
          $stmt->bind_param("i", $image_id);
          $stmt->execute();
          $stmt->close();

          // Delete file
          if (file_exists($file_path)) {
            unlink($file_path);
          }
        }
      }
    }

    // Handle new image uploads
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
        $relative_path = 'uploads/hotels/' . $hotel_id . '/' . $new_filename;

        // Move uploaded file
        if (move_uploaded_file($file_tmp, $destination)) {
          $uploaded_images[] = $new_filename;

          // Set as primary only if this is the first image and no existing images
          $is_primary = (count($images) === 0 && $i === 0) ? 1 : 0;

          // Insert image record into database
          $imgSql = "INSERT INTO hotel_images (hotel_id, image_path, is_primary) VALUES (?, ?, ?)";

          $imgStmt = $conn->prepare($imgSql);
          if (!$imgStmt) {
            throw new Exception("Database error: " . $conn->error);
          }

          $imgStmt->bind_param("isi", $hotel_id, $relative_path, $is_primary);

          if (!$imgStmt->execute()) {
            throw new Exception("Error saving image: " . $imgStmt->error);
          }

          $imgStmt->close();
        } else {
          throw new Exception("Failed to upload {$file_name}");
        }
      }
    }

    // Handle primary image change
    if (isset($_POST['primary_image']) && is_numeric($_POST['primary_image'])) {
      $new_primary_id = intval($_POST['primary_image']);

      // First reset all to non-primary
      $stmt = $conn->prepare("UPDATE hotel_images SET is_primary = 0 WHERE hotel_id = ?");
      $stmt->bind_param("i", $hotel_id);
      $stmt->execute();
      $stmt->close();

      // Set new primary
      $stmt = $conn->prepare("UPDATE hotel_images SET is_primary = 1 WHERE id = ? AND hotel_id = ?");
      $stmt->bind_param("ii", $new_primary_id, $hotel_id);
      $stmt->execute();
      $stmt->close();
    }

    // Commit transaction
    $conn->commit();

    $success = true;
    $message = "Hotel updated successfully!";

    // Refresh data after update
    header("Location: edit-hotel.php?id=$hotel_id&success=1");
    exit;
  } catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    $message = $e->getMessage();
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Hotel | UmrahFlights Admin</title>
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
            <i class="fas fa-hotel text-indigo-600 mr-2"></i> Edit Hotel: <?= htmlspecialchars($hotel['hotel_name']) ?>
          </h4>
        </div>

        <div>
          <button onclick="window.location.href='view-hotels.php'" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
            <i class="fas fa-arrow-left mr-2"></i> Back to Hotels
          </button>
        </div>
      </div>
    </nav>

    <!-- Alerts -->
    <?php if (isset($_GET['success'])): ?>
      <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow" role="alert">
        <div class="flex">
          <div class="py-1"><i class="fas fa-check-circle text-green-500 mr-2"></i></div>
          <div>Hotel updated successfully!</div>
          <button type="button" class="ml-auto -mx-1.5 -my-1.5 text-green-500 hover:text-green-900 focus:outline-none p-1.5" onclick="this.parentElement.parentElement.remove()">
            <span class="sr-only">Close</span>
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>
    <?php endif; ?>

    <?php if (isset($message) && !empty($message)): ?>
      <div class="<?= $success ? 'bg-green-100 border-l-4 border-green-500 text-green-700' : 'bg-red-100 border-l-4 border-red-500 text-red-700' ?> p-4 mb-6 rounded shadow" role="alert">
        <div class="flex">
          <div class="py-1">
            <i class="fas <?= $success ? 'fa-check-circle text-green-500' : 'fa-exclamation-circle text-red-500' ?> mr-2"></i>
          </div>
          <div><?= htmlspecialchars($message) ?></div>
          <button type="button" class="ml-auto -mx-1.5 -my-1.5 <?= $success ? 'text-green-500 hover:text-green-900' : 'text-red-500 hover:text-red-900' ?> focus:outline-none p-1.5" onclick="this.parentElement.parentElement.remove()">
            <span class="sr-only">Close</span>
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>
    <?php endif; ?>

    <!-- Form Container -->
    <div class="bg-white shadow-lg rounded-lg overflow-hidden mb-6">
      <div class="p-6">
        <div class="mb-6">
          <h2 class="text-xl font-semibold text-indigo-600">
            <i class="fas fa-edit mr-2"></i>Edit Hotel Information
          </h2>
          <p class="text-gray-500 mt-1">Update the details for this hotel</p>
        </div>

        <form action="" method="POST" enctype="multipart/form-data" class="space-y-6">
          <!-- Existing Images -->
          <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Current Images</label>
            <?php if (count($images) > 0): ?>
              <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-4">
                <?php foreach ($images as $image): ?>
                  <div class="relative group">
                    <img src="../<?= htmlspecialchars($image['image_path']) ?>" alt="Hotel Image" class="w-full h-32 object-cover rounded-lg">
                    <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity rounded-lg">
                      <label class="flex items-center text-white cursor-pointer">
                        <input type="radio" name="primary_image" value="<?= $image['id'] ?>" <?= $image['is_primary'] ? 'checked' : '' ?> class="mr-1">
                        <span>Primary</span>
                      </label>
                      <label class="flex items-center text-red-300 hover:text-red-100 ml-4 cursor-pointer">
                        <input type="checkbox" name="delete_images[]" value="<?= $image['id'] ?>" class="mr-1">
                        <span>Delete</span>
                      </label>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <p class="text-gray-500 italic">No images uploaded yet</p>
            <?php endif; ?>

            <!-- New Image Upload -->
            <label class="block text-sm font-medium text-gray-700 mb-2">Add More Images</label>
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
            <div>
              <label for="hotel_name" class="block text-sm font-medium text-gray-700 mb-1">Hotel Name <span class="text-red-500">*</span></label>
              <input type="text" name="hotel_name" id="hotel_name"
                class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                value="<?= htmlspecialchars($hotel['hotel_name']) ?>"
                placeholder="Enter hotel name (letters only)"
                maxlength="25"
                required>
              <div id="hotel_name_error" class="text-red-500 text-xs mt-1 hidden">
                Hotel name must contain only letters (A-Z) and be 25 characters or less
              </div>
              <div class="text-xs text-gray-500 mt-1">
                <span id="hotel_name_counter"><?= strlen($hotel['hotel_name']) ?></span>/25 characters
              </div>
            </div>

            <div>
              <label for="location" class="block text-sm font-medium text-gray-700 mb-1">Location <span class="text-red-500">*</span></label>
              <select name="location"
                class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                required>
                <option value="">Select Location</option>
                <option value="makkah" <?= $hotel['location'] === 'makkah' ? 'selected' : '' ?>>Makkah</option>
                <option value="madinah" <?= $hotel['location'] === 'madinah' ? 'selected' : '' ?>>Madinah</option>
              </select>
            </div>
          </div>

          <!-- Room Count Field -->
          <div>
            <label for="room_count" class="block text-sm font-medium text-gray-700 mb-1">Number of Rooms <span class="text-red-500">*</span></label>
            <input type="number" name="room_count" id="room_count" min="1" max="10"
              class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
              value="<?= htmlspecialchars($hotel['room_count']) ?>"
              placeholder="Enter number of rooms (1-10)"
              required>
            <div id="room_count_error" class="text-red-500 text-xs mt-1 hidden">
              Please enter a number between 1 and 10
            </div>
            <p class="text-xs text-gray-500 mt-1">Room IDs (r1, r2, etc.) will be automatically generated based on this count</p>
          </div>

          <!-- Price and Rating -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label for="price" class="block text-sm font-medium text-gray-700 mb-1">Price per Night (PKR) <span class="text-red-500">*</span></label>
              <input type="number" name="price" id="price"
                class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                value="<?= htmlspecialchars($hotel['price']) ?>"
                placeholder="Enter price (PKR1-PKR50,000)"
                required>
              <div id="price_error" class="text-red-500 text-xs mt-1 hidden">
                Maximum price is PKR50,000
              </div>
            </div>

            <div>
              <label for="rating" class="block text-sm font-medium text-gray-700 mb-1">Hotel Rating</label>
              <select name="rating"
                class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                <option value="5" <?= $hotel['rating'] == 5 ? 'selected' : '' ?>>5 Stars</option>
                <option value="4" <?= $hotel['rating'] == 4 ? 'selected' : '' ?>>4 Stars</option>
                <option value="3" <?= $hotel['rating'] == 3 ? 'selected' : '' ?>>3 Stars</option>
                <option value="2" <?= $hotel['rating'] == 2 ? 'selected' : '' ?>>2 Stars</option>
                <option value="1" <?= $hotel['rating'] == 1 ? 'selected' : '' ?>>1 Star</option>
              </select>
            </div>
          </div>

          <!-- Hotel Description -->
          <div>
            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Hotel Description <span class="text-red-500">*</span></label>
            <textarea name="description" id="description" rows="6"
              class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
              placeholder="Enter hotel description (200 words maximum)"
              required><?= htmlspecialchars($hotel['description']) ?></textarea>
            <div id="desc_error" class="text-red-500 text-xs mt-1 hidden">
              Maximum 200 words reached (backspace to edit)
            </div>
            <div class="text-xs text-gray-500 mt-1">
              <span id="word_count"><?= str_word_count($hotel['description']) ?></span>/200 words
              <span id="limit_reached" class="text-red-500 font-semibold hidden"> (Limit reached)</span>
            </div>
          </div>

          <!-- Amenities -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Hotel Amenities</label>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
              <label class="inline-flex items-center">
                <input type="checkbox" name="amenities[]" value="wifi" class="rounded text-indigo-600 focus:ring-indigo-500" <?= in_array('wifi', $amenities) ? 'checked' : '' ?>>
                <span class="ml-2">Free WiFi</span>
              </label>
              <label class="inline-flex items-center">
                <input type="checkbox" name="amenities[]" value="parking" class="rounded text-indigo-600 focus:ring-indigo-500" <?= in_array('parking', $amenities) ? 'checked' : '' ?>>
                <span class="ml-2">Parking</span>
              </label>
              <label class="inline-flex items-center">
                <input type="checkbox" name="amenities[]" value="restaurant" class="rounded text-indigo-600 focus:ring-indigo-500" <?= in_array('restaurant', $amenities) ? 'checked' : '' ?>>
                <span class="ml-2">Restaurant</span>
              </label>
              <label class="inline-flex items-center">
                <input type="checkbox" name="amenities[]" value="gym" class="rounded text-indigo-600 focus:ring-indigo-500" <?= in_array('gym', $amenities) ? 'checked' : '' ?>>
                <span class="ml-2">Gym</span>
              </label>
              <label class="inline-flex items-center">
                <input type="checkbox" name="amenities[]" value="pool" class="rounded text-indigo-600 focus:ring-indigo-500" <?= in_array('pool', $amenities) ? 'checked' : '' ?>>
                <span class="ml-2">Swimming Pool</span>
              </label>
              <label class="inline-flex items-center">
                <input type="checkbox" name="amenities[]" value="ac" class="rounded text-indigo-600 focus:ring-indigo-500" <?= in_array('ac', $amenities) ? 'checked' : '' ?>>
                <span class="ml-2">Air Conditioning</span>
              </label>
              <label class="inline-flex items-center">
                <input type="checkbox" name="amenities[]" value="room_service" class="rounded text-indigo-600 focus:ring-indigo-500" <?= in_array('room_service', $amenities) ? 'checked' : '' ?>>
                <span class="ml-2">Room Service</span>
              </label>
              <label class="inline-flex items-center">
                <input type="checkbox" name="amenities[]" value="spa" class="rounded text-indigo-600 focus:ring-indigo-500" <?= in_array('spa', $amenities) ? 'checked' : '' ?>>
                <span class="ml-2">Spa</span>
              </label>
            </div>
          </div>

          <!-- Submit Buttons -->
          <div class="flex flex-wrap gap-4 pt-6 border-t border-gray-200">
            <button type="submit" class="inline-flex items-center px-5 py-2.5 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
              <i class="fas fa-save mr-2"></i> Update Hotel
            </button>
            <button type="button" onclick="window.location.href='view-hotels.php'" class="inline-flex items-center px-5 py-2.5 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
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
      dropArea.classList.add('border-indigo-500', 'bg-indigo-50');
    }

    function unhighlight() {
      dropArea.classList.remove('border-indigo-500', 'bg-indigo-50');
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

      // Enforce minimum PKR1
      if (value < 1 && this.value !== '') {
        this.value = 1;
        errorElement.textContent = "Price must be at least PKR1";
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