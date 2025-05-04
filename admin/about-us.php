<?php
require_once '../config/db.php'; // Include db.php with $conn
// Start admin session
session_name('admin_session');
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if admin is logged in
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
  header('Location: login.php');
  exit;
}

// Verify database connection
if (!$conn) {
  die("Database connection failed: " . mysqli_connect_error());
}

// Function to handle image upload
function uploadImage($file, $target_dir = "../uploads/about/")
{
  // Create directory if it doesn't exist
  if (!file_exists($target_dir)) {
    mkdir($target_dir, 0777, true);
  }

  $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
  $new_filename = uniqid() . '.' . $file_extension;
  $target_file = $target_dir . $new_filename;

  // Check if file is an actual image
  $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
  if (!in_array($file_extension, $allowed_types)) {
    return ['success' => false, 'message' => 'Only JPG, JPEG, PNG, GIF & WEBP files are allowed.'];
  }

  // Check file size (5MB maximum)
  if ($file["size"] > 5000000) {
    return ['success' => false, 'message' => 'File is too large. Maximum size is 5MB.'];
  }

  // Upload file
  if (move_uploaded_file($file["tmp_name"], $target_file)) {
    return ['success' => true, 'filename' => $new_filename];
  } else {
    return ['success' => false, 'message' => 'Failed to upload file.'];
  }
}

// Handle form submission - Prevent duplicate submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Check if the form token matches the session token
  if (isset($_POST['form_token']) && isset($_SESSION['form_token']) && $_POST['form_token'] === $_SESSION['form_token']) {

    // Process the form submission
    if (isset($_POST['action'])) {
      switch ($_POST['action']) {
        case 'update_about':
          $id = intval($_POST['id']);
          $section_type = $_POST['section_type'];
          $title = trim($_POST['title']);
          $subtitle = trim($_POST['subtitle']);
          $content = trim($_POST['content']);
          $display_order = intval($_POST['display_order']);
          $status = $_POST['status'] ?? 'active';

          // Handle image upload if provided
          $image_url = null;
          if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_result = uploadImage($_FILES['image']);
            if ($upload_result['success']) {
              $image_url = 'uploads/about/' . $upload_result['filename'];

              // Delete old image if exists
              if (!empty($_POST['old_image'])) {
                $old_image_path = '../' . $_POST['old_image'];
                if (file_exists($old_image_path)) {
                  unlink($old_image_path);
                }
              }
            } else {
              $error_message = $upload_result['message'];
            }
          } else {
            // Keep existing image
            $image_url = $_POST['old_image'] ?? null;
          }

          if ($id > 0) {
            $stmt = $conn->prepare("UPDATE about_us SET title = ?, subtitle = ?, content = ?, image_url = ?, display_order = ?, status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("ssssisi", $title, $subtitle, $content, $image_url, $display_order, $status, $id);
            if ($stmt->execute()) {
              $success_message = "About content updated successfully!";
            } else {
              $error_message = "Error updating about content: " . $conn->error;
            }
            $stmt->close();
          }
          break;

        case 'add_value':
          $title = trim($_POST['title']);
          $description = trim($_POST['description']);
          $icon_class = trim($_POST['icon_class']);
          $display_order = intval($_POST['display_order']);
          $status = $_POST['status'] ?? 'active';

          if (!empty($title) && !empty($description)) {
            $stmt = $conn->prepare("INSERT INTO company_values (title, description, icon_class, display_order, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("sssis", $title, $description, $icon_class, $display_order, $status);
            if ($stmt->execute()) {
              $success_message = "Value added successfully!";
            } else {
              $error_message = "Error adding value: " . $conn->error;
            }
            $stmt->close();
          } else {
            $error_message = "Title and description are required!";
          }
          break;

        case 'edit_value':
          $id = intval($_POST['id']);
          $title = trim($_POST['title']);
          $description = trim($_POST['description']);
          $icon_class = trim($_POST['icon_class']);
          $display_order = intval($_POST['display_order']);
          $status = $_POST['status'] ?? 'active';

          if (!empty($title) && !empty($description) && $id > 0) {
            $stmt = $conn->prepare("UPDATE company_values SET title = ?, description = ?, icon_class = ?, display_order = ?, status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("sssisi", $title, $description, $icon_class, $display_order, $status, $id);
            if ($stmt->execute()) {
              $success_message = "Value updated successfully!";
            } else {
              $error_message = "Error updating value: " . $conn->error;
            }
            $stmt->close();
          } else {
            $error_message = "Invalid data for update!";
          }
          break;

        case 'delete_value':
          $id = intval($_POST['id']);
          if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM company_values WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
              $success_message = "Value deleted successfully!";
            } else {
              $error_message = "Error deleting value: " . $conn->error;
            }
            $stmt->close();
          }
          break;

        case 'add_stat':
          $label = trim($_POST['label']);
          $value = trim($_POST['value']);
          $prefix = trim($_POST['prefix']);
          $suffix = trim($_POST['suffix']);
          $display_order = intval($_POST['display_order']);
          $status = $_POST['status'] ?? 'active';

          if (!empty($label) && !empty($value)) {
            $stmt = $conn->prepare("INSERT INTO company_statistics (label, value, prefix, suffix, display_order, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssssls", $label, $value, $prefix, $suffix, $display_order, $status);
            if ($stmt->execute()) {
              $success_message = "Statistic added successfully!";
            } else {
              $error_message = "Error adding statistic: " . $conn->error;
            }
            $stmt->close();
          } else {
            $error_message = "Label and value are required!";
          }
          break;

        case 'edit_stat':
          $id = intval($_POST['id']);
          $label = trim($_POST['label']);
          $value = trim($_POST['value']);
          $prefix = trim($_POST['prefix']);
          $suffix = trim($_POST['suffix']);
          $display_order = intval($_POST['display_order']);
          $status = $_POST['status'] ?? 'active';

          if (!empty($label) && !empty($value) && $id > 0) {
            $stmt = $conn->prepare("UPDATE company_statistics SET label = ?, value = ?, prefix = ?, suffix = ?, display_order = ?, status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("ssssisi", $label, $value, $prefix, $suffix, $display_order, $status, $id);
            if ($stmt->execute()) {
              $success_message = "Statistic updated successfully!";
            } else {
              $error_message = "Error updating statistic: " . $conn->error;
            }
            $stmt->close();
          } else {
            $error_message = "Invalid data for update!";
          }
          break;

        case 'delete_stat':
          $id = intval($_POST['id']);
          if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM company_statistics WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
              $success_message = "Statistic deleted successfully!";
            } else {
              $error_message = "Error deleting statistic: " . $conn->error;
            }
            $stmt->close();
          }
          break;
      }
    }

    // Clear the form token
    unset($_SESSION['form_token']);

    // Redirect to prevent form resubmission
    $_SESSION['success_message'] = $success_message ?? null;
    $_SESSION['error_message'] = $error_message ?? null;
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
  }
}

