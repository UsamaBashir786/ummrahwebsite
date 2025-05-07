<?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
<link rel="stylesheet" href="../src/output.css">
<style>
  #nav-close {
    display: block;
    /* Default: show on small screen */
  }

  @media (min-width: 1024px) {
    #nav-close {
      display: none;
      /* Hide on large screens and up */
    }
  }

  /* Cursor styling for interactive elements */
  .nav-link,
  .nav-btn,
  .nav-btn[data-nav-target] {
    cursor: pointer !important;
  }

  /* Sidebar and overlay z-index */
  .nav-panel,
  .nav-overlay {
    z-index: 10000 !important;
  }

  /* Content area styling */
  .content-area {
    margin-left: 0;
    padding: 1.5rem;
    transition: margin-left 0.3s ease-in-out;
  }

  /* Responsive sidebar width */
  @media (min-width: 1024px) {
    .content-area {
      margin-left: 280px;
      /* Matches sidebar width */
    }
  }

  /* Ensure sidebar is scrollable */
  .nav-panel {
    overflow-y: auto;
  }
  .navbar-target{
      overflow-y:scroll;
      z-index:1000 !important;
  }
</style>

<!-- Sidebar Overlay -->
<div class="fixed inset-0 bg-opacity-60 hidden transition-opacity duration-300" id="nav-overlay"></div>

