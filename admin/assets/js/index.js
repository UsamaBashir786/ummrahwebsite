document.addEventListener('DOMContentLoaded', function() {
  // Mobile sidebar toggle
  const sidebarToggle = document.getElementById('sidebarToggle');
  const sidebar = document.getElementById('sidebar');
  const sidebarOverlay = document.querySelector('.sidebar-overlay');
  const mainContent = document.querySelector('.main-content');

  if (sidebarToggle && sidebar && sidebarOverlay) {
    sidebarToggle.addEventListener('click', function() {
      sidebar.classList.toggle('show');
      sidebarOverlay.classList.toggle('show');
    });

    sidebarOverlay.addEventListener('click', function() {
      sidebar.classList.remove('show');
      sidebarOverlay.classList.remove('show');
    });
  }

  // Initialize charts (placeholder for actual implementation)
  function initCharts() {
    // Example for bookings chart implementation would go here
    // const bookingsCtx = document.getElementById('bookingsChart').getContext('2d');
    // new Chart(bookingsCtx, { ... });

    // Example for revenue chart implementation would go here
    // const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    // new Chart(revenueCtx, { ... });
  }

  // For demo purposes - actual implementation would connect to real data
  function loadDashboardData() {
    // Fetch bookings data
    // fetch('/api/bookings/stats')
    //   .then(response => response.json())
    //   .then(data => {
    //     // Update DOM with data
    //   });
  }

  // Admin notification system
  function setupNotifications() {
    const notificationBtn = document.getElementById('notificationBtn');

    if (notificationBtn) {
      notificationBtn.addEventListener('click', function() {
        // Show notification panel - this would be implemented with a dropdown or modal
        console.log('Notification panel toggled');
      });
    }
  }

  // Initialize dashboard functions
  // initCharts();
  // loadDashboardData();
  setupNotifications();

  // Enable Bootstrap tooltips
  const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  const tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });
});