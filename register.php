<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>UmrahFlights - Register</title>
  <link rel="stylesheet" href="assets/bootstrap-5.2.3-dist/css/bootstrap.min.css">
  <style>
    body {
      background-color: #f8f9fa;
      display: flex;
      justify-content: center;
      align-items: center;
      /* height: 100vh; */
      margin: 0;
    }

    .register-container {
      background-color: white;
      padding: 40px;
      border-radius: 15px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
      max-width: 400px;
      width: 100%;
    }

    .register-container h2 {
      font-size: 2rem;
      font-weight: bold;
      color: #2c3e50;
      margin-bottom: 20px;
      text-align: center;
    }

    .form-label {
      color: #2c3e50;
      font-weight: 500;
    }

    .form-control {
      border-radius: 8px;
      border: 1px solid #ced4da;
      padding: 10px;
    }

    .form-control:focus {
      border-color: #17a2b8;
      box-shadow: 0 0 5px rgba(23, 162, 184, 0.3);
    }

    .btn-register {
      background-color: #17a2b8;
      color: white;
      border: none;
      padding: 10px;
      border-radius: 20px;
      font-weight: 500;
      width: 100%;
    }

    .btn-register:hover {
      background-color: #138496;
    }

    .login-link {
      text-align: center;
      margin-top: 20px;
    }

    .login-link a {
      color: #17a2b8;
      text-decoration: none;
      font-weight: 500;
    }

    .login-link a:hover {
      color: #138496;
    }
  </style>
</head>

<body>
  <div class="register-container">
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

  <script src="assets/bootstrap-5.2.3-dist/js/bootstrap.bundle.js"></script>
</body>

</html>