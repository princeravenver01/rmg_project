-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 14, 2025 at 09:22 AM
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
-- Database: `rmg_database`
--

-- --------------------------------------------------------

--
-- Table structure for table `activation_codes`
--

CREATE TABLE `activation_codes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(50) NOT NULL,
  `package_id` int(10) UNSIGNED NOT NULL,
  `account_type` varchar(20) NOT NULL,
  `status` enum('available','used') NOT NULL DEFAULT 'available',
  `generated_by_id` bigint(20) UNSIGNED NOT NULL,
  `source_sale_id` int(10) UNSIGNED DEFAULT NULL,
  `used_by_id` bigint(20) UNSIGNED DEFAULT NULL,
  `used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activation_codes`
--

INSERT INTO `activation_codes` (`id`, `code`, `package_id`, `account_type`, `status`, `generated_by_id`, `source_sale_id`, `used_by_id`, `used_at`, `created_at`) VALUES
(38, 'RMG-E5727860', 3, 'Paid Account', 'used', 1, 22, 24, '2025-10-10 06:31:26', '2025-10-10 06:30:57'),
(39, 'RMG-22462875', 3, 'Paid Account', 'used', 1, 23, 25, '2025-10-10 06:32:47', '2025-10-10 06:32:19'),
(40, 'RMG-3978F0DC', 3, 'CD Account', 'used', 1, NULL, 26, '2025-10-10 06:37:26', '2025-10-10 06:37:01'),
(41, 'RMG-08B0324D', 3, 'FS Account', 'used', 1, NULL, 27, '2025-10-10 06:38:27', '2025-10-10 06:38:05'),
(42, 'RMG-3165C5F4', 3, 'FS Account', 'used', 1, NULL, 29, '2025-10-10 08:04:23', '2025-10-10 07:50:58'),
(43, 'RMG-16B7ABB7', 3, 'Paid Account', 'used', 1, 24, 30, '2025-10-10 08:05:11', '2025-10-10 08:04:51'),
(44, 'RMG-6F5F7C38', 3, 'Paid Account', 'used', 1, 25, 31, '2025-10-10 08:31:45', '2025-10-10 08:31:23'),
(45, 'RMG-59B0B4E0', 3, 'Paid Account', 'used', 1, 26, 32, '2025-10-10 08:32:54', '2025-10-10 08:32:37'),
(46, 'RMG-BCC7C1A9', 3, 'FS Account', 'available', 1, NULL, NULL, NULL, '2025-10-10 09:56:28'),
(47, 'RMG-0205E3B5', 3, 'Paid Account', 'used', 1, 27, 33, '2025-10-13 15:26:09', '2025-10-13 15:10:41'),
(48, 'RMG-453D642A', 3, 'Paid Account', 'used', 1, 28, 34, '2025-10-13 15:27:09', '2025-10-13 15:26:47'),
(51, 'RMG-0BD8877A', 3, 'Paid Account', 'available', 1, 29, NULL, NULL, '2025-10-14 05:53:49'),
(52, 'RMG-C4B610B6', 3, 'Paid Account', 'used', 1, 35, 35, '2025-10-14 06:09:38', '2025-10-14 06:07:31');

-- --------------------------------------------------------

--
-- Table structure for table `binary_points`
--

CREATE TABLE `binary_points` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL COMMENT 'The user who OWNS these points',
  `points` int(11) NOT NULL,
  `position` enum('L','R') NOT NULL COMMENT 'Which leg of the user these points are on',
  `source_user_id` bigint(20) UNSIGNED NOT NULL COMMENT 'The new member who generated these points',
  `status` enum('unprocessed','processed') NOT NULL DEFAULT 'unprocessed' COMMENT 'To track if points have been used in a commission run',
  `cycle_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'ID of the commission cycle that processed these points',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `binary_points`
--

