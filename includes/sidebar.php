<?php
$current_page = basename($_SERVER['PHP_SELF']);

// Ensure user is logged in and has a role
if (!is_logged_in() || !isset($_SESSION['user_role'])) {
    redirect(BASE_URL . '/auth/login.php');
}

$current_role = $_SESSION['user_role'];
?>
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="user-profile">
            <div class="profile-image">
                <i class="fas fa-user-circle"></i>
            </div>
            <div class="profile-info">
                <h4><?php echo $_SESSION['user_name']; ?></h4>
                <span class="role-badge"><?php echo ucfirst($_SESSION['user_role']); ?></span>
            </div>
        </div>
    </div>
    
    <div class="sidebar-nav">
        <ul>
            <?php if ($current_role === 'admin'): ?>
                <li class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
                    <a href="<?php echo BASE_URL; ?>/admin/index.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                
                <li class="<?php echo $current_page == 'manage_employees.php' ? 'active' : ''; ?>">
                    <a href="<?php echo BASE_URL; ?>/admin/manage_employees.php">
                        <i class="fas fa-users"></i>
                        <span>Kelola Karyawan</span>
                    </a>
                </li>
                
                <li class="<?php echo $current_page == 'verify_workhours.php' ? 'active' : ''; ?>">
                    <a href="<?php echo BASE_URL; ?>/admin/verify_workhours.php">
                        <i class="fas fa-clock"></i>
                        <span>Verifikasi Jam Kerja</span>
                    </a>
                </li>
                
                <li class="<?php echo $current_page == 'salary_hours_analysis.php' ? 'active' : ''; ?>">
                    <a href="<?php echo BASE_URL; ?>/admin/salary_hours_analysis.php">
                        <i class="fas fa-chart-line"></i>
                        <span>Analisis Gaji & Jam</span>
                    </a>
                </li>
                
                <li class="<?php echo $current_page == 'close_period.php' ? 'active' : ''; ?>">
                    <a href="<?php echo BASE_URL; ?>/admin/close_period.php">
                        <i class="fas fa-calendar-times"></i>
                        <span>Tutup Periode</span>
                    </a>
                </li>
                
                <li class="<?php echo $current_page == 'process_payroll.php' ? 'active' : ''; ?>">
                    <a href="<?php echo BASE_URL; ?>/admin/process_payroll.php">
                        <i class="fas fa-money-bill-wave"></i>
                        <span>Proses Gaji</span>
                    </a>
                </li>
                
            <?php elseif ($current_role === 'manager'): ?>
                <li class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
                    <a href="<?php echo BASE_URL; ?>/manager/index.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                
                <li class="<?php echo $current_page == 'review_workhours.php' ? 'active' : ''; ?>">
                    <a href="<?php echo BASE_URL; ?>/manager/review_workhours.php">
                        <i class="fas fa-clock"></i>
                        <span>Review Jam Kerja</span>
                    </a>
                </li>
                
                <li class="<?php echo $current_page == 'approve_overtime.php' ? 'active' : ''; ?>">
                    <a href="<?php echo BASE_URL; ?>/manager/approve_overtime.php">
                        <i class="fas fa-plus-circle"></i>
                        <span>Lembur</span>
                    </a>
                </li>
                
            <?php elseif ($current_role === 'employee'): ?>
                <li class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
                    <a href="<?php echo BASE_URL; ?>/employee/index.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                
                <li class="<?php echo $current_page == 'workhours.php' ? 'active' : ''; ?>">
                    <a href="<?php echo BASE_URL; ?>/employee/workhours.php">
                        <i class="fas fa-clock"></i>
                        <span>Jam Kerja Saya</span>
                    </a>
                </li>
                
                <li class="<?php echo $current_page == 'overtime.php' ? 'active' : ''; ?>">
                    <a href="<?php echo BASE_URL; ?>/employee/overtime.php">
                        <i class="fas fa-clock"></i>
                        <span>Jam Lembur Saya</span>
                    </a>
                </li>
                
                <li class="<?php echo $current_page == 'salary.php' ? 'active' : ''; ?>">
                    <a href="<?php echo BASE_URL; ?>/employee/salary.php">
                        <i class="fas fa-money-bill"></i>
                        <span>Gaji Saya</span>
                    </a>
                </li>
            <?php endif; ?>
            
            <li class="logout-item">
                <a href="<?php echo BASE_URL; ?>/auth/logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    
    if (sidebarToggle && sidebar && mainContent) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            mainContent.classList.toggle('sidebar-active');
        });
    }
});
</script> 