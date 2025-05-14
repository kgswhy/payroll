<?php
// Only start session if none is active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if user is logged in
if(!is_logged_in()) {
    redirect(BASE_URL . '/auth/login.php');
}

// Check session timeout
if(isset($_SESSION['last_activity'])) {
    $inactive_time = time() - $_SESSION['last_activity'];
    
    if($inactive_time >= SESSION_TIMEOUT) {
        // Session expired, log out
        $_SESSION = array();
        session_destroy();
        
        // Redirect to login with message
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        set_alert('danger', 'Your session has expired. Please log in again.');
        redirect(BASE_URL . '/auth/login.php');
    }
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Check if user has access to the current page based on role
$current_path = $_SERVER['PHP_SELF'];

// Admin pages
if(strpos($current_path, '/admin/') !== false && !has_role('admin')) {
    set_alert('danger', 'You do not have permission to access this page.');
    redirect(BASE_URL . '/auth/login.php');
}

// Manager pages
if(strpos($current_path, '/manager/') !== false && !has_role('manager')) {
    set_alert('danger', 'You do not have permission to access this page.');
    redirect(BASE_URL . '/auth/login.php');
}

// Employee pages
if(strpos($current_path, '/employee/') !== false && !has_role('employee')) {
    set_alert('danger', 'You do not have permission to access this page.');
    redirect(BASE_URL . '/auth/login.php');
}
?> 