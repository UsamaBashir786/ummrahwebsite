-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 22, 2025 at 08:26 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `latestummrah`
--

-- --------------------------------------------------------

--
-- Table structure for table `flights`
--

CREATE TABLE `flights` (
  `id` int(11) NOT NULL,
  `airline_name` varchar(100) NOT NULL,
  `flight_number` varchar(10) NOT NULL,
  `departure_city` varchar(100) NOT NULL,
  `arrival_city` varchar(100) NOT NULL,
  `has_stops` tinyint(1) DEFAULT 0,
  `stops` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`stops`)),
  `departure_date` date NOT NULL,
  `departure_time` time NOT NULL,
  `flight_duration` float NOT NULL,
  `distance` int(11) NOT NULL,
  `has_return` tinyint(1) DEFAULT 0,
  `return_airline` varchar(100) DEFAULT NULL,
  `return_flight_number` varchar(10) DEFAULT NULL,
  `return_date` date DEFAULT NULL,
  `return_time` time DEFAULT NULL,
  `return_flight_duration` float DEFAULT NULL,
  `has_return_stops` tinyint(1) DEFAULT 0,
  `return_stops` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`return_stops`)),
  `economy_price` int(11) NOT NULL,
  `business_price` int(11) NOT NULL,
  `first_class_price` int(11) NOT NULL,
  `economy_seats` int(11) NOT NULL,
  `business_seats` int(11) NOT NULL,
  `first_class_seats` int(11) NOT NULL,
  `flight_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `flights`
--

INSERT INTO `flights` (`id`, `airline_name`, `flight_number`, `departure_city`, `arrival_city`, `has_stops`, `stops`, `departure_date`, `departure_time`, `flight_duration`, `distance`, `has_return`, `return_airline`, `return_flight_number`, `return_date`, `return_time`, `return_flight_duration`, `has_return_stops`, `return_stops`, `economy_price`, `business_price`, `first_class_price`, `economy_seats`, `business_seats`, `first_class_seats`, `flight_notes`, `created_at`) VALUES
(9, 'Qatar', 'PK-309', 'Karachi', 'Jeddah', 1, '[{\"city\":\"DSFASDF\",\"duration\":12312},{\"city\":\"ASDFSAD\",\"duration\":12}]', '2025-04-18', '22:30:00', 2, 23213, 1, 'Emirates', 'PK-309', '2025-04-25', '22:30:00', 2, 1, '[{\"city\":\"SDFASDF\",\"duration\":2},{\"city\":\"DSFASD\",\"duration\":22}]', 21312, 21321, 213, 12321, 21321, 312, 'DFASDG', '2025-04-22 16:32:55'),
(10, 'Flydubai', 'pk-307', 'Lahore', 'Jeddah', 0, '[]', '2025-05-02', '22:03:00', 5, 3333, 0, NULL, NULL, NULL, NULL, NULL, 0, '[]', 333, 3, 333, 33, 33, 33, 'dsfsadg', '2025-04-22 16:44:07');

-- --------------------------------------------------------

--
-- Table structure for table `hotels`
--

CREATE TABLE `hotels` (
  `id` int(11) NOT NULL,
  `hotel_name` varchar(100) NOT NULL,
  `location` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `rating` int(1) NOT NULL DEFAULT 5,
  `description` text NOT NULL,
  `room_count` int(2) NOT NULL,
  `amenities` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hotels`
--

INSERT INTO `hotels` (`id`, `hotel_name`, `location`, `price`, `rating`, `description`, `room_count`, `amenities`, `created_at`, `updated_at`) VALUES
(4, 'wow', 'madinah', 800.00, 5, 'testingg', 5, 'wifi,pool,spa', '2025-04-22 17:24:06', '2025-04-22 17:24:06'),
(5, 'sdafsf', 'makkah', 559.00, 4, 'testing ', 3, 'wifi,parking,restaurant,gym,pool,ac,room_service,spa', '2025-04-22 17:24:43', '2025-04-22 17:36:41');

