<?php
// Start admin session with custom name
session_name('admin_session');
session_start();

// Redirect if already logged in
if (isset($_SESSION['admin_loggedin']) && $_SESSION['admin_loggedin'] === true) {
  header('Location: index.php');
  exit;
}

// Predefined admin credentials
$admin_email = 'admin@admin.com';
$admin_password = 'admin123'; // In real app, store hashed password

// Process login if form submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $email = trim($_POST['email']);
  $password = trim($_POST['password']);

  // Validate credentials
  if ($email === $admin_email && $password === $admin_password) {
    // Regenerate session ID for security
    session_regenerate_id(true);

    // Set admin session variables
    $_SESSION['admin_loggedin'] = true;
    $_SESSION['admin_email'] = $email;
    $_SESSION['admin_last_login'] = time();

    // Redirect to dashboard
    header('Location: index.php');
    exit;
  } else {
    // Invalid credentials
    header('Location: admin-login.php?error=invalid');
    exit;
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login | UmrahFlights</title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="assets/css/login.css">
</head>

<body>
  <div class="login-card">
    <div class="login-header">
      <div class="logo-icon">
        <i class="fas fa-plane-departure"></i>
      </div>
      <h4>UmrahFlights Admin</h4>
      <p class="mb-0">Please login to your account</p>
    </div>
    <div class="login-body">
      <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <?php
          $error = $_GET['error'];
          if ($error == 'invalid') {
            echo 'Invalid email or password. Please try again.';
          } else {
            echo 'An error occurred. Please try again.';
          }
          ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
        <div class="form-floating mb-3">
          <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" required>
          <label for="email">Email address</label>
        </div>
        <div class="form-floating mb-3">
          <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
          <label for="password">Password</label>
        </div>
        <div class="d-flex justify-content-between mb-4">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" value="1" id="rememberMe" name="remember">
            <label class="form-check-label" for="rememberMe">
              Remember me
            </label>
          </div>
        </div>
        <div class="d-grid">
          <button type="submit" class="btn btn-primary btn-login">Sign In</button>
        </div>
      </form>
      <div class="back-to-site">
        <a href="../index.php" class="text-decoration-none">
          <i class="fas fa-arrow-left me-1"></i> Back to website
        </a>
      </div>
    </div>
  </div>

  <!-- Bootstrap 5 JS Bundle with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>