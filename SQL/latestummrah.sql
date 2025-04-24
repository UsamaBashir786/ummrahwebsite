-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 24, 2025 at 02:21 PM
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
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `status` enum('unread','read','replied','archived') NOT NULL DEFAULT 'unread',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `admin_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

-- --------------------------------------------------------

--
-- Table structure for table `flight_bookings`
--

CREATE TABLE `flight_bookings` (
  `id` int(11) NOT NULL,
  `flight_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `cabin_class` varchar(20) NOT NULL,
  `adult_count` int(11) NOT NULL DEFAULT 1,
  `children_count` int(11) NOT NULL DEFAULT 0,
  `total_price` decimal(10,2) NOT NULL,
  `passenger_name` varchar(100) NOT NULL,
  `passenger_email` varchar(100) NOT NULL,
  `passenger_phone` varchar(20) NOT NULL,
  `booking_status` enum('confirmed','pending','cancelled') NOT NULL DEFAULT 'pending',
  `payment_status` enum('pending','completed','failed') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `room_count` int(4) NOT NULL,
  `amenities` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `type` enum('new_booking','payment_update','cancellation','booking_accepted','new_user') NOT NULL,
  `message` text NOT NULL,
  `related_table` varchar(50) DEFAULT NULL,
  `related_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `package_bookings`
--

CREATE TABLE `package_bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `travel_date` date NOT NULL,
  `num_travelers` int(11) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `booking_status` enum('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'pending',
  `payment_status` enum('pending','paid','refunded') NOT NULL DEFAULT 'pending',
  `booking_reference` varchar(20) NOT NULL,
  `special_requests` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rentacar_routes`
--

CREATE TABLE `rentacar_routes` (
  `id` int(11) NOT NULL,
  `service_title` varchar(255) NOT NULL,
  `year` int(11) NOT NULL,
  `route_number` int(11) NOT NULL,
  `route_name` varchar(255) NOT NULL,
  `gmc_16_19_price` decimal(10,2) NOT NULL,
  `gmc_22_23_price` decimal(10,2) NOT NULL,
  `coaster_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `taxi_routes`
--

CREATE TABLE `taxi_routes` (
  `id` int(11) NOT NULL,
  `service_title` varchar(255) NOT NULL,
  `year` int(11) NOT NULL,
  `route_number` int(11) NOT NULL,
  `route_name` varchar(255) NOT NULL,
  `camry_sonata_price` decimal(10,2) NOT NULL,
  `starex_staria_price` decimal(10,2) NOT NULL,
  `hiace_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transportation_bookings`
--

CREATE TABLE `transportation_bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `transport_type` varchar(50) NOT NULL,
  `route_id` int(11) NOT NULL,
  `route_name` varchar(255) NOT NULL,
  `vehicle_type` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `pickup_date` date NOT NULL,
  `pickup_time` time NOT NULL,
  `pickup_location` varchar(255) NOT NULL,
  `additional_notes` text DEFAULT NULL,
  `booking_status` enum('pending','confirmed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transportation_settings`
--

CREATE TABLE `transportation_settings` (
  `id` int(11) NOT NULL,
  `service_type` varchar(20) NOT NULL,
  `service_title` varchar(255) NOT NULL,
  `year` int(11) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `dob` date NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `flights`
--
ALTER TABLE `flights`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `flight_bookings`
--
ALTER TABLE `flight_bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `flight_bookings_ibfk_2` (`user_id`),
  ADD KEY `fk_flight_bookings_flight_id` (`flight_id`);

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
  ADD KEY `idx_hotel_bookings_status` (`booking_status`),
  ADD KEY `fk_hotel_bookings_user` (`user_id`);

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
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_notifications_user` (`user_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `package_bookings`
--
ALTER TABLE `package_bookings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `booking_reference_unique` (`booking_reference`),
  ADD KEY `package_id` (`package_id`),
  ADD KEY `package_bookings_ibfk_2` (`user_id`);

--
-- Indexes for table `rentacar_routes`
--
ALTER TABLE `rentacar_routes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `taxi_routes`
--
ALTER TABLE `taxi_routes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transportation_bookings`
--
ALTER TABLE `transportation_bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transportation_bookings_ibfk_2` (`user_id`);

--
-- Indexes for table `transportation_settings`
--
ALTER TABLE `transportation_settings`
  ADD PRIMARY KEY (`id`);

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
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `flights`
--
ALTER TABLE `flights`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `flight_bookings`
--
ALTER TABLE `flight_bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `hotels`
--
ALTER TABLE `hotels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `hotel_bookings`
--
ALTER TABLE `hotel_bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `hotel_images`
--
ALTER TABLE `hotel_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `hotel_reviews`
--
ALTER TABLE `hotel_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hotel_rooms`
--
ALTER TABLE `hotel_rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `package_bookings`
--
ALTER TABLE `package_bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `rentacar_routes`
--
ALTER TABLE `rentacar_routes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `taxi_routes`
--
ALTER TABLE `taxi_routes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `transportation_bookings`
--
ALTER TABLE `transportation_bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `transportation_settings`
--
ALTER TABLE `transportation_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `umrah_packages`
--
ALTER TABLE `umrah_packages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `flight_bookings`
--
ALTER TABLE `flight_bookings`
  ADD CONSTRAINT `fk_flight_bookings_flight_id` FOREIGN KEY (`flight_id`) REFERENCES `flights` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `flight_bookings_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `hotel_bookings`
--
ALTER TABLE `hotel_bookings`
  ADD CONSTRAINT `fk_hotel_bookings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
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

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `package_bookings`
--
ALTER TABLE `package_bookings`
  ADD CONSTRAINT `package_bookings_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `umrah_packages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `package_bookings_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transportation_bookings`
--
ALTER TABLE `transportation_bookings`
  ADD CONSTRAINT `transportation_bookings_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
