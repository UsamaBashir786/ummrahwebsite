<?php
// Ensure database connection is available
if (!isset($conn)) {
  require_once '../config/db.php';
}

// Initialize stats array
$stats = [
  'total_hotels' => 0,
  'total_rooms' => 0,
  'available_rooms' => 0,
  'booked_rooms' => 0,
  'makkah_hotels' => 0,
  'madinah_hotels' => 0,
  'avg_price' => 0,
  'min_price' => 0,
  'max_price' => 0,
  'rating_5' => 0,
  'rating_4' => 0,
  'rating_3' => 0,
  'rating_2' => 0,
  'rating_1' => 0,
  'with_images' => 0,
  'without_images' => 0,
  'total_bookings' => 0,
  'estimated_revenue' => 0,
  'amenities' => [
    'wifi' => 0,
    'parking' => 0,
    'restaurant' => 0,
    'gym' => 0,
    'pool' => 0,
    'ac' => 0,
    'room_service' => 0,
    'spa' => 0
  ]
];

// Fetch hotel stats
$stats_query = "SELECT 
    COUNT(*) as total_hotels,
    SUM(room_count) as total_rooms,
    AVG(price) as avg_price,
    MIN(price) as min_price,
    MAX(price) as max_price,
    COUNT(CASE WHEN location = 'makkah' THEN 1 END) as makkah_hotels,
    COUNT(CASE WHEN location = 'madinah' THEN 1 END) as madinah_hotels,
    COUNT(CASE WHEN rating = 5 THEN 1 END) as rating_5,
    COUNT(CASE WHEN rating = 4 THEN 1 END) as rating_4,
    COUNT(CASE WHEN rating = 3 THEN 1 END) as rating_3,
    COUNT(CASE WHEN rating = 2 THEN 1 END) as rating_2,
    COUNT(CASE WHEN rating = 1 THEN 1 END) as rating_1,
    COUNT(CASE WHEN EXISTS (SELECT 1 FROM hotel_images WHERE hotel_id = hotels.id) THEN 1 END) as with_images,
    COUNT(CASE WHEN NOT EXISTS (SELECT 1 FROM hotel_images WHERE hotel_id = hotels.id) THEN 1 END) as without_images,
    COUNT(CASE WHEN amenities LIKE '%wifi%' THEN 1 END) as wifi,
    COUNT(CASE WHEN amenities LIKE '%parking%' THEN 1 END) as parking,
    COUNT(CASE WHEN amenities LIKE '%restaurant%' THEN 1 END) as restaurant,
    COUNT(CASE WHEN amenities LIKE '%gym%' THEN 1 END) as gym,
    COUNT(CASE WHEN amenities LIKE '%pool%' THEN 1 END) as pool,
    COUNT(CASE WHEN amenities LIKE '%ac%' THEN 1 END) as ac,
    COUNT(CASE WHEN amenities LIKE '%room_service%' THEN 1 END) as room_service,
    COUNT(CASE WHEN amenities LIKE '%spa%' THEN 1 END) as spa
FROM hotels";

$stats_result = $conn->query($stats_query);
if ($stats_result && $stats_result->num_rows > 0) {
  $stats_data = $stats_result->fetch_assoc();
  $stats = array_merge($stats, $stats_data);
  $stats['amenities'] = [
    'wifi' => $stats_data['wifi'],
    'parking' => $stats_data['parking'],
    'restaurant' => $stats_data['restaurant'],
    'gym' => $stats_data['gym'],
    'pool' => $stats_data['pool'],
    'ac' => $stats_data['ac'],
    'room_service' => $stats_data['room_service'],
    'spa' => $stats_data['spa']
  ];
}

// Fetch room status stats
$room_stats_query = "SELECT 
    COUNT(*) as total_rooms,
    COUNT(CASE WHEN status = 'available' THEN 1 END) as available_rooms,
    COUNT(CASE WHEN status = 'booked' THEN 1 END) as booked_rooms
FROM hotel_rooms";
$room_stats_result = $conn->query($room_stats_query);
if ($room_stats_result && $room_stats_result->num_rows > 0) {
  $room_stats = $room_stats_result->fetch_assoc();
  $stats['total_rooms'] = $room_stats['total_rooms'];
  $stats['available_rooms'] = $room_stats['available_rooms'];
  $stats['booked_rooms'] = $room_stats['booked_rooms'];
}

// Fetch booking stats (using total_price)
$booking_stats_query = "SELECT 
    COUNT(*) as total_bookings,
    SUM(total_price) as estimated_revenue
