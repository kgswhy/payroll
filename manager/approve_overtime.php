<?php
require_once '../auth/check_session.php';
require_once '../includes/header.php';

// Get manager details
$manager_id = $_SESSION['user_id'];

// Process overtime approval
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve_overtime'])) {
        $work_hour_id = intval($_POST['id']);
        $notes = sanitize_input($_POST['notes']);

        try {
            $stmt = $conn->prepare("
                UPDATE work_hours 
                SET status = 'approved', notes = CONCAT(IFNULL(notes, ''), '\n[Overtime Approved: ', ?, ']'), reviewed_by = ? 
                WHERE id = ?
            ");
            $result = $stmt->execute([$notes, $manager_id, $work_hour_id]);

            if ($result) {
                set_alert('success', 'Overtime approved successfully');
            } else {
                set_alert('danger', 'Failed to approve overtime');
            }
        } catch (PDOException $e) {
            set_alert('danger', 'Error: ' . $e->getMessage());
        }
    } elseif (isset($_POST['reject_overtime'])) {
        $work_hour_id = intval($_POST['id']);
        $notes = sanitize_input($_POST['notes']);

        try {
            $stmt = $conn->prepare("
                UPDATE work_hours 
                SET status = 'rejected', notes = CONCAT(IFNULL(notes, ''), '\n[Overtime Rejected: ', ?, ']'), reviewed_by = ? 
                WHERE id = ?
            ");
            $result = $stmt->execute([$notes, $manager_id, $work_hour_id]);

            if ($result) {
                set_alert('success', 'Overtime rejected');
            } else {
                set_alert('danger', 'Failed to reject overtime');
            }
        } catch (PDOException $e) {
            set_alert('danger', 'Error: ' . $e->getMessage());
        }
    }
}

// Get potential overtime entries (more than 8 hours in a day)
$stmt = $conn->prepare("
    SELECT wh.*, u.name as employee_name,
    TIME_TO_SEC(TIMEDIFF(wh.time_out, wh.time_in)) / 3600 as hours_worked
    FROM work_hours wh 
    JOIN users u ON wh.employee_id = u.id 
    WHERE u.manager_id = ? 
    AND TIME_TO_SEC(TIMEDIFF(wh.time_out, wh.time_in)) / 3600 > 8
    AND wh.status = 'pending'
    ORDER BY wh.date DESC
");
$stmt->execute([$manager_id]);
$overtime_entries = $stmt->fetchAll();
?>

<div class="container">
    <h2>Approve Overtime</h2>

    <?php if (count($overtime_entries) > 0): ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Date</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                    <th>Total Hours</th>
                    <th>Overtime Hours</th>
                    <th>Notes</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($overtime_entries as $oe): ?>
                    <?php
                    $total_hours = $oe['hours_worked'];
                    $overtime_hours = $total_hours - 8; // Hours above standard 8-hour day
                    ?>
                    <tr>
                        <td><?php echo $oe['employee_name']; ?></td>
                        <td><?php echo format_date($oe['date']); ?></td>
                        <td><?php echo date('h:i A', strtotime($oe['time_in'])); ?></td>
                        <td><?php echo date('h:i A', strtotime($oe['time_out'])); ?></td>
                        <td><?php echo round($total_hours, 2); ?> hrs</td>
                        <td><strong><?php echo round($overtime_hours, 2); ?> hrs</strong></td>
                        <td><?php echo $oe['notes']; ?></td>
                        <td>
                            <button type="button" onclick="showApprovalForm(<?php echo $oe['id']; ?>)">Approve</button>
                            <button type="button" onclick="showRejectionForm(<?php echo $oe['id']; ?>)">Reject</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Approval Form (Hidden by default) -->
        <div id="overtime-approval-form" style="display: none;" class="modal-form">
            <h3>Approve Overtime</h3>
            <form method="post" action="">
                <input type="hidden" name="id" id="approve_id">
                <div class="form-group">
                    <label for="approve_notes">Comments (optional):</label>
                    <textarea name="notes" id="approve_notes" rows="3"></textarea>
                </div>
                <button type="submit" name="approve_overtime">Confirm Approval</button>
                <button type="button" onclick="hideApprovalForm()">Cancel</button>
            </form>
        </div>

        <!-- Rejection Form (Hidden by default) -->
        <div id="overtime-rejection-form" style="display: none;" class="modal-form">
            <h3>Reject Overtime</h3>
            <form method="post" action="">
                <input type="hidden" name="id" id="reject_id">
                <div class="form-group">
                    <label for="reject_notes">Reason for Rejection (required):</label>
                    <textarea name="notes" id="reject_notes" rows="3" required></textarea>
                </div>
                <button type="submit" name="reject_overtime">Confirm Rejection</button>
                <button type="button" onclick="hideRejectionForm()">Cancel</button>
            </form>
        </div>
    <?php else: ?>
        <p>No pending overtime entries to review.</p>
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
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
        z-index: 1000;
        width: 80%;
        max-width: 500px;
    }
</style>

<script>
    function showApprovalForm(id) {
        document.getElementById('approve_id').value = id;
        document.getElementById('overtime-approval-form').style.display = 'block';
    }

    function hideApprovalForm() {
        document.getElementById('overtime-approval-form').style.display = 'none';
    }

    function showRejectionForm(id) {
        document.getElementById('reject_id').value = id;
        document.getElementById('overtime-rejection-form').style.display = 'block';
    }

    function hideRejectionForm() {
        document.getElementById('overtime-rejection-form').style.display = 'none';
    }
</script>

<?php require_once '../includes/footer.php'; ?>