<!-- Sidebar -->
<nav class="navbar-target overflow-y-scroll fixed top-0 left-0 w-70 h-full bg-gray-50 shadow-xl transform -translate-x-full transition-transform duration-300 lg:translate-x-0" id="nav-panel">
  <div class="p-5">
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
      <div class="flex items-center space-x-3">
        <i class="fas fa-plane-departure text-2xl text-blue-700"></i>
        <h5 class="font-semibold text-xl text-gray-900">Ummrah</h5>
      </div>
      <button class="block lg:hidden w-5 text-gray-600 hover:text-gray-800 text-2xl focus:outline-none" id="nav-close">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
        &nbsp;
      </button>
    </div>

    <!-- Menu Sections -->
    <div class="space-y-8">
      <!-- Dashboard Section -->
      <div>
        <p class="px-3 text-xs font-bold text-gray-500 uppercase tracking-wide">Overview</p>
        <ul class="mt-3 space-y-2">
          <li>
            <a href="index.php" class="nav-link flex items-center px-3 py-2 text-sm font-semibold rounded-md <?= $currentPage == 'index.php' ? 'text-white bg-blue-600' : 'text-gray-800 hover:bg-blue-100' ?>">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 <?= $currentPage == 'index.php' ? 'text-white' : 'text-gray-600' ?>" viewBox="0 0 20 20" fill="currentColor">
                <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4z" />
                <path d="M3 10a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2z" />
                <path d="M3 16a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2z" />
              </svg>
              Dashboard
            </a>
          </li>
        </ul>
      </div>

      <!-- Management Section -->
      <div>
        <p class="px-3 text-xs font-bold text-gray-500 uppercase tracking-wide">Management</p>
        <ul class="mt-3 space-y-2">
          <!-- Flights Dropdown -->
          <li>
            <button class="nav-btn flex items-center justify-between w-full px-3 py-2 text-sm font-semibold text-gray-800 hover:bg-blue-100 rounded-md" data-nav-target="#nav-flights">
              <span class="flex items-center space-x-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600" viewBox="0 0 20 20" fill="currentColor">
                  <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                </svg>
                Flights
              </span>
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <div class="hidden space-y-2 pl-5 mt-2" id="nav-flights">
              <a href="add-flight.php" class="nav-link flex items-center px-3 py-2 text-sm rounded-md <?= $currentPage == 'add-flight.php' ? 'text-white bg-blue-600' : 'text-gray-800 hover:bg-blue-100' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-gray-600" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                </svg>
                Add Flight
              </a>
              <a href="view-flights.php" class="nav-link flex items-center px-3 py-2 text-sm rounded-md <?= $currentPage == 'view-flights.php' ? 'text-white bg-blue-600' : 'text-gray-800 hover:bg-blue-100' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-gray-600" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                </svg>
                View Flights
              </a>
              <a href="booked-flights.php" class="nav-link flex items-center px-3 py-2 text-sm rounded-md <?= $currentPage == 'booked-flights.php' ? 'text-white bg-blue-600' : 'text-gray-800 hover:bg-blue-100' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-gray-600" viewBox="0 0 20 20" fill="currentColor">
                  <path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z" />
                </svg>
                Flight Bookings
              </a>
            </div>
          </li>

          <!-- Hotels Dropdown -->
          <li>
            <button class="nav-btn flex items-center justify-between w-full px-3 py-2 text-sm font-semibold text-gray-800 hover:bg-blue-100 rounded-md" data-nav-target="#nav-hotels">
              <span class="flex items-center space-x-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600" viewBox="0 0 20 20" fill="currentColor">
                  <path d="M2 4a2 2 0 012-2h12a2 2 0 012 2v12a2 2 0 01-2 2H4a2 2 0 01-2-2V4zm4 12h8v-2H6v2zm0-4h8v-2H6v2zm0-4h8V6H6v2z" />
                </svg>
                Hotels
              </span>
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <div class="hidden space-y-2 pl-5 mt-2" id="nav-hotels">
              <a href="add-hotels.php" class="nav-link flex items-center px-3 py-2 text-sm rounded-md <?= $currentPage == 'add-hotels.php' ? 'text-white bg-blue-600' : 'text-gray-800 hover:bg-blue-100' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-gray-600" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                </svg>
                Add Hotel
              </a>
              <a href="view-hotels.php" class="nav-link flex items-center px-3 py-2 text-sm rounded-md <?= $currentPage == 'view-hotels.php' ? 'text-white bg-blue-600' : 'text-gray-800 hover:bg-blue-100' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-gray-600" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                </svg>
                View Hotels
              </a>
              <a href="booked-hotels.php" class="nav-link flex items-center px-3 py-2 text-sm rounded-md <?= $currentPage == 'booked-hotels.php' ? 'text-white bg-blue-600' : 'text-gray-800 hover:bg-blue-100' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-gray-600" viewBox="0 0 20 20" fill="currentColor">
                  <path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z" />
                </svg>
                Hotel Bookings
              </a>
            </div>
          </li>

          <!-- Packages Dropdown -->
          <li>
            <button class="nav-btn flex items-center justify-between w-full px-3 py-2 text-sm font-semibold text-gray-800 hover:bg-blue-100 rounded-md" data-nav-target="#nav-packages">
              <span class="flex items-center space-x-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600" viewBox="0 0 20 20" fill="currentColor">
                  <path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z" />
                </svg>
                Packages
              </span>
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <div class="hidden space-y-2 pl-5 mt-2" id="nav-packages">
              <a href="add-packages.php" class="nav-link flex items-center px-3 py-2 text-sm rounded-md <?= $currentPage == 'add-packages.php' ? 'text-white bg-blue-600' : 'text-gray-800 hover:bg-blue-100' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-gray-600" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                </svg>
                Add Package
              </a>
              <a href="view-packages.php" class="nav-link flex items-center px-3 py-2 text-sm rounded-md <?= $currentPage == 'view-packages.php' ? 'text-white bg-blue-600' : 'text-gray-800 hover:bg-blue-100' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-gray-600" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                </svg>
                View Packages
              </a>
              <a href="booked-packages.php" class="nav-link flex items-center px-3 py-2 text-sm rounded-md <?= $currentPage == 'booked-packages.php' ? 'text-white bg-blue-600' : 'text-gray-800 hover:bg-blue-100' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-gray-600" viewBox="0 0 20 20" fill="currentColor">
                  <path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z" />
                </svg>
                Package Bookings
              </a>
            </div>
          </li>

          <!-- Transportation Dropdown -->
          <li>
            <button class="nav-btn flex items-center justify-between w-full px-3 py-2 text-sm font-semibold text-gray-800 hover:bg-blue-100 rounded-md" data-nav-target="#nav-transport">
              <span class="flex items-center space-x-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600" viewBox="0 0 20 20" fill="currentColor">
                  <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zm7 0a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                  <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H14a1 1 0 001-1v-3h-5v-1h9V8h-1a1 1 0 00-1-1h-6a1 1 0 00-1 1v7.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1V5a1 1 0 00-1-1H3z" />
                </svg>
                Transportation
              </span>
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <div class="hidden space-y-2 pl-5 mt-2" id="nav-transport">
              <a href="add-transportation.php" class="nav-link flex items-center px-3 py-2 text-sm rounded-md <?= $currentPage == 'add-transportation.php' ? 'text-white bg-blue-600' : 'text-gray-800 hover:bg-blue-100' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-gray-600" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                </svg>
                Add Transportation
              </a>
              <a href="view-transportation.php" class="nav-link flex items-center px-3 py-2 text-sm rounded-md <?= $currentPage == 'view-transportation.php' ? 'text-white bg-blue-600' : 'text-gray-800 hover:bg-blue-100' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-gray-600" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-manual="evenodd" />
                </svg>
                View Transportation
              </a>
              <a href="booked-transportation.php" class="nav-link flex items-center px-3 py-2 text-sm rounded-md <?= $currentPage == 'booked-transportation.php' ? 'text-white bg-blue-600' : 'text-gray-800 hover:bg-blue-100' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-gray-600" viewBox="0 0 20 20" fill="currentColor">
                  <path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z" />
                </svg>
                Transportation Bookings
              </a>
            </div>
          </li>

          <!-- Assignments -->
          <li>
            <a href="admin_bookings.php" class="nav-link flex items-center px-3 py-2 text-sm font-semibold rounded-md <?= $currentPage == 'admin_bookings.php' ? 'text-white bg-blue-600' : 'text-gray-800 hover:bg-blue-100' ?>">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 <?= $currentPage == 'admin_bookings.php' ? 'text-white' : 'text-gray-600' ?>" viewBox="0 0 20 20" fill="currentColor">
                <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zm8 0a3 3 0 11-6 0 3 3 0 016 0zm-4.07 11c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
              </svg>
              Assignments
            </a>
          </li>

          <!-- Users -->
          <li>
            <a href="users.php" class="nav-link flex items-center px-3 py-2 text-sm font-semibold rounded-md <?= $currentPage == 'users.php' ? 'text-white bg-blue-600' : 'text-gray-800 hover:bg-blue-100' ?>">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 <?= $currentPage == 'users.php' ? 'text-white' : 'text-gray-600' ?>" viewBox="0 0 20 20" fill="currentColor">
                <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zm8 0a3 3 0 11-6 0 3 3 0 016 0zm-4.07 11c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
              </svg>
              Users
            </a>
          </li>

          <!-- Contact -->
          <li>
            <a href="contact-us.php" class="nav-link flex items-center px-3 py-2 text-sm font-semibold rounded-md <?= $currentPage == 'contact-us.php' ? 'text-white bg-blue-600' : 'text-gray-800 hover:bg-blue-100' ?>">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 <?= $currentPage == 'contact-us.php' ? 'text-white' : 'text-gray-600' ?>" viewBox="0 0 20 20" fill="currentColor">
                <path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z" />
                <path d="M15 7v2a4 4 0 01-4 4H9.828l-1.766 1.767c.28.149.599.233.938.233h2l3 3v-3h2a2 2 0 002-2V9a2 2 0 00-2-2h-1z" />
              </svg>
              Contact
            </a>
          </li>
          
                <!-- FAQ's -->
          <li>
            <a href="faqs.php" class="nav-link flex items-center px-3 py-2 text-sm font-semibold rounded-md <?= $currentPage == 'faqs.php' ? 'text-white bg-blue-600' : 'text-gray-800 hover:bg-blue-100' ?>">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 <?= $currentPage == 'faqs.php' ? 'text-white' : 'text-gray-600' ?>" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
              </svg>
              FAQs
            </a>
          </li>
            <li>
              <a href="contact-info.php" class="nav-link flex items-center px-3 py-2 text-sm font-semibold rounded-md <?= $currentPage == 'contact-info.php' ? 'text-white bg-blue-600' : 'text-gray-800 hover:bg-blue-100' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 <?= $currentPage == 'contact-info.php' ? 'text-white' : 'text-gray-600' ?>" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 2a1 1 0 00-1 1v1a1 1 0 002 0V3a1 1 0 00-1-1zM4 4h3a3 3 0 006 0h3a2 2 0 012 2v9a2 2 0 01-2 2H4a2 2 0 01-2-2V6a2 2 0 012-2zm2.5 7a1.5 1.5 0 100-3 1.5 1.5 0 000 3zm2.45 4a2.5 2.5 0 10-4.9 0h4.9zM12 9a1 1 0 100 2h3a1 1 0 100-2h-3zm-1 4a1 1 0 011-1h2a1 1 0 110 2h-2a1 1 0 01-1-1z" clip-rule="evenodd" />
                </svg>
                Contact Info
              </a>
            </li>
            <!-- About Us -->
