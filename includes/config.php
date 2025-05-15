<?php
// Application configuration
define('APP_NAME', 'Payroll System');
define('APP_VERSION', '1.0.0');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'root'); // Default for XAMPP/MAMP
define('DB_NAME', 'payroll_system');

// Directory settings
define('ROOT_PATH', dirname(dirname(__FILE__)));
define('BASE_URL', 'http://localhost:8888/payroll');

// Session timeout settings (in seconds)
define('SESSION_TIMEOUT', 1800); // 30 minutes

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>