-- --------------------------------------------------------

--
-- Table structure for table `hotel_bookings`
--

CREATE TABLE `hotel_bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `hotel_id` int(11) NOT NULL,
  `room_id` varchar(10) NOT NULL,
  `check_in_date` date NOT NULL,
  `check_out_date` date NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `booking_status` enum('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'pending',
  `payment_status` enum('unpaid','paid','refunded') NOT NULL DEFAULT 'unpaid',
  `booking_reference` varchar(20) NOT NULL,
  `special_requests` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hotel_images`
--

CREATE TABLE `hotel_images` (
  `id` int(11) NOT NULL,
  `hotel_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hotel_images`
--

INSERT INTO `hotel_images` (`id`, `hotel_id`, `image_path`, `is_primary`, `created_at`) VALUES
(10, 4, 'uploads/hotels/4/hotel_6807d0b6eccb6.jpeg', 1, '2025-04-22 17:24:06'),
(11, 4, 'uploads/hotels/4/hotel_6807d0b6ed270.jpg', 0, '2025-04-22 17:24:06'),
(12, 4, 'uploads/hotels/4/hotel_6807d0b6ed88a.jpg', 0, '2025-04-22 17:24:06'),
(13, 5, 'uploads/hotels/5/hotel_6807d0db1740c.jpg', 1, '2025-04-22 17:24:43'),
(14, 5, 'uploads/hotels/5/hotel_6807d2ea1665c.jpeg', 0, '2025-04-22 17:33:30'),
(15, 5, 'uploads/hotels/5/hotel_6807d2ea16b1c.jpg', 0, '2025-04-22 17:33:30'),
(16, 5, 'uploads/hotels/5/hotel_6807d3a9e1a8e.jpeg', 0, '2025-04-22 17:36:41'),
(17, 5, 'uploads/hotels/5/hotel_6807d3a9e1f29.jpg', 0, '2025-04-22 17:36:41');

-- --------------------------------------------------------

--
-- Table structure for table `hotel_reviews`
--

CREATE TABLE `hotel_reviews` (
  `id` int(11) NOT NULL,
  `hotel_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `rating` int(1) NOT NULL,
  `review_text` text DEFAULT NULL,
  `review_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_approved` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hotel_rooms`
--

CREATE TABLE `hotel_rooms` (
  `id` int(11) NOT NULL,
  `hotel_id` int(11) NOT NULL,
  `room_id` varchar(10) NOT NULL,
  `status` enum('available','booked','maintenance') NOT NULL DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hotel_rooms`
--

INSERT INTO `hotel_rooms` (`id`, `hotel_id`, `room_id`, `status`, `created_at`, `updated_at`) VALUES
(13, 4, 'r1', 'available', '2025-04-22 17:24:06', '2025-04-22 17:24:06'),
(14, 4, 'r2', 'available', '2025-04-22 17:24:06', '2025-04-22 17:24:06'),
(15, 4, 'r3', 'available', '2025-04-22 17:24:06', '2025-04-22 17:24:06'),
(16, 4, 'r4', 'available', '2025-04-22 17:24:06', '2025-04-22 17:24:06'),
(17, 4, 'r5', 'available', '2025-04-22 17:24:06', '2025-04-22 17:24:06'),
(18, 5, 'r1', 'available', '2025-04-22 17:24:43', '2025-04-22 17:24:43'),
(19, 5, 'r2', 'available', '2025-04-22 17:24:43', '2025-04-22 17:24:43');

-- --------------------------------------------------------

--
-- Table structure for table `umrah_packages`
--

CREATE TABLE `umrah_packages` (
  `id` int(11) NOT NULL,
  `package_type` enum('single','group','vip') NOT NULL,
  `title` varchar(35) NOT NULL,
  `description` text NOT NULL,
  `flight_class` enum('economy','business','first') NOT NULL,
  `inclusions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`inclusions`)),
  `price` decimal(10,2) NOT NULL CHECK (`price` >= 0 and `price` <= 500000),
  `package_image` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `umrah_packages`
--

INSERT INTO `umrah_packages` (`id`, `package_type`, `title`, `description`, `flight_class`, `inclusions`, `price`, `package_image`, `created_at`) VALUES
(2, 'vip', 'test', 'dsfasdgfsd', 'first', '[\"flight\",\"hotel\",\"transport\",\"guide\"]', 500000.00, 'Uploads/pkg_6807d9c8406fc.jpeg', '2025-04-22 18:02:48'),
(3, 'single', 'test', 'dsfasdg', 'economy', '[\"flight\",\"hotel\"]', 213214.00, 'Uploads/pkg_6807de94bb935.jpeg', '2025-04-22 18:12:42');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `dob` date NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `dob`, `profile_image`, `created_at`) VALUES
(1, 'test', 'test@test.com', '$2y$10$dMY.YTQ4ejm0foQzMAJLcOTvYwYB0XQxskVMqQrzBMM93UU9sP.MK', '2025-04-16', '', '2025-04-22 20:43:14'),
(2, 'testing', 'testing@testing.com', '$2y$10$fiCbyziBeL/d8972s5avLe0qMVcJuQjOyjl/OV58WR9f6G9ZjQq7G', '2025-04-24', '', '2025-04-22 20:55:26');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `flights`
--
ALTER TABLE `flights`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hotels`
--
ALTER TABLE `hotels`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_hotels_location` (`location`),
  ADD KEY `idx_hotels_rating` (`rating`);

