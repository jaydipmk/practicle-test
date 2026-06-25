-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 25, 2026 at 09:47 PM
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
-- Database: `incident_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `target_type` varchar(50) NOT NULL,
  `target_id` int(10) UNSIGNED DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `target_type`, `target_id`, `details`, `ip_address`, `created_at`) VALUES
(1, 1, 'User Registered', 'user', 1, '{\"email\":\"test@gmail.com\"}', '::1', '2026-06-25 22:53:02'),
(2, 1, 'User Login', 'user', 1, '{\"email\":\"test@gmail.com\"}', '::1', '2026-06-25 22:53:35'),
(3, 1, 'Created Incident', 'incident', 1, '{\"title\":\"test\"}', '::1', '2026-06-25 22:54:22'),
(4, 1, 'User Logout', 'user', 1, NULL, '::1', '2026-06-25 22:55:21'),
(5, 2, 'User Login', 'user', 2, '{\"email\":\"superadmin@gmail.com\"}', '::1', '2026-06-25 23:56:39'),
(6, 2, 'Exported CSV', 'incident', NULL, '{\"count\":1}', '::1', '2026-06-25 23:58:13'),
(7, 2, 'Exported PDF', 'incident', NULL, '{\"count\":1}', '::1', '2026-06-25 23:58:15'),
(8, 2, 'User Logout', 'user', 2, NULL, '::1', '2026-06-25 23:58:55'),
(9, 3, 'User Login', 'user', 3, '{\"email\":\"admin@system.com\"}', '::1', '2026-06-25 23:59:31'),
(10, 3, 'User Logout', 'user', 3, NULL, '::1', '2026-06-26 00:18:49'),
(11, 4, 'User Login', 'user', 4, NULL, '::1', '2026-06-26 00:19:11'),
(12, 4, 'Created Incident', 'incident', 2, '{\"title\":\"test\"}', '::1', '2026-06-26 00:20:00'),
(13, 4, 'User Logout', 'user', 4, NULL, '::1', '2026-06-26 00:20:23'),
(14, 2, 'User Login', 'user', 2, NULL, '::1', '2026-06-26 00:21:18'),
(15, 2, 'User Logout', 'user', 2, NULL, '::1', '2026-06-26 00:23:28'),
(16, 3, 'User Login', 'user', 3, NULL, '::1', '2026-06-26 00:23:43'),
(17, 3, 'Updated Incident', 'incident', 2, '{\"status\":\"Resolved\",\"priority\":\"Low\",\"category\":\"Phishing\"}', '::1', '2026-06-26 00:37:10'),
(18, 3, 'Updated Incident', 'incident', 2, '{\"status\":\"In Progress\",\"priority\":\"Low\",\"category\":\"Phishing\"}', '::1', '2026-06-26 00:45:12'),
(19, 3, 'Assigned Incident', 'incident', 1, '{\"assigned_to\":3}', '::1', '2026-06-26 00:45:52'),
(20, 3, 'Assigned Incident', 'incident', 2, '{\"assigned_to\":2}', '::1', '2026-06-26 00:45:59'),
(21, 3, 'Unassigned Incident', 'incident', 1, NULL, '::1', '2026-06-26 00:46:48'),
(22, 3, 'Assigned Incident', 'incident', 1, '{\"assigned_to\":3}', '::1', '2026-06-26 00:47:01'),
(23, 3, 'User Logout', 'user', 3, NULL, '::1', '2026-06-26 00:47:52'),
(24, 2, 'User Login', 'user', 2, NULL, '::1', '2026-06-26 00:48:08'),
(25, 2, 'User Logout', 'user', 2, NULL, '::1', '2026-06-26 00:49:06'),
(26, 4, 'User Login', 'user', 4, NULL, '::1', '2026-06-26 00:49:22');

-- --------------------------------------------------------

--
-- Table structure for table `incidents`
--

CREATE TABLE `incidents` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `assigned_to` int(10) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `category` enum('Phishing','Malware','Ransomware','Unauthorized Access','DDoS','Data Breach','Social Engineering','Other') NOT NULL,
  `priority` enum('Low','Medium','High') NOT NULL DEFAULT 'Medium',
  `status` enum('Open','In Progress','Resolved') NOT NULL DEFAULT 'Open',
  `evidence_path` varchar(500) DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `incidents`
--

INSERT INTO `incidents` (`id`, `user_id`, `assigned_to`, `title`, `description`, `category`, `priority`, `status`, `evidence_path`, `resolved_at`, `created_at`, `updated_at`) VALUES
(1, 1, 3, 'test', 'test', 'Phishing', 'Low', 'Open', 'evidence_1782408262_1.pdf', NULL, '2026-06-25 22:54:22', '2026-06-26 00:47:01'),
(2, 4, 2, 'test', 'test', 'Phishing', 'Low', 'In Progress', 'evidence_1782413400_4.pdf', NULL, '2026-06-26 00:20:00', '2026-06-26 00:45:59');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `incident_id` int(10) UNSIGNED NOT NULL,
  `message` varchar(500) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `incident_id`, `message`, `is_read`, `created_at`) VALUES
(1, 4, 2, 'Your incident status has been updated to: Resolved', 1, '2026-06-26 00:37:10'),
(2, 4, 2, 'Your incident status has been updated to: In Progress', 1, '2026-06-26 00:45:12');

-- --------------------------------------------------------

--
-- Table structure for table `refresh_tokens`
--

CREATE TABLE `refresh_tokens` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `token` varchar(500) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `refresh_tokens`
--

INSERT INTO `refresh_tokens` (`id`, `user_id`, `token`, `expires_at`, `created_at`) VALUES
(3, 3, '56dfff2fb242882d14ce2cc2311575b3ecd70bfe00851bbced312de275f2d406', '2026-07-02 20:29:31', '2026-06-25 23:59:31');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin','superadmin') NOT NULL DEFAULT 'user',
  `is_blocked` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `is_blocked`, `created_at`, `updated_at`) VALUES
(1, 'Makwana Jaydip', 'test@gmail.com', '$2y$10$ixuDJwCTwcKuQfBEE8rGdeVqigl6kj4w9wHVphHo2euwmTt5SyyRy', 'user', 0, '2026-06-25 22:53:02', '2026-06-25 22:53:02'),
(2, 'Super Admin', 'superadmin@system.com', '$2y$10$ly3LIuR7ehF5KoDipsnVFeJ2biueLaVaW44l1UQkkF8pZyiicfCoi', 'superadmin', 0, '2026-06-25 23:02:44', '2026-06-26 00:21:09'),
(3, 'Demo Admin', 'admin@system.com', '$2y$10$ly3LIuR7ehF5KoDipsnVFeJ2biueLaVaW44l1UQkkF8pZyiicfCoi', 'admin', 0, '2026-06-25 23:02:44', '2026-06-25 23:57:16'),
(4, 'Demo User', 'user@system.com', '$2y$10$ly3LIuR7ehF5KoDipsnVFeJ2biueLaVaW44l1UQkkF8pZyiicfCoi', 'user', 0, '2026-06-25 23:02:44', '2026-06-25 23:57:22');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_target` (`target_type`,`target_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `incidents`
--
ALTER TABLE `incidents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assigned_to` (`assigned_to`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `incident_id` (`incident_id`),
  ADD KEY `idx_user_read` (`user_id`,`is_read`);

--
-- Indexes for table `refresh_tokens`
--
ALTER TABLE `refresh_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_token` (`token`(100));

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
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `incidents`
--
ALTER TABLE `incidents`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `refresh_tokens`
--
ALTER TABLE `refresh_tokens`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `incidents`
--
ALTER TABLE `incidents`
  ADD CONSTRAINT `incidents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `incidents_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`incident_id`) REFERENCES `incidents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `refresh_tokens`
--
ALTER TABLE `refresh_tokens`
  ADD CONSTRAINT `refresh_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
