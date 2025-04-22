  <!-- Sidebar Overlay -->
  <div class="sidebar-overlay"></div>

  <!-- Sidebar -->
  <nav class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <div class="d-flex align-items-center">
        <i class="fas fa-plane-departure text-primary fs-4 me-2"></i>
        <h5 class="mb-0">UmrahFlights</h5>
      </div>
    </div>

    <div class="pt-2">
      <div class="sidebar-heading">Dashboard</div>
      <ul class="nav flex-column">
        <li class="nav-item">
          <a href="index.php" class="nav-link active">
            <i class="fas fa-tachometer-alt"></i> Dashboard
          </a>
        </li>
      </ul>

      <div class="sidebar-heading mt-3">Management</div>
      <ul class="nav flex-column">
        <!-- Flights Dropdown -->
        <li class="nav-item menu-dropdown">
          <a href="#flights-dropdown" class="nav-link dropdown-toggle" data-bs-toggle="collapse">
            <i class="fas fa-plane"></i> Flights
          </a>
          <div class="collapse submenu" id="flights-dropdown">
            <ul class="nav flex-column">
              <li class="nav-item">
                <a href="flight.php" class="nav-link">
                  <i class="fas fa-plus-circle"></i> Add Flight
                </a>
              </li>
              <li class="nav-item">
                <a href="flights.php" class="nav-link">
                  <i class="fas fa-list"></i> View Flights
                </a>
              </li>
            </ul>
          </div>
        </li>

        <!-- Hotels Dropdown -->
        <li class="nav-item menu-dropdown">
          <a href="#hotels-dropdown" class="nav-link dropdown-toggle" data-bs-toggle="collapse">
            <i class="fas fa-hotel"></i> Hotels
          </a>
          <div class="collapse submenu" id="hotels-dropdown">
            <ul class="nav flex-column">
              <li class="nav-item">
                <a href="add-hotel.php" class="nav-link">
                  <i class="fas fa-plus-circle"></i> Add Hotel
                </a>
              </li>
              <li class="nav-item">
                <a href="hotels.php" class="nav-link">
                  <i class="fas fa-list"></i> View Hotels
                </a>
              </li>
            </ul>
          </div>
        </li>

        <!-- Packages Dropdown -->
        <li class="nav-item menu-dropdown">
          <a href="#packages-dropdown" class="nav-link dropdown-toggle" data-bs-toggle="collapse">
            <i class="fas fa-box"></i> Packages
          </a>
          <div class="collapse submenu" id="packages-dropdown">
            <ul class="nav flex-column">
              <li class="nav-item">
                <a href="add-package.php" class="nav-link">
                  <i class="fas fa-plus-circle"></i> Add Package
                </a>
              </li>
              <li class="nav-item">
                <a href="packages.php" class="nav-link">
                  <i class="fas fa-list"></i> View Packages
                </a>
              </li>
            </ul>
          </div>
        </li>

        <!-- Transportation Dropdown -->
        <li class="nav-item menu-dropdown">
          <a href="#transportation-dropdown" class="nav-link dropdown-toggle" data-bs-toggle="collapse">
            <i class="fas fa-bus"></i> Transportation
          </a>
          <div class="collapse submenu" id="transportation-dropdown">
            <ul class="nav flex-column">
              <li class="nav-item">
                <a href="add-transport.php" class="nav-link">
                  <i class="fas fa-plus-circle"></i> Add Transportation
                </a>
              </li>
              <li class="nav-item">
                <a href="transportation.php" class="nav-link">
                  <i class="fas fa-list"></i> View Transportation
                </a>
              </li>
            </ul>
          </div>
        </li>

        <!-- Assignments Dropdown -->
        <li class="nav-item menu-dropdown">
          <a href="#assignments-dropdown" class="nav-link dropdown-toggle" data-bs-toggle="collapse">
            <i class="fas fa-link"></i> Assignments
          </a>
          <div class="collapse submenu" id="assignments-dropdown">
            <ul class="nav flex-column">
              <li class="nav-item">
                <a href="assign-hotels.php" class="nav-link">
                  <i class="fas fa-hotel"></i> Assign Hotels
                </a>
              </li>
              <li class="nav-item">
                <a href="assign-transport.php" class="nav-link">
                  <i class="fas fa-bus"></i> Assign Transportation
                </a>
              </li>
              <li class="nav-item">
                <a href="assign-flights.php" class="nav-link">
                  <i class="fas fa-plane"></i> Assign Flights
                </a>
              </li>
            </ul>
          </div>
        </li>

        <!-- Users -->
        <li class="nav-item">
          <a href="users.php" class="nav-link">
            <i class="fas fa-users"></i> Users
          </a>
        </li>
      </ul>

      <div class="sidebar-heading mt-3">Settings</div>
      <ul class="nav flex-column">
        <li class="nav-item">
          <a href="../logout.php" class="nav-link text-danger">
            <i class="fas fa-sign-out-alt"></i> Logout
          </a>
        </li>
      </ul>
    </div>
  </nav>