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
  <style>
    :root {
      --primary: #1E3A8A;
      --primary-light: #3B82F6;
      --primary-dark: #1E40AF;
      --secondary: #10B981;
      --accent: #F59E0B;
      --dark: #111827;
      --light: #F9FAFB;
      --gray: #4B5563;
      --light-gray: #E5E7EB;
    }

    html,
    body {
      overflow-x: hidden;
      font-family: 'Plus Jakarta Sans', 'Inter', sans-serif;
      color: var(--dark);
      line-height: 1.6;
    }

    /* Contact Hero Section */
    .hero-contact {
      position: relative;
      background-color: #f8fafc;
      overflow: hidden;
      padding: 8rem 0 6rem;
    }

    .hero-contact::before {
      content: '';
      position: absolute;
      right: -15%;
      top: -20%;
      width: 50%;
      height: 120%;
      background: radial-gradient(circle at center, var(--primary-light) 0%, var(--primary) 70%);
      opacity: 0.05;
      border-radius: 50%;
    }

    .hero-contact::after {
      content: '';
      position: absolute;
      left: -10%;
      bottom: -30%;
      width: 40%;
      height: 80%;
      background: radial-gradient(circle at center, var(--primary-light) 0%, var(--primary) 70%);
      opacity: 0.05;
      border-radius: 50%;
    }

    .section-title {
      font-size: 2.25rem;
      font-weight: 800;
      color: var(--dark);
      line-height: 1.2;
      position: relative;
      display: inline-block;
      margin-bottom: 1.5rem;
    }

    .section-title::after {
      content: '';
      position: absolute;
      bottom: -10px;
      left: 0;
      width: 80px;
      height: 4px;
      background: linear-gradient(to right, var(--primary), var(--primary-light));
      border-radius: 2px;
    }

    .section-subtitle {
      color: var(--primary);
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.1em;
      margin-bottom: 0.5rem;
    }

    /* Contact Form */
    .contact-form-card {
      border-radius: 16px;
      overflow: hidden;
      background-color: white;
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
      padding: 2.5rem;
      position: relative;
    }

    .contact-form-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 5px;
      background: linear-gradient(to right, var(--primary), var(--primary-light));
    }

    .input-group {
      margin-bottom: 1.5rem;
    }

    .input-label {
      display: block;
      margin-bottom: 0.5rem;
      color: var(--dark);
      font-weight: 500;
    }

    .input-field {
      width: 100%;
      padding: 0.75rem 1rem;
      border: 1px solid var(--light-gray);
      border-radius: 8px;
      font-size: 1rem;
      transition: all 0.3s ease;
    }

    .input-field:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .textarea-field {
      min-height: 150px;
      resize: vertical;
    }

    .submit-button {
      background: linear-gradient(to right, var(--primary), var(--primary-dark));
      color: white;
      font-weight: 600;
      padding: 0.875rem 2rem;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      position: relative;
      overflow: hidden;
      z-index: 1;
    }

    .submit-button::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(to right, var(--primary-dark), var(--primary));
      transition: all 0.4s ease;
      z-index: -1;
    }

    .submit-button:hover::before {
      left: 0;
    }

    .submit-button:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    /* Contact Info */
    .contact-info-card {
      border-radius: 16px;
      overflow: hidden;
      background-color: white;
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
      padding: 2.5rem;
      height: 100%;
      position: relative;
    }

    .contact-info-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 5px;
      background: linear-gradient(to right, var(--primary), var(--primary-light));
    }

    .contact-info-item {
      margin-bottom: 2rem;
      display: flex;
      align-items: flex-start;
    }

    .contact-info-item:last-child {
      margin-bottom: 0;
    }

    .contact-icon {
      width: 50px;
      height: 50px;
      background: rgba(59, 130, 246, 0.1);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--primary);
      font-size: 1.25rem;
      margin-right: 1rem;
      flex-shrink: 0;
      transition: all 0.3s ease;
    }

    .contact-info-item:hover .contact-icon {
      background: var(--primary);
      color: white;
      transform: scale(1.1);
    }

    .contact-info-title {
      font-size: 1.25rem;
      font-weight: 700;
      color: var(--dark);
      margin-bottom: 0.5rem;
    }

    .contact-info-content {
      color: var(--gray);
    }

    .contact-link {
      color: var(--primary);
      transition: all 0.3s ease;
    }

    .contact-link:hover {
      color: var(--primary-dark);
    }

    .social-links {
      display: flex;
      gap: 0.75rem;
    }

    .social-link {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background-color: rgba(59, 130, 246, 0.1);
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--primary);
      transition: all 0.3s ease;
    }

    .social-link:hover {
      background-color: var(--primary);
      color: white;
    }

    /* Map Container */
    .map-container {
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
      height: 350px;
      position: relative;
    }

    /* Success/Error Messages */
    .alert {
      padding: 1rem;
      border-radius: 8px;
      margin-bottom: 1.5rem;
      position: relative;
      padding-left: 3.5rem;
    }

    .alert-success {
      background-color: rgba(16, 185, 129, 0.1);
      border-left: 4px solid #10B981;
      color: #065F46;
    }

    .alert-error {
      background-color: rgba(239, 68, 68, 0.1);
      border-left: 4px solid #EF4444;
      color: #B91C1C;
    }

    .alert-icon {
      position: absolute;
      left: 1rem;
      top: 50%;
      transform: translateY(-50%);
      width: 1.5rem;
      height: 1.5rem;
    }

    /* Featured Areas Section */
    .featured-areas {
      background-color: #f8fafc;
      padding: 6rem 0;
    }

    .feature-card {
      border-radius: 16px;
      overflow: hidden;
      background-color: white;
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
      transition: all 0.4s ease;
      height: 100%;
      display: flex;
      flex-direction: column;
    }

    .feature-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }

    .feature-icon-lg {
      width: 70px;
      height: 70px;
      background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(37, 99, 235, 0.2));
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 1.5rem;
      color: var(--primary);
      font-size: 1.75rem;
    }

    .feature-title {
      font-size: 1.25rem;
      font-weight: 700;
      margin-bottom: 1rem;
      color: var(--dark);
    }

    .feature-text {
      color: var(--gray);
      font-size: 1rem;
      line-height: 1.6;
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {

      .contact-form-card,
      .contact-info-card {
        padding: 1.5rem;
      }

      .contact-icon {
        width: 40px;
        height: 40px;
        font-size: 1rem;
      }

      .section-title {
        font-size: 1.75rem;
      }
    }
  </style>
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