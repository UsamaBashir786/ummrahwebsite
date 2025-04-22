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


