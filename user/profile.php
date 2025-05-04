<?php
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user data
$user_query = $conn->prepare("SELECT full_name, email, phone, dob, profile_image FROM users WHERE id = ?");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user = $user_query->get_result()->fetch_assoc();
$user_query->close();

// Handle profile update
if (isset($_POST['update_profile'])) {
  $full_name = filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_SPECIAL_CHARS);
  $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
  $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_SPECIAL_CHARS);
  $dob = filter_input(INPUT_POST, 'dob', FILTER_SANITIZE_SPECIAL_CHARS);

  $profile_image = $user['profile_image'];
  if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
    $upload_dir = '../assets/uploads/profile_images/';
    if (!is_dir($upload_dir)) {
      mkdir($upload_dir, 0755, true);
    }
    $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
    $filename = 'profile_' . time() . '.' . $ext;
    $target = $upload_dir . $filename;
    if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target)) {
      $profile_image = $target;
    }
  }

  $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, dob = ?, profile_image = ? WHERE id = ?");
  $stmt->bind_param("sssssi", $full_name, $email, $phone, $dob, $profile_image, $user_id);

  if ($stmt->execute()) {
    $_SESSION['profile_message'] = "Profile updated successfully!";
    $_SESSION['profile_message_type'] = "success";
  } else {
    $_SESSION['profile_message'] = "Error updating profile. Please try again.";
    $_SESSION['profile_message_type'] = "error";
  }
  $stmt->close();

  header("Location: profile.php");
  exit();
}

// Handle password change
if (isset($_POST['change_password'])) {
  $current_password = $_POST['current_password'];
  $new_password = $_POST['new_password'];
  $confirm_password = $_POST['confirm_password'];

  if ($new_password !== $confirm_password) {
    $_SESSION['profile_message'] = "New passwords do not match!";
    $_SESSION['profile_message_type'] = "error";
  } else {
    // Verify current password
    $check_query = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $check_query->bind_param("i", $user_id);
    $check_query->execute();
    $result = $check_query->get_result();
    $current_hash = $result->fetch_assoc()['password'];
    $check_query->close();

    if (password_verify($current_password, $current_hash)) {
      $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
      $update_query = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
      $update_query->bind_param("si", $new_hash, $user_id);

      if ($update_query->execute()) {
        $_SESSION['profile_message'] = "Password changed successfully!";
        $_SESSION['profile_message_type'] = "success";
      } else {
        $_SESSION['profile_message'] = "Error changing password. Please try again.";
        $_SESSION['profile_message_type'] = "error";
      }
      $update_query->close();
    } else {
      $_SESSION['profile_message'] = "Current password is incorrect!";
      $_SESSION['profile_message_type'] = "error";
    }
  }

  header("Location: profile.php");
  exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Profile - UmrahFlights</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <style>
    .profile-image-container {
      position: relative;
      width: 150px;
      height: 150px;
      margin: 0 auto;
    }

    .profile-image {
      width: 150px;
      height: 150px;
      object-fit: cover;
      border-radius: 50%;
      border: 4px solid #0891b2;
    }

    .profile-image-overlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      border-radius: 50%;
      background: rgba(0, 0, 0, 0.6);
      display: flex;
      align-items: center;
      justify-content: center;
      opacity: 0;
      transition: opacity 0.3s;
      cursor: pointer;
    }

    .profile-image-container:hover .profile-image-overlay {
      opacity: 1;
    }
  </style>
</head>

