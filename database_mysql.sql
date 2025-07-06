-- OrderDesk MySQL Database Schema
-- Compatible with shared hosting providers like Namecheap
-- Use this file for MySQL/MariaDB databases

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Users table
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('superadmin','admin','agent') NOT NULL DEFAULT 'agent',
  `status` enum('active','inactive','pending') NOT NULL DEFAULT 'pending',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pending users table
CREATE TABLE `pending_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('admin','agent') NOT NULL DEFAULT 'agent',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stores table
CREATE TABLE `stores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `url` varchar(255) DEFAULT NULL,
  `admin_id` int(11) NOT NULL,
  `assigned_agent_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `admin_id` (`admin_id`),
  KEY `assigned_agent_id` (`assigned_agent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Orders table
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_number` varchar(50) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_email` varchar(100) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `customer_address` text,
  `product_name` varchar(255) NOT NULL,
  `product_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `quantity` int(11) NOT NULL DEFAULT 1,
  `store_id` int(11) DEFAULT NULL,
  `admin_id` int(11) NOT NULL,
  `assigned_agent_id` int(11) DEFAULT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled','failed') NOT NULL DEFAULT 'pending',
  `tracking_number` varchar(100) DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `store_id` (`store_id`),
  KEY `admin_id` (`admin_id`),
  KEY `assigned_agent_id` (`assigned_agent_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Screenshots table
CREATE TABLE `screenshots` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `uploaded_by` (`uploaded_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Call logs table
CREATE TABLE `call_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `agent_id` int(11) NOT NULL,
  `call_type` enum('inbound','outbound') NOT NULL,
  `call_status` enum('answered','no_answer','busy','failed') NOT NULL,
  `duration` int(11) DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `agent_id` (`agent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Activity logs table
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `description` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Foreign key constraints
ALTER TABLE `users`
  ADD CONSTRAINT `users_created_by_fk` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `stores`
  ADD CONSTRAINT `stores_admin_id_fk` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stores_assigned_agent_id_fk` FOREIGN KEY (`assigned_agent_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `orders`
  ADD CONSTRAINT `orders_store_id_fk` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_admin_id_fk` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_assigned_agent_id_fk` FOREIGN KEY (`assigned_agent_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `screenshots`
  ADD CONSTRAINT `screenshots_order_id_fk` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `screenshots_uploaded_by_fk` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `call_logs`
  ADD CONSTRAINT `call_logs_order_id_fk` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `call_logs_agent_id_fk` FOREIGN KEY (`agent_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

-- Insert default superadmin user
INSERT INTO `users` (`username`, `email`, `password`, `full_name`, `role`, `status`, `created_by`) VALUES
('superadmin', 'admin@orderdesk.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Administrator', 'superadmin', 'active', NULL);

-- Insert demo admin user
INSERT INTO `users` (`username`, `email`, `password`, `full_name`, `role`, `status`, `created_by`) VALUES
('admin', 'admin@demo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Demo Admin', 'admin', 'active', 1);

-- Insert demo agent users
INSERT INTO `users` (`username`, `email`, `password`, `full_name`, `role`, `status`, `created_by`) VALUES
('agent1', 'agent1@demo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Smith', 'agent', 'active', 2),
('agent2', 'agent2@demo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sarah Johnson', 'agent', 'active', 2);

-- Insert demo stores
INSERT INTO `stores` (`name`, `url`, `admin_id`, `assigned_agent_id`) VALUES
('Electronics Plus', 'https://electronicsplus.com', 2, 3),
('Fashion Hub', 'https://fashionhub.com', 2, 4),
('Home & Garden', 'https://homeandgarden.com', 2, 3);

-- Insert demo orders
INSERT INTO `orders` (`order_number`, `customer_name`, `customer_email`, `customer_phone`, `customer_address`, `product_name`, `product_price`, `quantity`, `store_id`, `admin_id`, `assigned_agent_id`, `status`, `tracking_number`) VALUES
('ORD-001', 'Michael Chen', 'michael@email.com', '+1-555-0101', '123 Main St, New York, NY 10001', 'Wireless Bluetooth Headphones', 79.99, 1, 1, 2, 3, 'delivered', 'TRK123456789'),
('ORD-002', 'Emily Davis', 'emily@email.com', '+1-555-0102', '456 Oak Ave, Los Angeles, CA 90210', 'Smart Watch Pro', 249.99, 1, 1, 2, 3, 'shipped', 'TRK987654321'),
('ORD-003', 'Robert Wilson', 'robert@email.com', '+1-555-0103', '789 Pine St, Chicago, IL 60601', 'Summer Dress Collection', 89.99, 2, 2, 2, 4, 'processing', NULL),
('ORD-004', 'Lisa Anderson', 'lisa@email.com', '+1-555-0104', '321 Elm St, Houston, TX 77001', 'Garden Tool Set', 149.99, 1, 3, 2, 3, 'pending', NULL),
('ORD-005', 'David Brown', 'david@email.com', '+1-555-0105', '654 Maple Dr, Phoenix, AZ 85001', 'Premium Coffee Maker', 199.99, 1, 1, 2, 4, 'delivered', 'TRK456789123');

-- Insert demo activity logs
INSERT INTO `activity_logs` (`user_id`, `action`, `description`, `ip_address`) VALUES
(2, 'login', 'Admin user logged in', '192.168.1.100'),
(3, 'order_updated', 'Updated order ORD-001 status to delivered', '192.168.1.101'),
(4, 'order_updated', 'Updated order ORD-003 status to processing', '192.168.1.102'),
(2, 'user_created', 'Created new agent user: agent1', '192.168.1.100'),
(3, 'screenshot_uploaded', 'Uploaded screenshot for order ORD-001', '192.168.1.101');

COMMIT;