INSERT INTO `binary_points` (`id`, `user_id`, `points`, `position`, `source_user_id`, `status`, `cycle_id`, `created_at`) VALUES
(3901008, 20, 50, 'L', 24, 'processed', 2025, '2025-10-10 06:31:26'),
(3901009, 20, 50, 'R', 25, 'processed', 2025, '2025-10-10 06:32:47'),
(3901030, 25, 50, 'R', 31, 'processed', 2025, '2025-10-10 08:34:51'),
(3901031, 20, 50, 'R', 31, 'processed', 2025, '2025-10-10 08:34:51'),
(3901035, 24, 50, 'L', 32, 'processed', 2025, '2025-10-10 08:35:35'),
(3901036, 20, 50, 'L', 32, 'processed', 2025, '2025-10-10 08:35:35'),
(3901037, 24, 50, 'R', 33, 'processed', 2025, '2025-10-13 15:26:09'),
(3901038, 20, 50, 'L', 33, 'processed', 2025, '2025-10-13 15:26:09'),
(3901039, 25, 50, 'L', 34, 'processed', 2025, '2025-10-13 15:27:09'),
(3901040, 20, 50, 'R', 34, 'processed', 2025, '2025-10-13 15:27:09'),
(3901041, 32, 50, 'L', 35, 'unprocessed', NULL, '2025-10-14 06:09:38'),
(3901042, 24, 50, 'L', 35, 'unprocessed', NULL, '2025-10-14 06:09:38'),
(3901043, 20, 50, 'L', 35, 'unprocessed', NULL, '2025-10-14 06:09:38');

-- --------------------------------------------------------

--
-- Table structure for table `commissions`
--

CREATE TABLE `commissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL COMMENT 'Who received the commission',
  `type` enum('binary_pair','leadership_l1','leadership_l2','leadership_l3','cycle_bonus','unilevel_bonus') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `source_user_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'The downline member who triggered this commission',
  `cycle_id` varchar(255) NOT NULL COMMENT 'Unique ID for the commission run, e.g., YYYY-MM-DD-AM/PM',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `commissions`
--

INSERT INTO `commissions` (`id`, `user_id`, `type`, `amount`, `source_user_id`, `cycle_id`, `created_at`) VALUES
(6, 20, 'binary_pair', 500.00, NULL, '2025-10-10-AM', '2025-10-10 06:33:13'),
(7, 20, 'binary_pair', 1000.00, NULL, '2025-10-13-PM', '2025-10-13 15:27:27'),
(8, 24, 'binary_pair', 500.00, NULL, '2025-10-13-PM', '2025-10-13 15:27:27'),
(9, 20, 'leadership_l1', 250.00, 24, '2025-10-13-PM', '2025-10-13 15:27:27'),
(10, 25, 'binary_pair', 500.00, NULL, '2025-10-13-PM', '2025-10-13 15:27:27'),
(11, 20, 'leadership_l1', 250.00, 25, '2025-10-13-PM', '2025-10-13 15:27:27');

-- --------------------------------------------------------

--
-- Table structure for table `company_gains`
--

