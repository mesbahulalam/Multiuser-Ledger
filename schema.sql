CREATE DATABASE IF NOT EXISTS user_management;
USE user_management;

-- Roles table
CREATE TABLE roles (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Permissions table
CREATE TABLE permissions (
    permission_id INT PRIMARY KEY AUTO_INCREMENT,
    permission_name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Role permissions mapping table
CREATE TABLE role_permissions (
    role_id INT,
    permission_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(role_id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(permission_id) ON DELETE CASCADE
);

-- Users table
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    first_name VARCHAR(50), --
    last_name VARCHAR(50), --
    phone_number VARCHAR(20), --
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    profile_picture TEXT, --
    dob DATE, --
    salary DECIMAL(10,2), --
    role_id INT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(role_id) ON DELETE SET NULL
);

-- User metadata table
CREATE TABLE user_metadata (
    metadata_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    meta_key VARCHAR(50) NOT NULL,
    meta_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY user_meta_key (user_id, meta_key)
);

-- attachments
CREATE TABLE attachments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    entry_by INT,
    attachment_name VARCHAR(255) NOT NULL,
    attachment_path VARCHAR(255) NOT NULL,
    attachment_type VARCHAR(50) NOT NULL,
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (entry_by) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Logs table
CREATE TABLE activity_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action_type VARCHAR(50) NOT NULL,
    action_description TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Incomes table
CREATE TABLE incomes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    entry_by INT,
    approved_by INT,  -- Adding approved_by field
    income_from VARCHAR(255) NOT NULL,
    category VARCHAR(100),
    amount DECIMAL(10,2) NOT NULL,
    method VARCHAR(50),
    bank VARCHAR(100),
    account_number VARCHAR(50) NULL,  -- Adding account_number field
    transaction_number VARCHAR(50) NULL,  -- Adding transaction_number field
    notes TEXT,
    attachment_id INT,
    status ENUM('pending', 'approved', 'denied', 'deleted') DEFAULT 'pending',
    date_realized TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  -- Adding date_realized field
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_modified TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    date_approved TIMESTAMP NULL,  -- Adding date_approved field
    FOREIGN KEY (entry_by) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (attachment_id) REFERENCES attachments(id) ON DELETE SET NULL
);

-- Expenses table
CREATE TABLE expenses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    entry_by INT,
    approved_by INT,
    expense_by VARCHAR(255) NOT NULL,
    category VARCHAR(100),
    purpose TEXT,
    amount DECIMAL(10,2) NOT NULL,
    method VARCHAR(50),
    bank VARCHAR(100),
    account_number VARCHAR(50) NULL,  -- Adding account_number field
    transaction_number VARCHAR(50) NULL,  -- Adding transaction_number field
    notes TEXT,
    attachment_id INT,
    status ENUM('pending', 'approved', 'denied', 'deleted') DEFAULT 'pending',
    date_realized TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  -- Adding date_realized field
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_modified TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    date_approved TIMESTAMP NULL,
    FOREIGN KEY (entry_by) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (attachment_id) REFERENCES attachments(id) ON DELETE SET NULL
);


CREATE TABLE salaries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    month VARCHAR(20) NOT NULL,
    basic_salary DECIMAL(10, 2) NOT NULL,
    allowances DECIMAL(10, 2) DEFAULT 0.00,
    deductions DECIMAL(10, 2) DEFAULT 0.00,
    net_salary DECIMAL(10, 2) NOT NULL,
    payment_details TEXT,
    approved_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Add index for salary lookups
ALTER TABLE salaries ADD INDEX idx_user_salary (user_id, created_at);

CREATE TABLE income_projections (
    id INT PRIMARY KEY AUTO_INCREMENT,
    entry_by INT,
    income_from VARCHAR(255) NOT NULL,
    category VARCHAR(100),
    amount DECIMAL(10,2) NOT NULL,
    notes TEXT,
    date_realized TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  -- Adding date_realized field
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_modified TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (entry_by) REFERENCES users(user_id) ON DELETE SET NULL
);

CREATE TABLE expense_projections (
    id INT PRIMARY KEY AUTO_INCREMENT,
    entry_by INT,
    expense_by VARCHAR(255) NOT NULL,
    category VARCHAR(100),
    purpose TEXT,
    amount DECIMAL(10,2) NOT NULL,
    notes TEXT,
    date_realized TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  -- Adding date_realized field
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_modified TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (entry_by) REFERENCES users(user_id) ON DELETE SET NULL
);

CREATE TABLE bw_vendors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vendor_name VARCHAR(255) NOT NULL UNIQUE,
    contact_person VARCHAR(100),
    phone_number VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE bw_bills (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vendor_id INT,
    bill_number VARCHAR(100) NOT NULL UNIQUE,
    bill_month VARCHAR(20) NOT NULL,
    bill_month_ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    amount DECIMAL(10,2) NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES bw_vendors(id) ON DELETE SET NULL
);

CREATE TABLE bw_metadata (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bill_id INT, -- Added to associate metadata with a specific bill
    type VARCHAR(255) NOT NULL, -- Changed to store the type of bandwidth
    quantity DECIMAL(10,2) NOT NULL, -- Added to store quantity
    unit_price DECIMAL(10,2) NOT NULL, -- Added to store unit price
    total DECIMAL(10,2) NOT NULL, -- Added to store the total for the item
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (bill_id) REFERENCES bw_bills(id) ON DELETE CASCADE
);


-- Insert dummy data into roles table
INSERT INTO roles (role_name) VALUES 
('Admin'),
('Manager'),
('User');

-- Insert dummy data into permissions table
INSERT INTO permissions (permission_name, description) VALUES 
('CREATE', 'Create permission'),
('READ', 'Read permission'),
('UPDATE', 'Update permission'),
('DELETE', 'Delete permission'),
('APPROVE', 'Approve permission');

-- Insert dummy data into role_permissions table
INSERT INTO role_permissions (role_id, permission_id) VALUES 
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(2, 1),
(2, 2),
(2, 3),
(2, 5),
(3, 1),
(3, 2);

-- Insert dummy data into users table
INSERT INTO users (username, email, password, password_hash, role_id) VALUES 
('admin', 'admin@example.com', 'adminpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 1),
('manager', 'manager@example.com', 'adminpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 2),
('user1', 'user1@example.com', 'adminpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3);