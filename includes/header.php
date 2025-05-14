<?php
session_start();
require_once 'functions.php';

// Check if user is logged in (except for login page)
$current_file = basename($_SERVER['PHP_SELF']);
if($current_file != 'login.php' && !is_logged_in()) {
    redirect('../auth/login.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <!-- Add Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
    <?php if(is_logged_in() && $current_file != 'login.php'): ?>
        <button class="sidebar-toggle">
            <i class="fas fa-bars"></i>
        </button>
        
        <div class="sidebar">
            <ul>
                <?php if(has_role('admin')): ?>
                    <li><a href="<?php echo BASE_URL; ?>/admin/index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/admin/manage_employees.php"><i class="fas fa-users"></i> Employees</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/admin/verify_workhours.php"><i class="fas fa-clock"></i> Work Hours</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/admin/process_payroll.php"><i class="fas fa-money-bill-wave"></i> Payroll</a></li>
                <?php elseif(has_role('manager')): ?>
                    <li><a href="<?php echo BASE_URL; ?>/manager/index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/manager/review_workhours.php"><i class="fas fa-clock"></i> Review Hours</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/manager/approve_overtime.php"><i class="fas fa-plus-circle"></i> Overtime</a></li>
                <?php elseif(has_role('employee')): ?>
                    <li><a href="<?php echo BASE_URL; ?>/employee/index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/employee/workhours.php"><i class="fas fa-clock"></i> My Hours</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/employee/salary.php"><i class="fas fa-money-bill"></i> My Salary</a></li>
                <?php endif; ?>
                <li><a href="<?php echo BASE_URL; ?>/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <header>
            <nav>
                <div class="logo">
                    <h1><?php echo APP_NAME; ?></h1>
                </div>
                <div class="user-info">
                    <span>Welcome, <?php echo $_SESSION['user_name']; ?> (<?php echo ucfirst($_SESSION['user_role']); ?>)</span>
                </div>
            </nav>
        </header>
    <?php endif; ?>
    
    <div class="<?php echo (is_logged_in() && $current_file != 'login.php') ? 'main-content' : ''; ?>">
        <?php echo display_alert(); ?> 