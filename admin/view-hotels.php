<?php
require_once '../config/db.php';

// Start admin session
session_name('admin_session');
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
  header('Location: login.php');
  exit;
}

// Initialize variables
$hotels = [];
$total_hotels = 0;
$total_rooms = 0;
$message = '';
$message_type = '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';
$location = isset($_GET['location']) ? $_GET['location'] : '';
$rating = isset($_GET['rating']) ? $_GET['rating'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Handle hotel deletion if requested
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
  $hotel_id = mysqli_real_escape_string($conn, $_GET['delete']);

  // Start transaction
  $conn->begin_transaction();

  try {
    // Check if hotel exists
    $check_query = "SELECT id FROM hotels WHERE id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $hotel_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows === 0) {
      throw new Exception("Hotel not found.");
    }

    // Get image paths to delete files
    $images_query = "SELECT image_path FROM hotel_images WHERE hotel_id = ?";
    $images_stmt = $conn->prepare($images_query);
    $images_stmt->bind_param("i", $hotel_id);
    $images_stmt->execute();
    $images_result = $images_stmt->get_result();

    $image_paths = [];
    while ($image = $images_result->fetch_assoc()) {
      $image_paths[] = '../' . $image['image_path'];
    }

    // Delete hotel (cascades to rooms and images due to foreign key)
    $delete_query = "DELETE FROM hotels WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param("i", $hotel_id);

    if (!$delete_stmt->execute()) {
      throw new Exception("Error deleting hotel: " . $delete_stmt->error);
    }

    // Commit transaction
    $conn->commit();

    // Delete image files
    foreach ($image_paths as $path) {
      if (file_exists($path)) {
        unlink($path);
      }
    }

    // Try to remove hotel directory
    $hotel_dir = '../uploads/hotels/' . $hotel_id;
    if (is_dir($hotel_dir)) {
      @rmdir($hotel_dir);
    }

    $message = "Hotel deleted successfully!";
    $message_type = "success";
  } catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    $message = "Error: " . $e->getMessage();
    $message_type = "error";
  }
}

// Prepare base query to fetch hotels
$base_query = "SELECT h.*, 
               (SELECT COUNT(*) FROM hotel_rooms WHERE hotel_id = h.id) as room_count,
               (SELECT COUNT(*) FROM hotel_rooms WHERE hotel_id = h.id AND status = 'available') as available_rooms,
               (SELECT COUNT(*) FROM hotel_rooms WHERE hotel_id = h.id AND status = 'booked') as booked_rooms,
               (SELECT image_path FROM hotel_images WHERE hotel_id = h.id AND is_primary = 1 LIMIT 1) as primary_image,
               (SELECT COUNT(*) FROM hotel_images WHERE hotel_id = h.id) as image_count
               FROM hotels h";

// Build WHERE clause based on filters
$where_conditions = [];
$params = [];
$types = "";

if (!empty($search)) {
  $where_conditions[] = "h.hotel_name LIKE ?";
  $params[] = "%$search%";
  $types .= "s";
}

if (!empty($location)) {
  $where_conditions[] = "h.location = ?";
  $params[] = $location;
  $types .= "s";
}

if (!empty($rating)) {
  $where_conditions[] = "h.rating = ?";
  $params[] = $rating;
  $types .= "i";
}

// Apply specific filters
if ($filter === 'high-rated') {
  $where_conditions[] = "h.rating >= 4";
}

if ($filter === 'low-price') {
  $where_conditions[] = "h.price <= 150";
}

if ($filter === 'makkah') {
  $where_conditions[] = "h.location = 'makkah'";
}

if ($filter === 'madinah') {
  $where_conditions[] = "h.location = 'madinah'";
}

// Build final WHERE clause
$where_clause = "";
if (!empty($where_conditions)) {
  $where_clause = " WHERE " . implode(" AND ", $where_conditions);
}

// Build ORDER BY clause based on sort parameter
$order_clause = " ORDER BY ";
switch ($sort) {
  case 'name_asc':
    $order_clause .= "h.hotel_name ASC";
    break;
  case 'name_desc':
    $order_clause .= "h.hotel_name DESC";
    break;
  case 'price_low':
    $order_clause .= "h.price ASC";
    break;
  case 'price_high':
    $order_clause .= "h.price DESC";
    break;
  case 'rating_high':
    $order_clause .= "h.rating DESC";
    break;
  case 'oldest':
    $order_clause .= "h.created_at ASC";
    break;
  case 'newest':
  default:
    $order_clause .= "h.created_at DESC";
    break;
}

