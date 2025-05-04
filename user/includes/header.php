<?php
// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

// Get user info for header
$user_id = $_SESSION['user_id'];
$header_query = $conn->prepare("SELECT full_name, email, profile_image FROM users WHERE id = ?");
$header_query->bind_param("i", $user_id);
$header_query->execute();
$header_user = $header_query->get_result()->fetch_assoc();
$header_query->close();

// Get notification count (optional - you can implement this based on your needs)
$notification_query = $conn->prepare("
    SELECT COUNT(*) as unread_count 
    FROM (
        SELECT id FROM flight_bookings 
        WHERE user_id = ? AND booking_status = 'confirmed' 
        AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
        UNION ALL
        SELECT id FROM hotel_bookings 
        WHERE user_id = ? AND booking_status = 'confirmed' 
        AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
        UNION ALL
        SELECT id FROM package_bookings 
        WHERE user_id = ? AND booking_status = 'confirmed' 
        AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ) AS notifications
");
$notification_query->bind_param("iii", $user_id, $user_id, $user_id);
$notification_query->execute();
$notifications = $notification_query->get_result()->fetch_assoc();
$notification_count = $notifications['unread_count'] ?? 0;
$notification_query->close();
?>

<!-- Top Navigation Bar -->
<nav class="bg-white shadow-md fixed w-full top-0 z-50">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between h-16">
      <!-- Left side - Logo and Navigation Toggle -->
      <div class="flex items-center">
        <!-- Mobile menu button -->
        <button type="button"
          class="lg:hidden inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-cyan-500"
          id="mobile-menu-button">
          <span class="sr-only">Open main menu</span>
          <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>

        <!-- Logo -->
        <div class="flex-shrink-0 flex items-center ml-4 lg:ml-0">
          <a href="index.php" class="flex items-center">
            <i class="fas fa-plane-departure text-2xl text-cyan-600 mr-2"></i>
            <span class="text-xl font-bold text-gray-900">UmrahFlights</span>
          </a>
        </div>
      </div>

      <!-- Center - Search Bar (hidden on mobile) -->
      <div class="hidden md:flex-1 md:flex md:items-center md:justify-center px-6">
        <div class="max-w-lg w-full">
          <form action="search.php" method="GET" class="relative">
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </div>
              <input type="search"
                name="q"
                class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500 sm:text-sm"
                placeholder="Search flights, hotels, packages...">
            </div>
          </form>
        </div>
      </div>

      <!-- Right side - User Menu -->
      <div class="flex items-center">
        <!-- Notifications -->
        <button type="button"
          class="p-2 rounded-full text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500"
          id="notifications-button">
          <span class="sr-only">View notifications</span>
          <div class="relative">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            <?php if ($notification_count > 0): ?>
              <span class="absolute top-0 right-0 block h-2 w-2 rounded-full bg-red-400 ring-2 ring-white"></span>
            <?php endif; ?>
          </div>
        </button>

        <!-- User Dropdown -->
        <div class="ml-3 relative">
          <div>
            <button type="button"
              class="flex rounded-full bg-white text-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500"
              id="user-menu-button"
              aria-expanded="false"
              aria-haspopup="true">
              <span class="sr-only">Open user menu</span>
              <?php if ($header_user['profile_image']): ?>
                <img class="h-8 w-8 rounded-full object-cover"
                  src="../<?php echo htmlspecialchars($header_user['profile_image']); ?>"
                  alt="<?php echo htmlspecialchars($header_user['full_name']); ?>">
              <?php else: ?>
                <div class="h-8 w-8 rounded-full bg-cyan-500 flex items-center justify-center text-white font-semibold">
                  <?php echo strtoupper(substr($header_user['full_name'], 0, 1)); ?>
                </div>
              <?php endif; ?>
            </button>
          </div>

          <!-- Dropdown Menu -->
          <div class="hidden origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 focus:outline-none"
            role="menu"
            aria-orientation="vertical"
            aria-labelledby="user-menu-button"
            id="user-dropdown-menu">
            <div class="px-4 py-2 text-sm text-gray-700 border-b">
              <div class="font-medium"><?php echo htmlspecialchars($header_user['full_name']); ?></div>
              <div class="text-gray-500"><?php echo htmlspecialchars($header_user['email']); ?></div>
            </div>
            <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
              <i class="fas fa-user mr-2"></i> Your Profile
            </a>
            <a href="booking-history.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
              <i class="fas fa-history mr-2"></i> Booking History
            </a>
            <a href="settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
              <i class="fas fa-cog mr-2"></i> Settings
            </a>
            <div class="border-t">
              <a href="logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100" role="menuitem">
                <i class="fas fa-sign-out-alt mr-2"></i> Sign out
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Mobile Search Bar -->
  <div class="md:hidden border-t border-gray-200 p-3">
    <form action="search.php" method="GET">
      <div class="relative">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
          <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>
        <input type="search"
          name="q"
          class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500 sm:text-sm"
          placeholder="Search...">
      </div>
    </form>
  </div>
</nav>

<!-- Notifications Panel -->
<div id="notifications-panel"
  class="hidden fixed inset-y-0 right-0 w-96 bg-white shadow-xl z-50 transform transition-transform duration-300 translate-x-full">
  <div class="h-full flex flex-col">
    <div class="p-4 border-b flex justify-between items-center">
      <h2 class="text-lg font-semibold">Notifications</h2>
      <button type="button" id="close-notifications" class="text-gray-400 hover:text-gray-500">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
    <div class="flex-1 overflow-y-auto p-4">
      <?php if ($notification_count > 0): ?>
        <div class="space-y-4">
          <!-- Example notifications - you can customize this -->
          <div class="bg-cyan-50 border-l-4 border-cyan-400 p-4">
            <div class="flex">
              <div class="flex-shrink-0">
                <i class="fas fa-check-circle text-cyan-400"></i>
              </div>
              <div class="ml-3">
                <p class="text-sm text-cyan-700">
                  Your flight booking has been confirmed!
                </p>
                <p class="text-xs text-cyan-600 mt-1">
                  Just now
                </p>
              </div>
            </div>
          </div>
        </div>
      <?php else: ?>
        <div class="text-center text-gray-500 mt-8">
          <i class="fas fa-bell-slash text-4xl mb-4"></i>
          <p>No new notifications</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Add some spacing for fixed header -->
<div class="h-16"></div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    // User dropdown menu
    const userMenuButton = document.getElementById('user-menu-button');
    const userDropdownMenu = document.getElementById('user-dropdown-menu');

    userMenuButton.addEventListener('click', function() {
      userDropdownMenu.classList.toggle('hidden');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
      if (!userMenuButton.contains(event.target) && !userDropdownMenu.contains(event.target)) {
        userDropdownMenu.classList.add('hidden');
      }
    });

    // Notifications panel
    const notificationsButton = document.getElementById('notifications-button');
    const notificationsPanel = document.getElementById('notifications-panel');
    const closeNotifications = document.getElementById('close-notifications');

    notificationsButton.addEventListener('click', function() {
      notificationsPanel.classList.remove('hidden');
      setTimeout(() => {
        notificationsPanel.classList.remove('translate-x-full');
      }, 10);
    });

    closeNotifications.addEventListener('click', function() {
      notificationsPanel.classList.add('translate-x-full');
      setTimeout(() => {
        notificationsPanel.classList.add('hidden');
      }, 300);
    });

    // Mobile menu integration with sidebar
    const mobileMenuButton = document.getElementById('mobile-menu-button');

    mobileMenuButton.addEventListener('click', function() {
      // Trigger the sidebar toggle
      const navToggle = document.getElementById('nav-toggle');
      if (navToggle) {
        navToggle.click();
      }
    });
  });
</script>