// Generate a new form token
$_SESSION['form_token'] = bin2hex(random_bytes(32));

// Check for flash messages
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Fetch all about us content
$about_query = "SELECT * FROM about_us ORDER BY display_order, created_at";
$about_result = $conn->query($about_query);

// Fetch all company values
$values_query = "SELECT * FROM company_values ORDER BY display_order, created_at";
$values_result = $conn->query($values_query);

// Fetch all company statistics
$stats_query = "SELECT * FROM company_statistics ORDER BY display_order, created_at";
$stats_result = $conn->query($stats_query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage About Us | UmrahFlights Admin</title>
  <!-- Tailwind CSS CDN -->
  <link rel="stylesheet" href="../src/output.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="assets/css/index.css">
</head>

<body class="bg-gray-100">
  <?php include 'includes/sidebar.php'; ?>

  <!-- Main Content -->
  <div class="ml-0 md:ml-64 mt-10 px-4 sm:px-6 lg:px-8 transition-all duration-300">
    <!-- Top Navbar -->
    <nav class="bg-white shadow-lg rounded-lg p-5 mb-6">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <h4 class="text-lg font-semibold text-gray-800">Manage About Us</h4>
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

    <!-- Alerts -->
    <?php if ($success_message): ?>
      <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
        <span class="block sm:inline"><?php echo htmlspecialchars($success_message); ?></span>
      </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
      <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
        <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
      </div>
    <?php endif; ?>

    <!-- About Us Content Section -->
    <div class="bg-white shadow-lg rounded-lg p-6 mb-6">
      <h5 class="text-lg font-semibold text-gray-800 mb-4">About Us Content</h5>
      <div class="space-y-4">
        <?php if ($about_result && $about_result->num_rows > 0): ?>
          <?php while ($about = $about_result->fetch_assoc()): ?>
            <form method="POST" action="" class="border-b pb-4" enctype="multipart/form-data">
              <input type="hidden" name="form_token" value="<?php echo $_SESSION['form_token']; ?>">
              <input type="hidden" name="action" value="update_about">
              <input type="hidden" name="id" value="<?php echo $about['id']; ?>">
              <input type="hidden" name="section_type" value="<?php echo $about['section_type']; ?>">
              <input type="hidden" name="old_image" value="<?php echo $about['image_url']; ?>">

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Section Type</label>
                  <input type="text" value="<?php echo htmlspecialchars($about['section_type']); ?>" disabled
                    class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100">
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Display Order</label>
                  <input type="number" name="display_order" value="<?php echo $about['display_order']; ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
              </div>

              <?php if ($about['title'] !== null): ?>
                <div class="mb-4">
                  <label class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                  <input type="text" name="title" value="<?php echo htmlspecialchars($about['title']); ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
              <?php endif; ?>

              <?php if ($about['subtitle'] !== null): ?>
                <div class="mb-4">
                  <label class="block text-sm font-medium text-gray-700 mb-2">Subtitle</label>
                  <textarea name="subtitle" rows="2"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"><?php echo htmlspecialchars($about['subtitle']); ?></textarea>
                </div>
              <?php endif; ?>

              <?php if ($about['content'] !== null): ?>
                <div class="mb-4">
                  <label class="block text-sm font-medium text-gray-700 mb-2">Content</label>
                  <textarea name="content" rows="4"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"><?php echo htmlspecialchars($about['content']); ?></textarea>
                </div>
              <?php endif; ?>

              <?php if ($about['section_type'] === 'mission'): ?>
                <div class="mb-4">
                  <label class="block text-sm font-medium text-gray-700 mb-2">Image</label>
                  <div class="flex items-center space-x-4">
                    <?php if (!empty($about['image_url'])): ?>
                      <img src="../<?php echo htmlspecialchars($about['image_url']); ?>" alt="About Image" class="w-32 h-32 object-cover rounded-lg">
                    <?php endif; ?>
                    <input type="file" name="image" accept="image/*"
                      class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                  </div>
                  <p class="text-sm text-gray-500 mt-1">Accepted formats: JPG, JPEG, PNG, GIF, WEBP (Max: 5MB)</p>
                </div>
              <?php endif; ?>

              <div class="flex justify-between items-center">
                <select name="status" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                  <option value="active" <?php echo $about['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                  <option value="inactive" <?php echo $about['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>

                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                  Update
                </button>
              </div>
            </form>
          <?php endwhile; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Company Values Section -->
    <div class="bg-white shadow-lg rounded-lg p-6 mb-6">
      <h5 class="text-lg font-semibold text-gray-800 mb-4">Company Values</h5>

      <!-- Add Value Form -->
      <form method="POST" action="" id="valueForm" class="mb-6">
        <input type="hidden" name="form_token" value="<?php echo $_SESSION['form_token']; ?>">
        <input type="hidden" name="action" id="valueAction" value="add_value">
        <input type="hidden" name="id" id="valueId">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Title *</label>
            <input type="text" name="title" id="valueTitle" required
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Icon Class (FontAwesome)</label>
            <input type="text" name="icon_class" id="valueIcon"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
              placeholder="e.g., fas fa-shield-alt">
          </div>
        </div>

        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Description *</label>
          <textarea name="description" id="valueDescription" required rows="3"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Display Order</label>
            <input type="number" name="display_order" id="valueOrder" value="0"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
            <select name="status" id="valueStatus"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
        </div>

        <div class="flex justify-end space-x-3">
          <button type="button" id="cancelValueEdit" onclick="resetValueForm()"
            class="hidden px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500">
            Cancel
          </button>
          <button type="submit"
            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <span id="valueSubmitText">Add Value</span>
          </button>
        </div>
      </form>

      <!-- Values List -->
      <div class="overflow-x-auto">
        <table class="w-full text-left">
          <thead>
            <tr class="border-b">
              <th class="py-3 px-4 text-sm font-semibold text-gray-600">Title</th>
              <th class="py-3 px-4 text-sm font-semibold text-gray-600">Icon</th>
              <th class="py-3 px-4 text-sm font-semibold text-gray-600">Order</th>
              <th class="py-3 px-4 text-sm font-semibold text-gray-600">Status</th>
              <th class="py-3 px-4 text-sm font-semibold text-gray-600">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($values_result && $values_result->num_rows > 0): ?>
              <?php while ($value = $values_result->fetch_assoc()): ?>
                <tr class="border-b hover:bg-gray-50">
                  <td class="py-3 px-4 text-sm text-gray-700">
                    <div class="font-medium"><?php echo htmlspecialchars($value['title']); ?></div>
                    <div class="text-gray-500 text-xs mt-1"><?php echo htmlspecialchars(substr($value['description'], 0, 100)) . '...'; ?></div>
                  </td>
                  <td class="py-3 px-4 text-sm text-gray-700">
                    <?php if ($value['icon_class']): ?>
                      <i class="<?php echo htmlspecialchars($value['icon_class']); ?> text-lg"></i>
                    <?php endif; ?>
                  </td>
                  <td class="py-3 px-4 text-sm text-gray-700"><?php echo $value['display_order']; ?></td>
                  <td class="py-3 px-4 text-sm">
                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                    <?php echo $value['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                      <?php echo ucfirst($value['status']); ?>
                    </span>
                  </td>
                  <td class="py-3 px-4 text-sm">
                    <button onclick="editValue(<?php echo htmlspecialchars(json_encode($value)); ?>)"
                      class="text-indigo-600 hover:text-indigo-900 mr-3">
                      <i class="fas fa-edit"></i>
                    </button>
                    <form method="POST" action="" class="inline" onsubmit="return confirm('Are you sure you want to delete this value?');">
                      <input type="hidden" name="form_token" value="<?php echo $_SESSION['form_token']; ?>">
                      <input type="hidden" name="action" value="delete_value">
                      <input type="hidden" name="id" value="<?php echo $value['id']; ?>">
                      <button type="submit" class="text-red-600 hover:text-red-900">
                        <i class="fas fa-trash"></i>
                      </button>
                    </form>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="5" class="py-3 px-4 text-sm text-gray-500 text-center">No values found.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Company Statistics Section -->
    <div class="bg-white shadow-lg rounded-lg p-6">
      <h5 class="text-lg font-semibold text-gray-800 mb-4">Company Statistics</h5>

      <!-- Add Statistic Form -->
      <form method="POST" action="" id="statForm" class="mb-6">
        <input type="hidden" name="form_token" value="<?php echo $_SESSION['form_token']; ?>">
        <input type="hidden" name="action" id="statAction" value="add_stat">
        <input type="hidden" name="id" id="statId">

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Label *</label>
            <input type="text" name="label" id="statLabel" required
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Value *</label>
            <input type="text" name="value" id="statValue" required
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Prefix</label>
            <input type="text" name="prefix" id="statPrefix"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
              placeholder="e.g., $">
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Suffix</label>
            <input type="text" name="suffix" id="statSuffix"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
              placeholder="e.g., %, +">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Display Order</label>
            <input type="number" name="display_order" id="statOrder" value="0"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
            <select name="status" id="statStatus"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
        </div>

        <div class="flex justify-end space-x-3">
          <button type="button" id="cancelStatEdit" onclick="resetStatForm()"
            class="hidden px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500">
            Cancel
          </button>
          <button type="submit"
            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <span id="statSubmitText">Add Statistic</span>
          </button>
        </div>
      </form>

      <!-- Statistics List -->
      <div class="overflow-x-auto">
        <table class="w-full text-left">
          <thead>
            <tr class="border-b">
              <th class="py-3 px-4 text-sm font-semibold text-gray-600">Label</th>
              <th class="py-3 px-4 text-sm font-semibold text-gray-600">Value</th>
              <th class="py-3 px-4 text-sm font-semibold text-gray-600">Order</th>
              <th class="py-3 px-4 text-sm font-semibold text-gray-600">Status</th>
              <th class="py-3 px-4 text-sm font-semibold text-gray-600">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($stats_result && $stats_result->num_rows > 0): ?>
              <?php while ($stat = $stats_result->fetch_assoc()): ?>
                <tr class="border-b hover:bg-gray-50">
                  <td class="py-3 px-4 text-sm text-gray-700"><?php echo htmlspecialchars($stat['label']); ?></td>
                  <td class="py-3 px-4 text-sm text-gray-700">
                    <?php echo htmlspecialchars($stat['prefix'] . $stat['value'] . $stat['suffix']); ?>
                  </td>
                  <td class="py-3 px-4 text-sm text-gray-700"><?php echo $stat['display_order']; ?></td>
                  <td class="py-3 px-4 text-sm">
                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                    <?php echo $stat['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                      <?php echo ucfirst($stat['status']); ?>
                    </span>
                  </td>
                  <td class="py-3 px-4 text-sm">
                    <button onclick="editStat(<?php echo htmlspecialchars(json_encode($stat)); ?>)"
                      class="text-indigo-600 hover:text-indigo-900 mr-3">
                      <i class="fas fa-edit"></i>
                    </button>
                    <form method="POST" action="" class="inline" onsubmit="return confirm('Are you sure you want to delete this statistic?');">
                      <input type="hidden" name="form_token" value="<?php echo $_SESSION['form_token']; ?>">
                      <input type="hidden" name="action" value="delete_stat">
                      <input type="hidden" name="id" value="<?php echo $stat['id']; ?>">
                      <button type="submit" class="text-red-600 hover:text-red-900">
                        <i class="fas fa-trash"></i>
                      </button>
                    </form>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="5" class="py-3 px-4 text-sm text-gray-500 text-center">No statistics found.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script>
    // User dropdown functionality
    document.getElementById('userDropdownButton').addEventListener('click', function() {
      document.getElementById('userDropdownMenu').classList.toggle('hidden');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
      const dropdown = document.getElementById('userDropdownMenu');
      const button = document.getElementById('userDropdownButton');
      if (!button.contains(event.target)) {
        dropdown.classList.add('hidden');
      }
    });

    // Edit value function
    function editValue(value) {
      document.getElementById('valueAction').value = 'edit_value';
      document.getElementById('valueId').value = value.id;
      document.getElementById('valueTitle').value = value.title;
      document.getElementById('valueIcon').value = value.icon_class || '';
      document.getElementById('valueDescription').value = value.description;
      document.getElementById('valueOrder').value = value.display_order;
      document.getElementById('valueStatus').value = value.status;
      document.getElementById('valueSubmitText').textContent = 'Update Value';
      document.getElementById('cancelValueEdit').classList.remove('hidden');

      // Scroll to form
      document.getElementById('valueForm').scrollIntoView({
        behavior: 'smooth'
      });
    }

    // Reset value form
    function resetValueForm() {
      document.getElementById('valueAction').value = 'add_value';
      document.getElementById('valueId').value = '';
      document.getElementById('valueForm').reset();
      document.getElementById('valueSubmitText').textContent = 'Add Value';
      document.getElementById('cancelValueEdit').classList.add('hidden');
    }

    // Edit statistic function
    function editStat(stat) {
      document.getElementById('statAction').value = 'edit_stat';
      document.getElementById('statId').value = stat.id;
      document.getElementById('statLabel').value = stat.label;
      document.getElementById('statValue').value = stat.value;
      document.getElementById('statPrefix').value = stat.prefix || '';
      document.getElementById('statSuffix').value = stat.suffix || '';
      document.getElementById('statOrder').value = stat.display_order;
      document.getElementById('statStatus').value = stat.status;
      document.getElementById('statSubmitText').textContent = 'Update Statistic';
      document.getElementById('cancelStatEdit').classList.remove('hidden');

      // Scroll to form
      document.getElementById('statForm').scrollIntoView({
        behavior: 'smooth'
      });
    }

    // Reset statistic form
    function resetStatForm() {
      document.getElementById('statAction').value = 'add_stat';
      document.getElementById('statId').value = '';
      document.getElementById('statForm').reset();
      document.getElementById('statSubmitText').textContent = 'Add Statistic';
      document.getElementById('cancelStatEdit').classList.add('hidden');
    }
  </script>
</body>

</html>