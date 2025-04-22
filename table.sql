-- ========================================
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  dob DATE NOT NULL,
  profile_image VARCHAR(255),
  created_at DATETIME NOT NULL
);
-- ========================================
CREATE TABLE flights (
  id INT AUTO_INCREMENT PRIMARY KEY,
  airline_name VARCHAR(255) NOT NULL,
  flight_number VARCHAR(10) NOT NULL,
  departure_city VARCHAR(255) NOT NULL,
  arrival_city VARCHAR(255) NOT NULL,
  departure_date DATE NOT NULL,
  departure_time TIME NOT NULL,
  flight_duration DECIMAL(5,2) NOT NULL,
  distance INT NOT NULL,
  economy_price DECIMAL(10,2) NOT NULL,
  business_price DECIMAL(10,2) NOT NULL,
  first_class_price DECIMAL(10,2) NOT NULL,
  economy_seats INT NOT NULL,
  business_seats INT NOT NULL,
  first_class_seats INT NOT NULL,
  flight_notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
-- ========================================


