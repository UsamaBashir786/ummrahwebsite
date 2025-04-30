<?php
require_once 'config/db.php';
session_start();
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
    .service-card {
      transition: all 0.3s ease;
    }

    .service-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
    }

    .team-member {
      transition: all 0.3s ease;
    }

    .team-member:hover {
      transform: translateY(-5px);
    }

    .team-member:hover .social-links {
      opacity: 1;
    }

    .social-links {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      background: linear-gradient(to top, rgba(0, 0, 0, 0.7), transparent);
      padding: 20px 0 15px;
      opacity: 0;
      transition: all 0.3s ease;
    }

    .milestone-counter {
      position: relative;
      z-index: 1;
    }

    .milestone-counter::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: rgba(4, 120, 87, 0.05);
      border-radius: 0.5rem;
      z-index: -1;
      transform: rotate(-3deg);
      transition: all 0.3s ease;
    }

    .milestone-counter:hover::before {
      transform: rotate(0deg);
      background-color: rgba(4, 120, 87, 0.1);
    }

    .counter-value {
      color: #047857;
      font-size: 2.5rem;
      font-weight: 700;
      line-height: 1;
    }

    .counter-label {
      color: #4b5563;
      font-size: 1rem;
      margin-top: 0.5rem;
    }

    .timeline-item {
      position: relative;
      padding-left: 30px;
      margin-bottom: 30px;
    }

    .timeline-item::before {
      content: '';
      position: absolute;
      left: 0;
      top: 5px;
      width: 15px;
      height: 15px;
      border-radius: 50%;
      background-color: #047857;
    }

    .timeline-item::after {
      content: '';
      position: absolute;
      left: 7px;
      top: 25px;
      width: 1px;
      height: calc(100% + 10px);
      background-color: #d1d5db;
    }

    .timeline-item:last-child::after {
      display: none;
    }

    .testimonial-card {
      transition: all 0.3s ease;
    }

    .testimonial-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }

    .testimonial-card::before {
      content: '"';
      position: absolute;
      top: 20px;
      left: 20px;
      font-size: 5rem;
      line-height: 1;
      color: rgba(4, 120, 87, 0.1);
      font-family: Georgia, serif;
    }
  </style>
</head>

