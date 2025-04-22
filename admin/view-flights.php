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

// Fetch flight data from the database
$query = "SELECT * FROM flights ORDER BY created_at DESC";
$result = $conn->query($query);

// Check if there are any flights in the database
$flights = [];
if ($result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $flights[] = $row;
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>View Flights | UmrahFlights Admin</title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="assets/css/view-flight.css">
</head>

<body>
  <?php include 'includes/sidebar.php'; ?>
  <div class="container-fluid">
    <div class="row">
      <?php include 'includes/sidebar.php'; ?>
      <!-- Main Content -->
      <main class="main-content col-md-9">
        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4">
          <div class="container-fluid">
            <button id="sidebarToggle" class="btn d-lg-none me-2">
              <i class="fas fa-bars"></i>
            </button>
            <h1 class="navbar-brand mb-0 d-flex align-items-center">
              <i class="text-primary fas fa-plane me-2"></i> View Flights
            </h1>
          </div>
        </nav>

        <!-- Flight Table -->
        <div class="container-fluid">
          <div class="card shadow-sm">
            <div class="card-body p-4">
              <div class="mb-4">
                <h2 class="card-title text-primary">
                  <i class="fas fa-plane-departure me-2"></i>Flight Details
                </h2>
              </div>

              <?php if (count($flights) > 0): ?>
                <div class="table-responsive">
                  <table class="table table-bordered table-hover">
                    <thead class="table-primary">
                      <tr>
                        <th>#</th>
                        <th>Airline</th>
                        <th>Flight Number</th>
                        <th>Departure</th>
                        <th>Arrival</th>
                        <th>Departure Date</th>
                        <th>Duration</th>
                        <th>Economy Price</th>
                        <th>Business Price</th>
                        <th>First Class Price</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($flights as $index => $flight): ?>
                        <tr>
                          <td><?php echo $index + 1; ?></td>
                          <td><?php echo htmlspecialchars($flight['airline_name']); ?></td>
                          <td><?php echo htmlspecialchars($flight['flight_number']); ?></td>
                          <td><?php echo htmlspecialchars($flight['departure_city']); ?></td>
                          <td><?php echo htmlspecialchars($flight['arrival_city']); ?></td>
                          <td><?php echo htmlspecialchars($flight['departure_date']); ?></td>
                          <td><?php echo htmlspecialchars($flight['flight_duration']); ?> hours</td>
                          <td>PKR <?php echo htmlspecialchars(number_format($flight['economy_price'], 2)); ?></td>
                          <td>PKR <?php echo htmlspecialchars(number_format($flight['business_price'], 2)); ?></td>
                          <td>PKR <?php echo htmlspecialchars(number_format($flight['first_class_price'], 2)); ?></td>
                          <td>
                            <a href="edit-flight.php?id=<?php echo $flight['id']; ?>" class="btn btn-warning btn-sm">
                              <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="delete-flight.php?id=<?php echo $flight['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this flight?');">
                              <i class="fas fa-trash"></i> Delete
                            </a>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php else: ?>
                <div class="alert alert-warning">
                  <i class="fas fa-info-circle me-2"></i>No flights found.
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>

  <!-- Bootstrap Bundle with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>