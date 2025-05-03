<?php
require_once 'config/db.php';
session_start();

// Initialize variables
$name = $email = $message = $phone = $subject = '';
$success_message = $error_message = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_contact'])) {
  // Get and sanitize form data
  $name = htmlspecialchars(trim($_POST['name'] ?? ''), ENT_QUOTES, 'UTF-8');
  $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
  $message = htmlspecialchars(trim($_POST['message'] ?? ''), ENT_QUOTES, 'UTF-8');
  $phone = htmlspecialchars(trim($_POST['phone'] ?? ''), ENT_QUOTES, 'UTF-8');
  $subject = htmlspecialchars(trim($_POST['subject'] ?? ''), ENT_QUOTES, 'UTF-8');
  $ip_address = $_SERVER['REMOTE_ADDR'];
  $created_at = date('Y-m-d H:i:s');
  $status = 'unread'; // Default status

  // Validate inputs
  if (empty($name)) {
    $error_message = "Please enter your name.";
  } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error_message = "Please enter a valid email address.";
  } elseif (empty($message)) {
    $error_message = "Please enter your message.";
  } else {
    // Insert into database
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
          $email_message .= "Best regards,\nUmrah Partner Team";

          $headers = "From: no-reply@umrahpartner.com\r\n";
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
          $email_message .= "Submitted At: $created_at";

          if (!mail($to, $email_subject, $email_message, $headers)) {
            throw new Exception('Failed to send admin email.');
          }

          // Set success message
          $success_message = "Thank you for your message! We will get back to you soon.";
          // Clear form fields
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
  <!-- Add AOS (Animate on Scroll) Library -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" />
  <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
  <style>
    /* Custom styles for the contact page */
    .contact-icon {
      transition: all 0.3s ease;
    }

    .contact-info-item:hover .contact-icon {
      transform: scale(1.2);
      color: #047857;
    }

    .contact-form input:focus,
    .contact-form textarea:focus {
      border-color: #047857;
      box-shadow: 0 0 0 3px rgba(4, 120, 87, 0.1);
    }

    .contact-form button {
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
      z-index: 1;
    }

    .contact-form button::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(255, 255, 255, 0.1);
      z-index: -2;
    }

    .contact-form button::before {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      width: 0%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.1);
      transition: all 0.3s;
      z-index: -1;
    }

    .contact-form button:hover::before {
      width: 100%;
    }

    .map-container {
      position: relative;
      overflow: hidden;
      border-radius: 0.5rem;
    }

    .floating-card {
      transition: all 0.3s ease;
    }

    .floating-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
    }
  </style>
</head>