FROM hotel_bookings";
$booking_stats_result = $conn->query($booking_stats_query);
if ($booking_stats_result && $booking_stats_result->num_rows > 0) {
  $booking_stats = $booking_stats_result->fetch_assoc();
  $stats['total_bookings'] = $booking_stats['total_bookings'];
  $stats['estimated_revenue'] = $booking_stats['estimated_revenue'] ?? 0;
}
?>

<!-- Stats Section -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
  <!-- Total Hotels -->
  <div class="bg-white rounded-lg shadow p-4 flex items-center">
    <div class="bg-blue-100 rounded-lg p-3 mr-4">
      <i class="fas fa-hotel text-blue-600 text-xl"></i>
    </div>
    <div>
      <h3 class="text-gray-500 text-sm font-medium">Total Hotels</h3>
      <p class="text-xl font-bold text-gray-800"><?php echo $stats['total_hotels']; ?></p>
    </div>
  </div>

  <!-- Total Rooms -->
  <div class="bg-white rounded-lg shadow p-4 flex items-center">
    <div class="bg-green-100 rounded-lg p-3 mr-4">
      <i class="fas fa-door-open text-green-600 text-xl"></i>
    </div>
    <div>
      <h3 class="text-gray-500 text-sm font-medium">Total Rooms</h3>
      <p class="text-xl font-bold text-gray-800"><?php echo $stats['total_rooms']; ?></p>
      <p class="text-xs text-gray-600">
        <span class="text-green-600"><?php echo $stats['available_rooms']; ?> Available</span> |
        <span class="text-red-600"><?php echo $stats['booked_rooms']; ?> Booked</span>
      </p>
    </div>
  </div>

  <!-- Average Price -->
  <div class="bg-white rounded-lg shadow p-4 flex items-center">
    <div class="bg-purple-100 rounded-lg p-3 mr-4">
      <i class="fas fa-dollar-sign text-purple-600 text-xl"></i>
    </div>
    <div>
      <h3 class="text-gray-500 text-sm font-medium">Average Price</h3>
      <p class="text-xl font-bold text-gray-800">
        <?php echo $stats['avg_price'] !== null ? 'PKR' . number_format(round($stats['avg_price'])) : 'N/A'; ?>
      </p>
      <p class="text-xs text-gray-600">
        Min: <?php echo $stats['min_price'] !== null ? 'PKR' . number_format($stats['min_price']) : 'N/A'; ?> |
        Max: <?php echo $stats['max_price'] !== null ? 'PKR' . number_format($stats['max_price']) : 'N/A'; ?>
      </p>
    </div>
  </div>

  <!-- 5-Star Hotels -->
  <div class="bg-white rounded-lg shadow p-4 flex items-center">
    <div class="bg-yellow-100 rounded-lg p-3 mr-4">
      <i class="fas fa-star text-yellow-600 text-xl"></i>
    </div>
    <div>
      <h3 class="text-gray-500 text-sm font-medium">5-Star Hotels</h3>
      <p class="text-xl font-bold text-gray-800"><?php echo $stats['rating_5']; ?></p>
    </div>
  </div>

  <!-- Makkah Hotels -->
  <div class="bg-white rounded-lg shadow p-4 flex items-center">
    <div class="bg-blue-100 rounded-lg p-3 mr-4">
      <i class="fas fa-mosque text-blue-600 text-xl"></i>
    </div>
    <div>
      <h3 class="text-gray-500 text-sm font-medium">Makkah Hotels</h3>
      <p class="text-xl font-bold text-gray-800"><?php echo $stats['makkah_hotels']; ?></p>
    </div>
  </div>

  <!-- Madinah Hotels -->
  <div class="bg-white rounded-lg shadow p-4 flex items-center">
    <div class="bg-green-100 rounded-lg p-3 mr-4">
      <i class="fas fa-mosque text-green-600 text-xl"></i>
    </div>
    <div>
      <h3 class="text-gray-500 text-sm font-medium">Madinah Hotels</h3>
      <p class="text-xl font-bold text-gray-800"><?php echo $stats['madinah_hotels']; ?></p>
    </div>
  </div>

  <!-- Total Bookings -->
  <div class="bg-white rounded-lg shadow p-4 flex items-center">
    <div class="bg-blue-100 rounded-lg p-3 mr-4">
      <i class="fas fa-book text-blue-600 text-xl"></i>
    </div>
    <div>
      <h3 class="text-gray-500 text-sm font-medium">Total Bookings</h3>
      <p class="text-xl font-bold text-gray-800"><?php echo $stats['total_bookings']; ?></p>
    </div>
  </div>

  <!-- Estimated Revenue -->
  <div class="bg-white rounded-lg shadow p-4 flex items-center">
    <div class="bg-green-100 rounded-lg p-3 mr-4">
      <i class="fas fa-dollar-sign text-green-600 text-xl"></i>
    </div>
    <div>
      <h3 class="text-gray-500 text-sm font-medium">Est. Revenue</h3>
      <p class="text-xl font-bold text-gray-800">PKR<?php echo number_format($stats['estimated_revenue'] ?? 0); ?></p>
    </div>
  </div>
