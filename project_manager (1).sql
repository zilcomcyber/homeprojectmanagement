-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 30, 2025 at 02:29 PM
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
-- Database: `project_manager`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('super_admin','admin','viewer') DEFAULT 'admin',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `last_ip` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `name`, `email`, `password_hash`, `role`, `is_active`, `created_at`, `last_login`, `last_ip`) VALUES
(1, 'Hamisi', 'hamisi@gmail.com', '$2y$10$C7y/xd22lUqgUazs6VqCuOvFdDFq8zHB/hu7k2V7Jgs5BMP0rjgA6', 'super_admin', 1, '2025-05-29 18:20:11', '2025-05-30 12:24:43', '::1');

-- --------------------------------------------------------

--
-- Table structure for table `counties`
--

CREATE TABLE `counties` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `counties`
--

INSERT INTO `counties` (`id`, `name`, `code`, `created_at`) VALUES
(1, 'Migori', 'MGR', '2025-05-29 18:18:13');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Water and Sanitation', 'Water supply, sewerage, and sanitation projects', '2025-05-29 18:18:13'),
(2, 'Roads and Transport', 'Road construction, maintenance, and transport infrastructure', '2025-05-29 18:18:13'),
(3, 'Health Services', 'Healthcare facilities and medical equipment', '2025-05-29 18:18:13'),
(4, 'Education', 'Schools, libraries, and educational infrastructure', '2025-05-29 18:18:13'),
(5, 'Agriculture', 'Agricultural development and irrigation projects', '2025-05-29 18:18:13'),
(6, 'Environment', 'Environmental conservation and climate projects', '2025-05-29 18:18:13');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `citizen_name` varchar(255) DEFAULT NULL,
  `citizen_email` varchar(255) DEFAULT NULL,
  `citizen_phone` varchar(20) DEFAULT NULL,
  `subject` varchar(500) NOT NULL,
  `message` text NOT NULL,
  `status` enum('pending','responded','resolved') DEFAULT 'pending',
  `admin_response` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `responded_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`id`, `project_id`, `citizen_name`, `citizen_email`, `citizen_phone`, `subject`, `message`, `status`, `admin_response`, `created_at`, `updated_at`, `responded_by`) VALUES
(1, 7, 'MS JENIFER MUHONJA', 'jenifermuhonja01@gmail.com', '', 'thanks mhesh', 'asante', 'responded', 'erokamano. tich tiyore', '2025-05-29 22:13:26', '2025-05-29 22:27:59', 1);

-- --------------------------------------------------------

--
-- Table structure for table `import_logs`
--

CREATE TABLE `import_logs` (
  `id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `total_rows` int(11) NOT NULL,
  `successful_imports` int(11) NOT NULL,
  `failed_imports` int(11) NOT NULL,
  `error_details` text DEFAULT NULL,
  `imported_by` int(11) NOT NULL,
  `imported_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `import_logs`
--

INSERT INTO `import_logs` (`id`, `filename`, `total_rows`, `successful_imports`, `failed_imports`, `error_details`, `imported_by`, `imported_at`) VALUES
(1, '6838cd091aa11_1748552969.csv', 3, 0, 3, 'Row 2: Column count mismatch\nRow 3: Column count mismatch\nRow 4: Column count mismatch', 1, '2025-05-29 21:09:29');

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `project_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `department_id` int(11) NOT NULL,
  `project_year` int(11) NOT NULL,
  `county_id` int(11) NOT NULL,
  `sub_county_id` int(11) NOT NULL,
  `ward_id` int(11) NOT NULL,
  `location_address` text DEFAULT NULL,
  `location_coordinates` varchar(100) DEFAULT NULL,
  `budget` decimal(15,2) NOT NULL,
  `allocated_budget` decimal(15,2) DEFAULT 0.00,
  `spent_budget` decimal(15,2) DEFAULT 0.00,
  `start_date` date DEFAULT NULL,
  `expected_completion_date` date DEFAULT NULL,
  `actual_completion_date` date DEFAULT NULL,
  `contractor_name` varchar(255) DEFAULT NULL,
  `contractor_contact` varchar(100) DEFAULT NULL,
  `status` enum('draft','ongoing','completed','suspended','cancelled') NOT NULL DEFAULT 'draft',
  `step_status` enum('awaiting','running','completed') DEFAULT 'awaiting',
  `progress_percentage` decimal(5,2) DEFAULT 0.00,
  `total_steps` int(11) DEFAULT 0,
  `completed_steps` int(11) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `average_rating` decimal(3,2) DEFAULT 5.00,
  `total_ratings` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `project_name`, `description`, `department_id`, `project_year`, `county_id`, `sub_county_id`, `ward_id`, `location_address`, `location_coordinates`, `budget`, `allocated_budget`, `spent_budget`, `start_date`, `expected_completion_date`, `actual_completion_date`, `contractor_name`, `contractor_contact`, `status`, `step_status`, `progress_percentage`, `total_steps`, `completed_steps`, `created_by`, `created_at`, `updated_at`, `average_rating`, `total_ratings`) VALUES
