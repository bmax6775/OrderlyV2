-- OrderDesk MySQL Database Schema for NameCheap Shared Hosting
-- Compatible with MySQL 5.7+ and MariaDB 10.3+

-- Create database (run this separately if needed)
-- CREATE DATABASE orderdesk_db;
-- USE orderdesk_db;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    role ENUM('super_admin', 'admin', 'agent') NOT NULL,
    status ENUM('pending', 'active', 'inactive', 'suspended') NOT NULL DEFAULT 'pending',
    created_by INT NULL,
    last_login TIMESTAMP NULL,
    platform VARCHAR(50) DEFAULT 'web',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Stores table
CREATE TABLE stores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    url VARCHAR(255),
    platform VARCHAR(50) DEFAULT 'shopify',
    api_key VARCHAR(255),
    api_secret VARCHAR(255),
    webhook_url VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    admin_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Orders table
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id VARCHAR(100) UNIQUE NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_phone VARCHAR(20),
    customer_email VARCHAR(100),
    customer_address TEXT,
    customer_city VARCHAR(50),
    product_name VARCHAR(200) NOT NULL,
    product_price DECIMAL(10,2) NOT NULL,
    quantity INT DEFAULT 1,
    total_amount DECIMAL(10,2) GENERATED ALWAYS AS (product_price * quantity) STORED,
    status ENUM('new', 'called', 'confirmed', 'in_transit', 'delivered', 'failed') NOT NULL DEFAULT 'new',
    assigned_agent_id INT NULL,
    store_id INT NOT NULL,
    admin_id INT NOT NULL,
    remarks TEXT,
    screenshot_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_agent_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Order status history
CREATE TABLE order_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    old_status ENUM('new', 'called', 'confirmed', 'in_transit', 'delivered', 'failed'),
    new_status ENUM('new', 'called', 'confirmed', 'in_transit', 'delivered', 'failed') NOT NULL,
    changed_by INT NOT NULL,
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Audit logs
CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Branding settings
CREATE TABLE branding_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) UNIQUE NOT NULL,
    setting_value TEXT,
    updated_by INT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Payment records
CREATE TABLE payment_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50),
    transaction_id VARCHAR(100),
    status ENUM('pending', 'paid', 'overdue') NOT NULL DEFAULT 'pending',
    due_date DATE,
    paid_date DATE NULL,
    admin_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Courier integrations
CREATE TABLE courier_integrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    api_url VARCHAR(255) NOT NULL,
    api_token VARCHAR(255),
    api_secret VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    last_sync TIMESTAMP NULL,
    sync_frequency INT DEFAULT 60,
    admin_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Courier orders