</div>

<!-- Detailed Stats -->
<div class="bg-white rounded-lg shadow p-4 mb-6">
  <h2 class="text-lg font-bold text-gray-800 mb-4">Detailed Hotel Statistics</h2>

  <!-- Rating Distribution -->
  <div class="mb-6">
    <h3 class="text-sm font-medium text-gray-700 mb-2">Rating Distribution</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-2">
      <?php for ($i = 5; $i >= 1; $i--): ?>
        <?php
        $rating_count = $stats["rating_$i"];
        $rating_percent = $stats['total_hotels'] > 0 ? ($rating_count / $stats['total_hotels']) * 100 : 0;
        ?>
        <div class="bg-gray-50 rounded-lg p-3 flex items-center">
          <div class="bg-yellow-100 rounded-lg p-2 mr-3">
            <i class="fas fa-star text-yellow-600 text-sm"></i>
          </div>
          <div>
            <h4 class="text-xs font-medium text-gray-600"><?php echo $i; ?> Stars</h4>
            <p class="text-sm font-bold text-gray-800"><?php echo $rating_count; ?></p>
            <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
              <div class="bg-yellow-500 h-2 rounded-full" style="width: <?php echo $rating_percent; ?>%"></div>
            </div>
          </div>
        </div>
      <?php endfor; ?>
    </div>
  </div>

  <!-- Amenities Distribution -->
  <div class="mb-6">
    <h3 class="text-sm font-medium text-gray-700 mb-2">Amenities Distribution</h3>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
      <?php foreach ($stats['amenities'] as $amenity => $count): ?>
        <div class="bg-gray-50 rounded-lg p-3 flex items-center">
          <div class="bg-blue-100 rounded-lg p-2 mr-3">
            <?php
            $icon = match ($amenity) {
              'wifi' => 'fa-wifi',
              'parking' => 'fa-car',
              'restaurant' => 'fa-utensils',
              'gym' => 'fa-dumbbell',
              'pool' => 'fa-swimming-pool',
              'ac' => 'fa-snowflake',
              'room_service' => 'fa-concierge-bell',
              'spa' => 'fa-spa',
              default => 'fa-check'
            };
            ?>
            <i class="fas <?php echo $icon; ?> text-blue-600 text-sm"></i>
          </div>
          <div>
            <h4 class="text-xs font-medium text-gray-600 capitalize"><?php echo str_replace('_', ' ', $amenity); ?></h4>
            <p class="text-sm font-bold text-gray-800"><?php echo $count; ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Image Coverage -->
  <div class="mb-6">
    <h3 class="text-sm font-medium text-gray-700 mb-2">Image Coverage</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
      <div class="bg-gray-50 rounded-lg p-3 flex items-center">
        <div class="bg-green-100 rounded-lg p-2 mr-3">
          <i class="fas fa-image text-green-600 text-sm"></i>
        </div>
        <div>
          <h4 class="text-xs font-medium text-gray-600">With Images</h4>
          <p class="text-sm font-bold text-gray-800"><?php echo $stats['with_images']; ?></p>
          <?php
          $image_percent = $stats['total_hotels'] > 0 ? ($stats['with_images'] / $stats['total_hotels']) * 100 : 0;
          ?>
          <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
            <div class="bg-green-600 h-2 rounded-full" style="width: <?php echo $image_percent; ?>%"></div>
          </div>
        </div>
      </div>
      <div class="bg-gray-50 rounded-lg p-3 flex items-center">
        <div class="bg-red-100 rounded-lg p-2 mr-3">
          <i class="fas fa-times text-red-600 text-sm"></i>
        </div>
        <div>
          <h4 class="text-xs font-medium text-gray-600">Without Images</h4>
          <p class="text-sm font-bold text-gray-800"><?php echo $stats['without_images']; ?></p>
          <?php
          $no_image_percent = $stats['total_hotels'] > 0 ? ($stats['without_images'] / $stats['total_hotels']) * 100 : 0;
          ?>
          <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
            <div class="bg-red-600 h-2 rounded-full" style="width: <?php echo $no_image_percent; ?>%"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>