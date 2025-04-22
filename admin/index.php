<?php
require_once '../config/db.php';
// Start admin session
session_name('admin_session');
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
  header('Location: login.php');
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard | UmrahFlights</title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="assets/css/index.css">
</head>

<body>
  <?php include 'includes/sidebar.php'; ?>
  <!-- Main Content -->
  <div class="main-content">
    <!-- Top Navbar -->
    <nav class="navbar navbar-expand-lg top-navbar mb-4">
      <div class="container-fluid">
        <button id="sidebarToggle" class="btn d-lg-none">
          <i class="fas fa-bars"></i>
        </button>
        <h4 class="mb-0 ms-2">Dashboard</h4>

        <div class="d-flex align-items-center">
          <div class="position-relative me-3">
            <button class="btn position-relative" id="notificationBtn">
              <i class="fas fa-bell fs-5"></i>
              <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                3
              </span>
            </button>
          </div>

          <div class="dropdown">
            <button class="btn dropdown-toggle d-flex align-items-center" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
              <div class="rounded-circle overflow-hidden me-2" style="width: 32px; height: 32px;">
                <img src="../assets/img/admin.jpg" alt="Admin User" class="img-fluid">
              </div>
              <span class="d-none d-md-inline">Admin User</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
              <li><a class="dropdown-item text-danger" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
            </ul>
          </div>
        </div>
      </div>
    </nav>

    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
      <div class="col-12 col-md-6 col-lg-3">
        <div class="card stat-card border-start border-primary border-4">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h3 class="fw-bold mb-1">158</h3>
                <div class="text-muted">Total Bookings</div>
              </div>
              <div class="stat-card-icon bg-primary bg-opacity-10 text-primary">
                <i class="fas fa-calendar-check"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-md-6 col-lg-3">
        <div class="card stat-card border-start border-success border-4">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h3 class="fw-bold mb-1">Rs.5.4M</h3>
                <div class="text-muted">Total Revenue</div>
              </div>
              <div class="stat-card-icon bg-success bg-opacity-10 text-success">
                <i class="fas fa-wallet"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-md-6 col-lg-3">
        <div class="card stat-card border-start border-info border-4">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h3 class="fw-bold mb-1">427</h3>
                <div class="text-muted">Total Users</div>
              </div>
              <div class="stat-card-icon bg-info bg-opacity-10 text-info">
                <i class="fas fa-users"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-md-6 col-lg-3">
        <div class="card stat-card border-start border-warning border-4">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h3 class="fw-bold mb-1">25</h3>
                <div class="text-muted">Active Packages</div>
              </div>
              <div class="stat-card-icon bg-warning bg-opacity-10 text-warning">
                <i class="fas fa-box"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap 5 JS Bundle with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@3.5.1/dist/chart.min.js"></script>
  <!-- Custom JavaScript -->
  <script src="assets/js/index.js"></script>
</body>

</html>