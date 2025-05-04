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

// Handle Approve Requests
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['approve_user'])) {
  $user_id = (int)$_POST['user_id'];
  $admin_id = $_SESSION['admin_id'];

  $stmt = $conn->prepare("UPDATE users SET is_approved = 1, approved_at = NOW(), approved_by = ? WHERE id = ?");
  $stmt->bind_param("ii", $admin_id, $user_id);

  if ($stmt->execute()) {
    $success_message = "User approved successfully!";
  } else {
    $error_message = "Error approving user: " . $conn->error;
  }
  $stmt->close();
}

// Handle Delete Requests
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_user'])) {
  $user_id = (int)$_POST['user_id'];
  // Prevent deleting the current admin
  if ($user_id !== (int)$_SESSION['admin_id']) {
    $conn->begin_transaction();
    try {
      // Get current profile image and visa image to delete them
      $stmt = $conn->prepare("SELECT profile_image, visa_image FROM users WHERE id = ?");
      $stmt->bind_param("i", $user_id);
      $stmt->execute();
      $result = $stmt->get_result();
      if ($user = $result->fetch_assoc()) {
        if ($user['profile_image'] && file_exists($user['profile_image'])) {
          unlink($user['profile_image']);
        }
        if ($user['visa_image'] && file_exists($user['visa_image'])) {
          unlink($user['visa_image']);
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
  'pending_approvals' => 0
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

// Pending Approvals
$result = $conn->query("SELECT COUNT(*) as total FROM users WHERE is_approved = 0");
$stats['pending_approvals'] = $result->fetch_assoc()['total'];

// Fetch Users
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$filter_image = isset($_GET['filter_image']) ? $_GET['filter_image'] : '';
$filter_approval = isset($_GET['filter_approval']) ? $_GET['filter_approval'] : '';

$sql = "SELECT id, full_name, email, dob, profile_image, visa_image, created_at, is_approved, approved_at FROM users WHERE 1=1";
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

if ($filter_approval === 'pending') {
  $sql .= " AND is_approved = 0";
} elseif ($filter_approval === 'approved') {
  $sql .= " AND is_approved = 1";
}

$sql .= " ORDER BY is_approved ASC, created_at DESC";

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
  <title>Manage Users | UmrahFlights Admin</title>
  <!-- Tailwind CSS -->
  <link rel="stylesheet" href="../src/output.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <!-- SweetAlert2 -->
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
      transition: transform 0.2s;
    }

    .stat-card:hover {
      transform: translateY(-5px);
    }
  </style>
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
            <i class="fas fa-users text-indigo-600 mr-2"></i> Manage Users
          </h4>
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

    <!-- Alert Messages -->
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

    <!-- Statistics Section -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
      <div class="stat-card bg-white shadow-lg rounded-lg p-6 border-l-4 border-indigo-500">
        <div class="flex justify-between items-center">
          <div>
            <h3 class="text-lg font-semibold text-gray-800">Total Users</h3>
            <p class="text-2xl font-bold text-indigo-600"><?php echo $stats['total_users']; ?></p>
          </div>
          <div class="flex items-center justify-center w-12 h-12 rounded-full bg-indigo-100 text-indigo-500">
            <i class="fas fa-users text-xl"></i>
          </div>
        </div>
      </div>

      <div class="stat-card bg-white shadow-lg rounded-lg p-6 border-l-4 border-green-500">
        <div class="flex justify-between items-center">
          <div>
            <h3 class="text-lg font-semibold text-gray-800">With Profile Images</h3>
            <p class="text-2xl font-bold text-green-600"><?php echo $stats['users_with_images']; ?></p>
          </div>
          <div class="flex items-center justify-center w-12 h-12 rounded-full bg-green-100 text-green-500">
            <i class="fas fa-image text-xl"></i>
          </div>
        </div>
      </div>

      <div class="stat-card bg-white shadow-lg rounded-lg p-6 border-l-4 border-purple-500">
        <div class="flex justify-between items-center">
          <div>
            <h3 class="text-lg font-semibold text-gray-800">Recent Registrations</h3>
            <p class="text-2xl font-bold text-purple-600"><?php echo $stats['recent_registrations']; ?></p>
            <p class="text-xs text-gray-500">Last 30 days</p>
          </div>
          <div class="flex items-center justify-center w-12 h-12 rounded-full bg-purple-100 text-purple-500">
            <i class="fas fa-user-plus text-xl"></i>
          </div>
        </div>
      </div>

      <div class="stat-card bg-white shadow-lg rounded-lg p-6 border-l-4 border-yellow-500">
        <div class="flex justify-between items-center">
          <div>
            <h3 class="text-lg font-semibold text-gray-800">Pending Approvals</h3>
            <p class="text-2xl font-bold text-yellow-600"><?php echo $stats['pending_approvals']; ?></p>
          </div>
          <div class="flex items-center justify-center w-12 h-12 rounded-full bg-yellow-100 text-yellow-500">
            <i class="fas fa-hourglass-half text-xl"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Users Management Section -->
    <div class="bg-white shadow-lg rounded-lg p-6 mb-6">
      <!-- Search and Filter -->
      <div class="mb-6">
        <form method="GET" class="flex flex-col md:flex-row gap-4">
          <div class="flex-1">
            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search Users</label>
            <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name or email"
              class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
          </div>
          <div>
            <label for="filter_image" class="block text-sm font-medium text-gray-700 mb-1">Filter by Profile Image</label>
            <select id="filter_image" name="filter_image"
              class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
              <option value="" <?php echo $filter_image === '' ? 'selected' : ''; ?>>All</option>
              <option value="with_image" <?php echo $filter_image === 'with_image' ? 'selected' : ''; ?>>With Image</option>
              <option value="without_image" <?php echo $filter_image === 'without_image' ? 'selected' : ''; ?>>Without Image</option>
            </select>
          </div>
          <div>
            <label for="filter_approval" class="block text-sm font-medium text-gray-700 mb-1">Filter by Approval</label>
            <select id="filter_approval" name="filter_approval"
              class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
              <option value="" <?php echo $filter_approval === '' ? 'selected' : ''; ?>>All</option>
              <option value="pending" <?php echo $filter_approval === 'pending' ? 'selected' : ''; ?>>Pending</option>
              <option value="approved" <?php echo $filter_approval === 'approved' ? 'selected' : ''; ?>>Approved</option>
            </select>
          </div>
          <div class="flex items-end">
            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
              <i class="fas fa-search mr-2"></i>Search
            </button>
          </div>
        </form>
      </div>

      <!-- Users Table -->
      <div class="mb-6 overflow-x-auto">
        <div class="flex justify-between items-center mb-4">
          <h3 class="font-medium text-gray-700">User List</h3>
          <a href="add-user.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            <i class="fas fa-user-plus mr-2"></i>Add New User
          </a>
        </div>
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-indigo-600 text-white">
              <th class="p-3 w-16 text-center">ID</th>
              <th class="p-3 text-left">Full Name</th>
              <th class="p-3 text-left">Email</th>
              <th class="p-3 text-center">Date of Birth</th>
              <th class="p-3 text-center">Profile Image</th>
              <th class="p-3 text-center">Visa Document</th>
              <th class="p-3 text-center">Status</th>
              <th class="p-3 text-center">Registered</th>
              <th class="p-3 w-40 text-center">Actions</th>
            </tr>
          </thead>
          <tbody id="users-body">
            <?php if (empty($users)): ?>
              <tr>
                <td colspan="9" class="p-3 text-center text-gray-500">No users found.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($users as $user): ?>
                <!-- View Row -->
                <tr class="view-row border-b hover:bg-gray-50" data-user-id="<?php echo $user['id']; ?>">
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
                  <td class="p-3 text-center">
                    <?php if ($user['visa_image'] && file_exists('../' . $user['visa_image'])): ?>
                      <a href="../<?php echo htmlspecialchars($user['visa_image']); ?>" target="_blank" class="text-blue-600 hover:text-blue-800">
                        <i class="fas fa-passport mr-1"></i>View
                      </a>
                    <?php else: ?>
                      <span class="text-gray-500">None</span>
                    <?php endif; ?>
                  </td>
                  <td class="p-3 text-center">
                    <?php if ($user['is_approved'] == 1): ?>
                      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        Approved
                      </span>
                    <?php else: ?>
                      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                        Pending
                      </span>
                    <?php endif; ?>
                  </td>
                  <td class="p-3 text-center"><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                  <td class="p-3 text-center">
                    <?php if ($user['is_approved'] == 0): ?>
                      <button type="button" class="text-green-600 hover:text-green-800 approve-btn" data-id="<?php echo $user['id']; ?>" title="Approve">
                        <i class="fas fa-check"></i>
                      </button>
                    <?php endif; ?>
                    <button type="button" class="text-indigo-600 hover:text-indigo-800 edit-btn ml-2" title="Edit"><i class="fas fa-edit"></i></button>
                    <button type="button" class="text-red-600 hover:text-red-800 delete-btn ml-2" title="Delete"><i class="fas fa-trash"></i></button>
                  </td>
                </tr>
                <!-- Edit Form Row -->
                <tr class="edit-form border-b bg-gray-50" data-user-id="<?php echo $user['id']; ?>">
                  <td class="p-3 text-center"><?php echo htmlspecialchars($user['id']); ?></td>
                  <td class="p-3">
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>"
                      class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                      pattern="[A-Za-z\s]+" title="Only letters and spaces allowed" maxlength="100" required>
                    <div class="text-red-500 text-xs error-msg-name hidden">Only letters and spaces allowed (max 100 chars)</div>
                  </td>
                  <td class="p-3">
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>"
                      class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                      maxlength="100" required>
                    <div class="text-red-500 text-xs error-msg-email hidden">Invalid email format</div>
                  </td>
                  <td class="p-3">
                    <input type="date" name="dob" value="<?php echo htmlspecialchars($user['dob']); ?>"
                      class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-center"
                      required>
                    <div class="text-red-500 text-xs error-msg-dob hidden">Invalid date</div>
                  </td>
                  <td class="p-3" colspan="4">
                    <input type="file" name="profile_image" accept=".jpg,.jpeg,.png,.gif"
                      class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    <div class="text-red-500 text-xs error-msg-image hidden">Invalid file type or size (max 5MB)</div>
                    <?php if ($user['profile_image'] && file_exists($user['profile_image'])): ?>
                      <p class="text-xs text-gray-500 mt-1">Current: <a href="<?php echo htmlspecialchars($user['profile_image']); ?>" target="_blank" class="text-indigo-600 hover:text-indigo-800">View Image</a></p>
                    <?php endif; ?>
                  </td>
                  <td class="p-3 text-center"><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                  <td class="p-3 text-center">
                    <button type="button" class="text-green-600 hover:text-green-800 save-btn" title="Save"><i class="fas fa-save"></i></button>
                    <button type="button" class="text-gray-600 hover:text-gray-800 cancel-btn ml-2" title="Cancel"><i class="fas fa-times"></i></button>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
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
            cancelButtonColor: '#4f46e5',
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

      // Approve Button Handler
      document.querySelectorAll('.approve-btn').forEach(button => {
        button.addEventListener('click', function() {
          const userId = this.dataset.id;

          Swal.fire({
            title: 'Approve User?',
            text: "Are you sure you want to approve this user?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#22c55e',
            cancelButtonColor: '#4f46e5',
            confirmButtonText: 'Yes, approve!'
          }).then((result) => {
            if (result.isConfirmed) {
              const formData = new FormData();
              formData.append('approve_user', '1');
              formData.append('user_id', userId);

              fetch('', {
                  method: 'POST',
                  body: formData
                }).then(response => response.text())
                .then(() => {
                  Swal.fire({
                    icon: 'success',
                    title: 'Approved!',
                    text: 'User has been approved successfully.',
                    showConfirmButton: false,
                    timer: 1500
                  }).then(() => {
                    location.reload();
                  });
                }).catch(error => {
                  Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while approving the user.'
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