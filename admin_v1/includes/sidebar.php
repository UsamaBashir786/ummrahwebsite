<!-- Sidebar -->
<aside class="w-64 bg-white shadow-lg sidebar hidden md:block overflow-y-auto">
  <nav class="p-4">
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

    <div class="space-y-6">
      <div>
        <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Main Menu</p>
        <ul class="mt-2 space-y-1">
          <li>
            <a href="index.php" class="flex items-center px-4 py-2.5 text-sm font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'text-white bg-gradient-to-r from-indigo-600 to-purple-600' : 'text-gray-700 hover:bg-indigo-50'; ?> rounded-lg sidebar-item">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'text-white' : 'text-gray-500'; ?>" viewBox="0 0 20 20" fill="currentColor">
                <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4z" />
                <path d="M3 10a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2z" />
                <path d="M3 16a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2z" />
              </svg>
              <span>Dashboard</span>
            </a>
          </li>
          <li>
            <a href="vehicles.php" class="flex items-center px-4 py-2.5 text-sm font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'vehicles.php' || basename($_SERVER['PHP_SELF']) == 'edit_vehicle_form.php' || basename($_SERVER['PHP_SELF']) == 'vehicle_details.php' ? 'text-white bg-gradient-to-r from-indigo-600 to-purple-600' : 'text-gray-700 hover:bg-indigo-50'; ?> rounded-lg sidebar-item">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 <?php echo basename($_SERVER['PHP_SELF']) == 'vehicles.php' || basename($_SERVER['PHP_SELF']) == 'edit_vehicle_form.php' || basename($_SERVER['PHP_SELF']) == 'vehicle_details.php' ? 'text-white' : 'text-gray-500'; ?>" viewBox="0 0 20 20" fill="currentColor">
                <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zm7 0a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H14a1 1 0 001-1v-3h-5v-1h9V8h-1a1 1 0 00-1-1h-6a1 1 0 00-1 1v7.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1V5a1 1 0 00-1-1H3z" />
              </svg>
              <span>Vehicles</span>
            </a>
          </li>
          <li>
            <a href="inquiries.php" class="flex items-center px-4 py-2.5 text-sm font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'inquiries.php' ? 'text-white bg-gradient-to-r from-indigo-600 to-purple-600' : 'text-gray-700 hover:bg-indigo-50'; ?> rounded-lg sidebar-item">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 <?php echo basename($_SERVER['PHP_SELF']) == 'inquiries.php' ? 'text-white' : 'text-gray-500'; ?>" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2h-1V9a1 1 0 00-1-1z" clip-rule="evenodd" />
              </svg>
              <span>Inquiries</span>
            </a>
          </li>
          <li>
            <a href="contact.php" class="flex items-center px-4 py-2.5 text-sm font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'text-white bg-gradient-to-r from-indigo-600 to-purple-600' : 'text-gray-700 hover:bg-indigo-50'; ?> rounded-lg sidebar-item">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 <?php echo basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'text-white' : 'text-gray-500'; ?>" viewBox="0 0 20 20" fill="currentColor">
                <path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z" />
                <path d="M15 7v2a4 4 0 01-4 4H9.828l-1.766 1.767c.28.149.599.233.938.233h2l3 3v-3h2a2 2 0 002-2V9a2 2 0 00-2-2h-1z" />
              </svg>
              <span>Contact</span>
            </a>
          </li>
          <li>
            <a href="manage_dropdowns.php" class="flex items-center px-4 py-2.5 text-sm font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'manage_dropdowns.php' ? 'text-white bg-gradient-to-r from-indigo-600 to-purple-600' : 'text-gray-700 hover:bg-indigo-50'; ?> rounded-lg sidebar-item">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 <?php echo basename($_SERVER['PHP_SELF']) == 'manage_dropdowns.php' ? 'text-white' : 'text-gray-500'; ?>" viewBox="0 0 20 20" fill="currentColor">
                <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
              </svg>
              <span>Manage Dropdowns</span>
            </a>
          </li>
          <li>
            <a href="users.php" class="flex items-center px-4 py-2.5 text-sm font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'text-white bg-gradient-to-r from-indigo-600 to-purple-600' : 'text-gray-700 hover:bg-indigo-50'; ?> rounded-lg sidebar-item">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'text-white' : 'text-gray-500'; ?>" viewBox="0 0 20 20" fill="currentColor">
                <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zm8 0a3 3 0 11-6 0 3 3 0 016 0zm-4.07 11c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
              </svg>
              <span>Customers</span>
            </a>
          </li>
        </ul>
      </div>

      <div>
        <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Settings</p>
        <ul class="mt-2 space-y-1">
          <li>
            <a href="settings.php" class="flex items-center px-4 py-2.5 text-sm font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'text-white bg-gradient-to-r from-indigo-600 to-purple-600' : 'text-gray-700 hover:bg-indigo-50'; ?> rounded-lg sidebar-item">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'text-white' : 'text-gray-500'; ?>" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />
              </svg>
              <span>Settings</span>
            </a>
          </li>
        </ul>
      </div>
    </div>
  </nav>
</aside>