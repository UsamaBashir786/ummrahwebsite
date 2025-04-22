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
  <title>All Users | UmrahFlights Admin</title>
  <?php include 'includes/css-links.php'; ?>
  <link rel="stylesheet" href="assets/css/admin-style.css">
</head>

<body>
  <div class="admin-container">
    <?php include 'includes/sidebar.php'; ?>

    <div class="content">
      <?php include 'includes/header.php'; ?>

      <div class="container-fluid p-4">
        <div class="row mb-4">
          <div class="col">
            <h2 class="page-title">All Users</h2>
          </div>
          <div class="col-auto">
            <a href="add-user.php" class="btn btn-primary">
              <i class="fas fa-user-plus"></i> Add New User
            </a>
          </div>
        </div>

        <?php if (!empty($message)): ?>
          <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        <?php endif; ?>

        <div class="card shadow">
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>User Type</th>
                    <th>Registered On</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($users)): ?>
                    <tr>
                      <td colspan="6" class="text-center">No users found</td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($users as $user): ?>
                      <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                          <?php if ($user['user_type'] == 'admin'): ?>
                            <span class="badge bg-danger">Admin</span>
                          <?php else: ?>
                            <span class="badge bg-info">User</span>
                          <?php endif; ?>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                        <td>
                          <a href="edit-user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary">
                            <i class="fas fa-edit"></i>
                          </a>
                          <a href="#" class="btn btn-sm btn-danger delete-user" data-id="<?php echo $user['id']; ?>" data-name="<?php echo htmlspecialchars($user['full_name']); ?>">
                            <i class="fas fa-trash"></i>
                          </a>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Delete Confirmation Modal -->
  <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          Are you sure you want to delete the user <span id="userName"></span>?
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <a href="#" id="confirmDelete" class="btn btn-danger">Delete</a>
        </div>
      </div>
    </div>
  </div>

  <?php include 'includes/js-links.php'; ?>

  <script>
    $(document).ready(function() {
      // Setup DataTable
      $('.table').DataTable({
        "responsive": true,
        "order": [
          [4, "desc"]
        ]
      });

      // Handle delete user
      $('.delete-user').click(function(e) {
        e.preventDefault();
        const userId = $(this).data('id');
        const userName = $(this).data('name');

        $('#userName').text(userName);
        $('#confirmDelete').attr('href', 'all-users.php?delete=' + userId);
        $('#deleteModal').modal('show');
      });
    });
  </script>
</body>

</html>