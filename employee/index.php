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

<div class="container">
    <h2>Employee Dashboard</h2>
    
    <div class="dashboard-cards">
        <div class="card">
            <h3>Monthly Hours</h3>
            <p class="big-number"><?php echo $monthly_hours; ?></p>
            <p>Approved hours this month</p>
        </div>
        <div class="card">
            <h3>Base Salary</h3>
            <p class="big-number"><?php echo format_money($employee['base_salary']); ?></p>
        </div>
        <div class="card">
            <h3>Last Salary</h3>
            <p class="big-number">
                <?php echo $latest_payroll ? format_money($latest_payroll['net_salary']) : 'N/A'; ?>
            </p>
            <p>
                <?php 
                    if($latest_payroll) {
                        echo get_month_name($latest_payroll['month']) . ' ' . $latest_payroll['year'];
                    } 
                ?>
            </p>
        </div>
    </div>
    
    <div class="recent-entries">
        <h3>Recent Time Entries</h3>
        <?php if(count($recent_hours) > 0): ?>
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
                            <?php 
                                switch($wh['status']) {
                                    case 'approved': echo '<span class="text-success">Approved</span>'; break;
                                    case 'rejected': echo '<span class="text-danger">Rejected</span>'; break;
                                    case 'corrected': echo '<span class="text-warning">Corrected</span>'; break;
                                    default: echo '<span class="text-muted">Pending</span>';
                                }
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p><a href="workhours.php">View all time entries</a></p>
        <?php else: ?>
            <p>No recent time entries found. <a href="workhours.php">Add your first time entry</a></p>
        <?php endif; ?>
    </div>
    
    <div class="quick-actions">
        <h3>Quick Actions</h3>
        <div class="action-buttons">
            <a href="workhours.php" class="button">
                <i class="fas fa-clock"></i> Log Work Hours
            </a>
            <a href="salary.php" class="button">
                <i class="fas fa-money-bill"></i> View Salary
            </a>
        </div>
    </div>
</div>

<style>
.action-buttons {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

.button {
    display: inline-block;
    padding: 10px 15px;
    background-color: #3498db;
    color: white;
    text-decoration: none;
    border-radius: 4px;
}

.button:hover {
    background-color: #2980b9;
}

.text-success { color: #4CAF50; }
.text-danger { color: #e74c3c; }
.text-warning { color: #f39c12; }
.text-muted { color: #7f8c8d; }
</style>

<?php require_once '../includes/footer.php'; ?> 
 