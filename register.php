<?php
// Start session and include database connection
session_start();
require_once 'config/db.php';

// Initialize variables
$errors = [];
$success = '';

// Process form when submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  // Sanitize and validate inputs
  $fullName = mysqli_real_escape_string($conn, trim($_POST['fullName']));
  $email = mysqli_real_escape_string($conn, trim($_POST['email']));
  $password = mysqli_real_escape_string($conn, trim($_POST['password']));
  $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));
  $dob = mysqli_real_escape_string($conn, $_POST['dob']);

  // Validate inputs
  if (empty($fullName)) {
    $errors[] = "Full name is required";
  }

  if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Valid email is required";
  }

  if (empty($password) || strlen($password) < 6) {
    $errors[] = "Password must be at least 6 characters";
  }

  if (empty($phone) || !preg_match("/^[0-9+\-\(\) ]{7,20}$/", $phone)) {
    $errors[] = "Valid phone number is required (7-20 characters, numbers, +, -, (), spaces allowed)";
  }

  if (empty($dob)) {
    $errors[] = "Date of birth is required";
  }

  // Check if email already exists
  $checkEmail = $conn->query("SELECT id FROM users WHERE email = '$email'");
  if ($checkEmail->num_rows > 0) {
    $errors[] = "Email already registered";
  }

  // Handle profile image upload
  $profileImage = '';
  if (isset($_FILES['profileImage']) && $_FILES['profileImage']['error'] == 0) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $ext = pathinfo($_FILES['profileImage']['name'], PATHINFO_EXTENSION);

    if (in_array(strtolower($ext), $allowed)) {
      $uploadDir = 'uploads/profile_images/';
      if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
      }

      $filename = uniqid() . '.' . $ext;
      $destination = $uploadDir . $filename;

      if (move_uploaded_file($_FILES['profileImage']['tmp_name'], $destination)) {
        $profileImage = $destination;
      } else {
        $errors[] = "Failed to upload profile image";
      }
    } else {
      $errors[] = "Invalid file type for profile image. Only JPG, JPEG, PNG, GIF allowed";
    }
  }

  // Handle visa image upload (required)
  $visaImage = '';
  if (!isset($_FILES['visaImage']) || $_FILES['visaImage']['error'] != 0) {
    $errors[] = "Visa document is required";
  } else {
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
    $ext = pathinfo($_FILES['visaImage']['name'], PATHINFO_EXTENSION);

    if (in_array(strtolower($ext), $allowed)) {
      $uploadDir = 'uploads/visa_documents/';
      if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
      }

      $filename = uniqid() . '.' . $ext;
      $destination = $uploadDir . $filename;

      if (move_uploaded_file($_FILES['visaImage']['tmp_name'], $destination)) {
        $visaImage = $destination;
      } else {
        $errors[] = "Failed to upload visa document";
      }
    } else {
      $errors[] = "Invalid file type for visa document. Only JPG, JPEG, PNG, GIF, PDF allowed";
    }
  }

  // If no errors, insert into database
  if (empty($errors)) {
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // First check if the users table has the new columns
    $checkColumns = $conn->query("SHOW COLUMNS FROM users LIKE 'visa_image'");

    if ($checkColumns->num_rows == 0) {
      // Add missing columns
      $alterTableQuery = "ALTER TABLE users 
        ADD COLUMN visa_image VARCHAR(255) DEFAULT NULL,
        ADD COLUMN is_approved TINYINT(1) DEFAULT 0,
        ADD COLUMN approved_at DATETIME DEFAULT NULL,
        ADD COLUMN approved_by INT(11) DEFAULT NULL";

      $conn->query($alterTableQuery);
    }

    $query = "INSERT INTO users (full_name, email, password, phone, dob, profile_image, visa_image, is_approved, created_at) 
              VALUES ('$fullName', '$email', '$hashedPassword', '$phone', '$dob', '$profileImage', '$visaImage', 0, NOW())";

    if ($conn->query($query)) {
      $success = "Registration successful! Please wait for admin approval before you can login. You will be notified once your account is approved.";
      // Clear form fields
      $fullName = $email = $phone = $dob = '';
    } else {
      $errors[] = "Registration failed: " . $conn->error;
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register - UmrahFlights</title>
  <!-- Include Tailwind CSS -->
  <!-- <script src="https://cdn.tailwindcss.com"></script> -->
   <link rel="stylesheet" href="src/output.css">
  <?php include 'includes/css-links.php' ?>
</head>

<body class="bg-gray-50 pt-24">
  <!-- Navbar -->
  <?php include 'includes/navbar.php'; ?>

  <div class="max-w-xl mx-auto my-10 bg-white p-8 rounded-lg shadow-md">
    <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">Register with UmrahFlights</h2>

    <?php if (!empty($errors)): ?>
      <div class="mb-6 bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded">
        <?php foreach ($errors as $error): ?>
          <p class="text-sm mb-1"><?php echo $error; ?></p>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
      <div class="mb-6 bg-green-50 border border-green-200 text-green-600 px-4 py-3 rounded">
        <p class="text-sm"><?php echo $success; ?></p>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" enctype="multipart/form-data">
      <div class="mb-4">
        <label for="fullName" class="block text-sm font-medium text-gray-700 mb-1">Full Name <span class="text-red-500">*</span></label>
        <input type="text"
          class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
          id="fullName"
          name="fullName"
          placeholder="Enter your full name"
          value="<?php echo isset($fullName) ? htmlspecialchars($fullName) : ''; ?>"
          required>
      </div>

      <div class="mb-4">
        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address <span class="text-red-500">*</span></label>
        <input type="email"
          class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
          id="email"
          name="email"
          placeholder="Enter your email"
          value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>"
          required>
      </div>

      <div class="mb-4">
        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number <span class="text-red-500">*</span></label>
        <input type="tel"
          class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
          id="phone"
          name="phone"
          placeholder="Enter your phone number (e.g., +1234567890)"
          value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>"
          required>
      </div>

      <div class="mb-4">
        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password <span class="text-red-500">*</span></label>
        <input type="password"
          class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
          id="password"
          name="password"
          placeholder="Enter your password (min. 6 characters)"
          required>
      </div>

      <div class="mb-4">
        <label for="dob" class="block text-sm font-medium text-gray-700 mb-1">Date of Birth <span class="text-red-500">*</span></label>
        <input type="date"
          class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
          id="dob"
          name="dob"
          value="<?php echo isset($dob) ? htmlspecialchars($dob) : ''; ?>"
          required>
      </div>

      <div class="mb-4">
        <label for="profileImage" class="block text-sm font-medium text-gray-700 mb-1">Profile Image (Optional)</label>
        <input type="file"
          class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
          id="profileImage"
          name="profileImage"
          accept="image/*">
        <p class="mt-1 text-xs text-gray-500">Upload a profile picture (JPG, JPEG, PNG, GIF) - Max 5MB</p>
      </div>

      <div class="mb-6">
        <label for="visaImage" class="block text-sm font-medium text-gray-700 mb-1">Visa Document <span class="text-red-500">*</span></label>
        <input type="file"
          class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
          id="visaImage"
          name="visaImage"
          accept="image/*,.pdf"
          required>
        <p class="mt-1 text-xs text-gray-500">Upload your visa document (JPG, JPEG, PNG, GIF, PDF) - Max 10MB - Required for registration</p>
      </div>

      <div class="mb-6 bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded">
        <p class="text-sm">
          <strong>Important:</strong> Your account will require admin approval before you can login. Please ensure your visa document is clear and valid.
        </p>
      </div>

      <button type="submit"
        class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-md transition duration-300 ease-in-out">
        Register
      </button>

      <div class="mt-4 text-center text-gray-600">
        <p>Already have an account? <a href="login.php" class="text-green-600 hover:text-green-800 font-medium">Login here</a></p>
      </div>
    </form>
  </div>

  <!-- Footer -->
  <?php include 'includes/js-links.php' ?>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Get form and input elements
      const form = document.querySelector('form');
      const fullNameInput = document.getElementById('fullName');
      const emailInput = document.getElementById('email');
      const phoneInput = document.getElementById('phone');
      const passwordInput = document.getElementById('password');
      const dobInput = document.getElementById('dob');
      const profileImageInput = document.getElementById('profileImage');
      const visaImageInput = document.getElementById('visaImage');

      // Function to create or update error messages
      function showError(input, message) {
        // Remove existing error message if any
        const existingError = input.nextElementSibling;
        if (existingError && existingError.classList.contains('error-message')) {
          existingError.remove();
        }

        // Create new error message
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message text-red-500 text-xs mt-1';
        errorDiv.textContent = message;
        input.parentElement.appendChild(errorDiv);

        // Add error styling to input
        input.classList.add('border-red-500');
        input.classList.remove('focus:border-green-500');
      }

      // Function to clear error messages
      function clearError(input) {
        const existingError = input.nextElementSibling;
        if (existingError && existingError.classList.contains('error-message')) {
          existingError.remove();
        }
        input.classList.remove('border-red-500');
        input.classList.add('focus:border-green-500');
      }

      // Validation functions
      function validateFullName() {
        const value = fullNameInput.value.trim();
        if (value === '') {
          showError(fullNameInput, 'Full name is required');
          return false;
        } else if (!/^[A-Za-z\s]{1,100}$/.test(value)) {
          showError(fullNameInput, 'Only letters and spaces allowed (max 100 characters)');
          return false;
        }
        clearError(fullNameInput);
        return true;
      }

      function validateEmail() {
        const value = emailInput.value.trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (value === '') {
          showError(emailInput, 'Email is required');
          return false;
        } else if (!emailRegex.test(value)) {
          showError(emailInput, 'Enter a valid email address');
          return false;
        }
        clearError(emailInput);
        return true;
      }

      function validatePhone() {
        const value = phoneInput.value.trim();
        const phoneRegex = /^[0-9+\-\(\) ]{7,20}$/;
        if (value === '') {
          showError(phoneInput, 'Phone number is required');
          return false;
        } else if (!phoneRegex.test(value)) {
          showError(phoneInput, '7-20 characters, only numbers, +, -, (), spaces allowed');
          return false;
        }
        clearError(phoneInput);
        return true;
      }

      function validatePassword() {
        const value = passwordInput.value;
        if (value === '') {
          showError(passwordInput, 'Password is required');
          return false;
        } else if (value.length < 6) {
          showError(passwordInput, 'Password must be at least 6 characters');
          return false;
        }
        clearError(passwordInput);
        return true;
      }

      function validateDob() {
        const value = dobInput.value;
        const today = new Date();
        const dob = new Date(value);
        if (value === '') {
          showError(dobInput, 'Date of birth is required');
          return false;
        } else if (dob >= today) {
          showError(dobInput, 'Date of birth must be in the past');
          return false;
        }
        clearError(dobInput);
        return true;
      }

      function validateProfileImage() {
        const file = profileImageInput.files[0];
        if (!file) {
          clearError(profileImageInput);
          return true; // Image is optional
        }

        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        const maxSize = 5 * 1024 * 1024; // 5MB
        if (!allowedTypes.includes(file.type)) {
          showError(profileImageInput, 'Only JPG, JPEG, PNG, GIF allowed');
          return false;
        } else if (file.size > maxSize) {
          showError(profileImageInput, 'File size must be less than 5MB');
          return false;
        }
        clearError(profileImageInput);
        return true;
      }

      function validateVisaImage() {
        const file = visaImageInput.files[0];
        if (!file) {
          showError(visaImageInput, 'Visa document is required');
          return false;
        }

        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf'];
        const maxSize = 10 * 1024 * 1024; // 10MB
        if (!allowedTypes.includes(file.type)) {
          showError(visaImageInput, 'Only JPG, JPEG, PNG, GIF, PDF allowed');
          return false;
        } else if (file.size > maxSize) {
          showError(visaImageInput, 'File size must be less than 10MB');
          return false;
        }
        clearError(visaImageInput);
        return true;
      }

      // Real-time validation on input
      fullNameInput.addEventListener('input', validateFullName);
      emailInput.addEventListener('input', validateEmail);
      phoneInput.addEventListener('input', validatePhone);
      passwordInput.addEventListener('input', validatePassword);
      dobInput.addEventListener('input', validateDob);
      profileImageInput.addEventListener('change', validateProfileImage);
      visaImageInput.addEventListener('change', validateVisaImage);

      // Form submission validation
      form.addEventListener('submit', function(e) {
        const isValid =
          validateFullName() &&
          validateEmail() &&
          validatePhone() &&
          validatePassword() &&
          validateDob() &&
          validateProfileImage() &&
          validateVisaImage();

        if (!isValid) {
          e.preventDefault(); // Prevent form submission if validation fails
        }
      });

      // Optional: Check if email exists in real-time using AJAX
      emailInput.addEventListener('blur', function() {
        const email = emailInput.value.trim();
        if (!validateEmail()) return; // Skip if email is invalid

        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'check_email.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
          if (xhr.status === 200) {
            const response = xhr.responseText;
            if (response === 'exists') {
              showError(emailInput, 'Email already registered');
            } else {
              clearError(emailInput);
            }
          }
        };
        xhr.send('email=' + encodeURIComponent(email));
      });
    });
  </script>
</body>

</html>