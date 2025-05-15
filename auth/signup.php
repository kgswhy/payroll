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

// Process signup form
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    $role = sanitize_input($_POST['role']);
    $position = sanitize_input($_POST['position']);
    
    // Validate inputs
    if(empty($name) || empty($email) || empty($password) || empty($password_confirm) || empty($role) || empty($position)) {
        set_alert('danger', 'All fields are required');
    } elseif($password !== $password_confirm) {
        set_alert('danger', 'Passwords do not match');
    } elseif(strlen($password) < 6) {
        set_alert('danger', 'Password must be at least 6 characters long');
    } else {
        // Check if email already exists
        $user = get_user_by_email($email);
        
        if($user) {
            set_alert('danger', 'Email already registered');
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            try {
                // Default values for salary and allowance
                $base_salary = 0;
                $allowance = 0;
                
                // Insert user
                $stmt = $conn->prepare("
                    INSERT INTO users (name, email, password, role, position, base_salary, allowance) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $result = $stmt->execute([$name, $email, $hashed_password, $role, $position, $base_salary, $allowance]);
                
                if($result) {
                    set_alert('success', 'Registration successful! Please wait for admin approval before you can log in.');
                    redirect(BASE_URL . '/auth/login.php');
                } else {
                    set_alert('danger', 'Registration failed');
                }
            } catch (PDOException $e) {
                set_alert('danger', 'Error: ' . $e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <!-- Add Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
    <div class="signup-container">
        <h2><?php echo APP_NAME; ?> Sign Up</h2>
        
        <?php echo display_alert(); ?>
        
        <form method="post" action="">
            <div class="form-group">
                <label for="name">Full Name:</label>
                <input type="text" name="name" id="name" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" name="email" id="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" name="password" id="password" required>
            </div>
            <div class="form-group">
                <label for="password_confirm">Confirm Password:</label>
                <input type="password" name="password_confirm" id="password_confirm" required>
            </div>
            <div class="form-group">
                <label for="role">Role:</label>
                <select name="role" id="role" required>
                    <option value="employee" <?php echo (isset($_POST['role']) && $_POST['role'] == 'employee') ? 'selected' : ''; ?>>Employee</option>
                    <option value="manager" <?php echo (isset($_POST['role']) && $_POST['role'] == 'manager') ? 'selected' : ''; ?>>Manager</option>
                </select>
            </div>
            <div class="form-group">
                <label for="position">Position:</label>
                <input type="text" name="position" id="position" required value="<?php echo isset($_POST['position']) ? htmlspecialchars($_POST['position']) : ''; ?>">
            </div>
            <button type="submit" name="signup">Sign Up</button>
            <p class="login-link">Already have an account? <a href="<?php echo BASE_URL; ?>/auth/login.php">Login</a></p>
        </form>
    </div>
    
    <script src="<?php echo BASE_URL; ?>/assets/js/script.js"></script>
</body>
</html> 