(6, 'Uriri Stadium', 'uriri community stadioum by hon hamisi william', 2, 2025, 1, 4, 8, 'Uriri Primary, near uriri police station', '-1.3167,34.4833', 23000000.00, 0.00, 0.00, '2025-01-01', '2026-05-01', NULL, 'XYZ construction company', '0702353585', 'ongoing', 'awaiting', 29.00, 7, 0, 1, '2025-05-29 21:22:02', '2025-05-30 12:19:16', 5.00, 1),
(7, 'Uriri Stadium 2', 'ttttttttttttt', 3, 2025, 1, 1, 1, 'Uriri Primary, near uriri police station', '-0.860817,34.211262', 10000000.00, 0.00, 0.00, '2024-01-02', '2026-04-05', NULL, 'XYZ construction company', '0702353585', 'completed', 'awaiting', 100.00, 6, 0, 1, '2025-05-29 22:01:26', '2025-05-30 12:22:41', 1.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `project_feedback`
--

CREATE TABLE `project_feedback` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `citizen_name` varchar(100) NOT NULL,
  `citizen_email` varchar(100) DEFAULT NULL,
  `citizen_phone` varchar(20) DEFAULT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `status` enum('new','reviewed','responded','closed') DEFAULT 'new',
  `admin_response` text DEFAULT NULL,
  `responded_by` int(11) DEFAULT NULL,
  `responded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `project_ratings`
--

CREATE TABLE `project_ratings` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `user_name` varchar(100) DEFAULT NULL,
  `user_email` varchar(100) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project_ratings`
--

INSERT INTO `project_ratings` (`id`, `project_id`, `rating`, `user_name`, `user_email`, `comment`, `created_at`, `ip_address`) VALUES
(1, 6, 5, '', '', '', '2025-05-29 22:42:05', '::1'),
(2, 7, 1, '', '', '', '2025-05-29 22:44:24', '::1');

-- --------------------------------------------------------

--
-- Table structure for table `project_steps`
--

CREATE TABLE `project_steps` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `step_number` int(11) NOT NULL,
  `step_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','in_progress','completed','skipped') DEFAULT 'pending',
  `start_date` date DEFAULT NULL,
  `expected_end_date` date DEFAULT NULL,
  `actual_end_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project_steps`
--

INSERT INTO `project_steps` (`id`, `project_id`, `step_number`, `step_name`, `description`, `status`, `start_date`, `expected_end_date`, `actual_end_date`, `notes`, `created_at`, `updated_at`) VALUES
(8, 6, 1, 'Survey &amp; Design', 'Road survey, traffic analysis, and engineering design', 'pending', NULL, NULL, NULL, NULL, '2025-05-29 21:22:02', '2025-05-29 21:23:53'),
(9, 6, 2, 'Environmental Clearance', 'Environmental impact assessment and approvals', 'pending', NULL, NULL, NULL, NULL, '2025-05-29 21:22:02', '2025-05-29 21:23:53'),
(10, 6, 3, 'Earthworks', 'Road cutting, filling, and grading', 'pending', NULL, NULL, NULL, NULL, '2025-05-29 21:22:02', '2025-05-29 21:23:53'),
(11, 6, 4, 'Base &amp; Sub-base', 'Laying of road base and sub-base materials', 'pending', NULL, NULL, NULL, NULL, '2025-05-29 21:22:02', '2025-05-29 21:23:53'),
(12, 6, 5, 'Surface &amp; Drainage', 'Tarmacking and drainage system installation', 'in_progress', '2025-05-30', NULL, NULL, '', '2025-05-29 21:22:02', '2025-05-29 21:35:15'),
(14, 6, 6, 'purchase of land parcel', 'purchase of land parcel', 'completed', '2025-05-30', NULL, '2025-05-30', '', '2025-05-29 21:22:02', '2025-05-29 21:24:44'),
(15, 6, 7, 'approval of the plan proposal', 'approval of proposal', 'completed', NULL, NULL, '2025-05-30', '', '2025-05-29 21:22:02', '2025-05-29 21:23:53'),
(16, 7, 2, 'Planning &amp; Design', 'Facility design and medical equipment planning', 'completed', NULL, NULL, '2025-05-30', '', '2025-05-29 22:01:26', '2025-05-29 22:01:34'),
(17, 7, 3, 'Permits &amp; Approvals', 'Building permits and health ministry approvals', 'completed', NULL, NULL, '2025-05-30', '', '2025-05-29 22:01:26', '2025-05-29 22:53:41'),
(18, 7, 4, 'Foundation &amp; Structure', 'Building foundation and structural construction', 'completed', NULL, NULL, '2025-05-30', '', '2025-05-29 22:01:26', '2025-05-29 22:53:50'),
(19, 7, 5, 'Medical Infrastructure', 'Specialized medical installations and utilities', 'completed', NULL, NULL, '2025-05-30', '', '2025-05-29 22:01:26', '2025-05-29 22:53:57'),
(20, 7, 6, 'Equipment Installation', 'Medical equipment and furniture installation', 'completed', NULL, NULL, '2025-05-30', '', '2025-05-29 22:01:26', '2025-05-29 22:54:05'),
(21, 7, 7, 'Certification &amp; Launch', 'Health certification and facility commissioning', 'completed', NULL, NULL, '2025-05-30', '', '2025-05-29 22:01:26', '2025-05-29 22:55:41');

-- --------------------------------------------------------

--
-- Table structure for table `sub_counties`
--

CREATE TABLE `sub_counties` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `county_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sub_counties`
--

INSERT INTO `sub_counties` (`id`, `name`, `county_id`, `created_at`) VALUES
(1, 'Migori Central', 1, '2025-05-29 18:18:13'),
(2, 'Awendo', 1, '2025-05-29 18:18:13'),
(3, 'Suna East', 1, '2025-05-29 18:18:13'),
(4, 'Suna West', 1, '2025-05-29 18:18:13'),
(5, 'Uriri', 1, '2025-05-29 18:18:13'),
(6, 'Nyatike', 1, '2025-05-29 18:18:13'),
(7, 'Kuria East', 1, '2025-05-29 18:18:13'),
(8, 'Kuria West', 1, '2025-05-29 18:18:13');

-- --------------------------------------------------------

--
-- Table structure for table `wards`
--

CREATE TABLE `wards` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `sub_county_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wards`
--

INSERT INTO `wards` (`id`, `name`, `sub_county_id`, `created_at`) VALUES
(1, 'Central Ward', 1, '2025-05-29 18:18:13'),
(2, 'God Jope Ward', 1, '2025-05-29 18:18:13'),
(3, 'Kisii Central Ward', 1, '2025-05-29 18:18:13'),
(4, 'North Sakwa Ward', 2, '2025-05-29 18:18:13'),
(5, 'South Sakwa Ward', 2, '2025-05-29 18:18:13'),
(6, 'West Sakwa Ward', 2, '2025-05-29 18:18:13'),
(7, 'Kakrao Ward', 3, '2025-05-29 18:18:13'),
(8, 'Wiga Ward', 4, '2025-05-29 18:18:13'),
(9, 'East Kanyamkago Ward', 5, '2025-05-29 18:18:13'),
(10, 'Karungu Ward', 6, '2025-05-29 18:18:13'),
(11, 'Ntimaru Ward', 7, '2025-05-29 18:18:13'),
(12, 'Isibania Ward', 8, '2025-05-29 18:18:13');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `counties`
--
ALTER TABLE `counties`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `fk_feedback_responded_by` (`responded_by`);

--
-- Indexes for table `import_logs`
--
ALTER TABLE `import_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `imported_by` (`imported_by`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `county_id` (`county_id`),
  ADD KEY `sub_county_id` (`sub_county_id`),
  ADD KEY `ward_id` (`ward_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `project_feedback`
--
ALTER TABLE `project_feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `responded_by` (`responded_by`);

--
-- Indexes for table `project_ratings`
--
ALTER TABLE `project_ratings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_project_rating` (`project_id`),
  ADD KEY `idx_rating` (`rating`);

--
-- Indexes for table `project_steps`
--
ALTER TABLE `project_steps`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_project_step` (`project_id`,`step_number`);

--
-- Indexes for table `sub_counties`
--
ALTER TABLE `sub_counties`
  ADD PRIMARY KEY (`id`),
  ADD KEY `county_id` (`county_id`);

--
-- Indexes for table `wards`
--
ALTER TABLE `wards`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sub_county_id` (`sub_county_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `counties`
--
ALTER TABLE `counties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `import_logs`
--
ALTER TABLE `import_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `project_feedback`
--
ALTER TABLE `project_feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project_ratings`
--
ALTER TABLE `project_ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `project_steps`
--
ALTER TABLE `project_steps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `sub_counties`
--
ALTER TABLE `sub_counties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `wards`
--
ALTER TABLE `wards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_feedback_responded_by` FOREIGN KEY (`responded_by`) REFERENCES `admins` (`id`);

--
-- Constraints for table `import_logs`
--
ALTER TABLE `import_logs`
  ADD CONSTRAINT `import_logs_ibfk_1` FOREIGN KEY (`imported_by`) REFERENCES `admins` (`id`);

--
-- Constraints for table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  ADD CONSTRAINT `projects_ibfk_2` FOREIGN KEY (`county_id`) REFERENCES `counties` (`id`),
  ADD CONSTRAINT `projects_ibfk_3` FOREIGN KEY (`sub_county_id`) REFERENCES `sub_counties` (`id`),
  ADD CONSTRAINT `projects_ibfk_4` FOREIGN KEY (`ward_id`) REFERENCES `wards` (`id`),
  ADD CONSTRAINT `projects_ibfk_5` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`);

--
-- Constraints for table `project_feedback`
--
ALTER TABLE `project_feedback`
  ADD CONSTRAINT `project_feedback_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `project_feedback_ibfk_2` FOREIGN KEY (`responded_by`) REFERENCES `admins` (`id`);

--
-- Constraints for table `project_ratings`
--
ALTER TABLE `project_ratings`
  ADD CONSTRAINT `project_ratings_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `project_steps`
--
ALTER TABLE `project_steps`
  ADD CONSTRAINT `project_steps_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sub_counties`
--
ALTER TABLE `sub_counties`
  ADD CONSTRAINT `sub_counties_ibfk_1` FOREIGN KEY (`county_id`) REFERENCES `counties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wards`
--
ALTER TABLE `wards`
  ADD CONSTRAINT `wards_ibfk_1` FOREIGN KEY (`sub_county_id`) REFERENCES `sub_counties` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
