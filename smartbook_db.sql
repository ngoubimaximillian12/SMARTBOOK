-- ngoubi@gmail.com
-- abcabc
-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 14, 2025 at 08:43 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `smartbook_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `booking_start` datetime NOT NULL,
  `booking_end` datetime NOT NULL,
  `hours` int(11) NOT NULL,
  `total_amount` decimal(11,0) NOT NULL,
  `status` enum('pending','confirmed','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`booking_id`, `customer_id`, `service_id`, `booking_start`, `booking_end`, `hours`, `total_amount`, `status`, `created_at`, `updated_at`) VALUES
(10, 7, 7, '2025-03-20 10:00:00', '2025-03-20 13:00:00', 3, 45, 'completed', '2025-02-14 19:36:08', '2025-02-14 19:38:51');

-- --------------------------------------------------------

--
-- Table structure for table `loyalty_points`
--

CREATE TABLE `loyalty_points` (
  `transaction_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `points` int(11) NOT NULL,
  `updated_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loyalty_points`
--

INSERT INTO `loyalty_points` (`transaction_id`, `user_id`, `points`, `updated_date`) VALUES
(3, 7, 1, '2025-02-14 19:38:51');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL CHECK (`rating` between 1 and 5),
  `review_text` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`review_id`, `booking_id`, `customer_id`, `rating`, `review_text`, `created_at`, `updated_at`) VALUES
(4, 10, 7, 5, 'Great service and was able to complete what we needed to get approved on the App Store.', '2025-02-14 19:41:02', '2025-02-14 19:41:02');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `service_id` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `service_name` varchar(100) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `working_days` char(7) NOT NULL DEFAULT '1111111',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`service_id`, `provider_id`, `service_name`, `category`, `description`, `icon`, `price`, `working_days`, `created_at`, `updated_at`) VALUES
(6, 6, 'I will offer website design and web application development', 'Digital Marketing', 'As an experience individual in website design & development, I am here to provide you with an exceptional online presence that will set you apart from the competition.', 'uploads/1739561057_Screenshot 2025-02-15 002357.png', 10.00, '1111111', '2025-02-14 19:24:17', '2025-02-14 19:24:17'),
(7, 6, 'I will do mobile or web app development', 'Digital Marketing', 'Need Web or Mobile App Development? Look no further! We\'re Marrix Power seasoned professionals specializing in high-quality Web and both Android and iOS apps, along with web-based admin panels. Let\'s bring your vision to life!', 'uploads/1739561203_Screenshot 2025-02-15 002628.png', 15.00, '1111111', '2025-02-14 19:26:43', '2025-02-14 19:26:43');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('customer','provider','admin') DEFAULT 'customer',
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `loyalty_points` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `password_hash`, `role`, `phone`, `address`, `profile_picture`, `loyalty_points`, `created_at`, `updated_at`) VALUES
(6, 'Ngoubi Maximillian Diangha', 'ngoubi@gmail.com', '$2y$10$oIn.sGvWxlqPGD15tRJHS.8qwDpD0n781rY60wnthzU70e5NByAsy', 'provider', '012345667', 'UK', 'uploads/1739560854_istockphoto-1310533180-612x612.jpg', 0, '2025-02-14 19:20:54', '2025-02-14 19:20:54'),
(7, 'Naresh Naik Khethavath', 'naresh@gmail.com', '$2y$10$tjhlcBzKt40r4TGhaVug2OaTUNTOXcqP2iOlcWmLRMswvwGCZu.c6', 'customer', '1234567', 'UK', 'uploads/1739561719_images-naresh.jpg', 0, '2025-02-14 19:35:19', '2025-02-14 19:42:31');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `fk_booking_customer` (`customer_id`),
  ADD KEY `fk_booking_service` (`service_id`);

--
-- Indexes for table `loyalty_points`
--
ALTER TABLE `loyalty_points`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `fk_loyalty_user` (`user_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `fk_review_booking` (`booking_id`),
  ADD KEY `fk_review_customer` (`customer_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`service_id`),
  ADD KEY `fk_provider_service` (`provider_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `loyalty_points`
--
ALTER TABLE `loyalty_points`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `service_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `fk_booking_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_booking_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`) ON DELETE CASCADE;

--
-- Constraints for table `loyalty_points`
--
ALTER TABLE `loyalty_points`
  ADD CONSTRAINT `fk_loyalty_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_review_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_review_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `services`
--
ALTER TABLE `services`
  ADD CONSTRAINT `fk_provider_service` FOREIGN KEY (`provider_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
