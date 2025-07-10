-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 09, 2025 at 11:11 AM
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
-- Database: `data_repository`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `adminid` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `user_id`, `adminid`, `created_at`) VALUES
(1, 2, 'ADM001', '2025-07-01 07:15:30'),
(2, 3, 'ADM_6864ed58bcd9d', '2025-07-02 08:27:04');

-- --------------------------------------------------------

--
-- Table structure for table `admin_actions_log`
--

CREATE TABLE `admin_actions_log` (
  `id` int(11) NOT NULL,
  `admin_user_id` int(11) NOT NULL,
  `action_type` varchar(100) NOT NULL,
  `target_entity_type` varchar(50) DEFAULT NULL,
  `target_entity_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `action_timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_actions_log`
--

INSERT INTO `admin_actions_log` (`id`, `admin_user_id`, `action_type`, `target_entity_type`, `target_entity_id`, `details`, `action_timestamp`) VALUES
(1, 2, 'approve_user', '0', 3, '{\"approved_user_id\":3}', '2025-07-01 07:22:40'),
(2, 2, 'approve_user', '0', 4, '{\"approved_user_id\":4}', '2025-07-01 07:31:14'),
(3, 2, 'approve_dataset', '0', 1, '{\"approved_dataset_id\":1}', '2025-07-01 08:57:51'),
(4, 2, 'approve_dataset', '0', 2, '{\"approved_dataset_id\":2}', '2025-07-02 05:46:30');

-- --------------------------------------------------------

--
-- Table structure for table `allowed_institutions`
--

CREATE TABLE `allowed_institutions` (
  `id` int(11) NOT NULL,
  `institution_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Healthcare', NULL, '2025-06-30 08:41:26'),
(2, 'Finance', NULL, '2025-06-30 08:41:26'),
(3, 'Climate Science', NULL, '2025-06-30 08:41:26'),
(4, 'Education', NULL, '2025-06-30 08:41:26'),
(5, 'Transport', NULL, '2025-06-30 08:41:26'),
(6, 'Agriculture', NULL, '2025-06-30 08:41:26');

-- --------------------------------------------------------

--
-- Table structure for table `datasets`
--

CREATE TABLE `datasets` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_size` varchar(50) DEFAULT NULL,
  `file_format` varchar(50) DEFAULT NULL,
  `uploaded_by_user_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `metadata_summary` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata_summary`)),
  `status` enum('pending','approved','disapproved') NOT NULL DEFAULT 'pending',
  `approval_notes` text DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `approved_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `datasets`
--

INSERT INTO `datasets` (`id`, `title`, `description`, `file_path`, `file_size`, `file_format`, `uploaded_by_user_id`, `category_id`, `metadata_summary`, `status`, `approval_notes`, `uploaded_at`, `approved_at`) VALUES
(1, 'weather', 'weather', 'uploads/datasets/dataset_6863a2ca5c0cf2.81262691.pdf', '550.39 KB', 'pdf', 3, 3, NULL, 'approved', NULL, '2025-07-01 08:56:42', '2025-07-01 08:57:50'),
(2, 'finance', 'financials', 'uploads/datasets/dataset_6864c586ec2789.46900045.docx', '27.04 KB', 'docx', 3, 2, NULL, 'approved', NULL, '2025-07-02 05:37:10', '2025-07-02 05:46:30');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('normal_user','student','researcher','academic','contributor','curator') NOT NULL DEFAULT 'normal_user',
  `is_approved` tinyint(1) NOT NULL DEFAULT 1,
  `institution` varchar(255) DEFAULT NULL,
  `student_id` varchar(255) DEFAULT NULL,
  `course_of_study` varchar(255) DEFAULT NULL,
  `year_of_study` int(11) DEFAULT NULL,
  `research_area` varchar(255) DEFAULT NULL,
  `research_id` varchar(255) DEFAULT NULL,
  `experience` int(11) DEFAULT NULL,
  `academic_institution` varchar(255) DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `designation` varchar(255) DEFAULT NULL,
  `academic_email` varchar(255) DEFAULT NULL,
  `organization_name` varchar(255) DEFAULT NULL,
  `type_of_data` varchar(255) DEFAULT NULL,
  `contact_info` varchar(255) DEFAULT NULL,
  `area_of_expertise` varchar(255) DEFAULT NULL,
  `curator_institution` varchar(255) DEFAULT NULL,
  `curator_experience` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `role`, `is_approved`, `institution`, `student_id`, `course_of_study`, `year_of_study`, `research_area`, `research_id`, `experience`, `academic_institution`, `department`, `designation`, `academic_email`, `organization_name`, `type_of_data`, `contact_info`, `area_of_expertise`, `curator_institution`, `curator_experience`, `created_at`, `updated_at`) VALUES
