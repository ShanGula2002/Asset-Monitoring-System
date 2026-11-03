-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 26, 2026 at 04:22 AM
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
-- Database: `school_assets`
--

-- --------------------------------------------------------

--
-- Table structure for table `assets`
--

CREATE TABLE `assets` (
  `asset_id` int(11) NOT NULL,
  `asset_tag` varchar(50) NOT NULL,
  `asset_name` varchar(100) NOT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `date_acquired` date DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `room_id` int(11) DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_available` tinyint(1) DEFAULT 1,
  `status` varchar(50) DEFAULT '',
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assets`
--

INSERT INTO `assets` (`asset_id`, `asset_tag`, `asset_name`, `brand`, `serial_number`, `date_acquired`, `category`, `room_id`, `last_updated`, `is_available`, `status`, `latitude`, `longitude`) VALUES
(15, 'Laptop 1111', 'Laptop', 'Acer', 'XKAHJSODJHI3324454353', '2024-01-01', 'Electronics', 24, '2026-08-26 01:56:07', 1, '', 10.53830300, 122.83973300),
(16, 'Laptop 1100', 'Laptop', 'Dell', 'XXASD76767565676', '2025-01-08', 'Electronics', 24, '2026-03-23 15:11:56', 1, '', 10.53815000, 122.83986800),
(18, 'TV 0001', 'Adriod TV', 'Samsung', 'XFDSDF2454543', '2023-06-23', 'Electronics', 30, '2026-03-26 02:08:08', 1, '', 10.53841400, 122.84008200);

-- --------------------------------------------------------

--
-- Table structure for table `borrowing_logs`
--

CREATE TABLE `borrowing_logs` (
  `borrow_id` int(11) NOT NULL,
  `asset_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `borrow_date` datetime DEFAULT current_timestamp(),
  `expected_return_date` datetime NOT NULL,
  `actual_return_date` datetime DEFAULT NULL,
  `borrow_status` enum('active','returned','overdue') DEFAULT 'active',
  `borrowed_room_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `borrowing_logs`
--

INSERT INTO `borrowing_logs` (`borrow_id`, `asset_id`, `user_id`, `borrow_date`, `expected_return_date`, `actual_return_date`, `borrow_status`, `borrowed_room_id`) VALUES
(36, 15, 4, '2026-07-20 02:15:53', '2026-07-21 02:15:00', '2026-07-20 02:16:10', 'returned', 0),
(37, 15, 4, '2026-07-27 19:39:58', '2026-07-28 19:39:00', '2026-07-27 19:40:56', 'returned', 0),
(38, 15, 4, '2026-08-26 09:09:35', '2026-08-26 10:10:00', '2026-08-26 09:16:57', 'returned', 0),
(39, 15, 4, '2026-08-26 09:18:46', '2026-08-26 09:18:00', '2026-08-26 09:24:51', 'returned', 0),
(40, 15, 4, '2026-08-26 09:25:37', '2026-08-27 09:25:00', '2026-08-26 09:56:07', 'returned', 26);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `room_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `type` varchar(50) NOT NULL COMMENT 'security_alert, overdue_alert, system',
  `is_read` tinyint(1) DEFAULT 0 COMMENT '0 = unread, 1 = read',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `room_id`, `message`, `type`, `is_read`, `created_at`) VALUES
(1, 2, NULL, 'LOCATION MISMATCH: User ID 2 scanned Room ID 24 for Asset TV 0001, but it should be in Room ID 30.', 'security_alert', 1, '2026-03-23 14:03:05'),
(7, 2, NULL, 'LOCATION MISMATCH: User ID 2 scanned Room ID 25 for Asset Laptop 1100, but it should be in Room ID 24.', 'security_alert', 1, '2026-03-23 16:14:23'),
(8, 2, NULL, 'LOCATION MISMATCH: User ID 2 scanned Room ID 25 for Asset Laptop 1100, but it should be in Room ID 24.', 'security_alert', 1, '2026-03-23 16:14:31'),
(9, 2, NULL, 'SECURITY ALERT: User attempted to borrow \'Adriod TV\' (TV 0001) from Room ID 24. Expected Room ID was 30.', 'security_alert', 1, '2026-03-23 16:37:38'),
(10, 2, NULL, 'SECURITY ALERT: User attempted to borrow \'Adriod TV\' (TV 0001) from Room ID 24. Expected Room ID was 30.', 'security_alert', 1, '2026-03-23 16:42:22'),
(11, 2, NULL, 'SECURITY ALERT: User attempted to borrow \'Adriod TV\' (TV 0001) from Room ID 24. Expected Room ID was 30.', 'security_alert', 1, '2026-03-23 16:53:04'),
(12, 2, NULL, 'SECURITY ALERT: User attempted to borrow \'Laptop\' (Laptop 1111) from Room ID 25. Expected Room ID was 24.', 'security_alert', 1, '2026-03-23 19:05:36'),
(13, 2, NULL, 'SECURITY ALERT: User attempted to borrow \'Laptop\' (Laptop 1111) from Room ID 25. Expected Room ID was 24.', 'security_alert', 1, '2026-03-23 21:09:24'),
(14, 4, NULL, 'SECURITY ALERT: User attempted to borrow \'Laptop\' (Laptop 1100) from Room ID 25. Expected Room ID was 24.', 'security_alert', 1, '2026-03-23 23:02:17'),
(15, 4, NULL, 'SECURITY ALERT: User attempted to borrow \'Adriod TV\' (TV 0001) in Room ID 24, but it belongs in \'Room 5\'.', 'security_alert', 1, '2026-03-25 15:25:14'),
(16, 4, 24, 'SECURITY ALERT: User attempted to borrow \'Adriod TV\' (TV 0001) in Room ID 24, but it belongs in \'Room 5\'.', 'security_alert', 1, '2026-03-25 18:11:20'),
(17, 4, 25, 'SECURITY ALERT: User attempted to borrow \'Laptop\' (Laptop 1111) in Room ID 25, but it belongs in \'Room 1\'.', 'security_alert', 1, '2026-03-25 18:22:58'),
(18, 4, 30, 'MISSING ALERT: Shan reported that \'Adriod TV\' (TV 0001) is missing from its assigned room.', 'security_alert', 1, '2026-03-26 10:00:00'),
(19, 4, NULL, 'MISSING REPORT: \'Adriod TV\' was reported missing from Room 5 by Shan.', 'danger', 1, '2026-03-26 10:07:09'),
(20, 4, 24, 'MISSING ALERT: Shan reported that \'Laptop\' (Laptop 1111) is missing from its assigned room.', 'security_alert', 1, '2026-03-26 11:38:26'),
(21, 4, 24, 'MISSING ALERT: Shan reported that \'Laptop\' (Laptop 1111) is missing from its assigned room.', 'security_alert', 1, '2026-07-27 19:43:08'),
(22, 4, 24, 'MISSING ALERT: Shan reported that \'Laptop\' (Laptop 1111) is missing from its assigned room.', 'security_alert', 1, '2026-07-27 19:49:58');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `room_id` int(11) NOT NULL,
  `room_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `room_image` varchar(255) DEFAULT 'default_room.jpg',
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `allowed_radius` int(11) DEFAULT 20
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`room_id`, `room_name`, `description`, `room_image`, `latitude`, `longitude`, `allowed_radius`) VALUES
(24, 'Room 1', '', '1784480663_747086655_122187249884706972_5639270411381745208_n.jpg', 10.53830300, 122.83973300, 10),
(25, 'Room 2', '', 'default_room.jpg', 10.53814000, 122.83988900, 10),
(26, 'Room 3', '', 'default_room.jpg', 10.53797600, 122.84006000, 10),
(27, 'Room 4', '', 'default_room.jpg', 10.53783400, 122.84023800, 10),
(30, 'Room 5', '', 'default_room.jpg', 10.53841900, 122.84008200, 10);

-- --------------------------------------------------------

--
-- Table structure for table `tracking_logs`
--

CREATE TABLE `tracking_logs` (
  `log_id` int(11) NOT NULL,
  `asset_id` int(11) DEFAULT NULL,
  `room_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `scan_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(50) DEFAULT 'Scanned',
  `note` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tracking_logs`
--

INSERT INTO `tracking_logs` (`log_id`, `asset_id`, `room_id`, `user_id`, `scan_time`, `status`, `note`) VALUES
(24, 18, 30, 4, '2026-03-23 15:00:15', 'Scanned', ''),
(25, 16, 24, 4, '2026-03-23 15:02:55', 'Scanned', ''),
(26, 18, 30, 4, '2026-03-23 15:24:47', 'Scanned', ''),
(27, 18, 30, 4, '2026-03-25 07:25:38', 'Scanned', ''),
(28, 18, 30, 4, '2026-03-25 07:26:35', 'Issue Reported: need repair', ''),
(29, 18, 30, 4, '2026-03-25 10:06:16', 'Issue Reported: need repair', ''),
(30, 15, 24, 4, '2026-03-25 10:23:49', 'Scanned', ''),
(31, 15, 24, 4, '2026-07-19 17:08:40', 'Scanned', ''),
(32, 15, 24, 4, '2026-07-19 17:35:35', 'Scanned', ''),
(33, 15, 24, 4, '2026-07-19 17:49:49', 'Scanned', ''),
(34, 15, 24, 4, '2026-07-19 18:00:10', 'Scanned', ''),
(35, 15, 30, 4, '2026-07-19 18:00:30', 'Scanned', ''),
(36, 15, 24, 4, '2026-07-19 18:13:11', 'Scanned', ''),
(37, 15, 24, 4, '2026-07-19 18:13:30', 'Scanned', ''),
(38, 15, 24, 4, '2026-07-19 18:15:53', 'Scanned', ''),
(39, 15, 24, 4, '2026-07-19 18:16:10', 'Scanned', ''),
(40, 15, 24, 4, '2026-07-27 11:39:58', 'Scanned', ''),
(41, 15, 24, 4, '2026-07-27 11:40:56', 'Scanned', ''),
(42, 15, 24, 4, '2026-08-26 01:09:35', 'Scanned', ''),
(43, 15, 24, 4, '2026-08-26 01:16:57', 'Scanned', ''),
(44, 15, 24, 4, '2026-08-26 01:18:46', 'Scanned', ''),
(45, 15, 24, 4, '2026-08-26 01:24:51', 'Scanned', ''),
(46, 15, 24, 4, '2026-08-26 01:25:37', 'Scanned', ''),
(47, 15, 24, 4, '2026-08-26 01:56:07', 'Scanned', '');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','employee') NOT NULL,
  `full_name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `role`, `full_name`) VALUES
(4, 'employee', '$2y$10$T54aSCWZY3D3zeGs4r8XT.roa8lUBPOT5CCC47EhXT6w4lFgF8wwi', 'employee', 'Shan'),
(5, 'admin', '$2y$10$CpO//WkVjW.QZKmk/LIPXu119uBdc9ucAaGpO0W3/sLEmgkxuuu0q', 'admin', 'shan');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assets`
--
ALTER TABLE `assets`
  ADD PRIMARY KEY (`asset_id`),
  ADD UNIQUE KEY `asset_tag` (`asset_tag`),
  ADD KEY `assets_ibfk_1` (`room_id`);

--
-- Indexes for table `borrowing_logs`
--
ALTER TABLE `borrowing_logs`
  ADD PRIMARY KEY (`borrow_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `borrowing_logs_ibfk_1` (`asset_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`room_id`),
  ADD UNIQUE KEY `room_name` (`room_name`);

--
-- Indexes for table `tracking_logs`
--
ALTER TABLE `tracking_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `scanned_by` (`user_id`),
  ADD KEY `tracking_logs_ibfk_1` (`asset_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assets`
--
ALTER TABLE `assets`
  MODIFY `asset_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `borrowing_logs`
--
ALTER TABLE `borrowing_logs`
  MODIFY `borrow_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `tracking_logs`
--
ALTER TABLE `tracking_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assets`
--
ALTER TABLE `assets`
  ADD CONSTRAINT `assets_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `borrowing_logs`
--
ALTER TABLE `borrowing_logs`
  ADD CONSTRAINT `borrowing_logs_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`asset_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `borrowing_logs_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `tracking_logs`
--
ALTER TABLE `tracking_logs`
  ADD CONSTRAINT `tracking_logs_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`asset_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tracking_logs_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`),
  ADD CONSTRAINT `tracking_logs_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
