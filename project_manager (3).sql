-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 13, 2025 at 02:36 PM
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
(1, 'Hamisi', 'hamisi@gmail.com', '$2y$10$C7y/xd22lUqgUazs6VqCuOvFdDFq8zHB/hu7k2V7Jgs5BMP0rjgA6', 'super_admin', 1, '2025-05-29 18:20:11', '2025-06-13 11:45:44', '::1'),
(2, 'MS JENIFER MUHONJA', 'hamweed68@gmail.com', '$2y$10$usiBnlbLyZkR7PbZ8DRV/ekH7ciugp.T8Qw1Wvk6q5pmG2qvLc7Fm', 'admin', 0, '2025-06-13 10:00:04', '2025-06-13 10:00:51', '::1');

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
(6, 'Environment', 'Environmental conservation and climate projects', '2025-05-29 18:18:13'),
(7, 'department', NULL, '2025-05-30 17:40:04'),
(8, 'Trade and Commerce', NULL, '2025-06-12 09:19:21'),
(9, 'Health', NULL, '2025-06-12 13:07:41');

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
  `status` enum('pending','reviewed','responded') DEFAULT 'pending',
  `admin_response` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `responded_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`id`, `project_id`, `citizen_name`, `citizen_email`, `citizen_phone`, `subject`, `message`, `status`, `admin_response`, `created_at`, `updated_at`, `responded_by`) VALUES
(2, 10, 'MS JENIFER MUHONJA', 'admis@gmail.com', '', 'Oyani gogo road', 'well done', 'responded', 'thank you', '2025-05-30 18:07:00', '2025-06-13 08:42:40', 1);

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
  `start_date` date DEFAULT NULL,
  `expected_completion_date` date DEFAULT NULL,
  `actual_completion_date` date DEFAULT NULL,
  `contractor_name` varchar(255) DEFAULT NULL,
  `contractor_contact` varchar(100) DEFAULT NULL,
  `status` enum('planning','ongoing','completed','suspended','cancelled') NOT NULL DEFAULT 'planning',
  `visibility` enum('private','published') DEFAULT 'private',
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

INSERT INTO `projects` (`id`, `project_name`, `description`, `department_id`, `project_year`, `county_id`, `sub_county_id`, `ward_id`, `location_address`, `location_coordinates`, `start_date`, `expected_completion_date`, `actual_completion_date`, `contractor_name`, `contractor_contact`, `status`, `visibility`, `step_status`, `progress_percentage`, `total_steps`, `completed_steps`, `created_by`, `created_at`, `updated_at`, `average_rating`, `total_ratings`) VALUES
(6, 'Uriri Stadium', 'uriri community stadioum by hon hamisi william', 2, 2025, 1, 4, 8, 'Uriri Primary, near uriri police station', '-1.3167,34.4833', '2025-01-01', '2026-05-01', NULL, 'XYZ construction company', '0702353585', 'ongoing', 'published', 'awaiting', 57.00, 7, 0, 1, '2025-05-29 21:22:02', '2025-06-12 09:46:11', 5.00, 1),
(7, 'Uriri Stadium 2', 'ttttttttttttt', 3, 2025, 1, 1, 1, 'Uriri Primary, near uriri police station', '-0.860817,34.211262', '2024-01-02', '2026-04-05', NULL, 'XYZ construction company', '0702353585', 'completed', 'published', 'awaiting', 100.00, 6, 0, 1, '2025-05-29 22:01:26', '2025-06-12 09:46:11', 1.00, 1),
(8, 'rongo water reserve', 'rongo', 6, 2025, 1, 2, 4, 'Uriri Primary, near uriri police station', '-1.018725,34.448841', '2020-03-03', '2020-12-11', NULL, 'XYZ construction company', '0702353585', 'ongoing', 'published', 'awaiting', 62.50, 4, 0, 1, '2025-05-30 14:57:22', '2025-06-12 12:41:24', 5.00, 0),
(9, 'Migori County Health Center Construction', 'Construction of a modern health center to serve the local community with medical facilities and equipment', 5, 2024, 1, 2, 4, 'Migori Town Center, near the main market', '-1.0634,34.4731', '2024-01-15', '2024-12-31', NULL, 'ABC Construction Ltd', '254712345678', 'ongoing', 'published', 'awaiting', 50.00, 2, 0, 1, '2025-05-30 17:40:04', '2025-06-12 12:40:10', 4.00, 1),
(10, 'Gusi stadium', 'kisii national stadium', 2, 2025, 1, 5, 9, 'Kisii town', '-0.847086,34.544971', '2020-11-11', '2020-02-20', NULL, 'XYZ construction company', '0702353585', 'ongoing', 'published', 'awaiting', 37.50, 4, 0, 1, '2025-05-30 18:02:27', '2025-06-13 11:51:49', 3.00, 1),
(17, 'Migori-Isebania Road Improvement', 'Upgrading of 15km stretch of Migori-Isebania road with tarmac surface and proper drainage', 2, 2024, 1, 10, 14, 'Migori-Isebania Highway, Migori Town', '-1.0634,34.4731', '2024-01-15', '2024-12-31', NULL, 'Kens Construction Ltd', '254712345678', 'completed', 'published', 'awaiting', 100.00, 1, 0, 1, '2025-06-12 09:58:54', '2025-06-13 09:32:37', 5.00, 0),
(18, 'Rongo Market Upgrade', 'Construction of modern market stalls with proper sanitation and drainage facilities', 8, 2024, 1, 11, 15, 'Rongo Town Center', '-1.2345,34.6789', '2024-03-01', '2024-08-30', NULL, 'Unity Builders', '254723456789', 'ongoing', 'published', 'awaiting', 25.00, 2, 0, 1, '2025-06-12 09:58:54', '2025-06-12 12:37:16', 5.00, 0),
(19, 'Nyatike Health Center Extension', 'Addition of maternity wing and medical equipment procurement', 3, 2024, 1, 6, 16, 'Nyatike Health Center', '-1.1234,34.1234', '2024-02-01', '2024-11-30', NULL, 'Medical Contractors Kenya', '254734567890', 'ongoing', 'published', 'awaiting', 50.00, 1, 0, 1, '2025-06-12 09:58:54', '2025-06-12 12:47:00', 5.00, 0),
(20, 'Oyani gogo road construction', 'Oyani gogo road construction', 2, 2025, 1, 5, 9, 'Uriri Primary, near uriri police station', '-0.970066,34.496477', '2020-12-12', NULL, NULL, 'XYZ construction company', '254712345678', 'ongoing', 'published', 'awaiting', 50.00, 3, 0, 1, '2025-06-12 10:32:57', '2025-06-13 11:52:24', 5.00, 0),
(21, 'Oyani SDA Dispensary expansion', 'contruction of ward fercility', 9, 2025, 1, 5, 17, 'Oyani SDA', '-0.951454, 34.443863', NULL, NULL, NULL, 'ABC Construction Ltd', '727266454', 'ongoing', 'published', 'awaiting', 50.00, 1, 0, 1, '2025-06-12 13:07:41', '2025-06-12 18:25:37', 2.00, 1),
(22, 'Kaminolewe market construction', 'kaminolewe market improvement to market standards', 8, 2026, 1, 5, 17, 'Kaminolewe market', '-0.950614, 34.447275', NULL, NULL, NULL, 'ABC Construction Ltd', '726473575', 'ongoing', 'published', 'awaiting', 12.50, 4, 0, 1, '2025-06-12 13:07:41', '2025-06-13 09:36:15', 5.00, 0);

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
(2, 7, 1, '', '', '', '2025-05-29 22:44:24', '::1'),
(3, 10, 3, '', '', '', '2025-06-05 15:49:02', '::1'),
(4, 9, 4, '', '', '', '2025-06-12 08:28:21', '::1'),
(5, 21, 2, '', '', '', '2025-06-12 18:25:37', '::1');

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
(8, 6, 1, 'Survey &amp; Design', 'Road survey, traffic analysis, and engineering design', 'completed', NULL, NULL, '2025-05-30', '', '2025-05-29 21:22:02', '2025-05-30 14:49:42'),
(9, 6, 2, 'Environmental Clearance', 'Environmental impact assessment and approvals', 'in_progress', '2025-05-30', NULL, '2025-05-30', '', '2025-05-29 21:22:02', '2025-05-30 14:51:26'),
(10, 6, 3, 'Earthworks', 'Road cutting, filling, and grading', 'completed', NULL, NULL, '2025-05-30', '', '2025-05-29 21:22:02', '2025-05-30 14:50:02'),
(11, 6, 4, 'Base &amp; Sub-base', 'Laying of road base and sub-base materials', 'pending', NULL, NULL, '2025-05-30', '', '2025-05-29 21:22:02', '2025-05-30 14:51:32'),
(12, 6, 5, 'Surface &amp; Drainage', 'Tarmacking and drainage system installation', 'pending', '2025-05-30', NULL, '2025-05-30', '', '2025-05-29 21:22:02', '2025-06-05 16:25:33'),
(14, 6, 6, 'purchase of land parcel', 'purchase of land parcel', 'completed', '2025-05-30', NULL, '2025-05-30', '', '2025-05-29 21:22:02', '2025-05-29 21:24:44'),
(15, 6, 7, 'approval of the plan proposal', 'approval of proposal', 'completed', NULL, NULL, '2025-05-30', '', '2025-05-29 21:22:02', '2025-05-29 21:23:53'),
(16, 7, 2, 'Planning &amp; Design', 'Facility design and medical equipment planning', 'completed', NULL, NULL, '2025-05-30', '', '2025-05-29 22:01:26', '2025-05-29 22:01:34'),
(17, 7, 3, 'Permits &amp; Approvals', 'Building permits and health ministry approvals', 'completed', NULL, NULL, '2025-05-30', '', '2025-05-29 22:01:26', '2025-05-29 22:53:41'),
(18, 7, 4, 'Foundation &amp; Structure', 'Building foundation and structural construction', 'completed', NULL, NULL, '2025-05-30', '', '2025-05-29 22:01:26', '2025-05-29 22:53:50'),
(19, 7, 5, 'Medical Infrastructure', 'Specialized medical installations and utilities', 'completed', NULL, NULL, '2025-05-30', '', '2025-05-29 22:01:26', '2025-05-29 22:53:57'),
(20, 7, 6, 'Equipment Installation', 'Medical equipment and furniture installation', 'completed', NULL, NULL, '2025-05-30', '', '2025-05-29 22:01:26', '2025-05-29 22:54:05'),
(21, 7, 7, 'Certification &amp; Launch', 'Health certification and facility commissioning', 'completed', NULL, NULL, '2025-05-30', '', '2025-05-29 22:01:26', '2025-05-29 22:55:41'),
(22, 8, 1, 'procurement', '', 'completed', '2025-05-30', NULL, '2025-05-30', '', '2025-05-30 14:57:43', '2025-05-30 17:03:00'),
(23, 8, 2, 'land clearing', '', 'completed', '2025-05-30', NULL, '2025-05-31', '', '2025-05-30 14:58:07', '2025-05-31 00:05:46'),
(24, 8, 3, 'building sitestructures', '', 'in_progress', '2025-05-31', NULL, '2025-05-31', '', '2025-05-30 14:58:21', '2025-06-12 12:41:24'),
(25, 8, 4, 'commisioning', '', 'pending', '2025-05-31', NULL, '2025-05-31', '', '2025-05-30 14:58:33', '2025-05-31 00:06:15'),
(26, 9, 1, 'Project Planning & Approval', 'Initial project planning, design review, and regulatory approval process', 'completed', '2025-05-30', NULL, '2025-06-12', '', '2025-05-30 17:40:04', '2025-06-12 09:56:30'),
(27, 10, 1, 'procurement', '', 'completed', '2025-05-30', NULL, '2025-05-30', '', '2025-05-30 18:02:44', '2025-05-30 18:03:19'),
(28, 10, 2, 'land clearing', '', 'in_progress', '2025-06-13', NULL, NULL, '', '2025-05-30 18:02:51', '2025-06-13 11:51:49'),
(29, 10, 3, 'building sitestructures', '', 'pending', NULL, NULL, NULL, NULL, '2025-05-30 18:02:57', '2025-05-30 18:02:57'),
(30, 10, 4, 'commisioning', '', 'pending', NULL, NULL, NULL, NULL, '2025-05-30 18:03:07', '2025-05-30 18:03:07'),
(38, 9, 2, 'commisioning', '', 'in_progress', '2025-06-12', NULL, '2025-06-12', '', '2025-06-12 09:56:25', '2025-06-12 09:58:27'),
(39, 17, 1, 'Road Survey and Design', 'Conduct topographical survey and prepare detailed engineering designs', 'completed', '2025-06-12', NULL, '2025-06-13', '', '2025-06-12 09:58:54', '2025-06-13 09:32:37'),
(40, 18, 1, 'Site Preparation', 'Clear site and prepare foundation for market construction', 'in_progress', '2025-06-12', NULL, '2025-06-12', '', '2025-06-12 09:58:54', '2025-06-12 12:36:59'),
(41, 19, 1, 'Architectural Planning', 'Design maternity wing and plan equipment installation', 'in_progress', '2025-06-12', NULL, '2025-06-12', '', '2025-06-12 09:58:54', '2025-06-12 12:47:00'),
(42, 20, 2, 'Project Planning &amp; Approval', 'Initial project planning, design review, and regulatory approval process', 'completed', '2025-06-12', NULL, '2025-06-13', '', '2025-06-12 10:32:57', '2025-06-13 11:52:18'),
(43, 20, 3, 'Environmental Clearance', '', 'in_progress', '2025-06-13', NULL, '2025-06-12', '', '2025-06-12 10:32:57', '2025-06-13 11:52:24'),
(44, 18, 2, 'procurement', '', 'pending', '2025-06-12', NULL, NULL, '', '2025-06-12 12:36:45', '2025-06-12 12:37:16'),
(45, 20, 4, 'land clearing', '', 'pending', NULL, NULL, NULL, NULL, '2025-06-12 12:43:20', '2025-06-12 12:43:20'),
(46, 21, 1, 'Project Planning & Approval', 'Initial project planning, design review, and regulatory approval process', 'in_progress', '2025-06-12', NULL, NULL, '', '2025-06-12 13:07:41', '2025-06-12 13:08:39'),
(47, 22, 1, 'Project Planning & Approval', 'Initial project planning, design review, and regulatory approval process', 'in_progress', '2025-06-13', NULL, NULL, '', '2025-06-12 13:07:41', '2025-06-13 09:36:15'),
(48, 22, 2, 'procurement', '', 'pending', NULL, NULL, NULL, NULL, '2025-06-13 06:43:16', '2025-06-13 06:43:16'),
(49, 22, 3, 'land clearing', '', 'pending', NULL, NULL, NULL, NULL, '2025-06-13 06:43:28', '2025-06-13 06:43:28'),
(50, 22, 4, 'building sitestructures', '', 'pending', NULL, NULL, NULL, NULL, '2025-06-13 06:43:41', '2025-06-13 06:43:41');

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
(8, 'Kuria West', 1, '2025-05-29 18:18:13'),
(10, 'Migori', 1, '2025-06-12 09:19:21'),
(11, 'Rongo', 1, '2025-06-12 09:19:21');

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
(12, 'Isibania Ward', 8, '2025-05-29 18:18:13'),
(14, 'Central Sakwa', 10, '2025-06-12 09:19:21'),
(15, 'East Kamagambo', 11, '2025-06-12 09:19:21'),
(16, 'North Kadem', 6, '2025-06-12 09:19:21'),
(17, 'West Kanyamkago', 5, '2025-06-12 13:07:41');

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
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_projects_status` (`status`),
  ADD KEY `idx_projects_visibility` (`visibility`),
  ADD KEY `idx_projects_year` (`project_year`);

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
  ADD UNIQUE KEY `unique_project_step` (`project_id`,`step_number`),
  ADD KEY `idx_project_steps_status` (`status`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `counties`
--
ALTER TABLE `counties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `import_logs`
--
ALTER TABLE `import_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `project_feedback`
--
ALTER TABLE `project_feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project_ratings`
--
ALTER TABLE `project_ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `project_steps`
--
ALTER TABLE `project_steps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `sub_counties`
--
ALTER TABLE `sub_counties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `wards`
--
ALTER TABLE `wards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

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
