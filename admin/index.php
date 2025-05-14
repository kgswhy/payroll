<?php
require_once '../auth/check_session.php';
require_once '../includes/header.php';

// Get counts for dashboard
$employee_count = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'employee'")->fetchColumn();
$pending_hours = $conn->query("SELECT COUNT(*) FROM work_hours WHERE status = 'pending'")->fetchColumn();
$processed_payroll = $conn->query("SELECT COUNT(*) FROM payroll WHERE status = 'finalized'")->fetchColumn();

// Get recent activity
$stmt = $conn->query("
    SELECT 'Work Hour' as type, wh.id, wh.date, wh.status, u.name as user_name 
    FROM work_hours wh 
    JOIN users u ON wh.employee_id = u.id 
    ORDER BY wh.created_at DESC 
    LIMIT 5
");
$recent_activities = $stmt->fetchAll();
?>

<div class="container">
    <h2>Admin Dashboard</h2>
    
    <div class="dashboard-cards">
        <div class="card">
            <h3>Total Employees</h3>
            <p class="big-number"><?php echo $employee_count; ?></p>
        </div>
        <div class="card">
            <h3>Pending Approvals</h3>
            <p class="big-number"><?php echo $pending_hours; ?></p>
        </div>
        <div class="card">
            <h3>Payrolls Processed</h3>
            <p class="big-number"><?php echo $processed_payroll; ?></p>
        </div>
    </div>
    
    <div class="recent-activity">
        <h3>Recent Activity</h3>
        <?php if(count($recent_activities) > 0): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>User</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($recent_activities as $activity): ?>
                    <tr>
                        <td><?php echo $activity['type']; ?></td>
                        <td><?php echo $activity['user_name']; ?></td>
                        <td><?php echo format_date($activity['date']); ?></td>
                        <td><?php echo ucfirst($activity['status']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No recent activity found.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 