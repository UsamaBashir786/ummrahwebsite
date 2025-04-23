<?php
// Ensure session is started
if (session_status() == PHP_SESSION_NONE) {
  session_start();
}
?>

<nav class="bg-white shadow-md fixed top-0 w-full z-50">
  <div class="container mx-auto px-4">
    <div class="flex justify-between h-16">
      <div class="flex items-center">
        <a class="flex-shrink-0 flex items-center text-2xl font-bold text-gray-800" href="#">
          <!-- <img class="h-8 w-auto mr-2" src="path/to/logo.png" alt="Umrah Logo"> -->
          UMRAH
        </a>
      </div>

      <div class="hidden md:flex items-center">
        <div class="ml-10 flex items-baseline space-x-4">
          <a href="index.php" class="text-gray-700 hover:text-teal-500 px-3 py-2 rounded-md text-sm font-medium">Home</a>
          <a href="packages.php" class="text-gray-700 hover:text-teal-500 px-3 py-2 rounded-md text-sm font-medium">Packages</a>
          <a href="about-us.php" class="text-gray-700 hover:text-teal-500 px-3 py-2 rounded-md text-sm font-medium">About Us</a>

          <!-- Dropdown - FIXED -->
          <div class="relative group">
            <button class="text-gray-700 group-hover:text-teal-500 px-3 py-2 rounded-md text-sm font-medium inline-flex items-center">
              <span>More</span>
              <svg class="ml-1 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
              </svg>
            </button>
            <!-- Added padding-top to create a buffer zone between button and menu -->
            <div class="absolute right-0 w-48 pt-2 origin-top-right z-10">
              <!-- This is the actual dropdown menu content -->
              <div class="bg-white rounded-md shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition duration-150 ease-in-out">
                <div class="py-1">
                  <a href="transportation.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Transportation</a>
                  <a href="flights.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Flights</a>
                  <a href="hotels.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Hotels</a>
                </div>
              </div>
            </div>
          </div>

          <?php if (isset($_SESSION['user_id'])): ?>
            <a href="user/index.php" class="text-gray-700 hover:text-teal-500 px-3 py-2 rounded-md text-sm font-medium">Dashboard</a>
            <a href="logout.php" class="text-gray-700 hover:text-teal-500 px-3 py-2 rounded-md text-sm font-medium">Logout</a>
          <?php else: ?>
            <a href="login.php" class="text-gray-700 hover:text-teal-500 px-3 py-2 rounded-md text-sm font-medium">Login</a>
            <a href="register.php" class="text-teal-500 bg-teal-50 hover:bg-teal-100 px-4 py-2 rounded-md text-sm font-medium">Register</a>
          <?php endif; ?>
        </div>
      </div>

      <!-- Mobile menu button -->
      <div class="flex items-center md:hidden">
        <button type="button" class="mobile-menu-button inline-flex items-center justify-center p-2 rounded-md text-gray-700 hover:text-teal-500 hover:bg-gray-100 focus:outline-none" aria-controls="mobile-menu" aria-expanded="false">
          <span class="sr-only">Open main menu</span>
          <!-- Icon when menu is closed -->
          <svg class="block h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>
      </div>
    </div>
  </div>

  <!-- Mobile menu, show/hide based on menu state -->
  <div class="md:hidden hidden" id="mobile-menu">
    <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
      <a href="index.php" class="text-gray-700 hover:text-teal-500 block px-3 py-2 rounded-md text-base font-medium">Home</a>
      <a href="packages.php" class="text-gray-700 hover:text-teal-500 block px-3 py-2 rounded-md text-base font-medium">Packages</a>
      <a href="about-us.php" class="text-gray-700 hover:text-teal-500 block px-3 py-2 rounded-md text-base font-medium">About Us</a>

      <!-- Mobile dropdown -->
      <div class="relative">
        <button id="mobileMoreBtn" class="text-gray-700 hover:text-teal-500 block px-3 py-2 rounded-md text-base font-medium w-full text-left">
          <div class="flex justify-between items-center">
            <span>More</span>
            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
          </div>
        </button>
        <div id="mobileSubmenu" class="px-2 py-2 hidden">
          <a href="transportation.php" class="block px-3 py-2 text-base font-medium text-gray-700 hover:text-teal-500 hover:bg-gray-50 rounded-md">Transportation</a>
          <a href="flights.php" class="block px-3 py-2 text-base font-medium text-gray-700 hover:text-teal-500 hover:bg-gray-50 rounded-md">Flights</a>
          <a href="hotels.php" class="block px-3 py-2 text-base font-medium text-gray-700 hover:text-teal-500 hover:bg-gray-50 rounded-md">Hotels</a>
        </div>
      </div>

      <?php if (isset($_SESSION['user_id'])): ?>
        <a href="user/index.php" class="text-gray-700 hover:text-teal-500 block px-3 py-2 rounded-md text-base font-medium">Dashboard</a>
        <a href="logout.php" class="text-gray-700 hover:text-teal-500 block px-3 py-2 rounded-md text-base font-medium">Logout</a>
      <?php else: ?>
        <a href="login.php" class="text-gray-700 hover:text-teal-500 block px-3 py-2 rounded-md text-base font-medium">Login</a>
        <a href="register.php" class="bg-teal-50 text-teal-500 block px-3 py-2 rounded-md text-base font-medium">Register</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<script>
  // Mobile menu toggle
  document.querySelector('.mobile-menu-button').addEventListener('click', function() {
    document.getElementById('mobile-menu').classList.toggle('hidden');
  });

  // Mobile submenu toggle - fixed to use the proper IDs
  document.getElementById('mobileMoreBtn').addEventListener('click', function() {
    document.getElementById('mobileSubmenu').classList.toggle('hidden');
  });
</script>