<body class="bg-gray-100">
  <?php include 'includes/sidebar.php'; ?>

  <!-- Main Content -->
  <div class="content-area">
    <!-- Top Header -->
    <div class="bg-white shadow-lg rounded-lg p-5 mb-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">My Profile</h1>
          <p class="text-gray-600">Manage your personal information and account settings</p>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Profile Information -->
      <div class="lg:col-span-2">
        <div class="bg-white shadow-lg rounded-lg p-6">
          <h2 class="text-xl font-semibold mb-6">Personal Information</h2>
          <form method="POST" enctype="multipart/form-data">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-gray-700 font-medium mb-2" for="full_name">Full Name</label>
                <input type="text" name="full_name" id="full_name"
                  value="<?php echo htmlspecialchars($user['full_name']); ?>"
                  class="form-control rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500"
                  required>
              </div>
              <div>
                <label class="block text-gray-700 font-medium mb-2" for="email">Email Address</label>
                <input type="email" name="email" id="email"
                  value="<?php echo htmlspecialchars($user['email']); ?>"
                  class="form-control rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500"
                  required>
              </div>
              <div>
                <label class="block text-gray-700 font-medium mb-2" for="phone">Phone Number</label>
                <input type="text" name="phone" id="phone"
                  value="<?php echo htmlspecialchars($user['phone']); ?>"
                  class="form-control rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500"
                  required>
              </div>
              <div>
                <label class="block text-gray-700 font-medium mb-2" for="dob">Date of Birth</label>
                <input type="date" name="dob" id="dob"
                  value="<?php echo htmlspecialchars($user['dob']); ?>"
                  class="form-control rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500"
                  required>
              </div>
            </div>
            <div class="mt-6">
              <label class="block text-gray-700 font-medium mb-2">Profile Image</label>
              <input type="file" name="profile_image" id="profile_image" accept="image/*"
                class="form-control rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500">
            </div>
            <div class="mt-6">
              <button type="submit" name="update_profile"
                class="bg-cyan-600 hover:bg-cyan-700 text-white font-semibold py-2 px-6 rounded-lg transition duration-300">
                Update Profile
              </button>
            </div>
          </form>
        </div>

        <!-- Change Password Section -->
        <div class="bg-white shadow-lg rounded-lg p-6 mt-6">
          <h2 class="text-xl font-semibold mb-6">Change Password</h2>
          <form method="POST">
            <div class="grid grid-cols-1 gap-6">
              <div>
                <label class="block text-gray-700 font-medium mb-2" for="current_password">Current Password</label>
                <input type="password" name="current_password" id="current_password"
                  class="form-control rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500"
                  required>
              </div>
              <div>
                <label class="block text-gray-700 font-medium mb-2" for="new_password">New Password</label>
                <input type="password" name="new_password" id="new_password"
                  class="form-control rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500"
                  required>
              </div>
              <div>
                <label class="block text-gray-700 font-medium mb-2" for="confirm_password">Confirm New Password</label>
                <input type="password" name="confirm_password" id="confirm_password"
                  class="form-control rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500"
                  required>
              </div>
            </div>
            <div class="mt-6">
              <button type="submit" name="change_password"
                class="bg-cyan-600 hover:bg-cyan-700 text-white font-semibold py-2 px-6 rounded-lg transition duration-300">
                Change Password
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Profile Sidebar -->
      <div class="lg:col-span-1">
        <div class="bg-white shadow-lg rounded-lg p-6">
          <div class="text-center">
            <div class="profile-image-container mb-4">
              <?php if ($user['profile_image']): ?>
                <img src="../<?php echo htmlspecialchars($user['profile_image']); ?>"
                  alt="Profile Image"
                  class="profile-image">
              <?php else: ?>
                <div class="profile-image bg-gray-200 flex items-center justify-center">
                  <i class="fas fa-user text-4xl text-gray-400"></i>
                </div>
              <?php endif; ?>
              <div class="profile-image-overlay" onclick="document.getElementById('profile_image').click()">
                <i class="fas fa-camera text-white text-2xl"></i>
              </div>
            </div>
            <h3 class="text-xl font-semibold text-gray-800"><?php echo htmlspecialchars($user['full_name']); ?></h3>
            <p class="text-gray-600"><?php echo htmlspecialchars($user['email']); ?></p>
          </div>

          <hr class="my-6">

          <div class="space-y-4">
            <div class="flex items-center text-gray-600">
              <i class="fas fa-phone-alt w-6"></i>
              <span><?php echo htmlspecialchars($user['phone']); ?></span>
            </div>
            <div class="flex items-center text-gray-600">
              <i class="fas fa-birthday-cake w-6"></i>
              <span><?php echo $user['dob'] ? date('d M Y', strtotime($user['dob'])) : 'Not set'; ?></span>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white shadow-lg rounded-lg p-6 mt-6">
          <h3 class="text-lg font-semibold mb-4">Quick Actions</h3>
          <div class="space-y-3">
            <a href="booking-history.php" class="block text-center bg-cyan-600 text-white py-2 rounded-lg hover:bg-cyan-700 transition">
              View Booking History
            </a>

          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Show success/error messages
    <?php if (isset($_SESSION['profile_message'])): ?>
      Swal.fire({
        icon: '<?php echo $_SESSION['profile_message_type']; ?>',
        title: '<?php echo $_SESSION['profile_message_type'] == 'success' ? 'Success!' : 'Error!'; ?>',
        text: '<?php echo $_SESSION['profile_message']; ?>',
        confirmButtonColor: '#06b6d4'
      });
      <?php
      unset($_SESSION['profile_message']);
      unset($_SESSION['profile_message_type']);
      ?>
    <?php endif; ?>

    // Image preview functionality
    document.getElementById('profile_image').addEventListener('change', function(e) {
      if (e.target.files && e.target.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
          const profileImg = document.querySelector('.profile-image');
          if (profileImg.tagName === 'IMG') {
            profileImg.src = e.target.result;
          } else {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.className = 'profile-image';
            img.alt = 'Profile Image';
            profileImg.parentNode.replaceChild(img, profileImg);
          }
        }
        reader.readAsDataURL(e.target.files[0]);
      }
    });
  </script>
</body>

</html>