(1, 'james', 'mwendihillary21@gmail.com', '$2y$10$C.oenKYi4HElu5RYjWNfKehqlNYQUlTKfIEnLaN4JQC0pHcmfhzYu', 'normal_user', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-01 05:56:38', '2025-07-01 05:56:38'),
(2, 'New Admin User', 'new.admin@example.com', '$2y$10$6c/Qz9N6M7PoBsHyGUnPQOwUwG8Juxt.3fxJ2dCCAMzxdsQ25QGPO', 'normal_user', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-01 07:15:30', '2025-07-01 07:15:30'),
(3, 'KIMAIGA', 'man@gmail.com', '$2y$10$PvLfusK7qd5zCBXObkgmfOsKi4m69Tu6lSGaZHCGyYMpjA/j8lmxC', '', 1, 'jkuat', NULL, NULL, NULL, 'machine learning', 'ppid067', 6, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-01 07:22:26', '2025-07-02 08:27:04'),
(4, 'tilis', 'tilis@gmail.com', '$2y$10$VTFzbPtNmb3SR53/gijxSeecTR7Jlv0fBrjffXrJd.f8AP9jKDHc6', 'academic', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'jkuat', 'computing', 'doctor', 'dr@jkuat.ac.ke', NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-01 07:30:21', '2025-07-01 07:31:14'),
(5, 'laura', 'min@gmail.com', '$2y$10$TiJG0zsXGbWOwUp3WTfJhugfs63Vbwhb9lyLs4OfKzXof4LUtPq0C', 'student', 1, 'jkuat', 'ct23/87', 'computer science', 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-01 09:23:48', '2025-07-01 09:23:49'),
(6, 'kim hillary', 'mwendihillary@gmail.com', '$2y$10$C5qgUixhVVayOOmGN/8V2.asWppgyTBKKORowPJWjRHhZMxMoZXla', 'contributor', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'jhub', 'scientific', '111345678', NULL, NULL, NULL, '2025-07-02 05:50:51', '2025-07-02 08:14:06'),
(7, 'nderu hems', 'hems@gmail.com', '$2y$10$U71JbvAyYwqv.hrT0jsqiuK7rzHisqhoONbxqzrtIjjfQ6u/MAr02', 'researcher', 1, 'jkuat', NULL, NULL, NULL, 'machine learning', 'ppid06709', 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-02 08:55:31', '2025-07-02 08:55:42'),
(8, 'joseph', 'jose@gmail.com', '$2y$10$PPTEGzT0dx/j8rJCaksr9.uTPt3tJJfwl/zg5dGaTnj7O01yrfED2', 'normal_user', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-03 06:35:11', '2025-07-03 06:35:11'),
(9, 'HILLARY mwendi kimaiga', 'manki@gmail.com', '$2y$10$mAyVgqTG1sdPHUjyKeUceuqU7jOVLYiiuc6cwwwxlt7DIrdwIPN/K', 'researcher', 1, 'welt', NULL, NULL, NULL, 'machine learning', 'ppid06754', 6, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-03 07:02:46', '2025-07-03 07:04:11');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `adminid` (`adminid`);

--
-- Indexes for table `admin_actions_log`
--
ALTER TABLE `admin_actions_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_user_id` (`admin_user_id`);

--
-- Indexes for table `allowed_institutions`
--
ALTER TABLE `allowed_institutions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `institution_name` (`institution_name`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `datasets`
--
ALTER TABLE `datasets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uploaded_by_user_id` (`uploaded_by_user_id`),
  ADD KEY `category_id` (`category_id`);

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
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `admin_actions_log`
--
ALTER TABLE `admin_actions_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `allowed_institutions`
--
ALTER TABLE `allowed_institutions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `datasets`
--
ALTER TABLE `datasets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admins`
--
ALTER TABLE `admins`
  ADD CONSTRAINT `admins_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `admin_actions_log`
--
ALTER TABLE `admin_actions_log`
  ADD CONSTRAINT `admin_actions_log_ibfk_1` FOREIGN KEY (`admin_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `datasets`
--
ALTER TABLE `datasets`
  ADD CONSTRAINT `datasets_ibfk_1` FOREIGN KEY (`uploaded_by_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `datasets_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