<li>
  <a href="about-us.php" class="nav-link flex items-center px-3 py-2 text-sm font-semibold rounded-md <?= $currentPage == 'about-us.php' ? 'text-white bg-blue-600' : 'text-gray-800 hover:bg-blue-100' ?>">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 <?= $currentPage == 'about-us.php' ? 'text-white' : 'text-gray-600' ?>" viewBox="0 0 20 20" fill="currentColor">
      <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z" />
    </svg>
    About Us
  </a>
</li>
        </ul>
      </div>

      <!-- Settings Section -->
      <div>
        <p class="px-3 text-xs font-bold text-gray-500 uppercase tracking-wide">Settings</p>
        <ul class="mt-3 space-y-2">
          <li>
            <a href="logout.php" class="nav-link flex items-center px-3 py-2 text-sm font-semibold text-red-600 hover:bg-red-100 rounded-md">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-red-600" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 3.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L16.586 11H7a1 1 0 110-2h9.586l-3.293-3.293a1 1 0 010-1.414z" clip-rule="evenodd" />
              </svg>
              Logout
            </a>
          </li>
        </ul>
      </div>
    </div>
  </div>
</nav>

<!-- Hamburger Toggle Button -->
<button class="fixed top-4 left-4 z-[10001] p-2 bg-blue-600 rounded-md shadow-md focus:outline-none lg:hidden" id="nav-toggle">
  <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
  </svg>