// Combine query parts
$query = $base_query . $where_clause . $order_clause;

// Prepare and execute query
$stmt = $conn->prepare($query);
if (!empty($params)) {
  $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Fetch hotels
if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    // Get additional images for each hotel
    $images_query = "SELECT image_path FROM hotel_images WHERE hotel_id = ? LIMIT 5";
    $images_stmt = $conn->prepare($images_query);
    $images_stmt->bind_param("i", $row['id']);
    $images_stmt->execute();
    $images_result = $images_stmt->get_result();

    $images = [];
    while ($image = $images_result->fetch_assoc()) {
      $images[] = $image['image_path'];
    }

    $row['images'] = $images;

    // Parse amenities
    $row['amenities_array'] = !empty($row['amenities']) ? explode(',', $row['amenities']) : [];

    $hotels[] = $row;
  }

  $total_hotels = count($hotels);

  // Calculate total rooms
  foreach ($hotels as $hotel) {
    $total_rooms += $hotel['room_count'];
  }
}

// Get stats for dashboard
$stats = [
  'makkah_hotels' => 0,
  'madinah_hotels' => 0,
  'avg_price' => 0,
  'five_star' => 0
];

$stats_query = "SELECT 
                COUNT(CASE WHEN location = 'makkah' THEN 1 END) as makkah_hotels,
                COUNT(CASE WHEN location = 'madinah' THEN 1 END) as madinah_hotels,
                AVG(price) as avg_price,
                COUNT(CASE WHEN rating = 5 THEN 1 END) as five_star
                FROM hotels";

$stats_result = $conn->query($stats_query);
if ($stats_result && $stats_result->num_rows > 0) {
  $stats = $stats_result->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>View Hotels | UmrahFlights Admin</title>
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <style>
    .hotel-card:hover {
      transform: translateY(-4px);
      transition: transform 0.3s ease;
    }

    .amenity-badge {
      font-size: 0.7rem;
    }

    .image-gallery::-webkit-scrollbar {
      height: 4px;
    }

    .image-gallery::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 10px;
    }

    .image-gallery::-webkit-scrollbar-thumb {
      background: #888;
      border-radius: 10px;
    }

    .image-gallery::-webkit-scrollbar-thumb:hover {
      background: #555;
    }
  </style>
</head>

