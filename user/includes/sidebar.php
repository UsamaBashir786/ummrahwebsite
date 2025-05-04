<?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
<link rel="stylesheet" href="../src/output.css">
<style>
  #nav-close {
    display: block;
  }

  @media (min-width: 1024px) {
    #nav-close {
      display: none;
    }
  }

  .nav-link,
  .nav-btn,
  .nav-btn[data-nav-target] {
    cursor: pointer !important;
  }

  .nav-panel,
  .nav-overlay {
    z-index: 10000 !important;
  }

  .content-area {
    margin-left: 0;
    padding: 1.5rem;
    transition: margin-left 0.3s ease-in-out;
  }

  @media (min-width: 1024px) {
    .content-area {
      margin-left: 280px;
    }
  }

  .nav-panel {
    overflow-y: auto;
  }

  nav {
    overflow-y: scroll;
  }
</style>

<!-- Sidebar Overlay -->
<div class="fixed inset-0 bg-gray-900 bg-opacity-60 hidden transition-opacity duration-300" id="nav-overlay"></div>

<!-- Sidebar -->
<nav class="overflow-y-scroll fixed top-0 left-0 w-72 h-full bg-gray-50 shadow-xl transform -translate-x-full transition-transform duration-300 lg:translate-x-0" id="nav-panel">
  <div class="p-5">
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
      <div class="flex items-center space-x-3">
        <i class="fas fa-plane-departure text-2xl text-cyan-600"></i>
        <h5 class="font-semibold text-xl text-gray-900">Ummrah</h5>
      </div>
      <button class="block lg:hidden w-5 bg-amber-950 text-gray-600 hover:text-gray-800 text-2xl focus:outline-none" id="nav-close">
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
            <a href="index.php" class="nav-link flex items-center px-3 py-2 text-sm font-semibold rounded-md <?= $currentPage == 'index.php' ? 'text-white bg-cyan-600' : 'text-gray-800 hover:bg-cyan-100' ?>">
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

      <!-- Bookings Section -->
      <div>
        <p class="px-3 text-xs font-bold text-gray-500 uppercase tracking-wide">My Bookings</p>
        <ul class="mt-3 space-y-2">
          <!-- Flight Bookings -->
          <li>
            <a href="flights.php" class="nav-link flex items-center px-3 py-2 text-sm font-semibold rounded-md <?= $currentPage == 'flights.php' ? 'text-white bg-cyan-600' : 'text-gray-800 hover:bg-cyan-100' ?>">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 <?= $currentPage == 'flights.php' ? 'text-white' : 'text-gray-600' ?>" viewBox="0 0 20 20" fill="currentColor">
                <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
              </svg>
              Flight Bookings
            </a>
          </li>

          <!-- Hotel Bookings -->
          <li>
            <a href="hotels.php" class="nav-link flex items-center px-3 py-2 text-sm font-semibold rounded-md <?= $currentPage == 'hotels.php' ? 'text-white bg-cyan-600' : 'text-gray-800 hover:bg-cyan-100' ?>">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 <?= $currentPage == 'hotels.php' ? 'text-white' : 'text-gray-600' ?>" viewBox="0 0 20 20" fill="currentColor">
                <path d="M2 4a2 2 0 012-2h12a2 2 0 012 2v12a2 2 0 01-2 2H4a2 2 0 01-2-2V4zm4 12h8v-2H6v2zm0-4h8v-2H6v2zm0-4h8V6H6v2z" />
              </svg>
              Hotel Bookings
            </a>
          </li>

          <!-- Package Bookings -->
          <li>
            <a href="packages.php" class="nav-link flex items-center px-3 py-2 text-sm font-semibold rounded-md <?= $currentPage == 'packages.php' ? 'text-white bg-cyan-600' : 'text-gray-800 hover:bg-cyan-100' ?>">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 <?= $currentPage == 'packages.php' ? 'text-white' : 'text-gray-600' ?>" viewBox="0 0 20 20" fill="currentColor">
                <path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z" />
              </svg>
              Package Bookings
            </a>
          </li>

          <!-- Transportation Bookings -->
          <li>
            <a href="transportation.php" class="nav-link flex items-center px-3 py-2 text-sm font-semibold rounded-md <?= $currentPage == 'transportation.php' ? 'text-white bg-cyan-600' : 'text-gray-800 hover:bg-cyan-100' ?>">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 <?= $currentPage == 'transportation.php' ? 'text-white' : 'text-gray-600' ?>" viewBox="0 0 20 20" fill="currentColor">
                <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zm7 0a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H14a1 1 0 001-1v-3h-5v-1h9V8h-1a1 1 0 00-1-1h-6a1 1 0 00-1 1v7.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1V5a1 1 0 00-1-1H3z" />
              </svg>
              Transportation Bookings
            </a>
          </li>

          <!-- All Bookings -->
          <li>
            <a href="booking-history.php" class="nav-link flex items-center px-3 py-2 text-sm font-semibold rounded-md <?= $currentPage == 'booking-history.php' ? 'text-white bg-cyan-600' : 'text-gray-800 hover:bg-cyan-100' ?>">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 <?= $currentPage == 'booking-history.php' ? 'text-white' : 'text-gray-600' ?>" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
              </svg>
              Booking History
            </a>
          </li>
        </ul>
      </div>

      <!-- Account Section -->
      <div>
        <p class="px-3 text-xs font-bold text-gray-500 uppercase tracking-wide">Account</p>
        <ul class="mt-3 space-y-2">
          <li>
            <a href="profile.php" class="nav-link flex items-center px-3 py-2 text-sm font-semibold rounded-md <?= $currentPage == 'profile.php' ? 'text-white bg-cyan-600' : 'text-gray-800 hover:bg-cyan-100' ?>">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 <?= $currentPage == 'profile.php' ? 'text-white' : 'text-gray-600' ?>" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
              </svg>
              My Profile
            </a>
          </li>

        </ul>
      </div>

      <!-- Support Section -->
      <div>
        <p class="px-3 text-xs font-bold text-gray-500 uppercase tracking-wide">Support</p>
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
<button class="fixed top-4 left-4 z-[10001] p-2 bg-cyan-600 rounded-md shadow-md focus:outline-none lg:hidden" id="nav-toggle">
  <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
  </svg>
</button>

<script>
  const navPanel = document.getElementById('nav-panel');
  const navOverlay = document.getElementById('nav-overlay');
  const navToggle = document.getElementById('nav-toggle');
  const navClose = document.getElementById('nav-close');

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

  // Handle screen resize
  window.addEventListener('resize', () => {
    if (window.innerWidth >= 1024) {
      navPanel.classList.remove('-translate-x-full');
      navOverlay.classList.add('hidden');
      navToggle.classList.add('hidden');
    } else {
      navPanel.classList.add('-translate-x-full');
      navOverlay.classList.add('hidden');
      navToggle.classList.remove('hidden');
    }
  });

  // Initialize sidebar state
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