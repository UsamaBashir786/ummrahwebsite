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

  if (empty($dob)) {
    $errors[] = "Date of birth is required";
  }

  // Check if email already exists
  $checkEmail = $conn->query("SELECT id FROM users WHERE email = '$email'");
  if ($checkEmail->num_rows > 0) {
    $errors[] = "Email already registered";
  }

  // Handle file upload
  $profileImage = '';
  if (isset($_FILES['profileImage']) && $_FILES['profileImage']['error'] == 0) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $ext = pathinfo($_FILES['profileImage']['name'], PATHINFO_EXTENSION);

    if (in_array(strtolower($ext), $allowed)) {
      $uploadDir = 'assets/uploads/profile_images/';
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
      $errors[] = "Invalid file type. Only JPG, JPEG, PNG, GIF allowed";
    }
  }

  // If no errors, insert into database
  if (empty($errors)) {
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $query = "INSERT INTO users (full_name, email, password, dob, profile_image, created_at) 
                  VALUES ('$fullName', '$email', '$hashedPassword', '$dob', '$profileImage', NOW())";

    if ($conn->query($query)) {
      $success = "Registration successful! You can now login.";
      // Clear form fields
      $fullName = $email = $password = $dob = '';
    } else {
      $errors[] = "Registration failed: " . $conn->error;
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php' ?>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    body {
      background-color: #f8f9fa;
      justify-content: center;
      align-items: center;
      margin: 0;
    }

    .error {
      color: red;
      margin-bottom: 10px;
    }

    .success {
      color: green;
      margin-bottom: 10px;
    }
  </style>
</head>

<body>
  <!-- Navbar -->
  <?php include 'includes/navbar.php'; ?>

  <div class="register-container my-5 m-auto">
    <h2>Register with UmrahFlights</h2>

    <?php if (!empty($errors)): ?>
      <div class="error">
        <?php foreach ($errors as $error): ?>
          <p><?php echo $error; ?></p>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="success">
        <p><?php echo $success; ?></p>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" enctype="multipart/form-data">
      <div class="mb-3">
        <label for="fullName" class="form-label">Full Name</label>
        <input type="text" class="form-control" id="fullName" name="fullName"
          placeholder="Enter your full name" value="<?php echo isset($fullName) ? $fullName : ''; ?>" required>
      </div>
      <div class="mb-3">
        <label for="email" class="form-label">Email Address</label>
        <input type="email" class="form-control" id="email" name="email"
          placeholder="Enter your email" value="<?php echo isset($email) ? $email : ''; ?>" required>
      </div>
      <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control" id="password" name="password"
          placeholder="Enter your password" required>
      </div>
      <div class="mb-3">
        <label for="dob" class="form-label">Date of Birth</label>
        <input type="date" class="form-control" id="dob" name="dob"
          value="<?php echo isset($dob) ? $dob : ''; ?>" required>
      </div>
      <div class="mb-3">
        <label for="profileImage" class="form-label">Profile Image</label>
        <input type="file" class="form-control" id="profileImage" name="profileImage" accept="image/*">
      </div>
      <button type="submit" class="btn-register">Register</button>
      <div class="login-link">
        <p>Already have an account? <a href="login.php">Login here</a></p>
      </div>
    </form>
  </div>

  <!-- Footer -->
  <?php include 'includes/js-links.php' ?>
</body>

</html>