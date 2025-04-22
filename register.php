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

    .register-container {
      /* position: absolute; */
      /* left: 50%; */
      /* top: 50%; */
      /* transform: translate(-50%, -50%); */
    }
  </style>
</head>

<body>
  <!-- Navbar -->
  <?php include 'includes/navbar.php'; ?>
  <div class="register-container my-5 m-auto">
    <h2>Register with UmrahFlights</h2>
    <div>
      <div class="mb-3">
        <label for="fullName" class="form-label">Full Name</label>
        <input type="text" class="form-control" id="fullName" placeholder="Enter your full name" required>
      </div>
      <div class="mb-3">
        <label for="email" class="form-label">Email Address</label>
        <input type="email" class="form-control" id="email" placeholder="Enter your email" required>
      </div>
      <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control" id="password" placeholder="Enter your password" required>
      </div>
      <div class="mb-3">
        <label for="dob" class="form-label">Date of Birth</label>
        <input type="date" class="form-control" id="dob" required>
      </div>
      <div class="mb-3">
        <label for="profileImage" class="form-label">Profile Image</label>
        <input type="file" class="form-control" id="profileImage" accept="image/*">
      </div>
      <button type="button" class="btn-register">Register</button>
      <div class="login-link">
        <p>Already have an account? <a href="login.html">Login here</a></p>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <?php include 'includes/js-links.php' ?>
</body>

</html>