<?php
require_once '../config/db.php';
session_name('admin_session');
session_start();

if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
  header('Location: login.php');
  exit;
}

// Initialize variables
$success_message = '';
$error_message = '';

// Define upload directory and allowed file types
$upload_dir = 'uploads/';
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
$max_file_size = 5 * 1024 * 1024; // 5MB

// Ensure upload directory exists
if (!is_dir($upload_dir)) {
  mkdir($upload_dir, 0755, true);
}

// Handle Delete Requests
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_user'])) {
  $user_id = (int)$_POST['user_id'];
  // Prevent deleting the current admin
  if ($user_id !== (int)$_SESSION['admin_id']) {
    $conn->begin_transaction();
    try {
      // Get current profile image to delete it
      $stmt = $conn->prepare("SELECT profile_image FROM users WHERE id = ?");
      $stmt->bind_param("i", $user_id);
      $stmt->execute();
      $result = $stmt->get_result();
      if ($user = $result->fetch_assoc()) {
        if ($user['profile_image'] && file_exists($user['profile_image'])) {
          unlink($user['profile_image']);
        }
      } else {
        throw new Exception("User not found.");
      }
      $stmt->close();

      // Delete related flight_bookings (since no CASCADE)
      $stmt = $conn->prepare("DELETE FROM flight_bookings WHERE user_id = ?");
      $stmt->bind_param("i", $user_id);
      $stmt->execute();
      $stmt->close();

      // Delete user (CASCADE will handle hotel_bookings, package_bookings, transportation_bookings)
      $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
      $stmt->bind_param("i", $user_id);
      $stmt->execute();
      $stmt->close();

      $conn->commit();
      $success_message = "User and all related bookings deleted successfully!";
    } catch (Exception $e) {
      $conn->rollback();
      $error_message = "Error deleting user: " . $e->getMessage();
      error_log("Delete user error: " . $e->getMessage());
    }
  } else {
    $error_message = "Cannot delete your own account!";
  }
}

// Handle Edit Requests
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_user'])) {
  $user_id = (int)$_POST['user_id'];
  $full_name = $conn->real_escape_string($_POST['full_name']);
  $email = $conn->real_escape_string($_POST['email']);
  $dob = $conn->real_escape_string($_POST['dob']);
  $profile_image = null;

  // Handle file upload
  if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['profile_image'];
    if ($file['error'] === UPLOAD_ERR_OK) {
      if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_file_size) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('user_') . '.' . $ext;
        $destination = $upload_dir . $filename;

        // Delete old image if exists
        $stmt = $conn->prepare("SELECT profile_image FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($user = $result->fetch_assoc()) {
          if ($user['profile_image'] && file_exists($user['profile_image'])) {
            unlink($user['profile_image']);
          }
        }
        $stmt->close();

        if (move_uploaded_file($file['tmp_name'], $destination)) {
          $profile_image = $destination;
        } else {
          $error_message = "Error uploading image.";
        }
      } else {
        $error_message = "Invalid file type or size. Allowed: JPG, PNG, GIF, max 5MB.";
      }
    } else {
      $error_message = "Error uploading image: " . $file['error'];
    }
  } else {
    // Keep existing image if no new file is uploaded
    $stmt = $conn->prepare("SELECT profile_image FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($user = $result->fetch_assoc()) {
      $profile_image = $user['profile_image'];
    }
    $stmt->close();
  }

  if (!$error_message) {
    $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, dob = ?, profile_image = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $full_name, $email, $dob, $profile_image, $user_id);
    if ($stmt->execute()) {
      $success_message = "User updated successfully!";
    } else {
      $error_message = "Error updating user: " . $conn->error;
    }
    $stmt->close();
  }
}

// Fetch Statistics
$stats = [
  'total_users' => 0,
  'users_with_images' => 0,
  'recent_registrations' => 0,
  'average_age' => 0
];

// Total Users
$result = $conn->query("SELECT COUNT(*) as total FROM users");
$stats['total_users'] = $result->fetch_assoc()['total'];

