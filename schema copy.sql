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
('user1', 'user1@example.com', 'adminpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user2', 'user2@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user3', 'user3@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user4', 'user4@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user5', 'user5@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user6', 'user6@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user7', 'user7@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user8', 'user8@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user9', 'user9@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user10', 'user10@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user11', 'user11@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user12', 'user12@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user13', 'user13@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user14', 'user14@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user15', 'user15@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user16', 'user16@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user17', 'user17@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user18', 'user18@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user19', 'user19@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user20', 'user20@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user21', 'user21@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user22', 'user22@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user23', 'user23@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user24', 'user24@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user25', 'user25@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user26', 'user26@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user27', 'user27@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user28', 'user28@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user29', 'user29@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user30', 'user30@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user31', 'user31@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user32', 'user32@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user33', 'user33@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user34', 'user34@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user35', 'user35@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user36', 'user36@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user37', 'user37@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user38', 'user38@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user39', 'user39@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user40', 'user40@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user41', 'user41@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user42', 'user42@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user43', 'user43@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user44', 'user44@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user45', 'user45@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user46', 'user46@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user47', 'user47@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user48', 'user48@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user49', 'user49@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user50', 'user50@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user51', 'user51@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user52', 'user52@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user53', 'user53@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user54', 'user54@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user55', 'user55@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user56', 'user56@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user57', 'user57@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user58', 'user58@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user59', 'user59@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user60', 'user60@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user61', 'user61@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user62', 'user62@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user63', 'user63@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user64', 'user64@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user65', 'user65@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user66', 'user66@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user67', 'user67@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user68', 'user68@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user69', 'user69@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user70', 'user70@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user71', 'user71@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user72', 'user72@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user73', 'user73@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user74', 'user74@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user75', 'user75@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user76', 'user76@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user77', 'user77@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user78', 'user78@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user79', 'user79@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user80', 'user80@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user81', 'user81@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user82', 'user82@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user83', 'user83@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user84', 'user84@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user85', 'user85@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user86', 'user86@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user87', 'user87@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user88', 'user88@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user89', 'user89@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user90', 'user90@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user91', 'user91@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user92', 'user92@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user93', 'user93@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user94', 'user94@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user95', 'user95@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user96', 'user96@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user97', 'user97@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user98', 'user98@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user99', 'user99@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3),
('user100', 'user100@example.com', 'userpass', '$2y$10$hVniY2FlF0HGTd.ZzbHhxuU1YXPUJyNlhpB8Grr9VZMXFteyEZSta', 3);

-- Insert dummy data into activity_logs table
INSERT INTO `activity_logs` (`user_id`,`action_type`, `action_description`, `ip_address`) VALUES
(1, 'LOGIN', 'Admin logged in', '192.168.1.1'),
(2, 'LOGIN', 'Manager logged in', '192.168.1.2'),
(3, 'LOGIN', 'User1 logged in', '192.168.1.3'),
(1, 'AUTH', 'User logged out', '127.0.0.1'),
(1, 'AUTH', 'User logged in', '127.0.0.1'),
(1, 'AUTH', 'User logged out', '127.0.0.1'),
(1, 'AUTH', 'User logged in', '127.0.0.1'),
(1, 'AUTH', 'User logged in', '127.0.0.1'),
(1, 'AUTH', 'User logged out', '127.0.0.1'),
(1, 'AUTH', 'admin logged in', '127.0.0.1'),
(44, 'UPDATE', 'admin updated 44 account', '127.0.0.1'),
(1, 'UPDATE', 'Updated role permissions', '127.0.0.1'),
(1, 'UPDATE', 'Updated role permissions', '127.0.0.1');



-- Insert dummy data into bw_vendors table
-- Insert dummy data into bw_vendors table
INSERT INTO bw_vendors (vendor_name, contact_person, phone_number, address) VALUES
('Bandwidth Provider A', 'John Smith', '+1-555-0123', '123 Network Street, Tech City, TC 12345'),
('Internet Solutions B', 'Mary Johnson', '+1-555-0124', '456 Fiber Avenue, Data Town, DT 67890'),
('Network Corp C', 'Robert Wilson', '+1-555-0125', '789 Bandwidth Road, Connect City, CC 34567'),
('Digital Links D', 'Sarah Brown', '+1-555-0126', '321 Internet Lane, Web Valley, WV 89012'),
('Cloud Connect E', 'Michael Davis', '+1-555-0127', '654 Cloud Street, Net City, NC 45678'),
('vendor 1', '', '', ''),
('vendor 2', '', '', ''),
('vendor 3', '', '', ''),
('vendor 4', '', '', ''),
('vendor 5', '', '', ''),
('IMF', 'Reptile', '', ''),
('Test Vendor', 'Chan mia', '', '');




-- Insert dummy data into incomes table
INSERT INTO incomes (entry_by, approved_by, income_from, category, amount, method, bank, account_number, transaction_number, notes, status, date_approved) VALUES
(3, 2, 'Client A', 'Consulting', 1500.00, 'Bank Transfer', 'Bank A', '123456789', 'TXN12345', 'Consulting services', 'approved', '2023-01-15 10:00:00'),
(4, 2, 'Client B', 'Sales', 2500.00, 'Credit Card', 'Bank B', '987654321', 'TXN54321', 'Product sales', 'approved', '2023-02-20 14:30:00'),
(5, 1, 'Client C', 'Freelance', 1200.00, 'PayPal', 'Bank C', '456789123', 'TXN67890', 'Freelance work', 'approved', '2023-03-10 09:45:00');

-- Insert dummy data into expenses table
INSERT INTO expenses (entry_by, approved_by, expense_by, category, purpose, amount, method, bank, account_number, transaction_number, notes, status, date_approved) VALUES
(3, 2, 'Vendor A', 'Office Supplies', 'Purchase of office supplies', 300.00, 'Bank Transfer', 'Bank A', '123456789', 'TXN12345', 'Office supplies purchase', 'approved', '2023-01-15 10:00:00'),
(4, 2, 'Vendor B', 'Travel', 'Business trip expenses', 800.00, 'Credit Card', 'Bank B', '987654321', 'TXN54321', 'Travel expenses', 'approved', '2023-02-20 14:30:00'),
(5, 1, 'Vendor C', 'Marketing', 'Marketing campaign', 1500.00, 'PayPal', 'Bank C', '456789123', 'TXN67890', 'Marketing campaign expenses', 'approved', '2023-03-10 09:45:00');



-- Insert random data into incomes table
INSERT INTO incomes (entry_by, approved_by, income_from, category, amount, method, bank, account_number, transaction_number, notes, status, date_approved)
SELECT 
    FLOOR(1 + (RAND() * 100)),  -- Random entry_by
    FLOOR(1 + (RAND() * 100)),  -- Random approved_by
    CONCAT('Client ', CHAR(FLOOR(65 + (RAND() * 26)))),  -- Random income_from
    CASE FLOOR(1 + (RAND() * 3))
        WHEN 1 THEN 'Consulting'
        WHEN 2 THEN 'Sales'
        ELSE 'Freelance'
    END,  -- Random category
    ROUND(RAND() * 5000, 2),  -- Random amount
    CASE FLOOR(1 + (RAND() * 3))
        WHEN 1 THEN 'Bank Transfer'
        WHEN 2 THEN 'Credit Card'
        ELSE 'PayPal'
    END,  -- Random method
    CONCAT('Bank ', CHAR(FLOOR(65 + (RAND() * 26)))),  -- Random bank
    LPAD(FLOOR(RAND() * 1000000000), 9, '0'),  -- Random account_number
    CONCAT('TXN', LPAD(FLOOR(RAND() * 100000), 5, '0')),  -- Random transaction_number
    'Random income note',  -- Random notes
    CASE FLOOR(1 + (RAND() * 3))
        WHEN 1 THEN 'approved'
        WHEN 2 THEN 'pending'
        ELSE 'denied'
    END,  -- Random status
    NOW()  -- Current timestamp for date_approved
FROM 
    (SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10 UNION SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION SELECT 15 UNION SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19 UNION SELECT 20 UNION SELECT 21 UNION SELECT 22 UNION SELECT 23 UNION SELECT 24 UNION SELECT 25 UNION SELECT 26 UNION SELECT 27 UNION SELECT 28 UNION SELECT 29 UNION SELECT 30 UNION SELECT 31 UNION SELECT 32 UNION SELECT 33 UNION SELECT 34 UNION SELECT 35 UNION SELECT 36 UNION SELECT 37 UNION SELECT 38 UNION SELECT 39 UNION SELECT 40 UNION SELECT 41 UNION SELECT 42 UNION SELECT 43 UNION SELECT 44 UNION SELECT 45 UNION SELECT 46 UNION SELECT 47 UNION SELECT 48 UNION SELECT 49 UNION SELECT 50 UNION SELECT 51 UNION SELECT 52 UNION SELECT 53 UNION SELECT 54 UNION SELECT 55 UNION SELECT 56 UNION SELECT 57 UNION SELECT 58 UNION SELECT 59 UNION SELECT 60 UNION SELECT 61 UNION SELECT 62 UNION SELECT 63 UNION SELECT 64 UNION SELECT 65 UNION SELECT 66 UNION SELECT 67 UNION SELECT 68 UNION SELECT 69 UNION SELECT 70 UNION SELECT 71 UNION SELECT 72 UNION SELECT 73 UNION SELECT 74 UNION SELECT 75 UNION SELECT 76 UNION SELECT 77 UNION SELECT 78 UNION SELECT 79 UNION SELECT 80 UNION SELECT 81 UNION SELECT 82 UNION SELECT 83 UNION SELECT 84 UNION SELECT 85 UNION SELECT 86 UNION SELECT 87 UNION SELECT 88 UNION SELECT 89 UNION SELECT 90 UNION SELECT 91 UNION SELECT 92 UNION SELECT 93 UNION SELECT 94 UNION SELECT 95 UNION SELECT 96 UNION SELECT 97 UNION SELECT 98 UNION SELECT 99 UNION SELECT 100) AS tmp;

-- Insert random data into expenses table
INSERT INTO expenses (entry_by, approved_by, expense_by, category, purpose, amount, method, bank, account_number, transaction_number, notes, status, date_approved)
SELECT 
    FLOOR(1 + (RAND() * 100)),  -- Random entry_by
    FLOOR(1 + (RAND() * 100)),  -- Random approved_by
    CONCAT('Vendor ', CHAR(FLOOR(65 + (RAND() * 26)))),  -- Random expense_by
    CASE FLOOR(1 + (RAND() * 3))
        WHEN 1 THEN 'Office Supplies'
        WHEN 2 THEN 'Travel'
        ELSE 'Marketing'
    END,  -- Random category
    'Random purpose',  -- Random purpose
    ROUND(RAND() * 5000, 2),  -- Random amount
    CASE FLOOR(1 + (RAND() * 3))
        WHEN 1 THEN 'Bank Transfer'
        WHEN 2 THEN 'Credit Card'
        ELSE 'PayPal'
    END,  -- Random method
    CONCAT('Bank ', CHAR(FLOOR(65 + (RAND() * 26)))),  -- Random bank
    LPAD(FLOOR(RAND() * 1000000000), 9, '0'),  -- Random account_number
    CONCAT('TXN', LPAD(FLOOR(RAND() * 100000), 5, '0')),  -- Random transaction_number
    'Random expense note',  -- Random notes
    CASE FLOOR(1 + (RAND() * 3))
        WHEN 1 THEN 'approved'
        WHEN 2 THEN 'pending'
        ELSE 'denied'
    END,  -- Random status
    NOW()  -- Current timestamp for date_approved
FROM 
    (SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10 UNION SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION SELECT 15 UNION SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19 UNION SELECT 20 UNION SELECT 21 UNION SELECT 22 UNION SELECT 23 UNION SELECT 24 UNION SELECT 25 UNION SELECT 26 UNION SELECT 27 UNION SELECT 28 UNION SELECT 29 UNION SELECT 30 UNION SELECT 31 UNION SELECT 32 UNION SELECT 33 UNION SELECT 34 UNION SELECT 35 UNION SELECT 36 UNION SELECT 37 UNION SELECT 38 UNION SELECT 39 UNION SELECT 40 UNION SELECT 41 UNION SELECT 42 UNION SELECT 43 UNION SELECT 44 UNION SELECT 45 UNION SELECT 46 UNION SELECT 47 UNION SELECT 48 UNION SELECT 49 UNION SELECT 50 UNION SELECT 51 UNION SELECT 52 UNION SELECT 53 UNION SELECT 54 UNION SELECT 55 UNION SELECT 56 UNION SELECT 57 UNION SELECT 58 UNION SELECT 59 UNION SELECT 60 UNION SELECT 61 UNION SELECT 62 UNION SELECT 63 UNION SELECT 64 UNION SELECT 65 UNION SELECT 66 UNION SELECT 67 UNION SELECT 68 UNION SELECT 69 UNION SELECT 70 UNION SELECT 71 UNION SELECT 72 UNION SELECT 73 UNION SELECT 74 UNION SELECT 75 UNION SELECT 76 UNION SELECT 77 UNION SELECT 78 UNION SELECT 79 UNION SELECT 80 UNION SELECT 81 UNION SELECT 82 UNION SELECT 83 UNION SELECT 84 UNION SELECT 85 UNION SELECT 86 UNION SELECT 87 UNION SELECT 88 UNION SELECT 89 UNION SELECT 90 UNION SELECT 91 UNION SELECT 92 UNION SELECT 93 UNION SELECT 94 UNION SELECT 95 UNION SELECT 96 UNION SELECT 97 UNION SELECT 98 UNION SELECT 99 UNION SELECT 100) AS tmp;



-- Insert random data into income_projections table
INSERT INTO income_projections (entry_by, income_from, category, amount, notes)
SELECT 
    FLOOR(1 + (RAND() * 100)),  -- Random entry_by
    CONCAT('Client ', CHAR(FLOOR(65 + (RAND() * 26)))),  -- Random income_from
    CASE FLOOR(1 + (RAND() * 3))
        WHEN 1 THEN 'Consulting'
        WHEN 2 THEN 'Sales'
        ELSE 'Freelance'
    END,  -- Random category
    ROUND(RAND() * 5000, 2),  -- Random amount
    'Random income note'  -- Random notes
FROM
    (SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10 UNION SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION SELECT 15 UNION SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19 UNION SELECT 20 UNION SELECT 21 UNION SELECT 22 UNION SELECT 23 UNION SELECT 24 UNION SELECT 25 UNION SELECT 26 UNION SELECT 27 UNION SELECT 28 UNION SELECT 29 UNION SELECT 30) AS tmp;

-- Insert random data into expense_projections table
INSERT INTO expense_projections (entry_by, expense_by, category, purpose, amount, notes)
SELECT 
    FLOOR(1 + (RAND() * 100)),  -- Random entry_by
    CONCAT('Vendor ', CHAR(FLOOR(65 + (RAND() * 26)))),  -- Random expense_by
    CASE FLOOR(1 + (RAND() * 3))
        WHEN 1 THEN 'Office Supplies'
        WHEN 2 THEN 'Travel'
        ELSE 'Marketing'
    END,  -- Random category
    'Random purpose',  -- Random purpose
    ROUND(RAND() * 5000, 2),  -- Random amount
    'Random expense note'  -- Random notes
FROM
    (SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10 UNION SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION SELECT 15 UNION SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19 UNION SELECT 20 UNION SELECT 21 UNION SELECT 22 UNION SELECT 23 UNION SELECT 24 UNION SELECT 25 UNION SELECT 26 UNION SELECT 27 UNION SELECT 28 UNION SELECT 29 UNION SELECT 30) AS tmp;