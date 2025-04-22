<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>UmrahFlights - Login</title>
  <link rel="stylesheet" href="assets/bootstrap-5.2.3-dist/css/bootstrap.min.css">
  <style>
    body {
      background-color: #f8f9fa;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }

    .login-container {
      background-color: white;
      padding: 40px;
      border-radius: 15px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
      max-width: 400px;
      width: 100%;
    }

    .login-container h2 {
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

    .btn-login {
      background-color: #17a2b8;
      color: white;
      border: none;
      padding: 10px;
      border-radius: 20px;
      font-weight: 500;
      width: 100%;
    }

    .btn-login:hover {
      background-color: #138496;
    }

    .register-link {
      text-align: center;
      margin-top: 20px;
    }

    .register-link a {
      color: #17a2b8;
      text-decoration: none;
      font-weight: 500;
    }

    .register-link a:hover {
      color: #138496;
    }
  </style>
</head>

<body>
  <div class="login-container">
    <h2>Login to UmrahFlights</h2>
    <div>
      <div class="mb-3">
        <label for="email" class="form-label">Email Address</label>
        <input type="email" class="form-control" id="email" placeholder="Enter your email" required>
      </div>
      <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control" id="password" placeholder="Enter your password" required>
      </div>
      <button type="button" class="btn-login">Login</button>
      <div class="register-link">
        <p>Don't have an account? <a href="register.html">Register here</a></p>
      </div>
    </div>
  </div>

  <script src="assets/bootstrap-5.2.3-dist/js/bootstrap.bundle.js"></script>
</body>

</html>