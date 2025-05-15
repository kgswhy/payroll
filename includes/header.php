<?php
// Only start session if none is active
if (session_status() === PHP_SESSION_NONE) {
session_start();
}
require_once 'functions.php';

// Check if user is logged in (except for login page)
$current_file = basename($_SERVER['PHP_SELF']);
if ($current_file != 'login.php') {
    if (!is_logged_in() || !isset($_SESSION['user_role'])) {
        redirect(BASE_URL . '/auth/login.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Roboto+Mono:wght@400;500&display=swap"
        rel="stylesheet">
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/sidebar.css">
    <?php if (is_logged_in() && $current_file != 'login.php'): ?>
        <script src="<?php echo BASE_URL; ?>/assets/js/sidebar.js" defer></script>
    <?php endif; ?>
</head>

<body>
    <?php if (is_logged_in() && $current_file != 'login.php'): ?>
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
        
        <?php require_once 'sidebar.php'; ?>

        <div class="main-content" id="mainContent">
            <?php echo display_alert(); ?>
    <?php else: ?>
        <div class="auth-content">
            <?php echo display_alert(); ?>
    <?php endif; ?>
</body>
    
</html>