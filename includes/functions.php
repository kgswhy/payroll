<?php
require_once 'db_connect.php';

/**
 * Sanitize input data
 * 
 * @param string $data Data to be sanitized
 * @return string Sanitized data
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Check if a user is logged in
 * 
 * @return bool True if user is logged in, false otherwise
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Redirect to a specific URL
 * 
 * @param string $url URL to redirect to
 * @return void
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Get user information by ID
 * 
 * @param int $user_id User ID
 * @return array|bool User data as array or false if not found
 */
function get_user_by_id($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

/**
 * Get user information by email
 * 
 * @param string $email User email
 * @return array|bool User data as array or false if not found
 */
function get_user_by_email($email) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch();
}

/**
 * Get all users with a specific role
 * 
 * @param string $role User role
 * @return array Array of users with the specified role
 */
function get_users_by_role($role) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE role = ?");
    $stmt->execute([$role]);
    return $stmt->fetchAll();
}

/**
 * Get all employees assigned to a manager
 * 
 * @param int $manager_id Manager ID
 * @return array Array of employees
 */
function get_employees_by_manager($manager_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE manager_id = ? AND role = 'employee'");
    $stmt->execute([$manager_id]);
    return $stmt->fetchAll();
}

/**
 * Get work hours for an employee
 * 
 * @param int $employee_id Employee ID
 * @param string|null $status Filter by status (optional)
 * @return array Array of work hours
 */
function get_work_hours($employee_id, $status = null) {
    global $conn;
    
    if($status) {
        $stmt = $conn->prepare("SELECT * FROM work_hours WHERE employee_id = ? AND status = ? ORDER BY date DESC");
        $stmt->execute([$employee_id, $status]);
    } else {
        $stmt = $conn->prepare("SELECT * FROM work_hours WHERE employee_id = ? ORDER BY date DESC");
        $stmt->execute([$employee_id]);
    }
    
    return $stmt->fetchAll();
}

/**
 * Get all work hours pending review
 * 
 * @param int|null $manager_id Manager ID to filter by (optional)
 * @return array Array of pending work hours
 */
function get_pending_work_hours($manager_id = null) {
    global $conn;
    
    if($manager_id) {
        $stmt = $conn->prepare("
            SELECT wh.*, u.name as employee_name 
            FROM work_hours wh 
            JOIN users u ON wh.employee_id = u.id 
            WHERE u.manager_id = ? AND wh.status = 'pending' 
            ORDER BY wh.date DESC
        ");
        $stmt->execute([$manager_id]);
    } else {
        $stmt = $conn->prepare("
            SELECT wh.*, u.name as employee_name 
            FROM work_hours wh 
            JOIN users u ON wh.employee_id = u.id 
            WHERE wh.status = 'pending' 
            ORDER BY wh.date DESC
        ");
        $stmt->execute();
    }
    
    return $stmt->fetchAll();
}

/**
 * Calculate work hours
 * 
 * @param string $time_in Time in (format: HH:MM:SS)
 * @param string $time_out Time out (format: HH:MM:SS)
 * @return float Hours worked
 */
function calculate_hours($time_in, $time_out) {
    $in = strtotime($time_in);
    $out = strtotime($time_out);
    
    // Handle overnight shifts
    if($out < $in) {
        $out += 86400; // Add 24 hours (in seconds)
    }
    
    $diff_seconds = $out - $in;
    $hours = $diff_seconds / 3600; // Convert to hours
    
    return round($hours, 2);
}

/**
 * Set alert message in session
 * 
 * @param string $type Alert type (success, danger)
 * @param string $message Alert message
 * @return void
 */
function set_alert($type, $message) {
    $_SESSION['alert_type'] = $type;
    $_SESSION['alert_message'] = $message;
}

/**
 * Display alert message from session
 * 
 * @return string HTML for alert message
 */
function display_alert() {
    $html = '';
    
    if(isset($_SESSION['alert_type']) && isset($_SESSION['alert_message'])) {
        $type = $_SESSION['alert_type'];
        $message = $_SESSION['alert_message'];
        
        $html = "<div class=\"alert-{$type}\">{$message}</div>";
        
        // Clear the alert
        unset($_SESSION['alert_type']);
        unset($_SESSION['alert_message']);
    }
    
    return $html;
}

/**
 * Get payroll data for an employee
 * 
 * @param int $employee_id Employee ID
 * @return array Array of payroll data
 */
function get_employee_payroll($employee_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM payroll WHERE employee_id = ? ORDER BY year DESC, month DESC");
    $stmt->execute([$employee_id]);
    return $stmt->fetchAll();
}

/**
 * Format money value
 * 
 * @param float $amount Amount to format
 * @return string Formatted amount
 */
function format_money($amount) {
    return '$' . number_format($amount, 2, '.', ',');
}

/**
 * Format date
 * 
 * @param string $date Date to format (YYYY-MM-DD)
 * @return string Formatted date
 */
function format_date($date) {
    return date('F j, Y', strtotime($date));
}

/**
 * Get month name from number
 * 
 * @param int $month_number Month number (1-12)
 * @return string Month name
 */
function get_month_name($month_number) {
    $dateObj = DateTime::createFromFormat('!m', $month_number);
    return $dateObj->format('F');
}

/**
 * Check if user has specific role
 * 
 * @param string $role Role to check
 * @return bool True if user has the role, false otherwise
 */
function has_role($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}
?> 