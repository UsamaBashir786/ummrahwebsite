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

// Check if delete parameter is present but empty or invalid
if (isset($_GET['delete']) && (empty($_GET['delete']) || !is_numeric($_GET['delete']))) {
  $redirect_url = 'view-hotels.php';
  if (!empty($_GET)) {
    $params = $_GET;
    unset($params['delete']);
    if (!empty($params)) {
      $redirect_url .= '?' . http_build_query($params);
    }
  }
  header("Location: $redirect_url");
  exit;
}

// Initialize variables
$hotels = [];
$total_hotels = 0;
$total_rooms = 0;
$message = '';
$message_type = '';
$filter = isset($_GET['filter']) ? filter_var($_GET['filter'], FILTER_SANITIZE_STRING) : '';
$location = isset($_GET['location']) ? filter_var($_GET['location'], FILTER_SANITIZE_STRING) : '';
$rating = isset($_GET['rating']) ? filter_var($_GET['rating'], FILTER_VALIDATE_INT) : '';
$search = isset($_GET['search']) ? filter_var($_GET['search'], FILTER_SANITIZE_STRING) : '';
$sort = isset($_GET['sort']) ? filter_var($_GET['sort'], FILTER_SANITIZE_STRING) : 'newest';

// Handle hotel deletion if requested
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
  $hotel_id = filter_var($_GET['delete'], FILTER_VALIDATE_INT);
  $conn->begin_transaction();
  try {
    // Check if hotel exists
    $check_query = "SELECT id FROM hotels WHERE id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $hotel_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    if ($check_result->num_rows === 0) {
      $conn->rollback();
      header('Location: view-hotels.php');
      exit;
    }
    $check_stmt->close();

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
    $images_stmt->close();

    // Count bookings to be deleted for success message
    $bookings_count_query = "SELECT COUNT(*) as booking_count FROM hotel_bookings WHERE hotel_id = ?";
    $bookings_count_stmt = $conn->prepare($bookings_count_query);
    $bookings_count_stmt->bind_param("i", $hotel_id);
    $bookings_count_stmt->execute();
    $bookings_count_result = $bookings_count_stmt->get_result();
    $bookings_count = $bookings_count_result->fetch_assoc()['booking_count'];
    $bookings_count_stmt->close();

    // Delete related bookings
    $delete_bookings_query = "DELETE FROM hotel_bookings WHERE hotel_id = ?";
    $delete_bookings_stmt = $conn->prepare($delete_bookings_query);
    $delete_bookings_stmt->bind_param("i", $hotel_id);
    if (!$delete_bookings_stmt->execute()) {
      throw new Exception("Error deleting hotel bookings: " . $delete_bookings_stmt->error);
    }
    $delete_bookings_stmt->close();

    // Delete hotel (cascades to rooms and images due to foreign key)
    $delete_query = "DELETE FROM hotels WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param("i", $hotel_id);
    if (!$delete_stmt->execute()) {
      throw new Exception("Error deleting hotel: " . $delete_stmt->error);
    }
    $delete_stmt->close();

    // Commit transaction
    $conn->commit();

    // Delete image files
    foreach ($image_paths as $path) {
      if (file_exists($path)) {
        unlink($path);
      }
    }
    $hotel_dir = '../Uploads/hotels/' . $hotel_id;
    if (is_dir($hotel_dir)) {
      @rmdir($hotel_dir);
    }

    $message = "Hotel and $bookings_count booking(s) deleted successfully!";
    $message_type = "success";
  } catch (Exception $e) {
    $conn->rollback();
    error_log("Error deleting hotel: " . $e->getMessage());
    $message = "An error occurred while deleting the hotel and its bookings.";
    $message_type = "error";
  }
  header('Location: view-hotels.php');
  exit;
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

// Get stats for dashboard (optional, as hotel-stats.php handles this)
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
  <link rel="stylesheet" href="../src/output.css">
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

