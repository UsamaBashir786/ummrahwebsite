<?php
// Start admin session
session_name('admin_session');
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
  header('Location: login.php');
  exit;
}

// Initialize variables
$users = [];
$message = '';
$messageType = '';

// Handle user deletion if requested
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
  $user_id = mysqli_real_escape_string($conn, $_GET['delete']);

  // Don't allow admin to delete themselves
  if ($user_id == $_SESSION['user_id']) {
    $message = "You cannot delete your own account!";
    $messageType = "danger";
  } else {
    // Delete the user
    $delete_query = "DELETE FROM users WHERE id = '$user_id'";
    if ($conn->query($delete_query)) {
      $message = "User deleted successfully!";
      $messageType = "success";
    } else {
      $message = "Error deleting user: " . $conn->error;
      $messageType = "danger";
    }
  }
}

// Fetch all users from the database
$query = "SELECT id, full_name, email, created_at, user_type FROM users ORDER BY created_at DESC";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $users[] = $row;
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>All Users | UmrahFlights</title>
  <!-- Tailwind CSS (same as other pages) -->
  <link rel="stylesheet" href="../src/output.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <!-- SweetAlert2 (for delete confirmation) -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    .badge {
      @apply inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium;
    }

    .badge-danger {
      @apply bg-red-100 text-red-800;
    }

    .badge-info {
      @apply bg-indigo-100 text-indigo-800;
    }
  </style>
</head>

