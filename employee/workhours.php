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

<div class="container">
    <h2>My Work Hours</h2>
    
    <div class="work-hours-form">
        <h3>Log Work Hours</h3>
        <form method="post" action="">
            <div class="form-group">
                <label for="date">Date:</label>
                <input type="date" name="date" id="date" required max="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="form-group">
                <label for="time_in">Time In:</label>
                <input type="time" name="time_in" id="time_in" required>
            </div>
            <div class="form-group">
                <label for="time_out">Time Out:</label>
                <input type="time" name="time_out" id="time_out" required>
            </div>
            <div class="form-group">
                <div id="total-hours"></div>
            </div>
            <div class="form-group">
                <label for="notes">Notes (optional):</label>
                <textarea name="notes" id="notes" rows="3"></textarea>
            </div>
            <button type="submit" name="submit_hours">Submit Hours</button>
        </form>
    </div>
    
    <div class="work-hours-history">
        <h3>My Work History</h3>
        <?php if(count($work_hours) > 0): ?>
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
                            <?php 
                                $status_class = '';
                                switch($wh['status']) {
                                    case 'approved': $status_class = 'text-success'; break;
                                    case 'rejected': $status_class = 'text-danger'; break;
                                    case 'corrected': $status_class = 'text-warning'; break;
                                    default: $status_class = 'text-muted';
                                }
                                echo '<span class="' . $status_class . '">' . ucfirst($wh['status']) . '</span>';
                            ?>
                        </td>
                        <td><?php echo $wh['notes']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No work hours recorded yet.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 