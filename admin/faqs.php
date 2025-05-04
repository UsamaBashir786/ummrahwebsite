<?php
require_once '../config/db.php'; // Include db.php with $conn
// Start admin session
session_name('admin_session');
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if admin is logged in
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
  header('Location: login.php');
  exit;
}

// Verify database connection
if (!$conn) {
  die("Database connection failed: " . mysqli_connect_error());
}

// Handle form submission - Prevent duplicate submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Check if the form token matches the session token
  if (isset($_POST['form_token']) && isset($_SESSION['form_token']) && $_POST['form_token'] === $_SESSION['form_token']) {

    // Process the form submission
    if (isset($_POST['action'])) {
      switch ($_POST['action']) {
        case 'add':
          $question = trim($_POST['question']);
          $answer = trim($_POST['answer']);
          $category = trim($_POST['category']);
          $status = $_POST['status'] ?? 'active';

          if (!empty($question) && !empty($answer)) {
            $stmt = $conn->prepare("INSERT INTO faqs (question, answer, category, status, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssss", $question, $answer, $category, $status);
            if ($stmt->execute()) {
              $success_message = "FAQ added successfully!";
            } else {
              $error_message = "Error adding FAQ: " . $conn->error;
            }
            $stmt->close();
          } else {
            $error_message = "Question and answer are required!";
          }
          break;

        case 'edit':
          $id = intval($_POST['id']);
          $question = trim($_POST['question']);
          $answer = trim($_POST['answer']);
          $category = trim($_POST['category']);
          $status = $_POST['status'] ?? 'active';

          if (!empty($question) && !empty($answer) && $id > 0) {
            $stmt = $conn->prepare("UPDATE faqs SET question = ?, answer = ?, category = ?, status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("ssssi", $question, $answer, $category, $status, $id);
            if ($stmt->execute()) {
              $success_message = "FAQ updated successfully!";
            } else {
              $error_message = "Error updating FAQ: " . $conn->error;
            }
            $stmt->close();
          } else {
            $error_message = "Invalid data for update!";
          }
          break;

        case 'delete':
          $id = intval($_POST['id']);
          if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM faqs WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
              $success_message = "FAQ deleted successfully!";
            } else {
              $error_message = "Error deleting FAQ: " . $conn->error;
            }
            $stmt->close();
          } else {
            $error_message = "Invalid FAQ ID!";
          }
          break;
      }
    }

    // Clear the form token
    unset($_SESSION['form_token']);

    // Redirect to prevent form resubmission
    $_SESSION['success_message'] = $success_message ?? null;
    $_SESSION['error_message'] = $error_message ?? null;
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
  }
}

// Generate a new form token
$_SESSION['form_token'] = bin2hex(random_bytes(32));

// Check for flash messages
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Fetch all FAQs
$faqs_query = "SELECT * FROM faqs ORDER BY category, created_at DESC";
$faqs_result = $conn->query($faqs_query);

