<?php
// Only start session if none is active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Check if already logged in
if (is_logged_in()) {
    $role = $_SESSION['user_role'];
    switch ($role) {
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];

    // Validate inputs
    if (empty($email) || empty($password)) {
        set_alert('danger', 'All fields are required');
    } else {
        // Get user data
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Set session data
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];

            // Redirect based on role
            switch ($user['role']) {
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
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f7f9fc;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            font-family: 'Roboto', sans-serif;
        }

        .login-page {
            display: flex;
            width: 900px;
            max-width: 100%;
            background-color: #fff;
            box-shadow: 0 0 40px rgba(0, 0, 0, 0.05);
            border-radius: 12px;
            overflow: hidden;
        }

        .login-illustration {
            flex: 1;
            background-color: #1976d2;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .login-illustration h1 {
            font-size: 2.5rem;
            font-weight: 300;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }

        .login-illustration p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 30px;
            position: relative;
            z-index: 1;
        }

        .illustration-shapes {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            overflow: hidden;
        }

        .shape {
            position: absolute;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .shape-1 {
            width: 150px;
            height: 150px;
            bottom: -50px;
            left: -50px;
        }

        .shape-2 {
            width: 100px;
            height: 100px;
            top: 50px;
            right: 30px;
        }

        .shape-3 {
            width: 200px;
            height: 200px;
            bottom: 50px;
            right: -80px;
        }

        .login-form-container {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-form-container h2 {
            font-weight: 400;
            color: #333;
            margin-bottom: 30px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: #666;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #eaeaea;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
            background-color: #f9f9f9;
        }

        .form-control:focus {
            border-color: #1976d2;
            outline: none;
            background-color: #fff;
        }

        .login-btn {
            background-color: #1976d2;
            color: white;
            border: none;
            padding: 14px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.3s;
            margin-top: 12px;
        }

        .login-btn:hover {
            background-color: #1565c0;
        }

        .role-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 24px;
        }

        .role-option {
            flex: 1;
            text-align: center;
            padding: 12px;
            border: 2px solid #eaeaea;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
            background-color: #f9f9f9;
        }

        .role-option i {
            font-size: 24px;
            margin-bottom: 8px;
            color: #666;
        }

        .role-option.selected {
            border-color: #1976d2;
            background-color: rgba(25, 118, 210, 0.05);
        }

        .role-option.selected i {
            color: #1976d2;
        }

        .role-option:hover {
            border-color: #ccc;
            background-color: #f5f5f5;
        }

        .hidden-select {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .alert-danger {
            background-color: #ffebee;
            color: #d32f2f;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .login-page {
                flex-direction: column;
                width: 100%;
                border-radius: 0;
                height: 100vh;
            }

            .login-illustration {
                padding: 20px;
                text-align: center;
            }

            .login-form-container {
                padding: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="login-page">
        <div class="login-illustration">
            <div class="illustration-shapes">
                <div class="shape shape-1"></div>
                <div class="shape shape-2"></div>
                <div class="shape shape-3"></div>
            </div>
            <h1><?php echo APP_NAME; ?></h1>
            <p>Sistem Penggajian Modern untuk Perusahaan Anda</p>
        </div>
        <div class="login-form-container">
            <h2>Login to Your Account</h2>

            <?php echo display_alert(); ?>

            <form method="post" action="" id="loginForm">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" name="email" id="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Select Your Role</label>
                    <div class="role-selector">
                        <div class="role-option" data-role="admin">
                            <i class="fas fa-user-shield"></i>
                            <div>Admin</div>
                        </div>
                        <div class="role-option" data-role="manager">
                            <i class="fas fa-user-tie"></i>
                            <div>Manager</div>
                        </div>
                        <div class="role-option" data-role="employee">
                            <i class="fas fa-user"></i>
                            <div>Employee</div>
                        </div>
                    </div>
                    <select name="role" id="role" class="hidden-select" required>
                        <option value="admin">Admin</option>
                        <option value="employee">Employee</option>
                        <option value="manager">Manager</option>
                    </select>
                </div>

                <button type="submit" name="login" class="login-btn">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const roleOptions = document.querySelectorAll('.role-option');
            const roleSelect = document.getElementById('role');

            // Set first option as selected by default
            if (roleOptions.length > 0) {
                roleOptions[0].classList.add('selected');
                roleSelect.value = roleOptions[0].getAttribute('data-role');
            }

            roleOptions.forEach(option => {
                option.addEventListener('click', function () {
                    // Remove selected class from all options
                    roleOptions.forEach(opt => opt.classList.remove('selected'));

                    // Add selected class to clicked option
                    this.classList.add('selected');

                    // Update hidden select value
                    roleSelect.value = this.getAttribute('data-role');
                });
            });
        });
    </script>
</body>

</html>