<?php
// Start admin session with custom name
session_name('admin_session');
session_start();

// Redirect if already logged in
if (isset($_SESSION['admin_loggedin']) && $_SESSION['admin_loggedin'] === true) {
    header('Location: index.php');
    exit;
}

// Include database connection
require_once '../config/db.php';

// Initialize variables
$errors = [];

// Rate limiting setup
$max_attempts = 5;
$lockout_time = 15 * 60; // 15 minutes in seconds
$attempt_key = 'login_attempts_' . md5($_SERVER['REMOTE_ADDR']);
if (!isset($_SESSION[$attempt_key])) {
    $_SESSION[$attempt_key] = ['count' => 0, 'time' => time()];
}

// Process login if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check for rate limiting
    if ($_SESSION[$attempt_key]['count'] >= $max_attempts && (time() - $_SESSION[$attempt_key]['time']) < $lockout_time) {
        $errors[] = "Too many login attempts. Please try again after " . ceil(($lockout_time - (time() - $_SESSION[$attempt_key]['time'])) / 60) . " minutes.";
    } else {
        // Reset attempts if lockout time has passed
        if ((time() - $_SESSION[$attempt_key]['time']) >= $lockout_time) {
            $_SESSION[$attempt_key] = ['count' => 0, 'time' => time()];
        }

        // Sanitize and validate inputs
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        // Enhanced validation
        if (empty($email)) {
            $errors[] = "Email is required";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        } elseif (strlen($email) > 255) {
            $errors[] = "Email is too long";
        }

        if (empty($password)) {
            $errors[] = "Password is required";
        } elseif (strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters";
        }

        // Process login if no validation errors
        if (empty($errors)) {
            try {
                // Verify database connection
                if (!$conn) {
                    throw new Exception("Database connection failed");
                }

                // Fetch admin from database using prepared statement
                $query = "SELECT id, email, password FROM admins WHERE email = ?";
                $stmt = $conn->prepare($query);
                if (!$stmt) {
                    throw new Exception("Failed to prepare statement: " . $conn->error);
                }

                $stmt->bind_param("s", $email);
                if (!$stmt->execute()) {
                    throw new Exception("Query execution failed: " . $conn->error);
                }

                $result = $stmt->get_result();
                if ($result === false) {
                    throw new Exception("Failed to retrieve results: " . $conn->error);
                }

                if ($result->num_rows > 0) {
                    $admin = $result->fetch_assoc();

                    // Verify password
                    if (password_verify($password, $admin['password'])) {
                        // Regenerate session ID for security
                        session_regenerate_id(true);

                        // Set admin session variables
                        $_SESSION['admin_loggedin'] = true;
                        $_SESSION['admin_id'] = $admin['id'];
                        $_SESSION['admin_email'] = $admin['email'];
                        $_SESSION['admin_last_login'] = time();

                        // Reset login attempts on successful login
                        $_SESSION[$attempt_key] = ['count' => 0, 'time' => time()];

                        // Handle "remember me" functionality
                        if (isset($_POST['remember']) && $_POST['remember'] == '1') {
                            // Set a cookie for 30 days
                            $token = bin2hex(random_bytes(32));
                            setcookie('admin_remember', $token, time() + (30 * 24 * 60 * 60), '/', '', true, true);
                            // In a real app, store the token in the database
                        }

                        // Redirect to dashboard
                        header('Location: index.php');
                        exit;
                    } else {
                        $errors[] = "Incorrect password";
                    }
                } else {
                    $errors[] = "No account found with that email";
                }

                $stmt->close();
            } catch (Exception $e) {
                $errors[] = "An error occurred: " . htmlspecialchars($e->getMessage());
            }
        }

        // Increment login attempts on failure
        if (!empty($errors)) {
            $_SESSION[$attempt_key]['count']++;
            $_SESSION[$attempt_key]['time'] = time();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | UmrahFlights</title>
    <link rel="stylesheet" href="../src/output.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-gray-100 to-gray-300 flex items-center justify-center min-h-screen p-4">
    <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md">
        <!-- Logo and Header -->
        <div class="flex flex-col items-center mb-6">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-4">
                <i class="fas fa-plane-departure text-green-600 text-3xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 mb-2">UmrahFlights Admin</h2>
            <p class="text-center text-sm text-gray-600">Secure login for administrators</p>
        </div>

        <!-- Error Messages -->
        <?php if (!empty($errors)): ?>
            <div class="mb-6 bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg">
                <?php foreach ($errors as $error): ?>
                    <p class="text-sm"><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <div class="mb-5">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                <div class="relative">
                    <input type="email" id="email" name="email" class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 transition duration-300" placeholder="admin@example.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                        <i class="fas fa-envelope text-gray-400"></i>
                    </span>
                </div>
            </div>
            <div class="mb-6">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <div class="relative">
                    <input type="password" id="password" name="password" class="w-full pl-10 pr-10 py-3 border border-gray-200 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 transition duration-300" placeholder="••••••••" required>
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
                    <input type="checkbox" name="remember" value="1" class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded" <?php echo isset($_POST['remember']) ? 'checked' : ''; ?>>
                    <span class="ml-2 text-sm text-gray-600">Remember me</span>
                </label>
            </div>
            <button type="submit" class="w-full bg-green-600 text-white py-3 px-4 rounded-lg shadow-sm hover:bg-green-700 transition duration-300">Sign In</button>
        </form>

        <!-- Back to Website Link -->
        <div class="mt-6 text-center">
            <a href="../index.php" class="text-sm text-green-600 hover:underline flex items-center justify-center">
                <i class="fas fa-arrow-left mr-2"></i> Back to website
            </a>
        </div>
    </div>

    <!-- Password Toggle Script -->
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