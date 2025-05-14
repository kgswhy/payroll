<?php
require_once '../auth/check_session.php';
require_once '../includes/header.php';

// Process approvals/rejections
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(isset($_POST['approve_hours'])) {
        $id = intval($_POST['id']);
        $notes = sanitize_input($_POST['notes']);
        
        try {
            $stmt = $conn->prepare("
                UPDATE work_hours 
                SET status = 'approved', notes = CONCAT(IFNULL(notes, ''), '\n[Manager: ', ?, ']'), reviewed_by = ? 
                WHERE id = ?
            ");
            $result = $stmt->execute([$notes, $_SESSION['user_id'], $id]);
            
            if($result) {
                set_alert('success', 'Work hours approved successfully');
            } else {
                set_alert('danger', 'Failed to approve work hours');
            }
        } catch (PDOException $e) {
            set_alert('danger', 'Error: ' . $e->getMessage());
        }
    } elseif(isset($_POST['reject_hours'])) {
        $id = intval($_POST['id']);
        $notes = sanitize_input($_POST['notes']);
        
        if(empty($notes)) {
            set_alert('danger', 'Please provide a reason for rejection');
        } else {
            try {
                $stmt = $conn->prepare("
                    UPDATE work_hours 
                    SET status = 'rejected', notes = CONCAT(IFNULL(notes, ''), '\n[Manager: ', ?, ']'), reviewed_by = ? 
                    WHERE id = ?
                ");
                $result = $stmt->execute([$notes, $_SESSION['user_id'], $id]);
                
                if($result) {
                    set_alert('success', 'Work hours rejected');
                } else {
                    set_alert('danger', 'Failed to reject work hours');
                }
            } catch (PDOException $e) {
                set_alert('danger', 'Error: ' . $e->getMessage());
            }
        }
    } elseif(isset($_POST['correct_hours'])) {
        $id = intval($_POST['id']);
        $time_in = sanitize_input($_POST['time_in']);
        $time_out = sanitize_input($_POST['time_out']);
        $notes = sanitize_input($_POST['notes']);
        
        try {
            $stmt = $conn->prepare("
                UPDATE work_hours 
                SET time_in = ?, time_out = ?, status = 'corrected', 
                    notes = CONCAT(IFNULL(notes, ''), '\n[Manager Correction: ', ?, ']'), 
                    reviewed_by = ? 
                WHERE id = ?
            ");
            $result = $stmt->execute([$time_in, $time_out, $notes, $_SESSION['user_id'], $id]);
            
            if($result) {
                set_alert('success', 'Work hours corrected');
            } else {
                set_alert('danger', 'Failed to correct work hours');
            }
        } catch (PDOException $e) {
            set_alert('danger', 'Error: ' . $e->getMessage());
        }
    }
}

// Get pending work hours for employees managed by this manager
$manager_id = $_SESSION['user_id'];
$pending_hours = get_pending_work_hours($manager_id);
?>

<div class="container">
    <h2>Review Work Hours</h2>
    
    <?php if(count($pending_hours) > 0): ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Date</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                    <th>Hours</th>
                    <th>Notes</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($pending_hours as $ph): ?>
                <tr>
                    <td><?php echo $ph['employee_name']; ?></td>
                    <td><?php echo format_date($ph['date']); ?></td>
                    <td><?php echo date('h:i A', strtotime($ph['time_in'])); ?></td>
                    <td><?php echo date('h:i A', strtotime($ph['time_out'])); ?></td>
                    <td><?php echo calculate_hours($ph['time_in'], $ph['time_out']); ?> hrs</td>
                    <td><?php echo $ph['notes']; ?></td>
                    <td>
                        <button type="button" onclick="showApprovalForm(<?php echo $ph['id']; ?>)">Approve</button>
                        <button type="button" onclick="showRejectionForm(<?php echo $ph['id']; ?>)">Reject</button>
                        <button type="button" onclick="showCorrectionForm(<?php 
                            echo htmlspecialchars(json_encode([
                                'id' => $ph['id'],
                                'time_in' => $ph['time_in'],
                                'time_out' => $ph['time_out']
                            ]), ENT_QUOTES, 'UTF-8'); 
                        ?>)">Correct</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Approval Form (Hidden by default) -->
        <div id="approval-form" style="display: none;" class="modal-form">
            <h3>Approve Work Hours</h3>
            <form method="post" action="">
                <input type="hidden" name="id" id="approve_id">
                <div class="form-group">
                    <label for="approve_notes">Comments (optional):</label>
                    <textarea name="notes" id="approve_notes" rows="3"></textarea>
                </div>
                <button type="submit" name="approve_hours">Confirm Approval</button>
                <button type="button" onclick="hideApprovalForm()">Cancel</button>
            </form>
        </div>
        
        <!-- Rejection Form (Hidden by default) -->
        <div id="rejection-form" style="display: none;" class="modal-form">
            <h3>Reject Work Hours</h3>
            <form method="post" action="">
                <input type="hidden" name="id" id="reject_id">
                <div class="form-group">
                    <label for="reject_notes">Reason for Rejection (required):</label>
                    <textarea name="notes" id="reject_notes" rows="3" required></textarea>
                </div>
                <button type="submit" name="reject_hours">Confirm Rejection</button>
                <button type="button" onclick="hideRejectionForm()">Cancel</button>
            </form>
        </div>
        
        <!-- Correction Form (Hidden by default) -->
        <div id="correction-form" style="display: none;" class="modal-form">
            <h3>Correct Work Hours</h3>
            <form method="post" action="">
                <input type="hidden" name="id" id="correct_id">
                <div class="form-group">
                    <label for="correct_time_in">Time In:</label>
                    <input type="time" name="time_in" id="correct_time_in" required>
                </div>
                <div class="form-group">
                    <label for="correct_time_out">Time Out:</label>
                    <input type="time" name="time_out" id="correct_time_out" required>
                </div>
                <div class="form-group">
                    <label for="correct_notes">Reason for Correction:</label>
                    <textarea name="notes" id="correct_notes" rows="3" required></textarea>
                </div>
                <button type="submit" name="correct_hours">Confirm Correction</button>
                <button type="button" onclick="hideCorrectionForm()">Cancel</button>
            </form>
        </div>
    <?php else: ?>
        <p>No pending work hours to review.</p>
    <?php endif; ?>
</div>

<style>
.modal-form {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background-color: white;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 0 10px rgba(0,0,0,0.3);
    z-index: 1000;
    width: 80%;
    max-width: 500px;
}
</style>

<script>
function showApprovalForm(id) {
    document.getElementById('approve_id').value = id;
    document.getElementById('approval-form').style.display = 'block';
}

function hideApprovalForm() {
    document.getElementById('approval-form').style.display = 'none';
}

function showRejectionForm(id) {
    document.getElementById('reject_id').value = id;
    document.getElementById('rejection-form').style.display = 'block';
}

function hideRejectionForm() {
    document.getElementById('rejection-form').style.display = 'none';
}

function showCorrectionForm(data) {
    document.getElementById('correct_id').value = data.id;
    document.getElementById('correct_time_in').value = data.time_in;
    document.getElementById('correct_time_out').value = data.time_out;
    document.getElementById('correction-form').style.display = 'block';
}

function hideCorrectionForm() {
    document.getElementById('correction-form').style.display = 'none';
}
</script>

<?php require_once '../includes/footer.php'; ?> 