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
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contact Messages | UmrahFlights Admin</title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="assets/css/index.css">
  <style>
    .message-card {
      transition: all 0.2s ease;
    }

    .message-card:hover {
      box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
      transform: translateY(-3px);
    }

    .status-badge {
      font-size: 0.75rem;
    }

    .status-unread {
      background-color: #dc3545;
    }

    .status-read {
      background-color: #0d6efd;
    }

    .status-replied {
      background-color: #198754;
    }

    .status-archived {
      background-color: #6c757d;
    }

    .message-preview {
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .table-responsive {
      overflow-x: auto;
    }

    .table th {
      white-space: nowrap;
    }

    .pagination {
      justify-content: center;
    }
  </style>
</head>

<body>
  <?php include 'includes/sidebar.php'; ?>
  <!-- Main Content -->
  <div class="main-content">
    <!-- Top Navbar -->
    <nav class="navbar navbar-expand-lg top-navbar mb-4">
      <div class="container-fluid">
        <button id="sidebarToggle" class="btn d-lg-none">
          <i class="fas fa-bars"></i>
        </button>
        <h4 class="mb-0 ms-2">Contact Messages</h4>

        <div class="d-flex align-items-center">
          <div class="position-relative me-3">
            <button class="btn position-relative" id="notificationBtn">
              <i class="fas fa-bell fs-5"></i>
              <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                <?php echo count(array_filter($messages, function ($msg) {
                  return $msg['status'] === 'unread';
                })); ?>
              </span>
            </button>
          </div>

          <div class="dropdown">
            <button class="btn dropdown-toggle d-flex align-items-center" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
              <div class="rounded-circle overflow-hidden me-2" style="width: 32px; height: 32px;">
                <i class="fas fa-user"></i>
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
    <?php if (!empty($success_message)): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($success_message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>
    <?php if (!empty($error_message)): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($error_message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <!-- Filters and Search -->
    <div class="card mb-4">
      <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
          <div class="col-md-4">
            <label for="status" class="form-label">Filter by Status</label>
            <select name="status" id="status" class="form-select">
              <option value="">All Messages</option>
              <option value="unread" <?php echo $filter_status === 'unread' ? 'selected' : ''; ?>>Unread</option>
              <option value="read" <?php echo $filter_status === 'read' ? 'selected' : ''; ?>>Read</option>
              <option value="replied" <?php echo $filter_status === 'replied' ? 'selected' : ''; ?>>Replied</option>
              <option value="archived" <?php echo $filter_status === 'archived' ? 'selected' : ''; ?>>Archived</option>
            </select>
          </div>
          <div class="col-md-6">
            <label for="search" class="form-label">Search</label>
            <div class="input-group">
              <input type="text" class="form-control" id="search" name="search" placeholder="Search by name, email, subject or message" value="<?php echo htmlspecialchars($search_term); ?>">
              <button class="btn btn-outline-secondary" type="submit">
                <i class="fas fa-search"></i>
              </button>
            </div>
          </div>
          <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Statistics -->
    <div class="row mb-4">
      <div class="col-xl-3 col-md-6">
        <div class="card bg-primary text-white mb-4">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h5 class="mb-0">Total Messages</h5>
                <h2 class="mt-2 mb-0"><?php echo $total_messages; ?></h2>
              </div>
              <div>
                <i class="fas fa-envelope fa-3x opacity-50"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

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

      <div class="col-xl-3 col-md-6">
        <div class="card bg-danger text-white mb-4">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h5 class="mb-0">Unread</h5>
                <h2 class="mt-2 mb-0"><?php echo $status_counts['unread']; ?></h2>
              </div>
              <div>
                <i class="fas fa-bell fa-3x opacity-50"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-xl-3 col-md-6">
        <div class="card bg-success text-white mb-4">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h5 class="mb-0">Replied</h5>
                <h2 class="mt-2 mb-0"><?php echo $status_counts['replied']; ?></h2>
              </div>
              <div>
                <i class="fas fa-reply fa-3x opacity-50"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-xl-3 col-md-6">
        <div class="card bg-secondary text-white mb-4">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h5 class="mb-0">Archived</h5>
                <h2 class="mt-2 mb-0"><?php echo $status_counts['archived']; ?></h2>
              </div>
              <div>
                <i class="fas fa-archive fa-3x opacity-50"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Messages List -->
    <div class="card mb-4">
      <div class="card-header">
        <i class="fas fa-envelope me-1"></i>
        Contact Messages
      </div>
      <div class="card-body">
        <?php if (empty($messages)): ?>
          <div class="alert alert-info" role="alert">
            <i class="fas fa-info-circle me-2"></i> No messages found matching your criteria.
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-striped table-hover">
              <thead>
                <tr>
                  <th width="5%">#</th>
                  <th width="15%">Name</th>
                  <th width="15%">Email</th>
                  <th width="15%">Subject</th>
                  <th width="20%">Message</th>
                  <th width="10%">Date</th>
                  <th width="10%">Status</th>
                  <th width="10%">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($messages as $index => $message): ?>
                  <tr class="message-card <?php echo $message['status'] === 'unread' ? 'table-light fw-bold' : ''; ?>">
                    <td><?php echo $offset + $index + 1; ?></td>
                    <td><?php echo htmlspecialchars($message['name']); ?></td>
                    <td>
                      <a href="mailto:<?php echo htmlspecialchars($message['email']); ?>" class="text-decoration-none">
                        <?php echo htmlspecialchars($message['email']); ?>
                      </a>
                      <?php if (!empty($message['phone'])): ?>
                        <div class="small text-muted">
                          <i class="fas fa-phone-alt me-1"></i> <?php echo htmlspecialchars($message['phone']); ?>
                        </div>
                      <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($message['subject'] ?: 'No Subject'); ?></td>
                    <td class="message-preview"><?php echo htmlspecialchars($message['message']); ?></td>
                    <td><?php echo date('M d, Y', strtotime($message['created_at'])); ?></td>
                    <td>
                      <span class="badge status-<?php echo $message['status']; ?>">
                        <?php echo ucfirst($message['status']); ?>
                      </span>
                    </td>
                    <td>
                      <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-primary view-message" data-bs-toggle="modal" data-bs-target="#viewMessageModal" data-message='<?php echo json_encode($message); ?>'>
                          <i class="fas fa-eye"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-danger delete-message" data-bs-toggle="modal" data-bs-target="#deleteMessageModal" data-id="<?php echo $message['id']; ?>">
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
            <nav aria-label="Page navigation">
              <ul class="pagination">
                <li class="page-item <?php echo $current_page <= 1 ? 'disabled' : ''; ?>">
                  <a class="page-link" href="?page=<?php echo $current_page - 1; ?>&status=<?php echo urlencode($filter_status); ?>&search=<?php echo urlencode($search_term); ?>" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                  </a>
                </li>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                  <li class="page-item <?php echo $current_page == $i ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo urlencode($filter_status); ?>&search=<?php echo urlencode($search_term); ?>">
                      <?php echo $i; ?>
                    </a>
                  </li>
                <?php endfor; ?>

                <li class="page-item <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>">
                  <a class="page-link" href="?page=<?php echo $current_page + 1; ?>&status=<?php echo urlencode($filter_status); ?>&search=<?php echo urlencode($search_term); ?>" aria-label="Next">
                    <span aria-hidden="true">&raquo;</span>
                  </a>
                </li>
              </ul>
            </nav>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- View Message Modal -->
  <div class="modal fade" id="viewMessageModal" tabindex="-1" aria-labelledby="viewMessageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="viewMessageModalLabel">Message Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-8">
              <div class="mb-3">
                <h5 class="subject-display">Subject Title</h5>
                <div class="text-muted">
                  From: <span class="name-display">Sender Name</span>
                  &lt;<span class="email-display">email@example.com</span>&gt;
                  <?php if (!empty($message['phone'])): ?>
                    | <span class="phone-display">Phone Number</span>
                  <?php endif; ?>
                </div>
                <div class="text-muted small date-display">Date and Time</div>
              </div>

              <div class="card mb-3">
                <div class="card-body">
                  <p class="message-display">Message content will appear here...</p>
                </div>
              </div>

              <div class="mb-3">
                <h6>Additional Information</h6>
                <div class="small text-muted">
                  <div>IP Address: <span class="ip-display">127.0.0.1</span></div>
                </div>
              </div>
            </div>

            <div class="col-md-4">
              <form method="POST" id="statusUpdateForm">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="message_id" id="message_id_input" value="">

                <div class="mb-3">
                  <label for="status_select" class="form-label">Update Status</label>
                  <select class="form-select" id="status_select" name="status">
                    <option value="unread">Unread</option>
                    <option value="read">Read</option>
                    <option value="replied">Replied</option>
                    <option value="archived">Archived</option>
                  </select>
                </div>

                <div class="mb-3">
                  <label for="admin_notes" class="form-label">Admin Notes</label>
                  <textarea class="form-control" id="admin_notes" name="admin_notes" rows="4" placeholder="Add private notes about this contact"></textarea>
                </div>

                <div class="d-grid">
                  <button type="submit" class="btn btn-primary">Update Status</button>
                </div>

                <hr>

                <div class="d-grid">
                  <a href="#" class="btn btn-outline-primary reply-email">
                    <i class="fas fa-reply me-2"></i> Reply via Email
                  </a>
                </div>
              </form>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Delete Message Modal -->
  <div class="modal fade" id="deleteMessageModal" tabindex="-1" aria-labelledby="deleteMessageModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="deleteMessageModalLabel">Confirm Deletion</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to delete this message? This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <form method="POST">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="message_id" id="delete_message_id" value="">
            <button type="submit" class="btn btn-danger">Delete</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap 5 JS Bundle with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Custom JavaScript -->
  <script src="assets/js/index.js"></script>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // View Message Modal
      const viewMessageButtons = document.querySelectorAll('.view-message');
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

          // Automatically mark as read if it was unread
          if (messageData.status === 'unread') {
            document.getElementById('status_select').value = 'read';
            // Optional: Auto-submit the form to update status to 'read'
            // document.getElementById('statusUpdateForm').submit();
          }
        });
      });

      // Delete Message Modal
      const deleteButtons = document.querySelectorAll('.delete-message');
      deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
          const messageId = this.getAttribute('data-id');
          document.getElementById('delete_message_id').value = messageId;
        });
      });
    });
  </script>
</body>

</html>