<body class="bg-gray-100">
  <?php include 'includes/sidebar.php'; ?>

  <!-- Main Content -->
  <div class="ml-0 md:ml-64 mt-10 px-4 sm:px-6 lg:px-8 transition-all duration-300">
    <!-- Top Navbar -->
    <nav class="bg-white shadow-lg rounded-lg p-5 mb-6">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <button id="sidebarToggle" class="text-gray-500 hover:text-gray-700 focus:outline-none md:hidden">
            
          </button>
          <h4 class="text-lg font-semibold text-gray-800">
            <i class="fas fa-hotel text-indigo-600 mr-2"></i> Hotel Management
          </h4>
        </div>

        <div>
          <a href="add-hotels.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            <i class="fas fa-plus mr-2"></i> Add New Hotel
          </a>
        </div>
      </div>
    </nav>

    <!-- Stats Section -->
    <?php include 'includes/hotel-stats.php'; ?>

    <!-- Alert Messages -->
    <?php if (!empty($message)): ?>
      <div class="mb-6">
        <div class="<?php echo $message_type === 'success' ? 'bg-green-100 border-l-4 border-green-500 text-green-700' : 'bg-red-100 border-l-4 border-red-500 text-red-700'; ?> p-4 rounded shadow" role="alert">
          <div class="flex">
            <div class="py-1">
              <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle text-green-500' : 'fa-exclamation-circle text-red-500'; ?> mr-2"></i>
            </div>
            <div><?php echo htmlspecialchars($message); ?></div>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- Filter and Search Bar -->
    <div class="bg-white shadow-lg rounded-lg p-6 mb-6">
      <form action="" method="GET" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
            <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>"
              placeholder="Search hotels..."
              class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
          </div>

          <div>
            <label for="location" class="block text-sm font-medium text-gray-700 mb-1">Location</label>
            <select id="location" name="location" class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
              <option value="">All Locations</option>
              <option value="makkah" <?php echo $location === 'makkah' ? 'selected' : ''; ?>>Makkah</option>
              <option value="madinah" <?php echo $location === 'madinah' ? 'selected' : ''; ?>>Madinah</option>
            </select>
          </div>

          <div>
            <label for="rating" class="block text-sm font-medium text-gray-700 mb-1">Rating</label>
            <select id="rating" name="rating" class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
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
            <select id="sort" name="sort" class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
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
          <a href="view-hotels.php" class="px-3 py-1 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 flex items-center">
            <i class="fas fa-sync-alt mr-1"></i> Reset
          </a>
          <a href="view-hotels.php?filter=high-rated" class="px-3 py-1 <?php echo $filter === 'high-rated' ? 'bg-indigo-600 text-white border border-transparent' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'; ?> rounded-md flex items-center">
            <i class="fas fa-star mr-1"></i> High Rated
          </a>
          <a href="view-hotels.php?filter=low-price" class="px-3 py-1 <?php echo $filter === 'low-price' ? 'bg-indigo-600 text-white border border-transparent' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'; ?> rounded-md flex items-center">
            <i class="fas fa-tags mr-1"></i> Budget
          </a>
          <a href="view-hotels.php?filter=makkah" class="px-3 py-1 <?php echo $filter === 'makkah' ? 'bg-indigo-600 text-white border border-transparent' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'; ?> rounded-md flex items-center">
            <i class="fas fa-mosque mr-1"></i> Makkah
          </a>
          <a href="view-hotels.php?filter=madinah" class="px-3 py-1 <?php echo $filter === 'madinah' ? 'bg-indigo-600 text-white border border-transparent' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'; ?> rounded-md flex items-center">
            <i class="fas fa-mosque mr-1"></i> Madinah
          </a>
          <button type="submit" class="ml-auto px-4 py-1 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            <i class="fas fa-search mr-1"></i> Apply Filters
          </button>
        </div>
      </form>
    </div>

    <!-- Hotels Grid -->
    <?php if (empty($hotels)): ?>
      <div class="bg-white shadow-lg rounded-lg p-8 text-center">
        <div class="w-20 h-20 mx-auto mb-4 flex items-center justify-center rounded-full bg-gray-100">
          <i class="fas fa-hotel text-4xl text-gray-400"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-800 mb-2">No Hotels Found</h3>
        <p class="text-gray-600 mb-4">There are no hotels matching your search criteria.</p>
        <a href="add-hotels.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
          <i class="fas fa-plus mr-2"></i> Add New Hotel
        </a>
      </div>
    <?php else: ?>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($hotels as $hotel): ?>
          <div class="hotel-card bg-white rounded-lg shadow-lg overflow-hidden flex flex-col">
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
                <span class="px-2 py-1 bg-indigo-600 text-white text-xs rounded-lg capitalize">
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
                <span class="text-lg font-bold text-indigo-600">PKR<?php echo number_format($hotel['price']); ?></span>
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
                    <span class="amenity-badge bg-indigo-100 text-indigo-800 px-1.5 py-0.5 rounded">
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
              <a href="edit-hotel.php?id=<?php echo $hotel['id']; ?>" class="flex-1 text-center py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <i class="fas fa-edit mr-1"></i> Edit
              </a>
              <button onclick="confirmDelete(<?php echo $hotel['id']; ?>, '<?php echo htmlspecialchars($hotel['hotel_name']); ?>')" class="flex-1 text-center py-2 border border-transparent text-sm font-medium rounded-md text-red-600 bg-red-100 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                <i class="fas fa-trash-alt mr-1"></i> Delete
              </button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
  <!-- Delete Confirmation Modal -->
  <div id="deleteModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <!-- Background overlay -->
      <div class="fixed inset-0 transition-opacity" aria-hidden="true">
        <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
      </div>

      <!-- Modal content -->
      <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
          <div class="sm:flex sm:items-start">
            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
              <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
            </div>
            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
              <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Delete Hotel</h3>
              <div class="mt-2">
                <p class="text-sm text-gray-500">Are you sure you want to delete <span id="hotelName" class="font-semibold"></span>? This action cannot be undone and will remove all associated data, including bookings.</p>
              </div>
            </div>
          </div>
        </div>
        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
          <a id="confirmDelete" href="#" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
            Delete
          </a>
          <button type="button" id="cancelDelete" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
            Cancel
          </button>
        </div>
      </div>
    </div>
  </div>
  <!-- Scripts -->
  <script>
    // User Dropdown Toggle
    const userDropdownButton = document.getElementById('userDropdownButton');
    const userDropdownMenu = document.getElementById('userDropdownMenu');

    if (userDropdownButton && userDropdownMenu) {
      userDropdownButton.addEventListener('click', function() {
        userDropdownMenu.classList.toggle('hidden');
      });

      // Close dropdown when clicking outside
      document.addEventListener('click', function(event) {
        if (!userDropdownButton.contains(event.target) && !userDropdownMenu.contains(event.target)) {
          userDropdownMenu.classList.add('hidden');
        }
      });
    }

    // Sidebar Toggle (assuming sidebar toggle functionality from sidebar.php)
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebar-overlay');

    if (sidebarToggle && sidebar && sidebarOverlay) {
      sidebarToggle.addEventListener('click', function() {
        sidebar.classList.remove('-translate-x-full');
        sidebarOverlay.classList.remove('hidden');
      });
    }

    // Delete confirmation modal
    // function confirmDelete(id, name) {
    //   const modal = document.getElementById('deleteModal');
    //   const hotelNameSpan = document.getElementById('hotelName');
    //   const confirmBtn = document.getElementById('confirmDelete');

    //   hotelNameSpan.textContent = name;
    //   confirmBtn.href = 'view-hotels.php?delete=' + id;

    //   modal.classList.remove('hidden');
    // }

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
  <script>
    // Delete confirmation modal
    function confirmDelete(id, name) {
      const modal = document.getElementById('deleteModal');
      const hotelNameSpan = document.getElementById('hotelName');
      const confirmBtn = document.getElementById('confirmDelete');

      hotelNameSpan.textContent = name;
      confirmBtn.href = 'view-hotels.php?delete=' + id;

      modal.classList.remove('hidden');
      document.body.classList.add('overflow-hidden');
    }

    // Cancel delete
    document.getElementById('cancelDelete').addEventListener('click', function() {
      const modal = document.getElementById('deleteModal');
      modal.classList.add('hidden');
      document.body.classList.remove('overflow-hidden');
    });

    // Close modal when clicking outside
    document.addEventListener('click', function(e) {
      const modal = document.getElementById('deleteModal');
      if (e.target === modal) {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
      }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
      const modal = document.getElementById('deleteModal');
      if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
      }
    });
  </script>
</body>

</html>