</button>

<script>
  const navPanel = document.getElementById('nav-panel');
  const navOverlay = document.getElementById('nav-overlay');
  const navToggle = document.getElementById('nav-toggle');
  const navClose = document.getElementById('nav-close');
  const navButtons = document.querySelectorAll('[data-nav-target]');

  // Open Sidebar
  navToggle.addEventListener('click', () => {
    navPanel.classList.remove('-translate-x-full');
    navOverlay.classList.remove('hidden');
    navToggle.classList.add('hidden');
  });

  // Close Sidebar
  const closeSidebar = () => {
    navPanel.classList.add('-translate-x-full');
    navOverlay.classList.add('hidden');
    navToggle.classList.remove('hidden');
  };

  navClose.addEventListener('click', closeSidebar);
  navOverlay.addEventListener('click', closeSidebar);

  // Dropdown Toggle
  navButtons.forEach(button => {
    button.addEventListener('click', () => {
      const target = document.querySelector(button.getAttribute('data-nav-target'));
      target.classList.toggle('hidden');
    });
  });

  // Handle screen resize to ensure proper sidebar state
  window.addEventListener('resize', () => {
    if (window.innerWidth >= 1024) {
      // Ensure sidebar is active on desktop
      navPanel.classList.remove('-translate-x-full');
      navOverlay.classList.add('hidden');
      navToggle.classList.add('hidden');
    } else {
      // Ensure sidebar is hidden on mobile until toggled
      navPanel.classList.add('-translate-x-full');
      navOverlay.classList.add('hidden');
      navToggle.classList.remove('hidden');
    }
  });

  // Initialize sidebar state on page load
  if (window.innerWidth >= 1024) {
    navPanel.classList.remove('-translate-x-full');
    navOverlay.classList.add('hidden');
    navToggle.classList.add('hidden');
  } else {
    navPanel.classList.add('-translate-x-full');
    navOverlay.classList.add('hidden');
    navToggle.classList.remove('hidden');
  }
</script>
<script>
  // Dropdown toggle logic
  document.querySelectorAll('.nav-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const targetId = btn.getAttribute('data-nav-target');
      const targetEl = document.querySelector(targetId);
      if (targetEl) {
        targetEl.classList.toggle('hidden');
      }
    });
  });

  // Sidebar close button for mobile
  const navCloseBtn = document.getElementById('nav-close');
  const navOverlay = document.getElementById('nav-overlay');
  const navPanel = document.getElementById('nav-panel');

  navCloseBtn?.addEventListener('click', () => {
    navPanel.classList.add('-translate-x-full');
    navOverlay.classList.add('hidden');
  });

  // Optional: overlay click closes menu
  navOverlay?.addEventListener('click', () => {
    navPanel.classList.add('-translate-x-full');
    navOverlay.classList.add('hidden');
  });
</script>

