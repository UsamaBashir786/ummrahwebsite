<?php
require_once 'config/db.php';
require_once 'includes/contact-functions.php';

session_start();

// Initialize variables
$name = $email = $message = $phone = $subject = '';
$success_message = $error_message = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_contact'])) {
  $name = htmlspecialchars(trim($_POST['name'] ?? ''), ENT_QUOTES, 'UTF-8');
  $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
  $message = htmlspecialchars(trim($_POST['message'] ?? ''), ENT_QUOTES, 'UTF-8');
  $phone = htmlspecialchars(trim($_POST['phone'] ?? ''), ENT_QUOTES, 'UTF-8');
  $subject = htmlspecialchars(trim($_POST['subject'] ?? ''), ENT_QUOTES, 'UTF-8');
  $ip_address = $_SERVER['REMOTE_ADDR'];
  $created_at = date('Y-m-d H:i:s');
  $status = 'unread';

  if (empty($name)) {
    $error_message = "Please enter your name.";
  } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error_message = "Please enter a valid email address.";
  } elseif (empty($message)) {
    $error_message = "Please enter your message.";
  } else {
    $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, phone, subject, message, ip_address, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
      $stmt->bind_param("ssssssss", $name, $email, $phone, $subject, $message, $ip_address, $status, $created_at);

      if ($stmt->execute()) {
        try {
          // Send email to User
          $to = $email;
          $email_subject = 'Thank You for Contacting Umrah Partner';
          $email_message = "Thank You for Your Message, $name!\n\n";
          $email_message .= "We have received your inquiry and will get back to you within 24 hours.\n\n";
          $email_message .= "Your Submitted Details:\n";
          $email_message .= "Name: $name\n";
          $email_message .= "Email: $email\n";
          $email_message .= "Phone: $phone\n";
          $email_message .= "Subject: $subject\n";
          $email_message .= "Message: $message\n\n";
          $email_message .= "Best regards,\nUmrah Partner Team\n\n";
          $email_message .= "Email: info@umrahpartner.com\n";
          $email_message .= "Website: https://umrahpartner.com\n";
          $email_message .= "Phone: +44 775 983691\n";

          $headers = "From: Umrah Partner Team <no-reply@umrahpartner.com>\r\n";
          $headers .= "Reply-To: info@umrahpartner.com\r\n";
          $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

          if (!mail($to, $email_subject, $email_message, $headers)) {
            throw new Exception('Failed to send user email.');
          }

          // Send email to Admin
          $to = 'info@umrahpartner.com';
          $email_subject = 'New Contact Form Submission';
          $email_message = "New Contact Form Submission\n\n";
          $email_message .= "A new message has been received from the contact form.\n\n";
          $email_message .= "Details:\n";
          $email_message .= "Name: $name\n";
          $email_message .= "Email: $email\n";
          $email_message .= "Phone: $phone\n";
          $email_message .= "Subject: $subject\n";
          $email_message .= "Message: $message\n";
          $email_message .= "IP Address: $ip_address\n";
          $email_message .= "Submitted At: $created_at\n\n";
          $email_message .= "Email: info@umrahpartner.com\n";
          $email_message .= "Website: https://umrahpartner.com\n";
          $email_message .= "Phone: +44 775 983691\n";

          if (!mail($to, $email_subject, $email_message, $headers)) {
            throw new Exception('Failed to send admin email.');
          }

          $success_message = "Thank you for your message! We will get back to you soon.";
          $name = $email = $message = $phone = $subject = '';
        } catch (Exception $e) {
          $error_message = "Message sent, but email notification failed. Please contact us directly. Error: " . $e->getMessage();
          error_log("Mail Error: " . $e->getMessage());
        }
      } else {
        $error_message = "Sorry, there was an error sending your message. Please try again later.";
      }
      $stmt->close();
    } else {
      $error_message = "Database error: " . $conn->error;
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php' ?>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="src/output.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" />
  <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
  <link rel="stylesheet" href="assets/css/contact-us.css">
</head>

<body>
  <?php include 'includes/navbar.php'; ?>

  <!-- Hero Section -->
  <section class="hero-contact">
    <div class="container mx-auto px-4">
      <div class="text-center max-w-3xl mx-auto" data-aos="fade-up">
        <p class="text-primary font-semibold mb-3">REACH OUT TO US</p>
        <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6">Get in Touch With Our Team</h1>
        <p class="text-xl text-gray-600 mb-8">We're here to answer your questions and provide guidance for your sacred journey. Contact us today for personalized assistance.</p>
      </div>
    </div>
  </section>

  <!-- Contact Section -->
  <section class="py-20 bg-white">
    <div class="container mx-auto px-4">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
        <!-- Contact Form -->
        <div data-aos="fade-right" data-aos-duration="1000">
          <div class="contact-form-card">
            <h2 class="section-title">Send Us a Message</h2>
            <p class="text-gray-600 mb-8">Fill out the form below, and our team will get back to you within 24 hours.</p>

            <?php if ($success_message): ?>
              <div class="alert alert-success">
                <svg class="alert-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <p><?php echo $success_message; ?></p>
              </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
              <div class="alert alert-error">
                <svg class="alert-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                <p><?php echo $error_message; ?></p>
              </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="contact-form">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="input-group">
                  <label for="name" class="input-label">Full Name <span class="text-red-500">*</span></label>
                  <input
                    type="text"
                    id="name"
                    name="name"
                    value="<?php echo htmlspecialchars($name); ?>"
                    class="input-field"
                    placeholder="Enter your name"
                    required>
                </div>

                <div class="input-group">
                  <label for="email" class="input-label">Email Address <span class="text-red-500">*</span></label>
                  <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?php echo htmlspecialchars($email); ?>"
                    class="input-field"
                    placeholder="Enter your email"
                    required>
                </div>

                <div class="input-group">
                  <label for="phone" class="input-label">Phone Number</label>
                  <input
                    type="tel"
                    id="phone"
                    name="phone"
                    value="<?php echo htmlspecialchars($phone ?? ''); ?>"
                    class="input-field"
                    placeholder="Enter your phone number">
                </div>

                <div class="input-group">
                  <label for="subject" class="input-label">Subject</label>
                  <input
                    type="text"
                    id="subject"
                    name="subject"
                    value="<?php echo htmlspecialchars($subject ?? ''); ?>"
                    class="input-field"
                    placeholder="What is this regarding?">
                </div>
              </div>

              <div class="input-group">
                <label for="message" class="input-label">Message <span class="text-red-500">*</span></label>
                <textarea
                  id="message"
                  name="message"
                  rows="5"
                  class="input-field textarea-field"
                  placeholder="Enter your message"
                  required><?php echo htmlspecialchars($message); ?></textarea>
              </div>

              <div class="text-right">
                <button
                  type="submit"
                  name="submit_contact"
                  class="submit-button">
                  <span class="flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    Send Message
                  </span>
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Contact Info -->
        <div data-aos="fade-left" data-aos-duration="1000">
          <div class="contact-info-card mb-10">
            <h2 class="section-title">Contact Information</h2>
            <p class="text-gray-600 mb-8">Feel free to reach out to us through any of the following contact methods.</p>

            <div class="space-y-6">
              <?php
              // Note: The footer.php already provides these functions, so we don't need to declare them
              // Address information
              if (function_exists('getContactsByType')) {
                $addresses = getContactsByType($conn, 'address');
                if (!empty($addresses)):
              ?>
                  <div class="contact-info-item">
                    <div class="contact-icon">
                      <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div>
                      <h3 class="contact-info-title">Our Location</h3>
                      <div class="contact-info-content">
                        <?php foreach ($addresses as $address): ?>
                          <p><?php echo htmlspecialchars($address['value']); ?></p>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  </div>
                <?php
                endif;

                // Phone information
                $phones = getContactsByType($conn, 'phone');
                if (!empty($phones)):
                ?>
                  <div class="contact-info-item">
                    <div class="contact-icon">
                      <i class="fas fa-phone-alt"></i>
                    </div>
                    <div>
                      <h3 class="contact-info-title">Phone Numbers</h3>
                      <div class="contact-info-content">
                        <?php foreach ($phones as $phone): ?>
                          <p>
                            <?php echo htmlspecialchars($phone['label']); ?>:
                            <a href="tel:<?php echo htmlspecialchars($phone['value']); ?>" class="contact-link">
                              <?php echo htmlspecialchars($phone['value']); ?>
                            </a>
                          </p>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  </div>
                <?php
                endif;

                // WhatsApp information
                $whatsapp = getContactsByType($conn, 'whatsapp');
                if (!empty($whatsapp)):
                ?>
                  <div class="contact-info-item">
                    <div class="contact-icon">
                      <i class="fab fa-whatsapp"></i>
                    </div>
                    <div>
                      <h3 class="contact-info-title">WhatsApp</h3>
                      <div class="contact-info-content">
                        <?php foreach ($whatsapp as $wa): ?>
                          <p>
                            <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $wa['value']); ?>" class="contact-link" target="_blank">
                              <?php echo htmlspecialchars($wa['value']); ?>
                            </a>
                          </p>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  </div>
                <?php
                endif;

                // Email information
                $emails = getContactsByType($conn, 'email');
                if (!empty($emails)):
                ?>
                  <div class="contact-info-item">
                    <div class="contact-icon">
                      <i class="fas fa-envelope"></i>
                    </div>
                    <div>
                      <h3 class="contact-info-title">Email Addresses</h3>
                      <div class="contact-info-content">
                        <?php foreach ($emails as $email): ?>
                          <p>
                            <?php echo htmlspecialchars($email['label']); ?>:
                            <a href="mailto:<?php echo htmlspecialchars($email['value']); ?>" class="contact-link">
                              <?php echo htmlspecialchars($email['value']); ?>
                            </a>
                          </p>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  </div>
                <?php
                endif;

                // Business hours
                $hours = getContactsByType($conn, 'hours');
                if (!empty($hours)):
                ?>
                  <div class="contact-info-item">
                    <div class="contact-icon">
                      <i class="fas fa-clock"></i>
                    </div>
                    <div>
                      <h3 class="contact-info-title">Business Hours</h3>
                      <div class="contact-info-content">
                        <?php foreach ($hours as $hour): ?>
                          <p><?php echo htmlspecialchars($hour['value']); ?></p>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  </div>
                <?php
                endif;

                // Social media links
                $socials = getContactsByType($conn, 'social');
                if (!empty($socials)):
                ?>
                  <div class="contact-info-item">
                    <div class="contact-icon">
                      <i class="fas fa-globe"></i>
                    </div>
                    <div>
                      <h3 class="contact-info-title">Connect With Us</h3>
                      <div class="social-links mt-3">
                        <?php foreach ($socials as $social): ?>
                          <a href="<?php echo htmlspecialchars($social['value']); ?>" target="_blank" class="social-link" title="<?php echo htmlspecialchars($social['label']); ?>">
                            <?php if (strpos(strtolower($social['label']), 'facebook') !== false): ?>
                              <i class="fab fa-facebook-f"></i>
                            <?php elseif (strpos(strtolower($social['label']), 'twitter') !== false): ?>
                              <i class="fab fa-twitter"></i>
                            <?php elseif (strpos(strtolower($social['label']), 'instagram') !== false): ?>
                              <i class="fab fa-instagram"></i>
                            <?php elseif (strpos(strtolower($social['label']), 'linkedin') !== false): ?>
                              <i class="fab fa-linkedin-in"></i>
                            <?php elseif (strpos(strtolower($social['label']), 'youtube') !== false): ?>
                              <i class="fab fa-youtube"></i>
                            <?php else: ?>
                              <i class="fas fa-link"></i>
                            <?php endif; ?>
                          </a>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  </div>
              <?php
                endif;
              } else {
                // Fallback if functions don't exist
                echo '<p class="text-red-600">Contact functions not found. Please check your footer.php file.</p>';
              }
              ?>
            </div>
          </div>

          <?php
          // Display map if map data is available
          if (function_exists('getPrimaryContact')) {
            $map = getPrimaryContact($conn, 'map');
            if ($map):
          ?>
              <div class="map-container" data-aos="fade-up" data-aos-delay="100">
                <iframe src="<?php echo htmlspecialchars($map['value']); ?>" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
              </div>
            <?php else: ?>
              <!-- Default map for Karachi if no map data is available -->
              <div class="map-container" data-aos="fade-up" data-aos-delay="100">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d462118.02491053584!2d66.88572213952266!3d25.193672714866416!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3eb33e06651d4bbf%3A0x9cf92f44555a0c23!2sKarachi%2C%20Karachi%20City%2C%20Sindh!5e0!3m2!1sen!2s!4v1656363929991!5m2!1sen!2s" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
              </div>
            <?php
            endif;
          } else {
            ?>
            <!-- Default map fallback if functions aren't available -->
            <div class="map-container" data-aos="fade-up" data-aos-delay="100">
              <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d462118.02491053584!2d66.88572213952266!3d25.193672714866416!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3eb33e06651d4bbf%3A0x9cf92f44555a0c23!2sKarachi%2C%20Karachi%20City%2C%20Sindh!5e0!3m2!1sen!2s!4v1656363929991!5m2!1sen!2s" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
          <?php } ?>
        </div>
      </div>
    </div>
  </section>


  <?php include 'includes/footer.php'; ?>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      AOS.init({
        duration: 800,
        easing: 'ease-in-out',
        once: true
      });
    });
  </script>

  <?php include 'includes/js-links.php'; ?>
</body>

</html>