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
    airline_name VARCHAR(100) NOT NULL,
    flight_number VARCHAR(10) NOT NULL,
    departure_city VARCHAR(100) NOT NULL,
    arrival_city VARCHAR(100) NOT NULL,
    departure_date DATE NOT NULL,
    departure_time TIME NOT NULL,
    flight_duration DECIMAL(4,1) NOT NULL,
    distance INT NOT NULL,
    is_round_trip TINYINT(1) DEFAULT 0,
    return_airline VARCHAR(100),
    return_flight_number VARCHAR(10),
    return_date DATE,
    return_time TIME,
    return_flight_duration DECIMAL(4,1),
    flight_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE flight_stops (
    id INT AUTO_INCREMENT PRIMARY KEY,
    flight_id INT NOT NULL,
    stop_city VARCHAR(100) NOT NULL,
    stop_duration DECIMAL(4,1) NOT NULL,
    is_return_stop TINYINT(1) DEFAULT 0,
    FOREIGN KEY (flight_id) REFERENCES flights(id) ON DELETE CASCADE
);

CREATE TABLE flight_pricing (
    id INT AUTO_INCREMENT PRIMARY KEY,
    flight_id INT NOT NULL,
    economy_price DECIMAL(10,2) NOT NULL,
    business_price DECIMAL(10,2) NOT NULL,
    first_class_price DECIMAL(10,2) NOT NULL,
    economy_seats INT NOT NULL,
    business_seats INT NOT NULL,
    first_class_seats INT NOT NULL,
    FOREIGN KEY (flight_id) REFERENCES flights(id) ON DELETE CASCADE
);
-- ========================================


