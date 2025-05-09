<?php
// Ensure session is started
if (session_status() == PHP_SESSION_NONE) {
  session_start();
}
?>

<nav class="bg-white shadow-lg fixed top-0 w-full z-50">
  <div class="container mx-auto px-4">
    <div class="flex justify-between items-center h-20">
      <!-- Logo -->
      <div class="flex items-center">
        <a class="flex-shrink-0 flex items-center text-3xl font-extrabold text-gray-800 hover:text-emerald-600 transition" href="index.php">
          UMRAH
        </a>
      </div>

      <!-- Desktop Menu -->
      <div class="hidden md:flex items-center space-x-6">
        <a href="index.php" class="text-gray-700 hover:text-emerald-600 px-4 py-2 rounded-lg text-base font-medium transition">Home</a>
        <a href="about-us.php" class="text-gray-700 hover:text-emerald-600 px-4 py-2 rounded-lg text-base font-medium transition">About Us</a>
        <a href="contact-us.php" class="text-gray-700 hover:text-emerald-600 px-4 py-2 rounded-lg text-base font-medium transition">Contact Us</a>
        <a href="packages.php" class="text-gray-700 hover:text-emerald-600 px-4 py-2 rounded-lg text-base font-medium transition">Packages</a>

        <!-- Dropdown -->
        <div class="relative group">
          <button class="text-gray-700 group-hover:text-emerald-600 px-4 py-2 rounded-lg text-base font-medium inline-flex items-center transition">
            <span>More</span>
            <i class="fas fa-chevron-down ml-2 text-sm"></i>
          </button>
          <div class="absolute right-0 w-56 mt-2 origin-top-right z-10">
            <div class="bg-white rounded-xl shadow-xl border border-gray-100 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 ease-in-out transform group-hover:scale-100 scale-95">
              <div class="py-2">
                <a href="transportation.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-emerald-50 hover:text-emerald-600 transition">Transportation</a>
                <a href="flights.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-emerald-50 hover:text-emerald-600 transition">Flights</a>
                <a href="hotels.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-emerald-50 hover:text-emerald-600 transition">Hotels</a>
              </div>
            </div>
          </div>
        </div>

        <!-- Conditional Links -->
        <?php if (isset($_SESSION['user_id'])): ?>
          <a href="user/index.php" class="text-gray-700 hover:text-emerald-600 px-4 py-2 rounded-lg text-base font-medium transition">Dashboard</a>
          <a href="logout.php" class="text-gray-700 hover:text-emerald-600 px-4 py-2 rounded-lg text-base font-medium transition">Logout</a>
        <?php else: ?>
          <a href="login.php" class="text-gray-700 hover:text-emerald-600 px-4 py-2 rounded-lg text-base font-medium transition">Login</a>
          <a href="register.php" class="gradient-button px-4 py-2 rounded-lg text-base font-medium">Register</a>
        <?php endif; ?>
      </div>

      <!-- Mobile Menu Button -->
      <div class="flex items-center md:hidden">
        <button type="button" class="mobile-menu-button inline-flex items-center justify-center p-2 rounded-lg text-gray-700 hover:text-emerald-600 hover:bg-emerald-50 focus:outline-none" aria-controls="mobile-menu" aria-expanded="false">
          <span class="sr-only">Open main menu</span>
          <i class="fas fa-bars h-6 w-6"></i>
        </button>
      </div>
    </div>
  </div>

  <!-- Mobile Menu -->
  <div class="md:hidden hidden" id="mobile-menu">
    <div class="px-2 pt-2 pb-4 space-y-2 sm:px-3 bg-white shadow-lg">
      <a href="index.php" class="text-gray-700 hover:text-emerald-600 hover:bg-emerald-50 block px-4 py-2 rounded-lg text-base font-medium transition">Home</a>
      <a href="about-us.php" class="text-gray-700 hover:text-emerald-600 hover:bg-emerald-50 block px-4 py-2 rounded-lg text-base font-medium transition">About Us</a>
      <a href="contact-us.php" class="text-gray-700 hover:text-emerald-600 hover:bg-emerald-50 block px-4 py-2 rounded-lg text-base font-medium transition">Contact Us</a>
      <a href="packages.php" class="text-gray-700 hover:text-emerald-600 hover:bg-emerald-50 block px-4 py-2 rounded-lg text-base font-medium transition">Packages</a>

      <!-- Mobile Dropdown -->
      <div class="relative">
        <button id="mobileMoreBtn" class="text-gray-700 hover:text-emerald-600 hover:bg-emerald-50 block px-4 py-2 rounded-lg text-base font-medium w-full text-left flex justify-between items-center transition">
          <span>More</span>
          <i class="fas fa-chevron-down text-sm"></i>
        </button>
        <div id="mobileSubmenu" class="px-4 py-2 hidden">
          <a href="transportation.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-emerald-50 hover:text-emerald-600 rounded-lg transition">Transportation</a>
          <a href="flights.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-emerald-50 hover:text-emerald-600 rounded-lg transition">Flights</a>
          <a href="hotels.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-emerald-50 hover:text-emerald-600 rounded-lg transition">Hotels</a>
        </div>
      </div>

      <!-- Conditional Links -->
      <?php if (isset($_SESSION['user_id'])): ?>
        <a href="user/index.php" class="text-gray-700 hover:text-emerald-600 hover:bg-emerald-50 block px-4 py-2 rounded-lg text-base font-medium transition">Dashboard</a>
        <a href="logout.php" class="text-gray-700 hover:text-emerald-600 hover:bg-emerald-50 block px-4 py-2 rounded-lg text-base font-medium transition">Logout</a>
      <?php else: ?>
        <a href="login.php" class="text-gray-700 hover:text-emerald-600 hover:bg-emerald-50 block px-4 py-2 rounded-lg text-base font-medium transition">Login</a>
        <a href="register.php" class="gradient-button block px-4 py-2 rounded-lg text-base font-medium text-center">Register</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const mobileMenuButton = document.querySelector('.mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');
    mobileMenuButton.addEventListener('click', function() {
      mobileMenu.classList.toggle('hidden');
      const icon = mobileMenuButton.querySelector('i');
      icon.classList.toggle('fa-bars');
      icon.classList.toggle('fa-times');
    });

    // Mobile submenu toggle
    const mobileMoreBtn = document.getElementById('mobileMoreBtn');
    const mobileSubmenu = document.getElementById('mobileSubmenu');
    mobileMoreBtn.addEventListener('click', function() {
      mobileSubmenu.classList.toggle('hidden');
      const icon = mobileMoreBtn.querySelector('i');
      icon.classList.toggle('fa-chevron-down');
      icon.classList.toggle('fa-chevron-up');
    });
  });
</script>

<style>
  @import url('https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700;800&display=swap');

  nav {
    font-family: 'Manrope', sans-serif;
  }

  .gradient-button {
    background: linear-gradient(90deg, #10b981, #059669);
    color: white;
    transition: transform 0.3s ease, background 0.3s ease;
  }

  .gradient-button:hover {
    background: linear-gradient(90deg, #059669, #10b981);
    transform: scale(1.05);
  }
</style>