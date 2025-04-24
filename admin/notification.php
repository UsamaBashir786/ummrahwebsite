<?php
require_once '../config/db.php'; // Include database connection
session_name('admin_session');
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
  header('Location: login.php');
  exit;
}

// Initialize variables
$success_message = '';
$error_message = '';
$filter_type = isset($_GET['type']) ? $conn->real_escape_string($_GET['type']) : '';
$filter_status = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';

// Build query with filters
$query = "SELECT id, type, message, related_id, status, created_at FROM notifications WHERE 1=1";
$params = [];
$types = '';

if ($filter_type && in_array($filter_type, ['flight_booking', 'transportation_booking', 'hotel_booking', 'package_booking', 'cancellation'])) {
  $query .= " AND type = ?";
  $params[] = $filter_type;
  $types .= 's';
}

if ($filter_status && in_array($filter_status, ['unread', 'read'])) {
  $query .= " AND status = ?";
  $params[] = $filter_status;
  $types .= 's';
}

$query .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
  $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$notifications = [];
while ($row = $result->fetch_assoc()) {
  $notifications[] = $row;
}
$stmt->close();

// Handle bulk mark as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
  $update_query = "UPDATE notifications SET status = 'read' WHERE status = 'unread'";
  if ($conn->query($update_query)) {
    $success_message = 'All notifications marked as read!';
    header('Location: notifications.php'); // Refresh to show updated statuses
    exit;
  } else {
    $error_message = 'Failed to mark all notifications as read: ' . $conn->error;
  }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notifications | UmrahFlights</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.tailwindcss.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/dataTables.tailwindcss.min.js"></script>
  <link rel="stylesheet" href="assets/css/index.css">
  <style>
    .dataTables_wrapper .dataTables_filter input {
      border: 1px solid #d1d5db;
      border-radius: 0.375rem;
      padding: 0.5rem;
      margin-bottom: 1rem;
    }

    .dataTables_wrapper .dataTables_length select {
      border: 1px solid #d1d5db;
      border-radius: 0.375rem;
      padding: 0.5rem;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button {
      background: #3b82f6;
      color: white;
      padding: 0.5rem 1rem;
      margin: 0 0.25rem;
      border-radius: 0.375rem;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
      background: #2563eb;
    }

    .filter-btn {
      padding: 0.5rem 1rem;
      border-radius: 0.375rem;
      font-weight: 600;
      transition: all 0.3s ease;
    }

    .filter-btn.active {
      background-color: #3b82f6;
      color: white;
    }

    .filter-btn:not(.active) {
      background-color: #e2e8f0;
      color: #1e293b;
    }

    .filter-btn:hover:not(.active) {
      background-color: #cbd5e1;
    }
  </style>
</head>

<body class="bg-gray-100 font-sans">
  <?php include 'includes/sidebar.php'; ?>
  <main class="ml-0 md:ml-64 p-6 min-h-screen" role="main" aria-label="Main content">
    <nav class="flex items-center justify-between bg-white shadow-md p-4 rounded-lg mb-6">
      <div class="flex items-center">
        <button id="sidebarToggle" class="md:hidden text-gray-600 hover:text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500" aria-label="Toggle sidebar">
          <i class="fas fa-bars text-xl"></i>
        </button>
        <h1 class="text-xl font-semibold text-gray-800 ml-4">Notifications</h1>
      </div>
      <div class="flex items-center space-x-4">
        <div class="relative">
          <button id="userDropdown" class="flex items-center text-gray-600 hover:text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500" aria-label="User menu" aria-expanded="false">
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

    <section class="bg-white p-6 rounded-lg shadow-md" aria-label="Notifications management">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">All Notifications</h2>
        <form method="POST" action="">
          <button type="submit" name="mark_all_read" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <i class="fas fa-check mr-2"></i> Mark All as Read
          </button>
        </form>
      </div>

      <!-- Filters -->
      <div class="flex flex-wrap gap-4 mb-6">
        <div>
          <label for="type_filter" class="block text-sm font-medium text-gray-700">Filter by Type</label>
          <select id="type_filter" name="type" onchange="applyFilters()" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            <option value="" <?php echo $filter_type === '' ? 'selected' : ''; ?>>All Types</option>
            <option value="flight_booking" <?php echo $filter_type === 'flight_booking' ? 'selected' : ''; ?>>Flight Booking</option>
            <option value="transportation_booking" <?php echo $filter_type === 'transportation_booking' ? 'selected' : ''; ?>>Transportation Booking</option>
            <option value="hotel_booking" <?php echo $filter_type === 'hotel_booking' ? 'selected' : ''; ?>>Hotel Booking</option>
            <option value="package_booking" <?php echo $filter_type === 'package_booking' ? 'selected' : ''; ?>>Package Booking</option>
            <option value="cancellation" <?php echo $filter_type === 'cancellation' ? 'selected' : ''; ?>>Cancellation</option>
          </select>
        </div>
        <div>
          <label for="status_filter" class="block text-sm font-medium text-gray-700">Filter by Status</label>
          <select id="status_filter" name="status" onchange="applyFilters()" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            <option value="" <?php echo $filter_status === '' ? 'selected' : ''; ?>>All Statuses</option>
            <option value="unread" <?php echo $filter_status === 'unread' ? 'selected' : ''; ?>>Unread</option>
            <option value="read" <?php echo $filter_status === 'read' ? 'selected' : ''; ?>>Read</option>
          </select>
        </div>
      </div>

      <!-- Notifications Table -->
      <div class="overflow-x-auto">
        <table id="notificationsTable" class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-blue-600 text-white">
              <th class="p-3">ID</th>
              <th class="p-3">Type</th>
              <th class="p-3">Message</th>
              <th class="p-3">Related ID</th>
              <th class="p-3">Status</th>
              <th class="p-3">Created At</th>
              <th class="p-3">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($notifications as $notification): ?>
              <tr class="hover:bg-gray-50">
                <td class="p-3"><?php echo htmlspecialchars($notification['id']); ?></td>
                <td class="p-3"><?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($notification['type']))); ?></td>
                <td class="p-3"><?php echo htmlspecialchars($notification['message']); ?></td>
                <td class="p-3"><?php echo htmlspecialchars($notification['related_id'] ?: '-'); ?></td>
                <td class="p-3">
                  <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $notification['status'] === 'unread' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'; ?>">
                    <?php echo ucfirst(htmlspecialchars($notification['status'])); ?>
                  </span>
                </td>
                <td class="p-3"><?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?></td>
                <td class="p-3">
                  <button class="toggle-read text-blue-500 hover:text-blue-700" data-id="<?php echo $notification['id']; ?>" data-status="<?php echo $notification['status']; ?>" title="<?php echo $notification['status'] === 'unread' ? 'Mark as Read' : 'Mark as Unread'; ?>">
                    <i class="fas <?php echo $notification['status'] === 'unread' ? 'fa-eye' : 'fa-eye-slash'; ?>"></i>
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>

  <script>
    // Initialize DataTables
    $(document).ready(function() {
      $('#notificationsTable').DataTable({
        responsive: true,
        pageLength: 10,
        order: [
          [5, 'desc']
        ], // Sort by Created At (descending)
        language: {
          search: "Search notifications:",
          lengthMenu: "Show _MENU_ entries"
        }
      });
    });

    // Apply filters
    function applyFilters() {
      const type = document.getElementById('type_filter').value;
      const status = document.getElementById('status_filter').value;
      const url = new URL(window.location.href);
      if (type) url.searchParams.set('type', type);
      else url.searchParams.delete('type');
      if (status) url.searchParams.set('status', status);
      else url.searchParams.delete('status');
      window.location.href = url.toString();
    }

    // Toggle read/unread status
    document.querySelectorAll('.toggle-read').forEach(button => {
      button.addEventListener('click', function() {
        const notificationId = this.dataset.id;
        const currentStatus = this.dataset.status;
        const newStatus = currentStatus === 'unread' ? 'read' : 'unread';

        fetch('mark_notification_read.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `notification_id=${notificationId}&status=${newStatus}`
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              Swal.fire({
                icon: 'success',
                title: 'Success',
                text: `Notification marked as ${newStatus}!`,
                showConfirmButton: false,
                timer: 1500
              }).then(() => {
                location.reload();
              });
            } else {
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.error || 'Failed to update notification status'
              });
            }
          })
          .catch(error => {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: 'An unexpected error occurred'
            });
          });
      });
    });

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
  </script>
</body>

</html>