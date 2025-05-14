<?php
// Only start session if none is active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Check if already logged in
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
            redirect(BASE_URL . '/index.php');
    }
}

// Process login form
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    
    // Validate inputs
    if(empty($email) || empty($password)) {
        set_alert('danger', 'All fields are required');
    } else {
        // Get user data
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if($user && password_verify($password, $user['password'])) {
            // Set session data
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            
            // Redirect based on role
            switch($user['role']) {
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
                    redirect(BASE_URL . '/index.php');
            }
        } else {
            set_alert('danger', 'Invalid email or password');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <!-- Add Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <h2><?php echo APP_NAME; ?> Login</h2>
        
        <?php echo display_alert(); ?>
        
        <form method="post" action="">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" name="email" id="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" name="password" id="password" required>
            </div>
            <button type="submit" name="login">Login</button>
            <p class="signup-link">Don't have an account? <a href="<?php echo BASE_URL; ?>/auth/signup.php">Sign Up</a></p>
        </form>
    </div>
    
    <script src="<?php echo BASE_URL; ?>/assets/js/script.js"></script>
</body>
</html> 