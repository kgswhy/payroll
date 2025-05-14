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
    allowance DECIMAL(10,2),
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

-- Payroll Table
CREATE TABLE payroll (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    month INT NOT NULL,
    year INT NOT NULL,
    base_salary DECIMAL(10,2) NOT NULL,
    allowance DECIMAL(10,2) NOT NULL,
    deductions DECIMAL(10,2) DEFAULT 0,
    net_salary DECIMAL(10,2) NOT NULL,
    status ENUM('processing', 'finalized', 'sent') DEFAULT 'processing',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin user (password: admin123)
INSERT INTO users (name, email, password, role, position) VALUES 
('Admin User', 'admin@example.com', '$2y$10$Qx.ZQiRoG9aNsZggpSp6R.C5J.CwGZPbPMHzHzpY5.TUavAWJrWWe', 'admin', 'System Administrator'); 