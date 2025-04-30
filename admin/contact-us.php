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

// Initialize variables
$success_message = '';
$error_message = '';
$messages = [];
$total_messages = 0;
$total_pages = 1;
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = 10;
$offset = ($current_page - 1) * $items_per_page;
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

// Handle message status update
if (isset($_POST['action']) && $_POST['action'] === 'update_status' && isset($_POST['message_id'], $_POST['status'])) {
  $message_id = (int)$_POST['message_id'];
  $status = $_POST['status'];
  $admin_notes = isset($_POST['admin_notes']) ? $_POST['admin_notes'] : '';

  if (in_array($status, ['unread', 'read', 'replied', 'archived'])) {
    $stmt = $conn->prepare("UPDATE contact_messages SET status = ?, admin_notes = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("ssi", $status, $admin_notes, $message_id);

    if ($stmt->execute()) {
      $success_message = "Message status updated successfully.";
    } else {
      $error_message = "Error updating message status: " . $conn->error;
    }

    $stmt->close();
  } else {
    $error_message = "Invalid status value.";
  }
}

// Handle message deletion
if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['message_id'])) {
  $message_id = (int)$_POST['message_id'];

  $stmt = $conn->prepare("DELETE FROM contact_messages WHERE id = ?");
  $stmt->bind_param("i", $message_id);

  if ($stmt->execute()) {
    $success_message = "Message deleted successfully.";
  } else {
    $error_message = "Error deleting message: " . $conn->error;
  }

  $stmt->close();
}

// Build the query based on filters
$query = "SELECT * FROM contact_messages WHERE 1=1";
$count_query = "SELECT COUNT(*) as total FROM contact_messages WHERE 1=1";
$query_params = [];
$param_types = "";

if (!empty($filter_status)) {
  $query .= " AND status = ?";
  $count_query .= " AND status = ?";
  $query_params[] = $filter_status;
  $param_types .= "s";
}

if (!empty($search_term)) {
  $search_term_like = "%$search_term%";
  $query .= " AND (name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)";
  $count_query .= " AND (name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)";
  $query_params[] = $search_term_like;
  $query_params[] = $search_term_like;
  $query_params[] = $search_term_like;
  $query_params[] = $search_term_like;
  $param_types .= "ssss";
}

// Get total count for pagination
$stmt = $conn->prepare($count_query);
if (!empty($query_params)) {
  $stmt->bind_param($param_types, ...$query_params);
}
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_messages = $row['total'];
$total_pages = ceil($total_messages / $items_per_page);
$stmt->close();

// Finalize and execute the main query
$query .= " ORDER BY created_at DESC LIMIT ?, ?";
$query_params[] = $offset;
$query_params[] = $items_per_page;
$param_types .= "ii";

$stmt = $conn->prepare($query);
if (!empty($query_params)) {
  $stmt->bind_param($param_types, ...$query_params);
}
$stmt->execute();
$result = $stmt->get_result();

