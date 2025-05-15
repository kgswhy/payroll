<?php
require_once '../auth/check_session.php';
require_once '../includes/header.php';

// Check if user is a manager
if($_SESSION['user_role'] !== 'manager') {
    redirect('../index.php');
}

// Handle form submission for approval or rejection
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $overtime_id = sanitize_input($_POST['overtime_id']);
    $action = sanitize_input($_POST['action']);
    $manager_id = $_SESSION['user_id'];
    
    // Validate action
    if($action !== 'approve' && $action !== 'reject') {
        set_alert('danger', 'Invalid action');
    } else {
        try {
            $status = ($action === 'approve') ? 'approved' : 'rejected';
            
            $stmt = $conn->prepare("
                UPDATE overtime 
                SET status = ?, reviewed_by = ?, reviewed_at = NOW() 
                WHERE id = ?
            ");
            $result = $stmt->execute([$status, $manager_id, $overtime_id]);
            
            if($result) {
                $message = ($status === 'approved') ? 'Overtime approved successfully' : 'Overtime rejected successfully';
                set_alert('success', $message);
            } else {
                set_alert('danger', 'Failed to update overtime status');
            }
        } catch (PDOException $e) {
            set_alert('danger', 'Error: ' . $e->getMessage());
        }
    }
}

// Get pending overtime requests for this manager
$manager_id = $_SESSION['user_id'];
$overtime_requests = get_pending_overtime($manager_id);
?>

<!-- Include admin CSS -->
<link rel="stylesheet" href="../assets/css/admin.css">
<!-- Include admin utilities -->
<script src="../assets/js/admin-utils.js"></script>

<div class="admin-container">
    <div class="page-header">
        <h2><i class="fas fa-business-time"></i> Approve Overtime</h2>
    </div>
    
    <?php echo display_alert(); ?>
    
    <div class="admin-card">
        <div class="card-header">
            <h3><i class="fas fa-tasks"></i> Pending Overtime Requests</h3>
        </div>
        <div class="card-body">
            <?php if(count($overtime_requests) > 0): ?>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Position</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Hours</th>
                                <th>Reason</th>
                                <th>Submitted</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($overtime_requests as $request): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($request['employee_name']); ?></td>
                                <td><?php echo htmlspecialchars($request['position']); ?></td>
                                <td><?php echo format_date($request['date']); ?></td>
                                <td>
                                    <?php 
                                        echo date('h:i A', strtotime($request['start_time'])) . ' - ' . 
                                             date('h:i A', strtotime($request['end_time'])); 
                                    ?>
                                </td>
                                <td><?php echo $request['hours']; ?> hrs</td>
                                <td><?php echo nl2br(htmlspecialchars($request['reason'])); ?></td>
                                <td><?php echo date('M j, Y', strtotime($request['created_at'])); ?></td>
                                <td class="actions">
                                    <form method="post" class="approve-form">
                                        <input type="hidden" name="overtime_id" value="<?php echo $request['id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="admin-btn admin-btn-success admin-btn-sm" onclick="return confirm('Are you sure you want to approve this overtime request?')">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                    </form>
                                    <form method="post" class="reject-form">
                                        <input type="hidden" name="overtime_id" value="<?php echo $request['id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="admin-btn admin-btn-danger admin-btn-sm" onclick="return confirm('Are you sure you want to reject this overtime request?')">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="admin-alert admin-alert-info">
                    <i class="fas fa-info-circle"></i> No pending overtime requests.
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- View all approved and rejected overtime -->
    <div class="admin-card mt-4">
        <div class="card-header">
            <h3><i class="fas fa-history"></i> Recent Overtime History</h3>
        </div>
        <div class="card-body">
            <div class="filter-controls">
                <select id="status-filter" class="filter-select">
                    <option value="all">All Statuses</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
                <button id="filter-btn" class="admin-btn admin-btn-primary ml-2">Filter</button>
            </div>
            
            <div id="overtime-history">
                <!-- AJAX loaded content will appear here -->
                <div class="text-center py-3">
                    <p>Select a filter and click "Filter" to view overtime history.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle filter button click
    document.getElementById('filter-btn').addEventListener('click', function() {
        const status = document.getElementById('status-filter').value;
        loadOvertimeHistory(status);
    });
    
    function loadOvertimeHistory(status) {
        const historyContainer = document.getElementById('overtime-history');
        showLoading(historyContainer);
        
        // Call the API to get overtime history
        fetchWithErrorHandling(`../api/get_overtime_history.php?status=${status}`)
            .then(data => {
                if (data.count === 0) {
                    showEmptyState(historyContainer, 'No overtime records found with the selected filter.', 'clock');
                    return;
                }
                
                // Create table with overtime history
                let html = `
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Position</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Hours</th>
                                    <th>Status</th>
                                    <th>Reviewed By</th>
                                    <th>Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                data.data.forEach(record => {
                    html += `
                        <tr>
                            <td>${record.employee.name}</td>
                            <td>${record.employee.position}</td>
                            <td>${record.date}</td>
                            <td>${record.time}</td>
                            <td>${record.hours} hrs</td>
                            <td><span class="status-badge ${record.status}">${record.status.charAt(0).toUpperCase() + record.status.slice(1)}</span></td>
                            <td>${record.reviewed_by || '-'}</td>
                            <td>${record.reason}</td>
                        </tr>
                    `;
                });
                
                html += `
                            </tbody>
                        </table>
                    </div>
                `;
                
                historyContainer.innerHTML = html;
            })
            .catch(error => {
                showError(historyContainer, 'Failed to load overtime history: ' + error.message);
            });
    }
    
    // Load all overtime history on page load
    loadOvertimeHistory('all');
});
</script>

<?php require_once '../includes/footer.php'; ?>