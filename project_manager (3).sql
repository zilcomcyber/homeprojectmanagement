-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 21, 2025 at 11:42 AM
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
(1, 'Administrator', 'hamisi@gmail.com', '$2y$10$7aGT4Cz5FxEoUm/zxaUb7efAy85wF65eNjWQup98.83wPMXF7RxHW', 'super_admin', 1, '2025-05-29 15:20:11', '2025-06-21 07:58:23', '::1'),
(2, 'MS JENIFER MUHONJA', 'hamweed68@gmail.com', '$2y$10$usiBnlbLyZkR7PbZ8DRV/ekH7ciugp.T8Qw1Wvk6q5pmG2qvLc7Fm', 'admin', 1, '2025-06-13 07:00:04', '2025-06-15 02:52:08', '::1');

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
(1, 'Migori', 'MGR', '2025-06-21 09:39:15');

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
(1, 'Public Health and Medical Services', 'Oversees healthcare services, hospitals, clinics, and public health programs across Migori County.', '2025-06-21 09:39:48'),
(2, 'Water and Energy', 'Responsible for water supply, irrigation systems, and energy development projects in the county.', '2025-06-21 09:39:48'),
(3, 'Finance and Economic Planning', 'Manages county budgeting, financial planning, revenue collection and economic development strategies.', '2025-06-21 09:39:48'),
(4, 'Public Service Management and Devolution', 'Handles human resource management, capacity building and implementation of devolution policies.', '2025-06-21 09:39:48'),
(5, 'Roads, Transport and Public Works', 'Develops and maintains road infrastructure, public transport systems and county government buildings.', '2025-06-21 09:39:48'),
(6, 'Education, Gender, Youth, Sports, Culture and Social Services', 'Coordinates education programs, youth empowerment, sports development and cultural activities.', '2025-06-21 09:39:48'),
(7, 'Lands, Housing, Physical Planning and Urban Development', 'Manages land administration, housing projects, urban planning and development control.', '2025-06-21 09:39:48'),
(8, 'Agriculture, Livestock, Veterinary Services, Fisheries and Blue Economy', 'Promotes agricultural development, livestock health, fisheries and blue economy initiatives.', '2025-06-21 09:39:48'),
(9, 'Environment, Natural Resources, Climate Change and Disaster Management', 'Leads environmental conservation, natural resource management and climate resilience programs.', '2025-06-21 09:39:48'),
(10, 'Trade, Tourism, Industrialization and Cooperative Development', 'Facilitates trade, tourism promotion, industrialization and cooperative society development.', '2025-06-21 09:39:48'),
(11, 'ICT, e-Governance and Innovation', 'Drives digital transformation, e-government services and innovation in public service delivery.', '2025-06-21 09:39:48'),
(12, 'County Assembly', 'The legislative arm of Migori County Government that makes laws and oversees county operations.', '2025-06-21 09:39:48'),
(13, 'Public Service Board', 'Responsible for human resource management and public service administration in the county.', '2025-06-21 09:39:48');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `citizen_name` varchar(255) NOT NULL,
  `citizen_email` varchar(255) DEFAULT NULL,
  `citizen_phone` varchar(20) DEFAULT NULL,
  `subject` varchar(500) DEFAULT 'Project Comment',
  `message` text NOT NULL,
  `status` enum('pending','approved','rejected','responded','spam') DEFAULT 'pending',
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `sentiment` enum('positive','neutral','negative') DEFAULT 'neutral',
  `parent_comment_id` int(11) DEFAULT 0,
  `user_ip` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `admin_response` text DEFAULT NULL,
  `responded_by` int(11) DEFAULT NULL,
  `responded_at` timestamp NULL DEFAULT NULL,
  `moderated_by` int(11) DEFAULT NULL,
  `moderated_at` timestamp NULL DEFAULT NULL,
  `internal_notes` text DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `engagement_score` int(11) DEFAULT 0,
  `response_time_hours` decimal(10,2) DEFAULT NULL,
  `follow_up_required` tinyint(1) DEFAULT 0,
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`id`, `project_id`, `citizen_name`, `citizen_email`, `citizen_phone`, `subject`, `message`, `status`, `priority`, `sentiment`, `parent_comment_id`, `user_ip`, `user_agent`, `admin_response`, `responded_by`, `responded_at`, `moderated_by`, `moderated_at`, `internal_notes`, `is_featured`, `engagement_score`, `response_time_hours`, `follow_up_required`, `tags`, `attachments`, `created_at`, `updated_at`) VALUES
(1, 39, 'MS JENIFER MUHONJA', 'jenifermuhonja01@gmail.com', NULL, 'Project Comment', 'last comment test', 'approved', 'medium', 'neutral', 0, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', 'We appreciate your technical observations regarding this project. Our engineering team has reviewed your comments and would like to provide clarification on the technical aspects you mentioned. If you have specific technical expertise or additional insights, we welcome further discussion to ensure the best possible outcomes for this project.', 1, NULL, NULL, NULL, NULL, 0, 0, NULL, 0, NULL, NULL, '2025-06-19 12:20:09', '2025-06-20 09:49:14'),
(2, 39, 'hamisi', 'hamweed@gmail.com', NULL, 'Project Comment', 'another stand alone comment', 'approved', 'medium', 'neutral', 0, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', 'We appreciate your technical observations regarding this project. Our engineering team has reviewed your comments and would like to provide clarification on the technical aspects you mentioned. If you have specific technical expertise or additional insights, we welcome further discussion to ensure the best possible outcomes for this project.', 1, NULL, NULL, NULL, NULL, 0, 0, NULL, 0, NULL, NULL, '2025-06-19 13:53:35', '2025-06-20 09:49:14'),
(3, 39, 'kev', 'kev@gmail.com', NULL, 'Reply to comment', 'this is a user response', 'approved', 'medium', 'neutral', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 0, NULL, NULL, '2025-06-19 13:55:20', '2025-06-20 09:49:14'),
(5, 39, 'dopman', 'dsd@gmail.com', NULL, 'Project Comment', 'third time', 'approved', 'medium', 'neutral', 0, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', 'Your feedback is currently under review by our team. We will respond within 3-5 business days.', 1, NULL, NULL, NULL, NULL, 0, 0, NULL, 0, NULL, NULL, '2025-06-19 13:56:48', '2025-06-20 09:49:14'),
(6, 39, 'goody', 'ddogt@gmail.co', NULL, 'Project Comment', 'to be hidden', 'approved', 'medium', 'neutral', 0, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 0, NULL, NULL, '2025-06-19 13:57:19', '2025-06-20 09:49:14'),
(7, 39, 'hesh', 'hesh@gmail.com', NULL, 'Reply to comment', 'this is third replies. the other was a mistake', 'approved', 'medium', 'neutral', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 0, NULL, NULL, '2025-06-19 13:58:19', '2025-06-20 09:49:14'),
(8, 39, 'done', 'go@gmail.com', NULL, 'Reply to comment', 'good work done', 'approved', 'medium', 'neutral', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 0, NULL, NULL, '2025-06-19 13:58:44', '2025-06-20 09:49:14'),
(9, 39, 'adis', 'admis@gmail.com', NULL, 'Reply to comment', 'last attempt', 'approved', 'medium', 'neutral', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 0, NULL, NULL, '2025-06-19 13:59:53', '2025-06-20 09:49:14'),
(10, 17, 'mock', 'jenifermuhonja01@gmail.com', NULL, 'Project Comment', 'this is a mock last test', 'approved', 'medium', 'neutral', 0, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', 'Thank you for your feedback. We appreciate your input and will review it carefully.', 1, NULL, NULL, NULL, NULL, 0, 0, NULL, 0, NULL, NULL, '2025-06-19 15:12:01', '2025-06-20 09:49:14'),
(11, 45, 'MS JENIFER MUHONJA', 'jenifermuhonja01@gmail.com', NULL, 'Project Comment', 'hamisisisisisisi', 'approved', 'medium', 'neutral', 0, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 0, NULL, NULL, '2025-06-21 07:26:05', '2025-06-21 07:57:21'),
(12, 45, 'hamisi', 'hamweed@gmail.com', NULL, 'Reply to comment', '45:1646 Uncaught SyntaxError: Unexpected token &#039;', 'approved', 'medium', 'neutral', 11, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 0, NULL, NULL, '2025-06-21 07:53:07', '2025-06-21 07:57:16');

-- --------------------------------------------------------

--
-- Table structure for table `feedback_notifications`
--

CREATE TABLE `feedback_notifications` (
  `id` int(11) NOT NULL,
  `feedback_id` int(11) NOT NULL,
  `notification_type` enum('response_sent','status_updated','follow_up') NOT NULL,
  `recipient_email` varchar(255) NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `delivery_status` enum('pending','sent','failed') DEFAULT 'pending',
  `error_message` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, '6838cd091aa11_1748552969.csv', 3, 0, 3, 'Row 2: Column count mismatch\nRow 3: Column count mismatch\nRow 4: Column count mismatch', 1, '2025-05-29 18:09:29');

-- --------------------------------------------------------

--
-- Table structure for table `prepared_responses`
--

CREATE TABLE `prepared_responses` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `category` varchar(100) DEFAULT 'general',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prepared_responses`
--

INSERT INTO `prepared_responses` (`id`, `name`, `content`, `category`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Thank You', 'Thank you for your feedback. We appreciate your input and will review it carefully.', 'acknowledgment', 1, '2025-06-19 14:28:09', '2025-06-19 14:28:09'),
(2, 'Under Review', 'Your feedback is currently under review by our team. We will respond within 3-5 business days.', 'status', 1, '2025-06-19 14:28:09', '2025-06-19 14:28:09'),
(3, 'More Information Needed', 'Thank you for reaching out. To better assist you, could you please provide more specific details about your concern?', 'inquiry', 1, '2025-06-19 14:28:09', '2025-06-19 14:28:09'),
(4, 'Issue Resolved', 'Thank you for bringing this to our attention. The issue has been resolved and appropriate measures have been taken.', 'resolution', 1, '2025-06-19 14:28:09', '2025-06-19 14:28:09'),
(5, 'Project Progress Update', 'Thank you for your inquiry about the project progress. We are currently on track with our planned timeline and will provide regular updates as work continues.', 'progress', 1, '2025-06-19 14:28:09', '2025-06-19 14:28:09');

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
(6, 'Uriri Stadium', 'uriri community stadioum by hon hamisi william', 2, 2025, 1, 4, 8, 'Uriri Primary, near uriri police station', '-1.3167,34.4833', '2025-01-01', '2026-05-01', NULL, 'XYZ construction company', '0702353585', 'ongoing', 'published', 'awaiting', 57.00, 7, 0, 1, '2025-05-29 18:22:02', '2025-06-12 06:46:11', 5.00, 1),
(7, 'Uriri Stadium 2', 'ttttttttttttt', 3, 2025, 1, 1, 1, 'Uriri Primary, near uriri police station', '-0.860817,34.211262', '2024-01-02', '2026-04-05', NULL, 'XYZ construction company', '0702353585', 'ongoing', 'published', 'awaiting', 91.67, 6, 0, 1, '2025-05-29 19:01:26', '2025-06-13 13:29:01', 1.00, 1),
(8, 'rongo water reserve', 'rongo', 6, 2025, 1, 2, 4, 'Uriri Primary, near uriri police station', '-1.018725,34.448841', '2020-03-03', '2020-12-11', NULL, 'XYZ construction company', '0702353585', 'ongoing', 'published', 'awaiting', 62.50, 4, 0, 1, '2025-05-30 11:57:22', '2025-06-12 09:41:24', 5.00, 0),
(9, 'Migori County Health Center Construction', 'Construction of a modern health center to serve the local community with medical facilities and equipment', 5, 2024, 1, 2, 4, 'Migori Town Center, near the main market', '-1.0634,34.4731', '2024-01-15', '2024-12-31', NULL, 'ABC Construction Ltd', '254712345678', 'ongoing', 'published', 'awaiting', 50.00, 2, 0, 1, '2025-05-30 14:40:04', '2025-06-12 09:40:10', 4.00, 1),
(10, 'Gusi stadium', 'kisii national stadium', 2, 2025, 1, 5, 9, 'Kisii town', '-0.847086,34.544971', '2020-11-11', '2020-02-20', NULL, 'XYZ construction company', '0702353585', 'ongoing', 'published', 'awaiting', 87.50, 4, 0, 1, '2025-05-30 15:02:27', '2025-06-14 17:39:03', 3.00, 1),
(17, 'Migori-Isebania Road Improvement', 'Upgrading of 15km stretch of Migori-Isebania road with tarmac surface and proper drainage', 2, 2024, 1, 10, 14, 'Migori-Isebania Highway, Migori Town', '-1.0634,34.4731', '2024-01-15', '2024-12-31', NULL, 'Kens Construction Ltd', '254712345678', 'completed', 'published', 'awaiting', 100.00, 1, 0, 1, '2025-06-12 06:58:54', '2025-06-13 06:32:37', 5.00, 0),
(18, 'Rongo Market Upgrade', 'Construction of modern market stalls with proper sanitation and drainage facilities', 8, 2024, 1, 11, 15, 'Rongo Town Center', '-1.2345,34.6789', '2024-03-01', '2024-08-30', NULL, 'Unity Builders', '254723456789', 'ongoing', 'published', 'awaiting', 25.00, 2, 0, 1, '2025-06-12 06:58:54', '2025-06-12 09:37:16', 5.00, 0),
(19, 'Nyatike Health Center Extension', 'Addition of maternity wing and medical equipment procurement', 3, 2024, 1, 6, 16, 'Nyatike Health Center', '-1.1234,34.1234', '2024-02-01', '2024-11-30', NULL, 'Medical Contractors Kenya', '254734567890', 'ongoing', 'published', 'awaiting', 50.00, 1, 0, 1, '2025-06-12 06:58:54', '2025-06-12 09:47:00', 5.00, 0),
(20, 'Oyani gogo road construction', 'Oyani gogo road construction', 2, 2025, 1, 5, 9, 'Uriri Primary, near uriri police station', '-0.970066,34.496477', '2020-12-12', NULL, NULL, 'XYZ construction company', '254712345678', 'ongoing', 'published', 'awaiting', 50.00, 3, 0, 1, '2025-06-12 07:32:57', '2025-06-13 08:52:24', 5.00, 0),
(21, 'Oyani SDA Dispensary expansion', 'contruction of ward fercility', 9, 2025, 1, 5, 17, 'Oyani SDA', '-0.951454, 34.443863', NULL, NULL, NULL, 'ABC Construction Ltd', '727266454', 'ongoing', 'published', 'awaiting', 50.00, 1, 0, 1, '2025-06-12 10:07:41', '2025-06-12 15:25:37', 2.00, 1),
(22, 'Kaminolewe market construction', 'kaminolewe market improvement to market standards', 8, 2026, 1, 5, 17, 'Kaminolewe market', '-0.950614, 34.447275', NULL, NULL, NULL, 'ABC Construction Ltd', '726473575', 'ongoing', 'published', 'awaiting', 62.50, 4, 0, 1, '2025-06-12 10:07:41', '2025-06-13 15:51:38', 5.00, 0),
(24, 'Migori Green Park', 'Establishment of an urban recreational park with landscaping.', 2, 2024, 1, 8, 19, 'North Sakwa Ward, Kuria West', '-1.3167,34.4833', '2024-07-28', '2025-07-12', NULL, 'Unity Construction Ltd', '0728209597', 'planning', 'published', 'awaiting', 0.00, 1, 0, 1, '2025-06-14 18:02:59', '2025-06-15 08:57:49', 5.00, 0),
(25, 'Suna West ECD Centers', 'Construction of Early Childhood Development classrooms.', 6, 2026, 1, 7, 20, 'Ntimaru Ward, Rongo', '-0.970066,34.496477', '2024-08-08', '2025-11-27', NULL, 'XYZ Construction Ltd', '0785083536', 'planning', 'published', 'awaiting', 0.00, 1, 0, 1, '2025-06-14 18:02:59', '2025-06-15 08:58:47', 5.00, 0),
(26, 'North Kadem Health Post', 'Setting up a new health post to serve remote villages.', 6, 2026, 1, 8, 21, 'Kisii Central Ward, Kuria West', '-0.860817,34.211262', '2024-03-23', '2025-07-26', NULL, 'Unity Construction Ltd', '0754106837', 'planning', 'published', 'awaiting', 0.00, 1, 0, 1, '2025-06-14 18:02:59', '2025-06-15 08:58:16', 5.00, 0),
(27, 'Sakwa Agricultural Stores', 'Construction of grain and input storage facilities.', 8, 2025, 1, 5, 22, 'West Sakwa Ward, Kuria West', '-0.950614,34.447275', '2024-03-22', '2025-09-15', NULL, 'Unity Construction Ltd', '0712255056', 'planning', 'published', 'awaiting', 0.00, 1, 0, 1, '2025-06-14 18:02:59', '2025-06-15 08:59:04', 5.00, 0),
(28, 'Kanyamkago Access Roads', 'Grading and graveling of rural access roads.', 3, 2024, 1, 8, 23, 'Central Ward, Uriri', '-0.950614,34.447275', '2024-06-05', '2025-09-26', NULL, 'ABC Construction Ltd', '0753974896', 'planning', 'published', 'awaiting', 0.00, 1, 0, 1, '2025-06-14 18:02:59', '2025-06-14 18:07:03', 5.00, 0),
(29, 'Nyatike Fishing Jetty', 'Construction of a modern fishing jetty to support local fishers.', 3, 2024, 1, 6, 16, 'West Kanyamkago, Nyatike', '-0.860817,34.211262', '2024-09-05', '2025-06-20', NULL, 'Unity Construction Ltd', '0766037526', 'planning', 'published', 'awaiting', 0.00, 1, 0, 1, '2025-06-14 18:02:59', '2025-06-15 08:55:33', 5.00, 0),
(30, 'Nyamaraga Drainage Works', 'Installation of drainage culverts and trench lining.', 3, 2024, 1, 11, 24, 'Ntimaru Ward, Suna West', '-0.847086,34.544971', '2024-08-24', '2025-06-07', NULL, 'ABC Construction Ltd', '0790318794', 'ongoing', 'published', 'awaiting', 50.00, 1, 0, 1, '2025-06-14 18:02:59', '2025-06-19 16:57:52', 5.00, 0),
(31, 'Isibania Bus Park', 'Establishment of a designated bus park and passenger shelters.', 1, 2025, 1, 8, 12, 'Kisii Central Ward, Uriri', '-0.970066,34.496477', '2024-11-22', '2025-05-09', NULL, 'ABC Construction Ltd', '0753290632', 'planning', 'published', 'awaiting', 0.00, 1, 0, 1, '2025-06-14 18:02:59', '2025-06-15 08:56:24', 5.00, 0),
(32, 'Kuria West Street Lighting', 'Installation of solar-powered street lights in key trading centers.', 4, 2023, 1, 1, 25, 'Kisii Central Ward, Suna West', '-0.950614,34.447275', '2024-09-21', '2025-04-18', NULL, 'XYZ Construction Ltd', '0746505405', 'ongoing', 'published', 'awaiting', 50.00, 1, 0, 1, '2025-06-14 18:02:59', '2025-06-19 15:00:35', 5.00, 0),
(33, 'Oyani Bridge Construction', 'Construction of a reinforced concrete bridge over River Oyani.', 6, 2023, 1, 1, 26, 'North Kadem, Kuria East', '-1.018725,34.448841', '2024-12-21', '2025-12-27', NULL, 'Unity Construction Ltd', '0764861839', 'planning', 'published', 'awaiting', 0.00, 1, 0, 1, '2025-06-14 18:02:59', '2025-06-15 08:59:14', 5.00, 0),
(34, 'Rongo-Kanga Road Rehabilitation', 'Rehabilitation of the 12 km Rongo-Kanga stretch with bitumen surface.', 5, 2025, 1, 2, 27, 'North Sakwa Ward, Suna East', '-1.018725,34.448841', '2024-09-09', '2025-06-08', NULL, 'XYZ Construction Ltd', '0777425545', 'planning', 'published', 'awaiting', 0.00, 1, 0, 1, '2025-06-14 18:02:59', '2025-06-15 08:55:13', 5.00, 0),
(35, 'Kanyasa Irrigation Scheme', 'Development of irrigation infrastructure for small-scale farmers in Nyatike Sub-county.', 4, 2024, 1, 5, 28, 'Karungu Ward, Uriri', '-1.1234,34.1234', '2024-12-14', '2025-10-11', NULL, 'ABC Construction Ltd', '0726481700', 'planning', 'published', 'awaiting', 0.00, 1, 0, 1, '2025-06-14 18:02:59', '2025-06-15 08:56:44', 5.00, 0),
(36, 'Uriri Water Supply Project', 'Installation of boreholes and distribution pipelines to rural households.', 3, 2024, 1, 4, 29, 'Central Sakwa, Migori', '-1.1234,34.1234', '2024-07-22', '2025-12-15', NULL, 'Unity Construction Ltd', '0745056899', 'planning', 'published', 'awaiting', 0.00, 1, 0, 1, '2025-06-14 18:02:59', '2025-06-15 08:58:39', 5.00, 0),
(37, 'Wiga Footbridge', 'Installation of a steel footbridge for school children.', 3, 2026, 1, 4, 30, 'Kisii Central Ward, Awendo', '-1.018725,34.448841', '2024-06-10', '2025-07-14', NULL, 'ABC Construction Ltd', '0766640466', 'planning', 'published', 'awaiting', 0.00, 1, 0, 1, '2025-06-14 18:02:59', '2025-06-15 08:56:11', 5.00, 0),
(39, 'Rongo Livestock Market', 'Construction of a livestock market with holding pens and water troughs.', 6, 2025, 1, 2, 4, 'North Sakwa Ward, Migori', '-0.847086,34.544971', '2024-07-11', '2025-11-02', NULL, 'XYZ Construction Ltd', '0782029631', 'ongoing', 'published', 'awaiting', 50.00, 1, 0, 1, '2025-06-14 18:02:59', '2025-06-15 09:00:00', 5.00, 0),
(40, 'Central Sakwa Sanitation Project', 'Construction of public sanitation blocks and waste collection points.', 1, 2023, 1, 3, 32, 'South Sakwa Ward, Rongo', '-1.0634,34.4731', '2024-08-12', '2025-12-31', NULL, 'ABC Construction Ltd', '0723308090', 'planning', 'published', 'awaiting', 0.00, 1, 0, 1, '2025-06-14 18:02:59', '2025-06-14 18:06:07', 5.00, 0),
(41, 'Migori Town Drainage System', 'Construction of stormwater drainage to reduce flooding in Migori Town.', 6, 2024, 1, 7, 33, 'East Kanyamkago Ward, Migori Central', '-1.018725,34.448841', '2024-01-12', '2025-12-27', NULL, 'Unity Construction Ltd', '0786958979', 'planning', 'published', 'awaiting', 0.00, 1, 0, 1, '2025-06-14 18:02:59', '2025-06-15 08:58:03', 5.00, 0),
(42, 'God Jope Community Hall', 'Construction of a community resource and event center.', 1, 2026, 1, 11, 34, 'Kisii Central Ward, Nyatike', '-0.970066,34.496477', '2024-05-04', '2025-10-05', NULL, 'ABC Construction Ltd', '0767063162', 'ongoing', 'published', 'awaiting', 50.00, 5, 2, 1, '2025-06-14 18:02:59', '2025-06-19 15:08:16', 5.00, 0),
(43, 'Kakrao Primary School Classrooms', 'Building of 6 modern classrooms and toilet blocks.', 6, 2024, 1, 10, 35, 'Central Sakwa, Kuria East', '-0.847086,34.544971', '2024-03-27', '2025-07-24', NULL, 'Unity Construction Ltd', '0752105523', 'planning', 'published', 'awaiting', 0.00, 1, 0, 1, '2025-06-14 18:02:59', '2025-06-14 18:07:31', 5.00, 0),
(44, 'Suna East Market Stalls', 'Construction of modern stalls and drainage system at the Suna East market.', 8, 2025, 1, 6, 10, 'North Kadem, Kuria West', '-0.860817,34.211262', '2024-04-05', '2025-11-20', NULL, 'ABC Construction Ltd', '0719654918', 'planning', 'published', 'awaiting', 0.00, 1, 0, 1, '2025-06-14 18:02:59', '2025-06-15 08:58:55', 5.00, 0),
(45, 'Awendo Sub-county Hospital Upgrade', 'Expansion and modernization of facilities including maternity and emergency wards.', 3, 2023, 1, 2, 27, 'God Jope Ward, Uriri', '-0.951454,34.443863', '2024-09-21', '2025-08-03', NULL, 'Unity Construction Ltd', '0731920091', 'completed', 'published', 'awaiting', 100.00, 3, 3, 1, '2025-06-14 18:02:59', '2025-06-19 14:59:32', 5.00, 0),
(46, 'Migori Fire Station', 'Construction of a modern fire station to serve Migori County.', 8, 2023, 1, 11, 37, 'East Kanyamkago Ward, Suna East', '-0.951454,34.443863', '2024-08-22', '2025-08-18', NULL, 'Unity Construction Ltd', '0743714974', 'planning', 'published', 'awaiting', 0.00, 1, 0, 1, '2025-06-14 18:02:59', '2025-06-15 08:57:34', 5.00, 0),
(47, 'Kamagambo ICT Training Center', 'Establishment of an ICT center to provide digital skills to youth.', 4, 2023, 1, 7, 38, 'East Kanyamkago Ward, Suna West', '-0.951454,34.443863', '2024-12-07', '2025-08-10', NULL, 'Unity Construction Ltd', '0732469484', 'planning', 'published', 'awaiting', 0.00, 1, 0, 1, '2025-06-14 18:02:59', '2025-06-14 18:07:19', 5.00, 0),
(48, 'Kuria East Borehole Project', 'Drilling and equipping boreholes to improve water access.', 6, 2025, 1, 4, 18, 'West Kanyamkago, Suna West', '-1.1234,34.1234', '2024-09-24', '2025-04-01', NULL, 'XYZ Construction Ltd', '0786968006', 'ongoing', 'published', 'awaiting', 25.00, 2, 0, 1, '2025-06-20 07:30:38', '2025-06-20 07:32:55', 5.00, 0),
(49, 'Nyabisawa Dispensary Upgrade', 'Expansion of facilities and addition of a maternity wing.', 6, 2024, 1, 1, 31, 'East Kamagambo, Migori Central', '-0.970066,34.496477', '2024-07-26', '2025-05-25', NULL, 'Unity Construction Ltd', '0721919769', 'planning', 'published', 'awaiting', 0.00, 1, 0, 1, '2025-06-20 07:30:38', '2025-06-20 10:05:52', 5.00, 0);

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
(8, 6, 1, 'Survey &amp; Design', 'Road survey, traffic analysis, and engineering design', 'completed', NULL, NULL, '2025-05-30', '', '2025-05-29 18:22:02', '2025-05-30 11:49:42'),
(9, 6, 2, 'Environmental Clearance', 'Environmental impact assessment and approvals', 'in_progress', '2025-05-30', NULL, '2025-05-30', '', '2025-05-29 18:22:02', '2025-05-30 11:51:26'),
(10, 6, 3, 'Earthworks', 'Road cutting, filling, and grading', 'completed', NULL, NULL, '2025-05-30', '', '2025-05-29 18:22:02', '2025-05-30 11:50:02'),
(11, 6, 4, 'Base &amp; Sub-base', 'Laying of road base and sub-base materials', 'pending', NULL, NULL, '2025-05-30', '', '2025-05-29 18:22:02', '2025-05-30 11:51:32'),
(12, 6, 5, 'Surface &amp; Drainage', 'Tarmacking and drainage system installation', 'pending', '2025-05-30', NULL, '2025-05-30', '', '2025-05-29 18:22:02', '2025-06-05 13:25:33'),
(14, 6, 6, 'purchase of land parcel', 'purchase of land parcel', 'completed', '2025-05-30', NULL, '2025-05-30', '', '2025-05-29 18:22:02', '2025-05-29 18:24:44'),
(15, 6, 7, 'approval of the plan proposal', 'approval of proposal', 'completed', NULL, NULL, '2025-05-30', '', '2025-05-29 18:22:02', '2025-05-29 18:23:53'),
(16, 7, 2, 'Planning &amp; Design', 'Facility design and medical equipment planning', 'completed', NULL, NULL, '2025-05-30', '', '2025-05-29 19:01:26', '2025-05-29 19:01:34'),
(17, 7, 3, 'Permits &amp; Approvals', 'Building permits and health ministry approvals', 'completed', NULL, NULL, '2025-05-30', '', '2025-05-29 19:01:26', '2025-05-29 19:53:41'),
(18, 7, 4, 'Foundation &amp; Structure', 'Building foundation and structural construction', 'completed', NULL, NULL, '2025-05-30', '', '2025-05-29 19:01:26', '2025-05-29 19:53:50'),
(19, 7, 5, 'Medical Infrastructure', 'Specialized medical installations and utilities', 'completed', NULL, NULL, '2025-05-30', '', '2025-05-29 19:01:26', '2025-05-29 19:53:57'),
(20, 7, 6, 'Equipment Installation', 'Medical equipment and furniture installation', 'completed', NULL, NULL, '2025-05-30', '', '2025-05-29 19:01:26', '2025-05-29 19:54:05'),
(21, 7, 7, 'Certification &amp; Launch', 'Health certification and facility commissioning', 'in_progress', '2025-06-13', NULL, '2025-05-30', '', '2025-05-29 19:01:26', '2025-06-13 13:29:01'),
(22, 8, 1, 'procurement', '', 'completed', '2025-05-30', NULL, '2025-05-30', '', '2025-05-30 11:57:43', '2025-05-30 14:03:00'),
(23, 8, 2, 'land clearing', '', 'completed', '2025-05-30', NULL, '2025-05-31', '', '2025-05-30 11:58:07', '2025-05-30 21:05:46'),
(24, 8, 3, 'building sitestructures', '', 'in_progress', '2025-05-31', NULL, '2025-05-31', '', '2025-05-30 11:58:21', '2025-06-12 09:41:24'),
(25, 8, 4, 'commisioning', '', 'pending', '2025-05-31', NULL, '2025-05-31', '', '2025-05-30 11:58:33', '2025-05-30 21:06:15'),
(26, 9, 1, 'Project Planning & Approval', 'Initial project planning, design review, and regulatory approval process', 'completed', '2025-05-30', NULL, '2025-06-12', '', '2025-05-30 14:40:04', '2025-06-12 06:56:30'),
(27, 10, 1, 'procurement', '', 'completed', '2025-05-30', NULL, '2025-05-30', '', '2025-05-30 15:02:44', '2025-05-30 15:03:19'),
(28, 10, 2, 'land clearing', '', 'completed', '2025-06-13', NULL, '2025-06-13', '', '2025-05-30 15:02:51', '2025-06-13 16:09:20'),
(29, 10, 3, 'building sitestructures', '', 'completed', NULL, NULL, '2025-06-13', '', '2025-05-30 15:02:57', '2025-06-13 16:09:26'),
(30, 10, 4, 'commisioning', '', 'in_progress', '2025-06-14', NULL, '2025-06-13', '', '2025-05-30 15:03:07', '2025-06-14 17:39:03'),
(38, 9, 2, 'commisioning', '', 'in_progress', '2025-06-12', NULL, '2025-06-12', '', '2025-06-12 06:56:25', '2025-06-12 06:58:27'),
(39, 17, 1, 'Road Survey and Design', 'Conduct topographical survey and prepare detailed engineering designs', 'completed', '2025-06-12', NULL, '2025-06-13', '', '2025-06-12 06:58:54', '2025-06-13 06:32:37'),
(40, 18, 1, 'Site Preparation', 'Clear site and prepare foundation for market construction', 'in_progress', '2025-06-12', NULL, '2025-06-12', '', '2025-06-12 06:58:54', '2025-06-12 09:36:59'),
(41, 19, 1, 'Architectural Planning', 'Design maternity wing and plan equipment installation', 'in_progress', '2025-06-12', NULL, '2025-06-12', '', '2025-06-12 06:58:54', '2025-06-12 09:47:00'),
(42, 20, 2, 'Project Planning &amp; Approval', 'Initial project planning, design review, and regulatory approval process', 'completed', '2025-06-12', NULL, '2025-06-13', '', '2025-06-12 07:32:57', '2025-06-13 08:52:18'),
(43, 20, 3, 'Environmental Clearance', '', 'in_progress', '2025-06-13', NULL, '2025-06-12', '', '2025-06-12 07:32:57', '2025-06-13 08:52:24'),
(44, 18, 2, 'procurement', '', 'pending', '2025-06-12', NULL, NULL, '', '2025-06-12 09:36:45', '2025-06-12 09:37:16'),
(45, 20, 4, 'land clearing', '', 'pending', NULL, NULL, NULL, NULL, '2025-06-12 09:43:20', '2025-06-12 09:43:20'),
(46, 21, 1, 'Project Planning & Approval', 'Initial project planning, design review, and regulatory approval process', 'in_progress', '2025-06-12', NULL, NULL, '', '2025-06-12 10:07:41', '2025-06-12 10:08:39'),
(47, 22, 1, 'Project Planning & Approval', 'Initial project planning, design review, and regulatory approval process', 'completed', '2025-06-13', NULL, '2025-06-13', '', '2025-06-12 10:07:41', '2025-06-13 15:51:20'),
(48, 22, 2, 'procurement', '', 'completed', '2025-06-13', NULL, '2025-06-13', '', '2025-06-13 03:43:16', '2025-06-13 15:51:32'),
(49, 22, 3, 'land clearing', '', 'in_progress', '2025-06-13', NULL, NULL, '', '2025-06-13 03:43:28', '2025-06-13 15:51:38'),
(50, 22, 4, 'building sitestructures', '', 'pending', NULL, NULL, NULL, NULL, '2025-06-13 03:43:41', '2025-06-13 03:43:41'),
(52, 24, 1, 'Commissioning', 'Bidding and contract award', 'pending', NULL, NULL, NULL, NULL, '2025-06-14 18:02:59', '2025-06-14 18:02:59'),
(53, 25, 1, 'Inspection', 'Bidding and contract award', 'pending', NULL, NULL, NULL, NULL, '2025-06-14 18:02:59', '2025-06-14 18:02:59'),
(54, 26, 1, 'Commissioning', 'Bidding and contract award', 'pending', NULL, NULL, NULL, NULL, '2025-06-14 18:02:59', '2025-06-14 18:02:59'),
(55, 27, 1, 'Inspection', 'Monitoring and quality check', 'pending', NULL, NULL, NULL, NULL, '2025-06-14 18:02:59', '2025-06-14 18:02:59'),
(56, 28, 1, 'Commissioning', 'Initial planning and blueprint development', 'pending', NULL, NULL, NULL, NULL, '2025-06-14 18:02:59', '2025-06-14 18:02:59'),
(57, 29, 1, 'Construction', 'Execution of physical works', 'pending', NULL, NULL, NULL, NULL, '2025-06-14 18:02:59', '2025-06-14 18:02:59'),
(58, 30, 1, 'Site Preparation', 'Monitoring and quality check', 'in_progress', '2025-06-19', NULL, '2025-06-19', '', '2025-06-14 18:02:59', '2025-06-19 16:57:52'),
(59, 31, 1, 'Commissioning', 'Monitoring and quality check', 'pending', NULL, NULL, NULL, NULL, '2025-06-14 18:02:59', '2025-06-14 18:02:59'),
(60, 32, 1, 'Inspection', 'Execution of physical works', 'in_progress', '2025-06-19', NULL, NULL, '', '2025-06-14 18:02:59', '2025-06-19 15:00:35'),
(61, 33, 1, 'Planning & Design', 'Execution of physical works', 'pending', NULL, NULL, NULL, NULL, '2025-06-14 18:02:59', '2025-06-14 18:02:59'),
(62, 34, 1, 'Site Preparation', 'Monitoring and quality check', 'pending', NULL, NULL, NULL, NULL, '2025-06-14 18:02:59', '2025-06-14 18:02:59'),
(63, 35, 1, 'Planning & Design', 'Bidding and contract award', 'pending', NULL, NULL, NULL, NULL, '2025-06-14 18:02:59', '2025-06-14 18:02:59'),
(64, 36, 1, 'Inspection', 'Execution of physical works', 'pending', NULL, NULL, NULL, NULL, '2025-06-14 18:02:59', '2025-06-14 18:02:59'),
(65, 37, 1, 'Inspection', 'Handover to stakeholders', 'pending', NULL, NULL, NULL, NULL, '2025-06-14 18:02:59', '2025-06-14 18:02:59'),
(67, 39, 1, 'Procurement', 'Handover to stakeholders', 'in_progress', '2025-06-15', NULL, NULL, '', '2025-06-14 18:02:59', '2025-06-15 09:00:00'),
(68, 40, 1, 'Commissioning', 'Bidding and contract award', 'pending', NULL, NULL, NULL, NULL, '2025-06-14 18:02:59', '2025-06-14 18:02:59'),
(69, 41, 1, 'Inspection', 'Bidding and contract award', 'pending', NULL, NULL, NULL, NULL, '2025-06-14 18:02:59', '2025-06-14 18:02:59'),
(70, 42, 1, 'Planning & Design', 'Initial planning and blueprint development', 'completed', '2025-06-19', NULL, '2025-06-19', '', '2025-06-14 18:02:59', '2025-06-19 15:08:04'),
(71, 43, 1, 'Inspection', 'Bidding and contract award', 'pending', NULL, NULL, NULL, NULL, '2025-06-14 18:02:59', '2025-06-14 18:02:59'),
(72, 44, 1, 'Commissioning', 'Bidding and contract award', 'pending', NULL, NULL, NULL, NULL, '2025-06-14 18:02:59', '2025-06-14 18:02:59'),
(73, 45, 1, 'Commissioning', 'Monitoring and quality check', 'completed', '2025-06-15', NULL, '2025-06-19', '', '2025-06-14 18:02:59', '2025-06-19 14:59:13'),
(74, 46, 1, 'Site Preparation', 'Handover to stakeholders', 'pending', NULL, NULL, NULL, NULL, '2025-06-14 18:02:59', '2025-06-14 18:02:59'),
(75, 47, 1, 'Commissioning', 'Clearing and leveling the project site', 'pending', NULL, NULL, NULL, NULL, '2025-06-14 18:02:59', '2025-06-14 18:02:59'),
(76, 45, 2, 'procurement', '', 'completed', '2025-06-19', NULL, '2025-06-19', '', '2025-06-19 14:58:49', '2025-06-19 14:59:26'),
(77, 45, 3, 'building sitestructures', '', 'completed', NULL, NULL, '2025-06-19', '', '2025-06-19 14:58:57', '2025-06-19 14:59:32'),
(78, 42, 2, 'procurement', '', 'completed', '2025-06-19', NULL, '2025-06-19', '', '2025-06-19 15:06:26', '2025-06-19 15:08:09'),
(79, 42, 3, 'building sitestructures', '', 'in_progress', '2025-06-19', NULL, NULL, '', '2025-06-19 15:06:35', '2025-06-19 15:08:16'),
(80, 42, 4, 'commisioning', '', 'pending', NULL, NULL, NULL, NULL, '2025-06-19 15:07:08', '2025-06-19 15:07:08'),
(81, 42, 5, 'last step test', '', 'pending', NULL, NULL, NULL, NULL, '2025-06-19 15:07:31', '2025-06-19 15:07:31'),
(84, 48, 1, 'Inspection', 'Monitoring and quality check', 'in_progress', '2025-06-20', NULL, NULL, '', '2025-06-20 07:30:38', '2025-06-20 07:32:55'),
(85, 49, 1, 'Procurement', 'Bidding and contract award', 'pending', NULL, NULL, NULL, NULL, '2025-06-20 07:30:38', '2025-06-20 07:30:38'),
(86, 48, 2, 'procurement', '', 'pending', NULL, NULL, NULL, NULL, '2025-06-20 07:32:43', '2025-06-20 07:32:43');

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
(1, 'Rongo', 1, '2025-06-21 09:39:15'),
(2, 'Awendo', 1, '2025-06-21 09:39:15'),
(3, 'Suna East', 1, '2025-06-21 09:39:15'),
(4, 'Suna West', 1, '2025-06-21 09:39:15'),
(5, 'Uriri', 1, '2025-06-21 09:39:15'),
(6, 'Kuria East', 1, '2025-06-21 09:39:15'),
(7, 'Nyatike', 1, '2025-06-21 09:39:15'),
(8, 'Kuria West', 1, '2025-06-21 09:39:15');

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
(1, 'North Kamagambo', 1, '2025-06-21 09:39:15'),
(2, 'Central Kamagambo', 1, '2025-06-21 09:39:15'),
(3, 'East Kamagambo', 1, '2025-06-21 09:39:15'),
(4, 'South Kamagambo', 1, '2025-06-21 09:39:15'),
(5, 'North East Sakwa', 2, '2025-06-21 09:39:15'),
(6, 'South Sakwa', 2, '2025-06-21 09:39:15'),
(7, 'West Sakwa', 2, '2025-06-21 09:39:15'),
(8, 'Central Sakwa', 2, '2025-06-21 09:39:15'),
(9, 'God Jope', 3, '2025-06-21 09:39:15'),
(10, 'Suna Central', 3, '2025-06-21 09:39:15'),
(11, 'Kakrao', 3, '2025-06-21 09:39:15'),
(12, 'Kwa', 3, '2025-06-21 09:39:15'),
(13, 'Wiga', 4, '2025-06-21 09:39:15'),
(14, 'Wasweta II', 4, '2025-06-21 09:39:15'),
(15, 'Ragana-Oruba', 4, '2025-06-21 09:39:15'),
(16, 'Wasimbete', 4, '2025-06-21 09:39:15'),
(17, 'West Kanyamkago', 5, '2025-06-21 09:39:15'),
(18, 'North Kanyamkago', 5, '2025-06-21 09:39:15'),
(19, 'Central Kanyamkago', 5, '2025-06-21 09:39:15'),
(20, 'South Kanyamkago', 5, '2025-06-21 09:39:15'),
(21, 'East Kanyamkago', 5, '2025-06-21 09:39:15'),
(22, 'Gokeharaka/Getamwega', 6, '2025-06-21 09:39:15'),
(23, 'Ntimaru West', 6, '2025-06-21 09:39:15'),
(24, 'Ntimaru East', 6, '2025-06-21 09:39:15'),
(25, 'Nyabasi East', 6, '2025-06-21 09:39:15'),
(26, 'Nyabasi West', 6, '2025-06-21 09:39:15'),
(27, 'Kachieng', 7, '2025-06-21 09:39:15'),
(28, 'Kanyasa', 7, '2025-06-21 09:39:15'),
(29, 'North Kadem', 7, '2025-06-21 09:39:15'),
(30, 'Macalder/Kanyarwanda', 7, '2025-06-21 09:39:15'),
(31, 'Kaler', 7, '2025-06-21 09:39:15'),
(32, 'Got Kachola', 7, '2025-06-21 09:39:15'),
(33, 'Muhuru', 7, '2025-06-21 09:39:15'),
(34, 'Bukira East', 8, '2025-06-21 09:39:15'),
(35, 'Bukira Central/Ikerege', 8, '2025-06-21 09:39:15'),
(36, 'Isibania', 8, '2025-06-21 09:39:15'),
(37, 'Makerero', 8, '2025-06-21 09:39:15'),
(38, 'Masaba', 8, '2025-06-21 09:39:15'),
(39, 'Tagare', 8, '2025-06-21 09:39:15'),
(40, 'Nyamosense/Komosoko', 8, '2025-06-21 09:39:15');

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
  ADD KEY `responded_by` (`responded_by`),
  ADD KEY `moderated_by` (`moderated_by`),
  ADD KEY `idx_project_id` (`project_id`),
  ADD KEY `idx_parent_comment_id` (`parent_comment_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_user_ip` (`user_ip`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_project_status` (`project_id`,`status`);

--
-- Indexes for table `feedback_notifications`
--
ALTER TABLE `feedback_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_feedback_notifications_feedback_id` (`feedback_id`),
  ADD KEY `idx_feedback_notifications_status` (`delivery_status`);

--
-- Indexes for table `import_logs`
--
ALTER TABLE `import_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `imported_by` (`imported_by`);

--
-- Indexes for table `prepared_responses`
--
ALTER TABLE `prepared_responses`
  ADD PRIMARY KEY (`id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `feedback_notifications`
--
ALTER TABLE `feedback_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `import_logs`
--
ALTER TABLE `import_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `prepared_responses`
--
ALTER TABLE `prepared_responses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `project_steps`
--
ALTER TABLE `project_steps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
