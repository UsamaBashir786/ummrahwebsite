<style>
  a {
    text-decoration: none;
  }
</style>
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

<!-- Sidebar Overlay -->
<div class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden" id="sidebar-overlay"></div>

<!-- Sidebar -->
<nav style="    height: 100vh;
    position: fixed;
    overflow-y: scroll;" class=" fixed top-0 left-0 w-64 h-full bg-white shadow-lg transform -translate-x-full transition-transform duration-300 z-50" id="sidebar">
  <div class="flex items-center justify-between p-4 bg-blue-500 text-white">
    <div class="flex items-center space-x-2">
      <i class="fas fa-plane-departure text-xl"></i>
      <h5 class="font-bold text-lg">UmrahFlights</h5>
    </div>
    <button class="text-white text-2xl focus:outline-none" id="sidebar-close">
      &times;
    </button>
  </div>

  <div class="px-4 py-6">
    <div class="text-gray-500 text-sm font-semibold uppercase">Dashboard</div>
    <ul class="space-y-2 mt-2">
      <li>
        <a href="index.php" class="flex items-center space-x-3 text-gray-700 hover:bg-blue-100 rounded-lg p-2">
          <i class="fas fa-tachometer-alt"></i> <span>Dashboard</span>
        </a>
      </li>
    </ul>

    <div class="text-gray-500 text-sm font-semibold uppercase mt-6">Management</div>
    <ul class="space-y-2 mt-2">
      <!-- Flights Dropdown -->
      <li>
        <button class="flex items-center justify-between w-full text-gray-700 hover:bg-blue-100 rounded-lg p-2" data-target="#flights-dropdown">
          <span class="flex items-center space-x-3">
            <i class="fas fa-plane"></i> <span>Flights</span>
          </span>
          <i class="fas fa-chevron-down"></i>
        </button>
        <div class="hidden space-y-2 pl-6 mt-2" id="flights-dropdown">
          <a href="add-flight.php" class="block text-gray-700 hover:bg-blue-100 rounded-lg p-2">
            <i class="fas fa-plus-circle"></i> Add Flight
          </a>
          <a href="view-flights.php" class="block text-gray-700 hover:bg-blue-100 rounded-lg p-2">
            <i class="fas fa-list"></i> View Flights
          </a>
          <a href="booked-flights.php" class="block text-gray-700 hover:bg-blue-100 rounded-lg p-2">
            <i class="fas fa-ticket-alt"></i> Flight Bookings
          </a>
        </div>
      </li>

      <!-- Hotels Dropdown -->
      <li>
        <button class="flex items-center justify-between w-full text-gray-700 hover:bg-blue-100 rounded-lg p-2" data-target="#hotels-dropdown">
          <span class="flex items-center space-x-3">
            <i class="fas fa-hotel"></i> <span>Hotels</span>
          </span>
          <i class="fas fa-chevron-down"></i>
        </button>
        <div class="hidden space-y-2 pl-6 mt-2" id="hotels-dropdown">
          <a href="add-hotels.php" class="block text-gray-700 hover:bg-blue-100 rounded-lg p-2">
            <i class="fas fa-plus-circle"></i> Add Hotel
          </a>
          <a href="view-hotels.php" class="block text-gray-700 hover:bg-blue-100 rounded-lg p-2">
            <i class="fas fa-list"></i> View Hotels
          </a>
          <a href="booked-hotels.php" class="block text-gray-700 hover:bg-blue-100 rounded-lg p-2">
            <i class="fas fa-bed"></i> Hotel Bookings
          </a>
        </div>
      </li>

      <!-- Packages Dropdown -->
      <li>
        <button class="flex items-center justify-between w-full text-gray-700 hover:bg-blue-100 rounded-lg p-2" data-target="#packages-dropdown">
          <span class="flex items-center space-x-3">
            <i class="fas fa-box"></i> <span>Packages</span>
          </span>
          <i class="fas fa-chevron-down"></i>
        </button>
        <div class="hidden space-y-2 pl-6 mt-2" id="packages-dropdown">
          <a href="add-packages.php" class="block text-gray-700 hover:bg-blue-100 rounded-lg p-2">
            <i class="fas fa-plus-circle"></i> Add Package
          </a>
          <a href="view-packages.php" class="block text-gray-700 hover:bg-blue-100 rounded-lg p-2">
            <i class="fas fa-list"></i> View Packages
          </a>
          <a href="booked-packages.php" class="block text-gray-700 hover:bg-blue-100 rounded-lg p-2">
            <i class="fas fa-suitcase"></i> Package Bookings
          </a>
        </div>
      </li>

      <!-- Transportation Dropdown -->
      <li>
        <button class="flex items-center justify-between w-full text-gray-700 hover:bg-blue-100 rounded-lg p-2" data-target="#transportation-dropdown">
          <span class="flex items-center space-x-3">
            <i class="fas fa-bus"></i> <span>Transportation</span>
          </span>
          <i class="fas fa-chevron-down"></i>
        </button>
        <div class="hidden space-y-2 pl-6 mt-2" id="transportation-dropdown">
          <a href="add-transportation.php" class="block text-gray-700 hover:bg-blue-100 rounded-lg p-2">
            <i class="fas fa-plus-circle"></i> Add Transportation
          </a>
          <a href="view-transportation.php" class="block text-gray-700 hover:bg-blue-100 rounded-lg p-2">
            <i class="fas fa-list"></i> View Transportation
          </a>
          <a href="booked-transportation.php" class="block text-gray-700 hover:bg-blue-100 rounded-lg p-2">
            <i class="fas fa-taxi"></i> Transportation Bookings
          </a>
        </div>
      </li>

      <!-- Assignments Dropdown -->
      <li>
        <a href="assign-services.php" class="flex items-center space-x-3 text-gray-700 hover:bg-blue-100 rounded-lg p-2">
          <i class="fas fa-users"></i> <span>Assignments</span>
        </a>
      </li>

      <!-- Users -->
      <li>
        <a href="users.php" class="flex items-center space-x-3 text-gray-700 hover:bg-blue-100 rounded-lg p-2">
          <i class="fas fa-users"></i> <span>Users</span>
        </a>
      </li>
    </ul>

    <div class="text-gray-500 text-sm font-semibold uppercase mt-6">Settings</div>
    <ul class="space-y-2 mt-2">
      <li>
        <a href="../logout.php" class="flex items-center space-x-3 text-red-500 hover:bg-red-100 rounded-lg p-2">
          <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
        </a>
      </li>
    </ul>
  </div>