CREATE TABLE courier_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    courier_id INT NOT NULL,
    tracking_number VARCHAR(100),
    courier_status VARCHAR(50),
    estimated_delivery DATE,
    actual_delivery DATE NULL,
    assigned_agent_id INT NULL,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (courier_id) REFERENCES courier_integrations(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_agent_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Courier sync logs
CREATE TABLE courier_sync_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    courier_id INT NOT NULL,
    sync_type ENUM('manual', 'auto') NOT NULL,
    status ENUM('success', 'failed', 'partial') NOT NULL,
    records_processed INT DEFAULT 0,
    errors_count INT DEFAULT 0,
    sync_duration DECIMAL(5,2),
    error_details TEXT,
    initiated_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (courier_id) REFERENCES courier_integrations(id) ON DELETE CASCADE,
    FOREIGN KEY (initiated_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert default super admin user
INSERT INTO users (username, email, password, full_name, role, status, created_at) VALUES
('superadmin', 'superadmin@orderdesk.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Administrator', 'super_admin', 'active', NOW()),
('demoadmin', 'admin@demo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Demo Admin', 'admin', 'active', NOW()),
('demoagent', 'agent@demo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Demo Agent', 'agent', 'active', NOW());

-- Insert default stores
INSERT INTO stores (name, url, platform, admin_id, created_at) VALUES
('Demo Store 1', 'https://demo-store-1.myshopify.com', 'shopify', 2, NOW()),
('Demo Store 2', 'https://demo-store-2.myshopify.com', 'shopify', 2, NOW()),
('Test Store', 'https://test-store.myshopify.com', 'woocommerce', 2, NOW());

-- Insert sample orders
INSERT INTO orders (order_id, customer_name, customer_phone, customer_city, product_name, product_price, status, assigned_agent_id, store_id, admin_id, remarks, created_at) VALUES
('ORD001', 'Ahmad Ali', '+92300123456', 'Karachi', 'Wireless Headphones', 2500.00, 'new', 3, 1, 2, 'Customer preferred evening delivery', NOW()),
('ORD002', 'Fatima Khan', '+92301234567', 'Lahore', 'Smart Watch', 5000.00, 'confirmed', 3, 1, 2, 'Payment confirmed via bank transfer', NOW()),
('ORD003', 'Hassan Ahmed', '+92302345678', 'Islamabad', 'Bluetooth Speaker', 1800.00, 'delivered', 3, 2, 2, 'Delivered successfully', NOW()),
('ORD004', 'Ayesha Malik', '+92303456789', 'Faisalabad', 'Phone Case', 800.00, 'new', NULL, 1, 2, 'Pending agent assignment', NOW()),
('ORD005', 'Usman Sheikh', '+92304567890', 'Rawalpindi', 'Power Bank', 1200.00, 'in_transit', 3, 2, 2, 'Out for delivery', NOW()),
('ORD006', 'Sara Hussain', '+92305678901', 'Multan', 'Gaming Mouse', 1500.00, 'called', 3, 1, 2, 'Customer contacted successfully', NOW()),
('ORD007', 'Ali Raza', '+92306789012', 'Peshawar', 'Keyboard', 3200.00, 'delivered', 3, 2, 2, 'Customer very satisfied', NOW()),
('ORD008', 'Zainab Ahmed', '+92307890123', 'Quetta', 'Monitor Stand', 1100.00, 'failed', NULL, 1, 2, 'Customer not reachable', NOW());

-- Insert sample audit logs
INSERT INTO audit_logs (user_id, action, details, created_at) VALUES
(1, 'user_login', 'Super Admin logged in', NOW()),
(2, 'order_created', 'New order ORD001 created', NOW()),
(3, 'order_updated', 'Order ORD002 status changed to confirmed', NOW()),
(2, 'store_created', 'Demo Store 1 created', NOW()),
(1, 'user_approved', 'Demo Admin approved', NOW()),
(3, 'order_delivered', 'Order ORD003 marked as delivered', NOW());

-- Insert default branding settings
INSERT INTO branding_settings (setting_key, setting_value, updated_by) VALUES
('app_name', 'Orderlyy', 1),
('logo_url', '', 1),
('primary_color', '#ffc500', 1),
('secondary_color', '#000000', 1),
('accent_color', '#333333', 1),
('background_color', '#ffffff', 1),
('card_background', '#ffffff', 1),
('text_color', '#212529', 1),
('success_color', '#28a745', 1),
('warning_color', '#ffc107', 1),
('danger_color', '#dc3545', 1),
('info_color', '#17a2b8', 1),
('tagline', 'The go-to orderly solution for ecommerce teams', 1),
('footer_text', 'Powered by Orderlyy - Professional Order Management', 1);

-- Insert sample courier integrations
INSERT INTO courier_integrations (name, api_url, status, admin_id, created_at) VALUES
('PostEx', 'https://api.postex.pk/v1/', 'active', 2, NOW()),
('Leopards Courier', 'https://api.leopardscourier.com/v1/', 'active', 2, NOW()),
('TCS', 'https://api.tcs.com.pk/v1/', 'inactive', 2, NOW()),
('M&P Express', 'https://api.callcourier.com.pk/v1/', 'active', 2, NOW()),
('Trax Logistics', 'https://api.traxlogistics.com/v1/', 'active', 2, NOW()),
('BlueEx', 'https://api.blue-ex.com/v1/', 'active', 2, NOW());

-- Create indexes for better performance
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_assigned_agent ON orders(assigned_agent_id);
CREATE INDEX idx_orders_store ON orders(store_id);
CREATE INDEX idx_orders_admin ON orders(admin_id);
CREATE INDEX idx_orders_created_at ON orders(created_at);
CREATE INDEX idx_audit_logs_user ON audit_logs(user_id);
CREATE INDEX idx_audit_logs_created_at ON audit_logs(created_at);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_status ON users(status);

-- Note: Default password for all demo accounts is 'password'
-- Passwords are hashed using PHP's password_hash() function with PASSWORD_DEFAULT
-- Make sure to change these passwords after installation!