<body>
  <!-- Navbar -->
  <?php include 'includes/navbar.php'; ?>

  <!-- Introduction Section -->
  <section class="py-16 bg-gray-50">
    <div class="container mx-auto px-4">
      <div class="flex flex-col md:flex-row gap-12 items-center">
        <div class="w-full md:w-1/2" data-aos="fade-right">
          <h2 class="text-3xl font-bold text-gray-800 mb-6">Our Mission</h2>
          <p class="text-gray-600 leading-relaxed mb-6">
            At UmrahFlights, we are dedicated to providing seamless travel solutions for pilgrims embarking on their sacred Umrah journey. We understand the profound spiritual significance of this pilgrimage and strive to make it as smooth and comfortable as possible.
          </p>
          <p class="text-gray-600 leading-relaxed mb-6">
            Our mission is to facilitate a spiritually enriching and logistically hassle-free Umrah experience for Muslims around the world. We believe that every aspect of the journey should contribute to the pilgrim's spiritual focus and peace of mind.
          </p>
          <div class="bg-white p-6 rounded-lg shadow-sm border-l-4 border-green-600">
            <p class="italic text-gray-600">
              "We don't just arrange travels; we facilitate spiritual journeys that transform lives and bring believers closer to their faith."
            </p>
            <p class="mt-2 font-medium text-gray-800">- Ahmed Khan, Founder</p>
          </div>
        </div>
        <div class="w-full md:w-1/2" data-aos="fade-left">
          <img src="assets/img/hero.jpg" alt="Umrah Pilgrimage" class="w-full h-auto rounded-lg shadow-md">
        </div>
      </div>
    </div>
  </section>

  <!-- Values Section -->
  <section class="py-16 bg-white">
    <div class="container mx-auto px-4">
      <div class="text-center mb-12" data-aos="fade-up">
        <h2 class="text-3xl font-bold text-gray-800 mb-4">Our Core Values</h2>
        <p class="text-gray-600 max-w-3xl mx-auto">
          These principles guide every decision we make and service we provide, ensuring that your sacred journey is handled with the utmost care and respect.
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <div class="service-card bg-white p-6 rounded-lg shadow-md border-t-4 border-green-600" data-aos="fade-up" data-aos-delay="100">
          <div class="w-14 h-14 bg-green-100 rounded-lg flex items-center justify-center mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
          </div>
          <h3 class="text-xl font-bold text-gray-800 mb-2">Integrity</h3>
          <p class="text-gray-600">
            We uphold the highest standards of honesty and transparency in all our dealings. What we promise is what we deliver, without hidden costs or surprises.
          </p>
        </div>

        <div class="service-card bg-white p-6 rounded-lg shadow-md border-t-4 border-green-600" data-aos="fade-up" data-aos-delay="200">
          <div class="w-14 h-14 bg-green-100 rounded-lg flex items-center justify-center mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
          </div>
          <h3 class="text-xl font-bold text-gray-800 mb-2">Excellence</h3>
          <p class="text-gray-600">
            We continuously strive for excellence in every service we provide, from the quality of accommodations to the efficiency of transportation and support.
          </p>
        </div>

        <div class="service-card bg-white p-6 rounded-lg shadow-md border-t-4 border-green-600" data-aos="fade-up" data-aos-delay="300">
          <div class="w-14 h-14 bg-green-100 rounded-lg flex items-center justify-center mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
          </div>
          <h3 class="text-xl font-bold text-gray-800 mb-2">Compassion</h3>
          <p class="text-gray-600">
            We approach our work with genuine care for our pilgrims, understanding the deep spiritual significance of their journey and their individual needs.
          </p>
        </div>

        <div class="service-card bg-white p-6 rounded-lg shadow-md border-t-4 border-green-600" data-aos="fade-up" data-aos-delay="400">
          <div class="w-14 h-14 bg-green-100 rounded-lg flex items-center justify-center mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
            </svg>
          </div>
          <h3 class="text-xl font-bold text-gray-800 mb-2">Communication</h3>
          <p class="text-gray-600">
            We maintain clear and consistent communication with our pilgrims at every stage of their journey, ensuring they are well-informed and supported.
          </p>
        </div>

        <div class="service-card bg-white p-6 rounded-lg shadow-md border-t-4 border-green-600" data-aos="fade-up" data-aos-delay="500">
          <div class="w-14 h-14 bg-green-100 rounded-lg flex items-center justify-center mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
          </div>
          <h3 class="text-xl font-bold text-gray-800 mb-2">Convenience</h3>
          <p class="text-gray-600">
            We design our services to be as convenient and hassle-free as possible, taking care of the logistical details so pilgrims can focus on their spiritual journey.
          </p>
        </div>

        <div class="service-card bg-white p-6 rounded-lg shadow-md border-t-4 border-green-600" data-aos="fade-up" data-aos-delay="600">
          <div class="w-14 h-14 bg-green-100 rounded-lg flex items-center justify-center mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
          </div>
          <h3 class="text-xl font-bold text-gray-800 mb-2">Support</h3>
          <p class="text-gray-600">
            We provide 24/7 support to our pilgrims, ensuring that assistance is always available whenever and wherever it's needed during their journey.
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- Statistics Section -->
  <section class="py-16 bg-gray-50">
    <div class="container mx-auto px-4">
      <div class="text-center mb-12" data-aos="fade-up">
        <h2 class="text-3xl font-bold text-gray-800 mb-4">Our Impact in Numbers</h2>
        <p class="text-gray-600 max-w-3xl mx-auto">
          Since our founding, we've been honored to facilitate the spiritual journeys of thousands of pilgrims.
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
        <div class="milestone-counter text-center p-6" data-aos="fade-up" data-aos-delay="100">
          <div class="counter-value">12,000+</div>
          <div class="counter-label">Pilgrims Served</div>
        </div>

        <div class="milestone-counter text-center p-6" data-aos="fade-up" data-aos-delay="200">
          <div class="counter-value">15</div>
          <div class="counter-label">Years of Experience</div>
        </div>

        <div class="milestone-counter text-center p-6" data-aos="fade-up" data-aos-delay="300">
          <div class="counter-value">98%</div>
          <div class="counter-label">Customer Satisfaction</div>
        </div>

        <div class="milestone-counter text-center p-6" data-aos="fade-up" data-aos-delay="400">
          <div class="counter-value">24/7</div>
          <div class="counter-label">Customer Support</div>
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