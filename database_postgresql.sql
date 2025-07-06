-- OrderDesk PostgreSQL Database Schema

-- Users table
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    role VARCHAR(20) NOT NULL CHECK (role IN ('super_admin', 'admin', 'agent')),
    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'active', 'inactive', 'suspended')),
    plan_id INTEGER,
    created_by INTEGER,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_status ON users(status);

-- Pricing plans table
CREATE TABLE pricing_plans (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    max_agents INTEGER NOT NULL,
    max_orders INTEGER NOT NULL,
    features TEXT,
    is_custom BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default pricing plans
INSERT INTO pricing_plans (name, price, max_agents, max_orders, features) VALUES
('Basic', 29.00, 3, 500, 'Basic Analytics,Email Support,CSV Export'),
('Professional', 59.00, 10, 2000, 'Advanced Analytics,Priority Support,All Export Formats'),
('Enterprise', 99.00, -1, -1, 'Premium Analytics,24/7 Support,Custom Features'),
('Custom', 0.00, -1, -1, 'Fully Customizable,White Label,API Access');

-- Stores table
CREATE TABLE stores (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    admin_id INTEGER NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Store agents assignment table
CREATE TABLE store_agents (
    id SERIAL PRIMARY KEY,
    store_id INTEGER NOT NULL,
    agent_id INTEGER NOT NULL,
    assigned_by INTEGER NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    FOREIGN KEY (agent_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(store_id, agent_id)
);

-- Admin agents assignment table
CREATE TABLE admin_agents (
    id SERIAL PRIMARY KEY,
    admin_id INTEGER NOT NULL,
    agent_id INTEGER NOT NULL,
    assigned_by INTEGER NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (agent_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(admin_id, agent_id)
);

-- Orders table
CREATE TABLE orders (
    id SERIAL PRIMARY KEY,
    order_id VARCHAR(100) NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    customer_city VARCHAR(50),
    product_name VARCHAR(200) NOT NULL,
    product_price DECIMAL(10,2),
    status VARCHAR(20) DEFAULT 'new' CHECK (status IN ('new', 'called', 'confirmed', 'in_transit', 'delivered', 'failed')),
    assigned_agent_id INTEGER,
    store_id INTEGER,
    admin_id INTEGER NOT NULL,
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_agent_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE SET NULL,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_orders_order_id ON orders(order_id);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_assigned_agent ON orders(assigned_agent_id);
CREATE INDEX idx_orders_store ON orders(store_id);

-- Order status history table
CREATE TABLE order_status_history (
    id SERIAL PRIMARY KEY,
    order_id INTEGER NOT NULL,
    status VARCHAR(20) NOT NULL CHECK (status IN ('new', 'called', 'confirmed', 'in_transit', 'delivered', 'failed')),
    changed_by INTEGER NOT NULL,
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Screenshots table
CREATE TABLE screenshots (
    id SERIAL PRIMARY KEY,
    order_id INTEGER NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255),
    uploaded_by INTEGER NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Payment records table
CREATE TABLE payment_records (
    id SERIAL PRIMARY KEY,
    admin_id INTEGER NOT NULL,
    plan_id INTEGER NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50),
    payment_date DATE,
    due_date DATE,
    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'paid', 'overdue')),
    invoice_number VARCHAR(50),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES pricing_plans(id) ON DELETE CASCADE
);

CREATE INDEX idx_payment_records_admin ON payment_records(admin_id);
CREATE INDEX idx_payment_records_status ON payment_records(status);

-- Audit logs table
CREATE TABLE audit_logs (
    id SERIAL PRIMARY KEY,
    user_id INTEGER,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX idx_audit_logs_user ON audit_logs(user_id);
CREATE INDEX idx_audit_logs_created_at ON audit_logs(created_at);

-- Notifications table
CREATE TABLE notifications (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(20) DEFAULT 'info' CHECK (type IN ('info', 'success', 'warning', 'error')),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_notifications_user_read ON notifications(user_id, is_read);

-- Insert default Super Admin
INSERT INTO users (username, email, password, full_name, role, status, created_at) VALUES
('superadmin', 'superadmin@orderdesk.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Administrator', 'super_admin', 'active', NOW());

-- Insert demo admin and agent (approved by default for demo)
INSERT INTO users (username, email, password, full_name, role, status, created_by, created_at) VALUES
('demoadmin', 'admin@demo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Demo Admin', 'admin', 'active', 1, NOW()),
('demoagent', 'agent@demo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Demo Agent', 'agent', 'active', 2, NOW());

-- Insert demo store
INSERT INTO stores (name, admin_id, description) VALUES
('Demo Store', 2, 'Demo store for testing purposes');

-- Assign demo agent to demo store
INSERT INTO store_agents (store_id, agent_id, assigned_by) VALUES
(1, 3, 2);

-- Assign demo agent to demo admin
INSERT INTO admin_agents (admin_id, agent_id, assigned_by) VALUES
(2, 3, 1);

-- Insert demo orders
INSERT INTO orders (order_id, customer_name, customer_phone, customer_city, product_name, product_price, status, store_id, admin_id) VALUES
('ORD-001', 'John Doe', '+1234567890', 'New York', 'iPhone 14 Pro', 999.99, 'new', 1, 2),
('ORD-002', 'Jane Smith', '+1234567891', 'Los Angeles', 'Samsung Galaxy S23', 799.99, 'called', 1, 2),
('ORD-003', 'Bob Johnson', '+1234567892', 'Chicago', 'iPad Air', 599.99, 'confirmed', 1, 2),
('ORD-004', 'Alice Brown', '+1234567893', 'Houston', 'MacBook Pro', 1299.99, 'in_transit', 1, 2),
('ORD-005', 'Charlie Wilson', '+1234567894', 'Phoenix', 'AirPods Pro', 249.99, 'delivered', 1, 2);

-- Update demo admin with a plan
UPDATE users SET plan_id = 2 WHERE id = 2;

-- Insert demo payment record
INSERT INTO payment_records (admin_id, plan_id, amount, payment_date, due_date, status, invoice_number) VALUES
(2, 2, 59.00, CURRENT_DATE, CURRENT_DATE + INTERVAL '30 days', 'paid', 'INV-2025-001');

-- Insert some audit logs
INSERT INTO audit_logs (user_id, action, details) VALUES
(1, 'login', 'Super admin logged in'),
(2, 'login', 'Demo admin logged in'),
(3, 'login', 'Demo agent logged in'),
(2, 'order_created', 'Created order ORD-001'),
(2, 'order_status_changed', 'Changed order ORD-002 status to called');