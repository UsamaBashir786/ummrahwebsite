<!-- Header -->
<header class="bg-gradient-to-r from-indigo-700 to-purple-700 text-white shadow-lg">
  <div class="container mx-auto px-4 py-4 flex justify-between items-center">
    <div class="flex items-center space-x-4">
      <div class="flex items-center space-x-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" viewBox="0 0 20 20" fill="currentColor">
          <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zm7 0a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
          <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H14a1 1 0 001-1v-3h-5v-1h9V8h-1a1 1 0 00-1-1h-6a1 1 0 00-1 1v7.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1V5a1 1 0 00-1-1H3z" />
        </svg>
        <h1 class="text-2xl font-bold">CentralAutogy</h1>
      </div>
      <span class="hidden md:inline-block text-sm bg-white text-black bg-opacity-20 px-3 py-1 rounded-full">Admin Dashboard</span>
    </div>
    <div class="flex items-center space-x-6">
      <div class="relative hidden md:block">
        <div class="flex items-center space-x-2">
          <div class="h-8 w-8 rounded-full bg-black bg-opacity-20 flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd" />
            </svg>
          </div>
          <span class="font-medium">Welcome, <?php echo htmlspecialchars($_SESSION["admin_name"]); ?></span>
        </div>
      </div>
      <div class="flex items-center space-x-1">
        <a href="notifications.php" class="p-2 rounded-full hover:bg-black hover:bg-opacity-10 transition-all relative">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z" />
          </svg>
          <?php
          // Check for unread notifications - this is a placeholder
          // You would implement actual notification count logic here
          $notification_count = 0; // Replace with actual count from database
          if ($notification_count > 0):
          ?>
            <span class="absolute top-0 right-0 h-4 w-4 bg-red-500 rounded-full text-xs flex items-center justify-center"><?php echo $notification_count; ?></span>
          <?php endif; ?>
        </a>
        <a href="help.php" class="p-2 rounded-full hover:bg-black hover:bg-opacity-10 transition-all">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
          </svg>
        </a>
        <a href="logout.php" id="logoutBtn" class="flex items-center space-x-1 bg-white bg-opacity-10 hover:bg-black hover:text-white  px-3 py-1.5 rounded-full text-sm font-medium transition-all">
          <span class="text-red-600 hover:text-white">Logout</span>
        </a>
      </div>
    </div>
  </div>
</header>

<!-- Mobile Admin Info - Visible on small screens -->
<div class="md:hidden bg-gray-100 p-3 flex items-center justify-between">
  <div class="flex items-center space-x-2">
    <div class="h-8 w-8 rounded-full bg-indigo-600 flex items-center justify-center text-white">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd" />
      </svg>
    </div>
    <div>
      <p class="font-medium text-gray-800"><?php echo htmlspecialchars($_SESSION["admin_name"]); ?></p>
      <p class="text-xs text-gray-500"><?php echo htmlspecialchars($_SESSION["admin_role"]); ?></p>
    </div>
  </div>
  <div>
    <span class="inline-block text-xs bg-indigo-600 text-white px-2 py-1 rounded">Admin Dashboard</span>
  </div>
</div>

<script>
  // Confirm logout
  document.getElementById('logoutBtn').addEventListener('click', function(e) {
    if (!confirm('Are you sure you want to logout?')) {
      e.preventDefault();
    }
  });
</script>