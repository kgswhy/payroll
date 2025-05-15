-- Create database
CREATE DATABASE IF NOT EXISTS payroll_system;
USE payroll_system;

-- Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'employee', 'manager') NOT NULL,
    position VARCHAR(100),
    base_salary DECIMAL(10,2),
    manager_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Work Hours Table
CREATE TABLE work_hours (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    date DATE NOT NULL,
    time_in TIME NOT NULL,
    time_out TIME NOT NULL,
    status ENUM('pending', 'approved', 'corrected', 'rejected') DEFAULT 'pending',
    notes TEXT,
    reviewed_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Overtime Table
CREATE TABLE overtime (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    hours DECIMAL(5,2) NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    reviewed_by INT,
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Payroll Table
CREATE TABLE payroll (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    month INT NOT NULL,
    year INT NOT NULL,
    base_salary DECIMAL(10,2) NOT NULL,
    deductions DECIMAL(10,2) DEFAULT 0,
    net_salary DECIMAL(10,2) NOT NULL,
    status ENUM('processing', 'finalized', 'sent') DEFAULT 'processing',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create allowances table
CREATE TABLE `allowances` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `transport_allowance` decimal(15,2) NOT NULL DEFAULT 0,
    `meal_allowance` decimal(15,2) NOT NULL DEFAULT 0,
    `health_allowance` decimal(15,2) NOT NULL DEFAULT 0,
    `position_allowance` decimal(15,2) NOT NULL DEFAULT 0,
    `attendance_allowance` decimal(15,2) NOT NULL DEFAULT 0,
    `family_allowance` decimal(15,2) NOT NULL DEFAULT 0,
    `communication_allowance` decimal(15,2) NOT NULL DEFAULT 0,
    `education_allowance` decimal(15,2) NOT NULL DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `allowances_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Modify users table to remove single allowance column
ALTER TABLE users DROP COLUMN allowance;

-- Insert default admin user (password: admin123)
INSERT INTO users (name, email, password, role, position) VALUES 
('Admin User', 'admin@example.com', '$2y$10$ie2j8xvI3qVZQ9XHJq.A.eg95K0xyQTpGTNtLJrLXA1MoVWxEk3wW', 'admin', 'System Administrator'); 