// Store results in array
while ($row = $result->fetch_assoc()) {
  $messages[] = $row;
}
$stmt->close();
?>
\
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contact Messages | UmrahFlights Admin</title>
  <!-- Tailwind CSS -->
  <link rel="stylesheet" href="../src/output.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <style>
    .message-card {
      transition: all 0.2s ease;
    }

    .message-card:hover {
      transform: translateY(-3px);
    }

    .message-preview {
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
      text-overflow: ellipsis;
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
            <i class="fas fa-envelope text-indigo-600 mr-2"></i> Contact Messages
          </h4>
        </div>

        <div class="flex items-center space-x-4">
          <!-- Notification -->
          <div class="relative">
            <button class="flex items-center text-gray-500 hover:text-gray-700 focus:outline-none">
              <i class="fas fa-bell text-xl"></i>
              <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                <?php echo count(array_filter($messages, function ($msg) {
                  return $msg['status'] === 'unread';
                })); ?>
              </span>
            </button>
          </div>

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
    <?php if (!empty($success_message)): ?>
      <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-6 flex justify-between items-center" role="alert">
        <span><?php echo htmlspecialchars($success_message); ?></span>
        <button class="text-green-700 hover:text-green-900 focus:outline-none focus:ring-2 focus:ring-green-500" onclick="this.parentElement.remove()" aria-label="Close alert">
          <i class="fas fa-times"></i>
        </button>
      </div>
    <?php endif; ?>
    <?php if (!empty($error_message)): ?>
      <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-6 flex justify-between items-center" role="alert">
        <span><?php echo htmlspecialchars($error_message); ?></span>
        <button class="text-red-700 hover:text-red-900 focus:outline-none focus:ring-2 focus:ring-red-500" onclick="this.parentElement.remove()" aria-label="Close alert">
          <i class="fas fa-times"></i>
        </button>
      </div>
    <?php endif; ?>

    <!-- Filters and Search -->
    <div class="bg-white shadow-lg rounded-lg p-6 mb-6">
      <form method="GET" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
        <div class="md:col-span-4">
          <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Filter by Status</label>
          <select name="status" id="status" class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            <option value="">All Messages</option>
            <option value="unread" <?php echo $filter_status === 'unread' ? 'selected' : ''; ?>>Unread</option>
            <option value="read" <?php echo $filter_status === 'read' ? 'selected' : ''; ?>>Read</option>
            <option value="replied" <?php echo $filter_status === 'replied' ? 'selected' : ''; ?>>Replied</option>
            <option value="archived" <?php echo $filter_status === 'archived' ? 'selected' : ''; ?>>Archived</option>
          </select>
        </div>
        <div class="md:col-span-6">
          <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
          <div class="relative">
            <input type="text" class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent" id="search" name="search" placeholder="Search by name, email, subject or message" value="<?php echo htmlspecialchars($search_term); ?>">
            <div class="absolute inset-y-0 right-0 flex items-center pr-3">
              <i class="fas fa-search text-gray-400"></i>
            </div>
          </div>
        </div>
        <div class="md:col-span-2">
          <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            Apply Filters
          </button>
        </div>
      </form>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
      <?php
      // Count messages by status
      $status_counts = [
        'unread' => 0,
        'read' => 0,
        'replied' => 0,
        'archived' => 0
      ];

      $stmt = $conn->query("SELECT status, COUNT(*) as count FROM contact_messages GROUP BY status");
      while ($row = $stmt->fetch_assoc()) {
        if (isset($status_counts[$row['status']])) {
          $status_counts[$row['status']] = $row['count'];
        }
      }
      ?>

      <div class="bg-white shadow-lg rounded-lg p-6 border-l-4 border-indigo-500">
        <div class="flex justify-between items-center">
          <div>
            <h3 class="text-lg font-semibold text-gray-800">Total Messages</h3>
            <p class="text-2xl font-bold text-indigo-600"><?php echo $total_messages; ?></p>
          </div>
          <div class="flex items-center justify-center w-12 h-12 rounded-full bg-indigo-100 text-indigo-500">
            <i class="fas fa-envelope text-xl"></i>
          </div>
        </div>
      </div>

      <div class="bg-white shadow-lg rounded-lg p-6 border-l-4 border-red-500">
        <div class="flex justify-between items-center">
          <div>
            <h3 class="text-lg font-semibold text-gray-800">Unread</h3>
            <p class="text-2xl font-bold text-red-600"><?php echo $status_counts['unread']; ?></p>
          </div>
          <div class="flex items-center justify-center w-12 h-12 rounded-full bg-red-100 text-red-500">
            <i class="fas fa-bell text-xl"></i>
          </div>
        </div>
      </div>

      <div class="bg-white shadow-lg rounded-lg p-6 border-l-4 border-green-500">
        <div class="flex justify-between items-center">
          <div>
            <h3 class="text-lg font-semibold text-gray-800">Replied</h3>
            <p class="text-2xl font-bold text-green-600"><?php echo $status_counts['replied']; ?></p>
          </div>
          <div class="flex items-center justify-center w-12 h-12 rounded-full bg-green-100 text-green-500">
            <i class="fas fa-reply text-xl"></i>
          </div>
        </div>
      </div>

      <div class="bg-white shadow-lg rounded-lg p-6 border-l-4 border-gray-500">
        <div class="flex justify-between items-center">
          <div>
            <h3 class="text-lg font-semibold text-gray-800">Archived</h3>
            <p class="text-2xl font-bold text-gray-600"><?php echo $status_counts['archived']; ?></p>
          </div>
          <div class="flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 text-gray-500">
            <i class="fas fa-archive text-xl"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Messages List -->
    <div class="bg-white shadow-lg rounded-lg overflow-hidden mb-6">
      <div class="p-4 border-b border-gray-200 bg-gray-50">
        <div class="flex items-center">
          <i class="fas fa-envelope text-indigo-600 mr-2"></i>
          <h3 class="text-lg font-medium text-gray-700">Contact Messages</h3>
        </div>
      </div>
      <div class="p-6">
        <?php if (empty($messages)): ?>
          <div class="bg-blue-50 border-l-4 border-blue-500 text-blue-700 p-4 rounded" role="alert">
            <div class="flex">
              <div class="py-1"><i class="fas fa-info-circle text-blue-500 mr-2"></i></div>
              <div>No messages found matching your criteria.</div>
            </div>
          </div>
        <?php else: ?>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-5">#</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-15">Name</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-15">Email</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-15">Subject</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-20">Message</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-10">Date</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-10">Status</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-10">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($messages as $index => $message): ?>
                  <tr class="message-card hover:bg-gray-50 <?php echo $message['status'] === 'unread' ? 'font-semibold bg-gray-50' : ''; ?>">
                    <td class="px-6 py-4 whitespace-nowrap text-sm"><?php echo $offset + $index + 1; ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm"><?php echo htmlspecialchars($message['name']); ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                      <a href="mailto:<?php echo htmlspecialchars($message['email']); ?>" class="text-indigo-600 hover:text-indigo-900">
                        <?php echo htmlspecialchars($message['email']); ?>
                      </a>
                      <?php if (!empty($message['phone'])): ?>
                        <div class="text-xs text-gray-500">
                          <i class="fas fa-phone-alt mr-1"></i> <?php echo htmlspecialchars($message['phone']); ?>
                        </div>
                      <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm"><?php echo htmlspecialchars($message['subject'] ?: 'No Subject'); ?></td>
                    <td class="px-6 py-4 text-sm message-preview"><?php echo htmlspecialchars($message['message']); ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('M d, Y', strtotime($message['created_at'])); ?></td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                        <?php
                        switch ($message['status']) {
                          case 'unread':
                            echo 'bg-red-100 text-red-800';
                            break;
                          case 'read':
                            echo 'bg-indigo-100 text-indigo-800';
                            break;
                          case 'replied':
                            echo 'bg-green-100 text-green-800';
                            break;
                          case 'archived':
                            echo 'bg-gray-100 text-gray-800';
                            break;
                          default:
                            echo 'bg-gray-100 text-gray-800';
                        }
                        ?>">
                        <?php echo ucfirst($message['status']); ?>
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      <div class="flex space-x-2">
                        <button type="button" class="view-message text-indigo-600 hover:text-indigo-900" data-bs-toggle="modal" data-bs-target="#viewMessageModal" data-message='<?php echo json_encode($message); ?>'>
                          <i class="fas fa-eye"></i>
                        </button>
                        <button type="button" class="delete-message text-red-600 hover:text-red-900" data-bs-toggle="modal" data-bs-target="#deleteMessageModal" data-id="<?php echo $message['id']; ?>">
                          <i class="fas fa-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <?php if ($total_pages > 1): ?>
            <div class="mt-6 flex justify-center">
              <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                <a href="?page=<?php echo $current_page - 1; ?>&status=<?php echo urlencode($filter_status); ?>&search=<?php echo urlencode($search_term); ?>"
                  class="<?php echo $current_page <= 1 ? 'opacity-50 cursor-not-allowed' : ''; ?> relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                  <span class="sr-only">Previous</span>
                  <i class="fas fa-chevron-left"></i>
                </a>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                  <a href="?page=<?php echo $i; ?>&status=<?php echo urlencode($filter_status); ?>&search=<?php echo urlencode($search_term); ?>"
                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium <?php echo $current_page == $i ? 'text-indigo-600 bg-indigo-50 border-indigo-500 z-10' : 'text-gray-700 hover:bg-gray-50'; ?>">
                    <?php echo $i; ?>
                  </a>
                <?php endfor; ?>

                <a href="?page=<?php echo $current_page + 1; ?>&status=<?php echo urlencode($filter_status); ?>&search=<?php echo urlencode($search_term); ?>"
                  class="<?php echo $current_page >= $total_pages ? 'opacity-50 cursor-not-allowed' : ''; ?> relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                  <span class="sr-only">Next</span>
                  <i class="fas fa-chevron-right"></i>
                </a>
              </nav>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- View Message Modal -->
  <div class="fixed inset-0 bg-gray-500 bg-opacity-75 hidden items-center justify-center z-50" id="viewMessageModal">
    <div class="bg-white rounded-lg shadow-xl overflow-hidden max-w-4xl w-full mx-4">
      <div class="flex justify-between items-center p-6 bg-gray-50 border-b">
        <h5 class="text-lg font-semibold text-gray-900">Message Details</h5>
        <button type="button" class="text-gray-500 hover:text-gray-700 focus:outline-none" id="closeModal">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <div class="p-6">
        <div class="flex flex-col md:flex-row gap-6">
          <div class="md:w-2/3">
            <div class="mb-6">
              <h5 class="text-xl font-semibold text-gray-900 subject-display">Subject Title</h5>
              <div class="text-gray-600">
                From: <span class="name-display">Sender Name</span>
                &lt;<span class="email-display">email@example.com</span>&gt;
                <?php if (!empty($message['phone'])): ?>
                  | <span class="phone-display">Phone Number</span>
                <?php endif; ?>
              </div>
              <div class="text-sm text-gray-500 date-display">Date and Time</div>
            </div>

            <div class="bg-gray-50 rounded-lg p-4 mb-6">
              <p class="message-display">Message content will appear here...</p>
            </div>

            <div class="mb-6">
              <h6 class="font-medium text-gray-700">Additional Information</h6>
              <div class="text-sm text-gray-500">
                <div>IP Address: <span class="ip-display">127.0.0.1</span></div>
              </div>
            </div>
          </div>

          <div class="md:w-1/3">
            <form method="POST" id="statusUpdateForm" class="space-y-4">
              <input type="hidden" name="action" value="update_status">
              <input type="hidden" name="message_id" id="message_id_input" value="">

              <div>
                <label for="status_select" class="block text-sm font-medium text-gray-700 mb-1">Update Status</label>
                <select class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent" id="status_select" name="status">
                  <option value="unread">Unread</option>
                  <option value="read">Read</option>
                  <option value="replied">Replied</option>
                  <option value="archived">Archived</option>
                </select>
              </div>

              <div>
                <label for="admin_notes" class="block text-sm font-medium text-gray-700 mb-1">Admin Notes</label>
                <textarea class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent" id="admin_notes" name="admin_notes" rows="4" placeholder="Add private notes about this contact"></textarea>
              </div>

              <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Update Status
              </button>

              <div class="border-t border-gray-200 pt-4">
                <a href="#" class="reply-email w-full inline-flex items-center justify-center px-4 py-2 border border-indigo-500 text-sm font-medium rounded-md text-indigo-700 bg-indigo-50 hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                  <i class="fas fa-reply mr-2"></i> Reply via Email
                </a>
              </div>
            </form>
          </div>
        </div>
      </div>
      <div class="p-4 bg-gray-50 border-t">
        <div class="flex justify-end">
          <button type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" id="closeModalBtn">
            Close
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Delete Message Modal -->
  <div class="fixed inset-0 bg-gray-500 bg-opacity-75 hidden items-center justify-center z-50" id="deleteMessageModal">
    <div class="bg-white rounded-lg shadow-xl overflow-hidden max-w-md w-full mx-4">
      <div class="p-6">
        <div class="flex justify-center mb-4">
          <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
            <i class="fas fa-exclamation-triangle text-red-600 text-lg"></i>
          </div>
        </div>
        <h5 class="text-lg font-semibold text-gray-900 text-center mb-2">Confirm Deletion</h5>
        <p class="text-center text-gray-600 mb-6">Are you sure you want to delete this message? This action cannot be undone.</p>
        <div class="flex justify-center space-x-4">
          <button type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" id="cancelDelete">
            Cancel
          </button>
          <form method="POST">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="message_id" id="delete_message_id" value="">
            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
              Delete
            </button>
          </form>
        </div>
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

      // View Message Modal
      const viewMessageModal = document.getElementById('viewMessageModal');
      const viewMessageButtons = document.querySelectorAll('.view-message');
      const closeModal = document.getElementById('closeModal');
      const closeModalBtn = document.getElementById('closeModalBtn');

      viewMessageButtons.forEach(button => {
        button.addEventListener('click', function() {
          const messageData = JSON.parse(this.getAttribute('data-message'));

          // Update modal content
          document.querySelector('.subject-display').textContent = messageData.subject || 'No Subject';
          document.querySelector('.name-display').textContent = messageData.name;
          document.querySelector('.email-display').textContent = messageData.email;

          if (document.querySelector('.phone-display')) {
            document.querySelector('.phone-display').textContent = messageData.phone || 'No phone provided';
          }

          document.querySelector('.date-display').textContent = new Date(messageData.created_at).toLocaleString();
          document.querySelector('.message-display').textContent = messageData.message;
          document.querySelector('.ip-display').textContent = messageData.ip_address || 'Not recorded';

          // Set form values
          document.getElementById('message_id_input').value = messageData.id;
          document.getElementById('status_select').value = messageData.status;
          document.getElementById('admin_notes').value = messageData.admin_notes || '';

          // Set reply email link
          const replyLink = document.querySelector('.reply-email');
          replyLink.href = 'mailto:' + messageData.email + '?subject=Re: ' + (messageData.subject || 'Your Contact Form Submission');

          // Show modal
          viewMessageModal.classList.remove('hidden');
          viewMessageModal.classList.add('flex');

          // Automatically mark as read if it was unread
          if (messageData.status === 'unread') {
            document.getElementById('status_select').value = 'read';
            // Optional: Auto-submit the form to update status to 'read'
            // document.getElementById('statusUpdateForm').submit();
          }
        });
      });

      // Close View Modal
      if (closeModal) {
        closeModal.addEventListener('click', function() {
          viewMessageModal.classList.add('hidden');
          viewMessageModal.classList.remove('flex');
        });
      }

      if (closeModalBtn) {
        closeModalBtn.addEventListener('click', function() {
          viewMessageModal.classList.add('hidden');
          viewMessageModal.classList.remove('flex');
        });
      }

      // Close modal when clicking outside content
      viewMessageModal.addEventListener('click', function(e) {
        if (e.target === viewMessageModal) {
          viewMessageModal.classList.add('hidden');
          viewMessageModal.classList.remove('flex');
        }
      });

      // Delete Message Modal
      const deleteMessageModal = document.getElementById('deleteMessageModal');
      const deleteButtons = document.querySelectorAll('.delete-message');
      const cancelDelete = document.getElementById('cancelDelete');

      deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
          const messageId = this.getAttribute('data-id');
          document.getElementById('delete_message_id').value = messageId;

          // Show modal
          deleteMessageModal.classList.remove('hidden');
          deleteMessageModal.classList.add('flex');
        });
      });

      // Close Delete Modal
      if (cancelDelete) {
        cancelDelete.addEventListener('click', function() {
          deleteMessageModal.classList.add('hidden');
          deleteMessageModal.classList.remove('flex');
        });
      }

      // Close modal when clicking outside content
      deleteMessageModal.addEventListener('click', function(e) {
        if (e.target === deleteMessageModal) {
          deleteMessageModal.classList.add('hidden');
          deleteMessageModal.classList.remove('flex');
        }
      });

      // Close modals with Escape key
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
          if (!viewMessageModal.classList.contains('hidden')) {
            viewMessageModal.classList.add('hidden');
            viewMessageModal.classList.remove('flex');
          }

          if (!deleteMessageModal.classList.contains('hidden')) {
            deleteMessageModal.classList.add('hidden');
            deleteMessageModal.classList.remove('flex');
          }
        }
      });
    });
  </script>
</body>

</html>