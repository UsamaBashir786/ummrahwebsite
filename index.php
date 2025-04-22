<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Umrah Navbar</title>
  <link rel="stylesheet" href="assets/bootstrap-5.2.3-dist/css/bootstrap.min.css">
  <style>
    .navbar {
      background-color: #f8f9fa;
      padding: 10px 20px;
    }

    .navbar-brand {
      display: flex;
      align-items: center;
      font-size: 1.5rem;
      font-weight: bold;
      color: #2c3e50;
    }

    .navbar-brand img {
      width: 40px;
      margin-right: 10px;
    }

    .nav-link {
      color: #2c3e50;
      font-weight: 500;
      margin-right: 15px;
    }

    .nav-link:hover {
      color: #17a2b8;
    }

    .dropdown-menu {
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .dropdown-item {
      color: #2c3e50;
    }

    .dropdown-item:hover {
      background-color: #e9ecef;
      color: #17a2b8;
    }

    .dashboard-btn {
      background-color: #17a2b8;
      color: white;
      border-radius: 20px;
      padding: 5px 15px;
      font-weight: 500;
    }

    .dashboard-btn:hover {
      background-color: #138496;
      color: white;
    }

    .logout-link {
      color: #17a2b8;
      font-weight: 500;
    }

    .logout-link:hover {
      color: #138496;
    }

    /* hero section */
    .hero-section {
      position: relative;
      height: 400px;
      background-image: url('path/to/hero-image.jpg');
      background-size: cover;
      background-position: center;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      color: white;
    }

    .hero-section::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      z-index: 1;
    }

    .hero-content {
      position: relative;
      z-index: 2;
      max-width: 800px;
      padding: 20px;
    }

    .hero-content h1 {
      font-size: 3rem;
      font-weight: bold;
      margin-bottom: 20px;
      text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
    }

    .hero-content p {
      font-size: 1.2rem;
      margin-bottom: 30px;
      text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.5);
    }

    .explore-btn {
      background-color: #17a2b8;
      color: white;
      border: none;
      padding: 10px 30px;
      font-size: 1.1rem;
      font-weight: 500;
      border-radius: 25px;
      transition: background-color 0.3s ease;
    }

    .explore-btn:hover {
      background-color: #138496;
      color: white;
    }

    /* packages section */
    .packages-section {
      padding: 50px 0;
      background-color: #f8f9fa;
    }

    .section-title {
      font-size: 2.5rem;
      font-weight: bold;
      color: #2c3e50;
      margin-bottom: 40px;
    }

    .section-subtitle {
      color: #17a2b8;
      font-size: 1.2rem;
      margin-bottom: 10px;
    }

    .package-card {
      border: none;
      border-radius: 15px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
      overflow: hidden;
      transition: transform 0.3s ease;
    }

    .package-card:hover {
      transform: translateY(-10px);
    }

    .package-card img {
      width: 100%;
      height: 200px;
      object-fit: cover;
    }

    .limited-offer {
      position: absolute;
      top: 10px;
      right: 10px;
      background-color: #17a2b8;
      color: white;
      padding: 5px 10px;
      border-radius: 10px;
      font-size: 0.9rem;
      font-weight: 500;
    }

    .card-body {
      padding: 20px;
    }

    .package-price {
      font-size: 1.8rem;
      font-weight: bold;
      color: #2c3e50;
      margin-bottom: 10px;
    }

    .package-title {
      font-size: 1.3rem;
      font-weight: 600;
      color: #17a2b8;
      margin-bottom: 5px;
    }

    .package-location {
      font-size: 0.9rem;
      color: #6c757d;
      margin-bottom: 15px;
    }

    .package-features {
      list-style: none;
      padding: 0;
      margin-bottom: 20px;
    }

    .package-features li {
      display: flex;
      align-items: center;
      margin-bottom: 10px;
      font-size: 0.95rem;
      color: #2c3e50;
    }

    .package-features li img {
      width: 20px;
      margin-right: 10px;
    }

    .learn-more-btn {
      background-color: #17a2b8;
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 20px;
      font-weight: 500;
      transition: background-color 0.3s ease;
    }

    .learn-more-btn:hover {
      background-color: #138496;
      color: white;
    }

    a {
      text-decoration: none;
    }

    /* elevate */
    .features-section {
      padding: 50px 0;
      background-color: #f8f9fa;
    }

    .section-subtitle {
      color: #17a2b8;
      font-size: 1.2rem;
      margin-bottom: 10px;
    }

    .section-title {
      font-size: 2.5rem;
      font-weight: bold;
      color: #2c3e50;
      margin-bottom: 40px;
    }

    .feature-image img {
      width: 100%;
      max-width: 400px;
      border-radius: 15px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .feature-item {
      display: flex;
      align-items: center;
      margin-bottom: 20px;
    }

    .feature-item img {
      width: 40px;
      margin-right: 15px;
    }

    .feature-item h5 {
      font-size: 1.2rem;
      font-weight: 600;
      color: #2c3e50;
      margin-bottom: 5px;
    }

    .feature-item p {
      font-size: 0.95rem;
      color: #6c757d;
      margin: 0;
    }

    .view-packages {
      color: #17a2b8;
      font-weight: 500;
      text-decoration: none;
      font-size: 1.1rem;
      display: inline-block;
      margin-top: 20px;
      float: right;
    }

    .view-packages:hover {
      color: #138496;
    }

    /* footer page */
    .footer {
      background-color: #2c3e50;
      color: white;
      padding: 40px 0;
      position: relative;
    }

    .footer h5 {
      font-size: 1.2rem;
      font-weight: bold;
      margin-bottom: 20px;
    }

    .footer p {
      font-size: 0.95rem;
      color: #b0b8c1;
    }

    .footer ul {
      list-style: none;
      padding: 0;
    }

    .footer ul li {
      margin-bottom: 10px;
    }

    .footer ul li a {
      color: #b0b8c1;
      text-decoration: none;
      font-size: 0.95rem;
    }

    .footer ul li a:hover {
      color: #17a2b8;
    }

    .social-icons a {
      color: #b0b8c1;
      font-size: 1.2rem;
      margin-right: 15px;
      text-decoration: none;
    }

    .social-icons a:hover {
      color: #17a2b8;
    }

    .contact-info {
      display: flex;
      align-items: center;
      margin-bottom: 10px;
    }

    .contact-info img {
      width: 20px;
      margin-right: 10px;
    }

    .contact-info a,
    .contact-info p {
      color: #b0b8c1;
      text-decoration: none;
      font-size: 0.95rem;
    }

    .contact-info a:hover {
      color: #17a2b8;
    }

    .back-to-top {
      position: absolute;
      bottom: 20px;
      right: 20px;
      background-color: #17a2b8;
      color: white;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      text-decoration: none;
      font-size: 1.2rem;
    }

    .back-to-top:hover {
      background-color: #138496;
    }
  </style>
</head>

<body>
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
      <a class="navbar-brand" href="#">
        <!-- <img src="path/to/logo.png" alt="Umrah Logo"> -->
        UMRAH
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
        <ul class="navbar-nav">
          <li class="nav-item">
            <a class="nav-link" href="#">Home</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#">Packages</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#">About Us</a>
          </li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              More
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
              <li><a class="dropdown-item" href="#">Transportation</a></li>
              <li><a class="dropdown-item" href="#">Flights</a></li>
              <li><a class="dropdown-item" href="#">Hotels</a></li>
            </ul>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#">Login</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#">Register</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>
  <!-- Hero Section -->
  <section class="hero-section">
    <div class="hero-content">
      <h1>Experience the Sacred Journey of Umrah</h1>
      <p>Embark on a transformative spiritual journey with our comprehensive Umrah packages. Let us help you make the most of your pilgrimage experience with our tailored services.</p>
      <a href="#" class="explore-btn text-decoration-none">Explore Packages</a>
    </div>
  </section>
  <!-- Packages Section -->
  <section class="packages-section">
    <div class="container">
      <div class="text-center">
        <div class="section-subtitle">- Packages</div>
        <h2 class="section-title">Choose Your Umrah Package</h2>
      </div>
      <div class="row mt-4">
        <!-- Package 1 -->
        <div class="col-lg-4 col-md-6 mb-4">
          <div class="package-card">
            <div class="position-relative">
              <img src="path/to/image1.jpg" alt="Budget Umrah Bliss">
              <span class="limited-offer">Limited Offer</span>
            </div>
            <div class="card-body">
              <div class="package-price">Rs245,000.00</div>
              <div class="package-title">Budget Umrah Bliss</div>
              <div class="package-location">Lahore - Jeddah</div>
              <ul class="package-features">
                <li><img src="path/to/icon1.png" alt="icon">Document Guide</li>
                <li><img src="path/to/icon2.png" alt="icon">Economy Class Flight</li>
                <li><img src="path/to/icon3.png" alt="icon">Local Meals</li>
                <li><img src="path/to/icon4.png" alt="icon">Visa Included</li>
              </ul>
              <a href="#" class="learn-more-btn">Learn More</a>
            </div>
          </div>
        </div>
        <!-- Package 2 -->
        <div class="col-lg-4 col-md-6 mb-4">
          <div class="package-card">
            <div class="position-relative">
              <img src="path/to/image2.jpg" alt="Premium Spiritual Retreat">
              <span class="limited-offer">Limited Offer</span>
            </div>
            <div class="card-body">
              <div class="package-price">Rs375,000.00</div>
              <div class="package-title">Premium Spiritual Retreat</div>
              <div class="package-location">Islamabad - Madinah</div>
              <ul class="package-features">
                <li><img src="path/to/icon1.png" alt="icon">Document Guide</li>
                <li><img src="path/to/icon2.png" alt="icon">Business Class Flight</li>
                <li><img src="path/to/icon3.png" alt="icon">Local Meals</li>
                <li><img src="path/to/icon4.png" alt="icon">Visa Included</li>
              </ul>
              <a href="#" class="learn-more-btn">Learn More</a>
            </div>
          </div>
        </div>
        <!-- Package 3 -->
        <div class="col-lg-4 col-md-6 mb-4">
          <div class="package-card">
            <div class="position-relative">
              <img src="path/to/image3.jpg" alt="Executive Umrah Experience">
              <span class="limited-offer">Limited Offer</span>
            </div>
            <div class="card-body">
              <div class="package-price">Rs500,000.00</div>
              <div class="package-title">Executive Umrah Experience</div>
              <div class="package-location">Karachi - Jeddah</div>
              <ul class="package-features">
                <li><img src="path/to/icon1.png" alt="icon">Document Guide</li>
                <li><img src="path/to/icon2.png" alt="icon">First Class Flight</li>
                <li><img src="path/to/icon3.png" alt="icon">Local Meals</li>
                <li><img src="path/to/icon4.png" alt="icon">Visa Included</li>
              </ul>
              <a href="#" class="learn-more-btn">Learn More</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <!-- Elevate Section -->
  <section class="features-section">
    <div class="container">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <div class="section-subtitle">- Features</div>
          <h2 class="section-title">Elevate Your Faith</h2>
        </div>
        <a href="#" class="view-packages">View Packages <span>→</span></a>
      </div>
      <div class="row align-items-center">
        <!-- Image -->
        <div class="col-lg-6 mb-4">
          <div class="feature-image">
            <img src="path/to/feature-image.jpg" alt="Feature Image">
          </div>
        </div>
        <!-- Features in 3x2 Grid -->
        <div class="col-lg-6">
          <div class="row">
            <!-- Feature 1 -->
            <div class="col-6">
              <div class="feature-item">
                <img src="path/to/icon1.png" alt="Tawaf Icon">
                <div>
                  <h5>Tawaf</h5>
                  <p>Circumambulating the Kaaba in unity.</p>
                </div>
              </div>
            </div>
            <!-- Feature 2 -->
            <div class="col-6">
              <div class="feature-item">
                <img src="path/to/icon2.png" alt="Ihram Icon">
                <div>
                  <h5>Ihram</h5>
                  <p>Sacred attire signifying purity.</p>
                </div>
              </div>
            </div>
            <!-- Feature 3 -->
            <div class="col-6">
              <div class="feature-item">
                <img src="path/to/icon3.png" alt="Mina Icon">
                <div>
                  <h5>Mina</h5>
                  <p>Sacred desert valley for pilgrims.</p>
                </div>
              </div>
            </div>
            <!-- Feature 4 -->
            <div class="col-6">
              <div class="feature-item">
                <img src="path/to/icon4.png" alt="Jamarat Icon">
                <div>
                  <h5>Jamarat</h5>
                  <p>Symbolic act of rejecting Satan.</p>
                </div>
              </div>
            </div>
            <!-- Feature 5 -->
            <div class="col-6">
              <div class="feature-item">
                <img src="path/to/icon5.png" alt="Zam-Zam Icon">
                <div>
                  <h5>Zam-Zam</h5>
                  <p>Holy water with miraculous origins.</p>
                </div>
              </div>
            </div>
            <!-- Feature 6 -->
            <div class="col-6">
              <div class="feature-item">
                <img src="path/to/icon6.png" alt="Prayer Mat Icon">
                <div>
                  <h5>Prayer Mat</h5>
                  <p>Sacred space for performing Salah.</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <!-- Footer -->
  <footer class="footer">
    <div class="container">
      <div class="row">
        <!-- UmrahFlights Description -->
        <div class="col-lg-3 col-md-6 mb-4">
          <h5>UmrahFlights</h5>
          <p>Making your journey to the Holy Land easier and more comfortable.</p>
          <div class="social-icons">
            <a href="#"><i class="fab fa-facebook-f"></i></a>
            <a href="#"><i class="fab fa-twitter"></i></a>
            <a href="#"><i class="fab fa-instagram"></i></a>
            <a href="#"><i class="fab fa-youtube"></i></a>
          </div>
        </div>
        <!-- Quick Links -->
        <div class="col-lg-3 col-md-6 mb-4">
          <h5>Quick Links</h5>
          <ul>
            <li><a href="#">Home</a></li>
            <li><a href="#">Flights</a></li>
            <li><a href="#">Packages</a></li>
            <li><a href="#">About Us</a></li>
            <li><a href="#">Contact</a></li>
          </ul>
        </div>
        <!-- Support -->
        <div class="col-lg-3 col-md-6 mb-4">
          <h5>Support</h5>
          <ul>
            <li><a href="#">FAQ</a></li>
            <li><a href="#">Baggage Information</a></li>
            <li><a href="#">Visa Requirements</a></li>
            <li><a href="#">Terms & Conditions</a></li>
            <li><a href="#">Privacy Policy</a></li>
          </ul>
        </div>
        <!-- Contact Us -->
        <div class="col-lg-3 col-md-6 mb-4">
          <h5>Contact Us</h5>
          <div class="contact-info">
            <img src="path/to/location-icon.png" alt="Location Icon">
            <p>123 Business Avenue, Karachi, Pakistan</p>
          </div>
          <div class="contact-info">
            <img src="path/to/phone-icon.png" alt="Phone Icon">
            <a href="tel:+923001234567">+92 300 1234567</a>
          </div>
          <div class="contact-info">
            <img src="path/to/email-icon.png" alt="Email Icon">
            <a href="mailto:info@umrahflights.com">info@umrahflights.com</a>
          </div>
        </div>
      </div>
    </div>
    <a href="#" class="back-to-top">↑</a>
  </footer>
  <script src="assets/bootstrap-5.2.3-dist/js/bootstrap.bundle.js"></script>
</body>

</html>