<body class="bg-gray-100 min-h-screen">
  <!-- Sidebar -->
  <?php include 'includes/sidebar.php'; ?>

  <!-- Main Content -->
  <div class="ml-64 p-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-800 flex items-center">
          <i class="fas fa-hotel text-blue-600 mr-2"></i> Hotel Management
        </h1>
        <p class="text-gray-600">View and manage all hotel listings</p>
      </div>
      <div class="mt-4 md:mt-0">
        <a href="add-hotel.php" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
          <i class="fas fa-plus mr-2"></i> Add New Hotel
        </a>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <div class="bg-white rounded-lg shadow p-4 flex items-start">
        <div class="bg-blue-100 rounded-lg p-3 mr-4">
          <i class="fas fa-hotel text-blue-600 text-xl"></i>
        </div>
        <div>
          <h3 class="text-gray-500 text-sm font-medium">Total Hotels</h3>
          <p class="text-2xl font-bold text-gray-800"><?php echo $total_hotels; ?></p>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-4 flex items-start">
        <div class="bg-green-100 rounded-lg p-3 mr-4">
          <i class="fas fa-door-open text-green-600 text-xl"></i>
        </div>
        <div>
          <h3 class="text-gray-500 text-sm font-medium">Total Rooms</h3>
          <p class="text-2xl font-bold text-gray-800"><?php echo $total_rooms; ?></p>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-4 flex items-start">
        <div class="bg-purple-100 rounded-lg p-3 mr-4">
          <i class="fas fa-dollar-sign text-purple-600 text-xl"></i>
        </div>
        <div>
          <h3 class="text-gray-500 text-sm font-medium">Average Price</h3>
          <p class="text-2xl font-bold text-gray-800">PKR<?php echo round($stats['avg_price']); ?></p>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-4 flex items-start">
        <div class="bg-yellow-100 rounded-lg p-3 mr-4">
          <i class="fas fa-star text-yellow-600 text-xl"></i>
        </div>
        <div>
          <h3 class="text-gray-500 text-sm font-medium">5-Star Hotels</h3>
          <p class="text-2xl font-bold text-gray-800"><?php echo $stats['five_star']; ?></p>
        </div>
      </div>
    </div>

    <!-- Location Stats -->
    <div class="bg-white rounded-lg shadow mb-6 p-4">
      <h2 class="text-lg font-bold text-gray-800 mb-3">Hotels by Location</h2>
      <div class="flex items-center">
        <div class="w-full bg-gray-200 rounded-full h-4">
          <?php
          $total_location = $stats['makkah_hotels'] + $stats['madinah_hotels'];
          $makkah_percent = $total_location > 0 ? ($stats['makkah_hotels'] / $total_location) * 100 : 0;
          ?>
          <div class="bg-blue-600 h-4 rounded-full" style="width: <?php echo $makkah_percent; ?>%"></div>
        </div>
        <div class="ml-4 text-sm">
          <span class="text-blue-600 font-bold"><?php echo $stats['makkah_hotels']; ?></span> Makkah
          <span class="mx-2">|</span>
          <span class="text-green-600 font-bold"><?php echo $stats['madinah_hotels']; ?></span> Madinah
        </div>
      </div>
    </div>

    <!-- Alert Messages -->
    <?php if (!empty($message)): ?>
      <div class="mb-6">
        <div class="rounded-lg p-4 <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?> flex items-center">
          <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-3"></i>
          <?php echo $message; ?>
        </div>
      </div>
    <?php endif; ?>

    <!-- Filter and Search Bar -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
      <form action="" method="GET" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
            <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>"
              placeholder="Search hotels..."
              class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
          </div>

          <div>
            <label for="location" class="block text-sm font-medium text-gray-700 mb-1">Location</label>
            <select id="location" name="location" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
              <option value="">All Locations</option>
              <option value="makkah" <?php echo $location === 'makkah' ? 'selected' : ''; ?>>Makkah</option>
              <option value="madinah" <?php echo $location === 'madinah' ? 'selected' : ''; ?>>Madinah</option>
            </select>
          </div>

          <div>
            <label for="rating" class="block text-sm font-medium text-gray-700 mb-1">Rating</label>
            <select id="rating" name="rating" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
              <option value="">All Ratings</option>
              <option value="5" <?php echo $rating === '5' ? 'selected' : ''; ?>>5 Stars</option>
              <option value="4" <?php echo $rating === '4' ? 'selected' : ''; ?>>4 Stars</option>
              <option value="3" <?php echo $rating === '3' ? 'selected' : ''; ?>>3 Stars</option>
              <option value="2" <?php echo $rating === '2' ? 'selected' : ''; ?>>2 Stars</option>
              <option value="1" <?php echo $rating === '1' ? 'selected' : ''; ?>>1 Star</option>
            </select>
          </div>

          <div>
            <label for="sort" class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
            <select id="sort" name="sort" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
              <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
              <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
              <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Name (A-Z)</option>
              <option value="name_desc" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>Name (Z-A)</option>
              <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price (Low to High)</option>
              <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price (High to Low)</option>
              <option value="rating_high" <?php echo $sort === 'rating_high' ? 'selected' : ''; ?>>Highest Rating</option>
            </select>
          </div>
        </div>

        <div class="flex flex-wrap gap-2">
          <a href="view-hotels.php" class="inline-flex items-center px-3 py-1 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition-colors">
            <i class="fas fa-sync-alt mr-1"></i> Reset
          </a>
          <a href="view-hotels.php?filter=high-rated" class="inline-flex items-center px-3 py-1 <?php echo $filter === 'high-rated' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-800'; ?> rounded-lg hover:bg-blue-600 hover:text-white transition-colors">
            <i class="fas fa-star mr-1"></i> High Rated
          </a>
          <a href="view-hotels.php?filter=low-price" class="inline-flex items-center px-3 py-1 <?php echo $filter === 'low-price' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-800'; ?> rounded-lg hover:bg-blue-600 hover:text-white transition-colors">
            <i class="fas fa-tags mr-1"></i> Budget
          </a>
          <a href="view-hotels.php?filter=makkah" class="inline-flex items-center px-3 py-1 <?php echo $filter === 'makkah' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-800'; ?> rounded-lg hover:bg-blue-600 hover:text-white transition-colors">
            <i class="fas fa-mosque mr-1"></i> Makkah
          </a>
          <a href="view-hotels.php?filter=madinah" class="inline-flex items-center px-3 py-1 <?php echo $filter === 'madinah' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-800'; ?> rounded-lg hover:bg-blue-600 hover:text-white transition-colors">
            <i class="fas fa-mosque mr-1"></i> Madinah
          </a>
          <button type="submit" class="ml-auto px-4 py-1 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
            <i class="fas fa-search mr-1"></i> Apply Filters
          </button>
        </div>
      </form>
    </div>

    <!-- Hotels Grid -->
    <?php if (empty($hotels)): ?>
      <div class="bg-white rounded-lg shadow p-8 text-center">
        <div class="w-20 h-20 mx-auto mb-4 flex items-center justify-center rounded-full bg-gray-100">
          <i class="fas fa-hotel text-4xl text-gray-400"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-800 mb-2">No Hotels Found</h3>
        <p class="text-gray-600 mb-4">There are no hotels matching your search criteria.</p>
        <a href="add-hotel.php" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
          <i class="fas fa-plus mr-2"></i> Add New Hotel
        </a>
      </div>
    <?php else: ?>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($hotels as $hotel): ?>
          <div class="hotel-card bg-white rounded-lg shadow overflow-hidden flex flex-col">
            <!-- Main Image -->
            <div class="relative">
              <?php if (!empty($hotel['primary_image'])): ?>
                <img src="../<?php echo htmlspecialchars($hotel['primary_image']); ?>" alt="<?php echo htmlspecialchars($hotel['hotel_name']); ?>" class="w-full h-48 object-cover">
              <?php else: ?>
                <div class="w-full h-48 bg-gray-200 flex items-center justify-center">
                  <i class="fas fa-hotel text-3xl text-gray-400"></i>
                </div>
              <?php endif; ?>

              <!-- Location Badge -->
              <div class="absolute top-2 left-2">
                <span class="px-2 py-1 bg-blue-600 text-white text-xs rounded-lg capitalize">
                  <?php echo htmlspecialchars($hotel['location']); ?>
                </span>
              </div>

              <!-- Rating Badge -->
              <div class="absolute top-2 right-2">
                <span class="px-2 py-1 bg-yellow-500 text-white text-xs rounded-lg flex items-center">
                  <?php for ($i = 0; $i < $hotel['rating']; $i++): ?>
                    <i class="fas fa-star text-xs mr-0.5"></i>
                  <?php endfor; ?>
                </span>
              </div>
            </div>

            <!-- Hotel Info -->
            <div class="p-4 flex-grow">
              <div class="flex justify-between items-start mb-2">
                <h3 class="text-lg font-bold text-gray-800 truncate"><?php echo htmlspecialchars($hotel['hotel_name']); ?></h3>
                <span class="text-lg font-bold text-blue-600">PKR<?php echo number_format($hotel['price']); ?></span>
              </div>

              <!-- Room Status -->
              <div class="flex items-center text-sm text-gray-600 mb-2">
                <i class="fas fa-door-open mr-1"></i>
                <span><?php echo $hotel['room_count']; ?> Rooms (</span>
                <span class="text-green-600 mx-1"><?php echo $hotel['available_rooms']; ?> Available</span>
                <span>|</span>
                <span class="text-red-600 mx-1"><?php echo $hotel['booked_rooms']; ?> Booked</span>
                <span>)</span>
              </div>

              <!-- Description -->
              <div class="mb-3">
                <p class="text-sm text-gray-600 line-clamp-2"><?php echo htmlspecialchars(substr($hotel['description'], 0, 120)) . '...'; ?></p>
              </div>

              <!-- Image Gallery Thumbnails -->
              <?php if (!empty($hotel['images'])): ?>
                <div class="image-gallery flex space-x-2 overflow-x-auto pb-2 mb-3">
                  <?php foreach ($hotel['images'] as $image): ?>
                    <img src="../<?php echo htmlspecialchars($image); ?>" class="h-10 w-16 object-cover rounded-md flex-shrink-0" alt="Hotel Image">
                  <?php endforeach; ?>
                  <?php if (count($hotel['images']) < $hotel['image_count']): ?>
                    <div class="h-10 w-16 bg-gray-100 rounded-md flex items-center justify-center text-xs text-gray-500">
                      +<?php echo $hotel['image_count'] - count($hotel['images']); ?> more
                    </div>
                  <?php endif; ?>
                </div>
              <?php endif; ?>

              <!-- Amenities -->
              <div class="mb-3">
                <div class="flex flex-wrap gap-1">
                  <?php foreach ($hotel['amenities_array'] as $amenity): ?>
                    <span class="amenity-badge bg-blue-100 text-blue-800 px-1.5 py-0.5 rounded">
                      <?php
                      $icon = '';
                      switch ($amenity) {
                        case 'wifi':
                          $icon = 'fa-wifi';
                          break;
                        case 'parking':
                          $icon = 'fa-car';
                          break;
                        case 'restaurant':
                          $icon = 'fa-utensils';
                          break;
                        case 'gym':
                          $icon = 'fa-dumbbell';
                          break;
                        case 'pool':
                          $icon = 'fa-swimming-pool';
                          break;
                        case 'ac':
                          $icon = 'fa-snowflake';
                          break;
                        case 'room_service':
                          $icon = 'fa-concierge-bell';
                          break;
                        case 'spa':
                          $icon = 'fa-spa';
                          break;
                        default:
                          $icon = 'fa-check';
                          break;
                      }
                      ?>
                      <i class="fas <?php echo $icon; ?> mr-0.5"></i>
                      <?php echo ucfirst(str_replace('_', ' ', $amenity)); ?>
                    </span>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="p-4 pt-0 flex items-center space-x-2 border-t border-gray-100">
              <a href="edit-hotel.php?id=<?php echo $hotel['id']; ?>" class="flex-1 text-center py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">
                <i class="fas fa-edit mr-1"></i> Edit
              </a>
              <button onclick="confirmDelete(<?php echo $hotel['id']; ?>, '<?php echo htmlspecialchars($hotel['hotel_name']); ?>')" class="flex-1 text-center py-2 bg-red-100 text-red-600 rounded hover:bg-red-200 transition-colors">
                <i class="fas fa-trash-alt mr-1"></i> Delete
              </button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Delete Confirmation Modal -->
  <div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
      <h2 class="text-xl font-bold text-gray-800 mb-4">Confirm Deletion</h2>
      <p class="text-gray-600 mb-6">Are you sure you want to delete <span id="hotelName" class="font-semibold"></span>? This action cannot be undone and will remove all associated data.</p>
      <div class="flex justify-end space-x-3">
        <button id="cancelDelete" class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 transition-colors">
          Cancel
        </button>
        <a id="confirmDelete" href="#" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition-colors">
          Yes, Delete
        </a>
      </div>
    </div>
  </div>

  <script>
    // Delete confirmation modal
    function confirmDelete(id, name) {
      const modal = document.getElementById('deleteModal');
      const hotelNameSpan = document.getElementById('hotelName');
      const confirmBtn = document.getElementById('confirmDelete');

      hotelNameSpan.textContent = name;
      confirmBtn.href = 'view-hotels.php?delete=' + id;

      modal.classList.remove('hidden');
    }

    // Cancel delete
    document.getElementById('cancelDelete').addEventListener('click', function() {
      document.getElementById('deleteModal').classList.add('hidden');
    });
    // Close modal when clicking outside
    document.getElementById('deleteModal').addEventListener('click', function(e) {
      if (e.target === this) {
        this.classList.add('hidden');
      }
    });

    // Ensure the filter form submits with all filters
    document.querySelectorAll('select').forEach(select => {
      select.addEventListener('change', function() {
        if (this.id === 'location' || this.id === 'rating' || this.id === 'sort') {
          // Don't auto-submit on these changes
          return;
        }
        document.querySelector('form').submit();
      });
    });
  </script>
</body>

</html>