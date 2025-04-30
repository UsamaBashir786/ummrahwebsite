<link rel="stylesheet" href="../src/output.css">
<!-- Sidebar Overlay -->
<div class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden" id="sidebar-overlay"></div>

<!-- Sidebar -->
<nav class="fixed top-0 left-0 w-64 h-full bg-white shadow-lg transform -translate-x-full transition-transform duration-300 z-50 md:translate-x-0 md:block overflow-y-auto" id="sidebar">
  <div class="p-4">
    <!-- Header (matching sample's clean look, no blue background) -->
    <div class="flex items-center justify-between mb-6">
      <div class="flex items-center space-x-2">
        <i class="fas fa-plane-departure text-xl text-indigo-600"></i>
        <h5 class="font-bold text-lg text-gray-800">UmrahFlights</h5>
      </div>
      <button class="text-gray-400 hover:text-gray-500 text-2xl focus:outline-none md:hidden" id="sidebar-close">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <!-- Search Bar (from sample) -->
    <div class="mb-6">
      <div class="flex items-center px-4 py-2.5">
        <div class="w-full relative">
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
          <input type="text" placeholder="Search..." class="pl-10 w-full rounded-lg border border-gray-200 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
        </div>
      </div>
    </div>

    <!-- Menu Sections -->
    <div class="space-y-6">
      <!-- Dashboard Section -->
      <div>
        <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Dashboard</p>
        <ul class="mt-2 space-y-1">
          <li>
            <a href="index.php" class="flex items-center px-4 py-2.5 text-sm font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'text-white bg-gradient-to-r from-indigo-600 to-purple-600' : 'text-gray-700 hover:bg-indigo-50'; ?> rounded-lg">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'text-white' : 'text-gray-500'; ?>" viewBox="0 0 20 20" fill="currentColor">
                <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4z" />
                <path d="M3 10a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2z" />
                <path d="M3 16a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2z" />
              </svg>
              <span>Dashboard</span>
            </a>
          </li>
        </ul>
      </div>

      <!-- Management Section -->
      <div>
        <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Management</p>
        <ul class="mt-2 space-y-1">
          <!-- Flights Dropdown -->
          <li>
            <button class="flex items-center justify-between w-full px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-indigo-50 rounded-lg" data-target="#flights-dropdown">
              <span class="flex items-center space-x-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                  <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                </svg>
                <span>Flights</span>
              </span>
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <div class="hidden space-y-1 pl-6 mt-2" id="flights-dropdown">
              <a href="add-flight.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 rounded-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                </svg>
                Add Flight
              </a>
              <a href="view-flights.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 rounded-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                </svg>
                View Flights
              </a>
              <a href="booked-flights.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 rounded-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                  <path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z" />
                </svg>
                Flight Bookings
              </a>
            </div>
          </li>

          <!-- Hotels Dropdown -->
          <li>
            <button class="flex items-center justify-between w-full px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-indigo-50 rounded-lg" data-target="#hotels-dropdown">
              <span class="flex items-center space-x-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                  <path d="M2 4a2 2 0 012-2h12a2 2 0 012 2v12a2 2 0 01-2 2H4a2 2 0 01-2-2V4zm4 12h8v-2H6v2zm0-4h8v-2H6v2zm0-4h8V6H6v2z" />
                </svg>
                <span>Hotels</span>
              </span>
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <div class="hidden space-y-1 pl-6 mt-2" id="hotels-dropdown">
              <a href="add-hotels.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 rounded-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                </svg>
                Add Hotel
              </a>
              <a href="view-hotels.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 rounded-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                </svg>
                View Hotels
              </a>
              <a href="booked-hotels.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 rounded-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                  <path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z" />
                </svg>
                Hotel Bookings
              </a>
            </div>
          </li>

          <!-- Packages Dropdown -->
          <li>
            <button class="flex items-center justify-between w-full px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-indigo-50 rounded-lg" data-target="#packages-dropdown">
              <span class="flex items-center space-x-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                  <path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z" />
                </svg>
                <span>Packages</span>
              </span>
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <div class="hidden space-y-1 pl-6 mt-2" id="packages-dropdown">
              <a href="add-packages.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 rounded-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                </svg>
                Add Package
              </a>
              <a href="view-packages.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 rounded-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                </svg>
                View Packages
              </a>
              <a href="booked-packages.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 rounded-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                  <path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-=2"/>
                </svg>
                Package Bookings
              </a>
            </div>
          </li>

          <!-- Transportation Dropdown -->
          <li>
            <button class="flex items-center justify-between w-full px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-indigo-50 rounded-lg" data-target="#transportation-dropdown">
              <span class="flex items-center space-x-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                  <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zm7 0a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                  <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H14a1 1 0 001-1v-3h-5v-1h9V8h-1a1 1 0 00-1-1h-6a1 1 0 00-1 1v7.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1V5a1 1 0 00-1-1H3z" />
                </svg>
                <span>Transportation</span>
              </span>
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <div class="hidden space-y-1 pl-6 mt-2" id="transportation-dropdown">
              <a href="add-transportation.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 rounded-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                </svg>
                Add Transportation
              </a>
              <a href="view-transportation.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 rounded-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                </svg>
                View Transportation
              </a>
              <a href="booked-transportation.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 rounded-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                  <path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z" />
                </svg>
                Transportation Bookings
              </a>
            </div>
          </li>

          <!-- Assignments -->
          <li>
            <a href="admin_bookings.php" class="flex items-center px-4 py-2.5 text-sm font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'admin_bookings.php' ? 'text-white bg-gradient-to-r from-indigo-600 to-purple-600' : 'text-gray-700 hover:bg-indigo-50'; ?> rounded-lg">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 <?php echo basename($_SERVER['PHP_SELF']) == 'admin_bookings.php' ? 'text-white' : 'text-gray-500'; ?>" viewBox="0 0 20 20" fill="currentColor">
                <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zm8 0a3 3 0 11-6 0 3 3 0 016 0zm-4.07 11c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
              </svg>
              <span>Assignments</span>
            </a>
          </li>

          <!-- Users -->
          <li>
            <a href="users.php" class="flex items-center px-4 py-2.5 text-sm font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'text-white bg-gradient-to-r from-indigo-600 to-purple-600' : 'text-gray-700 hover:bg-indigo-50'; ?> rounded-lg">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'text-white' : 'text-gray-500'; ?>" viewBox="0 0 20 20" fill="currentColor">
                <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zm8 0a3 3 0 11-6 0 3 3 0 016 0zm-4.07 11c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
              </svg>
              <span>Users</span>
            </a>
          </li>

          <!-- Contact -->
          <li>
            <a href="contact-us.php" class="flex items-center px-4 py-2.5 text-sm font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'contact-us.php' ? 'text-white bg-gradient-to-r from-indigo-600 to-purple-600' : 'text-gray-700 hover:bg-indigo-50'; ?> rounded-lg">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 <?php echo basename($_SERVER['PHP_SELF']) == 'contact-us.php' ? 'text-white' : 'text-gray-500'; ?>" viewBox="0 0 20 20" fill="currentColor">
                <path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z" />
                <path d="M15 7v2a4 4 0 01-4 4H9.828l-1.766 1.767c.28.149.599.233.938.233h2l3 3v-3h2a2 2 0 002-2V9a2 2 0 00-2-2h-1z" />
              </svg>
              <span>Contact</span>
            </a>
          </li>
        </ul>
      </div>

      <!-- Settings Section -->
      <div>
        <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Settings</p>
        <ul class="mt-2 space-y-1">
          <li>
            <a href="logout.php" class="flex items-center px-4 py-2.5 text-sm font-medium text-red-500 hover:bg-red-50 rounded-lg">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 3.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L16.586 11H7a1 1 0 110-2h9.586l-3.293-3.293a1 1 0 010-1.414z" clip-rule="evenodd" />
              </svg>
              <span>Logout</span>
            </a>
          </li>
        </ul>
      </div>
    </div>
  </div>
</nav>

<!-- Sidebar Toggle Button -->
<button class="fixed top-4 left-4 z-50 p-2 bg-indigo-600 text-white rounded-lg shadow-lg focus:outline-none md:hidden" id="sidebar-toggle">
  <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
  </svg>
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
    sidebarToggle.classList.add('hidden');
  });

  // Close Sidebar
  sidebarClose.addEventListener('click', () => {
    sidebar.classList.add('-translate-x-full');
    sidebarOverlay.classList.add('hidden');
    sidebarToggle.classList.remove('hidden');
  });

  sidebarOverlay.addEventListener('click', () => {
    sidebar.classList.add('-translate-x-full');
    sidebarOverlay.classList.add('hidden');
    sidebarToggle.classList.remove('hidden');
  });

  // Dropdown Toggle
  dropdownButtons.forEach(button => {
    button.addEventListener('click', () => {
      const target = document.querySelector(button.getAttribute('data-target'));
      target.classList.toggle('hidden');
    });
  });
</script>