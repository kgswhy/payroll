<?php
// Only start session if none is active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Redirect to appropriate dashboard based on role
if(is_logged_in()) {
    $role = $_SESSION['user_role'];
    
    switch($role) {
        case 'admin':
            redirect(BASE_URL . '/admin/index.php');
            break;
        case 'manager':
            redirect(BASE_URL . '/manager/index.php');
            break;
        case 'employee':
            redirect(BASE_URL . '/employee/index.php');
            break;
        default:
            // If role is unknown, log out
            $_SESSION = array();
            session_destroy();
    }
}

// Otherwise redirect to login page
redirect(BASE_URL . '/auth/login.php');
?> 