// Fetch categories for the dropdown
$categories_query = "SELECT DISTINCT category FROM faqs WHERE category IS NOT NULL AND category != '' ORDER BY category";
$categories_result = $conn->query($categories_query);
$categories = [];
while ($row = $categories_result->fetch_assoc()) {
  $categories[] = $row['category'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage FAQs | UmrahFlights Admin</title>
  <!-- Tailwind CSS CDN -->
  <link rel="stylesheet" href="../src/output.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="assets/css/index.css">
</head>

<body class="bg-gray-100">
  <?php include 'includes/sidebar.php'; ?>

  <!-- Main Content -->
  <div class="ml-0 md:ml-64 mt-10 px-4 sm:px-6 lg:px-8 transition-all duration-300">
    <!-- Top Navbar -->
    <nav class="bg-white shadow-lg rounded-lg p-5 mb-6">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <h4 class="text-lg font-semibold text-gray-800">Manage FAQs</h4>
        </div>

        <div class="flex items-center space-x-4">
          <!-- User Dropdown -->
          <div class="relative">
            <button id="userDropdownButton" class="flex items-center space-x-2 text-gray-700 hover:bg-indigo-50 rounded-lg px-3 py-2 focus:outline-none">
              <div class="rounded-full overflow-hidden" style="width: 32px; height: 32px;">
                <div class="bg-gray-200 w-full h-full"></div>
              </div>
              <span class="hidden md:inline text-sm font-medium">Admin User</span>
              <svg class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <ul id="userDropdownMenu" class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 hidden z-50">
              <li>
                <a class="flex items-center px-4 py-2 text-sm text-red-500 hover:bg-red-50" href="logout.php">
                  <i class="fas fa-sign-out-alt mr-2"></i> Logout
                </a>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </nav>

    <!-- Alerts -->
    <?php if ($success_message): ?>
      <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
        <span class="block sm:inline"><?php echo htmlspecialchars($success_message); ?></span>
      </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
      <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
        <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
      </div>
    <?php endif; ?>

    <!-- Add/Edit FAQ Form -->
    <div class="bg-white shadow-lg rounded-lg p-6 mb-6">
      <h5 class="text-lg font-semibold text-gray-800 mb-4" id="formTitle">Add New FAQ</h5>
      <form method="POST" action="" id="faqForm">
        <input type="hidden" name="form_token" value="<?php echo $_SESSION['form_token']; ?>">
        <input type="hidden" name="action" id="formAction" value="add">
        <input type="hidden" name="id" id="faqId">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
          <div>
            <label for="question" class="block text-sm font-medium text-gray-700 mb-2">Question *</label>
            <input type="text" name="question" id="question" required
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
          </div>

          <div>
            <label for="category" class="block text-sm font-medium text-gray-700 mb-2">Category</label>
            <div class="relative">
              <input type="text" name="category" id="category" list="categoryList"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                placeholder="Select or type a category">
              <datalist id="categoryList">
                <?php foreach ($categories as $cat): ?>
                  <option value="<?php echo htmlspecialchars($cat); ?>">
                  <?php endforeach; ?>
              </datalist>
            </div>
          </div>
        </div>

        <div class="mb-4">
          <label for="answer" class="block text-sm font-medium text-gray-700 mb-2">Answer *</label>
          <textarea name="answer" id="answer" required rows="4"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
        </div>

        <div class="mb-4">
          <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
          <select name="status" id="status"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>

        <div class="flex justify-end space-x-3">
          <button type="button" id="cancelEdit"
            class="hidden px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500">
            Cancel
          </button>
          <button type="submit"
            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <span id="submitBtnText">Add FAQ</span>
          </button>
        </div>
      </form>
    </div>

    <!-- FAQs List -->
    <div class="bg-white shadow-lg rounded-lg p-6">
      <h5 class="text-lg font-semibold text-gray-800 mb-4">FAQs List</h5>
      <div class="overflow-x-auto">
        <table class="w-full text-left">
          <thead>
            <tr class="border-b">
              <th class="py-3 px-4 text-sm font-semibold text-gray-600">Question</th>
              <th class="py-3 px-4 text-sm font-semibold text-gray-600">Category</th>
              <th class="py-3 px-4 text-sm font-semibold text-gray-600">Status</th>
              <th class="py-3 px-4 text-sm font-semibold text-gray-600">Created</th>
              <th class="py-3 px-4 text-sm font-semibold text-gray-600">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($faqs_result && $faqs_result->num_rows > 0): ?>
              <?php while ($faq = $faqs_result->fetch_assoc()): ?>
                <tr class="border-b hover:bg-gray-50">
                  <td class="py-3 px-4 text-sm text-gray-700">
                    <div class="font-medium"><?php echo htmlspecialchars($faq['question']); ?></div>
                    <div class="text-gray-500 text-xs mt-1"><?php echo htmlspecialchars(substr($faq['answer'], 0, 100)) . '...'; ?></div>
                  </td>
                  <td class="py-3 px-4 text-sm text-gray-700"><?php echo htmlspecialchars($faq['category'] ?? 'Uncategorized'); ?></td>
                  <td class="py-3 px-4 text-sm">
                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                    <?php echo $faq['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                      <?php echo ucfirst($faq['status']); ?>
                    </span>
                  </td>
                  <td class="py-3 px-4 text-sm text-gray-700"><?php echo date('M d, Y', strtotime($faq['created_at'])); ?></td>
                  <td class="py-3 px-4 text-sm">
                    <button onclick="editFAQ(<?php echo htmlspecialchars(json_encode($faq)); ?>)"
                      class="text-indigo-600 hover:text-indigo-900 mr-3">
                      <i class="fas fa-edit"></i>
                    </button>
                    <form method="POST" action="" class="inline" onsubmit="return confirm('Are you sure you want to delete this FAQ?');">
                      <input type="hidden" name="form_token" value="<?php echo $_SESSION['form_token']; ?>">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?php echo $faq['id']; ?>">
                      <button type="submit" class="text-red-600 hover:text-red-900">
                        <i class="fas fa-trash"></i>
                      </button>
                    </form>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="5" class="py-3 px-4 text-sm text-gray-500 text-center">No FAQs found.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script>
    // User dropdown functionality
    document.getElementById('userDropdownButton').addEventListener('click', function() {
      document.getElementById('userDropdownMenu').classList.toggle('hidden');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
      const dropdown = document.getElementById('userDropdownMenu');
      const button = document.getElementById('userDropdownButton');
      if (!button.contains(event.target)) {
        dropdown.classList.add('hidden');
      }
    });

    // Edit FAQ function
    function editFAQ(faq) {
      document.getElementById('formTitle').textContent = 'Edit FAQ';
      document.getElementById('formAction').value = 'edit';
      document.getElementById('faqId').value = faq.id;
      document.getElementById('question').value = faq.question;
      document.getElementById('answer').value = faq.answer;
      document.getElementById('category').value = faq.category || '';
      document.getElementById('status').value = faq.status;
      document.getElementById('submitBtnText').textContent = 'Update FAQ';
      document.getElementById('cancelEdit').classList.remove('hidden');

      // Scroll to form
      document.getElementById('faqForm').scrollIntoView({
        behavior: 'smooth'
      });
    }

    // Cancel edit function
    document.getElementById('cancelEdit').addEventListener('click', function() {
      document.getElementById('formTitle').textContent = 'Add New FAQ';
      document.getElementById('formAction').value = 'add';
      document.getElementById('faqId').value = '';
      document.getElementById('faqForm').reset();
      document.getElementById('submitBtnText').textContent = 'Add FAQ';
      this.classList.add('hidden');
    });
  </script>
</body>

</html>