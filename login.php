<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session and include database connection
session_start();
require_once 'config/db.php';

// Initialize variables
$errors = [];
$debug = []; // For debugging issues

// Check if database connection is successful
if (!$conn) {
  $errors[] = "Database connection failed: " . mysqli_connect_error();
  $debug[] = "Ensure config/db.php is correctly configured and the database server is running.";
}

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
      // Use prepared statement to prevent SQL injection
      $query = "SELECT id, full_name, email, password, is_approved FROM users WHERE email = ?";
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
          // Check if the user is approved
          if (isset($user['is_approved']) && $user['is_approved'] == 1) {
            // User is approved, proceed with login
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];

            // Redirect to dashboard or protected page only on success
            header("Location: index.php");
            exit;
          } else {
            // User is not approved yet
            $errors[] = "Your account is pending approval. Please wait for admin verification before you can log in.";
          }
        } else {
          $errors[] = "Invalid email or password";
        }
      } else {
        $errors[] = "No account found with this email";
      }

      $stmt->close();
    } catch (Exception $e) {
      $errors[] = "An error occurred while processing your request: " . $e->getMessage();
      $debug[] = "Check database connection and 'users' table structure.";
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | UmrahFlights</title>
  <!-- <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet"> -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="src/output.css">
  <link rel="stylesheet" href="assets/css/login.css">
</head>

<body class="flex flex-col min-h-screen">
  <!-- Navbar -->
  <?php
  if (file_exists('includes/navbar.php')) {
    include 'includes/navbar.php';
  } else {
    $errors[] = "Navbar file not found. Please ensure 'includes/navbar.php' exists.";
    $debug[] = "Create 'includes/navbar.php' or provide a fallback navigation.";
    echo '<nav class="bg-white shadow-md p-4"><div class="container mx-auto"><a href="index.php" class="text-2xl font-bold text-emerald-600">UmrahFlights</a></div></nav>';
  }
  ?>

  <!-- Main Content -->
  <section class="flex-grow flex items-center justify-center py-16 px-4">
    <div class="login-card w-full max-w-md animate-on-scroll">
      <h2 class="text-3xl font-extrabold text-center text-gray-800 mb-4">Login to UmrahFlights</h2>
      <p class="text-center text-sm text-gray-600 mb-6">Sign in to access your spiritual journey</p>

      <!-- Debug Messages (only shown if errors exist) -->
      <?php if (!empty($debug) && !empty($errors)): ?>
        <div class="debug-message">
          <p class="text-sm"><strong>Debug Info:</strong></p>
          <?php foreach ($debug as $msg): ?>
            <p class="text-sm"><?php echo htmlspecialchars($msg); ?></p>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <!-- Info Message -->
      <div class="info-message mb-6">
        <p class="text-sm">
          <i class="fas fa-info-circle mr-2"></i>
          <strong>Note:</strong> New accounts require admin approval. Please wait for approval notification before logging in.
        </p>
      </div>

      <!-- Error Messages -->
      <?php if (!empty($errors)): ?>
        <div class="alert bg-red-50 border-l-4 border-red-500 text-red-700 mb-6">
          <?php foreach ($errors as $error): ?>
            <p class="text-sm"><?php echo htmlspecialchars($error); ?></p>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <!-- Login Form -->
      <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
        <!-- Email -->
        <div class="mb-5 relative">
          <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address <span class="text-red-500">*</span></label>
          <i class="fas fa-envelope input-icon"></i>
          <input type="email"
            id="email"
            name="email"
            class="w-full input-field"
            placeholder="you@example.com"
            value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>"
            required>
        </div>

        <!-- Password -->
        <div class="mb-6 relative">
          <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password <span class="text-red-500">*</span></label>
          <i class="fas fa-lock input-icon"></i>
          <input type="password"
            id="password"
            name="password"
            class="w-full input-field"
            placeholder="••••••••"
            required>
          <button type="button" id="togglePassword"
            class="absolute inset-y-0 right-0 flex items-center pr-4 top-9 text-gray-400 hover:text-gray-600">
            <i class="fas fa-eye"></i>
          </button>
        </div>

        <!-- Remember Me and Forgot Password -->
        <div class="flex items-center justify-between mb-6">
          <label class="flex items-center">
            <input type="checkbox"
              class="h-4 w-4 text-emerald-600 focus:ring-emerald-500 border-gray-300 rounded">
            <span class="ml-2 text-sm text-gray-600">Remember me</span>
          </label>
          <a href="#" class="text-sm text-emerald-600 hover:text-emerald-800 transition">Forgot Password?</a>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="gradient-button w-full"><i class="fas fa-sign-in-alt mr-2"></i>Sign In</button>

        <!-- Register Link -->
        <p class="mt-6 text-center text-sm text-gray-600">
          Don't have an account?
          <a href="register.php" class="text-emerald-600 font-medium hover:text-emerald-800 transition">Register here</a>
        </p>
      </form>
    </div>
  </section>



  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Toggle password visibility
      const togglePassword = document.getElementById('togglePassword');
      const passwordInput = document.getElementById('password');
      togglePassword.addEventListener('click', () => {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        togglePassword.querySelector('i').classList.toggle('fa-eye');
        togglePassword.querySelector('i').classList.toggle('fa-eye-slash');
      });

      // Scroll animations
      const elements = document.querySelectorAll('.animate-on-scroll');
      const observer = new IntersectionObserver(
        (entries) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              entry.target.classList.add('visible');
            }
          });
        }, {
          threshold: 0.1
        }
      );
      elements.forEach((el) => observer.observe(el));
    });
  </script>
</body>

</html>