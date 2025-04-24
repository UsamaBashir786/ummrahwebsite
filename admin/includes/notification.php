<?php
// notification.php
require_once '../config/db.php'; // Adjust path as needed

class NotificationSystem
{
  private $conn;

  public function __construct($connection)
  {
    $this->conn = $connection;
  }

  /**
   * Retrieve recent notifications across all booking types
   * @param int $limit Number of notifications to retrieve
   * @return array Associative array of notifications
   */
  public function getRecentNotifications($limit = 10)
  {
    $notifications = [];

    // Flight Bookings
    $flight_query = "SELECT 
            'flight' AS type, 
            id, 
            passenger_name AS name, 
            booking_status, 
            created_at,
            total_price,
            CONCAT('Flight Booking #', id) AS reference
        FROM flight_bookings 
        ORDER BY created_at DESC 
        LIMIT $limit";

    // Hotel Bookings
    $hotel_query = "SELECT 
            'hotel' AS type, 
            id, 
            booking_reference AS reference, 
            booking_status, 
            created_at,
            total_price,
            (SELECT hotel_name FROM hotels WHERE id = hotel_bookings.hotel_id) AS name
        FROM hotel_bookings 
        ORDER BY created_at DESC 
        LIMIT $limit";

    // Transportation Bookings
    $transport_query = "SELECT 
            'transportation' AS type, 
            id, 
            full_name AS name, 
            booking_status, 
            created_at,
            price AS total_price,
            CONCAT(transport_type, ' - ', route_name) AS reference
        FROM transportation_bookings 
        ORDER BY created_at DESC 
        LIMIT $limit";

    // Package Bookings
    $package_query = "SELECT 
            'package' AS type, 
            id, 
            (SELECT title FROM umrah_packages WHERE id = package_bookings.package_id) AS name, 
            booking_status, 
            created_at,
            total_price,
            CONCAT('Package Booking #', id) AS reference
        FROM package_bookings 
        ORDER BY created_at DESC 
        LIMIT $limit";

    // Combine and sort all notifications
    $combined_query = "
            SELECT * FROM (
                ($flight_query)
                UNION ALL
                ($hotel_query)
                UNION ALL
                ($transport_query)
                UNION ALL
                ($package_query)
            ) AS combined_notifications
            ORDER BY created_at DESC
            LIMIT $limit
        ";

    $result = $this->conn->query($combined_query);

    if ($result) {
      while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
      }
    }