<body class="bg-gray-100 font-sans min-h-screen">
  <?php include 'includes/sidebar.php'; ?>
  <main class="ml-0 md:ml-64 mt-10 px-4 sm:px-6 lg:px-8 transition-all duration-300" role="main" aria-label="Main content">
    <!-- Top Navbar (aligned with other pages) -->
    <nav class="bg-white shadow-lg rounded-lg p-5 mb-6">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <button id="sidebarToggle" class="text-gray-500 hover:text-gray-700 focus:outline-none md:hidden" aria-label="Toggle sidebar">
            <i class="fas fa-bars text-xl"></i>
          </button>
          <h4 id="dashboardHeader" class="text-lg font-semibold text-gray-800 cursor-pointer hover:text-indigo-600">All Users</h4>
        </div>
        <div class="flex items-center space-x-4">
          <!-- User Dropdown -->
          <div class="relative">
            <button id="userDropdownButton" class="flex items-center space-x-2 text-gray-700 hover:bg-indigo-50 rounded-lg px-3 py-2 focus:outline-none" aria-label="User menu" aria-expanded="false">
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

    <!-- Main Content Section -->
    <section class="bg-white shadow-lg rounded-lg p-6" aria-label="Manage users">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">
          <i class="fas fa-users text-indigo-600 mr-2"></i>All Users
        </h2>
        <a href="add-user.php" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
          <i class="fas fa-user-plus mr-2"></i>Add New User
        </a>
      </div>

      <!-- Alerts -->
      <?php if (!empty($message)): ?>
        <div class="bg-<?php echo $messageType === 'success' ? 'green' : 'red'; ?>-100 border-l-4 border-<?php echo $messageType === 'success' ? 'green' : 'red'; ?>-500 text-<?php echo $messageType === 'success' ? 'green' : 'red'; ?>-700 p-4 rounded-lg mb-6 flex justify-between items-center" role="alert">
          <span><?php echo htmlspecialchars($message); ?></span>
          <button class="text-<?php echo $messageType === 'success' ? 'green' : 'red'; ?>-700 hover:text-<?php echo $messageType === 'success' ? 'green' : 'red'; ?>-900 focus:outline-none focus:ring-2 focus:ring-<?php echo $messageType === 'success' ? 'green' : 'red'; ?>-500" onclick="this.parentElement.remove()" aria-label="Close alert">
            <i class="fas fa-times"></i>
          </button>
        </div>
      <?php endif; ?>

      <!-- Users Table -->
      <div class="overflow-x-auto">
        <table class="min-w-full bg-white border border-gray-200 rounded-lg">
          <thead>
            <tr class="bg-gray-100">
              <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">ID</th>
              <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Name</th>
              <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Email</th>
              <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">User Type</th>
              <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Registered On</th>
              <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <?php if (empty($users)): ?>
              <tr>
                <td colspan="6" class="py-4 px-4 text-center text-gray-500">No users found</td>
              </tr>
            <?php else: ?>
              <?php foreach ($users as $user): ?>
                <tr class="hover:bg-gray-50">
                  <td class="py-2 px-4"><?php echo $user['id']; ?></td>
                  <td class="py-2 px-4"><?php echo htmlspecialchars($user['full_name']); ?></td>
                  <td class="py-2 px-4"><?php echo htmlspecialchars($user['email']); ?></td>
                  <td class="py-2 px-4">
                    <?php if ($user['user_type'] == 'admin'): ?>
                      <span class="badge badge-danger">Admin</span>
                    <?php else: ?>
                      <span class="badge badge-info">User</span>
                    <?php endif; ?>
                  </td>
                  <td class="py-2 px-4"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                  <td class="py-2 px-4">
                    <a href="edit-user.php?id=<?php echo $user['id']; ?>" class="bg-indigo-600 text-white px-3 py-1 rounded-md hover:bg-indigo-700 mr-2">
                      <i class="fas fa-edit"></i>
                    </a>
                    <button class="bg-red-500 text-white px-3 py-1 rounded-md hover:bg-red-600 delete-user" data-id="<?php echo $user['id']; ?>" data-name="<?php echo htmlspecialchars($user['full_name']); ?>">
                      <i class="fas fa-trash"></i>
                    </button>
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
      // Sidebar elements (aligned with other pages)
      const sidebar = document.getElementById('sidebar');
      const sidebarOverlay = document.getElementById('sidebar-overlay');
      const sidebarToggle = document.getElementById('sidebarToggle');
      const sidebarClose = document.getElementById('sidebar-close');
      const dashboardHeader = document.getElementById('dashboardHeader');

      // User dropdown elements
      const userDropdownButton = document.getElementById('userDropdownButton');
      const userDropdownMenu = document.getElementById('userDropdownMenu');

      // Error handling for missing elements
      if (!sidebar || !sidebarOverlay || !sidebarToggle || !sidebarClose) {
        console.warn('One or more sidebar elements are missing.');
        return;
      }
      if (!userDropdownButton || !userDropdownMenu) {
        console.warn('User dropdown elements are missing.');
        return;
      }
      if (!dashboardHeader) {
        console.warn('Dashboard header element is missing.');
        return;
      }

      // Sidebar toggle function
      const toggleSidebar = () => {
        sidebar.classList.toggle('-translate-x-full');
        sidebarOverlay.classList.toggle('hidden');
        sidebarToggle.classList.toggle('hidden');
      };

      // Open sidebar
      sidebarToggle.addEventListener('click', toggleSidebar);

      // Close sidebar
      sidebarClose.addEventListener('click', toggleSidebar);

      // Close sidebar via overlay
      sidebarOverlay.addEventListener('click', toggleSidebar);

      // Open sidebar on Dashboard header click
      dashboardHeader.addEventListener('click', () => {
        if (sidebar.classList.contains('-translate-x-full')) {
          toggleSidebar();
        }
      });

      // User dropdown toggle
      userDropdownButton.addEventListener('click', () => {
        userDropdownMenu.classList.toggle('hidden');
      });

      // Close dropdown when clicking outside
      document.addEventListener('click', (event) => {
        if (!userDropdownButton.contains(event.target) && !userDropdownMenu.contains(event.target)) {
          userDropdownMenu.classList.add('hidden');
        }
      });

      // Handle delete user with SweetAlert2
      document.querySelectorAll('.delete-user').forEach(button => {
        button.addEventListener('click', function() {
          const userId = this.getAttribute('data-id');
          const userName = this.getAttribute('data-name');

          Swal.fire({
            title: 'Are you sure?',
            text: `Do you want to delete the user ${userName}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Delete',
            cancelButtonText: 'Cancel'
          }).then((result) => {
            if (result.isConfirmed) {
              window.location.href = `all-users.php?delete=${userId}`;
            }
          });
        });
      });
    });
  </script>
</body>

</html>