CREATE TABLE `company_gains` (
  `id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `source_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cycle_id` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `debt_payments`
--

CREATE TABLE `debt_payments` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `encashment_request_id` int(10) UNSIGNED NOT NULL,
  `amount_deducted` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `encashment_requests`
--

CREATE TABLE `encashment_requests` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','approved','declined') NOT NULL DEFAULT 'pending',
  `requested_at` timestamp NULL DEFAULT current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL,
  `processed_by_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Admin/Staff who processed it',
  `notes` text DEFAULT NULL COMMENT 'Reason for decline, or transaction ID'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `genealogy_tree`
--

CREATE TABLE `genealogy_tree` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `sponsor_id` bigint(20) UNSIGNED NOT NULL,
  `upline_id` bigint(20) UNSIGNED NOT NULL,
  `position` enum('L','R') NOT NULL,
  `path` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `genealogy_tree`
--

INSERT INTO `genealogy_tree` (`id`, `user_id`, `sponsor_id`, `upline_id`, `position`, `path`, `created_at`) VALUES
(1, 20, 20, 0, 'L', NULL, '2025-10-09 16:37:51'),
(20, 24, 20, 20, 'L', NULL, '2025-10-10 06:31:26'),
(21, 25, 20, 20, 'R', NULL, '2025-10-10 06:32:47'),
(27, 31, 20, 25, 'R', NULL, '2025-10-10 08:31:45'),
(28, 32, 20, 24, 'L', NULL, '2025-10-10 08:32:54'),
(29, 33, 20, 24, 'R', NULL, '2025-10-13 15:26:09'),
(30, 34, 20, 25, 'L', NULL, '2025-10-13 15:27:09'),
(31, 35, 20, 32, 'L', NULL, '2025-10-14 06:09:38');

-- --------------------------------------------------------

--
-- Table structure for table `gift_certificates`
--

CREATE TABLE `gift_certificates` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(50) NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL COMMENT 'The member who earned this GC',
  `amount` decimal(10,2) NOT NULL,
  `source_commission_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'The commission that triggered this GC',
  `status` enum('active','used') NOT NULL DEFAULT 'active',
  `used_at` datetime DEFAULT NULL,
  `used_in_sale_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `member_profiles`
--

CREATE TABLE `member_profiles` (
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `address` text DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `debt_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `carry_over_left` int(11) NOT NULL DEFAULT 0,
  `carry_over_right` int(11) NOT NULL DEFAULT 0,
  `redundant_points_balance` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `member_profiles`
--

INSERT INTO `member_profiles` (`user_id`, `address`, `phone_number`, `debt_balance`, `carry_over_left`, `carry_over_right`, `redundant_points_balance`) VALUES
(22, NULL, NULL, 0.00, 0, 0, 0),
(23, NULL, NULL, 0.00, 0, 0, 0),
(24, NULL, NULL, 0.00, 0, 0, 0),
(25, NULL, NULL, 0.00, 0, 0, 0),
(31, NULL, NULL, 0.00, 0, 0, 0),
(32, NULL, NULL, 0.00, 0, 0, 0),
(33, NULL, NULL, 0.00, 0, 0, 0),
(34, NULL, NULL, 0.00, 0, 0, 0),
(35, NULL, NULL, 0.00, 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `packages`
--

CREATE TABLE `packages` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `points_value` int(11) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `packages`
--

INSERT INTO `packages` (`id`, `name`, `price`, `points_value`, `image_path`, `is_active`) VALUES
(3, 'Kick-Start Package (Option A)', 4988.00, 50, NULL, 1),
(4, 'Kick-Start Package (Option B)', 4988.00, 50, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `package_products`
--

CREATE TABLE `package_products` (
  `id` int(10) UNSIGNED NOT NULL,
  `package_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `package_products`
--

INSERT INTO `package_products` (`id`, `package_id`, `product_id`, `quantity`) VALUES
(5, 3, 5, 2),
(6, 4, 3, 1),
(7, 4, 4, 1),
(8, 4, 5, 1);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `manufacturer` varchar(255) DEFAULT NULL,
  `member_price` decimal(10,2) NOT NULL,
  `srp` decimal(10,2) NOT NULL COMMENT 'Suggested Retail Price',
  `points_value` int(11) NOT NULL,
  `unilevel_bonus` decimal(10,2) NOT NULL DEFAULT 0.00,
  `barcode` varchar(100) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `manufacturer`, `member_price`, `srp`, `points_value`, `unilevel_bonus`, `barcode`, `image_path`, `stock_quantity`, `created_at`) VALUES
(3, 'Tricura', 'N/A', 'RMG Corporation', 1480.00, 2500.00, 4, 18.00, '1001', '', 100, '2025-10-01 16:11:06'),
(4, 'Fish-Oil', 'N/A', 'RMG Corporation', 780.00, 1500.00, 3, 0.00, '1002', NULL, 99, '2025-10-01 16:11:34'),
(5, 'Vitamin-C', 'N/A', 'RMG Corporation', 740.00, 1350.00, 2, 0.00, '1003', NULL, 100, '2025-10-01 16:12:06'),
(6, 'Gab-Oil', 'N/A', 'RMG Corporation', 99.00, 169.00, 1, 0.00, '1004', NULL, 100, '2025-10-01 16:12:44'),
(7, 'Alpha', 'N/A', 'RMG Corporation', 295.00, 430.00, 1, 0.00, '1005', NULL, 100, '2025-10-01 16:13:11'),
(8, 'Paid Account (4,988.00)', 'N/A', 'RMG Corporation', 4988.00, 4988.00, 1, 0.00, '11110000', '', 73, '2025-10-01 16:15:55');

-- --------------------------------------------------------

--
-- Table structure for table `product_sales`
--

CREATE TABLE `product_sales` (
  `id` int(10) UNSIGNED NOT NULL,
  `member_id` bigint(20) UNSIGNED NOT NULL COMMENT 'The member who purchased',
  `total_amount` decimal(10,2) NOT NULL,
  `total_points` int(11) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `processed_by_id` bigint(20) UNSIGNED NOT NULL COMMENT 'Admin/Staff who recorded it',
  `is_code_generated` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_sales`
--

INSERT INTO `product_sales` (`id`, `member_id`, `total_amount`, `total_points`, `payment_method`, `processed_by_id`, `is_code_generated`, `created_at`) VALUES
(22, 20, 4988.00, 1, 'Cash', 1, 1, '2025-10-10 06:30:48'),
(23, 20, 4988.00, 1, 'Cash', 1, 1, '2025-10-10 06:32:04'),
(24, 20, 4988.00, 1, 'Cash', 1, 1, '2025-10-10 08:04:43'),
(25, 20, 4988.00, 1, 'Cash', 1, 1, '2025-10-10 08:31:11'),
(26, 20, 4988.00, 1, 'Cash', 1, 1, '2025-10-10 08:32:26'),
(27, 20, 4988.00, 1, 'Cash', 1, 1, '2025-10-13 15:10:31'),
(28, 20, 4988.00, 1, 'Cash', 1, 1, '2025-10-13 15:26:40'),
(29, 20, 4988.00, 1, 'Cash', 1, 1, '2025-10-14 04:40:47'),
(30, 20, 4988.00, 1, 'Cash', 1, 0, '2025-10-14 04:52:39'),
(31, 20, 4988.00, 1, 'Cash', 1, 0, '2025-10-14 05:08:37'),
(32, 20, 4988.00, 1, 'Cash', 1, 0, '2025-10-14 05:33:28'),
(33, 0, 4988.00, 0, '', 0, 0, '2025-10-14 05:44:54'),
(34, 20, 4988.00, 1, 'Cash', 1, 0, '2025-10-14 05:54:05'),
(35, 20, 4988.00, 1, 'Cash', 1, 1, '2025-10-14 06:06:20'),
(36, 20, 14964.00, 3, 'Cash', 1, 0, '2025-10-14 06:06:48'),
(37, 20, 4988.00, 1, 'Cash', 43, 0, '2025-10-14 06:26:24'),
(38, 20, 4988.00, 1, 'Cash', 43, 0, '2025-10-14 06:26:34'),
(39, 20, 4988.00, 1, 'Cash', 43, 0, '2025-10-14 06:41:36'),
(40, 20, 9976.00, 2, 'Cash', 43, 0, '2025-10-14 06:41:56'),
(41, 20, 4988.00, 1, 'Cash', 43, 0, '2025-10-14 07:04:14'),
(42, 20, 4988.00, 1, 'Cash', 43, 0, '2025-10-14 07:16:15'),
(43, 20, 4988.00, 1, 'Cash', 43, 0, '2025-10-14 07:19:40');

-- --------------------------------------------------------

--
-- Table structure for table `product_sale_items`
--

CREATE TABLE `product_sale_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `sale_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `quantity` int(11) NOT NULL,
  `price_per_item` decimal(10,2) NOT NULL,
  `points_per_item` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_sale_items`
--

INSERT INTO `product_sale_items` (`id`, `sale_id`, `product_id`, `quantity`, `price_per_item`, `points_per_item`) VALUES
(21, 22, 8, 1, 4988.00, 1),
(22, 23, 8, 1, 4988.00, 1),
(23, 24, 8, 1, 4988.00, 1),
(24, 25, 8, 1, 4988.00, 1),
(25, 26, 8, 1, 4988.00, 1),
(26, 27, 8, 1, 4988.00, 1),
(27, 28, 8, 1, 4988.00, 1),
(28, 29, 8, 1, 4988.00, 1),
(29, 35, 8, 1, 4988.00, 1),
(30, 36, 8, 3, 4988.00, 1),
(31, 37, 8, 1, 4988.00, 1),
(32, 38, 8, 1, 4988.00, 1),
(33, 41, 8, 1, 4988.00, 1),
(34, 42, 8, 1, 4988.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `purchase_history`
--

CREATE TABLE `purchase_history` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'The member who made the purchase',
  `package_id` int(10) UNSIGNED NOT NULL,
  `account_type` varchar(20) NOT NULL,
  `activation_code_id` bigint(20) UNSIGNED NOT NULL COMMENT 'The code generated by this purchase',
  `price_paid` decimal(10,2) NOT NULL,
  `points_earned` int(11) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `processed_by_id` bigint(20) UNSIGNED NOT NULL COMMENT 'Admin/Staff who recorded it',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_history`
--

INSERT INTO `purchase_history` (`id`, `user_id`, `package_id`, `account_type`, `activation_code_id`, `price_paid`, `points_earned`, `payment_method`, `processed_by_id`, `created_at`) VALUES
(30, NULL, 3, 'Paid Account', 38, 4988.00, 50, 'Admin Generated', 1, '2025-10-10 06:30:57'),
(31, NULL, 3, 'Paid Account', 39, 4988.00, 50, 'Admin Generated', 1, '2025-10-10 06:32:19'),
(32, NULL, 3, 'CD Account', 40, 4988.00, 50, 'Admin Generated', 1, '2025-10-10 06:37:01'),
(33, NULL, 3, 'FS Account', 41, 0.00, 50, 'Admin Generated', 1, '2025-10-10 06:38:05'),
(34, NULL, 3, 'FS Account', 42, 0.00, 50, 'Admin Generated', 1, '2025-10-10 07:50:58'),
(35, NULL, 3, 'Paid Account', 43, 4988.00, 50, 'Admin Generated', 1, '2025-10-10 08:04:51'),
(36, NULL, 3, 'Paid Account', 44, 4988.00, 50, 'Admin Generated', 1, '2025-10-10 08:31:23'),
(37, NULL, 3, 'Paid Account', 45, 4988.00, 50, 'Admin Generated', 1, '2025-10-10 08:32:37'),
(38, NULL, 3, 'FS Account', 46, 0.00, 50, 'Admin Generated', 1, '2025-10-10 09:56:28'),
(39, NULL, 3, 'Paid Account', 47, 4988.00, 50, 'Admin Generated', 1, '2025-10-13 15:10:41'),
(40, NULL, 3, 'Paid Account', 48, 4988.00, 50, 'Admin Generated', 1, '2025-10-13 15:26:47'),
(41, NULL, 3, 'Paid Account', 51, 4988.00, 50, 'Admin Generated', 1, '2025-10-14 05:53:49'),
(42, NULL, 3, 'Paid Account', 52, 4988.00, 50, 'Admin Generated', 1, '2025-10-14 06:07:31');

-- --------------------------------------------------------

--
-- Table structure for table `refbrgy`
--

CREATE TABLE `refbrgy` (
  `id` int(11) NOT NULL,
  `brgyCode` varchar(255) DEFAULT NULL,
  `brgyDesc` text DEFAULT NULL,
  `regCode` varchar(255) DEFAULT NULL,
  `provCode` varchar(255) DEFAULT NULL,
  `citymunCode` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `refcitymun`
--

CREATE TABLE `refcitymun` (
  `id` int(255) NOT NULL,
  `psgcCode` varchar(255) DEFAULT NULL,
  `citymunDesc` text DEFAULT NULL,
  `regDesc` varchar(255) DEFAULT NULL,
  `provCode` varchar(255) DEFAULT NULL,
  `citymunCode` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `refprovince`
--

CREATE TABLE `refprovince` (
  `id` int(11) NOT NULL,
  `psgcCode` varchar(255) DEFAULT NULL,
  `provDesc` text DEFAULT NULL,
  `regCode` varchar(255) DEFAULT NULL,
  `provCode` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `refregion`
--

CREATE TABLE `refregion` (
  `id` int(11) NOT NULL,
  `psgcCode` varchar(255) DEFAULT NULL,
  `regDesc` text DEFAULT NULL,
  `regCode` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES
('gift_certificate_amount', '1000.00');

-- --------------------------------------------------------

--
-- Table structure for table `tg_beneficiaries`
--

CREATE TABLE `tg_beneficiaries` (
  `id` int(11) NOT NULL,
  `rmg_policy_holder_id` bigint(20) UNSIGNED NOT NULL COMMENT 'FK to the users table',
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tg_beneficiaries`
--

INSERT INTO `tg_beneficiaries` (`id`, `rmg_policy_holder_id`, `first_name`, `last_name`, `created_at`) VALUES
(1, 35, 'adwd', 'awdawd', '2025-10-14 06:09:39'),
(2, 35, 'awdwad', 'awcawd', '2025-10-14 06:09:39');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `rank` varchar(50) NOT NULL DEFAULT 'Member',
  `account_type` varchar(50) NOT NULL DEFAULT 'Standard',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_tg_member` tinyint(1) NOT NULL DEFAULT 0,
  `total_pairs_earned` int(11) NOT NULL DEFAULT 0,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','staff','member') NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `username`, `rank`, `account_type`, `is_active`, `is_tg_member`, `total_pairs_earned`, `password`, `role`, `created_at`, `updated_at`) VALUES
(1, 'Super Admin', 'admin@rmg.com', 'SuperAdmin01', 'Admin', 'N/A', 1, 0, 0, '$2y$10$XSHrMjN4UtRSVT0a78nCxOq5wM2M1Xyi/KkVTmwtu7dERnS1EmvUq', 'admin', '2025-09-29 14:09:03', '2025-09-30 14:34:20'),
(20, 'RMG Corporation Account', 'corp@rmg.com', 'RMG_Acc_01', 'Member', 'Company', 1, 0, 0, '$2y$10$eEq8kOlUBHfCCHbzVbvw5exroVXqxuyfpNcH0V6GbmOYiU52P3mue', 'member', '2025-10-09 16:36:30', '2025-10-10 05:59:57'),
(21, 'John Doe', 'johndoe@gmail.com', NULL, 'staff', 'Standard', 1, 0, 0, '$2y$10$wBSmXl8gpP/MU3jix/aXu.yAMQbu2L5C1Ec75TwtEJK/9Q4hcd6HG', 'staff', '2025-10-09 16:42:06', '2025-10-14 03:26:21'),
(24, 'RMG Corporation Account 2', 'rmg@gmail.com', 'RMG_Acc_02', 'Member', 'Paid Account', 1, 0, 0, '$2y$10$kKtPgr71dgej9RGRrBnVO.yVbUCq35XSQiCpL3fAQdbROAnWmy80.', 'member', '2025-10-10 06:31:26', '2025-10-10 06:31:26'),
(25, 'RMG Corporation Account 3', 'rmg3@gmail.com', 'RMG_Acc_03', 'Member', 'Paid Account', 1, 0, 0, '$2y$10$ybRB0iVaqnmyqZB3J0DAHuT3pRUKJMXgyc8IaDILkFKHlva0wanP.', 'member', '2025-10-10 06:32:47', '2025-10-10 06:32:47'),
(31, 'Jane Doe', 'aiwjd@gma.com', 'jane01', 'Member', 'Paid Account', 1, 1, 0, '$2y$10$GOF3wt9pnHQ.lCW8MoIJ3uIHl0TTMVVKiAh1mGLiH9R6dUwFlRf4K', 'member', '2025-10-10 08:31:45', '2025-10-13 15:12:35'),
(32, 'kael Doe', 'aokwd@gka.com', 'kael1', 'Member', 'Paid Account', 1, 0, 0, '$2y$10$mpyYWRuL7CSUjMTFIPiR.OUxBPzceqvr6kNVDQCKW6by0uk8ltZei', 'member', '2025-10-10 08:32:54', '2025-10-10 08:32:54'),
(33, 'Jane Doe', 'aodw@mfaomw.com', 'janedoe2', 'Member', 'Paid Account', 1, 0, 0, '$2y$10$fEN68423lVomheQ40YEyoejbwvSG3fFItfMp7Pm.R6XGA0.lmiYXe', 'member', '2025-10-13 15:26:09', '2025-10-13 15:26:09'),
(34, 'Jane Doe', 'akwd@foaokwd.com', 'janedoe3', 'Member', 'Paid Account', 1, 0, 0, '$2y$10$gK55nKjLNzg8KDBV9K5im.nzpyCeCsd6Ay87hMlOBL643yUFZ0oPK', 'member', '2025-10-13 15:27:09', '2025-10-13 15:27:09'),
(35, 'James Doe', 'princeravenver04@gmail.com', 'jamesd1', 'Member', 'Paid Account', 1, 1, 0, '$2y$10$zPSgy4NI0ClR8ki/vDg3Yufh.VYFB8oAl84Tpd0ih8xcPC85Rd/8G', 'member', '2025-10-14 06:09:38', '2025-10-14 06:09:38');

-- --------------------------------------------------------

--
-- Table structure for table `vouchers`
--

CREATE TABLE `vouchers` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(50) NOT NULL,
  `discount_type` enum('fixed','percent') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `usage_limit` int(11) DEFAULT 1,
  `times_used` int(11) NOT NULL DEFAULT 0,
  `expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vouchers`
--

INSERT INTO `vouchers` (`id`, `code`, `discount_type`, `discount_value`, `is_active`, `usage_limit`, `times_used`, `expires_at`) VALUES
(1, 'DISCOUNT100', 'fixed', 100.00, 1, 10, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `wallets`
--

CREATE TABLE `wallets` (
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wallets`
--

INSERT INTO `wallets` (`user_id`, `balance`, `updated_at`) VALUES
(1, 1000.00, '2025-09-29 16:27:03'),
(20, 2000.00, '2025-10-13 15:27:27'),
(24, 500.00, '2025-10-13 15:27:27'),
(25, 500.00, '2025-10-13 15:27:27');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activation_codes`
--
ALTER TABLE `activation_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `activation_codes_code_unique` (`code`);

--
-- Indexes for table `binary_points`
--
ALTER TABLE `binary_points`
  ADD PRIMARY KEY (`id`),
  ADD KEY `binary_points_user_id_status_index` (`user_id`,`status`);

--
-- Indexes for table `commissions`
--
ALTER TABLE `commissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `commissions_user_id_index` (`user_id`);

--
-- Indexes for table `company_gains`
--
ALTER TABLE `company_gains`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `debt_payments`
--
ALTER TABLE `debt_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `debt_payments_user_id_index` (`user_id`);

--
-- Indexes for table `encashment_requests`
--
ALTER TABLE `encashment_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `encashment_requests_user_id_index` (`user_id`);

--
-- Indexes for table `genealogy_tree`
--
ALTER TABLE `genealogy_tree`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `genealogy_tree_user_id_unique` (`user_id`);

--
-- Indexes for table `gift_certificates`
--
ALTER TABLE `gift_certificates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `gc_code_unique` (`code`);

--
-- Indexes for table `member_profiles`
--
ALTER TABLE `member_profiles`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `packages`
--
ALTER TABLE `packages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `package_products`
--
ALTER TABLE `package_products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `package_id` (`package_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product_sales`
--
ALTER TABLE `product_sales`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product_sale_items`
--
ALTER TABLE `product_sale_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`);

--
-- Indexes for table `purchase_history`
--
ALTER TABLE `purchase_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `refbrgy`
--
ALTER TABLE `refbrgy`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `refcitymun`
--
ALTER TABLE `refcitymun`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `refprovince`
--
ALTER TABLE `refprovince`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `refregion`
--
ALTER TABLE `refregion`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `tg_beneficiaries`
--
ALTER TABLE `tg_beneficiaries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rmg_policy_holder_id` (`rmg_policy_holder_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `vouchers`
--
ALTER TABLE `vouchers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vouchers_code_unique` (`code`);

--
-- Indexes for table `wallets`
--
ALTER TABLE `wallets`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activation_codes`
--
ALTER TABLE `activation_codes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `binary_points`
--
ALTER TABLE `binary_points`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3901044;

--
-- AUTO_INCREMENT for table `commissions`
--
ALTER TABLE `commissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `company_gains`
--
ALTER TABLE `company_gains`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `debt_payments`
--
ALTER TABLE `debt_payments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `encashment_requests`
--
ALTER TABLE `encashment_requests`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `genealogy_tree`
--
ALTER TABLE `genealogy_tree`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `gift_certificates`
--
ALTER TABLE `gift_certificates`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `packages`
--
ALTER TABLE `packages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `package_products`
--
ALTER TABLE `package_products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `product_sales`
--
ALTER TABLE `product_sales`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `product_sale_items`
--
ALTER TABLE `product_sale_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `purchase_history`
--
ALTER TABLE `purchase_history`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `refbrgy`
--
ALTER TABLE `refbrgy`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `refcitymun`
--
ALTER TABLE `refcitymun`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `refprovince`
--
ALTER TABLE `refprovince`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `refregion`
--
ALTER TABLE `refregion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tg_beneficiaries`
--
ALTER TABLE `tg_beneficiaries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `vouchers`
--
ALTER TABLE `vouchers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `package_products`
--
ALTER TABLE `package_products`
  ADD CONSTRAINT `package_products_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `package_products_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_sale_items`
--
ALTER TABLE `product_sale_items`
  ADD CONSTRAINT `product_sale_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `product_sales` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