    return $notifications;
  }

  /**
   * Count unhandled notifications across all booking types
   * @return array Associative array of notification counts
   */
  public function getUnhandledNotificationsCounts()
  {
    $counts = [
      'pending_flight_bookings' => 0,
      'pending_hotel_bookings' => 0,
      'pending_transportation_bookings' => 0,
      'pending_package_bookings' => 0,
      'total_pending' => 0
    ];

    // Flight Bookings
    $flight_query = "SELECT COUNT(*) AS count FROM flight_bookings WHERE booking_status = 'pending'";
    $flight_result = $this->conn->query($flight_query);
    if ($flight_result) {
      $counts['pending_flight_bookings'] = $flight_result->fetch_assoc()['count'];
    }

    // Hotel Bookings
    $hotel_query = "SELECT COUNT(*) AS count FROM hotel_bookings WHERE booking_status = 'pending'";
    $hotel_result = $this->conn->query($hotel_query);
    if ($hotel_result) {
      $counts['pending_hotel_bookings'] = $hotel_result->fetch_assoc()['count'];
    }

    // Transportation Bookings
    $transport_query = "SELECT COUNT(*) AS count FROM transportation_bookings WHERE booking_status = 'pending'";
    $transport_result = $this->conn->query($transport_query);
    if ($transport_result) {
      $counts['pending_transportation_bookings'] = $transport_result->fetch_assoc()['count'];
    }

    // Package Bookings
    $package_query = "SELECT COUNT(*) AS count FROM package_bookings WHERE booking_status = 'pending'";
    $package_result = $this->conn->query($package_query);
    if ($package_result) {
      $counts['pending_package_bookings'] = $package_result->fetch_assoc()['count'];
    }

    // Calculate total pending
    $counts['total_pending'] =
      $counts['pending_flight_bookings'] +
      $counts['pending_hotel_bookings'] +
      $counts['pending_transportation_bookings'] +
      $counts['pending_package_bookings'];

    return $counts;
  }

  /**
   * Generate a human-readable time difference
   * @param string $datetime Datetime string
   * @return string Humanized time difference
   */
  public function humanTimeDiff($datetime)
  {
    $now = new DateTime();
    $past = new DateTime($datetime);
    $diff = $now->diff($past);

    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'Just now';
  }

  /**
   * Generate an icon based on booking type
   * @param string $type Booking type
   * @return string HTML for icon
   */
  public function getNotificationIcon($type)
  {
    $icons = [
      'flight' => '<i class="fas fa-plane text-blue-500"></i>',
      'hotel' => '<i class="fas fa-hotel text-green-500"></i>',
      'transportation' => '<i class="fas fa-car text-purple-500"></i>',
      'package' => '<i class="fas fa-box text-orange-500"></i>'
    ];
    return $icons[$type] ?? '<i class="fas fa-bell text-gray-500"></i>';
  }

  /**
   * Get status color class
   * @param string $status Booking status
   * @return string Tailwind color class
   */
  public function getStatusColor($status)
  {
    $colors = [
      'pending' => 'text-yellow-600 bg-yellow-100',
      'confirmed' => 'text-green-600 bg-green-100',
      'cancelled' => 'text-red-600 bg-red-100'
    ];
    return $colors[$status] ?? 'text-gray-600 bg-gray-100';
  }
}

// Usage in admin dashboard
if (isset($_SESSION['admin_loggedin']) && $_SESSION['admin_loggedin'] === true) {
  $notificationSystem = new NotificationSystem($conn);

  // Get recent notifications
  $recentNotifications = $notificationSystem->getRecentNotifications(5);

  // Get pending notification counts
  $notificationCounts = $notificationSystem->getUnhandledNotificationsCounts();
}
?>

<?php if (isset($recentNotifications) && !empty($recentNotifications)): ?>
  <div class="notification-dropdown absolute top-full right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 z-50 max-h-96 overflow-y-auto">
    <div class="p-4 border-b border-gray-200 flex justify-between items-center">
      <h3 class="font-semibold text-gray-800">Recent Notifications</h3>
      <span class="bg-red-500 text-white text-xs rounded-full px-2 py-1">
        <?php echo $notificationCounts['total_pending']; ?> Pending
      </span>
    </div>
    <?php foreach ($recentNotifications as $notification): ?>
      <div class="p-4 border-b border-gray-200 hover:bg-gray-50 transition duration-200 flex items-start">
        <div class="mr-3">
          <?php echo $notificationSystem->getNotificationIcon($notification['type']); ?>
        </div>
        <div class="flex-1">
          <div class="flex justify-between items-center mb-1">
            <h4 class="font-medium text-sm text-gray-800">
              <?php echo htmlspecialchars($notification['name']); ?>
            </h4>
            <span class="text-xs text-gray-500">
              <?php echo $notificationSystem->humanTimeDiff($notification['created_at']); ?>
            </span>
          </div>
          <p class="text-xs text-gray-600 mb-1">
            <?php echo htmlspecialchars($notification['reference']); ?>
          </p>
          <span class="inline-block text-xs px-2 py-1 rounded <?php echo $notificationSystem->getStatusColor($notification['booking_status']); ?>">
            <?php echo ucfirst(htmlspecialchars($notification['booking_status'])); ?>
          </span>
          <p class="text-xs text-gray-500 mt-1">
            Total Amount: PKR <?php echo number_format($notification['total_price'], 2); ?>
          </p>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>