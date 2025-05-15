<?php
require_once '../auth/check_session.php';
require_once '../includes/header.php';

// Handle form submission
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_overtime'])) {
    $date = sanitize_input($_POST['date']);
    $start_time = sanitize_input($_POST['start_time']);
    $end_time = sanitize_input($_POST['end_time']);
    $reason = sanitize_input($_POST['reason']);
    $employee_id = $_SESSION['user_id'];
    
    // Calculate hours
    $hours = calculate_overtime_hours($start_time, $end_time);
    
    // Validate inputs
    if(empty($date) || empty($start_time) || empty($end_time) || empty($reason)) {
        set_alert('danger', 'All fields are required');
    } elseif(strtotime($date) > time()) {
        set_alert('danger', 'You cannot enter overtime for future dates');
    } elseif($hours <= 0) {
        set_alert('danger', 'End time must be after start time');
    } else {
        try {
            $stmt = $conn->prepare("
                INSERT INTO overtime (employee_id, date, start_time, end_time, hours, reason) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $result = $stmt->execute([$employee_id, $date, $start_time, $end_time, $hours, $reason]);
            
            if($result) {
                set_alert('success', 'Overtime request submitted successfully');
            } else {
                set_alert('danger', 'Failed to submit overtime request');
            }
        } catch (PDOException $e) {
            set_alert('danger', 'Error: ' . $e->getMessage());
        }
    }
}

// Get overtime records for this employee
$employee_id = $_SESSION['user_id'];
$overtime_records = get_employee_overtime($employee_id);
?>

<!-- Include the admin CSS -->
<link rel="stylesheet" href="../assets/css/admin.css">

<div class="admin-container">
    <div class="page-header">
        <h2><i class="fas fa-business-time"></i> My Overtime</h2>
    </div>
    
    <?php echo display_alert(); ?>
    
    <div class="admin-card">
        <div class="card-header">
            <h3><i class="fas fa-plus-circle"></i> Request Overtime</h3>
        </div>
        <div class="card-body">
            <form method="post" action="" class="admin-form">
                <div class="form-group">
                    <label for="date" class="form-label">Date:</label>
                    <input type="date" name="date" id="date" class="form-input" required max="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label for="start_time" class="form-label">Start Time:</label>
                    <input type="time" name="start_time" id="start_time" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="end_time" class="form-label">End Time:</label>
                    <input type="time" name="end_time" id="end_time" class="form-input" required>
                    <div id="overtime-hours" class="form-note"></div>
                </div>
                <div class="form-group">
                    <label for="reason" class="form-label">Reason for Overtime:</label>
                    <textarea name="reason" id="reason" rows="3" class="form-input" required></textarea>
                    <div class="form-note">Please provide a detailed explanation of why overtime was necessary.</div>
                </div>
                <div class="form-actions">
                    <button type="submit" name="submit_overtime" class="admin-btn admin-btn-primary"><i class="fas fa-save"></i> Submit Request</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="admin-card mt-4">
        <div class="card-header">
            <h3><i class="fas fa-history"></i> My Overtime History</h3>
        </div>
        <div class="card-body">
            <?php if(count($overtime_records) > 0): ?>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Hours</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Submitted</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($overtime_records as $record): ?>
                            <tr>
                                <td><?php echo format_date($record['date']); ?></td>
                                <td><?php echo date('h:i A', strtotime($record['start_time'])); ?></td>
                                <td><?php echo date('h:i A', strtotime($record['end_time'])); ?></td>
                                <td><?php echo $record['hours']; ?> hrs</td>
                                <td><?php echo nl2br(htmlspecialchars($record['reason'])); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $record['status']; ?>">
                                        <?php echo ucfirst($record['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($record['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-inbox"></i>
                    <p>No overtime records found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add JavaScript for calculating overtime hours -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const startTimeInput = document.getElementById('start_time');
    const endTimeInput = document.getElementById('end_time');
    const overtimeHoursDiv = document.getElementById('overtime-hours');
    
    function calculateOvertimeHours() {
        if (startTimeInput.value && endTimeInput.value) {
            const startTime = new Date(`2000-01-01T${startTimeInput.value}`);
            let endTime = new Date(`2000-01-01T${endTimeInput.value}`);
            
            // Handle overnight shifts
            if (endTime < startTime) {
                endTime = new Date(`2000-01-02T${endTimeInput.value}`);
            }
            
            const diffMs = endTime - startTime;
            const diffHrs = diffMs / (1000 * 60 * 60);
            
            overtimeHoursDiv.textContent = `Total Overtime: ${diffHrs.toFixed(2)} hours`;
            
            // Add visual indication of valid/invalid hours
            if (diffHrs <= 0) {
                overtimeHoursDiv.style.color = 'var(--danger-color)';
            } else {
                overtimeHoursDiv.style.color = 'var(--success-color)';
            }
        } else {
            overtimeHoursDiv.textContent = '';
        }
    }
    
    startTimeInput.addEventListener('change', calculateOvertimeHours);
    endTimeInput.addEventListener('change', calculateOvertimeHours);
});
</script>

<?php require_once '../includes/footer.php'; ?> 