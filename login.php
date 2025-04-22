<?php
// Start session and include database connection
session_start();
require_once 'config/db.php';

// Initialize variables
$errors = [];

// Process form when submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  // Sanitize and validate inputs
  $email = mysqli_real_escape_string($conn, trim($_POST['email']));
  $password = mysqli_real_escape_string($conn, trim($_POST['password']));

  // Validate inputs
  if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Valid email is required";
  }

  if (empty($password)) {
    $errors[] = "Password is required";
  }

  if (empty($errors)) {
    // Check if email exists and fetch user data
    $query = "SELECT id, full_name, email, password FROM users WHERE email = '$email'";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
      $user = $result->fetch_assoc();

      // Verify password
      if (password_verify($password, $user['password'])) {
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email'] = $user['email'];

        // Redirect to dashboard or protected page
        header("Location: index.php");
        exit;
      } else {
        $errors[] = "Invalid email or password";
      }
    } else {
      $errors[] = "No account found with this email";
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php'; ?>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    body{
      margin-top: 100px !important;
    }
  </style>
</head>

<body>
  <?php include 'includes/navbar.php'; ?>

  <div class="login-container my-5 m-auto">
    <h2>Login</h2>

    <?php if (!empty($errors)): ?>
      <div class="error">
        <?php foreach ($errors as $error): ?>
          <p><?php echo $error; ?></p>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
      <div class="mb-3">
        <label for="email" class="form-label">Email Address</label>
        <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
      </div>
      <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
      </div>
      <button type="submit" class="btn-login">Login</button>
      <div class="register-link">
        <p>Don't have an account? <a href="register.php">Register here</a></p>
      </div>
    </form>
  </div>

  <?php include 'includes/js-links.php'; ?>
</body>

</html>