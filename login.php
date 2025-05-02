<?php
// Start session and include database connection
session_start();
require_once 'config/db.php';

// Initialize variables
$errors = [];

// Process form when submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  // Sanitize and validate inputs
  $email = trim($_POST['email']);
  $password = trim($_POST['password']);

  // Validate inputs
  if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Valid email is required";
  }

  if (empty($password)) {
    $errors[] = "Password is required";
  }

  if (empty($errors)) {
    try {
      // Check database connection
      if (!$conn) {
        throw new Exception("Database connection failed");
      }

      // Use prepared statement to prevent SQL injection
      $query = "SELECT id, full_name, email, password FROM users WHERE email = ?";
      $stmt = $conn->prepare($query);
      if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
      }

      $stmt->bind_param("s", $email);
      $stmt->execute();
      $result = $stmt->get_result();

      if ($result === false) {
        throw new Exception("Query execution failed: " . $conn->error);
      }

      if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Verify password
        if (password_verify($password, $user['password'])) {
          // Set session variables
          $_SESSION['user_id'] = $user['id'];
          $_SESSION['full_name'] = $user['full_name'];
          $_SESSION['email'] = $user['email'];

          // Redirect to dashboard or protected page only on success
          header("Location: index.php");
          exit;
        } else {
          $errors[] = "Invalid email or password";
        }
      } else {
        $errors[] = "No account found with this email";
      }

      $stmt->close();
    } catch (Exception $e) {
      $errors[] = "An error occurred while processing your request: " . $e->getMessage();
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Umrah Journey</title>
  <link rel="stylesheet" href="src/output.css">
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
  <?php include 'includes/css-links.php'; ?>
</head>

<body class="bg-gray-50 flex items-center justify-center min-h-screen p-4">
  <?php include 'includes/navbar.php'; ?>
  <div class="bg-white p-8 rounded-xl shadow-md w-full max-w-md mt-10">
    <h2 class="text-2xl font-bold text-center text-gray-800 mb-2">Umrah Portal Login</h2>

    <?php if (!empty($errors)): ?>
      <div class="mb-6 bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded">
        <?php foreach ($errors as $error): ?>
          <p class="text-sm"><?php echo htmlspecialchars($error); ?></p>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
      <div class="mb-5">
        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
        <div class="relative">
          <input type="email" id="email" name="email" class="w-full px-4 py-3 border border-gray-200 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 transition duration-300" placeholder="you@example.com" required>
          <span class="absolute inset-y-0 left-0 flex items-center pl-3">
            <i class="fas fa-envelope text-gray-400"></i>
          </span>
        </div>
      </div>
      <div class="mb-6">
        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
        <div class="relative">
          <input type="password" id="password" name="password" class="w-full px-4 py-3 border border-gray-200 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 transition duration-300" placeholder="••••••••" required>
          <span class="absolute inset-y-0 left-0 flex items-center pl-3">
            <i class="fas fa-lock text-gray-400"></i>
          </span>
          <button type="button" id="togglePassword" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600">
            <i class="fas fa-eye"></i>
          </button>
        </div>
      </div>
      <div class="flex items-center justify-between mb-6">
        <label class="flex items-center">
          <input type="checkbox" class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
          <span class="ml-2 text-sm text-gray-600">Remember me</span>
        </label>
        <a href="#" class="text-sm text-green-600 hover:underline">Forgot Password?</a>
      </div>
      <button type="submit" class="w-full bg-green-600 text-white py-3 px-4 rounded-lg shadow-sm hover:bg-green-700 transition duration-300">Sign In</button>
    </form>
    <p class="mt-6 text-center text-sm text-gray-600">
      Don't have an account? <a href="register.php" class="text-green-600 font-medium hover:underline">Register here</a>
    </p>
  </div>

  <?php include 'includes/js-links.php'; ?>
  <script>
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    togglePassword.addEventListener('click', () => {
      const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
      passwordInput.setAttribute('type', type);
      togglePassword.querySelector('i').classList.toggle('fa-eye');
      togglePassword.querySelector('i').classList.toggle('fa-eye-slash');
    });
  </script>
</body>

</html>