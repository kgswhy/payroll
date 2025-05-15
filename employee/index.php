<?php
require_once '../auth/check_session.php';
require_once '../includes/header.php';

// Get employee details
$employee_id = $_SESSION['user_id'];
$employee = get_user_by_id($employee_id);

// Get recent work hours entries
$recent_hours = get_work_hours($employee_id, null);
$recent_hours = array_slice($recent_hours, 0, 5); // Just the last 5 entries

// Get approved work hours for current month
$current_month = date('m');
$current_year = date('Y');
$stmt = $conn->prepare("
    SELECT SUM(TIME_TO_SEC(TIMEDIFF(time_out, time_in)) / 3600) as total_hours
    FROM work_hours 
    WHERE employee_id = ? 
    AND MONTH(date) = ? 
    AND YEAR(date) = ? 
    AND (status = 'approved' OR status = 'corrected')
");
$stmt->execute([$employee_id, $current_month, $current_year]);
$result = $stmt->fetch();
$monthly_hours = round(floatval($result['total_hours'] ?? 0), 2);

// Get recent payroll
$stmt = $conn->prepare("
    SELECT * FROM payroll 
    WHERE employee_id = ? 
    ORDER BY year DESC, month DESC 
    LIMIT 1
");
$stmt->execute([$employee_id]);
$latest_payroll = $stmt->fetch();
?>

<!-- Include the employee dashboard CSS -->
<link rel="stylesheet" href="../assets/css/employee-dashboard.css">

<div class="dashboard-container">
    <div class="dashboard-header">
        <h2><i class="fas fa-tachometer-alt"></i> Employee Dashboard</h2>
    </div>
    
    <div class="dashboard-grid">
        <div class="dashboard-card">
            <div class="card-header">
                <h3><i class="fas fa-clock"></i> Monthly Hours</h3>
            </div>
            <div class="card-body">
                <div class="status-card">
                    <div class="status-icon info">
                        <i class="fas fa-stopwatch"></i>
                    </div>
                    <div class="status-text">
                        <h4><?php echo $monthly_hours; ?> hours</h4>
                        <p>Approved hours this month</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="dashboard-card">
            <div class="card-header">
                <h3><i class="fas fa-money-bill-wave"></i> Base Salary</h3>
            </div>
            <div class="card-body">
                <div class="status-card">
                    <div class="status-icon success">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="status-text">
                        <h4><?php echo format_money($employee['base_salary']); ?></h4>
                        <p>Your current base salary</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="dashboard-card">
            <div class="card-header">
                <h3><i class="fas fa-wallet"></i> Last Salary</h3>
            </div>
            <div class="card-body">
                <div class="status-card">
                    <div class="status-icon warning">
                        <i class="fas fa-hand-holding-usd"></i>
                    </div>
                    <div class="status-text">
                        <h4><?php echo $latest_payroll ? format_money($latest_payroll['net_salary']) : 'N/A'; ?></h4>
                        <p>
                            <?php 
                                if($latest_payroll) {
                                    echo get_month_name($latest_payroll['month']) . ' ' . $latest_payroll['year'];
                                } else {
                                    echo 'No payroll data yet';
                                }
                            ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="dashboard-card">
        <div class="card-header">
            <h3><i class="fas fa-history"></i> Recent Time Entries</h3>
        </div>
        <div class="card-body">
            <?php if(count($recent_hours) > 0): ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time In</th>
                                <th>Time Out</th>
                                <th>Hours</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recent_hours as $wh): ?>
                            <tr>
                                <td><?php echo format_date($wh['date']); ?></td>
                                <td><?php echo date('h:i A', strtotime($wh['time_in'])); ?></td>
                                <td><?php echo date('h:i A', strtotime($wh['time_out'])); ?></td>
                                <td><?php echo calculate_hours($wh['time_in'], $wh['time_out']); ?> hrs</td>
                                <td>
                                    <span class="status-badge <?php echo $wh['status']; ?>">
                                        <?php echo ucfirst($wh['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p style="margin-top: 15px;"><a href="workhours.php">View all time entries</a></p>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-inbox"></i>
                    <p>No recent time entries found. <a href="workhours.php">Add your first time entry</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="dashboard-card">
        <div class="card-header">
            <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
        </div>
        <div class="card-body">
            <div class="quick-actions">
                <a href="workhours.php" class="action-button">
                    <i class="fas fa-clock"></i>
                    <span>Log Work Hours</span>
                </a>
                <a href="salary.php" class="action-button">
                    <i class="fas fa-money-bill"></i>
                    <span>View Salary</span>
                </a>
                <a href="../profile.php" class="action-button">
                    <i class="fas fa-user"></i>
                    <span>My Profile</span>
                </a>
                <a href="../help.php" class="action-button">
                    <i class="fas fa-question-circle"></i>
                    <span>Help & Support</span>
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 
 