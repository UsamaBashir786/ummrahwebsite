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
function uploadImage($file, $target_dir = "../Uploads/about/")
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
          $title = isset($_POST['title']) ? trim($_POST['title']) : null;
          $subtitle = isset($_POST['subtitle']) ? trim($_POST['subtitle']) : null;
          $content = isset($_POST['content']) ? trim($_POST['content']) : null;
          $display_order = intval($_POST['display_order']);
          $status = $_POST['status'] ?? 'active';

          // Handle image upload if provided
          $image_url = null;
          if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_result = uploadImage($_FILES['image']);
            if ($upload_result['success']) {
              $image_url = 'Uploads/about/' . $upload_result['filename'];

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
              <?php else: ?>
                <input type="hidden" name="title" value="">
              <?php endif; ?>

              <?php if ($about['subtitle'] !== null): ?>
                <div class="mb-4">
                  <label class="block text-sm font-medium text-gray-700 mb-2">Subtitle</label>
                  <textarea name="subtitle" rows="2"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"><?php echo htmlspecialchars($about['subtitle']); ?></textarea>
                </div>
              <?php else: ?>
                <input type="hidden" name="subtitle" value="">
              <?php endif; ?>

              <?php if ($about['content'] !== null): ?>
                <div class="mb-4">
                  <label class="block text-sm font-medium text-gray-700 mb-2">Content</label>
                  <textarea name="content" rows="4"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"><?php echo htmlspecialchars($about['content']); ?></textarea>
                </div>
              <?php else: ?>
                <input type="hidden" name="content" value="">
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

      <!-- Add New Value Form -->
      <div class="mb-6">
        <h6 class="text-md font-medium text-gray-700 mb-3">Add New Value</h6>
        <form method="POST" action="" class="border-b pb-4">
          <input type="hidden" name="form_token" value="<?php echo $_SESSION['form_token']; ?>">
          <input type="hidden" name="action" value="add_value">

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Title</label>
              <input type="text" name="title" required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Icon Class (Font Awesome)</label>
              <input type="text" name="icon_class" placeholder="e.g., fas fa-heart"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
          </div>

          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
            <textarea name="description" rows="3" required
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Display Order</label>
              <input type="number" name="display_order" value="0"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
              <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
          </div>

          <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            Add Value
          </button>
        </form>
      </div>

      <!-- List Existing Values -->
      <div class="space-y-4">
        <?php if ($values_result && $values_result->num_rows > 0): ?>
          <?php while ($value = $values_result->fetch_assoc()): ?>
            <form method="POST" action="" class="border-b pb-4">
              <input type="hidden" name="form_token" value="<?php echo $_SESSION['form_token']; ?>">
              <input type="hidden" name="action" value="edit_value">
              <input type="hidden" name="id" value="<?php echo $value['id']; ?>">

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                  <input type="text" name="title" value="<?php echo htmlspecialchars($value['title']); ?>" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Icon Class (Font Awesome)</label>
                  <input type="text" name="icon_class" value="<?php echo htmlspecialchars($value['icon_class']); ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
              </div>

              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb maidenhead-2">Description</label>
                <textarea name="description" rows="3" required
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"><?php echo htmlspecialchars($value['description']); ?></textarea>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Display Order</label>
                  <input type="number" name="display_order" value="<?php echo $value['display_order']; ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                  <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="active" <?php echo $value['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $value['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                  </select>
                </div>
              </div>

              <div class="flex justify-between items-center">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                  Update
                </button>
                <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this value?');">
                  <input type="hidden" name="form_token" value="<?php echo $_SESSION['form_token']; ?>">
                  <input type="hidden" name="action" value="delete_value">
                  <input type="hidden" name="id" value="<?php echo $value['id']; ?>">
                  <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                    Delete
                  </button>
                </form>
              </div>
            </form>
          <?php endwhile; ?>
        <?php else: ?>
          <p class="text-gray-500">No company values found.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- JavaScript for User Dropdown -->
  <script>
    const userDropdownButton = document.getElementById('userDropdownButton');
    const userDropdownMenu = document.getElementById('userDropdownMenu');

    userDropdownButton.addEventListener('click', () => {
      userDropdownMenu.classList.toggle('hidden');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', (event) => {
      if (!userDropdownButton.contains(event.target) && !userDropdownMenu.contains(event.target)) {
        userDropdownMenu.classList.add('hidden');
      }
    });
  </script>
</body>

</html>