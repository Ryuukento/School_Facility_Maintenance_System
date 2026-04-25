-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 25, 2026 at 05:15 PM
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
-- Database: `school_facility_maintenance`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(100) DEFAULT NULL,
  `entity_id` bigint(20) UNSIGNED DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `entity_type`, `entity_id`, `details`, `ip_address`, `created_at`, `updated_at`) VALUES
(1, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', '2026-03-27 14:55:27', '2026-03-27 14:55:27'),
(2, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', '2026-03-29 10:06:12', '2026-03-29 10:06:12'),
(3, 3, 'REGISTER', 'user', 3, 'Self-registered account (pending approval)', '::1', '2026-03-29 10:07:49', '2026-03-29 10:07:49'),
(4, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', '2026-03-29 10:08:26', '2026-03-29 10:08:26'),
(5, 1, 'APPROVE_USER', 'user', 3, 'Approved user #3 and assigned role maintenance_staff', '::1', '2026-03-29 10:29:12', '2026-03-29 10:29:12'),
(6, 1, 'INACTIVATE_USER', 'user', 3, 'Set user #3 to inactive', '::1', '2026-03-29 10:29:16', '2026-03-29 10:29:16'),
(7, 1, 'ACTIVATE_USER', 'user', 3, 'Set user #3 to active', '::1', '2026-03-29 10:29:19', '2026-03-29 10:29:19'),
(8, 3, 'LOGIN', 'user', 3, 'User logged in', '::1', '2026-03-29 10:29:50', '2026-03-29 10:29:50'),
(9, 3, 'CREATE_REPORT', 'report', 1, NULL, NULL, '2026-03-29 10:50:54', NULL),
(10, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', '2026-03-29 11:01:02', '2026-03-29 11:01:02'),
(11, 1, 'INACTIVATE_USER', 'user', 3, 'Set user #3 to inactive', '::1', '2026-03-29 11:01:16', '2026-03-29 11:01:16'),
(12, 2, 'LOGIN', 'user', 2, 'User logged in', '::1', '2026-03-29 11:01:54', '2026-03-29 11:01:54'),
(13, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', '2026-03-29 11:08:18', '2026-03-29 11:08:18'),
(14, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', '2026-03-31 11:12:47', '2026-03-31 11:12:47'),
(15, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', '2026-03-31 13:36:36', '2026-03-31 13:36:36'),
(17, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', '2026-03-31 13:41:27', '2026-03-31 13:41:27'),
(18, 1, 'REJECT_USER', 'user', 4, 'Rejected pending user #4 and deleted account', '::1', '2026-03-31 13:41:35', '2026-03-31 13:41:35'),
(20, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', '2026-03-31 13:45:12', '2026-03-31 13:45:12'),
(21, 1, 'REJECT_USER', 'user', 5, 'Rejected pending user #5 and deleted account', '::1', '2026-03-31 13:46:36', '2026-03-31 13:46:36'),
(23, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', '2026-03-31 13:50:34', '2026-03-31 13:50:34'),
(24, 1, 'REJECT_USER', 'user', 6, 'Rejected pending user #6 and deleted account', '::1', '2026-03-31 13:50:45', '2026-03-31 13:50:45'),
(26, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', '2026-04-01 06:52:28', '2026-04-01 06:52:28'),
(27, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', '2026-04-02 13:26:04', '2026-04-02 13:26:04'),
(28, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', NULL, NULL),
(29, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', NULL, NULL),
(30, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', NULL, NULL),
(31, 1, 'ACTIVATE_USER', 'user', 3, 'Set user #3 to active', '::1', NULL, NULL),
(32, 1, 'REJECT_USER', 'user', 7, 'Rejected pending user #7 and deleted account', '::1', NULL, NULL),
(33, 3, 'LOGIN', 'user', 3, 'User logged in', '::1', NULL, NULL),
(34, 3, 'CREATE_REPORT', 'report', 2, NULL, NULL, '2026-04-03 05:17:08', NULL),
(35, 3, 'LOGIN', 'user', 3, 'User logged in', '::1', NULL, NULL),
(36, 3, 'UPDATE_REPORT', 'report', 2, '{\"title\":\"Laravel Convert Test\",\"location\":\"Lourdes V 4 floor room 102\",\"priority\":\"urgent\",\"status\":\"submitted\",\"description\":\"adasdeas\"}', NULL, '2026-04-03 05:24:55', NULL),
(37, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', NULL, NULL),
(38, 3, 'LOGIN', 'user', 3, 'User logged in', '::1', NULL, NULL),
(39, 3, 'CREATE_REPORT', 'report', 3, NULL, NULL, '2026-04-03 05:39:30', NULL),
(40, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', NULL, NULL),
(41, 3, 'LOGIN', 'user', 3, 'User logged in', '::1', NULL, NULL),
(42, 3, 'DELETE_REPORT', 'report', 3, '\"Deleted report #3\"', NULL, '2026-04-03 05:50:22', NULL),
(43, 3, 'CREATE_REPORT', 'report', 4, NULL, NULL, '2026-04-03 05:50:54', NULL),
(44, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', NULL, NULL),
(45, 2, 'LOGIN', 'user', 2, 'User logged in', '::1', NULL, NULL),
(46, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', NULL, NULL),
(47, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', NULL, NULL),
(48, 2, 'LOGIN', 'user', 2, 'User logged in', '::1', NULL, NULL),
(49, 2, 'LOGIN', 'user', 2, 'User logged in', '::1', NULL, NULL),
(50, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', NULL, NULL),
(51, 2, 'LOGIN', 'user', 2, 'User logged in', '::1', NULL, NULL),
(52, 2, 'LOGIN', 'user', 2, 'User logged in', '::1', NULL, NULL),
(53, 3, 'LOGIN', 'user', 3, 'User logged in', '::1', NULL, NULL),
(54, 2, 'LOGIN', 'user', 2, 'User logged in', '::1', NULL, NULL),
(55, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', NULL, NULL),
(56, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', NULL, NULL),
(57, 1, 'INACTIVATE_USER', 'user', 3, 'Set user #3 to inactive', '::1', NULL, NULL),
(58, 1, 'ACTIVATE_USER', 'user', 3, 'Set user #3 to active', '::1', NULL, NULL),
(59, 2, 'LOGIN', 'user', 2, 'User logged in', '::1', NULL, NULL),
(60, 3, 'LOGIN', 'user', 3, 'User logged in', '::1', NULL, NULL),
(61, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', NULL, NULL),
(62, 1, 'INACTIVATE_USER', 'user', 3, 'Set user #3 to inactive', '::1', NULL, NULL),
(63, 1, 'ACTIVATE_USER', 'user', 3, 'Set user #3 to active', '::1', NULL, NULL),
(65, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', NULL, NULL),
(66, 1, 'REJECT_USER', 'user', 8, 'Rejected pending user #8 and deleted account', '::1', NULL, NULL),
(67, 2, 'LOGIN', 'user', 2, 'User logged in', '::1', NULL, NULL),
(68, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', NULL, NULL),
(69, 1, 'LOGOUT', 'user', 1, 'User logged out', '::1', NULL, NULL),
(70, 2, 'LOGIN', 'user', 2, 'User logged in', '::1', NULL, NULL),
(71, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', NULL, NULL),
(72, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', NULL, NULL),
(73, 1, 'DELETE_REPORT', 'report', 4, '\"Deleted report #4\"', NULL, '2026-04-04 13:08:11', NULL),
(74, 1, 'DELETE_REPORT', 'report', 2, '\"Deleted report #2\"', NULL, '2026-04-04 13:08:16', NULL),
(75, 1, 'DELETE_REPORT', 'report', 1, '\"Deleted report #1\"', NULL, '2026-04-04 13:08:43', NULL),
(76, 3, 'LOGIN', 'user', 3, 'User logged in', '::1', NULL, NULL),
(77, 3, 'CREATE_REPORT', 'report', 5, NULL, NULL, '2026-04-04 13:10:31', NULL),
(78, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', NULL, NULL),
(79, 1, 'RESERVE_INVENTORY', 'inventory_item', 5, '{\"report_id\":5,\"room_id\":1,\"quantity\":1,\"allocation_id\":1}', NULL, '2026-04-04 13:11:41', NULL),
(80, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', NULL, NULL),
(81, 1, 'CREATE_RESTOCK_REQUEST', 'inventory_item', NULL, '{\"restock_id\":1,\"item_name\":\"Whiteboard Marker Set\",\"requested_qty\":1,\"priority\":\"high\",\"source_report_id\":5}', NULL, '2026-04-05 04:55:40', NULL),
(82, 2, 'LOGIN', 'user', 2, 'User logged in', '::1', NULL, NULL),
(83, 2, 'LOGOUT', 'user', 2, 'User logged out', '::1', NULL, NULL),
(84, 3, 'LOGIN', 'user', 3, 'User logged in', '::1', NULL, NULL),
(85, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', NULL, NULL),
(87, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', NULL, NULL),
(88, 1, 'UPDATE_REPORT', 'report', 5, '{\"status\":\"assigned\",\"assigned_to\":\"3\"}', NULL, '2026-04-05 05:36:36', NULL),
(89, 1, 'REJECT_USER', 'user', 9, 'Rejected pending user #9 and deleted account', '::1', NULL, NULL),
(90, 3, 'LOGIN', 'user', 3, 'User logged in', '::1', NULL, NULL),
(91, 3, 'UPDATE_REPORT', 'report', 5, '{\"title\":\"Testing\",\"location\":\"103\",\"priority\":\"critical\",\"status\":\"submitted\",\"description\":\"asdasd\"}', NULL, '2026-04-05 05:41:09', NULL),
(92, 2, 'LOGIN', 'user', 2, 'User logged in', '::1', NULL, NULL),
(93, 2, 'UPDATE_REPORT', 'report', 5, '{\"status\":\"completed\",\"assigned_to\":null}', NULL, '2026-04-05 05:44:01', NULL),
(94, 2, 'UPDATE_REPORT', 'report', 5, '{\"status\":\"in_progress\",\"assigned_to\":null}', NULL, '2026-04-05 05:44:06', NULL),
(95, 3, 'LOGIN', 'user', 3, 'User logged in', '::1', NULL, NULL),
(96, 3, 'CREATE_REPORT', 'report', 6, NULL, NULL, '2026-04-05 05:46:10', NULL),
(97, 3, 'LOGOUT', 'user', 3, 'User logged out', '::1', NULL, NULL),
(98, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', NULL, NULL),
(99, 1, 'UPDATE_REPORT', 'report', 6, '{\"status\":\"assigned\",\"assigned_to\":\"2\"}', NULL, '2026-04-05 05:47:01', NULL),
(100, 2, 'LOGIN', 'user', 2, 'User logged in', '::1', NULL, NULL),
(101, 2, 'UPDATE_REPORT', 'report', 6, '{\"status\":\"assigned\",\"assigned_to\":\"3\"}', NULL, '2026-04-05 05:50:56', NULL),
(102, 2, 'UPDATE_REPORT', 'report', 6, '{\"status\":\"in_progress\",\"assigned_to\":null}', NULL, '2026-04-05 05:51:07', NULL),
(103, 2, 'UPDATE_REPORT', 'report', 6, '{\"status\":\"completed\",\"assigned_to\":null}', NULL, '2026-04-05 05:51:26', NULL),
(104, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', NULL, NULL),
(105, 3, 'LOGIN', 'user', 3, 'User logged in', '::1', NULL, NULL),
(106, 3, 'CREATE_REPORT', 'report', 7, NULL, NULL, '2026-04-05 05:53:01', NULL),
(107, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', NULL, NULL),
(108, 3, 'LOGIN', 'user', 3, 'User logged in', '::1', NULL, NULL),
(109, 10, 'REGISTER', 'user', 10, 'Self-registered account (pending approval)', '::1', NULL, NULL),
(110, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', NULL, NULL),
(111, 1, 'APPROVE_USER', 'user', 10, 'Approved user #10 and assigned role maintenance_admin', '::1', NULL, NULL),
(112, 10, 'LOGIN', 'user', 10, 'User logged in', '::1', NULL, NULL),
(113, 10, 'LOGOUT', 'user', 10, 'User logged out', '::1', NULL, NULL),
(114, 3, 'LOGIN', 'user', 3, 'User logged in', '::1', NULL, NULL),
(115, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', NULL, NULL),
(116, 1, 'LOGIN', 'user', 1, 'User logged in', '127.0.0.1', NULL, NULL),
(117, 1, 'LOGIN', 'user', 1, 'User logged in', '127.0.0.1', NULL, NULL),
(118, 1, 'INACTIVATE_USER', 'user', 2, 'Set user #2 to inactive', '127.0.0.1', NULL, NULL),
(119, 1, 'ACTIVATE_USER', 'user', 2, 'Set user #2 to active', '127.0.0.1', NULL, NULL),
(120, 1, 'LOGIN', 'user', 1, 'User logged in', '127.0.0.1', NULL, NULL),
(121, 2, 'LOGIN', 'user', 2, 'User logged in', '127.0.0.1', NULL, NULL),
(122, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', NULL, NULL),
(124, 1, 'REJECT_USER', 'user', 11, 'Rejected pending user #11 and deleted account', '::1', NULL, NULL),
(125, 1, 'CREATE_USER', 'user', 12, 'Created active user account for profile setup', '::1', NULL, NULL),
(126, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', NULL, NULL),
(127, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', NULL, NULL),
(128, 1, 'CREATE_USER', 'user', 13, 'Created active maintenance_staff account for profile setup', '::1', NULL, NULL),
(129, 13, 'LOGIN', 'user', 13, 'User logged in', '::1', NULL, NULL),
(130, 13, 'LOGIN', 'user', 13, 'User logged in', '::1', NULL, NULL),
(131, 13, 'LOGOUT', 'user', 13, 'User logged out', '::1', NULL, NULL),
(132, 13, 'LOGIN', 'user', 13, 'User logged in', '::1', NULL, NULL),
(133, 13, 'LOGOUT', 'user', 13, 'User logged out', '::1', NULL, NULL),
(134, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', NULL, NULL),
(135, 1, 'CREATE_USER', 'user', 14, 'Created active maintenance_admin account for profile setup', '::1', NULL, NULL),
(138, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', NULL, NULL),
(139, 1, 'LOGIN', 'user', 1, 'User logged in', '127.0.0.1', NULL, NULL),
(140, 3, 'LOGIN', 'user', 3, 'User logged in', '::1', NULL, NULL),
(141, 3, 'LOGIN', 'user', 3, 'User logged in', '::1', NULL, NULL),
(142, 3, 'CREATE_REPORT', 'report', 8, NULL, NULL, '2026-04-08 11:03:48', NULL),
(143, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', NULL, NULL),
(144, 3, 'LOGIN', 'user', 3, 'User logged in', '::1', NULL, NULL),
(145, 3, 'CREATE_REPORT', 'report', 9, NULL, NULL, '2026-04-08 11:18:10', NULL),
(146, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', NULL, NULL),
(147, 1, 'UPDATE_REPORT', 'report', 9, '{\"status\":\"assigned\",\"assigned_to\":\"3\"}', NULL, '2026-04-08 12:07:04', NULL),
(148, 3, 'LOGIN', 'user', 3, 'User logged in', '::1', NULL, NULL),
(149, 3, 'CREATE_REPORT', 'report', 10, NULL, NULL, '2026-04-08 12:11:34', NULL),
(150, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', NULL, NULL),
(151, 1, 'APPROVE_NEED_CHANGE', 'report', 10, '{\"need_change_item_id\":5,\"need_change_status\":\"deducted\"}', NULL, '2026-04-08 12:18:43', NULL),
(152, 1, 'UPDATE_REPORT', 'report', 10, '{\"status\":\"assigned\",\"assigned_to\":\"13\"}', NULL, '2026-04-08 12:18:53', NULL),
(153, 1, 'UPDATE_REPORT', 'report', 9, '{\"status\":\"assigned\",\"assigned_to\":\"2\"}', NULL, '2026-04-08 12:20:58', NULL),
(154, 1, 'UPDATE_REPORT', 'report', 10, '{\"status\":\"assigned\",\"assigned_to\":\"13\"}', NULL, '2026-04-08 12:41:03', NULL),
(155, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', NULL, NULL),
(156, 1, 'INACTIVATE_USER', 'user', 13, 'Set user #13 to inactive', '::1', NULL, NULL),
(157, 1, 'ACTIVATE_USER', 'user', 13, 'Set user #13 to active', '::1', NULL, NULL),
(158, 1, 'CREATE_USER', 'user', 15, 'Created active maintenance_admin account 20260409 for profile setup', '::1', NULL, NULL),
(159, 15, 'LOGIN', 'user', 15, 'User logged in', '::1', NULL, NULL),
(160, 15, 'LOGOUT', 'user', 15, 'User logged out', '::1', NULL, NULL),
(161, 15, 'LOGIN', 'user', 15, 'User logged in', '::1', NULL, NULL),
(162, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', NULL, NULL),
(163, 2, 'LOGIN', 'user', 2, 'User logged in', '::1', NULL, NULL),
(164, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', NULL, NULL),
(165, 2, 'LOGIN', 'user', 2, 'User logged in', '::1', NULL, NULL),
(166, 3, 'LOGIN', 'user', 3, 'User logged in', '::1', NULL, NULL),
(167, 3, 'UPDATE_REPORT', 'report', 10, '{\"status\":\"in_progress\",\"assigned_to\":null}', NULL, '2026-04-09 15:54:21', NULL),
(168, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', NULL, NULL),
(169, 3, 'LOGIN', 'user', 3, 'User logged in', '::1', NULL, NULL),
(170, 3, 'CREATE_REPORT', 'report', 11, NULL, NULL, '2026-04-10 01:13:19', NULL),
(171, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', NULL, NULL),
(172, 1, 'APPROVE_NEED_CHANGE', 'report', 11, '{\"need_change_item_id\":5,\"need_change_status\":\"deducted\"}', NULL, '2026-04-10 01:14:46', NULL),
(173, 3, 'LOGIN', 'user', 3, 'User logged in', '::1', NULL, NULL),
(174, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', NULL, NULL),
(175, 2, 'LOGIN', 'user', 2, 'User logged in', '::1', NULL, NULL),
(176, 3, 'LOGIN', 'user', 3, 'User logged in', '::1', NULL, NULL),
(177, 1, 'LOGIN', 'user', 1, 'User logged in', '127.0.0.1', '2026-04-13 13:20:02', '2026-04-13 13:20:02'),
(178, 1, 'LOGIN', 'user', 1, 'User logged in', '127.0.0.1', '2026-04-13 13:36:07', '2026-04-13 13:36:07'),
(179, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', '2026-04-13 13:56:05', '2026-04-13 13:56:05'),
(180, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', NULL, NULL),
(181, 1, 'LOGOUT', 'user', 1, 'User logged out', '::1', NULL, NULL),
(182, 2, 'LOGIN', 'user', 2, 'User logged in', '::1', NULL, NULL),
(183, 1, 'LOGIN', 'user', 1, 'User logged in', '127.0.0.1', NULL, NULL),
(184, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', NULL, NULL),
(185, 1, 'LOGIN', 'user', 1, 'User logged in', '127.0.0.1', NULL, NULL),
(186, 1, 'LOGIN', 'user', 1, 'User logged in', '::1', '2026-04-25 14:59:09', '2026-04-25 14:59:09'),
(187, 13, 'LOGIN', 'user', 13, 'User logged in', '127.0.0.1', '2026-04-25 15:00:15', '2026-04-25 15:00:15'),
(188, 13, 'LOGOUT', 'user', 13, 'User logged out', '127.0.0.1', NULL, NULL),
(189, 2, 'LOGIN', 'user', 2, 'User logged in', '127.0.0.1', '2026-04-25 15:01:58', '2026-04-25 15:01:58'),
(190, 2, 'LOGOUT', 'user', 2, 'User logged out', '127.0.0.1', NULL, NULL),
(191, 2, 'LOGIN', 'user', 2, 'User logged in', '127.0.0.1', '2026-04-25 15:02:56', '2026-04-25 15:02:56'),
(192, 2, 'LOGOUT', 'user', 2, 'User logged out', '127.0.0.1', NULL, NULL),
(193, 3, 'LOGIN', 'user', 3, 'User logged in', '127.0.0.1', '2026-04-25 15:03:34', '2026-04-25 15:03:34'),
(194, 3, 'LOGOUT', 'user', 3, 'User logged out', '127.0.0.1', NULL, NULL),
(195, 3, 'PASSWORD_RESET_REQUEST', 'user', 3, 'Requested Super Admin password reset assistance', '127.0.0.1', '2026-04-25 15:05:03', '2026-04-25 15:05:03'),
(196, 15, 'LOGIN', 'user', 15, 'User logged in', '127.0.0.1', '2026-04-25 15:06:36', '2026-04-25 15:06:36'),
(197, 15, 'LOGOUT', 'user', 15, 'User logged out', '127.0.0.1', NULL, NULL),
(198, 13, 'LOGIN', 'user', 13, 'User logged in', '127.0.0.1', '2026-04-25 15:10:35', '2026-04-25 15:10:35'),
(199, 13, 'LOGOUT', 'user', 13, 'User logged out', '127.0.0.1', NULL, NULL),
(200, 13, 'LOGIN', 'user', 13, 'User logged in', '127.0.0.1', '2026-04-25 15:11:06', '2026-04-25 15:11:06'),
(201, 13, 'CREATE_REPORT', 'report', 12, NULL, NULL, '2026-04-25 15:12:16', NULL),
(202, 13, 'UPDATE_REPORT', 'report', 12, '{\"status\":\"completed\",\"completion_proof_image\":\"\\/School_Facility_Maintenance_System\\/frontend\\/uploads\\/completion-proofs\\/report-12-20260425231406-a15fb811.jpg\",\"completion_proof_uploaded_at\":\"2026-04-25 23:14:06\"}', NULL, '2026-04-25 15:14:06', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `buildings`
--

CREATE TABLE `buildings` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `buildings`
--

INSERT INTO `buildings` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(9, 'Lourdes Building VIII', '', '2026-04-03 08:09:42', '2026-04-03 08:09:42'),
(10, 'Lourdes Building VII', '', '2026-04-03 08:09:51', '2026-04-03 08:09:51'),
(11, 'Lourdes Building VI', '', '2026-04-03 08:09:59', '2026-04-03 08:09:59'),
(12, 'Lourdes Building V', '', '2026-04-03 08:10:06', '2026-04-03 08:10:06'),
(13, 'Lourdes Building IV', '', '2026-04-03 08:10:32', '2026-04-03 08:10:32'),
(14, 'Lourdes Building III', '', '2026-04-03 08:10:42', '2026-04-03 08:10:42'),
(15, 'Lourdes Building II', '', '2026-04-03 08:10:48', '2026-04-03 08:10:48'),
(16, 'Lourdes Building I', '', '2026-04-03 08:10:56', '2026-04-03 08:11:02');

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('school-facility-maintenance-system-cache-forgot-password-request|bonifacioeuclide@gmail.com|127.0.0.1', 'i:1;', 1777129563),
('school-facility-maintenance-system-cache-forgot-password-request|bonifacioeuclide@gmail.com|127.0.0.1:timer', 'i:1777129563;', 1777129563),
('school-facility-maintenance-system-cache-ryaondido27@gmail.com|::1', 'i:1;', 1777129153),
('school-facility-maintenance-system-cache-ryaondido27@gmail.com|::1:timer', 'i:1777129153;', 1777129153);

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `department_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `floors`
--

CREATE TABLE `floors` (
  `id` int(10) UNSIGNED NOT NULL,
  `building_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `floors`
--

INSERT INTO `floors` (`id`, `building_id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 16, '1st Floor', NULL, '2026-04-03 08:11:35', '2026-04-03 08:11:35'),
(2, 16, '2nd', NULL, '2026-04-03 08:11:41', '2026-04-03 08:11:41'),
(3, 16, '3rd floor', NULL, '2026-04-03 08:11:44', '2026-04-03 08:11:44'),
(4, 16, '4rd Floor', NULL, '2026-04-03 08:12:03', '2026-04-03 08:12:03');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_categories`
--

CREATE TABLE `inventory_categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `default_low_stock_threshold` int(11) DEFAULT NULL,
  `allow_threshold_override` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_categories`
--

INSERT INTO `inventory_categories` (`id`, `name`, `code`, `default_low_stock_threshold`, `allow_threshold_override`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(4, 'Electrical', NULL, 10, 1, 1, 1, '2026-04-09 20:39:33', '2026-04-10 09:05:16'),
(5, 'IT & Electronics', NULL, 10, 1, 1, 2, '2026-04-09 20:39:33', '2026-04-09 20:39:33'),
(6, 'Furniture', NULL, 10, 1, 1, 3, '2026-04-09 20:39:33', '2026-04-09 20:39:33'),
(7, 'Plumbing', NULL, 10, 1, 1, 4, '2026-04-09 20:39:33', '2026-04-09 20:39:33'),
(8, 'Classroom', NULL, 10, 1, 1, 5, '2026-04-09 20:39:33', '2026-04-09 20:39:33');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_transactions`
--

CREATE TABLE `inventory_transactions` (
  `id` int(10) UNSIGNED NOT NULL,
  `item_id` int(10) UNSIGNED NOT NULL,
  `report_id` int(10) UNSIGNED DEFAULT NULL,
  `room_id` int(10) UNSIGNED DEFAULT NULL,
  `transaction_type` enum('reserve','release','deploy','return','adjustment','dispose') NOT NULL,
  `quantity` int(11) NOT NULL,
  `reference_note` text DEFAULT NULL,
  `performed_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory_transactions`
--

INSERT INTO `inventory_transactions` (`id`, `item_id`, `report_id`, `room_id`, `transaction_type`, `quantity`, `reference_note`, `performed_by`, `created_at`, `updated_at`) VALUES
(1, 5, 5, 1, 'reserve', 1, 'Reserved for report #5', 1, '2026-04-04 13:11:41', NULL),
(2, 5, 10, NULL, 'adjustment', 1, 'Auto-deducted after Super Admin approval for report #10 - 1 item(s)', 1, '2026-04-08 12:18:43', NULL),
(3, 5, 11, NULL, 'adjustment', 1, 'Auto-deducted after Super Admin approval for report #11 - 1 item(s)', 1, '2026-04-10 01:14:46', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
  `id` int(10) UNSIGNED NOT NULL,
  `room_id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `status` enum('available','damaged','low_stock','out_of_stock','maintenance') NOT NULL DEFAULT 'available',
  `quantity` int(11) NOT NULL DEFAULT 1,
  `low_stock_threshold_override` int(11) DEFAULT NULL,
  `reserved_quantity` int(11) NOT NULL DEFAULT 0,
  `reorder_level` int(11) NOT NULL DEFAULT 5,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `items`
--

INSERT INTO `items` (`id`, `room_id`, `category_id`, `name`, `status`, `quantity`, `low_stock_threshold_override`, `reserved_quantity`, `reorder_level`, `description`, `created_at`, `updated_at`) VALUES
(1, 2, NULL, 'Projector Epson X51', 'low_stock', 6, NULL, 0, 5, 'Portable projector for classroom presentations', '2026-04-04 02:02:35', '2026-04-04 02:02:35'),
(2, 1, NULL, 'Whiteboard Marker Set', 'low_stock', 12, NULL, 0, 5, 'Assorted marker colors', '2026-04-04 02:02:35', '2026-04-04 02:02:35'),
(3, 1, NULL, 'Extension Cord Heavy Duty', 'low_stock', 18, NULL, 0, 5, '5-meter grounded extension cord', '2026-04-04 02:02:35', '2026-04-04 02:02:35'),
(4, 2, NULL, 'LCD Monitor 24 inch', 'low_stock', 3, NULL, 0, 5, 'Needs HDMI port repair', '2026-04-04 02:02:35', '2026-04-04 02:02:35'),
(5, 1, NULL, 'Office Chair', 'available', 2, NULL, 1, 5, 'Broken wheel assembly', '2026-04-04 02:02:35', '2026-04-10 01:14:46'),
(6, 1, NULL, 'Printer Ink Cartridge HP 680', 'out_of_stock', 0, NULL, 0, 5, 'Black ink for faculty office printer', '2026-04-04 02:02:35', '2026-04-04 02:02:35'),
(7, 1, 4, 'Extension Cords', 'available', 10, NULL, 0, 5, 'Item in Electrical', '2026-04-09 12:39:33', '2026-04-09 12:39:33'),
(8, 1, 4, 'Circuit Breakers', 'available', 10, NULL, 0, 5, 'Item in Electrical', '2026-04-09 12:39:33', '2026-04-09 12:39:33'),
(9, 1, 4, 'Fluorescent Tubes', 'available', 10, NULL, 0, 5, 'Item in Electrical', '2026-04-09 12:39:33', '2026-04-09 12:39:33'),
(10, 1, 4, 'Outlets', 'available', 10, NULL, 0, 5, 'Item in Electrical', '2026-04-09 12:39:33', '2026-04-09 12:39:33'),
(11, 1, 5, 'Monitors', 'available', 10, NULL, 0, 5, 'Item in IT & Electronics', '2026-04-09 12:39:33', '2026-04-09 12:39:33'),
(12, 1, 5, 'Projectors', 'available', 10, NULL, 0, 5, 'Item in IT & Electronics', '2026-04-09 12:39:33', '2026-04-09 12:39:33'),
(13, 1, 5, 'Printers', 'available', 10, NULL, 0, 5, 'Item in IT & Electronics', '2026-04-09 12:39:33', '2026-04-09 12:39:33'),
(14, 1, 5, 'Switches', 'available', 10, NULL, 0, 5, 'Item in IT & Electronics', '2026-04-09 12:39:33', '2026-04-09 12:39:33'),
(15, 1, 5, 'Cables', 'available', 10, NULL, 0, 5, 'Item in IT & Electronics', '2026-04-09 12:39:33', '2026-04-09 12:39:33'),
(16, 1, 6, 'Chairs', 'available', 10, NULL, 0, 5, 'Item in Furniture', '2026-04-09 12:39:33', '2026-04-09 12:39:33'),
(17, 1, 6, 'Desks', 'available', 10, NULL, 0, 5, 'Item in Furniture', '2026-04-09 12:39:33', '2026-04-09 12:39:33'),
(18, 1, 6, 'Cabinets', 'available', 10, NULL, 0, 5, 'Item in Furniture', '2026-04-09 12:39:33', '2026-04-09 12:39:33'),
(19, 1, 6, 'Shelves', 'available', 10, NULL, 0, 5, 'Item in Furniture', '2026-04-09 12:39:33', '2026-04-09 12:39:33'),
(20, 1, 7, 'Faucets', 'available', 10, NULL, 0, 5, 'Item in Plumbing', '2026-04-09 12:39:33', '2026-04-09 12:39:33'),
(21, 1, 7, 'Pipes', 'available', 10, NULL, 0, 5, 'Item in Plumbing', '2026-04-09 12:39:33', '2026-04-09 12:39:33'),
(22, 1, 7, 'Valves', 'available', 10, NULL, 0, 5, 'Item in Plumbing', '2026-04-09 12:39:33', '2026-04-09 12:39:33'),
(23, 1, 7, 'Toilets', 'available', 10, NULL, 0, 5, 'Item in Plumbing', '2026-04-09 12:39:33', '2026-04-09 12:39:33'),
(24, 1, 8, 'Markers', 'available', 10, NULL, 0, 5, 'Item in Classroom', '2026-04-09 12:39:33', '2026-04-09 12:39:33'),
(25, 1, 8, 'Boards', 'available', 10, NULL, 0, 5, 'Item in Classroom', '2026-04-09 12:39:33', '2026-04-09 12:39:33'),
(26, 1, 8, 'Teaching Materials', 'available', 10, NULL, 0, 5, 'Item in Classroom', '2026-04-09 12:39:33', '2026-04-09 12:39:33');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_reports`
--

CREATE TABLE `maintenance_reports` (
  `report_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` longtext NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `priority` enum('low','medium','high','urgent','critical') NOT NULL DEFAULT 'medium',
  `status` enum('submitted','in_progress','completed','closed','cancelled') NOT NULL DEFAULT 'submitted',
  `created_by` int(10) UNSIGNED NOT NULL,
  `assigned_to` int(10) UNSIGNED DEFAULT NULL,
  `department_id` int(10) UNSIGNED DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `need_change_item_id` int(11) DEFAULT NULL,
  `need_change_quantity` int(11) DEFAULT 1,
  `need_change_status` varchar(50) DEFAULT NULL,
  `need_change_approved_by` int(11) DEFAULT NULL,
  `need_change_approved_at` datetime DEFAULT NULL,
  `need_change_deducted_at` datetime DEFAULT NULL,
  `completed_date` date DEFAULT NULL,
  `completion_proof_image` varchar(500) DEFAULT NULL,
  `completion_proof_uploaded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `maintenance_reports`
--

INSERT INTO `maintenance_reports` (`report_id`, `title`, `description`, `location`, `priority`, `status`, `created_by`, `assigned_to`, `department_id`, `due_date`, `need_change_item_id`, `need_change_quantity`, `need_change_status`, `need_change_approved_by`, `need_change_approved_at`, `need_change_deducted_at`, `completed_date`, `completion_proof_image`, `completion_proof_uploaded_at`, `created_at`, `updated_at`) VALUES
(5, 'Testing', 'asdasd', '103', 'critical', 'in_progress', 3, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-04 13:10:31', '2026-04-05 05:44:06'),
(6, 'Broken Chair', '2 Chairs has been broke', 'Building 1 room 103', 'low', 'completed', 3, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-05 05:46:10', '2026-04-05 05:51:26'),
(7, 'Alert this is testing report only', 'testing report only', 'Lourdes Building 4 Room 2-6', 'medium', 'submitted', 3, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-05 05:53:01', NULL),
(8, 'Walang Bitaw', 'Wala kang bitaw boi kaya manahimik kana lang baka ma bira kita', 'Mother Lourdes Building 4 Room 405', 'critical', 'submitted', 3, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-08 11:03:48', NULL),
(9, 'Test Replacement Item', 'asdads', 'Mother Lourdes Building 4 Room 405', 'low', '', 3, 2, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-08 11:18:10', '2026-04-08 12:20:58'),
(10, 'Hahaha', 'asdasd', 'Lourdes Building 4 Room 2-6', 'high', 'in_progress', 3, NULL, NULL, NULL, 5, 1, 'deducted', 1, '2026-04-08 20:18:43', '2026-04-08 20:18:43', NULL, NULL, NULL, '2026-04-08 12:11:34', '2026-04-09 15:54:21'),
(11, 'Recent Reports', 'Testing lang huh!', 'Lourdes Building 4 Room 2-6', 'medium', 'submitted', 3, NULL, NULL, NULL, 5, 1, 'deducted', 1, '2026-04-10 09:14:46', '2026-04-10 09:14:46', NULL, NULL, NULL, '2026-04-10 01:13:19', '2026-04-10 01:14:46'),
(12, 'Broken Heart', 'Aray ko its hurts so much ;((', 'Lourdes  ll  4floor room L301', 'critical', 'completed', 13, NULL, NULL, NULL, 8, 1, 'pending', NULL, NULL, NULL, NULL, '/School_Facility_Maintenance_System/frontend/uploads/completion-proofs/report-12-20260425231406-a15fb811.jpg', '2026-04-25 15:14:06', '2026-04-25 15:12:16', '2026-04-25 15:14:06');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_03_27_000100_create_departments_table', 1),
(5, '2026_03_27_000200_create_activity_logs_table', 1),
(6, '2026_03_27_000300_create_facility_tables', 1),
(7, '2026_03_27_000400_create_maintenance_reports_table', 1),
(8, '2026_03_27_000500_create_notifications_table', 1),
(9, '2026_04_04_000600_create_inventory_workflow_tables', 2);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `is_read`, `created_at`, `updated_at`) VALUES
(3, 2, 'New Maintenance Report Submitted', 'Mark Joseph Barreto submitted a new report: Test Email/Dashboard Notification', 1, '2026-04-03 05:50:54', NULL),
(5, 2, 'New Maintenance Report Submitted', 'Mark Joseph Barreto submitted a new report: Testing (Report #5)', 0, '2026-04-04 13:10:31', NULL),
(6, 3, 'Report Assigned to You', 'Ryan Mondido assigned report #5 (Testing) to you.', 1, '2026-04-05 05:36:36', NULL),
(8, 2, 'New Maintenance Report Submitted', 'Mark Joseph Barreto submitted a new report: Broken Chair (Report #6)', 0, '2026-04-05 05:46:10', NULL),
(9, 2, 'Report Assigned to You', 'Ryan Mondido assigned report #6 (Broken Chair) to you.', 1, '2026-04-05 05:47:01', NULL),
(10, 3, 'Report Assigned to You', 'Maintenance Admin assigned report #6 (Broken Chair) to you.', 1, '2026-04-05 05:50:56', NULL),
(12, 2, 'New Maintenance Report Submitted', 'Mark Joseph Barreto submitted a new report: Alert this is testing report only (Report #7)', 0, '2026-04-05 05:53:01', NULL),
(14, 2, 'New Maintenance Report Submitted', 'Mark Joseph Barreto submitted a new report: Walang Bitaw (Report #8)', 0, '2026-04-08 11:03:48', NULL),
(16, 2, 'New Maintenance Report Submitted', 'Mark Joseph Barreto submitted a new report: Test Replacement Item (Report #9)', 0, '2026-04-08 11:18:10', NULL),
(17, 3, 'Report Assigned to You', 'Ryan Mondido assigned report #9 (Test Replacement Item) to you.', 1, '2026-04-08 12:07:04', NULL),
(19, 2, 'New Maintenance Report Submitted', 'Mark Joseph Barreto submitted a new report: Hahaha (Report #10)', 0, '2026-04-08 12:11:34', NULL),
(20, 13, 'Report Assigned to You', 'Ryan Mondido assigned report #10 (Hahaha) to you.', 0, '2026-04-08 12:18:53', NULL),
(21, 2, 'Report Assigned to You', 'Ryan Mondido assigned report #9 (Test Replacement Item) to you.', 0, '2026-04-08 12:20:58', NULL),
(22, 1, 'New Maintenance Report Submitted', 'Mark Joseph Barreto submitted a new report: Recent Reports (Report #11)', 0, '2026-04-10 01:13:19', NULL),
(23, 2, 'New Maintenance Report Submitted', 'Mark Joseph Barreto submitted a new report: Recent Reports (Report #11)', 0, '2026-04-10 01:13:19', NULL),
(24, 15, 'New Maintenance Report Submitted', 'Mark Joseph Barreto submitted a new report: Recent Reports (Report #11)', 0, '2026-04-10 01:13:19', NULL),
(25, 1, 'Password Reset Request', 'Bonifacio, Euclide L. requested a password reset. Please update the password in User Management.', 0, '2026-04-25 15:05:03', NULL),
(26, 1, 'New Maintenance Report Submitted', 'Manzo, Kristine Joy D. submitted a new report: Broken Heart (Report #12)', 0, '2026-04-25 15:12:16', NULL),
(27, 2, 'New Maintenance Report Submitted', 'Manzo, Kristine Joy D. submitted a new report: Broken Heart (Report #12)', 0, '2026-04-25 15:12:16', NULL),
(28, 15, 'New Maintenance Report Submitted', 'Manzo, Kristine Joy D. submitted a new report: Broken Heart (Report #12)', 0, '2026-04-25 15:12:16', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `report_inventory_allocations`
--

CREATE TABLE `report_inventory_allocations` (
  `id` int(10) UNSIGNED NOT NULL,
  `report_id` int(10) UNSIGNED NOT NULL,
  `item_id` int(10) UNSIGNED NOT NULL,
  `room_id` int(10) UNSIGNED NOT NULL,
  `reserved_qty` int(11) NOT NULL DEFAULT 0,
  `deployed_qty` int(11) NOT NULL DEFAULT 0,
  `status` enum('reserved','deployed','cancelled') NOT NULL DEFAULT 'reserved',
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `report_inventory_allocations`
--

INSERT INTO `report_inventory_allocations` (`id`, `report_id`, `item_id`, `room_id`, `reserved_qty`, `deployed_qty`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 5, 5, 1, 1, 0, 'reserved', 1, '2026-04-04 13:11:41', '2026-04-04 13:11:41');

-- --------------------------------------------------------

--
-- Table structure for table `restock_requests`
--

CREATE TABLE `restock_requests` (
  `id` int(10) UNSIGNED NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `requested_qty` int(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `source_report_id` int(10) UNSIGNED DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `status` enum('open','approved','ordered','received','cancelled') NOT NULL DEFAULT 'open',
  `requested_by` int(10) UNSIGNED DEFAULT NULL,
  `approved_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `restock_requests`
--

INSERT INTO `restock_requests` (`id`, `item_name`, `requested_qty`, `reason`, `source_report_id`, `priority`, `status`, `requested_by`, `approved_by`, `created_at`, `updated_at`) VALUES
(1, 'Whiteboard Marker Set', 1, 'Requested from report #5', 5, 'high', 'open', 1, NULL, '2026-04-05 04:55:40', '2026-04-05 04:55:40');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(10) UNSIGNED NOT NULL,
  `building_id` int(10) UNSIGNED NOT NULL,
  `floor_id` int(10) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `capacity` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `building_id`, `floor_id`, `name`, `capacity`, `created_at`, `updated_at`) VALUES
(1, 16, 1, 'Storage Room A', 50, '2026-04-04 02:02:35', '2026-04-04 02:02:35'),
(2, 16, 1, 'Science Lab 101', 50, '2026-04-04 02:02:35', '2026-04-04 02:02:35'),
(3, 16, 2, 'HAHAHA ROOMS', 50000, '2026-04-05 13:51:33', '2026-04-05 13:51:33'),
(4, 16, 1, 'lab 102', 50, '2026-04-09 14:00:57', '2026-04-09 14:00:57'),
(5, 16, 1, 'lab 103', 40, '2026-04-09 14:01:08', '2026-04-09 14:01:08'),
(6, 16, 1, 'lab 104', 40, '2026-04-09 14:01:23', '2026-04-09 14:01:23'),
(7, 16, 1, 'lab 105', 30, '2026-04-09 14:06:21', '2026-04-09 14:06:21');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('R7jlkXoxh0SUL7Cnt6OtkWyUz1lKZhkbpefCRUjR', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 'YTo4OntzOjY6Il90b2tlbiI7czo0MDoiVjVhOHBKdHR2WTZBUGQ1QVo4MXZXNjZ6VEdZa3V4YWNxVU5zbzB0bCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJhdXRoX3VzZXIiO2E6Nzp7czo3OiJ1c2VyX2lkIjtpOjE7czo5OiJmdWxsX25hbWUiO3M6MTI6IlJ5YW4gTW9uZGlkbyI7czo1OiJlbWFpbCI7czoxNjoiUnlhbjI3QGdtYWlsLmNvbSI7czo0OiJyb2xlIjtzOjExOiJzdXBlcl9hZG1pbiI7czo2OiJzdGF0dXMiO3M6NjoiYWN0aXZlIjtzOjEzOiJkZXBhcnRtZW50X2lkIjtOO3M6NjoiYXZhdGFyIjtzOjExMDoiL1NjaG9vbF9GYWNpbGl0eV9NYWludGVuYW5jZV9TeXN0ZW0vbGFyYXZlbF9hcHAvcHVibGljL2Zyb250ZW5kL2Fzc2V0cy91cGxvYWRzL2F2YXRhcnMvYXZhdGFyXzFfMTc3NTI4ODc5OC5qcGciO31zOjQ6InVzZXIiO2E6Nzp7czo3OiJ1c2VyX2lkIjtpOjE7czo5OiJmdWxsX25hbWUiO3M6MTI6IlJ5YW4gTW9uZGlkbyI7czo1OiJlbWFpbCI7czoxNjoiUnlhbjI3QGdtYWlsLmNvbSI7czo0OiJyb2xlIjtzOjExOiJzdXBlcl9hZG1pbiI7czo2OiJzdGF0dXMiO3M6NjoiYWN0aXZlIjtzOjEzOiJkZXBhcnRtZW50X2lkIjtOO3M6NjoiYXZhdGFyIjtzOjExMDoiL1NjaG9vbF9GYWNpbGl0eV9NYWludGVuYW5jZV9TeXN0ZW0vbGFyYXZlbF9hcHAvcHVibGljL2Zyb250ZW5kL2Fzc2V0cy91cGxvYWRzL2F2YXRhcnMvYXZhdGFyXzFfMTc3NTI4ODc5OC5qcGciO31zOjc6InVzZXJfaWQiO2k6MTtzOjQ6InJvbGUiO3M6MTE6InN1cGVyX2FkbWluIjtzOjEzOiJsYXN0X2FjdGl2aXR5IjtpOjE3NzcxMjkxNDk7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NzE6Imh0dHA6Ly9sb2NhbGhvc3QvU2Nob29sX0ZhY2lsaXR5X01haW50ZW5hbmNlX1N5c3RlbS9hcGkvZGFzaGJvYXJkL3N0YXRzIjtzOjU6InJvdXRlIjtOO319', 1777129941),
('X71673SxaJmeaAVVitleiEUFbeTuREtZL6q1qsjV', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'YTo4OntzOjY6Il90b2tlbiI7czo0MDoiYUNCcnY1YXdqMnF1WVhWODYzRzdmaEhnZGlmQW9rV0NCam9nRmdhVSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MTAyOiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvU2Nob29sX0ZhY2lsaXR5X01haW50ZW5hbmNlX1N5c3RlbS9mcm9udGVuZC9hc3NldHMvY3NzL21haW50ZW5hbmNlLWRhc2hib2FyZC5jc3MiO3M6NToicm91dGUiO047fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6OToiYXV0aF91c2VyIjthOjc6e3M6NzoidXNlcl9pZCI7aToxMztzOjk6ImZ1bGxfbmFtZSI7czoyMjoiTWFuem8sIEtyaXN0aW5lIEpveSBELiI7czo1OiJlbWFpbCI7czoyNjoia3Jpc3RpbmVqb3ltYW56b0BnbWFpbC5jb20iO3M6NDoicm9sZSI7czoxNzoibWFpbnRlbmFuY2Vfc3RhZmYiO3M6Njoic3RhdHVzIjtzOjY6ImFjdGl2ZSI7czoxMzoiZGVwYXJ0bWVudF9pZCI7TjtzOjY6ImF2YXRhciI7czo5MjoiL1NjaG9vbF9GYWNpbGl0eV9NYWludGVuYW5jZV9TeXN0ZW0vZnJvbnRlbmQvYXNzZXRzL3VwbG9hZHMvYXZhdGFycy9hdmF0YXJfMTNfMTc3NzEyOTI3MS5qcGciO31zOjQ6InVzZXIiO2E6Nzp7czo3OiJ1c2VyX2lkIjtpOjEzO3M6OToiZnVsbF9uYW1lIjtzOjIyOiJNYW56bywgS3Jpc3RpbmUgSm95IEQuIjtzOjU6ImVtYWlsIjtzOjI2OiJrcmlzdGluZWpveW1hbnpvQGdtYWlsLmNvbSI7czo0OiJyb2xlIjtzOjE3OiJtYWludGVuYW5jZV9zdGFmZiI7czo2OiJzdGF0dXMiO3M6NjoiYWN0aXZlIjtzOjEzOiJkZXBhcnRtZW50X2lkIjtOO3M6NjoiYXZhdGFyIjtzOjkyOiIvU2Nob29sX0ZhY2lsaXR5X01haW50ZW5hbmNlX1N5c3RlbS9mcm9udGVuZC9hc3NldHMvdXBsb2Fkcy9hdmF0YXJzL2F2YXRhcl8xM18xNzc3MTI5MjcxLmpwZyI7fXM6NzoidXNlcl9pZCI7aToxMztzOjQ6InJvbGUiO3M6MTc6Im1haW50ZW5hbmNlX3N0YWZmIjtzOjEzOiJsYXN0X2FjdGl2aXR5IjtpOjE3NzcxMjk4NjY7fQ==', 1777130054);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `employee_id` varchar(20) DEFAULT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'user',
  `department_id` int(10) UNSIGNED DEFAULT NULL,
  `status` enum('active','inactive','suspended','pending') NOT NULL DEFAULT 'active',
  `avatar` varchar(500) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `force_profile_update` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `employee_id`, `full_name`, `email`, `password`, `role`, `department_id`, `status`, `avatar`, `remember_token`, `created_at`, `updated_at`, `created_by`, `force_profile_update`) VALUES
(1, NULL, 'Ryan Mondido', 'ryaondido27@gmail.com', '$2y$12$3YMY43XdyBIXbmYHnaIt2OfMrzcJSyRnpJuzBQZFL3FuC/MU3oRjy', 'super_admin', NULL, 'active', '/School_Facility_Maintenance_System/frontend/assets/uploads/avatars/avatar_1_1777129763.jpg', NULL, '2026-03-27 14:52:33', '2026-04-25 15:09:23', NULL, 0),
(2, NULL, 'Mariah Redjie, Galising L.', 'mariahredjiegalising08@gmail.com', '$2y$12$2lMhLROFY2LxiWjtvBzm2.Ce8WnI8xUR5cZMwITLooLHw3XI7GoO6', 'maintenance_admin', NULL, 'active', '/School_Facility_Maintenance_System/frontend/assets/uploads/avatars/avatar_2_1777129355.jpg', NULL, '2026-03-27 14:52:33', '2026-04-25 15:03:09', NULL, 0),
(3, NULL, 'Bonifacio, Euclide L.', 'bonifacioeuclide@gmail.com', '$2y$12$G7ms8SlXIWhHcuA81at.YOmbkl0Pz1pwy43zi43SNnDHNcIiGoRdq', 'maintenance_staff', NULL, 'active', '/School_Facility_Maintenance_System/frontend/assets/uploads/avatars/avatar_3_1777129461.jpg', NULL, '2026-03-29 10:07:49', '2026-04-25 15:04:21', NULL, 0),
(13, NULL, 'Manzo, Kristine Joy D.', 'kristinejoymanzo@gmail.com', '$2y$12$DFnoqeboZ9dCo0arfpyUJeomatZCIoiaPKS/jpPEF1AigPm2CHZk.', 'maintenance_staff', NULL, 'active', '/School_Facility_Maintenance_System/frontend/assets/uploads/avatars/avatar_13_1777129271.jpg', NULL, '2026-04-07 10:48:51', '2026-04-25 15:10:45', 1, 0),
(15, '20260409', 'Mondido Ryan F.', 'ryan27@gmail.com', '$2y$12$Pd7yJRa5gLzss6PThk.asehQuCTROy4y6ldzioc9.Ac8TaVLzHuFq', 'maintenance_admin', NULL, 'active', '/School_Facility_Maintenance_System/frontend/assets/uploads/avatars/avatar_15_1777129805.jpg', NULL, '2026-04-09 12:15:13', '2026-04-25 15:10:05', 1, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `activity_logs_user_id_action_index` (`user_id`,`action`);

--
-- Indexes for table `buildings`
--
ALTER TABLE `buildings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `buildings_name_unique` (`name`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`department_id`),
  ADD UNIQUE KEY `departments_name_unique` (`name`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `floors`
--
ALTER TABLE `floors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `floors_building_id_name_unique` (`building_id`,`name`);

--
-- Indexes for table `inventory_categories`
--
ALTER TABLE `inventory_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `inventory_transactions_item_id_transaction_type_index` (`item_id`,`transaction_type`),
  ADD KEY `inventory_transactions_report_id_index` (`report_id`),
  ADD KEY `inventory_transactions_room_id_index` (`room_id`),
  ADD KEY `inventory_transactions_performed_by_foreign` (`performed_by`);

--
-- Indexes for table `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `items_room_id_status_index` (`room_id`,`status`),
  ADD KEY `idx_items_category_id` (`category_id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `maintenance_reports`
--
ALTER TABLE `maintenance_reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `maintenance_reports_status_priority_index` (`status`,`priority`),
  ADD KEY `maintenance_reports_created_by_assigned_to_index` (`created_by`,`assigned_to`),
  ADD KEY `maintenance_reports_assigned_to_foreign` (`assigned_to`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notifications_user_id_is_read_index` (`user_id`,`is_read`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `report_inventory_allocations`
--
ALTER TABLE `report_inventory_allocations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `report_inventory_allocations_report_id_status_index` (`report_id`,`status`),
  ADD KEY `report_inventory_allocations_item_id_room_id_index` (`item_id`,`room_id`),
  ADD KEY `report_inventory_allocations_room_id_foreign` (`room_id`),
  ADD KEY `report_inventory_allocations_created_by_foreign` (`created_by`);

--
-- Indexes for table `restock_requests`
--
ALTER TABLE `restock_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `restock_requests_status_priority_index` (`status`,`priority`),
  ADD KEY `restock_requests_source_report_id_foreign` (`source_report_id`),
  ADD KEY `restock_requests_requested_by_foreign` (`requested_by`),
  ADD KEY `restock_requests_approved_by_foreign` (`approved_by`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rooms_building_id_name_unique` (`building_id`,`name`),
  ADD UNIQUE KEY `rooms_floor_id_name_unique` (`floor_id`,`name`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD UNIQUE KEY `users_employee_id_unique` (`employee_id`),
  ADD KEY `users_role_index` (`role`),
  ADD KEY `users_status_index` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=203;

--
-- AUTO_INCREMENT for table `buildings`
--
ALTER TABLE `buildings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `department_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `floors`
--
ALTER TABLE `floors`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `inventory_categories`
--
ALTER TABLE `inventory_categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `maintenance_reports`
--
ALTER TABLE `maintenance_reports`
  MODIFY `report_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `report_inventory_allocations`
--
ALTER TABLE `report_inventory_allocations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `restock_requests`
--
ALTER TABLE `restock_requests`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `floors`
--
ALTER TABLE `floors`
  ADD CONSTRAINT `floors_building_id_foreign` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD CONSTRAINT `inventory_transactions_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_transactions_performed_by_foreign` FOREIGN KEY (`performed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `inventory_transactions_report_id_foreign` FOREIGN KEY (`report_id`) REFERENCES `maintenance_reports` (`report_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `inventory_transactions_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `items`
--
ALTER TABLE `items`
  ADD CONSTRAINT `fk_items_inventory_category` FOREIGN KEY (`category_id`) REFERENCES `inventory_categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `items_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `maintenance_reports`
--
ALTER TABLE `maintenance_reports`
  ADD CONSTRAINT `maintenance_reports_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `maintenance_reports_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `report_inventory_allocations`
--
ALTER TABLE `report_inventory_allocations`
  ADD CONSTRAINT `report_inventory_allocations_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `report_inventory_allocations_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `report_inventory_allocations_report_id_foreign` FOREIGN KEY (`report_id`) REFERENCES `maintenance_reports` (`report_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `report_inventory_allocations_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `restock_requests`
--
ALTER TABLE `restock_requests`
  ADD CONSTRAINT `restock_requests_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `restock_requests_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `restock_requests_source_report_id_foreign` FOREIGN KEY (`source_report_id`) REFERENCES `maintenance_reports` (`report_id`) ON DELETE SET NULL;

--
-- Constraints for table `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `rooms_building_id_foreign` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rooms_floor_id_foreign` FOREIGN KEY (`floor_id`) REFERENCES `floors` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
