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
                    $type = $_POST['type'];
                    $label = trim($_POST['label']);
                    $value = trim($_POST['value']);
                    $is_primary = isset($_POST['is_primary']) ? 1 : 0;
                    $status = $_POST['status'] ?? 'active';
                    
                    // If marking as primary, unset other primaries of the same type
                    if ($is_primary) {
                        $stmt = $conn->prepare("UPDATE contact_info SET is_primary = 0 WHERE type = ?");
                        $stmt->bind_param("s", $type);
                        $stmt->execute();
                        $stmt->close();
                    }
                    
                    if (!empty($label) && !empty($value)) {
                        $stmt = $conn->prepare("INSERT INTO contact_info (type, label, value, is_primary, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                        $stmt->bind_param("sssis", $type, $label, $value, $is_primary, $status);
                        if ($stmt->execute()) {
                            $success_message = "Contact information added successfully!";
                        } else {
                            $error_message = "Error adding contact information: " . $conn->error;
                        }
                        $stmt->close();
                    } else {
                        $error_message = "Label and value are required!";
                    }
                    break;
                    
                case 'edit':
                    $id = intval($_POST['id']);
                    $type = $_POST['type'];
                    $label = trim($_POST['label']);
                    $value = trim($_POST['value']);
                    $is_primary = isset($_POST['is_primary']) ? 1 : 0;
                    $status = $_POST['status'] ?? 'active';
                    
                    // If marking as primary, unset other primaries of the same type
                    if ($is_primary) {
                        $stmt = $conn->prepare("UPDATE contact_info SET is_primary = 0 WHERE type = ? AND id != ?");
                        $stmt->bind_param("si", $type, $id);
                        $stmt->execute();
                        $stmt->close();
                    }
                    
                    if (!empty($label) && !empty($value) && $id > 0) {
                        $stmt = $conn->prepare("UPDATE contact_info SET type = ?, label = ?, value = ?, is_primary = ?, status = ?, updated_at = NOW() WHERE id = ?");
                        $stmt->bind_param("sssisi", $type, $label, $value, $is_primary, $status, $id);
                        if ($stmt->execute()) {
                            $success_message = "Contact information updated successfully!";
                        } else {
                            $error_message = "Error updating contact information: " . $conn->error;
                        }
                        $stmt->close();
                    } else {
                        $error_message = "Invalid data for update!";
                    }
                    break;
                    
                case 'delete':
                    $id = intval($_POST['id']);
                    if ($id > 0) {
                        $stmt = $conn->prepare("DELETE FROM contact_info WHERE id = ?");
                        $stmt->bind_param("i", $id);
                        if ($stmt->execute()) {
                            $success_message = "Contact information deleted successfully!";
                        } else {
                            $error_message = "Error deleting contact information: " . $conn->error;
                        }
                        $stmt->close();
                    } else {
                        $error_message = "Invalid contact ID!";
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

// Fetch all contact information
$contact_query = "SELECT * FROM contact_info ORDER BY type, is_primary DESC, created_at DESC";
$contact_result = $conn->query($contact_query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Contact Information | UmrahFlights Admin</title>
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
          <h4 class="text-lg font-semibold text-gray-800">Manage Contact Information</h4>
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

    <!-- Add/Edit Contact Form -->
    <div class="bg-white shadow-lg rounded-lg p-6 mb-6">
      <h5 class="text-lg font-semibold text-gray-800 mb-4" id="formTitle">Add New Contact Information</h5>
      <form method="POST" action="" id="contactForm">
        <input type="hidden" name="form_token" value="<?php echo $_SESSION['form_token']; ?>">
        <input type="hidden" name="action" id="formAction" value="add">
        <input type="hidden" name="id" id="contactId">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
          <div>
            <label for="type" class="block text-sm font-medium text-gray-700 mb-2">Type *</label>
            <select name="type" id="type" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
              <option value="phone">Phone</option>
              <option value="email">Email</option>
              <option value="address">Address</option>
              <option value="social">Social Media</option>
              <option value="whatsapp">WhatsApp</option>
            </select>
          </div>
          
          <div>
            <label for="label" class="block text-sm font-medium text-gray-700 mb-2">Label *</label>
            <input type="text" name="label" id="label" required 
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                   placeholder="e.g., Support Email, Office Phone">
          </div>
        </div>
        
        <div class="mb-4">
          <label for="value" class="block text-sm font-medium text-gray-700 mb-2">Value *</label>
          <input type="text" name="value" id="value" required
                 class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                 placeholder="e.g., support@umrahpartner.com, +1234567890">
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
          <div>
            <div class="flex items-center">
              <input type="checkbox" name="is_primary" id="is_primary" 
                     class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
              <label for="is_primary" class="ml-2 block text-sm text-gray-700">
                Set as Primary
              </label>
            </div>
            <p class="text-xs text-gray-500 mt-1">Primary contact will be displayed prominently</p>
          </div>
          
          <div>
            <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
            <select name="status" id="status"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
        </div>
        
        <div class="flex justify-end space-x-3">
          <button type="button" id="cancelEdit" 
                  class="hidden px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500">
            Cancel
          </button>
          <button type="submit" 
                  class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <span id="submitBtnText">Add Contact</span>
          </button>
        </div>
      </form>
    </div>

    <!-- Contact Information List -->
    <div class="bg-white shadow-lg rounded-lg p-6">
      <h5 class="text-lg font-semibold text-gray-800 mb-4">Contact Information List</h5>
      <div class="overflow-x-auto">
        <table class="w-full text-left">
          <thead>
            <tr class="border-b">
              <th class="py-3 px-4 text-sm font-semibold text-gray-600">Type</th>
              <th class="py-3 px-4 text-sm font-semibold text-gray-600">Label</th>
              <th class="py-3 px-4 text-sm font-semibold text-gray-600">Value</th>
              <th class="py-3 px-4 text-sm font-semibold text-gray-600">Primary</th>
              <th class="py-3 px-4 text-sm font-semibold text-gray-600">Status</th>
              <th class="py-3 px-4 text-sm font-semibold text-gray-600">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($contact_result && $contact_result->num_rows > 0): ?>
              <?php while($contact = $contact_result->fetch_assoc()): ?>
              <tr class="border-b hover:bg-gray-50">
                <td class="py-3 px-4 text-sm text-gray-700 capitalize"><?php echo htmlspecialchars($contact['type']); ?></td>
                <td class="py-3 px-4 text-sm text-gray-700"><?php echo htmlspecialchars($contact['label']); ?></td>
                <td class="py-3 px-4 text-sm text-gray-700"><?php echo htmlspecialchars($contact['value']); ?></td>
                <td class="py-3 px-4 text-sm">
                  <?php if ($contact['is_primary']): ?>
                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                      Primary
                    </span>
                  <?php endif; ?>
                </td>
                <td class="py-3 px-4 text-sm">
                  <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                    <?php echo $contact['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                    <?php echo ucfirst($contact['status']); ?>
                  </span>
                </td>
                <td class="py-3 px-4 text-sm">
                  <button onclick="editContact(<?php echo htmlspecialchars(json_encode($contact)); ?>)" 
                          class="text-indigo-600 hover:text-indigo-900 mr-3">
                    <i class="fas fa-edit"></i>
                  </button>
                  <form method="POST" action="" class="inline" onsubmit="return confirm('Are you sure you want to delete this contact info?');">
                    <input type="hidden" name="form_token" value="<?php echo $_SESSION['form_token']; ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?php echo $contact['id']; ?>">
                    <button type="submit" class="text-red-600 hover:text-red-900">
                      <i class="fas fa-trash"></i>
                    </button>
                  </form>
                </td>
              </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="6" class="py-3 px-4 text-sm text-gray-500 text-center">No contact information found.</td>
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

    // Edit contact function
    function editContact(contact) {
      document.getElementById('formTitle').textContent = 'Edit Contact Information';
      document.getElementById('formAction').value = 'edit';
      document.getElementById('contactId').value = contact.id;
      document.getElementById('type').value = contact.type;
      document.getElementById('label').value = contact.label;
      document.getElementById('value').value = contact.value;
      document.getElementById('is_primary').checked = contact.is_primary == 1;
      document.getElementById('status').value = contact.status;
      document.getElementById('submitBtnText').textContent = 'Update Contact';
      document.getElementById('cancelEdit').classList.remove('hidden');
      
      // Scroll to form
      document.getElementById('contactForm').scrollIntoView({ behavior: 'smooth' });
    }

    // Cancel edit function
    document.getElementById('cancelEdit').addEventListener('click', function() {
      document.getElementById('formTitle').textContent = 'Add New Contact Information';
      document.getElementById('formAction').value = 'add';
      document.getElementById('contactId').value = '';
      document.getElementById('contactForm').reset();
      document.getElementById('submitBtnText').textContent = 'Add Contact';
      this.classList.add('hidden');
    });
  </script>
</body>

</html>