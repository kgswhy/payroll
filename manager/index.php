<?php
require_once '../auth/check_session.php';
require_once '../includes/header.php';

// Get manager details
$manager_id = $_SESSION['user_id'];

// Get employees managed by this manager
$employees = get_employees_by_manager($manager_id);
$employee_count = count($employees);

// Get pending work hours for employees managed by this manager
$pending_hours = get_pending_work_hours($manager_id);
$pending_count = count($pending_hours);

// Get recent approved entries
$stmt = $conn->prepare("
    SELECT wh.*, u.name as employee_name 
    FROM work_hours wh 
    JOIN users u ON wh.employee_id = u.id 
    WHERE u.manager_id = ? 
    AND wh.reviewed_by = ? 
    AND (wh.status = 'approved' OR wh.status = 'corrected' OR wh.status = 'rejected') 
    ORDER BY wh.created_at DESC 
    LIMIT 5
");
$stmt->execute([$manager_id, $manager_id]);
$recent_approvals = $stmt->fetchAll();
?>

<div class="container">
    <h2>Manager Dashboard</h2>
    
    <div class="dashboard-cards">
        <div class="card">
            <h3>My Team</h3>
            <p class="big-number"><?php echo $employee_count; ?></p>
            <p>Employees</p>
        </div>
        <div class="card">
            <h3>Pending Approvals</h3>
            <p class="big-number"><?php echo $pending_count; ?></p>
            <p>Work hour entries to review</p>
        </div>
        <div class="card">
            <h3>Current Month</h3>
            <p class="big-number"><?php echo date('F Y'); ?></p>
        </div>
    </div>
    
    <?php if($pending_count > 0): ?>
    <div class="pending-approvals">
        <h3>Pending Approvals</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Date</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                    <th>Hours</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach(array_slice($pending_hours, 0, 5) as $ph): ?>
                <tr>
                    <td><?php echo $ph['employee_name']; ?></td>
                    <td><?php echo format_date($ph['date']); ?></td>
                    <td><?php echo date('h:i A', strtotime($ph['time_in'])); ?></td>
                    <td><?php echo date('h:i A', strtotime($ph['time_out'])); ?></td>
                    <td><?php echo calculate_hours($ph['time_in'], $ph['time_out']); ?> hrs</td>
                    <td>
                        <a href="../manager/review_workhours.php" class="button">Review</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if($pending_count > 5): ?>
            <p><a href="../manager/review_workhours.php">View all pending approvals</a></p>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <div class="my-team">
        <h3>My Team</h3>
        <?php if($employee_count > 0): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Position</th>
                        <th>Email</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($employees as $emp): ?>
                    <tr>
                        <td><?php echo $emp['name']; ?></td>
                        <td><?php echo $emp['position']; ?></td>
                        <td><?php echo $emp['email']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No employees assigned to you yet.</p>
        <?php endif; ?>
    </div>
    
    <div class="recent-activity">
        <h3>Recent Approvals</h3>
        <?php if(count($recent_approvals) > 0): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Date</th>
                        <th>Hours</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($recent_approvals as $ra): ?>
                    <tr>
                        <td><?php echo $ra['employee_name']; ?></td>
                        <td><?php echo format_date($ra['date']); ?></td>
                        <td><?php echo calculate_hours($ra['time_in'], $ra['time_out']); ?> hrs</td>
                        <td>
                            <?php 
                                switch($ra['status']) {
                                    case 'approved': echo '<span class="text-success">Approved</span>'; break;
                                    case 'rejected': echo '<span class="text-danger">Rejected</span>'; break;
                                    case 'corrected': echo '<span class="text-warning">Corrected</span>'; break;
                                    default: echo '<span class="text-muted">Unknown</span>';
                                }
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No recent approvals found.</p>
        <?php endif; ?>
    </div>
    
    <div class="quick-actions">
        <h3>Quick Actions</h3>
        <div class="action-buttons">
            <a href="../manager/review_workhours.php" class="button">
                <i class="fas fa-clock"></i> Review Work Hours
            </a>
            <a href="../manager/approve_overtime.php" class="button">
                <i class="fas fa-plus-circle"></i> Approve Overtime
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