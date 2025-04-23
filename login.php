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
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Umrah Journey</title>
  <!-- Include Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <?php include 'includes/css-links.php'; ?>
</head>

<body class="bg-gray-50 pt-24">
  <?php include 'includes/navbar.php'; ?>

  <div class="max-w-md mx-auto my-10 bg-white p-8 rounded-lg shadow-md">
    <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">Login</h2>

    <?php if (!empty($errors)): ?>
      <div class="mb-6 bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded">
        <?php foreach ($errors as $error): ?>
          <p class="text-sm"><?php echo $error; ?></p>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
      <div class="mb-4">
        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
        <input type="email"
          class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
          id="email"
          name="email"
          placeholder="Enter your email"
          required>
      </div>

      <div class="mb-6">
        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
        <input type="password"
          class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
          id="password"
          name="password"
          placeholder="Enter your password"
          required>
      </div>

      <button type="submit"
        class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-md transition duration-300 ease-in-out">
        Login
      </button>

      <div class="mt-4 text-center text-gray-600">
        <p>Don't have an account? <a href="register.php" class="text-green-600 hover:text-green-800 font-medium">Register here</a></p>
      </div>
    </form>
  </div>

  <?php include 'includes/js-links.php'; ?>
</body>

</html>