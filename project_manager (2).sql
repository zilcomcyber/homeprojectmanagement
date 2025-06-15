-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 15, 2025 at 07:04 PM
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
(1, 'Hamisi', 'hamisi@gmail.com', '$2y$10$7aGT4Cz5FxEoUm/zxaUb7efAy85wF65eNjWQup98.83wPMXF7RxHW', 'super_admin', 1, '2025-05-29 18:20:11', '2025-06-15 16:38:53', '::1'),
(2, 'MS JENIFER MUHONJA', 'hamweed68@gmail.com', '$2y$10$usiBnlbLyZkR7PbZ8DRV/ekH7ciugp.T8Qw1Wvk6q5pmG2qvLc7Fm', 'admin', 1, '2025-06-13 10:00:04', '2025-06-15 05:52:08', '::1');

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

-- --------------------------------------------------------

--
-- Table structure for table `feedback_analytics`
--

CREATE TABLE `feedback_analytics` (
  `id` int(11) NOT NULL,
  `feedback_id` int(11) NOT NULL,
  `action_type` enum('view','response','approve','reject','escalate') NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `action_timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `additional_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`additional_data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Table structure for table `feedback_templates`
--

CREATE TABLE `feedback_templates` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `subject` varchar(500) DEFAULT NULL,
  `content` text NOT NULL,
  `category` enum('thank_you','under_review','more_info','resolved','custom') DEFAULT 'custom',
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback_templates`
--

INSERT INTO `feedback_templates` (`id`, `name`, `subject`, `content`, `category`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Thank You Response', 'Thank you for your feedback', 'Thank you for your feedback', 'thank_you', 1, NULL, '2025-06-14 20:23:57', '2025-06-14 20:23:57'),
(2, 'Under Review', 'Under review', 'Under review', 'under_review', 1, NULL, '2025-06-14 20:23:57', '2025-06-14 20:23:57'),
(3, 'More Information Needed', 'More information needed', 'More information needed', 'more_info', 1, NULL, '2025-06-14 20:23:57', '2025-06-14 20:23:57'),
(4, 'Issue Resolved', 'Issue resolved', 'Issue resolved', 'resolved', 1, NULL, '2025-06-14 20:23:57', '2025-06-14 20:23:57'),
(5, 'Will Forward', 'Will forward to relevant department', 'Will forward to relevant department', 'forward', 1, NULL, '2025-06-14 20:23:57', '2025-06-14 20:23:57'),
(6, 'Noted', 'Noted and appreciated', 'Noted and appreciated', 'noted', 1, NULL, '2025-06-14 20:23:57', '2025-06-14 20:23:57');

--
-- Dumping data for table `feedback_templates`
--
-- Insert sample feedback templates for admin responses
INSERT INTO feedback_templates (name, content, created_at) VALUES
('Under Review', 'Thank you for bringing this matter to our attention. Your feedback regarding this project is currently under thorough review by our project management team. We will carefully assess your concerns and take appropriate action where necessary. We appreciate your engagement in helping us improve our community projects.', NOW()),
('Thank You Response', 'We sincerely appreciate you taking the time to share your valuable feedback about this project. Community input like yours is essential for ensuring our projects meet the needs and expectations of the residents we serve. Your participation helps us build better infrastructure and services for everyone in our community.', NOW()),
('More Information Needed', 'Thank you for your feedback. To help us better understand and address your specific concerns about this project, we would appreciate if you could provide additional details or clarification. This will enable our team to investigate the matter thoroughly and provide you with a more comprehensive response. Please feel free to contact us with any additional information.', NOW()),
('Issue Resolved', 'We are pleased to inform you that the issue you reported regarding this project has been successfully addressed and resolved. Our team has taken the necessary corrective measures to ensure the matter does not recur. We thank you for bringing this to our attention and for your patience while we worked to resolve it.', NOW()),
('Follow Up Required', 'Thank you for your feedback about this project. We acknowledge the importance of the concerns you have raised and want to assure you that we are taking them seriously. Our team will conduct a detailed follow-up investigation and will get back to you with a comprehensive response within the next few business days. We appreciate your patience as we work to address your concerns properly.', NOW()),
('Project Update', 'Thank you for your interest in this project. We wanted to provide you with an update on the current status and progress. The project team is working diligently to ensure all phases are completed according to schedule and quality standards. We will continue to keep the community informed of any significant developments or changes to the timeline.', NOW()),
('Technical Clarification', 'We appreciate your technical observations regarding this project. Our engineering team has reviewed your comments and would like to provide clarification on the technical aspects you mentioned. If you have specific technical expertise or additional insights, we welcome further discussion to ensure the best possible outcomes for this project.', NOW()),
('Budget Inquiry Response', 'Thank you for your inquiry about the financial aspects of this project. We understand the importance of transparency in how public funds are utilized. Detailed budget information and expenditure reports are available through our public records, and we are committed to ensuring responsible use of community resources throughout this project.', NOW());

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
(7, 'Uriri Stadium 2', 'ttttttttttttt', 3, 2025, 1, 1, 1, 'Uriri Primary, near uriri police station', '-0.860817,34.211262', '2024-01-02', '2026-04-05', NULL, 'XYZ construction company', '0702353585', 'ongoing', 'published', 'awaiting', 91.67, 6, 0, 1, '2025-05-29 22:01:26', '2025-06-13 16:29:01', 1.00, 1),
(8, 'rongo water reserve', 'rongo', 6, 2025, 1, 2, 4, 'Uriri Primary, near uriri police station', '-1.018725,34.448841', '2020-03-03', '2020-12-11', NULL, 'XYZ construction company', '0702353585', 'ongoing', 'published', 'awaiting', 62.50, 4, 0, 1, '2025-05-30 14:57:22', '2025-06-12 12:41:24', 5.00, 0),
(9, 'Migori County Health Center Construction', 'Construction of a modern health center to serve the local community with medical facilities and equipment', 5, 2024, 1, 2, 4, 'Migori Town Center, near the main market', '-1.0634,34.4731', '2024-01-15', '2024-12-31', NULL, 'ABC Construction Ltd', '254712345678', 'ongoing', 'published', 'awaiting', 50.00, 2, 0, 1, '2025-05-30 17:40:04', '2025-06-12 12:40:10', 4.00, 1),
(10, 'Gusi stadium', 'kisii national stadium', 2, 2025, 1, 5, 9, 'Kisii town', '-0.847086,34.544971', '2020-11-11', '2020-02-20', NULL, 'XYZ construction company', '0702353585', 'ongoing', 'published', 'awaiting', 87.50, 4, 0, 1, '2025-05-30 18:02:27', '2025-06-14 20:39:03', 3.00, 1),
(17, 'Migori-Isebania Road Improvement', 'Upgrading of 15km stretch of Migori-Isebania road with tarmac surface and proper drainage', 2, 2024, 1, 10, 14, 'Migori-Isebania Highway, Migori Town', '-1.0634,34.4731', '2024-01-15', '2024-12-31', NULL, 'Kens Construction Ltd', '254712345678', 'completed', 'published', 'awaiting', 100.00, 1, 0, 1, '2025-06-12 09:58:54', '2025-06-13 09:32:37', 5.00, 0),
(18, 'Rongo Market Upgrade', 'Construction of modern market stalls with proper sanitation and drainage facilities', 8, 2024, 1, 11, 15, 'Rongo Town Center', '-1.2345,34.6789', '2024-03-01', '2024-08-30', NULL, 'Unity Builders', '254723456789', 'ongoing', 'published', 'awaiting', 25.00, 2, 0, 1, '2025-06-12 09:58:54', '2025-06-12 12:37:16', 5.00, 0),
(19, 'Nyatike Health Center Extension', 'Addition of maternity wing and medical equipment procurement', 3, 2024, 1, 6, 16, 'Nyatike Health Center', '-1.1234,34.1234', '2024-02-01', '2024-11-30', NULL, 'Medical Contractors Kenya', '254734567890', 'ongoing', 'published', 'awaiting', 50.00, 1, 0, 1, '2025-06-12 09:58:54', '2025-06-12 12:47:00', 5.00, 0),
(20, 'Oyani gogo road construction', 'Oyani gogo road construction', 2, 2025, 1, 5, 9, 'Uriri Primary, near uriri police station', '-0.970066,34.496477', '2020-12-12', NULL, NULL, 'XYZ construction company', '254712345678', 'ongoing', 'published', 'awaiting', 50.00, 3, 0, 1, '2025-06-12 10:32:57', '2025-06-13 11:52:24', 5.00, 0),
(21, 'Oyani SDA Dispensary expansion', 'contruction of ward fercility', 9, 2025, 1, 5, 17, 'Oyani SDA', '-0.951454, 34.443863', NULL, NULL, NULL, 'ABC Construction Ltd', '727266454', 'ongoing', 'published', 'awaiting', 50.00, 1, 0, 1, '2025-06-12 13:07:41', '2025-06-12 18:25:37', 2.00, 1),
(22, 'Kaminolewe market construction', 'kaminolewe market improvement to market standards', 8, 2026, 1, 5, 17, 'Kaminolewe market', '-0.950614, 34.447275', NULL, NULL, NULL, 'ABC Construction Ltd', '726473575', 'ongoing', 'published', 'awaiting', 62.50, 4, 0, 1, '2025-06-12 13:07:41', '2025-06-13 18:51:38', 5.00, 0),
(23, 'Kuria East Borehole Project', 'Drilling and equipping boreholes to improve water access.', 6, 2025, 1, 4, 18, 'West Kanyamkago, Suna West', '-1.1234,34.1234', '2024-09-24', '2025-04-01', NULL, 'XYZ Construction Ltd', '0786968006', 'planning', 'published', 'awaiting', 0.00, 1, 0, 1, '2025-06-14 21:02:59', '2025-06-15 11:57:01', 5.00, 0),
(24, 'Migori Green Park', 'Establishment of an urban recreational park with landscaping.', 2, 2024, 1, 8, 19, 'North Sakwa Ward, Kuria West', '-1.3167,34.4833', '2024-07-28', '2025-07-12', NULL, 'Unity Construction Ltd', '0728209597', 'planning', 'published', 'awaiting', 0.00, 1, 0, 1, '2025-06-14 21:02:59', '2025-06-15 11:57:49', 5.00, 0),
(25, 'Suna West ECD Centers', 'Construction of Early Childhood Development classrooms.', 6, 2026, 1, 7, 20, 'Ntimaru Ward, Rongo', '-0.970066,34.496477', '2024-08-08', '2025-11-27', NULL, 'XYZ Construction Ltd', '0785083536', 'planning', 'published', 'awaiting', 0.00, 1, 0, 1, '2025-06-14 21:02:59', '2025-06-15 11:58:47', 5.00, 0),
(26, 'North Kadem Health Post', 'Setting up a new health post to serve remote villages.', 6, 2026, 1, 8, 21, 'Kisii Central Ward, Kuria West', '-0.860817,34.211262', '2024-03-23', '2025-07-26', NULL, 'Unity Construction Ltd', '0754106837', 'planning', 'published', 'awaiting', 0.00, 1, 0, 1, '2025-06-14 21:02:59', '2025-06-15 11:58:16', 5.00, 0),
(27, 'Sakwa Agricultural Stores', 'Construction of grain and input storage facilities.', 8, 2025, 1, 5, 22, 'West Sakwa Ward, Kuria West', '-0.950614,34.447275', '2024-03-22', '2025-09-15', NULL, 'Unity Construction Ltd', '0712255056', 'planning', 'published', 'awaiting', 0.00, 1, 0, 1, '2025-06-14 21:02:59', '2025-06-15 11:59:04', 5.00, 0),
(28, 'Kanyamkago Access Roads', 'Grading and graveling of rural access roads.', 3, 2024, 1, 8, 23, 'Central Ward, Uriri', '-0.950614,34.447275', '2024-06-05', '2025-09-26', NULL, 'ABC Construction Ltd', '0753974896', 'planning', 'published', 'awaiting', 0.00, 1, 0, 1, '2025-06-14 21:02:59', '2025-06-14 21:07:03', 5.00, 0),
(29, 'Nyatike Fishing Jetty', 'Construction of a modern fishing jetty to support local fishers.', 3, 2024, 1, 6, 16, 'West Kanyamkago, Nyatike', '-0.860817,34.211262', '2024-09-05', '2025-06-20', NULL, 'Unity Construction Ltd', '0766037526', 'planning', 'published', 'awaiting', 0.00, 1, 0, 1, '2025-06-14 21:02:59', '2025-06-15 11:55:33', 5.00, 0),
(30, 'Nyamaraga Drainage Works', 'Installation of drainage culverts and trench lining.', 3, 2024, 1, 11, 24, 'Ntimaru Ward, Suna West', '-0.847086,34.544971', '2024-08-24', '2025-06-07', NULL, 'ABC Construction Ltd', '0790318794', 'planning', 'published', 'awaiting', 0.00, 1, 0, 1, '2025-06-14 21:02:59', '2025-06-15 11:55:53', 5.00, 0),
(31, 'Isibania Bus Park', 'Establishment of a designated bus park and passenger shelters.', 1, 2025, 1, 8, 12, 'Kisii Central Ward, Uriri', '-0.970066,34.496477', '2024-11-22', '2025-05-09', NULL, 'ABC Construction Ltd', '0753290632', 'planning', 'published', 'awaiting', 0.00, 1, 0, 1, '2025-06-14 21:02:59', '2025-06-15 11:56:24', 5.00, 0),
(32, 'Kuria West Street Lighting', 'Installation of solar-powered street lights in key trading centers.', 4, 2023, 1, 1, 25, 'Kisii Central Ward, Suna West', '-0.950614,34.447275','2024-09-21', '2025-04-18', NULL, 'XYZ Construction Ltd', '0746505405', 'planning', 'published', 'awaiting', 0.00, 1, 0, 1, '2025-06-14 21:02:59', '2025-06-15 11:57:21', 5.00, 0),
(33, 'Oyani Bridge Construction', 'Construction of a reinforced concrete bridge over River Oyani.', 6, 2023, 1, 1, 26, 'North Kadem, Kuria East', '-1.018725,34.448841', '2024-12-21', '2025-12-27', NULL, 'Unity Construction Ltd', '0764861839', 'planning', 'published', 'awaiting', 0.00, 1, 0, 1, '2025-06-14 21:02:59', '2025-06-15 11:59:14', 5.00, 0),
(34, 'Rongo-Kanga Road Rehabilitation', 'Rehabilitation of the 12 km Rongo-Kanga stretch with bitumen surface.', 5, 2025, 1, 2, 27, 'North Sakwa Ward, Suna East', '-1.018725,34.448841', '2024-09-09', '2025-06-08', NULL, 'XYZ Construction Ltd', '0777425545', 'planning', 'published', 'awaiting', 0.00, 1, 0, 1, '2025-06-14 21:02:59', '2025-06-15 11:55:13', 5.00, 0),
(35, 'Kanyasa Irrigation Scheme', 'Development of irrigation infrastructure for small-scale farmers in Nyatike Sub-county.', 4, 2024, 1, 5, 28, 'Karungu Ward, Uriri', '-1.1234,34.1234', '2024-12-14', '2025-10-11', NULL, 'ABC Construction Ltd', '0726481700', 'planning', 'published', 'awaiting', 0.00, 1, 0, 1, '2025-06-14 21:02:59', '2025-06-15 11:56:44', 5.00, 0),
(36, 'Uriri Water Supply Project', 'Installation of boreholes and distribution pipelines to rural households.', 3, 2024, 1, 4, 29, 'Central Sakwa, Migori', '-1.1234,34.1234', '2024-07-22', '2025-12-15', NULL, 'Unity Construction Ltd', '0745056899', 'planning', 'published', 'awaiting', 0.00, 1, 0, 1, '2025-06-14 21:02:59', '2025-06-15 11:58:39', 5.00, 0),
(37, 'Wiga Footbridge', 'Installation of a steel footbridge for school children.', 3, 2026, 1, 4, 30, 'Kisii Central Ward, Awendo', '-1.018725,34.448841', '2024-06-10', '2025-07-14', NULL, 'ABC Construction Ltd', '0766640466', 'planning', 'published', 'awaiting', 0.00, 1, 0, 1, '2025-06-14 21:02:59', '2025-06-15 11:56:11', 5.00, 0),
(38, 'Nyabisawa Dispensary Upgrade', 'Expansion of facilities and addition of a maternity wing.', 6, 2024, 1, 1, 31, 'East Kamagambo, Migori Central', '-0.970066,34.496477', '2024-07-26', '2025-05-25', NULL, 'Unity Construction Ltd', '0721919769', 'planning', 'published', 'awaiting', 0.00, 1, 0, 1, '2025-06-14 21:02:59', '2025-06-15 11:59:34', 5.00, 0),
(39, 'Rongo Livestock Market', 'Construction of a livestock market with holding pens and water troughs.', 6, 2025, 1, 2, 4, 'North Sakwa Ward, Migori', '-0.847086,34.544971', '2024-07-11', '2025-11-02', NULL, 'XYZ Construction Ltd', '0782029631', 'ongoing', 'published', 'awaiting', 50.00, 1, 0, 1, '2025-06-14 21:02:59', '2025-06-15 12:00:00', 5.00, 0),
(40, 'Central Sakwa Sanitation Project', 'Construction of public sanitation blocks and waste collection points.', 1, 2023, 1, 3, 32, 'South Sakwa Ward, Rongo', '-1.0634,34.4731', '2024-08-12', '2025-12-31', NULL, 'ABC Construction Ltd', '0723308090', 'planning', 'published', 'awaiting', 0.00, 1, 0, 1, '2025-06-14 21:02:59', '2025-06-14 21:06:07', 5.00, 0),
(41, 'Migori Town Drainage System', 'Construction of stormwater drainage to reduce flooding in Migori Town.', 6, 2024, 1, 7, 33, 'East Kanyamkago Ward, Migori Central', '-1.018725,34.448841', '2024-01-12', '2025-12-27', NULL, 'Unity Construction Ltd', '0786958979', 'planning', 'published', 'awaiting', 0.00, 1, 0, 1, '2025-06-14 21:02:59', '2025-06-15 11:58:03', 5.00, 0),
(42, 'God Jope Community Hall', 'Construction of a community resource and event center.', 1, 2026, 1, 11, 34, 'Kisii Central Ward, Nyatike', '-0.970066,34.496477', '2024-05-04', '2025-10-05', NULL, 'ABC Construction Ltd', '0767063162', 'planning', 'published', 'awaiting', 0.00, 1, 0, 1, '2025-06-14 21:02:59', '2025-06-14 21:06:31', 5.00, 0),
(43, 'Kakrao Primary School Classrooms', 'Building of 6 modern classrooms and toilet blocks.', 6, 2024, 1, 10, 35, 'Central Sakwa, Kuria East', '-0.847086,34.544971', '2024-03-27', '2025-07-24', NULL, 'Unity Construction Ltd', '0752105523', 'planning', 'published', 'awaiting', 0.00, 1, 0, 1, '2025-06-14 21:02:59', '2025-06-14 21:07:31', 5.00, 0),
(44, 'Suna East Market Stalls', 'Construction of modern stalls and drainage system at the Suna East market.', 8, 2025, 1, 6, 10, 'North Kadem, Kuria West', '-0.860817,34.211262', '2024-04-05', '2025-11-20', NULL, 'ABC Construction Ltd', '0719654918', 'planning', 'published', 'awaiting', 0.00, 1, 0, 1, '2025-06-14 21:02:59', '2025-06-15 11:58:55', 5.00, 0),
(45, 'Awendo Sub-county Hospital Upgrade', 'Expansion and modernization of facilities including maternity and emergency wards.', 3, 2023, 1, 7, 36, 'God Jope Ward, Uriri', '-0.951454,34.443863', '2024-09-21', '2025-08-03', NULL, 'Unity Construction Ltd', '0731920091', 'planning', 'published', 'awaiting', 0.00, 1, 0, 1, '2025-06-14 21:02:59', '2025-06-14 21:04:45', 5.00, 0),
(46, 'Migori Fire Station', 'Construction of a modern fire station to serve Migori County.', 8, 2023, 1, 11, 37, 'East Kanyamkago Ward, Suna East', '-0.951454,34.443863', '2024-08-22', '2025-08-18', NULL, 'Unity Construction Ltd', '0743714974', 'planning', 'published', 'awaiting', 0.00, 1, 0, 1, '2025-06-14 21:02:59', '2025-06-15 11:57:34', 5.00, 0),
(47, 'Kamagambo ICT Training Center', 'Establishment of an ICT center to provide digital skills to youth.', 4, 2023, 1, 7, 38, 'East Kanyamkago Ward, Suna West', '-0.951454,34.443863', '2024-12-07', '2025-08-10', NULL, 'Unity Construction Ltd', '0732469484', 'planning', 'published', 'awaiting', 0.00, 1, 0, 1, '2025-06-14 21:02:59', '2025-06-14 21:07:19', 5.00, 0);

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
(21, 7, 7, 'Certification &amp; Launch', 'Health certification and facility commissioning', 'in_progress', '2025-06-13', NULL, '2025-05-30', '', '2025-05-29 22:01:26', '2025-06-13 16:29:01'),
(22, 8, 1, 'procurement', '', 'completed', '2025-05-30', NULL, '2025-05-30', '', '2025-05-30 14:57:43', '2025-05-30 17:03:00'),
(23, 8, 2, 'land clearing', '', 'completed', '2025-05-30', NULL, '2025-05-31', '', '2025-05-30 14:58:07', '2025-05-31 00:05:46'),
(24, 8, 3, 'building sitestructures', '', 'in_progress', '2025-05-31', NULL, '2025-05-31', '', '2025-05-30 14:58:21', '2025-06-12 12:41:24'),
(25, 8, 4, 'commisioning', '', 'pending', '2025-05-31', NULL, '2025-05-31', '', '2025-05-30 14:58:33', '2025-05-31 00:06:15'),
(26, 9, 1, 'Project Planning & Approval', 'Initial project planning, design review, and regulatory approval process', 'completed', '2025-05-30', NULL, '2025-06-12', '', '2025-05-30 17:40:04', '2025-06-12 09:56:30'),
(27, 10, 1, 'procurement', '', 'completed', '2025-05-30', NULL, '2025-05-30', '', '2025-05-30 18:02:44', '2025-05-30 18:03:19'),
(28, 10, 2, 'land clearing', '', 'completed', '2025-06-13', NULL, '2025-06-13', '', '2025-05-30 18:02:51', '2025-06-13 19:09:20'),
(29, 10, 3, 'building sitestructures', '', 'completed', NULL, NULL, '2025-06-13', '', '2025-05-30 18:02:57', '2025-06-13 19:09:26'),
(30, 10, 4, 'commisioning', '', 'in_progress', '2025-06-14', NULL, '2025-06-13', '', '2025-05-30 18:03:07', '2025-06-14 20:39:03'),
(38, 9, 2, 'commisioning', '', 'in_progress', '2025-06-12', NULL, '2025-06-12', '', '2025-06-12 09:56:25', '2025-06-12 09:58:27'),
(39, 17, 1, 'Road Survey and Design', 'Conduct topographical survey and prepare detailed engineering designs', 'completed', '2025-06-12', NULL, '2025-06-13', '', '2025-06-12 09:58:54', '2025-06-13 09:32:37'),
(40, 18, 1, 'Site Preparation', 'Clear site and prepare foundation for market construction', 'in_progress', '2025-06-12', NULL, '2025-06-12', '', '2025-06-12 09:58:54', '2025-06-12 12:36:59'),
(41, 19, 1, 'Architectural Planning', 'Design maternity wing and plan equipment installation', 'in_progress', '2025-06-12', NULL, '2025-06-12', '', '2025-06-12 09:58:54', '2025-06-12 12:47:00'),
(42, 20, 2, 'Project Planning &amp; Approval', 'Initial project planning, design review, and regulatory approval process', 'completed', '2025-06-12', NULL, '2025-06-13', '', '2025-06-12 10:32:57', '2025-06-13 11:52:18'),
(43, 20, 3, 'Environmental Clearance', '', 'in_progress', '2025-06-13', NULL, '2025-06-12', '', '2025-06-12 10:32:57', '2025-06-13 11:52:24'),
(44, 18, 2, 'procurement', '', 'pending', '2025-06-12', NULL, NULL, '', '2025-06-12 12:36:45', '2025-06-12 12:37:16'),
(45, 20, 4, 'land clearing', '', 'pending', NULL, NULL, NULL, NULL, '2025-06-12 12:43:20', '2025-06-12 12:43:20'),
(46, 21, 1, 'Project Planning & Approval', 'Initial project planning, design review, and regulatory approval process', 'in_progress', '2025-06-12', NULL, NULL, '', '2025-06-12 13:07:41', '2025-06-12 13:08:39'),
(47, 22, 1, 'Project Planning & Approval', 'Initial project planning, design review, and regulatory approval process', 'completed', '2025-06-13', NULL, '2025-06-13', '', '2025-06-12 13:07:41', '2025-06-13 18:51:20'),
(48, 22, 2, 'procurement', '', 'completed', '2025-06-13', NULL, '2025-06-13', '', '2025-06-13 06:43:16', '2025-06-13 18:51:32'),
(49, 22, 3, 'land clearing', '', 'in_progress', '2025-06-13', NULL, NULL, '', '2025-06-13 06:43:28', '2025-06-13 18:51:38'),
(50, 22, 4, 'building sitestructures', '', 'pending', NULL, NULL, NULL, NULL, '2025-06-13 06:43:41', '2025-06-13 06:43:41'),
(51, 23, 1, 'Inspection', 'Monitoring and quality check', 'pending', NULL, NULL, NULL, NULL, '2025-06-14 21:02:59', '2025-06-14 21:02:59'),
(52, 24, 1, 'Commissioning', 'Bidding and contract award', 'pending', NULL, NULL, NULL, NULL, '2025-06-14 21:02:59', '2025-06-14 21:02:59'),
(53, 25, 1, 'Inspection', 'Bidding and contract award', 'pending', NULL, NULL, NULL, NULL, '2025-06-14 21:02:59', '2025-06-14 21:02:59'),
(54, 26, 1, 'Commissioning', 'Bidding and contract award', 'pending', NULL, NULL, NULL, NULL, '2025-06-14 21:02:59', '2025-06-14 21:02:59'),
(55, 27, 1, 'Inspection', 'Monitoring and quality check', 'pending', NULL, NULL, NULL, NULL, '2025-06-14 21:02:59', '2025-06-14 21:02:59'),
(56, 28, 1, 'Commissioning', 'Initial planning and blueprint development', 'pending', NULL, NULL, NULL, NULL, '2025-06-14 21:02:59', '2025-06-14 21:02:59'),
(57, 29, 1, 'Construction', 'Execution of physical works', 'pending', NULL, NULL, NULL, NULL, '2025-06-14 21:02:59', '2025-06-14 21:02:59'),
(58, 30, 1, 'Site Preparation', 'Monitoring and quality check', 'pending', NULL, NULL, NULL, NULL, '2025-06-14 21:02:59', '2025-06-14 21:02:59'),
(59, 31, 1, 'Commissioning', 'Monitoring and quality check', 'pending', NULL, NULL, NULL, NULL, '2025-06-14 21:02:59', '2025-06-14 21:02:59'),
(60, 32, 1, 'Inspection', 'Execution of physical works', 'pending', NULL, NULL, NULL, NULL, '2025-06-14 21:02:59', '2025-06-14 21:02:59'),
(61, 33, 1, 'Planning & Design', 'Execution of physical works', 'pending', NULL, NULL, NULL, NULL, '2025-06-14 21:02:59', '2025-06-14 21:02:59'),
(62, 34, 1, 'Site Preparation', 'Monitoring and quality check', 'pending', NULL, NULL, NULL, NULL, '2025-06-14 21:02:59', '2025-06-14 21:02:59'),
(63, 35, 1, 'Planning & Design', 'Bidding and contract award', 'pending', NULL, NULL, NULL, NULL, '2025-06-14 21:02:59', '2025-06-14 21:02:59'),
(64, 36, 1, 'Inspection', 'Execution of physical works', 'pending', NULL, NULL, NULL, NULL, '2025-06-14 21:02:59', '2025-06-14 21:02:59'),
(65, 37, 1, 'Inspection', 'Handover to stakeholders', 'pending', NULL, NULL, NULL, NULL, '2025-06-14 21:02:59', '2025-06-14 21:02:59'),
(66, 38, 1, 'Procurement', 'Bidding and contract award', 'pending', NULL, NULL, NULL, NULL, '2025-06-14 21:02:59', '2025-06-14 21:02:59'),
(67, 39, 1, 'Procurement', 'Handover to stakeholders', 'in_progress', '2025-06-15', NULL, NULL, '', '2025-06-14 21:02:59', '2025-06-15 12:00:00'),
(68, 40, 1, 'Commissioning', 'Bidding and contract award', 'pending', NULL, NULL, NULL, NULL, '2025-06-14 21:02:59', '2025-06-14 21:02:59'),
(69, 41, 1, 'Inspection', 'Bidding and contract award', 'pending', NULL, NULL, NULL, NULL, '2025-06-14 21:02:59', '2025-06-14 21:02:59'),
(70, 42, 1, 'Planning & Design', 'Initial planning and blueprint development', 'pending', NULL, NULL, NULL, NULL, '2025-06-14 21:02:59', '2025-06-14 21:02:59'),
(71, 43, 1, 'Inspection', 'Bidding and contract award', 'pending', NULL, NULL, NULL, NULL, '2025-06-14 21:02:59', '2025-06-14 21:02:59'),
(72, 44, 1, 'Commissioning', 'Bidding and contract award', 'pending', NULL, NULL, NULL, NULL, '2025-06-14 21:02:59', '2025-06-14 21:02:59'),
(73, 45, 1, 'Commissioning', 'Monitoring and quality check', 'pending', '2025-06-15', NULL, NULL, '', '2025-06-14 21:02:59', '2025-06-14 21:04:45'),
(74, 46, 1, 'Site Preparation', 'Handover to stakeholders', 'pending', NULL, NULL, NULL, NULL, '2025-06-14 21:02:59', '2025-06-14 21:02:59'),
(75, 47, 1, 'Commissioning', 'Clearing and leveling the project site', 'pending', NULL, NULL, NULL, NULL, '2025-06-14 21:02:59', '2025-06-14 21:02:59');

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
(17, 'West Kanyamkago', 5, '2025-06-12 13:07:41'),
(18, 'Central Ward', 4, '2025-06-14 21:02:59'),
(19, 'Kakrao Ward', 8, '2025-06-14 21:02:59'),
(20, 'Karungu Ward', 7, '2025-06-14 21:02:59'),
(21, 'East Kamagambo', 8, '2025-06-14 21:02:59'),
(22, 'Central Sakwa', 5, '2025-06-14 21:02:59'),
(23, 'North Kadem', 8, '2025-06-14 21:02:59'),
(24, 'South Sakwa Ward', 11, '2025-06-14 21:02:59'),
(25, 'Isibania Ward', 1, '2025-06-14 21:02:59'),
(26, 'South Sakwa Ward', 1, '2025-06-14 21:02:59'),
(27, 'East Kamagambo', 2, '2025-06-14 21:02:59'),
(28, 'Kisii Central Ward', 5, '2025-06-14 21:02:59'),
(29, 'North Sakwa Ward', 4, '2025-06-14 21:02:59'),
(30, 'Karungu Ward', 4, '2025-06-14 21:02:59'),
(31, 'East Kamagambo', 1, '2025-06-14 21:02:59'),
(32, 'Central Sakwa', 3, '2025-06-14 21:02:59'),
(33, 'Kakrao Ward', 7, '2025-06-14 21:02:59'),
(34, 'West Kanyamkago', 11, '2025-06-14 21:02:59'),
(35, 'God Jope Ward', 10, '2025-06-14 21:02:59'),
(36, 'Central Ward', 7, '2025-06-14 21:02:59'),
(37, 'North Kadem', 11, '2025-06-14 21:02:59'),
(38, 'North Kadem', 7, '2025-06-14 21:02:59');

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
-- Indexes for table `feedback_analytics`
--
ALTER TABLE `feedback_analytics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `idx_feedback_analytics_feedback_id` (`feedback_id`),
  ADD KEY `idx_feedback_analytics_action_type` (`action_type`),
  ADD KEY `idx_feedback_analytics_timestamp` (`action_timestamp`);

--
-- Indexes for table `feedback_notifications`
--
ALTER TABLE `feedback_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_feedback_notifications_feedback_id` (`feedback_id`),
  ADD KEY `idx_feedback_notifications_status` (`delivery_status`);

--
-- Indexes for table `feedback_templates`
--
ALTER TABLE `feedback_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_feedback_templates_category` (`category`),
  ADD KEY `idx_feedback_templates_active` (`is_active`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `feedback_analytics`
--
ALTER TABLE `feedback_analytics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `feedback_notifications`
--
ALTER TABLE `feedback_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `feedback_templates`
--
ALTER TABLE `feedback_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `import_logs`
--
ALTER TABLE `import_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `project_steps`
--
ALTER TABLE `project_steps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT for table `sub_counties`
--
ALTER TABLE `sub_counties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `wards`
--
ALTER TABLE `wards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`responded_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `feedback_ibfk_3` FOREIGN KEY (`moderated_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `feedback_analytics`
--
ALTER TABLE `feedback_analytics`
  ADD CONSTRAINT `feedback_analytics_ibfk_1` FOREIGN KEY (`feedback_id`) REFERENCES `feedback` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `feedback_analytics_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `feedback_notifications`
--
ALTER TABLE `feedback_notifications`
  ADD CONSTRAINT `feedback_notifications_ibfk_1` FOREIGN KEY (`feedback_id`) REFERENCES `feedback` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `feedback_templates`
--
ALTER TABLE `feedback_templates`
  ADD CONSTRAINT `feedback_templates_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

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

INSERT INTO `prepared_responses` (`id`, `name`, `content`, `category`, `is_active`, `created_at`) VALUES
(1, 'Thank You', 'Thank you for your feedback. We appreciate your input and will review it carefully.', 'acknowledgment', 1, '2025-05-29 18:20:00'),
(2, 'Under Review', 'Your feedback is currently under review by our team. We will respond within 3-5 business days.', 'status', 1, '2025-05-29 18:20:00'),
(3, 'More Information Needed', 'Thank you for reaching out. To better assist you, could you please provide more specific details about your concern?', 'inquiry', 1, '2025-05-29 18:20:00'),
(4, 'Issue Resolved', 'Thank you for bringing this to our attention. The issue has been resolved and appropriate measures have been taken.', 'resolution', 1, '2025-05-29 18:20:00'),
(5, 'Project Progress Update', 'Thank you for your inquiry about the project progress. We are currently on track with our planned timeline and will provide regular updates as work continues. The project team is actively monitoring all aspects to ensure quality delivery within the scheduled timeframe. We appreciate your continued interest and support in this important community initiative.', 'progress', 1, '2025-05-29 18:20:00'),
(6, 'Environmental Compliance', 'We appreciate your concern regarding environmental impact. This project has undergone comprehensive environmental assessment and complies with all local and national environmental regulations. Our team works closely with environmental agencies to ensure minimal ecological disruption while delivering essential infrastructure improvements for our community. Regular environmental monitoring continues throughout the project lifecycle.', 'environmental', 1, '2025-05-29 18:20:00'),
(7, 'Budget and Transparency', 'Thank you for your question about project funding. We maintain full transparency in our budget allocation and expenditure tracking. All project costs are carefully monitored and reported according to government financial regulations. Detailed budget breakdowns are available through our public records, and we encourage community members to stay informed about how their tax contributions are being utilized for infrastructure development.', 'budget', 1, '2025-05-29 18:20:00'),
(8, 'Community Engagement', 'Your participation and feedback are vital to the success of this project. We are committed to maintaining open communication with all community members throughout the project implementation. Regular public meetings and progress updates will continue to be scheduled, and we encourage all residents to actively participate in the decision-making process. Together, we can ensure this project meets the needs and expectations of our community.', 'engagement', 1, '2025-05-29 18:20:00'),
(9, 'Timeline and Delays', 'We understand your concern about project timelines. While we strive to complete all projects within the scheduled timeframe, some delays may occur due to weather conditions, regulatory approvals, or unforeseen technical challenges. When delays are anticipated, we commit to providing timely updates to the community with revised schedules and explanations. Our project management team works diligently to minimize any disruptions to the community while maintaining quality standards.', 'timeline', 1, '2025-05-29 18:20:00'),
(10, 'Quality Assurance', 'Quality is our top priority in all project implementations. We employ qualified contractors and conduct regular inspections to ensure all work meets or exceeds industry standards and government specifications. Our quality assurance process includes multiple checkpoints, independent audits, and community feedback integration. We are committed to delivering infrastructure that will serve our community reliably for many years to come.', 'quality', 1, '2025-05-29 18:20:00');