</nav>

<!-- Sidebar Toggle Button -->
<button class="fixed top-4 left-4 z-50 p-2 bg-blue-500 text-white rounded-lg shadow-lg focus:outline-none" id="sidebar-toggle">
  <i class="fas fa-bars"></i>
</button>

<script>
  const sidebar = document.getElementById('sidebar');
  const sidebarOverlay = document.getElementById('sidebar-overlay');
  const sidebarToggle = document.getElementById('sidebar-toggle');
  const sidebarClose = document.getElementById('sidebar-close');
  const dropdownButtons = document.querySelectorAll('[data-target]');

  // Open Sidebar
  sidebarToggle.addEventListener('click', () => {
    sidebar.classList.remove('-translate-x-full');
    sidebarOverlay.classList.remove('hidden');
  });

  // Close Sidebar
  sidebarClose.addEventListener('click', () => {
    sidebar.classList.add('-translate-x-full');
    sidebarOverlay.classList.add('hidden');
  });

  sidebarOverlay.addEventListener('click', () => {
    sidebar.classList.add('-translate-x-full');
    sidebarOverlay.classList.add('hidden');
  });

  // Dropdown Toggle
  dropdownButtons.forEach(button => {
    button.addEventListener('click', () => {
      const target = document.querySelector(button.getAttribute('data-target'));
      target.classList.toggle('hidden');
    });
  });
</script>