<?php
require_once '../auth/check_session.php';
require_once '../includes/header.php';

// Handle form submission
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_hours'])) {
    $date = sanitize_input($_POST['date']);
    $time_in = sanitize_input($_POST['time_in']);
    $time_out = sanitize_input($_POST['time_out']);
    $notes = sanitize_input($_POST['notes']);
    $employee_id = $_SESSION['user_id'];
    
    // Validate inputs
    if(empty($date) || empty($time_in) || empty($time_out)) {
        set_alert('danger', 'Date, time in, and time out are required');
    } elseif(strtotime($date) > time()) {
        set_alert('danger', 'You cannot enter work hours for future dates');
    } else {
        try {
            $stmt = $conn->prepare("
                INSERT INTO work_hours (employee_id, date, time_in, time_out, notes) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $result = $stmt->execute([$employee_id, $date, $time_in, $time_out, $notes]);
            
            if($result) {
                set_alert('success', 'Work hours submitted successfully');
            } else {
                set_alert('danger', 'Failed to submit work hours');
            }
        } catch (PDOException $e) {
            set_alert('danger', 'Error: ' . $e->getMessage());
        }
    }
}

// Get work hours for this employee
$employee_id = $_SESSION['user_id'];
$work_hours = get_work_hours($employee_id);
?>

<!-- Include the employee workhours CSS -->
<link rel="stylesheet" href="../assets/css/employee-workhours.css">

<div class="workhours-container">
    <div class="page-header">
        <h2><i class="fas fa-clock"></i> My Work Hours</h2>
    </div>
    
    <div class="workhours-card">
        <div class="card-header">
            <h3><i class="fas fa-plus-circle"></i> Log Work Hours</h3>
        </div>
        <div class="card-body">
            <form method="post" action="" class="form-container">
                <div class="form-group">
                    <label for="date" class="form-label">Date:</label>
                    <input type="date" name="date" id="date" class="form-input" required max="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label for="time_in" class="form-label">Time In:</label>
                    <input type="time" name="time_in" id="time_in" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="time_out" class="form-label">Time Out:</label>
                    <input type="time" name="time_out" id="time_out" class="form-input" required>
                    <div id="total-hours" class="form-note"></div>
                </div>
                <div class="form-group">
                    <label for="notes" class="form-label">Notes (optional):</label>
                    <textarea name="notes" id="notes" rows="3" class="form-input"></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" name="submit_hours" class="btn btn-primary"><i class="fas fa-save"></i> Submit Hours</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="workhours-card">
        <div class="card-header">
            <h3><i class="fas fa-history"></i> My Work History</h3>
        </div>
        <div class="card-body">
            <?php if(count($work_hours) > 0): ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time In</th>
                                <th>Time Out</th>
                                <th>Hours</th>
                                <th>Status</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($work_hours as $wh): ?>
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
                                <td><?php echo $wh['notes']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-inbox"></i>
                    <p>No work hours recorded yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add JavaScript for calculating total hours -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const timeInInput = document.getElementById('time_in');
    const timeOutInput = document.getElementById('time_out');
    const totalHoursDiv = document.getElementById('total-hours');
    
    function calculateTotalHours() {
        if (timeInInput.value && timeOutInput.value) {
            const timeIn = new Date(`2000-01-01T${timeInInput.value}`);
            let timeOut = new Date(`2000-01-01T${timeOutInput.value}`);
            
            // Handle overnight shifts
            if (timeOut < timeIn) {
                timeOut = new Date(`2000-01-02T${timeOutInput.value}`);
            }
            
            const diffMs = timeOut - timeIn;
            const diffHrs = diffMs / (1000 * 60 * 60);
            
            totalHoursDiv.textContent = `Total: ${diffHrs.toFixed(2)} hours`;
        } else {
            totalHoursDiv.textContent = '';
        }
    }
    
    timeInInput.addEventListener('change', calculateTotalHours);
    timeOutInput.addEventListener('change', calculateTotalHours);
});
</script>

<?php require_once '../includes/footer.php'; ?> 