// Users with Profile Images
$result = $conn->query("SELECT COUNT(*) as total FROM users WHERE profile_image IS NOT NULL");
$stats['users_with_images'] = $result->fetch_assoc()['total'];

// Recent Registrations (last 30 days)
$result = $conn->query("SELECT COUNT(*) as total FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$stats['recent_registrations'] = $result->fetch_assoc()['total'];

// Average Age
$result = $conn->query("SELECT COALESCE(AVG(DATEDIFF(CURDATE(), dob)/365.25), 0) as avg_age FROM users WHERE dob IS NOT NULL");
$stats['average_age'] = round((float)$result->fetch_assoc()['avg_age'], 1);

// Fetch Users
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$filter_image = isset($_GET['filter_image']) ? $_GET['filter_image'] : '';

$sql = "SELECT id, full_name, email, dob, profile_image, created_at FROM users WHERE 1=1";
$params = [];
$types = '';

if ($search) {
  $sql .= " AND (full_name LIKE ? OR email LIKE ?)";
  $search_param = "%$search%";
  $params[] = $search_param;
  $params[] = $search_param;
  $types .= 'ss';
}

if ($filter_image === 'with_image') {
  $sql .= " AND profile_image IS NOT NULL";
} elseif ($filter_image === 'without_image') {
  $sql .= " AND profile_image IS NULL";
}

$stmt = $conn->prepare($sql);
if (!empty($params)) {
  $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Users | UmrahFlights</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="stylesheet" href="assets/css/index.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    .edit-form {
      display: none;
    }

    .edit-form.active {
      display: table-row;
    }

    .view-row {
      display: table-row;
    }

    .view-row.hidden {
      display: none;
    }

    .stat-card {
      background: linear-gradient(135deg, #3b82f6, #1d4ed8);
      color: white;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      transition: transform 0.2s;
    }

    .stat-card:hover {
      transform: translateY(-5px);
    }

    .input-field {
      width: 100%;
      padding: 0.375rem 0.75rem;
      border: 1px solid #d1d5db;
      border-radius: 0.375rem;
      font-size: 0.875rem;
    }

    .input-field:focus {
      outline: 2px solid #3b82f6;
      border-color: #3b82f6;
    }
  </style>
</head>

<body class="bg-gray-100 font-sans">
  <?php include 'includes/sidebar.php'; ?>
  <main class="mt-10 p-6 min-h-screen" role="main" aria-label="Main content">
    <nav class="flex items-center justify-between bg-white shadow-md p-4 rounded-lg mb-6">
      <div class="flex items-center">
        <button id="sidebarToggle" class="md:hidden text-gray-600 hover:text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500" aria-label="Toggle sidebar">
          <i class="fas fa-bars text-xl"></i>
        </button>
        <h1 class="text-xl font-semibold text-gray-800 ml-4">Manage Users</h1>
      </div>
      <div class="flex items-center space-x-4">
        <div class="relative">
          <button id="userDropdown" class="flex items-center text-gray-600 hover:text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500" aria-label="User menu" aria-expanded="false">
            <!-- <img src="../assets/img/admin.jpg" alt="Admin User" class="w-8 h-8 rounded-full mr-2"> -->
            <span class="hidden md:inline text-gray-800">Admin User</span>
            <i class="fas fa-chevron-down ml-1"></i>
          </button>
          <div id="userDropdownMenu" class="hidden absolute right-0 mt-2 w-48 bg-white shadow-lg rounded-lg py-2 z-10">
            <a href="logout.php" class="block px-4 py-2 text-red-600 hover:bg-gray-100"><i class="fas fa-sign-out-alt mr-2"></i> Logout</a>
          </div>
        </div>
      </div>
    </nav>

    <?php if ($success_message): ?>
      <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-6 flex justify-between items-center" role="alert">
        <span><?php echo htmlspecialchars($success_message); ?></span>
        <button class="text-green-700 hover:text-green-900 focus:outline-none focus:ring-2 focus:ring-green-500" onclick="this.parentElement.remove()" aria-label="Close alert">
          <i class="fas fa-times"></i>
        </button>
      </div>
    <?php endif; ?>
    <?php if ($error_message): ?>
      <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-6 flex justify-between items-center" role="alert">
        <span><?php echo htmlspecialchars($error_message); ?></span>
        <button class="text-red-700 hover:text-red-900 focus:outline-none focus:ring-2 focus:ring-red-500" onclick="this.parentElement.remove()" aria-label="Close alert">
          <i class="fas fa-times"></i>
        </button>
      </div>
    <?php endif; ?>

    <section class="bg-white p-6 rounded-lg shadow-md" aria-label="User management">
      <!-- Statistics Section -->
      <div class="mb-6">
        <h2 class="text-2xl font-bold mb-4">User Statistics</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <div class="stat-card">
            <h3 class="text-lg font-semibold">Total Users</h3>
            <p class="text-2xl mt-2"><?php echo $stats['total_users']; ?></p>
          </div>
          <div class="stat-card">
            <h3 class="text-lg font-semibold">Users with Profile Images</h3>
            <p class="text-2xl mt-2"><?php echo $stats['users_with_images']; ?></p>
          </div>
          <div class="stat-card">
            <h3 class="text-lg font-semibold">Recent Registrations</h3>
            <p class="text-2xl mt-2"><?php echo $stats['recent_registrations']; ?></p>
            <p class="text-sm opacity-80">Last 30 days</p>
          </div>
          <div class="stat-card">
            <h3 class="text-lg font-semibold">Average Age</h3>
            <p class="text-2xl mt-2"><?php echo $stats['average_age']; ?> years</p>
          </div>
        </div>
      </div>

      <!-- Search and Filter -->
      <div class="mb-6">
        <form method="GET" class="flex flex-col md:flex-row gap-4">
          <div class="flex-1">
            <label for="search" class="block text-sm font-medium text-gray-700">Search Users</label>
            <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name or email" class="input-field w-full">
          </div>
          <div>
            <label for="filter_image" class="block text-sm font-medium text-gray-700">Filter by Profile Image</label>
            <select id="filter_image" name="filter_image" class="input-field w-full md:w-48">
              <option value="" <?php echo $filter_image === '' ? 'selected' : ''; ?>>All</option>
              <option value="with_image" <?php echo $filter_image === 'with_image' ? 'selected' : ''; ?>>With Image</option>
              <option value="without_image" <?php echo $filter_image === 'without_image' ? 'selected' : ''; ?>>Without Image</option>
            </select>
          </div>
          <div class="flex items-end">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700"><i class="fas fa-search mr-2"></i>Search</button>
          </div>
        </form>
      </div>

      <!-- Users Table -->
      <div class="mb-6 overflow-x-auto">
        <h3 class="font-semibold text-lg mb-3">User List</h3>
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-blue-600 text-white">
              <th class="p-3 w-16 text-center">ID</th>
              <th class="p-3 text-left">Full Name</th>
              <th class="p-3 text-left">Email</th>
              <th class="p-3 text-center">Date of Birth</th>
              <th class="p-3 text-center">Profile Image</th>
              <th class="p-3 text-center">Registered</th>
              <th class="p-3 w-24 text-center">Actions</th>
            </tr>
          </thead>
          <tbody id="users-body">
            <?php if (empty($users)): ?>
              <tr>
                <td colspan="7" class="p-3 text-center text-gray-500">No users found.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($users as $user): ?>
                <!-- View Row -->
                <tr class="view-row" data-user-id="<?php echo $user['id']; ?>">
                  <td class="p-3 text-center"><?php echo htmlspecialchars($user['id']); ?></td>
                  <td class="p-3"><?php echo htmlspecialchars($user['full_name']); ?></td>
                  <td class="p-3"><?php echo htmlspecialchars($user['email']); ?></td>
                  <td class="p-3 text-center"><?php echo htmlspecialchars($user['dob']); ?></td>
                  <td class="p-3 text-center">
                    <?php if ($user['profile_image'] && file_exists('../' . $user['profile_image'])): ?>
                      <img src="../<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile" class="w-8 h-8 rounded-full mx-auto">
                    <?php else: ?>
                      <span class="text-gray-500">None</span>
                    <?php endif; ?>
                  </td>
                  <td class="p-3 text-center"><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                  <td class="p-3 text-center">
                    <button type="button" class="text-blue-500 hover:text-blue-700 edit-btn" title="Edit"><i class="fas fa-edit"></i></button>
                    <button type="button" class="text-red-500 hover:text-red-700 delete-btn" title="Delete"><i class="fas fa-trash"></i></button>
                  </td>
                </tr>
                <!-- Edit Form Row -->
                <tr class="edit-form" data-user-id="<?php echo $user['id']; ?>">
                  <td class="p-3 text-center"><?php echo htmlspecialchars($user['id']); ?></td>
                  <td class="p-3">
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" class="input-field w-full" pattern="[A-Za-z\s]+" title="Only letters and spaces allowed" maxlength="100" required>
                    <div class="text-red-500 text-xs error-msg-name hidden">Only letters and spaces allowed (max 100 chars)</div>
                  </td>
                  <td class="p-3">
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="input-field w-full" maxlength="100" required>
                    <div class="text-red-500 text-xs error-msg-email hidden">Invalid email format</div>
                  </td>
                  <td class="p-3">
                    <input type="date" name="dob" value="<?php echo htmlspecialchars($user['dob']); ?>" class="input-field w-full text-center" required>
                    <div class="text-red-500 text-xs error-msg-dob hidden">Invalid date</div>
                  </td>
                  <td class="p-3">
                    <input type="file" name="profile_image" accept=".jpg,.jpeg,.png,.gif" class="input-field w-full">
                    <div class="text-red-500 text-xs error-msg-image hidden">Invalid file type or size (max 5MB)</div>
                    <?php if ($user['profile_image'] && file_exists($user['profile_image'])): ?>
                      <p class="text-sm text-gray-500 mt-1">Current: <a href="<?php echo htmlspecialchars($user['profile_image']); ?>" target="_blank">View Image</a></p>
                    <?php endif; ?>
                  </td>
                  <td class="p-3 text-center"><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                  <td class="p-3 text-center">
                    <button type="button" class="text-green-500 hover:text-green-700 save-btn" title="Save"><i class="fas fa-save"></i></button>
                    <button type="button" class="text-gray-500 hover:text-gray-700 cancel-btn" title="Cancel"><i class="fas fa-times"></i></button>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Sidebar and Dropdown Handlers
      document.getElementById('sidebarToggle').addEventListener('click', function() {
        document.querySelector('aside').classList.toggle('hidden');
      });

      document.getElementById('userDropdown').addEventListener('click', function() {
        document.getElementById('userDropdownMenu').classList.toggle('hidden');
      });

      document.addEventListener('click', function(e) {
        const dropdown = document.getElementById('userDropdownMenu');
        const button = document.getElementById('userDropdown');
        if (!dropdown.contains(e.target) && !button.contains(e.target)) {
          dropdown.classList.add('hidden');
        }
      });

      // Validation Functions
      function validateName(input) {
        const errorMsg = input.closest('td').querySelector('.error-msg-name');
        if (!/^[A-Za-z\s]{1,100}$/.test(input.value)) {
          errorMsg.classList.remove('hidden');
          return false;
        }
        errorMsg.classList.add('hidden');
        return true;
      }

      function validateEmail(input) {
        const errorMsg = input.closest('td').querySelector('.error-msg-email');
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(input.value)) {
          errorMsg.classList.remove('hidden');
          return false;
        }
        errorMsg.classList.add('hidden');
        return true;
      }

      function validateDob(input) {
        const errorMsg = input.closest('td').querySelector('.error-msg-dob');
        const today = new Date();
        const dob = new Date(input.value);
        if (!input.value || dob > today) {
          errorMsg.classList.remove('hidden');
          return false;
        }
        errorMsg.classList.add('hidden');
        return true;
      }

      function validateImage(input) {
        const errorMsg = input.closest('td').querySelector('.error-msg-image');
        if (input.files.length > 0) {
          const file = input.files[0];
          const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
          const maxSize = 5 * 1024 * 1024; // 5MB
          if (!allowedTypes.includes(file.type) || file.size > maxSize) {
            errorMsg.classList.remove('hidden');
            return false;
          }
        }
        errorMsg.classList.add('hidden');
        return true;
      }

      // Input Validation
      document.querySelectorAll('input[name="full_name"]').forEach(input => {
        input.addEventListener('input', () => validateName(input));
      });

      document.querySelectorAll('input[name="email"]').forEach(input => {
        input.addEventListener('input', () => validateEmail(input));
      });

      document.querySelectorAll('input[name="dob"]').forEach(input => {
        input.addEventListener('input', () => validateDob(input));
      });

      document.querySelectorAll('input[name="profile_image"]').forEach(input => {
        input.addEventListener('change', () => validateImage(input));
      });

      // Edit Button Handler
      document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', function() {
          const row = this.closest('.view-row');
          const userId = row.dataset.userId;
          row.classList.add('hidden');
          document.querySelector(`.edit-form[data-user-id="${userId}"]`).classList.add('active');
        });
      });

      // Cancel Button Handler
      document.querySelectorAll('.cancel-btn').forEach(button => {
        button.addEventListener('click', function() {
          const row = this.closest('.edit-form');
          const userId = row.dataset.userId;
          row.classList.remove('active');
          document.querySelector(`.view-row[data-user-id="${userId}"]`).classList.remove('hidden');
        });
      });

      // Save Button Handler
      document.querySelectorAll('.save-btn').forEach(button => {
        button.addEventListener('click', function() {
          const row = this.closest('.edit-form');
          const userId = row.dataset.userId;
          const fullNameInput = row.querySelector('input[name="full_name"]');
          const emailInput = row.querySelector('input[name="email"]');
          const dobInput = row.querySelector('input[name="dob"]');
          const profileImageInput = row.querySelector('input[name="profile_image"]');

          const isValid = validateName(fullNameInput) && validateEmail(emailInput) && validateDob(dobInput) && validateImage(profileImageInput);

          if (!isValid) {
            Swal.fire({
              icon: 'error',
              title: 'Validation Error',
              text: 'Please fix all validation errors before saving.'
            });
            return;
          }

          const formData = new FormData();
          formData.append('edit_user', '1');
          formData.append('user_id', userId);
          formData.append('full_name', fullNameInput.value);
          formData.append('email', emailInput.value);
          formData.append('dob', dobInput.value);
          if (profileImageInput.files.length > 0) {
            formData.append('profile_image', profileImageInput.files[0]);
          }

          fetch('', {
              method: 'POST',
              body: formData
            }).then(response => response.text())
            .then(() => {
              Swal.fire({
                icon: 'success',
                title: 'Success',
                text: 'User updated successfully!',
                showConfirmButton: false,
                timer: 1500
              }).then(() => {
                location.reload();
              });
            }).catch(error => {
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'An error occurred while saving the user.'
              });
            });
        });
      });

      // Delete Button Handler
      document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', function() {
          const row = this.closest('.view-row');
          const userId = row.dataset.userId;

          Swal.fire({
            title: 'Are you sure?',
            text: "This will also delete all bookings associated with this user!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
          }).then((result) => {
            if (result.isConfirmed) {
              const formData = new FormData();
              formData.append('delete_user', '1');
              formData.append('user_id', userId);

              fetch('', {
                  method: 'POST',
                  body: formData
                }).then(response => response.text())
                .then(() => {
                  Swal.fire({
                    icon: 'success',
                    title: 'Deleted!',
                    text: 'User and all related bookings have been deleted.',
                    showConfirmButton: false,
                    timer: 1500
                  }).then(() => {
                    location.reload();
                  });
                }).catch(error => {
                  Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while deleting the user.'
                  });
                });
            }
          });
        });
      });
    });
  </script>
</body>

</html>