--
-- Indexes for table `hotel_bookings`
--
ALTER TABLE `hotel_bookings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `booking_reference_unique` (`booking_reference`),
  ADD KEY `hotel_id` (`hotel_id`),
  ADD KEY `idx_hotel_bookings_dates` (`check_in_date`,`check_out_date`),
  ADD KEY `idx_hotel_bookings_status` (`booking_status`);

--
-- Indexes for table `hotel_images`
--
ALTER TABLE `hotel_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `hotel_id` (`hotel_id`);

--
-- Indexes for table `hotel_reviews`
--
ALTER TABLE `hotel_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `hotel_id` (`hotel_id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `hotel_rooms`
--
ALTER TABLE `hotel_rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `hotel_room_unique` (`hotel_id`,`room_id`),
  ADD KEY `idx_hotel_rooms_status` (`status`);

--
-- Indexes for table `umrah_packages`
--
ALTER TABLE `umrah_packages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `flights`
--
ALTER TABLE `flights`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `hotels`
--
ALTER TABLE `hotels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `hotel_bookings`
--
ALTER TABLE `hotel_bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hotel_images`
--
ALTER TABLE `hotel_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `hotel_reviews`
--
ALTER TABLE `hotel_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hotel_rooms`
--
ALTER TABLE `hotel_rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `umrah_packages`
--
ALTER TABLE `umrah_packages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `hotel_bookings`
--
ALTER TABLE `hotel_bookings`
  ADD CONSTRAINT `hotel_bookings_ibfk_1` FOREIGN KEY (`hotel_id`) REFERENCES `hotels` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `hotel_images`
--
ALTER TABLE `hotel_images`
  ADD CONSTRAINT `hotel_images_ibfk_1` FOREIGN KEY (`hotel_id`) REFERENCES `hotels` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `hotel_reviews`
--
ALTER TABLE `hotel_reviews`
  ADD CONSTRAINT `hotel_reviews_ibfk_1` FOREIGN KEY (`hotel_id`) REFERENCES `hotels` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `hotel_reviews_ibfk_2` FOREIGN KEY (`booking_id`) REFERENCES `hotel_bookings` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `hotel_rooms`
--
ALTER TABLE `hotel_rooms`
  ADD CONSTRAINT `hotel_rooms_ibfk_1` FOREIGN KEY (`hotel_id`) REFERENCES `hotels` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