<body>
  <!-- Navbar -->
  <?php include 'includes/navbar.php'; ?>

  <section class="mt-5 py-16 bg-gray-50">
    <div class="container mx-auto px-4">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Contact Form -->
        <div data-aos="fade-right">
          <div class="bg-white rounded-xl shadow-md p-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Get in Touch</h2>

            <?php if ($success_message): ?>
              <div class="mb-6 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded">
                <div class="flex">
                  <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                  </div>
                  <div class="ml-3">
                    <p class="text-sm"><?php echo $success_message; ?></p>
                  </div>
                </div>
              </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
              <div class="mb-6 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded">
                <div class="flex">
                  <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                  </div>
                  <div class="ml-3">
                    <p class="text-sm"><?php echo $error_message; ?></p>
                  </div>
                </div>
              </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="contact-form">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                  <label for="name" class="block text-gray-700 font-medium mb-2">Full Name <span class="text-red-500">*</span></label>
                  <input
                    type="text"
                    id="name"
                    name="name"
                    value="<?php echo htmlspecialchars($name); ?>"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                    placeholder="Enter your name"
                    required>
                </div>

                <div>
                  <label for="email" class="block text-gray-700 font-medium mb-2">Email Address <span class="text-red-500">*</span></label>
                  <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?php echo htmlspecialchars($email); ?>"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                    placeholder="Enter your email"
                    required>
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                  <label for="phone" class="block text-gray-700 font-medium mb-2">Phone Number</label>
                  <input
                    type="tel"
                    id="phone"
                    name="phone"
                    value="<?php echo htmlspecialchars($phone ?? ''); ?>"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                    placeholder="Enter your phone number">
                </div>

                <div>
                  <label for="subject" class="block text-gray-700 font-medium mb-2">Subject</label>
                  <input
                    type="text"
                    id="subject"
                    name="subject"
                    value="<?php echo htmlspecialchars($subject ?? ''); ?>"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                    placeholder="What is this regarding?">
                </div>
              </div>

              <div class="mb-6">
                <label for="message" class="block text-gray-700 font-medium mb-2">Message <span class="text-red-500">*</span></label>
                <textarea
                  id="message"
                  name="message"
                  rows="5"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                  placeholder="Enter your message"
                  required><?php echo htmlspecialchars($message); ?></textarea>
              </div>

              <div class="text-right">
                <button
                  type="submit"
                  name="submit_contact"
                  class="bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-6 rounded-lg transition">
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

        <!-- Contact Info and Map -->
        <div data-aos="fade-left">
          <div class="bg-white rounded-xl shadow-md p-8 mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Contact Information</h2>

            <div class="space-y-6">
              <div class="contact-info-item flex items-start">
                <div class="contact-icon flex-shrink-0 w-10 h-10 bg-green-100 rounded-full flex items-center justify-center text-green-600 mr-4">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                </div>
                <div>
                  <h3 class="text-lg font-semibold text-gray-800 mb-1">Our Location</h3>
                  <p class="text-gray-600">123 Business Avenue, Karachi, Pakistan</p>
                  <p class="text-gray-600 mt-1">We are available for in-person consultations Monday through Friday, 9 AM to 5 PM.</p>
                </div>
              </div>

              <div class="contact-info-item flex items-start">
                <div class="contact-icon flex-shrink-0 w-10 h-10 bg-green-100 rounded-full flex items-center justify-center text-green-600 mr-4">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                  </svg>
                </div>
                <div>
                  <h3 class="text-lg font-semibold text-gray-800 mb-1">Phone Numbers</h3>
                  <p class="text-gray-600">Main: <a href="tel:+923001234567" class="text-green-600 hover:text-green-800 transition">+92 300 1234567</a></p>
                  <p class="text-gray-600">Support: <a href="tel:+923009876543" class="text-green-600 hover:text-green-800 transition">+92 300 9876543</a></p>
                </div>
              </div>

              <div class="contact-info-item flex items-start">
                <div class="contact-icon flex-shrink-0 w-10 h-10 bg-green-100 rounded-full flex items-center justify-center text-green-600 mr-4">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                  </svg>
                </div>
                <div>
                  <h3 class="text-lg font-semibold text-gray-800 mb-1">Email Addresses</h3>
                  <p class="text-gray-600">General Inquiries: <a href="mailto:info@umrahpartner.com" class="text-green-600 hover:text-green-800 transition">info@umrahpartner.com</a></p>
                  <p class="text-gray-600">Customer Support: <a href="mailto:support@umrahpartner.com" class="text-green-600 hover:text-green-800 transition">support@umrahpartner.com</a></p>
                </div>
              </div>

              <div class="contact-info-item flex items-start">
                <div class="contact-icon flex-shrink-0 w-10 h-10 bg-green-100 rounded-full flex items-center justify-center text-green-600 mr-4">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                <div>
                  <h3 class="text-lg font-semibold text-gray-800 mb-1">Business Hours</h3>
                  <p class="text-gray-600">Monday - Friday: 9:00 AM - 6:00 PM</p>
                  <p class="text-gray-600">Saturday: 10:00 AM - 2:00 PM</p>
                  <p class="text-gray-600">Sunday: Closed</p>
                </div>
              </div>
            </div>
          </div>

          <div class="map-container shadow-md rounded-xl overflow-hidden h-80">
            <!-- Replace with your actual Google Maps embed code -->
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d462118.02491053584!2d66.88572213952266!3d25.193672714866416!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3eb33e06651d4bbf%3A0x9cf92f44555a0c23!2sKarachi%2C%20Karachi%20City%2C%20Sindh!5e0!3m2!1sen!2s!4v1656363929991!5m2!1sen!2s" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- FAQ Section -->
  <section class="py-16 bg-white">
    <div class="container mx-auto px-4">
      <div class="text-center mb-12" data-aos="fade-up">
        <h2 class="text-3xl font-bold text-gray-800 mb-4">Frequently Asked Questions</h2>
        <p class="text-gray-600 max-w-2xl mx-auto">Find quick answers to common questions about our contact and support services.</p>
      </div>

      <div class="max-w-3xl mx-auto">
        <div class="space-y-4">
          <div class="bg-gray-50 rounded-lg p-6 shadow-sm" data-aos="fade-up" data-aos-delay="100">
            <h3 class="text-lg font-semibold text-gray-800 mb-2">How quickly can I expect a response to my inquiry?</h3>
            <p class="text-gray-600">We aim to respond to all inquiries within 24 hours during business days. For urgent matters, we recommend calling our support line directly.</p>
          </div>

          <div class="bg-gray-50 rounded-lg p-6 shadow-sm" data-aos="fade-up" data-aos-delay="200">
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Can I schedule an in-person consultation about Umrah packages?</h3>
            <p class="text-gray-600">Yes, you can schedule an in-person consultation at our office in Karachi. Please call ahead or use our contact form to book an appointment.</p>
          </div>

          <div class="bg-gray-50 rounded-lg p-6 shadow-sm" data-aos="fade-up" data-aos-delay="300">
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Do you offer support for visa-related questions?</h3>
            <p class="text-gray-600">Yes, our team can provide guidance on visa requirements for Umrah. For specific cases, we may refer you to our visa specialists for detailed assistance.</p>
          </div>

          <div class="bg-gray-50 rounded-lg p-6 shadow-sm" data-aos="fade-up" data-aos-delay="400">
            <h3 class="text-lg font-semibold text-gray-800 mb-2">How can I check the status of my existing booking?</h3>
            <p class="text-gray-600">You can check your booking status by logging into your account on our website or by contacting our customer support team with your booking reference number.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <?php include 'includes/footer.php'; ?>

  <!-- Initialize AOS (Animate on Scroll) -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Initialize AOS animation
      AOS.init({
        duration: 800,
        easing: 'ease-in-out',
        once: true
      });
    });
  </script>

  <?php include 'includes/js-links.php' ?>
</body>

</html>